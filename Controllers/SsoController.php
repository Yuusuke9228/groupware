<?php
namespace Controllers;

use Core\Auth;
use Core\Controller;
use Services\SsoService;

class SsoController extends Controller
{
    private $ssoService;
    private $authService;

    public function __construct()
    {
        parent::__construct();
        $this->ssoService = new SsoService();
        $this->authService = Auth::getInstance();
    }

    public function oidcLogin()
    {
        $config = $this->ssoService->getConfig();
        if (!$config['sso_enabled'] || !$config['oidc_enabled']) {
            $_SESSION['login_error'] = 'OIDCログインは無効です。';
            $this->redirect(BASE_PATH . '/login');
            return;
        }

        if ($config['oidc_issuer'] === '' || $config['oidc_client_id'] === '') {
            $_SESSION['login_error'] = 'OIDC設定が未完了です。管理者にお問い合わせください。';
            $this->redirect(BASE_PATH . '/login');
            return;
        }

        $redirect = trim((string)($_GET['redirect'] ?? ''));
        if ($redirect !== '') {
            $_SESSION['sso_redirect_after_login'] = $redirect;
        }

        try {
            $client = $this->buildOidcClient($config);
            $client->authenticate();
            $this->redirect(BASE_PATH . '/');
        } catch (\Throwable $e) {
            $this->ssoService->logAuthFailure('oidc', 'login', $e->getMessage());
            $_SESSION['login_error'] = 'OIDCログイン開始に失敗しました。';
            $this->redirect(BASE_PATH . '/login');
        }
    }

    public function oidcCallback()
    {
        $config = $this->ssoService->getConfig();
        if (!$config['sso_enabled'] || !$config['oidc_enabled']) {
            $_SESSION['login_error'] = 'OIDCログインは無効です。';
            $this->redirect(BASE_PATH . '/login');
            return;
        }

        try {
            $client = $this->buildOidcClient($config);
            $authenticated = $client->authenticate();
            if (!$authenticated) {
                throw new \RuntimeException('OIDC authentication failed');
            }

            $claims = [];
            $userInfo = $client->requestUserInfo();
            if (is_object($userInfo)) {
                $claims = (array)$userInfo;
            }

            $idTokenPayload = $client->getIdTokenPayload();
            if (is_object($idTokenPayload)) {
                $claims = array_merge((array)$idTokenPayload, $claims);
            }

            $subject = (string)($claims['sub'] ?? '');
            [$user, $error] = $this->ssoService->resolveOrProvisionUser('oidc', $subject, $claims);
            if ($user === null) {
                $this->ssoService->logAuthFailure('oidc', 'callback', $error ?: 'user resolve failed');
                $_SESSION['login_error'] = $error ?: 'OIDCログインに失敗しました。';
                $this->redirect(BASE_PATH . '/login');
                return;
            }

            $this->ssoService->finalizeLogin($user, 'oidc', 'callback');
            $redirect = trim((string)($_SESSION['sso_redirect_after_login'] ?? ''));
            unset($_SESSION['sso_redirect_after_login']);
            $this->redirect($this->safeRedirect($redirect));
        } catch (\Throwable $e) {
            $this->ssoService->logAuthFailure('oidc', 'callback', $e->getMessage());
            $_SESSION['login_error'] = 'OIDCログインに失敗しました。';
            $this->redirect(BASE_PATH . '/login');
        }
    }

    public function samlLogin()
    {
        $config = $this->ssoService->getConfig();
        if (!$config['sso_enabled'] || !$config['saml_enabled']) {
            $_SESSION['login_error'] = 'SAMLログインは無効です。';
            $this->redirect(BASE_PATH . '/login');
            return;
        }

        $redirect = trim((string)($_GET['redirect'] ?? ''));
        if ($redirect !== '') {
            $_SESSION['sso_redirect_after_login'] = $redirect;
        }

        try {
            $auth = $this->buildSamlAuth($config);
            $loginUrl = $auth->login(null, [], false, false, true);
            header('Location: ' . $loginUrl);
            exit;
        } catch (\Throwable $e) {
            $this->ssoService->logAuthFailure('saml', 'login', $e->getMessage());
            $_SESSION['login_error'] = 'SAMLログイン開始に失敗しました。';
            $this->redirect(BASE_PATH . '/login');
        }
    }

    public function samlAcs()
    {
        $config = $this->ssoService->getConfig();
        if (!$config['sso_enabled'] || !$config['saml_enabled']) {
            $_SESSION['login_error'] = 'SAMLログインは無効です。';
            $this->redirect(BASE_PATH . '/login');
            return;
        }

        try {
            $auth = $this->buildSamlAuth($config);
            $auth->processResponse();
            $errors = $auth->getErrors();
            if (!empty($errors) || !$auth->isAuthenticated()) {
                $reason = $auth->getLastErrorReason();
                throw new \RuntimeException('SAML response error: ' . implode(', ', $errors) . ' ' . $reason);
            }

            $attributes = [];
            foreach ((array)$auth->getAttributes() as $key => $values) {
                if (is_array($values)) {
                    $attributes[$key] = count($values) === 1 ? (string)$values[0] : $values;
                } else {
                    $attributes[$key] = $values;
                }
            }

            $subject = (string)$auth->getNameId();
            if ($subject === '') {
                $subject = (string)($attributes[$config['attr_email']] ?? '');
            }

            [$user, $error] = $this->ssoService->resolveOrProvisionUser('saml', $subject, $attributes);
            if ($user === null) {
                $this->ssoService->logAuthFailure('saml', 'acs', $error ?: 'user resolve failed');
                $_SESSION['login_error'] = $error ?: 'SAMLログインに失敗しました。';
                $this->redirect(BASE_PATH . '/login');
                return;
            }

            $this->ssoService->finalizeLogin($user, 'saml', 'acs');
            $redirect = trim((string)($_SESSION['sso_redirect_after_login'] ?? ''));
            unset($_SESSION['sso_redirect_after_login']);
            $this->redirect($this->safeRedirect($redirect));
        } catch (\Throwable $e) {
            $this->ssoService->logAuthFailure('saml', 'acs', $e->getMessage());
            $_SESSION['login_error'] = 'SAMLログインに失敗しました。';
            $this->redirect(BASE_PATH . '/login');
        }
    }

    public function samlMetadata()
    {
        $config = $this->ssoService->getConfig();
        if (!$config['saml_enabled']) {
            http_response_code(404);
            echo 'SAML is disabled.';
            exit;
        }

        try {
            $this->ensureVendorAutoload();
            $settings = new \OneLogin\Saml2\Settings($this->buildSamlSettingsArray($config), true);
            $metadata = $settings->getSPMetadata();
            $errors = $settings->validateMetadata($metadata);
            if (!empty($errors)) {
                throw new \RuntimeException('Metadata validation failed: ' . implode(', ', $errors));
            }

            header('Content-Type: application/samlmetadata+xml; charset=utf-8');
            echo $metadata;
            exit;
        } catch (\Throwable $e) {
            http_response_code(500);
            echo 'Failed to generate SAML metadata.';
            exit;
        }
    }

    public function localAdminLoginForm()
    {
        if ($this->authService->check()) {
            $this->redirect(BASE_PATH . '/');
            return;
        }

        if (!$this->authService->isAdminIpAllowed()) {
            $_SESSION['login_error'] = tr_text(
                '管理者ログインは許可されたネットワークからのみ利用できます。',
                'Administrator login is allowed only from approved networks.'
            );
            $this->redirect(BASE_PATH . '/login');
            return;
        }

        \Core\RuntimeI18n::renderPhp(__DIR__ . '/../views/auth/login.php', [
            'localAdminOnly' => true,
        ]);
    }

    public function localAdminLoginPost()
    {
        $username = trim((string)($_POST['username'] ?? ''));
        $password = trim((string)($_POST['password'] ?? ''));
        $remember = isset($_POST['remember']);

        if (!$this->authService->isAdminIpAllowed()) {
            $_SESSION['login_error'] = tr_text(
                '管理者ログインは許可されたネットワークからのみ利用できます。',
                'Administrator login is allowed only from approved networks.'
            );
            $this->redirect(BASE_PATH . '/login');
            return;
        }

        if (!$this->authService->login($username, $password, $remember) || !$this->authService->isAdmin()) {
            if ($this->authService->check() && !$this->authService->isAdmin()) {
                $this->authService->logout();
            }
            $_SESSION['login_error'] = $this->authService->getLastError() ?: '非常用ログインは管理者アカウントのみ利用できます。';
            $this->redirect(BASE_PATH . '/login/local-admin');
            return;
        }

        $this->redirect(BASE_PATH . '/');
    }

    private function buildOidcClient(array $config)
    {
        $this->ensureVendorAutoload();
        $client = new \Jumbojett\OpenIDConnectClient(
            $config['oidc_issuer'],
            $config['oidc_client_id'],
            $config['oidc_client_secret']
        );

        $redirectUri = $config['oidc_redirect_uri'] !== ''
            ? $config['oidc_redirect_uri']
            : $this->ssoService->getAbsoluteUrl(ltrim(BASE_PATH . '/auth/oidc/callback', '/'));

        $client->setRedirectURL($redirectUri);
        $client->setVerifyHost(true);
        $client->setVerifyPeer(true);
        $client->setCodeChallengeMethod('S256');

        $scopes = preg_split('/\s+/', trim($config['oidc_scopes']));
        if (is_array($scopes) && !empty($scopes)) {
            $client->addScope(array_values(array_filter($scopes)));
        }

        $providerConfig = [];
        if ($config['oidc_authorization_endpoint'] !== '') {
            $providerConfig['authorization_endpoint'] = $config['oidc_authorization_endpoint'];
        }
        if ($config['oidc_token_endpoint'] !== '') {
            $providerConfig['token_endpoint'] = $config['oidc_token_endpoint'];
        }
        if ($config['oidc_userinfo_endpoint'] !== '') {
            $providerConfig['userinfo_endpoint'] = $config['oidc_userinfo_endpoint'];
        }
        if (!empty($providerConfig)) {
            $client->providerConfigParam($providerConfig);
        }

        return $client;
    }

    private function buildSamlAuth(array $config)
    {
        $this->ensureVendorAutoload();
        return new \OneLogin\Saml2\Auth($this->buildSamlSettingsArray($config));
    }

    private function buildSamlSettingsArray(array $config)
    {
        $appUrl = rtrim($this->ssoService->getAbsoluteUrl(ltrim(BASE_PATH, '/')), '/');
        $acsUrl = $config['saml_sp_acs_url'] !== ''
            ? $config['saml_sp_acs_url']
            : $appUrl . '/auth/saml/acs';
        $spEntityId = $config['saml_sp_entity_id'] !== ''
            ? $config['saml_sp_entity_id']
            : $appUrl . '/auth/saml/metadata';

        return [
            'strict' => true,
            'debug' => false,
            'sp' => [
                'entityId' => $spEntityId,
                'assertionConsumerService' => [
                    'url' => $acsUrl,
                    'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST'
                ],
                'singleLogoutService' => [
                    'url' => $appUrl . '/logout',
                    'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect'
                ],
                'NameIDFormat' => $config['saml_nameid_format'] ?: 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress',
            ],
            'idp' => [
                'entityId' => $config['saml_idp_entity_id'],
                'singleSignOnService' => [
                    'url' => $config['saml_idp_sso_url'],
                    'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect'
                ],
                'singleLogoutService' => [
                    'url' => $config['saml_idp_slo_url'],
                    'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect'
                ],
                'x509cert' => str_replace(["\r", "\n"], '', $config['saml_idp_x509_cert']),
            ],
            'security' => [
                'nameIdEncrypted' => false,
                'authnRequestsSigned' => false,
                'logoutRequestSigned' => false,
                'logoutResponseSigned' => false,
                'signMetadata' => false,
                'wantMessagesSigned' => false,
                'wantAssertionsSigned' => true,
                'wantNameId' => true,
                'wantNameIdEncrypted' => false,
                'wantAssertionsEncrypted' => false,
                'wantXMLValidation' => true,
                'requestedAuthnContext' => true,
            ]
        ];
    }

    private function safeRedirect($path)
    {
        if ($path === '' || strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) {
            return BASE_PATH . '/';
        }

        if ($path[0] !== '/') {
            $path = '/' . $path;
        }

        return BASE_PATH . $path;
    }

    private function ensureVendorAutoload()
    {
        if (!class_exists(\Jumbojett\OpenIDConnectClient::class) || !class_exists(\OneLogin\Saml2\Auth::class)) {
            $autoload = __DIR__ . '/../vendor/autoload.php';
            if (!file_exists($autoload)) {
                throw new \RuntimeException('composer dependencies are not installed');
            }
            require_once $autoload;
        }
    }
}

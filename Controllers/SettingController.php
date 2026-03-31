<?php
// controllers/SettingController.php
namespace Controllers;

use Core\Controller;
use Core\Database;
use Core\Auth;
use Core\Mailer;
use Models\ScimToken;
use Models\Setting;
use Services\BackupService;
use Services\DemoDataService;
use Services\WebPushService;

class SettingController extends Controller
{
    private $db;
    private $model;
    private $scimTokenModel;
    private $webPushService;

    public function __construct()
    {
        parent::__construct();
        $this->db = Database::getInstance();
        $this->model = new Setting();
        $this->scimTokenModel = new ScimToken();
        $this->webPushService = new WebPushService();

        // 認証チェック
        if (!$this->auth->check()) {
            $this->redirect(BASE_PATH . '/login');
        }

        // 管理者権限チェック
        if (!$this->auth->isAdmin()) {
            $this->redirect(BASE_PATH . '/');
        }

        $this->enforceAdminNetworkRestriction();
    }

    /**
     * 基本設定ページを表示
     */
    public function index()
    {
        $settings = $this->model->getAll();

        // 設定値を連想配列に変換
        $settingsArray = [];
        foreach ($settings as $setting) {
            $settingsArray[$setting['setting_key']] = $setting['setting_value'];
        }

        $viewData = [
            'title' => t('settings.title'),
            'settings' => $settingsArray,
            'jsFiles' => ['setting.js']
        ];

        $this->view('setting/index', $viewData);
    }

    /**
     * SMTP設定ページを表示
     */
    public function smtp()
    {
        $settings = $this->model->getAll();

        // 設定値を連想配列に変換
        $settingsArray = [];
        foreach ($settings as $setting) {
            $settingsArray[$setting['setting_key']] = $setting['setting_value'];
        }

        $viewData = [
            'title' => t('settings.menu.smtp'),
            'settings' => $settingsArray,
            'jsFiles' => ['setting.js']
        ];

        $this->view('setting/smtp', $viewData);
    }

    /**
     * 通知設定ページを表示
     */
    public function notification()
    {
        $settings = $this->model->getAll();

        // 設定値を連想配列に変換
        $settingsArray = [];
        foreach ($settings as $setting) {
            $settingsArray[$setting['setting_key']] = $setting['setting_value'];
        }

        $viewData = [
            'title' => t('settings.menu.notification'),
            'settings' => $settingsArray,
            'jsFiles' => ['setting.js']
        ];

        $this->view('setting/notification', $viewData);
    }

    /**
     * 認証/PWA/SCIM設定ページを表示
     */
    public function security()
    {
        $settings = $this->model->getAll();
        $settingsArray = [];
        foreach ($settings as $setting) {
            $settingsArray[$setting['setting_key']] = $setting['setting_value'];
        }

        $settingsArray = array_merge([
            'security_password_min_length' => '8',
            'security_password_require_uppercase' => '1',
            'security_password_require_lowercase' => '1',
            'security_password_require_number' => '1',
            'security_password_require_symbol' => '0',
            'security_session_timeout_minutes' => '120',
            'security_login_max_attempts' => '5',
            'security_login_lock_minutes' => '15',
            'security_login_window_minutes' => '15',
            'security_admin_ip_restriction_enabled' => '0',
            'security_admin_ip_allowlist' => '',
        ], $settingsArray);

        // Pushが有効な場合は公開鍵を生成/表示できるようにする
        if (($settingsArray['pwa_notifications_enabled'] ?? '0') === '1') {
            try {
                $settingsArray['pwa_vapid_public_key'] = $this->webPushService->getPublicVapidKey();
            } catch (\Throwable $e) {
                // 生成失敗時はそのまま
            }
        }

        $scimSchemaReady = $this->scimTokenModel->isSchemaReady();
        $scimTokens = $scimSchemaReady ? $this->scimTokenModel->listAll() : [];

        $viewData = [
            'title' => t('settings.menu.security'),
            'settings' => $settingsArray,
            'scimTokens' => $scimTokens,
            'scimSchemaReady' => $scimSchemaReady,
            'csrfToken' => $this->generateCsrfToken(),
            'jsFiles' => ['setting.js']
        ];

        $this->view('setting/security', $viewData);
    }

    /**
     * API: 設定を更新
     */
    public function apiUpdate($params, $data)
    {
        // 認証チェック
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }

        // 管理者権限チェック
        if (!$this->auth->isAdmin()) {
            return ['error' => 'Permission denied', 'code' => 403];
        }

        $updated = [];
        $failed = [];
        $validationErrors = [];

        // トランザクション開始
        $this->db->beginTransaction();

        try {
            foreach ($data as $key => $value) {
                // セキュリティ上の理由でキーをフィルタリング
                if (preg_match('/^[a-zA-Z0-9_]+$/', $key)) {
                    try {
                        $value = $this->normalizeSettingValue($key, $value);
                    } catch (\InvalidArgumentException $e) {
                        $failed[] = $key;
                        $validationErrors[$key] = $e->getMessage();
                        continue;
                    }

                    $success = $this->model->update($key, $value);
                    if ($success) {
                        $updated[] = $key;
                    } else {
                        $failed[] = $key;
                    }
                }
            }

            if (empty($failed)) {
                $this->db->commit();
                return [
                    'success' => true,
                    'message' => tr_text('設定を更新しました', 'Settings updated.'),
                    'data' => [
                        'updated' => $updated
                    ]
                ];
            } else {
                $this->db->rollBack();
                $statusCode = !empty($validationErrors) ? 422 : 500;
                return [
                    'error' => tr_text('一部の設定の更新に失敗しました', 'Failed to update some settings.'),
                    'code' => $statusCode,
                    'data' => [
                        'failed' => $failed,
                        'errors' => $validationErrors
                    ]
                ];
            }
        } catch (\Exception $e) {
            $this->db->rollBack();
            return [
                'error' => tr_text('設定の更新中にエラーが発生しました: ', 'An error occurred while updating settings: ') . $e->getMessage(),
                'code' => 500
            ];
        }
    }

    /**
     * 設定値を正規化・検証
     *
     * @param string $key
     * @param mixed $value
     * @return string
     */
    private function normalizeSettingValue($key, $value)
    {
        $stringValue = is_string($value) ? trim($value) : (string)$value;

        switch ($key) {
            case 'mail_transport':
                $allowed = ['smtp', 'sendmail', 'mail'];
                if (!in_array($stringValue, $allowed, true)) {
                    throw new \InvalidArgumentException(tr_text('mail_transport は smtp/sendmail/mail のいずれかを指定してください。', 'mail_transport must be one of smtp/sendmail/mail.'));
                }
                return $stringValue;

            case 'notification_email':
            case 'mail_reply_to_email':
                if ($stringValue !== '' && !filter_var($stringValue, FILTER_VALIDATE_EMAIL)) {
                    throw new \InvalidArgumentException($key . tr_text(' の形式が不正です。', ' format is invalid.'));
                }
                return $stringValue;

            case 'smtp_port':
                $port = (int)$stringValue;
                if ($port < 1 || $port > 65535) {
                    throw new \InvalidArgumentException(tr_text('smtp_port は 1-65535 の範囲で指定してください。', 'smtp_port must be in the range 1-65535.'));
                }
                return (string)$port;

            case 'smtp_timeout':
                $timeout = (int)$stringValue;
                if ($timeout < 1 || $timeout > 600) {
                    throw new \InvalidArgumentException(tr_text('smtp_timeout は 1-600 秒の範囲で指定してください。', 'smtp_timeout must be in the range 1-600 seconds.'));
                }
                return (string)$timeout;

            case 'security_password_min_length':
                $minLength = (int)$stringValue;
                if ($minLength < 8 || $minLength > 128) {
                    throw new \InvalidArgumentException(tr_text('パスワード最小文字数は 8-128 の範囲で指定してください。', 'Password minimum length must be between 8 and 128.'));
                }
                return (string)$minLength;

            case 'security_session_timeout_minutes':
                $timeoutMinutes = (int)$stringValue;
                if ($timeoutMinutes < 1 || $timeoutMinutes > 1440) {
                    throw new \InvalidArgumentException(tr_text('セッションタイムアウトは 1-1440 分の範囲で指定してください。', 'Session timeout must be between 1 and 1440 minutes.'));
                }
                return (string)$timeoutMinutes;

            case 'security_login_max_attempts':
                $maxAttempts = (int)$stringValue;
                if ($maxAttempts < 1 || $maxAttempts > 20) {
                    throw new \InvalidArgumentException(tr_text('ログイン試行回数上限は 1-20 の範囲で指定してください。', 'Login max attempts must be between 1 and 20.'));
                }
                return (string)$maxAttempts;

            case 'security_login_lock_minutes':
            case 'security_login_window_minutes':
                $minutes = (int)$stringValue;
                if ($minutes < 1 || $minutes > 1440) {
                    throw new \InvalidArgumentException(tr_text('ロック/監視時間は 1-1440 分の範囲で指定してください。', 'Lock/window minutes must be between 1 and 1440.'));
                }
                return (string)$minutes;

            case 'smtp_auth':
            case 'smtp_allow_self_signed':
            case 'notification_enabled':
            case 'schedule_notification':
            case 'workflow_notification':
            case 'message_notification':
            case 'email_notification':
            case 'pwa_enabled':
            case 'pwa_notifications_enabled':
            case 'sso_enabled':
            case 'sso_local_login_enabled':
            case 'sso_jit_provisioning':
            case 'oidc_enabled':
            case 'saml_enabled':
            case 'scim_enabled':
            case 'security_password_require_uppercase':
            case 'security_password_require_lowercase':
            case 'security_password_require_number':
            case 'security_password_require_symbol':
            case 'security_admin_ip_restriction_enabled':
                return ($stringValue === '1' || strtolower($stringValue) === 'true') ? '1' : '0';

            case 'smtp_secure':
                $secure = strtolower($stringValue);
                if (!in_array($secure, ['tls', 'ssl', ''], true)) {
                    throw new \InvalidArgumentException(tr_text('smtp_secure は tls/ssl/空文字 のいずれかを指定してください。', 'smtp_secure must be one of tls/ssl/empty.'));
                }
                return $secure;

            case 'sso_provider':
                $provider = strtolower($stringValue);
                if (!in_array($provider, ['oidc', 'saml'], true)) {
                    throw new \InvalidArgumentException(tr_text('sso_provider は oidc/saml のいずれかを指定してください。', 'sso_provider must be one of oidc/saml.'));
                }
                return $provider;

            case 'sso_default_role':
                $role = strtolower($stringValue);
                if (!in_array($role, ['admin', 'manager', 'user'], true)) {
                    throw new \InvalidArgumentException(tr_text('sso_default_role は admin/manager/user のいずれかを指定してください。', 'sso_default_role must be one of admin/manager/user.'));
                }
                return $role;

            case 'sso_default_organization_id':
                $orgId = (int)$stringValue;
                if ($orgId < 1) {
                    throw new \InvalidArgumentException(tr_text('sso_default_organization_id は 1 以上の整数で指定してください。', 'sso_default_organization_id must be an integer greater than or equal to 1.'));
                }
                return (string)$orgId;

            case 'pwa_theme_color':
                if ($stringValue !== '' && !preg_match('/^#[0-9a-fA-F]{6}$/', $stringValue)) {
                    throw new \InvalidArgumentException($key . tr_text(' は #RRGGBB 形式で指定してください。', ' must be in #RRGGBB format.'));
                }
                return $stringValue !== '' ? $stringValue : '#2b7de9';

            case 'pwa_background_color':
                if ($stringValue !== '' && !preg_match('/^#[0-9a-fA-F]{6}$/', $stringValue)) {
                    throw new \InvalidArgumentException($key . tr_text(' は #RRGGBB 形式で指定してください。', ' must be in #RRGGBB format.'));
                }
                return $stringValue !== '' ? $stringValue : '#ffffff';

            case 'pwa_vapid_subject':
                if ($stringValue !== '' && strpos($stringValue, 'mailto:') !== 0 && !filter_var($stringValue, FILTER_VALIDATE_URL)) {
                    throw new \InvalidArgumentException(tr_text('pwa_vapid_subject は mailto: またはURL形式で指定してください。', 'pwa_vapid_subject must be mailto: or a valid URL.'));
                }
                return $stringValue;

            case 'oidc_issuer':
            case 'oidc_redirect_uri':
            case 'oidc_authorization_endpoint':
            case 'oidc_token_endpoint':
            case 'oidc_userinfo_endpoint':
            case 'saml_idp_sso_url':
            case 'saml_idp_slo_url':
            case 'saml_sp_acs_url':
                if ($stringValue !== '' && !filter_var($stringValue, FILTER_VALIDATE_URL)) {
                    throw new \InvalidArgumentException($key . tr_text(' はURL形式で指定してください。', ' must be a valid URL.'));
                }
                return $stringValue;

            case 'oidc_scopes':
                $scopes = preg_replace('/\s+/', ' ', trim($stringValue));
                return $scopes !== '' ? $scopes : 'openid profile email';

            case 'scim_base_path':
                if ($stringValue === '' || $stringValue[0] !== '/') {
                    throw new \InvalidArgumentException(tr_text('scim_base_path は / から始まるパスで指定してください。', 'scim_base_path must start with /.'));
                }
                return rtrim($stringValue, '/');

            case 'security_admin_ip_allowlist':
                return $this->normalizeIpAllowlist($stringValue);

            default:
                return $stringValue;
        }
    }

    /**
     * API: SMTP接続テスト
     */
    public function apiTestSmtp($params, $data)
    {
        // 認証チェック
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }

        // 管理者権限チェック
        if (!$this->auth->isAdmin()) {
            return ['error' => 'Permission denied', 'code' => 403];
        }

        $testEmail = $data['test_email'] ?? $this->auth->user()['email'];

        try {
            $mailer = new Mailer($this->model);
            $mailer->send(
                $testEmail,
                tr_text('メール送信テスト', 'Mail delivery test'),
                tr_text('<h1>メール送信テスト</h1><p>このメールは通知設定のテスト送信です。</p>', '<h1>Mail delivery test</h1><p>This is a test message sent from notification settings.</p>'),
                true
            );

            return [
                'success' => true,
                'message' => tr_text('テストメールを送信しました', 'Test email sent.'),
                'data' => [
                    'email' => $testEmail
                ]
            ];
        } catch (\Exception $e) {
            return [
                'error' => tr_text('メール送信に失敗しました: ', 'Failed to send email: ') . $e->getMessage(),
                'code' => 500
            ];
        }
    }

    /**
     * API: デモデータを生成/再構築
     */
    public function apiManageDemoData($params, $data)
    {
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }

        if (!$this->auth->isAdmin()) {
            return ['error' => 'Permission denied', 'code' => 403];
        }

        $action = strtolower(trim((string)($data['action'] ?? 'refresh')));
        $years = (int)($data['years'] ?? 3);
        $years = max(1, min(5, $years));
        @set_time_limit(0);
        @ignore_user_abort(true);

        $service = new DemoDataService();

        try {
            if ($action === 'rebuild') {
                $result = $service->rebuildAllDemoData((int)$this->auth->id(), $years);
            } else {
                $action = 'refresh';
                $result = $service->refreshFutureDemoData((int)$this->auth->id(), $years);
            }
        } catch (\Throwable $e) {
            error_log('Demo data generation failed: ' . $e->getMessage());
            return ['error' => tr_text('デモデータ処理中にエラーが発生しました', 'An error occurred during demo data processing.'), 'code' => 500];
        }

        if (empty($result['success'])) {
            return [
                'error' => $result['error'] ?? tr_text('デモデータ処理に失敗しました', 'Demo data processing failed.'),
                'code' => 500
            ];
        }

        return [
            'success' => true,
            'message' => $action === 'rebuild'
                ? tr_text('デモデータを全再構築しました', 'Demo data has been fully rebuilt.')
                : tr_text('本日から' . $years . '年分のデモデータを更新しました', 'Demo data has been refreshed for ' . $years . ' year(s) from today.'),
            'data' => $result
        ];
    }

    public function apiGenerateScimToken($params, $data)
    {
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }
        if (!$this->auth->isAdmin()) {
            return ['error' => 'Permission denied', 'code' => 403];
        }
        if (!$this->scimTokenModel->isSchemaReady()) {
            return ['error' => tr_text('SCIM関連テーブルが未作成です。db/upgrade_20260329_pwa_sso_scim.sql を適用してください。', 'SCIM tables are missing. Apply db/upgrade_20260329_pwa_sso_scim.sql.'), 'code' => 409];
        }

        $name = trim((string)($data['name'] ?? 'SCIM Token'));
        $token = $this->scimTokenModel->createToken($name, $this->auth->id());
        if (!$token) {
            return ['error' => tr_text('SCIMトークンの生成に失敗しました', 'Failed to generate SCIM token.'), 'code' => 500];
        }

        return [
            'success' => true,
            'message' => tr_text('SCIMトークンを生成しました。表示される平文トークンはこの画面でのみ確認できます。', 'SCIM token generated. The plain token is shown only on this screen.'),
            'data' => [
                'id' => $token['id'],
                'token' => $token['token'],
                'tokens' => $this->scimTokenModel->listAll()
            ]
        ];
    }

    public function apiToggleScimToken($params, $data)
    {
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }
        if (!$this->auth->isAdmin()) {
            return ['error' => 'Permission denied', 'code' => 403];
        }
        if (!$this->scimTokenModel->isSchemaReady()) {
            return ['error' => tr_text('SCIM関連テーブルが未作成です。db/upgrade_20260329_pwa_sso_scim.sql を適用してください。', 'SCIM tables are missing. Apply db/upgrade_20260329_pwa_sso_scim.sql.'), 'code' => 409];
        }

        $id = (int)($params['id'] ?? 0);
        $active = isset($data['is_active']) ? ((string)$data['is_active'] === '1' || $data['is_active'] === true) : false;
        if ($id <= 0) {
            return ['error' => tr_text('トークンIDが不正です', 'Invalid token ID.'), 'code' => 422];
        }

        $ok = $this->scimTokenModel->toggleActive($id, $active);
        return [
            'success' => (bool)$ok,
            'message' => $ok
                ? tr_text('SCIMトークン状態を更新しました', 'SCIM token status updated.')
                : tr_text('SCIMトークン状態の更新に失敗しました', 'Failed to update SCIM token status.'),
            'data' => [
                'tokens' => $this->scimTokenModel->listAll()
            ]
        ];
    }

    /**
     * API: バックアップ実行
     */
    public function apiRunBackup($params, $data)
    {
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }
        if (!$this->auth->isAdmin()) {
            return ['error' => 'Permission denied', 'code' => 403];
        }
        if (!$this->validateCsrfToken((string)($data['csrf_token'] ?? ''))) {
            return ['error' => tr_text('CSRFトークンが無効です。', 'Invalid CSRF token.'), 'code' => 403];
        }

        try {
            @set_time_limit(0);
            @ignore_user_abort(true);

            $service = new BackupService();
            $result = $service->run((int)$this->auth->id());

            return [
                'success' => true,
                'message' => tr_text('バックアップを作成しました', 'Backup created successfully.'),
                'data' => $result
            ];
        } catch (\Throwable $e) {
            error_log('Backup failed: ' . $e->getMessage());
            return [
                'error' => tr_text('バックアップ実行に失敗しました: ', 'Failed to run backup: ') . $e->getMessage(),
                'code' => 500
            ];
        }
    }

    /**
     * API: バックアップ履歴取得
     */
    public function apiBackupHistory($params)
    {
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }
        if (!$this->auth->isAdmin()) {
            return ['error' => 'Permission denied', 'code' => 403];
        }

        $limit = (int)($this->getQuery('limit', 50));

        try {
            $service = new BackupService();
            $history = $service->getHistory($limit);

            return [
                'success' => true,
                'data' => $history
            ];
        } catch (\Throwable $e) {
            return [
                'error' => tr_text('バックアップ履歴の取得に失敗しました: ', 'Failed to load backup history: ') . $e->getMessage(),
                'code' => 500
            ];
        }
    }

    /**
     * ダウンロード: バックアップファイル
     */
    public function downloadBackup($params)
    {
        if (!$this->auth->check()) {
            $this->redirect(BASE_PATH . '/login');
        }
        if (!$this->auth->isAdmin()) {
            http_response_code(403);
            echo 'Permission denied';
            exit;
        }

        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(404);
            echo 'Backup not found';
            exit;
        }

        try {
            $service = new BackupService();
            $service->streamDownload($id);
        } catch (\Throwable $e) {
            http_response_code(404);
            echo htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            exit;
        }
    }

    private function enforceAdminNetworkRestriction()
    {
        if ($this->auth->isAdminIpAllowed()) {
            return;
        }

        $message = tr_text(
            'この画面は許可されたネットワークからのみ利用できます。',
            'This page is available only from approved networks.'
        );

        $accept = strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? ''));
        $requestUri = (string)($_SERVER['REQUEST_URI'] ?? '');
        $isApi = strpos($requestUri, '/api/') !== false || strpos($accept, 'application/json') !== false;

        if ($isApi) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => $message], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $_SESSION['error'] = $message;
        $this->redirect(BASE_PATH . '/');
    }

    private function normalizeIpAllowlist($rawValue)
    {
        $entries = preg_split('/[\r\n,;]+/', (string)$rawValue) ?: [];
        $normalized = [];

        foreach ($entries as $entry) {
            $entry = trim($entry);
            if ($entry === '') {
                continue;
            }

            if ($entry === '*') {
                $normalized[] = $entry;
                continue;
            }

            if (strpos($entry, '/') !== false) {
                if (!$this->isValidIpv4Cidr($entry)) {
                    throw new \InvalidArgumentException(tr_text(
                        'IP許可リストに不正なCIDR表記があります: ' . $entry,
                        'Invalid CIDR entry in IP allowlist: ' . $entry
                    ));
                }
                $normalized[] = $entry;
                continue;
            }

            if (!filter_var($entry, FILTER_VALIDATE_IP)) {
                throw new \InvalidArgumentException(tr_text(
                    'IP許可リストに不正なIPアドレスがあります: ' . $entry,
                    'Invalid IP address in allowlist: ' . $entry
                ));
            }
            $normalized[] = $entry;
        }

        return implode("\n", array_values(array_unique($normalized)));
    }

    private function isValidIpv4Cidr($entry)
    {
        if (strpos($entry, '/') === false) {
            return false;
        }
        list($ip, $prefix) = explode('/', $entry, 2);
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return false;
        }
        if (!is_numeric($prefix)) {
            return false;
        }
        $prefix = (int)$prefix;
        return $prefix >= 0 && $prefix <= 32;
    }
}

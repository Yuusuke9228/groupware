<?php
// controllers/SettingController.php
namespace Controllers;

use Core\Controller;
use Core\Database;
use Core\Auth;
use Core\Mailer;
use Models\ScimToken;
use Models\Setting;
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
            'title' => 'システム設定',
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
            'title' => 'メール設定',
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
            'title' => '通知設定',
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
            'title' => '認証・PWA・SCIM設定',
            'settings' => $settingsArray,
            'scimTokens' => $scimTokens,
            'scimSchemaReady' => $scimSchemaReady,
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
                    'message' => '設定を更新しました',
                    'data' => [
                        'updated' => $updated
                    ]
                ];
            } else {
                $this->db->rollBack();
                return [
                    'error' => '一部の設定の更新に失敗しました',
                    'code' => 500,
                    'data' => [
                        'failed' => $failed,
                        'errors' => $validationErrors
                    ]
                ];
            }
        } catch (\Exception $e) {
            $this->db->rollBack();
            return [
                'error' => '設定の更新中にエラーが発生しました: ' . $e->getMessage(),
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
                    throw new \InvalidArgumentException('mail_transport は smtp/sendmail/mail のいずれかを指定してください。');
                }
                return $stringValue;

            case 'notification_email':
            case 'mail_reply_to_email':
                if ($stringValue !== '' && !filter_var($stringValue, FILTER_VALIDATE_EMAIL)) {
                    throw new \InvalidArgumentException($key . ' の形式が不正です。');
                }
                return $stringValue;

            case 'smtp_port':
                $port = (int)$stringValue;
                if ($port < 1 || $port > 65535) {
                    throw new \InvalidArgumentException('smtp_port は 1-65535 の範囲で指定してください。');
                }
                return (string)$port;

            case 'smtp_timeout':
                $timeout = (int)$stringValue;
                if ($timeout < 1 || $timeout > 600) {
                    throw new \InvalidArgumentException('smtp_timeout は 1-600 秒の範囲で指定してください。');
                }
                return (string)$timeout;

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
                return ($stringValue === '1' || strtolower($stringValue) === 'true') ? '1' : '0';

            case 'smtp_secure':
                $secure = strtolower($stringValue);
                if (!in_array($secure, ['tls', 'ssl', ''], true)) {
                    throw new \InvalidArgumentException('smtp_secure は tls/ssl/空文字 のいずれかを指定してください。');
                }
                return $secure;

            case 'sso_provider':
                $provider = strtolower($stringValue);
                if (!in_array($provider, ['oidc', 'saml'], true)) {
                    throw new \InvalidArgumentException('sso_provider は oidc/saml のいずれかを指定してください。');
                }
                return $provider;

            case 'sso_default_role':
                $role = strtolower($stringValue);
                if (!in_array($role, ['admin', 'manager', 'user'], true)) {
                    throw new \InvalidArgumentException('sso_default_role は admin/manager/user のいずれかを指定してください。');
                }
                return $role;

            case 'sso_default_organization_id':
                $orgId = (int)$stringValue;
                if ($orgId < 1) {
                    throw new \InvalidArgumentException('sso_default_organization_id は 1 以上の整数で指定してください。');
                }
                return (string)$orgId;

            case 'pwa_theme_color':
                if ($stringValue !== '' && !preg_match('/^#[0-9a-fA-F]{6}$/', $stringValue)) {
                    throw new \InvalidArgumentException($key . ' は #RRGGBB 形式で指定してください。');
                }
                return $stringValue !== '' ? $stringValue : '#2b7de9';

            case 'pwa_background_color':
                if ($stringValue !== '' && !preg_match('/^#[0-9a-fA-F]{6}$/', $stringValue)) {
                    throw new \InvalidArgumentException($key . ' は #RRGGBB 形式で指定してください。');
                }
                return $stringValue !== '' ? $stringValue : '#ffffff';

            case 'pwa_vapid_subject':
                if ($stringValue !== '' && strpos($stringValue, 'mailto:') !== 0 && !filter_var($stringValue, FILTER_VALIDATE_URL)) {
                    throw new \InvalidArgumentException('pwa_vapid_subject は mailto: またはURL形式で指定してください。');
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
                    throw new \InvalidArgumentException($key . ' はURL形式で指定してください。');
                }
                return $stringValue;

            case 'oidc_scopes':
                $scopes = preg_replace('/\s+/', ' ', trim($stringValue));
                return $scopes !== '' ? $scopes : 'openid profile email';

            case 'scim_base_path':
                if ($stringValue === '' || $stringValue[0] !== '/') {
                    throw new \InvalidArgumentException('scim_base_path は / から始まるパスで指定してください。');
                }
                return rtrim($stringValue, '/');

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
                'メール送信テスト',
                '<h1>メール送信テスト</h1><p>このメールは通知設定のテスト送信です。</p>',
                true
            );

            return [
                'success' => true,
                'message' => 'テストメールを送信しました',
                'data' => [
                    'email' => $testEmail
                ]
            ];
        } catch (\Exception $e) {
            return [
                'error' => 'メール送信に失敗しました: ' . $e->getMessage(),
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
            return ['error' => 'デモデータ処理中にエラーが発生しました', 'code' => 500];
        }

        if (empty($result['success'])) {
            return [
                'error' => $result['error'] ?? 'デモデータ処理に失敗しました',
                'code' => 500
            ];
        }

        return [
            'success' => true,
            'message' => $action === 'rebuild'
                ? 'デモデータを全再構築しました'
                : '本日から' . $years . '年分のデモデータを更新しました',
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
            return ['error' => 'SCIM関連テーブルが未作成です。db/upgrade_20260329_pwa_sso_scim.sql を適用してください。', 'code' => 409];
        }

        $name = trim((string)($data['name'] ?? 'SCIM Token'));
        $token = $this->scimTokenModel->createToken($name, $this->auth->id());
        if (!$token) {
            return ['error' => 'SCIMトークンの生成に失敗しました', 'code' => 500];
        }

        return [
            'success' => true,
            'message' => 'SCIMトークンを生成しました。表示される平文トークンはこの画面でのみ確認できます。',
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
            return ['error' => 'SCIM関連テーブルが未作成です。db/upgrade_20260329_pwa_sso_scim.sql を適用してください。', 'code' => 409];
        }

        $id = (int)($params['id'] ?? 0);
        $active = isset($data['is_active']) ? ((string)$data['is_active'] === '1' || $data['is_active'] === true) : false;
        if ($id <= 0) {
            return ['error' => 'トークンIDが不正です', 'code' => 422];
        }

        $ok = $this->scimTokenModel->toggleActive($id, $active);
        return [
            'success' => (bool)$ok,
            'message' => $ok ? 'SCIMトークン状態を更新しました' : 'SCIMトークン状態の更新に失敗しました',
            'data' => [
                'tokens' => $this->scimTokenModel->listAll()
            ]
        ];
    }
}

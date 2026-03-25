<?php
// controllers/SettingController.php
namespace Controllers;

use Core\Controller;
use Core\Database;
use Core\Auth;
use Core\Mailer;
use Models\Setting;

class SettingController extends Controller
{
    private $db;
    private $model;

    public function __construct()
    {
        parent::__construct();
        $this->db = Database::getInstance();
        $this->model = new Setting();

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
                return ($stringValue === '1' || strtolower($stringValue) === 'true') ? '1' : '0';

            case 'smtp_secure':
                $secure = strtolower($stringValue);
                if (!in_array($secure, ['tls', 'ssl', ''], true)) {
                    throw new \InvalidArgumentException('smtp_secure は tls/ssl/空文字 のいずれかを指定してください。');
                }
                return $secure;

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
}

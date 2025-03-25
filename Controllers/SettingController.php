<?php
// controllers/SettingController.php
namespace Controllers;

use Core\Controller;
use Core\Database;
use Core\Auth;
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

        // トランザクション開始
        $this->db->beginTransaction();

        try {
            foreach ($data as $key => $value) {
                // セキュリティ上の理由でキーをフィルタリング
                if (preg_match('/^[a-zA-Z0-9_]+$/', $key)) {
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
                        'failed' => $failed
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

        require_once __DIR__ . '/../vendor/autoload.php';

        try {
            // SMTPの設定を取得
            $smtpHost = $this->model->get('smtp_host');
            $smtpPort = $this->model->get('smtp_port');
            $smtpSecure = $this->model->get('smtp_secure');
            $smtpUsername = $this->model->get('smtp_username');
            $smtpPassword = $this->model->get('smtp_password');
            $fromEmail = $this->model->get('notification_email');
            $appName = $this->model->get('app_name');

            // PHPMailerの設定
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $smtpHost;
            $mail->SMTPAuth = true;
            $mail->Username = $smtpUsername;
            $mail->Password = $smtpPassword;
            $mail->SMTPSecure = $smtpSecure;
            $mail->Port = $smtpPort;
            $mail->CharSet = 'UTF-8';

            // 送信元・送信先の設定
            $mail->setFrom($fromEmail, $appName);
            $mail->addAddress($testEmail);

            // メール内容の設定
            $mail->isHTML(true);
            $mail->Subject = 'SMTP接続テスト';
            $mail->Body = '<h1>SMTP接続テスト</h1><p>このメールはSMTP接続テストとして送信されました。</p>';

            // メール送信
            $mail->send();

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

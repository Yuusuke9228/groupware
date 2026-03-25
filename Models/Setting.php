<?php
// models/Setting.php
namespace Models;

use Core\Database;

class Setting
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * 全ての設定を取得
     * 
     * @return array 設定の配列
     */
    public function getAll()
    {
        $sql = "SELECT * FROM settings ORDER BY setting_key";
        return $this->db->fetchAll($sql);
    }

    /**
     * 特定の設定を取得
     * 
     * @param string $key 設定キー
     * @param mixed $default デフォルト値
     * @return mixed 設定値
     */
    public function get($key, $default = null)
    {
        $sql = "SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1";
        $result = $this->db->fetch($sql, [$key]);

        return $result ? $result['setting_value'] : $default;
    }

    /**
     * 環境変数優先で設定値を取得
     *
     * @param string $settingKey
     * @param string $envKey
     * @param mixed $default
     * @return mixed
     */
    public function getWithEnv($settingKey, $envKey, $default = null)
    {
        $envValue = getenv($envKey);
        if ($envValue !== false && $envValue !== '') {
            return $envValue;
        }

        return $this->get($settingKey, $default);
    }

    /**
     * 設定を更新または追加
     * 
     * @param string $key 設定キー
     * @param mixed $value 設定値
     * @param string $description 説明（新規追加時のみ使用）
     * @return bool 成功時true、失敗時false
     */
    public function update($key, $value, $description = null)
    {
        // 既存の設定を確認
        $sql = "SELECT id FROM settings WHERE setting_key = ? LIMIT 1";
        $existing = $this->db->fetch($sql, [$key]);

        if ($existing) {
            // 既存の設定を更新
            $sql = "UPDATE settings SET setting_value = ? WHERE setting_key = ?";
            return $this->db->execute($sql, [$value, $key]);
        } else {
            // 新しい設定を追加
            $sql = "INSERT INTO settings (setting_key, setting_value, description) VALUES (?, ?, ?)";
            return $this->db->execute($sql, [$key, $value, $description]);
        }
    }

    /**
     * 設定を削除
     * 
     * @param string $key 設定キー
     * @return bool 成功時true、失敗時false
     */
    public function delete($key)
    {
        $sql = "DELETE FROM settings WHERE setting_key = ?";
        return $this->db->execute($sql, [$key]);
    }

    /**
     * アプリケーション名を取得
     * 
     * @return string アプリケーション名
     */
    public function getAppName()
    {
        return $this->get('app_name', 'TeamSpace');
    }

    /**
     * アプリケーションURLを取得
     *
     * 優先順:
     * 1. 環境変数 GW_APP_URL
     * 2. settings.app_url
     * 3. config/config.php の app.url
     *
     * @return string
     */
    public function getAppUrl()
    {
        $appUrl = trim((string)$this->getWithEnv('app_url', 'GW_APP_URL', ''));
        if ($appUrl !== '') {
            return rtrim($appUrl, '/');
        }

        $configPath = __DIR__ . '/../config/config.php';
        if (file_exists($configPath)) {
            $config = require $configPath;
            if (!empty($config['app']['url'])) {
                return rtrim((string)$config['app']['url'], '/');
            }
        }

        return '';
    }

    /**
     * 会社名を取得
     * 
     * @return string 会社名
     */
    public function getCompanyName()
    {
        return $this->get('company_name', '株式会社サンプル');
    }

    /**
     * 通知機能が有効かどうかを取得
     * 
     * @return bool 通知機能が有効ならtrue、無効ならfalse
     */
    public function isNotificationEnabled()
    {
        return (bool)$this->get('notification_enabled', true);
    }

    /**
     * メール通知に必要な設定が完了しているかチェック
     * 
     * @return bool 設定が完了していればtrue、そうでなければfalse
     */
    public function isEmailConfigured()
    {
        $config = $this->getMailConfig();

        if (empty($config['from_email']) || !filter_var($config['from_email'], FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $transport = $config['transport'];
        if ($transport === 'smtp') {
            if (empty($config['smtp_host']) || empty($config['smtp_port'])) {
                return false;
            }

            if (!empty($config['smtp_auth'])) {
                if (empty($config['smtp_username']) || empty($config['smtp_password'])) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * メール送信設定を取得（環境変数オーバーライド対応）
     *
     * @return array
     */
    public function getMailConfig()
    {
        $transport = strtolower(trim((string)$this->getWithEnv('mail_transport', 'GW_MAIL_TRANSPORT', 'smtp')));
        if (!in_array($transport, ['smtp', 'sendmail', 'mail'], true)) {
            $transport = 'smtp';
        }

        $fromEmail = trim((string)$this->getWithEnv('notification_email', 'GW_MAIL_FROM_EMAIL', ''));
        $fromName = trim((string)$this->getWithEnv('mail_from_name', 'GW_MAIL_FROM_NAME', $this->getAppName()));
        $replyToEmail = trim((string)$this->getWithEnv('mail_reply_to_email', 'GW_MAIL_REPLY_TO_EMAIL', ''));

        $smtpAuth = $this->toBool(
            $this->getWithEnv('smtp_auth', 'GW_SMTP_AUTH', '1'),
            true
        );

        $smtpAllowSelfSigned = $this->toBool(
            $this->getWithEnv('smtp_allow_self_signed', 'GW_SMTP_ALLOW_SELF_SIGNED', '0'),
            false
        );

        return [
            'transport' => $transport,
            'from_email' => $fromEmail,
            'from_name' => $fromName,
            'reply_to_email' => $replyToEmail,
            'smtp_host' => trim((string)$this->getWithEnv('smtp_host', 'GW_SMTP_HOST', '')),
            'smtp_port' => (int)$this->getWithEnv('smtp_port', 'GW_SMTP_PORT', '587'),
            'smtp_secure' => strtolower(trim((string)$this->getWithEnv('smtp_secure', 'GW_SMTP_SECURE', 'tls'))),
            'smtp_auth' => $smtpAuth,
            'smtp_username' => (string)$this->getWithEnv('smtp_username', 'GW_SMTP_USERNAME', ''),
            'smtp_password' => (string)$this->getWithEnv('smtp_password', 'GW_SMTP_PASSWORD', ''),
            'smtp_timeout' => (int)$this->getWithEnv('smtp_timeout', 'GW_SMTP_TIMEOUT', '30'),
            'smtp_allow_self_signed' => $smtpAllowSelfSigned,
            'sendmail_path' => (string)$this->getWithEnv('sendmail_path', 'GW_SENDMAIL_PATH', ''),
        ];
    }

    /**
     * 真偽値の正規化
     *
     * @param mixed $value
     * @param bool $default
     * @return bool
     */
    private function toBool($value, $default = false)
    {
        if (is_bool($value)) {
            return $value;
        }

        if ($value === null || $value === '') {
            return $default;
        }

        $normalized = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        return $normalized === null ? $default : $normalized;
    }
}

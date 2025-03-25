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
        return $this->get('app_name', 'GroupWare');
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
        $requiredSettings = [
            'smtp_host',
            'smtp_port',
            'smtp_username',
            'smtp_password',
            'notification_email'
        ];

        foreach ($requiredSettings as $key) {
            $value = $this->get($key);
            if (empty($value)) {
                return false;
            }
        }

        return true;
    }
}

<?php
namespace Core;

use Models\Setting;

class FeatureGate
{
    private $db;
    private $settingModel;
    private $settings = [];
    private $userOverridesReady = null;

    private static $modules = [
        'schedule' => ['label_ja' => 'スケジュール', 'label_en' => 'Schedule'],
        'facility' => ['label_ja' => '設備予約', 'label_en' => 'Facility reservations'],
        'messages' => ['label_ja' => 'メッセージ', 'label_en' => 'Messages'],
        'chat' => ['label_ja' => 'チャット', 'label_en' => 'Chat'],
        'workflow' => ['label_ja' => 'ワークフロー', 'label_en' => 'Workflow'],
        'task' => ['label_ja' => 'タスク', 'label_en' => 'Tasks'],
        'daily_report' => ['label_ja' => '日報', 'label_en' => 'Daily reports'],
        'bulletin' => ['label_ja' => '掲示板', 'label_en' => 'Bulletin board'],
        'webdatabase' => ['label_ja' => 'WEBデータベース', 'label_en' => 'Web database'],
        'address_book' => ['label_ja' => 'アドレス帳', 'label_en' => 'Address book'],
        'file_share' => ['label_ja' => 'ファイル共有', 'label_en' => 'File sharing'],
        'files' => ['label_ja' => 'ファイル管理', 'label_en' => 'File management'],
        'integrations' => ['label_ja' => '連携', 'label_en' => 'Integrations'],
        'automation' => ['label_ja' => '自動化', 'label_en' => 'Automation'],
    ];

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->settingModel = new Setting();
    }

    public static function modules()
    {
        return self::$modules;
    }

    public function canAccess($moduleKey, $user = null)
    {
        $moduleKey = $this->normalizeModuleKey($moduleKey);
        if ($moduleKey === '') return true;
        if (!$this->isEnabled($moduleKey)) return $this->isAdminUser($user);
        $override = $this->getUserOverride($moduleKey, $this->userId($user));
        if ($override && $override['can_access'] !== null) return (int)$override['can_access'] === 1 || $this->isAdminUser($user);
        return true;
    }

    public function canCreate($moduleKey, $user = null)
    {
        $moduleKey = $this->normalizeModuleKey($moduleKey);
        if (!$this->canAccess($moduleKey, $user)) return false;
        if ($this->isAdminUser($user)) return true;
        $override = $this->getUserOverride($moduleKey, $this->userId($user));
        if ($override && $override['can_create'] !== null) return (int)$override['can_create'] === 1;
        $roles = $this->getCreateRoles($moduleKey);
        $role = is_array($user) ? (string)(isset($user['role']) ? $user['role'] : 'user') : 'user';
        return in_array($role, $roles, true);
    }

    public function enforceAccess($moduleKey, $user = null, $redirect = null)
    {
        if ($this->canAccess($moduleKey, $user)) return;
        $this->deny(function_exists('tr_text') ? tr_text('この機能は現在利用できません。', 'This feature is currently disabled.') : 'この機能は現在利用できません。', $redirect);
    }

    public function enforceCreate($moduleKey, $user = null, $redirect = null)
    {
        if ($this->canCreate($moduleKey, $user)) return;
        $this->deny(function_exists('tr_text') ? tr_text('この操作を行う権限がありません。', 'You do not have permission to perform this action.') : 'この操作を行う権限がありません。', $redirect);
    }

    public function isEnabled($moduleKey)
    {
        return $this->toBool($this->getSetting('feature_' . $this->normalizeModuleKey($moduleKey) . '_enabled', '1'), true);
    }

    public function getCreateRoles($moduleKey)
    {
        $raw = (string)$this->getSetting('feature_' . $this->normalizeModuleKey($moduleKey) . '_create_roles', 'admin,manager,user');
        $roles = array_filter(array_map('trim', explode(',', $raw)));
        return empty($roles) ? ['admin'] : array_values(array_unique($roles));
    }

    private function deny($message, $redirect)
    {
        $accept = strtolower((string)(isset($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : ''));
        $uri = (string)(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '');
        $isApi = strpos($uri, '/api/') !== false || strpos($accept, 'application/json') !== false;
        if ($isApi) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => $message, 'code' => 403], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $_SESSION['flash_error'] = $message;
        header('Location: ' . ($redirect ?: BASE_PATH . '/'));
        exit;
    }

    private function getSetting($key, $default)
    {
        if (!array_key_exists($key, $this->settings)) $this->settings[$key] = $this->settingModel->get($key, $default);
        return $this->settings[$key];
    }

    private function getUserOverride($moduleKey, $userId)
    {
        if ($userId <= 0 || !$this->hasUserOverridesTable()) return null;
        return $this->db->fetch('SELECT can_access, can_create FROM module_user_permissions WHERE module_key = ? AND user_id = ? LIMIT 1', [$moduleKey, $userId]);
    }

    private function hasUserOverridesTable()
    {
        if ($this->userOverridesReady !== null) return $this->userOverridesReady;
        try {
            $row = $this->db->fetch("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'module_user_permissions' LIMIT 1");
            $this->userOverridesReady = (bool)$row;
        } catch (\Throwable $e) { $this->userOverridesReady = false; }
        return $this->userOverridesReady;
    }

    private function userId($user) { return is_array($user) ? (int)(isset($user['id']) ? $user['id'] : 0) : 0; }
    private function isAdminUser($user) { return is_array($user) && (string)(isset($user['role']) ? $user['role'] : '') === 'admin'; }
    private function normalizeModuleKey($moduleKey) { return preg_replace('/[^a-z0-9_]/', '', str_replace('-', '_', strtolower((string)$moduleKey))); }
    private function toBool($value, $default) { $v = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE); return $v === null ? $default : (bool)$v; }
}

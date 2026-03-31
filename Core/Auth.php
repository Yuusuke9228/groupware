<?php
// core/Auth.php
namespace Core;

class Auth
{
    private static $instance = null;
    private $db;
    private $config;
    private $securitySettings = null;
    private $securityColumnsReady = null;
    private $lastError = null;

    private function __construct()
    {
        $this->db = Database::getInstance();
        $this->config = require_once __DIR__ . '/../config/config.php';
        $this->bootstrapSession();
    }

    // シングルトンパターンでインスタンスを取得
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function bootstrapSession()
    {
        if (session_status() !== PHP_SESSION_NONE) {
            return;
        }

        $sessionName = (string)($this->config['auth']['session_name'] ?? 'gsession_user');
        $sessionLifetime = (int)($this->config['auth']['session_lifetime'] ?? 86400);
        if ($sessionLifetime < 300) {
            $sessionLifetime = 300;
        }

        $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

        @ini_set('session.gc_maxlifetime', (string)$sessionLifetime);
        @ini_set('session.use_strict_mode', '1');
        @ini_set('session.cookie_httponly', '1');
        @ini_set('session.cookie_secure', $isHttps ? '1' : '0');
        @ini_set('session.cookie_samesite', 'Lax');

        if (!headers_sent()) {
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'secure' => $isHttps,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }

        session_name($sessionName);
        session_start();
    }

    // ログイン処理
    public function login($username, $password, $remember = false)
    {
        $username = trim((string)$username);
        $password = (string)$password;
        $this->lastError = null;

        if ($username === '' || $password === '') {
            $this->lastError = $this->tr('ユーザー名またはパスワードが正しくありません。', 'Invalid username or password.');
            return false;
        }

        $user = null;
        if ($this->hasSecurityColumns()) {
            $sql = "SELECT id, username, password, role, display_name, status, failed_login_attempts, last_failed_login_at, locked_until
                    FROM users
                    WHERE username = ? AND status = 'active'
                    LIMIT 1";
            $user = $this->db->fetch($sql, [$username]);
        } else {
            $sql = "SELECT id, username, password, role, display_name, status
                    FROM users
                    WHERE username = ? AND status = 'active'
                    LIMIT 1";
            $user = $this->db->fetch($sql, [$username]);
        }

        if ($user && $this->isLocked($user)) {
            $this->setLockedError($user['locked_until'] ?? null);
            return false;
        }

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_name'] = $user['display_name'];
            $_SESSION['last_activity'] = time();

            $this->clearFailedLoginState((int)$user['id']);

            if ($this->hasSecurityColumns()) {
                $this->db->execute(
                    "UPDATE users SET last_login = NOW(), password_changed_at = COALESCE(password_changed_at, NOW()) WHERE id = ?",
                    [$user['id']]
                );
            } else {
                $this->db->execute(
                    "UPDATE users SET last_login = NOW() WHERE id = ?",
                    [$user['id']]
                );
            }

            if ($remember) {
                $token = bin2hex(random_bytes(32));
                $expires = time() + ($this->config['auth']['remember_me_days'] * 86400);

                $this->db->execute(
                    "INSERT INTO user_tokens (user_id, token, expires_at) VALUES (?, ?, FROM_UNIXTIME(?))",
                    [$user['id'], password_hash($token, PASSWORD_DEFAULT), $expires]
                );

                setcookie(
                    'remember_token',
                    $user['id'] . ':' . $token,
                    $expires,
                    '/',
                    '',
                    false,
                    true
                );
            }

            return true;
        }

        if ($user) {
            $this->recordFailedLoginAttempt($user);
        }

        if ($this->lastError === null) {
            $this->lastError = $this->tr('ユーザー名またはパスワードが正しくありません。', 'Invalid username or password.');
        }
        return false;
    }

    // ログアウト処理
    public function logout()
    {
        if (isset($_COOKIE['remember_token'])) {
            $parts = explode(':', (string)$_COOKIE['remember_token'], 2);
            $userId = (int)($parts[0] ?? 0);
            if ($userId > 0) {
                $this->db->execute(
                    "DELETE FROM user_tokens WHERE user_id = ?",
                    [$userId]
                );
            }
            setcookie('remember_token', '', time() - 3600, '/');
        }

        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        session_destroy();

        return true;
    }

    // 現在のユーザーが認証済みかチェック
    public function check()
    {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }

        if (!$this->enforceSessionTimeout()) {
            return false;
        }

        $_SESSION['last_activity'] = time();
        return true;
    }

    // 現在のユーザーIDを取得
    public function id()
    {
        return $this->check() ? $_SESSION['user_id'] : null;
    }

    // 現在のユーザー情報を取得
    public function user()
    {
        if (!$this->check()) {
            return null;
        }

        $sql = "SELECT * FROM users WHERE id = ? LIMIT 1";
        return $this->db->fetch($sql, [$_SESSION['user_id']]);
    }

    // 特定の権限を持っているかチェック
    public function hasRole($role)
    {
        if (!$this->check()) {
            return false;
        }

        return ($_SESSION['user_role'] ?? null) === $role;
    }

    // 管理者権限を持っているかチェック
    public function isAdmin()
    {
        return $this->hasRole('admin');
    }

    // Remember Me トークンからユーザーを認証
    public function authenticateFromRememberToken()
    {
        if ($this->check() || !isset($_COOKIE['remember_token'])) {
            return false;
        }

        $parts = explode(':', (string)$_COOKIE['remember_token'], 2);
        $userId = (int)($parts[0] ?? 0);
        $token = (string)($parts[1] ?? '');
        if ($userId <= 0 || $token === '') {
            setcookie('remember_token', '', time() - 3600, '/');
            return false;
        }

        $sql = "SELECT u.*, t.token, t.expires_at 
                FROM users u 
                JOIN user_tokens t ON u.id = t.user_id 
                WHERE u.id = ? AND u.status = 'active' 
                AND t.expires_at > NOW() 
                ORDER BY t.expires_at DESC LIMIT 1";

        $result = $this->db->fetch($sql, [$userId]);

        if ($result && password_verify($token, $result['token'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $result['id'];
            $_SESSION['user_role'] = $result['role'];
            $_SESSION['user_name'] = $result['display_name'];
            $_SESSION['last_activity'] = time();

            $this->db->execute(
                "UPDATE users SET last_login = NOW() WHERE id = ?",
                [$result['id']]
            );

            return true;
        }

        // 無効なトークンの場合、Cookieを削除
        setcookie('remember_token', '', time() - 3600, '/');
        return false;
    }

    public function getLastError()
    {
        return $this->lastError;
    }

    public function getPasswordPolicy()
    {
        $settings = $this->getSecuritySettings();
        return [
            'min_length' => (int)$settings['password_min_length'],
            'require_uppercase' => (bool)$settings['password_require_uppercase'],
            'require_lowercase' => (bool)$settings['password_require_lowercase'],
            'require_number' => (bool)$settings['password_require_number'],
            'require_symbol' => (bool)$settings['password_require_symbol'],
        ];
    }

    public function validatePasswordPolicy($password)
    {
        $password = (string)$password;
        $policy = $this->getPasswordPolicy();
        $errors = [];

        if (mb_strlen($password) < $policy['min_length']) {
            $errors[] = $this->tr(
                'パスワードは' . $policy['min_length'] . '文字以上で入力してください。',
                'Password must be at least ' . $policy['min_length'] . ' characters long.'
            );
        }
        if ($policy['require_uppercase'] && !preg_match('/[A-Z]/', $password)) {
            $errors[] = $this->tr('パスワードに英大文字を1文字以上含めてください。', 'Password must include at least one uppercase letter.');
        }
        if ($policy['require_lowercase'] && !preg_match('/[a-z]/', $password)) {
            $errors[] = $this->tr('パスワードに英小文字を1文字以上含めてください。', 'Password must include at least one lowercase letter.');
        }
        if ($policy['require_number'] && !preg_match('/\d/', $password)) {
            $errors[] = $this->tr('パスワードに数字を1文字以上含めてください。', 'Password must include at least one number.');
        }
        if ($policy['require_symbol'] && !preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = $this->tr('パスワードに記号を1文字以上含めてください。', 'Password must include at least one symbol.');
        }

        return $errors;
    }

    public function isAdminIpAllowed()
    {
        $settings = $this->getSecuritySettings();
        if (!$settings['admin_ip_restriction_enabled']) {
            return true;
        }

        $currentIp = $this->getClientIp();
        if ($currentIp === '') {
            return false;
        }

        return $this->isIpAllowed($currentIp, $settings['admin_ip_allowlist']);
    }

    private function enforceSessionTimeout()
    {
        $settings = $this->getSecuritySettings();
        $timeoutMinutes = (int)$settings['session_timeout_minutes'];
        if ($timeoutMinutes <= 0) {
            return true;
        }

        $lastActivity = (int)($_SESSION['last_activity'] ?? 0);
        if ($lastActivity <= 0) {
            return true;
        }

        if ((time() - $lastActivity) > ($timeoutMinutes * 60)) {
            $this->logout();
            return false;
        }

        return true;
    }

    private function hasSecurityColumns()
    {
        if ($this->securityColumnsReady !== null) {
            return $this->securityColumnsReady;
        }

        try {
            $requiredColumns = [
                'failed_login_attempts',
                'last_failed_login_at',
                'locked_until',
                'password_changed_at'
            ];

            foreach ($requiredColumns as $column) {
                $exists = $this->db->fetch(
                    "SELECT 1
                     FROM information_schema.COLUMNS
                     WHERE TABLE_SCHEMA = DATABASE()
                       AND TABLE_NAME = 'users'
                       AND COLUMN_NAME = ?
                     LIMIT 1",
                    [$column]
                );
                if (!$exists) {
                    $this->securityColumnsReady = false;
                    return false;
                }
            }

            $this->securityColumnsReady = true;
            return true;
        } catch (\Throwable $e) {
            $this->securityColumnsReady = false;
            return false;
        }
    }

    private function clearFailedLoginState($userId)
    {
        if (!$this->hasSecurityColumns()) {
            return;
        }
        $this->db->execute(
            "UPDATE users
             SET failed_login_attempts = 0,
                 last_failed_login_at = NULL,
                 locked_until = NULL,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = ?",
            [$userId]
        );
    }

    private function recordFailedLoginAttempt(array $user)
    {
        if (!$this->hasSecurityColumns()) {
            return;
        }

        $settings = $this->getSecuritySettings();
        $windowMinutes = max(1, (int)$settings['login_window_minutes']);
        $maxAttempts = max(1, (int)$settings['login_max_attempts']);
        $lockMinutes = max(1, (int)$settings['login_lock_minutes']);

        $attempts = (int)($user['failed_login_attempts'] ?? 0);
        $lastFailed = !empty($user['last_failed_login_at']) ? strtotime((string)$user['last_failed_login_at']) : 0;
        $withinWindow = $lastFailed > 0 && (time() - $lastFailed) <= ($windowMinutes * 60);
        $attempts = $withinWindow ? ($attempts + 1) : 1;

        $lockedUntil = null;
        if ($attempts >= $maxAttempts) {
            $lockedUntil = date('Y-m-d H:i:s', time() + ($lockMinutes * 60));
            $this->setLockedError($lockedUntil);
        }

        $this->db->execute(
            "UPDATE users
             SET failed_login_attempts = ?,
                 last_failed_login_at = NOW(),
                 locked_until = ?,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = ?",
            [$attempts, $lockedUntil, (int)$user['id']]
        );
    }

    private function isLocked(array $user)
    {
        if (!$this->hasSecurityColumns()) {
            return false;
        }

        $lockedUntil = (string)($user['locked_until'] ?? '');
        if ($lockedUntil === '') {
            return false;
        }

        $lockedTs = strtotime($lockedUntil);
        if ($lockedTs === false || $lockedTs <= time()) {
            return false;
        }

        return true;
    }

    private function setLockedError($lockedUntil)
    {
        $minutes = null;
        if (!empty($lockedUntil)) {
            $lockedTs = strtotime((string)$lockedUntil);
            if ($lockedTs !== false && $lockedTs > time()) {
                $minutes = (int)ceil(($lockedTs - time()) / 60);
            }
        }

        if ($minutes !== null && $minutes > 0) {
            $this->lastError = $this->tr(
                'ログインが一時的にロックされています（約' . $minutes . '分後に再試行できます）。',
                'Login is temporarily locked. Please try again in about ' . $minutes . ' minute(s).'
            );
            return;
        }

        $this->lastError = $this->tr(
            'ログインが一時的にロックされています。しばらくしてから再試行してください。',
            'Login is temporarily locked. Please try again later.'
        );
    }

    private function getSecuritySettings()
    {
        if ($this->securitySettings !== null) {
            return $this->securitySettings;
        }

        $defaults = [
            'password_min_length' => 8,
            'password_require_uppercase' => 1,
            'password_require_lowercase' => 1,
            'password_require_number' => 1,
            'password_require_symbol' => 0,
            'session_timeout_minutes' => 120,
            'login_max_attempts' => 5,
            'login_lock_minutes' => 15,
            'login_window_minutes' => 15,
            'admin_ip_restriction_enabled' => 0,
            'admin_ip_allowlist' => '',
        ];

        $settingKeyMap = [
            'security_password_min_length' => 'password_min_length',
            'security_password_require_uppercase' => 'password_require_uppercase',
            'security_password_require_lowercase' => 'password_require_lowercase',
            'security_password_require_number' => 'password_require_number',
            'security_password_require_symbol' => 'password_require_symbol',
            'security_session_timeout_minutes' => 'session_timeout_minutes',
            'security_login_max_attempts' => 'login_max_attempts',
            'security_login_lock_minutes' => 'login_lock_minutes',
            'security_login_window_minutes' => 'login_window_minutes',
            'security_admin_ip_restriction_enabled' => 'admin_ip_restriction_enabled',
            'security_admin_ip_allowlist' => 'admin_ip_allowlist',
        ];

        $settings = $defaults;

        try {
            $keys = array_keys($settingKeyMap);
            $placeholders = implode(',', array_fill(0, count($keys), '?'));
            $rows = $this->db->fetchAll(
                "SELECT setting_key, setting_value FROM settings WHERE setting_key IN ($placeholders)",
                $keys
            );

            foreach ($rows as $row) {
                $key = (string)($row['setting_key'] ?? '');
                if ($key === '' || !isset($settingKeyMap[$key])) {
                    continue;
                }
                $mapped = $settingKeyMap[$key];
                $value = (string)($row['setting_value'] ?? '');

                if (in_array($mapped, ['admin_ip_allowlist'], true)) {
                    $settings[$mapped] = $value;
                } elseif (in_array($mapped, ['password_min_length', 'session_timeout_minutes', 'login_max_attempts', 'login_lock_minutes', 'login_window_minutes'], true)) {
                    $settings[$mapped] = max(0, (int)$value);
                } else {
                    $settings[$mapped] = ($value === '1' || strtolower($value) === 'true') ? 1 : 0;
                }
            }
        } catch (\Throwable $e) {
            // settings table not ready yet
        }

        if ($settings['password_min_length'] < 8) {
            $settings['password_min_length'] = 8;
        }
        if ($settings['session_timeout_minutes'] < 1) {
            $settings['session_timeout_minutes'] = 120;
        }
        if ($settings['login_max_attempts'] < 1) {
            $settings['login_max_attempts'] = 5;
        }
        if ($settings['login_lock_minutes'] < 1) {
            $settings['login_lock_minutes'] = 15;
        }
        if ($settings['login_window_minutes'] < 1) {
            $settings['login_window_minutes'] = 15;
        }

        $this->securitySettings = $settings;
        return $this->securitySettings;
    }

    private function getClientIp()
    {
        $candidates = [];
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $candidates[] = $_SERVER['HTTP_CF_CONNECTING_IP'];
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $forwarded = explode(',', (string)$_SERVER['HTTP_X_FORWARDED_FOR']);
            foreach ($forwarded as $ip) {
                $candidates[] = trim($ip);
            }
        }
        if (!empty($_SERVER['REMOTE_ADDR'])) {
            $candidates[] = $_SERVER['REMOTE_ADDR'];
        }

        foreach ($candidates as $candidate) {
            if (filter_var($candidate, FILTER_VALIDATE_IP)) {
                return $candidate;
            }
        }

        return '';
    }

    private function isIpAllowed($currentIp, $allowlistRaw)
    {
        $entries = preg_split('/[\s,;]+/', (string)$allowlistRaw) ?: [];
        $entries = array_values(array_filter(array_map('trim', $entries), function ($entry) {
            return $entry !== '';
        }));

        if (empty($entries)) {
            return false;
        }

        foreach ($entries as $entry) {
            if ($this->matchIpEntry($currentIp, $entry)) {
                return true;
            }
        }

        return false;
    }

    private function matchIpEntry($ip, $entry)
    {
        if ($entry === '*') {
            return true;
        }

        if (strpos($entry, '/') !== false) {
            return $this->matchIpv4Cidr($ip, $entry);
        }

        return strcasecmp($ip, $entry) === 0;
    }

    private function matchIpv4Cidr($ip, $cidr)
    {
        if (strpos($cidr, '/') === false) {
            return false;
        }

        list($subnet, $prefix) = explode('/', $cidr, 2);
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) || !filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return false;
        }

        $prefix = (int)$prefix;
        if ($prefix < 0 || $prefix > 32) {
            return false;
        }

        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        if ($ipLong === false || $subnetLong === false) {
            return false;
        }

        $mask = $prefix === 0 ? 0 : (-1 << (32 - $prefix));
        return (($ipLong & $mask) === ($subnetLong & $mask));
    }

    private function tr($ja, $en)
    {
        if (function_exists('tr_text')) {
            return tr_text($ja, $en);
        }
        return $ja;
    }
}

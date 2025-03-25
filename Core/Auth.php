<?php
// core/Auth.php
namespace Core;

class Auth
{
    private static $instance = null;
    private $db;
    private $config;

    private function __construct()
    {
        $this->db = Database::getInstance();
        $this->config = require_once __DIR__ . '/../config/config.php';

        // セッション開始（まだ開始されていない場合）
        if (session_status() === PHP_SESSION_NONE) {
            session_name($this->config['auth']['session_name']);
            session_start();
        }
    }

    // シングルトンパターンでインスタンスを取得
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // ログイン処理
    public function login($username, $password, $remember = false)
    {
        // $sql = "SELECT * FROM users WHERE username = ? AND status = 'active' LIMIT 1";
        // $user = $this->db->fetch($sql, [$username]);
        // // デバッグ情報
        // error_log("Login attempt for user: $username");
        // 入力値をトリム
        $username = trim($username);
        $password = trim($password);

        $sql = "SELECT * FROM users WHERE username = ? AND status = 'active' LIMIT 1";
        $user = $this->db->fetch($sql, [$username]);

        // デバッグ情報
        error_log("Login attempt for user: $username");
        if ($user) {
            error_log("User found, password verification: " . ($password ? 'Password provided' : 'No password'));
            error_log("Stored hash: " . $user['password']);
            error_log("Password length: " . strlen($password));
            error_log("Password hex: " . bin2hex($password)); // 不可視文字を検出
            error_log("Verification result: " . (password_verify($password, $user['password']) ? 'Success' : 'Failed'));
        } else {
            error_log("User not found");
        }

        if ($user && password_verify($password, $user['password'])) {
            // セッションにユーザー情報を保存
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_name'] = $user['display_name'];

            // 最終ログイン日時を更新
            $this->db->execute(
                "UPDATE users SET last_login = NOW() WHERE id = ?",
                [$user['id']]
            );

            // Remember Me 機能
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                $expires = time() + ($this->config['auth']['remember_me_days'] * 86400);

                // トークンをDBに保存
                $this->db->execute(
                    "INSERT INTO user_tokens (user_id, token, expires_at) VALUES (?, ?, FROM_UNIXTIME(?))",
                    [$user['id'], password_hash($token, PASSWORD_DEFAULT), $expires]
                );

                // Cookieにトークンを保存
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

        return false;
    }

    // ログアウト処理
    public function logout()
    {
        // Remember Me トークンを削除
        if (isset($_COOKIE['remember_token'])) {
            list($user_id, $token) = explode(':', $_COOKIE['remember_token']);

            $this->db->execute(
                "DELETE FROM user_tokens WHERE user_id = ?",
                [$user_id]
            );

            // Cookieを削除
            setcookie('remember_token', '', time() - 3600, '/');
        }

        // セッション変数をクリア
        $_SESSION = [];

        // セッションCookieを削除
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

        // セッション破棄
        session_destroy();

        return true;
    }

    // 現在のユーザーが認証済みかチェック
    public function check()
    {
        return isset($_SESSION['user_id']);
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

        return $_SESSION['user_role'] === $role;
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

        list($user_id, $token) = explode(':', $_COOKIE['remember_token']);

        $sql = "SELECT u.*, t.token, t.expires_at 
                FROM users u 
                JOIN user_tokens t ON u.id = t.user_id 
                WHERE u.id = ? AND u.status = 'active' 
                AND t.expires_at > NOW() 
                ORDER BY t.expires_at DESC LIMIT 1";

        $result = $this->db->fetch($sql, [$user_id]);

        if ($result && password_verify($token, $result['token'])) {
            // セッションにユーザー情報を保存
            $_SESSION['user_id'] = $result['id'];
            $_SESSION['user_role'] = $result['role'];
            $_SESSION['user_name'] = $result['display_name'];

            // 最終ログイン日時を更新
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
}

-- Authentication security hardening
-- Adds login lock/session/password policy settings and required user columns.

SET @schema_name := DATABASE();

-- users.failed_login_attempts
SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = @schema_name
              AND TABLE_NAME = 'users'
              AND COLUMN_NAME = 'failed_login_attempts'
        ),
        'SELECT 1',
        'ALTER TABLE users ADD COLUMN failed_login_attempts INT NOT NULL DEFAULT 0 AFTER last_login'
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- users.last_failed_login_at
SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = @schema_name
              AND TABLE_NAME = 'users'
              AND COLUMN_NAME = 'last_failed_login_at'
        ),
        'SELECT 1',
        'ALTER TABLE users ADD COLUMN last_failed_login_at DATETIME NULL AFTER failed_login_attempts'
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- users.locked_until
SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = @schema_name
              AND TABLE_NAME = 'users'
              AND COLUMN_NAME = 'locked_until'
        ),
        'SELECT 1',
        'ALTER TABLE users ADD COLUMN locked_until DATETIME NULL AFTER last_failed_login_at'
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- users.password_changed_at
SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = @schema_name
              AND TABLE_NAME = 'users'
              AND COLUMN_NAME = 'password_changed_at'
        ),
        'SELECT 1',
        'ALTER TABLE users ADD COLUMN password_changed_at DATETIME NULL AFTER locked_until'
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Existing users: initialize password_changed_at to current time when null
UPDATE users
SET password_changed_at = COALESCE(password_changed_at, NOW());

INSERT INTO settings (setting_key, setting_value, description) VALUES
('security_password_min_length', '8', 'パスワード最小文字数'),
('security_password_require_uppercase', '1', 'パスワード英大文字必須'),
('security_password_require_lowercase', '1', 'パスワード英小文字必須'),
('security_password_require_number', '1', 'パスワード数字必須'),
('security_password_require_symbol', '0', 'パスワード記号必須'),
('security_session_timeout_minutes', '120', 'セッションタイムアウト分'),
('security_login_max_attempts', '5', 'ログイン失敗上限回数'),
('security_login_lock_minutes', '15', 'ログインロック分'),
('security_login_window_minutes', '15', 'ログイン失敗集計分'),
('security_admin_ip_restriction_enabled', '0', '管理者設定へのIP制限有効化'),
('security_admin_ip_allowlist', '', '管理者設定許可IP/CIDRリスト')
ON DUPLICATE KEY UPDATE
    setting_value = VALUES(setting_value),
    description = VALUES(description);

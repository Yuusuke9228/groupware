-- ユーザーごとのスケジュール表示色を追加
SET @schema_name := DATABASE();
SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = @schema_name
              AND TABLE_NAME = 'users'
              AND COLUMN_NAME = 'calendar_color'
        ),
        'SELECT 1',
        'ALTER TABLE users ADD COLUMN calendar_color VARCHAR(7) NOT NULL DEFAULT ''#3b82f6'' COMMENT ''スケジュール表示色'' AFTER mobile_phone'
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 既存データのうち不正値をデフォルトへ補正
UPDATE users
SET calendar_color = '#3b82f6'
WHERE calendar_color IS NULL
   OR calendar_color NOT REGEXP '^#[0-9A-Fa-f]{6}$';

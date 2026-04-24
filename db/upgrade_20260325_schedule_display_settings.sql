SET @col_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'notification_settings'
      AND COLUMN_NAME = 'schedule_view_start_time'
);
SET @sql := IF(
    @col_exists = 0,
    "ALTER TABLE notification_settings ADD COLUMN schedule_view_start_time TIME NOT NULL DEFAULT '00:00:00' AFTER email_notify",
    'DO 0'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'notification_settings'
      AND COLUMN_NAME = 'schedule_view_end_time'
);
SET @sql := IF(
    @col_exists = 0,
    "ALTER TABLE notification_settings ADD COLUMN schedule_view_end_time TIME NOT NULL DEFAULT '23:00:00' AFTER schedule_view_start_time",
    'DO 0'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

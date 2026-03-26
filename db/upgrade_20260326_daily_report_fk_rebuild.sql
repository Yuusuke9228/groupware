ALTER TABLE daily_reports
    MODIFY user_id INT NULL COMMENT '作成者ID';

SET @constraint_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'daily_reports'
      AND CONSTRAINT_NAME = 'daily_reports_ibfk_1'
);
SET @sql = IF(@constraint_exists > 0, 'ALTER TABLE daily_reports DROP FOREIGN KEY daily_reports_ibfk_1', 'DO 0');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @constraint_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'daily_reports'
      AND CONSTRAINT_NAME = 'fk_daily_reports_user'
);
SET @sql = IF(@constraint_exists > 0, 'ALTER TABLE daily_reports DROP FOREIGN KEY fk_daily_reports_user', 'DO 0');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE daily_reports
    ADD CONSTRAINT fk_daily_reports_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

SET @constraint_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'daily_report_comments'
      AND CONSTRAINT_NAME = 'daily_report_comments_ibfk_1'
);
SET @sql = IF(@constraint_exists > 0, 'ALTER TABLE daily_report_comments DROP FOREIGN KEY daily_report_comments_ibfk_1', 'DO 0');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @constraint_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'daily_report_comments'
      AND CONSTRAINT_NAME = 'fk_daily_report_comments_report'
);
SET @sql = IF(@constraint_exists > 0, 'ALTER TABLE daily_report_comments DROP FOREIGN KEY fk_daily_report_comments_report', 'DO 0');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE daily_report_comments
    ADD CONSTRAINT fk_daily_report_comments_report
        FOREIGN KEY (report_id) REFERENCES daily_reports(id) ON DELETE CASCADE;

SET @constraint_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'daily_report_likes'
      AND CONSTRAINT_NAME = 'daily_report_likes_ibfk_1'
);
SET @sql = IF(@constraint_exists > 0, 'ALTER TABLE daily_report_likes DROP FOREIGN KEY daily_report_likes_ibfk_1', 'DO 0');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @constraint_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'daily_report_likes'
      AND CONSTRAINT_NAME = 'fk_daily_report_likes_report'
);
SET @sql = IF(@constraint_exists > 0, 'ALTER TABLE daily_report_likes DROP FOREIGN KEY fk_daily_report_likes_report', 'DO 0');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE daily_report_likes
    ADD CONSTRAINT fk_daily_report_likes_report
        FOREIGN KEY (report_id) REFERENCES daily_reports(id) ON DELETE CASCADE;

SET @constraint_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'daily_report_permissions'
      AND CONSTRAINT_NAME = 'daily_report_permissions_ibfk_1'
);
SET @sql = IF(@constraint_exists > 0, 'ALTER TABLE daily_report_permissions DROP FOREIGN KEY daily_report_permissions_ibfk_1', 'DO 0');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @constraint_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'daily_report_permissions'
      AND CONSTRAINT_NAME = 'fk_daily_report_permissions_report'
);
SET @sql = IF(@constraint_exists > 0, 'ALTER TABLE daily_report_permissions DROP FOREIGN KEY fk_daily_report_permissions_report', 'DO 0');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE daily_report_permissions
    ADD CONSTRAINT fk_daily_report_permissions_report
        FOREIGN KEY (report_id) REFERENCES daily_reports(id) ON DELETE CASCADE;

SET @constraint_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'daily_report_reads'
      AND CONSTRAINT_NAME = 'daily_report_reads_ibfk_1'
);
SET @sql = IF(@constraint_exists > 0, 'ALTER TABLE daily_report_reads DROP FOREIGN KEY daily_report_reads_ibfk_1', 'DO 0');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @constraint_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'daily_report_reads'
      AND CONSTRAINT_NAME = 'fk_daily_report_reads_report'
);
SET @sql = IF(@constraint_exists > 0, 'ALTER TABLE daily_report_reads DROP FOREIGN KEY fk_daily_report_reads_report', 'DO 0');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE daily_report_reads
    ADD CONSTRAINT fk_daily_report_reads_report
        FOREIGN KEY (report_id) REFERENCES daily_reports(id) ON DELETE CASCADE;

SET @constraint_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'daily_report_schedules'
      AND CONSTRAINT_NAME = 'daily_report_schedules_ibfk_1'
);
SET @sql = IF(@constraint_exists > 0, 'ALTER TABLE daily_report_schedules DROP FOREIGN KEY daily_report_schedules_ibfk_1', 'DO 0');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @constraint_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'daily_report_schedules'
      AND CONSTRAINT_NAME = 'fk_daily_report_schedules_report'
);
SET @sql = IF(@constraint_exists > 0, 'ALTER TABLE daily_report_schedules DROP FOREIGN KEY fk_daily_report_schedules_report', 'DO 0');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE daily_report_schedules
    ADD CONSTRAINT fk_daily_report_schedules_report
        FOREIGN KEY (report_id) REFERENCES daily_reports(id) ON DELETE CASCADE;

SET @constraint_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'daily_report_tag_relations'
      AND CONSTRAINT_NAME = 'daily_report_tag_relations_ibfk_1'
);
SET @sql = IF(@constraint_exists > 0, 'ALTER TABLE daily_report_tag_relations DROP FOREIGN KEY daily_report_tag_relations_ibfk_1', 'DO 0');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @constraint_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'daily_report_tag_relations'
      AND CONSTRAINT_NAME = 'fk_daily_report_tag_relations_report'
);
SET @sql = IF(@constraint_exists > 0, 'ALTER TABLE daily_report_tag_relations DROP FOREIGN KEY fk_daily_report_tag_relations_report', 'DO 0');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE daily_report_tag_relations
    ADD CONSTRAINT fk_daily_report_tag_relations_report
        FOREIGN KEY (report_id) REFERENCES daily_reports(id) ON DELETE CASCADE;

SET @constraint_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'daily_report_tasks'
      AND CONSTRAINT_NAME = 'daily_report_tasks_ibfk_1'
);
SET @sql = IF(@constraint_exists > 0, 'ALTER TABLE daily_report_tasks DROP FOREIGN KEY daily_report_tasks_ibfk_1', 'DO 0');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @constraint_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'daily_report_tasks'
      AND CONSTRAINT_NAME = 'fk_daily_report_tasks_report'
);
SET @sql = IF(@constraint_exists > 0, 'ALTER TABLE daily_report_tasks DROP FOREIGN KEY fk_daily_report_tasks_report', 'DO 0');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE daily_report_tasks
    ADD CONSTRAINT fk_daily_report_tasks_report
        FOREIGN KEY (report_id) REFERENCES daily_reports(id) ON DELETE CASCADE;

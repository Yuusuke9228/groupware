SET @fk_name = (
    SELECT CONSTRAINT_NAME
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'daily_report_template_sections'
      AND COLUMN_NAME = 'template_id'
      AND REFERENCED_TABLE_NAME = 'daily_report_templates'
    LIMIT 1
);
SET @sql = IF(@fk_name IS NOT NULL, CONCAT('ALTER TABLE daily_report_template_sections DROP FOREIGN KEY ', @fk_name), 'DO 0');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE daily_report_template_sections
    ADD CONSTRAINT fk_daily_report_template_sections_template
        FOREIGN KEY (template_id) REFERENCES daily_report_templates(id) ON DELETE CASCADE;

SET @fk_name = (
    SELECT CONSTRAINT_NAME
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'web_database_record_data'
      AND COLUMN_NAME = 'record_id'
      AND REFERENCED_TABLE_NAME = 'web_database_records'
    LIMIT 1
);
SET @sql = IF(@fk_name IS NOT NULL, CONCAT('ALTER TABLE web_database_record_data DROP FOREIGN KEY ', @fk_name), 'DO 0');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE web_database_record_data
    ADD CONSTRAINT fk_web_database_record_data_record
        FOREIGN KEY (record_id) REFERENCES web_database_records(id) ON DELETE CASCADE;

-- 日報機能の構造化入力対応

-- daily_reports 追加カラム
SET @exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'daily_reports' AND COLUMN_NAME = 'summary_text');
SET @sql := IF(@exists = 0, 'ALTER TABLE daily_reports ADD COLUMN summary_text TEXT NULL COMMENT "本日の成果" AFTER is_template', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'daily_reports' AND COLUMN_NAME = 'issues_text');
SET @sql := IF(@exists = 0, 'ALTER TABLE daily_reports ADD COLUMN issues_text TEXT NULL COMMENT "課題・問題点" AFTER summary_text', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'daily_reports' AND COLUMN_NAME = 'tomorrow_plan_text');
SET @sql := IF(@exists = 0, 'ALTER TABLE daily_reports ADD COLUMN tomorrow_plan_text TEXT NULL COMMENT "明日の予定" AFTER issues_text', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'daily_reports' AND COLUMN_NAME = 'reflection_text');
SET @sql := IF(@exists = 0, 'ALTER TABLE daily_reports ADD COLUMN reflection_text TEXT NULL COMMENT "所感" AFTER tomorrow_plan_text', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'daily_reports' AND COLUMN_NAME = 'work_minutes');
SET @sql := IF(@exists = 0, 'ALTER TABLE daily_reports ADD COLUMN work_minutes INT UNSIGNED NOT NULL DEFAULT 0 COMMENT "実働分数" AFTER reflection_text', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'daily_reports' AND COLUMN_NAME = 'detail_json');
SET @sql := IF(@exists = 0, 'ALTER TABLE daily_reports ADD COLUMN detail_json LONGTEXT NULL COMMENT "構造化入力JSON" AFTER work_minutes', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'daily_reports' AND COLUMN_NAME = 'template_id');
SET @sql := IF(@exists = 0, 'ALTER TABLE daily_reports ADD COLUMN template_id INT NULL COMMENT "利用テンプレートID" AFTER detail_json', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'daily_reports' AND INDEX_NAME = 'idx_daily_reports_template_id');
SET @sql := IF(@exists = 0, 'ALTER TABLE daily_reports ADD INDEX idx_daily_reports_template_id (template_id)', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- daily_report_templates 追加カラム
SET @exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'daily_report_templates' AND COLUMN_NAME = 'description');
SET @sql := IF(@exists = 0, 'ALTER TABLE daily_report_templates ADD COLUMN description VARCHAR(255) NULL COMMENT "テンプレート説明" AFTER content', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'daily_report_templates' AND COLUMN_NAME = 'section_schema_json');
SET @sql := IF(@exists = 0, 'ALTER TABLE daily_report_templates ADD COLUMN section_schema_json LONGTEXT NULL COMMENT "構造化項目JSON" AFTER description', 'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 日報テンプレート構造化項目
CREATE TABLE IF NOT EXISTS daily_report_template_sections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_id INT NOT NULL COMMENT 'テンプレートID',
    section_key VARCHAR(80) NOT NULL COMMENT '項目キー',
    title VARCHAR(120) NOT NULL COMMENT '項目名',
    input_type ENUM('text', 'textarea', 'checklist', 'number', 'rating', 'toggle') NOT NULL DEFAULT 'textarea' COMMENT '入力種別',
    is_required BOOLEAN NOT NULL DEFAULT 0 COMMENT '必須フラグ',
    placeholder_text VARCHAR(255) NULL COMMENT '入力ヒント',
    default_value_text TEXT NULL COMMENT '初期値',
    options_json LONGTEXT NULL COMMENT '選択肢JSON',
    sort_order INT NOT NULL DEFAULT 1 COMMENT '表示順',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '作成日時',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新日時',
    FOREIGN KEY (template_id) REFERENCES daily_report_templates(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_daily_report_template_sections (template_id, section_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='日報テンプレート構造化項目';

-- 日報活動ログ
CREATE TABLE IF NOT EXISTS daily_report_activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL COMMENT '日報ID',
    start_time TIME NULL COMMENT '開始時刻',
    end_time TIME NULL COMMENT '終了時刻',
    activity_type VARCHAR(100) NULL COMMENT '活動分類',
    subject VARCHAR(255) NULL COMMENT '件名',
    result VARCHAR(255) NULL COMMENT '結果',
    memo TEXT NULL COMMENT 'メモ',
    sort_order INT NOT NULL DEFAULT 1 COMMENT '表示順',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '作成日時',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新日時',
    FOREIGN KEY (report_id) REFERENCES daily_reports(id) ON DELETE CASCADE,
    INDEX idx_daily_report_activity_logs_report_sort (report_id, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='日報活動ログ';

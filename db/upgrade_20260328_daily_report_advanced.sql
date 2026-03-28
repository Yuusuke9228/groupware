-- 日報機能拡張（分析・予実・添付・リッチテキスト） + WEBデータベース保存ビュー拡張

-- ========================================
-- daily_reports / templates: 保存形式
-- ========================================
SET @exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'daily_reports'
      AND COLUMN_NAME = 'content_format'
);
SET @sql := IF(
    @exists = 0,
    "ALTER TABLE daily_reports ADD COLUMN content_format ENUM('text','html') NOT NULL DEFAULT 'text' COMMENT '本文形式' AFTER content",
    'DO 0'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'daily_report_templates'
      AND COLUMN_NAME = 'content_format'
);
SET @sql := IF(
    @exists = 0,
    "ALTER TABLE daily_report_templates ADD COLUMN content_format ENUM('text','html') NOT NULL DEFAULT 'text' COMMENT '本文形式' AFTER content",
    'DO 0'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ========================================
-- 日報添付ファイル
-- ========================================
CREATE TABLE IF NOT EXISTS daily_report_attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL COMMENT '日報ID',
    uploaded_by INT NOT NULL COMMENT 'アップロードユーザーID',
    original_name VARCHAR(255) NOT NULL COMMENT '元ファイル名',
    stored_name VARCHAR(255) NOT NULL COMMENT '保存ファイル名',
    file_path VARCHAR(255) NOT NULL COMMENT '公開相対パス',
    mime_type VARCHAR(150) NULL COMMENT 'MIMEタイプ',
    file_size INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'ファイルサイズ',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '作成日時',
    FOREIGN KEY (report_id) REFERENCES daily_reports(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_daily_report_attachments_report (report_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='日報添付ファイル';

-- ========================================
-- 日報分析マスタ
-- ========================================
CREATE TABLE IF NOT EXISTS daily_report_industries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NULL COMMENT '業種コード',
    name VARCHAR(120) NOT NULL COMMENT '業種名',
    sort_order INT NOT NULL DEFAULT 1 COMMENT '表示順',
    is_active TINYINT(1) NOT NULL DEFAULT 1 COMMENT '有効フラグ',
    created_by INT NULL COMMENT '作成者',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_daily_report_industries_name (name),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='日報 業種マスタ';

CREATE TABLE IF NOT EXISTS daily_report_products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NULL COMMENT '商品コード',
    name VARCHAR(120) NOT NULL COMMENT '商品名',
    industry_id INT NULL COMMENT '業種ID',
    sort_order INT NOT NULL DEFAULT 1 COMMENT '表示順',
    is_active TINYINT(1) NOT NULL DEFAULT 1 COMMENT '有効フラグ',
    created_by INT NULL COMMENT '作成者',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_daily_report_products_name (name),
    FOREIGN KEY (industry_id) REFERENCES daily_report_industries(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='日報 商品マスタ';

CREATE TABLE IF NOT EXISTS daily_report_processes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NULL COMMENT 'プロセスコード',
    name VARCHAR(120) NOT NULL COMMENT 'プロセス名',
    sort_order INT NOT NULL DEFAULT 1 COMMENT '表示順',
    is_active TINYINT(1) NOT NULL DEFAULT 1 COMMENT '有効フラグ',
    created_by INT NULL COMMENT '作成者',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_daily_report_processes_name (name),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='日報 プロセスマスタ';

CREATE TABLE IF NOT EXISTS daily_report_projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NULL COMMENT '案件コード',
    name VARCHAR(150) NOT NULL COMMENT '案件名',
    industry_id INT NULL COMMENT '業種ID',
    product_id INT NULL COMMENT '商品ID',
    process_id INT NULL COMMENT '主プロセスID',
    sort_order INT NOT NULL DEFAULT 1 COMMENT '表示順',
    is_active TINYINT(1) NOT NULL DEFAULT 1 COMMENT '有効フラグ',
    created_by INT NULL COMMENT '作成者',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_daily_report_projects_name (name),
    FOREIGN KEY (industry_id) REFERENCES daily_report_industries(id) ON DELETE SET NULL,
    FOREIGN KEY (product_id) REFERENCES daily_report_products(id) ON DELETE SET NULL,
    FOREIGN KEY (process_id) REFERENCES daily_report_processes(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='日報 案件マスタ';

-- ========================================
-- 日報分析明細（1日報に複数行）
-- ========================================
CREATE TABLE IF NOT EXISTS daily_report_analysis_entries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL COMMENT '日報ID',
    project_id INT NULL COMMENT '案件ID',
    industry_id INT NULL COMMENT '業種ID',
    product_id INT NULL COMMENT '商品ID',
    process_id INT NULL COMMENT 'プロセスID',
    activity_type VARCHAR(100) NULL COMMENT '活動分類',
    planned_amount DECIMAL(15,2) NOT NULL DEFAULT 0 COMMENT '計画金額',
    actual_amount DECIMAL(15,2) NOT NULL DEFAULT 0 COMMENT '実績金額',
    planned_hours DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT '計画時間',
    actual_hours DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT '実績時間',
    quantity DECIMAL(15,2) NOT NULL DEFAULT 0 COMMENT '実績数量',
    memo TEXT NULL COMMENT 'メモ',
    sort_order INT NOT NULL DEFAULT 1 COMMENT '表示順',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (report_id) REFERENCES daily_reports(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES daily_report_projects(id) ON DELETE SET NULL,
    FOREIGN KEY (industry_id) REFERENCES daily_report_industries(id) ON DELETE SET NULL,
    FOREIGN KEY (product_id) REFERENCES daily_report_products(id) ON DELETE SET NULL,
    FOREIGN KEY (process_id) REFERENCES daily_report_processes(id) ON DELETE SET NULL,
    INDEX idx_daily_report_analysis_entries_report (report_id),
    INDEX idx_daily_report_analysis_entries_project (project_id),
    INDEX idx_daily_report_analysis_entries_product (product_id),
    INDEX idx_daily_report_analysis_entries_process (process_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='日報分析明細';

-- ========================================
-- 月次目標（予実）
-- ========================================
CREATE TABLE IF NOT EXISTS daily_report_monthly_targets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL COMMENT '対象ユーザー',
    target_month CHAR(7) NOT NULL COMMENT '対象月(YYYY-MM)',
    dimension_key VARCHAR(191) NOT NULL COMMENT '軸キー',
    project_id INT NULL COMMENT '案件ID',
    industry_id INT NULL COMMENT '業種ID',
    product_id INT NULL COMMENT '商品ID',
    process_id INT NULL COMMENT 'プロセスID',
    target_amount DECIMAL(15,2) NOT NULL DEFAULT 0 COMMENT '目標金額',
    target_hours DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT '目標時間',
    target_quantity DECIMAL(15,2) NOT NULL DEFAULT 0 COMMENT '目標数量',
    memo TEXT NULL COMMENT 'メモ',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES daily_report_projects(id) ON DELETE SET NULL,
    FOREIGN KEY (industry_id) REFERENCES daily_report_industries(id) ON DELETE SET NULL,
    FOREIGN KEY (product_id) REFERENCES daily_report_products(id) ON DELETE SET NULL,
    FOREIGN KEY (process_id) REFERENCES daily_report_processes(id) ON DELETE SET NULL,
    UNIQUE KEY uniq_daily_report_monthly_targets (user_id, target_month, dimension_key),
    INDEX idx_daily_report_monthly_targets_month (target_month)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='日報 月次目標';

-- ========================================
-- WEBデータベース保存ビュー拡張（範囲）
-- ========================================
SET @exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'web_database_views'
      AND COLUMN_NAME = 'scope_type'
);
SET @sql := IF(
    @exists = 0,
    "ALTER TABLE web_database_views ADD COLUMN scope_type ENUM('private','organization','global') NOT NULL DEFAULT 'private' COMMENT 'ビュー範囲' AFTER settings",
    'DO 0'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'web_database_views'
      AND COLUMN_NAME = 'organization_id'
);
SET @sql := IF(
    @exists = 0,
    "ALTER TABLE web_database_views ADD COLUMN organization_id INT NULL COMMENT '対象組織ID(scope=organization)' AFTER scope_type",
    'DO 0'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'web_database_views'
      AND INDEX_NAME = 'idx_web_database_views_scope'
);
SET @sql := IF(
    @exists = 0,
    "ALTER TABLE web_database_views ADD INDEX idx_web_database_views_scope (database_id, scope_type, organization_id)",
    'DO 0'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;


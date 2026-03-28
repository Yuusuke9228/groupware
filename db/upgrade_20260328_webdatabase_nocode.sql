-- WEBデータベース ノーコード強化アップグレード
-- 1) フォームレイアウト定義テーブル
-- 2) 集計・リレーション参照向けインデックス

CREATE TABLE IF NOT EXISTS web_database_form_layouts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    database_id INT NOT NULL COMMENT '対象データベースID',
    name VARCHAR(100) NOT NULL DEFAULT 'default' COMMENT 'レイアウト名',
    settings LONGTEXT NOT NULL COMMENT 'レイアウト定義(JSON)',
    is_default TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'デフォルトレイアウト',
    creator_id INT NULL COMMENT '作成者',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_web_database_form_layouts (database_id, name),
    INDEX idx_web_database_form_layouts_default (database_id, is_default),
    CONSTRAINT fk_web_database_form_layouts_database
        FOREIGN KEY (database_id) REFERENCES web_databases(id) ON DELETE CASCADE,
    CONSTRAINT fk_web_database_form_layouts_creator
        FOREIGN KEY (creator_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='WEBデータベース フォームレイアウト';

SET @exists := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'web_database_relations'
      AND INDEX_NAME = 'idx_web_database_relations_source_field_record'
);
SET @sql := IF(
    @exists = 0,
    "ALTER TABLE web_database_relations ADD INDEX idx_web_database_relations_source_field_record (source_field_id, source_record_id, sort_order)",
    'DO 0'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'web_database_record_data'
      AND INDEX_NAME = 'idx_web_database_record_data_field_value'
);
SET @sql := IF(
    @exists = 0,
    "ALTER TABLE web_database_record_data ADD INDEX idx_web_database_record_data_field_value (field_id, record_id)",
    'DO 0'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

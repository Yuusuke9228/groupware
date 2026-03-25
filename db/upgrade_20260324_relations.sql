-- WEBデータベース リレーション機能強化
-- 実行日: 2026-03-24

-- 1. フィールドタイプにリレーション系を追加
ALTER TABLE web_database_fields
MODIFY COLUMN type ENUM(
    'text',
    'textarea',
    'number',
    'date',
    'datetime',
    'select',
    'radio',
    'checkbox',
    'file',
    'user',
    'organization',
    'relation',
    'lookup',
    'calc',
    'url',
    'email',
    'phone',
    'currency',
    'percent',
    'auto_number'
) NOT NULL COMMENT 'フィールドタイプ';

-- 2. フィールドにリレーション設定カラムを追加
ALTER TABLE web_database_fields
ADD COLUMN relation_database_id INT NULL COMMENT 'リレーション先データベースID' AFTER is_sortable,
ADD COLUMN relation_field_id INT NULL COMMENT 'リレーション先表示フィールドID' AFTER relation_database_id,
ADD COLUMN relation_type ENUM('one_to_one', 'one_to_many', 'many_to_many') NULL DEFAULT 'one_to_many' COMMENT 'リレーションタイプ' AFTER relation_field_id,
ADD COLUMN lookup_relation_field_id INT NULL COMMENT 'ルックアップ元リレーションフィールドID' AFTER relation_type,
ADD COLUMN lookup_target_field_id INT NULL COMMENT 'ルックアップ参照先フィールドID' AFTER lookup_relation_field_id,
ADD COLUMN calc_formula TEXT NULL COMMENT '計算フィールドの式' AFTER lookup_target_field_id;

-- 3. リレーション中間テーブル（多対多用）
CREATE TABLE IF NOT EXISTS web_database_relations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    source_record_id INT NOT NULL COMMENT '元レコードID',
    source_field_id INT NOT NULL COMMENT '元フィールドID',
    target_record_id INT NOT NULL COMMENT '先レコードID',
    target_database_id INT NOT NULL COMMENT '先データベースID',
    sort_order INT NOT NULL DEFAULT 0 COMMENT '表示順',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (source_record_id) REFERENCES web_database_records(id) ON DELETE CASCADE,
    FOREIGN KEY (source_field_id) REFERENCES web_database_fields(id) ON DELETE CASCADE,
    FOREIGN KEY (target_record_id) REFERENCES web_database_records(id) ON DELETE CASCADE,
    FOREIGN KEY (target_database_id) REFERENCES web_databases(id) ON DELETE CASCADE,
    UNIQUE KEY uq_relation (source_record_id, source_field_id, target_record_id),
    INDEX idx_target (target_record_id, target_database_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='WEBデータベースリレーション';

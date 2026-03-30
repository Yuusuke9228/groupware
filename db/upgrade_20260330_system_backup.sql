CREATE TABLE IF NOT EXISTS system_backups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    executed_by INT NOT NULL,
    status ENUM('running', 'success', 'failed') NOT NULL DEFAULT 'running',
    file_name VARCHAR(255) NULL,
    file_path VARCHAR(512) NULL,
    file_size BIGINT UNSIGNED NOT NULL DEFAULT 0,
    error_message TEXT NULL,
    started_at DATETIME NOT NULL,
    finished_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_system_backups_user FOREIGN KEY (executed_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_system_backups_status_created (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='システムバックアップ履歴';

INSERT INTO settings (setting_key, setting_value, description)
VALUES ('backup_storage_path', 'storage/backups', 'バックアップ保存パス')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

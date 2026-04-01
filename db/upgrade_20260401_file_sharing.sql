-- File sharing links and storage governance settings
-- 2026-04-01

CREATE TABLE IF NOT EXISTS file_share_links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    file_id INT NOT NULL,
    token CHAR(48) NOT NULL,
    created_by INT NULL,
    expires_at DATETIME NULL,
    password_hash VARCHAR(255) NULL,
    max_downloads INT NULL,
    download_count INT NOT NULL DEFAULT 0,
    revoked_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_file_share_links_token (token),
    INDEX idx_file_share_links_file (file_id),
    INDEX idx_file_share_links_expires (expires_at),
    CONSTRAINT fk_file_share_links_file FOREIGN KEY (file_id) REFERENCES file_entries(id) ON DELETE CASCADE,
    CONSTRAINT fk_file_share_links_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS file_share_targets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    share_link_id INT NOT NULL,
    target_type ENUM('user', 'organization') NOT NULL,
    target_id INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_file_share_targets (share_link_id, target_type, target_id),
    INDEX idx_file_share_targets_target (target_type, target_id),
    CONSTRAINT fk_file_share_targets_link FOREIGN KEY (share_link_id) REFERENCES file_share_links(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO settings (setting_key, setting_value, description) VALUES
    ('files_max_upload_mb', '512', 'ファイル共有の1ファイル上限(MB)'),
    ('files_storage_quota_mb', '10240', 'ファイル共有の全体容量上限(MB)'),
    ('files_user_quota_mb', '2048', 'ファイル共有のユーザー別容量上限(MB)'),
    ('files_org_quota_mb', '5120', 'ファイル共有の組織別容量上限(MB)'),
    ('files_share_default_expiry_days', '7', '共有リンクの既定有効日数')
ON DUPLICATE KEY UPDATE setting_value = setting_value;

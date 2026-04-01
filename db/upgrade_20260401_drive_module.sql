-- Drive module (independent from File Manager)
-- 2026-04-01

CREATE TABLE IF NOT EXISTS drive_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    stored_name VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    mime_type VARCHAR(255) NULL,
    file_size BIGINT UNSIGNED NOT NULL DEFAULT 0,
    created_by INT NULL,
    owner_type ENUM('user', 'organization') NOT NULL DEFAULT 'user',
    owner_user_id INT NULL,
    owner_organization_id INT NULL,
    download_count INT NOT NULL DEFAULT 0,
    deleted_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_drive_items_owner_user (owner_user_id),
    INDEX idx_drive_items_owner_org (owner_organization_id),
    INDEX idx_drive_items_deleted (deleted_at),
    CONSTRAINT fk_drive_items_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_drive_items_owner_user FOREIGN KEY (owner_user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_drive_items_owner_org FOREIGN KEY (owner_organization_id) REFERENCES organizations(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS drive_share_links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    drive_item_id INT NOT NULL,
    token CHAR(48) NOT NULL,
    created_by INT NULL,
    expires_at DATETIME NULL,
    password_hash VARCHAR(255) NULL,
    max_downloads INT NULL,
    download_count INT NOT NULL DEFAULT 0,
    revoked_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_drive_share_links_token (token),
    INDEX idx_drive_share_links_item (drive_item_id),
    CONSTRAINT fk_drive_share_links_item FOREIGN KEY (drive_item_id) REFERENCES drive_items(id) ON DELETE CASCADE,
    CONSTRAINT fk_drive_share_links_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS drive_share_targets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    share_link_id INT NOT NULL,
    target_type ENUM('user', 'organization') NOT NULL,
    target_id INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_drive_share_targets (share_link_id, target_type, target_id),
    INDEX idx_drive_share_targets_target (target_type, target_id),
    CONSTRAINT fk_drive_share_targets_link FOREIGN KEY (share_link_id) REFERENCES drive_share_links(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO settings (setting_key, setting_value, description) VALUES
    ('drive_max_upload_mb', '1024', 'Driveの1ファイル容量上限(MB)'),
    ('drive_storage_quota_mb', '51200', 'Driveの全体容量上限(MB)'),
    ('drive_user_quota_mb', '10240', 'Driveのユーザー容量上限(MB)'),
    ('drive_org_quota_mb', '20480', 'Driveの組織容量上限(MB)'),
    ('drive_share_default_expiry_days', '7', 'Drive共有リンクの既定有効日数')
ON DUPLICATE KEY UPDATE setting_value = setting_value;

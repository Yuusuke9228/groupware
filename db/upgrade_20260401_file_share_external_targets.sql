-- File Sharing external recipients
-- 2026-04-01

CREATE TABLE IF NOT EXISTS drive_share_external_targets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    share_link_id INT NOT NULL,
    target_type ENUM('address_book', 'email') NOT NULL DEFAULT 'email',
    address_book_id INT NULL,
    email VARCHAR(255) NOT NULL,
    display_name VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_drive_share_external_targets (share_link_id, email),
    INDEX idx_drive_share_external_targets_share (share_link_id),
    INDEX idx_drive_share_external_targets_email (email),
    CONSTRAINT fk_drive_share_external_targets_link FOREIGN KEY (share_link_id) REFERENCES drive_share_links(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

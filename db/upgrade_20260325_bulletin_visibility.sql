-- 掲示板 公開対象制御
CREATE TABLE IF NOT EXISTS bulletin_post_targets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    target_type ENUM('organization', 'user') NOT NULL,
    target_id INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES bulletin_posts(id) ON DELETE CASCADE,
    INDEX idx_post_target (post_id, target_type),
    INDEX idx_target_lookup (target_type, target_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

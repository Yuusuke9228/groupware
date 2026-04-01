-- Repair migration for environments where checkout history table is missing
-- Safe to run multiple times

CREATE TABLE IF NOT EXISTS file_checkout_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    file_id INT NOT NULL,
    user_id INT NOT NULL,
    status ENUM('checked_out', 'released') NOT NULL,
    note VARCHAR(255) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_file_checkout_history_file FOREIGN KEY (file_id) REFERENCES file_entries(id) ON DELETE CASCADE,
    CONSTRAINT fk_file_checkout_history_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_file_checkout_history_file (file_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE file_entries
    ADD COLUMN approval_status ENUM('none', 'pending', 'approved', 'rejected') NOT NULL DEFAULT 'none' AFTER version,
    ADD COLUMN checked_out_by INT DEFAULT NULL AFTER approval_status,
    ADD COLUMN checked_out_at DATETIME DEFAULT NULL AFTER checked_out_by,
    ADD CONSTRAINT fk_file_entries_checked_out_by FOREIGN KEY (checked_out_by) REFERENCES users(id) ON DELETE SET NULL,
    ADD INDEX idx_file_entries_approval_status (approval_status),
    ADD INDEX idx_file_entries_checked_out_by (checked_out_by);

CREATE TABLE IF NOT EXISTS file_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    resource_type ENUM('folder', 'file') NOT NULL,
    resource_id INT NOT NULL,
    subject_type ENUM('organization', 'user') NOT NULL,
    subject_id INT NOT NULL,
    permission_type ENUM('view', 'edit', 'approve', 'admin') NOT NULL,
    created_by INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_file_permissions_resource (resource_type, resource_id),
    INDEX idx_file_permissions_subject (subject_type, subject_id),
    INDEX idx_file_permissions_permission (permission_type),
    UNIQUE KEY uk_file_permissions (resource_type, resource_id, subject_type, subject_id, permission_type),
    CONSTRAINT fk_file_permissions_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE IF NOT EXISTS file_approval_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    file_id INT NOT NULL,
    version_id INT NOT NULL,
    requested_by INT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    request_comment TEXT DEFAULT NULL,
    completed_at DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_file_approval_requests_file FOREIGN KEY (file_id) REFERENCES file_entries(id) ON DELETE CASCADE,
    CONSTRAINT fk_file_approval_requests_version FOREIGN KEY (version_id) REFERENCES file_versions(id) ON DELETE CASCADE,
    CONSTRAINT fk_file_approval_requests_requester FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_file_approval_requests_file (file_id, status),
    INDEX idx_file_approval_requests_version (version_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS file_approval_steps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    approver_id INT NOT NULL,
    step_order INT NOT NULL DEFAULT 1,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    comment TEXT DEFAULT NULL,
    acted_at DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_file_approval_steps_request FOREIGN KEY (request_id) REFERENCES file_approval_requests(id) ON DELETE CASCADE,
    CONSTRAINT fk_file_approval_steps_approver FOREIGN KEY (approver_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_file_approval_steps_request (request_id, step_order),
    INDEX idx_file_approval_steps_approver (approver_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

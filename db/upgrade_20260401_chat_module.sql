-- Chat module
-- 2026-04-01

CREATE TABLE IF NOT EXISTS chat_rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NULL,
    room_type ENUM('direct', 'group') NOT NULL DEFAULT 'group',
    created_by INT NOT NULL,
    deleted_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_chat_rooms_type (room_type),
    INDEX idx_chat_rooms_deleted (deleted_at),
    CONSTRAINT fk_chat_rooms_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chat_room_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    user_id INT NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_read_message_id INT NOT NULL DEFAULT 0,
    last_read_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_chat_room_members (room_id, user_id),
    INDEX idx_chat_room_members_user (user_id, is_active),
    CONSTRAINT fk_chat_room_members_room FOREIGN KEY (room_id) REFERENCES chat_rooms(id) ON DELETE CASCADE,
    CONSTRAINT fk_chat_room_members_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    user_id INT NOT NULL,
    message_text TEXT NULL,
    attachment_path VARCHAR(255) NULL,
    attachment_name VARCHAR(255) NULL,
    attachment_mime VARCHAR(191) NULL,
    attachment_size BIGINT NULL,
    deleted_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_chat_messages_room_id (room_id, id),
    INDEX idx_chat_messages_user_id (user_id, id),
    INDEX idx_chat_messages_deleted (deleted_at),
    CONSTRAINT fk_chat_messages_room FOREIGN KEY (room_id) REFERENCES chat_rooms(id) ON DELETE CASCADE,
    CONSTRAINT fk_chat_messages_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

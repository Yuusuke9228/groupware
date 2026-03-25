CREATE TABLE IF NOT EXISTS calendar_import_subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    source_url TEXT NOT NULL,
    is_enabled TINYINT(1) NOT NULL DEFAULT 1,
    sync_interval_minutes INT NOT NULL DEFAULT 30,
    visibility ENUM('public', 'private', 'specific') NOT NULL DEFAULT 'public',
    last_synced_at DATETIME DEFAULT NULL,
    last_error TEXT DEFAULT NULL,
    last_result VARCHAR(255) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_calendar_import_subscriptions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_calendar_import_subscriptions_user (user_id),
    INDEX idx_calendar_import_subscriptions_enabled (is_enabled, last_synced_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS calendar_import_event_map (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subscription_id INT NOT NULL,
    external_uid VARCHAR(255) NOT NULL,
    schedule_id INT NOT NULL,
    sync_hash CHAR(40) NOT NULL,
    last_seen_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    is_deleted TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_calendar_import_event_map_subscription FOREIGN KEY (subscription_id) REFERENCES calendar_import_subscriptions(id) ON DELETE CASCADE,
    CONSTRAINT fk_calendar_import_event_map_schedule FOREIGN KEY (schedule_id) REFERENCES schedules(id) ON DELETE CASCADE,
    UNIQUE KEY uk_calendar_import_event_map (subscription_id, external_uid),
    INDEX idx_calendar_import_event_map_schedule (schedule_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

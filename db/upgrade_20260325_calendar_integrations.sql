CREATE TABLE IF NOT EXISTS calendar_integration_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    ics_token VARCHAR(64) NOT NULL,
    feed_enabled TINYINT(1) NOT NULL DEFAULT 1,
    include_private TINYINT(1) NOT NULL DEFAULT 0,
    include_participant TINYINT(1) NOT NULL DEFAULT 1,
    include_organization TINYINT(1) NOT NULL DEFAULT 1,
    include_public TINYINT(1) NOT NULL DEFAULT 1,
    allow_ics_import TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_calendar_integration_user (user_id),
    UNIQUE KEY uniq_calendar_integration_token (ics_token),
    CONSTRAINT fk_calendar_integration_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

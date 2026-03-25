ALTER TABLE notification_settings
    ADD COLUMN schedule_view_start_time TIME NOT NULL DEFAULT '00:00:00' AFTER email_notify,
    ADD COLUMN schedule_view_end_time TIME NOT NULL DEFAULT '23:00:00' AFTER schedule_view_start_time;

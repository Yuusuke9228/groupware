-- 2026-02-11 feature upgrade
USE g_session;

ALTER TABLE notifications
    MODIFY COLUMN type ENUM('schedule', 'workflow', 'message', 'system', 'daily_report') NOT NULL COMMENT '通知タイプ';

CREATE TABLE IF NOT EXISTS workflow_template_organizations (
    template_id INT NOT NULL,
    organization_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (template_id, organization_id),
    FOREIGN KEY (template_id) REFERENCES workflow_templates(id) ON DELETE CASCADE,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='ワークフローテンプレート配布先組織';

CREATE TABLE IF NOT EXISTS daily_report_template_organizations (
    template_id INT NOT NULL,
    organization_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (template_id, organization_id),
    FOREIGN KEY (template_id) REFERENCES daily_report_templates(id) ON DELETE CASCADE,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='日報テンプレート配布先組織';

CREATE TABLE IF NOT EXISTS automation_jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    job_type ENUM('periodic_request', 'periodic_report', 'deadline_reminder') NOT NULL,
    frequency ENUM('daily', 'weekly', 'monthly') NOT NULL DEFAULT 'daily',
    run_at TIME NOT NULL DEFAULT '09:00:00',
    weekday TINYINT NULL,
    day_of_month TINYINT NULL,
    config_json JSON NULL,
    is_active BOOLEAN NOT NULL DEFAULT 1,
    created_by INT NOT NULL,
    last_run_at DATETIME NULL,
    next_run_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='自動化ジョブ';

CREATE TABLE IF NOT EXISTS automation_job_runs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    status ENUM('success', 'failed') NOT NULL,
    message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES automation_jobs(id) ON DELETE CASCADE,
    INDEX idx_automation_job_runs_job_id (job_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='自動化ジョブ実行履歴';

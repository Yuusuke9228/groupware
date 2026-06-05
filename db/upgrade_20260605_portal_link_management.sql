-- Portal link management settings page and version correction.
CREATE TABLE IF NOT EXISTS portal_links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(120) NOT NULL,
    url VARCHAR(1000) NOT NULL,
    description TEXT NULL,
    icon_class VARCHAR(80) NOT NULL DEFAULT 'fas fa-link',
    target VARCHAR(20) NOT NULL DEFAULT '_blank',
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT NOT NULL DEFAULT 1,
    created_by INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_active_order (is_active, sort_order, title)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO portal_links (title, url, description, icon_class, sort_order, is_active)
SELECT '社内ポータル', 'https://example.com/', '社内システムへの入口サンプルです。設定 > ポータルリンク から変更できます。', 'fas fa-building', 10, 1
FROM (SELECT 1) AS seed
WHERE NOT EXISTS (SELECT 1 FROM portal_links LIMIT 1);

INSERT INTO settings (setting_key, setting_value, description)
VALUES ('app_version', 'v1.0.0', 'Application version shown in footer')
ON DUPLICATE KEY UPDATE setting_value = 'v1.0.0';

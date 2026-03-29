-- PWA / SSO(SAML/OIDC) / SCIM 拡張
-- 後方互換を維持し、既存機能を壊さないため追加テーブル・追加設定のみを行う

CREATE TABLE IF NOT EXISTS external_identities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    provider ENUM('oidc', 'saml') NOT NULL,
    external_subject VARCHAR(191) NOT NULL,
    external_email VARCHAR(191) NULL,
    raw_attributes JSON NULL,
    last_login_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_external_identity (provider, external_subject),
    INDEX idx_external_identity_user (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = '外部IdPと内部ユーザーの紐付け';

CREATE TABLE IF NOT EXISTS push_subscriptions (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    endpoint VARCHAR(1024) NOT NULL,
    public_key VARCHAR(255) NULL,
    auth_token VARCHAR(255) NULL,
    content_encoding VARCHAR(50) NULL,
    expiration_time BIGINT NULL,
    user_agent VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_success_at DATETIME NULL,
    last_failure_at DATETIME NULL,
    failure_reason TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_push_subscription_endpoint (endpoint(255)),
    INDEX idx_push_subscription_user (user_id, is_active),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = 'Web Push購読情報';

CREATE TABLE IF NOT EXISTS scim_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    token_hash VARCHAR(255) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_used_at DATETIME NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_scim_token_hash (token_hash),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = 'SCIM APIトークン';

CREATE TABLE IF NOT EXISTS auth_audit_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    provider VARCHAR(32) NOT NULL,
    event_type VARCHAR(64) NOT NULL,
    status ENUM('success', 'failed') NOT NULL DEFAULT 'success',
    ip_address VARCHAR(64) NULL,
    user_agent VARCHAR(255) NULL,
    detail TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_auth_audit_provider (provider, event_type),
    INDEX idx_auth_audit_user (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = '認証監査ログ';

CREATE TABLE IF NOT EXISTS scim_audit_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    token_id INT NULL,
    actor VARCHAR(191) NULL,
    action VARCHAR(64) NOT NULL,
    resource_type VARCHAR(64) NOT NULL,
    resource_id VARCHAR(191) NULL,
    status_code INT NOT NULL DEFAULT 200,
    detail TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_scim_audit_action (action, resource_type),
    FOREIGN KEY (token_id) REFERENCES scim_tokens(id) ON DELETE SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COMMENT = 'SCIM監査ログ';

INSERT INTO settings (setting_key, setting_value, description) VALUES
('pwa_enabled', '0', 'PWA機能の有効化'),
('pwa_app_name', 'TeamSpace', 'PWAアプリ表示名'),
('pwa_short_name', 'TeamSpace', 'PWA短縮表示名'),
('pwa_theme_color', '#2b7de9', 'PWAテーマカラー'),
('pwa_background_color', '#ffffff', 'PWA背景色'),
('pwa_notifications_enabled', '0', 'PWA Push通知の有効化'),
('pwa_vapid_subject', 'mailto:admin@example.com', 'VAPID subject'),
('pwa_vapid_public_key', '', 'VAPID公開鍵'),
('pwa_vapid_private_key', '', 'VAPID秘密鍵'),
('sso_enabled', '0', 'SSO全体の有効化'),
('sso_provider', 'oidc', 'SSO方式(oidc/saml)'),
('sso_local_login_enabled', '1', '通常ローカルログイン許可'),
('sso_jit_provisioning', '1', '初回SSO時のユーザー自動作成'),
('sso_default_role', 'user', 'JIT時の既定ロール'),
('sso_default_organization_id', '1', 'JIT時の既定組織ID'),
('sso_attr_username', 'preferred_username', 'SSOユーザー名属性'),
('sso_attr_email', 'email', 'SSOメール属性'),
('sso_attr_display_name', 'name', 'SSO表示名属性'),
('oidc_enabled', '0', 'OIDC有効化'),
('oidc_issuer', '', 'OIDC Issuer URL'),
('oidc_client_id', '', 'OIDC Client ID'),
('oidc_client_secret', '', 'OIDC Client Secret'),
('oidc_redirect_uri', '', 'OIDC Redirect URI'),
('oidc_scopes', 'openid profile email', 'OIDC scope'),
('oidc_authorization_endpoint', '', 'OIDC Authorization Endpoint'),
('oidc_token_endpoint', '', 'OIDC Token Endpoint'),
('oidc_userinfo_endpoint', '', 'OIDC Userinfo Endpoint'),
('saml_enabled', '0', 'SAML有効化'),
('saml_idp_entity_id', '', 'SAML IdP entityID'),
('saml_idp_sso_url', '', 'SAML IdP SSO URL'),
('saml_idp_slo_url', '', 'SAML IdP SLO URL'),
('saml_idp_x509_cert', '', 'SAML IdP証明書'),
('saml_sp_entity_id', '', 'SAML SP entityID'),
('saml_sp_acs_url', '', 'SAML ACS URL'),
('saml_nameid_format', 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress', 'SAML NameID Format'),
('scim_enabled', '0', 'SCIM有効化'),
('scim_base_path', '/api/scim/v2', 'SCIM APIベースパス')
ON DUPLICATE KEY UPDATE description = VALUES(description);

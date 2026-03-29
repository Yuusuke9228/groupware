<?php
namespace Services;

use Core\Database;
use Models\AuthAuditLog;
use Models\ExternalIdentity;
use Models\Setting;

class SsoService
{
    private $db;
    private $settingModel;
    private $identityModel;
    private $auditLogModel;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->settingModel = new Setting();
        $this->identityModel = new ExternalIdentity();
        $this->auditLogModel = new AuthAuditLog();
    }

    public function getConfig()
    {
        return [
            'sso_enabled' => $this->toBool($this->settingModel->get('sso_enabled', '0')),
            'sso_provider' => (string)$this->settingModel->get('sso_provider', 'oidc'),
            'local_login_enabled' => $this->toBool($this->settingModel->get('sso_local_login_enabled', '1')),
            'jit_provisioning' => $this->toBool($this->settingModel->get('sso_jit_provisioning', '1')),
            'default_role' => (string)$this->settingModel->get('sso_default_role', 'user'),
            'default_organization_id' => (int)$this->settingModel->get('sso_default_organization_id', '1'),
            'attr_username' => (string)$this->settingModel->get('sso_attr_username', 'preferred_username'),
            'attr_email' => (string)$this->settingModel->get('sso_attr_email', 'email'),
            'attr_display_name' => (string)$this->settingModel->get('sso_attr_display_name', 'name'),
            'oidc_enabled' => $this->toBool($this->settingModel->get('oidc_enabled', '0')),
            'oidc_issuer' => trim((string)$this->settingModel->get('oidc_issuer', '')),
            'oidc_client_id' => trim((string)$this->settingModel->get('oidc_client_id', '')),
            'oidc_client_secret' => (string)$this->settingModel->get('oidc_client_secret', ''),
            'oidc_redirect_uri' => trim((string)$this->settingModel->get('oidc_redirect_uri', '')),
            'oidc_scopes' => trim((string)$this->settingModel->get('oidc_scopes', 'openid profile email')),
            'oidc_authorization_endpoint' => trim((string)$this->settingModel->get('oidc_authorization_endpoint', '')),
            'oidc_token_endpoint' => trim((string)$this->settingModel->get('oidc_token_endpoint', '')),
            'oidc_userinfo_endpoint' => trim((string)$this->settingModel->get('oidc_userinfo_endpoint', '')),
            'saml_enabled' => $this->toBool($this->settingModel->get('saml_enabled', '0')),
            'saml_idp_entity_id' => trim((string)$this->settingModel->get('saml_idp_entity_id', '')),
            'saml_idp_sso_url' => trim((string)$this->settingModel->get('saml_idp_sso_url', '')),
            'saml_idp_slo_url' => trim((string)$this->settingModel->get('saml_idp_slo_url', '')),
            'saml_idp_x509_cert' => trim((string)$this->settingModel->get('saml_idp_x509_cert', '')),
            'saml_sp_entity_id' => trim((string)$this->settingModel->get('saml_sp_entity_id', '')),
            'saml_sp_acs_url' => trim((string)$this->settingModel->get('saml_sp_acs_url', '')),
            'saml_nameid_format' => trim((string)$this->settingModel->get('saml_nameid_format', 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress')),
        ];
    }

    public function getAbsoluteUrl($path)
    {
        $configured = trim((string)$this->settingModel->getAppUrl());
        if ($configured !== '') {
            return rtrim($configured, '/') . '/' . ltrim($path, '/');
        }

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $scheme . '://' . $host . '/' . ltrim($path, '/');
    }

    public function extractAttribute(array $claims, $path, $default = null)
    {
        if ($path === '') {
            return $default;
        }
        if (array_key_exists($path, $claims)) {
            return $claims[$path];
        }
        // dot-path形式に対応
        $current = $claims;
        foreach (explode('.', $path) as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return $default;
            }
            $current = $current[$segment];
        }
        return $current;
    }

    public function resolveOrProvisionUser($provider, $subject, array $claims)
    {
        $subject = trim((string)$subject);
        if ($subject === '') {
            return [null, '外部ユーザー識別子(subject)が取得できませんでした。'];
        }

        $config = $this->getConfig();

        $identity = $this->identityModel->findByProviderAndSubject($provider, $subject);
        if ($identity) {
            $user = $this->db->fetch("SELECT * FROM users WHERE id = ? LIMIT 1", [(int)$identity['user_id']]);
            if ($user && $user['status'] === 'active') {
                $this->identityModel->upsert((int)$user['id'], $provider, $subject, $identity['external_email'] ?? null, $claims);
                return [$user, null];
            }
        }

        $email = trim((string)$this->extractAttribute($claims, $config['attr_email'], ''));
        $username = trim((string)$this->extractAttribute($claims, $config['attr_username'], ''));
        $displayName = trim((string)$this->extractAttribute($claims, $config['attr_display_name'], ''));

        if ($email !== '') {
            $existingByEmail = $this->db->fetch("SELECT * FROM users WHERE email = ? LIMIT 1", [$email]);
            if ($existingByEmail && $existingByEmail['status'] === 'active') {
                $this->identityModel->upsert((int)$existingByEmail['id'], $provider, $subject, $email, $claims);
                return [$existingByEmail, null];
            }
        }

        if ($username !== '') {
            $existingByUsername = $this->db->fetch("SELECT * FROM users WHERE username = ? LIMIT 1", [$username]);
            if ($existingByUsername && $existingByUsername['status'] === 'active') {
                $this->identityModel->upsert((int)$existingByUsername['id'], $provider, $subject, $email !== '' ? $email : null, $claims);
                return [$existingByUsername, null];
            }
        }

        if (!$config['jit_provisioning']) {
            return [null, 'ユーザーが未登録のためログインできません。管理者にお問い合わせください。'];
        }

        $role = in_array($config['default_role'], ['admin', 'manager', 'user'], true)
            ? $config['default_role']
            : 'user';
        $orgId = $config['default_organization_id'] > 0 ? $config['default_organization_id'] : 1;

        if ($username === '') {
            $base = $email !== '' ? strstr($email, '@', true) : ('sso_' . substr(sha1($subject), 0, 10));
            $username = $this->makeUniqueUsername($base ?: 'sso_user');
        } else {
            $username = $this->makeUniqueUsername($username);
        }

        if ($email === '') {
            $email = $username . '@invalid.local';
        }
        $email = $this->makeUniqueEmail($email);

        if ($displayName === '') {
            $displayName = $username;
        }

        $firstName = mb_substr($displayName, 0, 20);
        $lastName = 'SSO';

        $ok = $this->db->execute(
            "INSERT INTO users
             (username, password, email, first_name, last_name, display_name, organization_id, role, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')",
            [
                $username,
                password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT),
                $email,
                $firstName,
                $lastName,
                $displayName,
                $orgId,
                $role
            ]
        );

        if (!$ok) {
            return [null, 'SSOユーザーの自動作成に失敗しました。'];
        }

        $userId = (int)$this->db->lastInsertId();
        $this->db->execute(
            "INSERT IGNORE INTO user_organizations (user_id, organization_id, is_primary) VALUES (?, ?, 1)",
            [$userId, $orgId]
        );
        $this->identityModel->upsert($userId, $provider, $subject, $email, $claims);

        $user = $this->db->fetch("SELECT * FROM users WHERE id = ? LIMIT 1", [$userId]);
        return [$user, null];
    }

    public function finalizeLogin(array $user, $provider, $event = 'login')
    {
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['user_role'] = (string)$user['role'];
        $_SESSION['user_name'] = (string)$user['display_name'];
        session_regenerate_id(true);

        $this->db->execute("UPDATE users SET last_login = NOW() WHERE id = ?", [(int)$user['id']]);
        $this->auditLogModel->log($provider, $event, 'success', (int)$user['id']);
    }

    public function logAuthFailure($provider, $eventType, $detail)
    {
        $this->auditLogModel->log($provider, $eventType, 'failed', null, (string)$detail);
    }

    private function makeUniqueUsername($base)
    {
        $base = preg_replace('/[^a-zA-Z0-9_.-]/', '_', (string)$base);
        $base = trim($base, '_');
        if ($base === '') {
            $base = 'sso_user';
        }
        $candidate = $base;
        $suffix = 1;
        while ($this->db->fetch("SELECT id FROM users WHERE username = ? LIMIT 1", [$candidate])) {
            $suffix++;
            $candidate = $base . '_' . $suffix;
        }
        return $candidate;
    }

    private function makeUniqueEmail($email)
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email = 'sso_' . substr(sha1($email), 0, 10) . '@invalid.local';
        }

        $candidate = $email;
        $suffix = 1;
        while ($this->db->fetch("SELECT id FROM users WHERE email = ? LIMIT 1", [$candidate])) {
            $suffix++;
            $parts = explode('@', $email, 2);
            $local = $parts[0];
            $domain = $parts[1] ?? 'invalid.local';
            $candidate = $local . '+' . $suffix . '@' . $domain;
        }

        return $candidate;
    }

    private function toBool($value)
    {
        if (is_bool($value)) {
            return $value;
        }
        $normalized = filter_var((string)$value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        return $normalized === null ? false : $normalized;
    }
}

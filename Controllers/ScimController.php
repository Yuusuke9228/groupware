<?php
namespace Controllers;

use Core\Controller;
use Core\Database;
use Models\ScimAuditLog;
use Models\ScimToken;
use Models\Setting;

class ScimController extends Controller
{
    private $db;
    private $settingModel;
    private $tokenModel;
    private $auditModel;

    public function __construct()
    {
        parent::__construct();
        $this->db = Database::getInstance();
        $this->settingModel = new Setting();
        $this->tokenModel = new ScimToken();
        $this->auditModel = new ScimAuditLog();
    }

    public function apiServiceProviderConfig()
    {
        [$token, $error] = $this->authenticate();
        if ($error !== null) {
            return $error;
        }

        $this->audit($token, 'service_provider_config', 'ServiceProviderConfig', null, 200, null);

        return [
            'schemas' => ['urn:ietf:params:scim:schemas:core:2.0:ServiceProviderConfig'],
            'patch' => ['supported' => true],
            'bulk' => ['supported' => false, 'maxOperations' => 0, 'maxPayloadSize' => 0],
            'filter' => ['supported' => true, 'maxResults' => 200],
            'changePassword' => ['supported' => false],
            'sort' => ['supported' => true],
            'etag' => ['supported' => false],
            'authenticationSchemes' => [[
                'name' => 'OAuth Bearer Token',
                'description' => 'Bearer token for SCIM API',
                'specUri' => 'https://datatracker.ietf.org/doc/html/rfc6750',
                'type' => 'oauthbearertoken',
                'primary' => true
            ]]
        ];
    }

    public function apiListUsers()
    {
        [$token, $error] = $this->authenticate();
        if ($error !== null) {
            return $error;
        }

        $startIndex = max(1, (int)($_GET['startIndex'] ?? 1));
        $count = max(1, min(200, (int)($_GET['count'] ?? 50)));
        $filter = trim((string)($_GET['filter'] ?? ''));

        $params = [];
        $where = "WHERE 1=1";
        if ($filter !== '' && preg_match('/^userName\s+eq\s+"([^"]+)"$/', $filter, $m)) {
            $where .= " AND username = ?";
            $params[] = $m[1];
        }

        $total = $this->db->fetch("SELECT COUNT(*) AS c FROM users {$where}", $params);
        $offset = $startIndex - 1;
        $rows = $this->db->fetchAll(
            "SELECT * FROM users {$where} ORDER BY id ASC LIMIT {$count} OFFSET {$offset}",
            $params
        );

        $resources = [];
        foreach ($rows as $row) {
            $resources[] = $this->toScimUserResource($row);
        }

        $this->audit($token, 'list_users', 'User', null, 200, "count={$count}");

        return [
            'schemas' => ['urn:ietf:params:scim:api:messages:2.0:ListResponse'],
            'totalResults' => (int)($total['c'] ?? 0),
            'startIndex' => $startIndex,
            'itemsPerPage' => count($resources),
            'Resources' => $resources
        ];
    }

    public function apiGetUser($params)
    {
        [$token, $error] = $this->authenticate();
        if ($error !== null) {
            return $error;
        }

        $id = (int)($params['id'] ?? 0);
        $user = $this->db->fetch("SELECT * FROM users WHERE id = ? LIMIT 1", [$id]);
        if (!$user) {
            $this->audit($token, 'get_user', 'User', (string)$id, 404, 'not found');
            return $this->scimError('Resource not found', 404);
        }

        $this->audit($token, 'get_user', 'User', (string)$id, 200, null);
        return $this->toScimUserResource($user);
    }

    public function apiCreateUser($params, $data)
    {
        [$token, $error] = $this->authenticate();
        if ($error !== null) {
            return $error;
        }

        $userName = trim((string)($data['userName'] ?? ''));
        if ($userName === '') {
            return $this->scimError('userName is required', 400, 'invalidValue');
        }

        if ($this->db->fetch("SELECT id FROM users WHERE username = ? LIMIT 1", [$userName])) {
            return $this->scimError('userName already exists', 409, 'uniqueness');
        }

        $email = $this->extractPrimaryEmail($data);
        if ($email === '') {
            $email = $userName . '@invalid.local';
        }
        if ($this->db->fetch("SELECT id FROM users WHERE email = ? LIMIT 1", [$email])) {
            $email = $userName . '+' . time() . '@invalid.local';
        }

        $displayName = trim((string)($data['displayName'] ?? $userName));
        $givenName = trim((string)($data['name']['givenName'] ?? $displayName));
        $familyName = trim((string)($data['name']['familyName'] ?? 'SCIM'));
        $active = !array_key_exists('active', $data) ? true : (bool)$data['active'];
        $status = $active ? 'active' : 'inactive';

        $ok = $this->db->execute(
            "INSERT INTO users
             (username, password, email, first_name, last_name, display_name, organization_id, role, status)
             VALUES (?, ?, ?, ?, ?, ?, 1, 'user', ?)",
            [
                $userName,
                password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT),
                $email,
                $givenName !== '' ? $givenName : $userName,
                $familyName !== '' ? $familyName : 'SCIM',
                $displayName,
                $status
            ]
        );

        if (!$ok) {
            return $this->scimError('failed to create user', 500);
        }

        $id = (int)$this->db->lastInsertId();
        $this->db->execute(
            "INSERT IGNORE INTO user_organizations (user_id, organization_id, is_primary) VALUES (?, 1, 1)",
            [$id]
        );
        $user = $this->db->fetch("SELECT * FROM users WHERE id = ? LIMIT 1", [$id]);

        $this->audit($token, 'create_user', 'User', (string)$id, 201, $userName);
        $resource = $this->toScimUserResource($user);
        $resource['code'] = 201;
        return $resource;
    }

    public function apiPutUser($params, $data)
    {
        [$token, $error] = $this->authenticate();
        if ($error !== null) {
            return $error;
        }

        $id = (int)($params['id'] ?? 0);
        $user = $this->db->fetch("SELECT * FROM users WHERE id = ? LIMIT 1", [$id]);
        if (!$user) {
            return $this->scimError('Resource not found', 404);
        }

        $userName = trim((string)($data['userName'] ?? $user['username']));
        $displayName = trim((string)($data['displayName'] ?? $user['display_name']));
        $givenName = trim((string)($data['name']['givenName'] ?? $user['first_name']));
        $familyName = trim((string)($data['name']['familyName'] ?? $user['last_name']));
        $email = $this->extractPrimaryEmail($data);
        if ($email === '') {
            $email = (string)$user['email'];
        }
        $active = !array_key_exists('active', $data) ? ($user['status'] === 'active') : (bool)$data['active'];
        $status = $active ? 'active' : 'inactive';

        $dup = $this->db->fetch(
            "SELECT id FROM users WHERE username = ? AND id <> ? LIMIT 1",
            [$userName, $id]
        );
        if ($dup) {
            return $this->scimError('userName already exists', 409, 'uniqueness');
        }

        $dupEmail = $this->db->fetch(
            "SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1",
            [$email, $id]
        );
        if ($dupEmail) {
            return $this->scimError('email already exists', 409, 'uniqueness');
        }

        $this->db->execute(
            "UPDATE users
             SET username = ?, email = ?, first_name = ?, last_name = ?, display_name = ?, status = ?, updated_at = NOW()
             WHERE id = ?",
            [$userName, $email, $givenName, $familyName, $displayName, $status, $id]
        );

        $updated = $this->db->fetch("SELECT * FROM users WHERE id = ? LIMIT 1", [$id]);
        $this->audit($token, 'put_user', 'User', (string)$id, 200, null);
        return $this->toScimUserResource($updated);
    }

    public function apiPatchUser($params, $data)
    {
        [$token, $error] = $this->authenticate();
        if ($error !== null) {
            return $error;
        }

        $id = (int)($params['id'] ?? 0);
        $user = $this->db->fetch("SELECT * FROM users WHERE id = ? LIMIT 1", [$id]);
        if (!$user) {
            return $this->scimError('Resource not found', 404);
        }

        $active = $user['status'] === 'active';
        $displayName = (string)$user['display_name'];
        $email = (string)$user['email'];

        $operations = $data['Operations'] ?? [];
        if (!is_array($operations)) {
            return $this->scimError('invalid patch payload', 400);
        }

        foreach ($operations as $op) {
            $operation = strtolower((string)($op['op'] ?? ''));
            $path = (string)($op['path'] ?? '');
            $value = $op['value'] ?? null;

            if ($operation !== 'replace') {
                continue;
            }

            if ($path === 'active' || ($path === '' && is_array($value) && array_key_exists('active', $value))) {
                $active = (bool)($path === 'active' ? $value : $value['active']);
            }
            if ($path === 'displayName' || ($path === '' && is_array($value) && array_key_exists('displayName', $value))) {
                $displayName = trim((string)($path === 'displayName' ? $value : $value['displayName']));
            }
            if ($path === 'emails' && is_array($value)) {
                $email = $this->extractPrimaryEmail(['emails' => $value]) ?: $email;
            }
        }

        $this->db->execute(
            "UPDATE users SET status = ?, display_name = ?, email = ?, updated_at = NOW() WHERE id = ?",
            [$active ? 'active' : 'inactive', $displayName, $email, $id]
        );

        $updated = $this->db->fetch("SELECT * FROM users WHERE id = ? LIMIT 1", [$id]);
        $this->audit($token, 'patch_user', 'User', (string)$id, 200, null);
        return $this->toScimUserResource($updated);
    }

    public function apiDeleteUser($params)
    {
        [$token, $error] = $this->authenticate();
        if ($error !== null) {
            return $error;
        }

        $id = (int)($params['id'] ?? 0);
        $user = $this->db->fetch("SELECT * FROM users WHERE id = ? LIMIT 1", [$id]);
        if (!$user) {
            return $this->scimError('Resource not found', 404);
        }

        $this->db->execute("UPDATE users SET status = 'inactive', updated_at = NOW() WHERE id = ?", [$id]);
        $this->audit($token, 'delete_user', 'User', (string)$id, 204, null);
        return ['success' => true, 'code' => 204];
    }

    private function authenticate()
    {
        $scimEnabled = filter_var((string)$this->settingModel->get('scim_enabled', '0'), FILTER_VALIDATE_BOOLEAN);
        if (!$scimEnabled) {
            return [null, $this->scimError('SCIM is disabled', 403)];
        }

        $authorization = (string)($_SERVER['HTTP_AUTHORIZATION'] ?? '');
        if ($authorization === '' && function_exists('getallheaders')) {
            $headers = getallheaders();
            $authorization = (string)($headers['Authorization'] ?? $headers['authorization'] ?? '');
        }

        if (!preg_match('/Bearer\s+(.+)$/i', $authorization, $m)) {
            return [null, $this->scimError('Bearer token required', 401)];
        }

        $token = $this->tokenModel->verify(trim((string)$m[1]));
        if (!$token) {
            return [null, $this->scimError('Invalid token', 401)];
        }

        return [$token, null];
    }

    private function toScimUserResource(array $user)
    {
        return [
            'schemas' => ['urn:ietf:params:scim:schemas:core:2.0:User'],
            'id' => (string)$user['id'],
            'userName' => (string)$user['username'],
            'displayName' => (string)$user['display_name'],
            'name' => [
                'givenName' => (string)$user['first_name'],
                'familyName' => (string)$user['last_name'],
                'formatted' => trim((string)$user['display_name'])
            ],
            'emails' => [[
                'value' => (string)$user['email'],
                'primary' => true,
                'type' => 'work'
            ]],
            'active' => (string)$user['status'] === 'active',
            'meta' => [
                'resourceType' => 'User',
                'created' => (string)($user['created_at'] ?? ''),
                'lastModified' => (string)($user['updated_at'] ?? ''),
                'location' => BASE_PATH . '/api/scim/v2/Users/' . (int)$user['id']
            ]
        ];
    }

    private function extractPrimaryEmail(array $payload)
    {
        $emails = $payload['emails'] ?? [];
        if (is_array($emails)) {
            foreach ($emails as $mail) {
                if (is_array($mail) && !empty($mail['primary']) && !empty($mail['value'])) {
                    return trim((string)$mail['value']);
                }
            }
            foreach ($emails as $mail) {
                if (is_array($mail) && !empty($mail['value'])) {
                    return trim((string)$mail['value']);
                }
            }
        }
        return trim((string)($payload['email'] ?? ''));
    }

    private function scimError($detail, $statusCode, $scimType = null)
    {
        $error = [
            'schemas' => ['urn:ietf:params:scim:api:messages:2.0:Error'],
            'status' => (string)$statusCode,
            'detail' => (string)$detail,
            'code' => (int)$statusCode
        ];
        if ($scimType) {
            $error['scimType'] = (string)$scimType;
        }
        return $error;
    }

    private function audit($token, $action, $resourceType, $resourceId, $statusCode, $detail)
    {
        $tokenId = isset($token['id']) ? (int)$token['id'] : null;
        $actor = $token['name'] ?? null;
        $this->auditModel->log($tokenId, $actor, $action, $resourceType, $resourceId, (int)$statusCode, $detail);
    }
}

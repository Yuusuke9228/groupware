<?php
namespace Models;

use Core\Database;

class ScimAuditLog
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function log($tokenId, $actor, $action, $resourceType, $resourceId, $statusCode, $detail = null)
    {
        try {
            return $this->db->execute(
                "INSERT INTO scim_audit_logs
                 (token_id, actor, action, resource_type, resource_id, status_code, detail)
                 VALUES (?, ?, ?, ?, ?, ?, ?)",
                [
                    $tokenId ? (int)$tokenId : null,
                    $actor !== null ? (string)$actor : null,
                    (string)$action,
                    (string)$resourceType,
                    $resourceId !== null ? (string)$resourceId : null,
                    (int)$statusCode,
                    $detail !== null ? (string)$detail : null
                ]
            );
        } catch (\Throwable $e) {
            return false;
        }
    }
}

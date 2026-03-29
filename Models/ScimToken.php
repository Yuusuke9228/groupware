<?php
namespace Models;

use Core\Database;

class ScimToken
{
    private $db;
    private $schemaReady = null;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function listAll()
    {
        if (!$this->isSchemaReady()) {
            return [];
        }

        return $this->db->fetchAll(
            "SELECT id, name, is_active, last_used_at, created_by, created_at, updated_at
             FROM scim_tokens
             ORDER BY id DESC"
        );
    }

    public function createToken($name, $createdBy)
    {
        if (!$this->isSchemaReady()) {
            return null;
        }

        $plain = bin2hex(random_bytes(24));
        $hash = password_hash($plain, PASSWORD_DEFAULT);

        $ok = $this->db->execute(
            "INSERT INTO scim_tokens (name, token_hash, created_by, is_active)
             VALUES (?, ?, ?, 1)",
            [trim((string)$name) !== '' ? trim((string)$name) : 'SCIM Token', $hash, $createdBy ? (int)$createdBy : null]
        );

        if (!$ok) {
            return null;
        }

        return [
            'id' => (int)$this->db->lastInsertId(),
            'token' => $plain
        ];
    }

    public function toggleActive($id, $active)
    {
        if (!$this->isSchemaReady()) {
            return false;
        }

        return $this->db->execute(
            "UPDATE scim_tokens SET is_active = ?, updated_at = NOW() WHERE id = ?",
            [(int)((bool)$active), (int)$id]
        );
    }

    public function verify($plainToken)
    {
        if (!$this->isSchemaReady()) {
            return null;
        }

        $rows = $this->db->fetchAll("SELECT * FROM scim_tokens WHERE is_active = 1");
        foreach ($rows as $row) {
            if (password_verify((string)$plainToken, (string)$row['token_hash'])) {
                $this->db->execute(
                    "UPDATE scim_tokens SET last_used_at = NOW() WHERE id = ?",
                    [(int)$row['id']]
                );
                return $row;
            }
        }

        return null;
    }

    public function isSchemaReady()
    {
        if ($this->schemaReady !== null) {
            return (bool)$this->schemaReady;
        }

        try {
            $row = $this->db->fetch(
                "SELECT 1 AS ready
                 FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'scim_tokens'
                 LIMIT 1"
            );
            $this->schemaReady = !empty($row);
        } catch (\Throwable $e) {
            $this->schemaReady = false;
        }

        return (bool)$this->schemaReady;
    }
}

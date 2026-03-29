<?php
namespace Models;

use Core\Database;

class ExternalIdentity
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findByProviderAndSubject($provider, $subject)
    {
        return $this->db->fetch(
            "SELECT * FROM external_identities WHERE provider = ? AND external_subject = ? LIMIT 1",
            [(string)$provider, (string)$subject]
        );
    }

    public function upsert($userId, $provider, $subject, $email = null, array $rawAttributes = [])
    {
        $existing = $this->findByProviderAndSubject($provider, $subject);
        $json = !empty($rawAttributes)
            ? json_encode($rawAttributes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : null;

        if ($existing) {
            return $this->db->execute(
                "UPDATE external_identities
                 SET user_id = ?, external_email = ?, raw_attributes = ?, last_login_at = NOW()
                 WHERE id = ?",
                [(int)$userId, $email, $json, (int)$existing['id']]
            );
        }

        return $this->db->execute(
            "INSERT INTO external_identities
             (user_id, provider, external_subject, external_email, raw_attributes, last_login_at)
             VALUES (?, ?, ?, ?, ?, NOW())",
            [(int)$userId, (string)$provider, (string)$subject, $email, $json]
        );
    }
}

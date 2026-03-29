<?php
namespace Models;

use Core\Database;

class PushSubscription
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function upsert($userId, array $payload)
    {
        $endpoint = trim((string)($payload['endpoint'] ?? ''));
        if ($endpoint === '') {
            return false;
        }

        $keys = $payload['keys'] ?? [];
        $publicKey = (string)($keys['p256dh'] ?? '');
        $authToken = (string)($keys['auth'] ?? '');
        $encoding = (string)($payload['contentEncoding'] ?? 'aesgcm');
        $expirationTime = isset($payload['expirationTime']) && $payload['expirationTime'] !== ''
            ? (int)$payload['expirationTime']
            : null;
        $userAgent = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);

        $existing = $this->db->fetch(
            "SELECT id FROM push_subscriptions WHERE endpoint = ? LIMIT 1",
            [$endpoint]
        );

        if ($existing) {
            return $this->db->execute(
                "UPDATE push_subscriptions
                 SET user_id = ?, public_key = ?, auth_token = ?, content_encoding = ?,
                     expiration_time = ?, user_agent = ?, is_active = 1, updated_at = NOW()
                 WHERE id = ?",
                [
                    (int)$userId,
                    $publicKey,
                    $authToken,
                    $encoding,
                    $expirationTime,
                    $userAgent,
                    (int)$existing['id']
                ]
            );
        }

        return $this->db->execute(
            "INSERT INTO push_subscriptions
             (user_id, endpoint, public_key, auth_token, content_encoding, expiration_time, user_agent, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, 1)",
            [(int)$userId, $endpoint, $publicKey, $authToken, $encoding, $expirationTime, $userAgent]
        );
    }

    public function deactivateByEndpoint($userId, $endpoint)
    {
        return $this->db->execute(
            "UPDATE push_subscriptions
             SET is_active = 0, updated_at = NOW()
             WHERE user_id = ? AND endpoint = ?",
            [(int)$userId, (string)$endpoint]
        );
    }

    public function getActiveByUserId($userId)
    {
        return $this->db->fetchAll(
            "SELECT * FROM push_subscriptions WHERE user_id = ? AND is_active = 1 ORDER BY id DESC",
            [(int)$userId]
        );
    }

    public function markSuccess($id)
    {
        return $this->db->execute(
            "UPDATE push_subscriptions
             SET last_success_at = NOW(), failure_reason = NULL
             WHERE id = ?",
            [(int)$id]
        );
    }

    public function markFailure($id, $reason)
    {
        return $this->db->execute(
            "UPDATE push_subscriptions
             SET last_failure_at = NOW(), failure_reason = ?, is_active = IF(? REGEXP '404|410', 0, is_active)
             WHERE id = ?",
            [(string)$reason, (string)$reason, (int)$id]
        );
    }
}

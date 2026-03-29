<?php
namespace Models;

use Core\Database;

class AuthAuditLog
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function log($provider, $eventType, $status = 'success', $userId = null, $detail = null)
    {
        $ip = substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 64);
        $ua = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);

        try {
            return $this->db->execute(
                "INSERT INTO auth_audit_logs
                 (user_id, provider, event_type, status, ip_address, user_agent, detail)
                 VALUES (?, ?, ?, ?, ?, ?, ?)",
                [
                    $userId ? (int)$userId : null,
                    (string)$provider,
                    (string)$eventType,
                    $status === 'failed' ? 'failed' : 'success',
                    $ip,
                    $ua,
                    $detail
                ]
            );
        } catch (\Throwable $e) {
            return false;
        }
    }
}

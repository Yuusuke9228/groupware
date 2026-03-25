<?php
namespace Models;

use Core\Database;

class CalendarImportSubscription
{
    private $db;

    public function __construct($db = null)
    {
        $this->db = $db ?: Database::getInstance();
    }

    public function getByUserId($userId)
    {
        return $this->db->fetchAll(
            "SELECT cis.*, u.display_name AS user_name
             FROM calendar_import_subscriptions cis
             LEFT JOIN users u ON u.id = cis.user_id
             WHERE cis.user_id = ?
             ORDER BY cis.created_at DESC",
            [(int)$userId]
        );
    }

    public function getEnabledDueSubscriptions($limit = 50)
    {
        return $this->db->fetchAll(
            "SELECT *
             FROM calendar_import_subscriptions
             WHERE is_enabled = 1
               AND (
                    last_synced_at IS NULL
                    OR TIMESTAMPDIFF(MINUTE, last_synced_at, NOW()) >= sync_interval_minutes
               )
             ORDER BY COALESCE(last_synced_at, '1970-01-01 00:00:00') ASC
             LIMIT ?",
            [(int)$limit]
        );
    }

    public function getById($id)
    {
        return $this->db->fetch(
            "SELECT * FROM calendar_import_subscriptions WHERE id = ? LIMIT 1",
            [(int)$id]
        );
    }

    public function create($userId, array $data)
    {
        $normalized = self::normalizeData($data);

        $this->db->execute(
            "INSERT INTO calendar_import_subscriptions (
                user_id, name, source_url, is_enabled, sync_interval_minutes, visibility
             ) VALUES (?, ?, ?, ?, ?, ?)",
            [
                (int)$userId,
                $normalized['name'],
                $normalized['source_url'],
                $normalized['is_enabled'],
                $normalized['sync_interval_minutes'],
                $normalized['visibility']
            ]
        );

        return (int)$this->db->lastInsertId();
    }

    public function update($id, $userId, array $data)
    {
        $current = $this->getById($id);
        if (!$current || (int)$current['user_id'] !== (int)$userId) {
            return false;
        }

        $normalized = self::normalizeData(array_merge($current, $data));

        return $this->db->execute(
            "UPDATE calendar_import_subscriptions
             SET name = ?,
                 source_url = ?,
                 is_enabled = ?,
                 sync_interval_minutes = ?,
                 visibility = ?
             WHERE id = ? AND user_id = ?",
            [
                $normalized['name'],
                $normalized['source_url'],
                $normalized['is_enabled'],
                $normalized['sync_interval_minutes'],
                $normalized['visibility'],
                (int)$id,
                (int)$userId
            ]
        );
    }

    public function delete($id, $userId)
    {
        return $this->db->execute(
            "DELETE FROM calendar_import_subscriptions WHERE id = ? AND user_id = ?",
            [(int)$id, (int)$userId]
        );
    }

    public function markSyncResult($id, $success, $message)
    {
        return $this->db->execute(
            "UPDATE calendar_import_subscriptions
             SET last_synced_at = NOW(),
                 last_result = ?,
                 last_error = ?
             WHERE id = ?",
            [
                $success ? trim((string)$message) : null,
                $success ? null : trim((string)$message),
                (int)$id
            ]
        );
    }

    public static function normalizeData(array $data)
    {
        $name = trim((string)($data['name'] ?? ''));
        $sourceUrl = trim((string)($data['source_url'] ?? ''));
        $visibility = trim((string)($data['visibility'] ?? 'public'));
        $interval = (int)($data['sync_interval_minutes'] ?? 30);
        if ($interval < 5) {
            $interval = 5;
        }
        if ($interval > 1440) {
            $interval = 1440;
        }
        if (!in_array($visibility, ['public', 'private', 'specific'], true)) {
            $visibility = 'public';
        }

        return [
            'name' => $name !== '' ? $name : '外部カレンダー',
            'source_url' => $sourceUrl,
            'is_enabled' => self::normalizeBool($data['is_enabled'] ?? 1),
            'sync_interval_minutes' => $interval,
            'visibility' => $visibility,
        ];
    }

    private static function normalizeBool($value)
    {
        $normalized = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($normalized === null) {
            return !empty($value) ? 1 : 0;
        }
        return $normalized ? 1 : 0;
    }
}

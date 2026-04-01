<?php
namespace Models;

use Core\Database;

class Chat
{
    private $db;
    private $ready = null;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function isReady()
    {
        if ($this->ready !== null) {
            return $this->ready;
        }

        try {
            $room = $this->db->fetch("SHOW TABLES LIKE 'chat_rooms'");
            $member = $this->db->fetch("SHOW TABLES LIKE 'chat_room_members'");
            $message = $this->db->fetch("SHOW TABLES LIKE 'chat_messages'");
            $this->ready = !empty($room) && !empty($member) && !empty($message);
        } catch (\Throwable $e) {
            $this->ready = false;
        }

        return $this->ready;
    }

    public function getRoomsForUser($userId)
    {
        $userId = (int)$userId;
        if ($userId <= 0 || !$this->isReady()) {
            return [];
        }

        $sql = "SELECT
                    cr.id,
                    cr.name,
                    cr.room_type,
                    cr.created_at,
                    cr.updated_at,
                    crm.last_read_message_id,
                    lm.id AS last_message_id,
                    lm.message_text AS last_message_text,
                    lm.created_at AS last_message_at,
                    lm.user_id AS last_message_user_id,
                    lu.display_name AS last_sender_name,
                    (
                        SELECT u2.display_name
                        FROM chat_room_members crm2
                        INNER JOIN users u2 ON u2.id = crm2.user_id
                        WHERE crm2.room_id = cr.id
                          AND crm2.is_active = 1
                          AND crm2.user_id <> ?
                        LIMIT 1
                    ) AS direct_partner_name,
                    (
                        SELECT COUNT(*)
                        FROM chat_room_members crm3
                        WHERE crm3.room_id = cr.id
                          AND crm3.is_active = 1
                    ) AS member_count,
                    (
                        SELECT COUNT(*)
                        FROM chat_messages cmu
                        WHERE cmu.room_id = cr.id
                          AND cmu.deleted_at IS NULL
                          AND cmu.id > COALESCE(crm.last_read_message_id, 0)
                          AND cmu.user_id <> ?
                    ) AS unread_count
                FROM chat_rooms cr
                INNER JOIN chat_room_members crm
                    ON crm.room_id = cr.id
                   AND crm.user_id = ?
                   AND crm.is_active = 1
                LEFT JOIN chat_messages lm
                    ON lm.id = (
                        SELECT cm2.id
                        FROM chat_messages cm2
                        WHERE cm2.room_id = cr.id
                          AND cm2.deleted_at IS NULL
                        ORDER BY cm2.id DESC
                        LIMIT 1
                    )
                LEFT JOIN users lu ON lu.id = lm.user_id
                WHERE cr.deleted_at IS NULL
                ORDER BY COALESCE(lm.created_at, cr.updated_at, cr.created_at) DESC";

        $rooms = $this->db->fetchAll($sql, [$userId, $userId, $userId]);
        foreach ($rooms as &$room) {
            $room['display_name'] = $this->resolveRoomDisplayName($room, $userId);
            $room['unread_count'] = (int)($room['unread_count'] ?? 0);
            $room['member_count'] = (int)($room['member_count'] ?? 0);
        }
        unset($room);

        return $rooms;
    }

    public function getRoomByIdForUser($roomId, $userId)
    {
        $roomId = (int)$roomId;
        $userId = (int)$userId;
        if ($roomId <= 0 || $userId <= 0 || !$this->isReady()) {
            return null;
        }

        $sql = "SELECT
                    cr.*,
                    crm.last_read_message_id,
                    (
                        SELECT u2.display_name
                        FROM chat_room_members crm2
                        INNER JOIN users u2 ON u2.id = crm2.user_id
                        WHERE crm2.room_id = cr.id
                          AND crm2.is_active = 1
                          AND crm2.user_id <> ?
                        LIMIT 1
                    ) AS direct_partner_name
                FROM chat_rooms cr
                INNER JOIN chat_room_members crm
                    ON crm.room_id = cr.id
                   AND crm.user_id = ?
                   AND crm.is_active = 1
                WHERE cr.id = ?
                  AND cr.deleted_at IS NULL
                LIMIT 1";
        $room = $this->db->fetch($sql, [$userId, $userId, $roomId]);
        if (!$room) {
            return null;
        }

        $room['display_name'] = $this->resolveRoomDisplayName($room, $userId);
        return $room;
    }

    public function getRoomMembers($roomId)
    {
        $roomId = (int)$roomId;
        if ($roomId <= 0 || !$this->isReady()) {
            return [];
        }

        return $this->db->fetchAll(
            "SELECT u.id, u.display_name, u.username, u.email, crm.last_read_message_id
             FROM chat_room_members crm
             INNER JOIN users u ON u.id = crm.user_id
             WHERE crm.room_id = ? AND crm.is_active = 1
             ORDER BY u.display_name ASC",
            [$roomId]
        );
    }

    public function createRoom($creatorId, $roomName, array $memberUserIds)
    {
        $creatorId = (int)$creatorId;
        if ($creatorId <= 0 || !$this->isReady()) {
            return 0;
        }

        $memberIds = $this->normalizeMemberIds($creatorId, $memberUserIds);
        if (count($memberIds) < 2) {
            return 0;
        }

        $isDirect = (count($memberIds) === 2 && trim((string)$roomName) === '');
        if ($isDirect) {
            $existingId = $this->findExistingDirectRoomId($memberIds[0], $memberIds[1]);
            if ($existingId > 0) {
                return $existingId;
            }
        }

        $roomType = $isDirect ? 'direct' : 'group';
        $roomName = trim((string)$roomName);
        if ($roomType === 'group' && $roomName === '') {
            $roomName = tr_text('新規グループ', 'New Group') . ' ' . date('Y/m/d H:i');
        }

        $this->db->beginTransaction();
        try {
            $this->db->execute(
                "INSERT INTO chat_rooms (name, room_type, created_by, created_at, updated_at)
                 VALUES (?, ?, ?, NOW(), NOW())",
                [$roomName !== '' ? $roomName : null, $roomType, $creatorId]
            );
            $roomId = (int)$this->db->lastInsertId();

            foreach ($memberIds as $memberId) {
                $this->db->execute(
                    "INSERT INTO chat_room_members
                        (room_id, user_id, is_active, last_read_message_id, last_read_at, created_at, updated_at)
                     VALUES (?, ?, 1, 0, NOW(), NOW(), NOW())",
                    [$roomId, (int)$memberId]
                );
            }

            $this->db->commit();
            return $roomId;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            error_log('createRoom error: ' . $e->getMessage());
            return 0;
        }
    }

    public function updateRoom($roomId, $actorUserId, $roomName, array $memberUserIds)
    {
        $roomId = (int)$roomId;
        $actorUserId = (int)$actorUserId;
        if ($roomId <= 0 || $actorUserId <= 0 || !$this->isReady()) {
            return ['success' => false, 'reason' => 'invalid'];
        }
        if (!$this->isMember($roomId, $actorUserId)) {
            return ['success' => false, 'reason' => 'forbidden'];
        }

        $room = $this->db->fetch(
            "SELECT id, room_type, name
             FROM chat_rooms
             WHERE id = ? AND deleted_at IS NULL
             LIMIT 1",
            [$roomId]
        );
        if (!$room) {
            return ['success' => false, 'reason' => 'not_found'];
        }
        if ((string)($room['room_type'] ?? '') !== 'group') {
            return ['success' => false, 'reason' => 'not_editable'];
        }

        $memberIds = $this->normalizeMemberIds($actorUserId, $memberUserIds);
        if (count($memberIds) < 2) {
            return ['success' => false, 'reason' => 'member_too_few'];
        }

        $roomName = trim((string)$roomName);
        if ($roomName === '') {
            $roomName = trim((string)($room['name'] ?? ''));
        }
        if ($roomName === '') {
            $roomName = tr_text('グループチャット', 'Group Chat');
        }

        $this->db->beginTransaction();
        try {
            $this->db->execute(
                "UPDATE chat_rooms
                 SET name = ?, updated_at = NOW()
                 WHERE id = ?",
                [$roomName, $roomId]
            );

            $inSql = implode(',', array_fill(0, count($memberIds), '?'));
            $disableParams = array_merge([$roomId], $memberIds);
            $this->db->execute(
                "UPDATE chat_room_members
                 SET is_active = 0, updated_at = NOW()
                 WHERE room_id = ?
                   AND is_active = 1
                   AND user_id NOT IN ({$inSql})",
                $disableParams
            );

            foreach ($memberIds as $memberId) {
                $this->db->execute(
                    "INSERT INTO chat_room_members
                        (room_id, user_id, is_active, last_read_message_id, last_read_at, created_at, updated_at)
                     VALUES (?, ?, 1, 0, NULL, NOW(), NOW())
                     ON DUPLICATE KEY UPDATE
                        is_active = 1,
                        updated_at = NOW()",
                    [$roomId, (int)$memberId]
                );
            }

            $this->db->commit();
            return ['success' => true];
        } catch (\Throwable $e) {
            $this->db->rollBack();
            error_log('updateRoom error: ' . $e->getMessage());
            return ['success' => false, 'reason' => 'exception'];
        }
    }

    public function postMessage($roomId, $userId, $messageText, array $attachment = [])
    {
        $roomId = (int)$roomId;
        $userId = (int)$userId;
        $messageText = trim((string)$messageText);
        if ($roomId <= 0 || $userId <= 0 || !$this->isReady()) {
            return 0;
        }
        if (!$this->isMember($roomId, $userId)) {
            return 0;
        }
        if ($messageText === '' && empty($attachment['stored_name'])) {
            return 0;
        }

        $this->db->beginTransaction();
        try {
            $this->db->execute(
                "INSERT INTO chat_messages
                    (room_id, user_id, message_text, attachment_path, attachment_name, attachment_mime, attachment_size, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())",
                [
                    $roomId,
                    $userId,
                    $messageText !== '' ? $messageText : null,
                    $attachment['stored_name'] ?? null,
                    $attachment['original_name'] ?? null,
                    $attachment['mime_type'] ?? null,
                    isset($attachment['file_size']) ? (int)$attachment['file_size'] : null,
                ]
            );
            $messageId = (int)$this->db->lastInsertId();

            $this->db->execute(
                "UPDATE chat_rooms SET updated_at = NOW() WHERE id = ?",
                [$roomId]
            );

            $this->db->execute(
                "UPDATE chat_room_members
                 SET last_read_message_id = GREATEST(COALESCE(last_read_message_id, 0), ?),
                     last_read_at = NOW(),
                     updated_at = NOW()
                 WHERE room_id = ? AND user_id = ?",
                [$messageId, $roomId, $userId]
            );

            $this->db->commit();
            return $messageId;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            error_log('postMessage error: ' . $e->getMessage());
            return 0;
        }
    }

    public function getMessages($roomId, $sinceId = 0, $limit = 120)
    {
        $roomId = (int)$roomId;
        $sinceId = max(0, (int)$sinceId);
        $limit = max(1, min(300, (int)$limit));

        if ($roomId <= 0 || !$this->isReady()) {
            return [];
        }

        $sql = "SELECT
                    cm.id,
                    cm.room_id,
                    cm.user_id,
                    cm.message_text,
                    cm.attachment_path,
                    cm.attachment_name,
                    cm.attachment_mime,
                    cm.attachment_size,
                    cm.created_at,
                    u.display_name AS sender_name,
                    (
                        SELECT COUNT(*)
                        FROM chat_room_members crm
                        WHERE crm.room_id = cm.room_id
                          AND crm.is_active = 1
                          AND COALESCE(crm.last_read_message_id, 0) >= cm.id
                    ) AS read_count
                FROM chat_messages cm
                INNER JOIN users u ON u.id = cm.user_id
                WHERE cm.room_id = ?
                  AND cm.deleted_at IS NULL
                  AND cm.id > ?
                ORDER BY cm.id ASC
                LIMIT {$limit}";

        $rows = $this->db->fetchAll($sql, [$roomId, $sinceId]);
        foreach ($rows as &$row) {
            $row['id'] = (int)$row['id'];
            $row['room_id'] = (int)$row['room_id'];
            $row['user_id'] = (int)$row['user_id'];
            $row['read_count'] = (int)($row['read_count'] ?? 0);
            $row['has_attachment'] = !empty($row['attachment_path']);
        }
        unset($row);

        return $rows;
    }

    public function getMessageById($messageId)
    {
        $messageId = (int)$messageId;
        if ($messageId <= 0 || !$this->isReady()) {
            return null;
        }

        $row = $this->db->fetch(
            "SELECT cm.*, u.display_name AS sender_name
             FROM chat_messages cm
             INNER JOIN users u ON u.id = cm.user_id
             WHERE cm.id = ? AND cm.deleted_at IS NULL
             LIMIT 1",
            [$messageId]
        );
        if (!$row) {
            return null;
        }

        $row['id'] = (int)$row['id'];
        $row['room_id'] = (int)$row['room_id'];
        $row['user_id'] = (int)$row['user_id'];
        $row['read_count'] = (int)$this->db->fetch(
            "SELECT COUNT(*) AS cnt
             FROM chat_room_members
             WHERE room_id = ? AND is_active = 1 AND COALESCE(last_read_message_id, 0) >= ?",
            [(int)$row['room_id'], (int)$row['id']]
        )['cnt'];
        $row['has_attachment'] = !empty($row['attachment_path']);

        return $row;
    }

    public function getMessageForUser($messageId, $userId)
    {
        $messageId = (int)$messageId;
        $userId = (int)$userId;
        if ($messageId <= 0 || $userId <= 0 || !$this->isReady()) {
            return null;
        }

        return $this->db->fetch(
            "SELECT cm.*
             FROM chat_messages cm
             INNER JOIN chat_room_members crm
                ON crm.room_id = cm.room_id
               AND crm.user_id = ?
               AND crm.is_active = 1
             WHERE cm.id = ? AND cm.deleted_at IS NULL
             LIMIT 1",
            [$userId, $messageId]
        );
    }

    public function markRoomRead($roomId, $userId, $lastMessageId = null)
    {
        $roomId = (int)$roomId;
        $userId = (int)$userId;
        if ($roomId <= 0 || $userId <= 0 || !$this->isReady()) {
            return false;
        }
        if (!$this->isMember($roomId, $userId)) {
            return false;
        }

        if ($lastMessageId === null) {
            $lastMessageId = $this->getLastMessageId($roomId);
        } else {
            $lastMessageId = (int)$lastMessageId;
        }

        $this->db->execute(
            "UPDATE chat_room_members
             SET last_read_message_id = GREATEST(COALESCE(last_read_message_id, 0), ?),
                 last_read_at = NOW(),
                 updated_at = NOW()
             WHERE room_id = ? AND user_id = ?",
            [$lastMessageId, $roomId, $userId]
        );
        return true;
    }

    public function getLastMessageId($roomId)
    {
        $roomId = (int)$roomId;
        if ($roomId <= 0 || !$this->isReady()) {
            return 0;
        }
        $row = $this->db->fetch(
            "SELECT id FROM chat_messages
             WHERE room_id = ? AND deleted_at IS NULL
             ORDER BY id DESC LIMIT 1",
            [$roomId]
        );
        return (int)($row['id'] ?? 0);
    }

    public function isMember($roomId, $userId)
    {
        $roomId = (int)$roomId;
        $userId = (int)$userId;
        if ($roomId <= 0 || $userId <= 0 || !$this->isReady()) {
            return false;
        }

        $row = $this->db->fetch(
            "SELECT room_id
             FROM chat_room_members
             WHERE room_id = ? AND user_id = ? AND is_active = 1
             LIMIT 1",
            [$roomId, $userId]
        );
        return !empty($row);
    }

    public function getUnreadCount($userId)
    {
        $userId = (int)$userId;
        if ($userId <= 0 || !$this->isReady()) {
            return 0;
        }

        $row = $this->db->fetch(
            "SELECT COUNT(*) AS cnt
             FROM chat_messages cm
             INNER JOIN chat_room_members crm
                ON crm.room_id = cm.room_id
               AND crm.user_id = ?
               AND crm.is_active = 1
             INNER JOIN chat_rooms cr
                ON cr.id = cm.room_id
               AND cr.deleted_at IS NULL
             WHERE cm.deleted_at IS NULL
               AND cm.user_id <> ?
               AND cm.id > COALESCE(crm.last_read_message_id, 0)",
            [$userId, $userId]
        );

        return (int)($row['cnt'] ?? 0);
    }

    public function getReadMembersForMessage($roomId, $messageId, $requestUserId, $excludeUserId = 0)
    {
        $roomId = (int)$roomId;
        $messageId = (int)$messageId;
        $requestUserId = (int)$requestUserId;
        $excludeUserId = (int)$excludeUserId;

        if ($roomId <= 0 || $messageId <= 0 || $requestUserId <= 0 || !$this->isReady()) {
            return [];
        }
        if (!$this->isMember($roomId, $requestUserId)) {
            return [];
        }

        $message = $this->db->fetch(
            "SELECT id
             FROM chat_messages
             WHERE id = ? AND room_id = ? AND deleted_at IS NULL
             LIMIT 1",
            [$messageId, $roomId]
        );
        if (!$message) {
            return [];
        }

        $params = [$roomId, $messageId];
        $excludeSql = '';
        if ($excludeUserId > 0) {
            $excludeSql = ' AND crm.user_id <> ?';
            $params[] = $excludeUserId;
        }

        $rows = $this->db->fetchAll(
            "SELECT
                u.id,
                u.display_name,
                u.username,
                crm.last_read_at
             FROM chat_room_members crm
             INNER JOIN users u ON u.id = crm.user_id
             WHERE crm.room_id = ?
               AND crm.is_active = 1
               AND COALESCE(crm.last_read_message_id, 0) >= ?
               {$excludeSql}
             ORDER BY COALESCE(crm.last_read_at, '1970-01-01 00:00:00') DESC, u.display_name ASC",
            $params
        );

        foreach ($rows as &$row) {
            $row['id'] = (int)($row['id'] ?? 0);
        }
        unset($row);

        return $rows;
    }

    public function getParticipantUserIds($roomId, $excludeUserId = 0)
    {
        $roomId = (int)$roomId;
        $excludeUserId = (int)$excludeUserId;
        if ($roomId <= 0 || !$this->isReady()) {
            return [];
        }

        $params = [$roomId];
        $where = "room_id = ? AND is_active = 1";
        if ($excludeUserId > 0) {
            $where .= " AND user_id <> ?";
            $params[] = $excludeUserId;
        }

        $rows = $this->db->fetchAll(
            "SELECT user_id FROM chat_room_members WHERE {$where}",
            $params
        );

        $ids = [];
        foreach ($rows as $row) {
            $id = (int)($row['user_id'] ?? 0);
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }
        return array_values($ids);
    }

    private function findExistingDirectRoomId($userA, $userB)
    {
        $userA = (int)$userA;
        $userB = (int)$userB;
        if ($userA <= 0 || $userB <= 0 || !$this->isReady()) {
            return 0;
        }

        $row = $this->db->fetch(
            "SELECT cr.id
             FROM chat_rooms cr
             INNER JOIN chat_room_members crm
                ON crm.room_id = cr.id
               AND crm.is_active = 1
             WHERE cr.room_type = 'direct'
               AND cr.deleted_at IS NULL
             GROUP BY cr.id
             HAVING COUNT(*) = 2
                AND SUM(CASE WHEN crm.user_id IN (?, ?) THEN 1 ELSE 0 END) = 2
             ORDER BY cr.id DESC
             LIMIT 1",
            [$userA, $userB]
        );

        return (int)($row['id'] ?? 0);
    }

    private function normalizeMemberIds($creatorId, array $memberUserIds)
    {
        $ids = [(int)$creatorId => (int)$creatorId];
        foreach ($memberUserIds as $value) {
            $value = (int)$value;
            if ($value > 0) {
                $ids[$value] = $value;
            }
        }

        // active users only
        $idList = array_values($ids);
        if (empty($idList)) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($idList), '?'));
        $activeRows = $this->db->fetchAll(
            "SELECT id FROM users WHERE status = 'active' AND id IN ($placeholders)",
            $idList
        );
        $active = [];
        foreach ($activeRows as $row) {
            $id = (int)($row['id'] ?? 0);
            if ($id > 0) {
                $active[$id] = $id;
            }
        }

        return array_values($active);
    }

    private function resolveRoomDisplayName(array $room, $currentUserId)
    {
        $roomType = (string)($room['room_type'] ?? 'group');
        if ($roomType === 'direct') {
            $partner = trim((string)($room['direct_partner_name'] ?? ''));
            if ($partner !== '') {
                return $partner;
            }
        }

        $name = trim((string)($room['name'] ?? ''));
        if ($name !== '') {
            return $name;
        }

        return tr_text('グループチャット', 'Group Chat');
    }
}

<?php
// models/Message.php
namespace Models;

use Core\Database;

class Message
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * 新規メッセージを作成
     * 
     * @param array $data メッセージデータ
     * @return int|bool 成功時は新規メッセージID、失敗時はfalse
     */
    public function create($data)
    {
        // トランザクション開始
        $this->db->beginTransaction();

        try {
            // メッセージを作成
            $sql = "INSERT INTO messages (
                subject, 
                body, 
                sender_id, 
                parent_id, 
                thread_id
            ) VALUES (?, ?, ?, ?, ?)";

            // 親メッセージIDがある場合（返信）は、その親メッセージのthread_idを使用
            $threadId = null;
            if (!empty($data['parent_id'])) {
                $parentMessage = $this->getById($data['parent_id']);
                if ($parentMessage) {
                    $threadId = $parentMessage['thread_id'] ?? $parentMessage['id'];
                }
            }

            $this->db->execute($sql, [
                $data['subject'],
                $data['body'],
                $data['sender_id'],
                $data['parent_id'] ?? null,
                $threadId
            ]);

            $messageId = $this->db->lastInsertId();

            // thread_idが未設定の場合は自分自身のIDをthread_idとして設定
            if (empty($threadId)) {
                $this->db->execute(
                    "UPDATE messages SET thread_id = ? WHERE id = ?",
                    [$messageId, $messageId]
                );
            }

            // 受信者を追加
            if (!empty($data['recipients']) && is_array($data['recipients'])) {
                foreach ($data['recipients'] as $userId) {
                    $this->addRecipient($messageId, $userId);
                }
            }

            // 組織宛てを追加
            if (!empty($data['organizations']) && is_array($data['organizations'])) {
                foreach ($data['organizations'] as $organizationId) {
                    $this->addOrganization($messageId, $organizationId);

                    // 組織に所属するユーザーを取得して受信者に追加
                    $userModel = new User();
                    $users = $userModel->getUsersByOrganization($organizationId);

                    foreach ($users as $user) {
                        // 送信者自身は除外
                        if ($user['id'] != $data['sender_id']) {
                            $this->addRecipient($messageId, $user['id']);
                        }
                    }
                }
            }

            // 添付ファイルを保存
            if (!empty($data['attachments']) && is_array($data['attachments'])) {
                foreach ($data['attachments'] as $fileInfo) {
                    $this->addAttachment($messageId, $fileInfo);
                }
            }

            $this->db->commit();
            return $messageId;
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("Error creating message: " . $e->getMessage());
            return false;
        }
    }

    /**
     * メッセージを取得
     * 
     * @param int $id メッセージID
     * @return array|bool 成功時はメッセージデータ、失敗時はfalse
     */
    public function getById($id)
    {
        $sql = "SELECT m.*, u.display_name as sender_name 
                FROM messages m 
                LEFT JOIN users u ON m.sender_id = u.id 
                WHERE m.id = ? 
                LIMIT 1";

        return $this->db->fetch($sql, [$id]);
    }

    /**
     * メッセージのスレッドを取得
     * 
     * @param int $threadId スレッドID
     * @return array スレッド内のメッセージ一覧
     */
    public function getThreadMessages($threadId)
    {
        $sql = "SELECT m.*, u.display_name as sender_name 
                FROM messages m 
                LEFT JOIN users u ON m.sender_id = u.id 
                WHERE m.thread_id = ? 
                ORDER BY m.created_at ASC";

        return $this->db->fetchAll($sql, [$threadId]);
    }

    /**
     * ユーザーの受信メッセージ一覧を取得
     * 
     * @param int $userId ユーザーID
     * @param array $filters フィルター条件
     * @param int $page ページ番号
     * @param int $limit 1ページあたりの件数
     * @return array メッセージ一覧
     */
    public function getInbox($userId, $filters = [], $page = 1, $limit = 20)
    {
        $offset = ($page - 1) * $limit;
        $params = [$userId];

        $sql = "SELECT m.*, 
                    u.display_name as sender_name, 
                    mr.is_read,
                    mr.read_at,
                    mr.is_starred,
                    (SELECT COUNT(*) FROM messages WHERE thread_id = m.thread_id) as thread_count
                FROM messages m 
                JOIN message_recipients mr ON m.id = mr.message_id AND mr.user_id = ? 
                LEFT JOIN users u ON m.sender_id = u.id 
                WHERE mr.is_deleted = 0 ";

        // フィルター条件を適用
        if (!empty($filters['is_read']) && $filters['is_read'] !== 'all') {
            $sql .= "AND mr.is_read = ? ";
            $params[] = ($filters['is_read'] === 'read') ? 1 : 0;
        }

        if (!empty($filters['is_starred'])) {
            $sql .= "AND mr.is_starred = 1 ";
        }

        if (!empty($filters['search'])) {
            $sql .= "AND (m.subject LIKE ? OR m.body LIKE ?) ";
            $searchTerm = "%" . $filters['search'] . "%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        // 最新のメッセージ順に並べる
        $sql .= "ORDER BY m.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * ユーザーの送信メッセージ一覧を取得
     * 
     * @param int $userId ユーザーID
     * @param array $filters フィルター条件
     * @param int $page ページ番号
     * @param int $limit 1ページあたりの件数
     * @return array メッセージ一覧
     */
    public function getSent($userId, $filters = [], $page = 1, $limit = 20)
    {
        $offset = ($page - 1) * $limit;
        $params = [$userId];

        $sql = "SELECT m.*, 
                    u.display_name as sender_name,
                    (SELECT COUNT(*) FROM message_recipients WHERE message_id = m.id AND is_read = 1) as read_count,
                    (SELECT COUNT(*) FROM message_recipients WHERE message_id = m.id) as recipient_count,
                    (SELECT COUNT(*) FROM messages WHERE thread_id = m.thread_id) as thread_count
                FROM messages m 
                LEFT JOIN users u ON m.sender_id = u.id 
                WHERE m.sender_id = ? ";

        // フィルター条件を適用
        if (!empty($filters['search'])) {
            $sql .= "AND (m.subject LIKE ? OR m.body LIKE ?) ";
            $searchTerm = "%" . $filters['search'] . "%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        // 最新のメッセージ順に並べる
        $sql .= "ORDER BY m.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * 受信メッセージ数を取得
     * 
     * @param int $userId ユーザーID
     * @param array $filters フィルター条件
     * @return int メッセージ数
     */
    public function getInboxCount($userId, $filters = [])
    {
        $params = [$userId];

        $sql = "SELECT COUNT(*) as count 
                FROM messages m 
                JOIN message_recipients mr ON m.id = mr.message_id AND mr.user_id = ? 
                WHERE mr.is_deleted = 0 ";

        // フィルター条件を適用
        if (!empty($filters['is_read']) && $filters['is_read'] !== 'all') {
            $sql .= "AND mr.is_read = ? ";
            $params[] = ($filters['is_read'] === 'read') ? 1 : 0;
        }

        if (!empty($filters['is_starred'])) {
            $sql .= "AND mr.is_starred = 1 ";
        }

        if (!empty($filters['search'])) {
            $sql .= "AND (m.subject LIKE ? OR m.body LIKE ?) ";
            $searchTerm = "%" . $filters['search'] . "%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $result = $this->db->fetch($sql, $params);
        return $result['count'];
    }

    /**
     * 送信メッセージ数を取得
     * 
     * @param int $userId ユーザーID
     * @param array $filters フィルター条件
     * @return int メッセージ数
     */
    public function getSentCount($userId, $filters = [])
    {
        $params = [$userId];

        $sql = "SELECT COUNT(*) as count 
                FROM messages m 
                WHERE m.sender_id = ? ";

        // フィルター条件を適用
        if (!empty($filters['search'])) {
            $sql .= "AND (m.subject LIKE ? OR m.body LIKE ?) ";
            $searchTerm = "%" . $filters['search'] . "%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $result = $this->db->fetch($sql, $params);
        return $result['count'];
    }

    /**
     * 未読メッセージ数を取得
     * 
     * @param int $userId ユーザーID
     * @return int 未読メッセージ数
     */
    public function getUnreadCount($userId)
    {
        $sql = "SELECT COUNT(*) as count 
                FROM message_recipients 
                WHERE user_id = ? AND is_read = 0 AND is_deleted = 0";

        $result = $this->db->fetch($sql, [$userId]);
        return $result['count'];
    }

    /**
     * メッセージの受信者一覧を取得
     * 
     * @param int $messageId メッセージID
     * @return array 受信者一覧
     */
    public function getRecipients($messageId)
    {
        $sql = "SELECT u.*, mr.is_read, mr.read_at
                FROM message_recipients mr 
                JOIN users u ON mr.user_id = u.id 
                WHERE mr.message_id = ? 
                ORDER BY u.display_name";

        return $this->db->fetchAll($sql, [$messageId]);
    }

    /**
     * メッセージの組織宛先一覧を取得
     * 
     * @param int $messageId メッセージID
     * @return array 組織宛先一覧
     */
    public function getOrganizations($messageId)
    {
        $sql = "SELECT o.* 
                FROM message_organizations mo 
                JOIN organizations o ON mo.organization_id = o.id 
                WHERE mo.message_id = ? 
                ORDER BY o.name";

        return $this->db->fetchAll($sql, [$messageId]);
    }

    /**
     * メッセージの添付ファイル一覧を取得
     * 
     * @param int $messageId メッセージID
     * @return array 添付ファイル一覧
     */
    public function getAttachments($messageId)
    {
        $sql = "SELECT * FROM message_attachments 
                WHERE message_id = ? 
                ORDER BY file_name";

        return $this->db->fetchAll($sql, [$messageId]);
    }

    /**
     * メッセージに受信者を追加
     * 
     * @param int $messageId メッセージID
     * @param int $userId ユーザーID
     * @return bool 成功時はtrue、失敗時はfalse
     */
    public function addRecipient($messageId, $userId)
    {
        $sql = "INSERT IGNORE INTO message_recipients (
                    message_id, 
                    user_id
                ) VALUES (?, ?)";

        return $this->db->execute($sql, [$messageId, $userId]);
    }

    /**
     * メッセージに組織宛先を追加
     * 
     * @param int $messageId メッセージID
     * @param int $organizationId 組織ID
     * @return bool 成功時はtrue、失敗時はfalse
     */
    public function addOrganization($messageId, $organizationId)
    {
        $sql = "INSERT IGNORE INTO message_organizations (
                    message_id, 
                    organization_id
                ) VALUES (?, ?)";

        return $this->db->execute($sql, [$messageId, $organizationId]);
    }

    /**
     * メッセージに添付ファイルを追加
     * 
     * @param int $messageId メッセージID
     * @param array $fileInfo ファイル情報
     * @return bool 成功時はtrue、失敗時はfalse
     */
    public function addAttachment($messageId, $fileInfo)
    {
        $sql = "INSERT INTO message_attachments (
                    message_id, 
                    file_name, 
                    file_path, 
                    file_size, 
                    mime_type
                ) VALUES (?, ?, ?, ?, ?)";

        return $this->db->execute($sql, [
            $messageId,
            $fileInfo['name'],
            $fileInfo['path'],
            $fileInfo['size'],
            $fileInfo['type']
        ]);
    }

    /**
     * メッセージを既読にする
     * 
     * @param int $messageId メッセージID
     * @param int $userId ユーザーID
     * @return bool 成功時はtrue、失敗時はfalse
     */
    public function markAsRead($messageId, $userId)
    {
        $sql = "UPDATE message_recipients 
                SET is_read = 1, read_at = CURRENT_TIMESTAMP 
                WHERE message_id = ? AND user_id = ?";

        return $this->db->execute($sql, [$messageId, $userId]);
    }

    /**
     * メッセージを未読にする
     * 
     * @param int $messageId メッセージID
     * @param int $userId ユーザーID
     * @return bool 成功時はtrue、失敗時はfalse
     */
    public function markAsUnread($messageId, $userId)
    {
        $sql = "UPDATE message_recipients 
                SET is_read = 0, read_at = NULL 
                WHERE message_id = ? AND user_id = ?";

        return $this->db->execute($sql, [$messageId, $userId]);
    }

    /**
     * メッセージにスターを付ける/外す
     * 
     * @param int $messageId メッセージID
     * @param int $userId ユーザーID
     * @param bool $starred スター状態
     * @return bool 成功時はtrue、失敗時はfalse
     */
    public function toggleStar($messageId, $userId, $starred)
    {
        $sql = "UPDATE message_recipients 
                SET is_starred = ? 
                WHERE message_id = ? AND user_id = ?";

        return $this->db->execute($sql, [$starred ? 1 : 0, $messageId, $userId]);
    }

    /**
     * 受信者側でメッセージを削除
     * 
     * @param int $messageId メッセージID
     * @param int $userId ユーザーID
     * @return bool 成功時はtrue、失敗時はfalse
     */
    public function deleteForRecipient($messageId, $userId)
    {
        $sql = "UPDATE message_recipients 
                SET is_deleted = 1 
                WHERE message_id = ? AND user_id = ?";

        return $this->db->execute($sql, [$messageId, $userId]);
    }
}

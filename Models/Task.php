<?php
// models/Task.php
namespace Models;

use Core\Database;

class Task
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * ボードの作成
     * 
     * @param array $data ボードデータ
     * @return int|bool 成功時は新規ボードID、失敗時はfalse
     */
    public function createBoard($data)
    {
        try {
            $sql = "INSERT INTO task_boards (
                name, 
                description, 
                owner_type, 
                owner_id, 
                is_public, 
                background_color,
                created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?)";

            $this->db->execute($sql, [
                $data['name'],
                $data['description'] ?? null,
                $data['owner_type'],
                $data['owner_id'],
                isset($data['is_public']) ? 1 : 0,
                $data['background_color'] ?? '#f0f2f5',
                $data['created_by']
            ]);

            $boardId = $this->db->lastInsertId();

            // デフォルトのリストを作成（ToDo, In Progress, Done）
            $defaultLists = [
                ['name' => '未対応', 'color' => '#ffffff', 'sort_order' => 0],
                ['name' => '処理中', 'color' => '#ffffff', 'sort_order' => 1],
                ['name' => '完了', 'color' => '#ffffff', 'sort_order' => 2]
            ];

            foreach ($defaultLists as $listData) {
                $this->createList($boardId, $listData);
            }

            // 所有者を自動的にボードメンバーとして追加
            if ($data['owner_type'] == 'user') {
                $this->addBoardMember($boardId, $data['owner_id'], 'admin');
            } else if ($data['owner_type'] == 'team') {
                // チームメンバーを取得して追加
                $teamModel = new Team();
                $members = $teamModel->getMembers($data['owner_id']);

                foreach ($members as $member) {
                    $role = $member['role'] == 'admin' ? 'admin' : 'editor';
                    $this->addBoardMember($boardId, $member['user_id'], $role);
                }
            } else if ($data['owner_type'] == 'organization') {
                // 組織メンバーを取得して追加
                $userModel = new User();
                $members = $userModel->getUsersByOrganization($data['owner_id']);

                foreach ($members as $member) {
                    $this->addBoardMember($boardId, $member['id'], 'editor');
                }
            }

            return $boardId;
        } catch (\Exception $e) {
            error_log("Error creating task board: " . $e->getMessage());
            return false;
        }
    }

    /**
     * ボードの更新
     * 
     * @param int $id ボードID
     * @param array $data ボードデータ
     * @return bool 成功時true、失敗時false
     */
    public function updateBoard($id, $data)
    {
        try {
            $sql = "UPDATE task_boards SET 
                    name = ?, 
                    description = ?, 
                    is_public = ?, 
                    background_color = ?,
                    updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?";

            return $this->db->execute($sql, [
                $data['name'],
                $data['description'] ?? null,
                isset($data['is_public']) ? 1 : 0,
                $data['background_color'] ?? '#f0f2f5',
                $id
            ]);
        } catch (\Exception $e) {
            error_log("Error updating task board: " . $e->getMessage());
            return false;
        }
    }

    /**
     * ボードの削除
     * 
     * @param int $id ボードID
     * @return bool 成功時true、失敗時false
     */
    public function deleteBoard($id)
    {
        try {
            return $this->db->execute("DELETE FROM task_boards WHERE id = ?", [$id]);
        } catch (\Exception $e) {
            error_log("Error deleting task board: " . $e->getMessage());
            return false;
        }
    }

    /**
     * ボードの詳細を取得
     * 
     * @param int $id ボードID
     * @return array|null ボード情報
     */
    public function getBoard($id)
    {
        try {
            $sql = "SELECT * FROM task_boards WHERE id = ? LIMIT 1";
            return $this->db->fetch($sql, [$id]);
        } catch (\Exception $e) {
            error_log("Error getting task board: " . $e->getMessage());
            return null;
        }
    }

    /**
     * ユーザーのボード一覧を取得
     * 
     * @param int $userId ユーザーID
     * @return array ボードリスト
     */
    public function getUserBoards($userId)
    {
        try {
            $sql = "SELECT b.* FROM task_boards b
                LEFT JOIN task_board_members m ON b.id = m.board_id
                WHERE (b.owner_type = 'user' AND b.owner_id = ?)
                OR (m.user_id = ?)
                GROUP BY b.id
                ORDER BY b.created_at DESC";

            return $this->db->fetchAll($sql, [$userId, $userId]);
        } catch (\Exception $e) {
            error_log("Error getting user boards: " . $e->getMessage());
            return [];
        }
    }

    /**
     * チームのボード一覧を取得
     * 
     * @param int $teamId チームID
     * @return array ボードリスト
     */
    public function getTeamBoards($teamId)
    {
        try {
            $sql = "SELECT * FROM task_boards 
                    WHERE owner_type = 'team' AND owner_id = ?
                    ORDER BY created_at DESC";

            return $this->db->fetchAll($sql, [$teamId]);
        } catch (\Exception $e) {
            error_log("Error getting team boards: " . $e->getMessage());
            return [];
        }
    }

    /**
     * 組織のボード一覧を取得
     * 
     * @param int $organizationId 組織ID
     * @return array ボードリスト
     */
    public function getOrganizationBoards($organizationId)
    {
        try {
            $sql = "SELECT * FROM task_boards 
                    WHERE owner_type = 'organization' AND owner_id = ?
                    ORDER BY created_at DESC";

            return $this->db->fetchAll($sql, [$organizationId]);
        } catch (\Exception $e) {
            error_log("Error getting organization boards: " . $e->getMessage());
            return [];
        }
    }

    /**
     * ボードメンバーを追加
     * 
     * @param int $boardId ボードID
     * @param int $userId ユーザーID
     * @param string $role 権限（admin, editor, viewer）
     * @return bool 成功時true、失敗時false
     */
    public function addBoardMember($boardId, $userId, $role = 'viewer')
    {
        try {
            // 既に存在するか確認
            $sql = "SELECT * FROM task_board_members WHERE board_id = ? AND user_id = ?";
            $existing = $this->db->fetch($sql, [$boardId, $userId]);

            if ($existing) {
                // 既に存在する場合は権限を更新
                $sql = "UPDATE task_board_members SET role = ? WHERE board_id = ? AND user_id = ?";
                return $this->db->execute($sql, [$role, $boardId, $userId]);
            } else {
                // 新規追加
                $sql = "INSERT INTO task_board_members (board_id, user_id, role) VALUES (?, ?, ?)";
                return $this->db->execute($sql, [$boardId, $userId, $role]);
            }
        } catch (\Exception $e) {
            error_log("Error adding board member: " . $e->getMessage());
            return false;
        }
    }

    /**
     * ボードメンバーを削除
     * 
     * @param int $boardId ボードID
     * @param int $userId ユーザーID
     * @return bool 成功時true、失敗時false
     */
    public function removeBoardMember($boardId, $userId)
    {
        try {
            $sql = "DELETE FROM task_board_members WHERE board_id = ? AND user_id = ?";
            return $this->db->execute($sql, [$boardId, $userId]);
        } catch (\Exception $e) {
            error_log("Error removing board member: " . $e->getMessage());
            return false;
        }
    }

    /**
     * ボードメンバー一覧を取得
     * 
     * @param int $boardId ボードID
     * @return array メンバーリスト
     */
    public function getBoardMembers($boardId)
    {
        try {
            $sql = "SELECT m.*, u.display_name, u.email 
                    FROM task_board_members m
                    JOIN users u ON m.user_id = u.id
                    WHERE m.board_id = ?
                    ORDER BY m.role, u.display_name";

            return $this->db->fetchAll($sql, [$boardId]);
        } catch (\Exception $e) {
            error_log("Error getting board members: " . $e->getMessage());
            return [];
        }
    }

    /**
     * リストの作成
     * 
     * @param int $boardId ボードID
     * @param array $data リストデータ
     * @return int|bool 成功時は新規リストID、失敗時はfalse
     */
    public function createList($boardId, $data)
    {
        try {
            // 最大のソート順を取得
            if (!isset($data['sort_order'])) {
                $sql = "SELECT MAX(sort_order) as max_order FROM task_lists WHERE board_id = ?";
                $result = $this->db->fetch($sql, [$boardId]);
                $sortOrder = ($result && isset($result['max_order'])) ? $result['max_order'] + 1 : 0;
            } else {
                $sortOrder = $data['sort_order'];
            }

            $sql = "INSERT INTO task_lists (
                board_id, 
                name, 
                description, 
                color,
                sort_order
            ) VALUES (?, ?, ?, ?, ?)";

            $this->db->execute($sql, [
                $boardId,
                $data['name'],
                $data['description'] ?? null,
                $data['color'] ?? '#ffffff',
                $sortOrder
            ]);

            return $this->db->lastInsertId();
        } catch (\Exception $e) {
            error_log("Error creating task list: " . $e->getMessage());
            return false;
        }
    }

    /**
     * リストの更新
     * 
     * @param int $id リストID
     * @param array $data リストデータ
     * @return bool 成功時true、失敗時false
     */
    public function updateList($id, $data)
    {
        try {
            $sql = "UPDATE task_lists SET 
                    name = ?, 
                    description = ?, 
                    color = ?,
                    updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?";

            return $this->db->execute($sql, [
                $data['name'],
                $data['description'] ?? null,
                $data['color'] ?? '#ffffff',
                $id
            ]);
        } catch (\Exception $e) {
            error_log("Error updating task list: " . $e->getMessage());
            return false;
        }
    }

    /**
     * リストの削除
     * 
     * @param int $id リストID
     * @return bool 成功時true、失敗時false
     */
    public function deleteList($id)
    {
        try {
            return $this->db->execute("DELETE FROM task_lists WHERE id = ?", [$id]);
        } catch (\Exception $e) {
            error_log("Error deleting task list: " . $e->getMessage());
            return false;
        }
    }

    /**
     * リストの詳細を取得
     * 
     * @param int $id リストID
     * @return array|null リスト情報
     */
    public function getList($id)
    {
        try {
            $sql = "SELECT * FROM task_lists WHERE id = ? LIMIT 1";
            return $this->db->fetch($sql, [$id]);
        } catch (\Exception $e) {
            error_log("Error getting task list: " . $e->getMessage());
            return null;
        }
    }

    /**
     * ボードのリスト一覧を取得
     * 
     * @param int $boardId ボードID
     * @return array リストリスト
     */
    public function getBoardLists($boardId)
    {
        try {
            $sql = "SELECT * FROM task_lists 
                    WHERE board_id = ?
                    ORDER BY sort_order";

            return $this->db->fetchAll($sql, [$boardId]);
        } catch (\Exception $e) {
            error_log("Error getting board lists: " . $e->getMessage());
            return [];
        }
    }

    /**
     * リストの順序を更新
     * 
     * @param int $id リストID
     * @param int $newOrder 新しい順序
     * @return bool 成功時true、失敗時false
     */
    public function updateListOrder($id, $newOrder)
    {
        try {
            // リストとボードIDを取得
            $sql = "SELECT board_id FROM task_lists WHERE id = ?";
            $list = $this->db->fetch($sql, [$id]);

            if (!$list) {
                return false;
            }

            $boardId = $list['board_id'];

            // 現在の順序を取得
            $sql = "SELECT sort_order FROM task_lists WHERE id = ?";
            $current = $this->db->fetch($sql, [$id]);
            $currentOrder = $current ? $current['sort_order'] : null;

            if ($currentOrder === null || $currentOrder == $newOrder) {
                return true;
            }

            // トランザクション開始
            $this->db->beginTransaction();

            // 移動方向に応じて順序を更新
            if ($newOrder > $currentOrder) {
                // 下に移動する場合
                $sql = "UPDATE task_lists 
                        SET sort_order = sort_order - 1 
                        WHERE board_id = ? AND sort_order > ? AND sort_order <= ?";
                $this->db->execute($sql, [$boardId, $currentOrder, $newOrder]);
            } else {
                // 上に移動する場合
                $sql = "UPDATE task_lists 
                        SET sort_order = sort_order + 1 
                        WHERE board_id = ? AND sort_order >= ? AND sort_order < ?";
                $this->db->execute($sql, [$boardId, $newOrder, $currentOrder]);
            }

            // 対象リストの順序を更新
            $sql = "UPDATE task_lists SET sort_order = ? WHERE id = ?";
            $this->db->execute($sql, [$newOrder, $id]);

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("Error updating list order: " . $e->getMessage());
            return false;
        }
    }

    /**
     * カードの作成
     * 
     * @param int $listId リストID
     * @param array $data カードデータ
     * @param int $userId 作成者ID
     * @return int|bool 成功時は新規カードID、失敗時はfalse
     */
    public function createCard($listId, $data, $userId)
    {
        try {
            // 最大のソート順を取得
            $sql = "SELECT MAX(sort_order) as max_order FROM task_cards WHERE list_id = ?";
            $result = $this->db->fetch($sql, [$listId]);
            $sortOrder = ($result && isset($result['max_order'])) ? $result['max_order'] + 1 : 0;

            $sql = "INSERT INTO task_cards (
                list_id, 
                title, 
                description, 
                due_date, 
                priority, 
                status,
                progress, 
                color, 
                sort_order, 
                created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $this->db->execute($sql, [
                $listId,
                $data['title'],
                $data['description'] ?? null,
                $data['due_date'] ?? null,
                $data['priority'] ?? 'normal',
                $data['status'] ?? 'not_started',
                $data['progress'] ?? 0,
                $data['color'] ?? null,
                $sortOrder,
                $userId
            ]);

            $cardId = $this->db->lastInsertId();

            // 担当者がいれば追加
            if (isset($data['assignees']) && is_array($data['assignees'])) {
                foreach ($data['assignees'] as $assigneeId) {
                    $this->addCardAssignee($cardId, $assigneeId);
                }
            }

            // ラベルがあれば追加
            if (isset($data['labels']) && is_array($data['labels'])) {
                foreach ($data['labels'] as $labelId) {
                    $this->addCardLabel($cardId, $labelId);
                }
            }

            // 活動ログを記録
            $this->logActivity($this->getBoardIdFromList($listId), $cardId, $userId, 'card_created', [
                'card_title' => $data['title']
            ]);

            return $cardId;
        } catch (\Exception $e) {
            error_log("Error creating task card: " . $e->getMessage());
            return false;
        }
    }

    /**
     * カードの更新
     * 
     * @param int $id カードID
     * @param array $data カードデータ
     * @param int $userId 更新者ID
     * @return bool 成功時true、失敗時false
     */
    public function updateCard($id, $data, $userId)
    {
        try {
            $this->db->beginTransaction();

            // 元のカード情報を取得（変更ログ用）
            $oldCard = $this->getCard($id);

            // カード情報の更新
            $sql = "UPDATE task_cards SET 
                    title = ?, 
                    description = ?, 
                    due_date = ?, 
                    priority = ?, 
                    status = ?,
                    progress = ?, 
                    color = ?,
                    updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?";

            $result = $this->db->execute($sql, [
                $data['title'],
                $data['description'] ?? null,
                $data['due_date'] ?? null,
                $data['priority'] ?? 'normal',
                $data['status'] ?? 'not_started',
                $data['progress'] ?? 0,
                $data['color'] ?? null,
                $id
            ]);

            // リストが変更された場合
            if (isset($data['list_id']) && $oldCard && $oldCard['list_id'] != $data['list_id']) {
                $sql = "UPDATE task_cards SET list_id = ? WHERE id = ?";
                $this->db->execute($sql, [$data['list_id'], $id]);

                // 活動ログを記録
                $this->logActivity($this->getBoardIdFromList($data['list_id']), $id, $userId, 'card_moved', [
                    'from_list' => $this->getListName($oldCard['list_id']),
                    'to_list' => $this->getListName($data['list_id'])
                ]);
            }

            // 担当者の更新
            if (isset($data['assignees'])) {
                // 現在の担当者を削除
                $sql = "DELETE FROM task_assignees WHERE card_id = ?";
                $this->db->execute($sql, [$id]);

                // 新しい担当者を追加
                if (is_array($data['assignees'])) {
                    foreach ($data['assignees'] as $assigneeId) {
                        $this->addCardAssignee($id, $assigneeId);
                    }
                }
            }

            // ラベルの更新
            if (isset($data['labels'])) {
                // 現在のラベルを削除
                $sql = "DELETE FROM task_card_labels WHERE card_id = ?";
                $this->db->execute($sql, [$id]);

                // 新しいラベルを追加
                if (is_array($data['labels'])) {
                    foreach ($data['labels'] as $labelId) {
                        $this->addCardLabel($id, $labelId);
                    }
                }
            }

            // 活動ログを記録
            $changeLog = [];
            $newCard = $this->getCard($id);

            if ($oldCard['title'] != $newCard['title']) {
                $changeLog['title'] = ['old' => $oldCard['title'], 'new' => $newCard['title']];
                $this->logActivity($this->getBoardIdFromList($newCard['list_id']), $id, $userId, 'card_title_updated', [
                    'old_title' => $oldCard['title'],
                    'new_title' => $newCard['title']
                ]);
            }

            if ($oldCard['status'] != $newCard['status']) {
                $changeLog['status'] = ['old' => $oldCard['status'], 'new' => $newCard['status']];
                $this->logActivity($this->getBoardIdFromList($newCard['list_id']), $id, $userId, 'card_status_updated', [
                    'old_status' => $oldCard['status'],
                    'new_status' => $newCard['status']
                ]);
            }

            if ($oldCard['progress'] != $newCard['progress']) {
                $changeLog['progress'] = ['old' => $oldCard['progress'], 'new' => $newCard['progress']];
                $this->logActivity($this->getBoardIdFromList($newCard['list_id']), $id, $userId, 'card_progress_updated', [
                    'old_progress' => $oldCard['progress'],
                    'new_progress' => $newCard['progress']
                ]);
            }

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("Error updating task card: " . $e->getMessage());
            return false;
        }
    }

    /**
     * カードの削除
     * 
     * @param int $id カードID
     * @param int $userId 削除者ID
     * @return bool 成功時true、失敗時false
     */
    public function deleteCard($id, $userId)
    {
        try {
            // カード情報を取得
            $card = $this->getCard($id);
            if (!$card) {
                return false;
            }

            // ボードIDを取得
            $boardId = $this->getBoardIdFromList($card['list_id']);

            // 活動ログを記録
            $this->logActivity($boardId, null, $userId, 'card_deleted', [
                'card_title' => $card['title']
            ]);

            return $this->db->execute("DELETE FROM task_cards WHERE id = ?", [$id]);
        } catch (\Exception $e) {
            error_log("Error deleting task card: " . $e->getMessage());
            return false;
        }
    }

    /**
     * カードの詳細を取得
     * 
     * @param int $id カードID
     * @return array|null カード情報
     */
    public function getCard($id)
    {
        try {
            $sql = "SELECT * FROM task_cards WHERE id = ? LIMIT 1";
            return $this->db->fetch($sql, [$id]);
        } catch (\Exception $e) {
            error_log("Error getting task card: " . $e->getMessage());
            return null;
        }
    }

    /**
     * カードの詳細を関連情報も含めて取得
     * 
     * @param int $id カードID
     * @return array|null カード情報
     */
    public function getCardWithRelations($id)
    {
        try {
            // カード基本情報の取得
            $card = $this->getCard($id);
            if (!$card) {
                return null;
            }

            // 担当者の取得
            $sql = "SELECT a.*, u.display_name, u.email 
                    FROM task_assignees a
                    JOIN users u ON a.user_id = u.id
                    WHERE a.card_id = ?";
            $card['assignees'] = $this->db->fetchAll($sql, [$id]);

            // ラベルの取得
            $sql = "SELECT l.* 
                    FROM task_labels l
                    JOIN task_card_labels cl ON l.id = cl.label_id
                    WHERE cl.card_id = ?";
            $card['labels'] = $this->db->fetchAll($sql, [$id]);

            // チェックリストの取得
            $sql = "SELECT * FROM task_checklists WHERE card_id = ? ORDER BY sort_order";
            $checklists = $this->db->fetchAll($sql, [$id]);

            // チェックリスト項目の取得
            foreach ($checklists as &$checklist) {
                $sql = "SELECT * FROM task_checklist_items WHERE checklist_id = ? ORDER BY sort_order";
                $checklist['items'] = $this->db->fetchAll($sql, [$checklist['id']]);
            }

            $card['checklists'] = $checklists;

            // 添付ファイルの取得
            $sql = "SELECT * FROM task_attachments WHERE card_id = ? ORDER BY created_at DESC";
            $card['attachments'] = $this->db->fetchAll($sql, [$id]);

            // コメントの取得
            $sql = "SELECT c.*, u.display_name, u.email 
                    FROM task_comments c
                    JOIN users u ON c.user_id = u.id
                    WHERE c.card_id = ?
                    ORDER BY c.created_at";
            $card['comments'] = $this->db->fetchAll($sql, [$id]);

            return $card;
        } catch (\Exception $e) {
            error_log("Error getting task card with relations: " . $e->getMessage());
            return null;
        }
    }

    /**
     * リストに属するカード一覧を取得
     * 
     * @param int $listId リストID
     * @return array カードリスト
     */
    public function getListCards($listId)
    {
        try {
            $sql = "SELECT c.*, 
                    (SELECT COUNT(*) FROM task_assignees WHERE card_id = c.id) as assignee_count,
                    (SELECT COUNT(*) FROM task_card_labels WHERE card_id = c.id) as label_count
                    FROM task_cards c
                    WHERE c.list_id = ?
                    ORDER BY c.sort_order";

            $cards = $this->db->fetchAll($sql, [$listId]);

            // 各カードの担当者とラベルを取得
            foreach ($cards as &$card) {
                // 担当者の取得
                $sql = "SELECT a.*, u.display_name, u.email 
                        FROM task_assignees a
                        JOIN users u ON a.user_id = u.id
                        WHERE a.card_id = ?";
                $card['assignees'] = $this->db->fetchAll($sql, [$card['id']]);

                // ラベルの取得
                $sql = "SELECT l.* 
                        FROM task_labels l
                        JOIN task_card_labels cl ON l.id = cl.label_id
                        WHERE cl.card_id = ?";
                $card['labels'] = $this->db->fetchAll($sql, [$card['id']]);

                // チェックリスト完了率の計算
                $sql = "SELECT 
                        COUNT(*) as total_items,
                        SUM(CASE WHEN is_checked = 1 THEN 1 ELSE 0 END) as completed_items
                        FROM task_checklist_items i
                        JOIN task_checklists c ON i.checklist_id = c.id
                        WHERE c.card_id = ?";
                $checklistStats = $this->db->fetch($sql, [$card['id']]);

                if ($checklistStats && $checklistStats['total_items'] > 0) {
                    $card['checklist_completion'] = round(($checklistStats['completed_items'] / $checklistStats['total_items']) * 100);
                } else {
                    $card['checklist_completion'] = 0;
                }
            }

            return $cards;
        } catch (\Exception $e) {
            error_log("Error getting list cards: " . $e->getMessage());
            return [];
        }
    }

    /**
     * カードの順序を更新
     * 
     * @param int $id カードID
     * @param int $listId 新しいリストID
     * @param int $newOrder 新しい順序
     * @param int $userId 更新者ID
     * @return bool 成功時true、失敗時false
     */
    public function updateCardOrder($id, $listId, $newOrder, $userId)
    {
        try {
            // カード情報を取得
            $sql = "SELECT list_id FROM task_cards WHERE id = ?";
            $card = $this->db->fetch($sql, [$id]);

            if (!$card) {
                return false;
            }

            $oldListId = $card['list_id'];
            $listChanged = ($oldListId != $listId);

            // トランザクション開始
            $this->db->beginTransaction();

            if ($listChanged) {
                // リストが変更された場合
                // 元のリストの順序を詰める
                $sql = "UPDATE task_cards 
                        SET sort_order = sort_order - 1 
                        WHERE list_id = ? AND sort_order > (
                            SELECT sort_order FROM (
                                SELECT sort_order FROM task_cards WHERE id = ?
                            ) AS t
                        )";
                $this->db->execute($sql, [$oldListId, $id]);

                // 新しいリストに挿入する位置を空ける
                $sql = "UPDATE task_cards 
                        SET sort_order = sort_order + 1 
                        WHERE list_id = ? AND sort_order >= ?";
                $this->db->execute($sql, [$listId, $newOrder]);

                // カードを移動
                $sql = "UPDATE task_cards SET list_id = ?, sort_order = ? WHERE id = ?";
                $this->db->execute($sql, [$listId, $newOrder, $id]);

                // 活動ログを記録
                $this->logActivity($this->getBoardIdFromList($listId), $id, $userId, 'card_moved', [
                    'from_list' => $this->getListName($oldListId),
                    'to_list' => $this->getListName($listId)
                ]);
            } else {
                // 同じリスト内での順序変更
                // 現在の順序を取得
                $sql = "SELECT sort_order FROM task_cards WHERE id = ?";
                $current = $this->db->fetch($sql, [$id]);
                $currentOrder = $current ? $current['sort_order'] : null;

                if ($currentOrder === null || $currentOrder == $newOrder) {
                    $this->db->rollBack();
                    return true;
                }

                // 移動方向に応じて順序を更新
                if ($newOrder > $currentOrder) {
                    // 下に移動する場合
                    $sql = "UPDATE task_cards 
                            SET sort_order = sort_order - 1 
                            WHERE list_id = ? AND sort_order > ? AND sort_order <= ?";
                    $this->db->execute($sql, [$listId, $currentOrder, $newOrder]);
                } else {
                    // 上に移動する場合
                    $sql = "UPDATE task_cards 
                            SET sort_order = sort_order + 1 
                            WHERE list_id = ? AND sort_order >= ? AND sort_order < ?";
                    $this->db->execute($sql, [$listId, $newOrder, $currentOrder]);
                }

                // 対象カードの順序を更新
                $sql = "UPDATE task_cards SET sort_order = ? WHERE id = ?";
                $this->db->execute($sql, [$newOrder, $id]);
            }

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("Error updating card order: " . $e->getMessage());
            return false;
        }
    }

    /**
     * カードに担当者を追加
     * 
     * @param int $cardId カードID
     * @param int $userId ユーザーID
     * @return bool 成功時true、失敗時false
     */
    public function addCardAssignee($cardId, $userId)
    {
        try {
            // 既に存在するか確認
            $sql = "SELECT * FROM task_assignees WHERE card_id = ? AND user_id = ?";
            $existing = $this->db->fetch($sql, [$cardId, $userId]);

            if ($existing) {
                return true; // 既に存在する場合は成功とみなす
            }

            $sql = "INSERT INTO task_assignees (card_id, user_id) VALUES (?, ?)";
            return $this->db->execute($sql, [$cardId, $userId]);
        } catch (\Exception $e) {
            error_log("Error adding card assignee: " . $e->getMessage());
            return false;
        }
    }

    /**
     * カードの担当者を削除
     * 
     * @param int $cardId カードID
     * @param int $userId ユーザーID
     * @return bool 成功時true、失敗時false
     */
    public function removeCardAssignee($cardId, $userId)
    {
        try {
            $sql = "DELETE FROM task_assignees WHERE card_id = ? AND user_id = ?";
            return $this->db->execute($sql, [$cardId, $userId]);
        } catch (\Exception $e) {
            error_log("Error removing card assignee: " . $e->getMessage());
            return false;
        }
    }

    /**
     * カードの担当者一覧を取得
     * 
     * @param int $cardId カードID
     * @return array 担当者リスト
     */
    public function getCardAssignees($cardId)
    {
        try {
            $sql = "SELECT a.*, u.display_name, u.email 
                    FROM task_assignees a
                    JOIN users u ON a.user_id = u.id
                    WHERE a.card_id = ?";

            return $this->db->fetchAll($sql, [$cardId]);
        } catch (\Exception $e) {
            error_log("Error getting card assignees: " . $e->getMessage());
            return [];
        }
    }

    /**
     * ラベルの作成
     * 
     * @param int $boardId ボードID
     * @param array $data ラベルデータ
     * @return int|bool 成功時は新規ラベルID、失敗時はfalse
     */
    public function createLabel($boardId, $data)
    {
        try {
            $sql = "INSERT INTO task_labels (board_id, name, color) VALUES (?, ?, ?)";

            $this->db->execute($sql, [
                $boardId,
                $data['name'],
                $data['color'] ?? '#cccccc'
            ]);

            return $this->db->lastInsertId();
        } catch (\Exception $e) {
            error_log("Error creating task label: " . $e->getMessage());
            return false;
        }
    }

    /**
     * ラベルの更新
     * 
     * @param int $id ラベルID
     * @param array $data ラベルデータ
     * @return bool 成功時true、失敗時false
     */
    public function updateLabel($id, $data)
    {
        try {
            $sql = "UPDATE task_labels SET name = ?, color = ? WHERE id = ?";

            return $this->db->execute($sql, [
                $data['name'],
                $data['color'] ?? '#cccccc',
                $id
            ]);
        } catch (\Exception $e) {
            error_log("Error updating task label: " . $e->getMessage());
            return false;
        }
    }

    /**
     * ラベルの削除
     * 
     * @param int $id ラベルID
     * @return bool 成功時true、失敗時false
     */
    public function deleteLabel($id)
    {
        try {
            return $this->db->execute("DELETE FROM task_labels WHERE id = ?", [$id]);
        } catch (\Exception $e) {
            error_log("Error deleting task label: " . $e->getMessage());
            return false;
        }
    }

    /**
     * ラベルの詳細を取得
     * 
     * @param int $id ラベルID
     * @return array|null ラベル情報
     */
    public function getLabel($id)
    {
        try {
            $sql = "SELECT * FROM task_labels WHERE id = ? LIMIT 1";
            return $this->db->fetch($sql, [$id]);
        } catch (\Exception $e) {
            error_log("Error getting task label: " . $e->getMessage());
            return null;
        }
    }

    /**
     * ボードのラベル一覧を取得
     * 
     * @param int $boardId ボードID
     * @return array ラベルリスト
     */
    public function getBoardLabels($boardId)
    {
        try {
            $sql = "SELECT * FROM task_labels WHERE board_id = ? ORDER BY name";
            return $this->db->fetchAll($sql, [$boardId]);
        } catch (\Exception $e) {
            error_log("Error getting board labels: " . $e->getMessage());
            return [];
        }
    }

    /**
     * カードにラベルを追加
     * 
     * @param int $cardId カードID
     * @param int $labelId ラベルID
     * @return bool 成功時true、失敗時false
     */
    public function addCardLabel($cardId, $labelId)
    {
        try {
            // 既に存在するか確認
            $sql = "SELECT * FROM task_card_labels WHERE card_id = ? AND label_id = ?";
            $existing = $this->db->fetch($sql, [$cardId, $labelId]);

            if ($existing) {
                return true; // 既に存在する場合は成功とみなす
            }

            $sql = "INSERT INTO task_card_labels (card_id, label_id) VALUES (?, ?)";
            return $this->db->execute($sql, [$cardId, $labelId]);
        } catch (\Exception $e) {
            error_log("Error adding card label: " . $e->getMessage());
            return false;
        }
    }

    /**
     * カードからラベルを削除
     * 
     * @param int $cardId カードID
     * @param int $labelId ラベルID
     * @return bool 成功時true、失敗時false
     */
    public function removeCardLabel($cardId, $labelId)
    {
        try {
            $sql = "DELETE FROM task_card_labels WHERE card_id = ? AND label_id = ?";
            return $this->db->execute($sql, [$cardId, $labelId]);
        } catch (\Exception $e) {
            error_log("Error removing card label: " . $e->getMessage());
            return false;
        }
    }

    /**
     * カードのラベル一覧を取得
     * 
     * @param int $cardId カードID
     * @return array ラベルリスト
     */
    public function getCardLabels($cardId)
    {
        try {
            $sql = "SELECT l.* FROM task_labels l
                    JOIN task_card_labels cl ON l.id = cl.label_id
                    WHERE cl.card_id = ?
                    ORDER BY l.name";

            return $this->db->fetchAll($sql, [$cardId]);
        } catch (\Exception $e) {
            error_log("Error getting card labels: " . $e->getMessage());
            return [];
        }
    }

    /**
     * コメントの追加
     * 
     * @param int $cardId カードID
     * @param int $userId ユーザーID
     * @param string $comment コメント内容
     * @return int|bool 成功時は新規コメントID、失敗時はfalse
     */
    public function addComment($cardId, $userId, $comment)
    {
        try {
            $sql = "INSERT INTO task_comments (card_id, user_id, comment) VALUES (?, ?, ?)";

            $this->db->execute($sql, [$cardId, $userId, $comment]);

            $commentId = $this->db->lastInsertId();

            // 活動ログを記録
            $card = $this->getCard($cardId);
            if ($card) {
                $boardId = $this->getBoardIdFromList($card['list_id']);
                $this->logActivity($boardId, $cardId, $userId, 'comment_added', [
                    'comment_id' => $commentId
                ]);
            }

            return $commentId;
        } catch (\Exception $e) {
            error_log("Error adding comment: " . $e->getMessage());
            return false;
        }
    }

    /**
     * コメントの更新
     * 
     * @param int $id コメントID
     * @param string $comment 新しいコメント内容
     * @param int $userId 更新者ID
     * @return bool 成功時true、失敗時false
     */
    public function updateComment($id, $comment, $userId)
    {
        try {
            // コメント所有者チェック
            $sql = "SELECT * FROM task_comments WHERE id = ? AND user_id = ?";
            $existing = $this->db->fetch($sql, [$id, $userId]);

            if (!$existing) {
                return false; // 所有者でない場合は更新不可
            }

            $sql = "UPDATE task_comments SET comment = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            return $this->db->execute($sql, [$comment, $id]);
        } catch (\Exception $e) {
            error_log("Error updating comment: " . $e->getMessage());
            return false;
        }
    }

    /**
     * コメントの削除
     * 
     * @param int $id コメントID
     * @param int $userId 削除者ID（所有者またはボード管理者）
     * @return bool 成功時true、失敗時false
     */
    public function deleteComment($id, $userId)
    {
        try {
            // コメント所有者またはボード管理者かチェック
            $sql = "SELECT c.*, tc.list_id FROM task_comments c
                    JOIN task_cards tc ON c.card_id = tc.id
                    WHERE c.id = ?";
            $comment = $this->db->fetch($sql, [$id]);

            if (!$comment) {
                return false;
            }

            // ボードIDを取得
            $boardId = $this->getBoardIdFromList($comment['list_id']);

            // 所有者またはボード管理者かチェック
            if ($comment['user_id'] != $userId && !$this->isUserBoardAdmin($boardId, $userId)) {
                return false;
            }

            return $this->db->execute("DELETE FROM task_comments WHERE id = ?", [$id]);
        } catch (\Exception $e) {
            error_log("Error deleting comment: " . $e->getMessage());
            return false;
        }
    }

    /**
     * コメントの取得
     * 
     * @param int $id コメントID
     * @return array|null コメント情報
     */
    public function getComment($id)
    {
        try {
            $sql = "SELECT c.*, u.display_name, u.email 
                    FROM task_comments c
                    JOIN users u ON c.user_id = u.id
                    WHERE c.id = ? LIMIT 1";
            return $this->db->fetch($sql, [$id]);
        } catch (\Exception $e) {
            error_log("Error getting comment: " . $e->getMessage());
            return null;
        }
    }

    /**
     * カードのコメント一覧を取得
     * 
     * @param int $cardId カードID
     * @return array コメントリスト
     */
    public function getCardComments($cardId)
    {
        try {
            $sql = "SELECT c.*, u.display_name, u.email 
                    FROM task_comments c
                    JOIN users u ON c.user_id = u.id
                    WHERE c.card_id = ?
                    ORDER BY c.created_at";

            return $this->db->fetchAll($sql, [$cardId]);
        } catch (\Exception $e) {
            error_log("Error getting card comments: " . $e->getMessage());
            return [];
        }
    }

    /**
     * チェックリストの作成
     * 
     * @param int $cardId カードID
     * @param string $title チェックリスト名
     * @return int|bool 成功時は新規チェックリストID、失敗時はfalse
     */
    public function createChecklist($cardId, $title)
    {
        try {
            // 最大のソート順を取得
            $sql = "SELECT MAX(sort_order) as max_order FROM task_checklists WHERE card_id = ?";
            $result = $this->db->fetch($sql, [$cardId]);
            $sortOrder = ($result && isset($result['max_order'])) ? $result['max_order'] + 1 : 0;

            $sql = "INSERT INTO task_checklists (card_id, title, sort_order) VALUES (?, ?, ?)";

            $this->db->execute($sql, [$cardId, $title, $sortOrder]);

            return $this->db->lastInsertId();
        } catch (\Exception $e) {
            error_log("Error creating checklist: " . $e->getMessage());
            return false;
        }
    }

    /**
     * チェックリストの更新
     * 
     * @param int $id チェックリストID
     * @param string $title 新しいチェックリスト名
     * @return bool 成功時true、失敗時false
     */
    public function updateChecklist($id, $title)
    {
        try {
            $sql = "UPDATE task_checklists SET title = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            return $this->db->execute($sql, [$title, $id]);
        } catch (\Exception $e) {
            error_log("Error updating checklist: " . $e->getMessage());
            return false;
        }
    }

    /**
     * チェックリストの削除
     * 
     * @param int $id チェックリストID
     * @return bool 成功時true、失敗時false
     */
    public function deleteChecklist($id)
    {
        try {
            return $this->db->execute("DELETE FROM task_checklists WHERE id = ?", [$id]);
        } catch (\Exception $e) {
            error_log("Error deleting checklist: " . $e->getMessage());
            return false;
        }
    }

    /**
     * チェックリストの取得
     * 
     * @param int $id チェックリストID
     * @return array|null チェックリスト情報
     */
    public function getChecklist($id)
    {
        try {
            $sql = "SELECT * FROM task_checklists WHERE id = ? LIMIT 1";
            $checklist = $this->db->fetch($sql, [$id]);

            if ($checklist) {
                // チェックリスト項目の取得
                $sql = "SELECT * FROM task_checklist_items WHERE checklist_id = ? ORDER BY sort_order";
                $checklist['items'] = $this->db->fetchAll($sql, [$id]);
            }

            return $checklist;
        } catch (\Exception $e) {
            error_log("Error getting checklist: " . $e->getMessage());
            return null;
        }
    }

    /**
     * カードのチェックリスト一覧を取得
     * 
     * @param int $cardId カードID
     * @return array チェックリストリスト
     */
    public function getCardChecklists($cardId)
    {
        try {
            $sql = "SELECT * FROM task_checklists WHERE card_id = ? ORDER BY sort_order";
            $checklists = $this->db->fetchAll($sql, [$cardId]);

            // チェックリスト項目の取得
            foreach ($checklists as &$checklist) {
                $sql = "SELECT * FROM task_checklist_items WHERE checklist_id = ? ORDER BY sort_order";
                $checklist['items'] = $this->db->fetchAll($sql, [$checklist['id']]);

                // 完了率の計算
                $totalItems = count($checklist['items']);
                $completedItems = 0;

                foreach ($checklist['items'] as $item) {
                    if ($item['is_checked']) {
                        $completedItems++;
                    }
                }

                $checklist['completion'] = $totalItems > 0 ? round(($completedItems / $totalItems) * 100) : 0;
            }

            return $checklists;
        } catch (\Exception $e) {
            error_log("Error getting card checklists: " . $e->getMessage());
            return [];
        }
    }

    /**
     * チェックリスト項目の追加
     * 
     * @param int $checklistId チェックリストID
     * @param string $content 項目内容
     * @return int|bool 成功時は新規項目ID、失敗時はfalse
     */
    public function addChecklistItem($checklistId, $content)
    {
        try {
            // 最大のソート順を取得
            $sql = "SELECT MAX(sort_order) as max_order FROM task_checklist_items WHERE checklist_id = ?";
            $result = $this->db->fetch($sql, [$checklistId]);
            $sortOrder = ($result && isset($result['max_order'])) ? $result['max_order'] + 1 : 0;

            $sql = "INSERT INTO task_checklist_items (checklist_id, content, sort_order) VALUES (?, ?, ?)";

            $this->db->execute($sql, [$checklistId, $content, $sortOrder]);

            return $this->db->lastInsertId();
        } catch (\Exception $e) {
            error_log("Error adding checklist item: " . $e->getMessage());
            return false;
        }
    }

    /**
     * チェックリスト項目の更新
     * 
     * @param int $id 項目ID
     * @param array $data 更新データ（content, is_checked）
     * @return bool 成功時true、失敗時false
     */
    public function updateChecklistItem($id, $data)
    {
        try {
            $sql = "UPDATE task_checklist_items SET ";
            $params = [];

            if (isset($data['content'])) {
                $sql .= "content = ?, ";
                $params[] = $data['content'];
            }

            if (isset($data['is_checked'])) {
                $sql .= "is_checked = ?, ";
                $params[] = $data['is_checked'] ? 1 : 0;
            }

            $sql .= "updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $params[] = $id;

            return $this->db->execute($sql, $params);
        } catch (\Exception $e) {
            error_log("Error updating checklist item: " . $e->getMessage());
            return false;
        }
    }

    /**
     * チェックリスト項目の削除
     * 
     * @param int $id 項目ID
     * @return bool 成功時true、失敗時false
     */
    public function deleteChecklistItem($id)
    {
        try {
            return $this->db->execute("DELETE FROM task_checklist_items WHERE id = ?", [$id]);
        } catch (\Exception $e) {
            error_log("Error deleting checklist item: " . $e->getMessage());
            return false;
        }
    }

    /**
     * ファイルの添付
     * 
     * @param int $cardId カードID
     * @param array $fileData ファイルデータ
     * @param int $userId アップロードユーザーID
     * @return int|bool 成功時は新規添付ファイルID、失敗時はfalse
     */
    public function attachFile($cardId, $fileData, $userId)
    {
        try {
            $sql = "INSERT INTO task_attachments (
                card_id,
                file_name,
                file_path,
                file_size,
                mime_type,
                uploaded_by
            ) VALUES (?, ?, ?, ?, ?, ?)";

            $this->db->execute($sql, [
                $cardId,
                $fileData['name'],
                $fileData['path'],
                $fileData['size'],
                $fileData['type'],
                $userId
            ]);

            $attachmentId = $this->db->lastInsertId();

            // 活動ログを記録
            $card = $this->getCard($cardId);
            if ($card) {
                $boardId = $this->getBoardIdFromList($card['list_id']);
                $this->logActivity($boardId, $cardId, $userId, 'file_attached', [
                    'file_name' => $fileData['name']
                ]);
            }

            return $attachmentId;
        } catch (\Exception $e) {
            error_log("Error attaching file: " . $e->getMessage());
            return false;
        }
    }

    /**
     * ファイル添付の削除
     * 
     * @param int $id 添付ファイルID
     * @param int $userId 削除者ID
     * @return bool 成功時true、失敗時false
     */
    public function deleteAttachment($id, $userId)
    {
        try {
            // 添付ファイル情報を取得
            $sql = "SELECT a.*, c.list_id FROM task_attachments a 
                    JOIN task_cards c ON a.card_id = c.id
                    WHERE a.id = ?";
            $attachment = $this->db->fetch($sql, [$id]);

            if (!$attachment) {
                return false;
            }

            // ボードIDを取得
            $boardId = $this->getBoardIdFromList($attachment['list_id']);

            // アップロード者またはボード管理者かチェック
            if ($attachment['uploaded_by'] != $userId && !$this->isUserBoardAdmin($boardId, $userId)) {
                return false;
            }

            // 活動ログを記録
            $this->logActivity($boardId, $attachment['card_id'], $userId, 'file_deleted', [
                'file_name' => $attachment['file_name']
            ]);

            // ファイル削除
            $filePath = $attachment['file_path'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            return $this->db->execute("DELETE FROM task_attachments WHERE id = ?", [$id]);
        } catch (\Exception $e) {
            error_log("Error deleting attachment: " . $e->getMessage());
            return false;
        }
    }

    /**
     * カードの添付ファイル一覧を取得
     * 
     * @param int $cardId カードID
     * @return array 添付ファイルリスト
     */
    public function getCardAttachments($cardId)
    {
        try {
            $sql = "SELECT a.*, u.display_name as uploader_name 
                    FROM task_attachments a
                    JOIN users u ON a.uploaded_by = u.id
                    WHERE a.card_id = ?
                    ORDER BY a.created_at DESC";

            return $this->db->fetchAll($sql, [$cardId]);
        } catch (\Exception $e) {
            error_log("Error getting card attachments: " . $e->getMessage());
            return [];
        }
    }

    /**
     * 活動ログを記録
     * 
     * @param int $boardId ボードID
     * @param int|null $cardId カードID（null可）
     * @param int $userId ユーザーID
     * @param string $actionType アクション種類
     * @param array $actionData アクションデータ
     * @return int|bool 成功時は新規活動ログID、失敗時はfalse
     */
    public function logActivity($boardId, $cardId, $userId, $actionType, $actionData = [])
    {
        try {
            $sql = "INSERT INTO task_activities (
                board_id,
                card_id,
                user_id,
                action_type,
                action_data
            ) VALUES (?, ?, ?, ?, ?)";

            $this->db->execute($sql, [
                $boardId,
                $cardId,
                $userId,
                $actionType,
                json_encode($actionData)
            ]);

            return $this->db->lastInsertId();
        } catch (\Exception $e) {
            error_log("Error logging activity: " . $e->getMessage());
            return false;
        }
    }

    /**
     * ボードの活動ログ一覧を取得
     * 
     * @param int $boardId ボードID
     * @param int $limit 取得件数
     * @param int $offset オフセット
     * @return array 活動ログリスト
     */
    public function getBoardActivities($boardId, $limit = 20, $offset = 0)
    {
        try {
            $sql = "SELECT a.*, u.display_name, u.email, c.title as card_title
                    FROM task_activities a
                    JOIN users u ON a.user_id = u.id
                    LEFT JOIN task_cards c ON a.card_id = c.id
                    WHERE a.board_id = ?
                    ORDER BY a.created_at DESC
                    LIMIT ? OFFSET ?";

            return $this->db->fetchAll($sql, [$boardId, $limit, $offset]);
        } catch (\Exception $e) {
            error_log("Error getting board activities: " . $e->getMessage());
            return [];
        }
    }

    /**
     * カードの活動ログ一覧を取得
     * 
     * @param int $cardId カードID
     * @param int $limit 取得件数
     * @param int $offset オフセット
     * @return array 活動ログリスト
     */
    public function getCardActivities($cardId, $limit = 20, $offset = 0)
    {
        try {
            $sql = "SELECT a.*, u.display_name, u.email
                    FROM task_activities a
                    JOIN users u ON a.user_id = u.id
                    WHERE a.card_id = ?
                    ORDER BY a.created_at DESC
                    LIMIT ? OFFSET ?";

            return $this->db->fetchAll($sql, [$cardId, $limit, $offset]);
        } catch (\Exception $e) {
            error_log("Error getting card activities: " . $e->getMessage());
            return [];
        }
    }

    /**
     * ユーザーの活動ログ一覧を取得
     * 
     * @param int $userId ユーザーID
     * @param int $limit 取得件数
     * @param int $offset オフセット
     * @return array 活動ログリスト
     */
    public function getUserActivities($userId, $limit = 20, $offset = 0)
    {
        try {
            $sql = "SELECT a.*, u.display_name, u.email, c.title as card_title, b.name as board_name
                    FROM task_activities a
                    JOIN users u ON a.user_id = u.id
                    JOIN task_boards b ON a.board_id = b.id
                    LEFT JOIN task_cards c ON a.card_id = c.id
                    WHERE a.user_id = ?
                    ORDER BY a.created_at DESC
                    LIMIT ? OFFSET ?";

            return $this->db->fetchAll($sql, [$userId, $limit, $offset]);
        } catch (\Exception $e) {
            error_log("Error getting user activities: " . $e->getMessage());
            return [];
        }
    }

    /**
     * ユーザーがボードの管理者かどうかをチェック
     * 
     * @param int $boardId ボードID
     * @param int $userId ユーザーID
     * @return bool 管理者の場合true、そうでなければfalse
     */
    public function isUserBoardAdmin($boardId, $userId)
    {
        try {
            $sql = "SELECT * FROM task_board_members WHERE board_id = ? AND user_id = ? AND role = 'admin'";
            $result = $this->db->fetch($sql, [$boardId, $userId]);

            return $result !== false;
        } catch (\Exception $e) {
            error_log("Error checking user board admin: " . $e->getMessage());
            return false;
        }
    }

    /**
     * ユーザーにボードへのアクセス権があるかどうかをチェック
     * 
     * @param int $boardId ボードID
     * @param int $userId ユーザーID
     * @return bool アクセス権がある場合true、そうでなければfalse
     */
    public function canUserAccessBoard($boardId, $userId)
    {
        try {
            // ボード情報を取得
            $board = $this->getBoard($boardId);

            if (!$board) {
                return false;
            }

            // 公開ボードの場合はアクセス可能
            if ($board['is_public']) {
                return true;
            }

            // ユーザー所有のボードの場合
            if ($board['owner_type'] == 'user' && $board['owner_id'] == $userId) {
                return true;
            }

            // ボードメンバーの場合
            $sql = "SELECT * FROM task_board_members WHERE board_id = ? AND user_id = ?";
            $member = $this->db->fetch($sql, [$boardId, $userId]);

            if ($member) {
                return true;
            }

            // チーム所有のボードの場合
            if ($board['owner_type'] == 'team') {
                $teamModel = new Team();
                return $teamModel->isUserTeamMember($board['owner_id'], $userId);
            }

            // 組織所有のボードの場合
            if ($board['owner_type'] == 'organization') {
                $userModel = new User();
                $userOrgs = $userModel->getUserOrganizationIds($userId);
                return in_array($board['owner_id'], $userOrgs);
            }

            return false;
        } catch (\Exception $e) {
            error_log("Error checking user board access: " . $e->getMessage());
            return false;
        }
    }

    /**
     * ユーザーがカードを編集可能かどうかをチェック
     * 
     * @param int $cardId カードID
     * @param int $userId ユーザーID
     * @return bool 編集可能な場合true、そうでなければfalse
     */
    public function canUserEditCard($cardId, $userId)
    {
        try {
            // カード情報を取得
            $card = $this->getCard($cardId);

            if (!$card) {
                return false;
            }

            // リストからボードIDを取得
            $boardId = $this->getBoardIdFromList($card['list_id']);

            // ボード情報を取得
            $board = $this->getBoard($boardId);

            if (!$board) {
                return false;
            }

            // カード作成者の場合
            if ($card['created_by'] == $userId) {
                return true;
            }

            // ユーザー所有のボードの場合
            if ($board['owner_type'] == 'user' && $board['owner_id'] == $userId) {
                return true;
            }

            // ボードメンバーの場合
            $sql = "SELECT * FROM task_board_members WHERE board_id = ? AND user_id = ? AND role IN ('admin', 'editor')";
            $member = $this->db->fetch($sql, [$boardId, $userId]);

            if ($member) {
                return true;
            }

            // チーム所有のボードの場合
            if ($board['owner_type'] == 'team') {
                $teamModel = new Team();
                return $teamModel->isUserTeamAdmin($board['owner_id'], $userId);
            }

            // 組織所有のボードの場合は管理者のみ編集可能
            if ($board['owner_type'] == 'organization') {
                $user = new User();
                return $user->getById($userId)['role'] == 'admin';
            }

            return false;
        } catch (\Exception $e) {
            error_log("Error checking user card edit permission: " . $e->getMessage());
            return false;
        }
    }

    /**
     * ユーザーの担当タスク一覧を取得
     * 
     * @param int $userId ユーザーID
     * @param array $filters フィルター条件
     * @return array タスクリスト
     */
    public function getUserTasks($userId, $filters = [])
    {
        try {
            $params = [$userId];

            $sql = "SELECT c.*, l.name as list_name, b.name as board_name, b.id as board_id
                    FROM task_cards c
                    JOIN task_assignees a ON c.id = a.card_id
                    JOIN task_lists l ON c.list_id = l.id
                    JOIN task_boards b ON l.board_id = b.id
                    WHERE a.user_id = ?";

            // ステータスフィルター
            if (isset($filters['status']) && !empty($filters['status'])) {
                $sql .= " AND c.status = ?";
                $params[] = $filters['status'];
            }

            // 期限フィルター
            if (isset($filters['due_date'])) {
                if ($filters['due_date'] == 'overdue') {
                    $sql .= " AND c.due_date < CURDATE() AND c.status != 'completed'";
                } elseif ($filters['due_date'] == 'today') {
                    $sql .= " AND c.due_date = CURDATE()";
                } elseif ($filters['due_date'] == 'week') {
                    $sql .= " AND c.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
                }
            }

            // 優先度フィルター
            if (isset($filters['priority']) && !empty($filters['priority'])) {
                $sql .= " AND c.priority = ?";
                $params[] = $filters['priority'];
            }

            // ボードフィルター
            if (isset($filters['board_id']) && !empty($filters['board_id'])) {
                $sql .= " AND b.id = ?";
                $params[] = $filters['board_id'];
            }

            // 並び順
            $orderBy = "c.due_date IS NULL, c.due_date";
            if (isset($filters['order_by'])) {
                if ($filters['order_by'] == 'priority') {
                    $orderBy = "FIELD(c.priority, 'highest', 'high', 'normal', 'low', 'lowest')";
                } elseif ($filters['order_by'] == 'status') {
                    $orderBy = "FIELD(c.status, 'not_started', 'in_progress', 'completed', 'deferred')";
                } elseif ($filters['order_by'] == 'progress') {
                    $orderBy = "c.progress DESC";
                }
            }

            $sql .= " ORDER BY " . $orderBy;

            return $this->db->fetchAll($sql, $params);
        } catch (\Exception $e) {
            error_log("Error getting user tasks: " . $e->getMessage());
            return [];
        }
    }

    /**
     * ユーザーの直近のタスク一覧を取得（期限が近いもの）
     * 
     * @param int $userId ユーザーID
     * @param int $limit 取得件数
     * @return array タスクリスト
     */
    public function getUserUpcomingTasks($userId, $limit = 5)
    {
        try {
            $sql = "SELECT c.*, l.name as list_name, b.name as board_name, b.id as board_id
                    FROM task_cards c
                    JOIN task_assignees a ON c.id = a.card_id
                    JOIN task_lists l ON c.list_id = l.id
                    JOIN task_boards b ON l.board_id = b.id
                    WHERE a.user_id = ? 
                    AND c.status != 'completed' 
                    AND c.due_date IS NOT NULL
                    AND c.due_date >= CURDATE()
                    ORDER BY c.due_date, FIELD(c.priority, 'highest', 'high', 'normal', 'low', 'lowest')
                    LIMIT ?";

            return $this->db->fetchAll($sql, [$userId, $limit]);
        } catch (\Exception $e) {
            error_log("Error getting user upcoming tasks: " . $e->getMessage());
            return [];
        }
    }

    /**
     * ユーザーの遅延しているタスク一覧を取得
     * 
     * @param int $userId ユーザーID
     * @param int $limit 取得件数
     * @return array タスクリスト
     */
    public function getUserOverdueTasks($userId, $limit = 5)
    {
        try {
            $sql = "SELECT c.*, l.name as list_name, b.name as board_name, b.id as board_id
                    FROM task_cards c
                    JOIN task_assignees a ON c.id = a.card_id
                    JOIN task_lists l ON c.list_id = l.id
                    JOIN task_boards b ON l.board_id = b.id
                    WHERE a.user_id = ? 
                    AND c.status != 'completed' 
                    AND c.due_date IS NOT NULL
                    AND c.due_date < CURDATE()
                    ORDER BY c.due_date, FIELD(c.priority, 'highest', 'high', 'normal', 'low', 'lowest')
                    LIMIT ?";

            return $this->db->fetchAll($sql, [$userId, $limit]);
        } catch (\Exception $e) {
            error_log("Error getting user overdue tasks: " . $e->getMessage());
            return [];
        }
    }

    /**
     * タスク進捗状況のサマリーを取得
     * 
     * @param int $userId ユーザーID
     * @return array 集計データ
     */
    public function getUserTasksSummary($userId)
    {
        try {
            // ステータス別のタスク数
            $sql = "SELECT c.status, COUNT(*) as count
                    FROM task_cards c
                    JOIN task_assignees a ON c.id = a.card_id
                    WHERE a.user_id = ?
                    GROUP BY c.status";

            $statusCounts = $this->db->fetchAll($sql, [$userId]);

            // 優先度別のタスク数
            $sql = "SELECT c.priority, COUNT(*) as count
                    FROM task_cards c
                    JOIN task_assignees a ON c.id = a.card_id
                    WHERE a.user_id = ?
                    GROUP BY c.priority";

            $priorityCounts = $this->db->fetchAll($sql, [$userId]);

            // ボード別のタスク数
            $sql = "SELECT b.id, b.name, COUNT(*) as count
                    FROM task_cards c
                    JOIN task_assignees a ON c.id = a.card_id
                    JOIN task_lists l ON c.list_id = l.id
                    JOIN task_boards b ON l.board_id = b.id
                    WHERE a.user_id = ?
                    GROUP BY b.id";

            $boardCounts = $this->db->fetchAll($sql, [$userId]);

            // 期限切れのタスク数
            $sql = "SELECT COUNT(*) as count
                    FROM task_cards c
                    JOIN task_assignees a ON c.id = a.card_id
                    WHERE a.user_id = ? 
                    AND c.status != 'completed' 
                    AND c.due_date IS NOT NULL
                    AND c.due_date < CURDATE()";

            $overdue = $this->db->fetch($sql, [$userId]);

            // 今日が期限のタスク数
            $sql = "SELECT COUNT(*) as count
                    FROM task_cards c
                    JOIN task_assignees a ON c.id = a.card_id
                    WHERE a.user_id = ? 
                    AND c.status != 'completed' 
                    AND c.due_date = CURDATE()";

            $dueToday = $this->db->fetch($sql, [$userId]);

            // 今週が期限のタスク数
            $sql = "SELECT COUNT(*) as count
                    FROM task_cards c
                    JOIN task_assignees a ON c.id = a.card_id
                    WHERE a.user_id = ? 
                    AND c.status != 'completed' 
                    AND c.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";

            $dueThisWeek = $this->db->fetch($sql, [$userId]);

            return [
                'status' => $statusCounts,
                'priority' => $priorityCounts,
                'boards' => $boardCounts,
                'due_dates' => [
                    'overdue' => $overdue['count'] ?? 0,
                    'today' => $dueToday['count'] ?? 0,
                    'this_week' => $dueThisWeek['count'] ?? 0
                ],
                'total' => array_sum(array_column($statusCounts, 'count'))
            ];
        } catch (\Exception $e) {
            error_log("Error getting user tasks summary: " . $e->getMessage());
            return [];
        }
    }

    /**
     * ボードのタスク進捗状況のサマリーを取得
     * 
     * @param int $boardId ボードID
     * @return array 集計データ
     */
    public function getBoardTasksSummary($boardId)
    {
        try {
            // リスト別のタスク数
            $sql = "SELECT l.id, l.name, COUNT(c.id) as count
                    FROM task_lists l
                    LEFT JOIN task_cards c ON l.id = c.list_id
                    WHERE l.board_id = ?
                    GROUP BY l.id
                    ORDER BY l.sort_order";

            $listCounts = $this->db->fetchAll($sql, [$boardId]);

            // ステータス別のタスク数
            $sql = "SELECT c.status, COUNT(*) as count
                    FROM task_cards c
                    JOIN task_lists l ON c.list_id = l.id
                    WHERE l.board_id = ?
                    GROUP BY c.status";

            $statusCounts = $this->db->fetchAll($sql, [$boardId]);

            // 優先度別のタスク数
            $sql = "SELECT c.priority, COUNT(*) as count
                    FROM task_cards c
                    JOIN task_lists l ON c.list_id = l.id
                    WHERE l.board_id = ?
                    GROUP BY c.priority";

            $priorityCounts = $this->db->fetchAll($sql, [$boardId]);

            // 担当者別のタスク数
            $sql = "SELECT u.id, u.display_name, COUNT(DISTINCT c.id) as count
                    FROM users u
                    JOIN task_assignees a ON u.id = a.user_id
                    JOIN task_cards c ON a.card_id = c.id
                    JOIN task_lists l ON c.list_id = l.id
                    WHERE l.board_id = ?
                    GROUP BY u.id
                    ORDER BY count DESC";

            $assigneeCounts = $this->db->fetchAll($sql, [$boardId]);

            // 期限切れのタスク数
            $sql = "SELECT COUNT(*) as count
                    FROM task_cards c
                    JOIN task_lists l ON c.list_id = l.id
                    WHERE l.board_id = ? 
                    AND c.status != 'completed' 
                    AND c.due_date IS NOT NULL
                    AND c.due_date < CURDATE()";

            $overdue = $this->db->fetch($sql, [$boardId]);

            // 今週が期限のタスク数
            $sql = "SELECT COUNT(*) as count
                    FROM task_cards c
                    JOIN task_lists l ON c.list_id = l.id
                    WHERE l.board_id = ? 
                    AND c.status != 'completed' 
                    AND c.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";

            $dueThisWeek = $this->db->fetch($sql, [$boardId]);

            // 総タスク数と完了タスク数
            $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN c.status = 'completed' THEN 1 ELSE 0 END) as completed
                    FROM task_cards c
                    JOIN task_lists l ON c.list_id = l.id
                    WHERE l.board_id = ?";

            $completionStats = $this->db->fetch($sql, [$boardId]);

            return [
                'lists' => $listCounts,
                'status' => $statusCounts,
                'priority' => $priorityCounts,
                'assignees' => $assigneeCounts,
                'due_dates' => [
                    'overdue' => $overdue['count'] ?? 0,
                    'this_week' => $dueThisWeek['count'] ?? 0
                ],
                'completion' => [
                    'total' => $completionStats['total'] ?? 0,
                    'completed' => $completionStats['completed'] ?? 0,
                    'percentage' => ($completionStats['total'] > 0) ?
                        round(($completionStats['completed'] / $completionStats['total']) * 100) : 0
                ]
            ];
        } catch (\Exception $e) {
            error_log("Error getting board tasks summary: " . $e->getMessage());
            return [];
        }
    }

    /**
     * 組織のタスク進捗状況のサマリーを取得
     * 
     * @param int $organizationId 組織ID
     * @return array 集計データ
     */
    public function getOrganizationTasksSummary($organizationId)
    {
        try {
            // ボードのリスト取得
            $sql = "SELECT id FROM task_boards WHERE owner_type = 'organization' AND owner_id = ?";
            $boards = $this->db->fetchAll($sql, [$organizationId]);

            if (empty($boards)) {
                return [];
            }

            $boardIds = array_column($boards, 'id');
            $boardIdsStr = implode(',', $boardIds);

            // 総タスク数と完了タスク数
            $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN c.status = 'completed' THEN 1 ELSE 0 END) as completed
                    FROM task_cards c
                    JOIN task_lists l ON c.list_id = l.id
                    WHERE l.board_id IN ({$boardIdsStr})";

            $completionStats = $this->db->fetch($sql);

            // ボード別のタスク数
            $sql = "SELECT b.id, b.name, COUNT(c.id) as total,
                    SUM(CASE WHEN c.status = 'completed' THEN 1 ELSE 0 END) as completed
                    FROM task_boards b
                    LEFT JOIN task_lists l ON b.id = l.board_id
                    LEFT JOIN task_cards c ON l.id = c.list_id
                    WHERE b.id IN ({$boardIdsStr})
                    GROUP BY b.id
                    ORDER BY total DESC";

            $boardStats = $this->db->fetchAll($sql);

            // ユーザー別のタスク数
            $sql = "SELECT u.id, u.display_name, COUNT(DISTINCT c.id) as count
                    FROM users u
                    JOIN user_organizations uo ON u.id = uo.user_id
                    JOIN task_assignees a ON u.id = a.user_id
                    JOIN task_cards c ON a.card_id = c.id
                    JOIN task_lists l ON c.list_id = l.id
                    WHERE l.board_id IN ({$boardIdsStr})
                    AND uo.organization_id = ?
                    GROUP BY u.id
                    ORDER BY count DESC";

            $userStats = $this->db->fetchAll($sql, [$organizationId]);

            // ステータス別のタスク数
            $sql = "SELECT c.status, COUNT(*) as count
                    FROM task_cards c
                    JOIN task_lists l ON c.list_id = l.id
                    WHERE l.board_id IN ({$boardIdsStr})
                    GROUP BY c.status";

            $statusCounts = $this->db->fetchAll($sql);

            // 優先度別のタスク数
            $sql = "SELECT c.priority, COUNT(*) as count
                    FROM task_cards c
                    JOIN task_lists l ON c.list_id = l.id
                    WHERE l.board_id IN ({$boardIdsStr})
                    GROUP BY c.priority";

            $priorityCounts = $this->db->fetchAll($sql);

            // 期限切れのタスク数
            $sql = "SELECT COUNT(*) as count
                    FROM task_cards c
                    JOIN task_lists l ON c.list_id = l.id
                    WHERE l.board_id IN ({$boardIdsStr})
                    AND c.status != 'completed' 
                    AND c.due_date IS NOT NULL
                    AND c.due_date < CURDATE()";

            $overdue = $this->db->fetch($sql);

            return [
                'completion' => [
                    'total' => $completionStats['total'] ?? 0,
                    'completed' => $completionStats['completed'] ?? 0,
                    'percentage' => ($completionStats['total'] > 0) ?
                        round(($completionStats['completed'] / $completionStats['total']) * 100) : 0
                ],
                'boards' => $boardStats,
                'users' => $userStats,
                'status' => $statusCounts,
                'priority' => $priorityCounts,
                'overdue' => $overdue['count'] ?? 0
            ];
        } catch (\Exception $e) {
            error_log("Error getting organization tasks summary: " . $e->getMessage());
            return [];
        }
    }

    /**
     * リストからボードIDを取得する
     * 
     * @param int $listId リストID
     * @return int|null ボードID
     */
    private function getBoardIdFromList($listId)
    {
        try {
            $sql = "SELECT board_id FROM task_lists WHERE id = ? LIMIT 1";
            $result = $this->db->fetch($sql, [$listId]);

            return $result ? $result['board_id'] : null;
        } catch (\Exception $e) {
            error_log("Error getting board ID from list: " . $e->getMessage());
            return null;
        }
    }

    /**
     * リスト名を取得する
     * 
     * @param int $listId リストID
     * @return string|null リスト名
     */
    private function getListName($listId)
    {
        try {
            $sql = "SELECT name FROM task_lists WHERE id = ? LIMIT 1";
            $result = $this->db->fetch($sql, [$listId]);

            return $result ? $result['name'] : null;
        } catch (\Exception $e) {
            error_log("Error getting list name: " . $e->getMessage());
            return null;
        }
    }

    /**
     * チェックリスト項目を取得
     * 
     * @param int $id 項目ID
     * @return array|null 項目情報
     */
    public function getChecklistItem($id)
    {
        try {
            $sql = "SELECT * FROM task_checklist_items WHERE id = ? LIMIT 1";
            return $this->db->fetch($sql, [$id]);
        } catch (\Exception $e) {
            error_log("Error getting checklist item: " . $e->getMessage());
            return null;
        }
    }
}

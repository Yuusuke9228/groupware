<?php
// models/DailyReport.php
namespace Models;

use Core\Database;

class DailyReport
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * 日報を作成する
     * 
     * @param array $data 日報データ
     * @return int|bool 成功時は新規日報ID、失敗時はfalse
     */
    public function create($data)
    {
        try {
            $this->db->beginTransaction();

            // 日報を作成
            $sql = "INSERT INTO daily_reports (
                user_id,
                report_date,
                title,
                content,
                status
            ) VALUES (?, ?, ?, ?, ?)";

            $this->db->execute($sql, [
                $data['user_id'],
                $data['report_date'],
                $data['title'],
                $data['content'],
                $data['status'] ?? 'published'
            ]);

            $reportId = $this->db->lastInsertId();

            // タグを処理する
            if (!empty($data['tags'])) {
                $this->saveTags($reportId, $data['tags'], $data['user_id']);
            }

            // 権限設定
            if (!empty($data['permissions'])) {
                $this->savePermissions($reportId, $data['permissions']);
            }

            // スケジュールとの関連付け
            if (!empty($data['schedules'])) {
                $this->saveSchedules($reportId, $data['schedules']);
            }

            // タスクとの関連付け
            if (!empty($data['tasks'])) {
                $this->saveTasks($reportId, $data['tasks']);
            }

            $this->db->commit();
            return $reportId;
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("Error creating daily report: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 日報を更新する
     * 
     * @param int $id 日報ID
     * @param array $data 更新データ
     * @return bool 成功時true、失敗時false
     */
    public function update($id, $data)
    {
        try {
            $this->db->beginTransaction();

            // 更新フィールドと値の準備
            $fields = [];
            $values = [];

            // 更新可能なフィールド
            $updateableFields = [
                'report_date',
                'title',
                'content',
                'status'
            ];

            foreach ($updateableFields as $field) {
                if (array_key_exists($field, $data)) {
                    $fields[] = "$field = ?";
                    $values[] = $data[$field];
                }
            }

            if (empty($fields)) {
                return true; // 更新するものがない
            }

            $fields[] = "updated_at = CURRENT_TIMESTAMP";
            $values[] = $id; // WHEREの条件用

            // 日報情報更新
            $sql = "UPDATE daily_reports SET " . implode(", ", $fields) . " WHERE id = ?";
            $this->db->execute($sql, $values);

            // タグを更新
            if (isset($data['tags'])) {
                // 既存のタグ関連を削除
                $this->db->execute("DELETE FROM daily_report_tag_relations WHERE report_id = ?", [$id]);

                // 新しいタグを保存
                if (!empty($data['tags'])) {
                    $this->saveTags($id, $data['tags'], $data['user_id']);
                }
            }

            // 権限を更新
            if (isset($data['permissions'])) {
                // 既存の権限を削除
                $this->db->execute("DELETE FROM daily_report_permissions WHERE report_id = ?", [$id]);

                // 新しい権限を保存
                if (!empty($data['permissions'])) {
                    $this->savePermissions($id, $data['permissions']);
                }
            }

            // スケジュール関連を更新
            if (isset($data['schedules'])) {
                // 既存のスケジュール関連を削除
                $this->db->execute("DELETE FROM daily_report_schedules WHERE report_id = ?", [$id]);

                // 新しいスケジュール関連を保存
                if (!empty($data['schedules'])) {
                    $this->saveSchedules($id, $data['schedules']);
                }
            }

            // タスク関連を更新
            if (isset($data['tasks'])) {
                // 既存のタスク関連を削除
                $this->db->execute("DELETE FROM daily_report_tasks WHERE report_id = ?", [$id]);

                // 新しいタスク関連を保存
                if (!empty($data['tasks'])) {
                    $this->saveTasks($id, $data['tasks']);
                }
            }

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("Error updating daily report: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 日報を削除する
     * 
     * @param int $id 日報ID
     * @return bool 成功時true、失敗時false
     */
    public function delete($id)
    {
        try {
            return $this->db->execute("DELETE FROM daily_reports WHERE id = ?", [$id]);
        } catch (\Exception $e) {
            error_log("Error deleting daily report: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 日報を取得する
     * 
     * @param int $id 日報ID
     * @return array|null 日報データ
     */
    public function getById($id)
    {
        try {
            $sql = "SELECT r.*, u.display_name as creator_name
                    FROM daily_reports r
                    JOIN users u ON r.user_id = u.id
                    WHERE r.id = ?";

            $report = $this->db->fetch($sql, [$id]);

            if ($report) {
                // タグを取得
                $report['tags'] = $this->getReportTags($id);

                // 権限を取得
                $report['permissions'] = $this->getReportPermissions($id);

                // スケジュールを取得
                $report['schedules'] = $this->getReportSchedules($id);

                // タスクを取得
                $report['tasks'] = $this->getReportTasks($id);

                // いいね数
                $report['likes_count'] = $this->getLikesCount($id);

                // コメント数
                $report['comments_count'] = $this->getCommentsCount($id);
            }

            return $report;
        } catch (\Exception $e) {
            error_log("Error getting daily report: " . $e->getMessage());
            return null;
        }
    }

    /**
     * ユーザーの日報一覧を取得する
     * 
     * @param int $userId ユーザーID
     * @param array $filters フィルター条件
     * @param int $page ページ番号
     * @param int $limit 1ページの件数
     * @return array 日報リスト
     */
    public function getUserReports($userId, $filters = [], $page = 1, $limit = 20)
    {
        try {
            $offset = ($page - 1) * $limit;
            $params = [$userId];

            $sql = "SELECT r.*, u.display_name as creator_name,
                    (SELECT COUNT(*) FROM daily_report_likes WHERE report_id = r.id) as likes_count,
                    (SELECT COUNT(*) FROM daily_report_comments WHERE report_id = r.id) as comments_count
                    FROM daily_reports r
                    JOIN users u ON r.user_id = u.id
                    WHERE r.user_id = ?";

            // フィルター条件を適用
            if (!empty($filters['start_date'])) {
                $sql .= " AND r.report_date >= ?";
                $params[] = $filters['start_date'];
            }

            if (!empty($filters['end_date'])) {
                $sql .= " AND r.report_date <= ?";
                $params[] = $filters['end_date'];
            }

            if (!empty($filters['status'])) {
                $sql .= " AND r.status = ?";
                $params[] = $filters['status'];
            }

            if (!empty($filters['search'])) {
                $sql .= " AND (r.title LIKE ? OR r.content LIKE ?)";
                $searchTerm = "%" . $filters['search'] . "%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }

            // 並び順（デフォルトは日付の降順）
            $sql .= " ORDER BY r.report_date DESC, r.created_at DESC";

            // ページネーション
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;

            return $this->db->fetchAll($sql, $params);
        } catch (\Exception $e) {
            error_log("Error getting user reports: " . $e->getMessage());
            return [];
        }
    }

    /**
     * 読み取り可能な日報一覧を取得する
     * 
     * @param int $userId ユーザーID
     * @param array $filters フィルター条件
     * @param int $page ページ番号
     * @param int $limit 1ページの件数
     * @return array 日報リスト
     */
    public function getReadableReports($userId, $filters = [], $page = 1, $limit = 20)
    {
        try {
            $offset = ($page - 1) * $limit;
            $params = [$userId];

            // ユーザーの所属組織IDを取得
            $userModel = new User();
            $organizationIds = $userModel->getUserOrganizationIds($userId);

            $placeholders = '';
            if (!empty($organizationIds)) {
                $placeholders = implode(',', array_fill(0, count($organizationIds), '?'));
                $params = array_merge($params, $organizationIds);
            } else {
                $placeholders = '0'; // 組織がない場合は0を使用（常にfalseとなる）
            }

            $sql = "SELECT DISTINCT r.*, u.display_name as creator_name,
                    (SELECT COUNT(*) FROM daily_report_likes WHERE report_id = r.id) as likes_count,
                    (SELECT COUNT(*) FROM daily_report_comments WHERE report_id = r.id) as comments_count
                    FROM daily_reports r
                    JOIN users u ON r.user_id = u.id
                    LEFT JOIN daily_report_permissions p ON r.id = p.report_id
                    WHERE r.status = 'published' AND (
                        r.user_id = ? OR
                        (p.target_type = 'user' AND p.target_id = ?) OR
                        (p.target_type = 'organization' AND p.target_id IN ({$placeholders}))
                    )";

            // ユーザーIDをパラメータに追加（p.target_id = ? の部分用）
            $params[] = $userId;

            // フィルター条件を適用
            if (!empty($filters['start_date'])) {
                $sql .= " AND r.report_date >= ?";
                $params[] = $filters['start_date'];
            }

            if (!empty($filters['end_date'])) {
                $sql .= " AND r.report_date <= ?";
                $params[] = $filters['end_date'];
            }

            if (!empty($filters['user_id'])) {
                $sql .= " AND r.user_id = ?";
                $params[] = $filters['user_id'];
            }

            if (!empty($filters['search'])) {
                $sql .= " AND (r.title LIKE ? OR r.content LIKE ?)";
                $searchTerm = "%" . $filters['search'] . "%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }

            // タグによるフィルタリング
            if (!empty($filters['tag_id'])) {
                $sql .= " AND EXISTS (
                    SELECT 1 FROM daily_report_tag_relations tr
                    WHERE tr.report_id = r.id AND tr.tag_id = ?
                )";
                $params[] = $filters['tag_id'];
            }

            // 並び順（デフォルトは日付の降順）
            $sql .= " ORDER BY r.report_date DESC, r.created_at DESC";

            // ページネーション
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;

            return $this->db->fetchAll($sql, $params);
        } catch (\Exception $e) {
            error_log("Error getting readable reports: " . $e->getMessage());
            return [];
        }
    }

    /**
     * 日報の総数を取得
     * 
     * @param int $userId ユーザーID（指定された場合はそのユーザーの日報数）
     * @param array $filters フィルター条件
     * @return int 日報数
     */
    public function getCount($userId = null, $filters = [])
    {
        try {
            $params = [];
            $sql = "SELECT COUNT(*) as count FROM daily_reports WHERE 1=1";

            if ($userId !== null) {
                $sql .= " AND user_id = ?";
                $params[] = $userId;
            }

            // フィルター条件を適用
            if (!empty($filters['start_date'])) {
                $sql .= " AND report_date >= ?";
                $params[] = $filters['start_date'];
            }

            if (!empty($filters['end_date'])) {
                $sql .= " AND report_date <= ?";
                $params[] = $filters['end_date'];
            }

            if (!empty($filters['status'])) {
                $sql .= " AND status = ?";
                $params[] = $filters['status'];
            }

            if (!empty($filters['search'])) {
                $sql .= " AND (title LIKE ? OR content LIKE ?)";
                $searchTerm = "%" . $filters['search'] . "%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }

            $result = $this->db->fetch($sql, $params);
            return $result ? (int)$result['count'] : 0;
        } catch (\Exception $e) {
            error_log("Error getting daily report count: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * コメントを追加する
     * 
     * @param int $reportId 日報ID
     * @param int $userId ユーザーID
     * @param string $comment コメント内容
     * @return int|bool 成功時はコメントID、失敗時はfalse
     */
    public function addComment($reportId, $userId, $comment)
    {
        try {
            $sql = "INSERT INTO daily_report_comments (report_id, user_id, comment) 
                   VALUES (?, ?, ?)";

            $this->db->execute($sql, [$reportId, $userId, $comment]);
            return $this->db->lastInsertId();
        } catch (\Exception $e) {
            error_log("Error adding comment: " . $e->getMessage());
            return false;
        }
    }

    /**
     * コメントを取得する
     * 
     * @param int $reportId 日報ID
     * @return array コメントリスト
     */
    public function getComments($reportId)
    {
        try {
            $sql = "SELECT c.*, u.display_name
                   FROM daily_report_comments c
                   JOIN users u ON c.user_id = u.id
                   WHERE c.report_id = ?
                   ORDER BY c.created_at";

            return $this->db->fetchAll($sql, [$reportId]);
        } catch (\Exception $e) {
            error_log("Error getting comments: " . $e->getMessage());
            return [];
        }
    }

    /**
     * コメント数を取得する
     * 
     * @param int $reportId 日報ID
     * @return int コメント数
     */
    public function getCommentsCount($reportId)
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM daily_report_comments WHERE report_id = ?";
            $result = $this->db->fetch($sql, [$reportId]);
            return $result ? (int)$result['count'] : 0;
        } catch (\Exception $e) {
            error_log("Error getting comments count: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * いいねを追加または削除する
     * 
     * @param int $reportId 日報ID
     * @param int $userId ユーザーID
     * @return bool 成功時true、失敗時false
     */
    public function toggleLike($reportId, $userId)
    {
        try {
            // 既にいいねしているか確認
            $sql = "SELECT COUNT(*) as count FROM daily_report_likes 
                   WHERE report_id = ? AND user_id = ?";

            $result = $this->db->fetch($sql, [$reportId, $userId]);
            $hasLiked = $result && $result['count'] > 0;

            if ($hasLiked) {
                // いいねを削除
                $sql = "DELETE FROM daily_report_likes 
                       WHERE report_id = ? AND user_id = ?";
                return $this->db->execute($sql, [$reportId, $userId]);
            } else {
                // いいねを追加
                $sql = "INSERT INTO daily_report_likes (report_id, user_id) 
                       VALUES (?, ?)";
                return $this->db->execute($sql, [$reportId, $userId]);
            }
        } catch (\Exception $e) {
            error_log("Error toggling like: " . $e->getMessage());
            return false;
        }
    }

    /**
     * いいね数を取得する
     * 
     * @param int $reportId 日報ID
     * @return int いいね数
     */
    public function getLikesCount($reportId)
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM daily_report_likes WHERE report_id = ?";
            $result = $this->db->fetch($sql, [$reportId]);
            return $result ? (int)$result['count'] : 0;
        } catch (\Exception $e) {
            error_log("Error getting likes count: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * ユーザーがいいねしているか確認する
     * 
     * @param int $reportId 日報ID
     * @param int $userId ユーザーID
     * @return bool いいねしている場合true
     */
    public function hasLiked($reportId, $userId)
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM daily_report_likes 
                   WHERE report_id = ? AND user_id = ?";

            $result = $this->db->fetch($sql, [$reportId, $userId]);
            return $result && $result['count'] > 0;
        } catch (\Exception $e) {
            error_log("Error checking like status: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 既読状態を更新する
     * 
     * @param int $reportId 日報ID
     * @param int $userId ユーザーID
     * @return bool 成功時true、失敗時false
     */
    public function markAsRead($reportId, $userId)
    {
        try {
            $sql = "INSERT INTO daily_report_reads (report_id, user_id) 
                   VALUES (?, ?) 
                   ON DUPLICATE KEY UPDATE read_at = CURRENT_TIMESTAMP";

            return $this->db->execute($sql, [$reportId, $userId]);
        } catch (\Exception $e) {
            error_log("Error marking report as read: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 既読者リストを取得する
     * 
     * @param int $reportId 日報ID
     * @return array 既読者リスト
     */
    public function getReadUsers($reportId)
    {
        try {
            $sql = "SELECT r.*, u.display_name
                   FROM daily_report_reads r
                   JOIN users u ON r.user_id = u.id
                   WHERE r.report_id = ?
                   ORDER BY r.read_at DESC";

            return $this->db->fetchAll($sql, [$reportId]);
        } catch (\Exception $e) {
            error_log("Error getting read users: " . $e->getMessage());
            return [];
        }
    }

    /**
     * 日報のテンプレートを取得する
     * 
     * @param int $userId ユーザーID
     * @return array テンプレートリスト
     */
    public function getTemplates($userId)
    {
        try {
            $sql = "SELECT * FROM daily_report_templates 
                   WHERE user_id = ? OR is_public = 1
                   ORDER BY title";

            return $this->db->fetchAll($sql, [$userId]);
        } catch (\Exception $e) {
            error_log("Error getting templates: " . $e->getMessage());
            return [];
        }
    }

    /**
     * 日報のテンプレートを作成する
     * 
     * @param array $data テンプレートデータ
     * @return int|bool 成功時はテンプレートID、失敗時はfalse
     */
    public function createTemplate($data)
    {
        try {
            $sql = "INSERT INTO daily_report_templates (
                   title, content, user_id, is_public
                   ) VALUES (?, ?, ?, ?)";

            $this->db->execute($sql, [
                $data['title'],
                $data['content'],
                $data['user_id'],
                $data['is_public'] ?? 0
            ]);

            return $this->db->lastInsertId();
        } catch (\Exception $e) {
            error_log("Error creating template: " . $e->getMessage());
            return false;
        }
    }

    /**
     * テンプレートを取得する
     * 
     * @param int $id テンプレートID
     * @return array|null テンプレートデータ
     */
    public function getTemplateById($id)
    {
        try {
            $sql = "SELECT * FROM daily_report_templates WHERE id = ?";
            return $this->db->fetch($sql, [$id]);
        } catch (\Exception $e) {
            error_log("Error getting template: " . $e->getMessage());
            return null;
        }
    }

    /**
     * タグを保存する
     * 
     * @param int $reportId 日報ID
     * @param array $tags タグ配列
     * @param int $userId ユーザーID
     * @return bool 成功時true、失敗時false
     */
    private function saveTags($reportId, $tags, $userId)
    {
        try {
            foreach ($tags as $tagName) {
                // タグが存在するか確認
                $sql = "SELECT id FROM daily_report_tags WHERE name = ? AND user_id = ?";
                $tag = $this->db->fetch($sql, [$tagName, $userId]);

                // タグが存在しない場合は作成
                if (!$tag) {
                    $this->db->execute(
                        "INSERT INTO daily_report_tags (name, user_id) VALUES (?, ?)",
                        [$tagName, $userId]
                    );
                    $tagId = $this->db->lastInsertId();
                } else {
                    $tagId = $tag['id'];
                }

                // 日報とタグを関連付け
                $this->db->execute(
                    "INSERT IGNORE INTO daily_report_tag_relations (report_id, tag_id) VALUES (?, ?)",
                    [$reportId, $tagId]
                );
            }

            return true;
        } catch (\Exception $e) {
            error_log("Error saving tags: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 権限を保存する
     * 
     * @param int $reportId 日報ID
     * @param array $permissions 権限配列
     * @return bool 成功時true、失敗時false
     */
    private function savePermissions($reportId, $permissions)
    {
        try {
            foreach ($permissions as $permission) {
                $this->db->execute(
                    "INSERT INTO daily_report_permissions (report_id, target_type, target_id) 
                    VALUES (?, ?, ?)",
                    [$reportId, $permission['type'], $permission['id']]
                );
            }

            return true;
        } catch (\Exception $e) {
            error_log("Error saving permissions: " . $e->getMessage());
            return false;
        }
    }

    /**
     * スケジュールとの関連付けを保存する
     * 
     * @param int $reportId 日報ID
     * @param array $schedules スケジュールID配列
     * @return bool 成功時true、失敗時false
     */
    private function saveSchedules($reportId, $schedules)
    {
        try {
            foreach ($schedules as $scheduleId) {
                $this->db->execute(
                    "INSERT IGNORE INTO daily_report_schedules (report_id, schedule_id) VALUES (?, ?)",
                    [$reportId, $scheduleId]
                );
            }

            return true;
        } catch (\Exception $e) {
            error_log("Error saving schedules: " . $e->getMessage());
            return false;
        }
    }

    /**
     * タスクとの関連付けを保存する
     * 
     * @param int $reportId 日報ID
     * @param array $tasks タスクID配列
     * @return bool 成功時true、失敗時false
     */
    private function saveTasks($reportId, $tasks)
    {
        try {
            foreach ($tasks as $taskId) {
                $this->db->execute(
                    "INSERT IGNORE INTO daily_report_tasks (report_id, task_id) VALUES (?, ?)",
                    [$reportId, $taskId]
                );
            }

            return true;
        } catch (\Exception $e) {
            error_log("Error saving tasks: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 日報のタグを取得する
     * 
     * @param int $reportId 日報ID
     * @return array タグリスト
     */
    private function getReportTags($reportId)
    {
        try {
            $sql = "SELECT t.* 
                   FROM daily_report_tags t
                   JOIN daily_report_tag_relations tr ON t.id = tr.tag_id
                   WHERE tr.report_id = ?";

            return $this->db->fetchAll($sql, [$reportId]);
        } catch (\Exception $e) {
            error_log("Error getting report tags: " . $e->getMessage());
            return [];
        }
    }

    /**
     * 日報の権限設定を取得する
     * 
     * @param int $reportId 日報ID
     * @return array 権限リスト
     */
    private function getReportPermissions($reportId)
    {
        try {
            $sql = "SELECT * FROM daily_report_permissions WHERE report_id = ?";
            return $this->db->fetchAll($sql, [$reportId]);
        } catch (\Exception $e) {
            error_log("Error getting report permissions: " . $e->getMessage());
            return [];
        }
    }

    /**
     * 日報の関連スケジュールを取得する
     * 
     * @param int $reportId 日報ID
     * @return array スケジュールリスト
     */
    private function getReportSchedules($reportId)
    {
        try {
            $sql = "SELECT s.*
                   FROM schedules s
                   JOIN daily_report_schedules rs ON s.id = rs.schedule_id
                   WHERE rs.report_id = ?";

            return $this->db->fetchAll($sql, [$reportId]);
        } catch (\Exception $e) {
            error_log("Error getting report schedules: " . $e->getMessage());
            return [];
        }
    }

    /**
     * 日報の関連タスクを取得する
     * 
     * @param int $reportId 日報ID
     * @return array タスクリスト
     */
    private function getReportTasks($reportId)
    {
        try {
            $sql = "SELECT t.*
                   FROM task_cards t
                   JOIN daily_report_tasks rt ON t.id = rt.task_id
                   WHERE rt.report_id = ?";

            return $this->db->fetchAll($sql, [$reportId]);
        } catch (\Exception $e) {
            error_log("Error getting report tasks: " . $e->getMessage());
            return [];
        }
    }

    /**
     * 指定された日付の日報があるか確認する
     * 
     * @param int $userId ユーザーID
     * @param string $date 日付（Y-m-d形式）
     * @return bool 日報がある場合true
     */
    public function hasReportForDate($userId, $date)
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM daily_reports 
                   WHERE user_id = ? AND report_date = ?";

            $result = $this->db->fetch($sql, [$userId, $date]);
            return $result && $result['count'] > 0;
        } catch (\Exception $e) {
            error_log("Error checking report for date: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 指定された日付の日報を取得する
     * 
     * @param int $userId ユーザーID
     * @param string $date 日付（Y-m-d形式）
     * @return array|null 日報データ
     */
    public function getByDate($userId, $date)
    {
        try {
            $sql = "SELECT * FROM daily_reports 
                   WHERE user_id = ? AND report_date = ?
                   ORDER BY created_at DESC
                   LIMIT 1";

            $report = $this->db->fetch($sql, [$userId, $date]);

            if ($report) {
                return $this->getById($report['id']);
            }

            return null;
        } catch (\Exception $e) {
            error_log("Error getting report by date: " . $e->getMessage());
            return null;
        }
    }

    /**
     * ユーザーのタグ一覧を取得する
     * 
     * @param int $userId ユーザーID
     * @return array タグリスト
     */
    public function getUserTags($userId)
    {
        try {
            $sql = "SELECT t.*, COUNT(tr.report_id) as reports_count
                   FROM daily_report_tags t
                   LEFT JOIN daily_report_tag_relations tr ON t.id = tr.tag_id
                   WHERE t.user_id = ?
                   GROUP BY t.id
                   ORDER BY reports_count DESC, t.name";

            return $this->db->fetchAll($sql, [$userId]);
        } catch (\Exception $e) {
            error_log("Error getting user tags: " . $e->getMessage());
            return [];
        }
    }

    /**
     * 公開タグ一覧を取得する
     * 
     * @return array タグリスト
     */
    public function getPublicTags()
    {
        try {
            $sql = "SELECT t.*, COUNT(tr.report_id) as reports_count, u.display_name
                   FROM daily_report_tags t
                   LEFT JOIN daily_report_tag_relations tr ON t.id = tr.tag_id
                   JOIN users u ON t.user_id = u.id
                   GROUP BY t.id
                   HAVING reports_count > 0
                   ORDER BY reports_count DESC, t.name
                   LIMIT 50";

            return $this->db->fetchAll($sql);
        } catch (\Exception $e) {
            error_log("Error getting public tags: " . $e->getMessage());
            return [];
        }
    }

    /**
     * タグ別の日報数を取得する
     * 
     * @param int $tagId タグID
     * @return int 日報数
     */
    public function getReportCountByTag($tagId)
    {
        try {
            $sql = "SELECT COUNT(DISTINCT report_id) as count
                   FROM daily_report_tag_relations
                   WHERE tag_id = ?";

            $result = $this->db->fetch($sql, [$tagId]);
            return $result ? (int)$result['count'] : 0;
        } catch (\Exception $e) {
            error_log("Error getting report count by tag: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * ユーザーの日報統計を取得する
     * 
     * @param int $userId ユーザーID
     * @param string $startDate 開始日（Y-m-d形式）
     * @param string $endDate 終了日（Y-m-d形式）
     * @return array 統計データ
     */
    public function getUserStats($userId, $startDate = null, $endDate = null)
    {
        try {
            $params = [$userId];

            // 日付範囲の条件
            $dateCondition = "";
            if ($startDate) {
                $dateCondition .= " AND r.report_date >= ?";
                $params[] = $startDate;
            }

            if ($endDate) {
                $dateCondition .= " AND r.report_date <= ?";
                $params[] = $endDate;
            }

            // 総日報数
            $sql = "SELECT COUNT(*) as total FROM daily_reports r 
                   WHERE r.user_id = ? {$dateCondition}";

            $totalResult = $this->db->fetch($sql, $params);
            $total = $totalResult ? (int)$totalResult['total'] : 0;

            // 月別日報数
            $sql = "SELECT 
                    DATE_FORMAT(r.report_date, '%Y-%m') as month,
                    COUNT(*) as count
                   FROM daily_reports r
                   WHERE r.user_id = ? {$dateCondition}
                   GROUP BY month
                   ORDER BY month";

            $monthlyStats = $this->db->fetchAll($sql, $params);

            // 総いいね数
            $sql = "SELECT COUNT(*) as likes_count
                   FROM daily_report_likes l
                   JOIN daily_reports r ON l.report_id = r.id
                   WHERE r.user_id = ? {$dateCondition}";

            $likesResult = $this->db->fetch($sql, $params);
            $totalLikes = $likesResult ? (int)$likesResult['likes_count'] : 0;

            // 総コメント数
            $sql = "SELECT COUNT(*) as comments_count
                   FROM daily_report_comments c
                   JOIN daily_reports r ON c.report_id = r.id
                   WHERE r.user_id = ? {$dateCondition}";

            $commentsResult = $this->db->fetch($sql, $params);
            $totalComments = $commentsResult ? (int)$commentsResult['comments_count'] : 0;

            return [
                'total_reports' => $total,
                'monthly_stats' => $monthlyStats,
                'total_likes' => $totalLikes,
                'total_comments' => $totalComments
            ];
        } catch (\Exception $e) {
            error_log("Error getting user stats: " . $e->getMessage());
            return [
                'total_reports' => 0,
                'monthly_stats' => [],
                'total_likes' => 0,
                'total_comments' => 0
            ];
        }
    }
}

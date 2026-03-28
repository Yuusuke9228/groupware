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

            $structuredData = $this->normalizeStructuredData($data);

            // 日報を作成
            $sql = "INSERT INTO daily_reports (
                user_id,
                report_date,
                title,
                content,
                content_format,
                status,
                summary_text,
                issues_text,
                tomorrow_plan_text,
                reflection_text,
                work_minutes,
                detail_json,
                template_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $this->db->execute($sql, [
                $data['user_id'],
                $data['report_date'],
                $data['title'],
                $data['content'],
                $data['content_format'] ?? 'text',
                $data['status'] ?? 'published',
                $structuredData['summary_text'],
                $structuredData['issues_text'],
                $structuredData['tomorrow_plan_text'],
                $structuredData['reflection_text'],
                $structuredData['work_minutes'],
                $structuredData['detail_json'],
                $structuredData['template_id']
            ]);

            $reportId = $this->db->lastInsertId();

            // タグを処理する
            if (!empty($data['tags']) && !$this->saveTags($reportId, $data['tags'], $data['user_id'])) {
                throw new \Exception('タグの保存に失敗しました');
            }

            // 権限設定
            if (!empty($data['permissions']) && !$this->savePermissions($reportId, $data['permissions'])) {
                throw new \Exception('公開範囲の保存に失敗しました');
            }

            // スケジュールとの関連付け
            if (!empty($data['schedules']) && !$this->saveSchedules($reportId, $data['schedules'])) {
                throw new \Exception('関連スケジュールの保存に失敗しました');
            }

            // タスクとの関連付け
            if (!empty($data['tasks']) && !$this->saveTasks($reportId, $data['tasks'])) {
                throw new \Exception('関連タスクの保存に失敗しました');
            }

            if (!$this->saveActivityLogs($reportId, $structuredData['activities'])) {
                throw new \Exception('活動ログの保存に失敗しました');
            }

            if (!$this->saveAnalysisEntries($reportId, $structuredData['analysis_entries'])) {
                throw new \Exception('分析明細の保存に失敗しました');
            }

            if (!empty($data['attachments']) && !$this->saveAttachments($reportId, (int)$data['user_id'], $data['attachments'])) {
                throw new \Exception('添付ファイルの保存に失敗しました');
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

            $structuredData = $this->normalizeStructuredData($data);

            // 更新フィールドと値の準備
            $fields = [];
            $values = [];

            // 更新可能なフィールド
            $updateableFields = [
                'report_date',
                'title',
                'content',
                'content_format',
                'status',
                'summary_text',
                'issues_text',
                'tomorrow_plan_text',
                'reflection_text',
                'work_minutes',
                'detail_json',
                'template_id'
            ];

            $mergedData = array_merge($data, [
                'summary_text' => $structuredData['summary_text'],
                'issues_text' => $structuredData['issues_text'],
                'tomorrow_plan_text' => $structuredData['tomorrow_plan_text'],
                'reflection_text' => $structuredData['reflection_text'],
                'work_minutes' => $structuredData['work_minutes'],
                'detail_json' => $structuredData['detail_json'],
                'template_id' => $structuredData['template_id']
            ]);

            foreach ($updateableFields as $field) {
                if (array_key_exists($field, $mergedData)) {
                    $fields[] = "$field = ?";
                    $values[] = $mergedData[$field];
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
                    if (!$this->saveTags($id, $data['tags'], $data['user_id'])) {
                        throw new \Exception('タグの保存に失敗しました');
                    }
                }
            }

            // 権限を更新
            if (isset($data['permissions'])) {
                // 既存の権限を削除
                $this->db->execute("DELETE FROM daily_report_permissions WHERE report_id = ?", [$id]);

                // 新しい権限を保存
                if (!empty($data['permissions'])) {
                    if (!$this->savePermissions($id, $data['permissions'])) {
                        throw new \Exception('公開範囲の保存に失敗しました');
                    }
                }
            }

            // スケジュール関連を更新
            if (isset($data['schedules'])) {
                // 既存のスケジュール関連を削除
                $this->db->execute("DELETE FROM daily_report_schedules WHERE report_id = ?", [$id]);

                // 新しいスケジュール関連を保存
                if (!empty($data['schedules'])) {
                    if (!$this->saveSchedules($id, $data['schedules'])) {
                        throw new \Exception('関連スケジュールの保存に失敗しました');
                    }
                }
            }

            // タスク関連を更新
            if (isset($data['tasks'])) {
                // 既存のタスク関連を削除
                $this->db->execute("DELETE FROM daily_report_tasks WHERE report_id = ?", [$id]);

                // 新しいタスク関連を保存
                if (!empty($data['tasks'])) {
                    if (!$this->saveTasks($id, $data['tasks'])) {
                        throw new \Exception('関連タスクの保存に失敗しました');
                    }
                }
            }

            if (array_key_exists('activities', $data) || isset($data['detail_items']) || isset($data['activity_logs'])) {
                if (!$this->saveActivityLogs($id, $structuredData['activities'])) {
                    throw new \Exception('活動ログの保存に失敗しました');
                }
            }

            if (array_key_exists('analysis_entries', $data)) {
                if (!$this->saveAnalysisEntries($id, $structuredData['analysis_entries'])) {
                    throw new \Exception('分析明細の保存に失敗しました');
                }
            }

            if (!empty($data['attachments'])) {
                if (!$this->saveAttachments($id, (int)($data['user_id'] ?? 0), $data['attachments'])) {
                    throw new \Exception('添付ファイルの保存に失敗しました');
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
            $attachments = $this->getAttachments($id);
            foreach ($attachments as $attachment) {
                $this->deleteAttachmentFileByPath((string)($attachment['file_path'] ?? ''));
            }
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
                $report['detail_items'] = $this->decodeDetailJson($report['detail_json'] ?? null);
                $report['activity_logs'] = $this->getActivityLogs($id);
                $report['analysis_entries'] = $this->getAnalysisEntries($id);
                $report['attachments'] = $this->getAttachments($id);
                $report['work_minutes'] = (int)($report['work_minutes'] ?? 0);

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
                $sql .= " AND (r.title LIKE ? OR r.content LIKE ? OR r.summary_text LIKE ? OR r.issues_text LIKE ? OR r.tomorrow_plan_text LIKE ? OR r.reflection_text LIKE ?)";
                $searchTerm = "%" . $filters['search'] . "%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }

            if (!empty($filters['project_id']) || !empty($filters['industry_id']) || !empty($filters['product_id']) || !empty($filters['process_id'])) {
                $sql .= " AND EXISTS (
                    SELECT 1
                    FROM daily_report_analysis_entries ae
                    WHERE ae.report_id = r.id";
                if (!empty($filters['project_id'])) {
                    $sql .= " AND ae.project_id = ?";
                    $params[] = (int)$filters['project_id'];
                }
                if (!empty($filters['industry_id'])) {
                    $sql .= " AND ae.industry_id = ?";
                    $params[] = (int)$filters['industry_id'];
                }
                if (!empty($filters['product_id'])) {
                    $sql .= " AND ae.product_id = ?";
                    $params[] = (int)$filters['product_id'];
                }
                if (!empty($filters['process_id'])) {
                    $sql .= " AND ae.process_id = ?";
                    $params[] = (int)$filters['process_id'];
                }
                $sql .= ")";
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
                $sql .= " AND (r.title LIKE ? OR r.content LIKE ? OR r.summary_text LIKE ? OR r.issues_text LIKE ? OR r.tomorrow_plan_text LIKE ? OR r.reflection_text LIKE ?)";
                $searchTerm = "%" . $filters['search'] . "%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
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

            if (!empty($filters['project_id']) || !empty($filters['industry_id']) || !empty($filters['product_id']) || !empty($filters['process_id'])) {
                $sql .= " AND EXISTS (
                    SELECT 1
                    FROM daily_report_analysis_entries ae
                    WHERE ae.report_id = r.id";
                if (!empty($filters['project_id'])) {
                    $sql .= " AND ae.project_id = ?";
                    $params[] = (int)$filters['project_id'];
                }
                if (!empty($filters['industry_id'])) {
                    $sql .= " AND ae.industry_id = ?";
                    $params[] = (int)$filters['industry_id'];
                }
                if (!empty($filters['product_id'])) {
                    $sql .= " AND ae.product_id = ?";
                    $params[] = (int)$filters['product_id'];
                }
                if (!empty($filters['process_id'])) {
                    $sql .= " AND ae.process_id = ?";
                    $params[] = (int)$filters['process_id'];
                }
                $sql .= ")";
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
                $sql .= " AND (title LIKE ? OR content LIKE ? OR summary_text LIKE ? OR issues_text LIKE ? OR tomorrow_plan_text LIKE ? OR reflection_text LIKE ?)";
                $searchTerm = "%" . $filters['search'] . "%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }

            if (!empty($filters['project_id']) || !empty($filters['industry_id']) || !empty($filters['product_id']) || !empty($filters['process_id'])) {
                $sql .= " AND EXISTS (
                    SELECT 1
                    FROM daily_report_analysis_entries ae
                    WHERE ae.report_id = daily_reports.id";
                if (!empty($filters['project_id'])) {
                    $sql .= " AND ae.project_id = ?";
                    $params[] = (int)$filters['project_id'];
                }
                if (!empty($filters['industry_id'])) {
                    $sql .= " AND ae.industry_id = ?";
                    $params[] = (int)$filters['industry_id'];
                }
                if (!empty($filters['product_id'])) {
                    $sql .= " AND ae.product_id = ?";
                    $params[] = (int)$filters['product_id'];
                }
                if (!empty($filters['process_id'])) {
                    $sql .= " AND ae.process_id = ?";
                    $params[] = (int)$filters['process_id'];
                }
                $sql .= ")";
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
            $orgIds = (new User())->getUserOrganizationIds($userId);
            $params = [$userId];
            $orgClause = '0';
            if (!empty($orgIds)) {
                $orgClause = implode(',', array_fill(0, count($orgIds), '?'));
                $params = array_merge($params, $orgIds);
            }

            $sql = "SELECT DISTINCT t.*
                    FROM daily_report_templates t
                    LEFT JOIN daily_report_template_organizations dto ON dto.template_id = t.id
                    WHERE t.user_id = ? OR t.is_public = 1 OR dto.organization_id IN ({$orgClause})
                    ORDER BY t.title";

            return $this->db->fetchAll($sql, $params);
        } catch (\Exception $e) {
            error_log("Error getting templates: " . $e->getMessage());
            return [];
        }
    }

    /**
     * テンプレートを作成する
     * 
     * @param array $data テンプレートデータ
     * @return int|bool 成功時はテンプレートID、失敗時はfalse
     */
    public function createTemplate($data)
    {
        $startedTransaction = false;
        try {
            $this->db->beginTransaction();
            $startedTransaction = true;

            $normalizedSections = $this->normalizeTemplateSections($data['sections'] ?? []);
            $sectionSchemaJson = !empty($normalizedSections) ? json_encode($normalizedSections, JSON_UNESCAPED_UNICODE) : null;

            $sql = "INSERT INTO daily_report_templates (
                title, content, content_format, user_id, is_public, description, section_schema_json
                ) VALUES (?, ?, ?, ?, ?, ?, ?)";

            $params = [
                $data['title'],
                $data['content'],
                $data['content_format'] ?? 'text',
                $data['user_id'],
                $data['is_public'],
                $data['description'] ?? null,
                $sectionSchemaJson
            ];

            $result = $this->db->execute($sql, $params);

            if (!$result) {
                $this->db->rollBack();
                return false;
            }

            $lastId = (int)$this->db->lastInsertId();
            if ($lastId <= 0) {
                $this->db->rollBack();
                return false;
            }

            if (!$this->saveTemplateSections($lastId, $normalizedSections)) {
                $this->db->rollBack();
                return false;
            }

            $this->db->commit();

            return $lastId;
        } catch (\Exception $e) {
            if ($startedTransaction) {
                try {
                    $this->db->rollBack();
                } catch (\Throwable $ignore) {
                }
            }
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
            $template = $this->db->fetch($sql, [$id]);
            if (!$template) {
                return null;
            }
            $template['sections'] = $this->getTemplateSections($id);
            if (empty($template['sections'])) {
                $template['sections'] = $this->decodeDetailJson($template['section_schema_json'] ?? null);
            }
            return $template;
        } catch (\Exception $e) {
            error_log("Error getting template: " . $e->getMessage());
            return null;
        }
    }

    public function isTemplateAvailableForUser($templateId, $userId)
    {
        $template = $this->getTemplateById($templateId);
        if (!$template) {
            return false;
        }

        if ((int)$template['user_id'] === (int)$userId || (int)$template['is_public'] === 1) {
            return true;
        }

        $userOrgIds = (new User())->getUserOrganizationIds($userId);
        $targetOrgIds = $this->getTemplateOrganizationIds($templateId);
        return count(array_intersect($userOrgIds, $targetOrgIds)) > 0;
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
    public function getReportPermissions($reportId)
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

    /**
     * テンプレートを更新する
     * 
     * @param int $id テンプレートID
     * @param array $data テンプレートデータ
     * @return bool 成功時true、失敗時false
     */
    public function updateTemplate($id, $data)
    {
        $startedTransaction = false;
        try {
            $this->db->beginTransaction();
            $startedTransaction = true;

            $normalizedSections = $this->normalizeTemplateSections($data['sections'] ?? []);
            $sectionSchemaJson = !empty($normalizedSections) ? json_encode($normalizedSections, JSON_UNESCAPED_UNICODE) : null;
            $sql = "UPDATE daily_report_templates SET 
                title = ?, 
                content = ?, 
                content_format = ?,
                is_public = ?,
                description = ?,
                section_schema_json = ?,
                updated_at = CURRENT_TIMESTAMP
                WHERE id = ?";

            $result = $this->db->execute($sql, [
                $data['title'],
                $data['content'],
                $data['content_format'] ?? 'text',
                $data['is_public'] ? 1 : 0,
                $data['description'] ?? null,
                $sectionSchemaJson,
                $id
            ]);
            if (!$result) {
                $this->db->rollBack();
                return false;
            }

            if (!$this->saveTemplateSections($id, $normalizedSections)) {
                $this->db->rollBack();
                return false;
            }

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            if ($startedTransaction) {
                try {
                    $this->db->rollBack();
                } catch (\Throwable $ignore) {
                }
            }
            error_log("Error updating template: " . $e->getMessage());
            return false;
        }
    }

    /**
     * テンプレートを削除する
     * 
     * @param int $id テンプレートID
     * @return bool 成功時true、失敗時false
     */
    public function deleteTemplate($id)
    {
        try {
            $sql = "DELETE FROM daily_report_templates WHERE id = ?";
            return $this->db->execute($sql, [$id]);
        } catch (\Exception $e) {
            error_log("Error deleting template: " . $e->getMessage());
            return false;
        }
    }

    public function getTemplateOrganizationIds($templateId)
    {
        $rows = $this->db->fetchAll(
            "SELECT organization_id FROM daily_report_template_organizations WHERE template_id = ?",
            [$templateId]
        );
        return array_map('intval', array_column($rows, 'organization_id'));
    }

    public function updateTemplateOrganizations($templateId, $organizationIds = [])
    {
        $this->db->execute("DELETE FROM daily_report_template_organizations WHERE template_id = ?", [$templateId]);

        foreach ($organizationIds as $organizationId) {
            $id = (int)$organizationId;
            if ($id <= 0) {
                continue;
            }
            $this->db->execute(
                "INSERT INTO daily_report_template_organizations (template_id, organization_id) VALUES (?, ?)",
                [$templateId, $id]
            );
        }

        return true;
    }

    private function normalizeStructuredData($data)
    {
        $detailItems = [];
        if (isset($data['detail_items']) && is_array($data['detail_items'])) {
            $detailItems = $data['detail_items'];
        } elseif (isset($data['detail_json']) && is_string($data['detail_json'])) {
            $decoded = json_decode($data['detail_json'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $detailItems = $decoded;
            }
        }

        $activities = $data['activities'] ?? ($data['activity_logs'] ?? []);
        if (!is_array($activities)) {
            $activities = [];
        }
        $analysisEntries = $data['analysis_entries'] ?? [];
        if (!is_array($analysisEntries)) {
            $analysisEntries = [];
        }

        return [
            'summary_text' => $data['summary_text'] ?? null,
            'issues_text' => $data['issues_text'] ?? null,
            'tomorrow_plan_text' => $data['tomorrow_plan_text'] ?? null,
            'reflection_text' => $data['reflection_text'] ?? null,
            'work_minutes' => max(0, (int)($data['work_minutes'] ?? 0)),
            'template_id' => !empty($data['template_id']) ? (int)$data['template_id'] : null,
            'detail_json' => !empty($detailItems) ? json_encode(array_values($detailItems), JSON_UNESCAPED_UNICODE) : null,
            'activities' => $activities,
            'analysis_entries' => $analysisEntries
        ];
    }

    private function decodeDetailJson($json)
    {
        if (!$json || !is_string($json)) {
            return [];
        }
        $decoded = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            return [];
        }
        return $decoded;
    }

    private function saveActivityLogs($reportId, $activities)
    {
        try {
            $this->db->execute("DELETE FROM daily_report_activity_logs WHERE report_id = ?", [$reportId]);
            if (empty($activities)) {
                return true;
            }

            $sql = "INSERT INTO daily_report_activity_logs (
                        report_id, start_time, end_time, activity_type, subject, result, memo, sort_order
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

            $sortOrder = 1;
            foreach ($activities as $activity) {
                if (!is_array($activity)) {
                    continue;
                }
                $subject = trim((string)($activity['subject'] ?? ''));
                $activityType = trim((string)($activity['activity_type'] ?? ''));
                $memo = trim((string)($activity['memo'] ?? ''));
                if ($subject === '' && $activityType === '' && $memo === '') {
                    continue;
                }

                $this->db->execute($sql, [
                    $reportId,
                    $this->normalizeTimeValue($activity['start_time'] ?? null),
                    $this->normalizeTimeValue($activity['end_time'] ?? null),
                    $activityType !== '' ? $activityType : null,
                    $subject !== '' ? $subject : null,
                    trim((string)($activity['result'] ?? '')) ?: null,
                    $memo !== '' ? $memo : null,
                    $sortOrder++
                ]);
            }

            return true;
        } catch (\Exception $e) {
            error_log("Error saving activity logs: " . $e->getMessage());
            return false;
        }
    }

    private function normalizeTimeValue($value)
    {
        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }
        if (preg_match('/^\d{2}:\d{2}$/', $value) === 1) {
            return $value . ':00';
        }
        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $value) === 1) {
            return $value;
        }
        return null;
    }

    private function getActivityLogs($reportId)
    {
        try {
            $rows = $this->db->fetchAll(
                "SELECT id, start_time, end_time, activity_type, subject, result, memo, sort_order
                 FROM daily_report_activity_logs
                 WHERE report_id = ?
                 ORDER BY sort_order ASC, id ASC",
                [$reportId]
            );
            foreach ($rows as &$row) {
                $row['start_time'] = $row['start_time'] ? substr($row['start_time'], 0, 5) : '';
                $row['end_time'] = $row['end_time'] ? substr($row['end_time'], 0, 5) : '';
            }
            return $rows;
        } catch (\Exception $e) {
            return [];
        }
    }

    private function normalizeTemplateSections($sections)
    {
        if (!is_array($sections)) {
            return [];
        }

        $normalized = [];
        $usedSectionKeys = [];
        $sortOrder = 1;
        foreach ($sections as $section) {
            if (!is_array($section)) {
                continue;
            }
            $title = trim((string)($section['title'] ?? ''));
            if ($title === '') {
                continue;
            }
            $inputType = trim((string)($section['input_type'] ?? 'textarea'));
            if (!in_array($inputType, ['text', 'textarea', 'checklist', 'number', 'rating', 'toggle'], true)) {
                $inputType = 'textarea';
            }

            $rawSectionKey = trim((string)($section['section_key'] ?? ''));
            $sanitizedKey = preg_replace('/[^A-Za-z0-9_-]/', '_', $rawSectionKey);
            if ($sanitizedKey === '' || $sanitizedKey === null) {
                $sanitizedKey = 'section_' . $sortOrder;
            }
            $baseSectionKey = $sanitizedKey;
            $suffix = 2;
            while (isset($usedSectionKeys[$sanitizedKey])) {
                $sanitizedKey = $baseSectionKey . '_' . $suffix;
                $suffix++;
            }
            $usedSectionKeys[$sanitizedKey] = true;

            $normalized[] = [
                'section_key' => $sanitizedKey,
                'title' => $title,
                'input_type' => $inputType,
                'is_required' => !empty($section['is_required']) ? 1 : 0,
                'placeholder_text' => trim((string)($section['placeholder_text'] ?? '')),
                'default_value_text' => trim((string)($section['default_value_text'] ?? '')),
                'options_json' => isset($section['options_json']) ? (is_string($section['options_json']) ? $section['options_json'] : json_encode($section['options_json'], JSON_UNESCAPED_UNICODE)) : null,
                'sort_order' => $sortOrder++
            ];
        }

        return $normalized;
    }

    private function saveTemplateSections($templateId, $sections)
    {
        try {
            $this->db->execute("DELETE FROM daily_report_template_sections WHERE template_id = ?", [$templateId]);
            if (empty($sections)) {
                return true;
            }

            $sql = "INSERT INTO daily_report_template_sections (
                        template_id, section_key, title, input_type, is_required, placeholder_text, default_value_text, options_json, sort_order
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            foreach ($sections as $section) {
                $this->db->execute($sql, [
                    $templateId,
                    $section['section_key'],
                    $section['title'],
                    $section['input_type'],
                    $section['is_required'],
                    $section['placeholder_text'] !== '' ? $section['placeholder_text'] : null,
                    $section['default_value_text'] !== '' ? $section['default_value_text'] : null,
                    $section['options_json'],
                    $section['sort_order']
                ]);
            }
            return true;
        } catch (\Exception $e) {
            error_log("Error saving template sections: " . $e->getMessage());
            return false;
        }
    }

    private function getTemplateSections($templateId)
    {
        try {
            return $this->db->fetchAll(
                "SELECT section_key, title, input_type, is_required, placeholder_text, default_value_text, options_json, sort_order
                 FROM daily_report_template_sections
                 WHERE template_id = ?
                 ORDER BY sort_order ASC, id ASC",
                [$templateId]
            );
        } catch (\Exception $e) {
            return [];
        }
    }

    private function saveAnalysisEntries($reportId, $entries)
    {
        try {
            $this->db->execute("DELETE FROM daily_report_analysis_entries WHERE report_id = ?", [$reportId]);
            if (empty($entries) || !is_array($entries)) {
                return true;
            }

            $sql = "INSERT INTO daily_report_analysis_entries (
                        report_id, project_id, industry_id, product_id, process_id, activity_type,
                        planned_amount, actual_amount, planned_hours, actual_hours, quantity, memo, sort_order
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $sortOrder = 1;
            foreach ($entries as $entry) {
                if (!is_array($entry)) {
                    continue;
                }

                $projectId = (int)($entry['project_id'] ?? 0);
                $industryId = (int)($entry['industry_id'] ?? 0);
                $productId = (int)($entry['product_id'] ?? 0);
                $processId = (int)($entry['process_id'] ?? 0);
                $activityType = trim((string)($entry['activity_type'] ?? ''));
                $memo = trim((string)($entry['memo'] ?? ''));
                $plannedAmount = $this->normalizeDecimal($entry['planned_amount'] ?? 0);
                $actualAmount = $this->normalizeDecimal($entry['actual_amount'] ?? 0);
                $plannedHours = $this->normalizeDecimal($entry['planned_hours'] ?? 0, 2);
                $actualHours = $this->normalizeDecimal($entry['actual_hours'] ?? 0, 2);
                $quantity = $this->normalizeDecimal($entry['quantity'] ?? 0);

                if (
                    $projectId <= 0 && $industryId <= 0 && $productId <= 0 && $processId <= 0
                    && $activityType === '' && $memo === ''
                    && $plannedAmount == 0.0 && $actualAmount == 0.0 && $plannedHours == 0.0 && $actualHours == 0.0 && $quantity == 0.0
                ) {
                    continue;
                }

                $this->db->execute($sql, [
                    $reportId,
                    $projectId > 0 ? $projectId : null,
                    $industryId > 0 ? $industryId : null,
                    $productId > 0 ? $productId : null,
                    $processId > 0 ? $processId : null,
                    $activityType !== '' ? $activityType : null,
                    $plannedAmount,
                    $actualAmount,
                    $plannedHours,
                    $actualHours,
                    $quantity,
                    $memo !== '' ? $memo : null,
                    $sortOrder++
                ]);
            }

            return true;
        } catch (\Exception $e) {
            error_log("Error saving analysis entries: " . $e->getMessage());
            return false;
        }
    }

    private function getAnalysisEntries($reportId)
    {
        try {
            $sql = "SELECT ae.*,
                           p.name AS project_name,
                           i.name AS industry_name,
                           pr.name AS product_name,
                           ps.name AS process_name
                    FROM daily_report_analysis_entries ae
                    LEFT JOIN daily_report_projects p ON p.id = ae.project_id
                    LEFT JOIN daily_report_industries i ON i.id = ae.industry_id
                    LEFT JOIN daily_report_products pr ON pr.id = ae.product_id
                    LEFT JOIN daily_report_processes ps ON ps.id = ae.process_id
                    WHERE ae.report_id = ?
                    ORDER BY ae.sort_order ASC, ae.id ASC";
            return $this->db->fetchAll($sql, [$reportId]);
        } catch (\Exception $e) {
            return [];
        }
    }

    private function normalizeDecimal($value, $scale = 2)
    {
        if ($value === null || $value === '') {
            return 0.0;
        }
        if (is_string($value)) {
            $value = str_replace([',', ' '], '', $value);
        }
        $num = is_numeric($value) ? (float)$value : 0.0;
        return round($num, $scale);
    }

    public function saveAttachments($reportId, $uploadedBy, $attachments)
    {
        if (empty($attachments) || !is_array($attachments)) {
            return true;
        }

        try {
            $sql = "INSERT INTO daily_report_attachments (
                        report_id, uploaded_by, original_name, stored_name, file_path, mime_type, file_size
                    ) VALUES (?, ?, ?, ?, ?, ?, ?)";

            foreach ($attachments as $file) {
                if (!is_array($file)) {
                    continue;
                }
                $this->db->execute($sql, [
                    $reportId,
                    $uploadedBy,
                    (string)($file['original_name'] ?? ''),
                    (string)($file['stored_name'] ?? ''),
                    (string)($file['file_path'] ?? ''),
                    (string)($file['mime_type'] ?? ''),
                    (int)($file['file_size'] ?? 0)
                ]);
            }

            return true;
        } catch (\Exception $e) {
            error_log("Error saving report attachments: " . $e->getMessage());
            return false;
        }
    }

    public function getAttachments($reportId)
    {
        try {
            return $this->db->fetchAll(
                "SELECT a.*, u.display_name AS uploader_name
                 FROM daily_report_attachments a
                 LEFT JOIN users u ON u.id = a.uploaded_by
                 WHERE a.report_id = ?
                 ORDER BY a.id ASC",
                [$reportId]
            );
        } catch (\Exception $e) {
            return [];
        }
    }

    public function deleteAttachments($reportId, $attachmentIds = [])
    {
        if (empty($attachmentIds) || !is_array($attachmentIds)) {
            return true;
        }

        $ids = [];
        foreach ($attachmentIds as $id) {
            $num = (int)$id;
            if ($num > 0) {
                $ids[] = $num;
            }
        }
        if (empty($ids)) {
            return true;
        }

        try {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $params = array_merge([$reportId], $ids);
            $rows = $this->db->fetchAll(
                "SELECT id, file_path FROM daily_report_attachments WHERE report_id = ? AND id IN ({$placeholders})",
                $params
            );
            foreach ($rows as $row) {
                $this->deleteAttachmentFileByPath((string)$row['file_path']);
            }
            return $this->db->execute(
                "DELETE FROM daily_report_attachments WHERE report_id = ? AND id IN ({$placeholders})",
                $params
            );
        } catch (\Exception $e) {
            error_log("Error deleting report attachments: " . $e->getMessage());
            return false;
        }
    }

    private function deleteAttachmentFileByPath($relativePath)
    {
        if ($relativePath === '') {
            return;
        }
        $fullPath = realpath(__DIR__ . '/../public') . '/' . ltrim($relativePath, '/');
        if ($fullPath && is_file($fullPath)) {
            @unlink($fullPath);
        }
    }

    public function getMasterItems($type)
    {
        $meta = $this->getMasterMeta($type);
        if ($meta === null) {
            return [];
        }

        try {
            return $this->db->fetchAll($meta['list_sql']);
        } catch (\Exception $e) {
            return [];
        }
    }

    public function saveMasterItem($type, $data, $userId)
    {
        $meta = $this->getMasterMeta($type);
        if ($meta === null) {
            return false;
        }

        $id = (int)($data['id'] ?? 0);
        $name = trim((string)($data['name'] ?? ''));
        if ($name === '') {
            return false;
        }
        $code = trim((string)($data['code'] ?? ''));
        $sortOrder = max(1, (int)($data['sort_order'] ?? 1));
        $isActive = !isset($data['is_active']) || (int)$data['is_active'] === 1 ? 1 : 0;
        $industryId = max(0, (int)($data['industry_id'] ?? 0));
        $productId = max(0, (int)($data['product_id'] ?? 0));
        $processId = max(0, (int)($data['process_id'] ?? 0));

        try {
            if ($id > 0) {
                switch ($type) {
                    case 'industry':
                    case 'process':
                        $this->db->execute($meta['update_sql'], [
                            $code !== '' ? $code : null,
                            $name,
                            $sortOrder,
                            $isActive,
                            $id
                        ]);
                        break;
                    case 'product':
                        $this->db->execute($meta['update_sql'], [
                            $code !== '' ? $code : null,
                            $name,
                            $sortOrder,
                            $isActive,
                            $industryId > 0 ? $industryId : null,
                            $id
                        ]);
                        break;
                    case 'project':
                        $this->db->execute($meta['update_sql'], [
                            $code !== '' ? $code : null,
                            $name,
                            $sortOrder,
                            $isActive,
                            $industryId > 0 ? $industryId : null,
                            $productId > 0 ? $productId : null,
                            $processId > 0 ? $processId : null,
                            $id
                        ]);
                        break;
                }
                return $id;
            }

            switch ($type) {
                case 'industry':
                case 'process':
                    $this->db->execute($meta['insert_sql'], [
                        $code !== '' ? $code : null,
                        $name,
                        $sortOrder,
                        $isActive,
                        (int)$userId
                    ]);
                    break;
                case 'product':
                    $this->db->execute($meta['insert_sql'], [
                        $code !== '' ? $code : null,
                        $name,
                        $sortOrder,
                        $isActive,
                        $industryId > 0 ? $industryId : null,
                        (int)$userId
                    ]);
                    break;
                case 'project':
                    $this->db->execute($meta['insert_sql'], [
                        $code !== '' ? $code : null,
                        $name,
                        $sortOrder,
                        $isActive,
                        $industryId > 0 ? $industryId : null,
                        $productId > 0 ? $productId : null,
                        $processId > 0 ? $processId : null,
                        (int)$userId
                    ]);
                    break;
            }
            return (int)$this->db->lastInsertId();
        } catch (\Exception $e) {
            error_log("Error saving master item: " . $e->getMessage());
            return false;
        }
    }

    public function deleteMasterItem($type, $id)
    {
        $meta = $this->getMasterMeta($type);
        if ($meta === null) {
            return false;
        }

        try {
            return $this->db->execute("DELETE FROM {$meta['table']} WHERE id = ?", [(int)$id]);
        } catch (\Exception $e) {
            return false;
        }
    }

    private function getMasterMeta($type)
    {
        switch ($type) {
            case 'industry':
                return [
                    'table' => 'daily_report_industries',
                    'list_sql' => "SELECT id, code, name, sort_order, is_active, NULL AS industry_id, NULL AS product_id, NULL AS process_id
                                   FROM daily_report_industries ORDER BY is_active DESC, sort_order ASC, id ASC",
                    'insert_sql' => "INSERT INTO daily_report_industries (code, name, sort_order, is_active, created_by)
                                     VALUES (?, ?, ?, ?, ?)",
                    'update_sql' => "UPDATE daily_report_industries
                                     SET code = ?, name = ?, sort_order = ?, is_active = ?, updated_at = CURRENT_TIMESTAMP
                                     WHERE id = ?"
                ];
            case 'product':
                return [
                    'table' => 'daily_report_products',
                    'list_sql' => "SELECT p.id, p.code, p.name, p.sort_order, p.is_active, p.industry_id, NULL AS product_id, NULL AS process_id
                                   FROM daily_report_products p
                                   ORDER BY p.is_active DESC, p.sort_order ASC, p.id ASC",
                    'insert_sql' => "INSERT INTO daily_report_products (code, name, sort_order, is_active, industry_id, created_by)
                                     VALUES (?, ?, ?, ?, ?, ?)",
                    'update_sql' => "UPDATE daily_report_products
                                     SET code = ?, name = ?, sort_order = ?, is_active = ?, industry_id = ?, updated_at = CURRENT_TIMESTAMP
                                     WHERE id = ?"
                ];
            case 'process':
                return [
                    'table' => 'daily_report_processes',
                    'list_sql' => "SELECT id, code, name, sort_order, is_active, NULL AS industry_id, NULL AS product_id, NULL AS process_id
                                   FROM daily_report_processes ORDER BY is_active DESC, sort_order ASC, id ASC",
                    'insert_sql' => "INSERT INTO daily_report_processes (code, name, sort_order, is_active, created_by)
                                     VALUES (?, ?, ?, ?, ?)",
                    'update_sql' => "UPDATE daily_report_processes
                                     SET code = ?, name = ?, sort_order = ?, is_active = ?, updated_at = CURRENT_TIMESTAMP
                                     WHERE id = ?"
                ];
            case 'project':
                return [
                    'table' => 'daily_report_projects',
                    'list_sql' => "SELECT id, code, name, sort_order, is_active, industry_id, product_id, process_id
                                   FROM daily_report_projects ORDER BY is_active DESC, sort_order ASC, id ASC",
                    'insert_sql' => "INSERT INTO daily_report_projects (
                                        code, name, sort_order, is_active, industry_id, product_id, process_id, created_by
                                     ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                    'update_sql' => "UPDATE daily_report_projects
                                     SET code = ?, name = ?, sort_order = ?, is_active = ?,
                                         industry_id = ?, product_id = ?, process_id = ?, updated_at = CURRENT_TIMESTAMP
                                     WHERE id = ?"
                ];
            default:
                return null;
        }
    }

    public function getAnalysisMasterSet()
    {
        try {
            return [
                'industries' => $this->db->fetchAll("SELECT id, code, name FROM daily_report_industries WHERE is_active = 1 ORDER BY sort_order, id"),
                'products' => $this->db->fetchAll("SELECT id, code, name, industry_id FROM daily_report_products WHERE is_active = 1 ORDER BY sort_order, id"),
                'processes' => $this->db->fetchAll("SELECT id, code, name FROM daily_report_processes WHERE is_active = 1 ORDER BY sort_order, id"),
                'projects' => $this->db->fetchAll("SELECT id, code, name, industry_id, product_id, process_id FROM daily_report_projects WHERE is_active = 1 ORDER BY sort_order, id")
            ];
        } catch (\Exception $e) {
            return [
                'industries' => [],
                'products' => [],
                'processes' => [],
                'projects' => []
            ];
        }
    }

    public function saveMonthlyTarget($userId, $data)
    {
        $targetMonth = trim((string)($data['target_month'] ?? ''));
        if (preg_match('/^\d{4}-\d{2}$/', $targetMonth) !== 1) {
            return false;
        }

        $projectId = max(0, (int)($data['project_id'] ?? 0));
        $industryId = max(0, (int)($data['industry_id'] ?? 0));
        $productId = max(0, (int)($data['product_id'] ?? 0));
        $processId = max(0, (int)($data['process_id'] ?? 0));
        $dimensionKey = sprintf(
            'p:%d|i:%d|pr:%d|ps:%d',
            $projectId,
            $industryId,
            $productId,
            $processId
        );

        $targetAmount = $this->normalizeDecimal($data['target_amount'] ?? 0);
        $targetHours = $this->normalizeDecimal($data['target_hours'] ?? 0, 2);
        $targetQuantity = $this->normalizeDecimal($data['target_quantity'] ?? 0);
        $memo = trim((string)($data['memo'] ?? ''));

        try {
            $sql = "INSERT INTO daily_report_monthly_targets (
                        user_id, target_month, dimension_key, project_id, industry_id, product_id, process_id,
                        target_amount, target_hours, target_quantity, memo
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                        target_amount = VALUES(target_amount),
                        target_hours = VALUES(target_hours),
                        target_quantity = VALUES(target_quantity),
                        memo = VALUES(memo),
                        updated_at = CURRENT_TIMESTAMP";
            $this->db->execute($sql, [
                (int)$userId,
                $targetMonth,
                $dimensionKey,
                $projectId > 0 ? $projectId : null,
                $industryId > 0 ? $industryId : null,
                $productId > 0 ? $productId : null,
                $processId > 0 ? $processId : null,
                $targetAmount,
                $targetHours,
                $targetQuantity,
                $memo !== '' ? $memo : null
            ]);
            return true;
        } catch (\Exception $e) {
            error_log("Error saving monthly target: " . $e->getMessage());
            return false;
        }
    }

    public function getMonthlyTargets($userId, $targetMonth)
    {
        if (preg_match('/^\d{4}-\d{2}$/', (string)$targetMonth) !== 1) {
            return [];
        }

        try {
            $sql = "SELECT t.*,
                           p.name AS project_name,
                           i.name AS industry_name,
                           pr.name AS product_name,
                           ps.name AS process_name
                    FROM daily_report_monthly_targets t
                    LEFT JOIN daily_report_projects p ON p.id = t.project_id
                    LEFT JOIN daily_report_industries i ON i.id = t.industry_id
                    LEFT JOIN daily_report_products pr ON pr.id = t.product_id
                    LEFT JOIN daily_report_processes ps ON ps.id = t.process_id
                    WHERE t.user_id = ? AND t.target_month = ?
                    ORDER BY t.id DESC";
            return $this->db->fetchAll($sql, [(int)$userId, $targetMonth]);
        } catch (\Exception $e) {
            return [];
        }
    }

    public function deleteMonthlyTarget($userId, $targetId)
    {
        try {
            return $this->db->execute(
                "DELETE FROM daily_report_monthly_targets WHERE id = ? AND user_id = ?",
                [(int)$targetId, (int)$userId]
            );
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getAnalysisSummary($filters)
    {
        $params = [];
        $sql = "SELECT
                    COALESCE(SUM(ae.planned_amount), 0) AS planned_amount_total,
                    COALESCE(SUM(ae.actual_amount), 0) AS actual_amount_total,
                    COALESCE(SUM(ae.planned_hours), 0) AS planned_hours_total,
                    COALESCE(SUM(ae.actual_hours), 0) AS actual_hours_total,
                    COALESCE(SUM(ae.quantity), 0) AS quantity_total,
                    COUNT(DISTINCT r.id) AS reports_count
                FROM daily_report_analysis_entries ae
                JOIN daily_reports r ON r.id = ae.report_id
                WHERE 1=1";
        $this->appendAnalysisFilters($sql, $params, $filters);
        return $this->db->fetch($sql, $params) ?: [
            'planned_amount_total' => 0,
            'actual_amount_total' => 0,
            'planned_hours_total' => 0,
            'actual_hours_total' => 0,
            'quantity_total' => 0,
            'reports_count' => 0
        ];
    }

    public function getAnalysisBreakdown($filters, $axis = 'project')
    {
        $axisMap = [
            'project' => ['column' => 'ae.project_id', 'name_sql' => 'COALESCE(p.name, "(未設定)")'],
            'industry' => ['column' => 'ae.industry_id', 'name_sql' => 'COALESCE(i.name, "(未設定)")'],
            'product' => ['column' => 'ae.product_id', 'name_sql' => 'COALESCE(pr.name, "(未設定)")'],
            'process' => ['column' => 'ae.process_id', 'name_sql' => 'COALESCE(ps.name, "(未設定)")']
        ];
        if (!isset($axisMap[$axis])) {
            $axis = 'project';
        }

        $params = [];
        $column = $axisMap[$axis]['column'];
        $nameSql = $axisMap[$axis]['name_sql'];
        $sql = "SELECT {$column} AS axis_id,
                       {$nameSql} AS axis_name,
                       COALESCE(SUM(ae.planned_amount), 0) AS planned_amount_total,
                       COALESCE(SUM(ae.actual_amount), 0) AS actual_amount_total,
                       COALESCE(SUM(ae.planned_hours), 0) AS planned_hours_total,
                       COALESCE(SUM(ae.actual_hours), 0) AS actual_hours_total,
                       COALESCE(SUM(ae.quantity), 0) AS quantity_total,
                       COUNT(*) AS entries_count
                FROM daily_report_analysis_entries ae
                JOIN daily_reports r ON r.id = ae.report_id
                LEFT JOIN daily_report_projects p ON p.id = ae.project_id
                LEFT JOIN daily_report_industries i ON i.id = ae.industry_id
                LEFT JOIN daily_report_products pr ON pr.id = ae.product_id
                LEFT JOIN daily_report_processes ps ON ps.id = ae.process_id
                WHERE 1=1";
        $this->appendAnalysisFilters($sql, $params, $filters);
        $sql .= " GROUP BY {$column}, {$nameSql}
                  ORDER BY actual_amount_total DESC, axis_name ASC
                  LIMIT 100";
        return $this->db->fetchAll($sql, $params);
    }

    public function getAnalysisMonthlyTrend($filters)
    {
        $params = [];
        $sql = "SELECT DATE_FORMAT(r.report_date, '%Y-%m') AS target_month,
                       COALESCE(SUM(ae.planned_amount), 0) AS planned_amount_total,
                       COALESCE(SUM(ae.actual_amount), 0) AS actual_amount_total,
                       COALESCE(SUM(ae.planned_hours), 0) AS planned_hours_total,
                       COALESCE(SUM(ae.actual_hours), 0) AS actual_hours_total,
                       COALESCE(SUM(ae.quantity), 0) AS quantity_total
                FROM daily_report_analysis_entries ae
                JOIN daily_reports r ON r.id = ae.report_id
                WHERE 1=1";
        $this->appendAnalysisFilters($sql, $params, $filters);
        $sql .= " GROUP BY DATE_FORMAT(r.report_date, '%Y-%m')
                  ORDER BY target_month ASC";
        return $this->db->fetchAll($sql, $params);
    }

    public function getAnalysisTargetVsActual($userId, $targetMonth, $filters = [])
    {
        if (preg_match('/^\d{4}-\d{2}$/', (string)$targetMonth) !== 1) {
            return [];
        }

        $targets = $this->getMonthlyTargets($userId, $targetMonth);
        if (empty($targets)) {
            return [];
        }

        $result = [];
        foreach ($targets as $target) {
            $calcFilters = array_merge($filters, [
                'user_id' => (int)$userId,
                'month' => $targetMonth,
                'project_id' => $target['project_id'] ?? null,
                'industry_id' => $target['industry_id'] ?? null,
                'product_id' => $target['product_id'] ?? null,
                'process_id' => $target['process_id'] ?? null
            ]);
            $actual = $this->getAnalysisSummary($calcFilters);
            $result[] = [
                'target' => $target,
                'actual' => $actual
            ];
        }
        return $result;
    }

    private function appendAnalysisFilters(&$sql, array &$params, $filters)
    {
        if (!empty($filters['user_id'])) {
            $sql .= " AND r.user_id = ?";
            $params[] = (int)$filters['user_id'];
        }
        if (!empty($filters['start_date'])) {
            $sql .= " AND r.report_date >= ?";
            $params[] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $sql .= " AND r.report_date <= ?";
            $params[] = $filters['end_date'];
        }
        if (!empty($filters['month']) && preg_match('/^\d{4}-\d{2}$/', (string)$filters['month']) === 1) {
            $sql .= " AND DATE_FORMAT(r.report_date, '%Y-%m') = ?";
            $params[] = $filters['month'];
        }
        if (!empty($filters['project_id'])) {
            $sql .= " AND ae.project_id = ?";
            $params[] = (int)$filters['project_id'];
        }
        if (!empty($filters['industry_id'])) {
            $sql .= " AND ae.industry_id = ?";
            $params[] = (int)$filters['industry_id'];
        }
        if (!empty($filters['product_id'])) {
            $sql .= " AND ae.product_id = ?";
            $params[] = (int)$filters['product_id'];
        }
        if (!empty($filters['process_id'])) {
            $sql .= " AND ae.process_id = ?";
            $params[] = (int)$filters['process_id'];
        }
    }

    public function getCsvExportRows($filters)
    {
        $params = [];
        $sql = "SELECT r.id,
                       r.report_date,
                       u.display_name AS creator_name,
                       r.title,
                       r.status,
                       COALESCE(SUM(ae.planned_amount), 0) AS planned_amount_total,
                       COALESCE(SUM(ae.actual_amount), 0) AS actual_amount_total,
                       COALESCE(SUM(ae.planned_hours), 0) AS planned_hours_total,
                       COALESCE(SUM(ae.actual_hours), 0) AS actual_hours_total,
                       COALESCE(SUM(ae.quantity), 0) AS quantity_total
                FROM daily_reports r
                JOIN users u ON u.id = r.user_id
                LEFT JOIN daily_report_analysis_entries ae ON ae.report_id = r.id
                WHERE 1=1";
        if (!empty($filters['user_id'])) {
            $sql .= " AND r.user_id = ?";
            $params[] = (int)$filters['user_id'];
        }
        if (!empty($filters['start_date'])) {
            $sql .= " AND r.report_date >= ?";
            $params[] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $sql .= " AND r.report_date <= ?";
            $params[] = $filters['end_date'];
        }
        if (!empty($filters['project_id'])) {
            $sql .= " AND ae.project_id = ?";
            $params[] = (int)$filters['project_id'];
        }
        if (!empty($filters['industry_id'])) {
            $sql .= " AND ae.industry_id = ?";
            $params[] = (int)$filters['industry_id'];
        }
        if (!empty($filters['product_id'])) {
            $sql .= " AND ae.product_id = ?";
            $params[] = (int)$filters['product_id'];
        }
        if (!empty($filters['process_id'])) {
            $sql .= " AND ae.process_id = ?";
            $params[] = (int)$filters['process_id'];
        }
        $sql .= " GROUP BY r.id, r.report_date, u.display_name, r.title, r.status
                  ORDER BY r.report_date DESC, r.id DESC";
        return $this->db->fetchAll($sql, $params);
    }
}

<?php
// models/User.php
namespace Models;

use Core\Database;

class User {
    private $db;
    private const DEFAULT_CALENDAR_COLOR = '#3b82f6';
    private const CALENDAR_COLOR_PALETTE = [
        '#3b82f6', '#ef4444', '#10b981', '#f59e0b', '#8b5cf6',
        '#06b6d4', '#f97316', '#84cc16', '#ec4899', '#14b8a6',
        '#6366f1', '#22c55e', '#0ea5e9', '#d946ef', '#eab308'
    ];
    
    public function __construct() {
        $this->db = Database::getInstance();
    }

    private function normalizeCalendarColor($color)
    {
        if (!is_string($color)) {
            return null;
        }

        $color = trim($color);
        if ($color === '') {
            return null;
        }

        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
            return null;
        }

        return strtoupper($color);
    }

    private function getUsedCalendarColors()
    {
        $rows = $this->db->fetchAll(
            "SELECT calendar_color
             FROM users
             WHERE calendar_color REGEXP '^#[0-9A-Fa-f]{6}$'"
        );

        $colors = [];
        foreach ($rows as $row) {
            if (!empty($row['calendar_color'])) {
                $colors[strtoupper($row['calendar_color'])] = true;
            }
        }

        return array_keys($colors);
    }

    private function pickAutoCalendarColor()
    {
        $used = array_fill_keys($this->getUsedCalendarColors(), true);

        foreach (self::CALENDAR_COLOR_PALETTE as $candidate) {
            $normalized = strtoupper($candidate);
            if (!isset($used[$normalized])) {
                return $normalized;
            }
        }

        $count = (int)($this->db->fetch("SELECT COUNT(*) AS c FROM users")['c'] ?? 0);
        return strtoupper(self::CALENDAR_COLOR_PALETTE[$count % count(self::CALENDAR_COLOR_PALETTE)]);
    }
    
    // 全ユーザーを取得
    public function getAll($page = 1, $limit = 20, $search = null) {
        $offset = ($page - 1) * $limit;
        $params = [];
        
        $sql = "SELECT u.*, o.name as organization_name 
                FROM users u 
                LEFT JOIN organizations o ON u.organization_id = o.id ";
        
        // 検索条件
        if ($search) {
            $sql .= "WHERE u.username LIKE ? OR u.email LIKE ? OR 
                    u.first_name LIKE ? OR u.last_name LIKE ? OR u.display_name LIKE ? ";
            $searchTerm = "%" . $search . "%";
            $params = array_fill(0, 5, $searchTerm);
        }
        
        $sql .= "ORDER BY u.last_name, u.first_name LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        return $this->db->fetchAll($sql, $params);
    }
    
    // ユーザー数を取得
    public function getCount($search = null) {
        $sql = "SELECT COUNT(*) as count FROM users";
        $params = [];
        
        // 検索条件
        if ($search) {
            $sql .= " WHERE username LIKE ? OR email LIKE ? OR 
                    first_name LIKE ? OR last_name LIKE ? OR display_name LIKE ?";
            $searchTerm = "%" . $search . "%";
            $params = array_fill(0, 5, $searchTerm);
        }
        
        $result = $this->db->fetch($sql, $params);
        return $result['count'];
    }
    
    // 特定のユーザーを取得
    public function getById($id) {
        $sql = "SELECT * FROM users WHERE id = ? LIMIT 1";
        return $this->db->fetch($sql, [$id]);
    }
    
    // ユーザー名でユーザーを取得
    public function getByUsername($username) {
        $sql = "SELECT * FROM users WHERE username = ? LIMIT 1";
        return $this->db->fetch($sql, [$username]);
    }
    
    // メールアドレスでユーザーを取得
    public function getByEmail($email) {
        $sql = "SELECT * FROM users WHERE email = ? LIMIT 1";
        return $this->db->fetch($sql, [$email]);
    }
    
    // ユーザーの組織を取得
    public function getUserOrganizations($userId) {
        $sql = "SELECT o.*, uo.is_primary 
                FROM organizations o 
                JOIN user_organizations uo ON o.id = uo.organization_id 
                WHERE uo.user_id = ? 
                ORDER BY uo.is_primary DESC, o.name";
        
        return $this->db->fetchAll($sql, [$userId]);
    }
    
    // ユーザーを作成
    public function create($data) {
        // 必須項目チェック
        if (empty($data['username']) || empty($data['password']) || empty($data['email']) ||
            empty($data['first_name']) || empty($data['last_name'])) {
            return false;
        }
        
        // ユーザー名とメールアドレスの重複チェック
        if ($this->getByUsername($data['username']) || $this->getByEmail($data['email'])) {
            return false;
        }
        
        // パスワードハッシュ化
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // ディスプレイ名がなければ、氏名を結合
        if (empty($data['display_name'])) {
            $data['display_name'] = $data['last_name'] . ' ' . $data['first_name'];
        }

        $calendarColor = $this->normalizeCalendarColor($data['calendar_color'] ?? null);
        if ($calendarColor === null) {
            $calendarColor = $this->pickAutoCalendarColor();
        }
        
        // トランザクション開始
        $this->db->beginTransaction();
        
        try {
            // ユーザー情報を挿入
            $sql = "INSERT INTO users (
                        username, 
                        password, 
                        email, 
                        first_name, 
                        last_name, 
                        display_name, 
                        calendar_color,
                        organization_id, 
                        position, 
                        phone, 
                        mobile_phone, 
                        status, 
                        role
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $this->db->execute($sql, [
                $data['username'],
                $hashedPassword,
                $data['email'],
                $data['first_name'],
                $data['last_name'],
                $data['display_name'],
                $calendarColor,
                $data['organization_id'] ?? null,
                $data['position'] ?? null,
                $data['phone'] ?? null,
                $data['mobile_phone'] ?? null,
                $data['status'] ?? 'active',
                $data['role'] ?? 'user'
            ]);
            
            $userId = $this->db->lastInsertId();
            
            // 組織関連付けがあれば追加
            if (!empty($data['organization_id'])) {
                $this->addUserToOrganization($userId, $data['organization_id'], true);
            }
            
            // 追加の組織関連付け
            if (!empty($data['additional_organizations']) && is_array($data['additional_organizations'])) {
                foreach ($data['additional_organizations'] as $orgId) {
                    if ($orgId != $data['organization_id']) {
                        $this->addUserToOrganization($userId, $orgId, false);
                    }
                }
            }
            
            $this->db->commit();
            return $userId;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
    
    // ユーザーを更新
    public function update($id, $data) {
        $user = $this->getById($id);
        if (!$user) {
            return false;
        }
        
        // ユーザー名とメールアドレスの重複チェック
        if (!empty($data['username']) && $data['username'] !== $user['username'] && $this->getByUsername($data['username'])) {
            return false;
        }
        if (!empty($data['email']) && $data['email'] !== $user['email'] && $this->getByEmail($data['email'])) {
            return false;
        }
        
        // 更新フィールドと値の準備
        $fields = [];
        $values = [];

        if (array_key_exists('calendar_color', $data)) {
            $normalizedColor = $this->normalizeCalendarColor($data['calendar_color']);
            $data['calendar_color'] = $normalizedColor ?: ($user['calendar_color'] ?? self::DEFAULT_CALENDAR_COLOR);
        }
        
        // 更新可能なフィールド
        $updateableFields = [
            'username', 'email', 'first_name', 'last_name', 'display_name',
            'calendar_color', 'organization_id', 'position', 'phone', 'mobile_phone', 'status', 'role'
        ];
        
        foreach ($updateableFields as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "$field = ?";
                $values[] = $data[$field];
            }
        }
        
        // パスワード変更がある場合
        if (!empty($data['password'])) {
            $fields[] = "password = ?";
            $values[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        if (empty($fields)) {
            return true; // 更新するものがない
        }
        
        $fields[] = "updated_at = CURRENT_TIMESTAMP";
        $values[] = $id; // WHEREの条件用
        
        // トランザクション開始
        $this->db->beginTransaction();
        
        try {
            // ユーザー情報更新
            $sql = "UPDATE users SET " . implode(", ", $fields) . " WHERE id = ?";
            $this->db->execute($sql, $values);
            
            // 組織関連付けの更新
            if (isset($data['organization_id']) || 
                (isset($data['additional_organizations']) && is_array($data['additional_organizations']))) {
                
                // 主組織の更新
                if (isset($data['organization_id'])) {
                    // 既存の主組織を検索
                    $primaryOrgSql = "SELECT organization_id FROM user_organizations WHERE user_id = ? AND is_primary = 1";
                    $primaryOrg = $this->db->fetch($primaryOrgSql, [$id]);
                    
                    if ($primaryOrg) {
                        // 主組織を更新
                        if ($primaryOrg['organization_id'] != $data['organization_id']) {
                            $this->db->execute(
                                "UPDATE user_organizations SET is_primary = 0 WHERE user_id = ? AND organization_id = ?",
                                [$id, $primaryOrg['organization_id']]
                            );
                            
                            // 新しい主組織が既に関連付けられているか確認
                            $existingSql = "SELECT COUNT(*) as count FROM user_organizations WHERE user_id = ? AND organization_id = ?";
                            $existing = $this->db->fetch($existingSql, [$id, $data['organization_id']]);
                            
                            if ($existing && $existing['count'] > 0) {
                                // 既存の関連付けを主組織に更新
                                $this->db->execute(
                                    "UPDATE user_organizations SET is_primary = 1 WHERE user_id = ? AND organization_id = ?",
                                    [$id, $data['organization_id']]
                                );
                            } else {
                                // 新しい主組織を追加
                                $this->addUserToOrganization($id, $data['organization_id'], true);
                            }
                        }
                    } else {
                        // 主組織がない場合は追加
                        $this->addUserToOrganization($id, $data['organization_id'], true);
                    }
                }
                
                // 追加の組織関連付けを更新
                if (isset($data['additional_organizations']) && is_array($data['additional_organizations'])) {
                    // 現在の関連付けを取得
                    $currentOrgsSql = "SELECT organization_id FROM user_organizations WHERE user_id = ?";
                    $currentOrgsResult = $this->db->fetchAll($currentOrgsSql, [$id]);
                    $currentOrgs = array_column($currentOrgsResult, 'organization_id');
                    
                    // 主組織を除外
                    $primaryOrgId = isset($data['organization_id']) ? $data['organization_id'] : null;
                    if ($primaryOrgId) {
                        $data['additional_organizations'] = array_filter($data['additional_organizations'], function($orgId) use ($primaryOrgId) {
                            return $orgId != $primaryOrgId;
                        });
                    }
                    
                    // 削除する組織
                    $orgsToRemove = array_diff($currentOrgs, array_merge($data['additional_organizations'], [$primaryOrgId]));
                    foreach ($orgsToRemove as $orgId) {
                        $this->removeUserFromOrganization($id, $orgId);
                    }
                    
                    // 追加する組織
                    foreach ($data['additional_organizations'] as $orgId) {
                        if (!in_array($orgId, $currentOrgs)) {
                            $this->addUserToOrganization($id, $orgId, false);
                        }
                    }
                }
            }
            
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
    
    // ユーザーを削除
    public function delete($id) {
        // ユーザーが存在するか確認
        $user = $this->getById($id);
        if (!$user) {
            return false;
        }

        // トランザクション開始
        $this->db->beginTransaction();

        try {
            // 関連テーブルを先に削除（外部キー制約対応）
            // 直接削除するもの（ユーザー固有データ）
            $deleteTables = [
                'user_organizations' => 'user_id',
                'user_tokens' => 'user_id',
                'notification_settings' => 'user_id',
                'notifications' => 'user_id',
                'schedule_participants' => 'user_id',
                'message_recipients' => 'user_id',
                'bulletin_post_reads' => 'user_id',
                'bulletin_comments' => 'user_id',
                'daily_report_likes' => 'user_id',
                'daily_report_comments' => 'user_id',
                'daily_report_reads' => 'user_id',
                'daily_report_tags' => 'user_id',
                'task_assignees' => 'user_id',
                'task_comments' => 'user_id',
                'task_board_members' => 'user_id',
                'task_activities' => 'user_id',
                'team_members' => 'user_id',
                'facility_reservations' => 'user_id',
                'workflow_comments' => 'user_id',
                'workflow_approvals' => 'approver_id',
                'workflow_delegates' => 'user_id',
                'calendar_integration_settings' => 'user_id',
                'calendar_import_subscriptions' => 'user_id',
                'file_checkout_history' => 'user_id',
                'automation_jobs' => 'created_by',
                'address_book' => 'created_by',
                'business_cards' => 'user_id',
            ];

            foreach ($deleteTables as $table => $column) {
                try {
                    $this->db->execute("DELETE FROM {$table} WHERE {$column} = ?", [$id]);
                } catch (\Exception $e) {
                    // テーブルが存在しない場合はスキップ
                }
            }

            // delegate_id も削除
            try {
                $this->db->execute("DELETE FROM workflow_delegates WHERE delegate_id = ?", [$id]);
            } catch (\Exception $e) {}

            // NULLに設定するもの（他ユーザーのデータに紐付くもの）
            $nullifyTables = [
                'schedules' => 'creator_id',
                'messages' => 'sender_id',
                'bulletin_posts' => 'author_id',
                'bulletin_categories' => 'created_by',
                'workflow_templates' => 'creator_id',
                'workflow_requests' => 'requester_id',
                'workflow_approvals' => 'approver_id',
                'workflow_approvals' => 'delegate_id',
                'web_databases' => 'creator_id',
                'web_database_records' => 'creator_id',
                'web_database_records' => 'updater_id',
                'web_database_views' => 'creator_id',
                'file_folders' => 'created_by',
                'file_entries' => 'uploaded_by',
                'file_entries' => 'checked_out_by',
                'file_versions' => 'uploaded_by',
                'file_permissions' => 'created_by',
                'file_approval_requests' => 'requested_by',
                'file_approval_steps' => 'approver_id',
                'task_cards' => 'created_by',
                'task_attachments' => 'uploaded_by',
                'task_boards' => 'created_by',
                'teams' => 'created_by',
                'daily_reports' => 'user_id',
                'daily_report_templates' => 'user_id',
            ];

            foreach ($nullifyTables as $table => $column) {
                try {
                    $this->db->execute("UPDATE {$table} SET {$column} = NULL WHERE {$column} = ?", [$id]);
                } catch (\Exception $e) {
                    // テーブルが存在しない場合やNULL不可の場合はスキップ
                }
            }

            // ユーザーを削除
            $this->db->execute("DELETE FROM users WHERE id = ?", [$id]);

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("User delete failed (ID: {$id}): " . $e->getMessage());
            return false;
        }
    }
    
    // ユーザーを組織に追加
    public function addUserToOrganization($userId, $organizationId, $isPrimary = false) {
        // 既に関連付けがあるか確認
        $sql = "SELECT COUNT(*) as count FROM user_organizations WHERE user_id = ? AND organization_id = ?";
        $result = $this->db->fetch($sql, [$userId, $organizationId]);
        
        if ($result && $result['count'] > 0) {
            // 既に関連付けがある場合は主組織フラグのみ更新
            if ($isPrimary) {
                $this->db->execute(
                    "UPDATE user_organizations SET is_primary = 1 WHERE user_id = ? AND organization_id = ?",
                    [$userId, $organizationId]
                );
            }
            return true;
        }
        
        // 新規関連付け
        $sql = "INSERT INTO user_organizations (user_id, organization_id, is_primary) VALUES (?, ?, ?)";
        return $this->db->execute($sql, [$userId, $organizationId, $isPrimary ? 1 : 0]);
    }
    
    // ユーザーを組織から削除
    public function removeUserFromOrganization($userId, $organizationId) {
        // 主組織かどうか確認
        $sql = "SELECT is_primary FROM user_organizations WHERE user_id = ? AND organization_id = ?";
        $result = $this->db->fetch($sql, [$userId, $organizationId]);
        
        if ($result && $result['is_primary']) {
            // 主組織は削除不可（別の組織を主組織に設定してから削除する必要がある）
            return false;
        }
        
        // 関連付けを削除
        $sql = "DELETE FROM user_organizations WHERE user_id = ? AND organization_id = ?";
        return $this->db->execute($sql, [$userId, $organizationId]);
    }
    
    // 主組織を変更
    public function changePrimaryOrganization($userId, $organizationId) {
        // 組織が存在するか確認
        $orgModel = new Organization();
        if (!$orgModel->getById($organizationId)) {
            return false;
        }
        
        // ユーザーが存在するか確認
        if (!$this->getById($userId)) {
            return false;
        }
        
        // トランザクション開始
        $this->db->beginTransaction();
        
        try {
            // 現在の主組織を解除
            $this->db->execute(
                "UPDATE user_organizations SET is_primary = 0 WHERE user_id = ? AND is_primary = 1",
                [$userId]
            );
            
            // 新しい主組織が既に関連付けられているか確認
            $existingSql = "SELECT COUNT(*) as count FROM user_organizations WHERE user_id = ? AND organization_id = ?";
            $existing = $this->db->fetch($existingSql, [$userId, $organizationId]);
            
            if ($existing && $existing['count'] > 0) {
                // 既存の関連付けを主組織に更新
                $this->db->execute(
                    "UPDATE user_organizations SET is_primary = 1 WHERE user_id = ? AND organization_id = ?",
                    [$userId, $organizationId]
                );
            } else {
                // 新しい主組織を追加
                $this->addUserToOrganization($userId, $organizationId, true);
            }
            
            // users テーブルの organization_id も更新
            $this->db->execute(
                "UPDATE users SET organization_id = ? WHERE id = ?",
                [$organizationId, $userId]
            );
            
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
    
    // ユーザーのパスワードを変更
    public function changePassword($userId, $newPassword) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET password = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        return $this->db->execute($sql, [$hashedPassword, $userId]);
    }

    // 特定の組織に所属するユーザーを取得
    public function getUsersByOrganization($organizationId, $includeChildren = false)
    {
        $orgIds = [$organizationId];

        // 子組織も含める場合
        if ($includeChildren) {
            $orgModel = new Organization();
            $descendants = $orgModel->getDescendants($organizationId);
            foreach ($descendants as $descendant) {
                $orgIds[] = $descendant['id'];
            }
        }

        // デバッグ情報を追加
        error_log("GetUsersByOrganization - 対象組織ID: " . implode(', ', $orgIds));

        // プレースホルダーを生成
        $placeholders = implode(',', array_fill(0, count($orgIds), '?'));

        // クエリの実行
        $sql = "SELECT DISTINCT u.* 
                FROM users u 
                JOIN user_organizations uo ON u.id = uo.user_id 
                WHERE uo.organization_id IN ({$placeholders}) 
                ORDER BY u.last_name, u.first_name";

        $users = $this->db->fetchAll($sql, $orgIds);

        // デバッグ情報を追加
        error_log("GetUsersByOrganization - 取得ユーザー数: " . count($users));
        if (count($users) === 0) {
            // ユーザー組織関連テーブルの状態を確認
            $check_sql = "SELECT * FROM user_organizations WHERE organization_id = ?";
            $check_result = $this->db->fetchAll($check_sql, [$organizationId]);
            error_log("組織ID " . $organizationId . " の user_organizations テーブル: " . json_encode($check_result));

            // ユーザーテーブルの状態も確認
            $users_sql = "SELECT COUNT(*) as count FROM users";
            $users_count = $this->db->fetch($users_sql);
            error_log("ユーザーテーブルの総数: " . $users_count['count']);
        }

        return $users;
    }

    // アクティブなユーザー一覧を取得するメソッド
    public function getActiveUsers()
    {
        $sql = "SELECT id, username, display_name, email, calendar_color
            FROM users 
            WHERE status = 'active' 
            ORDER BY display_name";

        return $this->db->fetchAll($sql);
    }

    // ユーザーの所属組織IDのリストを取得するメソッド
    public function getUserOrganizationIds($userId)
    {
        $sql = "SELECT organization_id 
            FROM user_organizations 
            WHERE user_id = ?";

        $result = $this->db->fetchAll($sql, [$userId]);
        return array_column($result, 'organization_id');
    }
}

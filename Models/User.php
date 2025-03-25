<?php
// models/User.php
namespace Models;

use Core\Database;

class User {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
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
        
        // 更新可能なフィールド
        $updateableFields = [
            'username', 'email', 'first_name', 'last_name', 'display_name',
            'organization_id', 'position', 'phone', 'mobile_phone', 'status', 'role'
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
            // ユーザー組織関連を削除
            $this->db->execute("DELETE FROM user_organizations WHERE user_id = ?", [$id]);
            
            // ユーザーを削除
            $this->db->execute("DELETE FROM users WHERE id = ?", [$id]);
            
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
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
    public function getUsersByOrganization($organizationId, $includeChildren = false) {
        $orgIds = [$organizationId];
        
        // 子組織も含める場合
        if ($includeChildren) {
            $orgModel = new Organization();
            $descendants = $orgModel->getDescendants($organizationId);
            foreach ($descendants as $descendant) {
                $orgIds[] = $descendant['id'];
            }
        }
        
        $placeholders = implode(',', array_fill(0, count($orgIds), '?'));
        
        $sql = "SELECT DISTINCT u.* 
                FROM users u 
                JOIN user_organizations uo ON u.id = uo.user_id 
                WHERE uo.organization_id IN ({$placeholders}) 
                ORDER BY u.last_name, u.first_name";
        
        return $this->db->fetchAll($sql, $orgIds);
    }

    // アクティブなユーザー一覧を取得するメソッド
    public function getActiveUsers()
    {
        $sql = "SELECT id, username, display_name, email 
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

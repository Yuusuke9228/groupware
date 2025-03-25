<?php
// models/Organization.php
namespace Models;

use Core\Database;

class Organization {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    // 全組織を取得（ツリー構造で）
    public function getAllAsTree() {
        $sql = "SELECT * FROM organizations ORDER BY parent_id, sort_order, name";
        $organizations = $this->db->fetchAll($sql);
        
        return $this->buildTree($organizations);
    }
    
    // 組織ツリーを構築
    private function buildTree(array $elements, $parentId = null) {
        $branch = [];
        
        foreach ($elements as $element) {
            if ($element['parent_id'] == $parentId) {
                $children = $this->buildTree($elements, $element['id']);
                
                if ($children) {
                    $element['children'] = $children;
                }
                
                $branch[] = $element;
            }
        }
        
        return $branch;
    }
    
    // 全組織を取得（フラットリストで）
    public function getAll() {
        $sql = "SELECT * FROM organizations ORDER BY parent_id, sort_order, name";
        return $this->db->fetchAll($sql);
    }
    
    // 特定の組織を取得
    public function getById($id) {
        $sql = "SELECT * FROM organizations WHERE id = ? LIMIT 1";
        return $this->db->fetch($sql, [$id]);
    }
    
    // 組織を作成
    public function create($data) {
        $sql = "INSERT INTO organizations (
                    name, 
                    code, 
                    parent_id, 
                    level, 
                    sort_order, 
                    description
                ) VALUES (?, ?, ?, ?, ?, ?)";
        
        // 親組織があれば、レベルを計算
        $level = 1;
        if (!empty($data['parent_id'])) {
            $parent = $this->getById($data['parent_id']);
            if ($parent) {
                $level = $parent['level'] + 1;
            }
        }
        
        // 同じ親を持つ組織のsort_orderの最大値を取得
        $maxSortSql = "SELECT MAX(sort_order) as max_sort FROM organizations WHERE parent_id " . 
                      (empty($data['parent_id']) ? "IS NULL" : "= ?");
        $params = empty($data['parent_id']) ? [] : [$data['parent_id']];
        $maxSort = $this->db->fetch($maxSortSql, $params);
        $sortOrder = ($maxSort && isset($maxSort['max_sort'])) ? $maxSort['max_sort'] + 1 : 1;
        
        $this->db->execute($sql, [
            $data['name'],
            $data['code'],
            empty($data['parent_id']) ? null : $data['parent_id'],
            $level,
            $sortOrder,
            $data['description'] ?? null
        ]);
        
        return $this->db->lastInsertId();
    }
    
    // 組織を更新
    public function update($id, $data) {
        // 名前とコードは必須
        if (empty($data['name']) || empty($data['code'])) {
            return false;
        }
        
        // 親組織の設定を確認（循環参照防止）
        if (!empty($data['parent_id'])) {
            // 自分自身を親にはできない
            if ($id == $data['parent_id']) {
                return false;
            }
            
            // 子孫組織を親にはできない
            $descendants = $this->getDescendants($id);
            foreach ($descendants as $descendant) {
                if ($descendant['id'] == $data['parent_id']) {
                    return false;
                }
            }
            
            // 親組織のレベルを取得
            $parent = $this->getById($data['parent_id']);
            $level = $parent ? $parent['level'] + 1 : 1;
        } else {
            $level = 1;
        }
        
        $sql = "UPDATE organizations SET 
                name = ?, 
                code = ?, 
                parent_id = ?, 
                level = ?,
                description = ?,
                updated_at = CURRENT_TIMESTAMP
                WHERE id = ?";
        
        return $this->db->execute($sql, [
            $data['name'],
            $data['code'],
            empty($data['parent_id']) ? null : $data['parent_id'],
            $level,
            $data['description'] ?? null,
            $id
        ]);
    }
    
    // 組織を削除
    public function delete($id) {
        // 子組織があるかチェック
        $children = $this->getChildren($id);
        if (!empty($children)) {
            return false; // 子組織がある場合は削除不可
        }
        
        // 所属ユーザーがいるかチェック
        $sql = "SELECT COUNT(*) as count FROM user_organizations WHERE organization_id = ?";
        $result = $this->db->fetch($sql, [$id]);
        if ($result && $result['count'] > 0) {
            return false; // 所属ユーザーがいる場合は削除不可
        }
        
        return $this->db->execute("DELETE FROM organizations WHERE id = ?", [$id]);
    }
    
    // 子組織を取得
    public function getChildren($parentId) {
        $sql = "SELECT * FROM organizations WHERE parent_id = ? ORDER BY sort_order, name";
        return $this->db->fetchAll($sql, [$parentId]);
    }
    
    // 全ての子孫組織を取得
    public function getDescendants($id) {
        $result = [];
        $children = $this->getChildren($id);
        
        foreach ($children as $child) {
            $result[] = $child;
            $descendants = $this->getDescendants($child['id']);
            foreach ($descendants as $descendant) {
                $result[] = $descendant;
            }
        }
        
        return $result;
    }
    
    // 組織コードの重複チェック
    public function isCodeUnique($code, $excludeId = null) {
        $sql = "SELECT COUNT(*) as count FROM organizations WHERE code = ?";
        $params = [$code];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $result = $this->db->fetch($sql, $params);
        return ($result['count'] == 0);
    }
    
    // 表示順を更新
    public function updateSortOrder($id, $newOrder) {
        $org = $this->getById($id);
        if (!$org) {
            return false;
        }
        
        // 同じ親を持つ組織のリストを取得
        $siblings = $this->getChildren($org['parent_id']);
        
        // 現在の位置を特定
        $currentIndex = -1;
        foreach ($siblings as $index => $sibling) {
            if ($sibling['id'] == $id) {
                $currentIndex = $index;
                break;
            }
        }
        
        if ($currentIndex == -1 || $newOrder < 0 || $newOrder >= count($siblings)) {
            return false;
        }
        
        // トランザクション開始
        $this->db->beginTransaction();
        
        try {
            // 移動方向に応じて処理
            if ($newOrder > $currentIndex) {
                // 下に移動
                for ($i = $currentIndex + 1; $i <= $newOrder; $i++) {
                    $this->db->execute(
                        "UPDATE organizations SET sort_order = sort_order - 1 WHERE id = ?",
                        [$siblings[$i]['id']]
                    );
                }
            } else {
                // 上に移動
                for ($i = $newOrder; $i < $currentIndex; $i++) {
                    $this->db->execute(
                        "UPDATE organizations SET sort_order = sort_order + 1 WHERE id = ?",
                        [$siblings[$i]['id']]
                    );
                }
            }
            
            // 対象組織の位置を更新
            $this->db->execute(
                "UPDATE organizations SET sort_order = ? WHERE id = ?",
                [$newOrder, $id]
            );
            
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
}
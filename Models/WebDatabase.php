<?php
// models/WebDatabase.php
namespace Models;

use Core\Database;

class WebDatabase
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * 全データベースを取得
     */
    public function getAll($page = 1, $limit = 20, $search = null)
    {
        $offset = ($page - 1) * $limit;
        $params = [];

        $sql = "SELECT d.*, u.display_name as creator_name 
                FROM web_databases d 
                LEFT JOIN users u ON d.creator_id = u.id ";

        // 検索条件
        if ($search) {
            $sql .= "WHERE d.name LIKE ? OR d.description LIKE ? ";
            $searchTerm = "%" . $search . "%";
            $params = [$searchTerm, $searchTerm];
        }

        $sql .= "ORDER BY d.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * データベース数を取得
     */
    public function getCount($search = null)
    {
        $sql = "SELECT COUNT(*) as count FROM web_databases";
        $params = [];

        // 検索条件
        if ($search) {
            $sql .= " WHERE name LIKE ? OR description LIKE ?";
            $searchTerm = "%" . $search . "%";
            $params = [$searchTerm, $searchTerm];
        }

        $result = $this->db->fetch($sql, $params);
        return $result['count'];
    }

    /**
     * 特定のデータベースを取得
     */
    public function getById($id)
    {
        $sql = "SELECT d.*, u.display_name as creator_name 
                FROM web_databases d 
                LEFT JOIN users u ON d.creator_id = u.id 
                WHERE d.id = ? LIMIT 1";
        return $this->db->fetch($sql, [$id]);
    }

    /**
     * データベースを作成
     */
    public function create($data)
    {
        $sql = "INSERT INTO web_databases (
                    name, 
                    description, 
                    icon, 
                    color, 
                    is_public, 
                    creator_id,
                    created_at
                ) VALUES (?, ?, ?, ?, ?, ?, NOW())";

        $this->db->execute($sql, [
            $data['name'],
            $data['description'] ?? null,
            $data['icon'] ?? 'database',
            $data['color'] ?? '#3498db',
            $data['is_public'] ?? 0,
            $data['creator_id']
        ]);

        return $this->db->lastInsertId();
    }

    /**
     * データベースを更新
     */
    public function update($id, $data)
    {
        $fields = [];
        $values = [];

        // 更新可能なフィールド
        $updateableFields = [
            'name',
            'description',
            'icon',
            'color',
            'is_public'
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

        $fields[] = "updated_at = NOW()";
        $values[] = $id; // WHEREの条件用

        $sql = "UPDATE web_databases SET " . implode(", ", $fields) . " WHERE id = ?";
        return $this->db->execute($sql, $values);
    }

    /**
     * データベースを削除
     */
    public function delete($id)
    {
        // 関連するレコードがあるか確認
        $sql = "SELECT COUNT(*) as count FROM web_database_records WHERE database_id = ?";
        $result = $this->db->fetch($sql, [$id]);

        if ($result['count'] > 0) {
            // レコードがある場合は関連するレコードも削除
            $this->db->execute("DELETE FROM web_database_record_data WHERE record_id IN (SELECT id FROM web_database_records WHERE database_id = ?)", [$id]);
            $this->db->execute("DELETE FROM web_database_records WHERE database_id = ?", [$id]);
        }

        // 関連するフィールドを削除
        $this->db->execute("DELETE FROM web_database_fields WHERE database_id = ?", [$id]);

        // データベースを削除
        return $this->db->execute("DELETE FROM web_databases WHERE id = ?", [$id]);
    }
}

/**
 * WEBデータベースフィールドモデル
 */
class WebDatabaseField
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * データベースに関連するフィールドを取得
     */
    public function getByDatabaseId($databaseId)
    {
        $sql = "SELECT * FROM web_database_fields WHERE database_id = ? ORDER BY sort_order";
        return $this->db->fetchAll($sql, [$databaseId]);
    }

    /**
     * 特定のフィールドを取得
     */
    public function getById($id)
    {
        $sql = "SELECT * FROM web_database_fields WHERE id = ? LIMIT 1";
        return $this->db->fetch($sql, [$id]);
    }

    /**
     * フィールドを作成
     */
    public function create($data)
    {
        // 同じデータベースのフィールドの最大並び順を取得
        $sql = "SELECT MAX(sort_order) as max_sort FROM web_database_fields WHERE database_id = ?";
        $result = $this->db->fetch($sql, [$data['database_id']]);
        $sortOrder = ($result && isset($result['max_sort'])) ? $result['max_sort'] + 1 : 1;

        $sql = "INSERT INTO web_database_fields (
                    database_id, 
                    name, 
                    description, 
                    type, 
                    options, 
                    required, 
                    unique_value, 
                    default_value, 
                    validation, 
                    sort_order,
                    is_title_field,
                    is_filterable,
                    is_sortable,
                    created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $this->db->execute($sql, [
            $data['database_id'],
            $data['name'],
            $data['description'] ?? null,
            $data['type'],
            $data['options'] ?? null,
            $data['required'] ?? 0,
            $data['unique_value'] ?? 0,
            $data['default_value'] ?? null,
            $data['validation'] ?? null,
            $sortOrder,
            $data['is_title_field'] ?? 0,
            $data['is_filterable'] ?? 0,
            $data['is_sortable'] ?? 0
        ]);

        return $this->db->lastInsertId();
    }

    /**
     * フィールドを更新
     */
    public function update($id, $data)
    {
        $fields = [];
        $values = [];

        // 更新可能なフィールド
        $updateableFields = [
            'name',
            'description',
            'type',
            'options',
            'required',
            'unique_value',
            'default_value',
            'validation',
            'sort_order',
            'is_title_field',
            'is_filterable',
            'is_sortable'
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

        $fields[] = "updated_at = NOW()";
        $values[] = $id; // WHEREの条件用

        $sql = "UPDATE web_database_fields SET " . implode(", ", $fields) . " WHERE id = ?";
        return $this->db->execute($sql, $values);
    }

    /**
     * フィールドを削除
     */
    public function delete($id)
    {
        // 関連するフィールドデータを削除
        $this->db->execute("DELETE FROM web_database_record_data WHERE field_id = ?", [$id]);

        // フィールドを削除
        return $this->db->execute("DELETE FROM web_database_fields WHERE id = ?", [$id]);
    }

    /**
     * フィールドの並び順を更新
     */
    public function updateSortOrder($id, $sortOrder)
    {
        $sql = "UPDATE web_database_fields SET sort_order = ?, updated_at = NOW() WHERE id = ?";
        return $this->db->execute($sql, [$sortOrder, $id]);
    }
}

/**
 * WEBデータベースレコードモデル
 */
class WebDatabaseRecord
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * データベースに関連するレコードを取得
     */
    public function getByDatabaseId($databaseId, $page = 1, $limit = 20, $search = null, $filters = [], $sort = null, $order = 'asc')
    {
        $offset = ($page - 1) * $limit;
        $params = [$databaseId];

        // 基本クエリ
        $sql = "SELECT r.*, u.display_name as creator_name 
                FROM web_database_records r 
                LEFT JOIN users u ON r.creator_id = u.id 
                WHERE r.database_id = ? ";

        // 検索条件
        if ($search) {
            // タイトルフィールドを取得して検索
            $titleFieldSql = "SELECT id FROM web_database_fields WHERE database_id = ? AND is_title_field = 1 LIMIT 1";
            $titleField = $this->db->fetch($titleFieldSql, [$databaseId]);

            if ($titleField) {
                $sql .= "AND r.id IN (
                    SELECT record_id FROM web_database_record_data 
                    WHERE field_id = ? AND value LIKE ?
                ) ";
                $params[] = $titleField['id'];
                $params[] = "%" . $search . "%";
            }
        }

        // フィルター条件
        if (!empty($filters) && is_array($filters)) {
            foreach ($filters as $fieldId => $value) {
                $sql .= "AND r.id IN (
                    SELECT record_id FROM web_database_record_data 
                    WHERE field_id = ? AND value = ?
                ) ";
                $params[] = $fieldId;
                $params[] = $value;
            }
        }

        // ソート条件
        if ($sort) {
            $orderBy = "r.created_at"; // デフォルト

            // ソートするフィールドが指定されている場合
            if (is_numeric($sort)) {
                $orderBy = "(SELECT value FROM web_database_record_data WHERE record_id = r.id AND field_id = ?)";
                $params[] = $sort;
            }

            $sql .= "ORDER BY $orderBy " . ($order === 'asc' ? 'ASC' : 'DESC') . " ";
        } else {
            $sql .= "ORDER BY r.created_at DESC ";
        }

        $sql .= "LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $records = $this->db->fetchAll($sql, $params);

        // タイトルフィールド値を取得
        foreach ($records as &$record) {
            $record['title'] = $this->getRecordTitle($record['id'], $databaseId);
        }

        return $records;
    }

    /**
     * データベースのレコード数を取得
     */
    public function getCountByDatabaseId($databaseId, $search = null, $filters = [])
    {
        $params = [$databaseId];

        $sql = "SELECT COUNT(*) as count FROM web_database_records WHERE database_id = ?";

        // 検索条件
        if ($search) {
            // タイトルフィールドを取得して検索
            $titleFieldSql = "SELECT id FROM web_database_fields WHERE database_id = ? AND is_title_field = 1 LIMIT 1";
            $titleField = $this->db->fetch($titleFieldSql, [$databaseId]);

            if ($titleField) {
                $sql .= " AND id IN (
                    SELECT record_id FROM web_database_record_data 
                    WHERE field_id = ? AND value LIKE ?
                )";
                $params[] = $titleField['id'];
                $params[] = "%" . $search . "%";
            }
        }

        // フィルター条件
        if (!empty($filters) && is_array($filters)) {
            foreach ($filters as $fieldId => $value) {
                $sql .= " AND id IN (
                    SELECT record_id FROM web_database_record_data 
                    WHERE field_id = ? AND value = ?
                )";
                $params[] = $fieldId;
                $params[] = $value;
            }
        }

        $result = $this->db->fetch($sql, $params);
        return $result['count'];
    }

    /**
     * 特定のレコードを取得
     */
    public function getById($id)
    {
        $sql = "SELECT r.*, u.display_name as creator_name, 
                    u2.display_name as updater_name
                FROM web_database_records r 
                LEFT JOIN users u ON r.creator_id = u.id 
                LEFT JOIN users u2 ON r.updater_id = u2.id 
                WHERE r.id = ? LIMIT 1";

        $record = $this->db->fetch($sql, [$id]);

        if ($record) {
            $record['title'] = $this->getRecordTitle($id, $record['database_id']);
        }

        return $record;
    }

    /**
     * レコードのタイトルを取得
     */
    private function getRecordTitle($recordId, $databaseId)
    {
        // タイトルフィールドを取得
        $titleFieldSql = "SELECT id FROM web_database_fields WHERE database_id = ? AND is_title_field = 1 LIMIT 1";
        $titleField = $this->db->fetch($titleFieldSql, [$databaseId]);

        if (!$titleField) {
            return "ID: " . $recordId; // タイトルフィールドがない場合はID
        }

        // タイトルフィールドの値を取得
        $dataSql = "SELECT value FROM web_database_record_data WHERE record_id = ? AND field_id = ? LIMIT 1";
        $data = $this->db->fetch($dataSql, [$recordId, $titleField['id']]);

        return $data ? $data['value'] : "ID: " . $recordId;
    }

    /**
     * レコードを作成
     */
    public function create($data)
    {
        $sql = "INSERT INTO web_database_records (
                    database_id, 
                    creator_id,
                    created_at
                ) VALUES (?, ?, NOW())";

        $this->db->execute($sql, [
            $data['database_id'],
            $data['creator_id']
        ]);

        return $this->db->lastInsertId();
    }

    /**
     * レコードを更新
     */
    public function update($id, $data)
    {
        $sql = "UPDATE web_database_records SET 
                updater_id = ?,
                updated_at = NOW() 
                WHERE id = ?";

        return $this->db->execute($sql, [
            $data['updater_id'],
            $id
        ]);
    }

    /**
     * レコードを削除
     */
    public function delete($id)
    {
        // 関連するフィールドデータを削除
        $this->db->execute("DELETE FROM web_database_record_data WHERE record_id = ?", [$id]);

        // レコードを削除
        return $this->db->execute("DELETE FROM web_database_records WHERE id = ?", [$id]);
    }

    /**
     * レコードのフィールドデータを取得
     */
    public function getRecordData($recordId)
    {
        $sql = "SELECT field_id, value, file_info FROM web_database_record_data WHERE record_id = ?";
        $results = $this->db->fetchAll($sql, [$recordId]);

        $data = [];
        foreach ($results as $row) {
            $data[$row['field_id']] = $row['file_info'] ? json_decode($row['file_info'], true) : $row['value'];
        }

        return $data;
    }

    /**
     * レコードのフィールドデータを保存
     */
    public function saveFieldData($recordId, $fieldId, $value, $fileInfo = null)
    {
        // 既存データがあるか確認
        $sql = "SELECT id FROM web_database_record_data WHERE record_id = ? AND field_id = ? LIMIT 1";
        $existing = $this->db->fetch($sql, [$recordId, $fieldId]);

        if ($existing) {
            // 更新
            $sql = "UPDATE web_database_record_data SET value = ?, file_info = ? WHERE record_id = ? AND field_id = ?";
            return $this->db->execute($sql, [
                $value,
                $fileInfo ? json_encode($fileInfo) : null,
                $recordId,
                $fieldId
            ]);
        } else {
            // 新規作成
            $sql = "INSERT INTO web_database_record_data (record_id, field_id, value, file_info) VALUES (?, ?, ?, ?)";
            return $this->db->execute($sql, [
                $recordId,
                $fieldId,
                $value,
                $fileInfo ? json_encode($fileInfo) : null
            ]);
        }
    }

    /**
     * データベースに関連するレコードを全件取得（CSV出力用）
     */
    public function getAllByDatabaseId($databaseId, $search = null, $filters = [])
    {
        $params = [$databaseId];

        // 基本クエリ
        $sql = "SELECT r.*, u.display_name as creator_name 
                FROM web_database_records r 
                LEFT JOIN users u ON r.creator_id = u.id 
                WHERE r.database_id = ? ";

        // 検索条件
        if ($search) {
            // タイトルフィールドを取得して検索
            $titleFieldSql = "SELECT id FROM web_database_fields WHERE database_id = ? AND is_title_field = 1 LIMIT 1";
            $titleField = $this->db->fetch($titleFieldSql, [$databaseId]);

            if ($titleField) {
                $sql .= "AND r.id IN (
                    SELECT record_id FROM web_database_record_data 
                    WHERE field_id = ? AND value LIKE ?
                ) ";
                $params[] = $titleField['id'];
                $params[] = "%" . $search . "%";
            }
        }

        // フィルター条件
        if (!empty($filters) && is_array($filters)) {
            foreach ($filters as $fieldId => $value) {
                $sql .= "AND r.id IN (
                    SELECT record_id FROM web_database_record_data 
                    WHERE field_id = ? AND value = ?
                ) ";
                $params[] = $fieldId;
                $params[] = $value;
            }
        }

        $sql .= "ORDER BY r.created_at DESC";

        $records = $this->db->fetchAll($sql, $params);

        // タイトルフィールド値を取得
        foreach ($records as &$record) {
            $record['title'] = $this->getRecordTitle($record['id'], $databaseId);
        }

        return $records;
    }
}

<?php
namespace Models;

use Core\Database;

class PortalLink
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function all($includeInactive = true)
    {
        $where = $includeInactive ? '' : 'WHERE is_active = 1';
        return $this->db->fetchAll(
            "SELECT * FROM portal_links {$where} ORDER BY sort_order ASC, title ASC"
        );
    }

    public function find($id)
    {
        return $this->db->fetch('SELECT * FROM portal_links WHERE id = ? LIMIT 1', [(int)$id]);
    }

    public function create(array $data)
    {
        $sql = "INSERT INTO portal_links
            (title, url, description, icon_class, target, sort_order, is_active, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $ok = $this->db->execute($sql, [
            $data['title'],
            $data['url'],
            $data['description'],
            $data['icon_class'],
            $data['target'],
            (int)$data['sort_order'],
            (int)$data['is_active'],
            $data['created_by'] ?: null,
        ]);

        return $ok ? (int)$this->db->lastInsertId() : 0;
    }

    public function update($id, array $data)
    {
        $sql = "UPDATE portal_links
            SET title = ?, url = ?, description = ?, icon_class = ?, target = ?, sort_order = ?, is_active = ?
            WHERE id = ?";
        return $this->db->execute($sql, [
            $data['title'],
            $data['url'],
            $data['description'],
            $data['icon_class'],
            $data['target'],
            (int)$data['sort_order'],
            (int)$data['is_active'],
            (int)$id,
        ]);
    }

    public function delete($id)
    {
        return $this->db->execute('DELETE FROM portal_links WHERE id = ?', [(int)$id]);
    }
}

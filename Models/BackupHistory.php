<?php

namespace Models;

use Core\Database;

class BackupHistory
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function createRunning($userId)
    {
        $sql = 'INSERT INTO system_backups (executed_by, status, started_at, created_at, updated_at) VALUES (?, ?, NOW(), NOW(), NOW())';
        $this->db->execute($sql, [(int)$userId, 'running']);

        return (int)$this->db->lastInsertId();
    }

    public function markSuccess($id, $fileName, $filePath, $fileSize)
    {
        $sql = 'UPDATE system_backups
                SET status = ?, file_name = ?, file_path = ?, file_size = ?, error_message = NULL, finished_at = NOW(), updated_at = NOW()
                WHERE id = ?';

        return $this->db->execute($sql, ['success', $fileName, $filePath, (int)$fileSize, (int)$id]);
    }

    public function markFailed($id, $error)
    {
        $sql = 'UPDATE system_backups
                SET status = ?, error_message = ?, finished_at = NOW(), updated_at = NOW()
                WHERE id = ?';

        return $this->db->execute($sql, ['failed', (string)$error, (int)$id]);
    }

    public function find($id)
    {
        $sql = 'SELECT b.*, u.display_name AS executed_by_name
                FROM system_backups b
                LEFT JOIN users u ON b.executed_by = u.id
                WHERE b.id = ?
                LIMIT 1';

        return $this->db->fetch($sql, [(int)$id]);
    }

    public function listRecent($limit = 50)
    {
        $limit = max(1, min(200, (int)$limit));

        $sql = 'SELECT b.*, u.display_name AS executed_by_name
                FROM system_backups b
                LEFT JOIN users u ON b.executed_by = u.id
                ORDER BY b.id DESC
                LIMIT ' . $limit;

        return $this->db->fetchAll($sql);
    }
}

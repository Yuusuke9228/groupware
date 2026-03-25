<?php
namespace Models;

use Core\Database;

class UnifiedSearch
{
    private $db;
    private $userModel;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->userModel = new User();
    }

    public function searchAll($userId, $keyword, $limitPerModule = 10)
    {
        $keyword = trim((string)$keyword);
        if ($keyword === '') {
            return [
                'messages' => [],
                'workflow' => [],
                'schedules' => [],
                'tasks' => [],
                'total' => 0
            ];
        }

        $q = '%' . $keyword . '%';
        $orgIds = $this->userModel->getUserOrganizationIds($userId);

        $messages = $this->searchMessages($userId, $q, $limitPerModule);
        $workflow = $this->searchWorkflow($userId, $q, $limitPerModule);
        $schedules = $this->searchSchedules($userId, $orgIds, $q, $limitPerModule);
        $tasks = $this->searchTasks($userId, $q, $limitPerModule);

        return [
            'messages' => $messages,
            'workflow' => $workflow,
            'schedules' => $schedules,
            'tasks' => $tasks,
            'total' => count($messages) + count($workflow) + count($schedules) + count($tasks)
        ];
    }

    private function searchMessages($userId, $q, $limit)
    {
        $sql = "SELECT DISTINCT m.id, m.subject AS title, m.body AS body, m.created_at,
                       su.display_name AS sender_name
                FROM messages m
                LEFT JOIN users su ON su.id = m.sender_id
                LEFT JOIN message_recipients mr
                    ON mr.message_id = m.id AND mr.user_id = ? AND mr.is_deleted = 0
                WHERE (mr.id IS NOT NULL OR m.sender_id = ?)
                  AND (m.subject LIKE ? OR m.body LIKE ?)
                ORDER BY m.created_at DESC
                LIMIT ?";

        $rows = $this->db->fetchAll($sql, [$userId, $userId, $q, $q, (int)$limit]);
        foreach ($rows as &$row) {
            $row['snippet'] = $this->snippet($row['body']);
            $row['link'] = '/messages/view/' . $row['id'];
        }

        return $rows;
    }

    private function searchWorkflow($userId, $q, $limit)
    {
        $sql = "SELECT DISTINCT wr.id, wr.title, wr.request_number, wr.status, wr.created_at,
                       wt.name AS template_name
                FROM workflow_requests wr
                JOIN workflow_templates wt ON wt.id = wr.template_id
                LEFT JOIN workflow_approvals wa
                    ON wa.request_id = wr.id AND wa.approver_id = ?
                WHERE (wr.requester_id = ? OR wa.id IS NOT NULL)
                  AND (
                        wr.title LIKE ?
                     OR wr.request_number LIKE ?
                     OR wr.id IN (
                        SELECT request_id
                        FROM workflow_request_data
                        WHERE value LIKE ?
                     )
                  )
                ORDER BY wr.created_at DESC
                LIMIT ?";

        $rows = $this->db->fetchAll($sql, [$userId, $userId, $q, $q, $q, (int)$limit]);
        foreach ($rows as &$row) {
            $row['snippet'] = $row['template_name'] . ' / ' . $row['request_number'] . ' / ' . $row['status'];
            $row['link'] = '/workflow/view/' . $row['id'];
        }

        return $rows;
    }

    private function searchSchedules($userId, $orgIds, $q, $limit)
    {
        $params = [$userId, $userId];
        $orgClause = '0';

        if (!empty($orgIds)) {
            $orgClause = implode(',', array_fill(0, count($orgIds), '?'));
            $params = array_merge($params, $orgIds, $orgIds);
        }

        $params = array_merge($params, [$q, $q, $q, (int)$limit]);

        $sql = "SELECT DISTINCT s.id, s.title, s.description, s.location, s.start_time, s.end_time
                FROM schedules s
                LEFT JOIN schedule_participants sp
                    ON sp.schedule_id = s.id AND sp.user_id = ?
                LEFT JOIN schedule_organizations so ON so.schedule_id = s.id
                LEFT JOIN user_organizations uo_creator ON uo_creator.user_id = s.creator_id
                WHERE (
                        s.creator_id = ?
                     OR sp.user_id IS NOT NULL
                     OR s.visibility = 'public'
                     OR so.organization_id IN ({$orgClause})
                     OR uo_creator.organization_id IN ({$orgClause})
                )
                  AND (
                        s.title LIKE ?
                     OR COALESCE(s.description, '') LIKE ?
                     OR COALESCE(s.location, '') LIKE ?
                  )
                ORDER BY s.start_time DESC
                LIMIT ?";

        $rows = $this->db->fetchAll($sql, $params);
        foreach ($rows as &$row) {
            $row['snippet'] = date('Y/m/d H:i', strtotime($row['start_time'])) . ' - ' . $this->snippet(($row['location'] ?? '') . ' ' . ($row['description'] ?? ''), 80);
            $row['link'] = '/schedule/view/' . $row['id'];
        }

        return $rows;
    }

    private function searchTasks($userId, $q, $limit)
    {
        $sql = "SELECT DISTINCT c.id, c.title, c.description, c.status, c.due_date, c.updated_at,
                       b.name AS board_name
                FROM task_cards c
                JOIN task_lists l ON l.id = c.list_id
                JOIN task_boards b ON b.id = l.board_id
                LEFT JOIN task_assignees ta ON ta.card_id = c.id
                LEFT JOIN task_board_members tbm ON tbm.board_id = b.id
                WHERE (ta.user_id = ? OR tbm.user_id = ? OR c.created_by = ?)
                  AND (c.title LIKE ? OR COALESCE(c.description, '') LIKE ?)
                ORDER BY c.updated_at DESC
                LIMIT ?";

        $rows = $this->db->fetchAll($sql, [$userId, $userId, $userId, $q, $q, (int)$limit]);
        foreach ($rows as &$row) {
            $row['snippet'] = $row['board_name'] . ' / ' . ($row['due_date'] ?: '期限なし') . ' / ' . $row['status'];
            $row['link'] = '/task/card/' . $row['id'];
        }

        return $rows;
    }

    private function snippet($text, $length = 120)
    {
        $text = trim(strip_tags((string)$text));
        if ($text === '') {
            return '';
        }

        return mb_strlen($text) > $length ? mb_substr($text, 0, $length) . '...' : $text;
    }
}

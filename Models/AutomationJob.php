<?php
namespace Models;

use Core\Database;

class AutomationJob
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getAll($userId, $isAdmin = false)
    {
        $params = [];
        $where = '';

        if (!$isAdmin) {
            $where = 'WHERE created_by = ?';
            $params[] = $userId;
        }

        $sql = "SELECT j.*, u.display_name AS creator_name
                FROM automation_jobs j
                JOIN users u ON u.id = j.created_by
                {$where}
                ORDER BY j.id DESC";

        return $this->db->fetchAll($sql, $params);
    }

    public function getById($id)
    {
        return $this->db->fetch("SELECT * FROM automation_jobs WHERE id = ?", [$id]);
    }

    public function create($data, $userId)
    {
        $name = trim((string)($data['name'] ?? ''));
        $jobType = $data['job_type'] ?? '';
        $frequency = $data['frequency'] ?? 'daily';
        $runAt = $data['run_at'] ?? '09:00:00';
        $weekday = isset($data['weekday']) ? (int)$data['weekday'] : null;
        $dayOfMonth = isset($data['day_of_month']) ? (int)$data['day_of_month'] : null;
        $config = $data['config_json'] ?? [];

        if ($name === '' || $jobType === '') {
            return false;
        }

        if (is_array($config)) {
            $config = json_encode($config, JSON_UNESCAPED_UNICODE);
        }

        $nextRun = $this->calculateNextRunAt($frequency, $runAt, $weekday, $dayOfMonth);

        $sql = "INSERT INTO automation_jobs (
                    name, job_type, frequency, run_at, weekday, day_of_month,
                    config_json, is_active, created_by, next_run_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, ?)";

        $ok = $this->db->execute($sql, [
            $name,
            $jobType,
            $frequency,
            $runAt,
            $weekday,
            $dayOfMonth,
            $config,
            $userId,
            $nextRun
        ]);

        return $ok ? $this->db->lastInsertId() : false;
    }

    public function toggle($id, $isActive, $userId, $isAdmin = false)
    {
        $job = $this->getById($id);
        if (!$job) {
            return false;
        }

        if (!$isAdmin && (int)$job['created_by'] !== (int)$userId) {
            return false;
        }

        $nextRun = $job['next_run_at'];
        if ($isActive) {
            $nextRun = $this->calculateNextRunAt(
                $job['frequency'],
                $job['run_at'],
                $job['weekday'],
                $job['day_of_month']
            );
        }

        return $this->db->execute(
            "UPDATE automation_jobs SET is_active = ?, next_run_at = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
            [$isActive ? 1 : 0, $nextRun, $id]
        );
    }

    public function runDueJobs($limit = 20)
    {
        $jobs = $this->db->fetchAll(
            "SELECT * FROM automation_jobs
             WHERE is_active = 1
               AND next_run_at IS NOT NULL
               AND next_run_at <= NOW()
             ORDER BY next_run_at ASC
             LIMIT ?",
            [(int)$limit]
        );

        $summary = [
            'processed' => 0,
            'success' => 0,
            'failed' => 0,
            'details' => []
        ];

        foreach ($jobs as $job) {
            $summary['processed']++;
            $result = $this->executeJob($job);

            $this->db->execute(
                "INSERT INTO automation_job_runs (job_id, status, message, created_at)
                 VALUES (?, ?, ?, NOW())",
                [$job['id'], $result['success'] ? 'success' : 'failed', $result['message']]
            );

            if ($result['success']) {
                $summary['success']++;
            } else {
                $summary['failed']++;
            }

            $nextRun = $this->calculateNextRunAt(
                $job['frequency'],
                $job['run_at'],
                $job['weekday'],
                $job['day_of_month']
            );

            $this->db->execute(
                "UPDATE automation_jobs
                 SET last_run_at = NOW(), next_run_at = ?, updated_at = CURRENT_TIMESTAMP
                 WHERE id = ?",
                [$nextRun, $job['id']]
            );

            $summary['details'][] = [
                'job_id' => $job['id'],
                'name' => $job['name'],
                'success' => $result['success'],
                'message' => $result['message']
            ];
        }

        return $summary;
    }

    public function runJobNow($id, $userId, $isAdmin = false)
    {
        $job = $this->getById($id);
        if (!$job) {
            return ['success' => false, 'message' => 'ジョブが見つかりません'];
        }
        if (!$isAdmin && (int)$job['created_by'] !== (int)$userId) {
            return ['success' => false, 'message' => '権限がありません'];
        }

        $result = $this->executeJob($job);

        $this->db->execute(
            "INSERT INTO automation_job_runs (job_id, status, message, created_at)
             VALUES (?, ?, ?, NOW())",
            [$job['id'], $result['success'] ? 'success' : 'failed', $result['message']]
        );

        return $result;
    }

    private function executeJob($job)
    {
        $config = [];
        if (!empty($job['config_json'])) {
            $decoded = json_decode($job['config_json'], true);
            if (is_array($decoded)) {
                $config = $decoded;
            }
        }

        switch ($job['job_type']) {
            case 'periodic_request':
                return $this->executePeriodicWorkflowRequest($job, $config);
            case 'periodic_report':
                return $this->executePeriodicReport($job, $config);
            case 'deadline_reminder':
                return $this->executeDeadlineReminder($job, $config);
            default:
                return ['success' => false, 'message' => '未対応のジョブタイプです'];
        }
    }

    private function executePeriodicWorkflowRequest($job, $config)
    {
        $templateId = (int)($config['template_id'] ?? 0);
        $requesterId = (int)($config['requester_id'] ?? $job['created_by']);
        if ($templateId <= 0 || $requesterId <= 0) {
            return ['success' => false, 'message' => 'template_id / requester_id が必要です'];
        }

        $workflow = new Workflow();
        $template = $workflow->getTemplateById($templateId);
        if (!$template) {
            return ['success' => false, 'message' => 'テンプレートが存在しません'];
        }

        $requestNumber = $workflow->generateRequestNumber($templateId);
        $titlePrefix = trim((string)($config['title_prefix'] ?? '定期申請'));
        $title = $titlePrefix . ' ' . date('Y/m/d');

        $ok = $this->db->execute(
            "INSERT INTO workflow_requests (request_number, template_id, title, status, current_step, requester_id, created_at, updated_at)
             VALUES (?, ?, ?, 'draft', NULL, ?, NOW(), NOW())",
            [$requestNumber, $templateId, $title, $requesterId]
        );

        if (!$ok) {
            return ['success' => false, 'message' => '定期申請の下書き作成に失敗しました'];
        }

        $requestId = $this->db->lastInsertId();
        $notification = new Notification();
        $notification->create([
            'user_id' => $requesterId,
            'type' => 'workflow',
            'title' => '定期申請の下書きを作成しました',
            'content' => $title,
            'link' => '/workflow/edit/' . $requestId,
            'reference_id' => $requestId,
            'reference_type' => 'workflow_request'
        ]);

        return ['success' => true, 'message' => '定期申請下書きを作成しました'];
    }

    private function executePeriodicReport($job, $config)
    {
        $templateId = (int)($config['template_id'] ?? 0);
        $userId = (int)($config['user_id'] ?? $job['created_by']);
        if ($templateId <= 0 || $userId <= 0) {
            return ['success' => false, 'message' => 'template_id / user_id が必要です'];
        }

        $template = $this->db->fetch("SELECT * FROM daily_report_templates WHERE id = ?", [$templateId]);
        if (!$template) {
            return ['success' => false, 'message' => '日報テンプレートが存在しません'];
        }

        $reportDate = date('Y-m-d');
        $exists = $this->db->fetch(
            "SELECT id FROM daily_reports WHERE user_id = ? AND report_date = ? LIMIT 1",
            [$userId, $reportDate]
        );

        if ($exists) {
            return ['success' => true, 'message' => '本日の日報が既に存在するためスキップしました'];
        }

        $ok = $this->db->execute(
            "INSERT INTO daily_reports (user_id, report_date, title, content, status, created_at, updated_at)
             VALUES (?, ?, ?, ?, 'draft', NOW(), NOW())",
            [$userId, $reportDate, $template['title'], $template['content']]
        );

        if (!$ok) {
            return ['success' => false, 'message' => '定期日報の下書き作成に失敗しました'];
        }

        $reportId = $this->db->lastInsertId();
        $notification = new Notification();
        $notification->create([
            'user_id' => $userId,
            'type' => 'system',
            'title' => '定期日報の下書きを作成しました',
            'content' => $template['title'],
            'link' => '/daily-report/edit/' . $reportId,
            'reference_id' => $reportId,
            'reference_type' => 'daily_report'
        ]);

        return ['success' => true, 'message' => '定期日報の下書きを作成しました'];
    }

    private function executeDeadlineReminder($job, $config)
    {
        $days = max(0, (int)($config['days_before'] ?? 1));
        $targetDate = date('Y-m-d', strtotime('+' . $days . ' day'));

        $tasks = $this->db->fetchAll(
            "SELECT c.id, c.title, c.due_date, a.user_id
             FROM task_cards c
             JOIN task_assignees a ON a.card_id = c.id
             WHERE c.due_date = ?
               AND c.status <> 'completed'",
            [$targetDate]
        );

        if (empty($tasks)) {
            return ['success' => true, 'message' => '期限リマインド対象はありません'];
        }

        $notification = new Notification();
        foreach ($tasks as $task) {
            $notification->create([
                'user_id' => $task['user_id'],
                'type' => 'system',
                'title' => 'タスク期限リマインド',
                'content' => $task['title'] . ' の期限は ' . $task['due_date'] . ' です。',
                'link' => '/task/card/' . $task['id'],
                'reference_id' => $task['id'],
                'reference_type' => 'task_card'
            ]);
        }

        return ['success' => true, 'message' => count($tasks) . ' 件の期限リマインドを送信しました'];
    }

    private function calculateNextRunAt($frequency, $runAt, $weekday = null, $dayOfMonth = null)
    {
        $now = new \DateTime();
        $parts = explode(':', (string)$runAt);
        $hour = (int)($parts[0] ?? 9);
        $minute = (int)($parts[1] ?? 0);
        $second = (int)($parts[2] ?? 0);

        $next = new \DateTime();
        $next->setTime($hour, $minute, $second);

        if ($frequency === 'weekly') {
            $targetWeekday = max(1, min(7, (int)$weekday));
            while ((int)$next->format('N') !== $targetWeekday || $next <= $now) {
                $next->modify('+1 day');
            }
        } elseif ($frequency === 'monthly') {
            $targetDay = max(1, min(28, (int)$dayOfMonth));
            $next->setDate((int)$now->format('Y'), (int)$now->format('m'), $targetDay);
            if ($next <= $now) {
                $next->modify('first day of next month');
                $next->setDate((int)$next->format('Y'), (int)$next->format('m'), $targetDay);
                $next->setTime($hour, $minute, $second);
            }
        } else {
            if ($next <= $now) {
                $next->modify('+1 day');
            }
        }

        return $next->format('Y-m-d H:i:s');
    }
}

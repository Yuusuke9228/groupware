<?php

namespace Services;

use Core\Database;
use Models\DailyReport;
use Models\Task;
use Models\Team;
use Models\Workflow;

class DemoDataService
{
    private const DEMO_PREFIX = '[DEMO-AUTO]';

    private $db;
    private $dailyReportModel;
    private $taskModel;
    private $teamModel;
    private $workflowModel;
    private $tableExistsCache = [];
    private $columnExistsCache = [];
    private $verbose = false;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->dailyReportModel = new DailyReport();
        $this->taskModel = new Task();
        $this->teamModel = new Team();
        $this->workflowModel = new Workflow();
        $this->verbose = getenv('DEMO_DATA_VERBOSE') === '1';
        if ($this->verbose) {
            $row = $this->db->fetch('SELECT CONNECTION_ID() AS cid');
            fwrite(STDERR, "[demo-data] mysql-connection " . (int)($row['cid'] ?? 0) . "\n");
        }
    }

    public function refreshFutureDemoData($actorId, $years = 3)
    {
        return $this->run('refresh', (int)$actorId, (int)$years);
    }

    public function rebuildAllDemoData($actorId, $years = 3)
    {
        return $this->run('rebuild', (int)$actorId, (int)$years);
    }

    private function run($mode, $actorId, $years)
    {
        $years = max(1, min(5, (int)$years));
        $startDate = new \DateTimeImmutable('today');
        $endDate = $startDate->modify('+' . $years . ' years')->modify('-1 day');

        $users = $this->getSeedUsers();
        if (empty($users)) {
            return ['success' => false, 'error' => '有効なユーザーが存在しないためデモデータを作成できません'];
        }

        $actorId = $actorId > 0 ? $actorId : (int)$users[0]['id'];
        $context = $this->buildContext($users, $startDate, $endDate, $years, $actorId);
        $context['mode'] = $mode;

        $result = [
            'success' => true,
            'mode' => $mode,
            'range' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
                'years' => $years
            ],
            'stats' => [],
            'warnings' => []
        ];

        $this->db->execute('SET FOREIGN_KEY_CHECKS = 0');
        try {
            if ($mode === 'rebuild') {
                $this->collectStep($result, 'wipe', function () {
                    return $this->wipeFunctionalData();
                });
                $this->collectStep($result, 'baseline_sql', function () {
                    return $this->seedBaselineSql();
                });
            } else {
                $this->collectStep($result, 'cleanup', function () use ($startDate, $endDate) {
                    return $this->cleanupAutoDemoData($startDate, $endDate);
                });
            }

            $this->collectStep($result, 'daily_report_masters', function () use (&$context) {
                return $this->ensureDailyReportMasters($context);
            });
            $this->collectStep($result, 'schedule', function () use (&$context) {
                return $this->seedSchedules($context);
            });
            $this->collectStep($result, 'task', function () use (&$context) {
                return $this->seedTasks($context);
            });
            $this->collectStep($result, 'daily_report', function () use (&$context) {
                return $this->seedDailyReports($context);
            });
            $this->collectStep($result, 'workflow', function () use (&$context) {
                return $this->seedWorkflow($context);
            });
            $this->collectStep($result, 'bulletin', function () use (&$context) {
                return $this->seedBulletin($context);
            });
            $this->collectStep($result, 'message', function () use (&$context) {
                return $this->seedMessages($context);
            });
            $this->collectStep($result, 'facility', function () use (&$context) {
                return $this->seedFacilitiesAndReservations($context);
            });
            $this->collectStep($result, 'address_book', function () use (&$context) {
                return $this->seedAddressBook($context);
            });
            $this->collectStep($result, 'files', function () use (&$context) {
                return $this->seedFileEntries($context);
            });
            $this->collectStep($result, 'webdatabase', function () use (&$context) {
                return $this->seedWebDatabase($context);
            });
            $this->collectStep($result, 'notifications', function () use (&$context) {
                return $this->seedNotifications($context);
            });
        } finally {
            $this->db->execute('SET FOREIGN_KEY_CHECKS = 1');
        }

        return $result;
    }

    private function collectStep(array &$result, $name, callable $callback)
    {
        try {
            if ($this->verbose) {
                fwrite(STDERR, "[demo-data] start {$name}\n");
            }
            $result['stats'][$name] = $callback();
            if ($this->verbose) {
                fwrite(STDERR, "[demo-data] done {$name}\n");
            }
        } catch (\Throwable $e) {
            $result['success'] = false;
            $result['stats'][$name] = ['error' => 'failed'];
            $result['warnings'][] = $name . ': ' . $e->getMessage();
            error_log('DemoDataService step failed [' . $name . ']: ' . $e->getMessage());
            if ($this->verbose) {
                fwrite(STDERR, "[demo-data] fail {$name}: {$e->getMessage()}\n");
            }
        }
    }

    private function getSeedUsers()
    {
        $rows = $this->db->fetchAll(
            "SELECT id, username, display_name, role
             FROM users
             WHERE status = 'active'
             ORDER BY FIELD(role, 'admin', 'manager', 'user'), id ASC
             LIMIT 8"
        );

        return is_array($rows) ? $rows : [];
    }

    private function buildContext(array $users, \DateTimeImmutable $startDate, \DateTimeImmutable $endDate, $years, $actorId)
    {
        $userIds = array_map(static function ($row) {
            return (int)$row['id'];
        }, $users);

        $userPrimaryOrg = [];
        if ($this->hasTable('user_organizations') && !empty($userIds)) {
            $placeholders = implode(',', array_fill(0, count($userIds), '?'));
            $rows = $this->db->fetchAll(
                "SELECT user_id, organization_id, is_primary
                 FROM user_organizations
                 WHERE user_id IN ({$placeholders})
                 ORDER BY user_id, is_primary DESC, organization_id ASC",
                $userIds
            );
            foreach ($rows as $row) {
                $uid = (int)$row['user_id'];
                if (!isset($userPrimaryOrg[$uid])) {
                    $userPrimaryOrg[$uid] = (int)$row['organization_id'];
                }
            }
        }

        $orgRows = $this->hasTable('organizations')
            ? $this->db->fetchAll("SELECT id, name FROM organizations ORDER BY id ASC")
            : [];
        $orgIds = array_map(static function ($row) {
            return (int)$row['id'];
        }, $orgRows);

        return [
            'users' => $users,
            'user_ids' => $userIds,
            'user_primary_org' => $userPrimaryOrg,
            'org_ids' => $orgIds,
            'start' => $startDate,
            'end' => $endDate,
            'years' => $years,
            'actor_id' => $actorId,
            'prefix' => self::DEMO_PREFIX,
            'schedule_map' => [],
            'task_card_ids' => [],
            'daily_master' => []
        ];
    }

    private function wipeFunctionalData()
    {
        $tables = [
            'workflow_attachments', 'workflow_comments', 'workflow_approvals', 'workflow_request_data', 'workflow_requests',
            'workflow_route_definitions', 'workflow_form_definitions', 'workflow_template_organizations', 'workflow_templates', 'workflow_delegates',
            'daily_report_attachments', 'daily_report_analysis_entries', 'daily_report_activity_logs', 'daily_report_tasks',
            'daily_report_schedules', 'daily_report_permissions', 'daily_report_tag_relations', 'daily_report_likes',
            'daily_report_comments', 'daily_report_reads', 'daily_reports', 'daily_report_tags', 'daily_report_template_sections',
            'daily_report_template_organizations', 'daily_report_templates', 'daily_report_monthly_targets',
            'daily_report_projects', 'daily_report_products', 'daily_report_industries', 'daily_report_processes',
            'task_activities', 'task_checklist_items', 'task_checklists', 'task_card_labels', 'task_assignees',
            'task_comments', 'task_attachments', 'task_cards', 'task_labels', 'task_board_members', 'task_lists',
            'task_boards', 'team_members', 'teams',
            'schedule_participants', 'schedule_organizations', 'schedules',
            'message_attachments', 'message_organizations', 'message_recipients', 'messages',
            'bulletin_attachments', 'bulletin_post_reads', 'bulletin_comments', 'bulletin_post_targets', 'bulletin_posts', 'bulletin_categories',
            'facility_reservations', 'facilities',
            'file_approval_steps', 'file_approval_requests', 'file_checkout_history', 'file_versions', 'file_permissions', 'file_entries', 'file_folders',
            'business_cards', 'address_book',
            'web_database_relations', 'web_database_record_data', 'web_database_records', 'web_database_form_layouts',
            'web_database_views', 'web_database_permissions', 'web_database_fields', 'web_databases',
            'notifications'
        ];

        $stats = ['tables' => 0, 'deleted' => 0];

        foreach ($tables as $table) {
            if (!$this->hasTable($table)) {
                continue;
            }

            $stats['tables']++;
            try {
                $this->db->execute("TRUNCATE TABLE {$table}");
            } catch (\Throwable $e) {
                $this->db->execute("DELETE FROM {$table}");
            }
            $stats['deleted']++;
        }

        return $stats;
    }

    private function cleanupAutoDemoData(\DateTimeImmutable $startDate, \DateTimeImmutable $endDate)
    {
        $prefixLike = self::DEMO_PREFIX . '%';

        $stats = [
            'schedules' => $this->deleteSchedulesByPrefix($prefixLike, $startDate, $endDate),
            'daily_reports' => $this->deleteDailyReportsByPrefix($prefixLike, $startDate, $endDate),
            'tasks' => $this->deleteTaskBoardsByPrefix($prefixLike),
            'workflows' => $this->deleteWorkflowByPrefix($prefixLike),
            'bulletins' => $this->deleteBulletinsByPrefix($prefixLike),
            'messages' => $this->deleteMessagesByPrefix($prefixLike),
            'reservations' => $this->deleteFacilityReservationsByPrefix($prefixLike),
            'address' => $this->deleteAddressByPrefix($prefixLike),
            'files' => $this->deleteFilesByPrefix($prefixLike),
            'webdb' => $this->deleteWebDatabasesByPrefix($prefixLike),
            'notifications' => $this->deleteNotificationsByPrefix($prefixLike)
        ];

        return $stats;
    }

    private function seedBaselineSql()
    {
        $paths = [
            __DIR__ . '/../db/demo_data.sql',
            __DIR__ . '/../db/demo_data_comprehensive.sql'
        ];

        $executed = 0;
        foreach ($paths as $path) {
            if (!is_file($path)) {
                continue;
            }
            $executed += $this->executeSqlFile($path);
        }

        return ['statements' => $executed];
    }

    private function executeSqlFile($path)
    {
        $sql = (string)@file_get_contents($path);
        if ($sql === '') {
            return 0;
        }

        $statements = $this->splitSqlStatements($sql);
        $count = 0;
        foreach ($statements as $statement) {
            $trim = trim($statement);
            if ($trim === '') {
                continue;
            }
            $this->db->execute($trim);
            $count++;
        }

        return $count;
    }

    private function splitSqlStatements($sql)
    {
        $statements = [];
        $buffer = '';
        $inString = false;
        $quoteChar = '';
        $len = strlen($sql);

        for ($i = 0; $i < $len; $i++) {
            $ch = $sql[$i];
            $next = $i + 1 < $len ? $sql[$i + 1] : '';

            if (!$inString && $ch === '-' && $next === '-') {
                while ($i < $len && $sql[$i] !== "\n") {
                    $i++;
                }
                continue;
            }

            if (!$inString && $ch === '#') {
                while ($i < $len && $sql[$i] !== "\n") {
                    $i++;
                }
                continue;
            }

            if (($ch === '"' || $ch === "'") && ($i === 0 || $sql[$i - 1] !== '\\')) {
                if (!$inString) {
                    $inString = true;
                    $quoteChar = $ch;
                } elseif ($quoteChar === $ch) {
                    $inString = false;
                    $quoteChar = '';
                }
                $buffer .= $ch;
                continue;
            }

            if (!$inString && $ch === ';') {
                $statements[] = $buffer;
                $buffer = '';
                continue;
            }

            $buffer .= $ch;
        }

        $tail = trim($buffer);
        if ($tail !== '') {
            $statements[] = $tail;
        }

        return $statements;
    }

    private function ensureDailyReportMasters(array &$context)
    {
        $created = 0;
        if (!$this->hasTable('daily_report_industries')) {
            return ['created' => 0, 'items' => []];
        }

        $actorId = (int)$context['actor_id'];

        $industries = [
            ['code' => 'IND-IT', 'name' => 'ITサービス'],
            ['code' => 'IND-MFG', 'name' => '製造業'],
            ['code' => 'IND-RTL', 'name' => '流通・小売']
        ];
        foreach ($industries as $index => $row) {
            $exists = $this->db->fetch("SELECT id FROM daily_report_industries WHERE name = ? LIMIT 1", [$row['name']]);
            if (!$exists) {
                $this->db->execute(
                    "INSERT INTO daily_report_industries (code, name, sort_order, is_active, created_by) VALUES (?, ?, ?, 1, ?)",
                    [$row['code'], $row['name'], $index + 1, $actorId]
                );
                $created++;
            }
        }

        if ($this->hasTable('daily_report_processes')) {
            $processes = [
                ['code' => 'PROC-PLN', 'name' => '企画'],
                ['code' => 'PROC-SLS', 'name' => '営業'],
                ['code' => 'PROC-DEV', 'name' => '開発']
            ];
            foreach ($processes as $index => $row) {
                $exists = $this->db->fetch("SELECT id FROM daily_report_processes WHERE name = ? LIMIT 1", [$row['name']]);
                if (!$exists) {
                    $this->db->execute(
                        "INSERT INTO daily_report_processes (code, name, sort_order, is_active, created_by) VALUES (?, ?, ?, 1, ?)",
                        [$row['code'], $row['name'], $index + 1, $actorId]
                    );
                    $created++;
                }
            }
        }

        $industryRows = $this->hasTable('daily_report_industries')
            ? $this->db->fetchAll("SELECT id, name FROM daily_report_industries WHERE is_active = 1 ORDER BY sort_order, id")
            : [];

        if ($this->hasTable('daily_report_products')) {
            $products = [
                ['code' => 'PRD-GW', 'name' => 'TeamSpace導入支援', 'industry' => 'ITサービス'],
                ['code' => 'PRD-MFG', 'name' => '製造ライン改善', 'industry' => '製造業'],
                ['code' => 'PRD-RTL', 'name' => '店舗運用最適化', 'industry' => '流通・小売']
            ];
            foreach ($products as $index => $row) {
                $exists = $this->db->fetch("SELECT id FROM daily_report_products WHERE name = ? LIMIT 1", [$row['name']]);
                if ($exists) {
                    continue;
                }

                $industryId = null;
                foreach ($industryRows as $industryRow) {
                    if ((string)$industryRow['name'] === (string)$row['industry']) {
                        $industryId = (int)$industryRow['id'];
                        break;
                    }
                }

                $this->db->execute(
                    "INSERT INTO daily_report_products (code, name, industry_id, sort_order, is_active, created_by) VALUES (?, ?, ?, ?, 1, ?)",
                    [$row['code'], $row['name'], $industryId, $index + 1, $actorId]
                );
                $created++;
            }
        }

        if ($this->hasTable('daily_report_projects')) {
            $projects = [
                ['code' => 'PJ-SLS', 'name' => '営業基盤刷新', 'industry' => 'ITサービス'],
                ['code' => 'PJ-QA', 'name' => '品質改善活動', 'industry' => '製造業'],
                ['code' => 'PJ-DX', 'name' => '業務DX推進', 'industry' => '流通・小売']
            ];
            foreach ($projects as $index => $row) {
                $exists = $this->db->fetch("SELECT id FROM daily_report_projects WHERE name = ? LIMIT 1", [$row['name']]);
                if ($exists) {
                    continue;
                }

                $industryId = null;
                foreach ($industryRows as $industryRow) {
                    if ((string)$industryRow['name'] === (string)$row['industry']) {
                        $industryId = (int)$industryRow['id'];
                        break;
                    }
                }

                $this->db->execute(
                    "INSERT INTO daily_report_projects (code, name, industry_id, sort_order, is_active, created_by) VALUES (?, ?, ?, ?, 1, ?)",
                    [$row['code'], $row['name'], $industryId, $index + 1, $actorId]
                );
                $created++;
            }
        }

        $context['daily_master'] = $this->dailyReportModel->getAnalysisMasterSet();

        return [
            'created' => $created,
            'items' => [
                'industries' => count($context['daily_master']['industries'] ?? []),
                'products' => count($context['daily_master']['products'] ?? []),
                'processes' => count($context['daily_master']['processes'] ?? []),
                'projects' => count($context['daily_master']['projects'] ?? [])
            ]
        ];
    }

    private function seedSchedules(array &$context)
    {
        if (!$this->hasTable('schedules')) {
            return ['created' => 0];
        }

        $created = 0;
        $prefix = $context['prefix'];
        $users = $context['users'];
        $orgIds = $context['org_ids'];
        $map = [];

        $cursor = $context['start'];
        while ($cursor <= $context['end']) {
            $weekday = (int)$cursor->format('N');
            $date = $cursor->format('Y-m-d');

            if ($weekday === 2 && (int)$cursor->format('j') <= 7) {
                foreach ($users as $index => $user) {
                    $uid = (int)$user['id'];
                    $hour = 9 + (($index + (int)$cursor->format('z')) % 7);
                    $startTime = $date . ' ' . sprintf('%02d:00:00', $hour);
                    $endTime = $date . ' ' . sprintf('%02d:30:00', min(22, $hour + 1));
                    $title = sprintf('%s %s 業務予定 %s', $prefix, (string)$user['display_name'], $date);

                    $scheduleId = $this->insertSchedule([
                        'title' => $title,
                        'description' => 'デモ用に自動生成された予定です。',
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'all_day' => 0,
                        'location' => 'オンライン',
                        'creator_id' => $uid,
                        'visibility' => 'private',
                        'priority' => 'normal',
                        'status' => 'scheduled'
                    ], false);
                    if ($scheduleId > 0) {
                        $created++;
                        $map[$uid . ':' . $date][] = $scheduleId;
                        $map[$uid . ':' . substr($date, 0, 7)][] = $scheduleId;
                    }
                }
            }

            if ($weekday === 1) {
                $ownerId = (int)$users[0]['id'];
                $title = sprintf('%s 全体朝会 %s', $prefix, $date);
                $scheduleId = $this->insertSchedule([
                    'title' => $title,
                    'description' => '週次の全体朝会（デモデータ）',
                    'start_time' => $date . ' 09:00:00',
                    'end_time' => $date . ' 09:30:00',
                    'all_day' => 0,
                    'location' => '会議室A',
                    'creator_id' => $ownerId,
                    'visibility' => 'public',
                    'priority' => 'high',
                    'status' => 'scheduled'
                ], false);
                if ($scheduleId > 0) {
                    $created++;
                    if ($this->hasTable('schedule_organizations') && !empty($orgIds)) {
                        $this->db->execute(
                            "INSERT IGNORE INTO schedule_organizations (schedule_id, organization_id) VALUES (?, ?)",
                            [$scheduleId, (int)$orgIds[0]]
                        );
                    }
                }
            }

            if ($cursor->format('d') === '01') {
                $ownerId = (int)$users[0]['id'];
                $title = sprintf('%s 月次キックオフ %s', $prefix, $cursor->format('Y-m'));
                $scheduleId = $this->insertSchedule([
                    'title' => $title,
                    'description' => '月初の全社キックオフ（デモデータ）',
                    'start_time' => $date . ' 10:00:00',
                    'end_time' => $date . ' 11:30:00',
                    'all_day' => 0,
                    'location' => '大会議室',
                    'creator_id' => $ownerId,
                    'visibility' => 'public',
                    'priority' => 'high',
                    'status' => 'scheduled'
                ], false);
                if ($scheduleId > 0) {
                    $created++;
                }
            }

            $cursor = $cursor->modify('+1 day');
        }

        $context['schedule_map'] = $map;

        return [
            'created' => $created,
            'from' => $context['start']->format('Y-m-d'),
            'to' => $context['end']->format('Y-m-d')
        ];
    }

    private function insertSchedule(array $row, $checkExists = true)
    {
        if ($checkExists) {
            $existing = $this->db->fetch(
                "SELECT id FROM schedules WHERE title = ? AND creator_id = ? AND start_time = ? LIMIT 1",
                [$row['title'], $row['creator_id'], $row['start_time']]
            );
            if ($existing) {
                return (int)$existing['id'];
            }
        }

        $this->db->execute(
            "INSERT INTO schedules (
                title, description, start_time, end_time, all_day, location, creator_id,
                visibility, priority, status, repeat_type, repeat_end_date, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'none', NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)",
            [
                $row['title'],
                $row['description'],
                $row['start_time'],
                $row['end_time'],
                (int)$row['all_day'],
                $row['location'],
                (int)$row['creator_id'],
                $row['visibility'],
                $row['priority'],
                $row['status']
            ]
        );

        return (int)$this->db->lastInsertId();
    }

    private function seedTasks(array &$context)
    {
        if (!$this->hasTable('task_boards') || !$this->hasTable('task_lists') || !$this->hasTable('task_cards')) {
            return ['created_cards' => 0];
        }

        $prefix = $context['prefix'];
        $actorId = (int)$context['actor_id'];
        $userIds = $context['user_ids'];

        $teamId = null;
        if ($this->hasTable('teams')) {
            $team = $this->db->fetch("SELECT id FROM teams WHERE name = ? LIMIT 1", [$prefix . ' DX推進チーム']);
            if ($team) {
                $teamId = (int)$team['id'];
            } else {
                $teamId = $this->teamModel->create([
                    'name' => $prefix . ' DX推進チーム',
                    'description' => 'デモ自動生成用のチーム',
                    'members' => $userIds,
                    'member_roles' => []
                ], $actorId);
                $teamId = (int)$teamId;
            }
        }

        $boardIds = [];
        $boardNames = [
            $prefix . ' 3年ロードマップ',
            $prefix . ' 営業施策ボード'
        ];

        foreach ($boardNames as $boardName) {
            $board = $this->db->fetch("SELECT id FROM task_boards WHERE name = ? LIMIT 1", [$boardName]);
            if ($board) {
                $boardIds[] = (int)$board['id'];
                continue;
            }

            $ownerType = $teamId > 0 ? 'team' : 'user';
            $ownerId = $teamId > 0 ? $teamId : (int)$userIds[0];
            $newBoardId = $this->taskModel->createBoard([
                'name' => $boardName,
                'description' => 'デモデータ自動生成ボード',
                'owner_type' => $ownerType,
                'owner_id' => $ownerId,
                'is_public' => 1,
                'background_color' => '#eef6ff',
                'created_by' => $actorId
            ]);

            if ($newBoardId) {
                $boardIds[] = (int)$newBoardId;
            }
        }

        $createdCards = 0;
        $allCardIds = [];
        $monthCursor = new \DateTimeImmutable($context['start']->format('Y-m-01'));
        $monthEnd = new \DateTimeImmutable($context['end']->format('Y-m-01'));

        $labelIds = [];
        foreach ($boardIds as $boardId) {
            $labelName = $prefix . ' 重点';
            $existingLabel = $this->db->fetch("SELECT id FROM task_labels WHERE board_id = ? AND name = ? LIMIT 1", [$boardId, $labelName]);
            if ($existingLabel) {
                $labelIds[$boardId] = (int)$existingLabel['id'];
                continue;
            }
            $newLabelId = $this->taskModel->createLabel($boardId, ['name' => $labelName, 'color' => '#ff9800']);
            if ($newLabelId) {
                $labelIds[$boardId] = (int)$newLabelId;
            }
        }

        while ($monthCursor <= $monthEnd) {
            $ym = $monthCursor->format('Y-m');

            foreach ($boardIds as $boardIndex => $boardId) {
                $lists = $this->taskModel->getBoardLists($boardId);
                if (count($lists) < 3) {
                    continue;
                }

                $todoListId = (int)$lists[0]['id'];
                $inProgressListId = (int)$lists[1]['id'];
                $doneListId = (int)$lists[count($lists) - 1]['id'];

                for ($i = 0; $i < 1; $i++) {
                    $title = sprintf('%s %s 月次タスク %d-%d', $prefix, $ym, $boardIndex + 1, $i + 1);
                    $dueDate = $monthCursor->modify('+' . (7 + ($i * 7)) . ' day')->format('Y-m-d');
                    $existing = $this->db->fetch(
                        "SELECT c.id
                         FROM task_cards c
                         JOIN task_lists l ON l.id = c.list_id
                         WHERE l.board_id = ? AND c.title = ?
                         LIMIT 1",
                        [$boardId, $title]
                    );
                    if ($existing) {
                        $allCardIds[] = (int)$existing['id'];
                        continue;
                    }

                    $now = new \DateTimeImmutable('today');
                    $targetListId = $todoListId;
                    $status = 'not_started';
                    $progress = 0;
                    if ($monthCursor < $now->modify('-1 month')->modify('first day of this month')) {
                        $targetListId = $doneListId;
                        $status = 'completed';
                        $progress = 100;
                    } elseif ($monthCursor <= $now->modify('+1 month')->modify('first day of this month')) {
                        $targetListId = $inProgressListId;
                        $status = 'in_progress';
                        $progress = 50;
                    }

                    $assigneeId = (int)$userIds[($i + $boardIndex) % count($userIds)];
                    $cardId = $this->taskModel->createCard($targetListId, [
                        'title' => $title,
                        'description' => 'デモ自動生成タスクです。',
                        'due_date' => $dueDate,
                        'priority' => $i === 0 ? 'high' : 'normal',
                        'status' => $status,
                        'progress' => $progress,
                        'assignees' => [$assigneeId],
                        'labels' => !empty($labelIds[$boardId]) ? [(int)$labelIds[$boardId]] : []
                    ], $actorId);

                    if ($cardId) {
                        $createdCards++;
                        $allCardIds[] = (int)$cardId;
                    }
                }
            }

            $monthCursor = $monthCursor->modify('+1 month');
        }

        $context['task_card_ids'] = $allCardIds;

        return [
            'boards' => count($boardIds),
            'created_cards' => $createdCards,
            'total_demo_cards' => count($allCardIds)
        ];
    }

    private function seedDailyReports(array &$context)
    {
        if (!$this->hasTable('daily_reports')) {
            return ['created' => 0, 'targets' => 0];
        }

        $prefix = $context['prefix'];
        $created = 0;
        $targetSaved = 0;
        $users = $context['users'];
        $targetUsers = array_slice($users, 0, min(3, count($users)));
        $scheduleMap = $context['schedule_map'];
        $taskCardIds = $context['task_card_ids'];

        $templateId = $this->ensureDailyReportTemplate($context);
        $master = $context['daily_master'];
        $projects = $master['projects'] ?? [];
        $industries = $master['industries'] ?? [];
        $products = $master['products'] ?? [];
        $processes = $master['processes'] ?? [];
        $monthCursor = new \DateTimeImmutable($context['start']->format('Y-m-01'));
        $monthEnd = new \DateTimeImmutable($context['end']->format('Y-m-01'));
        while ($monthCursor <= $monthEnd) {
            foreach ($targetUsers as $uIndex => $user) {
                $uid = (int)$user['id'];
                $reportDate = $monthCursor->modify('+' . (($uIndex % 3) + 6) . ' day');
                $date = $reportDate->format('Y-m-d');
                $title = sprintf('%s 日報 %s %s', $prefix, $date, (string)$user['display_name']);

                $project = !empty($projects) ? $projects[$uIndex % count($projects)] : null;
                $industry = !empty($industries) ? $industries[$uIndex % count($industries)] : null;
                $product = !empty($products) ? $products[$uIndex % count($products)] : null;
                $process = !empty($processes) ? $processes[$uIndex % count($processes)] : null;

                $linkedSchedules = $scheduleMap[$uid . ':' . substr($date, 0, 7)] ?? [];
                $linkedTasks = !empty($taskCardIds)
                    ? [(int)$taskCardIds[($uIndex + (int)$monthCursor->format('n')) % count($taskCardIds)]]
                    : [];

                $reportId = $this->createDemoDailyReport([
                    'user_id' => $uid,
                    'report_date' => $date,
                    'title' => $title,
                    'content' => 'デモ環境向けに自動作成された月次サンプル日報です。',
                    'content_format' => 'text',
                    'status' => 'published',
                    'summary_text' => '重点施策を実施し、概ね計画どおりに進行しました。',
                    'issues_text' => '進行遅延要因を洗い出し、次月対策を設定済みです。',
                    'tomorrow_plan_text' => '主要タスクの前倒し着手と関係者レビューを実施します。',
                    'reflection_text' => '予実差分の原因を分析し、次回計画に反映しました。',
                    'work_minutes' => 420,
                    'template_id' => $templateId,
                    'activities' => [
                        [
                            'start_time' => '09:30',
                            'end_time' => '12:00',
                            'activity_type' => '定例作業',
                            'subject' => '計画タスク実施',
                            'result' => '完了',
                            'memo' => 'デモデータ'
                        ],
                        [
                            'start_time' => '13:00',
                            'end_time' => '17:00',
                            'activity_type' => '会議',
                            'subject' => '進捗共有',
                            'result' => '課題抽出',
                            'memo' => '翌営業日に対応'
                        ]
                    ],
                    'analysis_entry' => [
                        'project_id' => isset($project['id']) ? (int)$project['id'] : 0,
                        'industry_id' => isset($industry['id']) ? (int)$industry['id'] : 0,
                        'product_id' => isset($product['id']) ? (int)$product['id'] : 0,
                        'process_id' => isset($process['id']) ? (int)$process['id'] : 0,
                        'activity_type' => 'daily_work',
                        'planned_amount' => 120000,
                        'actual_amount' => 115000,
                        'planned_hours' => 8,
                        'actual_hours' => 7.5,
                        'quantity' => 1,
                        'memo' => 'デモ分析明細'
                    ],
                    'schedules' => array_slice($linkedSchedules, 0, 2),
                    'tasks' => $linkedTasks
                ]);

                if ($reportId) {
                    $created++;

                    if ($this->hasTable('daily_report_likes') && count($users) > 1) {
                        $likeUserId = (int)$users[($uIndex + 1) % count($users)]['id'];
                        $this->db->execute(
                            "INSERT IGNORE INTO daily_report_likes (report_id, user_id) VALUES (?, ?)",
                            [(int)$reportId, $likeUserId]
                        );
                    }

                    if ($this->hasTable('daily_report_comments') && ((int)$monthCursor->format('n') % 3 === 0)) {
                        $commentUserId = (int)$users[($uIndex + 1) % count($users)]['id'];
                        $this->db->execute(
                            "INSERT INTO daily_report_comments (report_id, user_id, comment, created_at, updated_at)
                             VALUES (?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)",
                            [(int)$reportId, $commentUserId, 'デモコメント: 月次報告を確認しました。']
                        );
                    }
                }
            }

            $monthCursor = $monthCursor->modify('+1 month');
        }

        $monthCursor = new \DateTimeImmutable($context['start']->format('Y-m-01'));
        while ($monthCursor <= $monthEnd) {
            $targetMonth = $monthCursor->format('Y-m');
            foreach ($targetUsers as $uIndex => $user) {
                $uid = (int)$user['id'];
                $project = !empty($projects) ? $projects[$uIndex % max(1, count($projects))] : null;
                $industry = !empty($industries) ? $industries[$uIndex % max(1, count($industries))] : null;
                $product = !empty($products) ? $products[$uIndex % max(1, count($products))] : null;
                $process = !empty($processes) ? $processes[$uIndex % max(1, count($processes))] : null;

                $ok = $this->dailyReportModel->saveMonthlyTarget($uid, [
                    'target_month' => $targetMonth,
                    'project_id' => isset($project['id']) ? (int)$project['id'] : null,
                    'industry_id' => isset($industry['id']) ? (int)$industry['id'] : null,
                    'product_id' => isset($product['id']) ? (int)$product['id'] : null,
                    'process_id' => isset($process['id']) ? (int)$process['id'] : null,
                    'target_amount' => 2200000,
                    'target_hours' => 160,
                    'target_quantity' => 20,
                    'memo' => 'デモ月次目標'
                ]);
                if ($ok) {
                    $targetSaved++;
                }
            }

            $monthCursor = $monthCursor->modify('+1 month');
        }

        return [
            'created' => $created,
            'monthly_targets_saved' => $targetSaved,
            'template_id' => $templateId
        ];
    }

    private function createDemoDailyReport(array $payload)
    {
        $columns = [
            'user_id',
            'report_date',
            'title',
            'content',
            'status',
            'summary_text',
            'issues_text',
            'tomorrow_plan_text',
            'reflection_text',
            'work_minutes',
            'detail_json',
            'template_id'
        ];

        $activities = is_array($payload['activities'] ?? null) ? $payload['activities'] : [];
        $detailJson = json_encode(['activity_logs' => $activities], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($detailJson === false) {
            $detailJson = '{}';
        }

        $values = [
            (int)$payload['user_id'],
            (string)$payload['report_date'],
            (string)$payload['title'],
            (string)$payload['content'],
            (string)($payload['status'] ?? 'published'),
            (string)($payload['summary_text'] ?? ''),
            (string)($payload['issues_text'] ?? ''),
            (string)($payload['tomorrow_plan_text'] ?? ''),
            (string)($payload['reflection_text'] ?? ''),
            (int)($payload['work_minutes'] ?? 0),
            $detailJson,
            !empty($payload['template_id']) ? (int)$payload['template_id'] : null
        ];

        if ($this->hasColumn('daily_reports', 'content_format')) {
            $columns[] = 'content_format';
            $values[] = (string)($payload['content_format'] ?? 'text');
        }

        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $columnSql = implode(', ', $columns);
        $this->db->execute(
            "INSERT INTO daily_reports ({$columnSql}, created_at, updated_at)
             VALUES ({$placeholders}, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)",
            $values
        );
        $reportId = (int)$this->db->lastInsertId();
        if ($reportId <= 0) {
            return 0;
        }

        if ($this->hasTable('daily_report_activity_logs')) {
            foreach ($activities as $idx => $activity) {
                $this->db->execute(
                    "INSERT INTO daily_report_activity_logs (
                        report_id, start_time, end_time, activity_type, subject, result, memo, sort_order, created_at, updated_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)",
                    [
                        $reportId,
                        $activity['start_time'] ?? null,
                        $activity['end_time'] ?? null,
                        $activity['activity_type'] ?? null,
                        $activity['subject'] ?? null,
                        $activity['result'] ?? null,
                        $activity['memo'] ?? null,
                        $idx + 1
                    ]
                );
            }
        }

        if ($this->hasTable('daily_report_analysis_entries') && is_array($payload['analysis_entry'] ?? null)) {
            $a = $payload['analysis_entry'];
            $this->db->execute(
                "INSERT INTO daily_report_analysis_entries (
                    report_id, project_id, industry_id, product_id, process_id, activity_type,
                    planned_amount, actual_amount, planned_hours, actual_hours, quantity, memo, sort_order, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)",
                [
                    $reportId,
                    !empty($a['project_id']) ? (int)$a['project_id'] : null,
                    !empty($a['industry_id']) ? (int)$a['industry_id'] : null,
                    !empty($a['product_id']) ? (int)$a['product_id'] : null,
                    !empty($a['process_id']) ? (int)$a['process_id'] : null,
                    $a['activity_type'] ?? null,
                    (float)($a['planned_amount'] ?? 0),
                    (float)($a['actual_amount'] ?? 0),
                    (float)($a['planned_hours'] ?? 0),
                    (float)($a['actual_hours'] ?? 0),
                    (float)($a['quantity'] ?? 0),
                    $a['memo'] ?? null
                ]
            );
        }

        if ($this->hasTable('daily_report_schedules') && !empty($payload['schedules'])) {
            foreach (array_slice(array_map('intval', (array)$payload['schedules']), 0, 3) as $scheduleId) {
                if ($scheduleId > 0) {
                    $this->db->execute(
                        "INSERT IGNORE INTO daily_report_schedules (report_id, schedule_id, created_at) VALUES (?, ?, CURRENT_TIMESTAMP)",
                        [$reportId, $scheduleId]
                    );
                }
            }
        }

        if ($this->hasTable('daily_report_tasks') && !empty($payload['tasks'])) {
            foreach (array_slice(array_map('intval', (array)$payload['tasks']), 0, 3) as $taskId) {
                if ($taskId > 0) {
                    $this->db->execute(
                        "INSERT IGNORE INTO daily_report_tasks (report_id, task_id, created_at) VALUES (?, ?, CURRENT_TIMESTAMP)",
                        [$reportId, $taskId]
                    );
                }
            }
        }

        return $reportId;
    }

    private function ensureDailyReportTemplate(array $context)
    {
        if (!$this->hasTable('daily_report_templates')) {
            return null;
        }

        $title = $context['prefix'] . ' 日報テンプレート';
        $existing = $this->db->fetch("SELECT id FROM daily_report_templates WHERE title = ? LIMIT 1", [$title]);
        if ($existing) {
            return (int)$existing['id'];
        }

        $templateId = $this->dailyReportModel->createTemplate([
            'title' => $title,
            'content' => "【本日の業務】\n\n【成果】\n\n【課題】\n\n【明日の予定】",
            'content_format' => 'text',
            'description' => 'デモデータ自動生成テンプレート',
            'user_id' => (int)$context['actor_id'],
            'is_public' => 1,
            'sections' => [
                [
                    'section_key' => 'today_work',
                    'title' => '本日の業務',
                    'input_type' => 'textarea',
                    'is_required' => 1
                ],
                [
                    'section_key' => 'result',
                    'title' => '成果',
                    'input_type' => 'textarea',
                    'is_required' => 1
                ],
                [
                    'section_key' => 'next_action',
                    'title' => '明日の予定',
                    'input_type' => 'textarea',
                    'is_required' => 1
                ]
            ]
        ]);

        return $templateId ? (int)$templateId : null;
    }

    private function seedWorkflow(array $context)
    {
        if (!$this->hasTable('workflow_templates') || !$this->hasTable('workflow_requests')) {
            return ['templates' => 0, 'requests' => 0];
        }

        $prefix = $context['prefix'];
        $actorId = (int)$context['actor_id'];
        $users = $context['users'];
        $targetUsers = array_slice($users, 0, min(3, count($users)));

        $templateNames = [
            $prefix . ' 経費精算'
        ];

        $templateIds = [];
        foreach ($templateNames as $idx => $name) {
            $tpl = $this->db->fetch("SELECT id FROM workflow_templates WHERE name = ? LIMIT 1", [$name]);
            if ($tpl) {
                $templateId = (int)$tpl['id'];
            } else {
                $templateId = (int)$this->workflowModel->createTemplate([
                    'name' => $name,
                    'description' => 'デモ自動生成テンプレート',
                    'status' => 'active',
                    'creator_id' => $actorId
                ]);

                if ($templateId > 0) {
                    $this->workflowModel->addFormField([
                        'template_id' => $templateId,
                        'field_id' => 'amount',
                        'field_type' => 'number',
                        'label' => '金額',
                        'is_required' => 1,
                        'sort_order' => 1
                    ]);
                    $this->workflowModel->addFormField([
                        'template_id' => $templateId,
                        'field_id' => 'purpose',
                        'field_type' => 'textarea',
                        'label' => '用途',
                        'is_required' => 1,
                        'sort_order' => 2
                    ]);

                    $approverId = (int)$users[0]['id'];
                    $this->workflowModel->addRouteStep([
                        'template_id' => $templateId,
                        'step_number' => 1,
                        'step_type' => 'approval',
                        'step_name' => '一次承認',
                        'approver_type' => 'user',
                        'approver_id' => $approverId,
                        'allow_delegation' => 1,
                        'allow_self_approval' => 0,
                        'parallel_approval' => 0,
                        'sort_order' => 1
                    ]);
                }
            }

            if ($templateId > 0) {
                $templateIds[] = $templateId;
            }
        }

        $requestCount = 0;
        $monthCursor = new \DateTimeImmutable($context['start']->format('Y-m-01'));
        $monthEnd = new \DateTimeImmutable($context['end']->format('Y-m-01'));

        while ($monthCursor <= $monthEnd) {
            $ym = $monthCursor->format('Y-m');

            foreach ($targetUsers as $uIndex => $user) {
                foreach ($templateIds as $tIndex => $templateId) {
                    $title = sprintf('%s %s 申請 %s %d', $prefix, $ym, (string)$user['display_name'], $tIndex + 1);

                    $requestId = $this->workflowModel->createRequest([
                        'template_id' => $templateId,
                        'title' => $title,
                        'status' => 'pending',
                        'requester_id' => (int)$user['id'],
                        'form_data' => [
                            'amount' => (string)(12000 + (($uIndex + $tIndex) * 2500)),
                            'purpose' => 'デモデータ自動生成の申請です。'
                        ]
                    ]);

                    if ($requestId) {
                        $requestCount++;

                        if ($this->hasTable('workflow_comments') && ((int)$monthCursor->format('n') % 2 === 0)) {
                            $this->db->execute(
                                "INSERT INTO workflow_comments (request_id, user_id, comment, created_at)
                                 VALUES (?, ?, ?, CURRENT_TIMESTAMP)",
                                [(int)$requestId, (int)$user['id'], 'デモコメント: 申請内容を確認ください。']
                            );
                        }

                        if ((int)$monthCursor->format('Ym') < (int)(new \DateTimeImmutable('today'))->format('Ym')) {
                            $this->db->execute(
                                "UPDATE workflow_approvals SET status = 'approved', acted_at = CURRENT_TIMESTAMP, comment = 'デモ承認済み' WHERE request_id = ?",
                                [(int)$requestId]
                            );
                            $this->db->execute(
                                "UPDATE workflow_requests SET status = 'approved', current_step = NULL, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
                                [(int)$requestId]
                            );
                        }
                    }
                }
            }

            $monthCursor = $monthCursor->modify('+2 month');
        }

        return [
            'templates' => count($templateIds),
            'requests' => $requestCount
        ];
    }

    private function seedBulletin(array $context)
    {
        if (!$this->hasTable('bulletin_posts') || !$this->hasTable('bulletin_categories')) {
            return ['created' => 0];
        }

        $prefix = $context['prefix'];
        $actorId = (int)$context['actor_id'];
        $users = $context['users'];

        $category = $this->db->fetch("SELECT id FROM bulletin_categories WHERE name = ? LIMIT 1", [$prefix . ' お知らせ']);
        if ($category) {
            $categoryId = (int)$category['id'];
        } else {
            $this->db->execute(
                "INSERT INTO bulletin_categories (name, description, sort_order, created_by, created_at)
                 VALUES (?, ?, 999, ?, CURRENT_TIMESTAMP)",
                [$prefix . ' お知らせ', 'デモ自動生成カテゴリ', $actorId]
            );
            $categoryId = (int)$this->db->lastInsertId();
        }

        $created = 0;
        $cursor = $context['start'];
        while ($cursor <= $context['end']) {
            if ((int)$cursor->format('N') === 2) {
                $date = $cursor->format('Y-m-d');
                $title = sprintf('%s 週次連絡 %s', $prefix, $date);
                $exists = $this->db->fetch("SELECT id FROM bulletin_posts WHERE title = ? LIMIT 1", [$title]);
                if (!$exists) {
                    $author = (int)$users[(int)$cursor->format('W') % count($users)]['id'];
                    $this->db->execute(
                        "INSERT INTO bulletin_posts (category_id, title, body, is_pinned, status, visibility, author_id, view_count, created_at, updated_at)
                         VALUES (?, ?, ?, 0, 'published', 'all', ?, 0, ?, ?)",
                        [
                            $categoryId,
                            $title,
                            'デモ環境向け週次お知らせです。',
                            $author,
                            $date . ' 09:00:00',
                            $date . ' 09:00:00'
                        ]
                    );
                    $postId = (int)$this->db->lastInsertId();
                    $created++;

                    if ($this->hasTable('bulletin_comments')) {
                        $commentUser = (int)$users[($author + 1) % count($users)]['id'];
                        $this->db->execute(
                            "INSERT INTO bulletin_comments (post_id, user_id, body, created_at)
                             VALUES (?, ?, ?, CURRENT_TIMESTAMP)",
                            [$postId, $commentUser, 'デモコメント: 内容を確認しました。']
                        );
                    }
                }
            }
            $cursor = $cursor->modify('+7 day');
        }

        return ['created' => $created, 'category_id' => $categoryId];
    }

    private function seedMessages(array $context)
    {
        if (!$this->hasTable('messages') || !$this->hasTable('message_recipients')) {
            return ['threads' => 0];
        }

        $prefix = $context['prefix'];
        $users = $context['users'];
        $createdThreads = 0;

        $monthCursor = new \DateTimeImmutable($context['start']->format('Y-m-01'));
        $monthEnd = new \DateTimeImmutable($context['end']->format('Y-m-01'));

        while ($monthCursor <= $monthEnd) {
            $ym = $monthCursor->format('Y-m');
            for ($i = 0; $i < 1; $i++) {
                $subject = sprintf('%s 月次連絡 %s #%d', $prefix, $ym, $i + 1);

                $sender = (int)$users[($i + (int)$monthCursor->format('n')) % count($users)]['id'];
                $createdAt = $monthCursor->format('Y-m-d') . ' 10:00:00';
                $this->db->execute(
                    "INSERT INTO messages (subject, body, sender_id, parent_id, thread_id, created_at, updated_at)
                     VALUES (?, ?, ?, NULL, NULL, ?, ?)",
                    [$subject, 'デモ環境向けメッセージです。', $sender, $createdAt, $createdAt]
                );
                $rootId = (int)$this->db->lastInsertId();
                $this->db->execute("UPDATE messages SET thread_id = ? WHERE id = ?", [$rootId, $rootId]);

                $recipientCount = 0;
                foreach ($context['user_ids'] as $uid) {
                    if ((int)$uid === $sender) {
                        continue;
                    }
                    $this->db->execute(
                        "INSERT INTO message_recipients (message_id, user_id, is_read, is_starred, is_deleted, created_at, updated_at)
                         VALUES (?, ?, 0, 0, 0, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)",
                        [$rootId, (int)$uid]
                    );
                    $recipientCount++;
                    if ($recipientCount >= 2) {
                        break;
                    }
                }

                $replier = (int)$users[($i + 1) % count($users)]['id'];
                $replyCreatedAt = $monthCursor->format('Y-m-d') . ' 11:00:00';
                $this->db->execute(
                    "INSERT INTO messages (subject, body, sender_id, parent_id, thread_id, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?)",
                    ['Re: ' . $subject, 'デモ返信です。', $replier, $rootId, $rootId, $replyCreatedAt, $replyCreatedAt]
                );
                $replyId = (int)$this->db->lastInsertId();
                $this->db->execute(
                    "INSERT INTO message_recipients (message_id, user_id, is_read, is_starred, is_deleted, created_at, updated_at)
                     VALUES (?, ?, 0, 0, 0, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)",
                    [$replyId, $sender]
                );

                $createdThreads++;
            }

            $monthCursor = $monthCursor->modify('+3 month');
        }

        return ['threads' => $createdThreads];
    }

    private function seedFacilitiesAndReservations(array $context)
    {
        if (!$this->hasTable('facilities') || !$this->hasTable('facility_reservations')) {
            return ['facilities' => 0, 'reservations' => 0];
        }

        $prefix = $context['prefix'];
        $actorId = (int)$context['actor_id'];

        $facilityNames = [
            $prefix . ' 会議室A',
            $prefix . ' 会議室B',
            $prefix . ' オンラインブース'
        ];

        $facilityIds = [];
        foreach ($facilityNames as $index => $name) {
            $row = $this->db->fetch("SELECT id FROM facilities WHERE name = ? LIMIT 1", [$name]);
            if ($row) {
                $facilityIds[] = (int)$row['id'];
                continue;
            }

            $this->db->execute(
                "INSERT INTO facilities (name, description, capacity, sort_order, created_at) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)",
                [$name, 'デモ自動生成施設', 12 + ($index * 4), 900 + $index]
            );
            $facilityIds[] = (int)$this->db->lastInsertId();
        }

        $reservations = 0;
        $cursor = $context['start'];
        while ($cursor <= $context['end']) {
            if ((int)$cursor->format('N') <= 5) {
                foreach ($facilityIds as $idx => $facilityId) {
                    if (($idx + (int)$cursor->format('W')) % 2 !== 0) {
                        continue;
                    }
                    $date = $cursor->format('Y-m-d');
                    $title = sprintf('%s 施設予約 %s #%d', $prefix, $date, $idx + 1);
                    $exists = $this->db->fetch(
                        "SELECT id FROM facility_reservations WHERE facility_id = ? AND title = ? AND start_time = ? LIMIT 1",
                        [$facilityId, $title, $date . ' 13:00:00']
                    );
                    if ($exists) {
                        continue;
                    }

                    $userId = (int)$context['user_ids'][($idx + (int)$cursor->format('z')) % count($context['user_ids'])];
                    $this->db->execute(
                        "INSERT INTO facility_reservations (
                            facility_id, user_id, title, start_time, end_time, memo, created_at, updated_at
                        ) VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)",
                        [$facilityId, $userId, $title, $date . ' 13:00:00', $date . ' 14:30:00', 'デモ予約']
                    );
                    $reservations++;
                }
            }

            $cursor = $cursor->modify('+7 day');
        }

        return ['facilities' => count($facilityIds), 'reservations' => $reservations];
    }

    private function seedAddressBook(array $context)
    {
        if (!$this->hasTable('address_book')) {
            return ['created' => 0];
        }

        $prefix = $context['prefix'];
        $actorId = (int)$context['actor_id'];
        $existing = $this->db->fetch(
            "SELECT COUNT(*) AS cnt FROM address_book WHERE memo LIKE ?",
            ['%' . $prefix . '%']
        );
        $current = (int)($existing['cnt'] ?? 0);
        $target = 180;

        $created = 0;
        for ($i = $current + 1; $i <= $target; $i++) {
            $name = sprintf('デモ連絡先%03d', $i);
            $company = sprintf('デモ企業%03d株式会社', $i);
            $email = sprintf('demo-contact-%03d@example.local', $i);
            $this->db->execute(
                "INSERT INTO address_book (
                    name, name_kana, company, department, position_title, email, phone, mobile,
                    postal_code, address, category, memo, created_by, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)",
                [
                    $name,
                    $name,
                    $company,
                    '営業部',
                    '担当',
                    $email,
                    '03-0000-' . str_pad((string)$i, 4, '0', STR_PAD_LEFT),
                    '090-0000-' . str_pad((string)$i, 4, '0', STR_PAD_LEFT),
                    '100-0001',
                    '東京都千代田区1-1-' . (($i % 20) + 1),
                    'デモ',
                    $prefix . ' 自動生成データ',
                    $actorId
                ]
            );
            $created++;
        }

        return ['created' => $created, 'total_demo_contacts' => $target];
    }

    private function seedFileEntries(array $context)
    {
        if (!$this->hasTable('file_folders') || !$this->hasTable('file_entries') || !$this->hasTable('file_versions')) {
            return ['created' => 0];
        }

        $prefix = $context['prefix'];
        $actorId = (int)$context['actor_id'];

        $folder = $this->db->fetch("SELECT id FROM file_folders WHERE name = ? LIMIT 1", [$prefix . ' 営業資料']);
        if ($folder) {
            $folderId = (int)$folder['id'];
        } else {
            $this->db->execute(
                "INSERT INTO file_folders (name, parent_id, description, created_by, created_at, updated_at)
                 VALUES (?, NULL, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)",
                [$prefix . ' 営業資料', 'デモ自動生成ファイル', $actorId]
            );
            $folderId = (int)$this->db->lastInsertId();
        }

        $baseDir = __DIR__ . '/../uploads/files';
        if (!is_dir($baseDir)) {
            @mkdir($baseDir, 0755, true);
        }

        $created = 0;
        $monthCursor = new \DateTimeImmutable($context['start']->format('Y-m-01'));
        $monthEnd = new \DateTimeImmutable($context['end']->format('Y-m-01'));

        while ($monthCursor <= $monthEnd) {
            $ym = $monthCursor->format('Y-m');
            $title = sprintf('%s 提案資料 %s', $prefix, $ym);
            $existing = $this->db->fetch("SELECT id FROM file_entries WHERE folder_id = ? AND title = ? LIMIT 1", [$folderId, $title]);
            if ($existing) {
                $monthCursor = $monthCursor->modify('+1 month');
                continue;
            }

            $fileName = sprintf('demo_auto_%s.txt', str_replace('-', '', $ym));
            $originalName = sprintf('提案資料_%s.txt', str_replace('-', '', $ym));
            $filePath = $baseDir . '/' . $fileName;
            $body = "TeamSpace デモ資料 {$ym}\nこのファイルは自動生成されました。\n";
            @file_put_contents($filePath, $body);
            $fileSize = is_file($filePath) ? (int)filesize($filePath) : strlen($body);

            $this->db->execute(
                "INSERT INTO file_entries (
                    folder_id, title, description, filename, original_name, file_size, mime_type,
                    version, approval_status, uploaded_by, download_count, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, 'text/plain', 1, 'approved', ?, 0, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)",
                [$folderId, $title, 'デモ自動生成ファイル', $fileName, $originalName, $fileSize, $actorId]
            );
            $fileId = (int)$this->db->lastInsertId();

            $this->db->execute(
                "INSERT INTO file_versions (
                    file_id, version_number, filename, original_name, file_size, mime_type, uploaded_by, comment, created_at
                ) VALUES (?, 1, ?, ?, ?, 'text/plain', ?, ?, CURRENT_TIMESTAMP)",
                [$fileId, $fileName, $originalName, $fileSize, $actorId, 'デモ自動生成初版']
            );

            $created++;
            $monthCursor = $monthCursor->modify('+1 month');
        }

        return ['created' => $created, 'folder_id' => $folderId];
    }

    private function seedWebDatabase(array $context)
    {
        if (!$this->hasTable('web_databases') || !$this->hasTable('web_database_fields') || !$this->hasTable('web_database_records')) {
            return ['created_records' => 0];
        }

        $prefix = $context['prefix'];
        $actorId = (int)$context['actor_id'];

        $customerDbId = $this->ensureWebDatabase($prefix . ' 顧客台帳', '3年デモ用の顧客台帳', $actorId, '#3b82f6');
        $salesDbId = $this->ensureWebDatabase($prefix . ' 売上台帳', '3年デモ用の売上台帳', $actorId, '#16a34a');

        $customerNameField = $this->ensureWebField($customerDbId, '顧客名', 'text', 1, ['is_title_field' => 1, 'required' => 1, 'is_filterable' => 1]);
        $customerRankField = $this->ensureWebField($customerDbId, 'ランク', 'select', 2, ['options' => [['label' => 'A', 'value' => 'A'], ['label' => 'B', 'value' => 'B'], ['label' => 'C', 'value' => 'C']], 'is_filterable' => 1]);

        $salesMonthField = $this->ensureWebField($salesDbId, '対象月', 'date', 1, ['required' => 1, 'is_filterable' => 1, 'is_sortable' => 1]);
        $salesOwnerField = $this->ensureWebField($salesDbId, '担当者', 'user', 2, ['is_filterable' => 1]);
        $salesAmountField = $this->ensureWebField($salesDbId, '売上金額', 'currency', 3, ['is_sortable' => 1]);
        $salesCountField = $this->ensureWebField($salesDbId, '案件数', 'number', 4, ['is_sortable' => 1]);
        $salesStatusField = $this->ensureWebField($salesDbId, '状態', 'select', 5, ['options' => [['label' => '計画', 'value' => 'plan'], ['label' => '進行中', 'value' => 'progress'], ['label' => '完了', 'value' => 'done']], 'is_filterable' => 1]);
        $salesCustomerField = $this->ensureWebField($salesDbId, '顧客', 'relation', 6, ['relation_database_id' => $customerDbId, 'relation_type' => 'many_to_many', 'is_filterable' => 1]);

        $customerRecords = [];
        for ($i = 1; $i <= 3; $i++) {
            $name = sprintf('デモ顧客%02d', $i);
            $customerRecords[] = $this->ensureWebRecord($customerDbId, $customerNameField, $name, $actorId, [
                $customerRankField => $i % 3 === 0 ? 'A' : ($i % 3 === 1 ? 'B' : 'C')
            ], null, false);
        }

        $createdRecords = 0;
        $monthCursor = new \DateTimeImmutable($context['start']->format('Y-m-01'));
        $monthEnd = new \DateTimeImmutable($context['end']->format('Y-m-01'));
        $monthIndex = 0;
        $maxMonths = min(6, max(1, (int)$context['years'] * 12));

        $users = array_slice($context['users'], 0, min(3, count($context['users'])));
        while ($monthCursor <= $monthEnd && $monthIndex < $maxMonths) {
            $monthIndex++;
            foreach ($users as $uIndex => $user) {
                $title = sprintf('%s 売上 %s %s', $prefix, $monthCursor->format('Y-m'), (string)$user['display_name']);
                $recordId = $this->ensureWebRecord($salesDbId, $salesMonthField, $monthCursor->format('Y-m-01'), $actorId, [
                    $salesOwnerField => (string)$user['id'],
                    $salesAmountField => (string)(1200000 + (($uIndex + (int)$monthCursor->format('n')) * 85000)),
                    $salesCountField => (string)(5 + (($uIndex + (int)$monthCursor->format('n')) % 8)),
                    $salesStatusField => ((int)$monthCursor->format('Ym') <= (int)(new \DateTimeImmutable('today'))->format('Ym')) ? 'done' : 'progress'
                ], $title, false);
                if ($recordId > 0) {
                    $createdRecords++;
                    if (!empty($customerRecords) && $salesCustomerField > 0) {
                        $targetCustomer = (int)$customerRecords[($uIndex + (int)$monthCursor->format('n')) % count($customerRecords)];
                        $this->ensureWebRelation($recordId, $salesCustomerField, $targetCustomer, $customerDbId);
                    }
                }
            }
            $monthCursor = $monthCursor->modify('+1 month');
            if ($this->verbose && ($monthIndex % 6 === 0)) {
                fwrite(STDERR, "[demo-data] webdatabase month {$monthIndex}\n");
            }
        }

        $this->ensureWebView($salesDbId, $actorId, $prefix . ' 売上一覧', 'list', [
            'view_type' => 'list',
            'visible_fields' => [$salesMonthField, $salesOwnerField, $salesAmountField, $salesCountField, $salesStatusField]
        ], 1);
        $this->ensureWebView($salesDbId, $actorId, $prefix . ' 売上グラフ', 'custom', [
            'view_type' => 'chart',
            'aggregate' => [
                'group_field_id' => $salesMonthField,
                'metric' => 'sum',
                'metric_field_id' => $salesAmountField,
                'date_grain' => 'month',
                'chart_type' => 'bar'
            ]
        ], 0);

        return [
            'databases' => 2,
            'created_records' => $createdRecords,
            'customer_db_id' => $customerDbId,
            'sales_db_id' => $salesDbId
        ];
    }

    private function seedNotifications(array $context)
    {
        if (!$this->hasTable('notifications')) {
            return ['created' => 0];
        }

        $created = 0;
        $prefix = $context['prefix'];
        foreach ($context['user_ids'] as $uid) {
            $title = $prefix . ' デモ再生成完了';
            $exists = $this->db->fetch(
                "SELECT id FROM notifications WHERE user_id = ? AND title = ? ORDER BY id DESC LIMIT 1",
                [(int)$uid, $title]
            );
            if ($exists) {
                continue;
            }

            $this->db->execute(
                "INSERT INTO notifications (
                    user_id, type, title, content, link, reference_id, reference_type, is_read, is_email_sent, created_at
                ) VALUES (?, 'system', ?, ?, ?, NULL, 'demo_data', 0, 0, CURRENT_TIMESTAMP)",
                [(int)$uid, $title, 'デモデータが更新されました。', '/settings']
            );
            $created++;
        }

        return ['created' => $created];
    }

    private function ensureWebDatabase($name, $description, $creatorId, $color)
    {
        $existing = $this->db->fetch("SELECT id FROM web_databases WHERE name = ? LIMIT 1", [$name]);
        if ($existing) {
            return (int)$existing['id'];
        }

        $this->db->execute(
            "INSERT INTO web_databases (name, description, icon, color, is_public, creator_id, created_at, updated_at)
             VALUES (?, ?, 'database', ?, 1, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)",
            [$name, $description, $color, (int)$creatorId]
        );

        return (int)$this->db->lastInsertId();
    }

    private function ensureWebField($databaseId, $name, $type, $sortOrder, array $meta = [])
    {
        $existing = $this->db->fetch(
            "SELECT id FROM web_database_fields WHERE database_id = ? AND name = ? LIMIT 1",
            [(int)$databaseId, $name]
        );
        if ($existing) {
            return (int)$existing['id'];
        }

        $options = $meta['options'] ?? null;
        if (is_array($options)) {
            $options = json_encode($options, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $relationType = $meta['relation_type'] ?? 'one_to_many';
        $calcFormula = array_key_exists('calc_formula', $meta) ? $meta['calc_formula'] : null;

        $this->db->execute(
            "INSERT INTO web_database_fields (
                database_id, name, description, type, options, required, unique_value, default_value,
                validation, sort_order, is_title_field, is_filterable, is_sortable,
                relation_database_id, relation_field_id, relation_type,
                lookup_relation_field_id, lookup_target_field_id, calc_formula,
                created_at, updated_at
             ) VALUES (?, ?, NULL, ?, ?, ?, 0, NULL, NULL, ?, ?, ?, ?, ?, NULL, ?, NULL, NULL, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)",
            [
                (int)$databaseId,
                $name,
                $type,
                $options,
                !empty($meta['required']) ? 1 : 0,
                (int)$sortOrder,
                !empty($meta['is_title_field']) ? 1 : 0,
                !empty($meta['is_filterable']) ? 1 : 0,
                !empty($meta['is_sortable']) ? 1 : 0,
                !empty($meta['relation_database_id']) ? (int)$meta['relation_database_id'] : null,
                $relationType,
                $calcFormula
            ]
        );

        return (int)$this->db->lastInsertId();
    }

    private function ensureWebRecord($databaseId, $titleFieldId, $titleValue, $creatorId, array $fieldValues = [], $uniqueKey = null, $checkExists = true)
    {
        $lookupValue = $uniqueKey !== null ? $uniqueKey : (string)$titleValue;
        if ($checkExists) {
            $existing = $this->db->fetch(
                "SELECT r.id
                 FROM web_database_records r
                 JOIN web_database_record_data d ON d.record_id = r.id
                 WHERE r.database_id = ? AND d.field_id = ? AND d.value = ?
                 LIMIT 1",
                [(int)$databaseId, (int)$titleFieldId, (string)$lookupValue]
            );
            if ($existing) {
                $recordId = (int)$existing['id'];
            } else {
                $this->db->execute(
                    "INSERT INTO web_database_records (database_id, creator_id, updater_id, created_at, updated_at)
                     VALUES (?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)",
                    [(int)$databaseId, (int)$creatorId, (int)$creatorId]
                );
                $recordId = (int)$this->db->lastInsertId();
            }
        } else {
            $this->db->execute(
                "INSERT INTO web_database_records (database_id, creator_id, updater_id, created_at, updated_at)
                 VALUES (?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)",
                [(int)$databaseId, (int)$creatorId, (int)$creatorId]
            );
            $recordId = (int)$this->db->lastInsertId();
        }

        $this->upsertWebRecordData($recordId, (int)$titleFieldId, (string)$lookupValue);
        foreach ($fieldValues as $fieldId => $value) {
            $this->upsertWebRecordData($recordId, (int)$fieldId, (string)$value);
        }

        return $recordId;
    }

    private function upsertWebRecordData($recordId, $fieldId, $value)
    {
        $exists = $this->db->fetch(
            "SELECT id FROM web_database_record_data WHERE record_id = ? AND field_id = ? LIMIT 1",
            [(int)$recordId, (int)$fieldId]
        );

        if ($exists) {
            $this->db->execute(
                "UPDATE web_database_record_data SET value = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
                [(string)$value, (int)$exists['id']]
            );
            return;
        }

        $this->db->execute(
            "INSERT INTO web_database_record_data (record_id, field_id, value, created_at, updated_at)
             VALUES (?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)",
            [(int)$recordId, (int)$fieldId, (string)$value]
        );
    }

    private function ensureWebRelation($sourceRecordId, $sourceFieldId, $targetRecordId, $targetDatabaseId)
    {
        if (!$this->hasTable('web_database_relations')) {
            return;
        }

        $exists = $this->db->fetch(
            "SELECT id FROM web_database_relations
             WHERE source_record_id = ? AND source_field_id = ? AND target_record_id = ?
             LIMIT 1",
            [(int)$sourceRecordId, (int)$sourceFieldId, (int)$targetRecordId]
        );
        if ($exists) {
            return;
        }

        $this->db->execute(
            "INSERT INTO web_database_relations (
                source_record_id, source_field_id, target_record_id, target_database_id, sort_order, created_at
             ) VALUES (?, ?, ?, ?, 0, CURRENT_TIMESTAMP)",
            [(int)$sourceRecordId, (int)$sourceFieldId, (int)$targetRecordId, (int)$targetDatabaseId]
        );
    }

    private function ensureWebView($databaseId, $creatorId, $name, $type, array $settings, $isDefault)
    {
        if (!$this->hasTable('web_database_views')) {
            return;
        }

        $settingsJson = json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $existing = $this->db->fetch(
            "SELECT id FROM web_database_views WHERE database_id = ? AND name = ? LIMIT 1",
            [(int)$databaseId, $name]
        );

        if ($existing) {
            $this->db->execute(
                "UPDATE web_database_views
                 SET type = ?, settings = ?, is_default = ?, updated_at = CURRENT_TIMESTAMP
                 WHERE id = ?",
                [$type, $settingsJson, (int)$isDefault, (int)$existing['id']]
            );
            return;
        }

        if ($this->hasColumn('web_database_views', 'scope_type')) {
            $this->db->execute(
                "INSERT INTO web_database_views (
                    database_id, name, description, type, settings, scope_type, organization_id,
                    is_default, creator_id, created_at, updated_at
                 ) VALUES (?, ?, ?, ?, ?, 'global', NULL, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)",
                [(int)$databaseId, $name, 'デモ自動生成ビュー', $type, $settingsJson, (int)$isDefault, (int)$creatorId]
            );
        } else {
            $this->db->execute(
                "INSERT INTO web_database_views (
                    database_id, name, description, type, settings,
                    is_default, creator_id, created_at, updated_at
                 ) VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)",
                [(int)$databaseId, $name, 'デモ自動生成ビュー', $type, $settingsJson, (int)$isDefault, (int)$creatorId]
            );
        }
    }

    private function deleteSchedulesByPrefix($prefixLike, \DateTimeImmutable $startDate, \DateTimeImmutable $endDate)
    {
        if (!$this->hasTable('schedules')) {
            return 0;
        }

        $rows = $this->db->fetchAll(
            "SELECT id FROM schedules
             WHERE title LIKE ?",
            [$prefixLike]
        );

        if (empty($rows)) {
            return 0;
        }

        $ids = array_map(static function ($row) {
            return (int)$row['id'];
        }, $rows);
        $this->deleteByIds('schedule_participants', 'schedule_id', $ids);
        $this->deleteByIds('schedule_organizations', 'schedule_id', $ids);
        $this->deleteByIds('schedules', 'id', $ids);
        return count($ids);
    }

    private function deleteDailyReportsByPrefix($prefixLike, \DateTimeImmutable $startDate, \DateTimeImmutable $endDate)
    {
        if (!$this->hasTable('daily_reports')) {
            return 0;
        }

        $rows = $this->db->fetchAll(
            "SELECT id FROM daily_reports
             WHERE title LIKE ?",
            [$prefixLike]
        );

        if (empty($rows)) {
            return 0;
        }

        $ids = array_map(static function ($row) {
            return (int)$row['id'];
        }, $rows);

        $this->deleteByIds('daily_report_attachments', 'report_id', $ids);
        $this->deleteByIds('daily_report_analysis_entries', 'report_id', $ids);
        $this->deleteByIds('daily_report_activity_logs', 'report_id', $ids);
        $this->deleteByIds('daily_report_tasks', 'report_id', $ids);
        $this->deleteByIds('daily_report_schedules', 'report_id', $ids);
        $this->deleteByIds('daily_report_permissions', 'report_id', $ids);
        $this->deleteByIds('daily_report_tag_relations', 'report_id', $ids);
        $this->deleteByIds('daily_report_likes', 'report_id', $ids);
        $this->deleteByIds('daily_report_comments', 'report_id', $ids);
        $this->deleteByIds('daily_report_reads', 'report_id', $ids);
        $this->deleteByIds('daily_reports', 'id', $ids);
        if ($this->hasTable('daily_report_monthly_targets')) {
            $this->db->execute("DELETE FROM daily_report_monthly_targets WHERE memo LIKE ?", ['デモ月次目標%']);
        }

        return count($ids);
    }

    private function deleteTaskBoardsByPrefix($prefixLike)
    {
        if (!$this->hasTable('task_boards')) {
            return 0;
        }

        $rows = $this->db->fetchAll("SELECT id FROM task_boards WHERE name LIKE ?", [$prefixLike]);
        if (empty($rows)) {
            return 0;
        }

        $boardIds = array_map(static function ($row) {
            return (int)$row['id'];
        }, $rows);
        $listIds = [];
        $cardIds = [];

        if ($this->hasTable('task_lists')) {
            $listRows = $this->fetchByIds('task_lists', 'board_id', $boardIds, ['id']);
            $listIds = array_map(static function ($row) {
                return (int)$row['id'];
            }, $listRows);
        }

        if (!empty($listIds) && $this->hasTable('task_cards')) {
            $cardRows = $this->fetchByIds('task_cards', 'list_id', $listIds, ['id']);
            $cardIds = array_map(static function ($row) {
                return (int)$row['id'];
            }, $cardRows);
        }

        $this->deleteByIds('task_activities', 'board_id', $boardIds);
        $this->deleteByIds('task_labels', 'board_id', $boardIds);
        $this->deleteByIds('task_board_members', 'board_id', $boardIds);

        if (!empty($cardIds)) {
            $this->deleteByIds('task_checklist_items', 'checklist_id', $this->extractChecklistIds($cardIds));
            $this->deleteByIds('task_checklists', 'card_id', $cardIds);
            $this->deleteByIds('task_card_labels', 'card_id', $cardIds);
            $this->deleteByIds('task_assignees', 'card_id', $cardIds);
            $this->deleteByIds('task_comments', 'card_id', $cardIds);
            $this->deleteByIds('task_attachments', 'card_id', $cardIds);
            $this->deleteByIds('task_cards', 'id', $cardIds);
        }

        if (!empty($listIds)) {
            $this->deleteByIds('task_lists', 'id', $listIds);
        }

        $this->deleteByIds('task_boards', 'id', $boardIds);
        return count($boardIds);
    }

    private function extractChecklistIds(array $cardIds)
    {
        if (!$this->hasTable('task_checklists') || empty($cardIds)) {
            return [];
        }
        $rows = $this->fetchByIds('task_checklists', 'card_id', $cardIds, ['id']);
        return array_map(static function ($row) {
            return (int)$row['id'];
        }, $rows);
    }

    private function deleteWorkflowByPrefix($prefixLike)
    {
        if (!$this->hasTable('workflow_templates')) {
            return 0;
        }

        $templateRows = $this->db->fetchAll("SELECT id FROM workflow_templates WHERE name LIKE ?", [$prefixLike]);
        $templateIds = array_map(static function ($row) {
            return (int)$row['id'];
        }, $templateRows);

        $requestRows = $this->hasTable('workflow_requests')
            ? $this->db->fetchAll("SELECT id FROM workflow_requests WHERE title LIKE ?", [$prefixLike])
            : [];
        $requestIds = array_map(static function ($row) {
            return (int)$row['id'];
        }, $requestRows);

        if (!empty($requestIds)) {
            $this->deleteByIds('workflow_attachments', 'request_id', $requestIds);
            $this->deleteByIds('workflow_comments', 'request_id', $requestIds);
            $this->deleteByIds('workflow_approvals', 'request_id', $requestIds);
            $this->deleteByIds('workflow_request_data', 'request_id', $requestIds);
            $this->deleteByIds('workflow_requests', 'id', $requestIds);
        }

        if (!empty($templateIds)) {
            $this->deleteByIds('workflow_route_definitions', 'template_id', $templateIds);
            $this->deleteByIds('workflow_form_definitions', 'template_id', $templateIds);
            $this->deleteByIds('workflow_template_organizations', 'template_id', $templateIds);
            $this->deleteByIds('workflow_templates', 'id', $templateIds);
        }

        return count($requestIds) + count($templateIds);
    }

    private function deleteBulletinsByPrefix($prefixLike)
    {
        if (!$this->hasTable('bulletin_posts')) {
            return 0;
        }

        $rows = $this->db->fetchAll("SELECT id FROM bulletin_posts WHERE title LIKE ?", [$prefixLike]);
        if (empty($rows)) {
            return 0;
        }

        $ids = array_map(static function ($row) {
            return (int)$row['id'];
        }, $rows);
        $this->deleteByIds('bulletin_attachments', 'post_id', $ids);
        $this->deleteByIds('bulletin_post_reads', 'post_id', $ids);
        $this->deleteByIds('bulletin_comments', 'post_id', $ids);
        $this->deleteByIds('bulletin_post_targets', 'post_id', $ids);
        $this->deleteByIds('bulletin_posts', 'id', $ids);

        $this->db->execute("DELETE FROM bulletin_categories WHERE name LIKE ?", [$prefixLike]);

        return count($ids);
    }

    private function deleteMessagesByPrefix($prefixLike)
    {
        if (!$this->hasTable('messages')) {
            return 0;
        }

        $roots = $this->db->fetchAll(
            "SELECT id FROM messages WHERE parent_id IS NULL AND subject LIKE ?",
            [$prefixLike . '%']
        );
        if (empty($roots)) {
            return 0;
        }

        $rootIds = array_map(static function ($row) {
            return (int)$row['id'];
        }, $roots);
        $rows = $this->fetchByIds('messages', 'thread_id', $rootIds, ['id']);
        if (empty($rows)) {
            return 0;
        }

        $ids = array_map(static function ($row) {
            return (int)$row['id'];
        }, $rows);
        $this->deleteByIds('message_attachments', 'message_id', $ids);
        $this->deleteByIds('message_organizations', 'message_id', $ids);
        $this->deleteByIds('message_recipients', 'message_id', $ids);
        $this->deleteByIds('messages', 'id', $ids);

        return count($ids);
    }

    private function deleteFacilityReservationsByPrefix($prefixLike)
    {
        $deleted = 0;
        if ($this->hasTable('facility_reservations')) {
            $this->db->execute("DELETE FROM facility_reservations WHERE title LIKE ?", [$prefixLike]);
            $deleted++;
        }
        if ($this->hasTable('facilities')) {
            $this->db->execute("DELETE FROM facilities WHERE name LIKE ?", [$prefixLike]);
        }
        return $deleted;
    }

    private function deleteAddressByPrefix($prefixLike)
    {
        if (!$this->hasTable('address_book')) {
            return 0;
        }
        $this->db->execute("DELETE FROM address_book WHERE memo LIKE ?", ['%' . $prefixLike . '%']);
        return 1;
    }

    private function deleteFilesByPrefix($prefixLike)
    {
        if (!$this->hasTable('file_entries')) {
            return 0;
        }

        $rows = $this->db->fetchAll("SELECT id, filename FROM file_entries WHERE title LIKE ?", [$prefixLike]);
        if (empty($rows)) {
            return 0;
        }

        $ids = [];
        foreach ($rows as $row) {
            $ids[] = (int)$row['id'];
            $fileName = trim((string)($row['filename'] ?? ''));
            if ($fileName !== '') {
                $path = __DIR__ . '/../uploads/files/' . $fileName;
                if (is_file($path)) {
                    @unlink($path);
                }
            }
        }

        $this->deleteByIds('file_versions', 'file_id', $ids);
        $this->deleteByIds('file_permissions', 'resource_id', $ids, "resource_type = 'file'");
        $this->deleteByIds('file_entries', 'id', $ids);

        if ($this->hasTable('file_folders')) {
            $folderRows = $this->db->fetchAll("SELECT id FROM file_folders WHERE name LIKE ?", [$prefixLike]);
            $folderIds = array_map(static function ($row) {
                return (int)$row['id'];
            }, $folderRows);
            if (!empty($folderIds)) {
                $this->deleteByIds('file_permissions', 'resource_id', $folderIds, "resource_type = 'folder'");
                $this->deleteByIds('file_folders', 'id', $folderIds);
            }
        }

        return count($ids);
    }

    private function deleteWebDatabasesByPrefix($prefixLike)
    {
        if (!$this->hasTable('web_databases')) {
            return 0;
        }

        $rows = $this->db->fetchAll("SELECT id FROM web_databases WHERE name LIKE ?", [$prefixLike]);
        if (empty($rows)) {
            return 0;
        }

        $dbIds = array_map(static function ($row) {
            return (int)$row['id'];
        }, $rows);

        $recordRows = !empty($dbIds) ? $this->fetchByIds('web_database_records', 'database_id', $dbIds, ['id']) : [];
        $recordIds = array_map(static function ($row) {
            return (int)$row['id'];
        }, $recordRows);

        if (!empty($recordIds)) {
            $this->deleteByIds('web_database_relations', 'source_record_id', $recordIds);
            $this->deleteByIds('web_database_relations', 'target_record_id', $recordIds);
            $this->deleteByIds('web_database_record_data', 'record_id', $recordIds);
            $this->deleteByIds('web_database_records', 'id', $recordIds);
        }

        $this->deleteByIds('web_database_form_layouts', 'database_id', $dbIds);
        $this->deleteByIds('web_database_views', 'database_id', $dbIds);
        $this->deleteByIds('web_database_permissions', 'database_id', $dbIds);
        $this->deleteByIds('web_database_fields', 'database_id', $dbIds);
        $this->deleteByIds('web_databases', 'id', $dbIds);

        return count($dbIds);
    }

    private function deleteNotificationsByPrefix($prefixLike)
    {
        if (!$this->hasTable('notifications')) {
            return 0;
        }
        $this->db->execute("DELETE FROM notifications WHERE title LIKE ?", [$prefixLike]);
        return 1;
    }

    private function deleteByIds($table, $column, array $ids, $extraWhere = null)
    {
        if (empty($ids) || !$this->hasTable($table)) {
            return;
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static function ($id) {
            return $id > 0;
        })));
        if (empty($ids)) {
            return;
        }

        foreach (array_chunk($ids, 500) as $chunk) {
            $placeholders = implode(',', array_fill(0, count($chunk), '?'));
            $sql = "DELETE FROM {$table} WHERE {$column} IN ({$placeholders})";
            if ($extraWhere) {
                $sql .= " AND {$extraWhere}";
            }
            $this->db->execute($sql, $chunk);
        }
    }

    private function fetchByIds($table, $column, array $ids, array $fields)
    {
        if (empty($ids) || !$this->hasTable($table)) {
            return [];
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static function ($id) {
            return $id > 0;
        })));
        if (empty($ids)) {
            return [];
        }

        $fieldSql = implode(', ', $fields);
        $rows = [];
        foreach (array_chunk($ids, 500) as $chunk) {
            $placeholders = implode(',', array_fill(0, count($chunk), '?'));
            $part = $this->db->fetchAll(
                "SELECT {$fieldSql} FROM {$table} WHERE {$column} IN ({$placeholders})",
                $chunk
            );
            if (!empty($part)) {
                $rows = array_merge($rows, $part);
            }
        }
        return $rows;
    }

    private function hasTable($tableName)
    {
        $tableName = (string)$tableName;
        if (!preg_match('/^[A-Za-z0-9_]+$/', $tableName)) {
            return false;
        }

        if (array_key_exists($tableName, $this->tableExistsCache)) {
            return $this->tableExistsCache[$tableName];
        }

        $row = $this->db->fetch("SHOW TABLES LIKE '{$tableName}'");
        $this->tableExistsCache[$tableName] = !empty($row);
        return $this->tableExistsCache[$tableName];
    }

    private function hasColumn($tableName, $columnName)
    {
        if (!$this->hasTable($tableName)) {
            return false;
        }

        $tableName = (string)$tableName;
        $columnName = (string)$columnName;
        if (!preg_match('/^[A-Za-z0-9_]+$/', $columnName)) {
            return false;
        }

        $cacheKey = $tableName . '.' . $columnName;
        if (array_key_exists($cacheKey, $this->columnExistsCache)) {
            return $this->columnExistsCache[$cacheKey];
        }

        $row = $this->db->fetch("SHOW COLUMNS FROM {$tableName} LIKE '{$columnName}'");
        $this->columnExistsCache[$cacheKey] = !empty($row);
        return $this->columnExistsCache[$cacheKey];
    }
}

<?php
namespace Controllers;

use Core\Controller;
use Core\Database;
use Models\CalendarImportSubscription;
use Models\CalendarIntegrationSetting;
use Models\Setting;
use Models\User;
use Services\CalendarFeedService;
use Services\CalendarImportService;

class IntegrationController extends Controller
{
    private $db;
    private $userModel;
    private $settingModel;
    private $calendarIntegrationSetting;
    private $calendarImportSubscription;
    private $calendarImportService;

    public function __construct($requireAuth = true)
    {
        parent::__construct();
        $this->db = Database::getInstance();
        $this->userModel = new User();
        $this->settingModel = new Setting();
        $this->calendarIntegrationSetting = new CalendarIntegrationSetting();
        $this->calendarImportSubscription = new CalendarImportSubscription($this->db);
        $this->calendarImportService = new CalendarImportService($this->db, $this->calendarImportSubscription);

        if ($requireAuth && !$this->auth->check()) {
            $this->redirect(BASE_PATH . '/login');
        }
    }

    public function index()
    {
        $userId = $this->auth->id();
        $settings = $this->calendarIntegrationSetting->getOrCreateByUserId($userId);
        $feedUrl = BASE_PATH . '/integrations/calendar.ics';
        $tokenFeedUrl = BASE_PATH . '/integrations/calendar/subscription/' . $settings['ics_token'];
        $absoluteTokenFeedUrl = $this->absoluteUrl($tokenFeedUrl);
        $googleUrl = 'https://calendar.google.com/calendar/r?cid=' . rawurlencode($absoluteTokenFeedUrl);
        $outlookUrl = 'https://outlook.live.com/calendar/0/addfromweb?url=' . rawurlencode($absoluteTokenFeedUrl) . '&name=' . rawurlencode('TeamSpace Schedule');

        $this->view('integration/index', [
            'title' => 'カレンダー連携',
            'settings' => $settings,
            'subscriptions' => $this->calendarImportSubscription->getByUserId($userId),
            'feedUrl' => $feedUrl,
            'tokenFeedUrl' => $tokenFeedUrl,
            'absoluteFeedUrl' => $absoluteTokenFeedUrl,
            'absoluteAuthFeedUrl' => $this->absoluteUrl($feedUrl),
            'googleUrl' => $googleUrl,
            'outlookUrl' => $outlookUrl,
            'jsFiles' => ['integration.js']
        ]);
    }

    public function calendarIcs()
    {
        $userId = $this->auth->id();
        $settings = $this->calendarIntegrationSetting->getOrCreateByUserId($userId);
        $this->streamCalendar($userId, $settings);
    }

    public function publicCalendarIcs($params)
    {
        $token = trim((string)($params['token'] ?? ''));
        $settings = $this->calendarIntegrationSetting->getByToken($token);

        if (!$settings || !(int)$settings['feed_enabled']) {
            http_response_code(404);
            echo '404 Not Found';
            exit;
        }

        $this->streamCalendar((int)$settings['user_id'], $settings);
    }

    public function apiSaveSettings($params, $data)
    {
        $userId = $this->auth->id();
        $saved = $this->calendarIntegrationSetting->updateForUser($userId, $data);
        $settings = $this->calendarIntegrationSetting->getOrCreateByUserId($userId);

        return [
            'success' => (bool)$saved,
            'message' => $saved ? '連携設定を更新しました' : '連携設定の更新に失敗しました',
            'data' => [
                'settings' => $settings,
                'feed_url' => $this->absoluteUrl(BASE_PATH . '/integrations/calendar/subscription/' . $settings['ics_token'])
            ]
        ];
    }

    public function apiRegenerateToken()
    {
        $userId = $this->auth->id();
        $settings = $this->calendarIntegrationSetting->regenerateToken($userId);

        return [
            'success' => true,
            'message' => '購読トークンを再発行しました',
            'data' => [
                'settings' => $settings,
                'feed_url' => $this->absoluteUrl(BASE_PATH . '/integrations/calendar/subscription/' . $settings['ics_token'])
            ]
        ];
    }

    public function storeSubscription()
    {
        if (!$this->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = '不正なリクエストです。';
            $this->redirect(BASE_PATH . '/integrations');
            return;
        }

        $userId = $this->auth->id();
        $data = CalendarImportSubscription::normalizeData($_POST);

        if (trim((string)$data['source_url']) === '') {
            $_SESSION['flash_error'] = '取り込みURLを入力してください。';
            $this->redirect(BASE_PATH . '/integrations');
            return;
        }

        $this->calendarImportSubscription->create($userId, $data);
        $_SESSION['flash_success'] = '外部カレンダー購読を追加しました。';
        $this->redirect(BASE_PATH . '/integrations');
    }

    public function updateSubscription($params)
    {
        if (!$this->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = '不正なリクエストです。';
            $this->redirect(BASE_PATH . '/integrations');
            return;
        }

        $userId = $this->auth->id();
        $subscriptionId = (int)($params['id'] ?? 0);
        $ok = $this->calendarImportSubscription->update($subscriptionId, $userId, CalendarImportSubscription::normalizeData($_POST));

        $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok ? '購読設定を更新しました。' : '購読設定の更新に失敗しました。';
        $this->redirect(BASE_PATH . '/integrations');
    }

    public function deleteSubscription($params)
    {
        if (!$this->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = '不正なリクエストです。';
            $this->redirect(BASE_PATH . '/integrations');
            return;
        }

        $userId = $this->auth->id();
        $subscriptionId = (int)($params['id'] ?? 0);
        $ok = $this->calendarImportSubscription->delete($subscriptionId, $userId);

        $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok ? '購読設定を削除しました。' : '購読設定の削除に失敗しました。';
        $this->redirect(BASE_PATH . '/integrations');
    }

    public function syncSubscription($params)
    {
        if (!$this->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = '不正なリクエストです。';
            $this->redirect(BASE_PATH . '/integrations');
            return;
        }

        $userId = $this->auth->id();
        $subscriptionId = (int)($params['id'] ?? 0);
        $subscription = $this->calendarImportSubscription->getById($subscriptionId);

        if (!$subscription || (int)$subscription['user_id'] !== (int)$userId) {
            $_SESSION['flash_error'] = '購読設定が見つかりません。';
            $this->redirect(BASE_PATH . '/integrations');
            return;
        }

        $result = $this->calendarImportService->syncSubscription($subscription);
        $_SESSION[$result['success'] ? 'flash_success' : 'flash_error'] = $result['message'] ?? '同期処理を実行しました。';
        $this->redirect(BASE_PATH . '/integrations');
    }

    public function exportCsv()
    {
        $module = $_GET['module'] ?? 'schedule';
        $userId = $this->auth->id();

        switch ($module) {
            case 'messages':
                $rows = $this->db->fetchAll(
                    "SELECT m.id, m.subject, m.body, m.created_at
                     FROM messages m
                     LEFT JOIN message_recipients mr ON mr.message_id = m.id AND mr.user_id = ?
                     WHERE mr.id IS NOT NULL OR m.sender_id = ?
                     ORDER BY m.created_at DESC
                     LIMIT 500",
                    [$userId, $userId]
                );
                break;
            case 'workflow':
                $rows = $this->db->fetchAll(
                    "SELECT wr.id, wr.request_number, wr.title, wr.status, wr.created_at
                     FROM workflow_requests wr
                     LEFT JOIN workflow_approvals wa ON wa.request_id = wr.id
                     WHERE wr.requester_id = ? OR wa.approver_id = ?
                     ORDER BY wr.created_at DESC
                     LIMIT 500",
                    [$userId, $userId]
                );
                break;
            case 'task':
                $rows = $this->db->fetchAll(
                    "SELECT c.id, c.title, c.status, c.due_date, c.updated_at
                     FROM task_cards c
                     LEFT JOIN task_assignees a ON a.card_id = c.id
                     WHERE a.user_id = ? OR c.created_by = ?
                     ORDER BY c.updated_at DESC
                     LIMIT 500",
                    [$userId, $userId]
                );
                break;
            case 'schedule':
            default:
                $rows = $this->db->fetchAll(
                    "SELECT s.id, s.title, s.start_time, s.end_time, s.location, s.visibility
                     FROM schedules s
                     LEFT JOIN schedule_participants sp ON sp.schedule_id = s.id
                     WHERE s.creator_id = ? OR sp.user_id = ? OR s.visibility = 'public'
                     ORDER BY s.start_time DESC
                     LIMIT 500",
                    [$userId, $userId]
                );
                break;
        }

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $module . '_export_' . date('Ymd_His') . '.csv"');

        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');
        if (!empty($rows)) {
            fputcsv($out, array_keys($rows[0]));
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
        } else {
            fputcsv($out, ['message']);
            fputcsv($out, ['no_data']);
        }
        fclose($out);
        exit;
    }

    public function apiImportIcs($params, $data)
    {
        $settings = $this->calendarIntegrationSetting->getOrCreateByUserId($this->auth->id());
        if (!(int)$settings['allow_ics_import']) {
            return ['success' => false, 'error' => 'ICS取り込みは現在無効です'];
        }

        $url = trim((string)($data['url'] ?? ''));
        if ($url === '') {
            return ['success' => false, 'error' => 'URLが必要です'];
        }

        $ics = @file_get_contents($url);
        if ($ics === false) {
            return ['success' => false, 'error' => 'ICS取得に失敗しました'];
        }

        $events = CalendarFeedService::parseIcsEvents($ics);
        if (empty($events)) {
            return ['success' => false, 'error' => '取り込み可能なイベントがありません'];
        }

        $created = 0;
        foreach ($events as $event) {
            if (empty($event['summary']) || empty($event['dtstart'])) {
                continue;
            }

            $start = date('Y-m-d H:i:s', strtotime($event['dtstart']));
            $end = !empty($event['dtend'])
                ? date('Y-m-d H:i:s', strtotime($event['dtend']))
                : date('Y-m-d H:i:s', strtotime($event['dtstart'] . ' +1 hour'));

            $ok = $this->db->execute(
                "INSERT INTO schedules (title, description, start_time, end_time, all_day, location, creator_id, visibility, priority, status, repeat_type, created_at, updated_at)
                 VALUES (?, ?, ?, ?, 0, ?, ?, 'public', 'normal', 'scheduled', 'none', NOW(), NOW())",
                [
                    $event['summary'],
                    $event['description'] ?? null,
                    $start,
                    $end,
                    $event['location'] ?? null,
                    $this->auth->id()
                ]
            );

            if ($ok) {
                $created++;
            }
        }

        return [
            'success' => true,
            'message' => $created . '件の予定を取り込みました'
        ];
    }

    public function apiData($params)
    {
        $userId = $this->auth->id();

        $scheduleCount = $this->db->fetch("SELECT COUNT(*) AS c FROM schedules WHERE creator_id = ?", [$userId]);
        $workflowCount = $this->db->fetch("SELECT COUNT(*) AS c FROM workflow_requests WHERE requester_id = ?", [$userId]);
        $taskCount = $this->db->fetch(
            "SELECT COUNT(*) AS c FROM task_cards c JOIN task_assignees a ON a.card_id = c.id WHERE a.user_id = ?",
            [$userId]
        );

        return [
            'success' => true,
            'data' => [
                'schedule_count' => (int)($scheduleCount['c'] ?? 0),
                'workflow_count' => (int)($workflowCount['c'] ?? 0),
                'task_count' => (int)($taskCount['c'] ?? 0),
                'ics_url' => BASE_PATH . '/integrations/calendar.ics'
            ]
        ];
    }

    private function absoluteUrl($path)
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $configuredAppUrl = trim((string)$this->settingModel->getWithEnv('app_url', 'GW_APP_URL', ''));

        return CalendarFeedService::buildAbsoluteUrl(
            $path,
            $configuredAppUrl,
            $scheme,
            $host
        );
    }

    private function streamCalendar($userId, array $settings)
    {
        $rows = $this->getFeedRows($userId, $settings);
        $appName = $this->settingModel->getAppName();

        foreach ($rows as &$row) {
            $row['link'] = $this->absoluteUrl(BASE_PATH . '/schedule/view/' . (int)$row['id']);
        }
        unset($row);

        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: inline; filename="groupware_schedule.ics"');

        echo CalendarFeedService::buildCalendar($rows, $appName);
        exit;
    }

    private function getFeedRows($userId, array $settings)
    {
        if (!(int)($settings['feed_enabled'] ?? 0)) {
            return [];
        }

        $conditions = [];
        $params = [];

        if (!empty($settings['include_public'])) {
            $conditions[] = "s.visibility = 'public'";
        }

        if (!empty($settings['include_private'])) {
            $conditions[] = "(s.creator_id = ? AND s.visibility IN ('private', 'specific'))";
            $params[] = (int)$userId;
        }

        if (!empty($settings['include_participant'])) {
            $conditions[] = "EXISTS (
                SELECT 1
                FROM schedule_participants sp
                WHERE sp.schedule_id = s.id AND sp.user_id = ?
            )";
            $params[] = (int)$userId;
        }

        if (!empty($settings['include_organization'])) {
            $conditions[] = "EXISTS (
                SELECT 1
                FROM schedule_organizations so
                INNER JOIN user_organizations uo ON uo.organization_id = so.organization_id
                WHERE so.schedule_id = s.id AND uo.user_id = ?
            )";
            $params[] = (int)$userId;
        }

        if (empty($conditions)) {
            return [];
        }

        $sql = "SELECT DISTINCT s.*
                FROM schedules s
                WHERE s.status = 'scheduled'
                  AND s.start_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                  AND s.start_time <= DATE_ADD(NOW(), INTERVAL 180 DAY)
                  AND (" . implode(' OR ', $conditions) . ")
                ORDER BY s.start_time ASC";

        return $this->db->fetchAll($sql, $params);
    }
}

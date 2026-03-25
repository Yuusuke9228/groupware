<?php
// controllers/HomeController.php
namespace Controllers;

use Core\Controller;
use Core\Database;
use Core\Auth;
use Models\Schedule;
use Models\Message;
use Models\Notification;
use Models\User;
use Models\Task; // タスクモデルを追加
use Models\Organization;

class HomeController extends Controller
{
    private $db;
    private $scheduleModel;
    private $messageModel;
    private $notificationModel;
    private $userModel;
    private $taskModel; // タスクモデルのプロパティを追加
    private $organizationModel;

    public function __construct()
    {
        parent::__construct();
        $this->db = Database::getInstance();
        $this->scheduleModel = new Schedule();
        $this->messageModel = new Message();
        $this->notificationModel = new Notification();
        $this->userModel = new User();
        $this->taskModel = new Task(); // タスクモデルをインスタンス化
        $this->organizationModel = new Organization();

        // 認証チェック
        if (!$this->auth->check()) {
            $this->redirect(BASE_PATH . '/login');
        }
    }

    /**
     * トップページを表示
     */
    public function index()
    {
        // 現在のユーザーID
        $userId = $this->auth->id();
        $user = $this->auth->user();

        // 現在の日付
        $today = date('Y-m-d');

        // 日付パラメータの取得（指定がなければ今日）
        $date = $_GET['date'] ?? $today;

        // 日付の妥当性をチェック
        if (!$this->isValidDate($date)) {
            $date = $today;
        }

        // 指定された日付から週の開始日と終了日を計算
        $targetDate = new \DateTime($date);
        $dayOfWeek = $targetDate->format('N'); // 1（月曜日）から 7（日曜日）
        $daysToMonday = $dayOfWeek - 1;

        // 週の開始日（月曜日）を取得
        $weekStart = clone $targetDate;
        $weekStart->modify("-{$daysToMonday} days");

        // 週の終了日（日曜日）を取得
        $weekEnd = clone $weekStart;
        $weekEnd->modify('+6 days');

        $currentWeekStartDate = $weekStart->format('Y-m-d');
        $currentWeekEndDate = $weekEnd->format('Y-m-d');

        // デフォルト組織（主所属）を解決
        $selectedOrganizationId = $this->resolveDefaultOrganizationId($userId);
        $organizationScopeIds = $this->getOrganizationScopeIds($selectedOrganizationId);

        // 選択された週の組織スケジュール取得
        $weekSchedules = $this->getOrganizationSchedulesByDateRange(
            $currentWeekStartDate,
            $currentWeekEndDate,
            $organizationScopeIds
        );


        // 終日予定の重複防止：同じスケジュール ID を持つ終日予定が複数の日付で表示されないようフィルタリング
        $processedAllDayEvents = [];
        foreach ($weekSchedules as $key => $schedule) {
            if ($schedule['all_day']) {
                $scheduleId = $schedule['id'];
                if (in_array($scheduleId, $processedAllDayEvents)) {
                    // すでに処理された終日予定は除外
                    unset($weekSchedules[$key]);
                } else {
                    // 処理済みリストに追加
                    $processedAllDayEvents[] = $scheduleId;
                }
            }
        }

        // スケジュールを開始時間でソート
        usort($weekSchedules, function ($a, $b) {
            if ($a['all_day'] && !$b['all_day']) return -1;
            if (!$a['all_day'] && $b['all_day']) return 1;
            return strtotime($a['start_time']) - strtotime($b['start_time']);
        });

        // 今日の組織スケジュール取得
        $todaySchedules = $this->getOrganizationSchedulesByDateRange(
            $today,
            $today,
            $organizationScopeIds
        );

        // 未読メッセージ取得（最新5件）
        $unreadMessages = $this->getUnreadMessages($userId, 5);

        // 未読通知取得（最新5件）
        $unreadNotifications = $this->notificationModel->getUnread($userId, 5);

        // 未読メッセージ数
        $unreadMessageCount = $this->messageModel->getUnreadCount($userId);

        // 未読通知数
        $unreadNotificationCount = $this->notificationModel->getUnreadCount($userId);

        // 週間カレンダー用のデータ
        $weekDates = [];
        $tempDate = clone $weekStart;
        for ($i = 0; $i < 7; $i++) {
            $weekDates[] = $tempDate->format('Y-m-d');
            $tempDate->modify('+1 day');
        }

        // 組織一覧を取得
        $organizations = $this->organizationModel->getAll();
        $selectedOrganization = $selectedOrganizationId ? $this->organizationModel->getById($selectedOrganizationId) : null;

        // タスク関連の情報を取得
        $taskSummary = $this->taskModel->getUserTasksSummary($userId);
        $upcomingTasks = $this->taskModel->getUserUpcomingTasks($userId, 5);
        $overdueTasks = $this->taskModel->getUserOverdueTasks($userId, 5);
        $boards = $this->taskModel->getUserBoards($userId);

        // 組織メンバーとユーザー別スケジュールを取得
        $orgMembers = $selectedOrganizationId
            ? $this->userModel->getUsersByOrganization($selectedOrganizationId, true)
            : [];

        // ユーザーごとにスケジュールを整理
        $userWeekSchedules = [];
        foreach ($orgMembers as $member) {
            $memberId = (int)$member['id'];
            $userWeekSchedules[$memberId] = [
                'user' => $member,
                'daily' => []
            ];
            foreach ($weekDates as $wd) {
                $userWeekSchedules[$memberId]['daily'][$wd] = [];
            }
        }
        foreach ($weekSchedules as $schedule) {
            $creatorId = (int)$schedule['creator_id'];
            if (!isset($userWeekSchedules[$creatorId])) continue;
            $startDate = date('Y-m-d', strtotime($schedule['start_time']));
            $endDate = date('Y-m-d', strtotime($schedule['end_time']));
            foreach ($weekDates as $wd) {
                if ($wd >= $startDate && $wd <= $endDate) {
                    $userWeekSchedules[$creatorId]['daily'][$wd][] = $schedule;
                }
            }
        }

        $viewData = [
            'title' => 'ホーム',
            'today' => $today,
            'selectedOrganizationId' => $selectedOrganizationId,
            'weekDates' => $weekDates,
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
            'todaySchedules' => $todaySchedules,
            'weekSchedules' => $weekSchedules,
            'userWeekSchedules' => $userWeekSchedules,
            'unreadMessages' => $unreadMessages,
            'unreadNotifications' => $unreadNotifications,
            'unreadMessageCount' => $unreadMessageCount,
            'unreadNotificationCount' => $unreadNotificationCount,
            'user' => $user,
            'organizations' => $organizations,
            'selectedOrganization' => $selectedOrganization,
            // タスク関連のデータを追加
            'taskSummary' => $taskSummary,
            'upcomingTasks' => $upcomingTasks,
            'overdueTasks' => $overdueTasks,
            'boards' => $boards,
            'jsFiles' => ['home.js', 'task.js'] // task.jsを追加
        ];

        $this->view('home/index', $viewData);
    }

    /**
     * 未読メッセージを取得
     */
    private function getUnreadMessages($userId, $limit = 5)
    {
        $sql = "SELECT m.*, 
                    u.display_name as sender_name, 
                    mr.is_read,
                    mr.read_at
                FROM messages m 
                JOIN message_recipients mr ON m.id = mr.message_id AND mr.user_id = ? 
                LEFT JOIN users u ON m.sender_id = u.id 
                WHERE mr.is_deleted = 0 AND mr.is_read = 0
                ORDER BY m.created_at DESC 
                LIMIT ?";

        return $this->db->fetchAll($sql, [$userId, $limit]);
    }

    /**
     * API: 通知とメッセージの未読数を取得
     */
    public function apiGetUnreadCounts()
    {
        // 認証チェック
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }

        $userId = $this->auth->id();

        // 未読メッセージ数
        $unreadMessageCount = $this->messageModel->getUnreadCount($userId);

        // 未読通知数
        $unreadNotificationCount = $this->notificationModel->getUnreadCount($userId);

        return [
            'success' => true,
            'data' => [
                'unread_messages' => $unreadMessageCount,
                'unread_notifications' => $unreadNotificationCount
            ]
        ];
    }

    /**
     * 日付の妥当性をチェック
     *
     * @param string $date 日付文字列（YYYY-MM-DD形式）
     * @return bool 有効な日付ならtrue、そうでなければfalse
     */
    private function isValidDate($date)
    {
        if (!$date) return false;

        try {
            $dt = new \DateTime($date);
            return $dt && $dt->format('Y-m-d') === $date;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function resolveDefaultOrganizationId($userId)
    {
        $userOrganizations = $this->userModel->getUserOrganizations($userId);
        if (!empty($userOrganizations)) {
            return (int)$userOrganizations[0]['id'];
        }

        $user = $this->userModel->getById($userId);
        if (!empty($user['organization_id'])) {
            return (int)$user['organization_id'];
        }

        $organizations = $this->organizationModel->getAll();
        if (!empty($organizations)) {
            return (int)$organizations[0]['id'];
        }

        return null;
    }

    private function getOrganizationScopeIds($organizationId)
    {
        if (!$organizationId) {
            return [];
        }

        $ids = [(int)$organizationId];
        $descendants = $this->organizationModel->getDescendants($organizationId);
        foreach ($descendants as $descendant) {
            $ids[] = (int)$descendant['id'];
        }

        return array_values(array_unique(array_filter($ids)));
    }

    private function getOrganizationSchedulesByDateRange($startDate, $endDate, $organizationIds)
    {
        if (empty($organizationIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($organizationIds), '?'));
        $sql = "SELECT DISTINCT s.*, u.display_name as creator_name
                FROM schedules s
                LEFT JOIN users u ON s.creator_id = u.id
                LEFT JOIN user_organizations uo_creator ON uo_creator.user_id = s.creator_id
                LEFT JOIN schedule_participants sp_member ON sp_member.schedule_id = s.id
                LEFT JOIN users um ON um.id = sp_member.user_id
                LEFT JOIN user_organizations uo_member ON uo_member.user_id = sp_member.user_id
                LEFT JOIN schedule_organizations so ON so.schedule_id = s.id
                WHERE s.start_time <= ? AND s.end_time >= ?
                  AND (
                        uo_creator.organization_id IN ({$placeholders})
                     OR u.organization_id IN ({$placeholders})
                     OR uo_member.organization_id IN ({$placeholders})
                     OR um.organization_id IN ({$placeholders})
                     OR so.organization_id IN ({$placeholders})
                  )
                ORDER BY s.start_time";

        $params = array_merge(
            [$endDate, $startDate],
            $organizationIds,
            $organizationIds,
            $organizationIds,
            $organizationIds,
            $organizationIds
        );

        return $this->db->fetchAll($sql, $params);
    }

}

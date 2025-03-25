<?php
// Controllers/ScheduleController.php
namespace Controllers;

use Core\Controller;
use Core\Database;
use Core\Auth;
use Error;
use Models\Schedule;
use Models\User;
use Models\Organization;
use Models\Notification;

class ScheduleController extends Controller
{
    private $db;
    // private $auth;
    private $schedule;
    private $user;
    private $organization;
    private $notification;

    public function __construct()
    {
        parent::__construct();

        $this->db = Database::getInstance();
        // $this->auth = Auth::getInstance(); は削除（親クラスで設定済み）
        $this->schedule = new Schedule();
        $this->user = new User();
        $this->organization = new Organization();
        $this->notification = new Notification();

        // ユーザーがログインしていない場合はログインページにリダイレクト
        if (!$this->auth->check()) {
            $this->redirect(BASE_PATH . '/login');
        }
    }

    // 日単位表示
    public function day()
    {
        // 日付パラメータの取得（指定がなければ今日）
        $date = $_GET['date'] ?? date('Y-m-d');

        // ユーザーIDパラメータの取得（指定がなければ現在のユーザー）
        $userId = $_GET['user_id'] ?? $this->auth->id();

        // 日付の妥当性をチェック
        if (!$this->isValidDate($date)) {
            $date = date('Y-m-d');
        }

        // 前日、翌日の日付を計算
        $prevDay = date('Y-m-d', strtotime($date . ' -1 day'));
        $nextDay = date('Y-m-d', strtotime($date . ' +1 day'));

        // ユーザー情報を取得
        $user = $this->user->getById($userId);
        if (!$user) {
            $userId = $this->auth->id();
            $user = $this->user->getById($userId);
        }

        // ユーザー一覧を取得（ユーザー切替用）
        $users = $this->user->getActiveUsers();

        $viewData = [
            'title' => date('Y年m月d日', strtotime($date)) . 'のスケジュール',
            'date' => $date,
            'prevDay' => $prevDay,
            'nextDay' => $nextDay,
            'userId' => $userId,
            'user' => $user,
            'users' => $users,
            'jsFiles' => ['schedule.js']
        ];

        $this->view('schedule/day', $viewData);
    }

    // 週単位表示
    public function week()
    {
        // 日付パラメータの取得（指定がなければ今日）
        $date = $_GET['date'] ?? date('Y-m-d');

        // ユーザーIDパラメータの取得（指定がなければ現在のユーザー）
        $userId = $_GET['user_id'] ?? $this->auth->id();

        // 日付の妥当性をチェック
        if (!$this->isValidDate($date)) {
            $date = date('Y-m-d');
        }

        // 週の開始日と終了日を取得（月曜日から日曜日）
        $dayOfWeek = date('N', strtotime($date));
        $weekStart = date('Y-m-d', strtotime($date . ' -' . ($dayOfWeek - 1) . ' days'));
        $weekEnd = date('Y-m-d', strtotime($weekStart . ' +6 days'));

        // 週の日付配列を生成
        $weekDates = [];
        for ($i = 0; $i < 7; $i++) {
            $weekDates[] = date('Y-m-d', strtotime($weekStart . ' +' . $i . ' days'));
        }

        // 前週、翌週の日付を計算
        $prevWeek = date('Y-m-d', strtotime($weekStart . ' -7 days'));
        $nextWeek = date('Y-m-d', strtotime($weekStart . ' +7 days'));

        // ユーザー情報を取得
        $user = $this->user->getById($userId);
        if (!$user) {
            $userId = $this->auth->id();
            $user = $this->user->getById($userId);
        }

        // ユーザー一覧を取得（ユーザー切替用）
        $users = $this->user->getActiveUsers();

        $viewData = [
            'title' => date('Y年m月d日', strtotime($weekStart)) . '～' . date('Y年m月d日', strtotime($weekEnd)) . 'のスケジュール',
            'date' => $date,
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
            'weekDates' => $weekDates,
            'prevWeek' => $prevWeek,
            'nextWeek' => $nextWeek,
            'userId' => $userId,
            'user' => $user,
            'users' => $users,
            'jsFiles' => ['schedule.js']
        ];

        $this->view('schedule/week', $viewData);
    }

    // 月単位表示
    public function month()
    {
        // 年月パラメータの取得（指定がなければ今月）
        $year = isset($_GET['year']) ? (int) $_GET['year'] : (int) date('Y');
        $month = isset($_GET['month']) ? (int) $_GET['month'] : (int) date('m');

        // ユーザーIDパラメータの取得（指定がなければ現在のユーザー）
        $userId = $_GET['user_id'] ?? $this->auth->id();

        // 年月の妥当性をチェック
        if ($year < 1970 || $year > 2099 || $month < 1 || $month > 12) {
            $year = (int) date('Y');
            $month = (int) date('m');
        }

        // 月の日数
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

        // 月の最初の日の曜日（0:日曜日、1:月曜日、...）
        $firstDayOfWeek = date('w', strtotime("$year-$month-01"));

        // 前月、翌月の年月を計算
        $prevMonth = $month - 1;
        $prevYear = $year;
        if ($prevMonth < 1) {
            $prevMonth = 12;
            $prevYear--;
        }

        $nextMonth = $month + 1;
        $nextYear = $year;
        if ($nextMonth > 12) {
            $nextMonth = 1;
            $nextYear++;
        }

        // ユーザー情報を取得
        $user = $this->user->getById($userId);
        if (!$user) {
            $userId = $this->auth->id();
            $user = $this->user->getById($userId);
        }

        // ユーザー一覧を取得（ユーザー切替用）
        $users = $this->user->getActiveUsers();

        $viewData = [
            'title' => $year . '年' . $month . '月のスケジュール',
            'year' => $year,
            'month' => $month,
            'daysInMonth' => $daysInMonth,
            'firstDayOfWeek' => $firstDayOfWeek,
            'prevYear' => $prevYear,
            'prevMonth' => $prevMonth,
            'nextYear' => $nextYear,
            'nextMonth' => $nextMonth,
            'userId' => $userId,
            'user' => $user,
            'users' => $users,
            'jsFiles' => ['schedule.js']
        ];

        $this->view('schedule/month', $viewData);
    }

    // スケジュール新規作成フォーム
    public function create()
    {
        // 日付パラメータの取得（指定がなければ今日）
        $date = $_GET['date'] ?? date('Y-m-d');
        $time = $_GET['time'] ?? '09:00';
        $allDay = isset($_GET['all_day']) ? (bool) $_GET['all_day'] : false;

        // 日付の妥当性をチェック
        if (!$this->isValidDate($date)) {
            $date = date('Y-m-d');
        }

        // 時間の妥当性をチェック
        if (!preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9]$/', $time)) {
            $time = '09:00';
        }

        // 開始時間と終了時間
        $startTime = $date . ' ' . $time;
        $endTime = date('Y-m-d H:i', strtotime($startTime . ' +1 hour'));

        // 組織一覧を取得
        $organizations = $this->organization->getAll();

        // 繰り返しタイプ一覧
        $repeatTypes = [
            'none' => '繰り返しなし',
            'daily' => '毎日',
            'weekly' => '毎週',
            'monthly' => '毎月',
            'yearly' => '毎年'
        ];

        // 優先度一覧
        $priorities = [
            'normal' => '通常',
            'high' => '高',
            'low' => '低'
        ];

        // 公開範囲一覧
        $visibilities = [
            'public' => '全体公開',
            'private' => '非公開',
            'specific' => '特定ユーザーのみ'
        ];

        $viewData = [
            'title' => 'スケジュール新規作成',
            'formTitle' => 'スケジュール新規作成',
            'formAction' => BASE_PATH . '/api/schedule',
            'formMethod' => 'POST',
            'schedule' => [
                'id' => null,
                'title' => '',
                'description' => '',
                'start_time' => $startTime,
                'end_time' => $endTime,
                'all_day' => $allDay,
                'repeat_type' => 'none',
                'repeat_end_date' => '',
                'location' => '',
                'priority' => 'normal',
                'visibility' => 'public',
                'participants' => [],
                'organizations' => []
            ],
            'repeatTypes' => $repeatTypes,
            'priorities' => $priorities,
            'visibilities' => $visibilities,
            'organizations' => $organizations,
            'jsFiles' => ['schedule.js']
        ];

        $this->view('schedule/form', $viewData);
    }

    // スケジュール編集フォーム
    public function edit($params)
    {
        $id = $params['id'] ?? 0;

        // スケジュールデータを取得
        $schedule = $this->schedule->getById($id);

        if (!$schedule) {
            $this->redirect('/schedule');
        }

        // 編集権限チェック
        $canEdit = $this->canEditSchedule($schedule);
        if (!$canEdit) {
            $this->redirect('/schedule/view/' . $id);
        }

        // 参加者一覧を取得
        $participants = $this->schedule->getParticipants($id);

        // 共有組織一覧を取得
        $sharedOrganizations = $this->schedule->getSharedOrganizations($id);

        // 組織一覧を取得
        $organizations = $this->organization->getAll();

        // 繰り返しタイプ一覧
        $repeatTypes = [
            'none' => '繰り返しなし',
            'daily' => '毎日',
            'weekly' => '毎週',
            'monthly' => '毎月',
            'yearly' => '毎年'
        ];

        // 優先度一覧
        $priorities = [
            'normal' => '通常',
            'high' => '高',
            'low' => '低'
        ];

        // 公開範囲一覧
        $visibilities = [
            'public' => '全体公開',
            'private' => '非公開',
            'specific' => '特定ユーザーのみ'
        ];

        $schedule['participants'] = $participants;
        $schedule['organizations'] = $sharedOrganizations;

        $viewData = [
            'title' => 'スケジュール編集',
            'formTitle' => 'スケジュール編集',
            'formAction' => BASE_PATH . '/api/schedule/' . $id,
            'formMethod' => 'POST',
            'schedule' => $schedule,
            'repeatTypes' => $repeatTypes,
            'priorities' => $priorities,
            'visibilities' => $visibilities,
            'organizations' => $organizations,
            'jsFiles' => ['schedule.js']
        ];

        $this->view('schedule/form', $viewData);
    }

    // 組織の詳細ページを表示 (メソッド名を変更)
    public function viewDetails($params)
    {
        $id = $params['id'] ?? null;
        if (!$id) {
            $this->redirect(BASE_PATH . '/schedule');
        }

        // スケジュールデータを取得
        $schedule = $this->schedule->getById($id);
        if (!$schedule) {
            $this->redirect(BASE_PATH . '/schedule');
        }

        // 閲覧権限チェック
        $canView = $this->canViewSchedule($schedule);
        if (!$canView) {
            $this->redirect(BASE_PATH . '/schedule');
        }

        // 編集権限チェック
        $canEdit = $this->canEditSchedule($schedule);

        // 削除権限チェック
        $canDelete = $this->canDeleteSchedule($schedule);

        // 参加者一覧を取得
        $participants = $this->schedule->getParticipants($id);

        // 共有組織一覧を取得
        $sharedOrganizations = $this->schedule->getSharedOrganizations($id);

        // 現在のユーザーの参加ステータス
        $participationStatus = $this->schedule->getUserParticipationStatus($id, $this->auth->id());

        $viewData = [
            'title' => $schedule['title'],
            'schedule' => $schedule,
            'participants' => $participants,
            'sharedOrganizations' => $sharedOrganizations,
            'participationStatus' => $participationStatus,
            'canEdit' => $canEdit,
            'canDelete' => $canDelete,
            'jsFiles' => ['schedule.js']
        ];

        $this->view('schedule/view', $viewData);
    }


    // 日単位スケジュールAPI
    public function apiGetDay($params)
    {
        // $date = $params['date'] ?? date('Y-m-d');
        // $userId = $params['user_id'] ?? $this->auth->id();
        // パラメータが欠落している場合は直接$_GETから取得
        $date = isset($params['date']) ? $params['date'] : (isset($_GET['date']) ? $_GET['date'] : date('Y-m-d'));
        $userId = isset($params['user_id']) ? $params['user_id'] : (isset($_GET['user_id']) ? $_GET['user_id'] : $this->auth->id());


        // 日付の妥当性をチェック
        if (!$this->isValidDate($date)) {
            $date = date('Y-m-d');
        }

        // ユーザーの妥当性をチェック
        $user = $this->user->getById($userId);
        if (!$user) {
            $userId = $this->auth->id();
        }

        // スケジュールデータを取得
        $schedules = $this->schedule->getByDay($date, $userId);

        // 閲覧権限でフィルタリング
        $filteredSchedules = array_filter($schedules, [$this, 'canViewSchedule']);

        return [
            'success' => true,
            'data' => array_values($filteredSchedules)
        ];
    }

    // 週単位スケジュールAPI
    public function apiGetWeek($params)
    {
        // パラメータが欠落している場合は直接$_GETから取得
        $date = isset($params['date']) ? $params['date'] : (isset($_GET['date']) ? $_GET['date'] : date('Y-m-d'));
        $userId = isset($params['user_id']) ? $params['user_id'] : (isset($_GET['user_id']) ? $_GET['user_id'] : $this->auth->id());
        error_log("API Get Week called with date: " . $date . ", user_id: " . $userId);

        // 日付の妥当性をチェック
        if (!$this->isValidDate($date)) {
            $date = date('Y-m-d');
        }

        // ユーザーの妥当性をチェック
        $user = $this->user->getById($userId);
        if (!$user) {
            $userId = $this->auth->id();
        }

        // 週の開始日と終了日を取得（月曜日から日曜日）
        // ここが問題: 渡された日付に基づいて週の日付を計算する必要がある
        $momentDate = new \DateTime($date);
        $dayOfWeek = (int)$momentDate->format('N'); // 1（月曜日）から7（日曜日）

        // 現在の日付から週の開始日（月曜日）を計算
        $daysToSubtract = $dayOfWeek - 1;
        $weekStart = clone $momentDate;
        $weekStart->modify("-{$daysToSubtract} days");

        // 週の終了日（日曜日）を計算
        $weekEnd = clone $weekStart;
        $weekEnd->modify('+6 days');

        $startDate = $weekStart->format('Y-m-d');
        $endDate = $weekEnd->format('Y-m-d');

        // 週の日付配列を生成
        $weekDates = [];
        $currentDate = clone $weekStart;
        for ($i = 0; $i < 7; $i++) {
            $weekDates[] = $currentDate->format('Y-m-d');
            $currentDate->modify('+1 day');
        }

        // スケジュールデータを取得
        $schedules = $this->schedule->getByDateRange($startDate, $endDate, $userId);

        // 閲覧権限でフィルタリング
        $filteredSchedules = array_filter($schedules, [$this, 'canViewSchedule']);

        return [
            'success' => true,
            'data' => [
                'week_dates' => $weekDates,
                'schedules' => array_values($filteredSchedules)
            ]
        ];
    }

    // 月単位スケジュールAPI
    public function apiGetMonth($params)
    {
        // $year = $params['year'] ?? date('Y');
        // $month = $params['month'] ?? date('m');
        // $userId = $params['user_id'] ?? $this->auth->id();
        // パラメータが欠落している場合は直接$_GETから取得
        $year = isset($params['year']) ? $params['year'] : (isset($_GET['year']) ? $_GET['year'] : date('Y'));
        $month = isset($params['month']) ? $params['month'] : (isset($_GET['month']) ? $_GET['month'] : date('m'));
        $userId = isset($params['user_id']) ? $params['user_id'] : (isset($_GET['user_id']) ? $_GET['user_id'] : $this->auth->id());

        // 年月の妥当性をチェック
        if ($year < 1970 || $year > 2099 || $month < 1 || $month > 12) {
            $year = date('Y');
            $month = date('m');
        }

        // ユーザーの妥当性をチェック
        $user = $this->user->getById($userId);
        if (!$user) {
            $userId = $this->auth->id();
        }

        // 月の開始日と終了日
        $startDate = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));

        // 月の日数
        $daysInMonth = date('t', strtotime($startDate));

        // 月の最初の日の曜日（0:日曜日、1:月曜日、...）
        $firstDayOfWeek = date('w', strtotime($startDate));

        // スケジュールデータを取得
        $schedules = $this->schedule->getByDateRange($startDate, $endDate, $userId);
        // apiGetMonth メソッド内に追加
        // テーブルのデータを確認
        $db = \Core\Database::getInstance();
        $participantsSql = "SELECT * FROM schedule_participants";
        $participantsResults = $db->fetchAll($participantsSql);
        error_log("participants table data: " . json_encode($participantsResults));

        $orgsSql = "SELECT * FROM schedule_organizations";
        $orgsResults = $db->fetchAll($orgsSql);
        error_log("organizations table data: " . json_encode($orgsResults));
        error_log(json_encode(['schedules' => $schedules]));

        // 閲覧権限でフィルタリング
        $filteredSchedules = array_filter($schedules, [$this, 'canViewSchedule']);

        return [
            'success' => true,
            'data' => [
                'days_in_month' => $daysInMonth,
                'first_day_of_week' => $firstDayOfWeek,
                'schedules' => array_values($filteredSchedules)
            ]
        ];
    }

    // 日付範囲でスケジュール取得API
    public function apiGetByDateRange($params)
    {
        $startDate = $params['start_date'] ?? date('Y-m-d');
        $endDate = $params['end_date'] ?? date('Y-m-d', strtotime('+30 days'));
        $userId = $params['user_id'] ?? $this->auth->id();
        error_log("getByDateRange called: startDate=$startDate, endDate=$endDate, userId=$userId");
        // 日付の妥当性をチェック
        if (!$this->isValidDate($startDate)) {
            $startDate = date('Y-m-d');
        }

        if (!$this->isValidDate($endDate)) {
            $endDate = date('Y-m-d', strtotime('+30 days'));
        }

        // ユーザーの妥当性をチェック
        $user = $this->user->getById($userId);
        if (!$user) {
            $userId = $this->auth->id();
        }

        // スケジュールデータを取得
        $schedules = $this->schedule->getByDateRange($startDate, $endDate, $userId);

        // 閲覧権限でフィルタリング
        $filteredSchedules = array_filter($schedules, [$this, 'canViewSchedule']);

        return [
            'success' => true,
            'data' => array_values($filteredSchedules)
        ];
    }

    // スケジュール詳細取得API
    public function apiGetOne($params)
    {
        $id = $params['id'] ?? 0;

        // スケジュールデータを取得
        $schedule = $this->schedule->getById($id);

        if (!$schedule) {
            return [
                'success' => false,
                'error' => 'スケジュールが見つかりません'
            ];
        }

        // 閲覧権限チェック
        $canView = $this->canViewSchedule($schedule);
        if (!$canView) {
            return [
                'success' => false,
                'error' => 'スケジュールの閲覧権限がありません'
            ];
        }

        // 参加者一覧を取得
        $participants = $this->schedule->getParticipants($id);

        // 共有組織一覧を取得
        $sharedOrganizations = $this->schedule->getSharedOrganizations($id);

        $schedule['participants'] = $participants;
        $schedule['organizations'] = $sharedOrganizations;

        return [
            'success' => true,
            'data' => $schedule
        ];
    }

    // API: スケジュール新規作成
    public function apiCreate($params, $data)
    {
        // error_log(json_encode(['postdata:' => $data]));
        $validation = $this->validateScheduleData($data);
        if (!empty($validation)) {
            return [
                'success' => false,
                'error' => '入力内容に誤りがあります',
                'validation' => $validation
            ];
        }

        // 現在のユーザーIDを取得
        $userId = $this->auth->id();

        // スケジュールデータを作成
        $scheduleData = [
            'title' => $data['title'],
            'description' => $data['description'] ?? '',
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'all_day' => isset($data['all_day']) ? 1 : 0,
            'repeat_type' => $data['repeat_type'] ?? 'none',
            'repeat_end_date' => $data['repeat_end_date'] ?? null,
            'location' => $data['location'] ?? '',
            'priority' => $data['priority'] ?? 'normal',
            'visibility' => $data['visibility'] ?? 'public',
            'creator_id' => $userId,
            'created_at' => date('Y-m-d H:i:s')
        ];

        // スケジュールを作成
        $scheduleId = $this->schedule->create($scheduleData);

        if (!$scheduleId) {
            return [
                'success' => false,
                'error' => 'スケジュールの作成に失敗しました'
            ];
        }

        // 参加者を追加
        $participants = isset($data['participants']) ? $data['participants'] : [];

        // 配列でない場合の処理を修正
        if (!is_array($participants)) {
            if (is_string($participants) && !empty($participants)) {
                $participants = explode(',', $participants);
            } else {
                $participants = [];
            }
        }

        // 作成者を参加者に追加
        if (!in_array($userId, $participants)) {
            $participants[] = $userId;
        }

        // 参加者のフィルタリングを確実に
        $participants = array_filter($participants, function ($id) {
            return !empty($id) && is_numeric($id);
        });
        $participants = array_unique($participants);

        // デバッグ出力を追加
        // error_log("Schedule ID: " . $scheduleId);
        // error_log("Participants: " . print_r($participants, true));

        // 参加者を追加
        foreach ($participants as $participantId) {
            // IDの型を確保
            $participantId = (int)$participantId;
            if (!$participantId) continue;

            // 参加者が自分自身の場合は「参加」、それ以外は「未回答」
            $status = ($participantId == $userId) ? 'accepted' : 'pending';

            // 参加者追加
            $result = $this->schedule->addParticipant($scheduleId, $participantId, $status);
            if (!$result) {
                error_log("Failed to add participant: $participantId");
            }
        }

        // 共有組織を追加
        $organizations = isset($data['organizations']) ? $data['organizations'] : [];
        if (!is_array($organizations)) {
            if (is_string($organizations) && !empty($organizations)) {
                $organizations = explode(',', $organizations);
            } else {
                $organizations = [];
            }
        }

        // 組織IDのフィルタリング
        $organizations = array_filter($organizations, function ($id) {
            return !empty($id) && is_numeric($id);
        });

        foreach ($organizations as $organizationId) {
            $this->schedule->addSharedOrganization($scheduleId, (int)$organizationId);
        }

        // 通知を送信
        $this->sendScheduleNotifications($scheduleId, 'create', $data);

        return [
            'success' => true,
            'message' => 'スケジュールが正常に作成されました',
            'data' => [
                'id' => $scheduleId
            ]
        ];
    }

    // スケジュール更新API
    public function apiUpdate($params, $data)
    {
        $id = $params['id'] ?? 0;

        // スケジュールデータを取得
        $schedule = $this->schedule->getById($id);

        if (!$schedule) {
            return [
                'success' => false,
                'error' => 'スケジュールが見つかりません'
            ];
        }

        // 編集権限チェック
        $canEdit = $this->canEditSchedule($schedule);
        if (!$canEdit) {
            return [
                'success' => false,
                'error' => 'スケジュールの編集権限がありません'
            ];
        }

        // バリデーション
        $validation = $this->validateScheduleData($data);
        if (!empty($validation)) {
            return [
                'success' => false,
                'error' => '入力内容に誤りがあります',
                'validation' => $validation
            ];
        }

        // スケジュールデータを更新
        $scheduleData = [
            'title' => $data['title'],
            'description' => $data['description'] ?? '',
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'all_day' => isset($data['all_day']) ? 1 : 0,
            'repeat_type' => $data['repeat_type'] ?? 'none',
            'repeat_end_date' => $data['repeat_end_date'] ?? null,
            'location' => $data['location'] ?? '',
            'priority' => $data['priority'] ?? 'normal',
            'visibility' => $data['visibility'] ?? 'public',
            'updated_at' => date('Y-m-d H:i:s')
        ];

        // スケジュールを更新
        $result = $this->schedule->update($id, $scheduleData);

        if (!$result) {
            return [
                'success' => false,
                'error' => 'スケジュールの更新に失敗しました'
            ];
        }

        // 参加者を更新
        $participants = isset($data['participants']) ? $data['participants'] : [];
        if (!is_array($participants)) {
            $participants = [];
        }

        // 作成者を参加者に追加
        $participants[] = $schedule['creator_id'];
        $participants = array_unique($participants);

        // 現在の参加者を削除
        $this->schedule->removeAllParticipants($id);

        // 参加者を追加
        foreach ($participants as $participantId) {
            // 既存の参加者のステータスを取得
            $status = $this->schedule->getUserParticipationStatus($id, $participantId);

            // ステータスがない場合は、作成者なら「参加」、それ以外は「未回答」
            if (!$status) {
                $status = ($participantId == $schedule['creator_id']) ? 'accepted' : 'pending';
            }

            $this->schedule->addParticipant($id, $participantId, $status);
        }

        // 共有組織を更新
        $organizations = isset($data['organizations']) ? $data['organizations'] : [];
        if (!is_array($organizations)) {
            $organizations = [];
        }

        // 現在の共有組織を削除
        $this->schedule->removeAllSharedOrganizations($id);

        // 共有組織を追加
        foreach ($organizations as $organizationId) {
            $this->schedule->addSharedOrganization($id, $organizationId);
        }

        // 通知を送信
        $this->sendScheduleNotifications($id, 'update', $data);

        return [
            'success' => true,
            'message' => 'スケジュールが正常に更新されました',
            'data' => [
                'id' => $id,
                'redirect' => BASE_PATH . '/schedule/view/' . $id
            ]
        ];
    }

    // スケジュール削除API
    public function apiDelete($params)
    {
        $id = $params['id'] ?? 0;

        // スケジュールデータを取得
        $schedule = $this->schedule->getById($id);

        if (!$schedule) {
            return [
                'success' => false,
                'error' => 'スケジュールが見つかりません'
            ];
        }

        // 削除権限チェック
        $canDelete = $this->canDeleteSchedule($schedule);
        if (!$canDelete) {
            return [
                'success' => false,
                'error' => 'スケジュールの削除権限がありません'
            ];
        }

        // スケジュールを削除
        $result = $this->schedule->delete($id);

        if (!$result) {
            return [
                'success' => false,
                'error' => 'スケジュールの削除に失敗しました'
            ];
        }

        // 通知を送信
        $this->sendScheduleNotifications($id, 'delete', $schedule);

        return [
            'success' => true,
            'message' => 'スケジュールが正常に削除されました',
            'data' => [
                'redirect' => BASE_PATH . '/schedule'
            ]
        ];
    }

    // 参加ステータス更新API
    public function apiUpdateParticipantStatus($params, $data)
    {
        $id = $params['id'] ?? 0;
        $status = $data['status'] ?? '';

        // スケジュールデータを取得
        $schedule = $this->schedule->getById($id);

        if (!$schedule) {
            return [
                'success' => false,
                'error' => 'スケジュールが見つかりません'
            ];
        }

        // 閲覧権限チェック
        $canView = $this->canViewSchedule($schedule);
        if (!$canView) {
            return [
                'success' => false,
                'error' => 'スケジュールの閲覧権限がありません'
            ];
        }

        // ステータスのバリデーション
        $validStatuses = ['pending', 'accepted', 'declined', 'tentative'];
        if (!in_array($status, $validStatuses)) {
            return [
                'success' => false,
                'error' => '無効なステータスです'
            ];
        }

        // 現在のユーザーIDを取得
        $userId = $this->auth->id();

        // 参加者かどうかチェック
        $isParticipant = $this->schedule->isParticipant($id, $userId);
        if (!$isParticipant) {
            return [
                'success' => false,
                'error' => 'このスケジュールの参加者ではありません'
            ];
        }

        // 参加ステータスを更新
        $result = $this->schedule->updateParticipantStatus($id, $userId, $status);

        if (!$result) {
            return [
                'success' => false,
                'error' => '参加ステータスの更新に失敗しました'
            ];
        }

        // 作成者に通知
        // if ($request['creator_id'] != $userId) {
        //     $this->sendScheduleStatusNotification($id, $userId, $status);
        // }

        return [
            'success' => true,
            'message' => '参加ステータスが正常に更新されました',
            'data' => [
                'status' => $status
            ]
        ];
    }

    // 参加者追加API
    public function apiAddParticipant($params, $data)
    {
        $id = $params['id'] ?? 0;
        $userId = $data['user_id'] ?? 0;
        $status = $data['status'] ?? 'pending';

        // スケジュールデータを取得
        $schedule = $this->schedule->getById($id);

        if (!$schedule) {
            return [
                'success' => false,
                'error' => 'スケジュールが見つかりません'
            ];
        }

        // 編集権限チェック
        $canEdit = $this->canEditSchedule($schedule);
        if (!$canEdit) {
            return [
                'success' => false,
                'error' => 'スケジュールの編集権限がありません'
            ];
        }

        // ユーザーの存在チェック
        $user = $this->user->getById($userId);
        if (!$user) {
            return [
                'success' => false,
                'error' => 'ユーザーが見つかりません'
            ];
        }

        // ステータスのバリデーション
        $validStatuses = ['pending', 'accepted', 'declined', 'tentative'];
        if (!in_array($status, $validStatuses)) {
            $status = 'pending';
        }

        // 参加者を追加
        $result = $this->schedule->addParticipant($id, $userId, $status);

        if (!$result) {
            return [
                'success' => false,
                'error' => '参加者の追加に失敗しました'
            ];
        }

        return [
            'success' => true,
            'message' => '参加者が正常に追加されました',
            'data' => [
                'user_id' => $userId,
                'status' => $status
            ]
        ];
    }

    // 参加者削除API
    public function apiRemoveParticipant($params, $data)
    {
        $id = $params['id'] ?? 0;
        $userId = $data['user_id'] ?? 0;

        // スケジュールデータを取得
        $schedule = $this->schedule->getById($id);

        if (!$schedule) {
            return [
                'success' => false,
                'error' => 'スケジュールが見つかりません'
            ];
        }

        // 権限チェック（作成者、管理者、または自分自身の参加のみ削除可能）
        $currentUserId = $this->auth->id();
        $canRemove = ($schedule['creator_id'] == $currentUserId || $this->auth->isAdmin() || $userId == $currentUserId);

        if (!$canRemove) {
            return [
                'success' => false,
                'error' => '参加者の削除権限がありません'
            ];
        }

        // 作成者は削除不可
        if ($userId == $schedule['creator_id']) {
            return [
                'success' => false,
                'error' => '作成者は参加者から削除できません'
            ];
        }

        // 参加者を削除
        $result = $this->schedule->removeParticipant($id, $userId);

        if (!$result) {
            return [
                'success' => false,
                'error' => '参加者の削除に失敗しました'
            ];
        }

        return [
            'success' => true,
            'message' => '参加者が正常に削除されました',
            'data' => [
                'user_id' => $userId
            ]
        ];
    }

    // 組織共有追加API
    public function apiAddOrganization($params, $data)
    {
        $id = $params['id'] ?? 0;
        $organizationId = $data['organization_id'] ?? 0;

        // スケジュールデータを取得
        $schedule = $this->schedule->getById($id);

        if (!$schedule) {
            return [
                'success' => false,
                'error' => 'スケジュールが見つかりません'
            ];
        }

        // 編集権限チェック
        $canEdit = $this->canEditSchedule($schedule);
        if (!$canEdit) {
            return [
                'success' => false,
                'error' => 'スケジュールの編集権限がありません'
            ];
        }

        // 組織の存在チェック
        $organization = $this->organization->getById($organizationId);
        if (!$organization) {
            return [
                'success' => false,
                'error' => '組織が見つかりません'
            ];
        }

        // 組織共有を追加
        $result = $this->schedule->addSharedOrganization($id, $organizationId);

        if (!$result) {
            return [
                'success' => false,
                'error' => '組織共有の追加に失敗しました'
            ];
        }

        return [
            'success' => true,
            'message' => '組織共有が正常に追加されました',
            'data' => [
                'organization_id' => $organizationId
            ]
        ];
    }

    // 組織共有削除API
    public function apiRemoveOrganization($params, $data)
    {
        $id = $params['id'] ?? 0;
        $organizationId = $data['organization_id'] ?? 0;

        // スケジュールデータを取得
        $schedule = $this->schedule->getById($id);

        if (!$schedule) {
            return [
                'success' => false,
                'error' => 'スケジュールが見つかりません'
            ];
        }

        // 編集権限チェック
        $canEdit = $this->canEditSchedule($schedule);
        if (!$canEdit) {
            return [
                'success' => false,
                'error' => 'スケジュールの編集権限がありません'
            ];
        }

        // 組織共有を削除
        $result = $this->schedule->removeSharedOrganization($id, $organizationId);

        if (!$result) {
            return [
                'success' => false,
                'error' => '組織共有の削除に失敗しました'
            ];
        }

        return [
            'success' => true,
            'message' => '組織共有が正常に削除されました',
            'data' => [
                'organization_id' => $organizationId
            ]
        ];
    }

    // 日付の妥当性チェック
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

    // スケジュールデータのバリデーション
    private function validateScheduleData($data)
    {
        $errors = [];

        // タイトルは必須
        if (empty($data['title'])) {
            $errors['title'] = 'タイトルは必須です';
        }

        // 開始日時は必須
        if (empty($data['start_time'])) {
            $errors['start_time'] = '開始日時は必須です';
        }

        // 終了日時は必須
        if (empty($data['end_time'])) {
            $errors['end_time'] = '終了日時は必須です';
        }

        // 開始日時 <= 終了日時
        if (!empty($data['start_time']) && !empty($data['end_time'])) {
            $startTime = strtotime($data['start_time']);
            $endTime = strtotime($data['end_time']);

            if ($startTime > $endTime) {
                $errors['end_time'] = '終了日時は開始日時以降にしてください';
            }
        }

        // 繰り返し設定のチェック
        if (!empty($data['repeat_type']) && $data['repeat_type'] !== 'none') {
            if (empty($data['repeat_end_date'])) {
                $errors['repeat_end_date'] = '繰り返し設定を使用する場合は、終了日を設定してください';
            } else {
                $repeatEndDate = strtotime($data['repeat_end_date']);
                $startDate = strtotime(date('Y-m-d', strtotime($data['start_time'])));

                if ($repeatEndDate < $startDate) {
                    $errors['repeat_end_date'] = '繰り返し終了日は開始日以降にしてください';
                }
            }
        }

        return $errors;
    }

    // スケジュールの閲覧権限チェック
    private function canViewSchedule($schedule)
    {
        if (!$schedule) return false;

        $userId = $this->auth->id();

        // 管理者は全て閲覧可能
        if ($this->auth->isAdmin()) return true;

        // 自分が作成したスケジュールは閲覧可能
        if ($schedule['creator_id'] == $userId) return true;

        // 公開スケジュールは閲覧可能
        if ($schedule['visibility'] === 'public') return true;

        // 非公開スケジュールは作成者のみ閲覧可能
        if ($schedule['visibility'] === 'private') {
            return $schedule['creator_id'] == $userId;
        }

        // 特定ユーザーのみ公開の場合
        if ($schedule['visibility'] === 'specific') {
            // 参加者かどうかチェック
            $isParticipant = $this->schedule->isParticipant($schedule['id'], $userId);
            if ($isParticipant) return true;

            // 共有組織のメンバーかどうかチェック
            $sharedOrganizations = $this->schedule->getSharedOrganizations($schedule['id']);
            $userOrganizations = $this->user->getUserOrganizationIds($userId);

            foreach ($sharedOrganizations as $org) {
                if (in_array($org['id'], $userOrganizations)) {
                    return true;
                }
            }
        }

        return false;
    }

    // スケジュールの編集権限チェック
    private function canEditSchedule($schedule)
    {
        if (!$schedule) return false;

        $userId = $this->auth->id();

        // 管理者は全て編集可能
        if ($this->auth->isAdmin()) return true;

        // 自分が作成したスケジュールのみ編集可能
        return $schedule['creator_id'] == $userId;
    }

    // スケジュールの削除権限チェック
    private function canDeleteSchedule($schedule)
    {
        // 編集権限と同じ
        return $this->canEditSchedule($schedule);
    }

    /**
     * スケジュール関連の通知を送信
     * 
     * @param int $scheduleId スケジュールID
     * @param string $action アクション（create, update, delete）
     * @param array $data スケジュールデータ
     */
    private function sendScheduleNotifications($scheduleId, $action, $data)
    {
        // スケジュールの詳細情報を取得
        $schedule = null;
        if ($action === 'delete') {
            $schedule = $data; // 削除の場合は既に取得済み
        } else {
            $schedule = $this->schedule->getById($scheduleId);
        }

        if (!$schedule) {
            return;
        }

        // 参加者一覧を取得
        $participants = [];
        if ($action !== 'delete') {
            $participants = $this->schedule->getParticipants($scheduleId);
        } elseif (isset($data['participants']) && is_array($data['participants'])) {
            $participants = array_map(function ($id) {
                return ['id' => $id];
            }, $data['participants']);
        }

        // 通知タイトルと内容を準備
        $title = '';
        $content = '';
        $creatorName = $this->user->getById($schedule['creator_id'])['display_name'] ?? 'ユーザー';

        switch ($action) {
            case 'create':
                $title = '新しいスケジュールが作成されました';
                $content = "{$creatorName}さんがあなたを含むスケジュールを作成しました。\n";
                $content .= "タイトル: {$schedule['title']}\n";
                $content .= "日時: " . date('Y年m月d日 H:i', strtotime($schedule['start_time']));
                if ($schedule['end_time']) {
                    $content .= " 〜 " . date('Y年m月d日 H:i', strtotime($schedule['end_time']));
                }
                break;

            case 'update':
                $title = 'スケジュールが更新されました';
                $content = "{$creatorName}さんがスケジュールを更新しました。\n";
                $content .= "タイトル: {$schedule['title']}\n";
                $content .= "日時: " . date('Y年m月d日 H:i', strtotime($schedule['start_time']));
                if ($schedule['end_time']) {
                    $content .= " 〜 " . date('Y年m月d日 H:i', strtotime($schedule['end_time']));
                }
                break;

            case 'delete':
                $title = 'スケジュールが削除されました';
                $content = "{$creatorName}さんによってスケジュールが削除されました。\n";
                $content .= "タイトル: {$schedule['title']}\n";
                $content .= "日時: " . date('Y年m月d日 H:i', strtotime($schedule['start_time']));
                if ($schedule['end_time']) {
                    $content .= " 〜 " . date('Y年m月d日 H:i', strtotime($schedule['end_time']));
                }
                break;
        }

        // リンク
        $link = "/schedule/view/{$scheduleId}";
        if ($action === 'delete') {
            $link = "/schedule";
        }

        // 作成者自身には通知しない
        $currentUserId = $this->auth->id();

        // 参加者に通知を送信
        foreach ($participants as $participant) {
            // 自分自身には通知しない
            if ($participant['id'] == $currentUserId) {
                continue;
            }

            // 通知データ
            $notificationData = [
                'user_id' => $participant['id'],
                'type' => 'schedule',
                'title' => $title,
                'content' => $content,
                'link' => $link,
                'reference_id' => $action === 'delete' ? null : $scheduleId,
                'reference_type' => 'schedule'
            ];

            // 通知を送信
            $this->notification->create($notificationData);
        }
    }

    /**
     * 参加ステータス更新の通知を送信
     * 
     * @param int $scheduleId スケジュールID
     * @param int $userId ステータスを更新したユーザーID
     * @param string $status 更新後のステータス
     */
    private function sendScheduleStatusNotification($scheduleId, $userId, $status)
    {
        // スケジュールを取得
        $schedule = $this->schedule->getById($scheduleId);
        if (!$schedule) {
            return;
        }

        // 更新者の情報を取得
        $user = $this->user->getById($userId);
        if (!$user) {
            return;
        }

        // ステータスの日本語表現
        $statusText = '';
        switch ($status) {
            case 'accepted':
                $statusText = '参加します';
                break;
            case 'declined':
                $statusText = '不参加';
                break;
            case 'tentative':
                $statusText = '未定';
                break;
            default:
                $statusText = $status;
                break;
        }

        // 通知データ
        $notificationData = [
            'user_id' => $schedule['creator_id'],
            'type' => 'schedule',
            'title' => 'スケジュール参加ステータスが更新されました',
            'content' => "{$user['display_name']}さんが「{$schedule['title']}」の参加ステータスを「{$statusText}」に更新しました。",
            'link' => "/schedule/view/{$scheduleId}",
            'reference_id' => $scheduleId,
            'reference_type' => 'schedule'
        ];

        // 通知を送信
        $this->notification->create($notificationData);
    }
}

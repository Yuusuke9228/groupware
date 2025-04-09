<?php
// Controllers/DailyReportController.php
namespace Controllers;

use Core\Controller;
use Core\Database;
use Core\Auth;
use Models\DailyReport;
use Models\Schedule;
use Models\Task;
use Models\Notification;
use Models\User;

class DailyReportController extends Controller
{
    private $db;
    private $reportModel;
    private $scheduleModel;
    private $taskModel;
    private $userModel;
    private $notificationModel;

    public function __construct()
    {
        parent::__construct();
        $this->db = Database::getInstance();
        $this->reportModel = new DailyReport();
        $this->scheduleModel = new Schedule();
        $this->taskModel = new Task();
        $this->userModel = new User();
        $this->notificationModel = new Notification();

        // 認証チェック
        if (!$this->auth->check()) {
            $this->redirect(BASE_PATH . '/login');
        }
    }

    /**
     * 日報のダッシュボードを表示する
     */
    public function index()
    {
        $userId = $this->auth->id();
        $user = $this->auth->user();

        // 本日の日付
        $today = date('Y-m-d');

        // 統計情報を取得（直近30日）
        $startDate = date('Y-m-d', strtotime('-30 days'));
        $stats = $this->reportModel->getUserStats($userId, $startDate);

        // 最近の日報を取得（最新5件）
        $recentReports = $this->reportModel->getUserReports($userId, [], 1, 5);

        // 本日の日報があるかチェック
        $hasTodayReport = $this->reportModel->hasReportForDate($userId, $today);
        $todayReport = $hasTodayReport ? $this->reportModel->getByDate($userId, $today) : null;

        // タグ一覧を取得
        $tags = $this->reportModel->getUserTags($userId);

        // 読んでない日報を取得（他のユーザーの最新5件）
        $unreadReports = $this->getUnreadReports($userId, 5);

        $viewData = [
            'title' => '日報',
            'user' => $user,
            'stats' => $stats,
            'recent_reports' => $recentReports,
            'has_today_report' => $hasTodayReport,
            'today_report' => $todayReport,
            'tags' => $tags,
            'unread_reports' => $unreadReports,
            'jsFiles' => ['daily-report.js']
        ];

        $this->view('daily_report/index', $viewData);
    }

    /**
     * 日報一覧を表示する
     */
    public function list()
    {
        $userId = $this->auth->id();
        $user = $this->auth->user();

        // フィルター条件
        $filters = [
            'start_date' => $_GET['start_date'] ?? null,
            'end_date' => $_GET['end_date'] ?? null,
            'user_id' => $_GET['user_id'] ?? null,
            'tag_id' => $_GET['tag_id'] ?? null,
            'search' => $_GET['search'] ?? null
        ];

        // ページネーション
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $page = max(1, $page);
        $limit = 20;

        // 日報一覧を取得
        if (!empty($filters['user_id']) && $filters['user_id'] == $userId) {
            // 自分の日報のみ表示
            $reports = $this->reportModel->getUserReports($userId, $filters, $page, $limit);
            $totalReports = $this->reportModel->getCount($userId, $filters);
        } else {
            // 読み取り可能な日報を表示
            $reports = $this->reportModel->getReadableReports($userId, $filters, $page, $limit);
            // 総数の取得は複雑なのでAPIで非同期に取得
            $totalReports = 0;
        }

        $totalPages = ceil($totalReports / $limit);

        // ユーザー一覧を取得（フィルター用）
        $users = $this->userModel->getActiveUsers();

        // タグ一覧を取得
        $tags = $this->reportModel->getUserTags($userId);
        $publicTags = $this->reportModel->getPublicTags();

        $viewData = [
            'title' => '日報一覧',
            'reports' => $reports,
            'totalReports' => $totalReports,
            'page' => $page,
            'totalPages' => $totalPages,
            'filters' => $filters,
            'users' => $users,
            'tags' => $tags,
            'publicTags' => $publicTags,
            'jsFiles' => ['daily-report.js']
        ];

        $this->view('daily_report/list', $viewData);
    }

    /**
     * 日報作成画面を表示する
     */
    public function create()
    {
        $userId = $this->auth->id();
        $user = $this->auth->user();

        // 日付パラメータの取得（指定がなければ今日）
        $date = $_GET['date'] ?? date('Y-m-d');

        // テンプレート一覧を取得
        $templates = $this->reportModel->getTemplates($userId);

        // テンプレートID指定があればロード
        $templateId = $_GET['template_id'] ?? null;
        $template = null;
        if ($templateId) {
            $template = $this->reportModel->getTemplateById($templateId);
        }

        // その日の予定を取得
        $schedules = $this->scheduleModel->getByDay($date, $userId);

        // 完了したタスクを取得
        $completedTasks = $this->getCompletedTasks($userId, $date);

        // 組織一覧を取得
        $organizationModel = new \Models\Organization();
        $organizations = $organizationModel->getAll();

        // ユーザー一覧を取得
        $users = $this->userModel->getActiveUsers();

        $viewData = [
            'title' => '日報作成',
            'user' => $user,
            'date' => $date,
            'templates' => $templates,
            'template' => $template,
            'schedules' => $schedules,
            'tasks' => $completedTasks,
            'organizations' => $organizations,
            'users' => $users,
            'jsFiles' => ['daily-report.js']
        ];

        $this->view('daily_report/form', $viewData);
    }

    /**
     * 日報編集画面を表示する
     */
    public function edit($params)
    {
        $id = $params['id'] ?? null;
        if (!$id) {
            $this->redirect(BASE_PATH . '/daily-report');
        }

        $userId = $this->auth->id();
        $user = $this->auth->user();

        // 日報データを取得
        $report = $this->reportModel->getById($id);
        if (!$report) {
            $this->redirect(BASE_PATH . '/daily-report');
        }

        // 権限チェック（自分の日報のみ編集可能）
        if ($report['user_id'] != $userId) {
            $this->redirect(BASE_PATH . '/daily-report/view/' . $id);
        }

        // その日の予定を取得
        $schedules = $this->scheduleModel->getByDay($report['report_date'], $userId);

        // 完了したタスクを取得
        $completedTasks = $this->getCompletedTasks($userId, $report['report_date']);

        // 組織一覧を取得
        $organizationModel = new \Models\Organization();
        $organizations = $organizationModel->getAll();

        // ユーザー一覧を取得
        $users = $this->userModel->getActiveUsers();

        $viewData = [
            'title' => '日報編集',
            'user' => $user,
            'report' => $report,
            'schedules' => $schedules,
            'tasks' => $completedTasks,
            'organizations' => $organizations,
            'users' => $users,
            'jsFiles' => ['daily-report.js']
        ];

        $this->view('daily_report/form', $viewData);
    }

    /**
     * 日報詳細を表示する
     */
    public function viewDetail($params)
    {
        $id = $params['id'] ?? null;
        if (!$id) {
            $this->redirect(BASE_PATH . '/daily-report');
        }

        $userId = $this->auth->id();

        // 日報データを取得
        $report = $this->reportModel->getById($id);
        if (!$report) {
            $this->redirect(BASE_PATH . '/daily-report');
        }

        // 権限チェック
        // TODO: 閲覧権限のチェック処理

        // 既読にする
        $this->reportModel->markAsRead($id, $userId);

        // コメント一覧を取得
        $comments = $this->reportModel->getComments($id);

        // いいねしているかチェック
        $hasLiked = $this->reportModel->hasLiked($id, $userId);

        // 既読者一覧を取得
        $readUsers = $this->reportModel->getReadUsers($id);

        $viewData = [
            'title' => $report['title'],
            'report' => $report,
            'comments' => $comments,
            'hasLiked' => $hasLiked,
            'readUsers' => $readUsers,
            'jsFiles' => ['daily-report.js']
        ];

        $this->view('daily_report/view', $viewData);
    }

    /**
     * テンプレート一覧を表示する
     */
    public function templates()
    {
        $userId = $this->auth->id();
        $user = $this->auth->user();

        // テンプレート一覧を取得
        $templates = $this->reportModel->getTemplates($userId);

        $viewData = [
            'title' => '日報テンプレート',
            'user' => $user,
            'templates' => $templates,
            'jsFiles' => ['daily-report.js']
        ];

        $this->view('daily_report/templates', $viewData);
    }

    /**
     * テンプレート作成・編集画面を表示する
     */
    public function editTemplate($params)
    {
        $id = $params['id'] ?? null;
        $userId = $this->auth->id();
        $user = $this->auth->user();

        $template = null;
        if ($id) {
            // テンプレートを取得
            $template = $this->reportModel->getTemplateById($id);
            if (!$template || ($template['user_id'] != $userId && !$template['is_public'])) {
                $this->redirect(BASE_PATH . '/daily-report/templates');
            }
        }

        $viewData = [
            'title' => $id ? 'テンプレート編集' : 'テンプレート作成',
            'user' => $user,
            'template' => $template,
            'jsFiles' => ['daily-report.js']
        ];

        $this->view('daily_report/template_form', $viewData);
    }

    /**
     * 統計情報を表示する
     */
    public function stats()
    {
        $userId = $this->auth->id();
        $user = $this->auth->user();

        // 期間の指定（デフォルトは過去365日）
        $endDate = date('Y-m-d');
        $startDate = date('Y-m-d', strtotime('-365 days'));

        if (isset($_GET['start_date']) && !empty($_GET['start_date'])) {
            $startDate = $_GET['start_date'];
        }

        if (isset($_GET['end_date']) && !empty($_GET['end_date'])) {
            $endDate = $_GET['end_date'];
        }

        // 統計情報を取得
        $stats = $this->reportModel->getUserStats($userId, $startDate, $endDate);

        // タグ一覧を取得
        $tags = $this->reportModel->getUserTags($userId);

        $viewData = [
            'title' => '日報統計',
            'user' => $user,
            'stats' => $stats,
            'tags' => $tags,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'jsFiles' => ['daily-report.js', 'chart.js']
        ];

        $this->view('daily_report/stats', $viewData);
    }

    /**
     * API: 日報を作成する
     */
    public function apiCreate($params, $data)
    {
        $userId = $this->auth->id();

        // バリデーション
        $errors = $this->validateReportData($data);
        if (!empty($errors)) {
            return [
                'success' => false,
                'error' => '入力内容に誤りがあります',
                'validation' => $errors
            ];
        }

        // 日報データを整形
        $reportData = [
            'user_id' => $userId,
            'report_date' => $data['report_date'],
            'title' => $data['title'],
            'content' => $data['content'],
            'status' => $data['status'] ?? 'published',
            'tags' => $data['tags'] ?? [],
            'permissions' => $data['permissions'] ?? [],
            'schedules' => $data['schedules'] ?? [],
            'tasks' => $data['tasks'] ?? []
        ];

        // 日報を作成
        $reportId = $this->reportModel->create($reportData);

        if (!$reportId) {
            return [
                'success' => false,
                'error' => '日報の作成に失敗しました'
            ];
        }

        // 通知を送信（公開状態の場合のみ）
        if ($data['status'] === 'published' && !empty($data['permissions'])) {
            $this->sendNotifications($reportId, 'create');
        }

        return [
            'success' => true,
            'message' => '日報を作成しました',
            'data' => [
                'id' => $reportId,
                'redirect' => BASE_PATH . '/daily-report/view/' . $reportId
            ]
        ];
    }

    /**
     * API: 日報を更新する
     */
    public function apiUpdate($params, $data)
    {
        $userId = $this->auth->id();
        $id = $params['id'] ?? null;

        if (!$id) {
            return [
                'success' => false,
                'error' => '日報IDが指定されていません'
            ];
        }

        // 日報が存在するか確認
        $report = $this->reportModel->getById($id);
        if (!$report) {
            return [
                'success' => false,
                'error' => '指定された日報が見つかりません'
            ];
        }

        // 権限チェック（自分の日報のみ編集可能）
        if ($report['user_id'] != $userId) {
            return [
                'success' => false,
                'error' => 'この日報を編集する権限がありません'
            ];
        }

        // バリデーション
        $errors = $this->validateReportData($data);
        if (!empty($errors)) {
            return [
                'success' => false,
                'error' => '入力内容に誤りがあります',
                'validation' => $errors
            ];
        }

        // 日報データを整形
        $reportData = [
            'user_id' => $userId,
            'report_date' => $data['report_date'],
            'title' => $data['title'],
            'content' => $data['content'],
            'status' => $data['status'] ?? $report['status'],
            'tags' => $data['tags'] ?? [],
            'permissions' => $data['permissions'] ?? [],
            'schedules' => $data['schedules'] ?? [],
            'tasks' => $data['tasks'] ?? []
        ];

        // 日報を更新
        $result = $this->reportModel->update($id, $reportData);

        if (!$result) {
            return [
                'success' => false,
                'error' => '日報の更新に失敗しました'
            ];
        }

        // ステータスが下書きから公開に変わった場合は通知を送信
        if ($report['status'] === 'draft' && $data['status'] === 'published') {
            $this->sendNotifications($id, 'create');
        }

        return [
            'success' => true,
            'message' => '日報を更新しました',
            'data' => [
                'id' => $id,
                'redirect' => BASE_PATH . '/daily-report/view/' . $id
            ]
        ];
    }

    /**
     * API: 日報を削除する
     */
    public function apiDelete($params)
    {
        $userId = $this->auth->id();
        $id = $params['id'] ?? null;

        if (!$id) {
            return [
                'success' => false,
                'error' => '日報IDが指定されていません'
            ];
        }

        // 日報が存在するか確認
        $report = $this->reportModel->getById($id);
        if (!$report) {
            return [
                'success' => false,
                'error' => '指定された日報が見つかりません'
            ];
        }

        // 権限チェック（自分の日報のみ削除可能）
        if ($report['user_id'] != $userId && !$this->auth->isAdmin()) {
            return [
                'success' => false,
                'error' => 'この日報を削除する権限がありません'
            ];
        }

        // 日報を削除
        $result = $this->reportModel->delete($id);

        if (!$result) {
            return [
                'success' => false,
                'error' => '日報の削除に失敗しました'
            ];
        }

        return [
            'success' => true,
            'message' => '日報を削除しました',
            'data' => [
                'redirect' => BASE_PATH . '/daily-report'
            ]
        ];
    }

    /**
     * API: コメントを追加する
     */
    public function apiAddComment($params, $data)
    {
        $userId = $this->auth->id();
        $id = $params['id'] ?? null;

        if (!$id) {
            return [
                'success' => false,
                'error' => '日報IDが指定されていません'
            ];
        }

        if (empty($data['comment'])) {
            return [
                'success' => false,
                'error' => 'コメントを入力してください'
            ];
        }

        // 日報が存在するか確認
        $report = $this->reportModel->getById($id);
        if (!$report) {
            return [
                'success' => false,
                'error' => '指定された日報が見つかりません'
            ];
        }

        // コメントを追加
        $commentId = $this->reportModel->addComment($id, $userId, $data['comment']);

        if (!$commentId) {
            return [
                'success' => false,
                'error' => 'コメントの追加に失敗しました'
            ];
        }

        // 日報作成者に通知を送信（自分自身の場合は除く）
        if ($report['user_id'] != $userId) {
            $this->sendCommentNotification($id, $userId, $report['user_id']);
        }

        // コメント一覧を再取得
        $comments = $this->reportModel->getComments($id);

        return [
            'success' => true,
            'message' => 'コメントを追加しました',
            'data' => [
                'comments' => $comments
            ]
        ];
    }

    /**
     * API: いいねを追加/削除する
     */
    public function apiToggleLike($params)
    {
        $userId = $this->auth->id();
        $id = $params['id'] ?? null;

        if (!$id) {
            return [
                'success' => false,
                'error' => '日報IDが指定されていません'
            ];
        }

        // 日報が存在するか確認
        $report = $this->reportModel->getById($id);
        if (!$report) {
            return [
                'success' => false,
                'error' => '指定された日報が見つかりません'
            ];
        }

        // いいねを追加/削除
        $result = $this->reportModel->toggleLike($id, $userId);

        if (!$result) {
            return [
                'success' => false,
                'error' => '操作に失敗しました'
            ];
        }

        // いいねの現在の状態を取得
        $hasLiked = $this->reportModel->hasLiked($id, $userId);
        $likesCount = $this->reportModel->getLikesCount($id);

        // 日報作成者に通知を送信（自分自身の場合は除く）
        if ($hasLiked && $report['user_id'] != $userId) {
            $this->sendLikeNotification($id, $userId, $report['user_id']);
        }

        return [
            'success' => true,
            'data' => [
                'has_liked' => $hasLiked,
                'likes_count' => $likesCount
            ]
        ];
    }

    /**
     * API: テンプレートを保存する
     */
    public function apiSaveTemplate($params, $data)
    {
        $userId = $this->auth->id();
        $id = $params['id'] ?? null;

        // デバッグ情報
        error_log("API Save Template - Params: " . print_r($params, true));
        error_log("API Save Template - Data: " . print_r($data, true));

        // バリデーション
        if (empty($data['title']) || empty($data['content'])) {
            return [
                'success' => false,
                'error' => 'タイトルと内容は必須です'
            ];
        }

        $templateData = [
            'title' => $data['title'],
            'content' => $data['content'],
            'user_id' => $userId,
            'is_public' => isset($data['is_public']) && $data['is_public'] ? 1 : 0
        ];

        // テンプレートの保存処理
        if ($id) {
            // 既存テンプレートを更新
            $template = $this->reportModel->getTemplateById($id);
            if (!$template) {
                return [
                    'success' => false,
                    'error' => '指定されたテンプレートが見つかりません'
                ];
            }

            // 権限チェック（自分のテンプレートのみ編集可能）
            if ($template['user_id'] != $userId && !$this->auth->isAdmin()) {
                return [
                    'success' => false,
                    'error' => 'このテンプレートを編集する権限がありません'
                ];
            }

            $result = $this->reportModel->updateTemplate($id, $templateData);
            $message = 'テンプレートを更新しました';
        } else {
            // 新規テンプレートを作成
            $id = $this->reportModel->createTemplate($templateData);
            $result = ($id !== false);
            $message = 'テンプレートを作成しました';
        }

        if (!$result) {
            return [
                'success' => false,
                'error' => 'テンプレートの保存に失敗しました'
            ];
        }

        return [
            'success' => true,
            'message' => $message,
            'data' => [
                'id' => $id,
                'redirect' => BASE_PATH . '/daily-report/templates'
            ]
        ];
    }

    /**
     * API: テンプレートを削除する
     */
    public function apiDeleteTemplate($params)
    {
        $userId = $this->auth->id();
        $id = $params['id'] ?? null;

        if (!$id) {
            return [
                'success' => false,
                'error' => 'テンプレートIDが指定されていません'
            ];
        }

        // テンプレートが存在するか確認
        $template = $this->reportModel->getTemplateById($id);
        if (!$template) {
            return [
                'success' => false,
                'error' => '指定されたテンプレートが見つかりません'
            ];
        }

        // 権限チェック（自分のテンプレートのみ削除可能）
        if ($template['user_id'] != $userId && !$this->auth->isAdmin()) {
            return [
                'success' => false,
                'error' => 'このテンプレートを削除する権限がありません'
            ];
        }

        // テンプレートを削除
        $result = $this->reportModel->deleteTemplate($id);

        if (!$result) {
            return [
                'success' => false,
                'error' => 'テンプレートの削除に失敗しました'
            ];
        }

        return [
            'success' => true,
            'message' => 'テンプレートを削除しました',
            'data' => [
                'redirect' => BASE_PATH . '/daily-report/templates'
            ]
        ];
    }

    /**
     * API: 読み取り可能な日報の総数を取得する
     */
    public function apiGetReadableReportsCount($params)
    {
        $userId = $this->auth->id();
        $filters = $params;

        // ここでは単純にデータベースからカウントを取得する代わりに、
        // 権限を考慮したカウント処理を実装する必要があります。
        // このAPIは日報一覧画面で非同期にカウントを取得する際に使用します。

        // 例として簡易的なカウント処理を示します
        $count = count($this->reportModel->getReadableReports($userId, $filters, 1, 1000));

        return [
            'success' => true,
            'data' => [
                'count' => $count
            ]
        ];
    }

    /**
     * API: 日報の統計情報を取得する
     */
    public function apiGetStats($params)
    {
        $userId = $this->auth->id();
        $startDate = $params['start_date'] ?? null;
        $endDate = $params['end_date'] ?? null;

        $stats = $this->reportModel->getUserStats($userId, $startDate, $endDate);

        return [
            'success' => true,
            'data' => $stats
        ];
    }

    /**
     * 日報データのバリデーション
     * 
     * @param array $data 日報データ
     * @return array エラーメッセージの配列
     */
    private function validateReportData($data)
    {
        $errors = [];

        if (empty($data['report_date'])) {
            $errors['report_date'] = '日付は必須です';
        }

        if (empty($data['title'])) {
            $errors['title'] = 'タイトルは必須です';
        } elseif (mb_strlen($data['title']) > 100) {
            $errors['title'] = 'タイトルは100文字以内で入力してください';
        }

        if (empty($data['content'])) {
            $errors['content'] = '内容は必須です';
        }

        return $errors;
    }

    /**
     * 完了済みのタスクを取得する
     * 
     * @param int $userId ユーザーID
     * @param string $date 日付
     * @return array タスクリスト
     */
    private function getCompletedTasks($userId, $date)
    {
        try {
            // task_cardsテーブルから完了したタスクを取得
            $sql = "SELECT c.*, l.name as list_name, b.name as board_name
                   FROM task_cards c
                   JOIN task_assignees a ON c.id = a.card_id
                   JOIN task_lists l ON c.list_id = l.id
                   JOIN task_boards b ON l.board_id = b.id
                   WHERE a.user_id = ? AND c.status = 'completed'";

            // 日付が指定されている場合は、その日に完了したタスクのみ取得
            if ($date) {
                $sql .= " AND DATE(c.updated_at) = ?";
                return $this->db->fetchAll($sql, [$userId, $date]);
            }

            return $this->db->fetchAll($sql, [$userId]);
        } catch (\Exception $e) {
            error_log("Error getting completed tasks: " . $e->getMessage());
            return [];
        }
    }

    /**
     * 未読の日報を取得する
     * 
     * @param int $userId ユーザーID
     * @param int $limit 件数
     * @return array 未読日報リスト
     */
    private function getUnreadReports($userId, $limit = 5)
    {
        try {
            // 権限を考慮した上で、まだ読んでいない日報を取得
            // ユーザーの所属組織IDを取得
            $userModel = new User();
            $organizationIds = $userModel->getUserOrganizationIds($userId);

            $placeholders = '';
            $params = [$userId];
            if (!empty($organizationIds)) {
                $placeholders = implode(',', array_fill(0, count($organizationIds), '?'));
                $params = array_merge($params, $organizationIds);
            } else {
                $placeholders = '0'; // 組織がない場合は0を使用（常にfalseとなる）
            }

            $params[] = $userId; // NOT EXISTS の部分用

            $sql = "SELECT r.*, u.display_name as creator_name
                   FROM daily_reports r
                   JOIN users u ON r.user_id = u.id
                   LEFT JOIN daily_report_permissions p ON r.id = p.report_id
                   WHERE r.status = 'published' 
                   AND r.user_id != ? 
                   AND (
                       (p.target_type = 'user' AND p.target_id = ?) OR
                       (p.target_type = 'organization' AND p.target_id IN ({$placeholders}))
                   )
                   AND NOT EXISTS (
                       SELECT 1 FROM daily_report_reads rr 
                       WHERE rr.report_id = r.id AND rr.user_id = ?
                   )
                   ORDER BY r.created_at DESC
                   LIMIT ?";

            $params[] = $userId; // p.target_id = ? の部分用
            $params[] = $limit;

            return $this->db->fetchAll($sql, $params);
        } catch (\Exception $e) {
            error_log("Error getting unread reports: " . $e->getMessage());
            return [];
        }
    }

    /**
     * 日報の通知を送信する
     * 
     * @param int $reportId 日報ID
     * @param string $action アクション（create/update）
     * @return void
     */
    private function sendNotifications($reportId, $action)
    {
        try {
            $report = $this->reportModel->getById($reportId);
            if (!$report) {
                return;
            }

            $creatorName = $report['creator_name'];
            $title = $report['title'];

            // 権限設定から通知対象を取得
            $notifyUserIds = [];
            $permissions = $this->reportModel->getReportPermissions($reportId);

            foreach ($permissions as $permission) {
                if ($permission['target_type'] === 'user') {
                    $notifyUserIds[] = $permission['target_id'];
                } elseif ($permission['target_type'] === 'organization') {
                    // 組織メンバーを取得
                    $userModel = new User();
                    $users = $userModel->getUsersByOrganization($permission['target_id']);
                    foreach ($users as $user) {
                        $notifyUserIds[] = $user['id'];
                    }
                }
            }

            // 重複を削除し、作成者自身を除外
            $notifyUserIds = array_unique($notifyUserIds);
            $notifyUserIds = array_diff($notifyUserIds, [$report['user_id']]);

            // 通知タイトルと内容を設定
            if ($action === 'create') {
                $notificationTitle = '新しい日報が登録されました';
                $notificationContent = "{$creatorName}さんが新しい日報「{$title}」を登録しました。";
            } else {
                $notificationTitle = '日報が更新されました';
                $notificationContent = "{$creatorName}さんが日報「{$title}」を更新しました。";
            }

            // 各ユーザーに通知を送信
            foreach ($notifyUserIds as $userId) {
                $notificationData = [
                    'user_id' => $userId,
                    'type' => 'daily_report',
                    'title' => $notificationTitle,
                    'content' => $notificationContent,
                    'link' => "/daily-report/view/{$reportId}",
                    'reference_id' => $reportId,
                    'reference_type' => 'daily_report'
                ];

                $this->notificationModel->create($notificationData);
            }
        } catch (\Exception $e) {
            error_log("Error sending notifications: " . $e->getMessage());
        }
    }

    /**
     * コメント通知を送信する
     * 
     * @param int $reportId 日報ID
     * @param int $commenterId コメント投稿者ID
     * @param int $reportOwnerId 日報作成者ID
     * @return void
     */
    private function sendCommentNotification($reportId, $commenterId, $reportOwnerId)
    {
        try {
            $report = $this->reportModel->getById($reportId);
            if (!$report) {
                return;
            }

            $commenter = $this->userModel->getById($commenterId);
            if (!$commenter) {
                return;
            }

            $commenterName = $commenter['display_name'];
            $title = $report['title'];

            $notificationData = [
                'user_id' => $reportOwnerId,
                'type' => 'daily_report',
                'title' => '日報にコメントがありました',
                'content' => "{$commenterName}さんがあなたの日報「{$title}」にコメントしました。",
                'link' => "/daily-report/view/{$reportId}",
                'reference_id' => $reportId,
                'reference_type' => 'daily_report'
            ];

            $this->notificationModel->create($notificationData);
        } catch (\Exception $e) {
            error_log("Error sending comment notification: " . $e->getMessage());
        }
    }

    /**
     * いいね通知を送信する
     * 
     * @param int $reportId 日報ID
     * @param int $likerId いいねしたユーザーID
     * @param int $reportOwnerId 日報作成者ID
     * @return void
     */
    private function sendLikeNotification($reportId, $likerId, $reportOwnerId)
    {
        try {
            $report = $this->reportModel->getById($reportId);
            if (!$report) {
                return;
            }

            $liker = $this->userModel->getById($likerId);
            if (!$liker) {
                return;
            }

            $likerName = $liker['display_name'];
            $title = $report['title'];

            $notificationData = [
                'user_id' => $reportOwnerId,
                'type' => 'daily_report',
                'title' => '日報にいいねがありました',
                'content' => "{$likerName}さんがあなたの日報「{$title}」にいいねしました。",
                'link' => "/daily-report/view/{$reportId}",
                'reference_id' => $reportId,
                'reference_type' => 'daily_report'
            ];

            $this->notificationModel->create($notificationData);
        } catch (\Exception $e) {
            error_log("Error sending like notification: " . $e->getMessage());
        }
    }
}

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
use Models\Organization;

class DailyReportController extends Controller
{
    private $db;
    private $reportModel;
    private $scheduleModel;
    private $taskModel;
    private $userModel;
    private $notificationModel;
    private $organizationModel;

    public function __construct()
    {
        parent::__construct();
        $this->db = Database::getInstance();
        $this->reportModel = new DailyReport();
        $this->scheduleModel = new Schedule();
        $this->taskModel = new Task();
        $this->userModel = new User();
        $this->notificationModel = new Notification();
        $this->organizationModel = new Organization();

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

        // テンプレート一覧を取得
        $templates = $this->reportModel->getTemplates($userId);

        $viewData = [
            'title' => '日報',
            'user' => $user,
            'stats' => $stats,
            'recent_reports' => $recentReports,
            'has_today_report' => $hasTodayReport,
            'today_report' => $todayReport,
            'templates' => $templates,
            'tags' => $tags,
            'unread_reports' => $unreadReports,
            'jsFiles' => ['daily-report.js']
        ];

        $this->view('daily_report/index', $viewData);
    }

    /**
     * 日報週間ビュー
     */
    public function week()
    {
        $userId = $this->auth->id();
        $date = $_GET['date'] ?? date('Y-m-d');
        $timestamp = strtotime($date);
        $weekStart = date('Y-m-d', strtotime('monday this week', $timestamp));
        $weekEnd = date('Y-m-d', strtotime('sunday this week', $timestamp));

        $reports = $this->reportModel->getReadableReports($userId, [
            'start_date' => $weekStart,
            'end_date' => $weekEnd
        ], 1, 500);

        $reportsByDate = [];
        for ($i = 0; $i < 7; $i++) {
            $day = date('Y-m-d', strtotime($weekStart . " +{$i} day"));
            $reportsByDate[$day] = [];
        }
        foreach ($reports as $report) {
            $reportsByDate[$report['report_date']][] = $report;
        }

        $this->view('daily_report/week', [
            'title' => '日報週間',
            'baseDate' => $date,
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
            'reportsByDate' => $reportsByDate,
            'jsFiles' => ['daily-report.js']
        ]);
    }

    /**
     * 日報月間ビュー
     */
    public function month()
    {
        $userId = $this->auth->id();
        $month = $_GET['month'] ?? date('Y-m');
        if (preg_match('/^\d{4}-\d{2}$/', $month) !== 1) {
            $month = date('Y-m');
        }

        $monthStart = $month . '-01';
        $monthEnd = date('Y-m-t', strtotime($monthStart));
        $reports = $this->reportModel->getReadableReports($userId, [
            'start_date' => $monthStart,
            'end_date' => $monthEnd
        ], 1, 2000);

        $dailyCounts = [];
        foreach ($reports as $report) {
            if (!isset($dailyCounts[$report['report_date']])) {
                $dailyCounts[$report['report_date']] = 0;
            }
            $dailyCounts[$report['report_date']]++;
        }

        $this->view('daily_report/month', [
            'title' => '日報月間',
            'month' => $month,
            'monthStart' => $monthStart,
            'monthEnd' => $monthEnd,
            'dailyCounts' => $dailyCounts,
            'jsFiles' => ['daily-report.js']
        ]);
    }

    /**
     * 日報タイムラインビュー
     */
    public function timeline()
    {
        $userId = $this->auth->id();
        $date = $_GET['date'] ?? date('Y-m-d');
        $filters = [
            'start_date' => $date,
            'end_date' => $date,
            'user_id' => $_GET['user_id'] ?? null
        ];
        $reports = $this->reportModel->getReadableReports($userId, $filters, 1, 500);

        usort($reports, static function ($a, $b) {
            return strcmp($b['created_at'], $a['created_at']);
        });

        $this->view('daily_report/timeline', [
            'title' => '日報タイムライン',
            'date' => $date,
            'reports' => $reports,
            'users' => $this->userModel->getActiveUsers(),
            'selectedUserId' => $filters['user_id'],
            'jsFiles' => ['daily-report.js']
        ]);
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
            'search' => $_GET['search'] ?? null,
            'project_id' => $_GET['project_id'] ?? null,
            'industry_id' => $_GET['industry_id'] ?? null,
            'product_id' => $_GET['product_id'] ?? null,
            'process_id' => $_GET['process_id'] ?? null
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
        $analysisMasters = $this->reportModel->getAnalysisMasterSet();

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
            'analysisMasters' => $analysisMasters,
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
            if ($template && !$this->reportModel->isTemplateAvailableForUser($templateId, $userId)) {
                $template = null;
            }
        }
        $templateSections = $template['sections'] ?? [];
        $defaultDetailItems = [];
        foreach ($templateSections as $section) {
            $defaultDetailItems[] = [
                'section_key' => $section['section_key'] ?? '',
                'title' => $section['title'] ?? '',
                'value' => $section['default_value_text'] ?? '',
                'input_type' => $section['input_type'] ?? 'textarea'
            ];
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
            'templateSections' => $templateSections,
            'defaultDetailItems' => $defaultDetailItems,
            'schedules' => $schedules,
            'tasks' => $completedTasks,
            'organizations' => $organizations,
            'users' => $users,
            'analysisMasters' => $this->reportModel->getAnalysisMasterSet(),
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

        $template = null;
        $templateSections = [];
        if (!empty($report['template_id'])) {
            $template = $this->reportModel->getTemplateById((int)$report['template_id']);
            $templateSections = $template['sections'] ?? [];
        }

        $viewData = [
            'title' => '日報編集',
            'user' => $user,
            'report' => $report,
            'template' => $template,
            'templateSections' => $templateSections,
            'schedules' => $schedules,
            'tasks' => $completedTasks,
            'organizations' => $organizations,
            'users' => $users,
            'analysisMasters' => $this->reportModel->getAnalysisMasterSet(),
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
        if (!$this->canReadReport($report, $userId)) {
            $this->redirect(BASE_PATH . '/daily-report');
        }

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
            if (!$template || !$this->reportModel->isTemplateAvailableForUser($id, $userId)) {
                $this->redirect(BASE_PATH . '/daily-report/templates');
            }
        }

        $viewData = [
            'title' => $id ? 'テンプレート編集' : 'テンプレート作成',
            'user' => $user,
            'template' => $template,
            'organizations' => $this->organizationModel->getAll(),
            'templateOrganizationIds' => $template ? $this->reportModel->getTemplateOrganizationIds($template['id']) : [],
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
     * 日報分析画面（案件・業種・商品・プロセス / 予実）
     */
    public function analysis()
    {
        $currentUserId = $this->auth->id();
        $selectedUserId = $currentUserId;
        if ($this->auth->isAdmin() && !empty($_GET['user_id'])) {
            $selectedUserId = (int)$_GET['user_id'];
        }

        $filters = [
            'user_id' => $selectedUserId,
            'start_date' => $_GET['start_date'] ?? date('Y-m-01'),
            'end_date' => $_GET['end_date'] ?? date('Y-m-d'),
            'project_id' => $_GET['project_id'] ?? null,
            'industry_id' => $_GET['industry_id'] ?? null,
            'product_id' => $_GET['product_id'] ?? null,
            'process_id' => $_GET['process_id'] ?? null
        ];
        $targetMonth = $_GET['target_month'] ?? date('Y-m');
        if (preg_match('/^\d{4}-\d{2}$/', (string)$targetMonth) !== 1) {
            $targetMonth = date('Y-m');
        }

        $summary = $this->reportModel->getAnalysisSummary($filters);
        $projectBreakdown = $this->reportModel->getAnalysisBreakdown($filters, 'project');
        $industryBreakdown = $this->reportModel->getAnalysisBreakdown($filters, 'industry');
        $productBreakdown = $this->reportModel->getAnalysisBreakdown($filters, 'product');
        $processBreakdown = $this->reportModel->getAnalysisBreakdown($filters, 'process');
        $monthlyTrend = $this->reportModel->getAnalysisMonthlyTrend($filters);
        $monthlyTargets = $this->reportModel->getMonthlyTargets($selectedUserId, $targetMonth);
        $targetVsActual = $this->reportModel->getAnalysisTargetVsActual($selectedUserId, $targetMonth);

        $this->view('daily_report/analysis', [
            'title' => '日報分析',
            'filters' => $filters,
            'targetMonth' => $targetMonth,
            'summary' => $summary,
            'projectBreakdown' => $projectBreakdown,
            'industryBreakdown' => $industryBreakdown,
            'productBreakdown' => $productBreakdown,
            'processBreakdown' => $processBreakdown,
            'monthlyTrend' => $monthlyTrend,
            'monthlyTargets' => $monthlyTargets,
            'targetVsActual' => $targetVsActual,
            'analysisMasters' => $this->reportModel->getAnalysisMasterSet(),
            'users' => $this->auth->isAdmin() ? $this->userModel->getActiveUsers() : [],
            'selectedUserId' => $selectedUserId,
            'jsFiles' => ['daily-report.js', 'chart.js']
        ]);
    }

    /**
     * API: 日報を作成する
     */
    public function apiCreate($params, $data)
    {
        $userId = $this->auth->id();
        $data = $this->resolveApiPayload($data);
        $uploadedFiles = $this->processReportAttachments($_FILES['attachments'] ?? null);

        // バリデーション
        $errors = $this->validateReportData($data, (int)$userId);
        if (!empty($errors)) {
            return [
                'success' => false,
                'error' => '入力内容に誤りがあります',
                'validation' => $errors
            ];
        }

        // 日報データを整形
        $reportData = $this->prepareReportPayload($data, $userId);
        $reportData['attachments'] = $uploadedFiles;

        // 日報を作成
        $reportId = $this->reportModel->create($reportData);

        if (!$reportId) {
            $this->rollbackUploadedFiles($uploadedFiles);
            return [
                'success' => false,
                'error' => '日報の作成に失敗しました'
            ];
        }

        // 通知を送信（公開状態の場合のみ）
        if ($reportData['status'] === 'published' && !empty($reportData['permissions'])) {
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
        $data = $this->resolveApiPayload($data);

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
        if (!$this->canReadReport($report, $userId)) {
            return [
                'success' => false,
                'error' => 'この日報を閲覧する権限がありません'
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
        $errors = $this->validateReportData($data, (int)$userId);
        if (!empty($errors)) {
            return [
                'success' => false,
                'error' => '入力内容に誤りがあります',
                'validation' => $errors
            ];
        }

        // 日報データを整形
        $reportData = $this->prepareReportPayload($data, $userId, $report);
        $uploadedFiles = $this->processReportAttachments($_FILES['attachments'] ?? null);
        $reportData['attachments'] = $uploadedFiles;

        // 日報を更新
        $result = $this->reportModel->update($id, $reportData);

        if (!$result) {
            $this->rollbackUploadedFiles($uploadedFiles);
            return [
                'success' => false,
                'error' => '日報の更新に失敗しました'
            ];
        }

        $deleteAttachmentIds = $data['delete_attachment_ids'] ?? [];
        if (is_string($deleteAttachmentIds)) {
            $decoded = json_decode($deleteAttachmentIds, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $deleteAttachmentIds = $decoded;
            }
        }
        if (is_array($deleteAttachmentIds) && !empty($deleteAttachmentIds)) {
            $this->reportModel->deleteAttachments((int)$id, $deleteAttachmentIds);
        }

        // ステータスが下書きから公開に変わった場合は通知を送信
        if ($report['status'] === 'draft' && $reportData['status'] === 'published') {
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
        if (!$this->canReadReport($report, $userId)) {
            return [
                'success' => false,
                'error' => 'この日報を閲覧する権限がありません'
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
        $data = $this->resolveApiPayload($data);
        $id = $params['id'] ?? null;

        // デバッグ情報
        // return [
        //     'params' => print_r($params, true),
        //     'data' => print_r($data, true)
        // ];

        $validation = $this->validateTemplateData($data);
        if (!empty($validation)) {
            return [
                'success' => false,
                'error' => '入力内容に誤りがあります',
                'validation' => $validation,
                'code' => 400
            ];
        }

        // バリデーション済みセクション
        $sections = is_array($data['sections'] ?? null) ? $data['sections'] : [];

        $templateData = [
            'title' => trim((string)$data['title']),
            'content' => $this->sanitizeRichText((string)($data['content'] ?? '')),
            'content_format' => ($data['content_format'] ?? 'text') === 'html' ? 'html' : 'text',
            'user_id' => $userId,
            'is_public' => isset($data['is_public']) && $data['is_public'] ? 1 : 0,
            'description' => isset($data['description']) ? trim((string)$data['description']) : null,
            'sections' => $sections
        ];

        // isEdit情報があればそれを使用
        $isEdit = isset($data['isEdit']) ? (bool)$data['isEdit'] : ($id !== null);


        $organizationIds = $this->normalizeOrganizationIds($data['organization_ids'] ?? []);

        // テンプレートの保存処理
        if ($isEdit) {
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
            if ($result) {
                $this->reportModel->updateTemplateOrganizations($id, $organizationIds);
            }
            $message = 'テンプレートを更新しました';
        } else {
            // 新規テンプレートを作成
            $id = $this->reportModel->createTemplate($templateData);
            $result = ($id !== false);
            if ($result) {
                $this->reportModel->updateTemplateOrganizations($id, $organizationIds);
            }
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

    public function apiGetAnalysisMasters($params)
    {
        return [
            'success' => true,
            'data' => $this->reportModel->getAnalysisMasterSet()
        ];
    }

    public function apiSaveMaster($params, $data)
    {
        if (!$this->auth->isAdmin()) {
            return ['success' => false, 'error' => '権限がありません', 'code' => 403];
        }

        $data = $this->resolveApiPayload($data);
        $type = (string)($params['type'] ?? '');
        $validation = $this->validateMasterPayload($type, $data);
        if (!empty($validation)) {
            return [
                'success' => false,
                'error' => '入力内容に誤りがあります',
                'validation' => $validation,
                'code' => 400
            ];
        }
        $savedId = $this->reportModel->saveMasterItem($type, $data, $this->auth->id());
        if (!$savedId) {
            return ['success' => false, 'error' => 'マスタの保存に失敗しました', 'code' => 500];
        }

        return [
            'success' => true,
            'message' => 'マスタを保存しました',
            'data' => [
                'id' => $savedId,
                'items' => $this->reportModel->getMasterItems($type)
            ]
        ];
    }

    public function apiDeleteMaster($params)
    {
        if (!$this->auth->isAdmin()) {
            return ['success' => false, 'error' => '権限がありません', 'code' => 403];
        }
        $type = (string)($params['type'] ?? '');
        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            return ['success' => false, 'error' => 'IDが不正です', 'code' => 400];
        }
        $result = $this->reportModel->deleteMasterItem($type, $id);
        if (!$result) {
            return ['success' => false, 'error' => 'マスタの削除に失敗しました', 'code' => 500];
        }
        return ['success' => true, 'message' => 'マスタを削除しました'];
    }

    public function apiSaveMonthlyTarget($params, $data)
    {
        $data = $this->resolveApiPayload($data);
        $userId = $this->auth->id();
        if ($this->auth->isAdmin() && !empty($data['user_id'])) {
            $userId = (int)$data['user_id'];
        }

        $validation = $this->validateMonthlyTargetPayload($data);
        if (!empty($validation)) {
            return [
                'success' => false,
                'error' => '入力内容に誤りがあります',
                'validation' => $validation,
                'code' => 400
            ];
        }

        $ok = $this->reportModel->saveMonthlyTarget($userId, $data);
        if (!$ok) {
            return ['success' => false, 'error' => '月次目標の保存に失敗しました', 'code' => 500];
        }

        $targetMonth = $data['target_month'] ?? date('Y-m');
        return [
            'success' => true,
            'message' => '月次目標を保存しました',
            'data' => [
                'targets' => $this->reportModel->getMonthlyTargets($userId, $targetMonth)
            ]
        ];
    }

    public function apiDeleteMonthlyTarget($params)
    {
        $userId = $this->auth->id();
        $targetId = (int)($params['id'] ?? 0);
        if ($targetId <= 0) {
            return ['success' => false, 'error' => 'IDが不正です', 'code' => 400];
        }
        $ok = $this->reportModel->deleteMonthlyTarget($userId, $targetId);
        if (!$ok) {
            return ['success' => false, 'error' => '月次目標の削除に失敗しました', 'code' => 500];
        }
        return ['success' => true, 'message' => '月次目標を削除しました'];
    }

    public function apiExportCsv($params)
    {
        $currentUserId = $this->auth->id();
        $targetUserId = $currentUserId;
        if ($this->auth->isAdmin() && !empty($params['user_id'])) {
            $targetUserId = (int)$params['user_id'];
        }

        $filters = [
            'user_id' => $targetUserId,
            'start_date' => $params['start_date'] ?? null,
            'end_date' => $params['end_date'] ?? null,
            'project_id' => $params['project_id'] ?? null,
            'industry_id' => $params['industry_id'] ?? null,
            'product_id' => $params['product_id'] ?? null,
            'process_id' => $params['process_id'] ?? null
        ];
        $rows = $this->reportModel->getCsvExportRows($filters);

        $filename = 'daily_report_analysis_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo "\xEF\xBB\xBF";
        $fp = fopen('php://output', 'w');
        fputcsv($fp, ['日報ID', '日付', '作成者', 'タイトル', '状態', '計画金額', '実績金額', '計画時間', '実績時間', '数量']);
        foreach ($rows as $row) {
            fputcsv($fp, [
                $row['id'],
                $row['report_date'],
                $row['creator_name'],
                $row['title'],
                $row['status'],
                $row['planned_amount_total'],
                $row['actual_amount_total'],
                $row['planned_hours_total'],
                $row['actual_hours_total'],
                $row['quantity_total']
            ]);
        }
        fclose($fp);
        exit;
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

        $content = trim((string)($data['content'] ?? ''));
        $summary = trim((string)($data['summary_text'] ?? ''));
        $issues = trim((string)($data['issues_text'] ?? ''));
        $tomorrow = trim((string)($data['tomorrow_plan_text'] ?? ''));
        $detailItems = $this->normalizeDetailItems($data['detail_items'] ?? []);
        $activities = $this->normalizeActivities($data['activities'] ?? ($data['activity_logs'] ?? []));
        $analysisEntries = $this->normalizeAnalysisEntries($data['analysis_entries'] ?? []);

        if ($content === '' && $summary === '' && $issues === '' && $tomorrow === '' && empty($detailItems) && empty($activities) && empty($analysisEntries)) {
            $errors['content'] = '内容・活動ログ・分析明細のいずれかを入力してください';
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
            $params = [$userId, $userId];
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

            $params[] = $limit;

            return $this->db->fetchAll($sql, $params);
        } catch (\Exception $e) {
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
            $permissions = $report['permissions'] ?? [];

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
        }
    }

    private function prepareReportPayload($data, $userId, $existingReport = null)
    {
        $detailItems = $this->normalizeDetailItems($data['detail_items'] ?? []);
        $activities = $this->normalizeActivities($data['activities'] ?? ($data['activity_logs'] ?? []));
        $analysisEntries = $this->normalizeAnalysisEntries($data['analysis_entries'] ?? []);

        $summaryText = trim((string)($data['summary_text'] ?? ''));
        $issuesText = trim((string)($data['issues_text'] ?? ''));
        $tomorrowPlanText = trim((string)($data['tomorrow_plan_text'] ?? ''));
        $reflectionText = trim((string)($data['reflection_text'] ?? ''));
        $content = trim((string)($data['content'] ?? ''));
        $contentFormat = ($data['content_format'] ?? (($existingReport['content_format'] ?? 'text'))) === 'html' ? 'html' : 'text';

        if ($contentFormat === 'html') {
            $content = $this->sanitizeRichText($content);
        }

        if ($content === '') {
            $content = $this->buildContentFromStructured($summaryText, $issuesText, $tomorrowPlanText, $reflectionText, $detailItems, $activities);
            $contentFormat = 'text';
        }

        return [
            'user_id' => $userId,
            'report_date' => $data['report_date'],
            'title' => trim((string)$data['title']),
            'content' => $content,
            'content_format' => $contentFormat,
            'status' => $data['status'] ?? ($existingReport['status'] ?? 'published'),
            'tags' => is_array($data['tags'] ?? null) ? $data['tags'] : [],
            'permissions' => is_array($data['permissions'] ?? null) ? $data['permissions'] : [],
            'schedules' => is_array($data['schedules'] ?? null) ? $data['schedules'] : [],
            'tasks' => is_array($data['tasks'] ?? null) ? $data['tasks'] : [],
            'summary_text' => $summaryText !== '' ? $summaryText : null,
            'issues_text' => $issuesText !== '' ? $issuesText : null,
            'tomorrow_plan_text' => $tomorrowPlanText !== '' ? $tomorrowPlanText : null,
            'reflection_text' => $reflectionText !== '' ? $reflectionText : null,
            'work_minutes' => max(0, (int)($data['work_minutes'] ?? 0)),
            'template_id' => !empty($data['template_id']) ? (int)$data['template_id'] : null,
            'detail_items' => $detailItems,
            'activities' => $activities,
            'analysis_entries' => $analysisEntries
        ];
    }

    private function normalizeDetailItems($items)
    {
        if (!is_array($items)) {
            return [];
        }
        $result = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $title = trim((string)($item['title'] ?? ''));
            $value = trim((string)($item['value'] ?? ''));
            if ($title === '' && $value === '') {
                continue;
            }
            $result[] = [
                'section_key' => trim((string)($item['section_key'] ?? '')),
                'title' => $title,
                'value' => $value,
                'input_type' => trim((string)($item['input_type'] ?? 'textarea'))
            ];
        }
        return $result;
    }

    private function normalizeActivities($activities)
    {
        if (!is_array($activities)) {
            return [];
        }
        $result = [];
        foreach ($activities as $activity) {
            if (!is_array($activity)) {
                continue;
            }
            $start = trim((string)($activity['start_time'] ?? ''));
            $end = trim((string)($activity['end_time'] ?? ''));
            $type = trim((string)($activity['activity_type'] ?? ''));
            $subject = trim((string)($activity['subject'] ?? ''));
            $outcome = trim((string)($activity['result'] ?? ''));
            $memo = trim((string)($activity['memo'] ?? ''));
            if ($start === '' && $end === '' && $type === '' && $subject === '' && $outcome === '' && $memo === '') {
                continue;
            }
            $result[] = [
                'start_time' => $start,
                'end_time' => $end,
                'activity_type' => $type,
                'subject' => $subject,
                'result' => $outcome,
                'memo' => $memo
            ];
        }
        return $result;
    }

    private function normalizeAnalysisEntries($entries)
    {
        if (!is_array($entries)) {
            return [];
        }

        $normalized = [];
        foreach ($entries as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $projectId = max(0, (int)($entry['project_id'] ?? 0));
            $industryId = max(0, (int)($entry['industry_id'] ?? 0));
            $productId = max(0, (int)($entry['product_id'] ?? 0));
            $processId = max(0, (int)($entry['process_id'] ?? 0));
            $activityType = trim((string)($entry['activity_type'] ?? ''));
            $memo = trim((string)($entry['memo'] ?? ''));
            $plannedAmount = $this->normalizeDecimalValue($entry['planned_amount'] ?? 0);
            $actualAmount = $this->normalizeDecimalValue($entry['actual_amount'] ?? 0);
            $plannedHours = $this->normalizeDecimalValue($entry['planned_hours'] ?? 0);
            $actualHours = $this->normalizeDecimalValue($entry['actual_hours'] ?? 0);
            $quantity = $this->normalizeDecimalValue($entry['quantity'] ?? 0);

            if (
                $projectId <= 0 && $industryId <= 0 && $productId <= 0 && $processId <= 0
                && $activityType === '' && $memo === ''
                && $plannedAmount == 0.0 && $actualAmount == 0.0 && $plannedHours == 0.0 && $actualHours == 0.0 && $quantity == 0.0
            ) {
                continue;
            }

            $normalized[] = [
                'project_id' => $projectId > 0 ? $projectId : null,
                'industry_id' => $industryId > 0 ? $industryId : null,
                'product_id' => $productId > 0 ? $productId : null,
                'process_id' => $processId > 0 ? $processId : null,
                'activity_type' => $activityType,
                'planned_amount' => $plannedAmount,
                'actual_amount' => $actualAmount,
                'planned_hours' => $plannedHours,
                'actual_hours' => $actualHours,
                'quantity' => $quantity,
                'memo' => $memo
            ];
        }
        return $normalized;
    }

    private function normalizeDecimalValue($value)
    {
        if ($value === null || $value === '') {
            return 0.0;
        }
        if (is_string($value)) {
            $value = str_replace([',', ' '], '', $value);
        }
        return is_numeric($value) ? (float)$value : 0.0;
    }

    private function resolveApiPayload($data)
    {
        if (is_array($data) && isset($data['payload']) && is_string($data['payload'])) {
            $decoded = json_decode($data['payload'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }
        return is_array($data) ? $data : [];
    }

    private function processReportAttachments($fileBag)
    {
        if (!is_array($fileBag) || empty($fileBag['name'])) {
            return [];
        }

        $uploadConfig = require __DIR__ . '/../config/config.php';
        $allowedExtensions = $uploadConfig['upload']['allowed_extensions'] ?? [];
        $maxSize = (int)($uploadConfig['upload']['max_size'] ?? 10485760);

        $uploadDir = realpath(__DIR__ . '/../public/uploads');
        if ($uploadDir === false) {
            return [];
        }
        $uploadDir .= '/daily-report';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0775, true);
        }

        $names = is_array($fileBag['name']) ? $fileBag['name'] : [$fileBag['name']];
        $types = is_array($fileBag['type']) ? $fileBag['type'] : [$fileBag['type']];
        $tmpNames = is_array($fileBag['tmp_name']) ? $fileBag['tmp_name'] : [$fileBag['tmp_name']];
        $errors = is_array($fileBag['error']) ? $fileBag['error'] : [$fileBag['error']];
        $sizes = is_array($fileBag['size']) ? $fileBag['size'] : [$fileBag['size']];

        $uploaded = [];
        foreach ($names as $idx => $originalName) {
            if (!isset($errors[$idx]) || (int)$errors[$idx] !== UPLOAD_ERR_OK) {
                continue;
            }

            $tmpName = $tmpNames[$idx] ?? '';
            $size = (int)($sizes[$idx] ?? 0);
            if ($tmpName === '' || $size <= 0 || !is_uploaded_file($tmpName) || $size > $maxSize) {
                continue;
            }

            $ext = strtolower((string)pathinfo((string)$originalName, PATHINFO_EXTENSION));
            if (!empty($allowedExtensions) && $ext !== '' && !in_array($ext, $allowedExtensions, true)) {
                continue;
            }

            $safeBase = preg_replace('/[^A-Za-z0-9_.-]/', '_', (string)pathinfo((string)$originalName, PATHINFO_FILENAME));
            if ($safeBase === '') {
                $safeBase = 'file';
            }
            $storedName = date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '_' . $safeBase . ($ext !== '' ? '.' . $ext : '');
            $destPath = $uploadDir . '/' . $storedName;
            if (!move_uploaded_file($tmpName, $destPath)) {
                continue;
            }

            $uploaded[] = [
                'original_name' => (string)$originalName,
                'stored_name' => $storedName,
                'file_path' => 'uploads/daily-report/' . $storedName,
                'mime_type' => (string)($types[$idx] ?? ''),
                'file_size' => $size
            ];
        }

        return $uploaded;
    }

    private function rollbackUploadedFiles($uploadedFiles)
    {
        if (!is_array($uploadedFiles) || empty($uploadedFiles)) {
            return;
        }
        $publicDir = realpath(__DIR__ . '/../public');
        if ($publicDir === false) {
            return;
        }
        foreach ($uploadedFiles as $file) {
            $relative = (string)($file['file_path'] ?? '');
            if ($relative === '') {
                continue;
            }
            $fullPath = $publicDir . '/' . ltrim($relative, '/');
            if (is_file($fullPath)) {
                @unlink($fullPath);
            }
        }
    }

    private function sanitizeRichText($html)
    {
        $html = trim((string)$html);
        if ($html === '') {
            return '';
        }
        $allowed = '<p><br><strong><b><em><i><u><ul><ol><li><blockquote><a><h1><h2><h3><h4><h5><h6><span><div>';
        $clean = strip_tags($html, $allowed);
        $clean = preg_replace('/on[a-z]+\s*=\s*"[^"]*"/i', '', $clean);
        $clean = preg_replace("/on[a-z]+\s*=\s*'[^']*'/i", '', $clean);
        $clean = preg_replace('/javascript:/i', '', $clean);
        return $clean;
    }

    private function buildContentFromStructured($summaryText, $issuesText, $tomorrowPlanText, $reflectionText, $detailItems, $activities)
    {
        $lines = [];
        if ($summaryText !== '') {
            $lines[] = "## 本日の成果";
            $lines[] = $summaryText;
            $lines[] = "";
        }
        if ($issuesText !== '') {
            $lines[] = "## 課題";
            $lines[] = $issuesText;
            $lines[] = "";
        }
        if ($tomorrowPlanText !== '') {
            $lines[] = "## 明日の予定";
            $lines[] = $tomorrowPlanText;
            $lines[] = "";
        }
        if ($reflectionText !== '') {
            $lines[] = "## 所感";
            $lines[] = $reflectionText;
            $lines[] = "";
        }

        foreach ($detailItems as $item) {
            $title = trim((string)($item['title'] ?? ''));
            $value = trim((string)($item['value'] ?? ''));
            if ($title === '' && $value === '') {
                continue;
            }
            $lines[] = "## " . ($title !== '' ? $title : '詳細');
            if ($value !== '') {
                $lines[] = $value;
            }
            $lines[] = "";
        }

        if (!empty($activities)) {
            $lines[] = "## 活動ログ";
            foreach ($activities as $activity) {
                $range = trim(($activity['start_time'] ?? '') . ' - ' . ($activity['end_time'] ?? ''), ' -');
                $subject = trim((string)($activity['subject'] ?? ''));
                $type = trim((string)($activity['activity_type'] ?? ''));
                $memo = trim((string)($activity['memo'] ?? ''));
                $oneLine = trim($range . ' ' . $type . ' ' . $subject);
                if ($oneLine !== '') {
                    $lines[] = "- " . $oneLine;
                }
                if ($memo !== '') {
                    $lines[] = "  " . $memo;
                }
            }
            $lines[] = "";
        }

        return trim(implode("\n", $lines));
    }

    private function canReadReport($report, $userId)
    {
        if (!$report) {
            return false;
        }
        if ((int)$report['user_id'] === (int)$userId) {
            return true;
        }
        if (($report['status'] ?? '') !== 'published') {
            return false;
        }

        $permissions = $report['permissions'] ?? [];
        if (empty($permissions)) {
            return false;
        }

        $orgIds = $this->userModel->getUserOrganizationIds($userId);
        foreach ($permissions as $permission) {
            if (($permission['target_type'] ?? '') === 'user' && (int)$permission['target_id'] === (int)$userId) {
                return true;
            }
            if (($permission['target_type'] ?? '') === 'organization' && in_array((int)$permission['target_id'], array_map('intval', $orgIds), true)) {
                return true;
            }
        }
        return false;
    }

    private function normalizeOrganizationIds($organizationIds)
    {
        if (is_string($organizationIds)) {
            $decoded = json_decode($organizationIds, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $organizationIds = $decoded;
            } else {
                $organizationIds = array_filter(array_map('trim', explode(',', $organizationIds)));
            }
        }

        if (!is_array($organizationIds)) {
            return [];
        }

        $ids = [];
        foreach ($organizationIds as $organizationId) {
            $id = (int)$organizationId;
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }
}

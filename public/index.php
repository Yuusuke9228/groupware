<?php
// public/index.php - アプリケーションのエントリーポイント

// 基本設定
session_start();
date_default_timezone_set('Asia/Tokyo');
ini_set('display_errors', true);
error_reporting(E_ALL);
mb_internal_encoding('UTF-8');

// アプリケーションのベースパスを設定
$basePath = dirname($_SERVER["SCRIPT_NAME"]);
if ($basePath == "/") $basePath = "";
define("BASE_PATH", $basePath);

// オートローダー設定
spl_autoload_register(function ($class) {
    // 名前空間を考慮してファイルパスに変換
    $path = str_replace('\\', DIRECTORY_SEPARATOR, $class);
    $file = __DIR__ . '/../' . $path . '.php';

    // 標準パスでファイルを探す
    if (file_exists($file)) {
        require_once $file;
        return true;
    }

    // 小文字のパスで試す
    $lowercasePath = strtolower($path);
    $file = __DIR__ . '/../' . $lowercasePath . '.php';

    if (file_exists($file)) {
        require_once $file;
        return true;
    }

    // 大文字と小文字を入れ替えたパスで試す (Core -> core)
    $altPath = str_ireplace('Core', 'core', $path);
    $file = __DIR__ . '/../' . $altPath . '.php';

    if (file_exists($file)) {
        require_once $file;
        return true;
    }

    return false;
});

// コアクラスのインスタンス取得
$router = Core\Router::getInstance();
$auth = Core\Auth::getInstance();
$config = require_once __DIR__ . '/../config/config.php';

// Remember Me トークンからの認証
if (!$auth->check()) {
    $auth->authenticateFromRememberToken();
}

// ルーティングの設定
// ホームページ 
$router->get('/', function () use ($auth) {
    if ($auth->check()) {
        $controller = new Controllers\HomeController();
        $controller->index();
    } else {
        header('Location: ' . BASE_PATH . '/login');
    }
});

// 認証関連
$router->get('/login', function () use ($auth) {
    if ($auth->check()) {
        header('Location: ' . BASE_PATH . '/');
        exit;
    }

    require_once __DIR__ . '/../views/auth/login.php';
});

$router->post('/login', function () use ($auth) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']) ? true : false;

    if ($auth->login($username, $password, $remember)) {
        $redirect = $_GET['redirect'] ?? '/';
        header('Location: ' . BASE_PATH . $redirect);
    } else {
        $_SESSION['login_error'] = 'ユーザー名またはパスワードが正しくありません';
        header('Location: ' . BASE_PATH . '/login');
    }
});

$router->get('/logout', function () use ($auth) {
    $auth->logout();
    header('Location: ' . BASE_PATH . '/login');
});

// 組織管理
$router->get('/organizations', function () {
    $controller = new Controllers\OrganizationController();
    $controller->index();
}, true);

$router->get('/organizations/create', function () {
    $controller = new Controllers\OrganizationController();
    $controller->create();
}, true);

$router->get('/organizations/edit/:id', function ($params) {
    $controller = new Controllers\OrganizationController();
    $controller->edit($params);
}, true);

$router->get('/organizations/view/:id', function ($params) {
    $controller = new Controllers\OrganizationController();
    $controller->viewDetails($params);
}, true);

// ユーザー管理
$router->get('/users', function () {
    $controller = new Controllers\UserController();
    $controller->index();
}, true);

$router->get('/users/create', function () {
    $controller = new Controllers\UserController();
    $controller->create();
}, true);

$router->get('/users/edit/:id', function ($params) {
    $controller = new Controllers\UserController();
    $controller->edit($params);
}, true);

$router->get('/users/view/:id', function ($params) {
    $controller = new Controllers\UserController();
    $controller->viewDetails($params);
}, true);

$router->get('/users/change-password/:id', function ($params) {
    $controller = new Controllers\UserController();
    $controller->changePassword($params);
}, true);

// スケジュール管理
$router->get('/schedule', function () {
    header('Location:' . BASE_PATH . '/schedule/month');
    exit;
}, true);

$router->get('/schedule/day', function () {
    $controller = new Controllers\ScheduleController();
    $controller->day();
}, true);

$router->get('/schedule/week', function () {
    $controller = new Controllers\ScheduleController();
    $controller->week();
}, true);

$router->get('/schedule/month', function () {
    $controller = new Controllers\ScheduleController();
    $controller->month();
}, true);

$router->get('/schedule/create', function () {
    $controller = new Controllers\ScheduleController();
    $controller->create();
}, true);

$router->get('/schedule/edit/:id', function ($params) {
    $controller = new Controllers\ScheduleController();
    $controller->edit($params);
}, true);

$router->get('/schedule/view/:id', function ($params) {
    $controller = new Controllers\ScheduleController();
    $controller->viewDetails($params);
}, true);

// 組織スケジュール管理
$router->get('/schedule/organization-week', function () {
    $controller = new Controllers\ScheduleController();
    $controller->organizationWeek();
}, true);

// API ルート

// 組織管理API
$router->apiGet('/organizations', function () {
    $controller = new Controllers\OrganizationController();
    return $controller->apiGetAll();
}, true);

$router->apiGet('/organizations/tree', function () {
    $controller = new Controllers\OrganizationController();
    return $controller->apiGetTree();
}, true);

$router->apiGet('/organizations/:id', function ($params) {
    $controller = new Controllers\OrganizationController();
    return $controller->apiGetOne($params);
}, true);

$router->apiPost('/organizations', function ($params, $data) {
    $controller = new Controllers\OrganizationController();
    return $controller->apiCreate($params, $data);
}, true);

$router->apiPost('/organizations/:id', function ($params, $data) {
    $controller = new Controllers\OrganizationController();
    return $controller->apiUpdate($params, $data);
}, true);

$router->apiDelete('/organizations/:id', function ($params) {
    $controller = new Controllers\OrganizationController();
    return $controller->apiDelete($params);
}, true);

$router->apiPost('/organizations/:id/move', function ($params, $data) {
    $controller = new Controllers\OrganizationController();
    return $controller->apiUpdateOrder($params, $data);
}, true);

// 組織コード重複チェックAPI
$router->apiGet('/organizations/check-code', function ($params) {
    $controller = new Controllers\OrganizationController();
    return $controller->apiCheckCodeUnique($params);
}, true);

// 組織のユーザー一覧を取得
$router->apiGet('/organizations/:id/users', function ($params) {
    $controller = new Controllers\OrganizationController();
    return $controller->apiGetUsers($params);
}, true);

// ユーザー管理API
$router->apiGet('/users', function ($params) {
    $controller = new Controllers\UserController();
    return $controller->apiGetAll($params);
}, true);

$router->apiGet('/users/:id', function ($params) {
    $controller = new Controllers\UserController();
    return $controller->apiGetOne($params);
}, true);

$router->apiGet('/users/:id/organizations', function ($params) {
    $controller = new Controllers\UserController();
    return $controller->apiGetUserOrganizations($params);
}, true);

$router->apiPost('/users', function ($params, $data) {
    $controller = new Controllers\UserController();
    return $controller->apiCreate($params, $data);
}, true);

$router->apiPost('/users/:id', function ($params, $data) {
    $controller = new Controllers\UserController();
    return $controller->apiUpdate($params, $data);
}, true);

$router->apiDelete('/users/:id', function ($params) {
    $controller = new Controllers\UserController();
    return $controller->apiDelete($params);
}, true);

$router->apiPost('/users/:id/change-password', function ($params, $data) {
    $controller = new Controllers\UserController();
    return $controller->apiChangePassword($params, $data);
}, true);

$router->apiPost('/users/:id/primary-organization', function ($params, $data) {
    $controller = new Controllers\UserController();
    return $controller->apiChangePrimaryOrganization($params, $data);
}, true);

// スケジュール管理API
$router->apiGet('/schedule/day', function ($params) {
    $controller = new Controllers\ScheduleController();
    return $controller->apiGetDay($params);
}, true);

$router->apiGet('/schedule/week', function ($params) {
    $controller = new Controllers\ScheduleController();
    error_log(json_encode(['debug:apigetweek:' => $params]));
    return $controller->apiGetWeek($params);
}, true);

$router->apiGet('/schedule/month', function ($params) {
    $controller = new Controllers\ScheduleController();
    return $controller->apiGetMonth($params);
}, true);

$router->apiGet('/schedule/range', function ($params) {
    $controller = new Controllers\ScheduleController();
    return $controller->apiGetByDateRange($params);
}, true);

$router->apiGet('/schedule/organization-week', function ($params) {
    $controller = new Controllers\ScheduleController();
    return $controller->apiGetOrganizationWeek($params);
}, true);

$router->apiGet('/schedule/:id', function ($params) {
    $controller = new Controllers\ScheduleController();
    return $controller->apiGetOne($params);
}, true);

$router->apiPost('/schedule', function ($params, $data) {
    $controller = new Controllers\ScheduleController();
    return $controller->apiCreate($params, $data);
}, true);

$router->apiPost('/schedule/:id', function ($params, $data) {
    $controller = new Controllers\ScheduleController();
    return $controller->apiUpdate($params, $data);
}, true);

$router->apiDelete('/schedule/:id', function ($params) {
    $controller = new Controllers\ScheduleController();
    return $controller->apiDelete($params);
}, true);

$router->apiPost('/schedule/:id/participation-status', function ($params, $data) {
    $controller = new Controllers\ScheduleController();
    return $controller->apiUpdateParticipantStatus($params, $data);
}, true);

$router->apiPost('/schedule/:id/participants', function ($params, $data) {
    $controller = new Controllers\ScheduleController();
    return $controller->apiAddParticipant($params, $data);
}, true);

$router->apiDelete('/schedule/:id/participants', function ($params, $data) {
    $controller = new Controllers\ScheduleController();
    return $controller->apiRemoveParticipant($params, $data);
}, true);

$router->apiPost('/schedule/:id/organizations', function ($params, $data) {
    $controller = new Controllers\ScheduleController();
    return $controller->apiAddOrganization($params, $data);
}, true);

$router->apiDelete('/schedule/:id/organizations', function ($params, $data) {
    $controller = new Controllers\ScheduleController();
    return $controller->apiRemoveOrganization($params, $data);
}, true);

// アクティブユーザー取得API
$router->apiGet('/active-users', function () {
    $controller = new Controllers\UserController();
    return $controller->apiGetActiveUsers();
}, true);

// ワークフロー関連のルーティング
// ワークフロー管理画面
$router->get('/workflow', function () {
    $controller = new Controllers\WorkflowController();
    $controller->index();
}, true);

// テンプレート一覧
$router->get('/workflow/templates', function () {
    $controller = new Controllers\WorkflowController();
    $controller->templates();
}, true);

// テンプレート作成画面
$router->get('/workflow/create-template', function () {
    $controller = new Controllers\WorkflowController();
    $controller->createTemplate();
}, true);

// テンプレート編集画面
$router->get('/workflow/edit-template/:id', function ($params) {
    $controller = new Controllers\WorkflowController();
    $controller->editTemplate($params);
}, true);

// フォームデザイナー画面
$router->get('/workflow/design-form/:id', function ($params) {
    $controller = new Controllers\WorkflowController();
    $controller->designForm($params);
}, true);

// 承認経路設定画面
$router->get('/workflow/design-route/:id', function ($params) {
    $controller = new Controllers\WorkflowController();
    $controller->designRoute($params);
}, true);

// 申請一覧画面
$router->get('/workflow/requests', function () {
    $controller = new Controllers\WorkflowController();
    $controller->requests();
}, true);

// 承認待ち一覧画面
$router->get('/workflow/approvals', function () {
    $controller = new Controllers\WorkflowController();
    $controller->approvals();
}, true);

// 新規申請作成画面
$router->get('/workflow/create/:id', function ($params) {
    $controller = new Controllers\WorkflowController();
    $controller->create($params);
}, true);

// 申請編集画面
$router->get('/workflow/edit/:id', function ($params) {
    $controller = new Controllers\WorkflowController();
    $controller->edit($params);
}, true);

// 申請詳細画面
$router->get('/workflow/view/:id', function ($params) {
    $controller = new Controllers\WorkflowController();
    $controller->viewDetails($params);
}, true);

// 代理承認設定画面
$router->get('/workflow/delegates', function () {
    $controller = new Controllers\WorkflowController();
    $controller->delegates();
}, true);

// ワークフロー関連のルーティング
// ワークフロー一覧
$router->get('/workflow', function () {
    $controller = new Controllers\WorkflowController();
    $controller->index();
}, true);

// テンプレート一覧
$router->get('/workflow/templates', function () {
    $controller = new Controllers\WorkflowController();
    $controller->templates();
}, true);

// テンプレート作成
$router->get('/workflow/create-template', function () {
    $controller = new Controllers\WorkflowController();
    $controller->createTemplate();
}, true);

// テンプレート編集
$router->get('/workflow/edit-template/:id', function ($params) {
    $controller = new Controllers\WorkflowController();
    $controller->editTemplate($params);
}, true);

// フォームデザイナー
$router->get('/workflow/design-form/:id', function ($params) {
    $controller = new Controllers\WorkflowController();
    $controller->designForm($params);
}, true);

// 承認経路デザイナー
$router->get('/workflow/design-route/:id', function ($params) {
    $controller = new Controllers\WorkflowController();
    $controller->designRoute($params);
}, true);

// 申請一覧
$router->get('/workflow/requests', function () {
    $controller = new Controllers\WorkflowController();
    $controller->requests();
}, true);

// 承認待ち一覧
$router->get('/workflow/approvals', function () {
    $controller = new Controllers\WorkflowController();
    $controller->approvals();
}, true);

// 新規申請作成
$router->get('/workflow/create/:id', function ($params) {
    $controller = new Controllers\WorkflowController();
    $controller->create($params);
}, true);

// 申請編集
$router->get('/workflow/edit/:id', function ($params) {
    $controller = new Controllers\WorkflowController();
    $controller->edit($params);
}, true);

// 申請詳細
$router->get('/workflow/view/:id', function ($params) {
    $controller = new Controllers\WorkflowController();
    $controller->viewDetails($params);
}, true);

// 代理承認設定
$router->get('/workflow/delegates', function () {
    $controller = new Controllers\WorkflowController();
    $controller->delegates();
}, true);

// API ルート
// テンプレート関連API
$router->apiGet('/workflow/templates', function ($params) {
    $controller = new Controllers\WorkflowController();
    return $controller->apiGetAllTemplates($params);
}, true);

$router->apiGet('/workflow/templates/:id', function ($params) {
    $controller = new Controllers\WorkflowController();
    return $controller->apiGetTemplate($params);
}, true);

$router->apiPost('/workflow/templates', function ($params, $data) {
    $controller = new Controllers\WorkflowController();
    return $controller->apiCreateTemplate($params, $data);
}, true);

$router->apiPost('/workflow/templates/:id', function ($params, $data) {
    $controller = new Controllers\WorkflowController();
    return $controller->apiUpdateTemplate($params, $data);
}, true);

$router->apiDelete('/workflow/templates/:id', function ($params) {
    $controller = new Controllers\WorkflowController();
    return $controller->apiDeleteTemplate($params);
}, true);

// フォーム関連API
$router->apiGet('/workflow/templates/:id/form', function ($params) {
    $controller = new Controllers\WorkflowController();
    return $controller->apiGetTemplate($params);
}, true);

$router->apiPost('/workflow/templates/:id/form', function ($params, $data) {
    $controller = new Controllers\WorkflowController();
    return $controller->apiSaveFormDefinitions($params, $data);
}, true);

$router->apiPost('/workflow/templates/:id/form-fields', function ($params, $data) {
    $controller = new Controllers\WorkflowController();
    return $controller->apiAddFormField($params, $data);
}, true);

$router->apiPost('/workflow/templates/:id/form-fields/:field_id', function ($params, $data) {
    $controller = new Controllers\WorkflowController();
    return $controller->apiUpdateFormField($params, $data);
}, true);

$router->apiDelete('/workflow/templates/:id/form-fields/:field_id', function ($params) {
    $controller = new Controllers\WorkflowController();
    return $controller->apiDeleteFormField($params);
}, true);

// 承認経路関連API
$router->apiGet('/workflow/templates/:id/route', function ($params) {
    $controller = new Controllers\WorkflowController();
    return $controller->apiGetTemplate($params);
}, true);

$router->apiPost('/workflow/templates/:id/route', function ($params, $data) {
    $controller = new Controllers\WorkflowController();
    return $controller->apiSaveRouteDefinitions($params, $data);
}, true);

$router->apiPost('/workflow/templates/:id/route-steps', function ($params, $data) {
    $controller = new Controllers\WorkflowController();
    return $controller->apiAddRouteStep($params, $data);
}, true);

$router->apiPost('/workflow/templates/:id/route-steps/:step_id', function ($params, $data) {
    $controller = new Controllers\WorkflowController();
    return $controller->apiUpdateRouteStep($params, $data);
}, true);

$router->apiDelete('/workflow/templates/:id/route-steps/:step_id', function ($params) {
    $controller = new Controllers\WorkflowController();
    return $controller->apiDeleteRouteStep($params);
}, true);

// 申請関連API
$router->apiGet('/workflow/requests', function ($params) {
    $controller = new Controllers\WorkflowController();
    return $controller->apiGetRequests($params);
}, true);

$router->apiGet('/workflow/requests/:id', function ($params) {
    $controller = new Controllers\WorkflowController();
    return $controller->apiGetRequest($params);
}, true);

$router->apiPost('/workflow/requests', function ($params, $data) {
    $controller = new Controllers\WorkflowController();
    return $controller->apiCreateRequest($params, $data);
}, true);

$router->apiPost('/workflow/requests/:id', function ($params, $data) {
    $controller = new Controllers\WorkflowController();
    return $controller->apiUpdateRequest($params, $data);
}, true);

$router->apiDelete('/workflow/requests/:id', function ($params) {
    $controller = new Controllers\WorkflowController();
    return $controller->apiCancelRequest($params);
}, true);

// 承認関連API
$router->apiPost('/workflow/requests/:id/approve', function ($params, $data) {
    $controller = new Controllers\WorkflowController();
    return $controller->apiProcessApproval($params, $data);
}, true);

$router->apiPost('/workflow/requests/:id/comments', function ($params, $data) {
    $controller = new Controllers\WorkflowController();
    return $controller->apiAddComment($params, $data);
}, true);

// 代理承認設定API
$router->apiPost('/workflow/delegates', function ($params, $data) {
    $controller = new Controllers\WorkflowController();
    return $controller->apiAddDelegation($params, $data);
}, true);

// エクスポートAPI
$router->apiGet('/workflow/requests/:id/export/pdf', function ($params) {
    $controller = new Controllers\WorkflowController();
    return $controller->apiExportPdf($params);
}, true);

$router->apiGet('/workflow/requests/:id/export/csv', function ($params) {
    $controller = new Controllers\WorkflowController();
    return $controller->apiExportCsv($params);
}, true);

// 統計情報API
$router->apiGet('/workflow/stats', function ($params) {
    $controller = new Controllers\WorkflowController();
    return $controller->apiGetStats($params);
}, true);

// メッセージ機能のルーティング
// メッセージの受信トレイ
$router->get('/messages/inbox', function () {
    $controller = new Controllers\MessageController();
    $controller->inbox();
}, true);

// 送信済みメッセージ
$router->get('/messages/sent', function () {
    $controller = new Controllers\MessageController();
    $controller->sent();
}, true);

// スター付きメッセージ
$router->get('/messages/starred', function () {
    $controller = new Controllers\MessageController();
    $controller->starred();
}, true);

// 新規メッセージ作成
$router->get('/messages/compose', function () {
    $controller = new Controllers\MessageController();
    $controller->create();
}, true);

// メッセージ返信
$router->get('/messages/reply/:id', function ($params) {
    $controller = new Controllers\MessageController();
    $controller->reply($params);
}, true);

// 全員に返信
$router->get('/messages/reply-all/:id', function ($params) {
    $controller = new Controllers\MessageController();
    $controller->replyAll($params);
}, true);

// メッセージ転送
$router->get('/messages/forward/:id', function ($params) {
    $controller = new Controllers\MessageController();
    $controller->forward($params);
}, true);

// メッセージ詳細表示
$router->get('/messages/view/:id', function ($params) {
    $controller = new Controllers\MessageController();
    $controller->viewDetails($params);
}, true);

// メッセージAPI
// メッセージ送信API
$router->apiPost('/messages/send', function ($params, $data) {
    $controller = new Controllers\MessageController();
    return $controller->apiSend($params, $data);
}, true);

// 既読にするAPI
$router->apiPost('/messages/:id/read', function ($params, $data) {
    $controller = new Controllers\MessageController();
    return $controller->apiMarkAsRead($params, $data);
}, true);

// 未読にするAPI
$router->apiPost('/messages/:id/unread', function ($params, $data) {
    $controller = new Controllers\MessageController();
    return $controller->apiMarkAsUnread($params, $data);
}, true);

// スター付けAPI
$router->apiPost('/messages/:id/star', function ($params, $data) {
    $controller = new Controllers\MessageController();
    return $controller->apiToggleStar($params, $data);
}, true);

// メッセージ削除API
$router->apiDelete('/messages/:id', function ($params) {
    $controller = new Controllers\MessageController();
    return $controller->apiDelete($params);
}, true);

// 未読メッセージ数API
$router->apiGet('/messages/unread-count', function () {
    $controller = new Controllers\MessageController();
    return $controller->apiGetUnreadCount();
}, true);

// システム設定関連のルート
// 基本設定
$router->get('/settings', function () {
    $controller = new Controllers\SettingController();
    $controller->index();
}, true);

// SMTP設定
$router->get('/settings/smtp', function () {
    $controller = new Controllers\SettingController();
    $controller->smtp();
}, true);

// 通知設定
$router->get('/settings/notification', function () {
    $controller = new Controllers\SettingController();
    $controller->notification();
}, true);

// システム設定API
$router->apiPost('/settings', function ($params, $data) {
    $controller = new Controllers\SettingController();
    return $controller->apiUpdate($params, $data);
}, true);

// SMTPテストAPI
$router->apiPost('/settings/test-smtp', function ($params, $data) {
    $controller = new Controllers\SettingController();
    return $controller->apiTestSmtp($params, $data);
}, true);

// メール送信処理API
$router->apiPost('/settings/process-email-queue', function ($params, $data) {
    $controller = new Controllers\NotificationController();
    $notificationModel = new Models\Notification();
    $result = $notificationModel->processEmailQueue(20);

    return [
        'success' => $result['success'],
        'message' => $result['message'],
        'data' => [
            'processed' => $result['processed'],
            'success_count' => $result['success_count'],
            'failed_count' => $result['failed_count']
        ]
    ];
}, true);

// 通知関連のルート
// 通知一覧
$router->get('/notifications', function () {
    $controller = new Controllers\NotificationController();
    $controller->index();
}, true);

// 通知設定
$router->get('/notifications/settings', function () {
    $controller = new Controllers\NotificationController();
    $controller->settings();
}, true);

// 未読通知取得API
$router->apiGet('/notifications/unread', function () {
    $controller = new Controllers\NotificationController();
    return $controller->apiGetUnread();
}, true);

// 通知既読API
$router->apiPost('/notifications/:id/read', function ($params) {
    $controller = new Controllers\NotificationController();
    return $controller->apiMarkAsRead($params);
}, true);

// 全通知既読API
$router->apiPost('/notifications/read-all', function () {
    $controller = new Controllers\NotificationController();
    return $controller->apiMarkAllAsRead();
}, true);

// 通知設定更新API
$router->apiPost('/notifications/settings', function ($params, $data) {
    $controller = new Controllers\NotificationController();
    return $controller->apiUpdateSettings($params, $data);
}, true);

// 未読通知取得API
$router->apiGet('/notifications/unread', function () {
    $controller = new Controllers\NotificationController();
    return $controller->apiGetUnread();
}, true);

// ホーム画面用API
$router->apiGet('/home/unread-counts', function () {
    $controller = new Controllers\HomeController();
    return $controller->apiGetUnreadCounts();
}, true);

// WEBデータベース関連のルート
$router->get('/webdatabase', function () {
    $controller = new Controllers\WebDatabaseController();
    $controller->index();
}, true);

$router->get('/webdatabase/create', function () {
    $controller = new Controllers\WebDatabaseController();
    $controller->create();
}, true);

$router->get('/webdatabase/edit/:id', function ($params) {
    $controller = new Controllers\WebDatabaseController();
    $controller->edit($params);
}, true);

$router->get('/webdatabase/fields/:id', function ($params) {
    $controller = new Controllers\WebDatabaseController();
    $controller->fields($params);
}, true);

$router->get('/webdatabase/records/:id', function ($params) {
    $controller = new Controllers\WebDatabaseController();
    $controller->records($params);
}, true);

$router->get('/webdatabase/create-record/:id', function ($params) {
    $controller = new Controllers\WebDatabaseController();
    $controller->createRecord($params);
}, true);

$router->get('/webdatabase/edit/:id/:record_id', function ($params) {
    $params['record_id'] = $params['record_id'] ?? null;
    $controller = new Controllers\WebDatabaseController();
    $controller->editRecord($params);
}, true);

$router->get('/webdatabase/view/:id/:record_id', function ($params) {
    $params['record_id'] = $params['record_id'] ?? null;
    $controller = new Controllers\WebDatabaseController();
    $controller->viewRecord($params);
}, true);

// WEBデータベースAPI
$router->apiGet('/webdatabase', function ($params) {
    $controller = new Controllers\WebDatabaseController();
    return $controller->apiGetDatabases($params);
}, true);

$router->apiPost('/webdatabase', function ($params, $data) {
    $controller = new Controllers\WebDatabaseController();
    return $controller->apiCreateDatabase($params, $data);
}, true);

$router->apiGet('/webdatabase/:id/records', function ($params) {
    // error_log('Routerr: apiGetRecords' . json_encode(['params: ' . $params]));
    $controller = new Controllers\WebDatabaseController();
    return $controller->apiGetRecords($params);
}, true);

$router->apiPost('/webdatabase/:id', function ($params, $data) {
    $controller = new Controllers\WebDatabaseController();
    return $controller->apiUpdateDatabase($params, $data);
}, true);

$router->apiDelete('/webdatabase/:id', function ($params) {
    $controller = new Controllers\WebDatabaseController();
    return $controller->apiDeleteDatabase($params);
}, true);

$router->apiPost('/webdatabase/:id/fields', function ($params, $data) {
    $controller = new Controllers\WebDatabaseController();
    return $controller->apiCreateField($params, $data);
}, true);

$router->apiPost('/webdatabase/fields/:id', function ($params, $data) {
    $controller = new Controllers\WebDatabaseController();
    return $controller->apiUpdateField($params, $data);
}, true);

$router->apiDelete('/webdatabase/fields/:id', function ($params) {
    $controller = new Controllers\WebDatabaseController();
    return $controller->apiDeleteField($params);
}, true);

$router->apiPost('/webdatabase/record/:id', function ($params, $data) {
    $controller = new Controllers\WebDatabaseController();
    return $controller->apiCreateRecord($params, $data);
}, true);

$router->apiPost('/webdatabase/record/:id/:record_id', function ($params, $data) {
    $controller = new Controllers\WebDatabaseController();
    return $controller->apiUpdateRecord($params, $data);
}, true);

$router->apiDelete('/webdatabase/record/:id/:record_id', function ($params) {
    $controller = new Controllers\WebDatabaseController();
    return $controller->apiDeleteRecord($params);
}, true);

// CSVエクスポート関連のルート
$router->get('/webdatabase/export-csv/:id', function ($params) {
    $controller = new Controllers\WebDatabaseController();
    $controller->exportCsv($params);
}, true);

$router->apiGet('/webdatabase/:id/export-csv', function ($params) {
    $controller = new Controllers\WebDatabaseController();
    return $controller->apiExportCsv($params);
}, true);

// CSVインポート関連のルート
$router->get('/webdatabase/import-csv/:id', function ($params) {
    $controller = new Controllers\WebDatabaseController();
    $controller->importCsv($params);
}, true);

$router->apiPost('/webdatabase/:id/import-csv', function ($params, $data) {
    $controller = new Controllers\WebDatabaseController();
    return $controller->apiImportCsv($params, $data);
}, true);

// フィールド情報取得API
$router->apiGet('/webdatabase/fields/:id', function ($params) {
    $controller = new Controllers\WebDatabaseController();
    return $controller->apiGetField($params);
}, true);

// タスク管理ルーティング
// タスク管理ダッシュボード
$router->get('/task', function () {
    $controller = new Controllers\TaskController();
    $controller->index();
}, true);

// 個人タスクボード一覧
$router->get('/task/my-boards', function () {
    $controller = new Controllers\TaskController();
    $controller->myBoards();
}, true);

// チームタスクボード一覧
$router->get('/task/team-boards', function () {
    $controller = new Controllers\TaskController();
    $controller->teamBoards();
}, true);

// 組織タスクボード一覧
$router->get('/task/organization-boards', function () {
    $controller = new Controllers\TaskController();
    $controller->organizationBoards();
}, true);

// チーム一覧
$router->get('/task/teams', function () {
    $controller = new Controllers\TaskController();
    $controller->teams();
}, true);

// チーム作成画面
$router->get('/task/create-team', function () {
    $controller = new Controllers\TaskController();
    $controller->createTeam();
}, true);

// チーム編集画面
$router->get('/task/edit-team/:id', function ($params) {
    $controller = new Controllers\TaskController();
    $controller->editTeam($params);
}, true);

// チーム詳細画面
$router->get('/task/team/:id', function ($params) {
    $controller = new Controllers\TaskController();
    $controller->viewTeam($params);
}, true);

// タスクボード作成画面
$router->get('/task/create-board', function () {
    $controller = new Controllers\TaskController();
    $controller->createBoard();
}, true);

// タスクボード編集画面
$router->get('/task/edit-board/:id', function ($params) {
    $controller = new Controllers\TaskController();
    $controller->editBoard($params);
}, true);

// タスクボード表示画面
$router->get('/task/board/:id', function ($params) {
    $controller = new Controllers\TaskController();
    $controller->board($params);
}, true);

// カード詳細画面
$router->get('/task/card/:id', function ($params) {
    $controller = new Controllers\TaskController();
    $controller->card($params);
}, true);

// カード作成画面
$router->get('/task/create-card/:list_id', function ($params) {
    $controller = new Controllers\TaskController();
    $controller->createCard($params);
}, true);

// カード編集画面
$router->get('/task/edit-card/:id', function ($params) {
    $controller = new Controllers\TaskController();
    $controller->editCard($params);
}, true);

// マイタスク一覧
$router->get('/task/my-tasks', function () {
    $controller = new Controllers\TaskController();
    $controller->myTasks();
}, true);

// API: タスク管理関連のAPI

// API: チーム作成
$router->apiPost('/task/teams', function ($params, $data) {
    $controller = new Controllers\TaskController();
    return $controller->apiCreateTeam($params, $data);
}, true);

// API: チーム更新
$router->apiPost('/task/teams/:id', function ($params, $data) {
    $controller = new Controllers\TaskController();
    return $controller->apiUpdateTeam($params, $data);
}, true);

// API: チーム削除
$router->apiDelete('/task/teams/:id', function ($params) {
    $controller = new Controllers\TaskController();
    return $controller->apiDeleteTeam($params);
}, true);

// API: タスクボード作成
$router->apiPost('/task/boards', function ($params, $data) {
    $controller = new Controllers\TaskController();
    return $controller->apiCreateBoard($params, $data);
}, true);

// API: タスクボード更新
$router->apiPost('/task/boards/:id', function ($params, $data) {
    $controller = new Controllers\TaskController();
    return $controller->apiUpdateBoard($params, $data);
}, true);

// API: タスクボード削除
$router->apiDelete('/task/boards/:id', function ($params) {
    $controller = new Controllers\TaskController();
    return $controller->apiDeleteBoard($params);
}, true);

// API: タスクボード概要情報取得
$router->apiGet('/task/boards/:id/summary', function ($params) {
    $controller = new Controllers\TaskController();
    return $controller->apiGetBoardSummary($params);
}, true);

// API: タスクリスト作成
$router->apiPost('/task/lists', function ($params, $data) {
    $controller = new Controllers\TaskController();
    return $controller->apiCreateList($params, $data);
}, true);

// API: タスクリスト更新
$router->apiPost('/task/lists/:id', function ($params, $data) {
    $controller = new Controllers\TaskController();
    return $controller->apiUpdateList($params, $data);
}, true);

// API: タスクリスト削除
$router->apiDelete('/task/lists/:id', function ($params) {
    $controller = new Controllers\TaskController();
    return $controller->apiDeleteList($params);
}, true);

// API: タスクリスト情報取得
$router->apiGet('/task/lists/:id', function ($params) {
    $controller = new Controllers\TaskController();
    return $controller->apiGetList($params);
}, true);

// API: リスト順序更新
$router->apiPost('/task/lists/:id/order', function ($params, $data) {
    $controller = new Controllers\TaskController();
    return $controller->apiUpdateListOrder($params, $data);
}, true);

// API: タスクカード作成
$router->apiPost('/task/cards', function ($params, $data) {
    $controller = new Controllers\TaskController();
    return $controller->apiCreateCard($params, $data);
}, true);

// API: タスクカード更新
$router->apiPost('/task/cards/:id', function ($params, $data) {
    $controller = new Controllers\TaskController();
    return $controller->apiUpdateCard($params, $data);
}, true);

// API: タスクカード削除
$router->apiDelete('/task/cards/:id', function ($params) {
    $controller = new Controllers\TaskController();
    return $controller->apiDeleteCard($params);
}, true);

// API: タスクカード情報取得
$router->apiGet('/task/cards/:id', function ($params) {
    $controller = new Controllers\TaskController();
    return $controller->apiGetCard($params);
}, true);

// API: カード順序更新
$router->apiPost('/task/cards/:id/order', function ($params, $data) {
    $controller = new Controllers\TaskController();
    return $controller->apiUpdateCardOrder($params, $data);
}, true);

// API: コメント追加
$router->apiPost('/task/cards/:id/comments', function ($params, $data) {
    $controller = new Controllers\TaskController();
    return $controller->apiAddComment($params, $data);
}, true);

// API: ラベル作成
$router->apiPost('/task/labels', function ($params, $data) {
    $controller = new Controllers\TaskController();
    return $controller->apiCreateLabel($params, $data);
}, true);

// API: ボードメンバー追加
$router->apiPost('/task/board-members', function ($params, $data) {
    $controller = new Controllers\TaskController();
    return $controller->apiAddBoardMember($params, $data);
}, true);

// API: チェックリスト作成
$router->apiPost('/task/cards/:id/checklists', function ($params, $data) {
    $controller = new Controllers\TaskController();
    return $controller->apiCreateChecklist($params, $data);
}, true);

// API: チェックリスト項目更新
$router->apiPost('/task/checklist-items/:id', function ($params, $data) {
    $controller = new Controllers\TaskController();
    return $controller->apiUpdateChecklistItem($params, $data);
}, true);

// API: チェックリスト削除
$router->apiDelete('/task/checklists/:id', function ($params) {
    $controller = new Controllers\TaskController();
    return $controller->apiDeleteChecklist($params);
}, true);

// API: チェックリスト項目追加
$router->apiPost('/task/checklists/:id/items', function ($params, $data) {
    $controller = new Controllers\TaskController();
    return $controller->apiAddChecklistItem($params, $data);
}, true);

// API: チェックリスト項目削除
$router->apiDelete('/task/checklist-items/:id', function ($params) {
    $controller = new Controllers\TaskController();
    return $controller->apiDeleteChecklistItem($params);
}, true);

// リクエストのディスパッチ（ルーティング処理の実行）
$router->dispatch();

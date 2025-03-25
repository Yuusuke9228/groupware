<?php
// controllers/NotificationController.php
namespace Controllers;

use Core\Controller;
use Core\Database;
use Core\Auth;
use Models\Notification;
use Models\Setting;

class NotificationController extends Controller
{
    private $db;
    private $model;
    private $setting;

    public function __construct()
    {
        parent::__construct();
        $this->db = Database::getInstance();
        $this->model = new Notification();
        $this->setting = new Setting();

        // 認証チェック
        if (!$this->auth->check()) {
            $this->redirect(BASE_PATH . '/login');
        }
    }

    /**
     * 通知一覧ページを表示
     */
    public function index()
    {
        // ページネーション
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $page = max(1, $page);
        $limit = 20;

        // フィルター条件
        $filters = [
            'type' => $_GET['type'] ?? null,
            'is_read' => isset($_GET['is_read']) ? (bool)$_GET['is_read'] : null,
            'search' => $_GET['search'] ?? null,
        ];

        // 現在のユーザーID
        $userId = $this->auth->id();

        // 通知リストを取得
        $notifications = $this->model->getNotifications($userId, $filters, $page, $limit);
        $totalNotifications = $this->model->getCount($userId, $filters);
        $totalPages = ceil($totalNotifications / $limit);

        // 未読通知数
        $unreadCount = $this->model->getUnreadCount($userId);

        $viewData = [
            'title' => '通知一覧',
            'notifications' => $notifications,
            'totalNotifications' => $totalNotifications,
            'unreadCount' => $unreadCount,
            'page' => $page,
            'totalPages' => $totalPages,
            'filters' => $filters,
            'jsFiles' => ['notification.js']
        ];

        $this->view('notification/index', $viewData);
    }

    /**
     * 通知設定ページを表示
     */
    public function settings()
    {
        // 現在のユーザーID
        $userId = $this->auth->id();

        // ユーザーの通知設定を取得
        $sql = "SELECT * FROM notification_settings WHERE user_id = ? LIMIT 1";
        $settings = $this->db->fetch($sql, [$userId]);

        // 設定がない場合はデフォルト設定を作成
        if (!$settings) {
            $this->model->createDefaultSettings($userId);
            $settings = [
                'notify_schedule' => 1,
                'notify_workflow' => 1,
                'notify_message' => 1,
                'email_notify' => 1,
            ];
        }

        $viewData = [
            'title' => '通知設定',
            'settings' => $settings,
            'jsFiles' => ['notification.js']
        ];

        $this->view('notification/settings', $viewData);
    }

    /**
     * API: 未読通知一覧を取得
     */
    public function apiGetUnread()
    {
        // 認証チェック
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }

        // 現在のユーザーID
        $userId = $this->auth->id();

        // 未読通知数
        $unreadCount = $this->model->getUnreadCount($userId);

        // 未読通知一覧（最新10件）
        $notifications = $this->model->getUnread($userId, 10);

        return [
            'success' => true,
            'data' => [
                'unread_count' => $unreadCount,
                'notifications' => $notifications
            ]
        ];
    }

    /**
     * API: 通知を既読にする
     */
    public function apiMarkAsRead($params)
    {
        // 認証チェック
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }

        $id = $params['id'] ?? null;
        if (!$id) {
            return ['error' => 'Invalid ID', 'code' => 400];
        }

        // 現在のユーザーID
        $userId = $this->auth->id();

        $result = $this->model->markAsRead($id, $userId);
        if (!$result) {
            return ['error' => 'Failed to mark notification as read', 'code' => 500];
        }

        return [
            'success' => true,
            'message' => '通知を既読にしました'
        ];
    }

    /**
     * API: 全ての通知を既読にする
     */
    public function apiMarkAllAsRead()
    {
        // 認証チェック
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }

        // 現在のユーザーID
        $userId = $this->auth->id();

        $result = $this->model->markAllAsRead($userId);
        if (!$result) {
            return ['error' => 'Failed to mark all notifications as read', 'code' => 500];
        }

        return [
            'success' => true,
            'message' => 'すべての通知を既読にしました'
        ];
    }

    /**
     * API: 通知設定を更新
     */
    public function apiUpdateSettings($params, $data)
    {
        // 認証チェック
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }

        // 現在のユーザーID
        $userId = $this->auth->id();

        $result = $this->model->updateSettings($userId, $data);
        if (!$result) {
            return ['error' => 'Failed to update notification settings', 'code' => 500];
        }

        return [
            'success' => true,
            'message' => '通知設定を更新しました'
        ];
    }
}

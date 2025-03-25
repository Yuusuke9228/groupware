<?php
// controllers/MessageController.php
namespace Controllers;

use Core\Controller;
use Core\Database;
use Core\Auth;
use Models\Message;
use Models\User;
use Models\Organization;
use Models\Notification;

class MessageController extends Controller
{
    private $db;
    private $model;
    private $userModel;
    private $organizationModel;
    private $notification;

    public function __construct()
    {
        parent::__construct();
        $this->db = Database::getInstance();
        $this->model = new Message();
        $this->userModel = new User();
        $this->organizationModel = new Organization();
        $this->notification = new Notification();

        // 認証チェック
        if (!$this->auth->check()) {
            $this->redirect(BASE_PATH . '/login');
        }
    }

    /**
     * 受信トレイページを表示
     */
    public function inbox()
    {
        // ページネーション
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $page = max(1, $page);
        $limit = 20;

        // フィルター条件
        $filters = [
            'is_read' => $_GET['is_read'] ?? 'all', // all, read, unread
            'is_starred' => isset($_GET['is_starred']),
            'search' => $_GET['search'] ?? null,
        ];

        // 現在のユーザーID
        $userId = $this->auth->id();

        // メッセージリストを取得
        $messages = $this->model->getInbox($userId, $filters, $page, $limit);
        $totalMessages = $this->model->getInboxCount($userId, $filters);
        $totalPages = ceil($totalMessages / $limit);

        // 未読メッセージ数
        $unreadCount = $this->model->getUnreadCount($userId);

        $viewData = [
            'title' => '受信トレイ',
            'section' => 'inbox',
            'messages' => $messages,
            'totalMessages' => $totalMessages,
            'unreadCount' => $unreadCount,
            'page' => $page,
            'totalPages' => $totalPages,
            'filters' => $filters,
            'jsFiles' => ['message.js']
        ];

        $this->view('message/inbox', $viewData);
    }

    /**
     * 送信済みメッセージページを表示
     */
    public function sent()
    {
        // ページネーション
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $page = max(1, $page);
        $limit = 20;

        // フィルター条件
        $filters = [
            'search' => $_GET['search'] ?? null,
        ];

        // 現在のユーザーID
        $userId = $this->auth->id();

        // メッセージリストを取得
        $messages = $this->model->getSent($userId, $filters, $page, $limit);
        $totalMessages = $this->model->getSentCount($userId, $filters);
        $totalPages = ceil($totalMessages / $limit);

        // 未読メッセージ数
        $unreadCount = $this->model->getUnreadCount($userId);

        $viewData = [
            'title' => '送信済み',
            'section' => 'sent',
            'messages' => $messages,
            'totalMessages' => $totalMessages,
            'unreadCount' => $unreadCount,
            'page' => $page,
            'totalPages' => $totalPages,
            'filters' => $filters,
            'jsFiles' => ['message.js']
        ];

        $this->view('message/sent', $viewData);
    }

    /**
     * スター付きメッセージページを表示
     */
    public function starred()
    {
        // ページネーション
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $page = max(1, $page);
        $limit = 20;

        // フィルター条件
        $filters = [
            'is_starred' => true,
            'search' => $_GET['search'] ?? null,
        ];

        // 現在のユーザーID
        $userId = $this->auth->id();

        // メッセージリストを取得
        $messages = $this->model->getInbox($userId, $filters, $page, $limit);
        $totalMessages = $this->model->getInboxCount($userId, $filters);
        $totalPages = ceil($totalMessages / $limit);

        // 未読メッセージ数
        $unreadCount = $this->model->getUnreadCount($userId);

        $viewData = [
            'title' => 'スター付き',
            'section' => 'starred',
            'messages' => $messages,
            'totalMessages' => $totalMessages,
            'unreadCount' => $unreadCount,
            'page' => $page,
            'totalPages' => $totalPages,
            'filters' => $filters,
            'jsFiles' => ['message.js']
        ];

        $this->view('message/starred', $viewData);
    }

    /**
     * 新規メッセージ作成ページを表示
     */
    public function create()
    {
        // アクティブユーザー一覧を取得（受信者選択用）
        $users = $this->userModel->getActiveUsers();

        // 組織一覧を取得（組織選択用）
        $organizations = $this->organizationModel->getAll();

        $viewData = [
            'title' => '新規メッセージ',
            'section' => 'compose',
            'users' => $users,
            'organizations' => $organizations,
            'jsFiles' => ['message.js']
        ];

        $this->view('message/compose', $viewData);
    }

    /**
     * メッセージ返信ページを表示
     */
    public function reply($params)
    {
        $id = $params['id'] ?? null;
        if (!$id) {
            $this->redirect(BASE_PATH . '/messages/inbox');
        }

        // メッセージを取得
        $message = $this->model->getById($id);
        if (!$message) {
            $this->redirect(BASE_PATH . '/messages/inbox');
        }

        // 現在のユーザーIDを取得
        $userId = $this->auth->id();

        // 受信者チェック（受信したメッセージにのみ返信可能）
        $recipients = $this->model->getRecipients($id);
        $isRecipient = false;
        foreach ($recipients as $recipient) {
            if ($recipient['id'] == $userId) {
                $isRecipient = true;
                break;
            }
        }

        if (!$isRecipient && $message['sender_id'] != $userId) {
            $this->redirect(BASE_PATH . '/messages/inbox');
        }

        // メッセージを既読にする
        $this->model->markAsRead($id, $userId);

        // 返信用件名
        $subject = (strpos($message['subject'], 'Re:') === 0) ? $message['subject'] : 'Re: ' . $message['subject'];

        // アクティブユーザー一覧を取得（追加の受信者選択用）
        $users = $this->userModel->getActiveUsers();

        // 組織一覧を取得（組織選択用）
        $organizations = $this->organizationModel->getAll();

        // 返信時の初期受信者は送信者
        $initialRecipients = [$message['sender_id']];

        $viewData = [
            'title' => '返信',
            'section' => 'compose',
            'message' => $message,
            'subject' => $subject,
            'users' => $users,
            'organizations' => $organizations,
            'initialRecipients' => $initialRecipients,
            'isReply' => true,
            'parentId' => $id,
            'jsFiles' => ['message.js']
        ];

        $this->view('message/compose', $viewData);
    }

    /**
     * 全員へ返信ページを表示
     */
    public function replyAll($params)
    {
        $id = $params['id'] ?? null;
        if (!$id) {
            $this->redirect(BASE_PATH . '/messages/inbox');
        }

        // メッセージを取得
        $message = $this->model->getById($id);
        if (!$message) {
            $this->redirect(BASE_PATH . '/messages/inbox');
        }

        // 現在のユーザーIDを取得
        $userId = $this->auth->id();

        // 受信者チェック（受信したメッセージにのみ返信可能）
        $recipients = $this->model->getRecipients($id);
        $isRecipient = false;
        foreach ($recipients as $recipient) {
            if ($recipient['id'] == $userId) {
                $isRecipient = true;
                break;
            }
        }

        if (!$isRecipient && $message['sender_id'] != $userId) {
            $this->redirect(BASE_PATH . '/messages/inbox');
        }

        // メッセージを既読にする
        $this->model->markAsRead($id, $userId);

        // 返信用件名
        $subject = (strpos($message['subject'], 'Re:') === 0) ? $message['subject'] : 'Re: ' . $message['subject'];

        // アクティブユーザー一覧を取得（追加の受信者選択用）
        $users = $this->userModel->getActiveUsers();

        // 組織一覧を取得（組織選択用）
        $organizations = $this->organizationModel->getAll();

        // 全員返信時の初期受信者は送信者と全受信者（自分を除く）
        $initialRecipients = [$message['sender_id']];
        foreach ($recipients as $recipient) {
            if ($recipient['id'] != $userId) {
                $initialRecipients[] = $recipient['id'];
            }
        }
        // 重複を除去
        $initialRecipients = array_unique($initialRecipients);

        $viewData = [
            'title' => '全員に返信',
            'section' => 'compose',
            'message' => $message,
            'subject' => $subject,
            'users' => $users,
            'organizations' => $organizations,
            'initialRecipients' => $initialRecipients,
            'isReply' => true,
            'parentId' => $id,
            'jsFiles' => ['message.js']
        ];

        $this->view('message/compose', $viewData);
    }

    /**
     * 転送メッセージページを表示
     */
    public function forward($params)
    {
        $id = $params['id'] ?? null;
        if (!$id) {
            $this->redirect(BASE_PATH . '/messages/inbox');
        }

        // メッセージを取得
        $message = $this->model->getById($id);
        if (!$message) {
            $this->redirect(BASE_PATH . '/messages/inbox');
        }

        // 現在のユーザーIDを取得
        $userId = $this->auth->id();

        // 受信者または送信者チェック
        $recipients = $this->model->getRecipients($id);
        $isRecipient = false;
        foreach ($recipients as $recipient) {
            if ($recipient['id'] == $userId) {
                $isRecipient = true;
                break;
            }
        }

        if (!$isRecipient && $message['sender_id'] != $userId) {
            $this->redirect(BASE_PATH . '/messages/inbox');
        }

        // メッセージを既読にする
        if ($isRecipient) {
            $this->model->markAsRead($id, $userId);
        }

        // 転送用件名
        $subject = (strpos($message['subject'], 'Fw:') === 0) ? $message['subject'] : 'Fw: ' . $message['subject'];

        // アクティブユーザー一覧を取得（受信者選択用）
        $users = $this->userModel->getActiveUsers();

        // 組織一覧を取得（組織選択用）
        $organizations = $this->organizationModel->getAll();

        // 添付ファイル一覧を取得
        $attachments = $this->model->getAttachments($id);

        // 転送本文
        $forwardBody = "\n\n----- 転送メッセージ -----\n";
        $forwardBody .= "送信者: " . $message['sender_name'] . "\n";
        $forwardBody .= "日時: " . date('Y年m月d日 H:i', strtotime($message['created_at'])) . "\n";
        $forwardBody .= "件名: " . $message['subject'] . "\n\n";
        $forwardBody .= $message['body'];

        $viewData = [
            'title' => '転送',
            'section' => 'compose',
            'message' => $message,
            'subject' => $subject,
            'body' => $forwardBody,
            'users' => $users,
            'organizations' => $organizations,
            'originalAttachments' => $attachments,
            'isForward' => true,
            'jsFiles' => ['message.js']
        ];

        $this->view('message/compose', $viewData);
    }

    /**
     * メッセージ詳細ページを表示
     */
    public function viewDetails($params)
    {
        $id = $params['id'] ?? null;
        if (!$id) {
            $this->redirect(BASE_PATH . '/messages/inbox');
        }

        // メッセージを取得
        $message = $this->model->getById($id);
        if (!$message) {
            $this->redirect(BASE_PATH . '/messages/inbox');
        }

        // 現在のユーザーIDを取得
        $userId = $this->auth->id();

        // 受信者または送信者チェック
        $recipients = $this->model->getRecipients($id);
        $isRecipient = false;
        foreach ($recipients as $recipient) {
            if ($recipient['id'] == $userId) {
                $isRecipient = true;
                break;
            }
        }

        if (!$isRecipient && $message['sender_id'] != $userId) {
            $this->redirect(BASE_PATH . '/messages/inbox');
        }

        // メッセージを既読にする
        if ($isRecipient) {
            $this->model->markAsRead($id, $userId);
        }

        // 組織宛先を取得
        $organizations = $this->model->getOrganizations($id);

        // 添付ファイルを取得
        $attachments = $this->model->getAttachments($id);

        // スレッドメッセージを取得
        $threadMessages = [];
        if ($message['thread_id']) {
            $threadMessages = $this->model->getThreadMessages($message['thread_id']);
        }

        // 未読メッセージ数
        $unreadCount = $this->model->getUnreadCount($userId);

        $viewData = [
            'title' => $message['subject'],
            'section' => 'view',
            'message' => $message,
            'recipients' => $recipients,
            'organizations' => $organizations,
            'attachments' => $attachments,
            'threadMessages' => $threadMessages,
            'currentUserId' => $userId,
            'isRecipient' => $isRecipient,
            'unreadCount' => $unreadCount,
            'jsFiles' => ['message.js']
        ];

        $this->view('message/view', $viewData);
    }

    /**
     * API: メッセージを送信
     */
    public function apiSend($params, $data)
    {
        // 認証チェック
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }

        // バリデーション
        if (empty($data['subject']) || empty($data['body'])) {
            return ['error' => '件名と本文は必須です', 'code' => 400];
        }

        if (empty($data['recipients']) && empty($data['organizations'])) {
            return ['error' => '宛先を指定してください', 'code' => 400];
        }

        // 現在のユーザーIDを取得
        $userId = $this->auth->id();

        // メッセージデータを作成
        $messageData = [
            'subject' => $data['subject'],
            'body' => $data['body'],
            'sender_id' => $userId,
            'parent_id' => $data['parent_id'] ?? null,
            'recipients' => $data['recipients'] ?? [],
            'organizations' => $data['organizations'] ?? []
        ];

        // アップロードディレクトリを確認・作成（プロジェクトルートから指定）
        // $uploadDir = realpath(__DIR__ . '/../') . '/uploads/messages/';
        // $uploadDir = realpath(__DIR__ . '/../public/uploads/messages/');
        $uploadDir = realpath(__DIR__ . '/../public/uploads/messages/') . '/';

        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
            chmod($uploadDir, 0777); // 確実に書き込み権限を付与
        }

        error_log("Upload directory: {$uploadDir} exists: " . (file_exists($uploadDir) ? 'Yes' : 'No') . " writable: " . (is_writable($uploadDir) ? 'Yes' : 'No'));

        // ファイル処理
        if (isset($_FILES) && !empty($_FILES)) {
            $messageData['attachments'] = $this->processAttachments($_FILES);
        }

        // 元のメッセージの添付ファイルを引き継ぐ（転送時）
        if (!empty($data['original_attachments']) && is_array($data['original_attachments'])) {
            if (!isset($messageData['attachments'])) {
                $messageData['attachments'] = [];
            }

            foreach ($data['original_attachments'] as $attachmentId) {
                // 元の添付ファイル情報を取得
                $sql = "SELECT * FROM message_attachments WHERE id = ? LIMIT 1";
                $attachment = $this->db->fetch($sql, [$attachmentId]);

                if ($attachment) {
                    $messageData['attachments'][] = [
                        'name' => $attachment['file_name'],
                        'path' => $attachment['file_path'],
                        'size' => $attachment['file_size'],
                        'type' => $attachment['mime_type']
                    ];
                }
            }
        }

        try {
            $messageId = $this->model->create($messageData);

            if (!$messageId) {
                return ['error' => 'メッセージの送信に失敗しました', 'code' => 500];
            }

            // 通知を送信
            if ($messageId) {
                $this->sendMessageNotifications($messageId, $messageData);
            }

            return [
                'success' => true,
                'data' => ['id' => $messageId],
                'message' => 'メッセージを送信しました',
                'redirect' => BASE_PATH . '/messages/sent'
            ];
        } catch (\Exception $e) {
            error_log("Exception in apiSend: " . $e->getMessage());
            return ['error' => $e->getMessage(), 'code' => 500];
        }
    }

    /**
     * API: メッセージを既読にする
     */
    public function apiMarkAsRead($params, $data)
    {
        // 認証チェック
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }

        $messageId = $params['id'] ?? null;
        if (!$messageId) {
            return ['error' => 'Invalid ID', 'code' => 400];
        }

        // 現在のユーザーIDを取得
        $userId = $this->auth->id();

        $result = $this->model->markAsRead($messageId, $userId);
        if (!$result) {
            return ['error' => 'Failed to mark message as read', 'code' => 500];
        }

        return [
            'success' => true,
            'message' => 'メッセージを既読にしました'
        ];
    }

    /**
     * API: メッセージを未読にする
     */
    public function apiMarkAsUnread($params, $data)
    {
        // 認証チェック
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }

        $messageId = $params['id'] ?? null;
        if (!$messageId) {
            return ['error' => 'Invalid ID', 'code' => 400];
        }

        // 現在のユーザーIDを取得
        $userId = $this->auth->id();

        $result = $this->model->markAsUnread($messageId, $userId);
        if (!$result) {
            return ['error' => 'Failed to mark message as unread', 'code' => 500];
        }

        return [
            'success' => true,
            'message' => 'メッセージを未読にしました'
        ];
    }

    /**
     * API: メッセージにスターを付ける/外す
     */
    public function apiToggleStar($params, $data)
    {
        // 認証チェック
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }

        $messageId = $params['id'] ?? null;
        if (!$messageId) {
            return ['error' => 'Invalid ID', 'code' => 400];
        }

        $starred = $data['starred'] ?? false;

        // 現在のユーザーIDを取得
        $userId = $this->auth->id();

        $result = $this->model->toggleStar($messageId, $userId, $starred);
        if (!$result) {
            return ['error' => 'Failed to toggle star', 'code' => 500];
        }

        return [
            'success' => true,
            'message' => $starred ? 'スターを付けました' : 'スターを外しました'
        ];
    }

    /**
     * API: メッセージを削除する
     */
    public function apiDelete($params)
    {
        // 認証チェック
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }

        $messageId = $params['id'] ?? null;
        if (!$messageId) {
            return ['error' => 'Invalid ID', 'code' => 400];
        }

        // 現在のユーザーIDを取得
        $userId = $this->auth->id();

        $result = $this->model->deleteForRecipient($messageId, $userId);
        if (!$result) {
            return ['error' => 'Failed to delete message', 'code' => 500];
        }

        return [
            'success' => true,
            'message' => 'メッセージを削除しました'
        ];
    }

    /**
     * API: 未読メッセージ数を取得
     */
    public function apiGetUnreadCount()
    {
        // 認証チェック
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }

        // 現在のユーザーIDを取得
        $userId = $this->auth->id();

        // 未読メッセージ数を取得
        $unreadCount = $this->model->getUnreadCount($userId);

        return [
            'success' => true,
            'data' => ['unread_count' => $unreadCount]
        ];
    }

    /**
     * 添付ファイルのアップロード処理
     */
    private function processAttachments($files)
    {
        $processedFiles = [];
        // アップロードディレクトリを実際のプロジェクトルートから取得するように修正
        // $uploadDir = realpath(__DIR__ . '/../') . '/uploads/messages/';
        $uploadDir = realpath(__DIR__ . '/../public/uploads/messages/');
        $uploadDir = realpath(__DIR__ . '/../public/uploads/messages/') . '/';


        // アップロードディレクトリが存在しない場合は作成し、権限を設定
        if (!file_exists($uploadDir)) {
            if (!mkdir($uploadDir, 0777, true)) {
                error_log("Failed to create directory: {$uploadDir}");
                return $processedFiles;
            }
            chmod($uploadDir, 0777); // 確実に書き込み権限を付与
        }

        error_log("Upload directory: {$uploadDir} exists: " . (file_exists($uploadDir) ? 'Yes' : 'No') . " writable: " . (is_writable($uploadDir) ? 'Yes' : 'No'));
        error_log("Processing attachments: " . print_r($files, true));

        // ファイル情報の配列構造に応じた処理
        foreach ($files as $fieldName => $fileInfo) {
            // 単一ファイルの場合
            if (is_string($fileInfo['name'])) {
                $fileName = $fileInfo['name'];
                $tmpName = $fileInfo['tmp_name'];
                $fileSize = $fileInfo['size'];
                $fileType = $fileInfo['type'];
                $fileError = $fileInfo['error'];

                error_log("Processing single file: {$fileName}, Error: {$fileError}");

                // ファイルがアップロードされている場合のみ処理
                if (!empty($fileName) && $fileError === 0 && is_uploaded_file($tmpName)) {
                    // ファイル名をサニタイズして安全なファイル名に変換
                    $safeName = $this->sanitizeFileName($fileName);

                    // 一意のファイル名を生成
                    $uniqueName = uniqid() . '_' . $safeName;
                    $filePath = $uploadDir . $uniqueName;

                    error_log("Moving file from {$tmpName} to {$filePath}");

                    // ファイルを移動
                    if (move_uploaded_file($tmpName, $filePath)) {
                        error_log("File moved successfully");
                        $processedFiles[] = [
                            'name' => $fileName,  // 元のファイル名を保持（表示用）
                            'path' => 'uploads/messages/' . $uniqueName,
                            'size' => $fileSize,
                            'type' => $fileType
                        ];
                    } else {
                        $errorMsg = error_get_last();
                        error_log("Failed to move file: " . ($errorMsg ? $errorMsg['message'] : 'Unknown error'));
                        // ファイルのパーミッション確認
                        error_log("Upload dir permissions: " . substr(sprintf('%o', fileperms($uploadDir)), -4));
                    }
                }
            }
            // 複数ファイルの場合
            elseif (is_array($fileInfo['name'])) {
                error_log("Processing multiple files: " . count($fileInfo['name']));

                for ($i = 0; $i < count($fileInfo['name']); $i++) {
                    $fileName = $fileInfo['name'][$i];
                    $tmpName = $fileInfo['tmp_name'][$i];
                    $fileSize = $fileInfo['size'][$i];
                    $fileType = $fileInfo['type'][$i];
                    $fileError = $fileInfo['error'][$i];

                    error_log("Processing file #{$i}: {$fileName}, Error: {$fileError}");

                    // ファイルがアップロードされている場合のみ処理
                    if (!empty($fileName) && $fileError === 0 && is_uploaded_file($tmpName)) {
                        // ファイル名をサニタイズして安全なファイル名に変換
                        $safeName = $this->sanitizeFileName($fileName);

                        // 一意のファイル名を生成
                        $uniqueName = uniqid() . '_' . $safeName;
                        $filePath = $uploadDir . $uniqueName;

                        error_log("Moving file from {$tmpName} to {$filePath}");

                        // ファイルを移動
                        if (move_uploaded_file($tmpName, $filePath)) {
                            error_log("File moved successfully");
                            $processedFiles[] = [
                                'name' => $fileName,  // 元のファイル名を保持（表示用）
                                'path' => 'uploads/messages/' . $uniqueName,
                                'size' => $fileSize,
                                'type' => $fileType
                            ];
                        } else {
                            $errorMsg = error_get_last();
                            error_log("Failed to move file: " . ($errorMsg ? $errorMsg['message'] : 'Unknown error'));
                            error_log("Upload dir permissions: " . substr(sprintf('%o', fileperms($uploadDir)), -4));
                        }
                    }
                }
            }
        }

        error_log("Processed files: " . count($processedFiles));
        return $processedFiles;
    }

    /**
     * ファイル名をサニタイズして安全なファイル名に変換
     * 
     * @param string $filename 元のファイル名
     * @return string サニタイズされたファイル名
     */
    private function sanitizeFileName($filename)
    {
        // ファイル名と拡張子を分離
        $pathInfo = pathinfo($filename);
        $extension = isset($pathInfo['extension']) ? '.' . $pathInfo['extension'] : '';
        $basename = $pathInfo['filename'];

        // 日本語や特殊文字を含むファイル名を英数字とアンダースコアに変換
        $basename = preg_replace('/[^\p{L}\p{N}_.-]/u', '_', $basename);

        // スペースをアンダースコアに変換
        $basename = str_replace(' ', '_', $basename);

        // 空のファイル名の場合は代替名を使用
        if (empty($basename)) {
            $basename = 'file';
        }

        // 拡張子を付けて返す
        return $basename . $extension;
    }

    /**
     * メッセージ送信の通知を送信
     * 
     * @param int $messageId メッセージID
     * @param array $data メッセージデータ
     */
    private function sendMessageNotifications($messageId, $data)
    {
        // メッセージの詳細情報を取得
        $message = $this->model->getById($messageId);
        if (!$message) {
            return;
        }

        // 送信者の情報を取得
        $sender = $this->userModel->getById($message['sender_id']);
        if (!$sender) {
            return;
        }

        // 受信者一覧を取得
        $recipients = [];
        if (!empty($data['recipients']) && is_array($data['recipients'])) {
            $recipients = $data['recipients'];
        }

        // 送信者自身には通知しない
        $senderId = $message['sender_id'];

        // 親メッセージがある場合（返信の場合）
        $isReply = !empty($message['parent_id']);
        $parentTitle = '';

        if ($isReply) {
            $parentMessage = $this->model->getById($message['parent_id']);
            if ($parentMessage) {
                $parentTitle = $parentMessage['subject'];
            }
        }

        // 通知タイトルと内容
        $title = $isReply ? '返信メッセージが届きました' : '新しいメッセージが届きました';
        $content = "{$sender['display_name']}さんから" . ($isReply ? '返信が' : 'メッセージが') . "届きました。\n";
        $content .= "件名: {$message['subject']}\n";

        // 本文の一部を添付（長い場合は省略）
        $bodyPreview = mb_substr(strip_tags($message['body']), 0, 100);
        if (mb_strlen($message['body']) > 100) {
            $bodyPreview .= '...';
        }
        $content .= "本文: {$bodyPreview}";

        // リンク
        $link = "/messages/view/{$messageId}";

        // 各受信者に通知を送信
        foreach ($recipients as $recipientId) {
            // 送信者自身には通知しない
            if ($recipientId == $senderId) {
                continue;
            }

            // 通知データ
            $notificationData = [
                'user_id' => $recipientId,
                'type' => 'message',
                'title' => $title,
                'content' => $content,
                'link' => $link,
                'reference_id' => $messageId,
                'reference_type' => 'message'
            ];

            // 通知を送信
            $this->notification->create($notificationData);
        }

        // 組織宛ての場合は所属ユーザーに通知
        if (!empty($data['organizations']) && is_array($data['organizations'])) {
            foreach ($data['organizations'] as $organizationId) {
                // 組織に所属するユーザーを取得
                $userModel = new User();
                $orgUsers = $userModel->getUsersByOrganization($organizationId);

                foreach ($orgUsers as $user) {
                    // 送信者自身と既に通知済みのユーザーには通知しない
                    if ($user['id'] == $senderId || in_array($user['id'], $recipients)) {
                        continue;
                    }

                    // 通知データ
                    $notificationData = [
                        'user_id' => $user['id'],
                        'type' => 'message',
                        'title' => $title . '（組織宛）',
                        'content' => $content,
                        'link' => $link,
                        'reference_id' => $messageId,
                        'reference_type' => 'message'
                    ];

                    // 通知を送信
                    $this->notification->create($notificationData);
                }
            }
        }
    }
}

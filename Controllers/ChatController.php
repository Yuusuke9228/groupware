<?php
namespace Controllers;

use Core\Controller;
use Core\Database;
use Models\Chat;
use Models\Notification;
use Models\Setting;
use Models\User;

class ChatController extends Controller
{
    private $db;
    private $chatModel;
    private $userModel;
    private $notificationModel;
    private $settingModel;
    private $uploadDir;

    public function __construct()
    {
        parent::__construct();
        $this->db = Database::getInstance();
        $this->chatModel = new Chat();
        $this->userModel = new User();
        $this->notificationModel = new Notification();
        $this->settingModel = new Setting();
        $this->uploadDir = __DIR__ . '/../uploads/chat/';

        if (!is_dir($this->uploadDir)) {
            @mkdir($this->uploadDir, 0755, true);
        }

        if (!$this->auth->check()) {
            $this->redirect(BASE_PATH . '/login');
        }
    }

    public function index()
    {
        $userId = (int)$this->auth->id();
        if (!$this->chatModel->isReady()) {
            $this->view('chat/index', [
                'title' => tr_text('チャット', 'Chat'),
                'chatReady' => false,
                'migrationFile' => 'db/upgrade_20260401_chat_module.sql',
                'rooms' => [],
                'activeRoom' => null,
                'messages' => [],
                'members' => [],
                'activeUsers' => [],
                'csrf_token' => $this->generateCsrfToken(),
                'jsFiles' => ['chat.js'],
            ]);
            return;
        }

        $rooms = $this->chatModel->getRoomsForUser($userId);
        $activeRoomId = isset($_GET['room_id']) ? (int)$_GET['room_id'] : 0;
        if ($activeRoomId <= 0 && !empty($rooms)) {
            $activeRoomId = (int)$rooms[0]['id'];
        }

        $activeRoom = null;
        $messages = [];
        $members = [];
        $activeMemberUserIds = [];
        if ($activeRoomId > 0) {
            $activeRoom = $this->chatModel->getRoomByIdForUser($activeRoomId, $userId);
            if ($activeRoom) {
                $messages = $this->chatModel->getMessages($activeRoomId, 0, 200);
                $members = $this->chatModel->getRoomMembers($activeRoomId);
                $activeMemberUserIds = array_values(array_map(
                    function ($row) {
                        return (int)($row['id'] ?? 0);
                    },
                    $members
                ));
                $lastMessageId = 0;
                if (!empty($messages)) {
                    $last = end($messages);
                    $lastMessageId = (int)($last['id'] ?? 0);
                }
                if ($lastMessageId > 0) {
                    $this->chatModel->markRoomRead($activeRoomId, $userId, $lastMessageId);
                }
            }
        }

        $activeUsers = array_values(array_filter(
            $this->userModel->getActiveUsers(),
            function ($u) use ($userId) {
                return (int)($u['id'] ?? 0) !== $userId;
            }
        ));

        $this->view('chat/index', [
            'title' => tr_text('チャット', 'Chat'),
            'chatReady' => true,
            'rooms' => $rooms,
            'activeRoom' => $activeRoom,
            'messages' => $messages,
            'members' => $members,
            'activeMemberUserIds' => $activeMemberUserIds,
            'activeUsers' => $activeUsers,
            'currentUser' => $this->auth->user(),
            'csrf_token' => $this->generateCsrfToken(),
            'jsFiles' => ['chat.js'],
        ]);
    }

    public function createRoom()
    {
        if (!$this->chatModel->isReady()) {
            $_SESSION['flash_error'] = tr_text('チャット機能のDBが未適用です。', 'Chat database migration is missing.');
            $this->redirect(BASE_PATH . '/chat');
            return;
        }

        if (!$this->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = tr_text('不正なリクエストです。', 'Invalid request.');
            $this->redirect(BASE_PATH . '/chat');
            return;
        }

        $name = trim((string)($_POST['room_name'] ?? ''));
        $members = $_POST['member_user_ids'] ?? [];
        if (!is_array($members)) {
            $members = [$members];
        }

        $roomId = $this->chatModel->createRoom((int)$this->auth->id(), $name, $members);
        if ($roomId <= 0) {
            $_SESSION['flash_error'] = tr_text(
                'グループ作成に失敗しました。参加メンバーを確認してください。',
                'Failed to create group. Please check selected members.'
            );
            $this->redirect(BASE_PATH . '/chat');
            return;
        }

        $_SESSION['flash_success'] = tr_text('チャットグループを作成しました。', 'Chat group created.');
        $this->redirect(BASE_PATH . '/chat?room_id=' . $roomId);
    }

    public function updateRoom($params)
    {
        if (!$this->chatModel->isReady()) {
            $_SESSION['flash_error'] = tr_text('チャット機能のDBが未適用です。', 'Chat database migration is missing.');
            $this->redirect(BASE_PATH . '/chat');
            return;
        }

        if (!$this->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = tr_text('不正なリクエストです。', 'Invalid request.');
            $this->redirect(BASE_PATH . '/chat');
            return;
        }

        $roomId = (int)($params['id'] ?? 0);
        $userId = (int)$this->auth->id();
        if ($roomId <= 0 || !$this->chatModel->isMember($roomId, $userId)) {
            $_SESSION['flash_error'] = tr_text('チャットルームへアクセスできません。', 'You cannot access this room.');
            $this->redirect(BASE_PATH . '/chat');
            return;
        }

        $room = $this->chatModel->getRoomByIdForUser($roomId, $userId);
        if (!$room || (string)($room['room_type'] ?? '') !== 'group') {
            $_SESSION['flash_error'] = tr_text(
                'このチャットルームは編集できません。',
                'This chat room cannot be edited.'
            );
            $this->redirect(BASE_PATH . '/chat?room_id=' . $roomId);
            return;
        }

        $roomName = trim((string)($_POST['room_name'] ?? ''));
        $members = $_POST['member_user_ids'] ?? [];
        if (!is_array($members)) {
            $members = [$members];
        }

        $result = $this->chatModel->updateRoom($roomId, $userId, $roomName, $members);
        if (empty($result['success'])) {
            $reason = (string)($result['reason'] ?? '');
            if ($reason === 'member_too_few') {
                $_SESSION['flash_error'] = tr_text(
                    'メンバーは2名以上必要です。',
                    'At least 2 members are required.'
                );
            } else {
                $_SESSION['flash_error'] = tr_text(
                    'チャットルームの更新に失敗しました。',
                    'Failed to update chat room.'
                );
            }
            $this->redirect(BASE_PATH . '/chat?room_id=' . $roomId);
            return;
        }

        $_SESSION['flash_success'] = tr_text(
            'チャットルームを更新しました。',
            'Chat room updated.'
        );
        $this->redirect(BASE_PATH . '/chat?room_id=' . $roomId);
    }

    public function postMessage($params)
    {
        $roomId = (int)($params['id'] ?? 0);
        $userId = (int)$this->auth->id();
        if ($roomId <= 0 || !$this->chatModel->isMember($roomId, $userId)) {
            $this->respondMessageError(tr_text('チャットルームへアクセスできません。', 'You cannot access this room.'), 403);
            return;
        }

        if (!$this->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->respondMessageError(tr_text('不正なリクエストです。', 'Invalid request.'), 422);
            return;
        }

        $text = trim((string)($_POST['message_text'] ?? ''));
        $attachment = $this->handleAttachmentUpload($_FILES['attachment'] ?? null);
        if (isset($attachment['error'])) {
            $this->respondMessageError($attachment['error'], 422);
            return;
        }

        if ($text === '' && empty($attachment['stored_name'])) {
            $this->respondMessageError(tr_text('メッセージかファイルを入力してください。', 'Please enter a message or select a file.'), 422);
            return;
        }

        $messageId = $this->chatModel->postMessage($roomId, $userId, $text, $attachment);
        if ($messageId <= 0) {
            $this->respondMessageError(tr_text('送信に失敗しました。', 'Failed to send message.'), 500);
            return;
        }

        $message = $this->chatModel->getMessageById($messageId);
        $room = $this->chatModel->getRoomByIdForUser($roomId, $userId);
        if ($room && $message) {
            $this->notifyMembers($room, $message, $userId);
        }

        if ($this->isAjaxRequest()) {
            $this->json([
                'success' => true,
                'message' => tr_text('送信しました。', 'Message sent.'),
                'data' => [
                    'message' => $message,
                    'unread_count' => $this->chatModel->getUnreadCount($userId),
                ]
            ]);
            return;
        }

        $this->redirect(BASE_PATH . '/chat?room_id=' . $roomId);
    }

    public function apiMessages($params)
    {
        $roomId = (int)($params['id'] ?? 0);
        $userId = (int)$this->auth->id();
        if ($roomId <= 0 || !$this->chatModel->isMember($roomId, $userId)) {
            return ['error' => tr_text('アクセス権限がありません。', 'Forbidden'), 'code' => 403];
        }

        $sinceId = isset($_GET['since_id']) ? (int)$_GET['since_id'] : 0;
        $messages = $this->chatModel->getMessages($roomId, $sinceId, 150);
        $lastId = $sinceId;
        if (!empty($messages)) {
            $last = end($messages);
            $lastId = (int)($last['id'] ?? $sinceId);
            $this->chatModel->markRoomRead($roomId, $userId, $lastId);
        }

        return [
            'success' => true,
            'data' => [
                'messages' => $messages,
                'last_message_id' => $lastId,
                'unread_count' => $this->chatModel->getUnreadCount($userId),
            ]
        ];
    }

    public function apiRooms()
    {
        $userId = (int)$this->auth->id();
        if (!$this->chatModel->isReady()) {
            return ['success' => true, 'data' => ['rooms' => [], 'unread_count' => 0]];
        }

        return [
            'success' => true,
            'data' => [
                'rooms' => $this->chatModel->getRoomsForUser($userId),
                'unread_count' => $this->chatModel->getUnreadCount($userId),
            ]
        ];
    }

    public function apiRead($params, $data)
    {
        $roomId = (int)($params['id'] ?? 0);
        $userId = (int)$this->auth->id();
        if ($roomId <= 0 || !$this->chatModel->isMember($roomId, $userId)) {
            return ['error' => tr_text('アクセス権限がありません。', 'Forbidden'), 'code' => 403];
        }

        $lastMessageId = isset($data['last_message_id']) ? (int)$data['last_message_id'] : null;
        $this->chatModel->markRoomRead($roomId, $userId, $lastMessageId);

        return [
            'success' => true,
            'data' => [
                'unread_count' => $this->chatModel->getUnreadCount($userId),
                'last_message_id' => $this->chatModel->getLastMessageId($roomId),
            ]
        ];
    }

    public function apiMessageReaders($params)
    {
        $roomId = (int)($params['room_id'] ?? 0);
        $messageId = (int)($params['message_id'] ?? 0);
        $userId = (int)$this->auth->id();
        if ($roomId <= 0 || $messageId <= 0 || !$this->chatModel->isMember($roomId, $userId)) {
            return ['error' => tr_text('アクセス権限がありません。', 'Forbidden'), 'code' => 403];
        }

        $excludeUserId = isset($_GET['exclude_user_id']) ? (int)$_GET['exclude_user_id'] : 0;
        $readers = $this->chatModel->getReadMembersForMessage($roomId, $messageId, $userId, $excludeUserId);

        return [
            'success' => true,
            'data' => [
                'message_id' => $messageId,
                'readers' => $readers,
                'count' => count($readers),
            ]
        ];
    }

    public function apiUnreadCount()
    {
        $userId = (int)$this->auth->id();
        if (!$this->chatModel->isReady()) {
            return ['success' => true, 'data' => ['count' => 0]];
        }
        return [
            'success' => true,
            'data' => [
                'count' => $this->chatModel->getUnreadCount($userId),
            ]
        ];
    }

    public function downloadAttachment($params)
    {
        $messageId = (int)($params['id'] ?? 0);
        $userId = (int)$this->auth->id();
        $message = $this->chatModel->getMessageForUser($messageId, $userId);
        if (!$message || empty($message['attachment_path'])) {
            http_response_code(404);
            echo tr_text('ファイルが見つかりません。', 'File not found.');
            return;
        }

        $path = $this->uploadDir . (string)$message['attachment_path'];
        if (!is_file($path)) {
            http_response_code(404);
            echo tr_text('ファイルが見つかりません。', 'File not found.');
            return;
        }

        $mime = (string)($message['attachment_mime'] ?? 'application/octet-stream');
        $name = str_replace('"', '', (string)($message['attachment_name'] ?? basename($path)));

        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . $name . '"');
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: no-cache, must-revalidate');
        @set_time_limit(0);
        readfile($path);
        exit;
    }

    private function notifyMembers(array $room, array $message, $senderId)
    {
        $senderId = (int)$senderId;
        $sender = $this->auth->user();
        $senderName = trim((string)($sender['display_name'] ?? tr_text('ユーザー', 'User')));
        $roomName = trim((string)($room['display_name'] ?? $room['name'] ?? tr_text('チャット', 'Chat')));
        $snippet = trim((string)($message['message_text'] ?? ''));
        if ($snippet === '' && !empty($message['attachment_name'])) {
            $snippet = tr_text('ファイルを送信しました', 'Sent a file');
        }
        if (mb_strlen($snippet) > 80) {
            $snippet = mb_substr($snippet, 0, 80) . '...';
        }

        $targetUserIds = $this->chatModel->getParticipantUserIds((int)$room['id'], $senderId);
        $link = '/chat?room_id=' . (int)$room['id'];
        $title = tr_text('チャット新着', 'New chat message');
        $content = $senderName . ' / ' . $roomName . "\n" . $snippet;

        foreach ($targetUserIds as $targetUserId) {
            $this->notificationModel->create([
                'user_id' => (int)$targetUserId,
                'type' => 'message',
                'title' => $title,
                'content' => $content,
                'link' => $link,
                'reference_id' => (int)$message['id'],
                'reference_type' => 'chat_message',
                'suppress_email' => true,
            ]);
        }
    }

    private function handleAttachmentUpload($file)
    {
        if (!$file || (int)($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return [];
        }
        if ((int)($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['error' => tr_text('添付ファイルのアップロードに失敗しました。', 'Failed to upload attachment.')];
        }

        $size = (int)($file['size'] ?? 0);
        if ($size <= 0) {
            return ['error' => tr_text('添付ファイルサイズが不正です。', 'Invalid attachment size.')];
        }
        $maxSize = 50 * 1024 * 1024; // 50MB
        if ($size > $maxSize) {
            return ['error' => tr_text('添付ファイルは50MB以下にしてください。', 'Attachment must be 50MB or smaller.')];
        }

        $originalName = trim((string)($file['name'] ?? ''));
        if ($originalName === '') {
            $originalName = 'attachment.bin';
        }
        $ext = strtolower((string)pathinfo($originalName, PATHINFO_EXTENSION));
        $stored = uniqid('chat_', true) . ($ext !== '' ? '.' . $ext : '');
        $dest = $this->uploadDir . $stored;

        if (!move_uploaded_file((string)$file['tmp_name'], $dest)) {
            return ['error' => tr_text('添付ファイルの保存に失敗しました。', 'Failed to save attachment.')];
        }

        return [
            'stored_name' => $stored,
            'original_name' => $originalName,
            'mime_type' => (string)($file['type'] ?? 'application/octet-stream'),
            'file_size' => $size,
        ];
    }

    private function respondMessageError($message, $status = 422)
    {
        if ($this->isAjaxRequest()) {
            $this->json(['error' => (string)$message], (int)$status);
            return;
        }
        $_SESSION['flash_error'] = (string)$message;
        $roomId = isset($_POST['room_id']) ? (int)$_POST['room_id'] : 0;
        $redirect = BASE_PATH . '/chat';
        if ($roomId > 0) {
            $redirect .= '?room_id=' . $roomId;
        }
        $this->redirect($redirect);
    }

    private function isAjaxRequest()
    {
        return strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
    }
}

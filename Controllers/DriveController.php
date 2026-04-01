<?php
namespace Controllers;

use Core\Controller;
use Core\Database;
use Models\Notification;
use Models\Organization;
use Models\Setting;
use Models\User;

class DriveController extends Controller
{
    private $db;
    private $uploadDir;
    private $notification;
    private $userModel;
    private $organizationModel;
    private $settingModel;

    public function __construct()
    {
        parent::__construct();
        $this->db = Database::getInstance();
        $this->uploadDir = __DIR__ . '/../uploads/drive/';
        $this->notification = new Notification();
        $this->userModel = new User();
        $this->organizationModel = new Organization();
        $this->settingModel = new Setting();

        if (!is_dir($this->uploadDir)) {
            @mkdir($this->uploadDir, 0755, true);
        }
    }

    public function index()
    {
        if (!$this->ensureAuthenticated()) {
            return;
        }

        $currentUser = $this->auth->user();
        $search = trim((string)($_GET['q'] ?? ''));
        $items = $this->getVisibleItems($currentUser, $search);

        $this->view('drive/index', [
            'title' => tr_text('ファイル共有', 'File Sharing'),
            'items' => $items,
            'search' => $search,
            'csrf_token' => $this->generateCsrfToken(),
            'driveLimits' => $this->getDriveLimits(),
            'driveUsage' => $this->getDriveUsage($currentUser),
        ]);
    }

    public function upload()
    {
        if (!$this->ensureAuthenticated()) {
            return;
        }

        $currentUser = $this->auth->user();
        $orgOptions = $this->getSelectableOrganizations($currentUser);
        $usage = $this->getDriveUsage($currentUser);
        $limits = $this->getDriveLimits();

        $this->view('drive/upload', [
            'title' => tr_text('ファイル共有アップロード', 'File Sharing Upload'),
            'csrf_token' => $this->generateCsrfToken(),
            'orgOptions' => $orgOptions,
            'driveUsage' => $usage,
            'driveLimits' => $limits,
            'defaultShareExpiry' => $this->getDefaultShareExpiryInput(),
        ]);
    }

    public function store()
    {
        if (!$this->ensureAuthenticated()) {
            return;
        }

        if (!$this->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = tr_text('不正なリクエストです。', 'Invalid request.');
            $this->redirect($this->modulePath());
            return;
        }

        if (!isset($_FILES['file']) || (int)($_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $_SESSION['flash_error'] = tr_text('ファイルのアップロードに失敗しました。', 'Failed to upload file.');
            $this->redirect($this->modulePath('/upload'));
            return;
        }

        $currentUser = $this->auth->user();
        $file = $_FILES['file'];
        $ownershipScope = (string)($_POST['ownership_scope'] ?? 'user');
        $ownerOrganizationId = (int)($_POST['owner_organization_id'] ?? 0);
        $owner = $this->resolveUploadOwner($ownershipScope, $ownerOrganizationId, $currentUser);

        if ($owner === null) {
            $_SESSION['flash_error'] = tr_text('共有先組織の指定が不正です。', 'Invalid organization selected.');
            $this->redirect($this->modulePath('/upload'));
            return;
        }

        $fileSize = (int)($file['size'] ?? 0);
        $quotaError = $this->validateDriveQuota($fileSize, $currentUser, (int)$owner['owner_organization_id']);
        if ($quotaError !== null) {
            $_SESSION['flash_error'] = $quotaError;
            $this->redirect($this->modulePath('/upload'));
            return;
        }

        $title = trim((string)($_POST['title'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));
        if ($title === '') {
            $title = pathinfo((string)$file['name'], PATHINFO_FILENAME);
            if ($title === '') {
                $title = tr_text('名称未設定', 'Untitled');
            }
        }

        $originalName = (string)$file['name'];
        $extension = strtolower((string)pathinfo($originalName, PATHINFO_EXTENSION));
        $storedName = uniqid('drive_', true) . ($extension !== '' ? '.' . $extension : '');
        $destination = $this->uploadDir . $storedName;

        @set_time_limit(0);
        if (!move_uploaded_file((string)$file['tmp_name'], $destination)) {
            $_SESSION['flash_error'] = tr_text('ファイル保存に失敗しました。', 'Failed to save uploaded file.');
            $this->redirect($this->modulePath('/upload'));
            return;
        }

        $mimeType = (string)($file['type'] ?? '');
        $this->db->execute(
            "INSERT INTO drive_items (
                title, description, stored_name, original_name, mime_type, file_size,
                created_by, owner_type, owner_user_id, owner_organization_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $title,
                $description !== '' ? $description : null,
                $storedName,
                $originalName,
                $mimeType !== '' ? $mimeType : null,
                $fileSize,
                (int)$currentUser['id'],
                $owner['owner_type'],
                $owner['owner_user_id'],
                $owner['owner_organization_id'],
            ]
        );
        $itemId = (int)$this->db->lastInsertId();

        $createPublicLink = ((string)($_POST['create_public_link'] ?? '1') === '1');
        if ($createPublicLink) {
            $expiresAt = $this->parseExpiry((string)($_POST['link_expires_at'] ?? ''));
            if ($expiresAt === false) {
                $_SESSION['flash_error'] = tr_text('公開リンクの有効期限が不正です。', 'Invalid expiry for public share link.');
                $this->redirect($this->modulePath('/upload'));
                return;
            }
            $maxDownloads = (int)($_POST['link_max_downloads'] ?? 0);
            if ($maxDownloads <= 0) {
                $maxDownloads = null;
            }
            $linkPassword = trim((string)($_POST['link_password'] ?? ''));
            $passwordHash = $linkPassword !== '' ? password_hash($linkPassword, PASSWORD_DEFAULT) : null;

            try {
                $token = $this->insertShareLink(
                    $itemId,
                    (int)$currentUser['id'],
                    $expiresAt ?: null,
                    $passwordHash,
                    $maxDownloads,
                    [],
                    []
                );
                $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                $_SESSION['file_share_created_link'] = rtrim($scheme . '://' . $host . BASE_PATH, '/') . '/file-share/share/' . $token;
                $_SESSION['flash_success'] = tr_text('アップロードと公開共有リンクの発行が完了しました。', 'Upload completed and a public share link has been created.');
            } catch (\Throwable $e) {
                error_log('auto create public share link failed: ' . $e->getMessage());
                $_SESSION['flash_success'] = tr_text('アップロードしました（公開リンク作成は失敗）。', 'Uploaded (public link creation failed).');
            }

            $this->redirect($this->modulePath('/file/' . $itemId));
            return;
        }

        $_SESSION['flash_success'] = tr_text('ファイル共有へアップロードしました。', 'Uploaded to File Sharing.');
        $this->redirect($this->modulePath());
    }

    public function show($params)
    {
        if (!$this->ensureAuthenticated()) {
            return;
        }

        $itemId = (int)($params['id'] ?? 0);
        $item = $this->findDriveItem($itemId);
        $currentUser = $this->auth->user();

        if (!$item || !$this->canViewItem($item, $currentUser)) {
            $_SESSION['flash_error'] = tr_text('対象ファイルにアクセスできません。', 'You cannot access this file.');
            $this->redirect($this->modulePath());
            return;
        }

        $shareLinks = $this->getShareLinksForItem($itemId);
        $defaultExpiry = $this->getDefaultShareExpiryInput();
        $orgOptions = $this->organizationModel->getAll();
        $userOptions = $this->userModel->getActiveUsers();

        $this->view('drive/show', [
            'title' => htmlspecialchars((string)$item['title']) . ' - ' . tr_text('ファイル共有', 'File Sharing'),
            'item' => $item,
            'csrf_token' => $this->generateCsrfToken(),
            'shareLinks' => $shareLinks,
            'defaultShareExpiry' => $defaultExpiry,
            'orgOptions' => $orgOptions,
            'userOptions' => $userOptions,
            'canManage' => $this->canManageItem($item, $currentUser),
        ]);
    }

    public function download($params)
    {
        if (!$this->ensureAuthenticated()) {
            return;
        }

        $itemId = (int)($params['id'] ?? 0);
        $item = $this->findDriveItem($itemId);
        $currentUser = $this->auth->user();

        if (!$item || !$this->canViewItem($item, $currentUser)) {
            $_SESSION['flash_error'] = tr_text('対象ファイルにアクセスできません。', 'You cannot access this file.');
            $this->redirect($this->modulePath());
            return;
        }

        $this->streamDriveFile($item, true);
    }

    public function delete($params)
    {
        if (!$this->ensureAuthenticated()) {
            return;
        }

        if (!$this->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = tr_text('不正なリクエストです。', 'Invalid request.');
            $this->redirect($this->modulePath());
            return;
        }

        $itemId = (int)($params['id'] ?? 0);
        $item = $this->findDriveItem($itemId);
        $currentUser = $this->auth->user();

        if (!$item || !$this->canManageItem($item, $currentUser)) {
            $_SESSION['flash_error'] = tr_text('削除権限がありません。', 'You do not have permission to delete this file.');
            $this->redirect($this->modulePath());
            return;
        }

        $this->db->execute(
            "UPDATE drive_items SET deleted_at = NOW() WHERE id = ?",
            [$itemId]
        );

        $_SESSION['flash_success'] = tr_text('ファイル共有のファイルを削除しました。', 'File Sharing item deleted.');
        $this->redirect($this->modulePath());
    }

    public function createShareLink($params)
    {
        if (!$this->ensureAuthenticated()) {
            return;
        }

        $itemId = (int)($params['id'] ?? 0);
        if (!$this->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = tr_text('不正なリクエストです。', 'Invalid request.');
            $this->redirect($this->modulePath('/file/' . $itemId));
            return;
        }

        $item = $this->findDriveItem($itemId);
        $currentUser = $this->auth->user();
        if (!$item || !$this->canManageItem($item, $currentUser)) {
            $_SESSION['flash_error'] = tr_text('共有リンクを作成する権限がありません。', 'You do not have permission to create share links.');
            $this->redirect($this->modulePath());
            return;
        }

        $expiresAt = $this->parseExpiry((string)($_POST['expires_at'] ?? ''));
        if ($expiresAt === false) {
            $_SESSION['flash_error'] = tr_text('有効期限の形式が不正です。', 'Invalid expiry format.');
            $this->redirect($this->modulePath('/file/' . $itemId));
            return;
        }

        $maxDownloads = (int)($_POST['max_downloads'] ?? 0);
        if ($maxDownloads <= 0) {
            $maxDownloads = null;
        }
        $password = trim((string)($_POST['share_password'] ?? ''));
        $passwordHash = $password !== '' ? password_hash($password, PASSWORD_DEFAULT) : null;
        $shareMode = (string)($_POST['share_access_mode'] ?? 'public');
        $shareUserIds = [];
        $shareOrganizationIds = [];
        $notifyRecipients = false;
        if ($shareMode === 'restricted') {
            $shareUserIds = $this->normalizeIds($_POST['share_user_ids'] ?? []);
            $shareOrganizationIds = $this->normalizeIds($_POST['share_organization_ids'] ?? []);
            $notifyRecipients = ((string)($_POST['notify_recipients'] ?? '1') === '1');
        }

        try {
            $token = $this->insertShareLink(
                $itemId,
                (int)$currentUser['id'],
                $expiresAt ?: null,
                $passwordHash,
                $maxDownloads,
                $shareUserIds,
                $shareOrganizationIds
            );
        } catch (\Throwable $e) {
            error_log('createFileShareLink error: ' . $e->getMessage());
            $_SESSION['flash_error'] = tr_text('共有リンクの作成に失敗しました。', 'Failed to create share link.');
            $this->redirect($this->modulePath('/file/' . $itemId));
            return;
        }

        if ($notifyRecipients && (!empty($shareUserIds) || !empty($shareOrganizationIds))) {
            $this->notifyShareRecipients($item, $token, $shareUserIds, $shareOrganizationIds, $expiresAt ?: null);
        }

        $_SESSION['flash_success'] = tr_text('共有リンクを作成しました。', 'Share link created.');
        $this->redirect($this->modulePath('/file/' . $itemId));
    }

    public function revokeShareLink($params)
    {
        if (!$this->ensureAuthenticated()) {
            return;
        }

        if (!$this->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = tr_text('不正なリクエストです。', 'Invalid request.');
            $this->redirect($this->modulePath());
            return;
        }

        $shareId = (int)($params['id'] ?? 0);
        $share = $this->db->fetch(
            "SELECT dsl.*, di.id AS drive_item_id
             FROM drive_share_links dsl
             INNER JOIN drive_items di ON di.id = dsl.drive_item_id
             WHERE dsl.id = ? AND di.deleted_at IS NULL",
            [$shareId]
        );
        if (!$share) {
            $_SESSION['flash_error'] = tr_text('共有リンクが見つかりません。', 'Share link not found.');
            $this->redirect($this->modulePath());
            return;
        }

        $item = $this->findDriveItem((int)$share['drive_item_id']);
        $currentUser = $this->auth->user();
        if (!$item || !$this->canManageItem($item, $currentUser)) {
            $_SESSION['flash_error'] = tr_text('共有リンクを無効化する権限がありません。', 'You do not have permission to revoke this share link.');
            $this->redirect($this->modulePath());
            return;
        }

        $this->db->execute(
            "UPDATE drive_share_links SET revoked_at = NOW() WHERE id = ?",
            [$shareId]
        );

        $_SESSION['flash_success'] = tr_text('共有リンクを無効化しました。', 'Share link revoked.');
        $this->redirect($this->modulePath('/file/' . (int)$share['drive_item_id']));
    }

    public function shareAccess($params)
    {
        $token = trim((string)($params['token'] ?? ''));
        if ($token === '') {
            http_response_code(404);
            $this->view('drive/shared_download', [
                'title' => tr_text('ファイル共有リンク', 'File Sharing Link'),
                'status' => 'not_found',
                'message' => tr_text('共有リンクが見つかりません。', 'Share link not found.'),
            ]);
            return;
        }

        $share = $this->db->fetch(
            "SELECT dsl.*, di.title, di.stored_name, di.original_name, di.mime_type, di.file_size
             FROM drive_share_links dsl
             INNER JOIN drive_items di ON di.id = dsl.drive_item_id
             WHERE dsl.token = ? AND di.deleted_at IS NULL
             LIMIT 1",
            [$token]
        );
        if (!$share) {
            http_response_code(404);
            $this->view('drive/shared_download', [
                'title' => tr_text('ファイル共有リンク', 'File Sharing Link'),
                'status' => 'not_found',
                'message' => tr_text('共有リンクが見つかりません。', 'Share link not found.'),
            ]);
            return;
        }

        $status = $this->validateShareStatus($share);
        if ($status !== 'ok') {
            http_response_code(410);
            $this->view('drive/shared_download', [
                'title' => tr_text('ファイル共有リンク', 'File Sharing Link'),
                'status' => $status,
                'share' => $share,
                'message' => $this->shareStatusMessage($status),
            ]);
            return;
        }

        $targets = $this->db->fetchAll(
            "SELECT target_type, target_id FROM drive_share_targets WHERE share_link_id = ?",
            [(int)$share['id']]
        );
        $targetCheck = $this->validateTargetAccess($targets);
        if ($targetCheck !== 'ok') {
            $httpCode = $targetCheck === 'login_required' ? 401 : 403;
            http_response_code($httpCode);
            $this->view('drive/shared_download', [
                'title' => tr_text('ファイル共有リンク', 'File Sharing Link'),
                'status' => $targetCheck,
                'share' => $share,
                'message' => $this->shareStatusMessage($targetCheck),
            ]);
            return;
        }

        $passwordError = '';
        if (!empty($share['password_hash']) && !$this->isSharePasswordVerified($token)) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $password = (string)($_POST['share_password'] ?? '');
                if ($password !== '' && password_verify($password, (string)$share['password_hash'])) {
                    $this->markSharePasswordVerified($token);
                    $this->redirect($this->modulePath('/share/' . urlencode($token)));
                    return;
                }
                $passwordError = tr_text('パスワードが一致しません。', 'Password is incorrect.');
            }

            $this->view('drive/shared_download', [
                'title' => tr_text('ファイル共有リンク', 'File Sharing Link'),
                'status' => 'password_required',
                'share' => $share,
                'message' => tr_text('この共有リンクはパスワード保護されています。', 'This share link is password protected.'),
                'passwordError' => $passwordError,
            ]);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string)($_POST['download'] ?? '') === '1') {
            $this->streamSharedFile($share);
            return;
        }

        $this->view('drive/shared_download', [
            'title' => tr_text('ファイル共有リンク', 'File Sharing Link'),
            'status' => 'ready',
            'share' => $share,
            'message' => tr_text('共有ファイルをダウンロードできます。', 'You can download this shared file.'),
        ]);
    }

    private function ensureAuthenticated()
    {
        if ($this->auth->check()) {
            return true;
        }
        $this->redirect(BASE_PATH . '/login');
        return false;
    }

    private function getVisibleItems(array $user, $search = '')
    {
        if (($user['role'] ?? '') === 'admin') {
            $where = "WHERE di.deleted_at IS NULL";
            $params = [];
        } else {
            $organizationIds = $this->getUserOrganizationIds($user);
            $orgPlaceholders = implode(',', array_fill(0, count($organizationIds), '?'));
            $where = "WHERE di.deleted_at IS NULL AND (di.owner_user_id = ? OR di.owner_organization_id IN ($orgPlaceholders))";
            $params = array_merge([(int)$user['id']], $organizationIds);
        }

        if ($search !== '') {
            $where .= " AND (di.title LIKE ? OR di.original_name LIKE ?)";
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }

        $sql = "SELECT di.*, u.display_name AS creator_name, o.name AS owner_organization_name,
                       COALESCE(ls.active_link_count, 0) AS active_share_links
                FROM drive_items di
                LEFT JOIN users u ON u.id = di.created_by
                LEFT JOIN organizations o ON o.id = di.owner_organization_id
                LEFT JOIN (
                    SELECT drive_item_id, COUNT(*) AS active_link_count
                    FROM drive_share_links
                    WHERE revoked_at IS NULL
                    GROUP BY drive_item_id
                ) ls ON ls.drive_item_id = di.id
                $where
                ORDER BY di.updated_at DESC";

        $items = $this->db->fetchAll($sql, $params);
        foreach ($items as &$item) {
            $item['can_manage'] = $this->canManageItem($item, $user);
        }
        return $items;
    }

    private function resolveUploadOwner($scope, $organizationId, array $user)
    {
        if ($scope === 'organization') {
            $allowedOrgIds = $this->getUserOrganizationIds($user);
            if ($organizationId <= 0 || !in_array($organizationId, $allowedOrgIds, true)) {
                return null;
            }
            return [
                'owner_type' => 'organization',
                'owner_user_id' => null,
                'owner_organization_id' => $organizationId,
            ];
        }

        return [
            'owner_type' => 'user',
            'owner_user_id' => (int)$user['id'],
            'owner_organization_id' => null,
        ];
    }

    private function findDriveItem($itemId)
    {
        return $this->db->fetch(
            "SELECT di.*, u.display_name AS creator_name, o.name AS owner_organization_name
             FROM drive_items di
             LEFT JOIN users u ON u.id = di.created_by
             LEFT JOIN organizations o ON o.id = di.owner_organization_id
             WHERE di.id = ? AND di.deleted_at IS NULL",
            [(int)$itemId]
        );
    }

    private function canViewItem(array $item, array $user)
    {
        if (($user['role'] ?? '') === 'admin') {
            return true;
        }
        if ((int)($item['owner_user_id'] ?? 0) === (int)$user['id']) {
            return true;
        }
        $ownerOrgId = (int)($item['owner_organization_id'] ?? 0);
        if ($ownerOrgId > 0) {
            return in_array($ownerOrgId, $this->getUserOrganizationIds($user), true);
        }

        return false;
    }

    private function canManageItem(array $item, array $user)
    {
        if (($user['role'] ?? '') === 'admin') {
            return true;
        }

        return (int)($item['created_by'] ?? 0) === (int)$user['id']
            || (int)($item['owner_user_id'] ?? 0) === (int)$user['id'];
    }

    private function streamDriveFile(array $item, $incrementCount)
    {
        $path = $this->uploadDir . (string)$item['stored_name'];
        if (!is_file($path)) {
            $_SESSION['flash_error'] = tr_text('ファイル実体が見つかりません。', 'File content not found.');
            $this->redirect($this->modulePath());
            return;
        }

        if ($incrementCount) {
            $this->db->execute(
                "UPDATE drive_items SET download_count = download_count + 1 WHERE id = ?",
                [(int)$item['id']]
            );
        }

        $mimeType = (string)($item['mime_type'] ?? '');
        if ($mimeType === '') {
            $mimeType = 'application/octet-stream';
        }
        $originalName = str_replace('"', '', (string)$item['original_name']);

        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . $originalName . '"');
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: no-cache, must-revalidate');
        @set_time_limit(0);
        readfile($path);
        exit;
    }

    private function getUserOrganizationIds(array $user)
    {
        $orgIds = [];
        $primaryOrgId = (int)($user['organization_id'] ?? 0);
        if ($primaryOrgId > 0) {
            $orgIds[$primaryOrgId] = $primaryOrgId;
        }

        $rows = $this->db->fetchAll(
            "SELECT organization_id FROM user_organizations WHERE user_id = ?",
            [(int)$user['id']]
        );
        foreach ($rows as $row) {
            $orgId = (int)($row['organization_id'] ?? 0);
            if ($orgId > 0) {
                $orgIds[$orgId] = $orgId;
            }
        }

        if (empty($orgIds)) {
            $orgIds[0] = 0;
        }
        return array_values($orgIds);
    }

    private function getSelectableOrganizations(array $user)
    {
        $orgIds = $this->getUserOrganizationIds($user);
        $placeholders = implode(',', array_fill(0, count($orgIds), '?'));

        return $this->db->fetchAll(
            "SELECT id, name FROM organizations WHERE id IN ($placeholders) ORDER BY name",
            $orgIds
        );
    }

    private function getDriveLimits()
    {
        return [
            'max_upload_mb' => max(0, (int)$this->settingModel->get('drive_max_upload_mb', '1024')),
            'storage_quota_mb' => max(0, (int)$this->settingModel->get('drive_storage_quota_mb', '51200')),
            'user_quota_mb' => max(0, (int)$this->settingModel->get('drive_user_quota_mb', '10240')),
            'org_quota_mb' => max(0, (int)$this->settingModel->get('drive_org_quota_mb', '20480')),
            'share_default_expiry_days' => max(0, (int)$this->settingModel->get('drive_share_default_expiry_days', '7')),
        ];
    }

    private function getDriveUsage(array $user)
    {
        $total = $this->db->fetch("SELECT COALESCE(SUM(file_size), 0) AS bytes FROM drive_items WHERE deleted_at IS NULL");
        $userUsage = $this->db->fetch(
            "SELECT COALESCE(SUM(file_size), 0) AS bytes FROM drive_items WHERE deleted_at IS NULL AND created_by = ?",
            [(int)$user['id']]
        );

        $orgIds = $this->getUserOrganizationIds($user);
        $orgUsage = 0;
        if (!empty($orgIds)) {
            $placeholders = implode(',', array_fill(0, count($orgIds), '?'));
            $orgRow = $this->db->fetch(
                "SELECT COALESCE(SUM(file_size), 0) AS bytes FROM drive_items WHERE deleted_at IS NULL AND owner_organization_id IN ($placeholders)",
                $orgIds
            );
            $orgUsage = (int)($orgRow['bytes'] ?? 0);
        }

        return [
            'total_bytes' => (int)($total['bytes'] ?? 0),
            'user_bytes' => (int)($userUsage['bytes'] ?? 0),
            'org_bytes' => $orgUsage,
        ];
    }

    private function validateDriveQuota($incomingBytes, array $user, $ownerOrganizationId)
    {
        $incomingBytes = (int)$incomingBytes;
        if ($incomingBytes <= 0) {
            return tr_text('ファイルサイズが不正です。', 'Invalid file size.');
        }

        $limits = $this->getDriveLimits();
        $usage = $this->getDriveUsage($user);

        $maxUpload = $this->toBytes((int)$limits['max_upload_mb']);
        if ($maxUpload > 0 && $incomingBytes > $maxUpload) {
            return tr_text('1ファイルの容量上限を超えています。', 'File exceeds max upload size.');
        }

        $totalQuota = $this->toBytes((int)$limits['storage_quota_mb']);
        if ($totalQuota > 0 && ((int)$usage['total_bytes'] + $incomingBytes) > $totalQuota) {
            return tr_text('ファイル共有全体の容量上限を超えるため保存できません。', 'Global File Sharing quota exceeded.');
        }

        $userQuota = $this->toBytes((int)$limits['user_quota_mb']);
        if ($userQuota > 0 && ((int)$usage['user_bytes'] + $incomingBytes) > $userQuota) {
            return tr_text('ユーザー容量上限を超えるため保存できません。', 'Per-user quota exceeded.');
        }

        $ownerOrganizationId = (int)$ownerOrganizationId;
        if ($ownerOrganizationId > 0) {
            $orgUsageRow = $this->db->fetch(
                "SELECT COALESCE(SUM(file_size), 0) AS bytes
                 FROM drive_items
                 WHERE deleted_at IS NULL AND owner_organization_id = ?",
                [$ownerOrganizationId]
            );
            $orgUsageBytes = (int)($orgUsageRow['bytes'] ?? 0);
            $orgQuota = $this->toBytes((int)$limits['org_quota_mb']);
            if ($orgQuota > 0 && ($orgUsageBytes + $incomingBytes) > $orgQuota) {
                return tr_text('組織容量上限を超えるため保存できません。', 'Per-organization quota exceeded.');
            }
        }

        return null;
    }

    private function toBytes($mb)
    {
        $mb = (int)$mb;
        if ($mb <= 0) {
            return 0;
        }
        return $mb * 1024 * 1024;
    }

    private function normalizeIds($values)
    {
        if (!is_array($values)) {
            $values = $values === null || $values === '' ? [] : [$values];
        }
        $unique = [];
        foreach ($values as $value) {
            $value = (int)$value;
            if ($value > 0) {
                $unique[$value] = $value;
            }
        }
        return array_values($unique);
    }

    private function parseExpiry($value)
    {
        $value = trim((string)$value);
        if ($value === '') {
            $days = (int)$this->settingModel->get('drive_share_default_expiry_days', '7');
            if ($days <= 0) {
                return '';
            }
            return date('Y-m-d H:i:s', strtotime('+' . $days . ' days'));
        }

        $timestamp = strtotime($value);
        if ($timestamp === false || $timestamp <= time()) {
            return false;
        }

        return date('Y-m-d H:i:s', $timestamp);
    }

    private function getDefaultShareExpiryInput()
    {
        $days = (int)$this->settingModel->get('drive_share_default_expiry_days', '7');
        if ($days <= 0) {
            return '';
        }
        return date('Y-m-d\TH:i', strtotime('+' . $days . ' days'));
    }

    private function generateShareToken()
    {
        for ($i = 0; $i < 10; $i++) {
            $token = bin2hex(random_bytes(24));
            $exists = $this->db->fetch("SELECT id FROM drive_share_links WHERE token = ? LIMIT 1", [$token]);
            if (!$exists) {
                return $token;
            }
        }
        throw new \RuntimeException('Failed to generate share token.');
    }

    private function insertShareLink($itemId, $createdBy, $expiresAt, $passwordHash, $maxDownloads, array $targetUserIds, array $targetOrganizationIds)
    {
        $token = $this->generateShareToken();
        $this->db->beginTransaction();
        try {
            $this->db->execute(
                "INSERT INTO drive_share_links (
                    drive_item_id, token, created_by, expires_at, password_hash, max_downloads
                ) VALUES (?, ?, ?, ?, ?, ?)",
                [
                    (int)$itemId,
                    (string)$token,
                    (int)$createdBy,
                    $expiresAt,
                    $passwordHash,
                    $maxDownloads,
                ]
            );
            $shareLinkId = (int)$this->db->lastInsertId();

            foreach ($targetUserIds as $targetUserId) {
                $this->db->execute(
                    "INSERT INTO drive_share_targets (share_link_id, target_type, target_id) VALUES (?, 'user', ?)",
                    [$shareLinkId, (int)$targetUserId]
                );
            }
            foreach ($targetOrganizationIds as $targetOrganizationId) {
                $this->db->execute(
                    "INSERT INTO drive_share_targets (share_link_id, target_type, target_id) VALUES (?, 'organization', ?)",
                    [$shareLinkId, (int)$targetOrganizationId]
                );
            }
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }

        return $token;
    }

    private function getShareLinksForItem($itemId)
    {
        $links = $this->db->fetchAll(
            "SELECT * FROM drive_share_links WHERE drive_item_id = ? ORDER BY created_at DESC",
            [(int)$itemId]
        );

        foreach ($links as &$link) {
            $targets = $this->db->fetchAll(
                "SELECT dst.target_type, dst.target_id, u.display_name AS user_name, o.name AS organization_name
                 FROM drive_share_targets dst
                 LEFT JOIN users u ON dst.target_type = 'user' AND u.id = dst.target_id
                 LEFT JOIN organizations o ON dst.target_type = 'organization' AND o.id = dst.target_id
                 WHERE dst.share_link_id = ?
                 ORDER BY dst.id ASC",
                [(int)$link['id']]
            );
            $link['target_users'] = [];
            $link['target_organizations'] = [];
            foreach ($targets as $target) {
                if ($target['target_type'] === 'user' && !empty($target['user_name'])) {
                    $link['target_users'][] = $target['user_name'];
                }
                if ($target['target_type'] === 'organization' && !empty($target['organization_name'])) {
                    $link['target_organizations'][] = $target['organization_name'];
                }
            }

            $link['has_password'] = !empty($link['password_hash']);
            $link['is_revoked'] = !empty($link['revoked_at']);
            $link['is_expired'] = !empty($link['expires_at']) && strtotime((string)$link['expires_at']) < time();
            $link['is_download_limited'] = !is_null($link['max_downloads']) && (int)$link['max_downloads'] > 0;
            $link['is_download_limit_reached'] = $link['is_download_limited'] && (int)$link['download_count'] >= (int)$link['max_downloads'];
        }

        return $links;
    }

    private function collectShareRecipients(array $targetUserIds, array $targetOrganizationIds)
    {
        $recipientIds = [];
        foreach ($targetUserIds as $userId) {
            $recipientIds[(int)$userId] = (int)$userId;
        }

        foreach ($targetOrganizationIds as $organizationId) {
            $rows = $this->db->fetchAll(
                "SELECT DISTINCT u.id
                 FROM users u
                 LEFT JOIN user_organizations uo ON uo.user_id = u.id
                 WHERE u.status = 'active'
                   AND (u.organization_id = ? OR uo.organization_id = ?)",
                [(int)$organizationId, (int)$organizationId]
            );
            foreach ($rows as $row) {
                $id = (int)($row['id'] ?? 0);
                if ($id > 0) {
                    $recipientIds[$id] = $id;
                }
            }
        }

        return array_values($recipientIds);
    }

    private function notifyShareRecipients(array $item, $token, array $targetUserIds, array $targetOrganizationIds, $expiresAt = null)
    {
        $recipients = $this->collectShareRecipients($targetUserIds, $targetOrganizationIds);
        if (empty($recipients)) {
            return;
        }

        $actor = $this->auth->user();
        $content = ($actor['display_name'] ?? tr_text('ユーザー', 'User')) . tr_text(' さんがファイル共有リンクを発行しました。', ' created a file share link.');
        $content .= "\n" . tr_text('ファイル: ', 'File: ') . ($item['title'] ?? '');
        if (!empty($expiresAt)) {
            $content .= "\n" . tr_text('有効期限: ', 'Expires at: ') . date('Y/m/d H:i', strtotime((string)$expiresAt));
        }

        foreach ($recipients as $recipientId) {
            $this->notification->create([
                'user_id' => (int)$recipientId,
                'type' => 'system',
                'title' => tr_text('ファイル共有リンク', 'File share link'),
                'content' => $content,
                'link' => '/file-share/share/' . $token,
                'reference_id' => (int)$item['id'],
                'reference_type' => 'drive_share',
            ]);
        }
    }

    private function validateShareStatus(array $share)
    {
        if (!empty($share['revoked_at'])) {
            return 'revoked';
        }
        if (!empty($share['expires_at']) && strtotime((string)$share['expires_at']) < time()) {
            return 'expired';
        }
        if (!is_null($share['max_downloads']) && (int)$share['max_downloads'] > 0 && (int)$share['download_count'] >= (int)$share['max_downloads']) {
            return 'download_limit';
        }
        return 'ok';
    }

    private function validateTargetAccess(array $targets)
    {
        if (empty($targets)) {
            return 'ok';
        }

        $user = $this->auth->user();
        if (!$user) {
            return 'login_required';
        }

        $userId = (int)$user['id'];
        $orgIds = $this->getUserOrganizationIds($user);
        foreach ($targets as $target) {
            $targetType = (string)($target['target_type'] ?? '');
            $targetId = (int)($target['target_id'] ?? 0);
            if ($targetType === 'user' && $targetId === $userId) {
                return 'ok';
            }
            if ($targetType === 'organization' && in_array($targetId, $orgIds, true)) {
                return 'ok';
            }
        }

        return 'forbidden';
    }

    private function shareStatusMessage($status)
    {
        switch ($status) {
            case 'revoked':
                return tr_text('この共有リンクは無効化されています。', 'This share link has been revoked.');
            case 'expired':
                return tr_text('この共有リンクは期限切れです。', 'This share link has expired.');
            case 'download_limit':
                return tr_text('この共有リンクはダウンロード上限に達しました。', 'This share link reached the download limit.');
            case 'login_required':
                return tr_text('このリンクはログインが必要です。', 'Sign-in is required for this link.');
            case 'forbidden':
                return tr_text('このリンクへのアクセス権限がありません。', 'You do not have access to this link.');
            default:
                return tr_text('共有リンクが見つかりません。', 'Share link not found.');
        }
    }

    private function isSharePasswordVerified($token)
    {
        return !empty($_SESSION['drive_share_verified_' . $token]);
    }

    private function markSharePasswordVerified($token)
    {
        $_SESSION['drive_share_verified_' . $token] = 1;
    }

    private function streamSharedFile(array $share)
    {
        $status = $this->validateShareStatus($share);
        if ($status !== 'ok') {
            http_response_code(410);
            $this->view('drive/shared_download', [
                'title' => tr_text('ファイル共有リンク', 'File Sharing Link'),
                'status' => $status,
                'share' => $share,
                'message' => $this->shareStatusMessage($status),
            ]);
            return;
        }

        $path = $this->uploadDir . (string)$share['stored_name'];
        if (!is_file($path)) {
            http_response_code(404);
            $this->view('drive/shared_download', [
                'title' => tr_text('ファイル共有リンク', 'File Sharing Link'),
                'status' => 'not_found',
                'share' => $share,
                'message' => tr_text('ファイル実体が見つかりません。', 'File content not found.'),
            ]);
            return;
        }

        $this->db->beginTransaction();
        try {
            $this->db->execute(
                "UPDATE drive_share_links SET download_count = download_count + 1 WHERE id = ?",
                [(int)$share['id']]
            );
            $this->db->execute(
                "UPDATE drive_items SET download_count = download_count + 1 WHERE id = ?",
                [(int)$share['drive_item_id']]
            );
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            error_log('streamSharedFile error: ' . $e->getMessage());
        }

        $mimeType = (string)($share['mime_type'] ?? '');
        if ($mimeType === '') {
            $mimeType = 'application/octet-stream';
        }
        $name = str_replace('"', '', (string)($share['original_name'] ?? 'download.bin'));

        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . $name . '"');
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: no-cache, must-revalidate');
        @set_time_limit(0);
        readfile($path);
        exit;
    }

    private function modulePath($suffix = '')
    {
        return BASE_PATH . '/file-share' . (string)$suffix;
    }
}

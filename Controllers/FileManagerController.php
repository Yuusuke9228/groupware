<?php
// Controllers/FileManagerController.php
namespace Controllers;

use Core\Controller;
use Core\Database;
use Models\Notification;
use Models\Organization;
use Models\User;
use Services\FileDiffService;
use Services\FilePermissionService;
use Services\NotificationRecipientHelper;

class FileManagerController extends Controller
{
    private $db;
    private $uploadDir;
    private $notification;
    private $userModel;
    private $organizationModel;
    private $permissionService;

    public function __construct()
    {
        parent::__construct();
        $this->db = Database::getInstance();
        $this->uploadDir = __DIR__ . '/../uploads/files/';
        $this->notification = new Notification();
        $this->userModel = new User();
        $this->organizationModel = new Organization();
        $this->permissionService = new FilePermissionService($this->db);

        if (!$this->auth->check()) {
            $this->redirect(BASE_PATH . '/login');
        }

        // アップロードディレクトリ作成
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    /**
     * ルートフォルダ一覧・ファイル一覧
     */
    public function index()
    {
        $search = $_GET['q'] ?? '';
        $currentUser = $this->auth->user();

        if ($search !== '') {
            $folders = $this->db->fetchAll(
                "SELECT ff.*, u.display_name as creator_name FROM file_folders ff LEFT JOIN users u ON ff.created_by = u.id WHERE ff.name LIKE ? ORDER BY ff.name",
                ['%' . $search . '%']
            );
            $files = $this->db->fetchAll(
                "SELECT fe.*, u.display_name as uploader_name FROM file_entries fe LEFT JOIN users u ON fe.uploaded_by = u.id WHERE fe.title LIKE ? OR fe.original_name LIKE ? ORDER BY fe.updated_at DESC",
                ['%' . $search . '%', '%' . $search . '%']
            );
        } else {
            $folders = $this->db->fetchAll(
                "SELECT ff.*, u.display_name as creator_name FROM file_folders ff LEFT JOIN users u ON ff.created_by = u.id WHERE ff.parent_id IS NULL ORDER BY ff.name"
            );
            $files = $this->db->fetchAll(
                "SELECT fe.*, u.display_name as uploader_name FROM file_entries fe LEFT JOIN users u ON fe.uploaded_by = u.id WHERE fe.folder_id IS NULL ORDER BY fe.updated_at DESC"
            );
        }

        $folders = array_values(array_filter($folders, function ($folder) use ($currentUser) {
            return $this->permissionService->canViewFolder($folder, $currentUser);
        }));
        $files = array_values(array_filter($files, function ($file) use ($currentUser) {
            return $this->permissionService->canViewFile($file, $currentUser);
        }));

        // フォルダ内のファイル数を取得
        foreach ($folders as &$folder) {
            $folder['file_count'] = $this->countFilesInFolder($folder['id']);
            $folder['subfolder_count'] = $this->db->fetch(
                "SELECT COUNT(*) as cnt FROM file_folders WHERE parent_id = ?",
                [$folder['id']]
            )['cnt'];
        }

        $this->view('file_manager/index', [
            'title' => 'ファイル管理',
            'folders' => $folders,
            'files' => $files,
            'breadcrumbs' => [],
            'currentFolder' => null,
            'search' => $search,
            'recentActivities' => $this->getRecentActivities(null),
            'canManageCurrentFolder' => true,
        ]);
    }

    /**
     * フォルダ内容表示
     */
    public function folder($params)
    {
        $folderId = (int) $params['id'];
        $folder = $this->db->fetch("SELECT * FROM file_folders WHERE id = ?", [$folderId]);
        $currentUser = $this->auth->user();

        if (!$folder) {
            $_SESSION['flash_error'] = 'フォルダが見つかりません。';
            $this->redirect(BASE_PATH . '/files');
            return;
        }

        if (!$this->permissionService->canViewFolder($folder, $currentUser)) {
            $_SESSION['flash_error'] = 'フォルダの閲覧権限がありません。';
            $this->redirect(BASE_PATH . '/files');
            return;
        }

        $subfolders = $this->db->fetchAll(
            "SELECT ff.*, u.display_name as creator_name FROM file_folders ff LEFT JOIN users u ON ff.created_by = u.id WHERE ff.parent_id = ? ORDER BY ff.name",
            [$folderId]
        );

        $files = $this->db->fetchAll(
            "SELECT fe.*, u.display_name as uploader_name FROM file_entries fe LEFT JOIN users u ON fe.uploaded_by = u.id WHERE fe.folder_id = ? ORDER BY fe.updated_at DESC",
            [$folderId]
        );

        $subfolders = array_values(array_filter($subfolders, function ($item) use ($currentUser) {
            return $this->permissionService->canViewFolder($item, $currentUser);
        }));
        $files = array_values(array_filter($files, function ($item) use ($currentUser) {
            return $this->permissionService->canViewFile($item, $currentUser);
        }));

        foreach ($subfolders as &$sf) {
            $sf['file_count'] = $this->countFilesInFolder($sf['id']);
            $sf['subfolder_count'] = $this->db->fetch(
                "SELECT COUNT(*) as cnt FROM file_folders WHERE parent_id = ?",
                [$sf['id']]
            )['cnt'];
        }

        $breadcrumbs = $this->buildBreadcrumbs($folderId);

        $this->view('file_manager/index', [
            'title' => htmlspecialchars($folder['name']) . ' - ファイル管理',
            'folders' => $subfolders,
            'files' => $files,
            'breadcrumbs' => $breadcrumbs,
            'currentFolder' => $folder,
            'search' => '',
            'recentActivities' => $this->getRecentActivities($folderId),
            'canManageCurrentFolder' => $this->permissionService->canEditFolder($folder, $currentUser),
        ]);
    }

    /**
     * フォルダ作成フォーム
     */
    public function createFolder()
    {
        $parentId = $_GET['parent_id'] ?? null;
        $parentFolder = null;
        $breadcrumbs = [];
        $currentUser = $this->auth->user();

        if ($parentId) {
            $parentFolder = $this->db->fetch("SELECT * FROM file_folders WHERE id = ?", [(int)$parentId]);
            if ($parentFolder) {
                if (!$this->permissionService->canEditFolder($parentFolder, $currentUser)) {
                    $_SESSION['flash_error'] = '親フォルダへの編集権限がありません。';
                    $this->redirect(BASE_PATH . '/files');
                    return;
                }
                $breadcrumbs = $this->buildBreadcrumbs((int)$parentId);
            }
        }

        $this->view('file_manager/folder_form', [
            'title' => '新規フォルダ作成 - ファイル管理',
            'folder' => null,
            'parentFolder' => $parentFolder,
            'breadcrumbs' => $breadcrumbs,
            'csrf_token' => $this->generateCsrfToken(),
            'organizations' => $this->organizationModel->getAll(),
            'users' => $this->userModel->getActiveUsers(),
            'permissionSummary' => [
                'view' => ['organizations' => [], 'users' => []],
                'edit' => ['organizations' => [], 'users' => []],
                'approve' => ['organizations' => [], 'users' => []],
                'admin' => ['organizations' => [], 'users' => []],
            ],
        ]);
    }

    /**
     * フォルダ保存
     */
    public function storeFolder()
    {
        if (!$this->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = '不正なリクエストです。';
            $this->redirect(BASE_PATH . '/files');
            return;
        }

        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $parentId = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
        $currentUser = $this->auth->user();

        if ($name === '') {
            $_SESSION['flash_error'] = 'フォルダ名を入力してください。';
            $this->redirect(BASE_PATH . '/files/folder/create' . ($parentId ? '?parent_id=' . $parentId : ''));
            return;
        }

        if ($parentId) {
            $parentFolder = $this->db->fetch("SELECT * FROM file_folders WHERE id = ?", [$parentId]);
            if ($parentFolder && !$this->permissionService->canEditFolder($parentFolder, $currentUser)) {
                $_SESSION['flash_error'] = '親フォルダへの編集権限がありません。';
                $this->redirect(BASE_PATH . '/files');
                return;
            }
        }

        $this->db->execute(
            "INSERT INTO file_folders (name, parent_id, description, created_by) VALUES (?, ?, ?, ?)",
            [$name, $parentId, $description, $this->auth->id()]
        );
        $folderId = (int)$this->db->lastInsertId();
        $permissionMap = $this->permissionService->extractPermissionMapFromRequest($_POST);
        $this->permissionService->replacePermissions('folder', $folderId, $permissionMap, $this->auth->id());

        $_SESSION['flash_success'] = 'フォルダを作成しました。';

        if ($parentId) {
            $this->redirect(BASE_PATH . '/files/folder/' . $parentId);
        } else {
            $this->redirect(BASE_PATH . '/files');
        }
    }

    /**
     * フォルダ編集フォーム
     */
    public function editFolder($params)
    {
        $folderId = (int) $params['id'];
        $folder = $this->db->fetch("SELECT * FROM file_folders WHERE id = ?", [$folderId]);
        $currentUser = $this->auth->user();

        if (!$folder) {
            $_SESSION['flash_error'] = 'フォルダが見つかりません。';
            $this->redirect(BASE_PATH . '/files');
            return;
        }

        if (!$this->permissionService->canEditFolder($folder, $currentUser)) {
            $_SESSION['flash_error'] = 'フォルダの編集権限がありません。';
            $this->redirect(BASE_PATH . '/files');
            return;
        }

        $breadcrumbs = $this->buildBreadcrumbs($folderId);

        $this->view('file_manager/folder_form', [
            'title' => 'フォルダ編集 - ファイル管理',
            'folder' => $folder,
            'parentFolder' => $folder['parent_id'] ? $this->db->fetch("SELECT * FROM file_folders WHERE id = ?", [$folder['parent_id']]) : null,
            'breadcrumbs' => $breadcrumbs,
            'csrf_token' => $this->generateCsrfToken(),
            'organizations' => $this->organizationModel->getAll(),
            'users' => $this->userModel->getActiveUsers(),
            'permissionSummary' => $this->permissionService->getPermissionSummary('folder', $folderId),
        ]);
    }

    /**
     * フォルダ更新
     */
    public function updateFolder($params)
    {
        $folderId = (int) $params['id'];

        if (!$this->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = '不正なリクエストです。';
            $this->redirect(BASE_PATH . '/files');
            return;
        }

        $folder = $this->db->fetch("SELECT * FROM file_folders WHERE id = ?", [$folderId]);
        $currentUser = $this->auth->user();
        if (!$folder) {
            $_SESSION['flash_error'] = 'フォルダが見つかりません。';
            $this->redirect(BASE_PATH . '/files');
            return;
        }

        if (!$this->permissionService->canEditFolder($folder, $currentUser)) {
            $_SESSION['flash_error'] = 'フォルダの編集権限がありません。';
            $this->redirect(BASE_PATH . '/files');
            return;
        }

        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($name === '') {
            $_SESSION['flash_error'] = 'フォルダ名を入力してください。';
            $this->redirect(BASE_PATH . '/files/folder/' . $folderId . '/edit');
            return;
        }

        $this->db->execute(
            "UPDATE file_folders SET name = ?, description = ? WHERE id = ?",
            [$name, $description, $folderId]
        );
        $permissionMap = $this->permissionService->extractPermissionMapFromRequest($_POST);
        $this->permissionService->replacePermissions('folder', $folderId, $permissionMap, $this->auth->id());

        $_SESSION['flash_success'] = 'フォルダを更新しました。';

        if ($folder['parent_id']) {
            $this->redirect(BASE_PATH . '/files/folder/' . $folder['parent_id']);
        } else {
            $this->redirect(BASE_PATH . '/files');
        }
    }

    /**
     * フォルダ削除
     */
    public function deleteFolder($params)
    {
        $folderId = (int) $params['id'];

        if (!$this->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = '不正なリクエストです。';
            $this->redirect(BASE_PATH . '/files');
            return;
        }

        $folder = $this->db->fetch("SELECT * FROM file_folders WHERE id = ?", [$folderId]);
        $currentUser = $this->auth->user();
        if (!$folder) {
            $_SESSION['flash_error'] = 'フォルダが見つかりません。';
            $this->redirect(BASE_PATH . '/files');
            return;
        }

        if (!$this->permissionService->canAdminFolder($folder, $currentUser)) {
            $_SESSION['flash_error'] = 'フォルダの削除権限がありません。';
            $this->redirect(BASE_PATH . '/files');
            return;
        }

        // フォルダ内のファイルを再帰的に削除
        $this->deleteFolderRecursive($folderId);

        $_SESSION['flash_success'] = 'フォルダを削除しました。';

        if ($folder['parent_id']) {
            $this->redirect(BASE_PATH . '/files/folder/' . $folder['parent_id']);
        } else {
            $this->redirect(BASE_PATH . '/files');
        }
    }

    /**
     * アップロードフォーム
     */
    public function upload()
    {
        $folderId = $_GET['folder_id'] ?? null;
        $folder = null;
        $breadcrumbs = [];
        $currentUser = $this->auth->user();

        if ($folderId) {
            $folder = $this->db->fetch("SELECT * FROM file_folders WHERE id = ?", [(int)$folderId]);
            if ($folder) {
                if (!$this->permissionService->canEditFolder($folder, $currentUser)) {
                    $_SESSION['flash_error'] = 'フォルダへのアップロード権限がありません。';
                    $this->redirect(BASE_PATH . '/files');
                    return;
                }
                $breadcrumbs = $this->buildBreadcrumbs((int)$folderId);
            }
        }

        // フォルダ一覧（セレクトボックス用）
        $allFolders = $this->getAllFoldersFlat();

        $this->view('file_manager/upload', [
            'title' => 'ファイルアップロード - ファイル管理',
            'folder' => $folder,
            'allFolders' => $allFolders,
            'breadcrumbs' => $breadcrumbs,
            'csrf_token' => $this->generateCsrfToken(),
            'organizations' => $this->organizationModel->getAll(),
            'users' => $this->userModel->getActiveUsers(),
        ]);
    }

    /**
     * ファイルアップロード処理
     */
    public function storeFile()
    {
        if (!$this->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = '不正なリクエストです。';
            $this->redirect(BASE_PATH . '/files');
            return;
        }

        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['flash_error'] = 'ファイルのアップロードに失敗しました。';
            $this->redirect(BASE_PATH . '/files/upload');
            return;
        }

        $file = $_FILES['file'];
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $folderId = !empty($_POST['folder_id']) ? (int)$_POST['folder_id'] : null;
        $currentUser = $this->auth->user();

        if ($title === '') {
            $title = pathinfo($file['name'], PATHINFO_FILENAME);
        }

        if ($folderId) {
            $folder = $this->db->fetch("SELECT * FROM file_folders WHERE id = ?", [$folderId]);
            if ($folder && !$this->permissionService->canEditFolder($folder, $currentUser)) {
                $_SESSION['flash_error'] = 'フォルダへのアップロード権限がありません。';
                $this->redirect(BASE_PATH . '/files');
                return;
            }
        }

        $originalName = $file['name'];
        $mimeType = $file['type'];
        $fileSize = $file['size'];

        // ユニークファイル名生成
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $storedName = uniqid('file_', true) . ($extension ? '.' . $extension : '');

        if (!move_uploaded_file($file['tmp_name'], $this->uploadDir . $storedName)) {
            $_SESSION['flash_error'] = 'ファイルの保存に失敗しました。';
            $this->redirect(BASE_PATH . '/files/upload');
            return;
        }

        // DBに保存
        $this->db->execute(
            "INSERT INTO file_entries (folder_id, title, description, filename, original_name, file_size, mime_type, version, uploaded_by) VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?)",
            [$folderId, $title, $description, $storedName, $originalName, $fileSize, $mimeType, $this->auth->id()]
        );
        $fileId = (int)$this->db->lastInsertId();

        // 初回バージョンを記録
        $this->db->execute(
            "INSERT INTO file_versions (file_id, version_number, filename, original_name, file_size, mime_type, uploaded_by, comment) VALUES (?, 1, ?, ?, ?, ?, ?, ?)",
            [$fileId, $storedName, $originalName, $fileSize, $mimeType, $this->auth->id(), '初回アップロード']
        );
        $versionId = (int)$this->db->lastInsertId();

        $permissionMap = $this->permissionService->extractPermissionMapFromRequest($_POST);
        $this->permissionService->replacePermissions('file', $fileId, $permissionMap, $this->auth->id());
        $this->handleApprovalSubmission($fileId, $versionId, $_POST);

        $this->notifyFileActivity('uploaded', $fileId, [
            'title' => $title,
            'folder_id' => $folderId,
            'original_name' => $originalName,
            'version' => 1
        ]);

        $_SESSION['flash_success'] = 'ファイルをアップロードしました。';

        if ($folderId) {
            $this->redirect(BASE_PATH . '/files/folder/' . $folderId);
        } else {
            $this->redirect(BASE_PATH . '/files');
        }
    }

    /**
     * ファイル詳細表示
     */
    public function showFile($params)
    {
        $fileId = (int) $params['id'];
        $currentUser = $this->auth->user();
        $file = $this->db->fetch(
            "SELECT fe.*, u.display_name as uploader_name, cu.display_name as checked_out_user_name
             FROM file_entries fe
             LEFT JOIN users u ON fe.uploaded_by = u.id
             LEFT JOIN users cu ON cu.id = fe.checked_out_by
             WHERE fe.id = ?",
            [$fileId]
        );

        if (!$file) {
            $_SESSION['flash_error'] = 'ファイルが見つかりません。';
            $this->redirect(BASE_PATH . '/files');
            return;
        }

        if (!$this->permissionService->canViewFile($file, $currentUser)) {
            $_SESSION['flash_error'] = 'ファイルの閲覧権限がありません。';
            $this->redirect(BASE_PATH . '/files');
            return;
        }

        $versions = $this->db->fetchAll(
            "SELECT fv.*, u.display_name as uploader_name FROM file_versions fv LEFT JOIN users u ON fv.uploaded_by = u.id WHERE fv.file_id = ? ORDER BY fv.version_number DESC",
            [$fileId]
        );

        $breadcrumbs = [];
        if ($file['folder_id']) {
            $breadcrumbs = $this->buildBreadcrumbs($file['folder_id']);
        }

        $approvalRequests = $this->getApprovalRequests($fileId);
        $activeApprovalRequest = $approvalRequests[0] ?? null;
        $comparison = $this->buildVersionComparison($versions);

        $this->view('file_manager/show', [
            'title' => htmlspecialchars($file['title']) . ' - ファイル管理',
            'file' => $file,
            'versions' => $versions,
            'breadcrumbs' => $breadcrumbs,
            'csrf_token' => $this->generateCsrfToken(),
            'organizations' => $this->organizationModel->getAll(),
            'users' => $this->userModel->getActiveUsers(),
            'permissionSummary' => $this->permissionService->getPermissionSummary('file', $fileId),
            'canEditFile' => $this->permissionService->canEditFile($file, $currentUser),
            'canApproveFile' => $this->permissionService->canApproveFile($file, $currentUser),
            'canAdminFile' => $this->permissionService->canAdminFile($file, $currentUser),
            'canReleaseCheckout' => (int)($file['checked_out_by'] ?? 0) === (int)$this->auth->id() || $this->permissionService->canAdminFile($file, $currentUser),
            'approvalRequests' => $approvalRequests,
            'activeApprovalRequest' => $activeApprovalRequest,
            'comparison' => $comparison,
        ]);
    }

    /**
     * ファイルダウンロード
     */
    public function download($params)
    {
        $fileId = (int) $params['id'];
        $versionId = $_GET['version'] ?? null;
        $file = $this->db->fetch("SELECT * FROM file_entries WHERE id = ?", [$fileId]);

        if (!$file) {
            $_SESSION['flash_error'] = 'ファイルが見つかりません。';
            $this->redirect(BASE_PATH . '/files');
            return;
        }

        if (!$this->permissionService->canViewFile($file, $this->auth->user())) {
            $_SESSION['flash_error'] = 'ファイルの閲覧権限がありません。';
            $this->redirect(BASE_PATH . '/files');
            return;
        }

        if ($versionId) {
            $version = $this->db->fetch(
                "SELECT * FROM file_versions WHERE id = ? AND file_id = ?",
                [(int)$versionId, $fileId]
            );
            if (!$version) {
                $_SESSION['flash_error'] = 'バージョンが見つかりません。';
                $this->redirect(BASE_PATH . '/files');
                return;
            }
            $filename = $version['filename'];
            $originalName = $version['original_name'];
            $mimeType = $version['mime_type'];
        } else {
            if (!$this->permissionService->canViewFile($file, $this->auth->user())) {
                $_SESSION['flash_error'] = 'ファイルの閲覧権限がありません。';
                $this->redirect(BASE_PATH . '/files');
                return;
            }
            $filename = $file['filename'];
            $originalName = $file['original_name'];
            $mimeType = $file['mime_type'];
        }

        $filePath = $this->uploadDir . $filename;

        if (!file_exists($filePath)) {
            $_SESSION['flash_error'] = 'ファイルが見つかりません。';
            $this->redirect(BASE_PATH . '/files');
            return;
        }

        // ダウンロード数増加
        $this->db->execute("UPDATE file_entries SET download_count = download_count + 1 WHERE id = ?", [$fileId]);

        // ファイルを送信
        header('Content-Type: ' . ($mimeType ?: 'application/octet-stream'));
        header('Content-Disposition: attachment; filename="' . $originalName . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: no-cache, must-revalidate');
        readfile($filePath);
        exit;
    }

    /**
     * ファイル更新（新バージョンアップロード）
     */
    public function updateFile($params)
    {
        $fileId = (int) $params['id'];

        if (!$this->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = '不正なリクエストです。';
            $this->redirect(BASE_PATH . '/files/file/' . $fileId);
            return;
        }

        $file = $this->db->fetch("SELECT * FROM file_entries WHERE id = ?", [$fileId]);
        if (!$file) {
            $_SESSION['flash_error'] = 'ファイルが見つかりません。';
            $this->redirect(BASE_PATH . '/files');
            return;
        }

        if (!$this->permissionService->canEditFile($file, $this->auth->user())) {
            $_SESSION['flash_error'] = 'ファイルの編集権限がありません。';
            $this->redirect(BASE_PATH . '/files/file/' . $fileId);
            return;
        }

        if (!empty($file['checked_out_by']) && (int)$file['checked_out_by'] !== (int)$this->auth->id()) {
            $_SESSION['flash_error'] = 'このファイルは現在チェックアウト中です。';
            $this->redirect(BASE_PATH . '/files/file/' . $fileId);
            return;
        }

        if (($file['approval_status'] ?? 'none') === 'pending') {
            $_SESSION['flash_error'] = '承認中のファイルは更新できません。';
            $this->redirect(BASE_PATH . '/files/file/' . $fileId);
            return;
        }

        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['flash_error'] = 'ファイルのアップロードに失敗しました。';
            $this->redirect(BASE_PATH . '/files/file/' . $fileId);
            return;
        }

        $uploadedFile = $_FILES['file'];
        $comment = trim($_POST['comment'] ?? '');
        $originalName = $uploadedFile['name'];
        $mimeType = $uploadedFile['type'];
        $fileSize = $uploadedFile['size'];

        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $storedName = uniqid('file_', true) . ($extension ? '.' . $extension : '');

        if (!move_uploaded_file($uploadedFile['tmp_name'], $this->uploadDir . $storedName)) {
            $_SESSION['flash_error'] = 'ファイルの保存に失敗しました。';
            $this->redirect(BASE_PATH . '/files/file/' . $fileId);
            return;
        }

        $newVersion = $file['version'] + 1;

        // file_entries更新
        $this->db->execute(
            "UPDATE file_entries SET filename = ?, original_name = ?, file_size = ?, mime_type = ?, version = ?, updated_at = NOW() WHERE id = ?",
            [$storedName, $originalName, $fileSize, $mimeType, $newVersion, $fileId]
        );

        // バージョン記録
        $this->db->execute(
            "INSERT INTO file_versions (file_id, version_number, filename, original_name, file_size, mime_type, uploaded_by, comment) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [$fileId, $newVersion, $storedName, $originalName, $fileSize, $mimeType, $this->auth->id(), $comment]
        );
        $versionId = (int)$this->db->lastInsertId();
        $this->handleApprovalSubmission($fileId, $versionId, $_POST);

        $this->notifyFileActivity('updated', $fileId, [
            'title' => $file['title'],
            'folder_id' => $file['folder_id'],
            'original_name' => $originalName,
            'version' => $newVersion,
            'comment' => $comment
        ]);

        $_SESSION['flash_success'] = 'ファイルを更新しました（バージョン ' . $newVersion . '）。';
        $this->redirect(BASE_PATH . '/files/file/' . $fileId);
    }

    /**
     * ファイル削除
     */
    public function deleteFile($params)
    {
        $fileId = (int) $params['id'];

        if (!$this->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = '不正なリクエストです。';
            $this->redirect(BASE_PATH . '/files');
            return;
        }

        $file = $this->db->fetch("SELECT * FROM file_entries WHERE id = ?", [$fileId]);
        if (!$file) {
            $_SESSION['flash_error'] = 'ファイルが見つかりません。';
            $this->redirect(BASE_PATH . '/files');
            return;
        }

        if (!$this->permissionService->canAdminFile($file, $this->auth->user())) {
            $_SESSION['flash_error'] = 'ファイルの削除権限がありません。';
            $this->redirect(BASE_PATH . '/files/file/' . $fileId);
            return;
        }

        // 全バージョンの物理ファイル削除
        $versions = $this->db->fetchAll("SELECT filename FROM file_versions WHERE file_id = ?", [$fileId]);
        foreach ($versions as $v) {
            $path = $this->uploadDir . $v['filename'];
            if (file_exists($path)) {
                unlink($path);
            }
        }

        // 現行ファイルも削除
        $currentPath = $this->uploadDir . $file['filename'];
        if (file_exists($currentPath)) {
            unlink($currentPath);
        }

        // DB削除（バージョンはCASCADEで削除される）
        $this->db->execute("DELETE FROM file_entries WHERE id = ?", [$fileId]);

        $_SESSION['flash_success'] = 'ファイルを削除しました。';

        if ($file['folder_id']) {
            $this->redirect(BASE_PATH . '/files/folder/' . $file['folder_id']);
        } else {
            $this->redirect(BASE_PATH . '/files');
        }
    }

    public function updatePermissions($params)
    {
        $fileId = (int)$params['id'];

        if (!$this->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = '不正なリクエストです。';
            $this->redirect(BASE_PATH . '/files/file/' . $fileId);
            return;
        }

        $file = $this->db->fetch("SELECT * FROM file_entries WHERE id = ?", [$fileId]);
        if (!$file || !$this->permissionService->canAdminFile($file, $this->auth->user())) {
            $_SESSION['flash_error'] = '権限設定を変更できません。';
            $this->redirect(BASE_PATH . '/files/file/' . $fileId);
            return;
        }

        $permissionMap = $this->permissionService->extractPermissionMapFromRequest($_POST);
        $this->permissionService->replacePermissions('file', $fileId, $permissionMap, $this->auth->id());

        $_SESSION['flash_success'] = 'ファイル権限を更新しました。';
        $this->redirect(BASE_PATH . '/files/file/' . $fileId);
    }

    public function checkoutFile($params)
    {
        $fileId = (int)$params['id'];
        if (!$this->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = '不正なリクエストです。';
            $this->redirect(BASE_PATH . '/files/file/' . $fileId);
            return;
        }
        $file = $this->db->fetch("SELECT * FROM file_entries WHERE id = ?", [$fileId]);

        if (!$file || !$this->permissionService->canEditFile($file, $this->auth->user())) {
            $_SESSION['flash_error'] = 'チェックアウト権限がありません。';
            $this->redirect(BASE_PATH . '/files');
            return;
        }

        if (!empty($file['checked_out_by']) && (int)$file['checked_out_by'] !== (int)$this->auth->id()) {
            $_SESSION['flash_error'] = '別ユーザーがチェックアウト中です。';
            $this->redirect(BASE_PATH . '/files/file/' . $fileId);
            return;
        }

        $this->db->execute(
            "UPDATE file_entries SET checked_out_by = ?, checked_out_at = NOW() WHERE id = ?",
            [$this->auth->id(), $fileId]
        );
        $this->db->execute(
            "INSERT INTO file_checkout_history (file_id, user_id, status, note) VALUES (?, ?, 'checked_out', ?)",
            [$fileId, $this->auth->id(), trim((string)($_POST['note'] ?? '')) ?: null]
        );

        $_SESSION['flash_success'] = 'ファイルをチェックアウトしました。';
        $this->redirect(BASE_PATH . '/files/file/' . $fileId);
    }

    public function releaseCheckout($params)
    {
        $fileId = (int)$params['id'];
        if (!$this->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = '不正なリクエストです。';
            $this->redirect(BASE_PATH . '/files/file/' . $fileId);
            return;
        }
        $file = $this->db->fetch("SELECT * FROM file_entries WHERE id = ?", [$fileId]);

        if (!$file) {
            $_SESSION['flash_error'] = 'ファイルが見つかりません。';
            $this->redirect(BASE_PATH . '/files');
            return;
        }

        $ownsCheckout = (int)($file['checked_out_by'] ?? 0) === (int)$this->auth->id();
        $canAdmin = $this->permissionService->canAdminFile($file, $this->auth->user());
        if (!$ownsCheckout && !$canAdmin) {
            $_SESSION['flash_error'] = 'チェックアウト解除権限がありません。';
            $this->redirect(BASE_PATH . '/files/file/' . $fileId);
            return;
        }

        $this->db->execute(
            "UPDATE file_entries SET checked_out_by = NULL, checked_out_at = NULL WHERE id = ?",
            [$fileId]
        );
        $this->db->execute(
            "INSERT INTO file_checkout_history (file_id, user_id, status, note) VALUES (?, ?, 'released', ?)",
            [$fileId, $this->auth->id(), trim((string)($_POST['note'] ?? '')) ?: null]
        );

        $_SESSION['flash_success'] = 'チェックアウトを解除しました。';
        $this->redirect(BASE_PATH . '/files/file/' . $fileId);
    }

    public function requestApproval($params)
    {
        $fileId = (int)$params['id'];
        if (!$this->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = '不正なリクエストです。';
            $this->redirect(BASE_PATH . '/files/file/' . $fileId);
            return;
        }
        $file = $this->db->fetch("SELECT * FROM file_entries WHERE id = ?", [$fileId]);

        if (!$file || !$this->permissionService->canEditFile($file, $this->auth->user())) {
            $_SESSION['flash_error'] = '承認申請権限がありません。';
            $this->redirect(BASE_PATH . '/files');
            return;
        }

        $latestVersion = $this->db->fetch(
            "SELECT id FROM file_versions WHERE file_id = ? ORDER BY version_number DESC LIMIT 1",
            [$fileId]
        );

        if (!$latestVersion) {
            $_SESSION['flash_error'] = '承認対象のバージョンが見つかりません。';
            $this->redirect(BASE_PATH . '/files/file/' . $fileId);
            return;
        }

        if ($this->hasPendingApprovalRequest($fileId)) {
            $_SESSION['flash_error'] = 'このファイルはすでに承認中です。';
            $this->redirect(BASE_PATH . '/files/file/' . $fileId);
            return;
        }

        $created = $this->createApprovalRequest(
            $fileId,
            (int)$latestVersion['id'],
            $this->permissionService->normalizeIds($_POST['approval_user_ids'] ?? []),
            trim((string)($_POST['approval_comment'] ?? ''))
        );

        $_SESSION[$created ? 'flash_success' : 'flash_error'] = $created ? '承認申請を作成しました。' : '承認申請の作成に失敗しました。';
        $this->redirect(BASE_PATH . '/files/file/' . $fileId);
    }

    public function approveRequest($params)
    {
        $requestId = (int)$params['request_id'];
        if (!$this->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = '不正なリクエストです。';
            $this->redirect(BASE_PATH . '/files');
            return;
        }
        $request = $this->db->fetch("SELECT * FROM file_approval_requests WHERE id = ? LIMIT 1", [$requestId]);
        if (!$request) {
            $_SESSION['flash_error'] = '承認依頼が見つかりません。';
            $this->redirect(BASE_PATH . '/files');
            return;
        }

        $this->handleApprovalDecision($request, 'approved', trim((string)($_POST['comment'] ?? '')));
        $this->redirect(BASE_PATH . '/files/file/' . (int)$request['file_id']);
    }

    public function rejectRequest($params)
    {
        $requestId = (int)$params['request_id'];
        if (!$this->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = '不正なリクエストです。';
            $this->redirect(BASE_PATH . '/files');
            return;
        }
        $request = $this->db->fetch("SELECT * FROM file_approval_requests WHERE id = ? LIMIT 1", [$requestId]);
        if (!$request) {
            $_SESSION['flash_error'] = '承認依頼が見つかりません。';
            $this->redirect(BASE_PATH . '/files');
            return;
        }

        $this->handleApprovalDecision($request, 'rejected', trim((string)($_POST['comment'] ?? '')));
        $this->redirect(BASE_PATH . '/files/file/' . (int)$request['file_id']);
    }

    // ========== ヘルパーメソッド ==========

    private function buildVersionComparison(array $versions)
    {
        $leftId = (int)($_GET['compare_from'] ?? 0);
        $rightId = (int)($_GET['compare_to'] ?? 0);

        if ($leftId <= 0 || $rightId <= 0 || $leftId === $rightId) {
            return null;
        }

        $byId = [];
        foreach ($versions as $version) {
            $byId[(int)$version['id']] = $version;
        }

        if (empty($byId[$leftId]) || empty($byId[$rightId])) {
            return null;
        }

        $left = $byId[$leftId];
        $right = $byId[$rightId];
        $result = FileDiffService::compareVersions(
            $this->uploadDir . $left['filename'],
            $this->uploadDir . $right['filename'],
            $left['original_name'],
            $right['original_name'],
            $left['mime_type'] ?? '',
            $right['mime_type'] ?? ''
        );
        $result['left'] = $left;
        $result['right'] = $right;
        return $result;
    }

    private function getApprovalRequests($fileId)
    {
        $requests = $this->db->fetchAll(
            "SELECT far.*, u.display_name AS requester_name, fv.version_number
             FROM file_approval_requests far
             LEFT JOIN users u ON u.id = far.requested_by
             LEFT JOIN file_versions fv ON fv.id = far.version_id
             WHERE far.file_id = ?
             ORDER BY far.created_at DESC",
            [(int)$fileId]
        );

        foreach ($requests as &$request) {
            $request['steps'] = $this->db->fetchAll(
                "SELECT fas.*, u.display_name AS approver_name
                 FROM file_approval_steps fas
                 LEFT JOIN users u ON u.id = fas.approver_id
                 WHERE fas.request_id = ?
                 ORDER BY fas.step_order ASC",
                [(int)$request['id']]
            );
        }

        return $requests;
    }

    private function handleApprovalSubmission($fileId, $versionId, array $data)
    {
        $approverIds = $this->permissionService->normalizeIds($data['approval_user_ids'] ?? []);
        if (empty($approverIds) && empty($data['require_approval'])) {
            $this->db->execute(
                "UPDATE file_entries SET approval_status = 'none' WHERE id = ?",
                [(int)$fileId]
            );
            return;
        }

        $this->createApprovalRequest($fileId, $versionId, $approverIds, trim((string)($data['approval_comment'] ?? '')));
    }

    private function createApprovalRequest($fileId, $versionId, array $approverIds, $comment = '')
    {
        if (empty($approverIds)) {
            return false;
        }

        if ($this->hasPendingApprovalRequest($fileId)) {
            return false;
        }

        $this->db->beginTransaction();
        try {
            $this->db->execute(
                "INSERT INTO file_approval_requests (file_id, version_id, requested_by, request_comment) VALUES (?, ?, ?, ?)",
                [(int)$fileId, (int)$versionId, (int)$this->auth->id(), $comment !== '' ? $comment : null]
            );
            $requestId = (int)$this->db->lastInsertId();

            $order = 1;
            foreach ($approverIds as $approverId) {
                $this->db->execute(
                    "INSERT INTO file_approval_steps (request_id, approver_id, step_order) VALUES (?, ?, ?)",
                    [$requestId, (int)$approverId, $order++]
                );
            }

            $this->db->execute(
                "UPDATE file_entries SET approval_status = 'pending' WHERE id = ?",
                [(int)$fileId]
            );

            $this->db->commit();
            $this->notifyApprovalRequested($fileId, $requestId, $approverIds);
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log('createApprovalRequest error: ' . $e->getMessage());
            return false;
        }
    }

    private function hasPendingApprovalRequest($fileId)
    {
        $row = $this->db->fetch(
            "SELECT id FROM file_approval_requests WHERE file_id = ? AND status = 'pending' LIMIT 1",
            [(int)$fileId]
        );
        return !empty($row);
    }

    private function handleApprovalDecision(array $request, $decision, $comment)
    {
        $allowedStatuses = ['approved', 'rejected'];
        if (!in_array($decision, $allowedStatuses, true)) {
            $_SESSION['flash_error'] = '不正な承認操作です。';
            return;
        }

        $step = $this->db->fetch(
            "SELECT * FROM file_approval_steps
             WHERE request_id = ? AND approver_id = ? AND status = 'pending'
             ORDER BY step_order ASC
             LIMIT 1",
            [(int)$request['id'], (int)$this->auth->id()]
        );

        if (!$step) {
            $_SESSION['flash_error'] = '承認権限がありません。';
            return;
        }

        $firstPending = $this->db->fetch(
            "SELECT id FROM file_approval_steps
             WHERE request_id = ? AND status = 'pending'
             ORDER BY step_order ASC
             LIMIT 1",
            [(int)$request['id']]
        );

        if ((int)($firstPending['id'] ?? 0) !== (int)$step['id']) {
            $_SESSION['flash_error'] = '前段の承認が完了していません。';
            return;
        }

        $this->db->beginTransaction();
        try {
            $this->db->execute(
                "UPDATE file_approval_steps
                 SET status = ?, comment = ?, acted_at = NOW()
                 WHERE id = ?",
                [$decision, $comment !== '' ? $comment : null, (int)$step['id']]
            );

            if ($decision === 'rejected') {
                $this->db->execute(
                    "UPDATE file_approval_requests SET status = 'rejected', completed_at = NOW() WHERE id = ?",
                    [(int)$request['id']]
                );
                $this->db->execute(
                    "UPDATE file_entries SET approval_status = 'rejected' WHERE id = ?",
                    [(int)$request['file_id']]
                );
            } else {
                $remaining = $this->db->fetch(
                    "SELECT COUNT(*) AS cnt FROM file_approval_steps WHERE request_id = ? AND status = 'pending'",
                    [(int)$request['id']]
                );
                if ((int)($remaining['cnt'] ?? 0) === 0) {
                    $this->db->execute(
                        "UPDATE file_approval_requests SET status = 'approved', completed_at = NOW() WHERE id = ?",
                        [(int)$request['id']]
                    );
                    $this->db->execute(
                        "UPDATE file_entries SET approval_status = 'approved' WHERE id = ?",
                        [(int)$request['file_id']]
                    );
                }
            }

            $this->db->commit();
            $this->notifyApprovalDecision($request, $decision, $comment);
            $_SESSION['flash_success'] = $decision === 'approved' ? '承認しました。' : '差し戻しました。';
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log('handleApprovalDecision error: ' . $e->getMessage());
            $_SESSION['flash_error'] = '承認処理に失敗しました。';
        }
    }

    /**
     * パンくずリスト構築
     */
    private function buildBreadcrumbs($folderId)
    {
        $breadcrumbs = [];
        $currentId = $folderId;

        while ($currentId) {
            $folder = $this->db->fetch("SELECT id, name, parent_id FROM file_folders WHERE id = ?", [$currentId]);
            if (!$folder) break;
            array_unshift($breadcrumbs, $folder);
            $currentId = $folder['parent_id'];
        }

        return $breadcrumbs;
    }

    /**
     * フォルダ内のファイル数をカウント
     */
    private function countFilesInFolder($folderId)
    {
        $result = $this->db->fetch(
            "SELECT COUNT(*) as cnt FROM file_entries WHERE folder_id = ?",
            [$folderId]
        );
        return $result['cnt'] ?? 0;
    }

    /**
     * フォルダ再帰削除
     */
    private function deleteFolderRecursive($folderId)
    {
        // サブフォルダを先に削除
        $subfolders = $this->db->fetchAll("SELECT id FROM file_folders WHERE parent_id = ?", [$folderId]);
        foreach ($subfolders as $sf) {
            $this->deleteFolderRecursive($sf['id']);
        }

        // フォルダ内のファイルを削除
        $files = $this->db->fetchAll("SELECT id, filename FROM file_entries WHERE folder_id = ?", [$folderId]);
        foreach ($files as $file) {
            // バージョンファイル削除
            $versions = $this->db->fetchAll("SELECT filename FROM file_versions WHERE file_id = ?", [$file['id']]);
            foreach ($versions as $v) {
                $path = $this->uploadDir . $v['filename'];
                if (file_exists($path)) unlink($path);
            }
            $path = $this->uploadDir . $file['filename'];
            if (file_exists($path)) unlink($path);
            $this->db->execute("DELETE FROM file_permissions WHERE resource_type = 'file' AND resource_id = ?", [$file['id']]);
            $this->db->execute("DELETE FROM file_entries WHERE id = ?", [$file['id']]);
        }

        // フォルダ自体を削除
        $this->db->execute("DELETE FROM file_permissions WHERE resource_type = 'folder' AND resource_id = ?", [$folderId]);
        $this->db->execute("DELETE FROM file_folders WHERE id = ?", [$folderId]);
    }

    /**
     * フォルダ一覧をフラットに取得（セレクトボックス用）
     */
    private function getAllFoldersFlat($parentId = null, $depth = 0)
    {
        $folders = $this->db->fetchAll(
            "SELECT id, name, parent_id FROM file_folders WHERE " . ($parentId === null ? "parent_id IS NULL" : "parent_id = ?") . " ORDER BY name",
            $parentId === null ? [] : [$parentId]
        );

        $result = [];
        foreach ($folders as $folder) {
            $folder['depth'] = $depth;
            $folder['indent_name'] = str_repeat('　', $depth) . $folder['name'];
            $result[] = $folder;
            $children = $this->getAllFoldersFlat($folder['id'], $depth + 1);
            $result = array_merge($result, $children);
        }

        return $result;
    }

    private function getRecentActivities($folderId = null, $limit = 8)
    {
        $params = [(int)$limit];
                $sql = "SELECT
                    fe.id,
                    fe.folder_id,
                    fe.title,
                    fe.original_name,
                    fe.updated_at,
                    fe.version,
                    fe.uploaded_by,
                    ff.name AS folder_name,
                    u.display_name AS uploader_name
                FROM file_entries fe
                LEFT JOIN file_folders ff ON ff.id = fe.folder_id
                LEFT JOIN users u ON u.id = fe.uploaded_by";

        if ($folderId !== null) {
            $sql .= " WHERE fe.folder_id = ?";
            $params = [(int)$folderId, (int)$limit];
        }

        $sql .= " ORDER BY fe.updated_at DESC LIMIT ?";
        $rows = $this->db->fetchAll($sql, $params);

        return array_values(array_filter($rows, function ($file) {
            return $this->permissionService->canViewFile($file, $this->auth->user());
        }));
    }

    private function notifyFileActivity($action, $fileId, array $fileData)
    {
        $actor = $this->auth->user();
        $recipients = NotificationRecipientHelper::uniqueRecipients(
            array_column($this->userModel->getActiveUsers(), 'id'),
            [$this->auth->id()]
        );

        if (empty($recipients)) {
            return;
        }

        $folderName = null;
        if (!empty($fileData['folder_id'])) {
            $folder = $this->db->fetch("SELECT name FROM file_folders WHERE id = ? LIMIT 1", [(int)$fileData['folder_id']]);
            $folderName = $folder['name'] ?? null;
        }

        $title = $action === 'updated' ? 'ファイルが更新されました' : '新しいファイルが追加されました';
        $content = ($actor['display_name'] ?? 'ユーザー') . ' さんが「' . ($fileData['title'] ?? 'ファイル') . '」を' .
            ($action === 'updated' ? '更新' : 'アップロード') . 'しました。';

        if (!empty($folderName)) {
            $content .= "\nフォルダ: " . $folderName;
        }

        if (!empty($fileData['version'])) {
            $content .= "\nバージョン: " . (int)$fileData['version'];
        }

        if (!empty($fileData['comment'])) {
            $content .= "\n変更内容: " . mb_strimwidth(trim((string)$fileData['comment']), 0, 100, '...');
        }

        foreach ($recipients as $recipientId) {
            $this->notification->create([
                'user_id' => $recipientId,
                'type' => 'system',
                'title' => $title,
                'content' => $content,
                'link' => '/files/file/' . (int)$fileId,
                'reference_id' => (int)$fileId,
                'reference_type' => 'file_entry',
                'suppress_email' => true
            ]);
        }
    }

    private function notifyApprovalRequested($fileId, $requestId, array $approverIds)
    {
        $file = $this->db->fetch("SELECT title FROM file_entries WHERE id = ? LIMIT 1", [(int)$fileId]);
        $actor = $this->auth->user();
        $title = 'ファイル承認依頼';
        $content = ($actor['display_name'] ?? 'ユーザー') . ' さんが「' . ($file['title'] ?? 'ファイル') . '」の承認を依頼しました。';

        foreach (NotificationRecipientHelper::uniqueRecipients($approverIds, [$this->auth->id()]) as $recipientId) {
            $this->notification->create([
                'user_id' => $recipientId,
                'type' => 'workflow',
                'title' => $title,
                'content' => $content,
                'link' => '/files/file/' . (int)$fileId,
                'reference_id' => (int)$requestId,
                'reference_type' => 'file_approval_request',
                'suppress_email' => true
            ]);
        }
    }

    private function notifyApprovalDecision(array $request, $decision, $comment)
    {
        $file = $this->db->fetch("SELECT title FROM file_entries WHERE id = ? LIMIT 1", [(int)$request['file_id']]);
        $actor = $this->auth->user();
        $content = ($actor['display_name'] ?? '承認者') . ' さんが「' . ($file['title'] ?? 'ファイル') . '」を' .
            ($decision === 'approved' ? '承認' : '差し戻し') . 'しました。';

        if ($comment !== '') {
            $content .= "\nコメント: " . mb_strimwidth($comment, 0, 120, '...');
        }

        $this->notification->create([
            'user_id' => (int)$request['requested_by'],
            'type' => 'workflow',
            'title' => $decision === 'approved' ? 'ファイルが承認されました' : 'ファイルが差し戻されました',
            'content' => $content,
            'link' => '/files/file/' . (int)$request['file_id'],
            'reference_id' => (int)$request['id'],
            'reference_type' => 'file_approval_request',
            'suppress_email' => true
        ]);
    }
}

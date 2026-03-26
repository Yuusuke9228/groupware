<?php
// Controllers/BulletinController.php
namespace Controllers;

use Core\Controller;
use Core\Database;
use Models\Notification;
use Models\Organization;
use Models\User;
use Services\NotificationRecipientHelper;

class BulletinController extends Controller
{
    private $db;
    private $notification;
    private $userModel;
    private $organizationModel;

    public function __construct()
    {
        parent::__construct();
        $this->db = Database::getInstance();
        $this->notification = new Notification();
        $this->userModel = new User();
        $this->organizationModel = new Organization();

        if (!$this->auth->check()) {
            $this->redirect(BASE_PATH . '/login');
        }
    }

    /**
     * 掲示板一覧
     */
    public function index()
    {
        $userId = $this->auth->id();
        $categoryId = $_GET['category'] ?? null;
        $search = $_GET['search'] ?? '';
        $page = max(1, intval($_GET['page'] ?? 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        list($visibilitySql, $visibilityParams) = $this->buildVisibilityCondition('p', $userId);

        $categories = $this->db->fetchAll(
            "SELECT c.*,
                    (
                        SELECT COUNT(*)
                        FROM bulletin_posts p
                        WHERE p.category_id = c.id
                          AND p.status = 'published'
                          AND {$visibilitySql}
                    ) AS post_count
             FROM bulletin_categories c
             ORDER BY c.sort_order, c.name",
            $visibilityParams
        );

        $where = ["p.status = 'published'", $visibilitySql];
        $params = $visibilityParams;

        if ($categoryId) {
            $where[] = "p.category_id = ?";
            $params[] = $categoryId;
        }

        if ($search) {
            $where[] = "(p.title LIKE ? OR p.body LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        $whereSql = implode(' AND ', $where);

        $total = $this->db->fetch(
            "SELECT COUNT(*) as cnt FROM bulletin_posts p WHERE {$whereSql}",
            $params
        )['cnt'];

        $pinnedPosts = $this->db->fetchAll(
            "SELECT p.*, u.display_name as author_name, c.name as category_name,
                    (SELECT COUNT(*) FROM bulletin_comments WHERE post_id = p.id) as comment_count,
                    (SELECT COUNT(*) FROM bulletin_post_reads WHERE post_id = p.id AND user_id = ?) as is_read
             FROM bulletin_posts p
             LEFT JOIN users u ON u.id = p.author_id
             LEFT JOIN bulletin_categories c ON c.id = p.category_id
             WHERE {$whereSql} AND p.is_pinned = 1
             ORDER BY p.updated_at DESC",
            array_merge([$userId], $params)
        );

        $posts = $this->db->fetchAll(
            "SELECT p.*, u.display_name as author_name, c.name as category_name,
                    (SELECT COUNT(*) FROM bulletin_comments WHERE post_id = p.id) as comment_count,
                    (SELECT COUNT(*) FROM bulletin_post_reads WHERE post_id = p.id AND user_id = ?) as is_read
             FROM bulletin_posts p
             LEFT JOIN users u ON u.id = p.author_id
             LEFT JOIN bulletin_categories c ON c.id = p.category_id
             WHERE {$whereSql} AND p.is_pinned = 0
             ORDER BY p.updated_at DESC
             LIMIT {$perPage} OFFSET {$offset}",
            array_merge([$userId], $params)
        );

        $unreadCount = $this->db->fetch(
            "SELECT COUNT(*) as cnt
             FROM bulletin_posts p
             WHERE p.status = 'published'
               AND {$visibilitySql}
               AND p.id NOT IN (
                   SELECT post_id FROM bulletin_post_reads WHERE user_id = ?
               )",
            array_merge($visibilityParams, [$userId])
        )['cnt'];

        $totalPages = ceil($total / $perPage);

        $this->view('bulletin/index', [
            'title' => '掲示板',
            'categories' => $categories,
            'pinnedPosts' => $pinnedPosts,
            'posts' => $posts,
            'currentCategory' => $categoryId,
            'search' => $search,
            'page' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
            'unreadCount' => $unreadCount,
        ]);
    }

    /**
     * 投稿詳細表示
     */
    public function show($params)
    {
        $postId = (int)$params['id'];
        $userId = $this->auth->id();

        $post = $this->db->fetch(
            "SELECT p.*, u.display_name as author_name, c.name as category_name
             FROM bulletin_posts p
             LEFT JOIN users u ON u.id = p.author_id
             LEFT JOIN bulletin_categories c ON c.id = p.category_id
             WHERE p.id = ?",
            [$postId]
        );

        if (!$post) {
            $_SESSION['error'] = '投稿が見つかりません。';
            $this->redirect(BASE_PATH . '/bulletin');
        }

        if (!$this->canViewPostRecord($post, $userId)) {
            $_SESSION['error'] = '閲覧権限がありません。';
            $this->redirect(BASE_PATH . '/bulletin');
        }

        try {
            $this->db->execute(
                "UPDATE bulletin_posts SET view_count = view_count + 1 WHERE id = ?",
                [$postId]
            );

            $existing = $this->db->fetch(
                "SELECT id FROM bulletin_post_reads WHERE post_id = ? AND user_id = ?",
                [$postId, $userId]
            );
            if (!$existing) {
                $this->db->execute(
                    "INSERT INTO bulletin_post_reads (post_id, user_id) VALUES (?, ?)",
                    [$postId, $userId]
                );
            }
        } catch (\Throwable $e) {
            error_log('Failed to update bulletin read state: ' . $e->getMessage());
        }

        $comments = $this->db->fetchAll(
            "SELECT c.*, u.display_name as user_name
             FROM bulletin_comments c
             LEFT JOIN users u ON u.id = c.user_id
             WHERE c.post_id = ?
             ORDER BY c.created_at ASC",
            [$postId]
        );

        $attachments = $this->db->fetchAll(
            "SELECT * FROM bulletin_attachments WHERE post_id = ? ORDER BY id",
            [$postId]
        );

        $readers = $this->db->fetchAll(
            "SELECT r.read_at, u.display_name
             FROM bulletin_post_reads r
             LEFT JOIN users u ON u.id = r.user_id
             WHERE r.post_id = ?
             ORDER BY r.read_at DESC",
            [$postId]
        );

        $targets = $this->getPostTargets($postId);

        $this->view('bulletin/show', [
            'title' => htmlspecialchars($post['title']) . ' - 掲示板',
            'post' => $post,
            'comments' => $comments,
            'attachments' => $attachments,
            'readers' => $readers,
            'targets' => $targets,
        ]);
    }

    /**
     * 新規投稿フォーム
     */
    public function create()
    {
        $categories = $this->db->fetchAll(
            "SELECT * FROM bulletin_categories ORDER BY sort_order, name"
        );

        $this->view('bulletin/form', [
            'title' => '新規投稿 - 掲示板',
            'post' => null,
            'categories' => $categories,
            'organizations' => $this->organizationModel->getAll(),
            'users' => $this->userModel->getActiveUsers(),
            'targetOrganizationIds' => [],
            'targetUserIds' => [],
            'csrfToken' => $this->generateCsrfToken(),
        ]);
    }

    /**
     * 新規投稿保存
     */
    public function store()
    {
        if (!$this->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = '不正なリクエストです。';
            $this->redirect(BASE_PATH . '/bulletin/create');
        }

        $title = trim($_POST['title'] ?? '');
        $body = trim($_POST['body'] ?? '');
        $categoryId = $_POST['category_id'] ?: null;
        $isPinned = isset($_POST['is_pinned']) ? 1 : 0;
        $status = $_POST['status'] ?? 'published';
        $visibility = $this->normalizeVisibility($_POST['visibility'] ?? 'all');

        if (empty($title) || empty($body)) {
            $_SESSION['error'] = 'タイトルと本文は必須です。';
            $this->redirect(BASE_PATH . '/bulletin/create');
        }

        $this->db->execute(
            "INSERT INTO bulletin_posts (category_id, title, body, is_pinned, status, visibility, author_id)
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [$categoryId, $title, $body, $isPinned, $status, $visibility, $this->auth->id()]
        );

        $postId = (int)$this->db->lastInsertId();

        $this->syncPostTargets(
            $postId,
            $visibility,
            $_POST['target_organization_ids'] ?? [],
            $_POST['target_user_ids'] ?? []
        );

        $this->handleAttachments($postId);
        $this->notifyPostPublished($postId, $title, $status, $visibility);

        $_SESSION['success'] = '投稿を作成しました。';
        $this->redirect(BASE_PATH . '/bulletin/' . $postId);
    }

    /**
     * 編集フォーム
     */
    public function edit($params)
    {
        $postId = (int)$params['id'];

        $post = $this->db->fetch(
            "SELECT * FROM bulletin_posts WHERE id = ?",
            [$postId]
        );

        if (!$post) {
            $_SESSION['error'] = '投稿が見つかりません。';
            $this->redirect(BASE_PATH . '/bulletin');
        }

        if ($post['author_id'] != $this->auth->id() && !$this->auth->isAdmin()) {
            $_SESSION['error'] = '編集権限がありません。';
            $this->redirect(BASE_PATH . '/bulletin/' . $postId);
        }

        $categories = $this->db->fetchAll(
            "SELECT * FROM bulletin_categories ORDER BY sort_order, name"
        );

        $attachments = $this->db->fetchAll(
            "SELECT * FROM bulletin_attachments WHERE post_id = ? ORDER BY id",
            [$postId]
        );

        $targets = $this->getPostTargets($postId);

        $this->view('bulletin/form', [
            'title' => '投稿を編集 - 掲示板',
            'post' => $post,
            'categories' => $categories,
            'attachments' => $attachments,
            'organizations' => $this->organizationModel->getAll(),
            'users' => $this->userModel->getActiveUsers(),
            'targetOrganizationIds' => array_map('intval', array_column($targets['organizations'], 'id')),
            'targetUserIds' => array_map('intval', array_column($targets['users'], 'id')),
            'csrfToken' => $this->generateCsrfToken(),
        ]);
    }

    /**
     * 投稿更新
     */
    public function update($params)
    {
        $postId = (int)$params['id'];

        $post = $this->db->fetch("SELECT * FROM bulletin_posts WHERE id = ?", [$postId]);
        if (!$post) {
            $_SESSION['error'] = '投稿が見つかりません。';
            $this->redirect(BASE_PATH . '/bulletin');
        }

        if ($post['author_id'] != $this->auth->id() && !$this->auth->isAdmin()) {
            $_SESSION['error'] = '編集権限がありません。';
            $this->redirect(BASE_PATH . '/bulletin/' . $postId);
        }

        if (!$this->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = '不正なリクエストです。';
            $this->redirect(BASE_PATH . '/bulletin/' . $postId . '/edit');
        }

        $title = trim($_POST['title'] ?? '');
        $body = trim($_POST['body'] ?? '');
        $categoryId = $_POST['category_id'] ?: null;
        $isPinned = isset($_POST['is_pinned']) ? 1 : 0;
        $status = $_POST['status'] ?? 'published';
        $visibility = $this->normalizeVisibility($_POST['visibility'] ?? 'all');

        if (empty($title) || empty($body)) {
            $_SESSION['error'] = 'タイトルと本文は必須です。';
            $this->redirect(BASE_PATH . '/bulletin/' . $postId . '/edit');
        }

        $this->db->execute(
            "UPDATE bulletin_posts
             SET category_id = ?, title = ?, body = ?, is_pinned = ?, status = ?, visibility = ?, updated_at = NOW()
             WHERE id = ?",
            [$categoryId, $title, $body, $isPinned, $status, $visibility, $postId]
        );

        $this->syncPostTargets(
            $postId,
            $visibility,
            $_POST['target_organization_ids'] ?? [],
            $_POST['target_user_ids'] ?? []
        );

        if (!empty($_POST['delete_attachments'])) {
            foreach ($_POST['delete_attachments'] as $attachId) {
                $attach = $this->db->fetch(
                    "SELECT * FROM bulletin_attachments WHERE id = ? AND post_id = ?",
                    [$attachId, $postId]
                );
                if ($attach) {
                    $filePath = __DIR__ . '/../public/uploads/bulletin/' . $attach['filename'];
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                    $this->db->execute("DELETE FROM bulletin_attachments WHERE id = ?", [$attachId]);
                }
            }
        }

        $this->handleAttachments($postId);

        $_SESSION['success'] = '投稿を更新しました。';
        $this->redirect(BASE_PATH . '/bulletin/' . $postId);
    }

    /**
     * 投稿削除
     */
    public function delete($params)
    {
        $postId = (int)$params['id'];

        $post = $this->db->fetch("SELECT * FROM bulletin_posts WHERE id = ?", [$postId]);
        if (!$post) {
            $_SESSION['error'] = '投稿が見つかりません。';
            $this->redirect(BASE_PATH . '/bulletin');
        }

        if ($post['author_id'] != $this->auth->id() && !$this->auth->isAdmin()) {
            $_SESSION['error'] = '削除権限がありません。';
            $this->redirect(BASE_PATH . '/bulletin/' . $postId);
        }

        $attachments = $this->db->fetchAll("SELECT * FROM bulletin_attachments WHERE post_id = ?", [$postId]);
        foreach ($attachments as $attach) {
            $filePath = __DIR__ . '/../public/uploads/bulletin/' . $attach['filename'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        $this->db->execute("DELETE FROM bulletin_posts WHERE id = ?", [$postId]);

        $_SESSION['success'] = '投稿を削除しました。';
        $this->redirect(BASE_PATH . '/bulletin');
    }

    /**
     * カテゴリ管理画面（管理者のみ）
     */
    public function categories()
    {
        if (!$this->auth->isAdmin()) {
            $_SESSION['error'] = '管理者権限が必要です。';
            $this->redirect(BASE_PATH . '/bulletin');
        }

        $categories = $this->db->fetchAll(
            "SELECT c.*, u.display_name as creator_name, COUNT(p.id) as post_count
             FROM bulletin_categories c
             LEFT JOIN users u ON u.id = c.created_by
             LEFT JOIN bulletin_posts p ON p.category_id = c.id
             GROUP BY c.id
             ORDER BY c.sort_order, c.name"
        );

        $this->view('bulletin/categories', [
            'title' => 'カテゴリ管理 - 掲示板',
            'categories' => $categories,
            'csrfToken' => $this->generateCsrfToken(),
        ]);
    }

    /**
     * カテゴリ追加
     */
    public function addCategory()
    {
        if (!$this->auth->isAdmin()) {
            $_SESSION['error'] = '管理者権限が必要です。';
            $this->redirect(BASE_PATH . '/bulletin');
        }

        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $sortOrder = intval($_POST['sort_order'] ?? 0);

        if (empty($name)) {
            $_SESSION['error'] = 'カテゴリ名は必須です。';
            $this->redirect(BASE_PATH . '/bulletin/categories');
        }

        $this->db->execute(
            "INSERT INTO bulletin_categories (name, description, sort_order, created_by) VALUES (?, ?, ?, ?)",
            [$name, $description, $sortOrder, $this->auth->id()]
        );

        $_SESSION['success'] = 'カテゴリを追加しました。';
        $this->redirect(BASE_PATH . '/bulletin/categories');
    }

    /**
     * カテゴリ更新
     */
    public function updateCategory($params)
    {
        if (!$this->auth->isAdmin()) {
            $this->json(['error' => '管理者権限が必要です。'], 403);
        }

        $id = $params['id'];
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $sortOrder = intval($_POST['sort_order'] ?? 0);

        if (empty($name)) {
            $_SESSION['error'] = 'カテゴリ名は必須です。';
            $this->redirect(BASE_PATH . '/bulletin/categories');
        }

        $this->db->execute(
            "UPDATE bulletin_categories SET name = ?, description = ?, sort_order = ? WHERE id = ?",
            [$name, $description, $sortOrder, $id]
        );

        $_SESSION['success'] = 'カテゴリを更新しました。';
        $this->redirect(BASE_PATH . '/bulletin/categories');
    }

    /**
     * カテゴリ削除
     */
    public function deleteCategory($params)
    {
        if (!$this->auth->isAdmin()) {
            $_SESSION['error'] = '管理者権限が必要です。';
            $this->redirect(BASE_PATH . '/bulletin');
        }

        $id = $params['id'];

        $this->db->execute("UPDATE bulletin_posts SET category_id = NULL WHERE category_id = ?", [$id]);
        $this->db->execute("DELETE FROM bulletin_categories WHERE id = ?", [$id]);

        $_SESSION['success'] = 'カテゴリを削除しました。';
        $this->redirect(BASE_PATH . '/bulletin/categories');
    }

    /**
     * コメント追加
     */
    public function addComment($params)
    {
        $postId = (int)$params['id'];
        $body = trim($_POST['body'] ?? '');

        if (empty($body)) {
            $_SESSION['error'] = 'コメントを入力してください。';
            $this->redirect(BASE_PATH . '/bulletin/' . $postId);
        }

        $post = $this->db->fetch("SELECT * FROM bulletin_posts WHERE id = ?", [$postId]);
        if (!$post) {
            $_SESSION['error'] = '投稿が見つかりません。';
            $this->redirect(BASE_PATH . '/bulletin');
        }

        if (!$this->canViewPostRecord($post, $this->auth->id())) {
            $_SESSION['error'] = 'コメント権限がありません。';
            $this->redirect(BASE_PATH . '/bulletin');
        }

        $this->db->execute(
            "INSERT INTO bulletin_comments (post_id, user_id, body) VALUES (?, ?, ?)",
            [$postId, $this->auth->id(), $body]
        );

        $this->db->execute("UPDATE bulletin_posts SET updated_at = NOW() WHERE id = ?", [$postId]);
        $this->notifyCommentAdded($postId, $body);

        $_SESSION['success'] = 'コメントを投稿しました。';
        $this->redirect(BASE_PATH . '/bulletin/' . $postId);
    }

    /**
     * コメント削除
     */
    public function deleteComment($params)
    {
        $commentId = $params['id'];

        $comment = $this->db->fetch(
            "SELECT * FROM bulletin_comments WHERE id = ?",
            [$commentId]
        );

        if (!$comment) {
            $_SESSION['error'] = 'コメントが見つかりません。';
            $this->redirect(BASE_PATH . '/bulletin');
        }

        if ($comment['user_id'] != $this->auth->id() && !$this->auth->isAdmin()) {
            $_SESSION['error'] = '削除権限がありません。';
            $this->redirect(BASE_PATH . '/bulletin/' . $comment['post_id']);
        }

        $this->db->execute("DELETE FROM bulletin_comments WHERE id = ?", [$commentId]);

        $_SESSION['success'] = 'コメントを削除しました。';
        $this->redirect(BASE_PATH . '/bulletin/' . $comment['post_id']);
    }

    /**
     * 添付ファイル処理
     */
    private function handleAttachments($postId)
    {
        if (empty($_FILES['attachments']) || empty($_FILES['attachments']['name'][0])) {
            return;
        }

        $uploadDir = __DIR__ . '/../public/uploads/bulletin/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $files = $_FILES['attachments'];
        $fileCount = count($files['name']);

        for ($i = 0; $i < $fileCount; $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }

            $originalName = $files['name'][$i];
            $tmpName = $files['tmp_name'][$i];
            $fileSize = $files['size'][$i];
            $mimeType = $files['type'][$i];

            $ext = pathinfo($originalName, PATHINFO_EXTENSION);
            $uniqueName = uniqid('bulletin_') . '_' . time() . '.' . $ext;

            if (move_uploaded_file($tmpName, $uploadDir . $uniqueName)) {
                $this->db->execute(
                    "INSERT INTO bulletin_attachments (post_id, filename, original_name, file_size, mime_type)
                     VALUES (?, ?, ?, ?, ?)",
                    [$postId, $uniqueName, $originalName, $fileSize, $mimeType]
                );
            }
        }
    }

    private function notifyPostPublished($postId, $title, $status, $visibility)
    {
        if ($status !== 'published') {
            return;
        }

        $actor = $this->auth->user();
        $recipients = NotificationRecipientHelper::uniqueRecipients(
            $this->resolveVisibilityRecipientIds($postId, $visibility),
            [$this->auth->id()]
        );

        foreach ($recipients as $recipientId) {
            $this->notification->create([
                'user_id' => $recipientId,
                'type' => 'system',
                'title' => '掲示板に新しい投稿があります',
                'content' => ($actor['display_name'] ?? 'ユーザー') . ' さんが「' . $title . '」を投稿しました。',
                'link' => '/bulletin/' . (int)$postId,
                'reference_id' => (int)$postId,
                'reference_type' => 'bulletin_post',
                'suppress_email' => true
            ]);
        }
    }

    private function notifyCommentAdded($postId, $body)
    {
        $post = $this->db->fetch(
            "SELECT id, title, author_id, visibility FROM bulletin_posts WHERE id = ? LIMIT 1",
            [(int)$postId]
        );

        if (!$post) {
            return;
        }

        $commenters = $this->db->fetchAll(
            "SELECT DISTINCT user_id FROM bulletin_comments WHERE post_id = ?",
            [(int)$postId]
        );

        $recipientIds = NotificationRecipientHelper::uniqueRecipients(
            array_merge(
                [(int)$post['author_id']],
                array_map('intval', array_column($commenters, 'user_id')),
                $this->resolveVisibilityRecipientIds((int)$postId, $post['visibility'])
            ),
            [$this->auth->id()]
        );

        if (empty($recipientIds)) {
            return;
        }

        $actor = $this->auth->user();
        $preview = mb_strimwidth(trim($body), 0, 80, '...');

        foreach ($recipientIds as $recipientId) {
            $this->notification->create([
                'user_id' => $recipientId,
                'type' => 'system',
                'title' => '掲示板に新しいコメントがあります',
                'content' => ($actor['display_name'] ?? 'ユーザー') . ' さんが「' . $post['title'] . '」にコメントしました。' .
                    ($preview !== '' ? "\n" . $preview : ''),
                'link' => '/bulletin/' . (int)$postId,
                'reference_id' => (int)$postId,
                'reference_type' => 'bulletin_comment',
                'suppress_email' => true
            ]);
        }
    }

    private function normalizeVisibility($visibility)
    {
        $visibility = trim((string)$visibility);
        if (!in_array($visibility, ['all', 'organization', 'specific'], true)) {
            return 'all';
        }

        return $visibility;
    }

    private function buildVisibilityCondition($postAlias, $userId)
    {
        return [
            "(
                {$postAlias}.visibility = 'all'
                OR {$postAlias}.author_id = ?
                OR (
                    {$postAlias}.visibility = 'organization'
                    AND EXISTS (
                        SELECT 1
                        FROM bulletin_post_targets bpt_org
                        INNER JOIN user_organizations uo ON uo.organization_id = bpt_org.target_id
                        WHERE bpt_org.post_id = {$postAlias}.id
                          AND bpt_org.target_type = 'organization'
                          AND uo.user_id = ?
                    )
                )
                OR (
                    {$postAlias}.visibility = 'specific'
                    AND EXISTS (
                        SELECT 1
                        FROM bulletin_post_targets bpt_user
                        WHERE bpt_user.post_id = {$postAlias}.id
                          AND bpt_user.target_type = 'user'
                          AND bpt_user.target_id = ?
                    )
                )
            )",
            [(int)$userId, (int)$userId, (int)$userId]
        ];
    }

    private function canViewPostRecord($post, $userId)
    {
        if (!$post) {
            return false;
        }

        if ($this->auth->isAdmin() || (int)$post['author_id'] === (int)$userId) {
            return true;
        }

        if (($post['status'] ?? 'published') !== 'published') {
            return false;
        }

        if (($post['visibility'] ?? 'all') === 'all') {
            return true;
        }

        if ($post['visibility'] === 'organization') {
            $row = $this->db->fetch(
                "SELECT 1
                 FROM bulletin_post_targets bpt
                 INNER JOIN user_organizations uo ON uo.organization_id = bpt.target_id
                 WHERE bpt.post_id = ? AND bpt.target_type = 'organization' AND uo.user_id = ?
                 LIMIT 1",
                [(int)$post['id'], (int)$userId]
            );

            return (bool)$row;
        }

        if ($post['visibility'] === 'specific') {
            $row = $this->db->fetch(
                "SELECT 1
                 FROM bulletin_post_targets
                 WHERE post_id = ? AND target_type = 'user' AND target_id = ?
                 LIMIT 1",
                [(int)$post['id'], (int)$userId]
            );

            return (bool)$row;
        }

        return false;
    }

    private function syncPostTargets($postId, $visibility, $organizationIds, $userIds)
    {
        $this->db->execute("DELETE FROM bulletin_post_targets WHERE post_id = ?", [(int)$postId]);

        if ($visibility === 'organization') {
            foreach ($this->normalizeTargetIds($organizationIds) as $organizationId) {
                $this->db->execute(
                    "INSERT INTO bulletin_post_targets (post_id, target_type, target_id)
                     VALUES (?, 'organization', ?)",
                    [(int)$postId, (int)$organizationId]
                );
            }
        }

        if ($visibility === 'specific') {
            foreach ($this->normalizeTargetIds($userIds) as $targetUserId) {
                $this->db->execute(
                    "INSERT INTO bulletin_post_targets (post_id, target_type, target_id)
                     VALUES (?, 'user', ?)",
                    [(int)$postId, (int)$targetUserId]
                );
            }
        }
    }

    private function normalizeTargetIds($ids)
    {
        if (!is_array($ids)) {
            $ids = [$ids];
        }

        $ids = array_map('intval', $ids);
        $ids = array_filter($ids, function ($id) {
            return $id > 0;
        });

        return array_values(array_unique($ids));
    }

    private function getPostTargets($postId)
    {
        $organizations = $this->db->fetchAll(
            "SELECT o.id, o.name
             FROM bulletin_post_targets bpt
             INNER JOIN organizations o ON o.id = bpt.target_id
             WHERE bpt.post_id = ? AND bpt.target_type = 'organization'
             ORDER BY o.name",
            [(int)$postId]
        );

        $users = $this->db->fetchAll(
            "SELECT u.id, u.display_name
             FROM bulletin_post_targets bpt
             INNER JOIN users u ON u.id = bpt.target_id
             WHERE bpt.post_id = ? AND bpt.target_type = 'user'
             ORDER BY u.display_name",
            [(int)$postId]
        );

        return [
            'organizations' => $organizations,
            'users' => $users,
        ];
    }

    private function resolveVisibilityRecipientIds($postId, $visibility)
    {
        if ($visibility === 'organization') {
            $rows = $this->db->fetchAll(
                "SELECT DISTINCT u.id
                 FROM bulletin_post_targets bpt
                 INNER JOIN user_organizations uo ON uo.organization_id = bpt.target_id
                 INNER JOIN users u ON u.id = uo.user_id
                 WHERE bpt.post_id = ?
                   AND bpt.target_type = 'organization'
                   AND u.status = 'active'",
                [(int)$postId]
            );

            return array_map('intval', array_column($rows, 'id'));
        }

        if ($visibility === 'specific') {
            $rows = $this->db->fetchAll(
                "SELECT DISTINCT u.id
                 FROM bulletin_post_targets bpt
                 INNER JOIN users u ON u.id = bpt.target_id
                 WHERE bpt.post_id = ?
                   AND bpt.target_type = 'user'
                   AND u.status = 'active'",
                [(int)$postId]
            );

            return array_map('intval', array_column($rows, 'id'));
        }

        return array_map('intval', array_column($this->userModel->getActiveUsers(), 'id'));
    }
}

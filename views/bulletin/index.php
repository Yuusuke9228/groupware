<?php
// views/bulletin/index.php
?>
<style>
.bulletin-sidebar .list-group-item.active {
    background-color: #2b7de9;
    border-color: #2b7de9;
}
.bulletin-sidebar .list-group-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.6rem 1rem;
    font-size: 0.9rem;
}
.bulletin-post-item {
    padding: 0.85rem 1rem;
    border-bottom: 1px solid #eee;
    transition: background 0.15s;
    cursor: pointer;
    text-decoration: none;
    color: inherit;
    display: block;
}
.bulletin-post-item:hover {
    background: #f8f9fa;
    color: inherit;
    text-decoration: none;
}
.bulletin-post-item.unread .post-title {
    font-weight: 700;
}
.bulletin-post-item .post-title {
    font-size: 0.95rem;
    margin-bottom: 0.2rem;
    color: #333;
}
.bulletin-post-item .post-meta {
    font-size: 0.8rem;
    color: #888;
}
.bulletin-post-item .post-meta span {
    margin-right: 0.75rem;
}
.badge-unread {
    background-color: #dc3545;
    color: #fff;
    font-size: 0.7rem;
    padding: 0.2em 0.55em;
    border-radius: 10px;
    margin-left: 0.4rem;
}
.pin-icon {
    color: #f0ad4e;
    margin-right: 0.35rem;
    font-size: 0.85rem;
}
.category-badge {
    font-size: 0.75rem;
    padding: 0.15em 0.5em;
    border-radius: 3px;
    background: #e9ecef;
    color: #555;
    margin-right: 0.5rem;
}
</style>

<div class="container-fluid py-3">
    <!-- ヘッダー -->
    <div class="row mb-3 align-items-center">
        <div class="col">
            <h1 class="h4 mb-0">
                <i class="fas fa-clipboard-list me-2 text-primary"></i>掲示板
                <?php if ($unreadCount > 0): ?>
                    <span class="badge bg-danger ms-2" style="font-size:0.65rem;vertical-align:middle;"><?php echo $unreadCount; ?> 未読</span>
                <?php endif; ?>
            </h1>
        </div>
        <div class="col-auto">
            <a href="<?php echo BASE_PATH; ?>/bulletin/create" class="btn btn-primary btn-sm">
                <i class="fas fa-plus me-1"></i>新規投稿
            </a>
            <?php if ($this->auth->isAdmin()): ?>
                <a href="<?php echo BASE_PATH; ?>/bulletin/categories" class="btn btn-outline-secondary btn-sm ms-1">
                    <i class="fas fa-cog me-1"></i>カテゴリ管理
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- サイドバー: カテゴリ -->
        <div class="col-lg-3 col-md-4 mb-3">
            <div class="card bulletin-sidebar">
                <div class="card-header py-2">
                    <strong><i class="fas fa-folder me-1"></i>カテゴリ</strong>
                </div>
                <div class="list-group list-group-flush">
                    <a href="<?php echo BASE_PATH; ?>/bulletin"
                       class="list-group-item list-group-item-action <?php echo !$currentCategory ? 'active' : ''; ?>">
                        すべて
                        <span class="badge <?php echo !$currentCategory ? 'bg-light text-dark' : 'bg-secondary'; ?>"><?php echo $total; ?></span>
                    </a>
                    <?php foreach ($categories as $cat): ?>
                        <a href="<?php echo BASE_PATH; ?>/bulletin?category=<?php echo $cat['id']; ?>"
                           class="list-group-item list-group-item-action <?php echo $currentCategory == $cat['id'] ? 'active' : ''; ?>">
                            <?php echo htmlspecialchars($cat['name']); ?>
                            <span class="badge <?php echo $currentCategory == $cat['id'] ? 'bg-light text-dark' : 'bg-secondary'; ?>"><?php echo $cat['post_count']; ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- メインコンテンツ -->
        <div class="col-lg-9 col-md-8">
            <!-- 検索 -->
            <div class="card mb-3">
                <div class="card-body py-2">
                    <form method="get" action="<?php echo BASE_PATH; ?>/bulletin" class="d-flex align-items-center">
                        <?php if ($currentCategory): ?>
                            <input type="hidden" name="category" value="<?php echo htmlspecialchars($currentCategory); ?>">
                        <?php endif; ?>
                        <div class="input-group input-group-sm">
                            <input type="text" name="search" class="form-control" placeholder="キーワードで検索..."
                                   value="<?php echo htmlspecialchars($search); ?>">
                            <button class="btn btn-outline-secondary" type="submit"><i class="fas fa-search"></i></button>
                            <?php if ($search): ?>
                                <a href="<?php echo BASE_PATH; ?>/bulletin<?php echo $currentCategory ? '?category=' . $currentCategory : ''; ?>"
                                   class="btn btn-outline-secondary"><i class="fas fa-times"></i></a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ピン留め投稿 -->
            <?php if (!empty($pinnedPosts)): ?>
                <div class="card mb-3">
                    <div class="card-header py-2 bg-warning bg-opacity-10">
                        <i class="fas fa-thumbtack text-warning me-1"></i>
                        <strong>重要なお知らせ</strong>
                    </div>
                    <div class="card-body p-0">
                        <?php foreach ($pinnedPosts as $post): ?>
                            <a href="<?php echo BASE_PATH; ?>/bulletin/<?php echo $post['id']; ?>"
                               class="bulletin-post-item <?php echo !$post['is_read'] ? 'unread' : ''; ?>">
                                <div class="post-title">
                                    <i class="fas fa-thumbtack pin-icon"></i>
                                    <?php if (!$post['is_read']): ?>
                                        <span class="badge-unread">NEW</span>
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($post['title']); ?>
                                </div>
                                <div class="post-meta">
                                    <?php if ($post['category_name']): ?>
                                        <span class="category-badge"><?php echo htmlspecialchars($post['category_name']); ?></span>
                                    <?php endif; ?>
                                    <span><i class="far fa-user me-1"></i><?php echo htmlspecialchars($post['author_name']); ?></span>
                                    <span><i class="far fa-clock me-1"></i><?php echo date('Y/m/d H:i', strtotime($post['updated_at'])); ?></span>
                                    <span><i class="far fa-eye me-1"></i><?php echo $post['view_count']; ?></span>
                                    <?php if ($post['comment_count'] > 0): ?>
                                        <span><i class="far fa-comment me-1"></i><?php echo $post['comment_count']; ?></span>
                                    <?php endif; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- 通常投稿 -->
            <div class="card">
                <div class="card-header py-2 d-flex justify-content-between align-items-center">
                    <strong>投稿一覧</strong>
                    <span class="text-muted small"><?php echo $total; ?>件</span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($posts) && empty($pinnedPosts)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-clipboard fa-3x mb-3 d-block opacity-25"></i>
                            <p>投稿はまだありません。</p>
                            <a href="<?php echo BASE_PATH; ?>/bulletin/create" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus me-1"></i>最初の投稿を作成
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($posts as $post): ?>
                            <a href="<?php echo BASE_PATH; ?>/bulletin/<?php echo $post['id']; ?>"
                               class="bulletin-post-item <?php echo !$post['is_read'] ? 'unread' : ''; ?>">
                                <div class="post-title">
                                    <?php if (!$post['is_read']): ?>
                                        <span class="badge-unread">NEW</span>
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($post['title']); ?>
                                </div>
                                <div class="post-meta">
                                    <?php if ($post['category_name']): ?>
                                        <span class="category-badge"><?php echo htmlspecialchars($post['category_name']); ?></span>
                                    <?php endif; ?>
                                    <span><i class="far fa-user me-1"></i><?php echo htmlspecialchars($post['author_name']); ?></span>
                                    <span><i class="far fa-clock me-1"></i><?php echo date('Y/m/d H:i', strtotime($post['updated_at'])); ?></span>
                                    <span><i class="far fa-eye me-1"></i><?php echo $post['view_count']; ?></span>
                                    <?php if ($post['comment_count'] > 0): ?>
                                        <span><i class="far fa-comment me-1"></i><?php echo $post['comment_count']; ?></span>
                                    <?php endif; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- ページネーション -->
                <?php if ($totalPages > 1): ?>
                    <div class="card-footer py-2">
                        <nav>
                            <ul class="pagination pagination-sm justify-content-center mb-0">
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="<?php echo BASE_PATH; ?>/bulletin?page=<?php echo $page - 1; ?><?php echo $currentCategory ? '&category=' . $currentCategory : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="<?php echo BASE_PATH; ?>/bulletin?page=<?php echo $i; ?><?php echo $currentCategory ? '&category=' . $currentCategory : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="<?php echo BASE_PATH; ?>/bulletin?page=<?php echo $page + 1; ?><?php echo $currentCategory ? '&category=' . $currentCategory : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
// views/bulletin/show.php
$isAuthor = ($post['author_id'] == $this->auth->id());
$isAdmin = $this->auth->isAdmin();
$canEdit = $isAuthor || $isAdmin;
$visibilityLabels = [
    'all' => '<span class="badge bg-success">全社公開</span>',
    'organization' => '<span class="badge bg-info text-dark">組織公開</span>',
    'specific' => '<span class="badge bg-primary">指定ユーザー公開</span>',
];
?>
<style>
.bulletin-detail .post-body {
    font-size: 0.95rem;
    line-height: 1.8;
    white-space: pre-wrap;
    word-wrap: break-word;
}
.bulletin-detail .post-body a {
    color: #2b7de9;
}
.bulletin-meta-item {
    display: inline-flex;
    align-items: center;
    margin-right: 1.2rem;
    color: #666;
    font-size: 0.85rem;
}
.bulletin-meta-item i {
    margin-right: 0.3rem;
}
.comment-item {
    padding: 0.75rem 0;
    border-bottom: 1px solid #f0f0f0;
}
.comment-item:last-child {
    border-bottom: none;
}
.comment-user {
    font-weight: 600;
    font-size: 0.9rem;
    color: #333;
}
.comment-time {
    font-size: 0.78rem;
    color: #999;
    margin-left: 0.5rem;
}
.comment-body {
    font-size: 0.9rem;
    line-height: 1.7;
    margin-top: 0.3rem;
    white-space: pre-wrap;
    word-wrap: break-word;
}
.attachment-list a {
    display: inline-flex;
    align-items: center;
    padding: 0.35rem 0.75rem;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    margin: 0.25rem;
    text-decoration: none;
    color: #333;
    font-size: 0.85rem;
    transition: background 0.15s;
}
.attachment-list a:hover {
    background: #f8f9fa;
}
.attachment-list a i {
    margin-right: 0.4rem;
    color: #6c757d;
}
.readers-toggle {
    cursor: pointer;
    color: #2b7de9;
    font-size: 0.85rem;
}
</style>

<div class="container-fluid py-3">
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

    <!-- パンくず -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb mb-0" style="font-size:0.85rem;">
            <li class="breadcrumb-item"><a href="<?php echo BASE_PATH; ?>/bulletin">掲示板</a></li>
            <?php if ($post['category_name']): ?>
                <li class="breadcrumb-item"><a href="<?php echo BASE_PATH; ?>/bulletin?category=<?php echo $post['category_id']; ?>"><?php echo htmlspecialchars($post['category_name']); ?></a></li>
            <?php endif; ?>
            <li class="breadcrumb-item active"><?php echo htmlspecialchars($post['title']); ?></li>
        </ol>
    </nav>

    <div class="row">
        <div class="col-lg-9">
            <!-- 投稿本文 -->
            <div class="card bulletin-detail mb-3">
                <div class="card-header py-2 d-flex justify-content-between align-items-start">
                    <div>
                        <?php if ($post['is_pinned']): ?>
                            <span class="badge bg-warning text-dark me-1"><i class="fas fa-thumbtack me-1"></i>ピン留め</span>
                        <?php endif; ?>
                        <?php if ($post['category_name']): ?>
                            <span class="badge bg-secondary"><?php echo htmlspecialchars($post['category_name']); ?></span>
                        <?php endif; ?>
                        <?php echo $visibilityLabels[$post['visibility'] ?? 'all'] ?? ''; ?>
                    </div>
                    <?php if ($canEdit): ?>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="<?php echo BASE_PATH; ?>/bulletin/<?php echo $post['id']; ?>/edit"><i class="fas fa-edit me-2"></i>編集</a></li>
                                <li>
                                    <form method="post" action="<?php echo BASE_PATH; ?>/bulletin/<?php echo $post['id']; ?>/delete"
                                          class="no-ajax" onsubmit="return confirm('この投稿を削除しますか？');">
                                        <button type="submit" class="dropdown-item text-danger"><i class="fas fa-trash me-2"></i>削除</button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <h4 class="mb-3"><?php echo htmlspecialchars($post['title']); ?></h4>

                    <div class="mb-3">
                        <span class="bulletin-meta-item"><i class="far fa-user"></i><?php echo htmlspecialchars($post['author_name']); ?></span>
                        <span class="bulletin-meta-item"><i class="far fa-clock"></i>投稿: <?php echo date('Y/m/d H:i', strtotime($post['created_at'])); ?></span>
                        <?php if ($post['updated_at'] !== $post['created_at']): ?>
                            <span class="bulletin-meta-item"><i class="fas fa-edit"></i>更新: <?php echo date('Y/m/d H:i', strtotime($post['updated_at'])); ?></span>
                        <?php endif; ?>
                        <span class="bulletin-meta-item"><i class="far fa-eye"></i><?php echo $post['view_count']; ?> 閲覧</span>
                    </div>

                    <hr>

                    <div class="post-body"><?php echo nl2br(htmlspecialchars($post['body'])); ?></div>

                    <?php if (($post['visibility'] ?? 'all') === 'organization' && !empty($targets['organizations'])): ?>
                        <hr>
                        <div class="small">
                            <strong><i class="fas fa-building me-1"></i>公開対象組織</strong>
                            <div class="mt-2">
                                <?php foreach ($targets['organizations'] as $target): ?>
                                    <span class="badge bg-light text-dark border me-1 mb-1"><?php echo htmlspecialchars($target['name']); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (($post['visibility'] ?? 'all') === 'specific' && !empty($targets['users'])): ?>
                        <hr>
                        <div class="small">
                            <strong><i class="fas fa-users me-1"></i>公開対象ユーザー</strong>
                            <div class="mt-2">
                                <?php foreach ($targets['users'] as $target): ?>
                                    <span class="badge bg-light text-dark border me-1 mb-1"><?php echo htmlspecialchars($target['display_name']); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- 添付ファイル -->
                    <?php if (!empty($attachments)): ?>
                        <hr>
                        <div class="attachment-list">
                            <strong class="d-block mb-2" style="font-size:0.85rem;"><i class="fas fa-paperclip me-1"></i>添付ファイル</strong>
                            <?php foreach ($attachments as $attach): ?>
                                <a href="<?php echo BASE_PATH; ?>/uploads/bulletin/<?php echo htmlspecialchars($attach['filename']); ?>" target="_blank">
                                    <i class="fas fa-file"></i>
                                    <?php echo htmlspecialchars($attach['original_name']); ?>
                                    <small class="text-muted ms-1">(<?php echo number_format($attach['file_size'] / 1024, 1); ?> KB)</small>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- コメントセクション -->
            <div class="card mb-3">
                <div class="card-header py-2">
                    <strong><i class="far fa-comment me-1"></i>コメント (<?php echo count($comments); ?>)</strong>
                </div>
                <div class="card-body">
                    <?php if (!empty($comments)): ?>
                        <?php foreach ($comments as $comment): ?>
                            <div class="comment-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <span class="comment-user"><?php echo htmlspecialchars($comment['user_name']); ?></span>
                                        <span class="comment-time"><?php echo date('Y/m/d H:i', strtotime($comment['created_at'])); ?></span>
                                    </div>
                                    <?php if ($comment['user_id'] == $this->auth->id() || $isAdmin): ?>
                                        <form method="post" action="<?php echo BASE_PATH; ?>/bulletin/comment/<?php echo $comment['id']; ?>/delete"
                                              class="no-ajax" onsubmit="return confirm('このコメントを削除しますか？');">
                                            <button type="submit" class="btn btn-sm btn-link text-danger p-0" title="削除">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                                <div class="comment-body"><?php echo nl2br(htmlspecialchars($comment['body'])); ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted mb-0 small">コメントはまだありません。</p>
                    <?php endif; ?>

                    <hr>

                    <!-- コメント投稿フォーム -->
                    <form method="post" action="<?php echo BASE_PATH; ?>/bulletin/<?php echo $post['id']; ?>/comment" class="no-ajax">
                        <div class="mb-2">
                            <textarea name="body" class="form-control form-control-sm" rows="3" placeholder="コメントを入力..." required></textarea>
                        </div>
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fas fa-paper-plane me-1"></i>コメントを投稿
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- サイドバー -->
        <div class="col-lg-3">
            <!-- 既読情報 -->
            <div class="card mb-3">
                <div class="card-header py-2">
                    <strong><i class="far fa-eye me-1"></i>既読 (<?php echo count($readers); ?>)</strong>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($readers)): ?>
                        <ul class="list-group list-group-flush" id="readers-list">
                            <?php foreach ($readers as $i => $reader): ?>
                                <li class="list-group-item py-1 px-3 small <?php echo $i >= 5 ? 'd-none reader-hidden' : ''; ?>">
                                    <span><?php echo htmlspecialchars($reader['display_name']); ?></span>
                                    <br><span class="text-muted" style="font-size:0.75rem;"><?php echo date('m/d H:i', strtotime($reader['read_at'])); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php if (count($readers) > 5): ?>
                            <div class="text-center py-2">
                                <span class="readers-toggle" onclick="document.querySelectorAll('.reader-hidden').forEach(e=>e.classList.remove('d-none'));this.remove();">
                                    + 残り<?php echo count($readers) - 5; ?>人を表示
                                </span>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="p-3 text-muted small">まだ誰も閲覧していません。</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 操作 -->
            <div class="card">
                <div class="card-body py-2">
                    <a href="<?php echo BASE_PATH; ?>/bulletin" class="btn btn-outline-secondary btn-sm w-100 mb-2">
                        <i class="fas fa-arrow-left me-1"></i>一覧に戻る
                    </a>
                    <?php if ($canEdit): ?>
                        <a href="<?php echo BASE_PATH; ?>/bulletin/<?php echo $post['id']; ?>/edit" class="btn btn-outline-primary btn-sm w-100 mb-2">
                            <i class="fas fa-edit me-1"></i>編集
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

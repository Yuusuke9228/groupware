<?php
// views/message/starred.php
// スター付きメッセージの一覧画面

// 現在のユーザー情報
$currentUser = $this->auth->user();
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/sidebar.php'; ?>

        <div class="col-md-9 col-lg-10">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">スター付き</h5>
                    <div>
                        <form class="d-inline-flex" action="<?php echo BASE_PATH; ?>/messages/starred" method="get">
                            <div class="input-group input-group-sm">
                                <input type="text" name="search" class="form-control" placeholder="検索..." value="<?php echo htmlspecialchars($filters['search'] ?? ''); ?>">
                                <button class="btn btn-outline-secondary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover message-list mb-0">
                            <thead>
                                <tr>
                                    <th width="40"></th>
                                    <th width="40"></th>
                                    <th width="200">送信者</th>
                                    <th>件名</th>
                                    <th width="160">日時</th>
                                    <th width="80">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($messages)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4">スター付きメッセージがありません</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($messages as $message): ?>
                                        <tr class="<?php echo ($message['is_read'] == 0) ? 'fw-bold' : ''; ?>">
                                            <td class="text-center">
                                                <a href="#" class="btn-toggle-star" data-message-id="<?php echo $message['id']; ?>" data-starred="<?php echo $message['is_starred']; ?>">
                                                    <i class="fas fa-star text-warning"></i>
                                                </a>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($message['thread_count'] > 1): ?>
                                                    <i class="fas fa-comments text-info" title="スレッド (<?php echo $message['thread_count']; ?>件)"></i>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="<?php echo BASE_PATH; ?>/messages/view/<?php echo $message['id']; ?>" class="text-truncate d-block">
                                                    <?php echo htmlspecialchars($message['sender_name']); ?>
                                                </a>
                                            </td>
                                            <td>
                                                <a href="<?php echo BASE_PATH; ?>/messages/view/<?php echo $message['id']; ?>" class="text-truncate d-block">
                                                    <?php echo htmlspecialchars($message['subject']); ?>
                                                </a>
                                            </td>
                                            <td>
                                                <?php echo date('Y/m/d H:i', strtotime($message['created_at'])); ?>
                                            </td>
                                            <td>
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                        操作
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li>
                                                            <a class="dropdown-item" href="<?php echo BASE_PATH; ?>/messages/view/<?php echo $message['id']; ?>">
                                                                <i class="fas fa-eye me-2"></i>詳細
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item" href="<?php echo BASE_PATH; ?>/messages/reply/<?php echo $message['id']; ?>">
                                                                <i class="fas fa-reply me-2"></i>返信
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item" href="<?php echo BASE_PATH; ?>/messages/forward/<?php echo $message['id']; ?>">
                                                                <i class="fas fa-share me-2"></i>転送
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <hr class="dropdown-divider">
                                                        </li>
                                                        <?php if ($message['is_read'] == 0): ?>
                                                            <li>
                                                                <a class="dropdown-item btn-mark-as-read" href="#" data-message-id="<?php echo $message['id']; ?>">
                                                                    <i class="fas fa-check-circle me-2"></i>既読にする
                                                                </a>
                                                            </li>
                                                        <?php else: ?>
                                                            <li>
                                                                <a class="dropdown-item btn-mark-as-unread" href="#" data-message-id="<?php echo $message['id']; ?>">
                                                                    <i class="fas fa-circle me-2"></i>未読にする
                                                                </a>
                                                            </li>
                                                        <?php endif; ?>
                                                        <li>
                                                            <a class="dropdown-item btn-delete-message" href="#" data-message-id="<?php echo $message['id']; ?>">
                                                                <i class="fas fa-trash me-2"></i>削除
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-between">
                    <div>
                        全 <?php echo $totalMessages; ?> 件中 <?php echo count($messages); ?> 件表示
                    </div>
                    <div>
                        <?php if ($totalPages > 1): ?>
                            <nav aria-label="Page navigation">
                                <ul class="pagination pagination-sm mb-0">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="<?php echo BASE_PATH; ?>/messages/starred?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($filters['search'] ?? ''); ?>" aria-label="Previous">
                                                <span aria-hidden="true">&laquo;</span>
                                            </a>
                                        </li>
                                    <?php endif; ?>

                                    <?php
                                    $startPage = max(1, $page - 2);
                                    $endPage = min($totalPages, $page + 2);
                                    ?>

                                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                        <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                            <a class="page-link" href="<?php echo BASE_PATH; ?>/messages/starred?page=<?php echo $i; ?>&search=<?php echo urlencode($filters['search'] ?? ''); ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>

                                    <?php if ($page < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="<?php echo BASE_PATH; ?>/messages/starred?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($filters['search'] ?? ''); ?>" aria-label="Next">
                                                <span aria-hidden="true">&raquo;</span>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
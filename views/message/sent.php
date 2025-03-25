<?php
// views/message/sent.php
// 送信済みメッセージの一覧画面

// 現在のユーザー情報
$currentUser = $this->auth->user();
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/sidebar.php'; ?>

        <div class="col-md-9 col-lg-10">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">送信済み</h5>
                    <div>
                        <form class="d-inline-flex" action="<?php echo BASE_PATH; ?>/messages/sent" method="get">
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
                                    <th width="200">宛先</th>
                                    <th>件名</th>
                                    <th width="90">既読状況</th>
                                    <th width="160">日時</th>
                                    <th width="80">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($messages)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4">送信済みメッセージがありません</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($messages as $message): ?>
                                        <tr>
                                            <td class="text-center">
                                                <?php if ($message['thread_count'] > 1): ?>
                                                    <i class="fas fa-comments text-info" title="スレッド (<?php echo $message['thread_count']; ?>件)"></i>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="<?php echo BASE_PATH; ?>/messages/view/<?php echo $message['id']; ?>" class="text-truncate d-block">
                                                    <?php
                                                    // 複数の宛先の場合は「他○名」と表示
                                                    if ($message['recipient_count'] > 1) {
                                                        echo '複数の宛先 (' . $message['recipient_count'] . '名)';
                                                    } else {
                                                        echo '個人宛て'; // 通常は受信者名を表示したいが、ここではダミー表示
                                                    }
                                                    ?>
                                                </a>
                                            </td>
                                            <td>
                                                <a href="<?php echo BASE_PATH; ?>/messages/view/<?php echo $message['id']; ?>" class="text-truncate d-block">
                                                    <?php echo htmlspecialchars($message['subject']); ?>
                                                </a>
                                            </td>
                                            <td>
                                                <?php if ($message['read_count'] == 0): ?>
                                                    <span class="badge bg-secondary">未読</span>
                                                <?php elseif ($message['read_count'] < $message['recipient_count']): ?>
                                                    <span class="badge bg-warning text-dark">一部既読</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success">全員既読</span>
                                                <?php endif; ?>
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
                                                            <a class="dropdown-item" href="<?php echo BASE_PATH; ?>/messages/forward/<?php echo $message['id']; ?>">
                                                                <i class="fas fa-share me-2"></i>転送
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
                                            <a class="page-link" href="<?php echo BASE_PATH; ?>/messages/sent?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($filters['search'] ?? ''); ?>" aria-label="Previous">
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
                                            <a class="page-link" href="<?php echo BASE_PATH; ?>/messages/sent?page=<?php echo $i; ?>&search=<?php echo urlencode($filters['search'] ?? ''); ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>

                                    <?php if ($page < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="<?php echo BASE_PATH; ?>/messages/sent?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($filters['search'] ?? ''); ?>" aria-label="Next">
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
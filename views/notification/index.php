<!-- views/notification/index.php -->
<div class="container">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3 mb-2">通知一覧</h1>
            <p class="text-muted">システムからのお知らせや更新情報を確認できます。</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="<?= BASE_PATH ?>/notifications/settings" class="btn btn-outline-primary me-2">
                <i class="fas fa-cog"></i> 通知設定
            </a>
            <button id="markAllReadBtn" class="btn btn-outline-secondary">
                <i class="fas fa-check-double"></i> すべて既読
            </button>
        </div>
    </div>

    <!-- フィルター -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <form id="notificationFilterForm" method="get">
                        <div class="row g-3 align-items-center">
                            <div class="col-md-3">
                                <label for="type" class="form-label">通知タイプ</label>
                                <select class="form-select" id="type" name="type">
                                    <option value="">すべて</option>
                                    <option value="schedule" <?= ($filters['type'] ?? '') === 'schedule' ? 'selected' : '' ?>>スケジュール</option>
                                    <option value="workflow" <?= ($filters['type'] ?? '') === 'workflow' ? 'selected' : '' ?>>ワークフロー</option>
                                    <option value="message" <?= ($filters['type'] ?? '') === 'message' ? 'selected' : '' ?>>メッセージ</option>
                                    <option value="system" <?= ($filters['type'] ?? '') === 'system' ? 'selected' : '' ?>>システム</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="is_read" class="form-label">既読状態</label>
                                <select class="form-select" id="is_read" name="is_read">
                                    <option value="">すべて</option>
                                    <option value="0" <?= isset($filters['is_read']) && $filters['is_read'] === false ? 'selected' : '' ?>>未読のみ</option>
                                    <option value="1" <?= isset($filters['is_read']) && $filters['is_read'] === true ? 'selected' : '' ?>>既読のみ</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="search" class="form-label">検索</label>
                                <input type="text" class="form-control" id="search" name="search" value="<?= htmlspecialchars($filters['search'] ?? '') ?>" placeholder="キーワード検索">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">検索</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- 通知リスト -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body p-0">
                    <?php if (empty($notifications)): ?>
                        <div class="text-center p-5">
                            <i class="far fa-bell fa-3x text-muted mb-3"></i>
                            <p class="mb-0">通知はありません。</p>
                        </div>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($notifications as $notification): ?>
                                <li class="list-group-item notification-item <?= $notification['is_read'] ? '' : 'bg-light' ?>" data-id="<?= $notification['id'] ?>">
                                    <div class="d-flex align-items-center">
                                        <!-- 通知アイコン -->
                                        <div class="me-3">
                                            <?php if ($notification['type'] === 'schedule'): ?>
                                                <i class="far fa-calendar-alt fa-2x text-primary"></i>
                                            <?php elseif ($notification['type'] === 'workflow'): ?>
                                                <i class="fas fa-tasks fa-2x text-success"></i>
                                            <?php elseif ($notification['type'] === 'message'): ?>
                                                <i class="far fa-envelope fa-2x text-info"></i>
                                            <?php else: ?>
                                                <i class="fas fa-bell fa-2x text-warning"></i>
                                            <?php endif; ?>
                                        </div>
                                        <!-- 通知内容 -->
                                        <div class="flex-grow-1">
                                            <h5 class="mb-1 <?= $notification['is_read'] ? '' : 'fw-bold' ?>"><?= htmlspecialchars($notification['title']) ?></h5>
                                            <p class="mb-1"><?= nl2br(htmlspecialchars($notification['content'])) ?></p>
                                            <small class="text-muted">
                                                <?= date('Y年m月d日 H:i', strtotime($notification['created_at'])) ?>
                                                <?php if (!$notification['is_read']): ?>
                                                    <span class="badge bg-primary ms-2">未読</span>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                        <!-- リンクボタン -->
                                        <?php if (!empty($notification['link'])): ?>
                                            <div>
                                                <a href="<?= BASE_PATH . $notification['link'] ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-external-link-alt"></i> 詳細
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ページネーション -->
    <?php if ($totalPages > 1): ?>
        <div class="row mt-4">
            <div class="col-md-12">
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= BASE_PATH ?>/notifications?page=<?= $page - 1 ?>&<?= http_build_query(array_filter($filters)) ?>">前へ</a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled">
                                <span class="page-link">前へ</span>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="<?= BASE_PATH ?>/notifications?page=<?= $i ?>&<?= http_build_query(array_filter($filters)) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= BASE_PATH ?>/notifications?page=<?= $page + 1 ?>&<?= http_build_query(array_filter($filters)) ?>">次へ</a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled">
                                <span class="page-link">次へ</span>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        </div>
    <?php endif; ?>
</div>
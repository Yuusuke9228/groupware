<div class="container-fluid pt-4 px-4">
    <div class="row g-4">
        <div class="col-12">
            <div class="bg-light rounded p-4 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="mb-0">日報一覧</h3>
                    <div>
                        <a href="<?= BASE_PATH ?>/daily-report/create" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>新規作成
                        </a>
                        <a href="<?= BASE_PATH ?>/daily-report" class="btn btn-secondary ms-2">
                            <i class="fas fa-home me-2"></i>ダッシュボード
                        </a>
                    </div>
                </div>

                <!-- フィルター -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">検索フィルター</h5>
                    </div>
                    <div class="card-body">
                        <form id="filterForm" method="GET" action="<?= BASE_PATH ?>/daily-report/list">
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label for="start_date" class="form-label">開始日</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?= $filters['start_date'] ?? '' ?>">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="end_date" class="form-label">終了日</label>
                                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?= $filters['end_date'] ?? '' ?>">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="user_id" class="form-label">ユーザー</label>
                                    <select class="form-select" id="user_id" name="user_id">
                                        <option value="">すべてのユーザー</option>
                                        <option value="<?= $this->auth->id() ?>" <?= (isset($filters['user_id']) && $filters['user_id'] == $this->auth->id()) ? 'selected' : '' ?>>自分の日報のみ</option>
                                        <?php foreach ($users as $user): ?>
                                            <?php if ($user['id'] != $this->auth->id()): ?>
                                                <option value="<?= $user['id'] ?>" <?= (isset($filters['user_id']) && $filters['user_id'] == $user['id']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($user['display_name']) ?>
                                                </option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="tag_id" class="form-label">タグ</label>
                                    <select class="form-select" id="tag_id" name="tag_id">
                                        <option value="">すべてのタグ</option>
                                        <?php if (!empty($tags)): ?>
                                            <optgroup label="自分のタグ">
                                                <?php foreach ($tags as $tag): ?>
                                                    <option value="<?= $tag['id'] ?>" <?= (isset($filters['tag_id']) && $filters['tag_id'] == $tag['id']) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($tag['name']) ?>
                                                        <?php if (isset($tag['reports_count']) && $tag['reports_count'] > 0): ?>
                                                            (<?= $tag['reports_count'] ?>)
                                                        <?php endif; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </optgroup>
                                        <?php endif; ?>
                                        <?php if (!empty($publicTags)): ?>
                                            <optgroup label="公開タグ">
                                                <?php foreach ($publicTags as $tag): ?>
                                                    <?php if (!in_array($tag['id'], array_column($tags, 'id'))): ?>
                                                        <option value="<?= $tag['id'] ?>" <?= (isset($filters['tag_id']) && $filters['tag_id'] == $tag['id']) ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($tag['name']) ?> (<?= $tag['reports_count'] ?>)
                                                        </option>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </optgroup>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="search" class="form-label">キーワード</label>
                                    <input type="text" class="form-control" id="search" name="search" placeholder="タイトルや内容を検索" value="<?= $filters['search'] ?? '' ?>">
                                </div>
                                <div class="col-md-6 d-flex align-items-end mb-3">
                                    <button type="submit" class="btn btn-primary me-2">
                                        <i class="fas fa-search me-2"></i>検索
                                    </button>
                                    <button type="button" id="resetFilter" class="btn btn-secondary">
                                        <i class="fas fa-undo me-2"></i>リセット
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- 日報一覧 -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">日報一覧</h5>
                        <div>
                            <span id="totalCount" class="badge bg-primary"><?= $totalReports ?></span> 件
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th>作成者</th>
                                        <th>日付</th>
                                        <th>タイトル</th>
                                        <th>作成日時</th>
                                        <th>いいね</th>
                                        <th>コメント</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($reports)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center">日報がありません</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($reports as $report): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($report['creator_name']) ?></td>
                                                <td><?= date('Y/m/d', strtotime($report['report_date'])) ?></td>
                                                <td>
                                                    <?= htmlspecialchars($report['title']) ?>
                                                    <?php if ($report['status'] == 'draft'): ?>
                                                        <span class="badge bg-warning ms-1">下書き</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= date('Y/m/d H:i', strtotime($report['created_at'])) ?></td>
                                                <td><?= $report['likes_count'] ?></td>
                                                <td><?= $report['comments_count'] ?></td>
                                                <td>
                                                    <a href="<?= BASE_PATH ?>/daily-report/view/<?= $report['id'] ?>" class="btn btn-sm btn-primary">詳細</a>
                                                    <?php if ($report['user_id'] == $this->auth->id()): ?>
                                                        <a href="<?= BASE_PATH ?>/daily-report/edit/<?= $report['id'] ?>" class="btn btn-sm btn-secondary">編集</a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- ページネーション -->
                    <?php if ($totalPages > 1): ?>
                        <div class="card-footer">
                            <nav>
                                <ul class="pagination justify-content-center mb-0">
                                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                        <a class="page-link" href="<?= BASE_PATH ?>/daily-report/list?<?= http_build_query(array_merge($filters, ['page' => $page - 1])) ?>">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </li>

                                    <?php
                                    $startPage = max(1, $page - 2);
                                    $endPage = min($totalPages, $page + 2);

                                    if ($startPage > 1) {
                                        echo '<li class="page-item"><a class="page-link" href="' . BASE_PATH . '/daily-report/list?' . http_build_query(array_merge($filters, ['page' => 1])) . '">1</a></li>';
                                        if ($startPage > 2) {
                                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                        }
                                    }

                                    for ($i = $startPage; $i <= $endPage; $i++) {
                                        echo '<li class="page-item ' . ($page == $i ? 'active' : '') . '">
                                            <a class="page-link" href="' . BASE_PATH . '/daily-report/list?' . http_build_query(array_merge($filters, ['page' => $i])) . '">' . $i . '</a>
                                        </li>';
                                    }

                                    if ($endPage < $totalPages) {
                                        if ($endPage < $totalPages - 1) {
                                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                        }
                                        echo '<li class="page-item"><a class="page-link" href="' . BASE_PATH . '/daily-report/list?' . http_build_query(array_merge($filters, ['page' => $totalPages])) . '">' . $totalPages . '</a></li>';
                                    }
                                    ?>

                                    <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                                        <a class="page-link" href="<?= BASE_PATH ?>/daily-report/list?<?= http_build_query(array_merge($filters, ['page' => $page + 1])) ?>">
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
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // フィルターのリセットボタン
        document.getElementById('resetFilter').addEventListener('click', function() {
            document.getElementById('start_date').value = '';
            document.getElementById('end_date').value = '';
            document.getElementById('user_id').value = '';
            document.getElementById('tag_id').value = '';
            document.getElementById('search').value = '';
            document.getElementById('filterForm').submit();
        });

        // ユーザーIDが指定されている場合、APIで総数を取得
        <?php if (empty($totalReports) && isset($filters['user_id']) && $filters['user_id'] != $this->auth->id()): ?>
            fetchTotalCount();
        <?php endif; ?>

        function fetchTotalCount() {
            const filters = {
                <?php foreach ($filters as $key => $value): ?>
                    <?php if (!empty($value)): ?>
                        <?= $key ?>: '<?= $value ?>',
                    <?php endif; ?>
                <?php endforeach; ?>
            };

            // APIで総数を取得
            fetch(`${BASE_PATH}/api/daily-report/count?${new URLSearchParams(filters)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('totalCount').textContent = data.data.count;
                    }
                })
                .catch(error => {
                    console.error('Error fetching total count:', error);
                });
        }
    });
</script>
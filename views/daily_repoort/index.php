<div class="container-fluid pt-4 px-4">
    <div class="row g-4">
        <div class="col-12">
            <div class="bg-light rounded p-4 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="mb-0">日報ダッシュボード</h3>
                    <div>
                        <a href="<?= BASE_PATH ?>/daily-report/create" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>新規作成
                        </a>
                        <a href="<?= BASE_PATH ?>/daily-report/list" class="btn btn-secondary ms-2">
                            <i class="fas fa-list me-2"></i>一覧表示
                        </a>
                    </div>
                </div>

                <!-- 本日の日報 -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card border-primary">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">本日の日報</h5>
                            </div>
                            <div class="card-body">
                                <?php if ($has_today_report): ?>
                                    <h5 class="card-title"><?= htmlspecialchars($today_report['title']) ?></h5>
                                    <p class="card-text">
                                        <?= nl2br(htmlspecialchars(mb_substr($today_report['content'], 0, 200))) ?>
                                        <?php if (mb_strlen($today_report['content']) > 200): ?>
                                            ...
                                        <?php endif; ?>
                                    </p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <small class="text-muted">
                                                <i class="far fa-clock me-1"></i><?= date('H:i', strtotime($today_report['created_at'])) ?>
                                                <i class="far fa-comment ms-2 me-1"></i><?= $today_report['comments_count'] ?>
                                                <i class="far fa-thumbs-up ms-2 me-1"></i><?= $today_report['likes_count'] ?>
                                            </small>
                                        </div>
                                        <a href="<?= BASE_PATH ?>/daily-report/view/<?= $today_report['id'] ?>" class="btn btn-sm btn-primary">詳細</a>
                                    </div>
                                <?php else: ?>
                                    <p class="text-center my-4">本日の日報はまだ作成されていません。</p>
                                    <div class="text-center">
                                        <a href="<?= BASE_PATH ?>/daily-report/create?date=<?= date('Y-m-d') ?>" class="btn btn-primary">
                                            <i class="fas fa-plus me-2"></i>作成する
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- 最近の日報 -->
                    <div class="col-lg-8 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">最近の日報</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-striped mb-0">
                                        <thead>
                                            <tr>
                                                <th>日付</th>
                                                <th>タイトル</th>
                                                <th>作成日時</th>
                                                <th>操作</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($recent_reports)): ?>
                                                <tr>
                                                    <td colspan="4" class="text-center">日報がありません</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($recent_reports as $report): ?>
                                                    <tr>
                                                        <td><?= date('Y/m/d', strtotime($report['report_date'])) ?></td>
                                                        <td><?= htmlspecialchars($report['title']) ?></td>
                                                        <td><?= date('Y/m/d H:i', strtotime($report['created_at'])) ?></td>
                                                        <td>
                                                            <a href="<?= BASE_PATH ?>/daily-report/view/<?= $report['id'] ?>" class="btn btn-sm btn-primary">詳細</a>
                                                            <a href="<?= BASE_PATH ?>/daily-report/edit/<?= $report['id'] ?>" class="btn btn-sm btn-secondary">編集</a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="card-footer text-center">
                                <a href="<?= BASE_PATH ?>/daily-report/list" class="btn btn-sm btn-secondary">すべての日報を見る</a>
                            </div>
                        </div>
                    </div>

                    <!-- 統計情報 -->
                    <div class="col-lg-4 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">日報統計</h5>
                            </div>
                            <div class="card-body">
                                <div class="text-center mb-4">
                                    <h1 class="display-4"><?= number_format($stats['total_reports']) ?></h1>
                                    <p class="text-muted">過去30日間の日報数</p>
                                </div>
                                <div class="row text-center">
                                    <div class="col-6">
                                        <h4><?= number_format($stats['total_likes']) ?></h4>
                                        <small class="text-muted">いいね数</small>
                                    </div>
                                    <div class="col-6">
                                        <h4><?= number_format($stats['total_comments']) ?></h4>
                                        <small class="text-muted">コメント数</small>
                                    </div>
                                </div>
                                <hr>
                                <div class="text-center mt-3">
                                    <a href="<?= BASE_PATH ?>/daily-report/stats" class="btn btn-sm btn-outline-primary">詳細な統計を見る</a>
                                </div>
                            </div>
                        </div>

                        <!-- タグクラウド -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h5 class="mb-0">タグクラウド</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($tags)): ?>
                                    <p class="text-center">タグがありません</p>
                                <?php else: ?>
                                    <div class="d-flex flex-wrap gap-2">
                                        <?php foreach ($tags as $tag): ?>
                                            <a href="<?= BASE_PATH ?>/daily-report/list?tag_id=<?= $tag['id'] ?>" class="badge bg-primary text-decoration-none">
                                                <?= htmlspecialchars($tag['name']) ?>
                                                <?php if (isset($tag['reports_count']) && $tag['reports_count'] > 0): ?>
                                                    <span class="badge bg-light text-dark"><?= $tag['reports_count'] ?></span>
                                                <?php endif; ?>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 未読の日報 -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">未読の日報</h5>
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
                                                <th>操作</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($unread_reports)): ?>
                                                <tr>
                                                    <td colspan="5" class="text-center">未読の日報はありません</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($unread_reports as $report): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($report['creator_name']) ?></td>
                                                        <td><?= date('Y/m/d', strtotime($report['report_date'])) ?></td>
                                                        <td><?= htmlspecialchars($report['title']) ?></td>
                                                        <td><?= date('Y/m/d H:i', strtotime($report['created_at'])) ?></td>
                                                        <td>
                                                            <a href="<?= BASE_PATH ?>/daily-report/view/<?= $report['id'] ?>" class="btn btn-sm btn-primary">詳細</a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="card-footer text-center">
                                <a href="<?= BASE_PATH ?>/daily-report/list" class="btn btn-sm btn-secondary">すべての日報を見る</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- テンプレート -->
<div class="modal fade" id="templateModal" tabindex="-1" aria-labelledby="templateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="templateModalLabel">テンプレートを選択</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="list-group">
                    <?php if (empty($templates)): ?>
                        <p class="text-center">テンプレートがありません</p>
                    <?php else: ?>
                        <?php foreach ($templates as $template): ?>
                            <a href="<?= BASE_PATH ?>/daily-report/create?template_id=<?= $template['id'] ?>" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h5 class="mb-1"><?= htmlspecialchars($template['title']) ?></h5>
                                    <small><?= $template['is_public'] ? '公開' : '個人用' ?></small>
                                </div>
                                <p class="mb-1">
                                    <?= htmlspecialchars(mb_substr($template['content'], 0, 100)) ?>
                                    <?php if (mb_strlen($template['content']) > 100): ?>
                                        ...
                                    <?php endif; ?>
                                </p>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
                <a href="<?= BASE_PATH ?>/daily-report/templates" class="btn btn-primary">テンプレート管理</a>
            </div>
        </div>
    </div>
</div>

<script>
    // ここに日報ダッシュボード用のJavaScriptを記述
    document.addEventListener('DOMContentLoaded', function() {
        // 必要に応じてJavaScriptのコードを追加
    });
</script>
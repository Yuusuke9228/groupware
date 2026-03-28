<?php
$baseTs = strtotime($baseDate ?? date('Y-m-d'));
$prevWeek = date('Y-m-d', strtotime('-7 day', $baseTs));
$nextWeek = date('Y-m-d', strtotime('+7 day', $baseTs));
?>
<div class="container-fluid pt-4 px-4">
    <div class="row g-4">
        <div class="col-12">
            <div class="bg-light rounded p-4 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <h3 class="mb-0">日報週間</h3>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="<?= BASE_PATH ?>/daily-report" class="btn btn-outline-secondary btn-sm">ダッシュボード</a>
                        <a href="<?= BASE_PATH ?>/daily-report/timeline" class="btn btn-outline-secondary btn-sm">タイムライン</a>
                        <a href="<?= BASE_PATH ?>/daily-report/month" class="btn btn-outline-secondary btn-sm">月間</a>
                        <a href="<?= BASE_PATH ?>/daily-report/list" class="btn btn-outline-secondary btn-sm">一覧</a>
                        <a href="<?= BASE_PATH ?>/daily-report/create" class="btn btn-primary btn-sm">新規作成</a>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <a class="btn btn-outline-primary btn-sm" href="<?= BASE_PATH ?>/daily-report/week?date=<?= $prevWeek ?>"><i class="fas fa-chevron-left me-1"></i>前週</a>
                    <strong><?= date('Y/m/d', strtotime($weekStart)) ?> - <?= date('Y/m/d', strtotime($weekEnd)) ?></strong>
                    <a class="btn btn-outline-primary btn-sm" href="<?= BASE_PATH ?>/daily-report/week?date=<?= $nextWeek ?>">翌週<i class="fas fa-chevron-right ms-1"></i></a>
                </div>

                <div class="row g-3">
                    <?php foreach ($reportsByDate as $day => $items): ?>
                        <div class="col-lg-3 col-md-4 col-sm-6">
                            <div class="card h-100">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?= date('m/d', strtotime($day)) ?></strong>
                                        <div class="small text-muted"><?= ['日','月','火','水','木','金','土'][(int)date('w', strtotime($day))] ?>曜日</div>
                                    </div>
                                    <a href="<?= BASE_PATH ?>/daily-report/create?date=<?= $day ?>" class="btn btn-sm btn-outline-primary">作成</a>
                                </div>
                                <div class="card-body p-2">
                                    <?php if (empty($items)): ?>
                                        <p class="text-muted small mb-0">日報なし</p>
                                    <?php else: ?>
                                        <div class="list-group list-group-flush">
                                            <?php foreach ($items as $report): ?>
                                                <a href="<?= BASE_PATH ?>/daily-report/view/<?= $report['id'] ?>" class="list-group-item list-group-item-action py-2">
                                                    <div class="small fw-bold"><?= htmlspecialchars($report['title']) ?></div>
                                                    <div class="small text-muted"><?= htmlspecialchars($report['creator_name']) ?></div>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

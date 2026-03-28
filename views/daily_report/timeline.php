<div class="container-fluid pt-4 px-4">
    <div class="row g-4">
        <div class="col-12">
            <div class="bg-light rounded p-4 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <h3 class="mb-0">日報タイムライン</h3>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="<?= BASE_PATH ?>/daily-report" class="btn btn-outline-secondary btn-sm">ダッシュボード</a>
                        <a href="<?= BASE_PATH ?>/daily-report/week" class="btn btn-outline-secondary btn-sm">週間</a>
                        <a href="<?= BASE_PATH ?>/daily-report/month" class="btn btn-outline-secondary btn-sm">月間</a>
                        <a href="<?= BASE_PATH ?>/daily-report/list" class="btn btn-outline-secondary btn-sm">一覧</a>
                        <a href="<?= BASE_PATH ?>/daily-report/create?date=<?= urlencode($date) ?>" class="btn btn-primary btn-sm">この日の日報を作成</a>
                    </div>
                </div>

                <form class="row g-2 mb-4" method="GET" action="<?= BASE_PATH ?>/daily-report/timeline">
                    <div class="col-md-3">
                        <input type="date" class="form-control" name="date" value="<?= htmlspecialchars($date) ?>">
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" name="user_id">
                            <option value="">すべてのユーザー</option>
                            <?php foreach ($users as $u): ?>
                                <option value="<?= (int)$u['id'] ?>" <?= (string)$selectedUserId === (string)$u['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($u['display_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">表示</button>
                    </div>
                </form>

                <?php if (empty($reports)): ?>
                    <div class="alert alert-secondary mb-0">該当日の日報はありません。</div>
                <?php else: ?>
                    <div class="timeline-list">
                        <?php foreach ($reports as $report): ?>
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h5 class="mb-1"><a href="<?= BASE_PATH ?>/daily-report/view/<?= $report['id'] ?>" class="text-decoration-none"><?= htmlspecialchars($report['title']) ?></a></h5>
                                            <div class="small text-muted">
                                                <?= htmlspecialchars($report['creator_name']) ?>
                                                ・<?= date('Y/m/d H:i', strtotime($report['created_at'])) ?>
                                            </div>
                                        </div>
                                        <div class="text-end small text-muted">
                                            <div>いいね <?= (int)$report['likes_count'] ?></div>
                                            <div>コメント <?= (int)$report['comments_count'] ?></div>
                                        </div>
                                    </div>
                                    <div class="small">
                                        <?= nl2br(htmlspecialchars(mb_substr((string)$report['content'], 0, 180))) ?>
                                        <?php if (mb_strlen((string)$report['content']) > 180): ?>...
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$isAdmin = $this->auth->isAdmin();
$masters = $analysisMasters ?? ['industries' => [], 'products' => [], 'processes' => [], 'projects' => []];
$filters = $filters ?? [];
$queryForExport = http_build_query([
    'user_id' => $filters['user_id'] ?? null,
    'start_date' => $filters['start_date'] ?? null,
    'end_date' => $filters['end_date'] ?? null,
    'project_id' => $filters['project_id'] ?? null,
    'industry_id' => $filters['industry_id'] ?? null,
    'product_id' => $filters['product_id'] ?? null,
    'process_id' => $filters['process_id'] ?? null
]);
?>

<div class="container-fluid pt-4 px-4">
    <div class="row g-4">
        <div class="col-12">
            <div class="bg-light rounded p-4 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <h3 class="mb-0">日報分析・予実管理</h3>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="<?= BASE_PATH ?>/daily-report" class="btn btn-outline-secondary btn-sm">ダッシュボード</a>
                        <a href="<?= BASE_PATH ?>/daily-report/list" class="btn btn-outline-secondary btn-sm">一覧</a>
                        <a href="<?= BASE_PATH ?>/api/daily-report/export-csv?<?= htmlspecialchars($queryForExport) ?>" class="btn btn-success btn-sm">
                            <i class="fas fa-file-csv me-1"></i>CSV出力
                        </a>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header"><strong>分析フィルタ</strong></div>
                    <div class="card-body">
                        <form method="GET" action="<?= BASE_PATH ?>/daily-report/analysis" class="row g-3">
                            <?php if ($isAdmin): ?>
                                <div class="col-md-3">
                                    <label class="form-label">ユーザー</label>
                                    <select name="user_id" class="form-select">
                                        <?php foreach (($users ?? []) as $u): ?>
                                            <option value="<?= (int)$u['id'] ?>" <?= (int)($selectedUserId ?? 0) === (int)$u['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($u['display_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php endif; ?>
                            <div class="col-md-3">
                                <label class="form-label">開始日</label>
                                <input type="date" class="form-control" name="start_date" value="<?= htmlspecialchars($filters['start_date'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">終了日</label>
                                <input type="date" class="form-control" name="end_date" value="<?= htmlspecialchars($filters['end_date'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">案件</label>
                                <select name="project_id" class="form-select">
                                    <option value="">すべて</option>
                                    <?php foreach (($masters['projects'] ?? []) as $item): ?>
                                        <option value="<?= (int)$item['id'] ?>" <?= (string)($filters['project_id'] ?? '') === (string)$item['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($item['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">業種</label>
                                <select name="industry_id" class="form-select">
                                    <option value="">すべて</option>
                                    <?php foreach (($masters['industries'] ?? []) as $item): ?>
                                        <option value="<?= (int)$item['id'] ?>" <?= (string)($filters['industry_id'] ?? '') === (string)$item['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($item['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">商品</label>
                                <select name="product_id" class="form-select">
                                    <option value="">すべて</option>
                                    <?php foreach (($masters['products'] ?? []) as $item): ?>
                                        <option value="<?= (int)$item['id'] ?>" <?= (string)($filters['product_id'] ?? '') === (string)$item['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($item['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">プロセス</label>
                                <select name="process_id" class="form-select">
                                    <option value="">すべて</option>
                                    <?php foreach (($masters['processes'] ?? []) as $item): ?>
                                        <option value="<?= (int)$item['id'] ?>" <?= (string)($filters['process_id'] ?? '') === (string)$item['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($item['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">適用</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="card border-primary h-100">
                            <div class="card-body">
                                <div class="text-muted small">計画金額</div>
                                <div class="fs-4 fw-bold">¥<?= number_format((float)($summary['planned_amount_total'] ?? 0), 2) ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-success h-100">
                            <div class="card-body">
                                <div class="text-muted small">実績金額</div>
                                <div class="fs-4 fw-bold">¥<?= number_format((float)($summary['actual_amount_total'] ?? 0), 2) ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-info h-100">
                            <div class="card-body">
                                <div class="text-muted small">計画時間 / 実績時間</div>
                                <div class="fs-5 fw-bold"><?= number_format((float)($summary['planned_hours_total'] ?? 0), 2) ?>h / <?= number_format((float)($summary['actual_hours_total'] ?? 0), 2) ?>h</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-secondary h-100">
                            <div class="card-body">
                                <div class="text-muted small">分析対象日報数</div>
                                <div class="fs-4 fw-bold"><?= number_format((int)($summary['reports_count'] ?? 0)) ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header"><strong>月次推移</strong></div>
                    <div class="card-body">
                        <canvas id="analysisTrendChart" height="110"></canvas>
                    </div>
                </div>

                <div class="row g-4 mb-4">
                    <?php
                    $breakdownList = [
                        '案件別集計' => $projectBreakdown ?? [],
                        '業種別集計' => $industryBreakdown ?? [],
                        '商品別集計' => $productBreakdown ?? [],
                        'プロセス別集計' => $processBreakdown ?? []
                    ];
                    ?>
                    <?php foreach ($breakdownList as $title => $rows): ?>
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header"><strong><?= htmlspecialchars($title) ?></strong></div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-striped mb-0">
                                            <thead>
                                                <tr>
                                                    <th>軸</th>
                                                    <th class="text-end">計画</th>
                                                    <th class="text-end">実績</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($rows)): ?>
                                                    <tr><td colspan="3" class="text-center text-muted">データなし</td></tr>
                                                <?php else: ?>
                                                    <?php foreach ($rows as $r): ?>
                                                        <tr>
                                                            <td><?= htmlspecialchars($r['axis_name']) ?></td>
                                                            <td class="text-end"><?= number_format((float)$r['planned_amount_total'], 2) ?></td>
                                                            <td class="text-end"><?= number_format((float)$r['actual_amount_total'], 2) ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="card mb-4">
                    <div class="card-header"><strong>月目標（予実管理）</strong></div>
                    <div class="card-body">
                        <form id="monthlyTargetForm" class="row g-3">
                            <input type="hidden" name="user_id" value="<?= (int)($selectedUserId ?? $this->auth->id()) ?>">
                            <div class="col-md-2">
                                <label class="form-label">対象月</label>
                                <input type="month" class="form-control" name="target_month" value="<?= htmlspecialchars($targetMonth ?? date('Y-m')) ?>" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">案件</label>
                                <select class="form-select" name="project_id">
                                    <option value="">未指定</option>
                                    <?php foreach (($masters['projects'] ?? []) as $item): ?>
                                        <option value="<?= (int)$item['id'] ?>"><?= htmlspecialchars($item['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">業種</label>
                                <select class="form-select" name="industry_id">
                                    <option value="">未指定</option>
                                    <?php foreach (($masters['industries'] ?? []) as $item): ?>
                                        <option value="<?= (int)$item['id'] ?>"><?= htmlspecialchars($item['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">商品</label>
                                <select class="form-select" name="product_id">
                                    <option value="">未指定</option>
                                    <?php foreach (($masters['products'] ?? []) as $item): ?>
                                        <option value="<?= (int)$item['id'] ?>"><?= htmlspecialchars($item['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">プロセス</label>
                                <select class="form-select" name="process_id">
                                    <option value="">未指定</option>
                                    <?php foreach (($masters['processes'] ?? []) as $item): ?>
                                        <option value="<?= (int)$item['id'] ?>"><?= htmlspecialchars($item['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">目標金額</label>
                                <input type="number" step="0.01" min="0" class="form-control" name="target_amount" value="0">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">目標時間</label>
                                <input type="number" step="0.01" min="0" class="form-control" name="target_hours" value="0">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">目標数量</label>
                                <input type="number" step="0.01" min="0" class="form-control" name="target_quantity" value="0">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">メモ</label>
                                <input type="text" class="form-control" name="memo" maxlength="255">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">目標保存</button>
                            </div>
                        </form>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>対象月</th>
                                    <th>軸</th>
                                    <th class="text-end">目標金額</th>
                                    <th class="text-end">実績金額</th>
                                    <th class="text-end">目標時間</th>
                                    <th class="text-end">実績時間</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($targetVsActual)): ?>
                                    <tr><td colspan="7" class="text-center text-muted">対象月の目標は未登録です。</td></tr>
                                <?php else: ?>
                                    <?php foreach ($targetVsActual as $row): ?>
                                        <?php $t = $row['target']; $a = $row['actual']; ?>
                                        <tr>
                                            <td><?= htmlspecialchars($t['target_month']) ?></td>
                                            <td>
                                                <?= htmlspecialchars($t['project_name'] ?: '案件未指定') ?> /
                                                <?= htmlspecialchars($t['industry_name'] ?: '業種未指定') ?> /
                                                <?= htmlspecialchars($t['product_name'] ?: '商品未指定') ?> /
                                                <?= htmlspecialchars($t['process_name'] ?: 'プロセス未指定') ?>
                                            </td>
                                            <td class="text-end"><?= number_format((float)$t['target_amount'], 2) ?></td>
                                            <td class="text-end"><?= number_format((float)$a['actual_amount_total'], 2) ?></td>
                                            <td class="text-end"><?= number_format((float)$t['target_hours'], 2) ?></td>
                                            <td class="text-end"><?= number_format((float)$a['actual_hours_total'], 2) ?></td>
                                            <td>
                                                <button type="button" class="btn btn-outline-danger btn-sm delete-target-btn" data-id="<?= (int)$t['id'] ?>">削除</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <?php if ($isAdmin): ?>
                    <div class="card">
                        <div class="card-header"><strong>分析マスタ管理</strong></div>
                        <div class="card-body">
                            <?php
                            $masterMap = [
                                'industry' => ['title' => '業種', 'rows' => $masters['industries'] ?? []],
                                'product' => ['title' => '商品', 'rows' => $masters['products'] ?? []],
                                'process' => ['title' => 'プロセス', 'rows' => $masters['processes'] ?? []],
                                'project' => ['title' => '案件', 'rows' => $masters['projects'] ?? []]
                            ];
                            ?>
                            <?php foreach ($masterMap as $type => $meta): ?>
                                <div class="border rounded p-3 mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h6 class="mb-0"><?= htmlspecialchars($meta['title']) ?>マスタ</h6>
                                    </div>
                                    <form class="row g-2 master-save-form" data-type="<?= htmlspecialchars($type) ?>">
                                        <div class="col-md-2"><input type="text" class="form-control form-control-sm" name="code" placeholder="コード"></div>
                                        <div class="col-md-3"><input type="text" class="form-control form-control-sm" name="name" placeholder="名称" required></div>
                                        <div class="col-md-2"><input type="number" class="form-control form-control-sm" name="sort_order" min="1" value="1"></div>
                                        <?php if (in_array($type, ['product', 'project'], true)): ?>
                                            <div class="col-md-2">
                                                <select class="form-select form-select-sm" name="industry_id">
                                                    <option value="">業種</option>
                                                    <?php foreach (($masters['industries'] ?? []) as $item): ?>
                                                        <option value="<?= (int)$item['id'] ?>"><?= htmlspecialchars($item['name']) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($type === 'project'): ?>
                                            <div class="col-md-2">
                                                <select class="form-select form-select-sm" name="product_id">
                                                    <option value="">商品</option>
                                                    <?php foreach (($masters['products'] ?? []) as $item): ?>
                                                        <option value="<?= (int)$item['id'] ?>"><?= htmlspecialchars($item['name']) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <select class="form-select form-select-sm" name="process_id">
                                                    <option value="">プロセス</option>
                                                    <?php foreach (($masters['processes'] ?? []) as $item): ?>
                                                        <option value="<?= (int)$item['id'] ?>"><?= htmlspecialchars($item['name']) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        <?php endif; ?>
                                        <div class="col-md-2 d-flex align-items-center">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
                                                <label class="form-check-label">有効</label>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <button type="submit" class="btn btn-primary btn-sm w-100">追加</button>
                                        </div>
                                    </form>
                                    <div class="table-responsive mt-2">
                                        <table class="table table-sm mb-0">
                                            <thead><tr><th>ID</th><th>名称</th><th>状態</th><th></th></tr></thead>
                                            <tbody>
                                                <?php if (empty($meta['rows'])): ?>
                                                    <tr><td colspan="4" class="text-muted text-center">未登録</td></tr>
                                                <?php else: ?>
                                                    <?php foreach ($meta['rows'] as $row): ?>
                                                        <tr>
                                                            <td><?= (int)$row['id'] ?></td>
                                                            <td><?= htmlspecialchars($row['name']) ?></td>
                                                            <td><?= !empty($row['is_active']) ? '有効' : '無効' ?></td>
                                                            <td><button type="button" class="btn btn-outline-danger btn-sm master-delete-btn" data-type="<?= htmlspecialchars($type) ?>" data-id="<?= (int)$row['id'] ?>">削除</button></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
window.dailyReportAnalysisTrend = <?= json_encode($monthlyTrend ?? []) ?>;

document.addEventListener('DOMContentLoaded', function () {
    if (typeof Chart !== 'undefined') {
        const trend = window.dailyReportAnalysisTrend || [];
        const labels = trend.map(x => x.target_month);
        const planned = trend.map(x => Number(x.planned_amount_total || 0));
        const actual = trend.map(x => Number(x.actual_amount_total || 0));
        const el = document.getElementById('analysisTrendChart');
        if (el) {
            new Chart(el.getContext('2d'), {
                type: 'line',
                data: {
                    labels,
                    datasets: [
                        {label: '計画金額', data: planned, borderColor: '#4f7eff', backgroundColor: 'rgba(79,126,255,0.15)', tension: 0.2},
                        {label: '実績金額', data: actual, borderColor: '#00a86b', backgroundColor: 'rgba(0,168,107,0.15)', tension: 0.2}
                    ]
                }
            });
        }
    }

    const monthlyTargetForm = document.getElementById('monthlyTargetForm');
    if (monthlyTargetForm) {
        monthlyTargetForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const payload = Object.fromEntries(new FormData(monthlyTargetForm).entries());
            fetch(`${BASE_PATH}/api/daily-report/targets`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
                body: JSON.stringify(payload)
            }).then(r => r.json()).then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.error || '保存に失敗しました');
                }
            }).catch(() => alert('通信エラーが発生しました'));
        });
    }

    document.querySelectorAll('.delete-target-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.dataset.id;
            if (!id || !confirm('この目標を削除しますか？')) return;
            fetch(`${BASE_PATH}/api/daily-report/targets/${id}`, {method: 'DELETE', headers: {'X-Requested-With': 'XMLHttpRequest'}})
                .then(r => r.json()).then(data => {
                    if (data.success) location.reload();
                    else alert(data.error || '削除に失敗しました');
                }).catch(() => alert('通信エラーが発生しました'));
        });
    });

    document.querySelectorAll('.master-save-form').forEach(form => {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const type = form.dataset.type;
            const payload = Object.fromEntries(new FormData(form).entries());
            if (!payload.is_active) payload.is_active = 0;
            fetch(`${BASE_PATH}/api/daily-report/master/${type}`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
                body: JSON.stringify(payload)
            }).then(r => r.json()).then(data => {
                if (data.success) location.reload();
                else alert(data.error || '保存に失敗しました');
            }).catch(() => alert('通信エラーが発生しました'));
        });
    });

    document.querySelectorAll('.master-delete-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const type = this.dataset.type;
            const id = this.dataset.id;
            if (!type || !id || !confirm('削除しますか？')) return;
            fetch(`${BASE_PATH}/api/daily-report/master/${type}/${id}`, {
                method: 'DELETE',
                headers: {'X-Requested-With': 'XMLHttpRequest'}
            }).then(r => r.json()).then(data => {
                if (data.success) location.reload();
                else alert(data.error || '削除に失敗しました');
            }).catch(() => alert('通信エラーが発生しました'));
        });
    });
});
</script>

<div class="container-fluid mt-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">全文検索</h4>
    </div>

    <form method="get" action="<?php echo BASE_PATH; ?>/search" class="mb-4 no-ajax">
        <div class="input-group input-group-lg">
            <input type="text" name="q" class="form-control" placeholder="メッセージ・ワークフロー・予定・タスクを検索" value="<?php echo htmlspecialchars($query ?? ''); ?>">
            <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i> 検索</button>
        </div>
    </form>

    <?php if (!empty($query)): ?>
        <div class="alert alert-light border">「<?php echo htmlspecialchars($query); ?>」の検索結果: <strong><?php echo (int)($results['total'] ?? 0); ?></strong> 件</div>

        <?php
        $sections = [
            'messages' => 'メッセージ',
            'workflow' => 'ワークフロー',
            'schedules' => '予定',
            'tasks' => 'タスク'
        ];
        ?>

        <?php foreach ($sections as $key => $label): ?>
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong><?php echo $label; ?></strong>
                    <span class="badge bg-secondary"><?php echo count($results[$key] ?? []); ?></span>
                </div>
                <div class="list-group list-group-flush">
                    <?php if (empty($results[$key])): ?>
                        <div class="list-group-item text-muted">該当なし</div>
                    <?php else: ?>
                        <?php foreach ($results[$key] as $item): ?>
                            <a class="list-group-item list-group-item-action" href="<?php echo BASE_PATH . ($item['link'] ?? '#'); ?>">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($item['title'] ?? '(無題)'); ?></h6>
                                    <small class="text-muted"><?php echo !empty($item['created_at']) ? date('Y/m/d H:i', strtotime($item['created_at'])) : ''; ?></small>
                                </div>
                                <small class="text-muted"><?php echo htmlspecialchars($item['snippet'] ?? ''); ?></small>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

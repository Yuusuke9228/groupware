<div class="container-fluid mt-3">
    <div class="row mb-3">
        <div class="col-md-8">
            <div class="d-flex justify-content-between align-items-center">
                <h4>組織タスクボード</h4>
                <div>
                    <a href="<?php echo BASE_PATH; ?>/task/create-board" class="btn btn-primary">
                        <i class="fas fa-plus-circle"></i> 新規ボード作成
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <?php if (empty($organizations)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i> 所属している組織がありません。
                </div>
            <?php else: ?>
                <?php if (empty($orgBoards)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> 利用できる組織ボードがありません。新しいボードを作成してください。
                    </div>
                <?php else: ?>
                    <?php foreach ($orgBoards as $orgId => $orgData): ?>
                        <div class="card shadow-sm mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-building me-2"></i>
                                    <?php echo htmlspecialchars($orgData['organization']['name']); ?>
                                </h5>
                                <a href="<?php echo BASE_PATH; ?>/task/create-board?type=organization&id=<?php echo $orgId; ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-plus"></i> ボード追加
                                </a>
                            </div>
                            <div class="card-body">
                                <div class="row row-cols-1 row-cols-md-3 g-4">
                                    <?php foreach ($orgData['boards'] as $board): ?>
                                        <div class="col">
                                            <div class="card h-100">
                                                <div class="card-body" style="background-color: <?php echo $board['background_color']; ?>10;">
                                                    <h5 class="card-title">
                                                        <a href="<?php echo BASE_PATH; ?>/task/board/<?php echo $board['id']; ?>" class="text-decoration-none">
                                                            <?php echo htmlspecialchars($board['name']); ?>
                                                        </a>
                                                    </h5>
                                                    <p class="card-text small text-muted">
                                                        <?php
                                                        $description = $board['description'] ?? '';
                                                        echo htmlspecialchars(mb_substr($description, 0, 50) . (mb_strlen($description) > 50 ? '...' : ''));
                                                        ?>
                                                    </p>
                                                </div>
                                                <div class="card-footer bg-transparent d-flex justify-content-between align-items-center">
                                                    <small class="text-muted">
                                                        <?php echo date('Y/m/d H:i', strtotime($board['created_at'])); ?>
                                                    </small>
                                                    <?php if ($board['is_public']): ?>
                                                        <span class="badge bg-info">公開</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">組織ボードについて</h5>
                </div>
                <div class="card-body">
                    <p>組織ボードは、同じ組織に所属するメンバー全員が利用できるタスクボードです。プロジェクトやチームの進捗管理に活用できます。</p>
                    <h6 class="mt-3">ボードの種類</h6>
                    <ul class="list-unstyled">
                        <li><span class="badge bg-info me-2">公開</span> 組織メンバー全員が閲覧・編集できます</li>
                        <li class="mt-2"><span class="badge bg-secondary me-2">非公開</span> 指定したメンバーのみ閲覧・編集できます</li>
                    </ul>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">所属組織一覧</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php if (empty($organizations)): ?>
                            <div class="list-group-item text-center py-3">所属組織がありません</div>
                        <?php else: ?>
                            <?php foreach ($organizations as $org): ?>
                                <a href="<?php echo BASE_PATH; ?>/organizations/view/<?php echo $org['id']; ?>" class="list-group-item list-group-item-action">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <i class="fas fa-building me-2"></i>
                                            <?php echo htmlspecialchars($org['name']); ?>
                                            <?php if (isset($org['is_primary']) && $org['is_primary']): ?>
                                                <span class="badge bg-primary ms-2">主組織</span>
                                            <?php endif; ?>
                                        </div>
                                        <?php
                                        // ボード数の代わりに仮の数字を表示
                                        $boardCount = isset($orgBoards[$org['id']]) ? count($orgBoards[$org['id']]['boards']) : 0;
                                        ?>
                                        <span class="badge bg-info"><?php echo $boardCount; ?> ボード</span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
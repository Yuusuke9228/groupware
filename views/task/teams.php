<div class="container-fluid mt-3">
    <div class="row mb-3">
        <div class="col-md-8">
            <div class="d-flex justify-content-between align-items-center">
                <h4>チーム管理</h4>
                <div>
                    <a href="<?php echo BASE_PATH; ?>/task/create-team" class="btn btn-primary">
                        <i class="fas fa-plus-circle"></i> 新規チーム作成
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <?php if (empty($teams)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i> 所属しているチームがありません。新しいチームを作成するか、他のユーザーからの招待をお待ちください。
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>チーム名</th>
                                        <th>説明</th>
                                        <th>メンバー</th>
                                        <th>ボード数</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($teams as $team): ?>
                                        <tr>
                                            <td>
                                                <a href="<?php echo BASE_PATH; ?>/task/team/<?php echo $team['id']; ?>">
                                                    <?php echo htmlspecialchars($team['name']); ?>
                                                </a>
                                                <?php if ($team['user_role'] == 'admin'): ?>
                                                    <span class="badge bg-primary ms-2">管理者</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-muted small">
                                                <?php echo htmlspecialchars(mb_substr($team['description'] ?? '', 0, 50) . (mb_strlen($team['description'] ?? '') > 50 ? '...' : '')); ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary"><?php echo $team['member_count']; ?></span>
                                            </td>
                                            <td>
                                                <?php
                                                // ボード数の代わりに仮の数字を表示
                                                $boardCount = rand(0, 5); // 実際の実装ではここに本当のボード数を表示
                                                ?>
                                                <span class="badge bg-info"><?php echo $boardCount; ?></span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="<?php echo BASE_PATH; ?>/task/team/<?php echo $team['id']; ?>" class="btn btn-outline-secondary" title="詳細">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <?php if ($team['user_role'] == 'admin'): ?>
                                                        <a href="<?php echo BASE_PATH; ?>/task/edit-team/<?php echo $team['id']; ?>" class="btn btn-outline-primary" title="編集">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-outline-danger btn-delete"
                                                            data-url="<?php echo BASE_PATH; ?>/api/task/teams/<?php echo $team['id']; ?>"
                                                            data-confirm="チーム「<?php echo htmlspecialchars($team['name']); ?>」を削除しますか？ ボードも全て削除されます。" title="削除">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">チームについて</h5>
                </div>
                <div class="card-body">
                    <p>チームを作成して、他のユーザーとタスクボードを共有できます。チームのメンバーはタスクボードに協力して作業を進めることができます。</p>
                    <h6 class="mt-3">メンバーの役割</h6>
                    <ul class="list-unstyled">
                        <li><span class="badge bg-primary me-2">管理者</span> チームの設定変更、メンバー管理、ボード管理ができます</li>
                        <li class="mt-2"><span class="badge bg-secondary me-2">メンバー</span> チームのボードを閲覧・編集できます</li>
                    </ul>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0">最近アクセスしたチームボード</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <!-- 仮のデータ表示。実際にはユーザーの最近のチームボードを表示 -->
                        <?php if (!empty($teams)): ?>
                            <?php for ($i = 0; $i < min(3, count($teams)); $i++): ?>
                                <?php
                                // ランダムなチームを選択して、そのチームのボード名を表示
                                $teamIndex = array_rand($teams);
                                $team = $teams[$teamIndex];
                                $randomBoardName = "ボード" . rand(1, 10);
                                ?>
                                <a href="#" class="list-group-item list-group-item-action">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <i class="fas fa-clipboard-list me-2"></i>
                                            <?php echo $randomBoardName; ?>
                                            <div class="small text-muted">
                                                チーム: <?php echo htmlspecialchars($team['name']); ?>
                                            </div>
                                        </div>
                                        <span class="text-muted small">
                                            <?php echo date('m/d H:i'); ?>
                                        </span>
                                    </div>
                                </a>
                            <?php endfor; ?>
                        <?php else: ?>
                            <div class="list-group-item text-center py-3">最近アクセスしたボードはありません</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
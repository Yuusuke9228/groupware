<div class="container-fluid mt-3">
    <div class="row mb-3">
        <div class="col-md-8">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_PATH; ?>/task">タスク管理</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo BASE_PATH; ?>/task/teams">チーム管理</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($team['name']); ?></li>
                </ol>
            </nav>
            <div class="d-flex justify-content-between align-items-center">
                <h4><?php echo htmlspecialchars($team['name']); ?></h4>
                <?php if ($team['created_by'] == $auth->id() || $this->auth->isAdmin()): ?>
                    <a href="<?php echo BASE_PATH; ?>/task/edit-team/<?php echo $team['id']; ?>" class="btn btn-primary">
                        <i class="fas fa-edit"></i> チーム編集
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <!-- チームの説明 -->
            <?php if (!empty($team['description'])): ?>
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="card-title">チーム説明</h5>
                        <p class="card-text"><?php echo nl2br(htmlspecialchars($team['description'])); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- チームのボード一覧 -->
            <div class="card shadow-sm mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">ボード一覧</h5>
                    <a href="<?php echo BASE_PATH; ?>/task/create-board?team_id=<?php echo $team['id']; ?>" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus"></i> 新規ボード作成
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($boards)): ?>
                        <div class="text-center py-3">
                            <p class="text-muted mb-0">このチームにはまだボードがありません。</p>
                            <a href="<?php echo BASE_PATH; ?>/task/create-board?team_id=<?php echo $team['id']; ?>" class="btn btn-outline-primary mt-2">
                                <i class="fas fa-plus"></i> 最初のボードを作成
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($boards as $board): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <h5 class="card-title">
                                                <a href="<?php echo BASE_PATH; ?>/task/board/<?php echo $board['id']; ?>" class="text-decoration-none">
                                                    <?php echo htmlspecialchars($board['name']); ?>
                                                </a>
                                                <?php if ($board['is_public']): ?>
                                                    <span class="badge bg-info ms-1">公開</span>
                                                <?php endif; ?>
                                            </h5>
                                            <p class="card-text small text-muted">
                                                <?php echo !empty($board['description']) ? mb_substr(htmlspecialchars($board['description']), 0, 100) . (mb_strlen($board['description']) > 100 ? '...' : '') : '説明なし'; ?>
                                            </p>
                                        </div>
                                        <div class="card-footer bg-transparent d-flex justify-content-between">
                                            <small class="text-muted">作成日: <?php echo date('Y/m/d', strtotime($board['created_at'])); ?></small>
                                            <a href="<?php echo BASE_PATH; ?>/task/board/<?php echo $board['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-external-link-alt"></i> 開く
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- チームメンバー一覧 -->
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0">チームメンバー</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>名前</th>
                                    <th>メール</th>
                                    <th>役割</th>
                                    <th>追加日</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($members)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-3">メンバーがいません</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($members as $member): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar me-2"><?php echo mb_substr($member['display_name'], 0, 1); ?></div>
                                                    <div>
                                                        <?php echo htmlspecialchars($member['display_name']); ?>
                                                        <?php if ($member['user_id'] == $auth->id()): ?>
                                                            <span class="badge bg-info ms-1">あなた</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($member['email']); ?></td>
                                            <td>
                                                <?php if ($member['role'] == 'admin'): ?>
                                                    <span class="badge bg-primary">管理者</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">メンバー</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('Y/m/d', strtotime($member['created_at'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- チーム情報カード -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">チーム情報</h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">作成者</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($team['creator_name'] ?? '不明'); ?></dd>

                        <dt class="col-sm-4">作成日</dt>
                        <dd class="col-sm-8"><?php echo date('Y年m月d日', strtotime($team['created_at'])); ?></dd>

                        <dt class="col-sm-4">メンバー</dt>
                        <dd class="col-sm-8"><?php echo count($members); ?>人</dd>

                        <dt class="col-sm-4">ボード</dt>
                        <dd class="col-sm-8"><?php echo count($boards); ?>個</dd>

                        <dt class="col-sm-4">管理者</dt>
                        <dd class="col-sm-8">
                            <?php
                            $adminCount = 0;
                            foreach ($members as $member) {
                                if ($member['role'] == 'admin') {
                                    $adminCount++;
                                }
                            }
                            echo $adminCount;
                            ?>人
                        </dd>
                    </dl>
                </div>
            </div>

            <!-- 最近の活動 -->
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0">最近の活動</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <!-- ここに実際の活動ログを表示するには、活動ログの機能が必要 -->
                        <!-- サンプルデータとして表示 -->
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">ボード「企画会議」が作成されました</h6>
                                <small class="text-muted">3日前</small>
                            </div>
                            <p class="mb-1 small">ユーザー: <?php echo htmlspecialchars($team['creator_name'] ?? '不明'); ?></p>
                        </div>
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">新しいメンバーが追加されました</h6>
                                <small class="text-muted">1週間前</small>
                            </div>
                            <p class="mb-1 small">ユーザー: <?php echo htmlspecialchars($team['creator_name'] ?? '不明'); ?></p>
                        </div>
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">チームが作成されました</h6>
                                <small class="text-muted"><?php echo date('Y/m/d', strtotime($team['created_at'])); ?></small>
                            </div>
                            <p class="mb-1 small">作成者: <?php echo htmlspecialchars($team['creator_name'] ?? '不明'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
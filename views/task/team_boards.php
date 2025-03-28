<div class="container mt-3">
    <div class="row mb-3">
        <div class="col-md-8">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_PATH; ?>/">ホーム</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo BASE_PATH; ?>/task">タスク管理</a></li>
                    <li class="breadcrumb-item active" aria-current="page">チームボード</li>
                </ol>
            </nav>
            <h4 class="mb-3">チームタスクボード</h4>
        </div>
        <div class="col-md-4 text-end">
            <div class="btn-group">
                <a href="<?php echo BASE_PATH; ?>/task/create-board" class="btn btn-primary">
                    <i class="fas fa-plus-circle"></i> 新規ボード作成
                </a>
                <a href="<?php echo BASE_PATH; ?>/task/create-team" class="btn btn-outline-primary">
                    <i class="fas fa-users"></i> 新規チーム作成
                </a>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-12">
            <?php if (empty($teams)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i> あなたが所属しているチームはありません。「新規チーム作成」ボタンからチームを作成してください。
                </div>
            <?php elseif (empty($teamBoards)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i> チーム用のタスクボードがありません。「新規ボード作成」ボタンから作成してください。
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php foreach ($teamBoards as $teamId => $teamData): ?>
        <div class="row mb-4">
            <div class="col-12">
                <h5 class="border-bottom pb-2">
                    <i class="fas fa-users text-primary me-2"></i>
                    <?php echo htmlspecialchars($teamData['team']['name']); ?>
                    <span class="badge bg-secondary ms-2"><?php echo count($teamData['boards']); ?> ボード</span>
                </h5>
            </div>
        </div>

        <div class="row mb-4">
            <?php foreach ($teamData['boards'] as $board): ?>
                <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                    <div class="card h-100 board-card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title text-center">
                                <i class="fas fa-clipboard-list board-icon" style="color: <?php echo $board['background_color']; ?>"></i>
                                <div><?php echo htmlspecialchars($board['name']); ?></div>
                            </h5>
                            <?php if ($board['is_public']): ?>
                                <span class="position-absolute top-0 end-0 badge bg-info m-2">公開</span>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer bg-white border-0 text-center">
                            <a href="<?php echo BASE_PATH; ?>/task/board/<?php echo $board['id']; ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-external-link-alt"></i> 開く
                            </a>
                            <a href="<?php echo BASE_PATH; ?>/task/edit-board/<?php echo $board['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-edit"></i> 編集
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- 新規ボード作成カード -->
            <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                <div class="card h-100 board-card shadow-sm">
                    <div class="card-body d-flex justify-content-center align-items-center">
                        <a href="<?php echo BASE_PATH; ?>/task/create-board?team_id=<?php echo $teamId; ?>" class="btn btn-outline-primary btn-lg">
                            <i class="fas fa-plus"></i><br>
                            新規ボード作成
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <?php if (!empty($teams) && empty($teamBoards)): ?>
        <div class="row">
            <div class="col-12">
                <h5 class="border-bottom pb-2">チーム一覧</h5>
            </div>

            <?php foreach ($teams as $team): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card shadow-sm team-card">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="fas fa-users text-primary me-2"></i>
                                <?php echo htmlspecialchars($team['name']); ?>
                            </h5>
                            <p class="card-text small">
                                <?php echo !empty($team['description']) ? htmlspecialchars($team['description']) : 'チームの説明はありません'; ?>
                            </p>
                        </div>
                        <div class="card-footer bg-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">メンバー: <?php echo $team['member_count']; ?>人</small>
                                <div>
                                    <a href="<?php echo BASE_PATH; ?>/task/create-board?team_id=<?php echo $team['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-plus"></i> ボード作成
                                    </a>
                                    <a href="<?php echo BASE_PATH; ?>/task/team/<?php echo $team['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-info-circle"></i> 詳細
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
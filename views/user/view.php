<?php
// views/user/view.php
$pageTitle = 'ユーザー詳細 - GroupWare';
?>
<div class="container-fluid" data-page-type="view">
    <div class="row mb-4">
        <div class="col">
            <h1 class="h3">ユーザー詳細</h1>
        </div>
        <div class="col-auto">
            <?php if ($this->auth->isAdmin() || $this->auth->id() == $user['id']): ?>
                <a href="<?php echo BASE_PATH; ?>/users/edit/<?php echo $user['id']; ?>" class="btn btn-primary me-2">
                    <i class="fas fa-edit"></i> 編集
                </a>
            <?php endif; ?>
            <a href="<?php echo BASE_PATH; ?>/users" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> 一覧に戻る
            </a>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">ユーザー情報</h5>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-3 fw-bold">ユーザー名</div>
                <div class="col-md-9"><?php echo htmlspecialchars($user['username']); ?></div>
            </div>

            <div class="row mb-3">
                <div class="col-md-3 fw-bold">氏名</div>
                <div class="col-md-9"><?php echo htmlspecialchars($user['last_name'] . ' ' . $user['first_name']); ?></div>
            </div>

            <div class="row mb-3">
                <div class="col-md-3 fw-bold">表示名</div>
                <div class="col-md-9"><?php echo htmlspecialchars($user['display_name']); ?></div>
            </div>

            <div class="row mb-3">
                <div class="col-md-3 fw-bold">メールアドレス</div>
                <div class="col-md-9"><?php echo htmlspecialchars($user['email']); ?></div>
            </div>

            <?php if (!empty($user['position'])): ?>
                <div class="row mb-3">
                    <div class="col-md-3 fw-bold">役職</div>
                    <div class="col-md-9"><?php echo htmlspecialchars($user['position']); ?></div>
                </div>
            <?php endif; ?>

            <?php if (!empty($user['phone'])): ?>
                <div class="row mb-3">
                    <div class="col-md-3 fw-bold">電話番号</div>
                    <div class="col-md-9"><?php echo htmlspecialchars($user['phone']); ?></div>
                </div>
            <?php endif; ?>

            <?php if (!empty($user['mobile_phone'])): ?>
                <div class="row mb-3">
                    <div class="col-md-3 fw-bold">携帯電話</div>
                    <div class="col-md-9"><?php echo htmlspecialchars($user['mobile_phone']); ?></div>
                </div>
            <?php endif; ?>

            <div class="row mb-3">
                <div class="col-md-3 fw-bold">ステータス</div>
                <div class="col-md-9">
                    <?php
                    $statusLabels = [
                        'active' => '<span class="badge bg-success">有効</span>',
                        'inactive' => '<span class="badge bg-warning">無効</span>',
                        'suspended' => '<span class="badge bg-danger">停止</span>'
                    ];
                    echo $statusLabels[$user['status']] ?? $user['status'];
                    ?>
                </div>
            </div>

            <?php if ($this->auth->isAdmin()): ?>
                <div class="row mb-3">
                    <div class="col-md-3 fw-bold">権限</div>
                    <div class="col-md-9">
                        <?php
                        $roleLabels = [
                            'admin' => '<span class="badge bg-danger">管理者</span>',
                            'manager' => '<span class="badge bg-warning">マネージャー</span>',
                            'user' => '<span class="badge bg-info">一般ユーザー</span>'
                        ];
                        echo $roleLabels[$user['role']] ?? $user['role'];
                        ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($user['last_login'])): ?>
                <div class="row mb-3">
                    <div class="col-md-3 fw-bold">最終ログイン</div>
                    <div class="col-md-9"><?php echo date('Y年m月d日 H:i', strtotime($user['last_login'])); ?></div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">所属組織</h5>
        </div>
        <div class="card-body">
            <div id="organization-list">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<input type="hidden" id="user-id" value="<?php echo $user['id']; ?>">
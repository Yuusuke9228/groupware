<?php
// views/user/change_password.php
$pageTitle = 'パスワード変更';
$requiresCurrentPassword = !empty($requiresCurrentPassword);
?>
<div class="container-fluid" data-page-type="change-password">
    <div class="row mb-4">
        <div class="col">
            <h1 class="h3">パスワード変更</h1>
            <p class="text-muted mb-0">
                対象ユーザー: <?= htmlspecialchars((string)($user['display_name'] ?? $user['username'] ?? '')) ?>
            </p>
        </div>
        <div class="col-auto">
            <a href="<?= BASE_PATH ?>/users/view/<?= (int)$user['id'] ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> 詳細に戻る
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="<?= BASE_PATH ?>/api/users/<?= (int)$user['id'] ?>/change-password" method="post">
                <?php if ($requiresCurrentPassword): ?>
                    <div class="mb-3">
                        <label for="current_password" class="form-label">現在のパスワード <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="current_password" name="current_password" autocomplete="current-password" required>
                        <div class="invalid-feedback"></div>
                    </div>
                <?php endif; ?>

                <div class="mb-3">
                    <label for="new_password" class="form-label">新しいパスワード <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" id="new_password" name="new_password" autocomplete="new-password" required>
                    <div class="progress mt-2" style="height: 6px;">
                        <div id="password-strength" class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <small id="password-feedback" class="form-text text-muted"></small>
                    <div class="invalid-feedback"></div>
                </div>

                <div class="mb-4">
                    <label for="new_password_confirm" class="form-label">新しいパスワード（確認） <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" id="new_password_confirm" name="new_password_confirm" autocomplete="new-password" required>
                    <div class="invalid-feedback"></div>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="<?= BASE_PATH ?>/users/view/<?= (int)$user['id'] ?>" class="btn btn-outline-secondary me-md-2">キャンセル</a>
                    <button type="submit" class="btn btn-primary">更新</button>
                </div>
            </form>
        </div>
    </div>
</div>

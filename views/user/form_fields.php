<?php
// views/user/form_fields.php

// 変数のデフォルト値を設定
if (!isset($user)) $user = [];
if (!isset($isEdit)) $isEdit = false;

// デバッグ用（実際の運用では削除してください）
// echo "<pre>";
// var_dump($user);
// echo "</pre>";
?>

<div class="row mb-3">
    <div class="col-md-6">
        <label for="username" class="form-label">ユーザー名 <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="username" name="username"
            value="<?php echo isset($user['username']) ? htmlspecialchars($user['username']) : ''; ?>"
            required <?php echo $isEdit ? 'readonly' : ''; ?>>
        <div class="invalid-feedback"></div>
    </div>
    <div class="col-md-6">
        <label for="email" class="form-label">メールアドレス <span class="text-danger">*</span></label>
        <input type="email" class="form-control" id="email" name="email"
            value="<?php echo isset($user['email']) ? htmlspecialchars($user['email']) : ''; ?>"
            required>
        <div class="invalid-feedback"></div>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-6">
        <label for="last_name" class="form-label">姓 <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="last_name" name="last_name"
            value="<?php echo isset($user['last_name']) ? htmlspecialchars($user['last_name']) : ''; ?>"
            required>
        <div class="invalid-feedback"></div>
    </div>
    <div class="col-md-6">
        <label for="first_name" class="form-label">名 <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="first_name" name="first_name"
            value="<?php echo isset($user['first_name']) ? htmlspecialchars($user['first_name']) : ''; ?>"
            required>
        <div class="invalid-feedback"></div>
    </div>
</div>

<div class="mb-3">
    <label for="display_name" class="form-label">表示名 <span class="text-danger">*</span></label>
    <input type="text" class="form-control" id="display_name" name="display_name"
        value="<?php echo isset($user['display_name']) ? htmlspecialchars($user['display_name']) : ''; ?>"
        required>
    <div class="invalid-feedback"></div>
    <small class="form-text text-muted">氏名から自動生成されます。必要に応じて変更できます。</small>
</div>

<?php if (!$isEdit): ?>
    <div class="row mb-3">
        <div class="col-md-6">
            <label for="password" class="form-label">パスワード <span class="text-danger">*</span></label>
            <input type="password" class="form-control" id="password" name="password" required>
            <div class="invalid-feedback"></div>
            <div class="mt-1">
                <div class="progress" style="height: 5px;">
                    <div id="password-strength" class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <small id="password-feedback" class="form-text text-muted"></small>
            </div>
        </div>
        <div class="col-md-6">
            <label for="password_confirm" class="form-label">パスワード（確認） <span class="text-danger">*</span></label>
            <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
            <div class="invalid-feedback"></div>
        </div>
    </div>
<?php endif; ?>

<div class="mb-3">
    <label for="organization_id" class="form-label">主所属組織</label>
    <select class="form-select" id="organization_id" name="organization_id"
        data-selected="<?php echo isset($primaryOrgId) ? $primaryOrgId : (isset($user['organization_id']) ? $user['organization_id'] : ''); ?>">
        <option value="">選択してください</option>
        <!-- 組織リストはJSで動的に生成 -->
    </select>
</div>

<div class="mb-3">
    <label class="form-label">追加所属組織</label>
    <div id="organizations-container">
        <!-- 追加組織の行はJSで動的に生成 -->
    </div>
    <button type="button" id="add-organization" class="btn btn-outline-secondary btn-sm mt-2">
        <i class="fas fa-plus"></i> 組織を追加
    </button>
    <input type="hidden" id="additional_organizations" name="additional_organizations"
        value="<?php echo isset($additionalOrganizations) ? $additionalOrganizations : ''; ?>">
</div>

<div class="row mb-3">
    <div class="col-md-6">
        <label for="position" class="form-label">役職</label>
        <input type="text" class="form-control" id="position" name="position"
            value="<?php echo isset($user['position']) ? htmlspecialchars($user['position']) : ''; ?>">
    </div>
    <div class="col-md-6">
        <label for="phone" class="form-label">電話番号</label>
        <input type="text" class="form-control" id="phone" name="phone"
            value="<?php echo isset($user['phone']) ? htmlspecialchars($user['phone']) : ''; ?>">
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-6">
        <label for="mobile_phone" class="form-label">携帯電話</label>
        <input type="text" class="form-control" id="mobile_phone" name="mobile_phone"
            value="<?php echo isset($user['mobile_phone']) ? htmlspecialchars($user['mobile_phone']) : ''; ?>">
    </div>
    <div class="col-md-6">
        <label for="status" class="form-label">ステータス <span class="text-danger">*</span></label>
        <select class="form-select" id="status" name="status" required>
            <option value="active" <?php echo isset($user['status']) && $user['status'] === 'active' ? 'selected' : ''; ?>>有効</option>
            <option value="inactive" <?php echo isset($user['status']) && $user['status'] === 'inactive' ? 'selected' : ''; ?>>無効</option>
            <option value="suspended" <?php echo isset($user['status']) && $user['status'] === 'suspended' ? 'selected' : ''; ?>>停止</option>
        </select>
    </div>
</div>

<?php if ($isEdit && \Core\Auth::getInstance()->isAdmin()): ?>
    <div class="mb-3">
        <label for="role" class="form-label">役割 <span class="text-danger">*</span></label>
        <select class="form-select" id="role" name="role" required>
            <option value="user" <?php echo isset($user['role']) && $user['role'] === 'user' ? 'selected' : ''; ?>>一般ユーザー</option>
            <option value="manager" <?php echo isset($user['role']) && $user['role'] === 'manager' ? 'selected' : ''; ?>>管理者</option>
            <option value="admin" <?php echo isset($user['role']) && $user['role'] === 'admin' ? 'selected' : ''; ?>>システム管理者</option>
        </select>
    </div>
<?php else: ?>
    <input type="hidden" name="role" value="<?php echo $isEdit && isset($user['role']) ? htmlspecialchars($user['role']) : 'user'; ?>">
<?php endif; ?>
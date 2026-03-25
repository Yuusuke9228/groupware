<!-- views/file_manager/folder_form.php -->
<?php
$permissionSummary = $permissionSummary ?? [
    'view' => ['organizations' => [], 'users' => []],
    'edit' => ['organizations' => [], 'users' => []],
    'approve' => ['organizations' => [], 'users' => []],
    'admin' => ['organizations' => [], 'users' => []],
];
?>
<div class="container-fluid mt-4">
    <div class="row mb-3">
        <div class="col">
            <h4 class="mb-1">
                <i class="fas fa-folder-plus text-primary me-2"></i>
                <?= $folder ? 'フォルダ編集' : '新規フォルダ作成' ?>
            </h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="<?= BASE_PATH ?>/files"><i class="fas fa-home me-1"></i>ルート</a></li>
                    <?php foreach ($breadcrumbs as $bc): ?>
                        <li class="breadcrumb-item">
                            <a href="<?= BASE_PATH ?>/files/folder/<?= $bc['id'] ?>"><?= htmlspecialchars($bc['name']) ?></a>
                        </li>
                    <?php endforeach; ?>
                    <li class="breadcrumb-item active"><?= $folder ? 'フォルダ編集' : '新規フォルダ' ?></li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- フラッシュメッセージ -->
    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-1"></i><?= htmlspecialchars($_SESSION['flash_error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <div class="row justify-content-center">
        <div class="col-lg-8 col-xl-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-folder text-warning me-1"></i>
                        <?= $folder ? 'フォルダ情報の編集' : 'フォルダ情報の入力' ?>
                    </h6>
                </div>
                <div class="card-body">
                    <form method="post" action="<?= $folder ? BASE_PATH . '/files/folder/' . $folder['id'] . '/update' : BASE_PATH . '/files/folder' ?>" class="no-ajax">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        <?php if (!$folder && $parentFolder): ?>
                            <input type="hidden" name="parent_id" value="<?= $parentFolder['id'] ?>">
                        <?php endif; ?>

                        <div class="mb-3">
                            <label for="name" class="form-label">フォルダ名 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" required
                                   value="<?= $folder ? htmlspecialchars($folder['name']) : '' ?>"
                                   placeholder="フォルダ名を入力" autofocus>
                        </div>

                        <?php if (!$folder && $parentFolder): ?>
                            <div class="mb-3">
                                <label class="form-label">親フォルダ</label>
                                <div class="form-control-plaintext">
                                    <i class="fas fa-folder text-warning me-1"></i><?= htmlspecialchars($parentFolder['name']) ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="mb-4">
                            <label for="description" class="form-label">説明</label>
                            <textarea class="form-control" id="description" name="description" rows="3"
                                      placeholder="フォルダの説明（任意）"><?= $folder ? htmlspecialchars($folder['description']) : '' ?></textarea>
                        </div>

                        <div class="card bg-light border-0 mb-4">
                            <div class="card-body">
                                <h6 class="mb-3"><i class="fas fa-shield-alt me-1"></i>フォルダ権限</h6>
                                <p class="text-muted small">未指定の場合は従来通り全ユーザーが利用できます。必要な範囲だけ指定してください。</p>
                                <?php foreach (['view' => '閲覧', 'edit' => '編集', 'approve' => '承認', 'admin' => '管理'] as $permissionType => $label): ?>
                                    <?php
                                    $selectedOrgIds = array_map('intval', array_column($permissionSummary[$permissionType]['organizations'] ?? [], 'id'));
                                    $selectedUserIds = array_map('intval', array_column($permissionSummary[$permissionType]['users'] ?? [], 'id'));
                                    ?>
                                    <div class="row g-3 mb-3">
                                        <div class="col-12">
                                            <div class="fw-bold small text-uppercase text-muted"><?= $label ?>権限</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small">対象組織</label>
                                            <select class="form-select form-select-sm" name="<?= $permissionType ?>_organization_ids[]" multiple size="5">
                                                <?php foreach ($organizations as $organization): ?>
                                                    <option value="<?= (int)$organization['id'] ?>" <?= in_array((int)$organization['id'], $selectedOrgIds, true) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($organization['name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small">対象ユーザー</label>
                                            <select class="form-select form-select-sm" name="<?= $permissionType ?>_user_ids[]" multiple size="5">
                                                <?php foreach ($users as $user): ?>
                                                    <option value="<?= (int)$user['id'] ?>" <?= in_array((int)$user['id'], $selectedUserIds, true) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($user['display_name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="<?= $folder ? (($folder['parent_id'] ? BASE_PATH . '/files/folder/' . $folder['parent_id'] : BASE_PATH . '/files')) : ($parentFolder ? BASE_PATH . '/files/folder/' . $parentFolder['id'] : BASE_PATH . '/files') ?>"
                               class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i>戻る
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i><?= $folder ? '更新' : '作成' ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

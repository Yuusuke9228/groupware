<!-- views/file_manager/upload.php -->
<?php
$organizations = $organizations ?? [];
$users = $users ?? [];
$fileShareLimits = $fileShareLimits ?? [];
$storageUsage = $storageUsage ?? ['total_bytes' => 0, 'user_bytes' => 0, 'org_bytes' => 0];
if (!function_exists('fmFormatBytes')) {
    function fmFormatBytes($bytes) {
        $bytes = (int)$bytes;
        if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
        if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
        if ($bytes >= 1024) return number_format($bytes / 1024, 2) . ' KB';
        return $bytes . ' B';
    }
}
?>
<div class="container-fluid mt-4">
    <div class="row mb-3">
        <div class="col">
            <h4 class="mb-1">
                <i class="fas fa-upload text-primary me-2"></i>ファイルアップロード
            </h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="<?= BASE_PATH ?>/files"><i class="fas fa-home me-1"></i>ルート</a></li>
                    <?php foreach ($breadcrumbs as $bc): ?>
                        <li class="breadcrumb-item">
                            <a href="<?= BASE_PATH ?>/files/folder/<?= $bc['id'] ?>"><?= htmlspecialchars($bc['name']) ?></a>
                        </li>
                    <?php endforeach; ?>
                    <li class="breadcrumb-item active">アップロード</li>
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

    <div class="alert alert-info">
        <div class="small fw-bold mb-1">容量ガイド</div>
        <div class="small">
            1ファイル上限: <?= (int)($fileShareLimits['max_upload_mb'] ?? 0) > 0 ? (int)$fileShareLimits['max_upload_mb'] . ' MB' : '無制限' ?> /
            全体使用量: <?= fmFormatBytes($storageUsage['total_bytes'] ?? 0) ?>
            <?php if ((int)($fileShareLimits['storage_quota_mb'] ?? 0) > 0): ?>
                （上限 <?= (int)$fileShareLimits['storage_quota_mb'] ?> MB）
            <?php endif; ?>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8 col-xl-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-cloud-upload-alt me-1"></i>ファイル情報の入力</h6>
                </div>
                <div class="card-body">
                    <form method="post" action="<?= BASE_PATH ?>/files/upload" enctype="multipart/form-data" id="uploadForm" class="no-ajax">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                        <!-- ドラッグ＆ドロップエリア -->
                        <div class="mb-3">
                            <label class="form-label">ファイル <span class="text-danger">*</span></label>
                            <div id="dropZone" class="drop-zone">
                                <div class="drop-zone-content" id="dropZoneContent">
                                    <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-2"></i>
                                    <p class="text-muted mb-1">ファイルをドラッグ＆ドロップ</p>
                                    <p class="text-muted small mb-2">または</p>
                                    <label for="fileInput" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-folder-open me-1"></i>ファイルを選択
                                    </label>
                                </div>
                                <div class="drop-zone-preview d-none" id="dropZonePreview">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-file fa-2x text-primary me-3" id="previewIcon"></i>
                                        <div>
                                            <div class="fw-bold" id="previewName"></div>
                                            <small class="text-muted" id="previewSize"></small>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-danger ms-auto" id="removeFile">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <input type="file" class="d-none" id="fileInput" name="file" required>
                        </div>

                        <div class="mb-3">
                            <label for="title" class="form-label">タイトル</label>
                            <input type="text" class="form-control" id="title" name="title"
                                   placeholder="空欄の場合はファイル名が使用されます">
                        </div>

                        <div class="mb-3">
                            <label for="folder_id" class="form-label">保存先フォルダ</label>
                            <select class="form-select" id="folder_id" name="folder_id">
                                <option value="">ルート（トップレベル）</option>
                                <?php foreach ($allFolders as $f): ?>
                                    <option value="<?= $f['id'] ?>" <?= ($folder && $folder['id'] == $f['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($f['indent_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label for="description" class="form-label">説明</label>
                            <textarea class="form-control" id="description" name="description" rows="3"
                                      placeholder="ファイルの説明（任意）"></textarea>
                        </div>

                        <div class="card bg-light border-0 mb-4">
                            <div class="card-body">
                                <h6 class="mb-3"><i class="fas fa-user-lock me-1"></i>ファイル権限と承認</h6>
                                <div class="row g-3 mb-3">
                                    <?php foreach (['view' => '閲覧', 'edit' => '編集', 'approve' => '承認', 'admin' => '管理'] as $permissionType => $label): ?>
                                        <div class="col-12">
                                            <div class="fw-bold small text-uppercase text-muted"><?= $label ?>権限</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small">対象組織</label>
                                            <select class="form-select form-select-sm select2-multi" name="<?= $permissionType ?>_organization_ids[]" multiple data-placeholder="組織を選択...">
                                                <?php foreach ($organizations as $organization): ?>
                                                    <option value="<?= (int)$organization['id'] ?>"><?= htmlspecialchars($organization['name']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small">対象ユーザー</label>
                                            <select class="form-select form-select-sm select2-multi" name="<?= $permissionType ?>_user_ids[]" multiple data-placeholder="ユーザーを選択...">
                                                <?php foreach ($users as $user): ?>
                                                    <option value="<?= (int)$user['id'] ?>"><?= htmlspecialchars($user['display_name']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="require_approval" name="require_approval" value="1">
                                    <label class="form-check-label" for="require_approval">アップロード後に承認フローへ回す</label>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small">承認者</label>
                                    <select class="form-select form-select-sm select2-multi" name="approval_user_ids[]" multiple data-placeholder="承認者を選択...">
                                        <?php foreach ($users as $user): ?>
                                            <option value="<?= (int)$user['id'] ?>"><?= htmlspecialchars($user['display_name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-0">
                                    <label for="approval_comment" class="form-label small">承認依頼コメント</label>
                                    <textarea class="form-control form-control-sm" id="approval_comment" name="approval_comment" rows="2" placeholder="確認してほしいポイントなど"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="<?= $folder ? BASE_PATH . '/files/folder/' . $folder['id'] : BASE_PATH . '/files' ?>"
                               class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i>戻る
                            </a>
                            <button type="submit" class="btn btn-primary" id="uploadBtn">
                                <i class="fas fa-upload me-1"></i>アップロード
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.drop-zone {
    border: 2px dashed #ccc;
    border-radius: 8px;
    padding: 2rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s ease;
    background: #fafbfc;
}
.drop-zone.dragover {
    border-color: #2b7de9;
    background: #e8f0fe;
}
.drop-zone:hover {
    border-color: #999;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var dropZone = document.getElementById('dropZone');
    var fileInput = document.getElementById('fileInput');
    var content = document.getElementById('dropZoneContent');
    var preview = document.getElementById('dropZonePreview');
    var previewName = document.getElementById('previewName');
    var previewSize = document.getElementById('previewSize');
    var removeBtn = document.getElementById('removeFile');

    function formatSize(bytes) {
        if (bytes >= 1073741824) return (bytes / 1073741824).toFixed(1) + ' GB';
        if (bytes >= 1048576) return (bytes / 1048576).toFixed(1) + ' MB';
        if (bytes >= 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return bytes + ' B';
    }

    function showPreview(file) {
        previewName.textContent = file.name;
        previewSize.textContent = formatSize(file.size);
        content.classList.add('d-none');
        preview.classList.remove('d-none');

        // タイトルが空ならファイル名をセット
        var titleInput = document.getElementById('title');
        if (!titleInput.value) {
            var name = file.name;
            var lastDot = name.lastIndexOf('.');
            titleInput.value = lastDot > 0 ? name.substring(0, lastDot) : name;
        }
    }

    function resetPreview() {
        content.classList.remove('d-none');
        preview.classList.add('d-none');
        fileInput.value = '';
    }

    // ドラッグ＆ドロップ
    dropZone.addEventListener('dragover', function(e) {
        e.preventDefault();
        dropZone.classList.add('dragover');
    });

    dropZone.addEventListener('dragleave', function(e) {
        e.preventDefault();
        dropZone.classList.remove('dragover');
    });

    dropZone.addEventListener('drop', function(e) {
        e.preventDefault();
        dropZone.classList.remove('dragover');
        if (e.dataTransfer.files.length > 0) {
            fileInput.files = e.dataTransfer.files;
            showPreview(e.dataTransfer.files[0]);
        }
    });

    // クリックでファイル選択
    dropZone.addEventListener('click', function(e) {
        if (e.target.closest('#removeFile') || e.target.closest('label[for="fileInput"]')) return;
        fileInput.click();
    });

    fileInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            showPreview(this.files[0]);
        }
    });

    removeBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        resetPreview();
    });
});
</script>

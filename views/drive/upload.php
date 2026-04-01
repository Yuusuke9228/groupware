<?php
$orgOptions = $orgOptions ?? [];
$driveUsage = $driveUsage ?? ['total_bytes' => 0, 'user_bytes' => 0, 'org_bytes' => 0];
$driveLimits = $driveLimits ?? [];
if (!function_exists('driveFormatBytes')) {
    function driveFormatBytes($bytes) {
        $bytes = (int)$bytes;
        if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
        if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
        if ($bytes >= 1024) return number_format($bytes / 1024, 2) . ' KB';
        return $bytes . ' B';
    }
}
?>
<div class="container mt-4" style="max-width: 920px;">
    <h4 class="mb-1"><i class="fas fa-upload me-2 text-primary"></i><?= htmlspecialchars(tr_text('Driveアップロード', 'Drive Upload')) ?></h4>
    <p class="text-muted mb-3"><?= htmlspecialchars(tr_text('通常のファイル管理とは別のDrive領域へ保存します。', 'Files are stored in the independent Drive area, separate from File Manager.')) ?></p>

    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['flash_error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <div class="alert alert-info">
        <div class="small fw-bold mb-1"><?= htmlspecialchars(tr_text('容量ガバナンス', 'Storage governance')) ?></div>
        <div class="small">
            <?= htmlspecialchars(tr_text('1ファイル上限', 'Max upload per file')) ?>:
            <?= (int)($driveLimits['max_upload_mb'] ?? 0) > 0 ? (int)$driveLimits['max_upload_mb'] . ' MB' : htmlspecialchars(tr_text('無制限', 'Unlimited')) ?>
            /
            <?= htmlspecialchars(tr_text('全体使用量', 'Total usage')) ?>: <?= driveFormatBytes($driveUsage['total_bytes'] ?? 0) ?>
            <?php if ((int)($driveLimits['storage_quota_mb'] ?? 0) > 0): ?>
                (<?= htmlspecialchars(tr_text('上限', 'Quota')) ?> <?= (int)$driveLimits['storage_quota_mb'] ?> MB)
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="post" action="<?= BASE_PATH ?>/drive/upload" enctype="multipart/form-data" class="no-ajax">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)$csrf_token) ?>">

                <div class="mb-3">
                    <label class="form-label"><?= htmlspecialchars(tr_text('ファイル', 'File')) ?> <span class="text-danger">*</span></label>
                    <input type="file" name="file" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label"><?= htmlspecialchars(tr_text('タイトル', 'Title')) ?></label>
                    <input type="text" name="title" class="form-control" placeholder="<?= htmlspecialchars(tr_text('未入力時はファイル名を使用', 'If empty, filename will be used')) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label"><?= htmlspecialchars(tr_text('説明', 'Description')) ?></label>
                    <textarea name="description" class="form-control" rows="3"></textarea>
                </div>

                <div class="mb-2">
                    <label class="form-label"><?= htmlspecialchars(tr_text('保存スコープ', 'Ownership scope')) ?></label>
                </div>
                <div class="form-check mb-1">
                    <input class="form-check-input" type="radio" name="ownership_scope" id="scopeUser" value="user" checked>
                    <label class="form-check-label" for="scopeUser"><?= htmlspecialchars(tr_text('個人（自分専用）', 'Personal (owned by me)')) ?></label>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="radio" name="ownership_scope" id="scopeOrg" value="organization">
                    <label class="form-check-label" for="scopeOrg"><?= htmlspecialchars(tr_text('組織（組織メンバーで共有）', 'Organization (shared in organization)')) ?></label>
                </div>

                <div class="mb-3">
                    <label class="form-label"><?= htmlspecialchars(tr_text('組織', 'Organization')) ?></label>
                    <select name="owner_organization_id" id="ownerOrganizationId" class="form-select" disabled>
                        <option value=""><?= htmlspecialchars(tr_text('組織を選択', 'Select organization')) ?></option>
                        <?php foreach ($orgOptions as $org): ?>
                            <option value="<?= (int)$org['id'] ?>"><?= htmlspecialchars((string)$org['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="d-flex gap-2">
                    <a href="<?= BASE_PATH ?>/drive" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i><?= htmlspecialchars(tr_text('戻る', 'Back')) ?>
                    </a>
                    <button type="submit" class="btn btn-primary ms-auto">
                        <i class="fas fa-upload me-1"></i><?= htmlspecialchars(tr_text('アップロード', 'Upload')) ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var scopeUser = document.getElementById('scopeUser');
    var scopeOrg = document.getElementById('scopeOrg');
    var orgSelect = document.getElementById('ownerOrganizationId');
    function syncScope() {
        var isOrg = !!(scopeOrg && scopeOrg.checked);
        if (!orgSelect) return;
        orgSelect.disabled = !isOrg;
        if (!isOrg) {
            orgSelect.value = '';
        }
    }
    if (scopeUser) scopeUser.addEventListener('change', syncScope);
    if (scopeOrg) scopeOrg.addEventListener('change', syncScope);
    syncScope();
});
</script>

<?php
$items = $items ?? [];
$search = $search ?? '';
$driveLimits = $driveLimits ?? [];
$driveUsage = $driveUsage ?? ['total_bytes' => 0, 'user_bytes' => 0, 'org_bytes' => 0];
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
<div class="container-fluid mt-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h4 class="mb-1"><i class="fas fa-cloud me-2 text-primary"></i><?= htmlspecialchars(tr_text('ファイル共有', 'File Sharing')) ?></h4>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= BASE_PATH ?>/file-share/upload" class="btn btn-primary">
                <i class="fas fa-upload me-1"></i><?= htmlspecialchars(tr_text('アップロード', 'Upload')) ?>
            </a>
        </div>
    </div>

    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['flash_success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>
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
        <div class="small mt-1"><?= htmlspecialchars(tr_text('共有リンクは対象ファイルのみを配布し、公開リンクはログインなしでダウンロードできます。', 'Share links distribute only the target file, and public links can be downloaded without login.')) ?></div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="get" action="<?= BASE_PATH ?>/file-share" class="row g-2">
                <div class="col-md-10">
                    <input type="text" name="q" class="form-control" value="<?= htmlspecialchars($search) ?>" placeholder="<?= htmlspecialchars(tr_text('タイトル・ファイル名で検索', 'Search by title or filename')) ?>">
                </div>
                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-outline-secondary"><i class="fas fa-search me-1"></i><?= htmlspecialchars(tr_text('検索', 'Search')) ?></button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <?php if (empty($items)): ?>
                <div class="text-center text-muted py-5">
                    <i class="fas fa-folder-open mb-2" style="font-size: 2rem;"></i>
                    <div><?= htmlspecialchars(tr_text('ファイル共有にファイルはありません。', 'No files in File Sharing yet.')) ?></div>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th><?= htmlspecialchars(tr_text('タイトル', 'Title')) ?></th>
                                <th><?= htmlspecialchars(tr_text('共有単位', 'Scope')) ?></th>
                                <th><?= htmlspecialchars(tr_text('サイズ', 'Size')) ?></th>
                                <th><?= htmlspecialchars(tr_text('更新日時', 'Updated')) ?></th>
                                <th><?= htmlspecialchars(tr_text('共有リンク', 'Share links')) ?></th>
                                <th style="width: 300px;"><?= htmlspecialchars(tr_text('操作', 'Actions')) ?></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($items as $item): ?>
                            <?php
                            $isOrg = ((string)($item['owner_type'] ?? 'user') === 'organization');
                            ?>
                            <tr>
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars((string)$item['title']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars((string)$item['original_name']) ?></small>
                                </td>
                                <td>
                                    <?php if ($isOrg): ?>
                                        <span class="badge bg-primary"><?= htmlspecialchars(tr_text('組織', 'Organization')) ?></span>
                                        <small class="text-muted d-block"><?= htmlspecialchars((string)($item['owner_organization_name'] ?? '')) ?></small>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?= htmlspecialchars(tr_text('個人', 'Personal')) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars(driveFormatBytes((int)($item['file_size'] ?? 0))) ?></td>
                                <td><?= htmlspecialchars(date('Y/m/d H:i', strtotime((string)$item['updated_at']))) ?></td>
                                <td>
                                    <span class="badge bg-info text-dark"><?= (int)($item['active_share_links'] ?? 0) ?></span>
                                </td>
                                <td>
                                    <div class="d-flex gap-1 flex-wrap">
                                        <a href="<?= BASE_PATH ?>/file-share/file/<?= (int)$item['id'] ?>" class="btn btn-sm btn-outline-secondary">
                                            <i class="fas fa-eye me-1"></i><?= htmlspecialchars(tr_text('詳細', 'Details')) ?>
                                        </a>
                                        <a href="<?= BASE_PATH ?>/file-share/download/<?= (int)$item['id'] ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-download me-1"></i><?= htmlspecialchars(tr_text('DL', 'Download')) ?>
                                        </a>
                                        <?php if (!empty($item['can_manage'])): ?>
                                            <a href="<?= BASE_PATH ?>/file-share/file/<?= (int)$item['id'] ?>" class="btn btn-sm btn-outline-success">
                                                <i class="fas fa-link me-1"></i><?= htmlspecialchars(tr_text('共有設定', 'Share settings')) ?>
                                            </a>
                                            <form method="post" action="<?= BASE_PATH ?>/file-share/file/<?= (int)$item['id'] ?>/delete" class="no-ajax d-inline" onsubmit="return confirm('<?= htmlspecialchars(tr_text('このファイルを削除しますか？', 'Delete this file?'), ENT_QUOTES) ?>');">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)$csrf_token) ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="fas fa-trash me-1"></i><?= htmlspecialchars(tr_text('削除', 'Delete')) ?>
                                                </button>
                                            </form>
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

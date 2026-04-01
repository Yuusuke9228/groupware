<?php
$shareLinks = $shareLinks ?? [];
$orgOptions = $orgOptions ?? [];
$userOptions = $userOptions ?? [];
$item = $item ?? [];
$canManage = !empty($canManage);
$defaultShareExpiry = $defaultShareExpiry ?? '';
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$shareBaseUrl = rtrim($scheme . '://' . $host . BASE_PATH, '/');
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
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
        <div>
            <h4 class="mb-1"><i class="fas fa-file me-2 text-primary"></i><?= htmlspecialchars((string)($item['title'] ?? '')) ?></h4>
            <div class="text-muted small"><?= htmlspecialchars((string)($item['original_name'] ?? '')) ?></div>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= BASE_PATH ?>/drive/download/<?= (int)($item['id'] ?? 0) ?>" class="btn btn-primary btn-sm">
                <i class="fas fa-download me-1"></i><?= htmlspecialchars(tr_text('ダウンロード', 'Download')) ?>
            </a>
            <a href="<?= BASE_PATH ?>/drive" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i><?= htmlspecialchars(tr_text('一覧へ戻る', 'Back')) ?>
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

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0"><?= htmlspecialchars(tr_text('ファイル情報', 'File information')) ?></h6>
                </div>
                <div class="card-body">
                    <table class="table table-borderless mb-0">
                        <tbody>
                            <tr><th style="width:180px;"><?= htmlspecialchars(tr_text('タイトル', 'Title')) ?></th><td><?= htmlspecialchars((string)($item['title'] ?? '')) ?></td></tr>
                            <tr><th><?= htmlspecialchars(tr_text('ファイル名', 'File name')) ?></th><td><?= htmlspecialchars((string)($item['original_name'] ?? '')) ?></td></tr>
                            <tr><th><?= htmlspecialchars(tr_text('サイズ', 'Size')) ?></th><td><?= htmlspecialchars(driveFormatBytes((int)($item['file_size'] ?? 0))) ?></td></tr>
                            <tr><th><?= htmlspecialchars(tr_text('共有単位', 'Scope')) ?></th><td><?= htmlspecialchars((string)($item['owner_type'] ?? '') === 'organization' ? tr_text('組織', 'Organization') : tr_text('個人', 'Personal')) ?></td></tr>
                            <?php if (!empty($item['owner_organization_name'])): ?>
                                <tr><th><?= htmlspecialchars(tr_text('対象組織', 'Owner organization')) ?></th><td><?= htmlspecialchars((string)$item['owner_organization_name']) ?></td></tr>
                            <?php endif; ?>
                            <tr><th><?= htmlspecialchars(tr_text('作成者', 'Creator')) ?></th><td><?= htmlspecialchars((string)($item['creator_name'] ?? '')) ?></td></tr>
                            <tr><th><?= htmlspecialchars(tr_text('更新日時', 'Updated')) ?></th><td><?= htmlspecialchars(date('Y/m/d H:i', strtotime((string)($item['updated_at'] ?? 'now')))) ?></td></tr>
                            <?php if (!empty($item['description'])): ?>
                                <tr><th><?= htmlspecialchars(tr_text('説明', 'Description')) ?></th><td><?= nl2br(htmlspecialchars((string)$item['description'])) ?></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php if ($canManage): ?>
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><?= htmlspecialchars(tr_text('共有リンクを発行', 'Create share link')) ?></h6>
                </div>
                <div class="card-body">
                    <form method="post" action="<?= BASE_PATH ?>/drive/file/<?= (int)($item['id'] ?? 0) ?>/share-links" class="no-ajax">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)$csrf_token) ?>">

                        <div class="mb-2">
                            <label class="form-label small"><?= htmlspecialchars(tr_text('有効期限', 'Expires at')) ?></label>
                            <input type="datetime-local" name="expires_at" class="form-control form-control-sm" value="<?= htmlspecialchars((string)$defaultShareExpiry) ?>">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small"><?= htmlspecialchars(tr_text('ダウンロード上限（空欄で無制限）', 'Download limit (empty = unlimited)')) ?></label>
                            <input type="number" min="1" name="max_downloads" class="form-control form-control-sm" placeholder="20">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small"><?= htmlspecialchars(tr_text('共有パスワード（任意）', 'Share password (optional)')) ?></label>
                            <input type="password" name="share_password" class="form-control form-control-sm">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small"><?= htmlspecialchars(tr_text('共有先組織', 'Target organizations')) ?></label>
                            <select class="form-select form-select-sm select2-multi" name="share_organization_ids[]" multiple data-placeholder="<?= htmlspecialchars(tr_text('組織を選択...', 'Select organizations...')) ?>">
                                <?php foreach ($orgOptions as $org): ?>
                                    <option value="<?= (int)$org['id'] ?>"><?= htmlspecialchars((string)$org['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small"><?= htmlspecialchars(tr_text('共有先ユーザー', 'Target users')) ?></label>
                            <select class="form-select form-select-sm select2-multi" name="share_user_ids[]" multiple data-placeholder="<?= htmlspecialchars(tr_text('ユーザーを選択...', 'Select users...')) ?>">
                                <?php foreach ($userOptions as $user): ?>
                                    <option value="<?= (int)$user['id'] ?>"><?= htmlspecialchars((string)$user['display_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="notify_recipients" name="notify_recipients" value="1" checked>
                            <label class="form-check-label small" for="notify_recipients"><?= htmlspecialchars(tr_text('共有先へ通知（メール連携）', 'Notify recipients (email-enabled)')) ?></label>
                        </div>
                        <button type="submit" class="btn btn-outline-primary btn-sm w-100">
                            <i class="fas fa-link me-1"></i><?= htmlspecialchars(tr_text('共有リンクを作成', 'Create share link')) ?>
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-5">
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0"><?= htmlspecialchars(tr_text('発行済み共有リンク', 'Issued share links')) ?></h6>
                </div>
                <div class="card-body">
                    <?php if (empty($shareLinks)): ?>
                        <div class="text-muted small"><?= htmlspecialchars(tr_text('共有リンクはまだありません。', 'No share links yet.')) ?></div>
                    <?php else: ?>
                        <div class="vstack gap-2">
                            <?php foreach ($shareLinks as $share): ?>
                                <?php
                                $statusLabel = tr_text('有効', 'Active');
                                $statusClass = 'bg-success';
                                if (!empty($share['is_revoked'])) {
                                    $statusLabel = tr_text('無効', 'Revoked');
                                    $statusClass = 'bg-secondary';
                                } elseif (!empty($share['is_expired'])) {
                                    $statusLabel = tr_text('期限切れ', 'Expired');
                                    $statusClass = 'bg-danger';
                                } elseif (!empty($share['is_download_limit_reached'])) {
                                    $statusLabel = tr_text('上限到達', 'Limit reached');
                                    $statusClass = 'bg-warning text-dark';
                                }
                                $publicUrl = $shareBaseUrl . '/drive/share/' . (string)$share['token'];
                                ?>
                                <div class="border rounded p-2">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="badge <?= $statusClass ?>"><?= htmlspecialchars($statusLabel) ?></span>
                                        <small class="text-muted"><?= htmlspecialchars(date('Y/m/d H:i', strtotime((string)$share['created_at']))) ?></small>
                                    </div>
                                    <div class="small text-break mb-1"><code><?= htmlspecialchars($publicUrl) ?></code></div>
                                    <div class="small text-muted mb-1">
                                        DL: <?= (int)$share['download_count'] ?>
                                        <?php if (!is_null($share['max_downloads']) && (int)$share['max_downloads'] > 0): ?>
                                            / <?= (int)$share['max_downloads'] ?>
                                        <?php endif; ?>
                                        <?php if (!empty($share['expires_at'])): ?>
                                            ・<?= htmlspecialchars(tr_text('期限', 'Expiry')) ?>: <?= htmlspecialchars(date('Y/m/d H:i', strtotime((string)$share['expires_at']))) ?>
                                        <?php endif; ?>
                                        <?php if (!empty($share['has_password'])): ?>
                                            ・<?= htmlspecialchars(tr_text('パスワード保護', 'Password protected')) ?>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($share['target_organizations']) || !empty($share['target_users'])): ?>
                                        <div class="small text-muted mb-2">
                                            <?php if (!empty($share['target_organizations'])): ?>
                                                <?= htmlspecialchars(tr_text('組織', 'Organizations')) ?>: <?= htmlspecialchars(implode(', ', $share['target_organizations'])) ?>
                                            <?php endif; ?>
                                            <?php if (!empty($share['target_users'])): ?>
                                                <?php if (!empty($share['target_organizations'])): ?> / <?php endif; ?>
                                                <?= htmlspecialchars(tr_text('ユーザー', 'Users')) ?>: <?= htmlspecialchars(implode(', ', $share['target_users'])) ?>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="d-flex gap-1">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="navigator.clipboard && navigator.clipboard.writeText('<?= htmlspecialchars($publicUrl, ENT_QUOTES) ?>');">
                                            <i class="fas fa-copy me-1"></i><?= htmlspecialchars(tr_text('コピー', 'Copy')) ?>
                                        </button>
                                        <?php if ($canManage && empty($share['is_revoked'])): ?>
                                            <form method="post" action="<?= BASE_PATH ?>/drive/share/<?= (int)$share['id'] ?>/revoke" class="no-ajax ms-auto" onsubmit="return confirm('<?= htmlspecialchars(tr_text('この共有リンクを無効化しますか？', 'Revoke this share link?'), ENT_QUOTES) ?>');">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)$csrf_token) ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="fas fa-ban me-1"></i><?= htmlspecialchars(tr_text('無効化', 'Revoke')) ?>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($canManage): ?>
                <div class="card">
                    <div class="card-body">
                        <form method="post" action="<?= BASE_PATH ?>/drive/file/<?= (int)($item['id'] ?? 0) ?>/delete" class="no-ajax" onsubmit="return confirm('<?= htmlspecialchars(tr_text('このファイルを削除しますか？', 'Delete this file?'), ENT_QUOTES) ?>');">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)$csrf_token) ?>">
                            <button type="submit" class="btn btn-outline-danger w-100">
                                <i class="fas fa-trash me-1"></i><?= htmlspecialchars(tr_text('Driveファイルを削除', 'Delete Drive file')) ?>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

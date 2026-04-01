<?php
$share = $share ?? null;
$status = $status ?? 'not_found';
$message = $message ?? tr_text('共有リンクが見つかりません。', 'Share link not found.');
$passwordError = $passwordError ?? '';
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
<div class="container py-5" style="max-width: 760px;">
    <div class="card shadow-sm">
        <div class="card-body p-4 p-md-5">
            <h1 class="h4 mb-3"><i class="fas fa-cloud-download-alt me-2 text-primary"></i><?= htmlspecialchars(tr_text('ファイル共有リンク', 'File Sharing Link')) ?></h1>
            <p class="text-muted mb-4"><?= htmlspecialchars($message) ?></p>

            <?php if ($status === 'ready' && $share): ?>
                <div class="border rounded p-3 mb-4 bg-light">
                    <div class="mb-2"><strong><?= htmlspecialchars(tr_text('ファイル名', 'File name')) ?>:</strong> <?= htmlspecialchars((string)$share['original_name']) ?></div>
                    <div class="mb-2"><strong><?= htmlspecialchars(tr_text('サイズ', 'Size')) ?>:</strong> <?= htmlspecialchars(driveFormatBytes((int)$share['file_size'])) ?></div>
                    <?php if (!empty($share['expires_at'])): ?>
                        <div class="mb-2"><strong><?= htmlspecialchars(tr_text('有効期限', 'Expires at')) ?>:</strong> <?= htmlspecialchars(date('Y/m/d H:i', strtotime((string)$share['expires_at']))) ?></div>
                    <?php endif; ?>
                    <?php if (!is_null($share['max_downloads']) && (int)$share['max_downloads'] > 0): ?>
                        <div><strong><?= htmlspecialchars(tr_text('ダウンロード回数', 'Downloads')) ?>:</strong> <?= (int)$share['download_count'] ?> / <?= (int)$share['max_downloads'] ?></div>
                    <?php endif; ?>
                </div>
                <form method="post">
                    <input type="hidden" name="download" value="1">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-download me-1"></i><?= htmlspecialchars(tr_text('ダウンロード', 'Download')) ?>
                    </button>
                </form>
            <?php elseif ($status === 'password_required'): ?>
                <?php if ($passwordError !== ''): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($passwordError) ?></div>
                <?php endif; ?>
                <?php if ($share): ?>
                    <div class="border rounded p-3 mb-3 bg-light">
                        <div><strong><?= htmlspecialchars(tr_text('ファイル名', 'File name')) ?>:</strong> <?= htmlspecialchars((string)$share['original_name']) ?></div>
                    </div>
                <?php endif; ?>
                <form method="post" class="d-flex gap-2 flex-wrap">
                    <input type="password" name="share_password" class="form-control" style="max-width: 340px;" placeholder="<?= htmlspecialchars(tr_text('共有パスワード', 'Share password')) ?>" required>
                    <button type="submit" class="btn btn-primary"><?= htmlspecialchars(tr_text('確認', 'Verify')) ?></button>
                </form>
            <?php elseif ($status === 'login_required'): ?>
                <a href="<?= BASE_PATH ?>/login" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt me-1"></i><?= htmlspecialchars(tr_text('ログインへ進む', 'Go to sign in')) ?>
                </a>
            <?php else: ?>
                <a href="<?= BASE_PATH ?>/" class="btn btn-outline-secondary">
                    <i class="fas fa-home me-1"></i><?= htmlspecialchars(tr_text('トップへ戻る', 'Back to home')) ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

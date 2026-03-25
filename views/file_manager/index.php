<!-- views/file_manager/index.php -->
<div class="container-fluid mt-4">
    <!-- ヘッダー -->
    <div class="row mb-3">
        <div class="col">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h4 class="mb-1"><i class="fas fa-cabinet-filing text-primary me-2"></i>
                        <i class="fas fa-folder-open text-primary me-2"></i>ファイル管理
                    </h4>
                    <!-- パンくずリスト -->
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item">
                                <?php if (empty($breadcrumbs) && !$currentFolder): ?>
                                    <span class="text-muted"><i class="fas fa-home me-1"></i>ルート</span>
                                <?php else: ?>
                                    <a href="<?= BASE_PATH ?>/files"><i class="fas fa-home me-1"></i>ルート</a>
                                <?php endif; ?>
                            </li>
                            <?php foreach ($breadcrumbs as $i => $bc): ?>
                                <?php if ($currentFolder && $bc['id'] == $currentFolder['id']): ?>
                                    <li class="breadcrumb-item active"><?= htmlspecialchars($bc['name']) ?></li>
                                <?php else: ?>
                                    <li class="breadcrumb-item">
                                        <a href="<?= BASE_PATH ?>/files/folder/<?= $bc['id'] ?>"><?= htmlspecialchars($bc['name']) ?></a>
                                    </li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ol>
                    </nav>
                </div>
                <div class="d-flex gap-2">
                    <?php if (!isset($canManageCurrentFolder) || $canManageCurrentFolder): ?>
                        <a href="<?= BASE_PATH ?>/files/folder/create<?= $currentFolder ? '?parent_id=' . $currentFolder['id'] : '' ?>" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-folder-plus me-1"></i>新規フォルダ
                        </a>
                        <a href="<?= BASE_PATH ?>/files/upload<?= $currentFolder ? '?folder_id=' . $currentFolder['id'] : '' ?>" class="btn btn-primary btn-sm">
                            <i class="fas fa-upload me-1"></i>アップロード
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- フラッシュメッセージ -->
    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-1"></i><?= htmlspecialchars($_SESSION['flash_success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>
    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-1"></i><?= htmlspecialchars($_SESSION['flash_error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <!-- 検索バー -->
    <div class="card mb-3">
        <div class="card-body py-2">
            <form method="get" action="<?= BASE_PATH ?>/files" class="d-flex gap-2 align-items-center">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" class="form-control" name="q" placeholder="ファイル名・フォルダ名で検索..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <button type="submit" class="btn btn-sm btn-outline-secondary">検索</button>
                <?php if ($search): ?>
                    <a href="<?= BASE_PATH ?>/files" class="btn btn-sm btn-outline-danger">クリア</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <?php if ($search): ?>
        <div class="alert alert-info py-2">
            <i class="fas fa-info-circle me-1"></i>「<?= htmlspecialchars($search) ?>」の検索結果: フォルダ <?= count($folders) ?>件、ファイル <?= count($files) ?>件
        </div>
    <?php endif; ?>

    <div class="row g-3">
        <div class="col-xl-9">
            <!-- フォルダ情報 -->
            <?php if ($currentFolder): ?>
                <div class="card mb-3">
                    <div class="card-body py-2 d-flex justify-content-between align-items-center">
                        <div>
                            <strong><i class="fas fa-folder text-warning me-1"></i><?= htmlspecialchars($currentFolder['name']) ?></strong>
                            <?php if ($currentFolder['description']): ?>
                                <span class="text-muted ms-2"><?= htmlspecialchars($currentFolder['description']) ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if (!isset($canManageCurrentFolder) || $canManageCurrentFolder): ?>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-cog"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="<?= BASE_PATH ?>/files/folder/<?= $currentFolder['id'] ?>/edit"><i class="fas fa-edit me-2"></i>フォルダ編集</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form method="post" action="<?= BASE_PATH ?>/files/folder/<?= $currentFolder['id'] ?>/delete" class="no-ajax" onsubmit="return confirm('このフォルダとその中のファイルをすべて削除しますか？');">
                                            <input type="hidden" name="csrf_token" value="<?= $this->generateCsrfToken() ?>">
                                            <button type="submit" class="dropdown-item text-danger"><i class="fas fa-trash me-2"></i>フォルダ削除</button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- フォルダ一覧 -->
            <?php if (!empty($folders)): ?>
                <div class="mb-4">
                    <h6 class="text-muted mb-2"><i class="fas fa-folder me-1"></i>フォルダ (<?= count($folders) ?>)</h6>
                    <div class="row g-3">
                        <?php foreach ($folders as $folder): ?>
                            <div class="col-sm-6 col-md-4 col-lg-3">
                                <a href="<?= BASE_PATH ?>/files/folder/<?= $folder['id'] ?>" class="card text-decoration-none h-100 folder-card">
                                    <div class="card-body py-3">
                                        <div class="d-flex align-items-center">
                                            <div class="me-3">
                                                <i class="fas fa-folder fa-2x text-warning"></i>
                                            </div>
                                            <div class="overflow-hidden">
                                                <div class="fw-bold text-dark text-truncate"><?= htmlspecialchars($folder['name']) ?></div>
                                                <small class="text-muted">
                                                    <?php if ($folder['subfolder_count'] > 0): ?>
                                                        <i class="fas fa-folder me-1"></i><?= $folder['subfolder_count'] ?>
                                                    <?php endif; ?>
                                                    <i class="fas fa-file ms-1 me-1"></i><?= $folder['file_count'] ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- ファイル一覧 -->
            <?php if (!empty($files)): ?>
                <div class="mb-4">
                    <h6 class="text-muted mb-2"><i class="fas fa-file me-1"></i>ファイル (<?= count($files) ?>)</h6>
                    <div class="card">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:40px;"></th>
                                        <th>タイトル</th>
                                        <th class="d-none d-md-table-cell">サイズ</th>
                                        <th class="d-none d-md-table-cell">更新日時</th>
                                        <th class="d-none d-lg-table-cell">アップロード者</th>
                                        <th class="d-none d-lg-table-cell text-center">Ver.</th>
                                        <th class="d-none d-lg-table-cell text-center">DL数</th>
                                        <th style="width:80px;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($files as $file): ?>
                                        <tr>
                                            <td class="text-center">
                                                <?= getFileIcon($file['mime_type'], $file['original_name']) ?>
                                            </td>
                                            <td>
                                                <a href="<?= BASE_PATH ?>/files/file/<?= $file['id'] ?>" class="text-decoration-none fw-bold">
                                                    <?= htmlspecialchars($file['title']) ?>
                                                </a>
                                                <?php if (($file['approval_status'] ?? 'none') === 'pending'): ?>
                                                    <span class="badge bg-warning text-dark ms-2">承認待ち</span>
                                                <?php elseif (($file['approval_status'] ?? 'none') === 'approved'): ?>
                                                    <span class="badge bg-success ms-2">承認済み</span>
                                                <?php elseif (($file['approval_status'] ?? 'none') === 'rejected'): ?>
                                                    <span class="badge bg-danger ms-2">差し戻し</span>
                                                <?php endif; ?>
                                                <?php if (!empty($file['checked_out_by'])): ?>
                                                    <span class="badge bg-secondary ms-1"><i class="fas fa-lock me-1"></i>Checkout</span>
                                                <?php endif; ?>
                                                <br>
                                                <small class="text-muted"><?= htmlspecialchars($file['original_name']) ?></small>
                                            </td>
                                            <td class="d-none d-md-table-cell text-nowrap">
                                                <small><?= formatFileSize($file['file_size']) ?></small>
                                            </td>
                                            <td class="d-none d-md-table-cell text-nowrap">
                                                <small><?= date('Y/m/d H:i', strtotime($file['updated_at'])) ?></small>
                                            </td>
                                            <td class="d-none d-lg-table-cell">
                                                <small><?= htmlspecialchars($file['uploader_name'] ?? '不明') ?></small>
                                            </td>
                                            <td class="d-none d-lg-table-cell text-center">
                                                <span class="badge bg-secondary"><?= $file['version'] ?></span>
                                            </td>
                                            <td class="d-none d-lg-table-cell text-center">
                                                <small class="text-muted"><?= $file['download_count'] ?></small>
                                            </td>
                                            <td class="text-end">
                                                <a href="<?= BASE_PATH ?>/files/download/<?= $file['id'] ?>" class="btn btn-sm btn-outline-primary" title="ダウンロード">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- 空表示 -->
            <?php if (empty($folders) && empty($files)): ?>
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                        <p class="text-muted mb-3">
                            <?php if ($search): ?>
                                検索結果が見つかりませんでした。
                            <?php else: ?>
                                フォルダやファイルがまだありません。
                            <?php endif; ?>
                        </p>
                        <?php if (!$search): ?>
                            <div class="d-flex justify-content-center gap-2">
                                <a href="<?= BASE_PATH ?>/files/folder/create<?= $currentFolder ? '?parent_id=' . $currentFolder['id'] : '' ?>" class="btn btn-outline-primary">
                                    <i class="fas fa-folder-plus me-1"></i>フォルダを作成
                                </a>
                                <a href="<?= BASE_PATH ?>/files/upload<?= $currentFolder ? '?folder_id=' . $currentFolder['id'] : '' ?>" class="btn btn-primary">
                                    <i class="fas fa-upload me-1"></i>ファイルをアップロード
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-xl-3">
            <div class="card mb-3">
                <div class="card-header py-2">
                    <strong><i class="fas fa-stream me-1"></i>最近の更新</strong>
                </div>
                <div class="list-group list-group-flush">
                    <?php if (empty($recentActivities)): ?>
                        <div class="p-3 text-muted small">最近の更新はありません。</div>
                    <?php else: ?>
                        <?php foreach ($recentActivities as $activity): ?>
                            <a href="<?= BASE_PATH ?>/files/file/<?= $activity['id'] ?>" class="list-group-item list-group-item-action">
                                <div class="fw-bold small text-truncate"><?= htmlspecialchars($activity['title']) ?></div>
                                <div class="text-muted small">
                                    <?= htmlspecialchars($activity['uploader_name'] ?? '不明') ?>
                                    <?php if (!empty($activity['folder_name'])): ?>
                                        ・<?= htmlspecialchars($activity['folder_name']) ?>
                                    <?php endif; ?>
                                </div>
                                <div class="text-muted" style="font-size:0.75rem;">
                                    Ver.<?= (int)$activity['version'] ?> ・ <?= date('m/d H:i', strtotime($activity['updated_at'])) ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
/**
 * ファイルアイコンを取得
 */
function getFileIcon($mimeType, $filename = '') {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    // 拡張子ベースの判定
    $iconMap = [
        'pdf'  => '<i class="fas fa-file-pdf fa-lg" style="color:#e74c3c;"></i>',
        'doc'  => '<i class="fas fa-file-word fa-lg" style="color:#2b579a;"></i>',
        'docx' => '<i class="fas fa-file-word fa-lg" style="color:#2b579a;"></i>',
        'xls'  => '<i class="fas fa-file-excel fa-lg" style="color:#217346;"></i>',
        'xlsx' => '<i class="fas fa-file-excel fa-lg" style="color:#217346;"></i>',
        'ppt'  => '<i class="fas fa-file-powerpoint fa-lg" style="color:#d24726;"></i>',
        'pptx' => '<i class="fas fa-file-powerpoint fa-lg" style="color:#d24726;"></i>',
        'zip'  => '<i class="fas fa-file-archive fa-lg" style="color:#f39c12;"></i>',
        'rar'  => '<i class="fas fa-file-archive fa-lg" style="color:#f39c12;"></i>',
        '7z'   => '<i class="fas fa-file-archive fa-lg" style="color:#f39c12;"></i>',
        'csv'  => '<i class="fas fa-file-csv fa-lg" style="color:#217346;"></i>',
        'txt'  => '<i class="fas fa-file-alt fa-lg" style="color:#6c757d;"></i>',
        'jpg'  => '<i class="fas fa-file-image fa-lg" style="color:#8e44ad;"></i>',
        'jpeg' => '<i class="fas fa-file-image fa-lg" style="color:#8e44ad;"></i>',
        'png'  => '<i class="fas fa-file-image fa-lg" style="color:#8e44ad;"></i>',
        'gif'  => '<i class="fas fa-file-image fa-lg" style="color:#8e44ad;"></i>',
        'svg'  => '<i class="fas fa-file-image fa-lg" style="color:#8e44ad;"></i>',
        'mp4'  => '<i class="fas fa-file-video fa-lg" style="color:#e67e22;"></i>',
        'mp3'  => '<i class="fas fa-file-audio fa-lg" style="color:#1abc9c;"></i>',
        'html' => '<i class="fas fa-file-code fa-lg" style="color:#e44d26;"></i>',
        'css'  => '<i class="fas fa-file-code fa-lg" style="color:#264de4;"></i>',
        'js'   => '<i class="fas fa-file-code fa-lg" style="color:#f7df1e;"></i>',
        'php'  => '<i class="fas fa-file-code fa-lg" style="color:#777bb4;"></i>',
    ];

    if (isset($iconMap[$ext])) {
        return $iconMap[$ext];
    }

    // MIMEタイプベースのフォールバック
    if ($mimeType) {
        if (strpos($mimeType, 'image/') === 0) return '<i class="fas fa-file-image fa-lg" style="color:#8e44ad;"></i>';
        if (strpos($mimeType, 'video/') === 0) return '<i class="fas fa-file-video fa-lg" style="color:#e67e22;"></i>';
        if (strpos($mimeType, 'audio/') === 0) return '<i class="fas fa-file-audio fa-lg" style="color:#1abc9c;"></i>';
        if (strpos($mimeType, 'text/') === 0) return '<i class="fas fa-file-alt fa-lg" style="color:#6c757d;"></i>';
    }

    return '<i class="fas fa-file fa-lg" style="color:#95a5a6;"></i>';
}

/**
 * ファイルサイズをフォーマット
 */
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 1) . ' GB';
    if ($bytes >= 1048576) return number_format($bytes / 1048576, 1) . ' MB';
    if ($bytes >= 1024) return number_format($bytes / 1024, 1) . ' KB';
    return $bytes . ' B';
}
?>

<style>
.folder-card {
    transition: all 0.15s ease;
    border: 1px solid #e0e0e0;
}
.folder-card:hover {
    border-color: #2b7de9;
    box-shadow: 0 2px 8px rgba(43,125,233,0.12);
    transform: translateY(-1px);
}
</style>

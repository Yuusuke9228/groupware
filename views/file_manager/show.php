<!-- views/file_manager/show.php -->
<?php
// ヘルパー関数（indexで定義済みでない場合）
$permissionSummary = $permissionSummary ?? [
    'view' => ['organizations' => [], 'users' => []],
    'edit' => ['organizations' => [], 'users' => []],
    'approve' => ['organizations' => [], 'users' => []],
    'admin' => ['organizations' => [], 'users' => []],
];
?>
<?php
if (!function_exists('getFileIcon')) {
    function getFileIcon($mimeType, $filename = '') {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
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
            'csv'  => '<i class="fas fa-file-csv fa-lg" style="color:#217346;"></i>',
            'txt'  => '<i class="fas fa-file-alt fa-lg" style="color:#6c757d;"></i>',
            'jpg'  => '<i class="fas fa-file-image fa-lg" style="color:#8e44ad;"></i>',
            'jpeg' => '<i class="fas fa-file-image fa-lg" style="color:#8e44ad;"></i>',
            'png'  => '<i class="fas fa-file-image fa-lg" style="color:#8e44ad;"></i>',
            'gif'  => '<i class="fas fa-file-image fa-lg" style="color:#8e44ad;"></i>',
            'svg'  => '<i class="fas fa-file-image fa-lg" style="color:#8e44ad;"></i>',
            'mp4'  => '<i class="fas fa-file-video fa-lg" style="color:#e67e22;"></i>',
            'mp3'  => '<i class="fas fa-file-audio fa-lg" style="color:#1abc9c;"></i>',
        ];
        if (isset($iconMap[$ext])) return $iconMap[$ext];
        if ($mimeType && strpos($mimeType, 'image/') === 0) return '<i class="fas fa-file-image fa-lg" style="color:#8e44ad;"></i>';
        return '<i class="fas fa-file fa-lg" style="color:#95a5a6;"></i>';
    }
}
if (!function_exists('formatFileSize')) {
    function formatFileSize($bytes) {
        if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 1) . ' GB';
        if ($bytes >= 1048576) return number_format($bytes / 1048576, 1) . ' MB';
        if ($bytes >= 1024) return number_format($bytes / 1024, 1) . ' KB';
        return $bytes . ' B';
    }
}
?>

<div class="container-fluid mt-4">
    <!-- ヘッダー -->
    <div class="row mb-3">
        <div class="col">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                <div>
                    <h4 class="mb-1">
                        <?= getFileIcon($file['mime_type'], $file['original_name']) ?>
                        <span class="ms-2"><?= htmlspecialchars($file['title']) ?></span>
                    </h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="<?= BASE_PATH ?>/files"><i class="fas fa-home me-1"></i>ルート</a></li>
                            <?php foreach ($breadcrumbs as $bc): ?>
                                <li class="breadcrumb-item">
                                    <a href="<?= BASE_PATH ?>/files/folder/<?= $bc['id'] ?>"><?= htmlspecialchars($bc['name']) ?></a>
                                </li>
                            <?php endforeach; ?>
                            <li class="breadcrumb-item active"><?= htmlspecialchars($file['title']) ?></li>
                        </ol>
                    </nav>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= BASE_PATH ?>/files/download/<?= $file['id'] ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-download me-1"></i>ダウンロード
                    </a>
                    <?php if (!empty($canEditFile) && empty($file['checked_out_by'])): ?>
                        <form method="post" action="<?= BASE_PATH ?>/files/file/<?= $file['id'] ?>/checkout" class="no-ajax">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                            <button type="submit" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-lock me-1"></i>チェックアウト
                            </button>
                        </form>
                    <?php elseif (!empty($file['checked_out_by']) && !empty($canReleaseCheckout)): ?>
                        <form method="post" action="<?= BASE_PATH ?>/files/file/<?= $file['id'] ?>/checkin" class="no-ajax">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                            <button type="submit" class="btn btn-outline-success btn-sm">
                                <i class="fas fa-unlock me-1"></i>チェックアウト解除
                            </button>
                        </form>
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

    <div class="row g-4">
        <!-- ファイル情報 -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fas fa-info-circle me-1"></i>ファイル情報</h6>
                    <?php if (!empty($canAdminFile)): ?>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <form method="post" action="<?= BASE_PATH ?>/files/file/<?= $file['id'] ?>/delete" class="no-ajax" onsubmit="return confirm('このファイルを削除しますか？すべてのバージョンも削除されます。');">
                                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                        <button type="submit" class="dropdown-item text-danger"><i class="fas fa-trash me-2"></i>ファイル削除</button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                    <table class="table table-borderless mb-0">
                        <tbody>
                            <tr>
                                <th class="text-muted" style="width:140px;">タイトル</th>
                                <td><?= htmlspecialchars($file['title']) ?></td>
                            </tr>
                            <tr>
                                <th class="text-muted">ファイル名</th>
                                <td><?= htmlspecialchars($file['original_name']) ?></td>
                            </tr>
                            <?php if ($file['description']): ?>
                            <tr>
                                <th class="text-muted">説明</th>
                                <td><?= nl2br(htmlspecialchars($file['description'])) ?></td>
                            </tr>
                            <?php endif; ?>
                            <tr>
                                <th class="text-muted">ファイルサイズ</th>
                                <td><?= formatFileSize($file['file_size']) ?></td>
                            </tr>
                            <tr>
                                <th class="text-muted">MIMEタイプ</th>
                                <td><code><?= htmlspecialchars($file['mime_type']) ?></code></td>
                            </tr>
                            <tr>
                                <th class="text-muted">現バージョン</th>
                                <td><span class="badge bg-primary">Ver. <?= $file['version'] ?></span></td>
                            </tr>
                            <tr>
                                <th class="text-muted">承認状態</th>
                                <td>
                                    <?php if (($file['approval_status'] ?? 'none') === 'pending'): ?>
                                        <span class="badge bg-warning text-dark">承認待ち</span>
                                    <?php elseif (($file['approval_status'] ?? 'none') === 'approved'): ?>
                                        <span class="badge bg-success">承認済み</span>
                                    <?php elseif (($file['approval_status'] ?? 'none') === 'rejected'): ?>
                                        <span class="badge bg-danger">差し戻し</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">なし</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th class="text-muted">チェックアウト</th>
                                <td>
                                    <?php if (!empty($file['checked_out_by'])): ?>
                                        <span class="badge bg-secondary">
                                            <?= htmlspecialchars($file['checked_out_user_name'] ?? ('ユーザー#' . $file['checked_out_by'])) ?>
                                        </span>
                                        <small class="text-muted ms-2"><?= !empty($file['checked_out_at']) ? date('Y/m/d H:i', strtotime($file['checked_out_at'])) : '' ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">未チェックアウト</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th class="text-muted">ダウンロード数</th>
                                <td><i class="fas fa-download me-1 text-muted"></i><?= $file['download_count'] ?> 回</td>
                            </tr>
                            <tr>
                                <th class="text-muted">アップロード者</th>
                                <td><?= htmlspecialchars($file['uploader_name'] ?? '不明') ?></td>
                            </tr>
                            <tr>
                                <th class="text-muted">作成日時</th>
                                <td><?= date('Y年m月d日 H:i', strtotime($file['created_at'])) ?></td>
                            </tr>
                            <tr>
                                <th class="text-muted">更新日時</th>
                                <td><?= date('Y年m月d日 H:i', strtotime($file['updated_at'])) ?></td>
                            </tr>
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>

            <!-- バージョン履歴 -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h6 class="mb-0"><i class="fas fa-history me-1"></i>バージョン履歴</h6>
                    <form method="get" class="d-flex gap-2 align-items-center">
                        <select name="compare_from" class="form-select form-select-sm">
                            <option value="">比較元</option>
                            <?php foreach ($versions as $v): ?>
                                <option value="<?= (int)$v['id'] ?>" <?= (int)($_GET['compare_from'] ?? 0) === (int)$v['id'] ? 'selected' : '' ?>>
                                    Ver.<?= (int)$v['version_number'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <select name="compare_to" class="form-select form-select-sm">
                            <option value="">比較先</option>
                            <?php foreach ($versions as $v): ?>
                                <option value="<?= (int)$v['id'] ?>" <?= (int)($_GET['compare_to'] ?? 0) === (int)$v['id'] ? 'selected' : '' ?>>
                                    Ver.<?= (int)$v['version_number'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-outline-secondary btn-sm">比較</button>
                    </form>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($versions)): ?>
                        <div class="text-center text-muted py-4">バージョン履歴はありません。</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:70px;" class="text-center">Ver.</th>
                                        <th>ファイル名</th>
                                        <th class="d-none d-md-table-cell">サイズ</th>
                                        <th class="d-none d-md-table-cell">アップロード者</th>
                                        <th>日時</th>
                                        <th class="d-none d-lg-table-cell">コメント</th>
                                        <th style="width:60px;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($versions as $v): ?>
                                        <tr>
                                            <td class="text-center">
                                                <span class="badge <?= $v['version_number'] == $file['version'] ? 'bg-primary' : 'bg-secondary' ?>">
                                                    <?= $v['version_number'] ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small><?= htmlspecialchars($v['original_name']) ?></small>
                                            </td>
                                            <td class="d-none d-md-table-cell">
                                                <small class="text-muted"><?= formatFileSize($v['file_size']) ?></small>
                                            </td>
                                            <td class="d-none d-md-table-cell">
                                                <small><?= htmlspecialchars($v['uploader_name'] ?? '不明') ?></small>
                                            </td>
                                            <td>
                                                <small class="text-muted"><?= date('Y/m/d H:i', strtotime($v['created_at'])) ?></small>
                                            </td>
                                            <td class="d-none d-lg-table-cell">
                                                <small class="text-muted"><?= htmlspecialchars($v['comment'] ?? '') ?></small>
                                            </td>
                                            <td>
                                                <a href="<?= BASE_PATH ?>/files/download/<?= $file['id'] ?>?version=<?= $v['id'] ?>"
                                                   class="btn btn-sm btn-outline-secondary" title="ダウンロード">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($comparison)): ?>
                <div class="card mt-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-code-compare me-1"></i>版比較: Ver.<?= (int)$comparison['left']['version_number'] ?> → Ver.<?= (int)$comparison['right']['version_number'] ?></h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small"><?= htmlspecialchars($comparison['summary']) ?></p>
                        <?php if (($comparison['mode'] ?? '') === 'text' && !empty($comparison['diff'])): ?>
                            <pre class="bg-dark text-light p-3 small mb-0" style="white-space:pre-wrap;max-height:420px;overflow:auto;"><?= htmlspecialchars($comparison['diff']) ?></pre>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- サイドバー: 新バージョンアップロード -->
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-upload me-1"></i>新しいバージョンをアップロード</h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($canEditFile)): ?>
                    <form method="post" action="<?= BASE_PATH ?>/files/file/<?= $file['id'] ?>/update" enctype="multipart/form-data" class="no-ajax">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                        <div class="mb-3">
                            <label for="updateFile" class="form-label">ファイル <span class="text-danger">*</span></label>
                            <input type="file" class="form-control form-control-sm" id="updateFile" name="file" required>
                            <div class="form-text">新しいバージョンとしてアップロードされます。</div>
                        </div>

                        <div class="mb-3">
                            <label for="comment" class="form-label">変更コメント</label>
                            <textarea class="form-control form-control-sm" id="comment" name="comment" rows="2"
                                      placeholder="変更内容を入力"></textarea>
                        </div>

                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="require_approval" name="require_approval" value="1">
                            <label class="form-check-label" for="require_approval">更新後に承認フローへ回す</label>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">承認者</label>
                            <select class="form-select form-select-sm select2-multi" name="approval_user_ids[]" multiple data-placeholder="承認者を選択...">
                                <?php foreach ($users as $user): ?>
                                    <option value="<?= (int)$user['id'] ?>"><?= htmlspecialchars($user['display_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="approval_comment" class="form-label">承認依頼コメント</label>
                            <textarea class="form-control form-control-sm" id="approval_comment" name="approval_comment" rows="2"></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            <i class="fas fa-upload me-1"></i>アップロード
                        </button>
                    </form>
                    <?php else: ?>
                        <div class="text-muted small">このファイルを更新する権限がありません。</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-stamp me-1"></i>承認フロー</h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($activeApprovalRequest)): ?>
                        <div class="mb-2 small text-muted">最新申請: <?= date('Y/m/d H:i', strtotime($activeApprovalRequest['created_at'])) ?></div>
                        <?php foreach ($activeApprovalRequest['steps'] as $step): ?>
                            <div class="border rounded p-2 mb-2">
                                <div class="fw-bold small"><?= htmlspecialchars($step['approver_name'] ?? '不明') ?></div>
                                <div class="small text-muted">Step <?= (int)$step['step_order'] ?> / <?= htmlspecialchars($step['status']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <?php if (!empty($canEditFile)): ?>
                        <form method="post" action="<?= BASE_PATH ?>/files/file/<?= $file['id'] ?>/request-approval" class="no-ajax">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                            <div class="mb-3">
                                <label class="form-label">承認者</label>
                                <select class="form-select form-select-sm select2-multi" name="approval_user_ids[]" multiple data-placeholder="承認者を選択...">
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?= (int)$user['id'] ?>"><?= htmlspecialchars($user['display_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">コメント</label>
                                <textarea class="form-control form-control-sm" name="approval_comment" rows="2"></textarea>
                            </div>
                            <button type="submit" class="btn btn-outline-primary btn-sm w-100">
                                <i class="fas fa-paper-plane me-1"></i>現バージョンを承認依頼
                            </button>
                        </form>
                    <?php endif; ?>

                    <?php if (!empty($canApproveFile) && !empty($activeApprovalRequest) && ($activeApprovalRequest['status'] ?? '') === 'pending'): ?>
                        <hr>
                        <form method="post" action="<?= BASE_PATH ?>/files/approval/<?= (int)$activeApprovalRequest['id'] ?>/approve" class="no-ajax mb-2">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                            <textarea class="form-control form-control-sm mb-2" name="comment" rows="2" placeholder="承認コメント"></textarea>
                            <button type="submit" class="btn btn-success btn-sm w-100">承認する</button>
                        </form>
                        <form method="post" action="<?= BASE_PATH ?>/files/approval/<?= (int)$activeApprovalRequest['id'] ?>/reject" class="no-ajax">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                            <textarea class="form-control form-control-sm mb-2" name="comment" rows="2" placeholder="差し戻しコメント"></textarea>
                            <button type="submit" class="btn btn-outline-danger btn-sm w-100">差し戻す</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($canAdminFile)): ?>
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-key me-1"></i>権限設定</h6>
                </div>
                <div class="card-body">
                    <form method="post" action="<?= BASE_PATH ?>/files/file/<?= $file['id'] ?>/permissions" class="no-ajax">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        <?php foreach (['view' => '閲覧', 'edit' => '編集', 'approve' => '承認', 'admin' => '管理'] as $permissionType => $label): ?>
                            <?php
                            $selectedOrgIds = array_map('intval', array_column($permissionSummary[$permissionType]['organizations'] ?? [], 'id'));
                            $selectedUserIds = array_map('intval', array_column($permissionSummary[$permissionType]['users'] ?? [], 'id'));
                            ?>
                            <div class="mb-3">
                                <div class="fw-bold small text-uppercase text-muted mb-1"><?= $label ?>権限</div>
                                <label class="form-label small text-muted mb-1">組織</label>
                                <select class="form-select form-select-sm select2-multi mb-2" name="<?= $permissionType ?>_organization_ids[]" multiple data-placeholder="組織を選択...">
                                    <?php foreach ($organizations as $organization): ?>
                                        <option value="<?= (int)$organization['id'] ?>" <?= in_array((int)$organization['id'], $selectedOrgIds, true) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($organization['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <label class="form-label small text-muted mb-1">ユーザー</label>
                                <select class="form-select form-select-sm select2-multi" name="<?= $permissionType ?>_user_ids[]" multiple data-placeholder="ユーザーを選択...">
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?= (int)$user['id'] ?>" <?= in_array((int)$user['id'], $selectedUserIds, true) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($user['display_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endforeach; ?>
                        <button type="submit" class="btn btn-outline-secondary btn-sm w-100">権限を更新</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- 戻るリンク -->
            <div class="mt-3">
                <a href="<?= $file['folder_id'] ? BASE_PATH . '/files/folder/' . $file['folder_id'] : BASE_PATH . '/files' ?>"
                   class="btn btn-outline-secondary btn-sm w-100">
                    <i class="fas fa-arrow-left me-1"></i>一覧に戻る
                </a>
            </div>
        </div>
    </div>
</div>

<?php
// views/bulletin/form.php
$isEdit = !empty($post);
$formAction = $isEdit
    ? BASE_PATH . '/bulletin/' . $post['id'] . '/update'
    : BASE_PATH . '/bulletin';
$pageLabel = $isEdit ? '投稿を編集' : '新規投稿';
$visibility = $isEdit ? ($post['visibility'] ?? 'all') : ($_POST['visibility'] ?? 'all');
?>

<div class="container-fluid py-3">
    <!-- パンくず -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb mb-0" style="font-size:0.85rem;">
            <li class="breadcrumb-item"><a href="<?php echo BASE_PATH; ?>/bulletin">掲示板</a></li>
            <?php if ($isEdit): ?>
                <li class="breadcrumb-item"><a href="<?php echo BASE_PATH; ?>/bulletin/<?php echo $post['id']; ?>"><?php echo htmlspecialchars($post['title']); ?></a></li>
            <?php endif; ?>
            <li class="breadcrumb-item active"><?php echo $pageLabel; ?></li>
        </ol>
    </nav>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card">
                <div class="card-header py-2">
                    <strong><i class="fas fa-edit me-1"></i><?php echo $pageLabel; ?></strong>
                </div>
                <div class="card-body">
                    <form method="post" action="<?php echo $formAction; ?>" enctype="multipart/form-data" class="no-ajax">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

                        <!-- タイトル -->
                        <div class="mb-3">
                            <label for="title" class="form-label fw-bold">タイトル <span class="text-danger">*</span></label>
                            <input type="text" name="title" id="title" class="form-control"
                                   value="<?php echo htmlspecialchars($isEdit ? $post['title'] : ($_POST['title'] ?? '')); ?>"
                                   required maxlength="255" placeholder="投稿タイトルを入力">
                        </div>

                        <!-- カテゴリ -->
                        <div class="mb-3">
                            <label for="category_id" class="form-label fw-bold">カテゴリ</label>
                            <select name="category_id" id="category_id" class="form-select">
                                <option value="">カテゴリなし</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>"
                                        <?php echo ($isEdit && $post['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="visibility" class="form-label fw-bold">公開範囲</label>
                            <select name="visibility" id="visibility" class="form-select">
                                <option value="all" <?php echo $visibility === 'all' ? 'selected' : ''; ?>>全社</option>
                                <option value="organization" <?php echo $visibility === 'organization' ? 'selected' : ''; ?>>組織</option>
                                <option value="specific" <?php echo $visibility === 'specific' ? 'selected' : ''; ?>>指定ユーザー</option>
                            </select>
                        </div>

                        <div class="mb-3 visibility-target visibility-organization" style="<?php echo $visibility === 'organization' ? '' : 'display:none;'; ?>">
                            <label for="target_organization_ids" class="form-label fw-bold">公開対象組織</label>
                            <select name="target_organization_ids[]" id="target_organization_ids" class="form-select" multiple size="8">
                                <?php foreach ($organizations as $organization): ?>
                                    <option value="<?php echo $organization['id']; ?>" <?php echo in_array((int)$organization['id'], array_map('intval', $targetOrganizationIds ?? []), true) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($organization['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">複数選択できます。</div>
                        </div>

                        <div class="mb-3 visibility-target visibility-specific" style="<?php echo $visibility === 'specific' ? '' : 'display:none;'; ?>">
                            <label for="target_user_ids" class="form-label fw-bold">公開対象ユーザー</label>
                            <select name="target_user_ids[]" id="target_user_ids" class="form-select" multiple size="10">
                                <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user['id']; ?>" <?php echo in_array((int)$user['id'], array_map('intval', $targetUserIds ?? []), true) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($user['display_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">複数選択できます。</div>
                        </div>

                        <!-- 本文 -->
                        <div class="mb-3">
                            <label for="body" class="form-label fw-bold">本文 <span class="text-danger">*</span></label>
                            <textarea name="body" id="body" class="form-control" rows="14" required
                                      placeholder="本文を入力してください..."><?php echo htmlspecialchars($isEdit ? $post['body'] : ($_POST['body'] ?? '')); ?></textarea>
                        </div>

                        <!-- オプション -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_pinned" id="is_pinned" value="1"
                                        <?php echo ($isEdit && $post['is_pinned']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_pinned">
                                        <i class="fas fa-thumbtack text-warning me-1"></i>ピン留め（上部に固定表示）
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold mb-1">ステータス</label>
                                <select name="status" class="form-select form-select-sm">
                                    <option value="published" <?php echo ($isEdit && $post['status'] === 'published') ? 'selected' : ''; ?>>公開</option>
                                    <option value="draft" <?php echo ($isEdit && $post['status'] === 'draft') ? 'selected' : ''; ?>>下書き</option>
                                </select>
                            </div>
                        </div>

                        <!-- 既存の添付ファイル（編集時） -->
                        <?php if ($isEdit && !empty($attachments)): ?>
                            <div class="mb-3">
                                <label class="form-label fw-bold">既存の添付ファイル</label>
                                <div class="list-group">
                                    <?php foreach ($attachments as $attach): ?>
                                        <div class="list-group-item d-flex justify-content-between align-items-center py-2">
                                            <div>
                                                <i class="fas fa-file me-2 text-muted"></i>
                                                <a href="<?php echo BASE_PATH; ?>/uploads/bulletin/<?php echo htmlspecialchars($attach['filename']); ?>" target="_blank">
                                                    <?php echo htmlspecialchars($attach['original_name']); ?>
                                                </a>
                                                <small class="text-muted ms-2">(<?php echo number_format($attach['file_size'] / 1024, 1); ?> KB)</small>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="delete_attachments[]" value="<?php echo $attach['id']; ?>" id="del_attach_<?php echo $attach['id']; ?>">
                                                <label class="form-check-label text-danger small" for="del_attach_<?php echo $attach['id']; ?>">削除</label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- 新規添付ファイル -->
                        <div class="mb-4">
                            <label for="attachments" class="form-label fw-bold">添付ファイル</label>
                            <input type="file" name="attachments[]" id="attachments" class="form-control" multiple>
                            <div class="form-text">複数ファイルを選択できます。</div>
                        </div>

                        <!-- ボタン -->
                        <div class="d-flex justify-content-between">
                            <a href="<?php echo $isEdit ? BASE_PATH . '/bulletin/' . $post['id'] : BASE_PATH . '/bulletin'; ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i>キャンセル
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i><?php echo $isEdit ? '更新' : '投稿'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var visibility = document.getElementById('visibility');
    if (!visibility) {
        return;
    }

    function toggleVisibilityTargets() {
        document.querySelectorAll('.visibility-target').forEach(function (element) {
            element.style.display = 'none';
        });

        if (visibility.value === 'organization') {
            document.querySelector('.visibility-organization').style.display = '';
        } else if (visibility.value === 'specific') {
            document.querySelector('.visibility-specific').style.display = '';
        }
    }

    visibility.addEventListener('change', toggleVisibilityTargets);
    toggleVisibilityTargets();
});
</script>

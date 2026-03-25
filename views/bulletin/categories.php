<?php
// views/bulletin/categories.php
?>

<div class="container-fluid py-3">
    <!-- パンくず -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb mb-0" style="font-size:0.85rem;">
            <li class="breadcrumb-item"><a href="<?php echo BASE_PATH; ?>/bulletin">掲示板</a></li>
            <li class="breadcrumb-item active">カテゴリ管理</li>
        </ol>
    </nav>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h1 class="h4 mb-0"><i class="fas fa-folder-open me-2 text-primary"></i>カテゴリ管理</h1>
                <a href="<?php echo BASE_PATH; ?>/bulletin" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i>掲示板に戻る
                </a>
            </div>

            <!-- カテゴリ一覧 -->
            <div class="card mb-4">
                <div class="card-header py-2">
                    <strong>カテゴリ一覧</strong>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($categories)): ?>
                        <div class="text-center py-4 text-muted">
                            <p>カテゴリがまだありません。</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th style="width:40px;">順序</th>
                                        <th>カテゴリ名</th>
                                        <th>説明</th>
                                        <th style="width:80px;">投稿数</th>
                                        <th>作成者</th>
                                        <th style="width:160px;">操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categories as $cat): ?>
                                        <tr id="cat-row-<?php echo $cat['id']; ?>">
                                            <td>
                                                <span class="cat-display-<?php echo $cat['id']; ?>"><?php echo $cat['sort_order']; ?></span>
                                            </td>
                                            <td>
                                                <span class="cat-display-<?php echo $cat['id']; ?> fw-bold"><?php echo htmlspecialchars($cat['name']); ?></span>
                                            </td>
                                            <td>
                                                <span class="cat-display-<?php echo $cat['id']; ?> text-muted small"><?php echo htmlspecialchars($cat['description'] ?? ''); ?></span>
                                            </td>
                                            <td><span class="badge bg-secondary"><?php echo $cat['post_count']; ?></span></td>
                                            <td class="small"><?php echo htmlspecialchars($cat['creator_name'] ?? '-'); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary me-1" onclick="editCategory(<?php echo $cat['id']; ?>, '<?php echo htmlspecialchars(addslashes($cat['name'])); ?>', '<?php echo htmlspecialchars(addslashes($cat['description'] ?? '')); ?>', <?php echo $cat['sort_order']; ?>)" title="編集">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if ($cat['post_count'] == 0): ?>
                                                    <form method="post" action="<?php echo BASE_PATH; ?>/bulletin/categories/<?php echo $cat['id']; ?>/delete"
                                                          class="d-inline no-ajax" onsubmit="return confirm('このカテゴリを削除しますか？');">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="削除">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <form method="post" action="<?php echo BASE_PATH; ?>/bulletin/categories/<?php echo $cat['id']; ?>/delete"
                                                          class="d-inline no-ajax" onsubmit="return confirm('このカテゴリを削除しますか？投稿はカテゴリなしになります。');">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="削除">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- カテゴリ追加フォーム -->
            <div class="card" id="category-form-card">
                <div class="card-header py-2">
                    <strong id="form-title"><i class="fas fa-plus me-1"></i>カテゴリを追加</strong>
                </div>
                <div class="card-body">
                    <form method="post" id="category-form" action="<?php echo BASE_PATH; ?>/bulletin/categories" class="no-ajax">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label">カテゴリ名 <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="cat-name" class="form-control" required maxlength="100" placeholder="カテゴリ名">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">説明</label>
                                <input type="text" name="description" id="cat-description" class="form-control" maxlength="500" placeholder="説明（任意）">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">表示順</label>
                                <input type="number" name="sort_order" id="cat-sort" class="form-control" value="0" min="0">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100" id="form-submit-btn">
                                    <i class="fas fa-plus me-1"></i>追加
                                </button>
                            </div>
                        </div>
                    </form>
                    <div id="edit-cancel" class="mt-2 d-none">
                        <button class="btn btn-sm btn-outline-secondary" onclick="cancelEdit()">キャンセル</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function editCategory(id, name, description, sortOrder) {
    document.getElementById('category-form').action = BASE_PATH + '/bulletin/categories/' + id + '/update';
    document.getElementById('cat-name').value = name;
    document.getElementById('cat-description').value = description;
    document.getElementById('cat-sort').value = sortOrder;
    document.getElementById('form-title').innerHTML = '<i class="fas fa-edit me-1"></i>カテゴリを編集';
    document.getElementById('form-submit-btn').innerHTML = '<i class="fas fa-save me-1"></i>更新';
    document.getElementById('edit-cancel').classList.remove('d-none');
    document.getElementById('category-form-card').scrollIntoView({behavior: 'smooth'});
}

function cancelEdit() {
    document.getElementById('category-form').action = BASE_PATH + '/bulletin/categories';
    document.getElementById('cat-name').value = '';
    document.getElementById('cat-description').value = '';
    document.getElementById('cat-sort').value = '0';
    document.getElementById('form-title').innerHTML = '<i class="fas fa-plus me-1"></i>カテゴリを追加';
    document.getElementById('form-submit-btn').innerHTML = '<i class="fas fa-plus me-1"></i>追加';
    document.getElementById('edit-cancel').classList.add('d-none');
}
</script>

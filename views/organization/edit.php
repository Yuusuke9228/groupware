<?php
// views/organization/edit.php
$pageTitle = '組織編集 - GroupWare';
?>
<div class="container-fluid" data-page-type="edit">
    <div class="row mb-4">
        <div class="col">
            <h1 class="h3">組織編集</h1>
        </div>
        <div class="col-auto">
            <a href="<?php echo BASE_PATH; ?>/organizations/view/<?php echo $organization['id']; ?>" class="btn btn-outline-secondary me-2">
                <i class="fas fa-eye"></i> 詳細を表示
            </a>
            <a href="<?php echo BASE_PATH; ?>/organizations" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> 一覧に戻る
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="<?php echo BASE_PATH; ?>/api/organizations/<?php echo $organization['id']; ?>" method="post">
                <input type="hidden" name="id" id="id" value="<?php echo $organization['id']; ?>">

                <div class="mb-3">
                    <label for="name" class="form-label">組織名 <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($organization['name']); ?>" required>
                    <div class="invalid-feedback"></div>
                </div>

                <div class="mb-3">
                    <label for="code" class="form-label">組織コード <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="code" name="code" value="<?php echo htmlspecialchars($organization['code']); ?>" required>
                    <div class="invalid-feedback"></div>
                    <small class="form-text text-muted">英数字とアンダースコアのみ使用可能です</small>
                </div>

                <div class="mb-3">
                    <label for="parent_id" class="form-label">親組織</label>
                    <select class="form-select" id="parent_id" name="parent_id">
                        <option value="">なし（トップレベル）</option>
                        <?php foreach ($organizations as $org): ?>
                            <?php if (!in_array($org['id'], $descendantIds)): ?>
                                <option value="<?php echo $org['id']; ?>" <?php echo ($organization['parent_id'] == $org['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($org['name']); ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">説明</label>
                    <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($organization['description'] ?? ''); ?></textarea>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="<?php echo BASE_PATH; ?>/organizations" class="btn btn-outline-secondary me-md-2">キャンセル</a>
                    <button type="submit" class="btn btn-primary">保存</button>
                </div>
            </form>
        </div>
    </div>
</div>
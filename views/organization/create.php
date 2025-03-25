<?php
// views/organization/create.php
$pageTitle = '新規組織作成 - GroupSession PHP';
?>
<div class="container-fluid" data-page-type="create">
    <div class="row mb-4">
        <div class="col">
            <h1 class="h3">新規組織作成</h1>
        </div>
        <div class="col-auto">
            <a href="<?php echo BASE_PATH; ?>/organizations" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> 一覧に戻る
            </a>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <form action="<?php echo BASE_PATH; ?>/api/organizations" method="post">
                <div class="mb-3">
                    <label for="name" class="form-label">組織名 <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="name" name="name" required autofocus>
                    <div class="invalid-feedback"></div>
                </div>
                
                <div class="mb-3">
                    <label for="code" class="form-label">組織コード <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="code" name="code" required>
                    <div class="invalid-feedback"></div>
                    <small class="form-text text-muted">英数字とアンダースコアのみ使用可能です</small>
                </div>
                
                <div class="mb-3">
                    <label for="parent_id" class="form-label">親組織</label>
                    <select class="form-select" id="parent_id" name="parent_id">
                        <option value="">なし（トップレベル）</option>
                        <?php foreach ($organizations as $org): ?>
                            <option value="<?php echo $org['id']; ?>">
                                <?php echo htmlspecialchars($org['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">説明</label>
                    <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                </div>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="<?php echo BASE_PATH; ?>/organizations" class="btn btn-outline-secondary me-md-2">キャンセル</a>
                    <button type="submit" class="btn btn-primary">作成</button>
                </div>
            </form>
        </div>
    </div>
</div>
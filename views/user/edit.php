<?php
// views/user/edit.php
$pageTitle = 'ユーザー編集 - GroupWare';
$isEdit = true;
?>
<div class="container-fluid" data-page-type="edit">
    <div class="row mb-4">
        <div class="col">
            <h1 class="h3">ユーザー編集</h1>
        </div>
        <div class="col-auto">
            <a href="<?php echo BASE_PATH; ?>/users" class="btn btn-outline-secondary me-2">
                <i class="fas fa-arrow-left"></i> 一覧に戻る
            </a>
            <a href="<?php echo BASE_PATH; ?>/users/view/<?php echo $user['id']; ?>" class="btn btn-outline-info">
                <i class="fas fa-eye"></i> 詳細を表示
            </a>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <form action="<?php echo BASE_PATH; ?>/api/users/<?php echo $user['id']; ?>" method="post">
                <input type="hidden" name="id" id="id" value="<?php echo $user['id']; ?>">
                <?php include __DIR__ . '/form_fields.php'; ?>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="<?php echo BASE_PATH; ?>/users" class="btn btn-outline-secondary me-md-2">キャンセル</a>
                    <button type="submit" class="btn btn-primary">保存</button>
                </div>
            </form>
        </div>
    </div>
</div>


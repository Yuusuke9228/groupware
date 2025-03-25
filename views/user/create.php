<?php
// views/user/create.php
$pageTitle = '新規ユーザー作成 - GroupWare';
$isEdit = false;
?>
<div class="container-fluid" data-page-type="create">
    <div class="row mb-4">
        <div class="col">
            <h1 class="h3">新規ユーザー作成</h1>
        </div>
        <div class="col-auto">
            <a href="<?php echo BASE_PATH; ?>/users" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> 一覧に戻る
            </a>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <form action="<?php echo BASE_PATH; ?>/api/users" method="post">
                <?php include __DIR__ . '/form_fields.php'; ?>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="<?php echo BASE_PATH; ?>/users" class="btn btn-outline-secondary me-md-2">キャンセル</a>
                    <button type="submit" class="btn btn-primary">作成</button>
                </div>
            </form>
        </div>
    </div>
</div>


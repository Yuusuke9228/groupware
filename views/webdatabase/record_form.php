<!-- views/webdatabase/record_form.php -->
<div class="container-fluid mt-4">
    <div class="row mb-3">
        <div class="col">
            <h1><?= isset($record) ? "レコード編集" : "新規レコード作成" ?></h1>
            <h5 class="text-muted"><?= htmlspecialchars($database['name']) ?></h5>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form id="record-form" action="<?= BASE_PATH ?>/api/webdatabase/<?= $database['id'] ?><?= isset($record) ? '/' . $record['id'] : '' ?>" method="POST" enctype="multipart/form-data">
                <!-- フィールドを順番に表示 -->
                <?php foreach ($fields as $field): ?>
                <div class="mbcol-md-6">
                    <input type="text" id="search-databases" class="form-control" placeholder="データベースを検索...">
                </div>
            </div>

            <div class="row" id="database-list">
                <!-- データベース一覧がここに表示される -->
                <div class="col-12 text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- データベースカードのテンプレート -->
<template id="database-card-template">
    <div class="col-md-4 col-lg-3 mb-4">
        <div class="card h-100">
            <div class="card-header" style="background-color: {{color}};">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0 text-white">
                        <i class="fas fa-{{icon}}"></i> {{name}}
                    </h5>
                    <div class="dropdown">
                        <button class="btn btn-sm text-white" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?= BASE_PATH ?>/webdatabase/edit/{{id}}"><i class="fas fa-edit"></i> 編集</a></li>
                            <li><a class="dropdown-item" href="<?= BASE_PATH ?>/webdatabase/fields/{{id}}"><i class="fas fa-list"></i> フィールド設定</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger btn-delete" href="#" data-url="<?= BASE_PATH ?>/api/webdatabase/{{id}}" data-confirm="このデータベースを削除しますか？全てのレコードも削除されます。"><i class="fas fa-trash"></i> 削除</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="card-body d-flex flex-column">
                <p class="card-text flex-grow-1">{{description}}</p>
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <small class="text-muted">作成者: {{creator_name}}</small>
                    <a href="<?= BASE_PATH ?>/webdatabase/records/{{id}}" class="btn btn-sm btn-outline-primary">レコード一覧</a>
                </div>
            </div>
        </div>
    </div>
</template>
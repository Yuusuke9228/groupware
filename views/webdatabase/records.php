<!-- views/webdatabase/records.php -->
<div class="container-fluid mt-4">
    <div class="row mb-3">
        <div class="col-md-8">
            <h1><?= htmlspecialchars($database['name']) ?> - レコード一覧</h1>
        </div>
        <div class="col-md-4 text-end">
            <a href="<?= BASE_PATH ?>/webdatabase/create-record/<?= $database['id'] ?>" class="btn btn-primary">
                <i class="fas fa-plus"></i> 新規レコード作成
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="row">
                <div class="col-md-6">
                    <input type="text" id="search-records" class="form-control" placeholder="レコードを検索...">
                </div>
                <div class="col-md-6 text-end">
                    <!-- フィルターボタン -->
                    <button class="btn btn-outline-secondary" type="button" id="filter-button" data-bs-toggle="collapse" data-bs-target="#filter-panel">
                        <i class="fas fa-filter"></i> フィルター
                    </button>
                </div>
            </div>
        </div>

        <!-- フィルターパネル -->
        <div class="collapse" id="filter-panel">
            <div class="card-body bg-light">
                <form id="filter-form">
                    <div class="row" id="filter-fields">
                        <!-- フィルターフィールドがここに動的に追加される -->
                        <?php foreach ($fields as $field): ?>
                            <?php if ($field['is_filterable']): ?>
                                <div class="col-md-4 mb-3">
                                    <label for="filter-<?= $field['id'] ?>" class="form-label"><?= htmlspecialchars($field['name']) ?></label>

                                    <?php if (in_array($field['type'], ['select', 'radio'])): ?>
                                        <select class="form-select filter-field" id="filter-<?= $field['id'] ?>" name="filters[<?= $field['id'] ?>]">
                                            <option value="">すべて</option>
                                            <?php
                                            $options = json_decode($field['options'], true);
                                            if ($options) {
                                                foreach ($options as $option) {
                                                    echo '<option value="' . htmlspecialchars($option['value']) . '">' . htmlspecialchars($option['label']) . '</option>';
                                                }
                                            }
                                            ?>
                                        </select>
                                    <?php elseif ($field['type'] == 'checkbox'): ?>
                                        <select class="form-select filter-field" id="filter-<?= $field['id'] ?>" name="filters[<?= $field['id'] ?>]">
                                            <option value="">すべて</option>
                                            <?php
                                            $options = json_decode($field['options'], true);
                                            if ($options) {
                                                foreach ($options as $option) {
                                                    echo '<option value="' . htmlspecialchars($option['value']) . '">' . htmlspecialchars($option['label']) . '</option>';
                                                }
                                            }
                                            ?>
                                        </select>
                                    <?php elseif ($field['type'] == 'user'): ?>
                                        <select class="form-select filter-field" id="filter-<?= $field['id'] ?>" name="filters[<?= $field['id'] ?>]">
                                            <option value="">すべて</option>
                                            <!-- ユーザーリストはJSで動的に読み込む -->
                                        </select>
                                    <?php elseif ($field['type'] == 'organization'): ?>
                                        <select class="form-select filter-field" id="filter-<?= $field['id'] ?>" name="filters[<?= $field['id'] ?>]">
                                            <option value="">すべて</option>
                                            <!-- 組織リストはJSで動的に読み込む -->
                                        </select>
                                    <?php else: ?>
                                        <input type="text" class="form-control filter-field" id="filter-<?= $field['id'] ?>" name="filters[<?= $field['id'] ?>]">
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="button" class="btn btn-outline-secondary me-2" id="reset-filter-btn">フィルターをリセット</button>
                        <button type="button" class="btn btn-primary" id="apply-filter-btn">適用</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <!-- タイトルフィールドを必ず含む -->
                            <th>タイトル</th>

                            <!-- 最大5つの主要フィールドを表示 -->
                            <?php
                            $displayFields = [];
                            foreach ($fields as $field) {
                                if (!$field['is_title_field'] && count($displayFields) < 5) {
                                    $displayFields[] = $field;
                                    echo '<th>' . htmlspecialchars($field['name']) . '</th>';
                                }
                            }
                            ?>

                            <th>作成者</th>
                            <th>作成日時</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody id="record-list">
                        <!-- レコード一覧がここに動的に追加される -->
                        <tr>
                            <td colspan="<?= count($displayFields) + 4 ?>" class="text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- ページネーション -->
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div>
                    <span id="total-records">0</span> 件中 <span id="showing-records">0-0</span> 件を表示
                </div>
                <nav aria-label="Page navigation">
                    <ul class="pagination" id="pagination">
                        <!-- ページネーションリンクがここに動的に追加される -->
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between mt-3">
        <a href="<?= BASE_PATH ?>/webdatabase/fields/<?= $database['id'] ?>" class="btn btn-secondary">フィールド設定に戻る</a>
    </div>
</div>

<!-- レコード行のテンプレート -->
<template id="record-row-template">
    <tr>
        <td><a href="<?= BASE_PATH ?>/webdatabase/view/{{database_id}}/{{id}}">{{title}}</a></td>
        <!-- 最大5つの主要フィールドを表示 -->
        {{field_values}}
        <td>{{creator_name}}</td>
        <td>{{created_at}}</td>
        <td>
            <div class="btn-group" role="group">
                <a href="<?= BASE_PATH ?>/webdatabase/edit/{{database_id}}/{{id}}" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-edit"></i> 編集
                </a>
                <button class="btn btn-sm btn-outline-danger btn-delete" data-url="<?= BASE_PATH ?>/api/webdatabase/{{database_id}}/{{id}}" data-confirm="このレコードを削除しますか？">
                    <i class="fas fa-trash"></i> 削除
                </button>
            </div>
        </td>
    </tr>
</template>
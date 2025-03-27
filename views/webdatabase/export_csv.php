<!-- views/webdatabase/export_csv.php -->
<div class="container-fluid mt-4">
    <div class="row mb-3">
        <div class="col">
            <h1>CSVエクスポート</h1>
            <h5 class="text-muted"><?= htmlspecialchars($database['name']) ?></h5>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form id="export-form" action="<?= BASE_PATH ?>/api/webdatabase/<?= $database['id'] ?>/export-csv" method="GET">
                <div class="mb-4">
                    <h5>エクスポート設定</h5>

                    <div class="mb-3">
                        <label for="search" class="form-label">検索条件</label>
                        <input type="text" class="form-control" id="search" name="search" placeholder="キーワード検索...">
                        <div class="form-text">検索条件に一致するレコードのみをエクスポートします。空白にすると全てのレコードが対象になります。</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">フィルター条件</label>
                        <div class="row">
                            <?php foreach ($fields as $field): ?>
                                <?php if ($field['is_filterable']): ?>
                                    <div class="col-md-4 mb-2">
                                        <div class="input-group">
                                            <span class="input-group-text"><?= htmlspecialchars($field['name']) ?></span>

                                            <?php if (in_array($field['type'], ['select', 'radio'])): ?>
                                                <select class="form-select" name="filters[<?= $field['id'] ?>]">
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
                                                <select class="form-select" name="filters[<?= $field['id'] ?>]">
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
                                                <select class="form-select user-select" name="filters[<?= $field['id'] ?>]">
                                                    <option value="">すべて</option>
                                                    <!-- ユーザーリストはJSで動的に読み込む -->
                                                </select>
                                            <?php elseif ($field['type'] == 'organization'): ?>
                                                <select class="form-select organization-select" name="filters[<?= $field['id'] ?>]">
                                                    <option value="">すべて</option>
                                                    <!-- 組織リストはJSで動的に読み込む -->
                                                </select>
                                            <?php else: ?>
                                                <input type="text" class="form-control" name="filters[<?= $field['id'] ?>]">
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">エクスポートするフィールド</label>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="select-all-fields" checked>
                                    <label class="form-check-label" for="select-all-fields">
                                        全てのフィールドを選択
                                    </label>
                                </div>
                            </div>
                            <?php foreach ($fields as $field): ?>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input field-checkbox" type="checkbox" id="field-<?= $field['id'] ?>" name="export_fields[]" value="<?= $field['id'] ?>" checked>
                                        <label class="form-check-label" for="field-<?= $field['id'] ?>">
                                            <?= htmlspecialchars($field['name']) ?>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="<?= BASE_PATH ?>/webdatabase/records/<?= $database['id'] ?>" class="btn btn-secondary">戻る</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-download"></i> CSVエクスポート
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 全選択・解除の処理
        const selectAllCheckbox = document.getElementById('select-all-fields');
        const fieldCheckboxes = document.querySelectorAll('.field-checkbox');

        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                const isChecked = this.checked;
                fieldCheckboxes.forEach(checkbox => {
                    checkbox.checked = isChecked;
                });
            });
        }

        // 個別チェックボックスの変更検知
        fieldCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                // 全てチェックされているか確認
                const allChecked = Array.from(fieldCheckboxes).every(cb => cb.checked);
                if (selectAllCheckbox) {
                    selectAllCheckbox.checked = allChecked;
                }
            });
        });
    });
</script>
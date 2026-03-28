<!-- views/webdatabase/record_form.php -->
<?php
$layoutItems = $formLayout['items'] ?? [];
$layoutByFieldId = [];
foreach ($layoutItems as $item) {
    $layoutByFieldId[(int)$item['field_id']] = $item;
}
$fieldById = [];
foreach ($fields as $field) {
    $fieldById[(int)$field['id']] = $field;
}
$orderedFields = [];
foreach ($layoutItems as $item) {
    $fid = (int)($item['field_id'] ?? 0);
    if ($fid > 0 && isset($fieldById[$fid])) {
        $orderedFields[] = $fieldById[$fid];
    }
}
foreach ($fields as $field) {
    $fid = (int)$field['id'];
    $exists = false;
    foreach ($orderedFields as $ordered) {
        if ((int)$ordered['id'] === $fid) {
            $exists = true;
            break;
        }
    }
    if (!$exists) {
        $orderedFields[] = $field;
    }
}
$sections = [];
foreach ($orderedFields as $field) {
    $fid = (int)$field['id'];
    $layout = $layoutByFieldId[$fid] ?? [];
    if (!empty($layout['hidden'])) {
        continue;
    }
    $sectionName = trim((string)($layout['section'] ?? '基本情報'));
    if ($sectionName === '') {
        $sectionName = '基本情報';
    }
    if (!isset($sections[$sectionName])) {
        $sections[$sectionName] = [];
    }
    $sections[$sectionName][] = ['field' => $field, 'layout' => $layout];
}
if (empty($sections)) {
    $sections['基本情報'] = [];
    foreach ($fields as $field) {
        $sections['基本情報'][] = ['field' => $field, 'layout' => $layoutByFieldId[(int)$field['id']] ?? []];
    }
}
?>
<div class="container-fluid mt-4">
    <div class="row mb-3">
        <div class="col">
            <h1><?= isset($record) ? "レコード編集" : "新規レコード作成" ?></h1>
            <h5 class="text-muted"><?= htmlspecialchars($database['name']) ?></h5>
        </div>
    </div>

    <form id="record-form" action="<?= BASE_PATH ?>/api/webdatabase/record/<?= $database['id'] ?><?= isset($record) ? '/' . $record['id'] : '' ?>" method="POST" enctype="multipart/form-data">
        <?php foreach ($sections as $sectionName => $sectionFields): ?>
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><?= htmlspecialchars((string)$sectionName) ?></h6>
                    <span class="badge bg-light text-dark"><?= count($sectionFields) ?>項目</span>
                </div>
                <div class="card-body">
                    <?php foreach ($sectionFields as $entry): ?>
                        <?php
                        $field = $entry['field'];
                        $layout = $entry['layout'];
                        $fieldId = (int)$field['id'];
                        $fieldType = (string)$field['type'];
                        $value = $recordData[$fieldId] ?? $field['default_value'] ?? '';
                        ?>
                        <div class="mb-3" data-field-id="<?= $fieldId ?>" data-field-type="<?= htmlspecialchars($fieldType) ?>">
                            <label for="field-<?= $fieldId ?>" class="form-label">
                                <?= htmlspecialchars((string)$field['name']) ?>
                                <?php if (!empty($field['required'])): ?><span class="text-danger">*</span><?php endif; ?>
                            </label>

                            <?php if ($fieldType === 'text'): ?>
                                <input type="text" class="form-control" id="field-<?= $fieldId ?>" name="fields[<?= $fieldId ?>]" value="<?= htmlspecialchars((string)$value) ?>" <?= !empty($field['required']) ? 'required' : '' ?>>

                            <?php elseif ($fieldType === 'textarea'): ?>
                                <textarea class="form-control" id="field-<?= $fieldId ?>" name="fields[<?= $fieldId ?>]" rows="4" <?= !empty($field['required']) ? 'required' : '' ?>><?= htmlspecialchars((string)$value) ?></textarea>

                            <?php elseif ($fieldType === 'number'): ?>
                                <input type="number" class="form-control" id="field-<?= $fieldId ?>" name="fields[<?= $fieldId ?>]" value="<?= htmlspecialchars((string)$value) ?>" <?= !empty($field['required']) ? 'required' : '' ?>>

                            <?php elseif ($fieldType === 'date'): ?>
                                <input type="date" class="form-control" id="field-<?= $fieldId ?>" name="fields[<?= $fieldId ?>]" value="<?= htmlspecialchars((string)$value) ?>" <?= !empty($field['required']) ? 'required' : '' ?>>

                            <?php elseif ($fieldType === 'datetime'): ?>
                                <input type="datetime-local" class="form-control" id="field-<?= $fieldId ?>" name="fields[<?= $fieldId ?>]" value="<?= htmlspecialchars((string)$value) ?>" <?= !empty($field['required']) ? 'required' : '' ?>>

                            <?php elseif ($fieldType === 'select'): ?>
                                <select class="form-select" id="field-<?= $fieldId ?>" name="fields[<?= $fieldId ?>]" <?= !empty($field['required']) ? 'required' : '' ?>>
                                    <option value="">選択してください</option>
                                    <?php $options = json_decode((string)$field['options'], true); if (is_array($options)) { foreach ($options as $option) {
                                        $selected = ((string)$value === (string)$option['value']) ? 'selected' : '';
                                        echo '<option value="' . htmlspecialchars((string)$option['value']) . '" ' . $selected . '>' . htmlspecialchars((string)$option['label']) . '</option>';
                                    }} ?>
                                </select>

                            <?php elseif ($fieldType === 'radio'): ?>
                                <?php $options = json_decode((string)$field['options'], true); if (is_array($options)) { foreach ($options as $option) {
                                    $checked = ((string)$value === (string)$option['value']) ? 'checked' : '';
                                    echo '<div class="form-check">';
                                    echo '<input class="form-check-input" type="radio" name="fields[' . $fieldId . ']" id="field-' . $fieldId . '-' . htmlspecialchars((string)$option['value']) . '" value="' . htmlspecialchars((string)$option['value']) . '" ' . $checked . ' ' . (!empty($field['required']) ? 'required' : '') . '>';
                                    echo '<label class="form-check-label" for="field-' . $fieldId . '-' . htmlspecialchars((string)$option['value']) . '">' . htmlspecialchars((string)$option['label']) . '</label>';
                                    echo '</div>';
                                }} ?>

                            <?php elseif ($fieldType === 'checkbox'): ?>
                                <?php
                                $currentValues = is_array($value) ? $value : explode(',', (string)$value);
                                $options = json_decode((string)$field['options'], true);
                                if (is_array($options)) {
                                    foreach ($options as $option) {
                                        $checked = in_array((string)$option['value'], array_map('strval', $currentValues), true) ? 'checked' : '';
                                        echo '<div class="form-check">';
                                        echo '<input class="form-check-input" type="checkbox" name="fields[' . $fieldId . '][]" id="field-' . $fieldId . '-' . htmlspecialchars((string)$option['value']) . '" value="' . htmlspecialchars((string)$option['value']) . '" ' . $checked . '>';
                                        echo '<label class="form-check-label" for="field-' . $fieldId . '-' . htmlspecialchars((string)$option['value']) . '">' . htmlspecialchars((string)$option['label']) . '</label>';
                                        echo '</div>';
                                    }
                                }
                                ?>

                            <?php elseif ($fieldType === 'file'): ?>
                                <input type="file" class="form-control" id="field-<?= $fieldId ?>" name="<?= $fieldId ?>" <?= !empty($field['required']) ? 'required' : '' ?>>
                                <?php if (!empty($recordData[$fieldId])): ?>
                                    <div class="mt-2 small text-muted">現在のファイル:
                                        <?php $files = is_array($recordData[$fieldId]) && isset($recordData[$fieldId][0]) ? $recordData[$fieldId] : [$recordData[$fieldId]]; ?>
                                        <?php foreach ($files as $file): ?>
                                            <?php if (is_array($file) && !empty($file['path'])): ?>
                                                <a href="<?= BASE_PATH ?>/<?= htmlspecialchars((string)$file['path']) ?>" target="_blank"><?= htmlspecialchars((string)$file['name']) ?></a>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                            <?php elseif ($fieldType === 'user'): ?>
                                <select class="form-select user-select" id="field-<?= $fieldId ?>" name="fields[<?= $fieldId ?>]" <?= !empty($field['required']) ? 'required' : '' ?> data-selected="<?= htmlspecialchars((string)$value) ?>">
                                    <option value="">ユーザーを選択</option>
                                </select>

                            <?php elseif ($fieldType === 'organization'): ?>
                                <select class="form-select organization-select" id="field-<?= $fieldId ?>" name="fields[<?= $fieldId ?>]" <?= !empty($field['required']) ? 'required' : '' ?> data-selected="<?= htmlspecialchars((string)$value) ?>">
                                    <option value="">組織を選択</option>
                                </select>

                            <?php elseif ($fieldType === 'relation' && !empty($layout['child_table'])): ?>
                                <?php
                                $initialRows = $relationData[$fieldId] ?? [];
                                $initialJson = json_encode($initialRows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                                ?>
                                <div class="child-table-container" data-field-id="<?= $fieldId ?>" data-relation-db="<?= (int)($field['relation_database_id'] ?? 0) ?>" data-summary-field-id="<?= (int)($layout['child_summary_field_id'] ?? 0) ?>" data-initial='<?= htmlspecialchars((string)$initialJson, ENT_QUOTES, 'UTF-8') ?>'>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <strong>明細入力</strong>
                                        <button type="button" class="btn btn-sm btn-outline-primary add-child-row-btn"><i class="fas fa-plus"></i> 行追加</button>
                                    </div>
                                    <input type="hidden" name="child_tables[<?= $fieldId ?>]" class="child-table-json">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered child-table-grid">
                                            <thead></thead>
                                            <tbody></tbody>
                                            <tfoot>
                                                <tr>
                                                    <th colspan="99" class="text-end"><span class="child-summary-label text-muted">合計: </span><span class="child-summary-value">0</span></th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>

                            <?php elseif ($fieldType === 'relation'): ?>
                                <?php
                                $selectedIds = [];
                                if (!empty($relationData[$fieldId])) {
                                    foreach ($relationData[$fieldId] as $relRow) {
                                        $selectedIds[] = (int)($relRow['target_record_id'] ?? 0);
                                    }
                                } elseif (!empty($value)) {
                                    if (is_array($value)) {
                                        foreach ($value as $vid) { $selectedIds[] = (int)$vid; }
                                    } else {
                                        foreach (explode(',', (string)$value) as $vid) { $selectedIds[] = (int)$vid; }
                                    }
                                }
                                $selectedIds = array_values(array_filter(array_unique($selectedIds)));
                                ?>
                                <div class="relation-field-container" data-field-id="<?= $fieldId ?>" data-relation-db="<?= (int)($field['relation_database_id'] ?? 0) ?>" data-relation-type="<?= htmlspecialchars((string)($field['relation_type'] ?? 'one_to_many')) ?>" data-filter-field-id="<?= (int)($layout['relation_filter_field_id'] ?? 0) ?>">
                                    <select class="form-select relation-select" id="field-<?= $fieldId ?>" name="fields[<?= $fieldId ?>][]" <?= (($field['relation_type'] ?? '') === 'one_to_one') ? '' : 'multiple' ?> data-selected='<?= htmlspecialchars((string)json_encode($selectedIds), ENT_QUOTES, 'UTF-8') ?>'>
                                        <option value="">レコードを選択</option>
                                    </select>
                                </div>

                            <?php elseif ($fieldType === 'lookup'): ?>
                                <input type="text" class="form-control" id="field-<?= $fieldId ?>" readonly value="<?= htmlspecialchars((string)$value) ?>" placeholder="リレーションから自動取得">

                            <?php elseif ($fieldType === 'calc'): ?>
                                <input type="text" class="form-control calc-field" id="field-<?= $fieldId ?>" readonly value="<?= htmlspecialchars((string)$value) ?>" data-formula="<?= htmlspecialchars((string)($field['calc_formula'] ?? '')) ?>">

                            <?php elseif ($fieldType === 'url'): ?>
                                <input type="url" class="form-control" id="field-<?= $fieldId ?>" name="fields[<?= $fieldId ?>]" value="<?= htmlspecialchars((string)$value) ?>" placeholder="https://" <?= !empty($field['required']) ? 'required' : '' ?>>

                            <?php elseif ($fieldType === 'email'): ?>
                                <input type="email" class="form-control" id="field-<?= $fieldId ?>" name="fields[<?= $fieldId ?>]" value="<?= htmlspecialchars((string)$value) ?>" placeholder="example@mail.com" <?= !empty($field['required']) ? 'required' : '' ?>>

                            <?php elseif ($fieldType === 'phone'): ?>
                                <input type="tel" class="form-control" id="field-<?= $fieldId ?>" name="fields[<?= $fieldId ?>]" value="<?= htmlspecialchars((string)$value) ?>" placeholder="090-1234-5678" <?= !empty($field['required']) ? 'required' : '' ?>>

                            <?php elseif ($fieldType === 'currency'): ?>
                                <div class="input-group">
                                    <span class="input-group-text">&yen;</span>
                                    <input type="number" class="form-control" id="field-<?= $fieldId ?>" name="fields[<?= $fieldId ?>]" value="<?= htmlspecialchars((string)$value) ?>" <?= !empty($field['required']) ? 'required' : '' ?>>
                                </div>

                            <?php elseif ($fieldType === 'percent'): ?>
                                <div class="input-group">
                                    <input type="number" min="0" max="100" step="0.1" class="form-control" id="field-<?= $fieldId ?>" name="fields[<?= $fieldId ?>]" value="<?= htmlspecialchars((string)$value) ?>" <?= !empty($field['required']) ? 'required' : '' ?>>
                                    <span class="input-group-text">%</span>
                                </div>

                            <?php elseif ($fieldType === 'auto_number'): ?>
                                <input type="text" class="form-control" id="field-<?= $fieldId ?>" name="fields[<?= $fieldId ?>]" value="<?= htmlspecialchars((string)$value) ?>" readonly>
                            <?php endif; ?>

                            <?php if (!empty($field['description'])): ?>
                                <div class="form-text"><?= htmlspecialchars((string)$field['description']) ?></div>
                            <?php endif; ?>
                            <div class="invalid-feedback"></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="d-flex justify-content-between mt-4">
            <a href="<?= BASE_PATH ?>/webdatabase/records/<?= $database['id'] ?>" class="btn btn-secondary">キャンセル</a>
            <button type="submit" class="btn btn-primary"><?= isset($record) ? "更新" : "作成" ?></button>
        </div>
    </form>
</div>

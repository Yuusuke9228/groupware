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
            <form id="record-form" action="<?= BASE_PATH ?>/api/webdatabase/record/<?= $database['id'] ?><?= isset($record) ? '/' . $record['id'] : '' ?>" method="POST" enctype="multipart/form-data">
                <!-- フィールドを順番に表示 -->
                <?php foreach ($fields as $field): ?>
                    <div class="mb-3">
                        <label for="field-<?= $field['id'] ?>" class="form-label">
                            <?= htmlspecialchars($field['name']) ?>
                            <?php if ($field['required']): ?>
                                <span class="text-danger">*</span>
                            <?php endif; ?>
                        </label>

                        <?php if ($field['type'] === 'text'): ?>
                            <input type="text" class="form-control" id="field-<?= $field['id'] ?>" name="fields[<?= $field['id'] ?>]"
                                value="<?= isset($recordData[$field['id']]) ? htmlspecialchars($recordData[$field['id']]) : $field['default_value'] ?>"
                                <?= $field['required'] ? 'required' : '' ?>>

                        <?php elseif ($field['type'] === 'textarea'): ?>
                            <textarea class="form-control" id="field-<?= $field['id'] ?>" name="fields[<?= $field['id'] ?>]" rows="4"
                                <?= $field['required'] ? 'required' : '' ?>><?= isset($recordData[$field['id']]) ? htmlspecialchars($recordData[$field['id']]) : $field['default_value'] ?></textarea>

                        <?php elseif ($field['type'] === 'number'): ?>
                            <input type="number" class="form-control" id="field-<?= $field['id'] ?>" name="fields[<?= $field['id'] ?>]"
                                value="<?= isset($recordData[$field['id']]) ? htmlspecialchars($recordData[$field['id']]) : $field['default_value'] ?>"
                                <?= $field['required'] ? 'required' : '' ?>>

                        <?php elseif ($field['type'] === 'date'): ?>
                            <input type="date" class="form-control" id="field-<?= $field['id'] ?>" name="fields[<?= $field['id'] ?>]"
                                value="<?= isset($recordData[$field['id']]) ? htmlspecialchars($recordData[$field['id']]) : $field['default_value'] ?>"
                                <?= $field['required'] ? 'required' : '' ?>>

                        <?php elseif ($field['type'] === 'datetime'): ?>
                            <input type="datetime-local" class="form-control" id="field-<?= $field['id'] ?>" name="fields[<?= $field['id'] ?>]"
                                value="<?= isset($recordData[$field['id']]) ? htmlspecialchars($recordData[$field['id']]) : $field['default_value'] ?>"
                                <?= $field['required'] ? 'required' : '' ?>>

                        <?php elseif ($field['type'] === 'select'): ?>
                            <select class="form-select" id="field-<?= $field['id'] ?>" name="fields[<?= $field['id'] ?>]" <?= $field['required'] ? 'required' : '' ?>>
                                <option value="">選択してください</option>
                                <?php
                                $options = json_decode($field['options'], true);
                                $currentValue = isset($recordData[$field['id']]) ? $recordData[$field['id']] : $field['default_value'];
                                if ($options) {
                                    foreach ($options as $option) {
                                        $selected = ($currentValue == $option['value']) ? 'selected' : '';
                                        echo '<option value="' . htmlspecialchars($option['value']) . '" ' . $selected . '>' . htmlspecialchars($option['label']) . '</option>';
                                    }
                                }
                                ?>
                            </select>

                        <?php elseif ($field['type'] === 'radio'): ?>
                            <?php
                            $options = json_decode($field['options'], true);
                            $currentValue = isset($recordData[$field['id']]) ? $recordData[$field['id']] : $field['default_value'];
                            if ($options) {
                                foreach ($options as $option) {
                                    $checked = ($currentValue == $option['value']) ? 'checked' : '';
                                    echo '<div class="form-check">';
                                    echo '<input class="form-check-input" type="radio" name="fields[' . $field['id'] . ']" id="field-' . $field['id'] . '-' . $option['value'] . '" value="' . htmlspecialchars($option['value']) . '" ' . $checked . ' ' . ($field['required'] ? 'required' : '') . '>';
                                    echo '<label class="form-check-label" for="field-' . $field['id'] . '-' . $option['value'] . '">' . htmlspecialchars($option['label']) . '</label>';
                                    echo '</div>';
                                }
                            }
                            ?>

                        <?php elseif ($field['type'] === 'checkbox'): ?>
                            <?php
                            $options = json_decode($field['options'], true);
                            $currentValues = isset($recordData[$field['id']]) ? (is_array($recordData[$field['id']]) ? $recordData[$field['id']] : [$recordData[$field['id']]]) : ($field['default_value'] ? [$field['default_value']] : []);
                            if ($options) {
                                foreach ($options as $option) {
                                    $checked = in_array($option['value'], $currentValues) ? 'checked' : '';
                                    echo '<div class="form-check">';
                                    echo '<input class="form-check-input" type="checkbox" name="fields[' . $field['id'] . '][]" id="field-' . $field['id'] . '-' . $option['value'] . '" value="' . htmlspecialchars($option['value']) . '" ' . $checked . '>';
                                    echo '<label class="form-check-label" for="field-' . $field['id'] . '-' . $option['value'] . '">' . htmlspecialchars($option['label']) . '</label>';
                                    echo '</div>';
                                }
                            }
                            ?>

                        <?php elseif ($field['type'] === 'file'): ?>
                            <input type="file" class="form-control" id="field-<?= $field['id'] ?>" name="<?= $field['id'] ?>" <?= $field['required'] ? 'required' : '' ?>>
                            <?php if (isset($recordData[$field['id']]) && !empty($recordData[$field['id']])): ?>
                                <div class="mt-2">
                                    <span class="text-muted">現在のファイル: </span>
                                    <?php if (is_array($recordData[$field['id']])): ?>
                                        <?php foreach ($recordData[$field['id']] as $file): ?>
                                            <a href="<?= BASE_PATH ?>/<?= $file['path'] ?>" target="_blank"><?= htmlspecialchars($file['name']) ?></a>
                                            <span class="text-muted">(<?= number_format($file['size'] / 1024, 1) ?> KB)</span><br>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <a href="<?= BASE_PATH ?>/<?= $recordData[$field['id']]['path'] ?>" target="_blank"><?= htmlspecialchars($recordData[$field['id']]['name']) ?></a>
                                        <span class="text-muted">(<?= number_format($recordData[$field['id']]['size'] / 1024, 1) ?> KB)</span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                        <?php elseif ($field['type'] === 'user'): ?>
                            <select class="form-select user-select" id="field-<?= $field['id'] ?>" name="fields[<?= $field['id'] ?>]" <?= $field['required'] ? 'required' : '' ?> data-selected="<?= isset($recordData[$field['id']]) ? $recordData[$field['id']] : '' ?>">
                                <option value="">ユーザーを選択</option>
                                <!-- ユーザーリストはJSで動的に読み込まれる -->
                            </select>

                        <?php elseif ($field['type'] === 'organization'): ?>
                            <select class="form-select organization-select" id="field-<?= $field['id'] ?>" name="fields[<?= $field['id'] ?>]" <?= $field['required'] ? 'required' : '' ?> data-selected="<?= isset($recordData[$field['id']]) ? $recordData[$field['id']] : '' ?>">
                                <option value="">組織を選択</option>
                            </select>

                        <?php elseif ($field['type'] === 'relation'): ?>
                            <div class="relation-field-container" data-field-id="<?= $field['id'] ?>" data-relation-db="<?= $field['relation_database_id'] ?>" data-relation-type="<?= $field['relation_type'] ?>">
                                <select class="form-select relation-select" id="field-<?= $field['id'] ?>" name="fields[<?= $field['id'] ?>][]" <?= $field['relation_type'] === 'one_to_one' ? '' : 'multiple' ?>>
                                    <option value="">レコードを選択</option>
                                </select>
                                <small class="form-text text-muted">リレーション先: <?= htmlspecialchars($field['relation_database_id'] ?? '') ?></small>
                            </div>

                        <?php elseif ($field['type'] === 'lookup'): ?>
                            <div class="lookup-field-container" data-field-id="<?= $field['id'] ?>" data-lookup-relation="<?= $field['lookup_relation_field_id'] ?>" data-lookup-target="<?= $field['lookup_target_field_id'] ?>">
                                <input type="text" class="form-control" id="field-<?= $field['id'] ?>" readonly placeholder="リレーション先の値を自動表示"
                                    value="<?= isset($recordData[$field['id']]) ? htmlspecialchars($recordData[$field['id']]) : '' ?>">
                                <small class="form-text text-muted">ルックアップフィールド（自動取得）</small>
                            </div>

                        <?php elseif ($field['type'] === 'calc'): ?>
                            <input type="text" class="form-control calc-field" id="field-<?= $field['id'] ?>" readonly placeholder="計算結果"
                                data-formula="<?= htmlspecialchars($field['calc_formula'] ?? '') ?>"
                                value="<?= isset($recordData[$field['id']]) ? htmlspecialchars($recordData[$field['id']]) : '' ?>">

                        <?php elseif ($field['type'] === 'url'): ?>
                            <input type="url" class="form-control" id="field-<?= $field['id'] ?>" name="fields[<?= $field['id'] ?>]"
                                value="<?= isset($recordData[$field['id']]) ? htmlspecialchars($recordData[$field['id']]) : $field['default_value'] ?>"
                                placeholder="https://" <?= $field['required'] ? 'required' : '' ?>>

                        <?php elseif ($field['type'] === 'email'): ?>
                            <input type="email" class="form-control" id="field-<?= $field['id'] ?>" name="fields[<?= $field['id'] ?>]"
                                value="<?= isset($recordData[$field['id']]) ? htmlspecialchars($recordData[$field['id']]) : $field['default_value'] ?>"
                                placeholder="example@mail.com" <?= $field['required'] ? 'required' : '' ?>>

                        <?php elseif ($field['type'] === 'phone'): ?>
                            <input type="tel" class="form-control" id="field-<?= $field['id'] ?>" name="fields[<?= $field['id'] ?>]"
                                value="<?= isset($recordData[$field['id']]) ? htmlspecialchars($recordData[$field['id']]) : $field['default_value'] ?>"
                                placeholder="090-1234-5678" <?= $field['required'] ? 'required' : '' ?>>

                        <?php elseif ($field['type'] === 'currency'): ?>
                            <div class="input-group">
                                <span class="input-group-text">&yen;</span>
                                <input type="number" class="form-control" id="field-<?= $field['id'] ?>" name="fields[<?= $field['id'] ?>]"
                                    value="<?= isset($recordData[$field['id']]) ? htmlspecialchars($recordData[$field['id']]) : $field['default_value'] ?>"
                                    step="1" <?= $field['required'] ? 'required' : '' ?>>
                            </div>

                        <?php elseif ($field['type'] === 'percent'): ?>
                            <div class="input-group">
                                <input type="number" class="form-control" id="field-<?= $field['id'] ?>" name="fields[<?= $field['id'] ?>]"
                                    value="<?= isset($recordData[$field['id']]) ? htmlspecialchars($recordData[$field['id']]) : $field['default_value'] ?>"
                                    step="0.1" min="0" max="100" <?= $field['required'] ? 'required' : '' ?>>
                                <span class="input-group-text">%</span>
                            </div>

                        <?php elseif ($field['type'] === 'auto_number'): ?>
                            <input type="text" class="form-control" id="field-<?= $field['id'] ?>" readonly placeholder="自動採番"
                                value="<?= isset($recordData[$field['id']]) ? htmlspecialchars($recordData[$field['id']]) : '(自動)' ?>">

                        <?php endif; ?>

                        <?php if (!empty($field['description'])): ?>
                            <div class="form-text"><?= htmlspecialchars($field['description']) ?></div>
                        <?php endif; ?>
                        <div class="invalid-feedback"></div>
                    </div>
                <?php endforeach; ?>

                <div class="d-flex justify-content-between mt-4">
                    <a href="<?= BASE_PATH ?>/webdatabase/records/<?= $database['id'] ?>" class="btn btn-secondary">キャンセル</a>
                    <button type="submit" class="btn btn-primary"><?= isset($record) ? "更新" : "作成" ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    window.addEventListener('load', function() {
        // フォーム送信時の処理
        $('#record-form').off('submit').on('submit', function(e) {
            e.preventDefault();

            // フォームデータの取得
            const formData = new FormData(this);
            const $submitBtn = $(this).find('button[type="submit"]');
            if ($submitBtn.prop('disabled')) {
                // すでに送信中なら処理しない（二重送信防止）
                return false;
            }

            // APIリクエスト
            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                beforeSend: function() {
                    $('button[type="submit"]').prop('disabled', true);
                    $('.is-invalid').removeClass('is-invalid');
                    $('.invalid-feedback').text('');
                },
                success: function(response) {
                   
                    if (response.success) {
                        App.showNotification(response.message, 'success');

                        // リダイレクト
                        if (response.redirect) {
                            setTimeout(function() {
                                window.location.href = response.redirect;
                            }, 1000);
                        }
                        return false;
                    } else {
                        App.showNotification(response.error || 'エラーが発生しました', 'error');

                        // バリデーションエラーの表示
                        if (response.validation) {
                            console.log("バリデーションエラー:", response.validation);
                            for (const field in response.validation) {
                                let input;
                                if (field.includes('.')) {
                                    // フィールドエラーの場合（fields.1 のような形式）
                                    const fieldId = field.split('.')[1];
                                    input = $(`[name="fields[${fieldId}]"]`);
                                } else {
                                    input = $(`[name="${field}"]`);
                                }

                                if (input.length) {
                                    input.addClass('is-invalid');
                                    input.next('.invalid-feedback').text(response.validation[field]);
                                }
                            }
                        }
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error:", status, error);
                    console.log("Response:", xhr.responseText);
                    App.showNotification('エラーが発生しました: ' + error, 'error');
                },
                complete: function() {
                    $('button[type="submit"]').prop('disabled', false);
                }

            });
            return false;
        });
        return false;
    });
</script>
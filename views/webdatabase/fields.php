<!-- views/webdatabase/fields.php -->
<div class="container-fluid mt-4">
    <div class="row mb-3">
        <div class="col-md-8">
            <h1><?= htmlspecialchars($database['name']) ?> - フィールド設定</h1>
        </div>
        <div class="col-md-4 text-end">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#add-field-modal">
                <i class="fas fa-plus"></i> 新規フィールド追加
            </button>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> フィールドを追加して、データベースの構造を定義します。少なくとも1つのフィールドを追加してください。
            </div>

            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th width="5%">#</th>
                            <th width="20%">フィールド名</th>
                            <th width="15%">タイプ</th>
                            <th width="30%">説明</th>
                            <th width="15%">属性</th>
                            <th width="15%">操作</th>
                        </tr>
                    </thead>
                    <tbody id="field-list">
                        <?php if (empty($fields)): ?>
                            <tr>
                                <td colspan="6" class="text-center">フィールドがありません。フィールドを追加してください。</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($fields as $index => $field): ?>
                                <tr id="field-<?= $field['id'] ?>">
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($field['name']) ?></td>
                                    <td>
                                        <?php
                                        $fieldTypes = [
                                            'text' => 'テキスト',
                                            'textarea' => '複数行テキスト',
                                            'number' => '数値',
                                            'date' => '日付',
                                            'datetime' => '日時',
                                            'select' => '選択肢',
                                            'radio' => 'ラジオボタン',
                                            'checkbox' => 'チェックボックス',
                                            'file' => 'ファイル',
                                            'user' => 'ユーザー',
                                            'organization' => '組織',
                                            'relation' => 'リレーション',
                                            'lookup' => 'ルックアップ',
                                            'calc' => '計算',
                                            'url' => 'URL',
                                            'email' => 'メール',
                                            'phone' => '電話番号',
                                            'currency' => '通貨',
                                            'percent' => 'パーセント',
                                            'auto_number' => '自動採番'
                                        ];
                                        echo $fieldTypes[$field['type']] ?? $field['type'];
                                        ?>
                                    </td>
                                    <td><?= htmlspecialchars($field['description'] ?? '') ?></td>
                                    <td>
                                        <?php if ($field['required']): ?>
                                            <span class="badge bg-danger me-1">必須</span>
                                        <?php endif; ?>
                                        <?php if ($field['unique_value']): ?>
                                            <span class="badge bg-warning me-1">ユニーク</span>
                                        <?php endif; ?>
                                        <?php if ($field['is_title_field']): ?>
                                            <span class="badge bg-primary me-1">タイトル</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary edit-field-btn" data-field-id="<?= $field['id'] ?>">
                                            <i class="fas fa-edit"></i> 編集
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger delete-field-btn" data-field-id="<?= $field['id'] ?>">
                                            <i class="fas fa-trash"></i> 削除
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between mt-3">
        <a href="<?= BASE_PATH ?>/webdatabase" class="btn btn-secondary">戻る</a>
        <a href="<?= BASE_PATH ?>/webdatabase/records/<?= $database['id'] ?>" class="btn btn-primary">レコード管理へ進む</a>
    </div>
</div>

<!-- 新規フィールド追加モーダル -->
<div class="modal fade" id="add-field-modal" tabindex="-1" aria-labelledby="add-field-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="add-field-modal-label">新規フィールド追加</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="add-field-form" action="<?= BASE_PATH ?>/api/webdatabase/<?= $database['id'] ?>/fields" method="POST">
                    <input type="hidden" name="database_id" value="<?= $database['id'] ?>">

                    <div class="mb-3">
                        <label for="field-name" class="form-label">フィールド名 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="field-name" name="name" required>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="mb-3">
                        <label for="field-type" class="form-label">フィールドタイプ <span class="text-danger">*</span></label>
                        <select class="form-select" id="field-type" name="type" required>
                            <option value="">タイプを選択</option>
                            <option value="text">テキスト</option>
                            <option value="textarea">複数行テキスト</option>
                            <option value="number">数値</option>
                            <option value="date">日付</option>
                            <option value="datetime">日時</option>
                            <option value="select">選択肢</option>
                            <option value="radio">ラジオボタン</option>
                            <option value="checkbox">チェックボックス</option>
                            <option value="file">ファイル</option>
                            <option value="user">ユーザー</option>
                            <option value="organization">組織</option>
                            <optgroup label="リレーション">
                                <option value="relation">リレーション（他DB参照）</option>
                                <option value="lookup">ルックアップ（参照先の値表示）</option>
                            </optgroup>
                            <optgroup label="高度なフィールド">
                                <option value="calc">計算フィールド</option>
                                <option value="url">URL</option>
                                <option value="email">メールアドレス</option>
                                <option value="phone">電話番号</option>
                                <option value="currency">通貨</option>
                                <option value="percent">パーセント</option>
                                <option value="auto_number">自動採番</option>
                            </optgroup>
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="mb-3">
                        <label for="field-description" class="form-label">説明</label>
                        <textarea class="form-control" id="field-description" name="description" rows="2"></textarea>
                        <div class="invalid-feedback"></div>
                    </div>

                    <!-- 選択肢オプション（select, radio, checkbox用） -->
                    <div class="mb-3 d-none" id="options-container">
                        <label for="field-options" class="form-label">選択肢オプション <span class="text-danger">*</span></label>
                        <div class="alert alert-info">
                            各選択肢を1行ずつ入力してください。例：<br>
                            選択肢1<br>
                            選択肢2<br>
                            選択肢3
                        </div>
                        <textarea class="form-control" id="field-options" name="options" rows="5"></textarea>
                        <div class="invalid-feedback"></div>
                    </div>

                    <!-- リレーション設定（relation型用） -->
                    <div class="mb-3 d-none" id="relation-container">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title"><i class="fas fa-link"></i> リレーション設定</h6>
                                <div class="mb-2">
                                    <label class="form-label">参照先データベース <span class="text-danger">*</span></label>
                                    <select class="form-select" id="field-relation-database" name="relation_database_id">
                                        <option value="">データベースを選択</option>
                                    </select>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">リレーションタイプ</label>
                                    <select class="form-select" id="field-relation-type" name="relation_type">
                                        <option value="one_to_many">1対多</option>
                                        <option value="many_to_many">多対多</option>
                                        <option value="one_to_one">1対1</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ルックアップ設定（lookup型用） -->
                    <div class="mb-3 d-none" id="lookup-container">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title"><i class="fas fa-eye"></i> ルックアップ設定</h6>
                                <div class="mb-2">
                                    <label class="form-label">リレーションフィールド <span class="text-danger">*</span></label>
                                    <select class="form-select" id="field-lookup-relation" name="lookup_relation_field_id">
                                        <option value="">リレーションフィールドを選択</option>
                                        <?php foreach ($fields as $f): ?>
                                            <?php if ($f['type'] === 'relation'): ?>
                                                <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['name']) ?></option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted">先にリレーションフィールドを作成してください</small>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">参照先フィールド <span class="text-danger">*</span></label>
                                    <select class="form-select" id="field-lookup-target" name="lookup_target_field_id">
                                        <option value="">フィールドを選択</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 計算式設定（calc型用） -->
                    <div class="mb-3 d-none" id="calc-container">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title"><i class="fas fa-calculator"></i> 計算式設定</h6>
                                <div class="mb-2">
                                    <label class="form-label">計算式</label>
                                    <input type="text" class="form-control" id="field-calc-formula" name="calc_formula" placeholder="例: {フィールド1} * {フィールド2}">
                                    <small class="text-muted">フィールド名を {} で囲んで参照。演算子: +, -, *, /</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="field-default" class="form-label">デフォルト値</label>
                        <input type="text" class="form-control" id="field-default" name="default_value">
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="field-required" name="required" value="1">
                            <label class="form-check-label" for="field-required">
                                必須項目
                            </label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="field-unique" name="unique_value" value="1">
                            <label class="form-check-label" for="field-unique">
                                ユニーク値（重複不可）
                            </label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="field-title" name="is_title_field" value="1">
                            <label class="form-check-label" for="field-title">
                                タイトルフィールド（一覧表示に使用）
                                <small class="text-muted">複数選択可能 - 選択した複数のフィールドはハイフン（-）で区切って表示されます</small>
                            </label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="field-filterable" name="is_filterable" value="1">
                            <label class="form-check-label" for="field-filterable">
                                フィルタ可能
                            </label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="field-sortable" name="is_sortable" value="1">
                            <label class="form-check-label" for="field-sortable">
                                ソート可能
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                <button type="button" class="btn btn-primary" id="save-field-btn">保存</button>
            </div>
        </div>
    </div>
</div>

<!-- フィールド編集モーダル -->
<div class="modal fade" id="edit-field-modal" tabindex="-1" aria-labelledby="edit-field-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="edit-field-modal-label">フィールド編集</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="edit-field-form" action="" method="POST">
                    <input type="hidden" name="database_id" value="<?= $database['id'] ?>">

                    <!-- 編集フォームのフィールド（追加フォームと同じ） -->
                    <div class="mb-3">
                        <label for="edit-field-name" class="form-label">フィールド名 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit-field-name" name="name" required>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="mb-3">
                        <label for="edit-field-type" class="form-label">フィールドタイプ <span class="text-danger">*</span></label>
                        <select class="form-select" id="edit-field-type" name="type" required>
                            <option value="">タイプを選択</option>
                            <option value="text">テキスト</option>
                            <option value="textarea">複数行テキスト</option>
                            <option value="number">数値</option>
                            <option value="date">日付</option>
                            <option value="datetime">日時</option>
                            <option value="select">選択肢</option>
                            <option value="radio">ラジオボタン</option>
                            <option value="checkbox">チェックボックス</option>
                            <option value="file">ファイル</option>
                            <option value="user">ユーザー</option>
                            <option value="organization">組織</option>
                            <optgroup label="リレーション">
                                <option value="relation">リレーション（他DB参照）</option>
                                <option value="lookup">ルックアップ（参照先の値表示）</option>
                            </optgroup>
                            <optgroup label="高度なフィールド">
                                <option value="calc">計算フィールド</option>
                                <option value="url">URL</option>
                                <option value="email">メールアドレス</option>
                                <option value="phone">電話番号</option>
                                <option value="currency">通貨</option>
                                <option value="percent">パーセント</option>
                                <option value="auto_number">自動採番</option>
                            </optgroup>
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="mb-3">
                        <label for="edit-field-description" class="form-label">説明</label>
                        <textarea class="form-control" id="edit-field-description" name="description" rows="2"></textarea>
                        <div class="invalid-feedback"></div>
                    </div>

                    <!-- 選択肢オプション（select, radio, checkbox用） -->
                    <div class="mb-3 d-none" id="edit-options-container">
                        <label for="edit-field-options" class="form-label">選択肢オプション <span class="text-danger">*</span></label>
                        <div class="alert alert-info">
                            各選択肢を1行ずつ入力してください。例：<br>
                            選択肢1<br>
                            選択肢2<br>
                            選択肢3
                        </div>
                        <textarea class="form-control" id="edit-field-options" name="options" rows="5"></textarea>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="mb-3">
                        <label for="edit-field-default" class="form-label">デフォルト値</label>
                        <input type="text" class="form-control" id="edit-field-default" name="default_value">
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="edit-field-required" name="required" value="1">
                            <label class="form-check-label" for="edit-field-required">
                                必須項目
                            </label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="edit-field-unique" name="unique_value" value="1">
                            <label class="form-check-label" for="edit-field-unique">
                                ユニーク値（重複不可）
                            </label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="edit-field-title" name="is_title_field" value="1">
                            <label class="form-check-label" for="edit-field-title">
                                タイトルフィールド（一覧表示に使用）
                                <small class="text-muted">複数選択可能 - 選択した複数のフィールドはハイフン（-）で区切って表示されます</small>
                            </label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="edit-field-filterable" name="is_filterable" value="1">
                            <label class="form-check-label" for="edit-field-filterable">
                                フィルタ可能
                            </label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="edit-field-sortable" name="is_sortable" value="1">
                            <label class="form-check-label" for="edit-field-sortable">
                                ソート可能
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                <button type="button" class="btn btn-primary" id="update-field-btn">更新</button>
            </div>
        </div>
    </div>
</div>
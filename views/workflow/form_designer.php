<?php
// views/workflow/form_designer.php
$pageTitle = 'フォームデザイン - ' . $template['name'];
?>
<div class="container-fluid" data-page-type="form_designer">
    <input type="hidden" id="template-id" value="<?php echo $template['id']; ?>">

    <div class="row mb-4">
        <div class="col">
            <h1 class="h3"><?php echo $pageTitle; ?></h1>
        </div>
        <div class="col-auto">
            <button id="btn-save-form" class="btn btn-primary me-2">
                <i class="fas fa-save"></i> 保存
            </button>
            <a href="<?php echo BASE_PATH; ?>/workflow/templates" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> 一覧に戻る
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">フォーム要素</h5>
                </div>
                <div class="card-body">
                    <div class="form-item card mb-2" data-type="text">
                        <div class="card-body">
                            <i class="fas fa-font me-2"></i> テキスト入力
                        </div>
                    </div>
                    <div class="form-item card mb-2" data-type="textarea">
                        <div class="card-body">
                            <i class="fas fa-align-left me-2"></i> テキストエリア
                        </div>
                    </div>
                    <div class="form-item card mb-2" data-type="select">
                        <div class="card-body">
                            <i class="fas fa-list me-2"></i> セレクトボックス
                        </div>
                    </div>
                    <div class="form-item card mb-2" data-type="radio">
                        <div class="card-body">
                            <i class="fas fa-dot-circle me-2"></i> ラジオボタン
                        </div>
                    </div>
                    <div class="form-item card mb-2" data-type="checkbox">
                        <div class="card-body">
                            <i class="fas fa-check-square me-2"></i> チェックボックス
                        </div>
                    </div>
                    <div class="form-item card mb-2" data-type="date">
                        <div class="card-body">
                            <i class="fas fa-calendar-alt me-2"></i> 日付
                        </div>
                    </div>
                    <div class="form-item card mb-2" data-type="number">
                        <div class="card-body">
                            <i class="fas fa-calculator me-2"></i> 数値
                        </div>
                    </div>
                    <div class="form-item card mb-2" data-type="file">
                        <div class="card-body">
                            <i class="fas fa-file me-2"></i> ファイル
                        </div>
                    </div>
                    <div class="form-item card mb-2" data-type="heading">
                        <div class="card-body">
                            <i class="fas fa-heading me-2"></i> 見出し
                        </div>
                    </div>
                    <div class="form-item card mb-2" data-type="hidden">
                        <div class="card-body">
                            <i class="fas fa-eye-slash me-2"></i> 隠しフィールド
                        </div>
                    </div>
                </div>
            </div>

            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> フォーム要素を右側のエリアにドラッグ＆ドロップしてください。
            </div>
        </div>

        <div class="col-md-9">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">フォームデザイン</h5>
                </div>
                <div class="card-body">
                    <div id="form-container" class="border rounded p-3" style="min-height: 300px;">
                        <!-- フォーム要素がここに追加される -->
                        <div class="text-center p-5 text-muted">フォーム要素をドラッグ＆ドロップしてください</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- フィールド編集モーダル -->
<div class="modal fade" id="field-edit-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">フィールド編集</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="field-edit-form">
                <input type="hidden" id="field-id" name="field-id">
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="field-key" class="form-label">フィールドID</label>
                            <input type="text" class="form-control" id="field-key" name="field-key" readonly>
                            <small class="form-text text-muted">システム内部で使用する識別子</small>
                        </div>
                        <div class="col-md-6">
                            <label for="field-type" class="form-label">フィールドタイプ</label>
                            <select class="form-select" id="field-type" name="field-type">
                                <option value="text">テキスト入力</option>
                                <option value="textarea">テキストエリア</option>
                                <option value="select">セレクトボックス</option>
                                <option value="radio">ラジオボタン</option>
                                <option value="checkbox">チェックボックス</option>
                                <option value="date">日付</option>
                                <option value="number">数値</option>
                                <option value="file">ファイル</option>
                                <option value="heading">見出し</option>
                                <option value="hidden">隠しフィールド</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="field-label" class="form-label">ラベル <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="field-label" name="field-label" required>
                        <small class="form-text text-muted">フォームに表示されるラベル</small>
                    </div>

                    <div class="mb-3">
                        <label for="field-placeholder" class="form-label">プレースホルダー</label>
                        <input type="text" class="form-control" id="field-placeholder" name="field-placeholder">
                        <small class="form-text text-muted">入力欄に表示されるヒント</small>
                    </div>

                    <div class="mb-3">
                        <label for="field-help" class="form-label">ヘルプテキスト</label>
                        <input type="text" class="form-control" id="field-help" name="field-help">
                        <small class="form-text text-muted">フィールドの下に表示される説明文</small>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="field-required" name="field-required">
                        <label class="form-check-label" for="field-required">
                            必須フィールド
                        </label>
                    </div>

                    <!-- フィールドタイプ別のオプション -->
                    <div id="text-options" class="field-type-options mb-3">
                        <hr>
                        <h6>テキストオプション</h6>
                        <div class="mb-3">
                            <label for="validation-min-length" class="form-label">最小文字数</label>
                            <input type="number" class="form-control" id="validation-min-length" name="validation-min-length">
                        </div>
                        <div class="mb-3">
                            <label for="validation-max-length" class="form-label">最大文字数</label>
                            <input type="number" class="form-control" id="validation-max-length" name="validation-max-length">
                        </div>
                        <div class="mb-3">
                            <label for="validation-pattern" class="form-label">入力パターン (正規表現)</label>
                            <input type="text" class="form-control" id="validation-pattern" name="validation-pattern">
                            <small class="form-text text-muted">例: メールアドレス: ^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$</small>
                        </div>
                    </div>

                    <div id="number-options" class="field-type-options mb-3">
                        <hr>
                        <h6>数値オプション</h6>
                        <div class="mb-3">
                            <label for="validation-min" class="form-label">最小値</label>
                            <input type="number" class="form-control" id="validation-min" name="validation-min">
                        </div>
                        <div class="mb-3">
                            <label for="validation-max" class="form-label">最大値</label>
                            <input type="number" class="form-control" id="validation-max" name="validation-max">
                        </div>
                    </div>

                    <div id="date-options" class="field-type-options mb-3">
                        <hr>
                        <h6>日付オプション</h6>
                        <div class="mb-3">
                            <label for="validation-min-date" class="form-label">最小日付</label>
                            <input type="date" class="form-control" id="validation-min-date" name="validation-min-date">
                        </div>
                        <div class="mb-3">
                            <label for="validation-max-date" class="form-label">最大日付</label>
                            <input type="date" class="form-control" id="validation-max-date" name="validation-max-date">
                        </div>
                    </div>

                    <div id="file-options" class="field-type-options mb-3">
                        <hr>
                        <h6>ファイルオプション</h6>
                        <div class="mb-3">
                            <label for="validation-file-types" class="form-label">許可するファイル形式</label>
                            <input type="text" class="form-control" id="validation-file-types" name="validation-file-types">
                            <small class="form-text text-muted">例: .pdf,.doc,.docx</small>
                        </div>
                        <div class="mb-3">
                            <label for="validation-max-size" class="form-label">最大ファイルサイズ (MB)</label>
                            <input type="number" class="form-control" id="validation-max-size" name="validation-max-size">
                        </div>
                    </div>

                    <div id="options-section" class="field-type-options mb-3">
                        <hr>
                        <h6>選択肢設定 <button type="button" id="btn-add-option" class="btn btn-sm btn-success"><i class="fas fa-plus"></i> 選択肢追加</button></h6>
                        <div id="options-container">
                            <!-- 選択肢が動的に追加される -->
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                    <button type="submit" class="btn btn-primary">保存</button>
                </div>
            </form>
        </div>
    </div>
</div>
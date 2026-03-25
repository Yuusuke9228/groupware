<style>
.csv-import-container { max-width: 960px; margin: 0 auto; padding: 20px; }
.csv-tab-nav { display: flex; gap: 0; border-bottom: 2px solid #dee2e6; margin-bottom: 24px; }
.csv-tab-btn { padding: 10px 24px; border: none; background: none; font-size: 14px; font-weight: 500; color: #6c757d; cursor: pointer; border-bottom: 2px solid transparent; margin-bottom: -2px; transition: all .2s; }
.csv-tab-btn:hover { color: #2b7de9; }
.csv-tab-btn.active { color: #2b7de9; border-bottom-color: #2b7de9; }
.csv-tab-content { display: none; }
.csv-tab-content.active { display: block; }
.csv-info-card { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; padding: 20px; margin-bottom: 20px; }
.csv-info-card h6 { margin: 0 0 12px; font-weight: 600; }
.csv-columns-table { width: 100%; font-size: 13px; }
.csv-columns-table th { background: #e9ecef; padding: 6px 10px; text-align: left; }
.csv-columns-table td { padding: 6px 10px; border-bottom: 1px solid #eee; }
.csv-columns-table .required { color: #dc3545; font-weight: 600; }
.csv-preview-area { margin-top: 16px; }
.csv-preview-area table { font-size: 12px; }
.csv-result { margin-top: 16px; }
.csv-result .alert ul { margin-bottom: 0; max-height: 200px; overflow-y: auto; }
.csv-upload-zone { border: 2px dashed #dee2e6; border-radius: 8px; padding: 30px; text-align: center; background: #fafbfc; margin-bottom: 16px; transition: border-color .2s; }
.csv-upload-zone:hover { border-color: #2b7de9; }
.csv-upload-zone i { font-size: 36px; color: #adb5bd; margin-bottom: 8px; }
</style>

<div class="csv-import-container">
    <h4 class="mb-1"><i class="fas fa-file-csv me-2"></i>CSVインポート</h4>
    <p class="text-muted mb-3">CSVファイルからデータを一括登録します。</p>

    <div class="csv-tab-nav">
        <button class="csv-tab-btn active" data-tab="users"><i class="fas fa-users me-1"></i>ユーザー</button>
        <button class="csv-tab-btn" data-tab="organizations"><i class="fas fa-sitemap me-1"></i>組織</button>
        <button class="csv-tab-btn" data-tab="address-book"><i class="fas fa-address-book me-1"></i>アドレス帳</button>
    </div>

    <!-- ユーザーインポート -->
    <div class="csv-tab-content active" id="tab-users">
        <div class="csv-info-card">
            <h6><i class="fas fa-info-circle me-1 text-primary"></i>ユーザーCSVの形式</h6>
            <div class="table-responsive"><table class="csv-columns-table">
                <thead><tr><th>列名</th><th>必須</th><th>説明</th></tr></thead>
                <tbody>
                    <tr><td>username</td><td class="required">必須</td><td>ログインID（ユニーク）</td></tr>
                    <tr><td>password</td><td class="required">必須</td><td>パスワード（インポート時にハッシュ化されます）</td></tr>
                    <tr><td>display_name</td><td class="required">必須</td><td>表示名</td></tr>
                    <tr><td>email</td><td>任意</td><td>メールアドレス（ユニーク）</td></tr>
                    <tr><td>role</td><td>任意</td><td>admin / manager / user（デフォルト: user）</td></tr>
                    <tr><td>organization_id</td><td>任意</td><td>所属組織ID（数値）</td></tr>
                    <tr><td>organization_code</td><td>任意</td><td>所属組織コード。`organization_id` がなくても指定できます</td></tr>
                </tbody>
            </table></div>
        </div>
        <a href="<?php echo BASE_PATH; ?>/admin/csv-import/sample/users" class="btn btn-outline-secondary btn-sm mb-3">
            <i class="fas fa-download me-1"></i>サンプルCSVをダウンロード
        </a>
        <form class="csv-import-form no-ajax" data-type="users" data-url="<?php echo BASE_PATH; ?>/admin/csv-import/users">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <div class="csv-upload-zone">
                <i class="fas fa-cloud-upload-alt d-block"></i>
                <p class="mb-2">CSVファイルを選択してください</p>
                <input type="file" name="csv_file" accept=".csv" class="form-control" style="max-width:400px;margin:0 auto;" required>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-primary btn-preview"><i class="fas fa-eye me-1"></i>プレビュー（先頭5件）</button>
                <button type="button" class="btn btn-primary btn-import" disabled><i class="fas fa-file-import me-1"></i>インポート実行</button>
            </div>
            <div class="csv-preview-area"></div>
            <div class="csv-result"></div>
        </form>
    </div>

    <!-- 組織インポート -->
    <div class="csv-tab-content" id="tab-organizations">
        <div class="csv-info-card">
            <h6><i class="fas fa-info-circle me-1 text-primary"></i>組織CSVの形式</h6>
            <div class="table-responsive"><table class="csv-columns-table">
                <thead><tr><th>列名</th><th>必須</th><th>説明</th></tr></thead>
                <tbody>
                    <tr><td>code</td><td>任意</td><td>組織コード（ユニーク）。未指定時は自動採番</td></tr>
                    <tr><td>name</td><td class="required">必須</td><td>組織名。同名でも親組織が異なれば登録できます</td></tr>
                    <tr><td>parent_id</td><td>任意</td><td>親組織ID（数値、既存組織を参照する場合）</td></tr>
                    <tr><td>parent_code</td><td>任意</td><td>親組織コード。階層登録はこちらの利用を推奨</td></tr>
                    <tr><td>description</td><td>任意</td><td>説明</td></tr>
                    <tr><td>sort_order</td><td>任意</td><td>表示順（数値、デフォルト: 0）</td></tr>
                </tbody>
            </table></div>
        </div>
        <div class="alert alert-info">
            同じ名称の子組織を複数の親配下に登録する場合は、`code` と `parent_code` を指定してください。
        </div>
        <a href="<?php echo BASE_PATH; ?>/admin/csv-import/sample/organizations" class="btn btn-outline-secondary btn-sm mb-3">
            <i class="fas fa-download me-1"></i>サンプルCSVをダウンロード
        </a>
        <form class="csv-import-form no-ajax" data-type="organizations" data-url="<?php echo BASE_PATH; ?>/admin/csv-import/organizations">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <div class="csv-upload-zone">
                <i class="fas fa-cloud-upload-alt d-block"></i>
                <p class="mb-2">CSVファイルを選択してください</p>
                <input type="file" name="csv_file" accept=".csv" class="form-control" style="max-width:400px;margin:0 auto;" required>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-primary btn-preview"><i class="fas fa-eye me-1"></i>プレビュー（先頭5件）</button>
                <button type="button" class="btn btn-primary btn-import" disabled><i class="fas fa-file-import me-1"></i>インポート実行</button>
            </div>
            <div class="csv-preview-area"></div>
            <div class="csv-result"></div>
        </form>
    </div>

    <!-- アドレス帳インポート -->
    <div class="csv-tab-content" id="tab-address-book">
        <div class="csv-info-card">
            <h6><i class="fas fa-info-circle me-1 text-primary"></i>アドレス帳CSVの形式</h6>
            <div class="table-responsive"><table class="csv-columns-table">
                <thead><tr><th>列名</th><th>必須</th><th>説明</th></tr></thead>
                <tbody>
                    <tr><td>name</td><td class="required">必須</td><td>氏名</td></tr>
                    <tr><td>name_kana</td><td>任意</td><td>フリガナ</td></tr>
                    <tr><td>company</td><td>任意</td><td>会社名</td></tr>
                    <tr><td>department</td><td>任意</td><td>部署</td></tr>
                    <tr><td>position_title</td><td>任意</td><td>役職</td></tr>
                    <tr><td>email</td><td>任意</td><td>メールアドレス</td></tr>
                    <tr><td>phone</td><td>任意</td><td>電話番号</td></tr>
                    <tr><td>mobile</td><td>任意</td><td>携帯番号</td></tr>
                    <tr><td>category</td><td>任意</td><td>カテゴリ（例: 取引先、社内など）</td></tr>
                </tbody>
            </table></div>
        </div>
        <a href="<?php echo BASE_PATH; ?>/admin/csv-import/sample/address-book" class="btn btn-outline-secondary btn-sm mb-3">
            <i class="fas fa-download me-1"></i>サンプルCSVをダウンロード
        </a>
        <form class="csv-import-form no-ajax" data-type="address-book" data-url="<?php echo BASE_PATH; ?>/admin/csv-import/address-book">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <div class="csv-upload-zone">
                <i class="fas fa-cloud-upload-alt d-block"></i>
                <p class="mb-2">CSVファイルを選択してください</p>
                <input type="file" name="csv_file" accept=".csv" class="form-control" style="max-width:400px;margin:0 auto;" required>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-primary btn-preview"><i class="fas fa-eye me-1"></i>プレビュー（先頭5件）</button>
                <button type="button" class="btn btn-primary btn-import" disabled><i class="fas fa-file-import me-1"></i>インポート実行</button>
            </div>
            <div class="csv-preview-area"></div>
            <div class="csv-result"></div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // タブ切り替え
    document.querySelectorAll('.csv-tab-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.csv-tab-btn').forEach(function(b) { b.classList.remove('active'); });
            document.querySelectorAll('.csv-tab-content').forEach(function(c) { c.classList.remove('active'); });
            btn.classList.add('active');
            document.getElementById('tab-' + btn.dataset.tab).classList.add('active');
        });
    });

    // プレビュー
    document.querySelectorAll('.btn-preview').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var form = btn.closest('.csv-import-form');
            var fileInput = form.querySelector('input[type="file"]');
            var previewArea = form.querySelector('.csv-preview-area');
            var resultArea = form.querySelector('.csv-result');
            var importBtn = form.querySelector('.btn-import');

            if (!fileInput.files.length) {
                alert('CSVファイルを選択してください。');
                return;
            }

            var fd = new FormData();
            fd.append('csv_file', fileInput.files[0]);
            fd.append('csrf_token', form.querySelector('[name="csrf_token"]').value);
            fd.append('preview', '1');

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>読込中...';

            fetch(form.dataset.url, { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-eye me-1"></i>プレビュー（先頭5件）';
                    resultArea.innerHTML = '';

                    if (!data.success) {
                        previewArea.innerHTML = '<div class="alert alert-danger mt-3">' + escapeHtml(data.message) + '</div>';
                        importBtn.disabled = true;
                        return;
                    }

                    var total = data.total;
                    var rows = data.preview;
                    if (!rows.length) {
                        previewArea.innerHTML = '<div class="alert alert-warning mt-3">データがありません。</div>';
                        importBtn.disabled = true;
                        return;
                    }

                    var cols = Object.keys(rows[0]);
                    var html = '<div class="mt-3"><strong>全 ' + total + ' 件のデータ（先頭5件プレビュー）</strong></div>';
                    html += '<div class="table-responsive mt-2"><table class="table table-sm table-bordered"><thead><tr>';
                    cols.forEach(function(c) { html += '<th>' + escapeHtml(c) + '</th>'; });
                    html += '</tr></thead><tbody>';
                    rows.forEach(function(row) {
                        html += '<tr>';
                        cols.forEach(function(c) {
                            var val = row[c] || '';
                            if (c === 'password') val = '********';
                            html += '<td>' + escapeHtml(val) + '</td>';
                        });
                        html += '</tr>';
                    });
                    html += '</tbody></table></div>';
                    previewArea.innerHTML = html;
                    importBtn.disabled = false;
                })
                .catch(function(err) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-eye me-1"></i>プレビュー（先頭5件）';
                    previewArea.innerHTML = '<div class="alert alert-danger mt-3">通信エラーが発生しました。</div>';
                });
        });
    });

    // インポート実行
    document.querySelectorAll('.btn-import').forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (!confirm('インポートを実行します。よろしいですか？')) return;

            var form = btn.closest('.csv-import-form');
            var fileInput = form.querySelector('input[type="file"]');
            var resultArea = form.querySelector('.csv-result');

            if (!fileInput.files.length) {
                alert('CSVファイルを選択してください。');
                return;
            }

            var fd = new FormData();
            fd.append('csv_file', fileInput.files[0]);
            fd.append('csrf_token', form.querySelector('[name="csrf_token"]').value);

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>インポート中...';

            fetch(form.dataset.url, { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-file-import me-1"></i>インポート実行';

                    if (!data.success && !data.successCount) {
                        resultArea.innerHTML = '<div class="alert alert-danger mt-3">' + escapeHtml(data.message) + '</div>';
                        return;
                    }

                    var alertClass = data.errorCount > 0 ? 'alert-warning' : 'alert-success';
                    var html = '<div class="alert ' + alertClass + ' mt-3">';
                    html += '<strong>' + escapeHtml(data.message) + '</strong>';
                    if (data.errors && data.errors.length) {
                        html += '<ul class="mt-2 mb-0">';
                        data.errors.forEach(function(e) { html += '<li>' + escapeHtml(e) + '</li>'; });
                        html += '</ul>';
                    }
                    html += '</div>';
                    resultArea.innerHTML = html;
                })
                .catch(function(err) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-file-import me-1"></i>インポート実行';
                    resultArea.innerHTML = '<div class="alert alert-danger mt-3">通信エラーが発生しました。</div>';
                });
        });
    });

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }
});
</script>

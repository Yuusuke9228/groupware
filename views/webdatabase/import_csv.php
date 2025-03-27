<!-- views/webdatabase/import_csv.php -->
<div class="container-fluid mt-4">
    <div class="row mb-3">
        <div class="col">
            <h1>CSVインポート</h1>
            <h5 class="text-muted"><?= htmlspecialchars($database['name']) ?></h5>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> CSVファイルをアップロードして、データベースにレコードをインポートします。<br>
                CSVファイルの最初の行はヘッダー行として扱われます。ヘッダー名とフィールド名を一致させると、自動的にマッピングされます。
            </div>

            <form id="import-form" action="<?= BASE_PATH ?>/api/webdatabase/<?= $database['id'] ?>/import-csv" method="POST" enctype="multipart/form-data">
                <div class="mb-4">
                    <h5>インポート設定</h5>

                    <div class="mb-3">
                        <label for="csv_file" class="form-label">CSVファイル <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" id="csv_file" name="csv_file" accept=".csv" required>
                        <div class="form-text">UTF-8またはShift-JIS形式のCSVファイルをアップロードしてください。</div>
                    </div>

                    <div class="mb-3" id="preview-container" style="display: none;">
                        <h5>プレビューとフィールドマッピング</h5>
                        <div class="alert alert-warning">
                            CSVヘッダーとデータベースフィールドを適切にマッピングしてください。
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered" id="preview-table">
                                <thead>
                                    <tr id="csv-headers">
                                        <!-- CSVヘッダーがここに表示される -->
                                    </tr>
                                    <tr id="field-mapping">
                                        <!-- フィールドマッピングのセレクトボックスがここに表示される -->
                                    </tr>
                                </thead>
                                <tbody id="csv-preview">
                                    <!-- CSVプレビューがここに表示される -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="<?= BASE_PATH ?>/webdatabase/records/<?= $database['id'] ?>" class="btn btn-secondary">戻る</a>
                    <button type="submit" class="btn btn-primary" id="import-btn">
                        <i class="fas fa-upload"></i> CSVインポート
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // フィールド情報をJSで利用できるように
    const databaseFields = <?= json_encode($fields) ?>;
</script>
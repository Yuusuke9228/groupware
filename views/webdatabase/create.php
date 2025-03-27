<!-- views/webdatabase/create.php -->
<div class="container-fluid mt-4">
    <div class="row mb-3">
        <div class="col">
            <h1>新規データベース作成</h1>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form id="create-database-form" action="<?= BASE_PATH ?>/api/webdatabase" method="POST">
                <div class="mb-3">
                    <label for="name" class="form-label">データベース名 <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="name" name="name" required>
                    <div class="invalid-feedback"></div>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">説明</label>
                    <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    <div class="invalid-feedback"></div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="icon" class="form-label">アイコン</label>
                        <div class="input-group">
                            <span class="input-group-text"><i id="icon-preview" class="fas fa-database"></i></span>
                            <select class="form-select" id="icon" name="icon">
                                <option value="database">データベース</option>
                                <option value="table">テーブル</option>
                                <option value="list">リスト</option>
                                <option value="tasks">タスク</option>
                                <option value="calendar">カレンダー</option>
                                <option value="users">ユーザー</option>
                                <option value="building">組織</option>
                                <option value="file">ファイル</option>
                                <option value="folder">フォルダ</option>
                                <option value="project-diagram">プロジェクト</option>
                                <option value="chart-bar">グラフ</option>
                                <option value="clipboard">クリップボード</option>
                                <option value="sticky-note">メモ</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="color" class="form-label">カラー</label>
                        <div class="input-group">
                            <input type="color" class="form-control form-control-color" id="color" name="color" value="#3498db">
                            <input type="text" class="form-control" id="color-text" value="#3498db" readonly>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="is_public" name="is_public" value="1">
                        <label class="form-check-label" for="is_public">
                            全体に公開する
                        </label>
                    </div>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="<?= BASE_PATH ?>/webdatabase" class="btn btn-secondary">キャンセル</a>
                    <button type="submit" class="btn btn-primary">作成</button>
                </div>
            </form>
        </div>
    </div>
</div>
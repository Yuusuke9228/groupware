<!-- views/webdatabase/edit.php -->
<div class="container-fluid mt-4">
    <div class="row mb-3">
        <div class="col">
            <h1>データベース編集</h1>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form id="edit-database-form" action="<?= BASE_PATH ?>/api/webdatabase/<?= $database['id'] ?>" method="POST">
                <div class="mb-3">
                    <label for="name" class="form-label">データベース名 <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($database['name']) ?>" required>
                    <div class="invalid-feedback"></div>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">説明</label>
                    <textarea class="form-control" id="description" name="description" rows="3"><?= htmlspecialchars($database['description'] ?? '') ?></textarea>
                    <div class="invalid-feedback"></div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="icon" class="form-label">アイコン</label>
                        <div class="input-group">
                            <span class="input-group-text"><i id="icon-preview" class="fas fa-<?= htmlspecialchars($database['icon']) ?>"></i></span>
                            <select class="form-select" id="icon" name="icon">
                                <option value="database" <?= $database['icon'] === 'database' ? 'selected' : '' ?>>データベース</option>
                                <option value="table" <?= $database['icon'] === 'table' ? 'selected' : '' ?>>テーブル</option>
                                <option value="list" <?= $database['icon'] === 'list' ? 'selected' : '' ?>>リスト</option>
                                <option value="tasks" <?= $database['icon'] === 'tasks' ? 'selected' : '' ?>>タスク</option>
                                <option value="calendar" <?= $database['icon'] === 'calendar' ? 'selected' : '' ?>>カレンダー</option>
                                <option value="users" <?= $database['icon'] === 'users' ? 'selected' : '' ?>>ユーザー</option>
                                <option value="building" <?= $database['icon'] === 'building' ? 'selected' : '' ?>>組織</option>
                                <option value="file" <?= $database['icon'] === 'file' ? 'selected' : '' ?>>ファイル</option>
                                <option value="folder" <?= $database['icon'] === 'folder' ? 'selected' : '' ?>>フォルダ</option>
                                <option value="project-diagram" <?= $database['icon'] === 'project-diagram' ? 'selected' : '' ?>>プロジェクト</option>
                                <option value="chart-bar" <?= $database['icon'] === 'chart-bar' ? 'selected' : '' ?>>グラフ</option>
                                <option value="clipboard" <?= $database['icon'] === 'clipboard' ? 'selected' : '' ?>>クリップボード</option>
                                <option value="sticky-note" <?= $database['icon'] === 'sticky-note' ? 'selected' : '' ?>>メモ</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="color" class="form-label">カラー</label>
                        <div class="input-group">
                            <input type="color" class="form-control form-control-color" id="color" name="color" value="<?= htmlspecialchars($database['color']) ?>">
                            <input type="text" class="form-control" id="color-text" value="<?= htmlspecialchars($database['color']) ?>" readonly>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="is_public" name="is_public" value="1" <?= $database['is_public'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_public">
                            全体に公開する
                        </label>
                    </div>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="<?= BASE_PATH ?>/webdatabase" class="btn btn-secondary">キャンセル</a>
                    <button type="submit" class="btn btn-primary">更新</button>
                </div>
            </form>
        </div>
    </div>
</div>
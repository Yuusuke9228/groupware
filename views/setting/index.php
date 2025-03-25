<!-- views/setting/index.php -->
<div class="container">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="h3 mb-2">システム設定</h1>
            <p class="text-muted">システムの基本的な設定を行います。</p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3">
            <!-- 設定メニュー -->
            <div class="card">
                <div class="card-header">設定メニュー</div>
                <div class="list-group list-group-flush">
                    <a href="<?= BASE_PATH ?>/settings" class="list-group-item list-group-item-action active">基本設定</a>
                    <a href="<?= BASE_PATH ?>/settings/smtp" class="list-group-item list-group-item-action">メール設定</a>
                    <a href="<?= BASE_PATH ?>/settings/notification" class="list-group-item list-group-item-action">通知設定</a>
                </div>
            </div>
        </div>

        <div class="col-md-9">
            <!-- 基本設定フォーム -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">基本設定</h5>
                </div>
                <div class="card-body">
                    <form id="settingsForm">
                        <div class="alert alert-success d-none" id="settingsSuccessAlert">
                            設定を保存しました。
                        </div>
                        <div class="alert alert-danger d-none" id="settingsErrorAlert"></div>

                        <!-- アプリケーション名 -->
                        <div class="mb-3">
                            <label for="app_name" class="form-label">アプリケーション名</label>
                            <input type="text" class="form-control" id="app_name" name="app_name" value="<?= htmlspecialchars($settings['app_name'] ?? 'GroupWare') ?>">
                            <div class="form-text">サイト全体に表示されるアプリケーション名です。</div>
                        </div>

                        <!-- 会社名 -->
                        <div class="mb-3">
                            <label for="company_name" class="form-label">会社名</label>
                            <input type="text" class="form-control" id="company_name" name="company_name" value="<?= htmlspecialchars($settings['company_name'] ?? '株式会社サンプル') ?>">
                            <div class="form-text">メールなどに表示される会社名です。</div>
                        </div>

                        <!-- タイムゾーン -->
                        <div class="mb-3">
                            <label for="timezone" class="form-label">タイムゾーン</label>
                            <select class="form-select" id="timezone" name="timezone">
                                <option value="Asia/Tokyo" <?= ($settings['timezone'] ?? 'Asia/Tokyo') === 'Asia/Tokyo' ? 'selected' : '' ?>>Asia/Tokyo (日本標準時)</option>
                                <option value="UTC" <?= ($settings['timezone'] ?? '') === 'UTC' ? 'selected' : '' ?>>UTC (世界協定時)</option>
                                <option value="America/New_York" <?= ($settings['timezone'] ?? '') === 'America/New_York' ? 'selected' : '' ?>>America/New_York (東部標準時)</option>
                                <option value="Europe/London" <?= ($settings['timezone'] ?? '') === 'Europe/London' ? 'selected' : '' ?>>Europe/London (イギリス時間)</option>
                            </select>
                            <div class="form-text">システム全体で使用するタイムゾーンです。</div>
                        </div>

                        <!-- 管理者メールアドレス -->
                        <div class="mb-3">
                            <label for="admin_email" class="form-label">管理者メールアドレス</label>
                            <input type="email" class="form-control" id="admin_email" name="admin_email" value="<?= htmlspecialchars($settings['admin_email'] ?? 'admin@example.com') ?>">
                            <div class="form-text">システム通知の送信先メールアドレスです。</div>
                        </div>

                        <!-- 日付形式 -->
                        <div class="mb-3">
                            <label for="date_format" class="form-label">日付形式</label>
                            <select class="form-select" id="date_format" name="date_format">
                                <option value="Y/m/d" <?= ($settings['date_format'] ?? 'Y/m/d') === 'Y/m/d' ? 'selected' : '' ?>>2023/01/31</option>
                                <option value="Y-m-d" <?= ($settings['date_format'] ?? '') === 'Y-m-d' ? 'selected' : '' ?>>2023-01-31</option>
                                <option value="d/m/Y" <?= ($settings['date_format'] ?? '') === 'd/m/Y' ? 'selected' : '' ?>>31/01/2023</option>
                                <option value="m/d/Y" <?= ($settings['date_format'] ?? '') === 'm/d/Y' ? 'selected' : '' ?>>01/31/2023</option>
                            </select>
                            <div class="form-text">日付の表示形式です。</div>
                        </div>

                        <!-- 時刻形式 -->
                        <div class="mb-3">
                            <label for="time_format" class="form-label">時刻形式</label>
                            <select class="form-select" id="time_format" name="time_format">
                                <option value="H:i" <?= ($settings['time_format'] ?? 'H:i') === 'H:i' ? 'selected' : '' ?>>24時間形式 (14:30)</option>
                                <option value="h:i A" <?= ($settings['time_format'] ?? '') === 'h:i A' ? 'selected' : '' ?>>12時間形式 (02:30 PM)</option>
                            </select>
                            <div class="form-text">時刻の表示形式です。</div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">設定を保存</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
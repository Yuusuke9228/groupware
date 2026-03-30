<!-- views/setting/index.php -->
<div class="container">
    <?php $isJaLocale = get_locale() === 'ja'; ?>
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="h3 mb-2"><?= htmlspecialchars(t('settings.title')) ?></h1>
            <p class="text-muted"><?= htmlspecialchars(tr_text('システムの基本的な設定を行います。', 'Configure core system settings.')) ?></p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3">
            <!-- <?= htmlspecialchars(t("settings.menu")) ?> -->
            <div class="card">
                <div class="card-header"><?= htmlspecialchars(t("settings.menu")) ?></div>
                <div class="list-group list-group-flush">
                    <a href="<?= BASE_PATH ?>/settings" class="list-group-item list-group-item-action active"><?= htmlspecialchars(t("settings.menu.basic")) ?></a>
                    <a href="<?= BASE_PATH ?>/settings/smtp" class="list-group-item list-group-item-action"><?= htmlspecialchars(t("settings.menu.smtp")) ?></a>
                    <a href="<?= BASE_PATH ?>/settings/notification" class="list-group-item list-group-item-action"><?= htmlspecialchars(t("settings.menu.notification")) ?></a>
                    <a href="<?= BASE_PATH ?>/settings/security" class="list-group-item list-group-item-action"><?= htmlspecialchars(t("settings.menu.security")) ?></a>
                    <a href="<?= BASE_PATH ?>/settings/security#backup-management" class="list-group-item list-group-item-action"><?= htmlspecialchars(t("settings.menu.backup")) ?></a>
                </div>
            </div>
        </div>

        <div class="col-md-9">
            <!-- <?= htmlspecialchars(t("settings.menu.basic")) ?>フォーム -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><?= htmlspecialchars(t("settings.menu.basic")) ?></h5>
                </div>
                <div class="card-body">
                    <form id="settingsForm">
                        <div class="alert alert-success d-none" id="settingsSuccessAlert">
                            <?= htmlspecialchars(tr_text('設定を保存しました。', 'Settings saved.')) ?>
                        </div>
                        <div class="alert alert-danger d-none" id="settingsErrorAlert"></div>

                        <!-- アプリケーション名 -->
                        <div class="mb-3">
                            <label for="app_name" class="form-label"><?= htmlspecialchars(tr_text('アプリケーション名', 'Application name')) ?></label>
                            <input type="text" class="form-control" id="app_name" name="app_name" value="<?= htmlspecialchars($settings['app_name'] ?? $appName) ?>">
                            <div class="form-text"><?= htmlspecialchars(tr_text('サイト全体に表示されるアプリケーション名です。', 'Application name shown across the system.')) ?></div>
                        </div>

                        <!-- 会社名 -->
                        <div class="mb-3">
                            <label for="company_name" class="form-label"><?= htmlspecialchars(tr_text('会社名', 'Company name')) ?></label>
                            <input type="text" class="form-control" id="company_name" name="company_name" value="<?= htmlspecialchars($settings['company_name'] ?? tr_text('株式会社サンプル', 'Sample Corporation')) ?>">
                            <div class="form-text"><?= htmlspecialchars(tr_text('メールなどに表示される会社名です。', 'Company name shown in emails and system outputs.')) ?></div>
                        </div>

                        <!-- タイムゾーン -->
                        <div class="mb-3">
                            <label for="timezone" class="form-label"><?= htmlspecialchars(tr_text('タイムゾーン', 'Time zone')) ?></label>
                            <select class="form-select" id="timezone" name="timezone">
                                <option value="Asia/Tokyo" <?= ($settings['timezone'] ?? 'Asia/Tokyo') === 'Asia/Tokyo' ? 'selected' : '' ?>><?= htmlspecialchars(tr_text('Asia/Tokyo (日本標準時)', 'Asia/Tokyo (Japan Standard Time)')) ?></option>
                                <option value="UTC" <?= ($settings['timezone'] ?? '') === 'UTC' ? 'selected' : '' ?>><?= htmlspecialchars(tr_text('UTC (世界協定時)', 'UTC (Coordinated Universal Time)')) ?></option>
                                <option value="America/New_York" <?= ($settings['timezone'] ?? '') === 'America/New_York' ? 'selected' : '' ?>><?= htmlspecialchars(tr_text('America/New_York (東部標準時)', 'America/New_York (Eastern Time)')) ?></option>
                                <option value="Europe/London" <?= ($settings['timezone'] ?? '') === 'Europe/London' ? 'selected' : '' ?>><?= htmlspecialchars(tr_text('Europe/London (イギリス時間)', 'Europe/London (UK Time)')) ?></option>
                            </select>
                            <div class="form-text"><?= htmlspecialchars(tr_text('システム全体で使用するタイムゾーンです。', 'Time zone used across the entire system.')) ?></div>
                        </div>

                        <!-- 管理者メールアドレス -->
                        <div class="mb-3">
                            <label for="admin_email" class="form-label"><?= htmlspecialchars(tr_text('管理者メールアドレス', 'Administrator email address')) ?></label>
                            <input type="email" class="form-control" id="admin_email" name="admin_email" value="<?= htmlspecialchars($settings['admin_email'] ?? 'admin@example.com') ?>">
                            <div class="form-text"><?= htmlspecialchars(tr_text('システム通知の送信先メールアドレスです。', 'Destination email address for system notifications.')) ?></div>
                        </div>

                        <!-- 日付形式 -->
                        <div class="mb-3">
                            <label for="date_format" class="form-label"><?= htmlspecialchars(tr_text('日付形式', 'Date format')) ?></label>
                            <select class="form-select" id="date_format" name="date_format">
                                <option value="Y/m/d" <?= ($settings['date_format'] ?? 'Y/m/d') === 'Y/m/d' ? 'selected' : '' ?>>2023/01/31</option>
                                <option value="Y-m-d" <?= ($settings['date_format'] ?? '') === 'Y-m-d' ? 'selected' : '' ?>>2023-01-31</option>
                                <option value="d/m/Y" <?= ($settings['date_format'] ?? '') === 'd/m/Y' ? 'selected' : '' ?>>31/01/2023</option>
                                <option value="m/d/Y" <?= ($settings['date_format'] ?? '') === 'm/d/Y' ? 'selected' : '' ?>>01/31/2023</option>
                            </select>
                            <div class="form-text"><?= htmlspecialchars(tr_text('日付の表示形式です。', 'Display format for dates.')) ?></div>
                        </div>

                        <!-- 時刻形式 -->
                        <div class="mb-3">
                            <label for="time_format" class="form-label"><?= htmlspecialchars(tr_text('時刻形式', 'Time format')) ?></label>
                            <select class="form-select" id="time_format" name="time_format">
                                <option value="H:i" <?= ($settings['time_format'] ?? 'H:i') === 'H:i' ? 'selected' : '' ?>><?= htmlspecialchars(tr_text('24時間形式 (14:30)', '24-hour format (14:30)')) ?></option>
                                <option value="h:i A" <?= ($settings['time_format'] ?? '') === 'h:i A' ? 'selected' : '' ?>><?= htmlspecialchars(tr_text('12時間形式 (02:30 PM)', '12-hour format (02:30 PM)')) ?></option>
                            </select>
                            <div class="form-text"><?= htmlspecialchars(tr_text('時刻の表示形式です。', 'Display format for time.')) ?></div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary"><?= htmlspecialchars(tr_text('設定を保存', 'Save settings')) ?></button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0"><?= htmlspecialchars(tr_text('デモデータ管理', 'Demo data management')) ?></h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-success d-none" id="demoDataSuccessAlert"></div>
                    <div class="alert alert-danger d-none" id="demoDataErrorAlert"></div>

                    <p class="mb-2">
                        <?= htmlspecialchars(tr_text('デモ環境を見栄えよく維持するための一括処理です。', 'Batch operations to keep the demo environment in presentable condition.')) ?>
                    </p>
                    <ul class="mb-3">
                        <li><strong><?= htmlspecialchars(tr_text('3年分更新', 'Refresh 3 years')) ?></strong>: <?= htmlspecialchars(tr_text('既存データを残したまま、本日から3年分のデモデータを補充します。', 'Add demo data for 3 years from today while keeping existing data.')) ?></li>
                        <li><strong><?= htmlspecialchars(tr_text('全再構築', 'Full rebuild')) ?></strong>: <?= htmlspecialchars(tr_text('業務データを削除し、デモデータに作り直します（破壊的）。', 'Delete business data and rebuild as demo data (destructive).')) ?></li>
                    </ul>

                    <div class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label for="demoDataYears" class="form-label"><?= htmlspecialchars(tr_text('生成年数', 'Years to generate')) ?></label>
                            <select id="demoDataYears" class="form-select">
                                <option value="1"><?= htmlspecialchars(tr_text('1年', '1 year')) ?></option>
                                <option value="2"><?= htmlspecialchars(tr_text('2年', '2 years')) ?></option>
                                <option value="3" selected><?= htmlspecialchars(tr_text('3年', '3 years')) ?></option>
                                <option value="4"><?= htmlspecialchars(tr_text('4年', '4 years')) ?></option>
                                <option value="5"><?= htmlspecialchars(tr_text('5年', '5 years')) ?></option>
                            </select>
                        </div>
                        <div class="col-md-9">
                            <div class="d-flex flex-wrap gap-2">
                                <button type="button" class="btn btn-outline-primary" id="btnRefreshDemoData">
                                    <?= htmlspecialchars(tr_text('本日から3年分を更新', 'Refresh from today for 3 years')) ?>
                                </button>
                                <button type="button" class="btn btn-danger" id="btnRebuildDemoData">
                                    <?= htmlspecialchars(tr_text('全データをデモ用に再構築', 'Rebuild all data for demo')) ?>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="small text-muted mt-3">
                        <?= htmlspecialchars(tr_text('定期運用は ', 'For scheduled operation, run ')) ?><code>php scripts/rebuild_demo_data.php --mode=rebuild --years=3</code><?= htmlspecialchars(tr_text(' をCRONで月1回実行してください。', ' via CRON once a month.')) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- views/setting/smtp.php -->
<div class="container">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="h3 mb-2">メール設定</h1>
            <p class="text-muted">システムから送信するメールの設定を行います。</p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3">
            <!-- 設定メニュー -->
            <div class="card">
                <div class="card-header">設定メニュー</div>
                <div class="list-group list-group-flush">
                    <a href="<?= BASE_PATH ?>/settings" class="list-group-item list-group-item-action">基本設定</a>
                    <a href="<?= BASE_PATH ?>/settings/smtp" class="list-group-item list-group-item-action active">メール設定</a>
                    <a href="<?= BASE_PATH ?>/settings/notification" class="list-group-item list-group-item-action">通知設定</a>
                </div>
            </div>
        </div>

        <div class="col-md-9">
            <!-- SMTP設定フォーム -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">SMTP設定</h5>
                </div>
                <div class="card-body">
                    <form id="smtpSettingsForm">
                        <div class="alert alert-success d-none" id="smtpSuccessAlert">
                            設定を保存しました。
                        </div>
                        <div class="alert alert-danger d-none" id="smtpErrorAlert"></div>

                        <!-- 送信元メールアドレス -->
                        <div class="mb-3">
                            <label for="notification_email" class="form-label">送信元メールアドレス</label>
                            <input type="email" class="form-control" id="notification_email" name="notification_email" value="<?= htmlspecialchars($settings['notification_email'] ?? 'notification@example.com') ?>">
                            <div class="form-text">通知メールの送信元アドレスです。</div>
                        </div>

                        <!-- SMTPサーバー -->
                        <div class="mb-3">
                            <label for="smtp_host" class="form-label">SMTPサーバー</label>
                            <input type="text" class="form-control" id="smtp_host" name="smtp_host" value="<?= htmlspecialchars($settings['smtp_host'] ?? 'smtp.example.com') ?>">
                            <div class="form-text">SMTPサーバーのホスト名またはIPアドレスです。</div>
                        </div>

                        <!-- SMTPポート -->
                        <div class="mb-3">
                            <label for="smtp_port" class="form-label">SMTPポート</label>
                            <input type="number" class="form-control" id="smtp_port" name="smtp_port" value="<?= htmlspecialchars($settings['smtp_port'] ?? '587') ?>">
                            <div class="form-text">SMTPサーバーのポート番号です。一般的には587（TLS）または465（SSL）を使用します。</div>
                        </div>

                        <!-- SMTP暗号化 -->
                        <div class="mb-3">
                            <label for="smtp_secure" class="form-label">暗号化</label>
                            <select class="form-select" id="smtp_secure" name="smtp_secure">
                                <option value="tls" <?= ($settings['smtp_secure'] ?? 'tls') === 'tls' ? 'selected' : '' ?>>TLS</option>
                                <option value="ssl" <?= ($settings['smtp_secure'] ?? '') === 'ssl' ? 'selected' : '' ?>>SSL</option>
                                <option value="" <?= ($settings['smtp_secure'] ?? '') === '' ? 'selected' : '' ?>>なし</option>
                            </select>
                            <div class="form-text">SMTP接続の暗号化方式です。</div>
                        </div>

                        <!-- SMTPユーザー名 -->
                        <div class="mb-3">
                            <label for="smtp_username" class="form-label">SMTPユーザー名</label>
                            <input type="text" class="form-control" id="smtp_username" name="smtp_username" value="<?= htmlspecialchars($settings['smtp_username'] ?? '') ?>">
                            <div class="form-text">SMTPサーバーの認証ユーザー名です。</div>
                        </div>

                        <!-- SMTPパスワード -->
                        <div class="mb-3">
                            <label for="smtp_password" class="form-label">SMTPパスワード</label>
                            <input type="password" class="form-control" id="smtp_password" name="smtp_password" value="<?= htmlspecialchars($settings['smtp_password'] ?? '') ?>">
                            <div class="form-text">SMTPサーバーの認証パスワードです。</div>
                        </div>

                        <!-- メール送信テスト -->
                        <div class="mb-3">
                            <label for="test_email" class="form-label">テスト送信先</label>
                            <div class="input-group">
                                <input type="email" class="form-control" id="test_email" name="test_email" value="<?= htmlspecialchars($this->auth->user()['email'] ?? '') ?>" placeholder="テスト送信先メールアドレス">
                                <button type="button" class="btn btn-outline-primary" id="testEmailBtn">テスト送信</button>
                            </div>
                            <div class="form-text">設定をテストするためのメールを送信します。</div>
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
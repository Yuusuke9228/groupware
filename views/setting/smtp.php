<!-- views/setting/smtp.php -->
<div class="container">
    <?php $isJaLocale = get_locale() === 'ja'; ?>
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="h3 mb-2"><?= htmlspecialchars(t("settings.menu.smtp")) ?></h1>
            <p class="text-muted"><?= $isJaLocale ? 'システムから送信するメールの設定を行います。' : 'Configure outgoing mail settings for system notifications.' ?></p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3">
            <!-- <?= htmlspecialchars(t("settings.menu")) ?> -->
            <div class="card">
                <div class="card-header"><?= htmlspecialchars(t("settings.menu")) ?></div>
                <div class="list-group list-group-flush">
                    <a href="<?= BASE_PATH ?>/settings" class="list-group-item list-group-item-action"><?= htmlspecialchars(t("settings.menu.basic")) ?></a>
                    <a href="<?= BASE_PATH ?>/settings/smtp" class="list-group-item list-group-item-action active"><?= htmlspecialchars(t("settings.menu.smtp")) ?></a>
                    <a href="<?= BASE_PATH ?>/settings/notification" class="list-group-item list-group-item-action"><?= htmlspecialchars(t("settings.menu.notification")) ?></a>
                    <a href="<?= BASE_PATH ?>/settings/security" class="list-group-item list-group-item-action"><?= htmlspecialchars(t("settings.menu.security")) ?></a>
                    <a href="<?= BASE_PATH ?>/settings/security#backup-management" class="list-group-item list-group-item-action"><?= htmlspecialchars(t("settings.menu.backup")) ?></a>
                </div>
            </div>
        </div>

        <div class="col-md-9">
            <!-- <?= htmlspecialchars(t("settings.menu.smtp")) ?>フォーム -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">メール送信設定</h5>
                </div>
                <div class="card-body">
                    <form id="smtpSettingsForm">
                        <div class="alert alert-success d-none" id="smtpSuccessAlert">
                            設定を保存しました。
                        </div>
                        <div class="alert alert-danger d-none" id="smtpErrorAlert"></div>

                        <div class="mb-3">
                            <label for="mail_transport" class="form-label">送信方式</label>
                            <select class="form-select" id="mail_transport" name="mail_transport">
                                <option value="smtp" <?= ($settings['mail_transport'] ?? 'smtp') === 'smtp' ? 'selected' : '' ?>>SMTP</option>
                                <option value="sendmail" <?= ($settings['mail_transport'] ?? '') === 'sendmail' ? 'selected' : '' ?>>sendmail</option>
                                <option value="mail" <?= ($settings['mail_transport'] ?? '') === 'mail' ? 'selected' : '' ?>>PHP mail()</option>
                            </select>
                            <div class="form-text">環境に合わせて送信方式を選択してください。</div>
                        </div>

                        <!-- 送信元メールアドレス -->
                        <div class="mb-3">
                            <label for="notification_email" class="form-label">送信元メールアドレス</label>
                            <input type="email" class="form-control" id="notification_email" name="notification_email" value="<?= htmlspecialchars($settings['notification_email'] ?? 'notification@example.com') ?>">
                            <div class="form-text">通知メールの送信元アドレスです。</div>
                        </div>

                        <div class="mb-3">
                            <label for="mail_from_name" class="form-label">送信者名</label>
                            <input type="text" class="form-control" id="mail_from_name" name="mail_from_name" value="<?= htmlspecialchars($settings['mail_from_name'] ?? ($settings['app_name'] ?? $appName)) ?>">
                            <div class="form-text">メールに表示する送信者名です。</div>
                        </div>

                        <div class="mb-3">
                            <label for="mail_reply_to_email" class="form-label">返信先メールアドレス（任意）</label>
                            <input type="email" class="form-control" id="mail_reply_to_email" name="mail_reply_to_email" value="<?= htmlspecialchars($settings['mail_reply_to_email'] ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label for="app_url" class="form-label">アプリURL</label>
                            <input type="text" class="form-control" id="app_url" name="app_url" value="<?= htmlspecialchars($settings['app_url'] ?? '') ?>" placeholder="例: https://groupware.example.com/groupware/public">
                            <div class="form-text">通知メール内リンク生成に使用します。未設定時はアクセスURLを使用します。</div>
                        </div>

                        <div id="smtpFields">
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

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="smtp_auth" name="smtp_auth" <?= ($settings['smtp_auth'] ?? '1') == '1' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="smtp_auth">SMTP認証を使用する</label>
                        </div>

                        <!-- SMTPユーザー名 -->
                        <div class="mb-3 smtp-auth-field">
                            <label for="smtp_username" class="form-label">SMTPユーザー名</label>
                            <input type="text" class="form-control" id="smtp_username" name="smtp_username" value="<?= htmlspecialchars($settings['smtp_username'] ?? '') ?>">
                            <div class="form-text">SMTPサーバーの認証ユーザー名です。</div>
                        </div>

                        <!-- SMTPパスワード -->
                        <div class="mb-3 smtp-auth-field">
                            <label for="smtp_password" class="form-label">SMTPパスワード</label>
                            <input type="password" class="form-control" id="smtp_password" name="smtp_password" value="<?= htmlspecialchars($settings['smtp_password'] ?? '') ?>">
                            <div class="form-text">SMTPサーバーの認証パスワードです。</div>
                        </div>

                        <div class="mb-3">
                            <label for="smtp_timeout" class="form-label">SMTPタイムアウト（秒）</label>
                            <input type="number" class="form-control" id="smtp_timeout" name="smtp_timeout" min="1" value="<?= htmlspecialchars($settings['smtp_timeout'] ?? '30') ?>">
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="smtp_allow_self_signed" name="smtp_allow_self_signed" <?= ($settings['smtp_allow_self_signed'] ?? '0') == '1' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="smtp_allow_self_signed">自己署名証明書を許可する（検証無効）</label>
                        </div>
                        </div>

                        <div id="sendmailFields" class="d-none">
                            <div class="mb-3">
                                <label for="sendmail_path" class="form-label">sendmailコマンド（任意）</label>
                                <input type="text" class="form-control" id="sendmail_path" name="sendmail_path" value="<?= htmlspecialchars($settings['sendmail_path'] ?? '') ?>" placeholder="/usr/sbin/sendmail -bs">
                                <div class="form-text">未設定時はPHPMailerの既定値を使用します。</div>
                            </div>
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

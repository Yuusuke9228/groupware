<!-- views/setting/smtp.php -->
<div class="container">
    <?php $isJaLocale = get_locale() === 'ja'; ?>
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="h3 mb-2"><?= htmlspecialchars(t("settings.menu.smtp")) ?></h1>
            <p class="text-muted"><?= htmlspecialchars(tr_text('システムから送信するメールの設定を行います。', 'Configure outgoing mail settings for system notifications.')) ?></p>
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
                    <h5 class="card-title mb-0"><?= htmlspecialchars(tr_text('メール送信設定', 'Mail delivery settings')) ?></h5>
                </div>
                <div class="card-body">
                    <form id="smtpSettingsForm">
                        <div class="alert alert-success d-none" id="smtpSuccessAlert">
                            <?= htmlspecialchars(tr_text('設定を保存しました。', 'Settings saved.')) ?>
                        </div>
                        <div class="alert alert-danger d-none" id="smtpErrorAlert"></div>

                        <div class="mb-3">
                            <label for="mail_transport" class="form-label"><?= htmlspecialchars(tr_text('送信方式', 'Delivery method')) ?></label>
                            <select class="form-select" id="mail_transport" name="mail_transport">
                                <option value="smtp" <?= ($settings['mail_transport'] ?? 'smtp') === 'smtp' ? 'selected' : '' ?>>SMTP</option>
                                <option value="sendmail" <?= ($settings['mail_transport'] ?? '') === 'sendmail' ? 'selected' : '' ?>>sendmail</option>
                                <option value="mail" <?= ($settings['mail_transport'] ?? '') === 'mail' ? 'selected' : '' ?>>PHP mail()</option>
                            </select>
                            <div class="form-text"><?= htmlspecialchars(tr_text('環境に合わせて送信方式を選択してください。', 'Choose a delivery method that matches your environment.')) ?></div>
                        </div>

                        <!-- 送信元メールアドレス -->
                        <div class="mb-3">
                            <label for="notification_email" class="form-label"><?= htmlspecialchars(tr_text('送信元メールアドレス', 'From email address')) ?></label>
                            <input type="email" class="form-control" id="notification_email" name="notification_email" value="<?= htmlspecialchars($settings['notification_email'] ?? 'notification@example.com') ?>">
                            <div class="form-text"><?= htmlspecialchars(tr_text('通知メールの送信元アドレスです。', 'From address used for notification emails.')) ?></div>
                        </div>

                        <div class="mb-3">
                            <label for="mail_from_name" class="form-label"><?= htmlspecialchars(tr_text('送信者名', 'Sender name')) ?></label>
                            <input type="text" class="form-control" id="mail_from_name" name="mail_from_name" value="<?= htmlspecialchars($settings['mail_from_name'] ?? ($settings['app_name'] ?? $appName)) ?>">
                            <div class="form-text"><?= htmlspecialchars(tr_text('メールに表示する送信者名です。', 'Sender name shown in outgoing emails.')) ?></div>
                        </div>

                        <div class="mb-3">
                            <label for="mail_reply_to_email" class="form-label"><?= htmlspecialchars(tr_text('返信先メールアドレス（任意）', 'Reply-to email address (optional)')) ?></label>
                            <input type="email" class="form-control" id="mail_reply_to_email" name="mail_reply_to_email" value="<?= htmlspecialchars($settings['mail_reply_to_email'] ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label for="app_url" class="form-label"><?= htmlspecialchars(tr_text('アプリURL', 'Application URL')) ?></label>
                            <input type="text" class="form-control" id="app_url" name="app_url" value="<?= htmlspecialchars($settings['app_url'] ?? '') ?>" placeholder="<?= htmlspecialchars(tr_text('例: https://groupware.example.com/groupware/public', 'Example: https://groupware.example.com/groupware/public')) ?>">
                            <div class="form-text"><?= htmlspecialchars(tr_text('通知メール内リンク生成に使用します。未設定時はアクセスURLを使用します。', 'Used to generate links in notification emails. If empty, current access URL is used.')) ?></div>
                        </div>

                        <div id="smtpFields">
                        <!-- SMTPサーバー -->
                        <div class="mb-3">
                            <label for="smtp_host" class="form-label"><?= htmlspecialchars(tr_text('SMTPサーバー', 'SMTP server')) ?></label>
                            <input type="text" class="form-control" id="smtp_host" name="smtp_host" value="<?= htmlspecialchars($settings['smtp_host'] ?? 'smtp.example.com') ?>">
                            <div class="form-text"><?= htmlspecialchars(tr_text('SMTPサーバーのホスト名またはIPアドレスです。', 'SMTP server hostname or IP address.')) ?></div>
                        </div>

                        <!-- SMTPポート -->
                        <div class="mb-3">
                            <label for="smtp_port" class="form-label"><?= htmlspecialchars(tr_text('SMTPポート', 'SMTP port')) ?></label>
                            <input type="number" class="form-control" id="smtp_port" name="smtp_port" value="<?= htmlspecialchars($settings['smtp_port'] ?? '587') ?>">
                            <div class="form-text"><?= htmlspecialchars(tr_text('SMTPサーバーのポート番号です。一般的には587（TLS）または465（SSL）を使用します。', 'SMTP server port. Usually 587 (TLS) or 465 (SSL).')) ?></div>
                        </div>

                        <!-- SMTP暗号化 -->
                        <div class="mb-3">
                            <label for="smtp_secure" class="form-label"><?= htmlspecialchars(tr_text('暗号化', 'Encryption')) ?></label>
                            <select class="form-select" id="smtp_secure" name="smtp_secure">
                                <option value="tls" <?= ($settings['smtp_secure'] ?? 'tls') === 'tls' ? 'selected' : '' ?>>TLS</option>
                                <option value="ssl" <?= ($settings['smtp_secure'] ?? '') === 'ssl' ? 'selected' : '' ?>>SSL</option>
                                <option value="" <?= ($settings['smtp_secure'] ?? '') === '' ? 'selected' : '' ?>><?= htmlspecialchars(tr_text('なし', 'None')) ?></option>
                            </select>
                            <div class="form-text"><?= htmlspecialchars(tr_text('SMTP接続の暗号化方式です。', 'Encryption mode for SMTP connection.')) ?></div>
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="smtp_auth" name="smtp_auth" <?= ($settings['smtp_auth'] ?? '1') == '1' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="smtp_auth"><?= htmlspecialchars(tr_text('SMTP認証を使用する', 'Use SMTP authentication')) ?></label>
                        </div>

                        <!-- SMTPユーザー名 -->
                        <div class="mb-3 smtp-auth-field">
                            <label for="smtp_username" class="form-label"><?= htmlspecialchars(tr_text('SMTPユーザー名', 'SMTP username')) ?></label>
                            <input type="text" class="form-control" id="smtp_username" name="smtp_username" value="<?= htmlspecialchars($settings['smtp_username'] ?? '') ?>">
                            <div class="form-text"><?= htmlspecialchars(tr_text('SMTPサーバーの認証ユーザー名です。', 'Authentication username for SMTP server.')) ?></div>
                        </div>

                        <!-- SMTPパスワード -->
                        <div class="mb-3 smtp-auth-field">
                            <label for="smtp_password" class="form-label"><?= htmlspecialchars(tr_text('SMTPパスワード', 'SMTP password')) ?></label>
                            <input type="password" class="form-control" id="smtp_password" name="smtp_password" value="<?= htmlspecialchars($settings['smtp_password'] ?? '') ?>">
                            <div class="form-text"><?= htmlspecialchars(tr_text('SMTPサーバーの認証パスワードです。', 'Authentication password for SMTP server.')) ?></div>
                        </div>

                        <div class="mb-3">
                            <label for="smtp_timeout" class="form-label"><?= htmlspecialchars(tr_text('SMTPタイムアウト（秒）', 'SMTP timeout (seconds)')) ?></label>
                            <input type="number" class="form-control" id="smtp_timeout" name="smtp_timeout" min="1" value="<?= htmlspecialchars($settings['smtp_timeout'] ?? '30') ?>">
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="smtp_allow_self_signed" name="smtp_allow_self_signed" <?= ($settings['smtp_allow_self_signed'] ?? '0') == '1' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="smtp_allow_self_signed"><?= htmlspecialchars(tr_text('自己署名証明書を許可する（検証無効）', 'Allow self-signed certificates (disable verification)')) ?></label>
                        </div>
                        </div>

                        <div id="sendmailFields" class="d-none">
                            <div class="mb-3">
                                <label for="sendmail_path" class="form-label"><?= htmlspecialchars(tr_text('sendmailコマンド（任意）', 'sendmail command (optional)')) ?></label>
                                <input type="text" class="form-control" id="sendmail_path" name="sendmail_path" value="<?= htmlspecialchars($settings['sendmail_path'] ?? '') ?>" placeholder="/usr/sbin/sendmail -bs">
                                <div class="form-text"><?= htmlspecialchars(tr_text('未設定時はPHPMailerの既定値を使用します。', 'If empty, PHPMailer default is used.')) ?></div>
                            </div>
                        </div>

                        <!-- メール送信テスト -->
                        <div class="mb-3">
                            <label for="test_email" class="form-label"><?= htmlspecialchars(tr_text('テスト送信先', 'Test destination')) ?></label>
                            <div class="input-group">
                                <input type="email" class="form-control" id="test_email" name="test_email" value="<?= htmlspecialchars($this->auth->user()['email'] ?? '') ?>" placeholder="<?= htmlspecialchars(tr_text('テスト送信先メールアドレス', 'Email address to send a test message')) ?>">
                                <button type="button" class="btn btn-outline-primary" id="testEmailBtn"><?= htmlspecialchars(tr_text('テスト送信', 'Send test')) ?></button>
                            </div>
                            <div class="form-text"><?= htmlspecialchars(tr_text('設定をテストするためのメールを送信します。', 'Sends a test email to validate current configuration.')) ?></div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary"><?= htmlspecialchars(tr_text('設定を保存', 'Save settings')) ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

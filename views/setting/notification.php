<!-- views/setting/notification.php -->
<div class="container">
    <?php $isJaLocale = get_locale() === 'ja'; ?>
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="h3 mb-2"><?= htmlspecialchars(t("settings.menu.notification")) ?></h1>
            <p class="text-muted"><?= htmlspecialchars(tr_text('システム通知の設定を行います。', 'Configure in-app and mail notification behavior.')) ?></p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3">
            <!-- <?= htmlspecialchars(t("settings.menu")) ?> -->
            <div class="card">
                <div class="card-header"><?= htmlspecialchars(t("settings.menu")) ?></div>
                <div class="list-group list-group-flush">
                    <a href="<?= BASE_PATH ?>/settings" class="list-group-item list-group-item-action"><?= htmlspecialchars(t("settings.menu.basic")) ?></a>
                    <a href="<?= BASE_PATH ?>/settings/smtp" class="list-group-item list-group-item-action"><?= htmlspecialchars(t("settings.menu.smtp")) ?></a>
                    <a href="<?= BASE_PATH ?>/settings/notification" class="list-group-item list-group-item-action active"><?= htmlspecialchars(t("settings.menu.notification")) ?></a>
                    <a href="<?= BASE_PATH ?>/settings/security" class="list-group-item list-group-item-action"><?= htmlspecialchars(t("settings.menu.security")) ?></a>
                    <a href="<?= BASE_PATH ?>/settings/security#backup-management" class="list-group-item list-group-item-action"><?= htmlspecialchars(t("settings.menu.backup")) ?></a>
                </div>
            </div>
        </div>

        <div class="col-md-9">
            <!-- <?= htmlspecialchars(t("settings.menu.notification")) ?>フォーム -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><?= htmlspecialchars(t("settings.menu.notification")) ?></h5>
                </div>
                <div class="card-body">
                    <form id="notificationSettingsForm">
                        <div class="alert alert-success d-none" id="notificationSuccessAlert">
                            <?= htmlspecialchars(tr_text('設定を保存しました。', 'Settings saved.')) ?>
                        </div>
                        <div class="alert alert-danger d-none" id="notificationErrorAlert"></div>

                        <!-- 通知機能の有効化 -->
                        <div class="mb-3 form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="notification_enabled" name="notification_enabled" <?= ($settings['notification_enabled'] ?? '1') == '1' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="notification_enabled"><?= htmlspecialchars(tr_text('通知機能を有効にする', 'Enable notifications')) ?></label>
                            <div class="form-text"><?= htmlspecialchars(tr_text('チェックを外すと、すべての通知が無効になります。', 'Disable to turn off all notifications.')) ?></div>
                        </div>

                        <hr>
                        <h5><?= htmlspecialchars(tr_text('通知タイプ', 'Notification types')) ?></h5>
                        <p class="text-muted"><?= htmlspecialchars(tr_text('有効にする通知タイプを選択してください。', 'Select notification types to enable.')) ?></p>

                        <!-- スケジュール通知 -->
                        <div class="mb-3 form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="schedule_notification" name="schedule_notification" <?= ($settings['schedule_notification'] ?? '1') == '1' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="schedule_notification"><?= htmlspecialchars(tr_text('スケジュール通知', 'Schedule notifications')) ?></label>
                            <div class="form-text"><?= htmlspecialchars(tr_text('スケジュールの作成・更新・削除時の通知を有効にします。', 'Enable notifications for schedule create/update/delete events.')) ?></div>
                        </div>

                        <!-- ワークフロー通知 -->
                        <div class="mb-3 form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="workflow_notification" name="workflow_notification" <?= ($settings['workflow_notification'] ?? '1') == '1' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="workflow_notification"><?= htmlspecialchars(tr_text('ワークフロー通知', 'Workflow notifications')) ?></label>
                            <div class="form-text"><?= htmlspecialchars(tr_text('ワークフローの進行・承認・却下時の通知を有効にします。', 'Enable notifications for workflow progress, approval, and rejection events.')) ?></div>
                        </div>

                        <!-- メッセージ通知 -->
                        <div class="mb-3 form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="message_notification" name="message_notification" <?= ($settings['message_notification'] ?? '1') == '1' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="message_notification"><?= htmlspecialchars(tr_text('メッセージ通知', 'Message notifications')) ?></label>
                            <div class="form-text"><?= htmlspecialchars(tr_text('新着メッセージの通知を有効にします。', 'Enable notifications for new messages.')) ?></div>
                        </div>

                        <hr>
                        <h5><?= htmlspecialchars(tr_text('メール通知設定', 'Email notification settings')) ?></h5>
                        <p class="text-muted"><?= htmlspecialchars(tr_text('システム通知と連動してメールを送信する設定です。', 'Configure how email delivery works with system notifications.')) ?></p>

                        <!-- メール通知の有効化 -->
                        <div class="mb-3 form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="email_notification" name="email_notification" <?= ($settings['email_notification'] ?? '1') == '1' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="email_notification"><?= htmlspecialchars(tr_text('メール通知を有効にする', 'Enable email notifications')) ?></label>
                            <div class="form-text"><?= htmlspecialchars(tr_text('チェックを外すと、メールによる通知が無効になります。', 'Disable to stop email notifications.')) ?></div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary"><?= htmlspecialchars(tr_text('設定を保存', 'Save settings')) ?></button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- メール通知スケジュール設定 -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0"><?= htmlspecialchars(tr_text('メール通知スケジュール', 'Email notification schedule')) ?></h5>
                </div>
                <div class="card-body">
                    <p><?= htmlspecialchars(tr_text('メール通知の送信処理は、以下のコマンドをcronに登録して定期的に実行する必要があります。', 'Register the following command in cron and run it periodically to process outgoing email notifications.')) ?></p>

                    <div class="alert alert-info">
                        <code>* * * * * php <?= realpath(__DIR__ . '/../../scripts/process_email_queue.php') ?></code>
                    </div>

                    <p><?= htmlspecialchars(tr_text('このコマンドは1分ごとに実行され、未送信のメール通知を処理します。', 'This command runs every minute and processes pending email notifications.')) ?></p>

                    <div class="mt-3">
                        <button type="button" class="btn btn-outline-primary" id="processCronManually"><?= htmlspecialchars(tr_text('手動でメール送信処理を実行', 'Run email queue processing manually')) ?></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- views/setting/notification.php -->
<div class="container">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="h3 mb-2">通知設定</h1>
            <p class="text-muted">システム通知の設定を行います。</p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3">
            <!-- 設定メニュー -->
            <div class="card">
                <div class="card-header">設定メニュー</div>
                <div class="list-group list-group-flush">
                    <a href="<?= BASE_PATH ?>/settings" class="list-group-item list-group-item-action">基本設定</a>
                    <a href="<?= BASE_PATH ?>/settings/smtp" class="list-group-item list-group-item-action">メール設定</a>
                    <a href="<?= BASE_PATH ?>/settings/notification" class="list-group-item list-group-item-action active">通知設定</a>
                </div>
            </div>
        </div>

        <div class="col-md-9">
            <!-- 通知設定フォーム -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">通知設定</h5>
                </div>
                <div class="card-body">
                    <form id="notificationSettingsForm">
                        <div class="alert alert-success d-none" id="notificationSuccessAlert">
                            設定を保存しました。
                        </div>
                        <div class="alert alert-danger d-none" id="notificationErrorAlert"></div>

                        <!-- 通知機能の有効化 -->
                        <div class="mb-3 form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="notification_enabled" name="notification_enabled" <?= ($settings['notification_enabled'] ?? '1') == '1' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="notification_enabled">通知機能を有効にする</label>
                            <div class="form-text">チェックを外すと、すべての通知が無効になります。</div>
                        </div>

                        <hr>
                        <h5>通知タイプ</h5>
                        <p class="text-muted">有効にする通知タイプを選択してください。</p>

                        <!-- スケジュール通知 -->
                        <div class="mb-3 form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="schedule_notification" name="schedule_notification" <?= ($settings['schedule_notification'] ?? '1') == '1' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="schedule_notification">スケジュール通知</label>
                            <div class="form-text">スケジュールの作成・更新・削除時の通知を有効にします。</div>
                        </div>

                        <!-- ワークフロー通知 -->
                        <div class="mb-3 form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="workflow_notification" name="workflow_notification" <?= ($settings['workflow_notification'] ?? '1') == '1' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="workflow_notification">ワークフロー通知</label>
                            <div class="form-text">ワークフローの進行・承認・却下時の通知を有効にします。</div>
                        </div>

                        <!-- メッセージ通知 -->
                        <div class="mb-3 form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="message_notification" name="message_notification" <?= ($settings['message_notification'] ?? '1') == '1' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="message_notification">メッセージ通知</label>
                            <div class="form-text">新着メッセージの通知を有効にします。</div>
                        </div>

                        <hr>
                        <h5>メール通知設定</h5>
                        <p class="text-muted">システム通知と連動してメールを送信する設定です。</p>

                        <!-- メール通知の有効化 -->
                        <div class="mb-3 form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="email_notification" name="email_notification" <?= ($settings['email_notification'] ?? '1') == '1' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="email_notification">メール通知を有効にする</label>
                            <div class="form-text">チェックを外すと、メールによる通知が無効になります。</div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">設定を保存</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- メール通知スケジュール設定 -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">メール通知スケジュール</h5>
                </div>
                <div class="card-body">
                    <p>メール通知の送信処理は、以下のコマンドをcronに登録して定期的に実行する必要があります。</p>

                    <div class="alert alert-info">
                        <code>* * * * * php <?= realpath(__DIR__ . '/../../scripts/process_email_queue.php') ?></code>
                    </div>

                    <p>このコマンドは1分ごとに実行され、未送信のメール通知を処理します。</p>

                    <div class="mt-3">
                        <button type="button" class="btn btn-outline-primary" id="processCronManually">手動でメール送信処理を実行</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
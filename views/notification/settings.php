<!-- views/notification/settings.php -->
<div class="container">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="h3 mb-2">個人通知設定</h1>
            <p class="text-muted">あなた宛の通知設定をカスタマイズできます。</p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8 mx-auto">
            <!-- 通知設定フォーム -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">通知設定</h5>
                </div>
                <div class="card-body">
                    <form id="userNotificationSettingsForm">
                        <div class="alert alert-success d-none" id="settingsSuccessAlert">
                            設定を保存しました。
                        </div>
                        <div class="alert alert-danger d-none" id="settingsErrorAlert"></div>

                        <h5>通知タイプ</h5>
                        <p class="text-muted">受け取りたい通知タイプを選択してください。</p>

                        <!-- スケジュール通知 -->
                        <div class="mb-3 form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="notify_schedule" name="notify_schedule" <?= ($settings['notify_schedule'] ?? 1) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="notify_schedule">スケジュール通知</label>
                            <div class="form-text">新しいスケジュールの追加・更新・削除時に通知を受け取ります。</div>
                        </div>

                        <!-- ワークフロー通知 -->
                        <div class="mb-3 form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="notify_workflow" name="notify_workflow" <?= ($settings['notify_workflow'] ?? 1) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="notify_workflow">ワークフロー通知</label>
                            <div class="form-text">ワークフローの進行・承認依頼・完了時に通知を受け取ります。</div>
                        </div>

                        <!-- メッセージ通知 -->
                        <div class="mb-3 form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="notify_message" name="notify_message" <?= ($settings['notify_message'] ?? 1) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="notify_message">メッセージ通知</label>
                            <div class="form-text">新着メッセージの受信時に通知を受け取ります。</div>
                        </div>

                        <hr>

                        <!-- メール通知 -->
                        <div class="mb-3 form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="email_notify" name="email_notify" <?= ($settings['email_notify'] ?? 1) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="email_notify">メール通知</label>
                            <div class="form-text">通知と連動してメールも受け取ります。</div>
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
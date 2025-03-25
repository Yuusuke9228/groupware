<?php
// views/message/compose.php
// メッセージ作成画面

// 現在のユーザー情報
$currentUser = $this->auth->user();

// フォームのモード（新規/返信/転送）
$isReply = isset($isReply) && $isReply;
$isForward = isset($isForward) && $isForward;

// 初期値
$subject = $subject ?? '';
$body = $body ?? '';
$initialRecipients = $initialRecipients ?? [];
$parentId = $parentId ?? null;
$originalAttachments = $originalAttachments ?? [];
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/sidebar.php'; ?>

        <div class="col-md-9 col-lg-10">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><?php echo htmlspecialchars($title); ?></h5>
                </div>
                <div class="card-body">
                    <form id="message-form" action="<?php echo BASE_PATH; ?>/api/messages/send" method="post" enctype="multipart/form-data">
                        <?php if ($parentId): ?>
                            <input type="hidden" name="parent_id" value="<?php echo $parentId; ?>">
                        <?php endif; ?>

                        <!-- 宛先（ユーザー） -->
                        <div class="mb-3">
                            <label for="recipients" class="form-label">宛先：</label>
                            <select id="recipients" name="recipients[]" class="form-control select2-users" multiple>
                                <?php foreach ($users as $user): ?>
                                    <?php if ($user['id'] != $currentUser['id']): ?>
                                        <option value="<?php echo $user['id']; ?>" <?php echo in_array($user['id'], $initialRecipients) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($user['display_name']); ?> (<?php echo htmlspecialchars($user['username']); ?>)
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>

                        <!-- 宛先（組織） -->
                        <div class="mb-3">
                            <label for="organizations" class="form-label">組織：</label>
                            <select id="organizations" name="organizations[]" class="form-control select2-organizations" multiple>
                                <?php foreach ($organizations as $organization): ?>
                                    <option value="<?php echo $organization['id']; ?>">
                                        <?php echo htmlspecialchars($organization['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted">選択した組織のすべてのメンバーにメッセージが送信されます。</small>
                        </div>

                        <!-- 件名 -->
                        <div class="mb-3">
                            <label for="subject" class="form-label">件名：</label>
                            <input type="text" class="form-control" id="subject" name="subject" required value="<?php echo htmlspecialchars($subject); ?>">
                            <div class="invalid-feedback"></div>
                        </div>

                        <!-- 本文 -->
                        <div class="mb-3">
                            <label for="body" class="form-label">本文：</label>
                            <textarea class="form-control" id="body" name="body" rows="10" required><?php echo htmlspecialchars($body); ?></textarea>
                            <div class="invalid-feedback"></div>
                        </div>

                        <!-- 添付ファイル -->
                        <div class="mb-3">
                            <label for="attachments" class="form-label">添付ファイル：</label>
                            <input type="file" class="form-control" id="attachments" name="attachments[]" multiple>
                            <small class="form-text text-muted">複数のファイルを添付できます。</small>
                        </div>

                        <!-- 転送時の元のファイル添付 -->
                        <?php if (!empty($originalAttachments)): ?>
                            <div class="mb-3">
                                <label class="form-label">元メッセージの添付ファイル：</label>
                                <div class="list-group">
                                    <?php foreach ($originalAttachments as $attachment): ?>
                                        <div class="list-group-item">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="attachment-<?php echo $attachment['id']; ?>" name="original_attachments[]" value="<?php echo $attachment['id']; ?>" checked>
                                                <label class="form-check-label" for="attachment-<?php echo $attachment['id']; ?>">
                                                    <?php echo htmlspecialchars($attachment['file_name']); ?>
                                                    <span class="text-muted">(<?php echo number_format($attachment['file_size'] / 1024, 1); ?> KB)</span>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- 送信ボタン -->
                        <div class="text-end">
                            <a href="<?php echo BASE_PATH; ?>/messages/inbox" class="btn btn-secondary me-2">キャンセル</a>
                            <button type="submit" class="btn btn-primary">送信</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // ページロード時の処理
    document.addEventListener('DOMContentLoaded', function() {
        // Select2の初期化
        $('.select2-users').select2({
            theme: 'bootstrap-5',
            placeholder: 'ユーザーを選択...',
            allowClear: true
        });

        $('.select2-organizations').select2({
            theme: 'bootstrap-5',
            placeholder: '組織を選択...',
            allowClear: true
        });

        // フォーム送信
        $('#message-form').off('submit').on('submit', function(e) {
            e.preventDefault();

            const form = $(this);
            const formData = new FormData(form[0]);

            // ユーザーか組織のいずれかが選択されているか確認
            const recipients = $('#recipients').val();
            const organizations = $('#organizations').val();

            if ((!recipients || recipients.length === 0) && (!organizations || organizations.length === 0)) {
                toastr.error('ユーザーまたは組織を少なくとも1つ選択してください');
                return false;
            }
            // 添付ファイルのデバッグ出力（開発時のみ）
            const files = $('#attachments')[0].files;
            console.log('Files selected:', files.length);
            for (let i = 0; i < files.length; i++) {
                console.log('File:', files[i].name, 'Size:', files[i].size, 'Type:', files[i].type);
            }

            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                beforeSend: function() {
                    // 送信ボタンを無効化
                    form.find('button[type="submit"]').prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> 送信中...');
                    // エラー表示をクリア
                    form.find('.is-invalid').removeClass('is-invalid');
                    form.find('.invalid-feedback').text('');
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message || 'メッセージを送信しました');

                        // リダイレクト指定があればリダイレクト
                        if (response.redirect) {
                            window.location.href = response.redirect;
                        }
                    } else {
                        toastr.error(response.error || 'エラーが発生しました');

                        // バリデーションエラーがある場合は表示
                        if (response.validation) {
                            for (const field in response.validation) {
                                const errorMsg = response.validation[field];
                                const input = form.find('[name="' + field + '"]');
                                input.addClass('is-invalid');
                                input.next('.invalid-feedback').text(errorMsg);
                            }
                        }
                    }
                },
                error: function(xhr, status, error) {
                    toastr.error('エラーが発生しました');
                    console.error(error);
                },
                complete: function() {
                    // 送信ボタンを有効化
                    form.find('button[type="submit"]').prop('disabled', false).text('送信');
                }
            });
           return false; // 確実にフォーム送信を防ぐ
        });
    });
</script>
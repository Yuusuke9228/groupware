<?php
// views/message/view.php
// メッセージ詳細・スレッド表示画面

// 現在のユーザー情報
$currentUser = $this->auth->user();
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/sidebar.php'; ?>

        <div class="col-md-9 col-lg-10">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><?php echo htmlspecialchars($message['subject']); ?></h5>
                    <div>
                        <a href="<?php echo BASE_PATH; ?>/messages/inbox" class="btn btn-sm btn-outline-secondary me-1">
                            <i class="fas fa-arrow-left"></i> 戻る
                        </a>
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                操作
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <?php if ($isRecipient): ?>
                                    <li>
                                        <a class="dropdown-item" href="<?php echo BASE_PATH; ?>/messages/reply/<?php echo $message['id']; ?>">
                                            <i class="fas fa-reply me-2"></i>返信
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="<?php echo BASE_PATH; ?>/messages/reply-all/<?php echo $message['id']; ?>">
                                            <i class="fas fa-reply-all me-2"></i>全員に返信
                                        </a>
                                    </li>
                                <?php endif; ?>
                                <li>
                                    <a class="dropdown-item" href="<?php echo BASE_PATH; ?>/messages/forward/<?php echo $message['id']; ?>">
                                        <i class="fas fa-share me-2"></i>転送
                                    </a>
                                </li>
                                <?php if ($isRecipient): ?>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <?php if ($message['is_read'] == 1): ?>
                                        <li>
                                            <a class="dropdown-item btn-mark-as-unread" href="#" data-message-id="<?php echo $message['id']; ?>">
                                                <i class="fas fa-circle me-2"></i>未読にする
                                            </a>
                                        </li>
                                    <?php else: ?>
                                        <li>
                                            <a class="dropdown-item btn-mark-as-read" href="#" data-message-id="<?php echo $message['id']; ?>">
                                                <i class="fas fa-check-circle me-2"></i>既読にする
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    <li>
                                        <a class="dropdown-item btn-toggle-star" href="#" data-message-id="<?php echo $message['id']; ?>" data-starred="<?php echo $message['is_starred'] ?? 0; ?>">
                                            <?php if ($message['is_starred'] ?? false): ?>
                                                <i class="fas fa-star me-2 text-warning"></i>スターを外す
                                            <?php else: ?>
                                                <i class="far fa-star me-2"></i>スターを付ける
                                            <?php endif; ?>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item btn-delete-message" href="#" data-message-id="<?php echo $message['id']; ?>">
                                            <i class="fas fa-trash me-2"></i>削除
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- スレッド表示 -->
                    <?php if (!empty($threadMessages)): ?>
                        <div class="thread-container">
                            <?php foreach ($threadMessages as $threadMessage): ?>
                                <div class="message-thread-item card mb-3 <?php echo ($threadMessage['id'] == $message['id']) ? 'border-primary' : ''; ?>">
                                    <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                                        <div>
                                            <span class="fw-bold"><?php echo htmlspecialchars($threadMessage['sender_name']); ?></span>
                                            <span class="text-muted ms-2"><?php echo date('Y/m/d H:i', strtotime($threadMessage['created_at'])); ?></span>
                                        </div>
                                        <?php if ($threadMessage['id'] != $message['id']): ?>
                                            <a href="<?php echo BASE_PATH; ?>/messages/view/<?php echo $threadMessage['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-body">
                                        <?php if ($threadMessage['id'] == $message['id']): ?>
                                            <h6 class="card-title">宛先：
                                                <?php
                                                $recipientNames = array_map(function ($r) {
                                                    return htmlspecialchars($r['display_name']);
                                                }, $recipients);
                                                echo implode(', ', $recipientNames);
                                                ?>

                                                <?php if (!empty($organizations)): ?>
                                                    <br>組織：
                                                    <?php
                                                    $orgNames = array_map(function ($o) {
                                                        return htmlspecialchars($o['name']);
                                                    }, $organizations);
                                                    echo implode(', ', $orgNames);
                                                    ?>
                                                <?php endif; ?>
                                            </h6>

                                            <!-- 添付ファイル表示 -->
                                            <?php if (!empty($attachments)): ?>
                                                <div class="attachments mb-3">
                                                    <div class="card">
                                                        <div class="card-header py-2">
                                                            <h6 class="mb-0"><i class="fas fa-paperclip me-2"></i>添付ファイル (<?php echo count($attachments); ?>)</h6>
                                                        </div>
                                                        <div class="list-group list-group-flush">
                                                            <?php foreach ($attachments as $attachment): ?>
                                                                <a href="<?php echo BASE_PATH . '/' . $attachment['file_path']; ?>" class="list-group-item list-group-item-action" target="_blank">
                                                                    <?php
                                                                    // ファイルタイプに応じたアイコンを表示
                                                                    $fileIcon = 'fa-file';
                                                                    if (strpos($attachment['mime_type'], 'image/') === 0) {
                                                                        $fileIcon = 'fa-file-image';
                                                                    } elseif (strpos($attachment['mime_type'], 'application/pdf') === 0) {
                                                                        $fileIcon = 'fa-file-pdf';
                                                                    } elseif (strpos($attachment['mime_type'], 'text/') === 0) {
                                                                        $fileIcon = 'fa-file-alt';
                                                                    } elseif (
                                                                        strpos($attachment['mime_type'], 'application/msword') === 0 ||
                                                                        strpos($attachment['mime_type'], 'application/vnd.openxmlformats-officedocument.wordprocessingml') === 0
                                                                    ) {
                                                                        $fileIcon = 'fa-file-word';
                                                                    } elseif (
                                                                        strpos($attachment['mime_type'], 'application/vnd.ms-excel') === 0 ||
                                                                        strpos($attachment['mime_type'], 'application/vnd.openxmlformats-officedocument.spreadsheetml') === 0
                                                                    ) {
                                                                        $fileIcon = 'fa-file-excel';
                                                                    }
                                                                    ?>
                                                                    <i class="fas <?php echo $fileIcon; ?> me-2"></i>
                                                                    <?php echo htmlspecialchars($attachment['file_name']); ?>
                                                                    <span class="text-muted">(<?php echo number_format($attachment['file_size'] / 1024, 1); ?> KB)</span>
                                                                </a>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        <?php endif; ?>

                                        <!-- メッセージ本文 -->
                                        <div class="message-body">
                                            <?php echo nl2br(htmlspecialchars($threadMessage['body'])); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <!-- 単一メッセージ表示 -->
                        <div class="single-message-container">
                            <div class="message-header mb-3">
                                <div class="row">
                                    <div class="col-md-2 fw-bold">送信者：</div>
                                    <div class="col-md-10"><?php echo htmlspecialchars($message['sender_name']); ?></div>
                                </div>
                                <div class="row">
                                    <div class="col-md-2 fw-bold">宛先：</div>
                                    <div class="col-md-10">
                                        <?php
                                        $recipientNames = array_map(function ($r) {
                                            return htmlspecialchars($r['display_name']);
                                        }, $recipients);
                                        echo implode(', ', $recipientNames);
                                        ?>
                                    </div>
                                </div>
                                <?php if (!empty($organizations)): ?>
                                    <div class="row">
                                        <div class="col-md-2 fw-bold">組織：</div>
                                        <div class="col-md-10">
                                            <?php
                                            $orgNames = array_map(function ($o) {
                                                return htmlspecialchars($o['name']);
                                            }, $organizations);
                                            echo implode(', ', $orgNames);
                                            ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <div class="row">
                                    <div class="col-md-2 fw-bold">日時：</div>
                                    <div class="col-md-10"><?php echo date('Y年m月d日 H:i', strtotime($message['created_at'])); ?></div>
                                </div>
                            </div>

                            <!-- 添付ファイル表示 -->
                            <?php if (!empty($attachments)): ?>
                                <div class="attachments mb-3">
                                    <div class="card">
                                        <div class="card-header py-2">
                                            <h6 class="mb-0"><i class="fas fa-paperclip me-2"></i>添付ファイル (<?php echo count($attachments); ?>)</h6>
                                        </div>
                                        <div class="list-group list-group-flush">
                                            <?php foreach ($attachments as $attachment): ?>
                                                <a href="<?php echo BASE_PATH . '/' . $attachment['file_path']; ?>" class="list-group-item list-group-item-action" target="_blank">
                                                    <?php
                                                    // ファイルタイプに応じたアイコンを表示
                                                    $fileIcon = 'fa-file';
                                                    if (strpos($attachment['mime_type'], 'image/') === 0) {
                                                        $fileIcon = 'fa-file-image';
                                                    } elseif (strpos($attachment['mime_type'], 'application/pdf') === 0) {
                                                        $fileIcon = 'fa-file-pdf';
                                                    } elseif (strpos($attachment['mime_type'], 'text/') === 0) {
                                                        $fileIcon = 'fa-file-alt';
                                                    } elseif (
                                                        strpos($attachment['mime_type'], 'application/msword') === 0 ||
                                                        strpos($attachment['mime_type'], 'application/vnd.openxmlformats-officedocument.wordprocessingml') === 0
                                                    ) {
                                                        $fileIcon = 'fa-file-word';
                                                    } elseif (
                                                        strpos($attachment['mime_type'], 'application/vnd.ms-excel') === 0 ||
                                                        strpos($attachment['mime_type'], 'application/vnd.openxmlformats-officedocument.spreadsheetml') === 0
                                                    ) {
                                                        $fileIcon = 'fa-file-excel';
                                                    }
                                                    ?>
                                                    <i class="fas <?php echo $fileIcon; ?> me-2"></i>
                                                    <?php echo htmlspecialchars($attachment['file_name']); ?>
                                                    <span class="text-muted">(<?php echo number_format($attachment['file_size'] / 1024, 1); ?> KB)</span>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="message-body p-3 bg-light rounded">
                                <?php echo nl2br(htmlspecialchars($message['body'])); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer">
                    <div class="d-flex justify-content-between">
                        <div>
                            <?php if ($isRecipient): ?>
                                <button type="button" class="btn btn-sm btn-toggle-star" data-message-id="<?php echo $message['id']; ?>" data-starred="<?php echo $message['is_starred'] ?? 0; ?>">
                                    <?php if ($message['is_starred'] ?? false): ?>
                                        <i class="fas fa-star text-warning"></i> スター解除
                                    <?php else: ?>
                                        <i class="far fa-star"></i> スターを付ける
                                    <?php endif; ?>
                                </button>
                            <?php endif; ?>
                        </div>
                        <div>
                            <?php if ($isRecipient): ?>
                                <a href="<?php echo BASE_PATH; ?>/messages/reply/<?php echo $message['id']; ?>" class="btn btn-primary btn-sm me-1">
                                    <i class="fas fa-reply"></i> 返信
                                </a>
                                <a href="<?php echo BASE_PATH; ?>/messages/reply-all/<?php echo $message['id']; ?>" class="btn btn-secondary btn-sm me-1">
                                    <i class="fas fa-reply-all"></i> 全員に返信
                                </a>
                            <?php endif; ?>
                            <a href="<?php echo BASE_PATH; ?>/messages/forward/<?php echo $message['id']; ?>" class="btn btn-info btn-sm">
                                <i class="fas fa-share"></i> 転送
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // ページロード時の処理
    document.addEventListener('DOMContentLoaded', function() {
        // スターを付ける/外す
        $('.btn-toggle-star').on('click', function(e) {
            e.preventDefault();

            const messageId = $(this).data('message-id');
            const isStarred = $(this).data('starred') == 1;
            const newStarred = !isStarred;
            const button = $(this);

            $.ajax({
                url: BASE_PATH + '/api/messages/' + messageId + '/star',
                type: 'POST',
                data: JSON.stringify({
                    starred: newStarred
                }),
                contentType: 'application/json',
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message || (newStarred ? 'スターを付けました' : 'スターを外しました'));

                        // ボタン表示を更新
                        button.data('starred', newStarred ? 1 : 0);

                        if (button.hasClass('dropdown-item')) {
                            // ドロップダウン内のボタン
                            if (newStarred) {
                                button.html('<i class="fas fa-star me-2 text-warning"></i>スターを外す');
                            } else {
                                button.html('<i class="far fa-star me-2"></i>スターを付ける');
                            }
                        } else {
                            // フッターのボタン
                            if (newStarred) {
                                button.html('<i class="fas fa-star text-warning"></i> スター解除');
                            } else {
                                button.html('<i class="far fa-star"></i> スターを付ける');
                            }
                        }
                    } else {
                        toastr.error(response.error || 'エラーが発生しました');
                    }
                },
                error: function(xhr, status, error) {
                    toastr.error('エラーが発生しました');
                    console.error(error);
                }
            });
        });

        // 既読にする
        $('.btn-mark-as-read').on('click', function(e) {
            e.preventDefault();

            const messageId = $(this).data('message-id');

            $.ajax({
                url: BASE_PATH + '/api/messages/' + messageId + '/read',
                type: 'POST',
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message || 'メッセージを既読にしました');
                    } else {
                        toastr.error(response.error || 'エラーが発生しました');
                    }
                },
                error: function(xhr, status, error) {
                    toastr.error('エラーが発生しました');
                    console.error(error);
                }
            });
        });

        // 未読にする
        $('.btn-mark-as-unread').on('click', function(e) {
            e.preventDefault();

            const messageId = $(this).data('message-id');

            $.ajax({
                url: BASE_PATH + '/api/messages/' + messageId + '/unread',
                type: 'POST',
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message || 'メッセージを未読にしました');
                    } else {
                        toastr.error(response.error || 'エラーが発生しました');
                    }
                },
                error: function(xhr, status, error) {
                    toastr.error('エラーが発生しました');
                    console.error(error);
                }
            });
        });

        // 削除する
        $('.btn-delete-message').on('click', function(e) {
            e.preventDefault();

            if (!confirm('このメッセージを削除してもよろしいですか？')) {
                return;
            }

            const messageId = $(this).data('message-id');

            $.ajax({
                url: BASE_PATH + '/api/messages/' + messageId,
                type: 'DELETE',
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message || 'メッセージを削除しました');
                        // 受信箱に戻る
                        window.location.href = BASE_PATH + '/messages/inbox';
                    } else {
                        toastr.error(response.error || 'エラーが発生しました');
                    }
                },
                error: function(xhr, status, error) {
                    toastr.error('エラーが発生しました');
                    console.error(error);
                }
            });
        });
    });
</script>
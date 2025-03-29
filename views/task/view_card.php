<?php
// views/task/view_card.php
?>
<div class="container-fluid mt-3">
    <div class="row mb-3">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_PATH; ?>/task">タスク管理</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo BASE_PATH; ?>/task/board/<?php echo $board['id']; ?>"><?php echo htmlspecialchars($board['name']); ?></a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($card['title']); ?></li>
                </ol>
            </nav>
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h4><?php echo htmlspecialchars($card['title']); ?></h4>
                </div>
                <div>
                    <?php if ($canEdit): ?>
                        <a href="<?php echo BASE_PATH; ?>/task/edit-card/<?php echo $card['id']; ?>" class="btn btn-primary">
                            <i class="fas fa-edit"></i> 編集
                        </a>
                    <?php endif; ?>
                    <a href="<?php echo BASE_PATH; ?>/task/board/<?php echo $board['id']; ?>" class="btn btn-outline-secondary ms-2">
                        <i class="fas fa-arrow-left"></i> ボードに戻る
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <?php if (!empty($card['labels'])): ?>
                        <div class="mb-3">
                            <?php foreach ($card['labels'] as $label): ?>
                                <span class="badge me-1 mb-1" style="background-color: <?php echo $label['color']; ?>">
                                    <?php echo htmlspecialchars($label['name']); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="mb-4">
                        <h5>説明</h5>
                        <?php if (!empty($card['description'])): ?>
                            <div class="card-text">
                                <?php echo nl2br(htmlspecialchars($card['description'])); ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">説明はありません</p>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($card['checklists'])): ?>
                        <div class="mb-4">
                            <h5>チェックリスト</h5>
                            <?php foreach ($card['checklists'] as $checklist): ?>
                                <div class="card mb-3">
                                    <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                                        <h6 class="mb-0"><?php echo htmlspecialchars($checklist['title']); ?></h6>
                                        <span class="badge bg-info">
                                            <?php
                                            $completedCount = 0;
                                            $totalCount = count($checklist['items']);
                                            foreach ($checklist['items'] as $item) {
                                                if ($item['is_checked']) $completedCount++;
                                            }
                                            $percentage = $totalCount > 0 ? round(($completedCount / $totalCount) * 100) : 0;
                                            echo $completedCount . '/' . $totalCount . ' - ' . $percentage . '%';
                                            ?>
                                        </span>
                                    </div>
                                    <div class="card-body py-2">
                                        <div class="progress mb-3" style="height: 8px;">
                                            <div class="progress-bar" role="progressbar" style="width: <?php echo $percentage; ?>%"
                                                aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <ul class="list-group list-group-flush">
                                            <?php foreach ($checklist['items'] as $item): ?>
                                                <li class="list-group-item px-0 py-2 border-0 d-flex align-items-center">
                                                    <div class="form-check">
                                                        <input class="form-check-input checklist-item" type="checkbox"
                                                            <?php echo $item['is_checked'] ? 'checked' : ''; ?>
                                                            data-item-id="<?php echo $item['id']; ?>"
                                                            id="item-<?php echo $item['id']; ?>"
                                                            <?php echo $canEdit ? '' : 'disabled'; ?>>
                                                        <label class="form-check-label <?php echo $item['is_checked'] ? 'text-decoration-line-through text-muted' : ''; ?>"
                                                            for="item-<?php echo $item['id']; ?>">
                                                            <?php echo htmlspecialchars($item['content']); ?>
                                                        </label>
                                                    </div>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($card['attachments'])): ?>
                        <div class="mb-4">
                            <h5>添付ファイル</h5>
                            <div class="list-group">
                                <?php foreach ($card['attachments'] as $attachment): ?>
                                    <a href="<?php echo BASE_PATH; ?>/uploads/tasks/<?php echo $attachment['file_path']; ?>" class="list-group-item list-group-item-action" target="_blank">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($attachment['file_name']); ?></h6>
                                            <small><?php echo $this->formatFileSize($attachment['file_size']); ?></small>
                                        </div>
                                        <small class="text-muted">
                                            アップロード: <?php echo date('Y/m/d H:i', strtotime($attachment['created_at'])); ?>
                                            by <?php echo htmlspecialchars($attachment['uploader_name'] ?? '不明'); ?>
                                        </small>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="mb-4">
                        <h5>コメント</h5>
                        <?php if ($canEdit): ?>
                            <div class="mb-3">
                                <div class="input-group">
                                    <textarea class="form-control" id="commentText" rows="2" placeholder="コメントを入力..."></textarea>
                                    <button class="btn btn-primary" type="button" id="addCommentBtn" data-card-id="<?php echo $card['id']; ?>">
                                        <i class="fas fa-paper-plane"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div id="commentsList">
                            <?php if (empty($card['comments'])): ?>
                                <div class="text-center text-muted py-3">コメントはありません</div>
                            <?php else: ?>
                                <?php foreach ($card['comments'] as $comment): ?>
                                    <div class="card mb-3">
                                        <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                                            <div class="d-flex align-items-center">
                                                <div class="avatar me-2"><?php echo mb_substr($comment['display_name'], 0, 1); ?></div>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($comment['display_name']); ?></strong>
                                                    <span class="text-muted ms-2 small">
                                                        <?php echo date('Y/m/d H:i', strtotime($comment['created_at'])); ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <?php if ($comment['user_id'] == $auth->id() && $canEdit): ?>
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-link text-secondary" type="button" data-bs-toggle="dropdown">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li><a class="dropdown-item edit-comment" href="#" data-id="<?php echo $comment['id']; ?>">
                                                                <i class="fas fa-edit me-2"></i> 編集
                                                            </a></li>
                                                        <li><a class="dropdown-item text-danger delete-comment" href="#" data-id="<?php echo $comment['id']; ?>">
                                                                <i class="fas fa-trash me-2"></i> 削除
                                                            </a></li>
                                                    </ul>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="card-body py-2">
                                            <div class="comment-content-<?php echo $comment['id']; ?>">
                                                <?php echo nl2br(htmlspecialchars($comment['comment'])); ?>
                                            </div>
                                            <?php if ($comment['updated_at'] != $comment['created_at']): ?>
                                                <small class="text-muted">編集済み: <?php echo date('Y/m/d H:i', strtotime($comment['updated_at'])); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">ステータス</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <p class="mb-1"><strong>リスト:</strong></p>
                        <?php
                        foreach ($lists as $list) {
                            if ($list['id'] == $card['list_id']) {
                                echo '<span class="badge bg-primary">' . htmlspecialchars($list['name']) . '</span>';
                                break;
                            }
                        }
                        ?>
                    </div>

                    <div class="mb-3">
                        <p class="mb-1"><strong>ステータス:</strong></p>
                        <?php
                        $statusColors = [
                            'not_started' => 'secondary',
                            'in_progress' => 'primary',
                            'completed' => 'success',
                            'deferred' => 'warning'
                        ];
                        $statusNames = [
                            'not_started' => '未対応',
                            'in_progress' => '処理中',
                            'completed' => '完了',
                            'deferred' => '保留'
                        ];
                        $statusColor = $statusColors[$card['status']] ?? 'info';
                        $statusName = $statusNames[$card['status']] ?? $card['status'];
                        ?>
                        <span class="badge bg-<?php echo $statusColor; ?>"><?php echo $statusName; ?></span>
                    </div>

                    <div class="mb-3">
                        <p class="mb-1"><strong>優先度:</strong></p>
                        <?php
                        $priorityColors = [
                            'highest' => 'danger',
                            'high' => 'warning',
                            'normal' => 'primary',
                            'low' => 'info',
                            'lowest' => 'secondary'
                        ];
                        $priorityNames = [
                            'highest' => '最高',
                            'high' => '高',
                            'normal' => '通常',
                            'low' => '低',
                            'lowest' => '最低'
                        ];
                        $priorityColor = $priorityColors[$card['priority']] ?? 'primary';
                        $priorityName = $priorityNames[$card['priority']] ?? $card['priority'];
                        ?>
                        <span class="badge bg-<?php echo $priorityColor; ?>"><?php echo $priorityName; ?></span>
                    </div>

                    <div class="mb-3">
                        <p class="mb-1"><strong>期限日:</strong></p>
                        <?php
                        if (!empty($card['due_date'])) {
                            $dueDate = new DateTime($card['due_date']);
                            $today = new DateTime();
                            $isDue = $dueDate < $today && $card['status'] != 'completed';

                            if ($isDue) {
                                echo '<span class="text-danger"><i class="fas fa-exclamation-circle"></i> ';
                                echo $dueDate->format('Y年m月d日');
                                echo ' (期限切れ)</span>';
                            } else {
                                echo $dueDate->format('Y年m月d日');
                            }
                        } else {
                            echo '<span class="text-muted">設定なし</span>';
                        }
                        ?>
                    </div>

                    <div class="mb-4">
                        <p class="mb-1"><strong>進捗率:</strong></p>
                        <div class="progress mb-1" style="height: 10px;">
                            <div class="progress-bar bg-<?php echo $card['status'] == 'completed' ? 'success' : $statusColor; ?>"
                                role="progressbar" style="width: <?php echo $card['progress']; ?>%"
                                aria-valuenow="<?php echo $card['progress']; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <small><?php echo $card['progress']; ?>%</small>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">担当者</h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($card['assignees'])): ?>
                        <div class="p-3 text-center text-muted">担当者がいません</div>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($card['assignees'] as $assignee): ?>
                                <li class="list-group-item d-flex align-items-center">
                                    <div class="avatar me-3"><?php echo mb_substr($assignee['display_name'], 0, 1); ?></div>
                                    <div class="flex-grow-1">
                                        <div><?php echo htmlspecialchars($assignee['display_name']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($assignee['email']); ?></small>
                                    </div>
                                    <?php if ($assignee['user_id'] == $auth->id()): ?>
                                        <span class="badge bg-info">あなた</span>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0">タスク情報</h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">作成者</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($card['creator_name'] ?? '不明'); ?></dd>

                        <dt class="col-sm-4">作成日</dt>
                        <dd class="col-sm-8"><?php echo date('Y年m月d日 H:i', strtotime($card['created_at'])); ?></dd>

                        <dt class="col-sm-4">更新日</dt>
                        <dd class="col-sm-8"><?php echo date('Y年m月d日 H:i', strtotime($card['updated_at'])); ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- コメント編集モーダル -->
<div class="modal fade" id="editCommentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">コメント編集</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editCommentForm">
                    <input type="hidden" id="editCommentId" name="comment_id">
                    <div class="mb-3">
                        <textarea class="form-control" id="editCommentText" name="comment" rows="5" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                <button type="button" class="btn btn-primary" id="updateCommentBtn">更新</button>
            </div>
        </div>
    </div>
</div>

<!-- 削除確認モーダル -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteConfirmTitle">削除の確認</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="deleteConfirmBody">
                <!-- 動的に内容が変わる -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">削除</button>
            </div>
        </div>
    </div>
</div>

<!-- jQuery と必要なスクリプトを確実に読み込む -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // ここでjQueryが読み込まれているか確認
        if (typeof jQuery === 'undefined') {
            console.error('jQuery が読み込まれていません！');
            // jQueryをロード
            var script = document.createElement('script');
            script.src = 'https://code.jquery.com/jquery-3.6.0.min.js';
            script.onload = initializeScripts;
            document.head.appendChild(script);
        } else {
            initializeScripts();
        }

        function initializeScripts() {
            // チェックリスト項目のチェック状態変更
            $('.checklist-item').on('change', function() {
                const itemId = $(this).data('item-id');
                const isChecked = $(this).prop('checked');

                // ラベルのスタイルを変更
                const label = $(`label[for="item-${itemId}"]`);
                if (isChecked) {
                    label.addClass('text-decoration-line-through text-muted');
                } else {
                    label.removeClass('text-decoration-line-through text-muted');
                }

                // API呼び出し
                $.ajax({
                    url: BASE_PATH + `/api/task/checklist-items/${itemId}`,
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        is_checked: isChecked
                    }),
                    success: function(response) {
                        if (!response.success) {
                            // エラー時は元に戻す
                            $(`#item-${itemId}`).prop('checked', !isChecked);
                            if (isChecked) {
                                label.removeClass('text-decoration-line-through text-muted');
                            } else {
                                label.addClass('text-decoration-line-through text-muted');
                            }
                            toastr.error(response.error || 'チェックリスト項目の更新に失敗しました');
                        }
                    },
                    error: function() {
                        // エラー時は元に戻す
                        $(`#item-${itemId}`).prop('checked', !isChecked);
                        if (isChecked) {
                            label.removeClass('text-decoration-line-through text-muted');
                        } else {
                            label.addClass('text-decoration-line-through text-muted');
                        }
                        toastr.error('サーバーとの通信に失敗しました');
                    }
                });
            });

            // コメント追加
            $('#addCommentBtn').on('click', function() {
                const cardId = $(this).data('card-id');
                const comment = $('#commentText').val().trim();

                if (!comment) {
                    toastr.warning('コメントを入力してください');
                    return;
                }

                $.ajax({
                    url: BASE_PATH + `/api/task/cards/${cardId}/comments`,
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        comment: comment
                    }),
                    success: function(response) {
                        if (response.success) {
                            // コメント入力欄をクリア
                            $('#commentText').val('');

                            // 通知
                            toastr.success('コメントを追加しました');

                            // ページをリロード
                            setTimeout(function() {
                                window.location.reload();
                            }, 1000);
                        } else {
                            toastr.error(response.error || 'コメントの追加に失敗しました');
                        }
                    },
                    error: function() {
                        toastr.error('サーバーとの通信に失敗しました');
                    }
                });
            });

            // コメント編集ボタンがクリックされたとき
            $('.edit-comment').on('click', function(e) {
                e.preventDefault();

                const commentId = $(this).data('id');
                const commentContent = $(`.comment-content-${commentId}`).text().trim();

                $('#editCommentId').val(commentId);
                $('#editCommentText').val(commentContent);

                $('#editCommentModal').modal('show');
            });

            // コメント更新
            $('#updateCommentBtn').on('click', function() {
                const commentId = $('#editCommentId').val();
                const comment = $('#editCommentText').val().trim();

                if (!comment) {
                    toastr.warning('コメントを入力してください');
                    return;
                }

                $.ajax({
                    url: BASE_PATH + `/api/task/comments/${commentId}`,
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        comment: comment
                    }),
                    success: function(response) {
                        if (response.success) {
                            // モーダルを閉じる
                            $('#editCommentModal').modal('hide');

                            // 通知
                            toastr.success('コメントを更新しました');

                            // ページをリロード
                            setTimeout(function() {
                                window.location.reload();
                            }, 1000);
                        } else {
                            toastr.error(response.error || 'コメントの更新に失敗しました');
                        }
                    },
                    error: function() {
                        toastr.error('サーバーとの通信に失敗しました');
                    }
                });
            });

            // コメント削除ボタンがクリックされたとき
            $('.delete-comment').on('click', function(e) {
                e.preventDefault();

                const commentId = $(this).data('id');

                $('#deleteConfirmTitle').text('コメント削除の確認');
                $('#deleteConfirmBody').html(`
                <p>このコメントを削除しますか？</p>
                <p>この操作は取り消せません。</p>
            `);

                $('#confirmDelete').off('click').on('click', function() {
                    $.ajax({
                        url: BASE_PATH + `/api/task/comments/${commentId}`,
                        type: 'DELETE',
                        success: function(response) {
                            if (response.success) {
                                // モーダルを閉じる
                                $('#deleteConfirmModal').modal('hide');

                                // 通知
                                toastr.success('コメントを削除しました');

                                // ページをリロード
                                setTimeout(function() {
                                    window.location.reload();
                                }, 1000);
                            } else {
                                toastr.error(response.error || 'コメントの削除に失敗しました');
                                $('#deleteConfirmModal').modal('hide');
                            }
                        },
                        error: function() {
                            toastr.error('サーバーとの通信に失敗しました');
                            $('#deleteConfirmModal').modal('hide');
                        }
                    });
                });

                $('#deleteConfirmModal').modal('show');
            });
        }
    });
</script>
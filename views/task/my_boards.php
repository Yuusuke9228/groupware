<div class="container-fluid mt-3">
    <div class="row mb-3">
        <div class="col-md-8">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_PATH; ?>/task">タスク管理</a></li>
                    <li class="breadcrumb-item active" aria-current="page">個人タスクボード</li>
                </ol>
            </nav>
            <h4 class="mb-3">個人タスクボード</h4>
        </div>
        <div class="col-md-4 text-md-end">
            <a href="<?php echo BASE_PATH; ?>/task/create-board" class="btn btn-primary">
                <i class="fas fa-plus-circle"></i> 新規ボード作成
            </a>
        </div>
    </div>

    <div class="row">
        <?php
        // 個人用ボードのみをフィルタリング
        $personalBoards = array_filter($boards, function ($board) {
            return $board['owner_type'] === 'user';
        });

        if (empty($personalBoards)):
        ?>
            <div class="col-12">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> 個人用タスクボードがありません。右上の「新規ボード作成」ボタンから作成できます。
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($personalBoards as $board): ?>
                <div class="col-md-4 col-sm-6 mb-4">
                    <div class="card board-card shadow-sm h-100">
                        <div class="card-body">
                            <a href="<?php echo BASE_PATH; ?>/task/board/<?php echo $board['id']; ?>" class="text-decoration-none text-dark">
                                <div class="d-flex flex-column align-items-center">
                                    <div class="board-icon mb-2">
                                        <i class="fas fa-clipboard-list" style="color: <?php echo htmlspecialchars($board['background_color']); ?>"></i>
                                    </div>
                                    <h5 class="card-title text-center"><?php echo htmlspecialchars($board['name']); ?></h5>
                                    <?php if ($board['description']): ?>
                                        <p class="card-text text-center small text-muted"><?php echo htmlspecialchars(mb_substr($board['description'], 0, 50)) . (mb_strlen($board['description']) > 50 ? '...' : ''); ?></p>
                                    <?php endif; ?>
                                </div>
                            </a>
                        </div>
                        <div class="card-footer bg-transparent d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                <i class="fas fa-calendar-alt me-1"></i>
                                <?php echo date('Y/m/d', strtotime($board['created_at'])); ?>
                            </small>
                            <div class="btn-group">
                                <a href="<?php echo BASE_PATH; ?>/task/edit-board/<?php echo $board['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-danger delete-board" data-id="<?php echo $board['id']; ?>" data-name="<?php echo htmlspecialchars($board['name']); ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- 削除確認モーダル -->
<div class="modal fade" id="deleteBoardModal" tabindex="-1" aria-labelledby="deleteBoardModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteBoardModalLabel">ボード削除の確認</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>ボード「<span id="boardNameToDelete"></span>」を削除しますか？</p>
                <p class="text-danger">この操作は取り消せません。ボード内のすべてのタスクも削除されます。</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBoard">削除</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // jQueryが利用可能かチェック
        if (typeof jQuery === 'undefined') {
            console.error('jQuery is not loaded! Adding jQuery from CDN...');

            // jQueryを動的に読み込む
            var script = document.createElement('script');
            script.src = 'https://code.jquery.com/jquery-3.6.4.min.js';
            script.integrity = 'sha256-oP6HI9z1XaZNBrJURtCoUT5SUnxFr8s3BzRl+cbzUq8=';
            script.crossOrigin = 'anonymous';

            script.onload = function() {
                console.log('jQuery loaded successfully');
                initializeBoard();
            };

            document.head.appendChild(script);
        } else {
            console.log('jQuery is already loaded');
            initializeBoard();
        }
    });

    // ボード関連の機能を初期化
    function initializeBoard() {
        // ボード削除処理
        jQuery('.delete-board').on('click', function() {
            const boardId = jQuery(this).data('id');
            const boardName = jQuery(this).data('name');

            jQuery('#boardNameToDelete').text(boardName);
            jQuery('#confirmDeleteBoard').data('id', boardId);
            jQuery('#deleteBoardModal').modal('show');
        });

        // 削除確認
        jQuery('#confirmDeleteBoard').on('click', function() {
            const boardId = jQuery(this).data('id');

            // API呼び出し
            jQuery.ajax({
                url: BASE_PATH + '/api/task/boards/' + boardId,
                type: 'DELETE',
                success: function(response) {
                    jQuery('#deleteBoardModal').modal('hide');

                    if (response.success) {
                        if (typeof toastr !== 'undefined') {
                            toastr.success(response.message || 'ボードを削除しました');
                        } else {
                            alert(response.message || 'ボードを削除しました');
                        }

                        // ページをリロード
                        setTimeout(function() {
                            window.location.reload();
                        }, 1000);
                    } else {
                        if (typeof toastr !== 'undefined') {
                            toastr.error(response.error || 'ボードの削除に失敗しました');
                        } else {
                            alert(response.error || 'ボードの削除に失敗しました');
                        }
                    }
                },
                error: function() {
                    jQuery('#deleteBoardModal').modal('hide');

                    if (typeof toastr !== 'undefined') {
                        toastr.error('サーバーとの通信に失敗しました');
                    } else {
                        alert('サーバーとの通信に失敗しました');
                    }
                }
            });
        });
    }
</script>
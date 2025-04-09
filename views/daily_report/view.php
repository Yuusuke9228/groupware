<div class="container-fluid pt-4 px-4">
    <div class="row g-4">
        <div class="col-12">
            <div class="bg-light rounded p-4 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h3 class="mb-0"><?= htmlspecialchars($report['title']) ?></h3>
                        <small class="text-muted"><?= date('Y年m月d日', strtotime($report['report_date'])) ?>の日報</small>
                    </div>
                    <div>
                        <a href="<?= BASE_PATH ?>/daily-report" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>戻る
                        </a>
                        <?php if ($report['user_id'] == $this->auth->id()): ?>
                            <a href="<?= BASE_PATH ?>/daily-report/edit/<?= $report['id'] ?>" class="btn btn-primary ms-2">
                                <i class="fas fa-edit me-2"></i>編集
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <strong>作成者：</strong> <?= htmlspecialchars($report['creator_name']) ?>
                            <span class="ms-3"><strong>作成日時：</strong> <?= date('Y/m/d H:i', strtotime($report['created_at'])) ?></span>
                        </div>
                        <div>
                            <!-- いいねボタン -->
                            <button type="button" class="btn btn-sm <?= $hasLiked ? 'btn-danger' : 'btn-outline-danger' ?>" id="likeButton" data-id="<?= $report['id'] ?>">
                                <i class="fas fa-thumbs-up me-1"></i>
                                <span id="likeCount"><?= $report['likes_count'] ?></span>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- タグ表示 -->
                        <?php if (!empty($report['tags'])): ?>
                            <div class="mb-3">
                                <?php foreach ($report['tags'] as $tag): ?>
                                    <a href="<?= BASE_PATH ?>/daily-report/list?tag_id=<?= $tag['id'] ?>" class="badge bg-primary text-decoration-none me-1">
                                        <?= htmlspecialchars($tag['name']) ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <!-- 日報本文 -->
                        <div class="daily-report-content mb-4">
                            <?= nl2br(htmlspecialchars($report['content'])) ?>
                        </div>

                        <!-- 関連スケジュール -->
                        <?php if (!empty($report['schedules'])): ?>
                            <div class="mt-4">
                                <h5>関連スケジュール</h5>
                                <div class="list-group">
                                    <?php foreach ($report['schedules'] as $schedule): ?>
                                        <a href="<?= BASE_PATH ?>/schedule/view/<?= $schedule['id'] ?>" class="list-group-item list-group-item-action">
                                            <div class="d-flex w-100 justify-content-between">
                                                <h6 class="mb-1"><?= htmlspecialchars($schedule['title']) ?></h6>
                                                <small>
                                                    <?= date('H:i', strtotime($schedule['start_time'])) ?> -
                                                    <?= date('H:i', strtotime($schedule['end_time'])) ?>
                                                </small>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- 関連タスク -->
                        <?php if (!empty($report['tasks'])): ?>
                            <div class="mt-4">
                                <h5>関連タスク</h5>
                                <div class="list-group">
                                    <?php foreach ($report['tasks'] as $task): ?>
                                        <a href="<?= BASE_PATH ?>/task/card/<?= $task['id'] ?>" class="list-group-item list-group-item-action">
                                            <div class="d-flex w-100 justify-content-between">
                                                <h6 class="mb-1"><?= htmlspecialchars($task['title']) ?></h6>
                                                <small class="badge bg-success">完了</small>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer text-muted small">
                        <?php if ($report['updated_at'] != $report['created_at']): ?>
                            <div>最終更新: <?= date('Y/m/d H:i', strtotime($report['updated_at'])) ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- コメント一覧 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">コメント（<?= count($comments) ?>）</h5>
                    </div>
                    <div class="card-body">
                        <div id="commentList">
                            <?php if (empty($comments)): ?>
                                <p class="text-center">まだコメントはありません</p>
                            <?php else: ?>
                                <?php foreach ($comments as $comment): ?>
                                    <div class="d-flex mb-3">
                                        <div class="flex-shrink-0">
                                            <div class="avatar">
                                                <span><?= mb_substr($comment['display_name'], 0, 1) ?></span>
                                            </div>
                                        </div>
                                        <div class="ms-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <h6 class="mb-0"><?= htmlspecialchars($comment['display_name']) ?></h6>
                                                <small class="text-muted"><?= date('Y/m/d H:i', strtotime($comment['created_at'])) ?></small>
                                            </div>
                                            <p class="mb-0"><?= nl2br(htmlspecialchars($comment['comment'])) ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <!-- コメント入力フォーム -->
                        <form id="commentForm" class="mt-4">
                            <input type="hidden" name="report_id" value="<?= $report['id'] ?>">
                            <div class="mb-3">
                                <label for="comment" class="form-label">コメントを追加</label>
                                <textarea class="form-control" id="comment" name="comment" rows="3" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">送信</button>
                        </form>
                    </div>
                </div>

                <!-- 既読者一覧 -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">既読者（<?= count($readUsers) ?>）</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($readUsers)): ?>
                            <p class="text-center">まだ誰も読んでいません</p>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($readUsers as $readUser): ?>
                                    <div class="col-md-4 mb-2">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar me-2">
                                                <span><?= mb_substr($readUser['display_name'], 0, 1) ?></span>
                                            </div>
                                            <div>
                                                <div><?= htmlspecialchars($readUser['display_name']) ?></div>
                                                <small class="text-muted"><?= date('Y/m/d H:i', strtotime($readUser['read_at'])) ?></small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // いいねボタンの処理
        const likeButton = document.getElementById('likeButton');
        if (likeButton) {
            likeButton.addEventListener('click', function() {
                const reportId = this.getAttribute('data-id');

                // いいねAPI呼び出し
                fetch(`${BASE_PATH}/api/daily-report/${reportId}/like`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // いいねボタンの見た目を更新
                            if (data.data.has_liked) {
                                likeButton.classList.remove('btn-outline-danger');
                                likeButton.classList.add('btn-danger');
                            } else {
                                likeButton.classList.remove('btn-danger');
                                likeButton.classList.add('btn-outline-danger');
                            }

                            // いいね数を更新
                            document.getElementById('likeCount').textContent = data.data.likes_count;
                        } else {
                            alert(data.error || 'エラーが発生しました');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('ネットワークエラーが発生しました');
                    });
            });
        }

        // コメントフォームの処理
        const commentForm = document.getElementById('commentForm');
        if (commentForm) {
            commentForm.addEventListener('submit', function(e) {
                e.preventDefault();

                const reportId = this.querySelector('[name="report_id"]').value;
                const comment = this.querySelector('[name="comment"]').value;

                if (!comment.trim()) {
                    alert('コメントを入力してください');
                    return;
                }

                // コメント送信API呼び出し
                fetch(`${BASE_PATH}/api/daily-report/${reportId}/comment`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            comment: comment
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // コメント欄を更新
                            const commentList = document.getElementById('commentList');
                            commentList.innerHTML = '';

                            if (data.data.comments && data.data.comments.length > 0) {
                                data.data.comments.forEach(comment => {
                                    const commentHtml = `
                                <div class="d-flex mb-3">
                                    <div class="flex-shrink-0">
                                        <div class="avatar">
                                            <span>${comment.display_name.substring(0, 1)}</span>
                                        </div>
                                    </div>
                                    <div class="ms-3">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0">${comment.display_name}</h6>
                                            <small class="text-muted">${new Date(comment.created_at).toLocaleString()}</small>
                                        </div>
                                        <p class="mb-0">${comment.comment.replace(/\n/g, '<br>')}</p>
                                    </div>
                                </div>
                            `;
                                    commentList.innerHTML += commentHtml;
                                });
                            } else {
                                commentList.innerHTML = '<p class="text-center">まだコメントはありません</p>';
                            }

                            // コメント入力欄をクリア
                            this.querySelector('[name="comment"]').value = '';

                            // 成功メッセージを表示
                            alert(data.message || 'コメントを送信しました');
                        } else {
                            alert(data.error || 'エラーが発生しました');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('ネットワークエラーが発生しました');
                    });
            });
        }
    });
</script>
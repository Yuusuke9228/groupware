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
                        <a href="<?= BASE_PATH ?>/daily-report/week?date=<?= urlencode($report['report_date']) ?>" class="btn btn-outline-secondary ms-2">
                            <i class="fas fa-calendar-week me-1"></i>週間
                        </a>
                        <a href="<?= BASE_PATH ?>/daily-report/month?month=<?= date('Y-m', strtotime($report['report_date'])) ?>" class="btn btn-outline-secondary ms-2">
                            <i class="fas fa-calendar-alt me-1"></i>月間
                        </a>
                        <a href="<?= BASE_PATH ?>/daily-report/timeline?date=<?= urlencode($report['report_date']) ?>" class="btn btn-outline-secondary ms-2">
                            <i class="fas fa-stream me-1"></i>タイムライン
                        </a>
                        <a href="<?= BASE_PATH ?>/daily-report/analysis" class="btn btn-outline-secondary ms-2">
                            <i class="fas fa-chart-line me-1"></i>分析
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

                        <?php if (!empty($report['summary_text']) || !empty($report['issues_text']) || !empty($report['tomorrow_plan_text']) || !empty($report['reflection_text'])): ?>
                            <div class="row g-3 mb-4">
                                <?php if (!empty($report['summary_text'])): ?>
                                    <div class="col-md-6">
                                        <div class="border rounded p-3 h-100">
                                            <h6 class="text-primary mb-2">本日の成果</h6>
                                            <div><?= nl2br(htmlspecialchars($report['summary_text'])) ?></div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($report['issues_text'])): ?>
                                    <div class="col-md-6">
                                        <div class="border rounded p-3 h-100">
                                            <h6 class="text-danger mb-2">課題・問題点</h6>
                                            <div><?= nl2br(htmlspecialchars($report['issues_text'])) ?></div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($report['tomorrow_plan_text'])): ?>
                                    <div class="col-md-6">
                                        <div class="border rounded p-3 h-100">
                                            <h6 class="text-success mb-2">明日の予定</h6>
                                            <div><?= nl2br(htmlspecialchars($report['tomorrow_plan_text'])) ?></div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($report['reflection_text'])): ?>
                                    <div class="col-md-6">
                                        <div class="border rounded p-3 h-100">
                                            <h6 class="text-secondary mb-2">所感・連絡事項</h6>
                                            <div><?= nl2br(htmlspecialchars($report['reflection_text'])) ?></div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($report['detail_items']) && is_array($report['detail_items'])): ?>
                            <div class="mt-3 mb-4">
                                <h5 class="mb-3">テンプレート項目</h5>
                                <div class="row g-3">
                                    <?php foreach ($report['detail_items'] as $item): ?>
                                        <?php if (empty($item['title']) && empty($item['value'])) continue; ?>
                                        <div class="col-md-6">
                                            <div class="border rounded p-3 h-100">
                                                <h6 class="mb-2"><?= htmlspecialchars($item['title'] ?: '項目') ?></h6>
                                                <div><?= nl2br(htmlspecialchars((string)($item['value'] ?? ''))) ?></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($report['activity_logs']) && is_array($report['activity_logs'])): ?>
                            <div class="mt-4 mb-4">
                                <h5 class="mb-3">活動ログ</h5>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered">
                                        <thead class="table-light">
                                            <tr>
                                                <th>時間</th>
                                                <th>分類</th>
                                                <th>件名</th>
                                                <th>結果</th>
                                                <th>メモ</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($report['activity_logs'] as $log): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars(trim(($log['start_time'] ?? '') . ' - ' . ($log['end_time'] ?? ''), ' -')) ?></td>
                                                    <td><?= htmlspecialchars((string)($log['activity_type'] ?? '')) ?></td>
                                                    <td><?= htmlspecialchars((string)($log['subject'] ?? '')) ?></td>
                                                    <td><?= htmlspecialchars((string)($log['result'] ?? '')) ?></td>
                                                    <td><?= htmlspecialchars((string)($log['memo'] ?? '')) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($report['analysis_entries']) && is_array($report['analysis_entries'])): ?>
                            <div class="mt-4 mb-4">
                                <h5 class="mb-3">分析明細</h5>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered">
                                        <thead class="table-light">
                                            <tr>
                                                <th>案件</th>
                                                <th>業種</th>
                                                <th>商品</th>
                                                <th>プロセス</th>
                                                <th>分類</th>
                                                <th class="text-end">計画金額</th>
                                                <th class="text-end">実績金額</th>
                                                <th class="text-end">計画時間</th>
                                                <th class="text-end">実績時間</th>
                                                <th class="text-end">数量</th>
                                                <th>メモ</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($report['analysis_entries'] as $entry): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars((string)($entry['project_name'] ?? '')) ?></td>
                                                    <td><?= htmlspecialchars((string)($entry['industry_name'] ?? '')) ?></td>
                                                    <td><?= htmlspecialchars((string)($entry['product_name'] ?? '')) ?></td>
                                                    <td><?= htmlspecialchars((string)($entry['process_name'] ?? '')) ?></td>
                                                    <td><?= htmlspecialchars((string)($entry['activity_type'] ?? '')) ?></td>
                                                    <td class="text-end"><?= number_format((float)($entry['planned_amount'] ?? 0), 2) ?></td>
                                                    <td class="text-end"><?= number_format((float)($entry['actual_amount'] ?? 0), 2) ?></td>
                                                    <td class="text-end"><?= number_format((float)($entry['planned_hours'] ?? 0), 2) ?></td>
                                                    <td class="text-end"><?= number_format((float)($entry['actual_hours'] ?? 0), 2) ?></td>
                                                    <td class="text-end"><?= number_format((float)($entry['quantity'] ?? 0), 2) ?></td>
                                                    <td><?= htmlspecialchars((string)($entry['memo'] ?? '')) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- 日報本文 -->
                        <div class="daily-report-content mb-4">
                            <?php if (($report['content_format'] ?? 'text') === 'html'): ?>
                                <?= (string)($report['content'] ?? '') ?>
                            <?php else: ?>
                                <?= nl2br(htmlspecialchars((string)($report['content'] ?? ''))) ?>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($report['attachments']) && is_array($report['attachments'])): ?>
                            <div class="mt-4">
                                <h5>添付ファイル</h5>
                                <div class="list-group">
                                    <?php foreach ($report['attachments'] as $attachment): ?>
                                        <a href="<?= BASE_PATH . '/' . ltrim((string)$attachment['file_path'], '/') ?>" target="_blank" rel="noopener noreferrer" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                            <span><?= htmlspecialchars((string)($attachment['original_name'] ?? '添付ファイル')) ?></span>
                                            <small class="text-muted"><?= number_format((int)($attachment['file_size'] ?? 0) / 1024, 1) ?> KB</small>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

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

                            const commentTitle = document.querySelector('#commentList')?.closest('.card-body')?.previousElementSibling?.querySelector('h5');
                            if (commentTitle && data.data.comments) {
                                commentTitle.textContent = `コメント（${data.data.comments.length}）`;
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

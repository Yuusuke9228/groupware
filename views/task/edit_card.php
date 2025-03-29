<?php
// views/task/edit_card.php
?>
<div class="container-fluid mt-3">
    <div class="row mb-3">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_PATH; ?>/task">タスク管理</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo BASE_PATH; ?>/task/board/<?php echo $board['id']; ?>"><?php echo htmlspecialchars($board['name']); ?></a></li>
                    <li class="breadcrumb-item"><a href="<?php echo BASE_PATH; ?>/task/card/<?php echo $card['id']; ?>"><?php echo htmlspecialchars($card['title']); ?></a></li>
                    <li class="breadcrumb-item active" aria-current="page">カード編集</li>
                </ol>
            </nav>
            <h4 class="mb-3">カード編集</h4>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form id="editCardForm">
                        <input type="hidden" name="id" value="<?php echo $card['id']; ?>">

                        <div class="mb-3">
                            <label for="cardTitle" class="form-label">タイトル <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="cardTitle" name="title" value="<?php echo htmlspecialchars($card['title']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="cardDescription" class="form-label">説明</label>
                            <textarea class="form-control" id="cardDescription" name="description" rows="4"><?php echo htmlspecialchars($card['description'] ?? ''); ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="cardStatus" class="form-label">ステータス</label>
                                <select class="form-select" id="cardStatus" name="status">
                                    <option value="not_started" <?php echo $card['status'] == 'not_started' ? 'selected' : ''; ?>>未対応</option>
                                    <option value="in_progress" <?php echo $card['status'] == 'in_progress' ? 'selected' : ''; ?>>処理中</option>
                                    <option value="completed" <?php echo $card['status'] == 'completed' ? 'selected' : ''; ?>>完了</option>
                                    <option value="deferred" <?php echo $card['status'] == 'deferred' ? 'selected' : ''; ?>>保留</option>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="cardPriority" class="form-label">優先度</label>
                                <select class="form-select" id="cardPriority" name="priority">
                                    <option value="highest" <?php echo $card['priority'] == 'highest' ? 'selected' : ''; ?>>最高</option>
                                    <option value="high" <?php echo $card['priority'] == 'high' ? 'selected' : ''; ?>>高</option>
                                    <option value="normal" <?php echo $card['priority'] == 'normal' ? 'selected' : ''; ?>>通常</option>
                                    <option value="low" <?php echo $card['priority'] == 'low' ? 'selected' : ''; ?>>低</option>
                                    <option value="lowest" <?php echo $card['priority'] == 'lowest' ? 'selected' : ''; ?>>最低</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="cardDueDate" class="form-label">期限日</label>
                                <input type="date" class="form-control" id="cardDueDate" name="due_date" value="<?php echo !empty($card['due_date']) ? date('Y-m-d', strtotime($card['due_date'])) : ''; ?>">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="cardProgress" class="form-label">進捗率</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="cardProgress" name="progress" min="0" max="100" value="<?php echo $card['progress']; ?>">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="cardList" class="form-label">リスト</label>
                            <select class="form-select" id="cardList" name="list_id">
                                <?php foreach ($lists as $list): ?>
                                    <option value="<?php echo $list['id']; ?>" <?php echo $card['list_id'] == $list['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($list['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="cardAssignees" class="form-label">担当者</label>
                            <select class="form-select select2-users" id="cardAssignees" name="assignees[]" multiple>
                                <?php foreach ($members as $member): ?>
                                    <?php
                                    $isAssigned = false;
                                    foreach ($card['assignees'] as $assignee) {
                                        if ($assignee['user_id'] == $member['user_id']) {
                                            $isAssigned = true;
                                            break;
                                        }
                                    }
                                    ?>
                                    <option value="<?php echo $member['user_id']; ?>" <?php echo $isAssigned ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($member['display_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="cardLabels" class="form-label">ラベル</label>
                            <select class="form-select select2-labels" id="cardLabels" name="labels[]" multiple>
                                <?php foreach ($labels as $label): ?>
                                    <?php
                                    $isSelected = false;
                                    foreach ($card['labels'] ?? [] as $cardLabel) {
                                        if ($cardLabel['id'] == $label['id']) {
                                            $isSelected = true;
                                            break;
                                        }
                                    }
                                    ?>
                                    <option value="<?php echo $label['id']; ?>" <?php echo $isSelected ? 'selected' : ''; ?> data-color="<?php echo $label['color']; ?>">
                                        <?php echo htmlspecialchars($label['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="cardColor" class="form-label">カード色</label>
                            <input type="color" class="form-control form-control-color" id="cardColor" name="color" value="<?php echo $card['color'] ?? '#ffffff'; ?>">
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <button type="button" class="btn btn-danger" id="deleteCardBtn">
                                <i class="fas fa-trash"></i> カード削除
                            </button>
                            <div>
                                <a href="<?php echo BASE_PATH; ?>/task/card/<?php echo $card['id']; ?>" class="btn btn-secondary me-2">キャンセル</a>
                                <button type="submit" class="btn btn-primary">更新</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- チェックリスト管理 -->
            <div class="card shadow-sm mt-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">チェックリスト</h5>
                    <button type="button" class="btn btn-sm btn-primary" id="addChecklistBtn">
                        <i class="fas fa-plus"></i> チェックリスト追加
                    </button>
                </div>
                <div class="card-body">
                    <?php if (empty($card['checklists'])): ?>
                        <div class="text-center py-3 text-muted">チェックリストはありません</div>
                    <?php else: ?>
                        <div id="checklistContainer">
                            <?php foreach ($card['checklists'] as $index => $checklist): ?>
                                <div class="card mb-3 checklist-card" data-checklist-id="<?php echo $checklist['id']; ?>">
                                    <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                                        <h6 class="mb-0"><?php echo htmlspecialchars($checklist['title']); ?></h6>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-primary edit-checklist" data-id="<?php echo $checklist['id']; ?>" data-title="<?php echo htmlspecialchars($checklist['title']); ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-danger delete-checklist" data-id="<?php echo $checklist['id']; ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body py-2">
                                        <ul class="list-group list-group-flush checklist-items">
                                            <?php foreach ($checklist['items'] as $item): ?>
                                                <li class="list-group-item px-0 py-2 border-0 d-flex justify-content-between align-items-center checklist-item" data-item-id="<?php echo $item['id']; ?>">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="item-<?php echo $item['id']; ?>" <?php echo $item['is_checked'] ? 'checked' : ''; ?>>
                                                        <label class="form-check-label <?php echo $item['is_checked'] ? 'text-decoration-line-through text-muted' : ''; ?>" for="item-<?php echo $item['id']; ?>">
                                                            <?php echo htmlspecialchars($item['content']); ?>
                                                        </label>
                                                    </div>
                                                    <div class="btn-group btn-group-sm">
                                                        <button type="button" class="btn btn-outline-secondary btn-sm edit-item" data-id="<?php echo $item['id']; ?>" data-content="<?php echo htmlspecialchars($item['content']); ?>">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-outline-danger btn-sm delete-item" data-id="<?php echo $item['id']; ?>">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </div>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                        <div class="mt-2">
                                            <div class="input-group input-group-sm">
                                                <input type="text" class="form-control new-item-text" placeholder="新しい項目を追加...">
                                                <button class="btn btn-outline-primary add-item" type="button" data-checklist-id="<?php echo $checklist['id']; ?>">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">カード情報</h5>
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

            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">コメント履歴</h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($card['comments'])): ?>
                        <div class="p-3 text-center text-muted">コメントはありません</div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach (array_slice($card['comments'], -5) as $comment): ?>
                                <div class="list-group-item">
                                    <div class="d-flex align-items-center mb-1">
                                        <div class="avatar me-2"><?php echo mb_substr($comment['display_name'], 0, 1); ?></div>
                                        <div>
                                            <div><strong><?php echo htmlspecialchars($comment['display_name']); ?></strong></div>
                                            <small class="text-muted"><?php echo date('Y/m/d H:i', strtotime($comment['created_at'])); ?></small>
                                        </div>
                                    </div>
                                    <div class="mt-1 ms-4 small">
                                        <?php echo mb_substr(htmlspecialchars($comment['comment']), 0, 100) . (mb_strlen($comment['comment']) > 100 ? '...' : ''); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if (count($card['comments']) > 5): ?>
                            <div class="p-2 text-center">
                                <a href="<?php echo BASE_PATH; ?>/task/card/<?php echo $card['id']; ?>" class="btn btn-sm btn-link">すべてのコメントを表示</a>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0">ヘルプ</h5>
                </div>
                <div class="card-body">
                    <h6>カードの使い方</h6>
                    <ul>
                        <li>ステータスを更新して進捗を管理しましょう</li>
                        <li>チェックリストを使って細かいタスクを管理できます</li>
                        <li>担当者を複数人設定できます</li>
                        <li>ラベルを使って分類してみましょう</li>
                    </ul>

                    <hr>

                    <h6>ステータスの種類</h6>
                    <ul class="list-unstyled">
                        <li><span class="badge bg-secondary me-1">未対応</span> これから始めるタスク</li>
                        <li><span class="badge bg-primary me-1">処理中</span> 現在作業中のタスク</li>
                        <li><span class="badge bg-success me-1">完了</span> 完了したタスク</li>
                        <li><span class="badge bg-warning me-1">保留</span> 一時的に中断しているタスク</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- チェックリスト追加モーダル -->
<div class="modal fade" id="addChecklistModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">チェックリスト追加</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="checklistTitle" class="form-label">チェックリスト名</label>
                    <input type="text" class="form-control" id="checklistTitle" placeholder="例: 準備作業">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                <button type="button" class="btn btn-primary" id="saveChecklistBtn">追加</button>
            </div>
        </div>
    </div>
</div>

<!-- チェックリスト編集モーダル -->
<div class="modal fade" id="editChecklistModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">チェックリスト編集</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editChecklistId">
                <div class="mb-3">
                    <label for="editChecklistTitle" class="form-label">チェックリスト名</label>
                    <input type="text" class="form-control" id="editChecklistTitle">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                <button type="button" class="btn btn-primary" id="updateChecklistBtn">更新</button>
            </div>
        </div>
    </div>
</div>

<!-- チェックリスト項目編集モーダル -->
<div class="modal fade" id="editItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">項目編集</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editItemId">
                <div class="mb-3">
                    <label for="editItemContent" class="form-label">内容</label>
                    <input type="text" class="form-control" id="editItemContent">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                <button type="button" class="btn btn-primary" id="updateItemBtn">更新</button>
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
        // jQueryが読み込まれているか確認
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
            // Select2の初期化
            if ($.fn.select2) {
                $('#cardAssignees').select2({
                    placeholder: '担当者を選択',
                    width: '100%'
                });

                $('#cardLabels').select2({
                    placeholder: 'ラベルを選択',
                    width: '100%',
                    templateResult: function(label) {
                        if (!label.id) return label.text;

                        const color = $(label.element).data('color') || '#cccccc';
                        return $(`<span><i class="fas fa-tag" style="color: ${color}"></i> ${label.text}</span>`);
                    }
                });
            }

            // フォーム送信時の処理
            $('#editCardForm').on('submit', function(e) {
                e.preventDefault();

                const form = $(this);
                const formData = {};

                // フォームデータを収集
                $.each(form.serializeArray(), function(_, field) {
                    if (field.name.endsWith('[]')) {
                        const key = field.name.slice(0, -2);
                        if (!formData[key]) formData[key] = [];
                        formData[key].push(field.value);
                    } else {
                        formData[field.name] = field.value;
                    }
                });

                // バリデーション
                if (!formData.title.trim()) {
                    toastr.error('タイトルを入力してください');
                    return false;
                }

                // 送信ボタンを無効化
                const submitBtn = form.find('button[type="submit"]');
                submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> 更新中...');

                // API呼び出し
                $.ajax({
                    url: BASE_PATH + '/api/task/cards/' + formData.id,
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify(formData),
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message || 'カードを更新しました');

                            // 詳細ページにリダイレクト
                            setTimeout(function() {
                                window.location.href = BASE_PATH + '/task/card/' + formData.id;
                            }, 1000);
                        } else {
                            toastr.error(response.error || 'カードの更新に失敗しました');
                            submitBtn.prop('disabled', false).html('更新');
                        }
                    },
                    error: function() {
                        toastr.error('サーバーとの通信に失敗しました');
                        submitBtn.prop('disabled', false).html('更新');
                    }
                });

                return false;
            });

            // カード削除ボタン
            $('#deleteCardBtn').on('click', function() {
                $('#deleteConfirmTitle').text('カード削除の確認');
                $('#deleteConfirmBody').html(`
                <p>カード「${$('#cardTitle').val()}」を削除しますか？</p>
                <p class="text-danger">この操作は取り消せません。カードに関連するすべてのチェックリスト、コメント、添付ファイルも削除されます。</p>
            `);

                $('#confirmDelete').off('click').on('click', function() {
                    // API呼び出し
                    $.ajax({
                        url: BASE_PATH + '/api/task/cards/<?php echo $card['id']; ?>',
                        type: 'DELETE',
                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.message || 'カードを削除しました');

                                // ボードページにリダイレクト
                                setTimeout(function() {
                                    window.location.href = BASE_PATH + '/task/board/<?php echo $board['id']; ?>';
                                }, 1000);
                            } else {
                                toastr.error(response.error || 'カードの削除に失敗しました');
                            }
                            $('#deleteConfirmModal').modal('hide');
                        },
                        error: function() {
                            toastr.error('サーバーとの通信に失敗しました');
                            $('#deleteConfirmModal').modal('hide');
                        }
                    });
                });

                $('#deleteConfirmModal').modal('show');
            });

            // チェックリスト追加ボタン
            $('#addChecklistBtn').on('click', function() {
                $('#checklistTitle').val('');
                $('#addChecklistModal').modal('show');
            });

            // チェックリスト保存
            $('#saveChecklistBtn').on('click', function() {
                const title = $('#checklistTitle').val().trim();

                if (!title) {
                    toastr.warning('チェックリスト名を入力してください');
                    return;
                }

                // API呼び出し
                $.ajax({
                    url: BASE_PATH + '/api/task/cards/<?php echo $card['id']; ?>/checklists',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        title: title
                    }),
                    success: function(response) {
                        if (response.success) {
                            toastr.success('チェックリストを追加しました');
                            $('#addChecklistModal').modal('hide');

                            // ページをリロード
                            setTimeout(function() {
                                window.location.reload();
                            }, 1000);
                        } else {
                            toastr.error(response.error || 'チェックリストの追加に失敗しました');
                        }
                    },
                    error: function() {
                        toastr.error('サーバーとの通信に失敗しました');
                    }
                });
            });

            // チェックリスト編集ボタン
            $('.edit-checklist').on('click', function() {
                const id = $(this).data('id');
                const title = $(this).data('title');

                $('#editChecklistId').val(id);
                $('#editChecklistTitle').val(title);

                $('#editChecklistModal').modal('show');
            });

            // チェックリスト更新
            $('#updateChecklistBtn').on('click', function() {
                const id = $('#editChecklistId').val();
                const title = $('#editChecklistTitle').val().trim();

                if (!title) {
                    toastr.warning('チェックリスト名を入力してください');
                    return;
                }

                // API呼び出し
                $.ajax({
                    url: BASE_PATH + '/api/task/checklists/' + id,
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        title: title
                    }),
                    success: function(response) {
                        if (response.success) {
                            toastr.success('チェックリストを更新しました');
                            $('#editChecklistModal').modal('hide');

                            // 表示を更新
                            $(`.checklist-card[data-checklist-id="${id}"] .card-header h6`).text(title);
                        } else {
                            toastr.error(response.error || 'チェックリストの更新に失敗しました');
                        }
                    },
                    error: function() {
                        toastr.error('サーバーとの通信に失敗しました');
                    }
                });
            });

            // チェックリスト削除ボタン
            $('.delete-checklist').on('click', function() {
                const id = $(this).data('id');
                const title = $(this).closest('.card-header').find('h6').text();

                $('#deleteConfirmTitle').text('チェックリスト削除の確認');
                $('#deleteConfirmBody').html(`
                <p>チェックリスト「${title}」を削除しますか？</p>
                <p class="text-danger">この操作は取り消せません。すべての項目も削除されます。</p>
            `);

                $('#confirmDelete').off('click').on('click', function() {
                    // API呼び出し
                    $.ajax({
                        url: BASE_PATH + '/api/task/checklists/' + id,
                        type: 'DELETE',
                        success: function(response) {
                            if (response.success) {
                                toastr.success('チェックリストを削除しました');
                                $('#deleteConfirmModal').modal('hide');

                                // 表示を更新
                                $(`.checklist-card[data-checklist-id="${id}"]`).remove();

                                // チェックリストが0になったら表示を更新
                                if ($('.checklist-card').length === 0) {
                                    $('#checklistContainer').html('<div class="text-center py-3 text-muted">チェックリストはありません</div>');
                                }
                            } else {
                                toastr.error(response.error || 'チェックリストの削除に失敗しました');
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

            // 項目追加ボタン
            $('.add-item').on('click', function() {
                const checklistId = $(this).data('checklist-id');
                const content = $(this).closest('.input-group').find('.new-item-text').val().trim();

                if (!content) {
                    toastr.warning('項目の内容を入力してください');
                    return;
                }

                // API呼び出し
                $.ajax({
                    url: BASE_PATH + '/api/task/checklists/' + checklistId + '/items',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        content: content
                    }),
                    success: function(response) {
                        if (response.success) {
                            toastr.success('項目を追加しました');

                            // 入力欄をクリア
                            $(`.checklist-card[data-checklist-id="${checklistId}"] .new-item-text`).val('');

                            // ページをリロード
                            setTimeout(function() {
                                window.location.reload();
                            }, 1000);
                        } else {
                            toastr.error(response.error || '項目の追加に失敗しました');
                        }
                    },
                    error: function() {
                        toastr.error('サーバーとの通信に失敗しました');
                    }
                });
            });

            // 項目編集ボタン
            $('.edit-item').on('click', function() {
                const id = $(this).data('id');
                const content = $(this).data('content');

                $('#editItemId').val(id);
                $('#editItemContent').val(content);

                $('#editItemModal').modal('show');
            });

            // 項目更新
            $('#updateItemBtn').on('click', function() {
                const id = $('#editItemId').val();
                const content = $('#editItemContent').val().trim();

                if (!content) {
                    toastr.warning('項目の内容を入力してください');
                    return;
                }

                // API呼び出し
                $.ajax({
                    url: BASE_PATH + '/api/task/checklist-items/' + id,
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        content: content
                    }),
                    success: function(response) {
                        if (response.success) {
                            toastr.success('項目を更新しました');
                            $('#editItemModal').modal('hide');

                            // 表示を更新
                            $(`.checklist-item[data-item-id="${id}"] label`).text(content);
                        } else {
                            toastr.error(response.error || '項目の更新に失敗しました');
                        }
                    },
                    error: function() {
                        toastr.error('サーバーとの通信に失敗しました');
                    }
                });
            });

            // 項目削除ボタン
            $('.delete-item').on('click', function() {
                const id = $(this).data('id');
                const content = $(this).closest('.checklist-item').find('label').text();

                $('#deleteConfirmTitle').text('項目削除の確認');
                $('#deleteConfirmBody').html(`
                <p>項目「${content}」を削除しますか？</p>
            `);

                $('#confirmDelete').off('click').on('click', function() {
                    // API呼び出し
                    $.ajax({
                        url: BASE_PATH + '/api/task/checklist-items/' + id,
                        type: 'DELETE',
                        success: function(response) {
                            if (response.success) {
                                toastr.success('項目を削除しました');
                                $('#deleteConfirmModal').modal('hide');

                                // 表示を更新
                                $(`.checklist-item[data-item-id="${id}"]`).remove();
                            } else {
                                toastr.error(response.error || '項目の削除に失敗しました');
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

            // チェックボックスの状態変更
            $('.checklist-item input[type="checkbox"]').on('change', function() {
                const itemId = $(this).closest('.checklist-item').data('item-id');
                const isChecked = $(this).prop('checked');

                // ラベルのスタイルを変更
                const label = $(this).next('label');
                if (isChecked) {
                    label.addClass('text-decoration-line-through text-muted');
                } else {
                    label.removeClass('text-decoration-line-through text-muted');
                }

                // API呼び出し
                $.ajax({
                    url: BASE_PATH + '/api/task/checklist-items/' + itemId,
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
        }
    });
</script>
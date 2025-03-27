<div class="container-fluid mt-3" id="board-container" data-board-id="<?php echo $board['id']; ?>">
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div class="d-flex align-items-center">
                    <h4 class="mb-0">
                        <?php if ($board['owner_type'] == 'user'): ?>
                            <i class="fas fa-clipboard-list me-2" style="color: <?php echo $board['background_color']; ?>"></i>
                        <?php elseif ($board['owner_type'] == 'team'): ?>
                            <i class="fas fa-users me-2" style="color: <?php echo $board['background_color']; ?>"></i>
                        <?php else: ?>
                            <i class="fas fa-building me-2" style="color: <?php echo $board['background_color']; ?>"></i>
                        <?php endif; ?>
                        <?php echo htmlspecialchars($board['name']); ?>
                    </h4>
                    <?php if ($board['is_public']): ?>
                        <span class="badge bg-info ms-2">公開</span>
                    <?php endif; ?>
                </div>
                <div class="d-flex mt-2 mt-md-0">
                    <?php if ($canEdit): ?>
                        <div class="dropdown me-2">
                            <button class="btn btn-primary dropdown-toggle" type="button" id="boardActionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-plus"></i> 追加
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="boardActionsDropdown">
                                <li><a class="dropdown-item" href="#" id="addListBtn"><i class="fas fa-list me-2"></i> リスト追加</a></li>
                                <li><a class="dropdown-item" href="#" id="addLabelBtn"><i class="fas fa-tag me-2"></i> ラベル追加</a></li>
                                <li><a class="dropdown-item" href="#" id="addMemberBtn"><i class="fas fa-user-plus me-2"></i> メンバー追加</a></li>
                            </ul>
                        </div>
                        <a href="<?php echo BASE_PATH; ?>/task/edit-board/<?php echo $board['id']; ?>" class="btn btn-outline-primary me-2">
                            <i class="fas fa-edit"></i> 編集
                        </a>
                    <?php endif; ?>
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="boardViewDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-eye"></i> 表示
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="boardViewDropdown">
                            <li><a class="dropdown-item active" href="#" id="kanbanViewBtn"><i class="fas fa-columns me-2"></i> カンバン表示</a></li>
                            <li><a class="dropdown-item" href="#" id="summaryViewBtn"><i class="fas fa-chart-pie me-2"></i> サマリー表示</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item" href="#" id="filterCardsBtn"><i class="fas fa-filter me-2"></i> フィルター</a></li>
                            <li><a class="dropdown-item" href="#" id="sortCardsBtn"><i class="fas fa-sort me-2"></i> 並び替え</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ボード情報セクション -->
    <div class="row mb-3">
        <div class="col-md-9">
            <?php if (!empty($board['description'])): ?>
                <div class="card shadow-sm mb-3">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2 text-muted">ボード説明</h6>
                        <p class="card-text"><?php echo nl2br(htmlspecialchars($board['description'])); ?></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between mb-2">
                        <small class="text-muted">タスク完了率</small>
                        <small><?php echo $summary['completion']['percentage']; ?>%</small>
                    </div>
                    <div class="progress" style="height: 10px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $summary['completion']['percentage']; ?>%"
                            aria-valuenow="<?php echo $summary['completion']['percentage']; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <div class="d-flex justify-content-between mt-2">
                        <small class="text-muted">タスク数</small>
                        <small><?php echo $summary['completion']['total']; ?></small>
                    </div>
                    <div class="d-flex justify-content-between mt-2">
                        <small class="text-muted">完了タスク</small>
                        <small><?php echo $summary['completion']['completed']; ?></small>
                    </div>
                    <?php if (!empty($summary['due_dates']['overdue'])): ?>
                        <div class="d-flex justify-content-between mt-2">
                            <small class="text-danger">期限切れ</small>
                            <small class="text-danger"><?php echo $summary['due_dates']['overdue']; ?></small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- カンバンボード部分 -->
    <div class="kanban-container">
        <div class="kanban-board" id="kanban-board">
            <?php foreach ($lists as $list): ?>
                <div class="kanban-list" data-list-id="<?php echo $list['id']; ?>">
                    <div class="kanban-list-header">
                        <h6 class="kanban-list-title">
                            <?php echo htmlspecialchars($list['name']); ?>
                            <span class="badge bg-secondary ms-1"><?php echo count($list['cards']); ?></span>
                        </h6>
                        <?php if ($canEdit): ?>
                            <div class="dropdown kanban-list-menu">
                                <button class="btn btn-sm btn-link" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item edit-list" href="#" data-list-id="<?php echo $list['id']; ?>">
                                            <i class="fas fa-edit me-2"></i> リスト編集
                                        </a></li>
                                    <li><a class="dropdown-item add-card" href="#" data-list-id="<?php echo $list['id']; ?>">
                                            <i class="fas fa-plus me-2"></i> カード追加
                                        </a></li>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li><a class="dropdown-item delete-list text-danger" href="#" data-list-id="<?php echo $list['id']; ?>">
                                            <i class="fas fa-trash me-2"></i> リスト削除
                                        </a></li>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="kanban-cards" id="cards-<?php echo $list['id']; ?>">
                        <?php if (empty($list['cards'])): ?>
                            <div class="kanban-empty-msg">カードがありません</div>
                        <?php else: ?>
                            <?php foreach ($list['cards'] as $card): ?>
                                <div class="kanban-card" data-card-id="<?php echo $card['id']; ?>">
                                    <?php if (!empty($card['color'])): ?>
                                        <div class="kanban-card-color" style="background-color: <?php echo $card['color']; ?>"></div>
                                    <?php endif; ?>

                                    <?php if (!empty($card['labels'])): ?>
                                        <div class="kanban-card-labels">
                                            <?php foreach ($card['labels'] as $label): ?>
                                                <span class="kanban-label" style="background-color: <?php echo $label['color']; ?>" title="<?php echo htmlspecialchars($label['name']); ?>"></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>

                                    <h6 class="kanban-card-title"><?php echo htmlspecialchars($card['title']); ?></h6>

                                    <?php if (!empty($card['due_date'])): ?>
                                        <?php
                                        $dueDate = new DateTime($card['due_date']);
                                        $today = new DateTime();
                                        $isDue = $dueDate < $today && $card['status'] != 'completed';
                                        ?>
                                        <div class="kanban-card-due<?php echo $isDue ? ' overdue' : ''; ?>">
                                            <i class="far fa-calendar-alt"></i>
                                            <?php echo $dueDate->format('Y/m/d'); ?>
                                        </div>
                                    <?php endif; ?>

                                    <div class="kanban-card-footer">
                                        <div class="kanban-card-info">
                                            <?php if ($card['assignee_count'] > 0): ?>
                                                <span class="kanban-card-assignees" title="担当者: <?php echo $card['assignee_count']; ?>人">
                                                    <i class="fas fa-user"></i> <?php echo $card['assignee_count']; ?>
                                                </span>
                                            <?php endif; ?>

                                            <?php if (!empty($card['checklist_completion'])): ?>
                                                <span class="kanban-card-checklist" title="チェックリスト: <?php echo $card['checklist_completion']; ?>% 完了">
                                                    <i class="fas fa-check-square"></i> <?php echo $card['checklist_completion']; ?>%
                                                </span>
                                            <?php endif; ?>

                                            <span class="kanban-card-priority priority-<?php echo $card['priority']; ?>" title="優先度: <?php echo $card['priority']; ?>">
                                                <?php
                                                $priorityIcons = [
                                                    'highest' => '<i class="fas fa-arrow-up"></i><i class="fas fa-arrow-up"></i>',
                                                    'high' => '<i class="fas fa-arrow-up"></i>',
                                                    'normal' => '<i class="fas fa-minus"></i>',
                                                    'low' => '<i class="fas fa-arrow-down"></i>',
                                                    'lowest' => '<i class="fas fa-arrow-down"></i><i class="fas fa-arrow-down"></i>'
                                                ];
                                                echo $priorityIcons[$card['priority']] ?? '';
                                                ?>
                                            </span>
                                        </div>

                                        <div class="kanban-card-progress">
                                            <div class="progress" style="height: 5px;">
                                                <div class="progress-bar bg-<?php echo $card['status'] == 'completed' ? 'success' : 'primary'; ?>" role="progressbar"
                                                    style="width: <?php echo $card['progress']; ?>%"
                                                    aria-valuenow="<?php echo $card['progress']; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <?php if ($canEdit): ?>
                        <div class="kanban-add-card">
                            <button class="btn btn-sm btn-light w-100 add-card" data-list-id="<?php echo $list['id']; ?>">
                                <i class="fas fa-plus"></i> カード追加
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <?php if ($canEdit): ?>
                <div class="kanban-add-list">
                    <button class="btn btn-outline-secondary h-100" id="add-list-btn">
                        <i class="fas fa-plus"></i> リスト追加
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- モーダル：カード詳細 -->
<div class="modal fade" id="cardDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cardDetailTitle">カード詳細</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="cardDetailBody">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">読み込み中...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- モーダル：リスト追加 -->
<div class="modal fade" id="addListModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">リスト追加</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addListForm">
                    <div class="mb-3">
                        <label for="listName" class="form-label">リスト名</label>
                        <input type="text" class="form-control" id="listName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="listColor" class="form-label">色</label>
                        <input type="color" class="form-control form-control-color" id="listColor" name="color" value="#ffffff">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                <button type="button" class="btn btn-primary" id="saveListBtn">保存</button>
            </div>
        </div>
    </div>
</div>

<!-- モーダル：リスト編集 -->
<div class="modal fade" id="editListModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">リスト編集</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editListForm">
                    <input type="hidden" id="editListId" name="id">
                    <div class="mb-3">
                        <label for="editListName" class="form-label">リスト名</label>
                        <input type="text" class="form-control" id="editListName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="editListColor" class="form-label">色</label>
                        <input type="color" class="form-control form-control-color" id="editListColor" name="color" value="#ffffff">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                <button type="button" class="btn btn-primary" id="updateListBtn">更新</button>
            </div>
        </div>
    </div>
</div>

<!-- モーダル：カード追加 -->
<div class="modal fade" id="addCardModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">カード追加</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addCardForm">
                    <input type="hidden" id="listIdForCard" name="list_id">
                    <div class="mb-3">
                        <label for="cardTitle" class="form-label">タイトル</label>
                        <input type="text" class="form-control" id="cardTitle" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="cardDescription" class="form-label">説明</label>
                        <textarea class="form-control" id="cardDescription" name="description" rows="3"></textarea>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="cardDueDate" class="form-label">期限日</label>
                            <input type="date" class="form-control" id="cardDueDate" name="due_date">
                        </div>
                        <div class="col-md-6">
                            <label for="cardPriority" class="form-label">優先度</label>
                            <select class="form-select" id="cardPriority" name="priority">
                                <option value="normal">通常</option>
                                <option value="highest">最高</option>
                                <option value="high">高</option>
                                <option value="low">低</option>
                                <option value="lowest">最低</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="cardStatus" class="form-label">ステータス</label>
                            <select class="form-select" id="cardStatus" name="status">
                                <option value="not_started">未対応</option>
                                <option value="in_progress">処理中</option>
                                <option value="completed">完了</option>
                                <option value="deferred">保留</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="cardProgress" class="form-label">進捗率</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="cardProgress" name="progress" min="0" max="100" value="0">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="cardAssignees" class="form-label">担当者</label>
                        <select class="form-select" id="cardAssignees" name="assignees[]" multiple>
                            <?php foreach ($members as $member): ?>
                                <option value="<?php echo $member['user_id']; ?>"><?php echo htmlspecialchars($member['display_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="cardLabels" class="form-label">ラベル</label>
                        <select class="form-select" id="cardLabels" name="labels[]" multiple>
                            <?php foreach ($labels as $label): ?>
                                <option value="<?php echo $label['id']; ?>" data-color="<?php echo $label['color']; ?>">
                                    <?php echo htmlspecialchars($label['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                <button type="button" class="btn btn-primary" id="saveCardBtn">保存</button>
            </div>
        </div>
    </div>
</div>

<!-- モーダル：ラベル追加 -->
<div class="modal fade" id="addLabelModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">ラベル追加</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addLabelForm">
                    <div class="mb-3">
                        <label for="labelName" class="form-label">ラベル名</label>
                        <input type="text" class="form-control" id="labelName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="labelColor" class="form-label">色</label>
                        <input type="color" class="form-control form-control-color" id="labelColor" name="color" value="#cccccc">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                <button type="button" class="btn btn-primary" id="saveLabelBtn">保存</button>
            </div>
        </div>
    </div>
</div>

<!-- モーダル：ラベル管理 -->
<div class="modal fade" id="manageLabelModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">ラベル管理</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <button type="button" class="btn btn-sm btn-primary" id="addNewLabelBtn">
                        <i class="fas fa-plus"></i> 新規ラベル
                    </button>
                </div>
                <div id="labelsList" class="list-group">
                    <!-- ラベル一覧がここに動的に表示される -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
            </div>
        </div>
    </div>
</div>

<!-- モーダル：メンバー追加 -->
<div class="modal fade" id="addMemberModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">メンバー追加</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addMemberForm">
                    <div class="mb-3">
                        <label for="memberUser" class="form-label">ユーザーを選択</label>
                        <select class="form-select" id="memberUser" name="user_id" required>
                            <option value="">選択してください</option>
                            <!-- ユーザー一覧はAjaxで動的に取得 -->
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="memberRole" class="form-label">役割</label>
                        <select class="form-select" id="memberRole" name="role">
                            <option value="viewer">閲覧者</option>
                            <option value="editor">編集者</option>
                            <option value="admin">管理者</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                <button type="button" class="btn btn-primary" id="saveMemberBtn">追加</button>
            </div>
        </div>
    </div>
</div>

<!-- モーダル：フィルター設定 -->
<div class="modal fade" id="filterModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">フィルター設定</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="filterForm">
                    <div class="mb-3">
                        <label class="form-label">ステータス</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="not_started" id="filterStatusNotStarted" name="status[]" checked>
                            <label class="form-check-label" for="filterStatusNotStarted">未対応</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="in_progress" id="filterStatusInProgress" name="status[]" checked>
                            <label class="form-check-label" for="filterStatusInProgress">処理中</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="completed" id="filterStatusCompleted" name="status[]" checked>
                            <label class="form-check-label" for="filterStatusCompleted">完了</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="deferred" id="filterStatusDeferred" name="status[]" checked>
                            <label class="form-check-label" for="filterStatusDeferred">保留</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">優先度</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="highest" id="filterPriorityHighest" name="priority[]" checked>
                            <label class="form-check-label" for="filterPriorityHighest">最高</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="high" id="filterPriorityHigh" name="priority[]" checked>
                            <label class="form-check-label" for="filterPriorityHigh">高</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="normal" id="filterPriorityNormal" name="priority[]" checked>
                            <label class="form-check-label" for="filterPriorityNormal">通常</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="low" id="filterPriorityLow" name="priority[]" checked>
                            <label class="form-check-label" for="filterPriorityLow">低</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="lowest" id="filterPriorityLowest" name="priority[]" checked>
                            <label class="form-check-label" for="filterPriorityLowest">最低</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">担当者</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="me" id="filterAssigneeMe" name="assignee[]" checked>
                            <label class="form-check-label" for="filterAssigneeMe">自分の担当タスク</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="others" id="filterAssigneeOthers" name="assignee[]" checked>
                            <label class="form-check-label" for="filterAssigneeOthers">他のメンバーの担当タスク</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="unassigned" id="filterAssigneeUnassigned" name="assignee[]" checked>
                            <label class="form-check-label" for="filterAssigneeUnassigned">未割り当てタスク</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">期限</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="overdue" id="filterDueOverdue" name="due[]" checked>
                            <label class="form-check-label" for="filterDueOverdue">期限切れ</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="today" id="filterDueToday" name="due[]" checked>
                            <label class="form-check-label" for="filterDueToday">今日</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="this_week" id="filterDueThisWeek" name="due[]" checked>
                            <label class="form-check-label" for="filterDueThisWeek">今週</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="this_month" id="filterDueThisMonth" name="due[]" checked>
                            <label class="form-check-label" for="filterDueThisMonth">今月</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="no_due" id="filterDueNoDue" name="due[]" checked>
                            <label class="form-check-label" for="filterDueNoDue">期限なし</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ラベル</label>
                        <div id="filterLabels">
                            <?php foreach ($labels as $label): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="<?php echo $label['id']; ?>"
                                        id="filterLabel<?php echo $label['id']; ?>" name="label[]" checked>
                                    <label class="form-check-label" for="filterLabel<?php echo $label['id']; ?>">
                                        <span class="badge" style="background-color: <?php echo $label['color']; ?>">
                                            <?php echo htmlspecialchars($label['name']); ?>
                                        </span>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                            <?php if (empty($labels)): ?>
                                <div class="text-muted">ラベルはありません</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                <button type="button" class="btn btn-outline-secondary" id="resetFilterBtn">リセット</button>
                <button type="button" class="btn btn-primary" id="applyFilterBtn">適用</button>
            </div>
        </div>
    </div>
</div>

<!-- モーダル：並び替え設定 -->
<div class="modal fade" id="sortModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">並び替え設定</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="sortForm">
                    <div class="mb-3">
                        <label for="sortField" class="form-label">並び替え項目</label>
                        <select class="form-select" id="sortField" name="sort_field">
                            <option value="title">タイトル</option>
                            <option value="due_date">期限日</option>
                            <option value="priority">優先度</option>
                            <option value="status">ステータス</option>
                            <option value="progress">進捗率</option>
                            <option value="created_at">作成日</option>
                            <option value="updated_at">更新日</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="sortDirection" class="form-label">並び順</label>
                        <select class="form-select" id="sortDirection" name="sort_direction">
                            <option value="asc">昇順（A→Z、古い→新しい）</option>
                            <option value="desc">降順（Z→A、新しい→古い）</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                <button type="button" class="btn btn-primary" id="applySortBtn">適用</button>
            </div>
        </div>
    </div>
</div>

<!-- サマリービュー（タブコンテンツ） -->
<div class="summary-view" style="display: none;">
    <div class="row">
        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">ステータス別</h5>
                </div>
                <div class="card-body">
                    <canvas id="statusChart" width="400" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">優先度別</h5>
                </div>
                <div class="card-body">
                    <canvas id="priorityChart" width="400" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">リスト別</h5>
                </div>
                <div class="card-body">
                    <canvas id="listChart" width="400" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">担当者別</h5>
                </div>
                <div class="card-body">
                    <canvas id="assigneeChart" width="400" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">期限別</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body text-center p-3">
                                    <h6 class="card-subtitle mb-2 text-danger">期限切れ</h6>
                                    <h3 class="mb-0 text-danger"><?php echo $summary['due_dates']['overdue'] ?? 0; ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body text-center p-3">
                                    <h6 class="card-subtitle mb-2 text-muted">今週期限</h6>
                                    <h3 class="mb-0"><?php echo $summary['due_dates']['this_week'] ?? 0; ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <canvas id="dueChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
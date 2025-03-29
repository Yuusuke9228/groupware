<div class="container-fluid mt-3">
    <div class="row mb-3">
        <div class="col-md-8">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_PATH; ?>/task">タスク管理</a></li>
                    <li class="breadcrumb-item active" aria-current="page">マイタスク</li>
                </ol>
            </nav>
            <h4 class="mb-3">マイタスク</h4>
        </div>
        <div class="col-md-4 text-md-end">
            <div class="btn-group">
                <a href="<?php echo BASE_PATH; ?>/task/create-board" class="btn btn-primary">
                    <i class="fas fa-plus-circle"></i> 新規ボード作成
                </a>
            </div>
        </div>
    </div>

    <!-- フィルターバー -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex flex-wrap align-items-center">
                        <div class="me-2 mb-2">
                            <span class="fw-bold">ステータス:</span>
                            <a href="<?php echo BASE_PATH; ?>/task/my-tasks" class="btn btn-sm <?php echo !isset($filters['status']) ? 'btn-primary' : 'btn-outline-primary'; ?> me-1">すべて</a>
                            <a href="<?php echo BASE_PATH; ?>/task/my-tasks?status=not_started" class="btn btn-sm <?php echo isset($filters['status']) && $filters['status'] == 'not_started' ? 'btn-secondary' : 'btn-outline-secondary'; ?> me-1">未対応</a>
                            <a href="<?php echo BASE_PATH; ?>/task/my-tasks?status=in_progress" class="btn btn-sm <?php echo isset($filters['status']) && $filters['status'] == 'in_progress' ? 'btn-primary' : 'btn-outline-primary'; ?> me-1">処理中</a>
                            <a href="<?php echo BASE_PATH; ?>/task/my-tasks?status=completed" class="btn btn-sm <?php echo isset($filters['status']) && $filters['status'] == 'completed' ? 'btn-success' : 'btn-outline-success'; ?> me-1">完了</a>
                            <a href="<?php echo BASE_PATH; ?>/task/my-tasks?status=deferred" class="btn btn-sm <?php echo isset($filters['status']) && $filters['status'] == 'deferred' ? 'btn-warning' : 'btn-outline-warning'; ?> me-1">保留</a>
                        </div>
                        <div class="me-2 mb-2">
                            <span class="fw-bold">優先度:</span>
                            <a href="<?php echo BASE_PATH; ?>/task/my-tasks" class="btn btn-sm <?php echo !isset($filters['priority']) ? 'btn-primary' : 'btn-outline-primary'; ?> me-1">すべて</a>
                            <a href="<?php echo BASE_PATH; ?>/task/my-tasks?priority=highest" class="btn btn-sm <?php echo isset($filters['priority']) && $filters['priority'] == 'highest' ? 'btn-danger' : 'btn-outline-danger'; ?> me-1">最高</a>
                            <a href="<?php echo BASE_PATH; ?>/task/my-tasks?priority=high" class="btn btn-sm <?php echo isset($filters['priority']) && $filters['priority'] == 'high' ? 'btn-warning' : 'btn-outline-warning'; ?> me-1">高</a>
                            <a href="<?php echo BASE_PATH; ?>/task/my-tasks?priority=normal" class="btn btn-sm <?php echo isset($filters['priority']) && $filters['priority'] == 'normal' ? 'btn-primary' : 'btn-outline-primary'; ?> me-1">通常</a>
                            <a href="<?php echo BASE_PATH; ?>/task/my-tasks?priority=low" class="btn btn-sm <?php echo isset($filters['priority']) && $filters['priority'] == 'low' ? 'btn-info' : 'btn-outline-info'; ?> me-1">低</a>
                            <a href="<?php echo BASE_PATH; ?>/task/my-tasks?priority=lowest" class="btn btn-sm <?php echo isset($filters['priority']) && $filters['priority'] == 'lowest' ? 'btn-secondary' : 'btn-outline-secondary'; ?> me-1">最低</a>
                        </div>
                        <div class="me-2 mb-2">
                            <span class="fw-bold">期限:</span>
                            <a href="<?php echo BASE_PATH; ?>/task/my-tasks" class="btn btn-sm <?php echo !isset($filters['due_date']) ? 'btn-primary' : 'btn-outline-primary'; ?> me-1">すべて</a>
                            <a href="<?php echo BASE_PATH; ?>/task/my-tasks?due_date=overdue" class="btn btn-sm <?php echo isset($filters['due_date']) && $filters['due_date'] == 'overdue' ? 'btn-danger' : 'btn-outline-danger'; ?> me-1">期限切れ</a>
                            <a href="<?php echo BASE_PATH; ?>/task/my-tasks?due_date=today" class="btn btn-sm <?php echo isset($filters['due_date']) && $filters['due_date'] == 'today' ? 'btn-warning' : 'btn-outline-warning'; ?> me-1">今日</a>
                            <a href="<?php echo BASE_PATH; ?>/task/my-tasks?due_date=week" class="btn btn-sm <?php echo isset($filters['due_date']) && $filters['due_date'] == 'week' ? 'btn-info' : 'btn-outline-info'; ?> me-1">今週</a>
                        </div>

                        <?php if (!empty($filters)): ?>
                            <div class="ms-auto mb-2">
                                <a href="<?php echo BASE_PATH; ?>/task/my-tasks" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-times"></i> フィルタークリア
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>タイトル</th>
                                    <th>リスト</th>
                                    <th>ボード</th>
                                    <th>優先度</th>
                                    <th>ステータス</th>
                                    <th>期限</th>
                                    <th>進捗</th>
                                    <th>更新日</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($tasks)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center py-3">タスクが見つかりません</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($tasks as $task): ?>
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

                                        $priorityColor = $priorityColors[$task['priority']] ?? 'primary';
                                        $priorityName = $priorityNames[$task['priority']] ?? $task['priority'];
                                        $statusColor = $statusColors[$task['status']] ?? 'info';
                                        $statusName = $statusNames[$task['status']] ?? $task['status'];

                                        $dueDate = !empty($task['due_date']) ? new DateTime($task['due_date']) : null;
                                        $today = new DateTime();
                                        $isDue = $dueDate && $dueDate < $today && $task['status'] != 'completed';
                                        ?>
                                        <tr>
                                            <td>
                                                <a href="<?php echo BASE_PATH; ?>/task/card/<?php echo $task['id']; ?>" class="text-decoration-none">
                                                    <?php echo htmlspecialchars($task['title']); ?>
                                                </a>
                                            </td>
                                            <td><?php echo htmlspecialchars($task['list_name'] ?? ''); ?></td>
                                            <td>
                                                <a href="<?php echo BASE_PATH; ?>/task/board/<?php echo $task['board_id']; ?>" class="text-decoration-none">
                                                    <?php echo htmlspecialchars($task['board_name']); ?>
                                                </a>
                                            </td>
                                            <td><span class="badge bg-<?php echo $priorityColor; ?>"><?php echo $priorityName; ?></span></td>
                                            <td><span class="badge bg-<?php echo $statusColor; ?>"><?php echo $statusName; ?></span></td>
                                            <td class="<?php echo $isDue ? 'text-danger fw-bold' : ''; ?>">
                                                <?php echo $dueDate ? $dueDate->format('Y/m/d') : '-'; ?>
                                            </td>
                                            <td>
                                                <div class="progress" style="height: 8px;">
                                                    <div class="progress-bar bg-<?php echo $statusColor; ?>" role="progressbar"
                                                        style="width: <?php echo $task['progress']; ?>%"
                                                        aria-valuenow="<?php echo $task['progress']; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                                <small class="text-muted"><?php echo $task['progress']; ?>%</small>
                                            </td>
                                            <td><?php echo date('Y/m/d', strtotime($task['updated_at'])); ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="<?php echo BASE_PATH; ?>/task/card/<?php echo $task['id']; ?>" class="btn btn-outline-secondary" title="詳細表示">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="<?php echo BASE_PATH; ?>/task/edit-card/<?php echo $task['id']; ?>" class="btn btn-outline-primary" title="編集">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
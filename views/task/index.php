<div class="container-fluid mt-3">
    <div class="row mb-3">
        <div class="col-md-8">
            <div class="d-flex justify-content-between align-items-center">
                <h4>タスク管理</h4>
                <div>
                    <a href="<?php echo BASE_PATH; ?>/task/create-board" class="btn btn-primary">
                        <i class="fas fa-plus-circle"></i> 新規ボード作成
                    </a>
                    <a href="<?php echo BASE_PATH; ?>/task/create-team" class="btn btn-outline-primary ms-2">
                        <i class="fas fa-users"></i> 新規チーム作成
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- 左側：タスク情報 -->
        <div class="col-md-8">
            <!-- タスク概要 -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">タスク概要</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body text-center p-3">
                                    <h6 class="card-subtitle mb-2 text-muted">担当タスク数</h6>
                                    <h3 class="mb-0"><?php echo $taskSummary['total'] ?? 0; ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body text-center p-3">
                                    <h6 class="card-subtitle mb-2 text-muted">完了タスク</h6>
                                    <h3 class="mb-0">
                                        <?php 
                                        $completed = 0;
                                        foreach ($taskSummary['status'] ?? [] as $status) {
                                            if ($status['status'] == 'completed') {
                                                $completed = $status['count'];
                                                break;
                                            }
                                        }
                                        echo $completed;
                                        ?>
                                    </h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body text-center p-3">
                                    <h6 class="card-subtitle mb-2 text-muted">今日期限</h6>
                                    <h3 class="mb-0"><?php echo $taskSummary['due_dates']['today'] ?? 0; ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body text-center p-3">
                                    <h6 class="card-subtitle mb-2 text-danger">期限切れ</h6>
                                    <h3 class="mb-0 text-danger"><?php echo $taskSummary['due_dates']['overdue'] ?? 0; ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <h6>ステータス別</h6>
                        <div class="progress" style="height: 25px;">
                            <?php
                            $statusColors = [
                                'not_started' => 'bg-secondary',
                                'in_progress' => 'bg-primary',
                                'completed' => 'bg-success',
                                'deferred' => 'bg-warning'
                            ];
                            $statusNames = [
                                'not_started' => '未対応',
                                'in_progress' => '処理中',
                                'completed' => '完了',
                                'deferred' => '保留'
                            ];
                            
                            foreach ($taskSummary['status'] ?? [] as $status) {
                                $percentage = ($status['count'] / max(1, $taskSummary['total'])) * 100;
                                $color = $statusColors[$status['status']] ?? 'bg-info';
                                $name = $statusNames[$status['status']] ?? $status['status'];
                                echo '<div class="progress-bar ' . $color . '" role="progressbar" style="width: ' . $percentage . '%" 
                                      aria-valuenow="' . $percentage . '" aria-valuemin="0" aria-valuemax="100" 
                                      title="' . $name . ': ' . $status['count'] . '">' . $name . '</div>';
                            }
                            ?>
                        </div>
                    </div>

                    <div class="mt-4">
                        <h6>優先度別</h6>
                        <div class="row">
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
                            
                            foreach ($taskSummary['priority'] ?? [] as $priority) {
                                $color = $priorityColors[$priority['priority']] ?? 'primary';
                                $name = $priorityNames[$priority['priority']] ?? $priority['priority'];
                                ?>
                                <div class="col-md-2 col-4 mb-2">
                                    <div class="d-flex justify-content-between">
                                        <span><?php echo $name; ?></span>
                                        <span class="badge bg-<?php echo $color; ?>"><?php echo $priority['count']; ?></span>
                                    </div>
                                </div>
                                <?php
                            }
                            ?>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-center">
                    <a href="<?php echo BASE_PATH; ?>/task/my-tasks" class="btn btn-sm btn-outline-primary">担当タスク一覧へ</a>
                </div>
            </div>

            <!-- 直近のタスク -->
            <div class="card shadow-sm mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">近日中のタスク</h5>
                    <a href="<?php echo BASE_PATH; ?>/task/my-tasks" class="btn btn-sm btn-outline-secondary">すべて表示</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>タイトル</th>
                                    <th>優先度</th>
                                    <th>ステータス</th>
                                    <th>期限</th>
                                    <th>進捗</th>
                                    <th>ボード</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($upcomingTasks)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-3">近日中のタスクはありません</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($upcomingTasks as $task): ?>
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
                                        ?>
                                        <tr>
                                            <td>
                                                <a href="<?php echo BASE_PATH; ?>/task/card/<?php echo $task['id']; ?>" class="text-decoration-none">
                                                    <?php echo htmlspecialchars($task['title']); ?>
                                                </a>
                                            </td>
                                            <td><span class="badge bg-<?php echo $priorityColor; ?>"><?php echo $priorityName; ?></span></td>
                                            <td><span class="badge bg-<?php echo $statusColor; ?>"><?php echo $statusName; ?></span></td>
                                            <td><?php echo date('Y/m/d', strtotime($task['due_date'])); ?></td>
                                            <td>
                                                <div class="progress" style="height: 8px;">
                                                    <div class="progress-bar bg-<?php echo $statusColor; ?>" role="progressbar" 
                                                         style="width: <?php echo $task['progress']; ?>%" 
                                                         aria-valuenow="<?php echo $task['progress']; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                            </td>
                                            <td>
                                                <a href="<?php echo BASE_PATH; ?>/task/board/<?php echo $task['board_id']; ?>" class="text-decoration-none">
                                                    <?php echo htmlspecialchars($task['board_name']); ?>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- 遅延タスク -->
            <?php if (!empty($overdueTasks)): ?>
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">期限切れタスク</h5>
                        <a href="<?php echo BASE_PATH; ?>/task/my-tasks?due_date=overdue" class="btn btn-sm btn-outline-light">すべて表示</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>タイトル</th>
                                        <th>優先度</th>
                                        <th>ステータス</th>
                                        <th>期限</th>
                                        <th>進捗</th>
                                        <th>ボード</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($overdueTasks as $task): ?>
                                        <?php
                                        $priorityColor = $priorityColors[$task['priority']] ?? 'primary';
                                        $priorityName = $priorityNames[$task['priority']] ?? $task['priority'];
                                        $statusColor = $statusColors[$task['status']] ?? 'info';
                                        $statusName = $statusNames[$task['status']] ?? $task['status'];
                                        ?>
                                        <tr>
                                            <td>
                                                <a href="<?php echo BASE_PATH; ?>/task/card/<?php echo $task['id']; ?>" class="text-decoration-none">
                                                    <?php echo htmlspecialchars($task['title']); ?>
                                                </a>
                                            </td>
                                            <td><span class="badge bg-<?php echo $priorityColor; ?>"><?php echo $priorityName; ?></span></td>
                                            <td><span class="badge bg-<?php echo $statusColor; ?>"><?php echo $statusName; ?></span></td>
                                            <td class="text-danger"><?php echo date('Y/m/d', strtotime($task['due_date'])); ?></td>
                                            <td>
                                                <div class="progress" style="height: 8px;">
                                                    <div class="progress-bar bg-<?php echo $statusColor; ?>" role="progressbar" 
                                                         style="width: <?php echo $task['progress']; ?>%" 
                                                         aria-valuenow="<?php echo $task['progress']; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                            </td>
                                            <td>
                                                <a href="<?php echo BASE_PATH; ?>/task/board/<?php echo $task['board_id']; ?>" class="text-decoration-none">
                                                    <?php echo htmlspecialchars($task['board_name']); ?>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- 右側：ボード一覧とチーム -->
        <div class="col-md-4">
            <!-- 個人ボード -->
            <div class="card shadow-sm mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">個人ボード</h5>
                    <a href="<?php echo BASE_PATH; ?>/task/my-boards" class="btn btn-sm btn-outline-primary">すべて表示</a>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php
                        $personalBoards = array_filter($boards, function($board) {
                            return $board['owner_type'] == 'user';
                        });
                        
                        if (empty($personalBoards)):
                        ?>
                            <div class="list-group-item text-center py-3">個人ボードはありません</div>
                        <?php else: ?>
                            <?php
                            // 最大5件まで表示
                            $count = 0;
                            foreach ($personalBoards as $board):
                                if ($count++ >= 5) break;
                            ?>
                                <a href="<?php echo BASE_PATH; ?>/task/board/<?php echo $board['id']; ?>" class="list-group-item list-group-item-action">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <i class="fas fa-clipboard-list me-2" style="color: <?php echo $board['background_color']; ?>"></i>
                                            <?php echo htmlspecialchars($board['name']);
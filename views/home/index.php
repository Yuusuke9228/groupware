<div class="container-fluid mt-3">
    <div class="row mb-3">
        <div class="col-lg-8">
            <h4 class="mb-3">ダッシュボード</h4>

            <!-- 今日の日付 -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5><?php echo date('Y年m月d日', strtotime($today)); ?> (<?php echo ['日', '月', '火', '水', '木', '金', '土'][date('w', strtotime($today))]; ?>)</h5>
                <a href="<?php echo BASE_PATH; ?>/schedule/day?date=<?php echo $today; ?>" class="btn btn-sm btn-outline-primary">1日表示へ</a>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- 左側：週間スケジュール -->
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">週間スケジュール</h5>
                    <div>
                        <!--
                        <a href="<?php echo BASE_PATH; ?>/schedule/create" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus-circle"></i> 予定登録
                        </a>
                        -->
                        <div class="dropdown d-inline-block me-2">
                            <button class="btn btn-sm btn-primary dropdown-toggle" type="button" id="scheduleDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-plus-circle"></i> 予定登録
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="scheduleDropdown">
                                <li><a class="dropdown-item" href="<?php echo BASE_PATH; ?>/schedule/create">個人予定登録</a></li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li>
                                    <h6 class="dropdown-header">組織スケジュール</h6>
                                </li>
                                <?php foreach ($organizations as $org): ?>
                                    <li><a class="dropdown-item" href="<?php echo BASE_PATH; ?>/schedule/organization-week?organization_id=<?php echo $org['id']; ?>"><?php echo htmlspecialchars($org['name']); ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <a href="<?php echo BASE_PATH; ?>/schedule/week" class="btn btn-sm btn-outline-secondary ms-2">
                            <i class="far fa-calendar-alt"></i> 予定管理
                        </a>
                    </div>
                </div>
                <div class="card-header bg-light py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="btn-group">
                            <a href="<?php echo BASE_PATH; ?>/?date=<?php echo date('Y-m-d', strtotime($weekStart->format('Y-m-d') . ' -7 days')); ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-chevron-left"></i> 前週
                            </a>
                            <a href="<?php echo BASE_PATH; ?>/" class="btn btn-sm btn-outline-primary">
                                今週
                            </a>
                            <a href="<?php echo BASE_PATH; ?>/?date=<?php echo date('Y-m-d', strtotime($weekStart->format('Y-m-d') . ' +7 days')); ?>" class="btn btn-sm btn-outline-secondary">
                                次週 <i class="fas fa-chevron-right"></i>
                            </a>
                        </div>
                        <span class="badge bg-info text-dark">
                            <?php echo $weekStart->format('Y年m月d日'); ?> 〜 <?php echo $weekEnd->format('Y年m月d日'); ?>
                        </span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="schedule-container">
                        <div class="schedule-grid">
                            <!-- タイムテーブルのヘッダー -->
                            <div class="schedule-header">
                                <div class="schedule-time-column"></div>
                                <?php foreach ($weekDates as $date): ?>
                                    <?php
                                    $dayOfWeek = date('w', strtotime($date));
                                    $dayName = ['日', '月', '火', '水', '木', '金', '土'][$dayOfWeek];
                                    $isToday = $date === $today;
                                    $headerClass = $isToday ? 'bg-primary text-white' : 'bg-light';
                                    $textClass = '';
                                    if (!$isToday) {
                                        $textClass .= $dayOfWeek == 0 ? 'text-danger' : '';
                                        $textClass .= $dayOfWeek == 6 ? 'text-primary' : '';
                                    }
                                    ?>
                                    <div class="schedule-day-column <?php echo $headerClass; ?>">
                                        <div class="schedule-date <?php echo $textClass; ?>">
                                            <?php echo date('m/d', strtotime($date)); ?>
                                        </div>
                                        <div class="schedule-day <?php echo $textClass; ?>">
                                            (<?php echo $dayName; ?>)
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- タイムテーブルの本体 -->
                            <div class="schedule-body">
                                <!-- 時間列 -->
                                <div class="schedule-time-column">
                                    <?php for ($hour = 0; $hour <= 23; $hour++): ?>
                                        <div class="schedule-time-cell">
                                            <?php echo sprintf('%02d:00', $hour); ?>
                                        </div>
                                    <?php endfor; ?>
                                </div>

                                <!-- 各曜日の列 -->
                                <?php foreach ($weekDates as $dateIndex => $date): ?>
                                    <?php
                                    $isToday = $date === $today;
                                    $columnClass = $isToday ? 'today-column' : '';
                                    ?>
                                    <div class="schedule-day-column <?php echo $columnClass; ?>">
                                        <!-- 時間帯背景セル -->
                                        <?php for ($hour = 0; $hour <= 23; $hour++): ?>
                                            <div class="schedule-hour-cell"></div>
                                        <?php endfor; ?>

                                        <!-- この日のスケジュール -->
                                        <?php
                                        // この日付のスケジュールをすべて抽出
                                        $daySchedules = [];
                                        foreach ($weekSchedules as $schedule) {
                                            $scheduleStart = strtotime($schedule['start_time']);
                                            $scheduleDate = date('Y-m-d', $scheduleStart);

                                            // 終日予定の場合は日付の一致を確認
                                            if ($schedule['all_day']) {
                                                // 終日予定の場合、スケジュールの開始日が現在の日付と一致する場合のみ表示
                                                if ($scheduleDate === $date) {
                                                    $daySchedules[] = $schedule;
                                                }
                                            } else if ($scheduleDate === $date) {
                                                // 通常予定は開始日が一致する場合のみ表示
                                                $daySchedules[] = $schedule;
                                            }
                                        }

                                        // 時間枠が重複するスケジュールをグループ化
                                        $scheduleLayers = [];
                                        foreach ($daySchedules as $schedule) {
                                            $placed = false;

                                            // 終日予定は別処理
                                            if ($schedule['all_day']) {
                                                continue;
                                            }

                                            $scheduleStart = strtotime($schedule['start_time']);
                                            $scheduleEnd = strtotime($schedule['end_time']);

                                            // 既存のレイヤーに配置できるか確認
                                            foreach ($scheduleLayers as $layerIndex => $layerEvents) {
                                                $canPlaceInLayer = true;

                                                foreach ($layerEvents as $existingEvent) {
                                                    $existingStart = strtotime($existingEvent['start_time']);
                                                    $existingEnd = strtotime($existingEvent['end_time']);

                                                    // 時間が重複するか確認
                                                    if ($scheduleStart < $existingEnd && $scheduleEnd > $existingStart) {
                                                        $canPlaceInLayer = false;
                                                        break;
                                                    }
                                                }

                                                if ($canPlaceInLayer) {
                                                    $scheduleLayers[$layerIndex][] = $schedule;
                                                    $placed = true;
                                                    break;
                                                }
                                            }

                                            // 既存のレイヤーに配置できなければ新しいレイヤーを作成
                                            if (!$placed) {
                                                $scheduleLayers[] = [$schedule];
                                            }
                                        }

                                        // 終日予定
                                        $allDayEvents = array_filter($daySchedules, function ($s) {
                                            return $s['all_day'] == 1;
                                        });

                                        // 終日予定の表示
                                        foreach ($allDayEvents as $allDayEvent):
                                            $priorityClass = 'priority-' . $allDayEvent['priority'];
                                        ?>
                                            <div class="schedule-event all-day-event <?php echo $priorityClass; ?>">
                                                <a href="<?php echo BASE_PATH; ?>/schedule/view/<?php echo $allDayEvent['id']; ?>" class="text-decoration-none text-dark">
                                                    <div class="event-title">
                                                        <small>終日</small> <?php echo htmlspecialchars($allDayEvent['title']); ?>
                                                    </div>
                                                </a>
                                            </div>
                                        <?php endforeach; ?>

                                        <!-- 通常予定の表示 -->
                                        <?php foreach ($scheduleLayers as $layerIndex => $layerEvents): ?>
                                            <?php
                                            $layerWidth = 100 / count($scheduleLayers);
                                            $leftOffset = $layerIndex * $layerWidth;

                                            foreach ($layerEvents as $event):
                                                $startTime = strtotime($event['start_time']);
                                                $endTime = strtotime($event['end_time']);

                                                // 開始・終了時間が日付範囲外の場合は調整
                                                $dayStartTime = strtotime($date . ' 00:00:00');
                                                $dayEndTime = strtotime($date . ' 23:59:59');

                                                if ($startTime < $dayStartTime) $startTime = $dayStartTime;
                                                if ($endTime > $dayEndTime) $endTime = $dayEndTime;

                                                // 位置とサイズの計算
                                                $startHour = (int)date('G', $startTime);
                                                $startMinute = (int)date('i', $startTime);
                                                $startPosition = $startHour * 60 + $startMinute;

                                                $endHour = (int)date('G', $endTime);
                                                $endMinute = (int)date('i', $endTime);
                                                $endPosition = $endHour * 60 + $endMinute;

                                                $eventHeight = max(30, $endPosition - $startPosition);
                                                $topPosition = $startPosition;

                                                $priorityClass = 'priority-' . $event['priority'];
                                                $displayTime = date('H:i', $startTime) . '-' . date('H:i', $endTime);
                                            ?>
                                                <div class="schedule-event <?php echo $priorityClass; ?>"
                                                    style="top: <?php echo $topPosition; ?>px; 
                                                            height: <?php echo $eventHeight; ?>px; 
                                                            left: <?php echo $leftOffset; ?>%; 
                                                            width: <?php echo $layerWidth; ?>%;">
                                                    <a href="<?php echo BASE_PATH; ?>/schedule/view/<?php echo $event['id']; ?>" class="text-decoration-none text-dark">
                                                        <div class="event-time"><?php echo $displayTime; ?></div>
                                                        <div class="event-title"><?php echo htmlspecialchars($event['title']); ?></div>
                                                        <?php if ($event['location']): ?>
                                                            <div class="event-location">
                                                                <small><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($event['location']); ?></small>
                                                            </div>
                                                        <?php endif; ?>
                                                    </a>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- タスク概要 -->
            <?php if (isset($taskSummary) && !empty($taskSummary)): ?>
            <div class="card shadow-sm mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">タスク概要</h5>
                    <div>
                        <a href="<?php echo BASE_PATH; ?>/task/create-board" class="btn btn-sm btn-primary me-2">
                            <i class="fas fa-plus-circle"></i> 新規ボード作成
                        </a>
                        <a href="<?php echo BASE_PATH; ?>/task" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-tasks"></i> タスク管理へ
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- タスク概要のタブ -->
                    <ul class="nav nav-tabs mb-3">
                        <li class="nav-item">
                            <a class="nav-link active task-summary-tab" href="#" data-target="task-summary-stats">統計</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link task-summary-tab" href="#" data-target="task-summary-charts">グラフ</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link task-summary-tab" href="#" data-target="task-summary-upcoming">近日中のタスク</a>
                        </li>
                    </ul>
                    
                    <!-- タスク統計 -->
                    <div id="task-summary-stats" class="task-summary-content">
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
                    
                    <!-- グラフ表示 -->
                    <div id="task-summary-charts" class="task-summary-content" style="display: none;">
                        <div class="row">
                            <!-- ステータス円グラフ -->
                            <div class="col-md-6">
                                <div class="card shadow-sm mb-3">
                                    <div class="card-header py-2">
                                        <h6 class="card-title mb-0">ステータス別タスク数</h6>
                                    </div>
                                    <div class="card-body">
                                        <?php
                                        // グラフ用データ作成
                                        $statusLabels = [];
                                        $statusValues = [];
                                        
                                        foreach ($taskSummary['status'] ?? [] as $status) {
                                            $name = $statusNames[$status['status']] ?? $status['status'];
                                            $statusLabels[] = $name;
                                            $statusValues[] = $status['count'];
                                        }
                                        ?>
                                        <div style="height: 250px;">
                                            <canvas id="taskStatusChart" 
                                                    data-labels='<?php echo json_encode($statusLabels); ?>' 
                                                    data-values='<?php echo json_encode($statusValues); ?>'></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- 優先度円グラフ -->
                            <div class="col-md-6">
                                <div class="card shadow-sm mb-3">
                                    <div class="card-header py-2">
                                        <h6 class="card-title mb-0">優先度別タスク数</h6>
                                    </div>
                                    <div class="card-body">
                                        <?php
                                        // グラフ用データ作成
                                        $priorityLabels = [];
                                        $priorityValues = [];
                                        
                                        foreach ($taskSummary['priority'] ?? [] as $priority) {
                                            $name = $priorityNames[$priority['priority']] ?? $priority['priority'];
                                            $priorityLabels[] = $name;
                                            $priorityValues[] = $priority['count'];
                                        }
                                        ?>
                                        <div style="height: 250px;">
                                            <canvas id="taskPriorityChart" 
                                                    data-labels='<?php echo json_encode($priorityLabels); ?>' 
                                                    data-values='<?php echo json_encode($priorityValues); ?>'></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 近日中のタスク -->
                    <div id="task-summary-upcoming" class="task-summary-content" style="display: none;">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>タイトル</th>
                                        <th>ステータス</th>
                                        <th>優先度</th>
                                        <th>期限</th>
                                        <th>ボード</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php if (empty($upcomingTasks)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center">近日中のタスクはありません</td>
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
                                        
                                        // 日付が期限切れかチェック
                                        $isDue = false;
                                        if (!empty($task['due_date'])) {
                                            $dueDate = new DateTime($task['due_date']);
                                            $today = new DateTime();
                                            $isDue = ($dueDate < $today) && $task['status'] != 'completed';
                                        }
                                        ?>
                                        <tr>
                                            <td>
                                                <a href="<?php echo BASE_PATH; ?>/task/card/<?php echo $task['id']; ?>" class="text-decoration-none">
                                                    <?php echo htmlspecialchars($task['title']); ?>
                                                </a>
                                            </td>
                                            <td><span class="badge bg-<?php echo $statusColor; ?>"><?php echo $statusName; ?></span></td>
                                            <td><span class="badge bg-<?php echo $priorityColor; ?>"><?php echo $priorityName; ?></span></td>
                                            <td class="<?php echo $isDue ? 'text-danger' : ''; ?>">
                                                <?php echo $task['due_date'] ? date('Y/m/d', strtotime($task['due_date'])) : '-'; ?>
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
                        
                        <?php if (!empty($upcomingTasks)): ?>
                        <div class="text-center mt-2">
                            <a href="<?php echo BASE_PATH; ?>/task/my-tasks" class="btn btn-sm btn-outline-primary">
                                すべてのタスクを表示
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- 右側：通知とメッセージ -->
        <div class="col-lg-4">
            <!-- 通知 -->
            <div class="card shadow-sm mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">通知</h5>
                    <a href="<?php echo BASE_PATH; ?>/notifications" class="btn btn-sm btn-outline-secondary">
                        すべて表示
                    </a>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php if (empty($unreadNotifications)): ?>
                            <li class="list-group-item text-center py-4">未読の通知はありません</li>
                        <?php else: ?>
                            <?php foreach ($unreadNotifications as $notification): ?>
                                <li class="list-group-item">
                                    <a href="<?php echo BASE_PATH . $notification['link']; ?>" class="text-decoration-none">
                                        <div class="d-flex justify-content-between">
                                            <span class="fw-bold text-truncate"><?php echo htmlspecialchars($notification['title']); ?></span>
                                            <small class="text-muted ms-2"><?php echo date('m/d H:i', strtotime($notification['created_at'])); ?></small>
                                        </div>
                                        <div class="small text-truncate"><?php echo htmlspecialchars($notification['content']); ?></div>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>

            <!-- 未読メッセージ -->
            <div class="card shadow-sm mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">未読メッセージ</h5>
                    <div>
                        <a href="<?php echo BASE_PATH; ?>/messages/compose" class="btn btn-sm btn-primary me-2">
                            <i class="fas fa-envelope"></i> 新規作成
                        </a>
                        <a href="<?php echo BASE_PATH; ?>/messages/inbox" class="btn btn-sm btn-outline-secondary">
                            受信トレイへ
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php if (empty($unreadMessages)): ?>
                            <li class="list-group-item text-center py-4">未読のメッセージはありません</li>
                        <?php else: ?>
                            <?php foreach ($unreadMessages as $message): ?>
                                <li class="list-group-item">
                                    <a href="<?php echo BASE_PATH; ?>/messages/view/<?php echo $message['id']; ?>" class="text-decoration-none">
                                        <div class="d-flex justify-content-between">
                                            <span class="fw-bold text-truncate"><?php echo htmlspecialchars($message['subject']); ?></span>
                                            <small class="text-muted ms-2"><?php echo date('m/d H:i', strtotime($message['created_at'])); ?></small>
                                        </div>
                                        <div class="small">
                                            <span class="text-primary"><?php echo htmlspecialchars($message['sender_name']); ?></span> から
                                        </div>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>

            <!-- 今日の予定 -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">今日の予定</h5>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php if (empty($todaySchedules)): ?>
                            <li class="list-group-item text-center py-4">今日の予定はありません</li>
                        <?php else: ?>
                            <?php foreach ($todaySchedules as $schedule): ?>
                                <?php
                                $priorityClass = 'priority-' . $schedule['priority'];
                                $displayTime = $schedule['all_day'] ?
                                    '終日' :
                                    date('H:i', strtotime($schedule['start_time'])) . '-' .
                                    date('H:i', strtotime($schedule['end_time']));
                                ?>
                                <li class="list-group-item">
                                    <a href="<?php echo BASE_PATH; ?>/schedule/view/<?php echo $schedule['id']; ?>" class="text-decoration-none">
                                        <div class="d-flex">
                                            <div class="me-2 <?php echo $priorityClass; ?>" style="width: 3px; border-radius: 2px;"></div>
                                            <div>
                                                <div class="fw-bold"><?php echo htmlspecialchars($schedule['title']); ?></div>
                                                <small class="text-muted"><?php echo $displayTime; ?></small>
                                                <?php if ($schedule['location']): ?>
                                                    <small class="text-muted ms-2">
                                                        <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($schedule['location']); ?>
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
            
            <!-- 期限切れタスク -->
            <?php if (!empty($overdueTasks)): ?>
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-danger text-white">
                    <h5 class="card-title mb-0">期限切れタスク</h5>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php foreach ($overdueTasks as $task): ?>
                            <?php
                            $priorityColors = [
                                'highest' => 'danger',
                                'high' => 'warning',
                                'normal' => 'primary',
                                'low' => 'info',
                                'lowest' => 'secondary'
                            ];
                            $priorityName = [
                                'highest' => '最高',
                                'high' => '高',
                                'normal' => '通常',
                                'low' => '低',
                                'lowest' => '最低'
                            ][$task['priority']] ?? $task['priority'];
                            $priorityBadge = '<span class="badge bg-' . $priorityColors[$task['priority']] . ' ms-1">' . $priorityName . '</span>';
                            ?>
                            <li class="list-group-item">
                                <a href="<?php echo BASE_PATH; ?>/task/card/<?php echo $task['id']; ?>" class="text-decoration-none">
                                    <div class="d-flex justify-content-between">
                                        <span class="fw-bold"><?php echo htmlspecialchars($task['title']); ?></span>
                                        <span class="text-danger"><?php echo date('m/d', strtotime($task['due_date'])); ?></span>
                                    </div>
                                    <div class="small">
                                        <?php echo $priorityBadge; ?>
                                        <span class="text-muted ms-1"><?php echo htmlspecialchars($task['board_name']); ?></span>
                                    </div>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="card-footer text-center">
                    <a href="<?php echo BASE_PATH; ?>/task/my-tasks?due_date=overdue" class="btn btn-sm btn-outline-danger">
                        すべての期限切れタスクを表示
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
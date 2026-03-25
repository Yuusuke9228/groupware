<?php
// 曜日名
$dayNames = ['日', '月', '火', '水', '木', '金', '土'];
$todayDow = $dayNames[date('w', strtotime($today))];
?>

<div class="gw-portal">
    <!-- ========== メインエリア ========== -->
    <div class="gw-portal-main">

        <!-- 日付表示 -->
        <div class="gw-portal-date">
            <?php echo date('Y年n月j日', strtotime($today)); ?> (<?php echo $todayDow; ?>)
            <span class="today-badge">TODAY</span>
        </div>

        <!-- 今日の予定 ウィジェット -->
        <div class="gw-widget">
            <div class="gw-widget-header">
                <div class="gw-widget-title">
                    <span class="icon"><i class="far fa-calendar-check"></i></span>
                    今日の予定
                    <?php if (!empty($todaySchedules)): ?>
                        <span class="badge bg-primary"><?php echo count($todaySchedules); ?></span>
                    <?php endif; ?>
                </div>
                <div class="gw-widget-actions">
                    <a href="<?php echo BASE_PATH; ?>/schedule/create" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus"></i> 予定登録
                    </a>
                    <a href="<?php echo BASE_PATH; ?>/schedule/organization-day?date=<?php echo $today; ?>&organization_id=<?php echo (int)$selectedOrganizationId; ?>" class="btn btn-sm btn-outline-secondary">
                        組織1日表示
                    </a>
                </div>
            </div>
            <div class="gw-widget-body">
                <?php if (empty($todaySchedules)): ?>
                    <div class="gw-empty-state">
                        <i class="far fa-calendar"></i>
                        今日の予定はありません
                    </div>
                <?php else: ?>
                    <div class="gw-schedule-list">
                        <?php foreach ($todaySchedules as $schedule): ?>
                            <?php
                            $priorityClass = $schedule['priority'] ?? 'normal';
                            $displayTime = $schedule['all_day'] ?
                                '終日' :
                                date('H:i', strtotime($schedule['start_time'])) . ' - ' .
                                date('H:i', strtotime($schedule['end_time']));
                            ?>
                            <a href="<?php echo BASE_PATH; ?>/schedule/view/<?php echo $schedule['id']; ?>" class="gw-schedule-item">
                                <div class="gw-schedule-color <?php echo $priorityClass; ?>"></div>
                                <div class="gw-schedule-time"><?php echo $displayTime; ?></div>
                                <div class="gw-schedule-content">
                                    <div class="gw-schedule-title"><?php echo htmlspecialchars($schedule['title']); ?></div>
                                    <div class="gw-schedule-meta">
                                        <?php if (!empty($schedule['creator_name'])): ?>
                                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($schedule['creator_name']); ?>
                                        <?php endif; ?>
                                        <?php if (!empty($schedule['location'])): ?>
                                            <span class="ms-2"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($schedule['location']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- 週間スケジュール ウィジェット -->
        <div class="gw-widget">
            <div class="gw-widget-header">
                <div class="gw-widget-title">
                    <span class="icon"><i class="far fa-calendar-alt"></i></span>
                    週間スケジュール
                    <?php if (!empty($selectedOrganization['name'])): ?>
                        <span style="font-weight:400;color:var(--text-muted);font-size:12px;">
                            (<?php echo htmlspecialchars($selectedOrganization['name']); ?>)
                        </span>
                    <?php endif; ?>
                </div>
                <div class="gw-widget-actions">
                    <a href="<?php echo BASE_PATH; ?>/schedule/organization-week?organization_id=<?php echo (int)$selectedOrganizationId; ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-external-link-alt"></i> 詳細表示
                    </a>
                </div>
            </div>
            <div class="gw-widget-body">
                <!-- 週ナビゲーション -->
                <div style="padding:8px 16px;background:var(--bg-sidebar);border-bottom:1px solid var(--border-light);">
                    <div class="gw-week-nav">
                        <div class="btn-group">
                            <a href="<?php echo BASE_PATH; ?>/?date=<?php echo date('Y-m-d', strtotime($weekStart->format('Y-m-d') . ' -7 days')); ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                            <a href="<?php echo BASE_PATH; ?>/" class="btn btn-sm btn-outline-primary">今週</a>
                            <a href="<?php echo BASE_PATH; ?>/?date=<?php echo date('Y-m-d', strtotime($weekStart->format('Y-m-d') . ' +7 days')); ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </div>
                        <span class="gw-week-range">
                            <?php echo $weekStart->format('n/j'); ?> - <?php echo $weekEnd->format('n/j'); ?>
                        </span>
                    </div>
                </div>

                <!-- 組織週間タイムライン -->
                <div class="home-org-timeline-wrap" style="overflow:auto;max-height:500px;-webkit-overflow-scrolling:touch;">
                    <?php if (empty($userWeekSchedules)): ?>
                        <div class="text-center text-muted p-4">組織メンバーがいません</div>
                    <?php else: ?>
                    <div class="home-org-timeline">
                        <!-- ヘッダー行 -->
                        <div class="home-org-header">
                            <div class="home-org-user-col">ユーザー</div>
                            <?php foreach ($weekDates as $date): ?>
                                <?php
                                $dow = date('w', strtotime($date));
                                $dn = $dayNames[$dow];
                                $isTd = $date === $today;
                                $wkend = ($dow == 0 || $dow == 6);
                                ?>
                                <div class="home-org-day-col <?= $isTd ? 'today' : '' ?> <?= $wkend ? 'weekend' : '' ?>">
                                    <div class="home-org-day-name"><?= $dn ?></div>
                                    <div class="home-org-day-num"><?= date('n/j', strtotime($date)) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <!-- ユーザー行 -->
                        <div class="home-org-body">
                            <?php foreach ($userWeekSchedules as $memberId => $memberData): ?>
                                <div class="home-org-row">
                                    <div class="home-org-user-col">
                                        <div class="home-org-user-name"><?= htmlspecialchars($memberData['user']['display_name']) ?></div>
                                    </div>
                                    <?php foreach ($weekDates as $date): ?>
                                        <?php
                                        $dow = date('w', strtotime($date));
                                        $isTd = $date === $today;
                                        $wkend = ($dow == 0 || $dow == 6);
                                        $dayEvts = $memberData['daily'][$date] ?? [];
                                        usort($dayEvts, function($a, $b) {
                                            if ($a['all_day'] && !$b['all_day']) return -1;
                                            if (!$a['all_day'] && $b['all_day']) return 1;
                                            return strtotime($a['start_time']) - strtotime($b['start_time']);
                                        });
                                        $maxShow = 3;
                                        $showEvts = array_slice($dayEvts, 0, $maxShow);
                                        $remaining = max(0, count($dayEvts) - $maxShow);
                                        ?>
                                        <div class="home-org-day-cell <?= $isTd ? 'today' : '' ?> <?= $wkend ? 'weekend' : '' ?>">
                                            <?php foreach ($showEvts as $ev): ?>
                                                <?php
                                                $pClass = 'priority-' . ($ev['priority'] ?? 'normal');
                                                $tDisp = $ev['all_day'] ? '終日' : date('H:i', strtotime($ev['start_time'])) . '-' . date('H:i', strtotime($ev['end_time']));
                                                ?>
                                                <a href="<?= BASE_PATH ?>/schedule/view/<?= $ev['id'] ?>" class="home-org-item <?= $pClass ?>">
                                                    <span class="home-org-time"><?= $tDisp ?></span>
                                                    <span class="home-org-title"><?= htmlspecialchars($ev['title']) ?></span>
                                                </a>
                                            <?php endforeach; ?>
                                            <?php if ($remaining > 0): ?>
                                                <div class="home-org-more">他 <?= $remaining ?> 件</div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>

        <!-- タスク概要 ウィジェット -->
        <?php if (isset($taskSummary) && !empty($taskSummary)): ?>
        <div class="gw-widget">
            <div class="gw-widget-header">
                <div class="gw-widget-title">
                    <span class="icon"><i class="fas fa-tasks"></i></span>
                    タスク概要
                </div>
                <div class="gw-widget-actions">
                    <a href="<?php echo BASE_PATH; ?>/task" class="btn btn-sm btn-outline-secondary">タスク管理</a>
                </div>
            </div>
            <div class="gw-widget-body with-padding">
                <div class="gw-stats-row">
                    <div class="gw-stat-card">
                        <div class="gw-stat-value"><?php echo $taskSummary['total'] ?? 0; ?></div>
                        <div class="gw-stat-label">担当タスク</div>
                    </div>
                    <div class="gw-stat-card">
                        <div class="gw-stat-value" style="color:var(--success);">
                            <?php
                            $completed = 0;
                            foreach ($taskSummary['status'] ?? [] as $status) {
                                if ($status['status'] == 'completed') { $completed = $status['count']; break; }
                            }
                            echo $completed;
                            ?>
                        </div>
                        <div class="gw-stat-label">完了</div>
                    </div>
                    <div class="gw-stat-card">
                        <div class="gw-stat-value" style="color:var(--warning);"><?php echo $taskSummary['due_dates']['today'] ?? 0; ?></div>
                        <div class="gw-stat-label">今日期限</div>
                    </div>
                    <div class="gw-stat-card">
                        <div class="gw-stat-value" style="color:var(--danger);"><?php echo $taskSummary['due_dates']['overdue'] ?? 0; ?></div>
                        <div class="gw-stat-label">期限切れ</div>
                    </div>
                </div>

                <!-- ステータスバー -->
                <?php if (!empty($taskSummary['status'])): ?>
                <div style="margin-top:12px;">
                    <div class="progress" style="height:8px;border-radius:4px;">
                        <?php
                        $statusColors = ['not_started' => 'bg-secondary', 'in_progress' => 'bg-primary', 'completed' => 'bg-success', 'deferred' => 'bg-warning'];
                        foreach ($taskSummary['status'] ?? [] as $st) {
                            $pct = ($st['count'] / max(1, $taskSummary['total'])) * 100;
                            $col = $statusColors[$st['status']] ?? 'bg-info';
                            echo '<div class="progress-bar '.$col.'" style="width:'.$pct.'%"></div>';
                        }
                        ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- 近日タスク -->
                <?php if (!empty($upcomingTasks)): ?>
                <div style="margin-top:16px;">
                    <h6 style="font-size:12px;font-weight:600;color:var(--text-secondary);margin-bottom:8px;">近日中のタスク</h6>
                    <?php foreach (array_slice($upcomingTasks, 0, 3) as $task): ?>
                        <?php
                        $priorityColors = ['highest' => 'danger', 'high' => 'warning', 'normal' => 'primary', 'low' => 'info', 'lowest' => 'secondary'];
                        $pc = $priorityColors[$task['priority']] ?? 'primary';
                        ?>
                        <a href="<?php echo BASE_PATH; ?>/task/card/<?php echo $task['id']; ?>" class="gw-list-item" style="padding:8px 0;">
                            <div class="gw-list-content">
                                <div class="gw-list-title"><?php echo htmlspecialchars($task['title']); ?></div>
                                <div class="gw-list-desc">
                                    <span class="badge bg-<?php echo $pc; ?>" style="font-size:10px;"><?php echo $task['due_date'] ? date('n/j', strtotime($task['due_date'])) . ' 期限' : '期限なし'; ?></span>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <!-- ========== サイドバー ========== -->
    <div class="gw-portal-sidebar">

        <!-- 未読メッセージ -->
        <div class="gw-widget">
            <div class="gw-widget-header">
                <div class="gw-widget-title">
                    <span class="icon"><i class="far fa-envelope"></i></span>
                    メッセージ
                    <?php if (!empty($unreadMessages)): ?>
                        <span class="badge bg-danger"><?php echo count($unreadMessages); ?></span>
                    <?php endif; ?>
                </div>
                <div class="gw-widget-actions">
                    <a href="<?php echo BASE_PATH; ?>/messages/compose" class="btn btn-sm btn-primary"><i class="fas fa-pen"></i></a>
                    <a href="<?php echo BASE_PATH; ?>/messages/inbox" class="btn btn-sm btn-outline-secondary">受信箱</a>
                </div>
            </div>
            <div class="gw-widget-body">
                <?php if (empty($unreadMessages)): ?>
                    <div class="gw-empty-state">
                        <i class="far fa-envelope-open"></i>
                        未読メッセージなし
                    </div>
                <?php else: ?>
                    <?php foreach ($unreadMessages as $msg): ?>
                        <a href="<?php echo BASE_PATH; ?>/messages/view/<?php echo $msg['id']; ?>" class="gw-list-item unread">
                            <div class="gw-list-icon">
                                <i class="far fa-envelope"></i>
                            </div>
                            <div class="gw-list-content">
                                <div class="gw-list-title"><?php echo htmlspecialchars($msg['subject']); ?></div>
                                <div class="gw-list-desc"><?php echo htmlspecialchars($msg['sender_name']); ?> から</div>
                            </div>
                            <span class="gw-list-time"><?php echo date('n/j H:i', strtotime($msg['created_at'])); ?></span>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- 通知 -->
        <div class="gw-widget">
            <div class="gw-widget-header">
                <div class="gw-widget-title">
                    <span class="icon"><i class="far fa-bell"></i></span>
                    通知
                    <?php if (!empty($unreadNotifications)): ?>
                        <span class="badge bg-danger"><?php echo count($unreadNotifications); ?></span>
                    <?php endif; ?>
                </div>
                <div class="gw-widget-actions">
                    <a href="<?php echo BASE_PATH; ?>/notifications" class="btn btn-sm btn-outline-secondary">すべて</a>
                </div>
            </div>
            <div class="gw-widget-body">
                <?php if (empty($unreadNotifications)): ?>
                    <div class="gw-empty-state">
                        <i class="far fa-bell-slash"></i>
                        未読通知なし
                    </div>
                <?php else: ?>
                    <?php foreach ($unreadNotifications as $notif): ?>
                        <a href="<?php echo BASE_PATH . $notif['link']; ?>" class="gw-list-item">
                            <div class="gw-list-icon" style="background:#e6f4ea;color:var(--success);">
                                <i class="fas fa-bell"></i>
                            </div>
                            <div class="gw-list-content">
                                <div class="gw-list-title"><?php echo htmlspecialchars($notif['title']); ?></div>
                                <div class="gw-list-desc"><?php echo htmlspecialchars($notif['content']); ?></div>
                            </div>
                            <span class="gw-list-time"><?php echo date('n/j H:i', strtotime($notif['created_at'])); ?></span>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- 期限切れタスク -->
        <?php if (!empty($overdueTasks)): ?>
        <div class="gw-widget" style="border-color:var(--danger);">
            <div class="gw-widget-header" style="background:#fce8e6;">
                <div class="gw-widget-title" style="color:var(--danger);">
                    <span class="icon"><i class="fas fa-exclamation-triangle"></i></span>
                    期限切れタスク
                    <span class="badge bg-danger"><?php echo count($overdueTasks); ?></span>
                </div>
            </div>
            <div class="gw-widget-body">
                <?php foreach ($overdueTasks as $task): ?>
                    <a href="<?php echo BASE_PATH; ?>/task/card/<?php echo $task['id']; ?>" class="gw-list-item">
                        <div class="gw-list-icon" style="background:#fce8e6;color:var(--danger);">
                            <i class="fas fa-exclamation"></i>
                        </div>
                        <div class="gw-list-content">
                            <div class="gw-list-title"><?php echo htmlspecialchars($task['title']); ?></div>
                            <div class="gw-list-desc">
                                <span class="text-danger"><?php echo date('n/j', strtotime($task['due_date'])); ?> 期限</span>
                                <span class="text-muted ms-1"><?php echo htmlspecialchars($task['board_name']); ?></span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
                <div style="padding:8px 16px;text-align:center;border-top:1px solid var(--border-light);">
                    <a href="<?php echo BASE_PATH; ?>/task/my-tasks?due_date=overdue" class="btn btn-sm btn-outline-danger">すべて表示</a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- クイックリンク -->
        <div class="gw-widget">
            <div class="gw-widget-header">
                <div class="gw-widget-title">
                    <span class="icon"><i class="fas fa-link"></i></span>
                    クイックアクセス
                </div>
            </div>
            <div class="gw-widget-body with-padding">
                <div class="d-grid gap-2">
                    <a href="<?php echo BASE_PATH; ?>/schedule/create" class="btn btn-sm btn-outline-primary text-start">
                        <i class="far fa-calendar-plus me-2"></i>予定登録
                    </a>
                    <a href="<?php echo BASE_PATH; ?>/messages/compose" class="btn btn-sm btn-outline-primary text-start">
                        <i class="far fa-edit me-2"></i>メッセージ作成
                    </a>
                    <a href="<?php echo BASE_PATH; ?>/workflow/requests" class="btn btn-sm btn-outline-primary text-start">
                        <i class="fas fa-file-alt me-2"></i>ワークフロー申請
                    </a>
                    <a href="<?php echo BASE_PATH; ?>/daily-report/create" class="btn btn-sm btn-outline-primary text-start">
                        <i class="fas fa-pencil-alt me-2"></i>日報作成
                    </a>
                </div>
            </div>
        </div>

    </div>
</div>

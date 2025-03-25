<?php
// views/schedule/week.php
$pageTitle = 'スケジュール（週表示） - GroupWare';

// 週の開始日と終了日を計算
$startOfWeek = reset($weekDates);
$endOfWeek = end($weekDates);

// 週表示用のフォーマット（例: 2023年4月1日～4月7日）
$startDate = new DateTime($startOfWeek);
$endDate = new DateTime($endOfWeek);

$formattedWeek = $startDate->format('Y年n月j日') . '～';
if ($startDate->format('Y-m') === $endDate->format('Y-m')) {
    // 同じ年月の場合は年月を省略
    $formattedWeek .= $endDate->format('j日');
} else if ($startDate->format('Y') === $endDate->format('Y')) {
    // 同じ年の場合は年を省略
    $formattedWeek .= $endDate->format('n月j日');
} else {
    $formattedWeek .= $endDate->format('Y年n月j日');
}
?>
<?php include __DIR__ . '/modal.php'; ?>
<div class="container-fluid" data-page-type="week">
    <input type="hidden" id="current-date" value="<?php echo $date; ?>">
    <input type="hidden" id="user-id" value="<?php echo $userId; ?>">
    <input type="hidden" id="current-user-id" value="<?php echo $this->auth->id(); ?>">

    <div class="row mb-4 align-items-center">
        <div class="col">
            <h1 class="h3"><?php echo $formattedWeek; ?> - スケジュール管理</h1>
        </div>
        <div class="col-auto">
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-outline-secondary btn-prev-week">
                    <i class="fas fa-chevron-left"></i> 前週
                </button>
                <button type="button" class="btn btn-outline-secondary btn-this-week">
                    今週
                </button>
                <button type="button" class="btn btn-outline-secondary btn-next-week">
                    次週 <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
        <div class="col-auto">
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-outline-primary btn-view-switcher" data-view="day">
                    <i class="fas fa-calendar-day"></i> 日
                </button>
                <button type="button" class="btn btn-primary btn-view-switcher" data-view="week">
                    <i class="fas fa-calendar-week"></i> 週
                </button>
                <button type="button" class="btn btn-outline-primary btn-view-switcher" data-view="month">
                    <i class="fas fa-calendar-alt"></i> 月
                </button>
            </div>
        </div>
        <div class="col-auto">
            <button id="btn-create-schedule" class="btn btn-success" data-date="<?php echo $date; ?>">
                <i class="fas fa-plus"></i> 新規作成
            </button>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-4">
            <label for="user-selector" class="visually-hidden">ユーザー選択</label>
            <select id="user-selector" class="form-select">
                <?php if (isset($users) && is_array($users)): ?>
                    <?php foreach ($users as $u): ?>
                        <option value="<?php echo $u['id']; ?>" <?php echo $userId == $u['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($u['display_name']); ?>
                        </option>
                    <?php endforeach; ?>
                <?php else: ?>
                    <option value="<?php echo $user['id']; ?>" selected>
                        <?php echo htmlspecialchars($user['display_name']); ?>
                    </option>
                <?php endif; ?>
            </select>
        </div>
    </div>

    <div class="card">
        <div class="card-body schedule-container">
            <div id="week-schedule-container">
                <!-- スケジュールはJSで動的に生成 -->
                <div class="text-center p-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* カードボディのスクロール設定 */
    .card-body.schedule-container {
        padding: 0;
        overflow: auto;
        max-height: calc(100vh - 200px);
        position: relative;
    }

    /* 週表示用スタイル */
    .week-schedule {
        width: 100%;
        min-width: 800px;
        position: relative;
        overflow-x: auto;
    }

    .week-header {
        display: flex;
        background-color: #cfdfef;
        border-bottom: 1px solid #dee2e6;
        position: sticky;
        top: 0;
        z-index: 100;
    }

    .week-time-column {
        width: 60px;
        min-width: 60px;
        padding: 8px;
        text-align: right;
        font-weight: bold;
        color: #6c757d;
        border-right: 1px solid #dee2e6;
        position: sticky;
        left: 0;
        background-color: #f8f9fa;
        z-index: 50;
    }

    .week-header .week-time-column {
        z-index: 150;
        background-color: #cfdfef;
    }

    .week-day {
        flex: 1;
        min-width: 120px;
        padding: 8px;
        text-align: center;
        border-right: 1px solid #dee2e6;
    }

    .week-day.today {
        background-color: #fff3cd;
    }

    .week-day-name {
        font-weight: bold;
    }

    .week-day-number {
        font-size: 1.2rem;
        font-weight: bold;
    }

    .week-all-day-row,
    .week-hour-row {
        display: flex;
        min-height: 60px;
        border-bottom: 1px solid #dee2e6;
        position: relative;
    }

    .week-day-content {
        flex: 1;
        min-width: 120px;
        padding: 2px;
        border-right: 1px solid #dee2e6;
        min-height: 60px;
        position: relative;
    }

    .week-day-content.today {
        background-color: #fff3cd;
    }

    .schedule-item,
    .schedule-timespan {
        margin-bottom: 2px;
        padding: 4px;
        border-radius: 3px;
        font-size: 0.8rem;
        cursor: pointer;
        overflow: hidden;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        position: absolute;
        left: 2px;
        right: 2px;
        z-index: 10;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .schedule-item:hover,
    .schedule-timespan:hover {
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
        z-index: 20;
    }

    .schedule-item .schedule-title,
    .schedule-timespan .schedule-title {
        font-weight: bold;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        flex: 1;
    }

    .schedule-item .schedule-time,
    .schedule-timespan .schedule-time {
        font-size: 0.75rem;
        white-space: nowrap;
        text-align: right;
    }

    .priority-high {
        background-color: #f8d7da;
        border-left: 3px solid #dc3545;
    }

    .priority-normal {
        background-color: #d1e7dd;
        border-left: 3px solid #198754;
    }

    .priority-low {
        background-color: #cfe2ff;
        border-left: 3px solid #0d6efd;
    }
</style>
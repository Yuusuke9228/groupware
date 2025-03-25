<?php
// views/schedule/month.php
$pageTitle = 'スケジュール（月表示） - GroupWare';
$monthNames = ['', '1月', '2月', '3月', '4月', '5月', '6月', '7月', '8月', '9月', '10月', '11月', '12月'];
?>
<?php include __DIR__ . '/modal.php'; ?>
<div class="container-fluid" data-page-type="month">
    <input type="hidden" id="current-year" value="<?php echo $year; ?>">
    <input type="hidden" id="current-month" value="<?php echo $month; ?>">
    <input type="hidden" id="user-id" value="<?php echo $userId; ?>">
    <input type="hidden" id="current-user-id" value="<?php echo $this->auth->id(); ?>">

    <div class="row mb-4 align-items-center">
        <div class="col">
            <h1 class="h3"><?php echo $year; ?>年<?php echo $monthNames[(int)$month]; ?> - スケジュール管理</h1>
        </div>
        <div class="col-auto">
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-outline-secondary btn-prev-month">
                    <i class="fas fa-chevron-left"></i> 前月
                </button>
                <button type="button" class="btn btn-outline-secondary btn-this-month">
                    今月
                </button>
                <button type="button" class="btn btn-outline-secondary btn-next-month">
                    次月 <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
        <div class="col-auto">
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-outline-primary btn-view-switcher" data-view="day">
                    <i class="fas fa-calendar-day"></i> 日
                </button>
                <button type="button" class="btn btn-outline-primary btn-view-switcher" data-view="week">
                    <i class="fas fa-calendar-week"></i> 週
                </button>
                <button type="button" class="btn btn-primary btn-view-switcher" data-view="month">
                    <i class="fas fa-calendar-alt"></i> 月
                </button>
            </div>
        </div>
        <div class="col-auto">
            <button id="btn-create-schedule" class="btn btn-success" data-date="<?php echo $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-01'; ?>">
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
        <div class="card-body p-0">
            <div id="month-schedule-container">
                <!-- カレンダーはJSで動的に生成 -->
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
    /* 月カレンダー用スタイル */
    .month-calendar {
        display: flex;
        flex-direction: column;
        width: 100%;
    }

    .week-row {
        display: flex;
        width: 100%;
    }

    .header-row {
        background-color: #f8f9fa;
        font-weight: bold;
    }

    .day-cell {
        flex: 1;
        min-height: 120px;
        border: 1px solid #dee2e6;
        position: relative;
        overflow: hidden;
    }

    .day-name {
        text-align: center;
        padding: 8px;
        min-height: auto;
    }

    .day-number {
        position: absolute;
        top: 5px;
        left: 5px;
        width: 24px;
        height: 24px;
        text-align: center;
        font-weight: bold;
    }

    .day-content {
        padding-top: 30px;
        padding-left: 5px;
        padding-right: 5px;
        height: calc(100% - 30px);
        overflow: hidden;
    }

    .empty-cell {
        background-color: #f8f9fa;
    }

    .today {
        background-color: #fff3cd;
    }

    .weekend {
        background-color: #f8f8f8;
    }

    .calendar-day {
        cursor: pointer;
    }

    .calendar-day:hover {
        background-color: #f0f0f0;
    }

    .schedule-item {
        margin-bottom: 3px;
        padding: 2px 4px;
        border-radius: 3px;
        font-size: 0.8rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .schedule-item a {
        color: inherit;
        text-decoration: none;
        display: block;
    }

    .schedule-item.all-day {
        border-left: 3px solid #007bff;
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

    .more-schedules {
        text-align: center;
        font-size: 0.8rem;
        background-color: #f8f9fa;
        padding: 2px;
        border-radius: 3px;
        cursor: pointer;
    }

    .more-schedules:hover {
        background-color: #e9ecef;
    }
</style>
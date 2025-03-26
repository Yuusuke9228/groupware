<?php
// views/schedule/day.php
$pageTitle = 'スケジュール（日表示） - GroupWare';

// 日付フォーマット
$formattedDate = date('Y年n月j日', strtotime($date));
$dayOfWeek = ['日', '月', '火', '水', '木', '金', '土'][date('w', strtotime($date))];
?>
<?php include __DIR__ . '/modal.php'; ?>
<div class="container-fluid" data-page-type="day">
    <input type="hidden" id="current-date" value="<?php echo $date; ?>">
    <input type="hidden" id="user-id" value="<?php echo $userId; ?>">
    <input type="hidden" id="current-user-id" value="<?php echo $this->auth->id(); ?>">

    <div class="row mb-4 align-items-center">
        <div class="col">
            <h1 class="h3"><?php echo $formattedDate; ?> (<?php echo $dayOfWeek; ?>) - スケジュール管理</h1>
        </div>
        <div class="col-auto">
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-outline-secondary btn-prev-day">
                    <i class="fas fa-chevron-left"></i> 前日
                </button>
                <button type="button" class="btn btn-outline-secondary btn-today">
                    今日
                </button>
                <button type="button" class="btn btn-outline-secondary btn-next-day">
                    翌日 <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
        <div class="col-auto">
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-primary btn-view-switcher" data-view="day">
                    <i class="fas fa-calendar-day"></i> 日
                </button>
                <button type="button" class="btn btn-outline-primary btn-view-switcher" data-view="week">
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
        <div class="card-body">
            <div id="day-schedule-container">
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
    /* 日表示と週表示の共通スタイル */
    .schedule-timeline {
        display: flex;
        flex-direction: column;
    }

    .schedule-hour {
        display: flex;
        min-height: 60px;
        border-bottom: 1px solid #dee2e6;
        position: relative;
    }

    .schedule-time {
        width: 80px;
        min-width: 80px;
        padding: 8px;
        font-weight: bold;
        color: #6c757d;
        text-align: right;
        border-right: 1px solid #dee2e6;
    }

    .schedule-items {
        flex: 1;
        padding: 5px;
        min-height: 60px;
        position: relative;
        /* 位置指定のための基準 */
    }

    .schedule-item {
        margin-bottom: 5px;
        padding: 5px 8px;
        border-radius: 4px;
        cursor: pointer;
        overflow: hidden;
        z-index: 5;
    }

    .schedule-timespan {
        position: absolute;
        margin: 1px 5px;
        border-radius: 4px;
        padding: 5px 8px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.15);
        z-index: 10;
    }

    .schedule-timespan .schedule-title {
        font-weight: bold;
        margin-bottom: 4px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .schedule-timespan .schedule-time {
        font-size: 0.85em;
        text-align: left;
        width: auto;
        border-right: none;
        padding: 0;
    }

    .schedule-item:hover,
    .schedule-timespan:hover {
        opacity: 0.9;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
    }

    .empty-slot {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 1;
        cursor: pointer;
    }

    .empty-slot:hover {
        background-color: rgba(248, 249, 250, 0.5);
    }

    /* 週表示特有のスタイル */
    .week-schedule {
        width: 100%;
        overflow-x: auto;
    }

    .week-header {
        display: flex;
        background-color: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
        position: sticky;
        top: 0;
        z-index: 10;
    }

    .week-time-column {
        width: 80px;
        min-width: 80px;
        padding: 8px;
        text-align: right;
        font-weight: bold;
        color: #6c757d;
        border-right: 1px solid #dee2e6;
    }

    .week-day {
        flex: 1;
        min-width: 120px;
        padding: 8px;
        text-align: center;
        border-right: 1px solid #dee2e6;
    }

    .week-day.today,
    .week-day-content.today {
        background-color: #fff3cd;
    }

    .week-day-name {
        font-weight: bold;
    }

    .week-day-number {
        font-size: 1.2rem;
        font-weight: bold;
    }

    .week-all-day-row {
        display: flex;
        min-height: 60px;
        border-bottom: 1px solid #dee2e6;
    }

    .week-hour-row {
        display: flex;
        min-height: 60px;
        border-bottom: 1px solid #dee2e6;
    }

    .week-day-content {
        flex: 1;
        min-width: 120px;
        padding: 5px;
        border-right: 1px solid #dee2e6;
        min-height: 60px;
        position: relative;
    }

    /* 優先度カラー */
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

    /* 月表示特有のスタイル */
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

    .more-schedules {
        text-align: center;
        font-size: 0.8rem;
        background-color: #f8f9fa;
        padding: 2px;
        border-radius: 3px;
        cursor: pointer;
    }

    /* 日表示のスケジュール表示スタイル更新 */
    .schedule-timespan {
        position: absolute;
        border-radius: 4px;
        padding: 5px 8px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.15);
        z-index: 10;
        display: flex;
        flex-direction: column;
    }

    .schedule-timespan .schedule-creator {
        font-size: 0.75rem;
        font-weight: 600;
        color: #495057;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        margin-bottom: 1px;
    }

    .schedule-timespan .schedule-time {
        font-size: 0.75rem;
        color: #6c757d;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        text-align: left;
        width: auto;
        border-right: none;
        padding: 0;
        margin-bottom: 1px;
    }

    .schedule-timespan .schedule-title {
        font-weight: bold;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* スケジュールが小さいときのスタイル調整 */
    .schedule-timespan[style*="height: 30px"] .schedule-creator,
    .schedule-timespan[style*="height: 30px"] .schedule-time {
        display: none;
    }

    .schedule-timespan[style*="height: 45px"] .schedule-time {
        display: none;
    }

    /* ホバー時のスタイル */
    .schedule-timespan:hover {
        opacity: 0.9;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        z-index: 20;
    }

    /* レスポンシブ対応 */
    @media (max-width: 768px) {
        .schedule-timespan {
            padding: 3px 5px;
        }

        .schedule-timespan .schedule-creator,
        .schedule-timespan .schedule-time {
            font-size: 0.65rem;
        }

        .schedule-timespan .schedule-title {
            font-size: 0.75rem;
        }

        /* 小さい画面での小さいスケジュール */
        .schedule-timespan[style*="height: 60px"] .schedule-creator {
            display: none;
        }
    }

    /* スケジュール表示用のスタイル調整 */
    .schedule-timespan {
        position: absolute;
        border-radius: 4px;
        padding: 5px 8px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.15);
        z-index: 10;
        display: flex;
        flex-direction: column;
        margin: 0 2px;
        /* 隣接するスケジュール間の間隔 */
    }

    .schedule-timespan .schedule-creator {
        font-size: 0.75rem;
        font-weight: 600;
        color: #495057;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        margin-bottom: 1px;
    }

    .schedule-timespan .schedule-time {
        font-size: 0.75rem;
        color: #6c757d;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        text-align: left;
        width: auto;
        border-right: none;
        padding: 0;
        margin-bottom: 1px;
    }

    .schedule-timespan .schedule-title {
        font-weight: bold;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* スケジュールの高さに応じた表示調整 */
    .schedule-timespan[style*="height: 30px"],
    .schedule-timespan[style*="height: 2"] {
        padding: 2px 4px;
    }

    .schedule-timespan[style*="height: 30px"] .schedule-creator,
    .schedule-timespan[style*="height: 2"] .schedule-creator,
    .schedule-timespan[style*="height: 30px"] .schedule-time,
    .schedule-timespan[style*="height: 2"] .schedule-time {
        display: none;
    }

    .schedule-timespan[style*="height: 45px"] .schedule-time,
    .schedule-timespan[style*="height: 4"] .schedule-time {
        display: none;
    }

    /* ホバー時のスタイル */
    .schedule-timespan:hover {
        opacity: 0.9;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        z-index: 20;
    }

    /* タイムラインの調整 */
    .schedule-timeline {
        display: flex;
        flex-direction: column;
        overflow-x: hidden;
        /* 横スクロールを防止 */
    }

    .schedule-hour {
        display: flex;
        min-height: 60px;
        border-bottom: 1px solid #dee2e6;
        position: relative;
    }

    .schedule-time {
        width: 80px;
        min-width: 80px;
        padding: 8px;
        font-weight: bold;
        color: #6c757d;
        text-align: right;
        border-right: 1px solid #dee2e6;
    }

    .schedule-items {
        flex: 1;
        padding: 5px;
        min-height: 60px;
        position: relative;
        overflow: visible;
        /* 重要: スケジュールが隠れないように */
    }

    /* レスポンシブ対応 */
    @media (max-width: 768px) {
        .schedule-timespan {
            padding: 3px 5px;
        }

        .schedule-timespan .schedule-creator,
        .schedule-timespan .schedule-time {
            font-size: 0.65rem;
        }

        .schedule-timespan .schedule-title {
            font-size: 0.75rem;
        }
    }
</style>
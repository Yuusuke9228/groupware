<?php
// views/schedule/week.php
$pageTitle = 'スケジュール（週表示）';
$startOfWeek = reset($weekDates);
$endOfWeek = end($weekDates);
$startDate = new DateTime($startOfWeek);
$endDate = new DateTime($endOfWeek);
$formattedWeek = $startDate->format('Y年n月j日') . '～';
if ($startDate->format('Y-m') === $endDate->format('Y-m')) {
    $formattedWeek .= $endDate->format('j日');
} else if ($startDate->format('Y') === $endDate->format('Y')) {
    $formattedWeek .= $endDate->format('n月j日');
} else {
    $formattedWeek .= $endDate->format('Y年n月j日');
}

$scheduleMode = 'personal';
$scheduleView = 'week';
$toolbarDate = $date;
$toolbarYear = date('Y', strtotime($date));
$toolbarMonth = date('n', strtotime($date));
$toolbarOrgId = null;
$toolbarTitle = $formattedWeek;
?>
<?php include __DIR__ . '/modal.php'; ?>
<div class="container-fluid" data-page-type="week">
    <input type="hidden" id="current-date" value="<?php echo $date; ?>">
    <input type="hidden" id="user-id" value="<?php echo $userId; ?>">
    <input type="hidden" id="current-user-id" value="<?php echo $this->auth->id(); ?>">
    <input type="hidden" id="schedule-display-start-time" value="<?php echo htmlspecialchars($displaySettings['start_time'] ?? '00:00'); ?>">
    <input type="hidden" id="schedule-display-end-time" value="<?php echo htmlspecialchars($displaySettings['end_time'] ?? '23:00'); ?>">

    <?php include __DIR__ . '/_toolbar.php'; ?>

    <div class="row mb-3">
        <div class="col-md-4">
            <select id="user-selector" class="form-select form-select-sm">
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
    .card-body.schedule-container { padding: 0; overflow: auto; max-height: calc(100vh - 200px); position: relative; }
    .week-schedule { width: 100%; min-width: 800px; position: relative; overflow-x: auto; }
    .week-header { display: flex; background-color: #cfdfef; border-bottom: 1px solid #dee2e6; position: sticky; top: 0; z-index: 100; }
    .week-time-column { width: 60px; min-width: 60px; padding: 8px; text-align: right; font-weight: bold; color: #6c757d; border-right: 1px solid #dee2e6; position: sticky; left: 0; background-color: #f8f9fa; z-index: 50; }
    .week-header .week-time-column { z-index: 150; background-color: #cfdfef; }
    .week-day { flex: 1; min-width: 120px; padding: 8px; text-align: center; border-right: 1px solid #dee2e6; }
    .week-day.today { background-color: #fff3cd; }
    .week-day.weekend { background-color: #f8f9fa; }
    .week-day-name { font-weight: bold; }
    .week-day-number { font-size: 1.2rem; font-weight: bold; }
    .week-all-day-row, .week-hour-row { display: flex; min-height: 60px; border-bottom: 1px solid #dee2e6; position: relative; }
    .week-day-content { flex: 1; min-width: 120px; padding: 2px; border-right: 1px solid #dee2e6; min-height: 60px; position: relative; }
    .week-day-content.today { background-color: #fff3cd; }
    .week-day-content.weekend { background-color: #f8f9fa; }
    .schedule-item, .schedule-timespan { margin-bottom: 2px; padding: 4px; border-radius: 3px; font-size: 0.8rem; cursor: pointer; overflow: hidden; box-shadow: 0 1px 2px rgba(0,0,0,0.1); position: relative; z-index: 10; display: flex; flex-direction: column; justify-content: space-between; }
    .schedule-timespan { position: absolute; left: 2px; right: 2px; z-index: 10; }
    .schedule-item:hover, .schedule-timespan:hover { box-shadow: 0 2px 5px rgba(0,0,0,0.3); z-index: 20; }
    .schedule-item .schedule-title, .schedule-timespan .schedule-title { font-weight: bold; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; flex: 1; }
    .schedule-item .schedule-time, .schedule-timespan .schedule-time { font-size: 0.75rem; white-space: nowrap; text-align: right; }
    .priority-high { background-color: #f8d7da; border-left: 3px solid #dc3545; }
    .priority-normal { background-color: #d1e7dd; border-left: 3px solid #198754; }
    .priority-low { background-color: #cfe2ff; border-left: 3px solid #0d6efd; }
    .more-schedules { font-size: 0.75rem; text-align: center; padding: 2px; margin-top: 2px; background-color: #eee; border-radius: 3px; cursor: pointer; }
    .more-schedules:hover { background-color: #ddd; }
    @media (max-width: 768px) {
        .container-fluid[data-page-type="week"] { padding-left: 0.35rem; padding-right: 0.35rem; }
        .card-body.schedule-container {
            max-height: none;
            overflow-x: auto;
            overflow-y: visible;
            -webkit-overflow-scrolling: touch;
        }
        .week-schedule { min-width: 760px; }
        .week-header { top: 0; z-index: 120; }
        .week-time-column {
            width: 54px;
            min-width: 54px;
            padding: 6px 4px;
            font-size: 0.68rem;
            background-color: #fff;
            z-index: 80;
            box-shadow: 1px 0 0 #dee2e6;
        }
        .week-header .week-time-column { background-color: #cfdfef; z-index: 140; }
        .week-day,
        .week-day-content { min-width: 100px; }
        .week-day { padding: 6px 4px; }
        .week-day-number { font-size: 1rem; }
        .week-all-day-row,
        .week-hour-row { min-height: 54px; }
        .week-day-content { padding: 1px; overflow: hidden; }
        .schedule-item,
        .schedule-timespan {
            font-size: 0.7rem;
            padding: 3px 4px;
            touch-action: manipulation;
        }
        .schedule-item .schedule-creator,
        .schedule-timespan .schedule-creator { display: none; }
        .schedule-item .schedule-time,
        .schedule-timespan .schedule-time {
            font-size: 0.64rem;
            text-align: left;
        }
    }
</style>

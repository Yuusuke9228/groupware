<?php
// views/schedule/day.php
$pageTitle = 'スケジュール（日表示）';
$formattedDate = date('Y年n月j日', strtotime($date));
$dayOfWeek = ['日', '月', '火', '水', '木', '金', '土'][date('w', strtotime($date))];

// ツールバー変数
$scheduleMode = 'personal';
$scheduleView = 'day';
$toolbarDate = $date;
$toolbarYear = date('Y', strtotime($date));
$toolbarMonth = date('n', strtotime($date));
$toolbarOrgId = null;
$toolbarTitle = $formattedDate . ' (' . $dayOfWeek . ')';
?>
<?php include __DIR__ . '/modal.php'; ?>
<div class="container-fluid" data-page-type="day">
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
        <div class="card-body">
            <div id="day-schedule-container">
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
    .schedule-timeline { display: flex; flex-direction: column; }
    .schedule-hour { display: flex; min-height: 60px; border-bottom: 1px solid #dee2e6; position: relative; }
    .schedule-time { width: 80px; min-width: 80px; padding: 8px; font-weight: bold; color: #6c757d; text-align: right; border-right: 1px solid #dee2e6; }
    .schedule-items { flex: 1; padding: 5px; min-height: 60px; position: relative; overflow: visible; }
    .schedule-item { margin-bottom: 5px; padding: 5px 8px; border-radius: 4px; cursor: pointer; overflow: hidden; z-index: 5; }
    .schedule-timespan { position: absolute; border-radius: 4px; padding: 5px 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.15); z-index: 10; display: flex; flex-direction: column; margin: 0 2px; }
    .schedule-timespan .schedule-creator { font-size: 0.75rem; font-weight: 600; color: #495057; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 1px; }
    .schedule-timespan .schedule-time { font-size: 0.75rem; color: #6c757d; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; text-align: left; width: auto; border-right: none; padding: 0; margin-bottom: 1px; }
    .schedule-timespan .schedule-title { font-weight: bold; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .schedule-item:hover, .schedule-timespan:hover { opacity: 0.9; box-shadow: 0 2px 5px rgba(0,0,0,0.2); z-index: 20; }
    .empty-slot { position: absolute; top: 0; left: 0; right: 0; bottom: 0; z-index: 1; cursor: pointer; }
    .empty-slot:hover { background-color: rgba(248,249,250,0.5); }
    .priority-high { background-color: #f8d7da; border-left: 3px solid #dc3545; }
    .priority-normal { background-color: #d1e7dd; border-left: 3px solid #198754; }
    .priority-low { background-color: #cfe2ff; border-left: 3px solid #0d6efd; }
</style>

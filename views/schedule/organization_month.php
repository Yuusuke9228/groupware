<?php
// views/schedule/organization_month.php
$pageTitle = '組織スケジュール（月表示）';
$monthNames = ['', '1月', '2月', '3月', '4月', '5月', '6月', '7月', '8月', '9月', '10月', '11月', '12月'];

$scheduleMode = 'organization';
$scheduleView = 'month';
$toolbarDate = $date;
$toolbarYear = $year;
$toolbarMonth = $month;
$toolbarOrgId = $organizationId;
$toolbarTitle = $year . '年' . $monthNames[(int)$month];
?>
<?php include __DIR__ . '/modal.php'; ?>
<div class="container-fluid" data-page-type="organization-month">
    <input type="hidden" id="current-year" value="<?php echo $year; ?>">
    <input type="hidden" id="current-month" value="<?php echo $month; ?>">
    <input type="hidden" id="current-date" value="<?php echo $date; ?>">
    <input type="hidden" id="organization-id" value="<?php echo $organizationId; ?>">
    <input type="hidden" id="current-user-id" value="<?php echo $this->auth->id(); ?>">

    <?php include __DIR__ . '/_toolbar.php'; ?>

    <div class="row mb-3">
        <div class="col-md-4 col-sm-6">
            <select id="organization-selector" class="form-select form-select-sm">
                <option value="">組織を選択</option>
                <?php if (isset($organizations) && is_array($organizations)): ?>
                    <?php foreach ($organizations as $org): ?>
                        <option value="<?php echo $org['id']; ?>" <?php echo $organizationId == $org['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($org['name']); ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div id="organization-month-schedule-container">
                <?php if (!$organizationId): ?>
                    <div class="gw-empty-state" style="padding:40px;">
                        <i class="fas fa-sitemap"></i>
                        組織を選択してください
                    </div>
                <?php else: ?>
                    <div class="text-center p-5">
                        <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
    .month-calendar { display: flex; flex-direction: column; width: 100%; }
    .week-row { display: flex; width: 100%; }
    .header-row { background: linear-gradient(180deg, #f8fbff 0%, #edf4ff 100%); font-weight: 700; position: sticky; top: 0; z-index: 6; }
    .day-cell { flex: 1; min-height: 128px; border: 1px solid #dce5f3; position: relative; overflow: hidden; background: #fff; }
    .day-name { text-align: center; padding: 8px; }
    .day-number { position: absolute; top: 5px; left: 5px; width: 24px; height: 24px; text-align: center; font-weight: 700; border-radius: 999px; line-height: 24px; background: rgba(255, 255, 255, 0.95); box-shadow: 0 1px 2px rgba(15, 23, 42, 0.12); }
    .day-content { padding-top: 30px; padding-left: 5px; padding-right: 5px; }
    .empty-cell { background-color: #f8f9fa; }
    .today { background-color: #fff8dc; }
    .weekend { background-color: #f8f8f8; }
    .calendar-day { cursor: pointer; }
    .org-schedule-item { margin-bottom: 3px; padding: 2px 4px; border-radius: 3px; font-size: 0.76rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; cursor: pointer; }
    .priority-high { background-color: #f8d7da; border-left: 3px solid #dc3545; }
    .priority-normal { background-color: #d1e7dd; border-left: 3px solid #198754; }
    .priority-low { background-color: #cfe2ff; border-left: 3px solid #0d6efd; }
    .more-schedules { text-align: center; font-size: 0.75rem; background-color: #f1f6ff; padding: 2px; border-radius: 999px; cursor: pointer; border: 1px solid #dbe7fb; color: #27569f; }
    .container-fluid[data-page-type="organization-month"] .card { border-radius: 12px; overflow: hidden; box-shadow: 0 2px 10px rgba(15, 23, 42, 0.06); }
    @media (max-width: 768px) {
        .container-fluid[data-page-type="organization-month"] { padding-left: 0.35rem; padding-right: 0.35rem; }
        .container-fluid[data-page-type="organization-month"] .card {
            border-radius: 0;
            margin-left: calc(50% - 50vw);
            margin-right: calc(50% - 50vw);
            width: 100vw;
        }
        .day-name { font-size: 0.72rem; padding: 5px 2px; }
        .day-cell { min-height: 96px; padding: 2px; }
        .day-number {
            top: 3px;
            left: 3px;
            width: 20px;
            height: 20px;
            line-height: 20px;
            border-radius: 999px;
            font-size: 0.7rem;
            background: rgba(255, 255, 255, 0.92);
        }
        .day-content { padding-top: 25px; padding-left: 3px; padding-right: 3px; }
        .org-schedule-item {
            font-size: 0.68rem;
            padding: 2px 4px;
            line-height: 1.25;
            touch-action: manipulation;
        }
        .more-schedules { font-size: 0.68rem; padding: 2px 3px; touch-action: manipulation; }
    }
</style>

<?php
// views/schedule/organization_month.php
$pageTitle = '組織スケジュール（月表示） - TeamSpace';
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
    .header-row { background-color: #f8f9fa; font-weight: bold; }
    .day-cell { flex: 1; min-height: 128px; border: 1px solid #dee2e6; position: relative; overflow: hidden; }
    .day-name { text-align: center; padding: 8px; }
    .day-number { position: absolute; top: 5px; left: 5px; width: 24px; height: 24px; text-align: center; font-weight: bold; }
    .day-content { padding-top: 30px; padding-left: 5px; padding-right: 5px; }
    .empty-cell { background-color: #f8f9fa; }
    .today { background-color: #fff3cd; }
    .weekend { background-color: #f8f8f8; }
    .calendar-day { cursor: pointer; }
    .org-schedule-item { margin-bottom: 3px; padding: 2px 4px; border-radius: 3px; font-size: 0.76rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; cursor: pointer; }
    .priority-high { background-color: #f8d7da; border-left: 3px solid #dc3545; }
    .priority-normal { background-color: #d1e7dd; border-left: 3px solid #198754; }
    .priority-low { background-color: #cfe2ff; border-left: 3px solid #0d6efd; }
    .more-schedules { text-align: center; font-size: 0.75rem; background-color: #f8f9fa; padding: 2px; border-radius: 3px; cursor: pointer; }
</style>

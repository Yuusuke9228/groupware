<?php
// views/schedule/organization_day.php
$pageTitle = '組織スケジュール（日表示） - TeamSpace';
$formattedDate = date('Y年n月j日', strtotime($date));
$dayOfWeek = ['日', '月', '火', '水', '木', '金', '土'][date('w', strtotime($date))];

$scheduleMode = 'organization';
$scheduleView = 'day';
$toolbarDate = $date;
$toolbarYear = date('Y', strtotime($date));
$toolbarMonth = date('n', strtotime($date));
$toolbarOrgId = $organizationId;
$toolbarTitle = $formattedDate . ' (' . $dayOfWeek . ')';
?>
<?php include __DIR__ . '/modal.php'; ?>
<div class="container-fluid" data-page-type="organization-day">
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
        <div class="card-body schedule-container">
            <div id="organization-day-schedule-container">
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
    .card-body.schedule-container { padding: 0; overflow: auto; max-height: calc(100vh - 250px); }
    .org-day-row { display: flex; border-bottom: 1px solid #dee2e6; min-height: 76px; }
    .org-day-user { width: 180px; min-width: 180px; max-width: 180px; padding: 10px; background: #f8f9fa; border-right: 1px solid #dee2e6; position: sticky; left: 0; z-index: 10; font-weight: bold; }
    .org-day-schedules { flex: 1; padding: 8px; min-height: 76px; }
    .org-day-empty { color: #6c757d; font-size: 0.9rem; padding-top: 4px; }
    .org-schedule-item { padding: 3px 6px; margin-bottom: 4px; border-radius: 3px; font-size: 0.82rem; cursor: pointer; }
    .priority-high { background-color: #f8d7da; border-left: 3px solid #dc3545; }
    .priority-normal { background-color: #d1e7dd; border-left: 3px solid #198754; }
    .priority-low { background-color: #cfe2ff; border-left: 3px solid #0d6efd; }
</style>

<?php
// views/schedule/organization_week.php
$pageTitle = '組織スケジュール（週表示）';
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

$scheduleMode = 'organization';
$scheduleView = 'week';
$toolbarDate = $date;
$toolbarYear = date('Y', strtotime($date));
$toolbarMonth = date('n', strtotime($date));
$toolbarOrgId = $organizationId;
$toolbarTitle = $formattedWeek;
?>
<?php include __DIR__ . '/modal.php'; ?>
<div class="container-fluid" data-page-type="organization-week">
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
            <div id="organization-week-schedule-container">
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
    .card-body.schedule-container { padding: 0; overflow: auto; max-height: calc(100vh - 220px); position: relative; }
    .org-timeline { width: 100%; min-width: 900px; position: relative; }
    .org-timeline-header { display: flex; background: linear-gradient(to bottom, #e8f0fe, #dce6f5); border-bottom: 2px solid var(--border-color); position: sticky; top: 0; z-index: 100; }
    .org-timeline-header-cell { flex: 1; min-width: 120px; text-align: center; padding: 10px 8px; border-right: 1px solid rgba(0,0,0,0.08); font-weight: 600; font-size: 13px; }
    .org-timeline-header-cell.user-column { width: 140px; min-width: 140px; max-width: 140px; position: sticky; left: 0; z-index: 150; background: linear-gradient(to bottom, #e8f0fe, #dce6f5); font-size: 12px; }
    .org-timeline-header-cell.today { background: var(--primary) !important; color: #fff; }
    .org-timeline-header-cell.weekend { background: #f5f5f5; }
    .org-timeline-day { font-size: 11px; opacity: 0.8; }
    .org-timeline-date { font-size: 16px; font-weight: 700; }
    .org-timeline-body { position: relative; }
    .org-timeline-row { display: flex; border-bottom: 1px solid var(--border-light); transition: background 0.15s; }
    .org-timeline-row:hover { background: rgba(43,125,233,0.02); }
    .org-timeline-user-cell { width: 140px; min-width: 140px; max-width: 140px; padding: 8px 10px; border-right: 1px solid var(--border-color); background: var(--bg-sidebar); position: sticky; left: 0; z-index: 50; display: flex; flex-direction: column; justify-content: center; }
    .org-timeline-user-name { font-weight: 600; font-size: 12px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: var(--text-primary); }
    .org-timeline-day-cell { flex: 1; min-width: 120px; padding: 4px; border-right: 1px solid var(--border-light); position: relative; min-height: 64px; }
    .org-timeline-day-cell.today { background: rgba(43,125,233,0.04); }
    .org-timeline-day-cell.weekend { background: #fafafa; }
    .org-schedule-item { padding: 3px 6px; margin-bottom: 2px; border-radius: 4px; font-size: 11px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; cursor: pointer; position: relative; z-index: 10; transition: box-shadow 0.15s, transform 0.1s; }
    .org-schedule-item:hover { box-shadow: var(--shadow-md); transform: translateY(-1px); z-index: 20; }
    .org-schedule-time { font-size: 10px; color: var(--text-muted); font-weight: 500; }
    .org-schedule-title { font-weight: 600; font-size: 11px; }
    .priority-high { background-color: #f8d7da; border-left: 3px solid #dc3545; }
    .priority-normal { background-color: #d1e7dd; border-left: 3px solid #198754; }
    .priority-low { background-color: #cfe2ff; border-left: 3px solid #0d6efd; }
    .more-schedules { text-align: center; font-size: 10px; background: var(--primary-light); color: var(--primary); padding: 2px 4px; border-radius: 3px; cursor: pointer; margin-top: 2px; font-weight: 500; }
    .more-schedules:hover { background: var(--primary); color: #fff; }
    @media (max-width: 768px) {
        .container-fluid[data-page-type="organization-week"] { padding-left: 0.35rem; padding-right: 0.35rem; }
        .card-body.schedule-container {
            max-height: none;
            overflow-x: auto;
            overflow-y: visible;
            -webkit-overflow-scrolling: touch;
        }
        .org-timeline { min-width: 760px; }
        .org-timeline-header { top: 0; z-index: 120; }
        .org-timeline-header-cell.user-column,
        .org-timeline-user-cell {
            width: 92px;
            min-width: 92px;
            max-width: 92px;
            font-size: 10px;
            z-index: 130;
        }
        .org-timeline-header-cell { min-width: 95px; padding: 6px 4px; font-size: 10px; }
        .org-timeline-day-cell { min-width: 95px; min-height: 56px; padding: 2px; overflow: hidden; }
        .org-schedule-item {
            font-size: 0.66rem;
            padding: 2px 4px;
            touch-action: manipulation;
        }
        .org-schedule-time { font-size: 0.6rem; }
        .org-schedule-title { font-size: 0.66rem; }
        .more-schedules { font-size: 0.64rem; }
    }
</style>

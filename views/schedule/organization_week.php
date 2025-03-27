<?php
// views/schedule/organization_week.php
$pageTitle = '組織スケジュール（週表示） - GroupWare';

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
<div class="container-fluid" data-page-type="organization-week">
    <input type="hidden" id="current-date" value="<?php echo $date; ?>">
    <input type="hidden" id="organization-id" value="<?php echo $organizationId; ?>">
    <input type="hidden" id="current-user-id" value="<?php echo $this->auth->id(); ?>">

    <div class="row mb-4 align-items-center">
        <div class="col">
            <h1 class="h3"><?php echo $formattedWeek; ?> - 組織スケジュール</h1>
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
                <a href="<?php echo BASE_PATH; ?>/schedule/day?date=<?php echo $date; ?>" class="btn btn-outline-primary">
                    <i class="fas fa-calendar-day"></i> 日
                </a>
                <a href="<?php echo BASE_PATH; ?>/schedule/week?date=<?php echo $date; ?>" class="btn btn-outline-primary">
                    <i class="fas fa-calendar-week"></i> 週
                </a>
                <a href="<?php echo BASE_PATH; ?>/schedule/month?year=<?php echo date('Y', strtotime($date)); ?>&month=<?php echo date('m', strtotime($date)); ?>" class="btn btn-outline-primary">
                    <i class="fas fa-calendar-alt"></i> 月
                </a>
                <a href="<?php echo BASE_PATH; ?>/schedule/organization-week?date=<?php echo $date; ?>&organization_id=<?php echo $organizationId; ?>" class="btn btn-primary">
                    <i class="far fa-building"></i> 組織
                </a>
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
            <label for="organization-selector" class="form-label">組織選択</label>
            <select id="organization-selector" class="form-select">
                <option value="">組織を選択してください</option>
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
                    <div class="alert alert-info">
                        組織を選択してください。
                    </div>
                <?php else: ?>
                    <!-- スケジュールはJSで動的に生成 -->
                    <div class="text-center p-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
    /* カードボディのスクロール設定 */
    .card-body.schedule-container {
        padding: 0;
        overflow: auto;
        max-height: calc(100vh - 250px);
        position: relative;
    }

    /* 組織スケジュール表示用のスタイル */
    .org-timeline {
        width: 100%;
        min-width: 900px;
        position: relative;
    }

    .org-timeline-header {
        display: flex;
        background-color: #cfdfef;
        border-bottom: 1px solid #dee2e6;
        position: sticky;
        top: 0;
        z-index: 100;
    }

    .org-timeline-header-cell {
        flex: 1;
        min-width: 120px;
        text-align: center;
        padding: 10px;
        border-right: 1px solid #dee2e6;
        font-weight: bold;
    }

    .org-timeline-header-cell.user-column {
        width: 150px;
        min-width: 150px;
        max-width: 150px;
        position: sticky;
        left: 0;
        z-index: 150;
        background-color: #cfdfef;
    }

    .org-timeline-header-cell.today {
        background-color: #fff3cd;
    }

    .org-timeline-header-cell.weekend {
        background-color: #f0f0f0;
    }

    .org-timeline-day {
        font-size: 0.9rem;
    }

    .org-timeline-date {
        font-size: 1.2rem;
    }

    .org-timeline-body {
        position: relative;
    }

    .org-timeline-row {
        display: flex;
        border-bottom: 1px solid #dee2e6;
    }

    .org-timeline-user-cell {
        width: 150px;
        min-width: 150px;
        max-width: 150px;
        padding: 8px;
        border-right: 1px solid #dee2e6;
        background-color: #f8f9fa;
        position: sticky;
        left: 0;
        z-index: 50;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .org-timeline-user-name {
        font-weight: bold;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .org-timeline-day-cell {
        flex: 1;
        min-width: 120px;
        padding: 5px;
        border-right: 1px solid #dee2e6;
        position: relative;
        min-height: 80px;
    }

    .org-timeline-day-cell.today {
        background-color: #fff3cd;
    }

    .org-timeline-day-cell.weekend {
        background-color: #f8f9fa;
    }

    .org-schedule-item {
        padding: 3px 5px;
        margin-bottom: 2px;
        border-radius: 3px;
        font-size: 0.8rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        cursor: pointer;
        position: relative;
        z-index: 10;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    }

    .org-schedule-item:hover {
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        z-index: 20;
    }

    .org-schedule-time {
        font-size: 0.7rem;
        color: #555;
    }

    .org-schedule-title {
        font-weight: bold;
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
        font-size: 0.75rem;
        background-color: #f8f9fa;
        padding: 2px;
        border-radius: 3px;
        cursor: pointer;
        margin-top: 2px;
    }

    .more-schedules:hover {
        background-color: #e9ecef;
    }
</style>
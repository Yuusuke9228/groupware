<?php
/**
 * スケジュール共通ツールバー
 *
 * 必要な変数:
 *   $scheduleMode  - 'personal' or 'organization'
 *   $scheduleView  - 'day', 'week', 'month'
 *   $toolbarDate   - 現在の日付 (Y-m-d)
 *   $toolbarYear   - 年 (月表示用)
 *   $toolbarMonth  - 月 (月表示用)
 *   $toolbarOrgId  - 組織ID (組織表示用, nullable)
 *   $toolbarTitle  - 表示タイトル
 */

$isPersonal = ($scheduleMode === 'personal');
$isOrg = ($scheduleMode === 'organization');
$orgParam = $isOrg && $toolbarOrgId ? '&organization_id=' . $toolbarOrgId : '';

// 各ビューURL生成
if ($isPersonal) {
    $dayUrl = BASE_PATH . '/schedule/day?date=' . $toolbarDate;
    $weekUrl = BASE_PATH . '/schedule/week?date=' . $toolbarDate;
    $monthUrl = BASE_PATH . '/schedule/month?year=' . $toolbarYear . '&month=' . $toolbarMonth;
} else {
    $dayUrl = BASE_PATH . '/schedule/organization-day?date=' . $toolbarDate . $orgParam;
    $weekUrl = BASE_PATH . '/schedule/organization-week?date=' . $toolbarDate . $orgParam;
    $monthUrl = BASE_PATH . '/schedule/organization-month?year=' . $toolbarYear . '&month=' . $toolbarMonth . $orgParam;
}
?>
<div class="schedule-toolbar">
    <div class="schedule-toolbar-left">
        <h1 class="schedule-toolbar-title"><?php echo $toolbarTitle; ?></h1>
    </div>
    <div class="schedule-toolbar-center">
        <!-- モード切替: 個人 / 組織 -->
        <div class="btn-group schedule-mode-switch" role="group">
            <a href="<?php echo BASE_PATH; ?>/schedule/<?php echo $scheduleView; ?>?date=<?php echo $toolbarDate; ?><?php echo $scheduleView === 'month' ? '&year=' . $toolbarYear . '&month=' . $toolbarMonth : ''; ?>"
               class="btn btn-sm <?php echo $isPersonal ? 'btn-primary' : 'btn-outline-primary'; ?>">
                <i class="fas fa-user"></i> 個人
            </a>
            <a href="<?php echo BASE_PATH; ?>/schedule/organization-<?php echo $scheduleView; ?>?date=<?php echo $toolbarDate; ?><?php echo $scheduleView === 'month' ? '&year=' . $toolbarYear . '&month=' . $toolbarMonth : ''; ?><?php echo $orgParam; ?>"
               class="btn btn-sm <?php echo $isOrg ? 'btn-primary' : 'btn-outline-primary'; ?>">
                <i class="fas fa-users"></i> 組織
            </a>
        </div>

        <!-- 日/週/月切替 -->
        <div class="btn-group schedule-view-switch" role="group">
            <a href="<?php echo $dayUrl; ?>" class="btn btn-sm <?php echo $scheduleView === 'day' ? 'btn-secondary' : 'btn-outline-secondary'; ?>">日</a>
            <a href="<?php echo $weekUrl; ?>" class="btn btn-sm <?php echo $scheduleView === 'week' ? 'btn-secondary' : 'btn-outline-secondary'; ?>">週</a>
            <a href="<?php echo $monthUrl; ?>" class="btn btn-sm <?php echo $scheduleView === 'month' ? 'btn-secondary' : 'btn-outline-secondary'; ?>">月</a>
        </div>

        <!-- 前/次ナビ -->
        <div class="btn-group schedule-nav-switch" role="group">
            <?php if ($scheduleView === 'day'): ?>
                <button type="button" class="btn btn-sm btn-outline-secondary btn-prev-day"><i class="fas fa-chevron-left"></i></button>
                <button type="button" class="btn btn-sm btn-outline-secondary btn-today">今日</button>
                <button type="button" class="btn btn-sm btn-outline-secondary btn-next-day"><i class="fas fa-chevron-right"></i></button>
            <?php elseif ($scheduleView === 'week'): ?>
                <button type="button" class="btn btn-sm btn-outline-secondary btn-prev-week"><i class="fas fa-chevron-left"></i></button>
                <button type="button" class="btn btn-sm btn-outline-secondary btn-this-week">今週</button>
                <button type="button" class="btn btn-sm btn-outline-secondary btn-next-week"><i class="fas fa-chevron-right"></i></button>
            <?php elseif ($scheduleView === 'month'): ?>
                <button type="button" class="btn btn-sm btn-outline-secondary btn-prev-month"><i class="fas fa-chevron-left"></i></button>
                <button type="button" class="btn btn-sm btn-outline-secondary btn-this-month">今月</button>
                <button type="button" class="btn btn-sm btn-outline-secondary btn-next-month"><i class="fas fa-chevron-right"></i></button>
            <?php endif; ?>
        </div>
    </div>
    <div class="schedule-toolbar-right">
        <button id="btn-create-schedule" class="btn btn-sm btn-success" data-date="<?php echo $toolbarDate; ?>">
            <i class="fas fa-plus"></i> <span class="d-none d-md-inline">新規作成</span>
        </button>
    </div>
</div>

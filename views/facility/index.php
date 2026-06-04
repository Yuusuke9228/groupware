<?php
$pageTitle = tr_text('施設予約', 'Facility reservations');
$today = date('Y-m-d');
$viewMode = isset($viewMode) ? $viewMode : 'day';
$canCreateReservation = isset($canCreateReservation) ? (bool)$canCreateReservation : true;
$prevDate = date('Y-m-d', strtotime($date . ($viewMode === 'month' ? ' -1 month' : ($viewMode === 'week' ? ' -7 days' : ' -1 day'))));
$nextDate = date('Y-m-d', strtotime($date . ($viewMode === 'month' ? ' +1 month' : ($viewMode === 'week' ? ' +7 days' : ' +1 day'))));
$isEnglish = function_exists('get_locale') && get_locale() === 'en';
$dayOfWeek = ($isEnglish ? ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] : ['日','月','火','水','木','金','土'])[date('w', strtotime($date))];
$formattedDate = $isEnglish ? date('F j, Y', strtotime($date)) . ' (' . $dayOfWeek . ')' : date('Y年n月j日', strtotime($date)) . '（' . $dayOfWeek . '）';
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h4 class="mb-0"><i class="fas fa-building me-2"></i><?= htmlspecialchars($pageTitle) ?></h4>
        <div class="d-flex gap-2">
            <?php if ($this->auth->isAdmin()): ?>
                <a href="<?= BASE_PATH ?>/facility/manage" class="btn btn-outline-secondary btn-sm"><i class="fas fa-cog me-1"></i><?= htmlspecialchars(tr_text('施設管理', 'Manage facilities')) ?></a>
            <?php endif; ?>
            <?php if ($canCreateReservation): ?>
                <a href="<?= BASE_PATH ?>/facility/create?date=<?= urlencode($date) ?>" class="btn btn-success btn-sm"><i class="fas fa-plus me-1"></i><?= htmlspecialchars(tr_text('予約する', 'Reserve')) ?></a>
            <?php endif; ?>
        </div>
    </div>

    <?php foreach (['flash_message' => 'success', 'flash_error' => 'danger'] as $key => $type): ?>
        <?php if (isset($_SESSION[$key])): ?>
            <div class="alert alert-<?= $type ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION[$key]) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION[$key]); ?>
        <?php endif; ?>
    <?php endforeach; ?>

    <div class="card mb-3">
        <div class="card-body py-2 d-flex align-items-center justify-content-center gap-3 flex-wrap">
            <a href="<?= BASE_PATH ?>/facility?view=<?= urlencode($viewMode) ?>&date=<?= urlencode($prevDate) ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-chevron-left"></i></a>
            <strong><?= htmlspecialchars($formattedDate) ?></strong>
            <a href="<?= BASE_PATH ?>/facility?view=<?= urlencode($viewMode) ?>&date=<?= urlencode($nextDate) ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-chevron-right"></i></a>
            <?php if ($date !== $today): ?><a href="<?= BASE_PATH ?>/facility?view=<?= urlencode($viewMode) ?>&date=<?= urlencode($today) ?>" class="btn btn-sm btn-outline-primary"><?= htmlspecialchars(tr_text('今日', 'Today')) ?></a><?php endif; ?>
            <div class="btn-group btn-group-sm ms-2">
                <a class="btn <?= $viewMode === 'day' ? 'btn-primary' : 'btn-outline-primary' ?>" href="<?= BASE_PATH ?>/facility?view=day&date=<?= urlencode($date) ?>"><?= htmlspecialchars(tr_text('日', 'Day')) ?></a>
                <a class="btn <?= $viewMode === 'week' ? 'btn-primary' : 'btn-outline-primary' ?>" href="<?= BASE_PATH ?>/facility?view=week&date=<?= urlencode($date) ?>"><?= htmlspecialchars(tr_text('週', 'Week')) ?></a>
                <a class="btn <?= $viewMode === 'month' ? 'btn-primary' : 'btn-outline-primary' ?>" href="<?= BASE_PATH ?>/facility?view=month&date=<?= urlencode($date) ?>"><?= htmlspecialchars(tr_text('月', 'Month')) ?></a>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><strong><i class="far fa-calendar-alt me-1"></i><?= htmlspecialchars(tr_text('予約カレンダー', 'Reservation calendar')) ?></strong></div>
        <div class="card-body"><div id="facility-calendar" data-initial-date="<?= htmlspecialchars($date) ?>" data-initial-view="<?= htmlspecialchars($viewMode) ?>" style="min-height:520px;"></div></div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var el = document.getElementById('facility-calendar');
        if (!el || !window.FullCalendar) return;
        var modeMap = { day: 'timeGridDay', week: 'timeGridWeek', month: 'dayGridMonth' };
        var initialMode = el.getAttribute('data-initial-view') || 'day';
        var calendar = new FullCalendar.Calendar(el, {
            locale: window.APP_LOCALE || 'ja',
            initialDate: el.getAttribute('data-initial-date') || new Date(),
            initialView: modeMap[initialMode] || 'timeGridDay',
            height: 'auto', nowIndicator: true, navLinks: true, selectable: <?= $canCreateReservation ? 'true' : 'false' ?>,
            headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,timeGridDay' },
            buttonText: { today: window.APP_LOCALE === 'en' ? 'Today' : '今日', month: window.APP_LOCALE === 'en' ? 'Month' : '月', week: window.APP_LOCALE === 'en' ? 'Week' : '週', day: window.APP_LOCALE === 'en' ? 'Day' : '日' },
            events: <?= json_encode($calendarEvents ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            dateClick: function (info) { <?php if ($canCreateReservation): ?>window.location.href = '<?= BASE_PATH ?>/facility/create?date=' + encodeURIComponent(info.dateStr.substring(0, 10));<?php endif; ?> },
            select: function (info) { <?php if ($canCreateReservation): ?>window.location.href = '<?= BASE_PATH ?>/facility/create?date=' + encodeURIComponent(info.startStr.substring(0, 10)) + '&start=' + encodeURIComponent(info.startStr.substring(11, 16));<?php endif; ?> },
            eventClick: function (info) { if (info.event.url) { info.jsEvent.preventDefault(); window.location.href = info.event.url; } }
        });
        calendar.render();
    });
    </script>

    <?php if ($facilities === null): ?>
        <div class="card"><div class="card-body text-center py-5"><i class="fas fa-building fa-3x text-muted mb-3"></i><h5><?= htmlspecialchars(tr_text('施設予約の初期設定', 'Facility setup required')) ?></h5><p class="text-muted mb-0"><?= htmlspecialchars(tr_text('施設予約を使用するには、データベーステーブルの作成が必要です。', 'Database tables are required to use facility reservations.')) ?></p></div></div>
    <?php elseif (empty($facilities)): ?>
        <div class="card"><div class="card-body text-center py-5"><i class="fas fa-building fa-3x text-muted mb-3"></i><h5><?= htmlspecialchars(tr_text('施設が登録されていません', 'No facilities registered')) ?></h5><p class="text-muted"><?= htmlspecialchars(tr_text('管理者が「施設管理」から会議室や設備を登録してください。', 'An administrator must register rooms or equipment from facility management.')) ?></p></div></div>
    <?php else: ?>
        <?php $reservationsByFacility = []; foreach ($reservations as $r) { $reservationsByFacility[$r['facility_id']][] = $r; } ?>
        <div class="row g-3">
        <?php foreach ($facilities as $f): ?>
            <div class="col-lg-6"><div class="card h-100"><div class="card-header d-flex justify-content-between align-items-center"><strong><i class="fas fa-door-open me-1"></i><?= htmlspecialchars($f['name']) ?></strong><?php if ($f['capacity'] > 0): ?><span class="badge bg-light text-dark"><i class="fas fa-users me-1"></i><?= (int)$f['capacity'] ?><?= htmlspecialchars(tr_text('名', ' people')) ?></span><?php endif; ?></div><div class="card-body p-2">
                <?php $fReservations = $reservationsByFacility[$f['id']] ?? []; ?>
                <?php if (empty($fReservations)): ?><div class="text-center text-muted py-3"><i class="far fa-calendar-check"></i> <?= htmlspecialchars(tr_text('予約なし（空き）', 'No reservations')) ?></div><?php else: ?>
                    <?php foreach ($fReservations as $r): ?><div class="d-flex align-items-center p-2 border-bottom"><div class="me-3 text-primary fw-bold" style="min-width:110px;font-size:13px;"><?= date('H:i', strtotime($r['start_time'])) ?> - <?= date('H:i', strtotime($r['end_time'])) ?></div><div class="flex-grow-1"><div class="fw-bold" style="font-size:13px;"><?= htmlspecialchars($r['title']) ?></div><small class="text-muted"><?= htmlspecialchars($r['reserver_name']) ?></small></div><?php if ($r['user_id'] == $this->auth->id() || $this->auth->isAdmin()): ?><a href="<?= BASE_PATH ?>/facility/delete/<?= (int)$r['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('<?= htmlspecialchars(tr_text('この予約を取り消しますか？', 'Cancel this reservation?')) ?>')" title="<?= htmlspecialchars(tr_text('取消', 'Cancel')) ?>"><i class="fas fa-times"></i></a><?php endif; ?></div><?php endforeach; ?>
                <?php endif; ?>
            </div><?php if ($canCreateReservation): ?><div class="card-footer text-end"><a href="<?= BASE_PATH ?>/facility/create?facility_id=<?= (int)$f['id'] ?>&date=<?= urlencode($date) ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-plus me-1"></i><?= htmlspecialchars(tr_text('この施設を予約', 'Reserve this facility')) ?></a></div><?php endif; ?></div></div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

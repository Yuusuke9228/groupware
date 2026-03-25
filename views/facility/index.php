<?php
$pageTitle = '施設予約 - TeamSpace';
$today = date('Y-m-d');
$prevDate = date('Y-m-d', strtotime($date . ' -1 day'));
$nextDate = date('Y-m-d', strtotime($date . ' +1 day'));
$dayOfWeek = ['日', '月', '火', '水', '木', '金', '土'][date('w', strtotime($date))];
$formattedDate = date('Y年n月j日', strtotime($date)) . '（' . $dayOfWeek . '）';
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h4 class="mb-0"><i class="fas fa-building me-2"></i>施設予約</h4>
        <div class="d-flex gap-2">
            <?php if ($this->auth->isAdmin()): ?>
                <a href="<?= BASE_PATH ?>/facility/manage" class="btn btn-outline-secondary btn-sm"><i class="fas fa-cog me-1"></i>施設管理</a>
            <?php endif; ?>
            <a href="<?= BASE_PATH ?>/facility/create?date=<?= $date ?>" class="btn btn-success btn-sm"><i class="fas fa-plus me-1"></i>予約する</a>
        </div>
    </div>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['flash_message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['flash_error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <!-- 日付ナビゲーション -->
    <div class="card mb-3">
        <div class="card-body py-2 d-flex align-items-center justify-content-center gap-3">
            <a href="<?= BASE_PATH ?>/facility?date=<?= $prevDate ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-chevron-left"></i></a>
            <strong><?= $formattedDate ?></strong>
            <a href="<?= BASE_PATH ?>/facility?date=<?= $nextDate ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-chevron-right"></i></a>
            <?php if ($date !== $today): ?>
                <a href="<?= BASE_PATH ?>/facility?date=<?= $today ?>" class="btn btn-sm btn-outline-primary">今日</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($facilities === null): ?>
        <!-- テーブルが未作成 -->
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-building fa-3x text-muted mb-3"></i>
                <h5>施設予約の初期設定</h5>
                <p class="text-muted mb-3">施設予約を使用するには、データベーステーブルの作成が必要です。</p>
                <?php if ($this->auth->isAdmin()): ?>
                    <div class="alert alert-info text-start" style="max-width:700px;margin:0 auto;">
                        <strong><i class="fas fa-info-circle me-1"></i>管理者向け:</strong> 以下のSQLを実行してください。
                        <pre class="mt-2 p-2 bg-light rounded" style="font-size:12px;overflow-x:auto;">CREATE TABLE IF NOT EXISTS facilities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    capacity INT DEFAULT 0,
    sort_order INT DEFAULT 0,
    created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS facility_reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    facility_id INT NOT NULL,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    memo TEXT,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_facility_date (facility_id, start_time),
    FOREIGN KEY (facility_id) REFERENCES facilities(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;</pre>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php elseif (empty($facilities)): ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-building fa-3x text-muted mb-3"></i>
                <h5>施設が登録されていません</h5>
                <p class="text-muted">管理者が「施設管理」から会議室や設備を登録してください。</p>
            </div>
        </div>
    <?php else: ?>
        <!-- 施設ごとの予約状況 -->
        <?php
        $reservationsByFacility = [];
        foreach ($reservations as $r) {
            $reservationsByFacility[$r['facility_id']][] = $r;
        }
        ?>
        <div class="row g-3">
        <?php foreach ($facilities as $f): ?>
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <strong><i class="fas fa-door-open me-1"></i><?= htmlspecialchars($f['name']) ?></strong>
                        <?php if ($f['capacity'] > 0): ?>
                            <span class="badge bg-light text-dark"><i class="fas fa-users me-1"></i><?= $f['capacity'] ?>名</span>
                        <?php endif; ?>
                    </div>
                    <div class="card-body p-2">
                        <?php $fReservations = $reservationsByFacility[$f['id']] ?? []; ?>
                        <?php if (empty($fReservations)): ?>
                            <div class="text-center text-muted py-3">
                                <i class="far fa-calendar-check"></i> 予約なし（空き）
                            </div>
                        <?php else: ?>
                            <?php foreach ($fReservations as $r): ?>
                                <div class="d-flex align-items-center p-2 border-bottom">
                                    <div class="me-3 text-primary fw-bold" style="min-width:110px;font-size:13px;">
                                        <?= date('H:i', strtotime($r['start_time'])) ?> - <?= date('H:i', strtotime($r['end_time'])) ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-bold" style="font-size:13px;"><?= htmlspecialchars($r['title']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($r['reserver_name']) ?></small>
                                    </div>
                                    <?php if ($r['user_id'] == $this->auth->id()): ?>
                                        <a href="<?= BASE_PATH ?>/facility/delete/<?= $r['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('この予約を取り消しますか？')" title="取消"><i class="fas fa-times"></i></a>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer text-end">
                        <a href="<?= BASE_PATH ?>/facility/create?facility_id=<?= $f['id'] ?>&date=<?= $date ?>" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-plus me-1"></i>この施設を予約
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

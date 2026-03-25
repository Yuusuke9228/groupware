<?php $pageTitle = '施設予約の作成 - TeamSpace'; ?>
<div class="container-fluid" style="max-width:600px;">
    <h4 class="mb-3"><i class="fas fa-plus me-2"></i>施設を予約する</h4>

    <form method="post" action="<?= BASE_PATH ?>/facility/store" class="no-ajax">
        <div class="card mb-3">
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">施設 <span class="text-danger">*</span></label>
                    <select name="facility_id" class="form-select" required>
                        <option value="">選択してください</option>
                        <?php foreach ($facilities as $f): ?>
                            <option value="<?= $f['id'] ?>" <?= $selectedFacilityId == $f['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($f['name']) ?>
                                <?= $f['capacity'] > 0 ? '（' . $f['capacity'] . '名）' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">用途・タイトル <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="title" required placeholder="例: 部門ミーティング">
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">開始日時 <span class="text-danger">*</span></label>
                        <input type="datetime-local" class="form-control" name="start_time" required value="<?= $date ?>T<?= $startTime ?: '09:00' ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">終了日時 <span class="text-danger">*</span></label>
                        <input type="datetime-local" class="form-control" name="end_time" required value="<?= $date ?>T<?= $startTime ? date('H:i', strtotime($startTime . ' +1 hour')) : '10:00' ?>">
                    </div>
                </div>
                <div class="mb-0">
                    <label class="form-label">メモ</label>
                    <textarea class="form-control" name="memo" rows="2"></textarea>
                </div>
            </div>
        </div>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="fas fa-check me-1"></i>予約する</button>
            <a href="<?= BASE_PATH ?>/facility" class="btn btn-outline-secondary">キャンセル</a>
        </div>
    </form>
</div>

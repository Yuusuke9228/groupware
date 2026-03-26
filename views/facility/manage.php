<?php $pageTitle = '施設管理'; ?>
<div class="container-fluid" style="max-width:800px;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0"><i class="fas fa-cog me-2"></i>施設管理</h4>
        <a href="<?= BASE_PATH ?>/facility" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>施設予約に戻る</a>
    </div>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['flash_message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>

    <!-- 新規施設追加 -->
    <div class="card mb-3">
        <div class="card-header"><strong>施設を追加</strong></div>
        <div class="card-body">
            <form method="post" action="<?= BASE_PATH ?>/facility/add-facility" class="no-ajax">
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">施設名 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" required placeholder="例: 会議室A">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">説明</label>
                        <input type="text" class="form-control" name="description" placeholder="例: 3F 大会議室">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">定員</label>
                        <input type="number" class="form-control" name="capacity" value="0" min="0">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">順序</label>
                        <input type="number" class="form-control" name="sort_order" value="0">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100"><i class="fas fa-plus me-1"></i>追加</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- 施設一覧 -->
    <div class="card">
        <div class="card-header"><strong>登録済み施設</strong></div>
        <?php if ($facilities === null || empty($facilities)): ?>
            <div class="card-body text-center py-4 text-muted">施設が登録されていません。</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr><th>施設名</th><th>説明</th><th>定員</th><th>順序</th><th></th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($facilities as $f): ?>
                        <tr>
                            <td class="fw-bold"><?= htmlspecialchars($f['name']) ?></td>
                            <td><?= htmlspecialchars($f['description'] ?? '') ?></td>
                            <td><?= $f['capacity'] ?>名</td>
                            <td><?= $f['sort_order'] ?></td>
                            <td>
                                <a href="<?= BASE_PATH ?>/facility/delete-facility/<?= $f['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('この施設を削除しますか？予約も全て削除されます。')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
// views/schedule/form.php
$pageTitle = isset($formTitle) ? $formTitle : 'スケジュール';
?>
<div class="container-fluid" data-page-type="form">
    <div class="row mb-4">
        <div class="col">
            <h1 class="h3"><?php echo htmlspecialchars($formTitle); ?></h1>
        </div>
        <div class="col-auto">
            <a href="javascript:history.back()" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> 戻る
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="<?php echo $formAction; ?>" method="<?php echo $formMethod; ?>">
                <?php if (isset($schedule['id']) && $schedule['id']): ?>
                    <input type="hidden" name="id" value="<?php echo $schedule['id']; ?>">
                <?php endif; ?>

                <?php include __DIR__ . '/form_fields.php'; ?>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="javascript:history.back()" class="btn btn-outline-secondary me-md-2">キャンセル</a>
                    <button type="submit" class="btn btn-primary"><?php echo isset($schedule['id']) ? '保存' : '作成'; ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
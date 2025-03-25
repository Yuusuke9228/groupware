<?php
// views/schedule/edit.php
$pageTitle = 'スケジュール編集 - GroupWare';
$isEdit = true;

// スケジュールの開始日時と終了日時を分解
$startDateTime = new DateTime($schedule['start_time']);
$endDateTime = new DateTime($schedule['end_time']);

$schedule['start_time_date'] = $startDateTime->format('Y-m-d');
$schedule['start_time_time'] = $startDateTime->format('H:i');
$schedule['end_time_date'] = $endDateTime->format('Y-m-d');
$schedule['end_time_time'] = $endDateTime->format('H:i');
?>
<div class="container-fluid" data-page-type="edit">
    <div class="row mb-4">
        <div class="col">
            <h1 class="h3">スケジュール編集</h1>
        </div>
        <div class="col-auto">
            <a href="<?php echo BASE_PATH; ?>/schedule/view/<?php echo $schedule['id']; ?>" class="btn btn-outline-secondary me-2">
                <i class="fas fa-eye"></i> 詳細を表示
            </a>
            <a href="javascript:history.back()" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> 戻る
            </a>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <form action="<?php echo BASE_PATH; ?>/api/schedule/<?php echo $schedule['id']; ?>" method="post">
                <input type="hidden" name="id" id="id" value="<?php echo $schedule['id']; ?>">
                <?php include __DIR__ . '/form_fields.php'; ?>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="javascript:history.back()" class="btn btn-outline-secondary me-md-2">キャンセル</a>
                    <button type="submit" class="btn btn-primary">保存</button>
                </div>
            </form>
        </div>
    </div>
</div>


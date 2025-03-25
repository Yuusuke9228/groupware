<?php
// views/schedule/create.php
$pageTitle = '新規スケジュール作成 - GroupWare';
$isEdit = false;

// パラメータから初期値を取得
$defaultDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$defaultTime = isset($_GET['time']) ? $_GET['time'] : date('H:i');
$defaultAllDay = isset($_GET['all_day']) && $_GET['all_day'] == 1;

// 初期値の設定
$schedule = [
    'start_time' => $defaultDate . ' ' . $defaultTime,
    'end_time' => $defaultDate . ' ' . date('H:i', strtotime($defaultTime) + 3600),
    'all_day' => $defaultAllDay ? 1 : 0,
    'visibility' => 'public',
    'priority' => 'normal',
    'status' => 'scheduled',
    'repeat_type' => 'none'
];

// 初期値を分解
$schedule['start_time_date'] = $defaultDate;
$schedule['start_time_time'] = $defaultTime;
$schedule['end_time_date'] = $defaultDate;
$schedule['end_time_time'] = date('H:i', strtotime($defaultTime) + 3600);
?>
<div class="container-fluid" data-page-type="create">
    <div class="row mb-4">
        <div class="col">
            <h1 class="h3">新規スケジュール作成</h1>
        </div>
        <div class="col-auto">
            <a href="javascript:history.back()" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> 戻る
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="<?php echo BASE_PATH; ?>/api/schedule" method="post">
                <?php include __DIR__ . '/form_fields.php'; ?>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="javascript:history.back()" class="btn btn-outline-secondary me-md-2">キャンセル</a>
                    <button type="submit" class="btn btn-primary">作成</button>
                </div>
            </form>
        </div>
    </div>
</div>
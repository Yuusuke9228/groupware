<?php
// views/schedule/view.php
$pageTitle = 'スケジュール詳細 - GroupWare';

// 現在のユーザー情報
$currentUser = \Core\Auth::getInstance()->user();

// 日時フォーマット
$startDateTime = new DateTime($schedule['start_time']);
$endDateTime = new DateTime($schedule['end_time']);

// 同日判定
$isSameDay = $startDateTime->format('Y-m-d') === $endDateTime->format('Y-m-d');

// 終日判定
$isAllDay = $schedule['all_day'] == 1;

// 表示用日時フォーマット
if ($isAllDay) {
    if ($isSameDay) {
        $dateTimeDisplay = $startDateTime->format('Y年n月j日') . ' (終日)';
    } else {
        $dateTimeDisplay = $startDateTime->format('Y年n月j日') . ' ～ ' . $endDateTime->format('Y年n月j日') . ' (終日)';
    }
} else {
    if ($isSameDay) {
        $dateTimeDisplay = $startDateTime->format('Y年n月j日 H:i') . ' ～ ' . $endDateTime->format('H:i');
    } else {
        $dateTimeDisplay = $startDateTime->format('Y年n月j日 H:i') . ' ～ ' . $endDateTime->format('Y年n月j日 H:i');
    }
}

// 優先度表示
$priorityLabels = [
    'high' => '<span class="badge bg-danger">高</span>',
    'normal' => '<span class="badge bg-success">通常</span>',
    'low' => '<span class="badge bg-info">低</span>'
];

// 状態表示
$statusLabels = [
    'scheduled' => '<span class="badge bg-primary">予定</span>',
    'tentative' => '<span class="badge bg-warning">仮予定</span>',
    'cancelled' => '<span class="badge bg-secondary">取消</span>'
];

// 公開範囲表示
$visibilityLabels = [
    'public' => '<span class="badge bg-success">全体公開</span>',
    'private' => '<span class="badge bg-danger">非公開（自分のみ）</span>',
    'specific' => '<span class="badge bg-info">特定ユーザー/組織</span>'
];

// 繰り返し表示
$repeatLabels = [
    'none' => 'なし',
    'daily' => '毎日',
    'weekly' => '毎週',
    'monthly' => '毎月',
    'yearly' => '毎年'
];
?>
<div class="container-fluid" data-page-type="view">
    <input type="hidden" id="schedule-id" value="<?php echo $schedule['id']; ?>">
    
    <div class="row mb-4">
        <div class="col">
            <h1 class="h3"><?php echo htmlspecialchars($schedule['title']); ?></h1>
        </div>
        <div class="col-auto">
            <?php if ($schedule['creator_id'] == $currentUser['id'] || $currentUser['role'] === 'admin'): ?>
            <a href="<?php echo BASE_PATH; ?>/schedule/edit/<?php echo $schedule['id']; ?>" class="btn btn-primary me-2">
                <i class="fas fa-edit"></i> 編集
            </a>
            <button type="button" class="btn btn-danger btn-delete" data-url="/api/schedule/<?php echo $schedule['id']; ?>" data-confirm="このスケジュールを削除しますか？">
                <i class="fas fa-trash"></i> 削除
            </button>
            <?php endif; ?>
            <a href="javascript:history.back()" class="btn btn-outline-secondary ms-2">
                <i class="fas fa-arrow-left"></i> 戻る
            </a>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">スケジュール詳細</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-3 fw-bold">日時</div>
                        <div class="col-md-9"><?php echo $dateTimeDisplay; ?></div>
                    </div>
                    
                    <?php if (!empty($schedule['location'])): ?>
                    <div class="row mb-3">
                        <div class="col-md-3 fw-bold">場所</div>
                        <div class="col-md-9"><?php echo htmlspecialchars($schedule['location']); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="row mb-3">
                        <div class="col-md-3 fw-bold">作成者</div>
                        <div class="col-md-9"><?php echo htmlspecialchars($schedule['creator_name']); ?></div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-3 fw-bold">優先度</div>
                        <div class="col-md-9"><?php echo $priorityLabels[$schedule['priority']] ?? '通常'; ?></div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-3 fw-bold">状態</div>
                        <div class="col-md-9"><?php echo $statusLabels[$schedule['status']] ?? '予定'; ?></div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-3 fw-bold">公開範囲</div>
                        <div class="col-md-9"><?php echo $visibilityLabels[$schedule['visibility']] ?? '全体公開'; ?></div>
                    </div>
                    
                    <?php if ($schedule['repeat_type'] !== 'none'): ?>
                    <div class="row mb-3">
                        <div class="col-md-3 fw-bold">繰り返し</div>
                        <div class="col-md-9">
                            <?php echo $repeatLabels[$schedule['repeat_type']] ?? 'なし'; ?>
                            <?php if (!empty($schedule['repeat_end_date'])): ?>
                                （～ <?php echo date('Y年n月j日', strtotime($schedule['repeat_end_date'])); ?>）
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($schedule['description'])): ?>
                    <div class="row mb-3">
                        <div class="col-md-3 fw-bold">説明</div>
                        <div class="col-md-9"><?php echo nl2br(htmlspecialchars($schedule['description'])); ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($schedule['visibility'] === 'specific' && !empty($sharedOrganizations)): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">共有組織</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group">
                        <?php foreach ($sharedOrganizations as $org): ?>
                        <li class="list-group-item"><?php echo htmlspecialchars($org['name']); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="col-md-4">
            <?php if ($isParticipant): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">参加ステータス</h5>
                </div>
                <div class="card-body">
                    <p>あなたの参加状況: <span id="participation-status" class="badge <?php echo $participationStatus === 'accepted' ? 'bg-success' : ($participationStatus === 'declined' ? 'bg-danger' : ($participationStatus === 'tentative' ? 'bg-warning' : 'bg-secondary')); ?>"><?php echo $participationStatus === 'accepted' ? '参加' : ($participationStatus === 'declined' ? '不参加' : ($participationStatus === 'tentative' ? '未定' : '未回答')); ?></span></p>
                    
                    <div class="btn-group w-100" role="group">
                        <button type="button" class="btn btn-outline-success btn-participation-status <?php echo $participationStatus === 'accepted' ? 'active' : ''; ?>" data-status="accepted">
                            <i class="fas fa-check"></i> 参加
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-participation-status <?php echo $participationStatus === 'declined' ? 'active' : ''; ?>" data-status="declined">
                            <i class="fas fa-times"></i> 不参加
                        </button>
                        <button type="button" class="btn btn-outline-warning btn-participation-status <?php echo $participationStatus === 'tentative' ? 'active' : ''; ?>" data-status="tentative">
                            <i class="fas fa-question"></i> 未定
                        </button>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($schedule['visibility'] === 'specific' && !empty($participants)): ?>
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">参加者一覧</h5>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php foreach ($participants as $participant): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <?php echo htmlspecialchars($participant['display_name']); ?>
                            
                            <?php if ($participant['participation_status'] === 'accepted'): ?>
                            <span class="badge bg-success">参加</span>
                            <?php elseif ($participant['participation_status'] === 'declined'): ?>
                            <span class="badge bg-danger">不参加</span>
                            <?php elseif ($participant['participation_status'] === 'tentative'): ?>
                            <span class="badge bg-warning">未定</span>
                            <?php else: ?>
                            <span class="badge bg-secondary">未回答</span>
                            <?php endif; ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

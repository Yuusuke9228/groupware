<?php
// views/schedule/form_fields.php
// スケジュールフォームの共通入力項目

// 変数のデフォルト値を設定
if (!isset($schedule)) $schedule = [];
if (!isset($isEdit)) $isEdit = false;

// 各フィールドの値取得用ヘルパー関数
function getValue($field, $default = '')
{
    global $schedule;
    return isset($schedule[$field]) ? htmlspecialchars($schedule[$field]) : $default;
}

// 日時分解（開始日時と終了日時を日付と時間に分ける）
if (isset($schedule['start_time']) && !isset($schedule['start_time_date'])) {
    $startDateTime = explode(' ', $schedule['start_time']);
    $schedule['start_time_date'] = $startDateTime[0] ?? date('Y-m-d');
    $schedule['start_time_time'] = $startDateTime[1] ?? date('H:i');
}

if (isset($schedule['end_time']) && !isset($schedule['end_time_date'])) {
    $endDateTime = explode(' ', $schedule['end_time']);
    $schedule['end_time_date'] = $endDateTime[0] ?? date('Y-m-d');
    $schedule['end_time_time'] = $endDateTime[1] ?? date('H:i', strtotime('+1 hour'));
}
?>

<div class="mb-3">
    <label for="title" class="form-label">タイトル <span class="text-danger">*</span></label>
    <input type="text" class="form-control" id="title" name="title" value="<?php echo getValue('title'); ?>" required autofocus>
    <div class="invalid-feedback"></div>
</div>

<div class="mb-3">
    <div class="form-check">
        <input class="form-check-input" type="checkbox" id="all_day" name="all_day" value="1" <?php echo getValue('all_day') == 1 ? 'checked' : ''; ?>>
        <label class="form-check-label" for="all_day">
            終日
        </label>
    </div>
</div>

<div class="row mb-3">
    <input type="hidden" id="start_time" name="start_time" value="<?php echo getValue('start_time'); ?>">
    <input type="hidden" id="end_time" name="end_time" value="<?php echo getValue('end_time'); ?>">

    <div class="col-md-3">
        <label for="start_time_date" class="form-label">開始日 <span class="text-danger">*</span></label>
        <input type="text" class="form-control date-picker" id="start_time_date" name="start_time_date" value="<?php echo getValue('start_time_date'); ?>" required>
        <div class="invalid-feedback"></div>
    </div>
    <div class="col-md-3 time-picker">
        <label for="start_time_time" class="form-label">開始時間 <span class="text-danger">*</span></label>
        <input type="text" class="form-control time-picker" id="start_time_time" name="start_time_time" value="<?php echo getValue('start_time_time'); ?>" required>
        <div class="invalid-feedback"></div>
    </div>
    <div class="col-md-3">
        <label for="end_time_date" class="form-label">終了日 <span class="text-danger">*</span></label>
        <input type="text" class="form-control date-picker" id="end_time_date" name="end_time_date" value="<?php echo getValue('end_time_date'); ?>" required>
        <div class="invalid-feedback"></div>
    </div>
    <div class="col-md-3 time-picker">
        <label for="end_time_time" class="form-label">終了時間 <span class="text-danger">*</span></label>
        <input type="text" class="form-control time-picker" id="end_time_time" name="end_time_time" value="<?php echo getValue('end_time_time'); ?>" required>
        <div class="invalid-feedback"></div>
    </div>
</div>

<div class="mb-3">
    <label for="location" class="form-label">場所</label>
    <input type="text" class="form-control" id="location" name="location" value="<?php echo getValue('location'); ?>">
</div>

<div class="mb-3">
    <label for="description" class="form-label">説明</label>
    <textarea class="form-control" id="description" name="description" rows="3"><?php echo getValue('description'); ?></textarea>
</div>

<div class="row mb-3">
    <div class="col-md-4">
        <label for="visibility" class="form-label">公開範囲</label>
        <select class="form-select" id="visibility" name="visibility">
            <option value="public" <?php echo getValue('visibility') === 'public' ? 'selected' : ''; ?>>全体公開</option>
            <option value="private" <?php echo getValue('visibility') === 'private' ? 'selected' : ''; ?>>非公開（自分のみ）</option>
            <option value="specific" <?php echo getValue('visibility') === 'specific' ? 'selected' : ''; ?>>特定ユーザー/組織</option>
        </select>
    </div>
    <div class="col-md-4">
        <label for="priority" class="form-label">優先度</label>
        <select class="form-select" id="priority" name="priority">
            <option value="high" <?php echo getValue('priority') === 'high' ? 'selected' : ''; ?>>高</option>
            <option value="normal" <?php echo getValue('priority') === 'normal' ? 'selected' : ''; ?>>通常</option>
            <option value="low" <?php echo getValue('priority') === 'low' ? 'selected' : ''; ?>>低</option>
        </select>
    </div>
    <div class="col-md-4">
        <label for="status" class="form-label">状態</label>
        <select class="form-select" id="status" name="status">
            <option value="scheduled" <?php echo getValue('status') === 'scheduled' ? 'selected' : ''; ?>>予定</option>
            <option value="tentative" <?php echo getValue('status') === 'tentative' ? 'selected' : ''; ?>>仮予定</option>
            <option value="cancelled" <?php echo getValue('status') === 'cancelled' ? 'selected' : ''; ?>>取消</option>
        </select>
    </div>
</div>

<div class="mb-3 visibility-specific" style="<?php echo getValue('visibility') === 'specific' ? '' : 'display: none;'; ?>">
    <label for="participants" class="form-label">参加者</label>
    <select class="form-select participant-select" id="participants" name="participants[]" multiple>
        <?php if (isset($participants) && is_array($participants)): ?>
            <?php foreach ($participants as $p): ?>
                <option value="<?php echo $p['id']; ?>" selected>
                    <?php echo htmlspecialchars($p['display_name']); ?>
                </option>
            <?php endforeach; ?>
        <?php endif; ?>
    </select>
    <small class="form-text text-muted">参加者を選択してください（複数選択可）</small>
</div>

<div class="mb-3 visibility-specific" style="<?php echo getValue('visibility') === 'specific' ? '' : 'display: none;'; ?>">
    <label for="organizations" class="form-label">共有組織</label>
    <select class="form-select organization-select" id="organizations" name="organizations[]" multiple>
        <?php if (isset($sharedOrganizations) && is_array($sharedOrganizations)): ?>
            <?php foreach ($sharedOrganizations as $org): ?>
                <option value="<?php echo $org['id']; ?>" selected>
                    <?php echo htmlspecialchars($org['name']); ?>
                </option>
            <?php endforeach; ?>
        <?php endif; ?>
    </select>
    <small class="form-text text-muted">共有する組織を選択してください（複数選択可）</small>
</div>

<div class="mb-3">
    <label for="repeat_type" class="form-label">繰り返し</label>
    <select class="form-select" id="repeat_type" name="repeat_type">
        <option value="none" <?php echo getValue('repeat_type') === 'none' ? 'selected' : ''; ?>>繰り返しなし</option>
        <option value="daily" <?php echo getValue('repeat_type') === 'daily' ? 'selected' : ''; ?>>毎日</option>
        <option value="weekly" <?php echo getValue('repeat_type') === 'weekly' ? 'selected' : ''; ?>>毎週</option>
        <option value="monthly" <?php echo getValue('repeat_type') === 'monthly' ? 'selected' : ''; ?>>毎月</option>
        <option value="yearly" <?php echo getValue('repeat_type') === 'yearly' ? 'selected' : ''; ?>>毎年</option>
    </select>
</div>

<div class="mb-3 repeat-options" style="<?php echo getValue('repeat_type') !== 'none' ? '' : 'display: none;'; ?>">
    <label for="repeat_end_date" class="form-label">繰り返し終了日</label>
    <input type="text" class="form-control date-picker" id="repeat_end_date" name="repeat_end_date" value="<?php echo getValue('repeat_end_date'); ?>">
    <div class="invalid-feedback"></div>
</div>
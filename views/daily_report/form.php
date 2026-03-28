<?php
$isEdit = isset($report);
$formTitle = $isEdit ? '日報編集' : '日報作成';
$formAction = $isEdit ? BASE_PATH . "/api/daily-report/{$report['id']}" : BASE_PATH . "/api/daily-report";
$submitButtonText = $isEdit ? '更新する' : '保存する';

$reportDate = $isEdit ? $report['report_date'] : ($date ?? date('Y-m-d'));
$isDraft = $isEdit ? (($report['status'] ?? '') === 'draft') : false;

$summaryText = $isEdit ? ($report['summary_text'] ?? '') : '';
$issuesText = $isEdit ? ($report['issues_text'] ?? '') : '';
$tomorrowPlanText = $isEdit ? ($report['tomorrow_plan_text'] ?? '') : '';
$reflectionText = $isEdit ? ($report['reflection_text'] ?? '') : '';
$workMinutes = $isEdit ? (int)($report['work_minutes'] ?? 0) : 0;

$selectedTemplateId = '';
if ($isEdit && !empty($report['template_id'])) {
    $selectedTemplateId = (string)$report['template_id'];
} elseif (!$isEdit && !empty($template['id'])) {
    $selectedTemplateId = (string)$template['id'];
}

$reportTags = [];
if ($isEdit && !empty($report['tags'])) {
    foreach ($report['tags'] as $tag) {
        $reportTags[] = $tag['name'];
    }
}

$permissions = [];
if ($isEdit && !empty($report['permissions'])) {
    foreach ($report['permissions'] as $permission) {
        $permissions[] = [
            'type' => $permission['target_type'],
            'id' => $permission['target_id']
        ];
    }
}

$selectedSchedules = [];
if ($isEdit && !empty($report['schedules'])) {
    foreach ($report['schedules'] as $schedule) {
        $selectedSchedules[] = $schedule['id'];
    }
}

$selectedTasks = [];
if ($isEdit && !empty($report['tasks'])) {
    foreach ($report['tasks'] as $task) {
        $selectedTasks[] = $task['id'];
    }
}

$templateSections = $templateSections ?? [];
$detailItems = [];
if ($isEdit && !empty($report['detail_items']) && is_array($report['detail_items'])) {
    $detailItems = $report['detail_items'];
} elseif (!empty($defaultDetailItems) && is_array($defaultDetailItems)) {
    $detailItems = $defaultDetailItems;
}

$detailMap = [];
foreach ($detailItems as $item) {
    $mapKey = trim((string)($item['section_key'] ?? ''));
    if ($mapKey === '') {
        $mapKey = trim((string)($item['title'] ?? ''));
    }
    if ($mapKey !== '') {
        $detailMap[$mapKey] = $item;
    }
}

$finalDetailItems = [];
foreach ($templateSections as $section) {
    $key = trim((string)($section['section_key'] ?? ''));
    if ($key === '') {
        $key = trim((string)($section['title'] ?? ''));
    }

    if (isset($detailMap[$key])) {
        $finalDetailItems[] = [
            'section_key' => $key,
            'title' => $section['title'] ?? ($detailMap[$key]['title'] ?? ''),
            'value' => $detailMap[$key]['value'] ?? '',
            'input_type' => $section['input_type'] ?? ($detailMap[$key]['input_type'] ?? 'textarea'),
            'placeholder_text' => $section['placeholder_text'] ?? '',
            'required' => !empty($section['is_required'])
        ];
        unset($detailMap[$key]);
    } else {
        $finalDetailItems[] = [
            'section_key' => $key,
            'title' => $section['title'] ?? '',
            'value' => $section['default_value_text'] ?? '',
            'input_type' => $section['input_type'] ?? 'textarea',
            'placeholder_text' => $section['placeholder_text'] ?? '',
            'required' => !empty($section['is_required'])
        ];
    }
}

foreach ($detailMap as $leftover) {
    $finalDetailItems[] = [
        'section_key' => trim((string)($leftover['section_key'] ?? '')),
        'title' => $leftover['title'] ?? '',
        'value' => $leftover['value'] ?? '',
        'input_type' => $leftover['input_type'] ?? 'textarea',
        'placeholder_text' => '',
        'required' => false
    ];
}

$activityLogs = [];
if ($isEdit && !empty($report['activity_logs']) && is_array($report['activity_logs'])) {
    $activityLogs = $report['activity_logs'];
}
if (empty($activityLogs)) {
    $activityLogs[] = [
        'start_time' => '',
        'end_time' => '',
        'activity_type' => '',
        'subject' => '',
        'result' => '',
        'memo' => ''
    ];
}

$analysisEntries = [];
if ($isEdit && !empty($report['analysis_entries']) && is_array($report['analysis_entries'])) {
    $analysisEntries = $report['analysis_entries'];
}
if (empty($analysisEntries)) {
    $analysisEntries[] = [
        'project_id' => '',
        'industry_id' => '',
        'product_id' => '',
        'process_id' => '',
        'activity_type' => '',
        'planned_amount' => '',
        'actual_amount' => '',
        'planned_hours' => '',
        'actual_hours' => '',
        'quantity' => '',
        'memo' => ''
    ];
}

$attachments = [];
if ($isEdit && !empty($report['attachments']) && is_array($report['attachments'])) {
    $attachments = $report['attachments'];
}

$contentFormat = 'text';
if ($isEdit && ($report['content_format'] ?? 'text') === 'html') {
    $contentFormat = 'html';
} elseif (!$isEdit && !empty($template['content_format']) && $template['content_format'] === 'html') {
    $contentFormat = 'html';
}
$rawContent = $isEdit ? (string)($report['content'] ?? '') : (string)($template['content'] ?? '');
$editorInitialHtml = $contentFormat === 'html'
    ? $rawContent
    : nl2br(htmlspecialchars($rawContent, ENT_QUOTES, 'UTF-8'));
?>

<div class="container-fluid pt-4 px-4">
    <div class="row g-4">
        <div class="col-12">
            <div class="bg-light rounded p-4 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="mb-0"><?= $formTitle ?></h3>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="<?= BASE_PATH ?>/daily-report" class="btn btn-outline-secondary btn-sm">ダッシュボード</a>
                        <a href="<?= BASE_PATH ?>/daily-report/week" class="btn btn-outline-secondary btn-sm">週間</a>
                        <a href="<?= BASE_PATH ?>/daily-report/month" class="btn btn-outline-secondary btn-sm">月間</a>
                        <a href="<?= BASE_PATH ?>/daily-report/timeline" class="btn btn-outline-secondary btn-sm">タイムライン</a>
                        <a href="<?= BASE_PATH ?>/daily-report/list" class="btn btn-outline-secondary btn-sm">一覧</a>
                        <a href="<?= BASE_PATH ?>/daily-report/analysis" class="btn btn-outline-secondary btn-sm">分析</a>
                        <a href="<?= BASE_PATH ?>/daily-report/templates" class="btn btn-outline-secondary btn-sm">テンプレート</a>
                    </div>
                </div>

                <form id="reportForm" method="POST" action="<?= $formAction ?>" enctype="multipart/form-data">
                    <input type="hidden" id="template_id" name="template_id" value="<?= htmlspecialchars($selectedTemplateId) ?>">
                    <input type="hidden" id="content_format" name="content_format" value="<?= htmlspecialchars($contentFormat) ?>">

                    <div class="card mb-4">
                        <div class="card-header"><strong>基本情報</strong></div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label for="report_date" class="form-label">日付 <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="report_date" name="report_date" value="<?= htmlspecialchars($reportDate) ?>" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-6">
                                    <label for="title" class="form-label">タイトル <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="title" name="title"
                                        value="<?= $isEdit ? htmlspecialchars($report['title']) : ($template ? htmlspecialchars($template['title']) : '') ?>"
                                        required maxlength="100">
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-3">
                                    <label for="work_minutes" class="form-label">実働時間(分)</label>
                                    <input type="number" min="0" class="form-control" id="work_minutes" name="work_minutes" value="<?= $workMinutes ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="template_selector" class="form-label">テンプレート</label>
                                    <select class="form-select" id="template_selector">
                                        <option value="">テンプレートを選択</option>
                                        <?php foreach (($templates ?? []) as $tpl): ?>
                                            <option value="<?= (int)$tpl['id'] ?>" <?= $selectedTemplateId === (string)$tpl['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($tpl['title']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header"><strong>業務サマリー</strong></div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="summary_text" class="form-label">本日の成果</label>
                                <textarea class="form-control" id="summary_text" rows="3"><?= htmlspecialchars($summaryText) ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="issues_text" class="form-label">課題・問題点</label>
                                <textarea class="form-control" id="issues_text" rows="3"><?= htmlspecialchars($issuesText) ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="tomorrow_plan_text" class="form-label">明日の予定</label>
                                <textarea class="form-control" id="tomorrow_plan_text" rows="3"><?= htmlspecialchars($tomorrowPlanText) ?></textarea>
                            </div>
                            <div>
                                <label for="reflection_text" class="form-label">所感・連絡事項</label>
                                <textarea class="form-control" id="reflection_text" rows="3"><?= htmlspecialchars($reflectionText) ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4" id="detailItemsCard">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <strong>テンプレート項目</strong>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="addDetailItemBtn">
                                <i class="fas fa-plus me-1"></i>項目追加
                            </button>
                        </div>
                        <div class="card-body" id="detailItemsContainer">
                            <?php if (empty($finalDetailItems)): ?>
                                <div class="detail-item-row border rounded p-3 mb-3" data-input-type="textarea">
                                    <div class="row g-2">
                                        <div class="col-md-4">
                                            <input type="text" class="form-control detail-item-title" placeholder="項目名 (例: 顧客対応)">
                                            <input type="hidden" class="detail-item-key" value="">
                                        </div>
                                        <div class="col-md-8">
                                            <textarea class="form-control detail-item-value" rows="2" placeholder="内容"></textarea>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <?php foreach ($finalDetailItems as $item): ?>
                                    <div class="detail-item-row border rounded p-3 mb-3" data-input-type="<?= htmlspecialchars($item['input_type']) ?>">
                                        <div class="row g-2 align-items-start">
                                            <div class="col-md-4">
                                                <label class="form-label small text-muted mb-1">項目名<?= !empty($item['required']) ? ' <span class="text-danger">*</span>' : '' ?></label>
                                                <input type="text" class="form-control detail-item-title" value="<?= htmlspecialchars($item['title']) ?>" <?= !empty($item['required']) ? 'readonly' : '' ?>>
                                                <input type="hidden" class="detail-item-key" value="<?= htmlspecialchars($item['section_key']) ?>">
                                                <input type="hidden" class="detail-item-type" value="<?= htmlspecialchars($item['input_type']) ?>">
                                            </div>
                                            <div class="col-md-7">
                                                <label class="form-label small text-muted mb-1">内容</label>
                                                <?php if (($item['input_type'] ?? '') === 'text'): ?>
                                                    <input type="text" class="form-control detail-item-value" value="<?= htmlspecialchars($item['value']) ?>" placeholder="<?= htmlspecialchars($item['placeholder_text'] ?? '') ?>">
                                                <?php else: ?>
                                                    <textarea class="form-control detail-item-value" rows="2" placeholder="<?= htmlspecialchars($item['placeholder_text'] ?? '') ?>"><?= htmlspecialchars($item['value']) ?></textarea>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-md-1 d-flex align-items-end">
                                                <?php if (empty($item['required'])): ?>
                                                    <button type="button" class="btn btn-outline-danger btn-sm remove-detail-item" title="項目削除">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <strong>活動ログ (時系列)</strong>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="addActivityRowBtn">
                                <i class="fas fa-plus me-1"></i>行追加
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm mb-0" id="activityTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="min-width:90px;">開始</th>
                                            <th style="min-width:90px;">終了</th>
                                            <th style="min-width:130px;">分類</th>
                                            <th style="min-width:180px;">件名</th>
                                            <th style="min-width:180px;">結果</th>
                                            <th>メモ</th>
                                            <th style="width:60px;"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($activityLogs as $row): ?>
                                            <tr class="activity-row">
                                                <td><input type="time" class="form-control form-control-sm activity-start" value="<?= htmlspecialchars($row['start_time'] ?? '') ?>"></td>
                                                <td><input type="time" class="form-control form-control-sm activity-end" value="<?= htmlspecialchars($row['end_time'] ?? '') ?>"></td>
                                                <td><input type="text" class="form-control form-control-sm activity-type" value="<?= htmlspecialchars($row['activity_type'] ?? '') ?>" placeholder="商談/開発"></td>
                                                <td><input type="text" class="form-control form-control-sm activity-subject" value="<?= htmlspecialchars($row['subject'] ?? '') ?>" placeholder="件名"></td>
                                                <td><input type="text" class="form-control form-control-sm activity-result" value="<?= htmlspecialchars($row['result'] ?? '') ?>" placeholder="結果"></td>
                                                <td><input type="text" class="form-control form-control-sm activity-memo" value="<?= htmlspecialchars($row['memo'] ?? '') ?>" placeholder="補足"></td>
                                                <td>
                                                    <button type="button" class="btn btn-outline-danger btn-sm remove-activity-row"><i class="fas fa-times"></i></button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <strong>分析明細 (案件 / 業種 / 商品 / プロセス / 予実)</strong>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="addAnalysisRowBtn">
                                <i class="fas fa-plus me-1"></i>行追加
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm mb-0" id="analysisTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="min-width:150px;">案件</th>
                                            <th style="min-width:130px;">業種</th>
                                            <th style="min-width:130px;">商品</th>
                                            <th style="min-width:130px;">プロセス</th>
                                            <th style="min-width:120px;">活動分類</th>
                                            <th style="min-width:110px;">計画金額</th>
                                            <th style="min-width:110px;">実績金額</th>
                                            <th style="min-width:100px;">計画時間</th>
                                            <th style="min-width:100px;">実績時間</th>
                                            <th style="min-width:90px;">数量</th>
                                            <th style="min-width:160px;">メモ</th>
                                            <th style="width:60px;"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($analysisEntries as $entry): ?>
                                            <tr class="analysis-row">
                                                <td>
                                                    <select class="form-select form-select-sm analysis-project">
                                                        <option value="">未指定</option>
                                                        <?php foreach (($analysisMasters['projects'] ?? []) as $item): ?>
                                                            <option value="<?= (int)$item['id'] ?>" <?= (string)($entry['project_id'] ?? '') === (string)$item['id'] ? 'selected' : '' ?>>
                                                                <?= htmlspecialchars($item['name']) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </td>
                                                <td>
                                                    <select class="form-select form-select-sm analysis-industry">
                                                        <option value="">未指定</option>
                                                        <?php foreach (($analysisMasters['industries'] ?? []) as $item): ?>
                                                            <option value="<?= (int)$item['id'] ?>" <?= (string)($entry['industry_id'] ?? '') === (string)$item['id'] ? 'selected' : '' ?>>
                                                                <?= htmlspecialchars($item['name']) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </td>
                                                <td>
                                                    <select class="form-select form-select-sm analysis-product">
                                                        <option value="">未指定</option>
                                                        <?php foreach (($analysisMasters['products'] ?? []) as $item): ?>
                                                            <option value="<?= (int)$item['id'] ?>" <?= (string)($entry['product_id'] ?? '') === (string)$item['id'] ? 'selected' : '' ?>>
                                                                <?= htmlspecialchars($item['name']) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </td>
                                                <td>
                                                    <select class="form-select form-select-sm analysis-process">
                                                        <option value="">未指定</option>
                                                        <?php foreach (($analysisMasters['processes'] ?? []) as $item): ?>
                                                            <option value="<?= (int)$item['id'] ?>" <?= (string)($entry['process_id'] ?? '') === (string)$item['id'] ? 'selected' : '' ?>>
                                                                <?= htmlspecialchars($item['name']) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </td>
                                                <td><input type="text" class="form-control form-control-sm analysis-activity-type" value="<?= htmlspecialchars($entry['activity_type'] ?? '') ?>" placeholder="商談/開発"></td>
                                                <td><input type="number" min="0" step="0.01" class="form-control form-control-sm analysis-planned-amount" value="<?= htmlspecialchars((string)($entry['planned_amount'] ?? '')) ?>"></td>
                                                <td><input type="number" min="0" step="0.01" class="form-control form-control-sm analysis-actual-amount" value="<?= htmlspecialchars((string)($entry['actual_amount'] ?? '')) ?>"></td>
                                                <td><input type="number" min="0" step="0.01" class="form-control form-control-sm analysis-planned-hours" value="<?= htmlspecialchars((string)($entry['planned_hours'] ?? '')) ?>"></td>
                                                <td><input type="number" min="0" step="0.01" class="form-control form-control-sm analysis-actual-hours" value="<?= htmlspecialchars((string)($entry['actual_hours'] ?? '')) ?>"></td>
                                                <td><input type="number" min="0" step="0.01" class="form-control form-control-sm analysis-quantity" value="<?= htmlspecialchars((string)($entry['quantity'] ?? '')) ?>"></td>
                                                <td><input type="text" class="form-control form-control-sm analysis-memo" value="<?= htmlspecialchars($entry['memo'] ?? '') ?>" placeholder="補足"></td>
                                                <td><button type="button" class="btn btn-outline-danger btn-sm remove-analysis-row"><i class="fas fa-times"></i></button></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header"><strong>ファイル添付</strong></div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="attachments" class="form-label">添付ファイル</label>
                                <input type="file" class="form-control" id="attachments" name="attachments[]" multiple>
                                <div class="form-text">複数ファイルを同時に添付できます。</div>
                            </div>
                            <?php if (!empty($attachments)): ?>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>ファイル名</th>
                                                <th class="text-end">サイズ</th>
                                                <th>更新日時</th>
                                                <th style="width: 120px;">更新時に削除</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($attachments as $attachment): ?>
                                                <tr>
                                                    <td>
                                                        <a href="<?= BASE_PATH . '/' . ltrim((string)$attachment['file_path'], '/') ?>" target="_blank" rel="noopener noreferrer">
                                                            <?= htmlspecialchars((string)($attachment['original_name'] ?? '添付ファイル')) ?>
                                                        </a>
                                                    </td>
                                                    <td class="text-end"><?= number_format((int)($attachment['file_size'] ?? 0) / 1024, 1) ?> KB</td>
                                                    <td><?= htmlspecialchars((string)($attachment['created_at'] ?? '')) ?></td>
                                                    <td>
                                                        <div class="form-check">
                                                            <input class="form-check-input delete-attachment-check" type="checkbox" value="<?= (int)$attachment['id'] ?>" id="delete_attachment_<?= (int)$attachment['id'] ?>">
                                                            <label class="form-check-label" for="delete_attachment_<?= (int)$attachment['id'] ?>">削除する</label>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="accordion mb-4" id="reportAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingContentRaw">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseContentRaw" aria-expanded="false" aria-controls="collapseContentRaw">
                                    自由記述 (任意)
                                </button>
                            </h2>
                            <div id="collapseContentRaw" class="accordion-collapse collapse" aria-labelledby="headingContentRaw" data-bs-parent="#reportAccordion">
                                <div class="accordion-body">
                                    <div class="btn-toolbar mb-2" role="toolbar" aria-label="リッチテキスト操作">
                                        <div class="btn-group btn-group-sm me-2" role="group">
                                            <button type="button" class="btn btn-outline-secondary rt-btn" data-cmd="bold"><i class="fas fa-bold"></i></button>
                                            <button type="button" class="btn btn-outline-secondary rt-btn" data-cmd="italic"><i class="fas fa-italic"></i></button>
                                            <button type="button" class="btn btn-outline-secondary rt-btn" data-cmd="underline"><i class="fas fa-underline"></i></button>
                                        </div>
                                        <div class="btn-group btn-group-sm me-2" role="group">
                                            <button type="button" class="btn btn-outline-secondary rt-btn" data-cmd="insertUnorderedList"><i class="fas fa-list-ul"></i></button>
                                            <button type="button" class="btn btn-outline-secondary rt-btn" data-cmd="insertOrderedList"><i class="fas fa-list-ol"></i></button>
                                        </div>
                                        <div class="btn-group btn-group-sm me-2" role="group">
                                            <button type="button" class="btn btn-outline-secondary rt-link-btn"><i class="fas fa-link"></i></button>
                                            <button type="button" class="btn btn-outline-secondary rt-btn" data-cmd="removeFormat"><i class="fas fa-eraser"></i></button>
                                        </div>
                                    </div>
                                    <div id="content_editor" class="form-control" contenteditable="true" style="min-height: 220px; overflow-y: auto;"><?= $editorInitialHtml ?></div>
                                    <textarea class="d-none" id="content" name="content" rows="8"></textarea>
                                    <div class="form-text">未入力の場合は上記の構造化入力から本文を自動生成します。</div>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingTags">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTags" aria-expanded="false" aria-controls="collapseTags">
                                    タグ設定
                                </button>
                            </h2>
                            <div id="collapseTags" class="accordion-collapse collapse" aria-labelledby="headingTags" data-bs-parent="#reportAccordion">
                                <div class="accordion-body">
                                    <label for="tags" class="form-label">タグ（カンマ区切り）</label>
                                    <input type="text" class="form-control" id="tags" name="tags" value="<?= implode(',', $reportTags) ?>">
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingPermissions">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapsePermissions" aria-expanded="false" aria-controls="collapsePermissions">
                                    公開範囲設定
                                </button>
                            </h2>
                            <div id="collapsePermissions" class="accordion-collapse collapse" aria-labelledby="headingPermissions" data-bs-parent="#reportAccordion">
                                <div class="accordion-body">
                                    <div class="mb-3">
                                        <label class="form-label">ユーザー</label>
                                        <select class="form-select select2" id="user_permissions" multiple>
                                            <?php foreach ($users as $u): ?>
                                                <?php if ($u['id'] != $this->auth->id()): ?>
                                                    <?php
                                                    $selected = '';
                                                    if ($isEdit) {
                                                        foreach ($permissions as $p) {
                                                            if ($p['type'] === 'user' && (int)$p['id'] === (int)$u['id']) {
                                                                $selected = 'selected';
                                                                break;
                                                            }
                                                        }
                                                    }
                                                    ?>
                                                    <option value="user:<?= $u['id'] ?>" <?= $selected ?>><?= htmlspecialchars($u['display_name']) ?></option>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">組織</label>
                                        <select class="form-select select2" id="organization_permissions" multiple>
                                            <?php foreach ($organizations as $org): ?>
                                                <?php
                                                $selected = '';
                                                if ($isEdit) {
                                                    foreach ($permissions as $p) {
                                                        if ($p['type'] === 'organization' && (int)$p['id'] === (int)$org['id']) {
                                                            $selected = 'selected';
                                                            break;
                                                        }
                                                    }
                                                }
                                                ?>
                                                <option value="organization:<?= $org['id'] ?>" <?= $selected ?>><?= htmlspecialchars($org['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingSchedules">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSchedules" aria-expanded="false" aria-controls="collapseSchedules">
                                    スケジュール関連付け
                                </button>
                            </h2>
                            <div id="collapseSchedules" class="accordion-collapse collapse" aria-labelledby="headingSchedules" data-bs-parent="#reportAccordion">
                                <div class="accordion-body">
                                    <?php if (empty($schedules)): ?>
                                        <p class="text-center">関連付け可能なスケジュールがありません</p>
                                    <?php else: ?>
                                        <div class="list-group mb-3">
                                            <?php foreach ($schedules as $schedule): ?>
                                                <label class="list-group-item">
                                                    <input class="form-check-input me-1" type="checkbox" name="schedules[]" value="<?= $schedule['id'] ?>" <?= in_array($schedule['id'], $selectedSchedules, true) ? 'checked' : '' ?>>
                                                    <div>
                                                        <strong><?= htmlspecialchars($schedule['title']) ?></strong><br>
                                                        <small class="text-muted">
                                                            <?= date('H:i', strtotime($schedule['start_time'])) ?> - <?= date('H:i', strtotime($schedule['end_time'])) ?>
                                                        </small>
                                                    </div>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingTasks">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTasks" aria-expanded="false" aria-controls="collapseTasks">
                                    タスク関連付け
                                </button>
                            </h2>
                            <div id="collapseTasks" class="accordion-collapse collapse" aria-labelledby="headingTasks" data-bs-parent="#reportAccordion">
                                <div class="accordion-body">
                                    <?php if (empty($tasks)): ?>
                                        <p class="text-center">関連付け可能な完了済みタスクがありません</p>
                                    <?php else: ?>
                                        <div class="list-group mb-3">
                                            <?php foreach ($tasks as $task): ?>
                                                <label class="list-group-item">
                                                    <input class="form-check-input me-1" type="checkbox" name="tasks[]" value="<?= $task['id'] ?>" <?= in_array($task['id'], $selectedTasks, true) ? 'checked' : '' ?>>
                                                    <div>
                                                        <strong><?= htmlspecialchars($task['title']) ?></strong><br>
                                                        <small class="text-muted"><?= htmlspecialchars($task['board_name']) ?> / <?= htmlspecialchars($task['list_name']) ?></small>
                                                    </div>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="is_draft" name="is_draft" <?= $isDraft ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_draft">下書きとして保存する</label>
                    </div>

                    <div class="text-center mt-4">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fas fa-save me-2"></i><?= $submitButtonText ?>
                        </button>
                        <?php if ($isEdit): ?>
                            <button type="button" class="btn btn-danger ms-2" id="deleteButton" data-id="<?= $report['id'] ?>">
                                <i class="fas fa-trash me-2"></i>削除
                            </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const isEditMode = <?= json_encode($isEdit) ?>;
    const analysisMasterMap = <?= json_encode($analysisMasters ?? ['projects' => [], 'industries' => [], 'products' => [], 'processes' => []], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    $('.select2').select2({
        placeholder: '選択してください',
        width: '100%'
    });

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function buildMasterOptions(type, selectedId) {
        const map = {
            project: analysisMasterMap.projects || [],
            industry: analysisMasterMap.industries || [],
            product: analysisMasterMap.products || [],
            process: analysisMasterMap.processes || []
        };
        let html = '<option value="">未指定</option>';
        (map[type] || []).forEach(item => {
            const id = String(item.id);
            const selected = selectedId !== undefined && selectedId !== null && String(selectedId) === id ? ' selected' : '';
            html += `<option value="${escapeHtml(id)}"${selected}>${escapeHtml(item.name)}</option>`;
        });
        return html;
    }

    function createDetailItemRow() {
        return `
            <div class="detail-item-row border rounded p-3 mb-3" data-input-type="textarea">
                <div class="row g-2 align-items-start">
                    <div class="col-md-4">
                        <input type="text" class="form-control detail-item-title" placeholder="項目名">
                        <input type="hidden" class="detail-item-key" value="">
                        <input type="hidden" class="detail-item-type" value="textarea">
                    </div>
                    <div class="col-md-7">
                        <textarea class="form-control detail-item-value" rows="2" placeholder="内容"></textarea>
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="button" class="btn btn-outline-danger btn-sm remove-detail-item"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
            </div>`;
    }

    function createActivityRow() {
        return `
            <tr class="activity-row">
                <td><input type="time" class="form-control form-control-sm activity-start"></td>
                <td><input type="time" class="form-control form-control-sm activity-end"></td>
                <td><input type="text" class="form-control form-control-sm activity-type" placeholder="商談/開発"></td>
                <td><input type="text" class="form-control form-control-sm activity-subject" placeholder="件名"></td>
                <td><input type="text" class="form-control form-control-sm activity-result" placeholder="結果"></td>
                <td><input type="text" class="form-control form-control-sm activity-memo" placeholder="補足"></td>
                <td><button type="button" class="btn btn-outline-danger btn-sm remove-activity-row"><i class="fas fa-times"></i></button></td>
            </tr>`;
    }

    function createAnalysisRow() {
        return `
            <tr class="analysis-row">
                <td><select class="form-select form-select-sm analysis-project">${buildMasterOptions('project')}</select></td>
                <td><select class="form-select form-select-sm analysis-industry">${buildMasterOptions('industry')}</select></td>
                <td><select class="form-select form-select-sm analysis-product">${buildMasterOptions('product')}</select></td>
                <td><select class="form-select form-select-sm analysis-process">${buildMasterOptions('process')}</select></td>
                <td><input type="text" class="form-control form-control-sm analysis-activity-type" placeholder="商談/開発"></td>
                <td><input type="number" min="0" step="0.01" class="form-control form-control-sm analysis-planned-amount"></td>
                <td><input type="number" min="0" step="0.01" class="form-control form-control-sm analysis-actual-amount"></td>
                <td><input type="number" min="0" step="0.01" class="form-control form-control-sm analysis-planned-hours"></td>
                <td><input type="number" min="0" step="0.01" class="form-control form-control-sm analysis-actual-hours"></td>
                <td><input type="number" min="0" step="0.01" class="form-control form-control-sm analysis-quantity"></td>
                <td><input type="text" class="form-control form-control-sm analysis-memo" placeholder="補足"></td>
                <td><button type="button" class="btn btn-outline-danger btn-sm remove-analysis-row"><i class="fas fa-times"></i></button></td>
            </tr>`;
    }

    function syncEditorToField() {
        const editor = document.getElementById('content_editor');
        const hidden = document.getElementById('content');
        if (!editor || !hidden) {
            return;
        }
        let html = (editor.innerHTML || '').trim();
        if (html === '<br>' || html === '<div><br></div>' || html === '<p><br></p>') {
            html = '';
        }
        hidden.value = html;
    }

    $('#addDetailItemBtn').on('click', function () {
        $('#detailItemsContainer').append(createDetailItemRow());
    });

    $('#detailItemsContainer').on('click', '.remove-detail-item', function () {
        $(this).closest('.detail-item-row').remove();
    });

    $('#addActivityRowBtn').on('click', function () {
        $('#activityTable tbody').append(createActivityRow());
    });

    $('#activityTable').on('click', '.remove-activity-row', function () {
        const rows = $('#activityTable tbody .activity-row');
        if (rows.length <= 1) {
            rows.find('input').val('');
            return;
        }
        $(this).closest('tr').remove();
    });

    $('#addAnalysisRowBtn').on('click', function () {
        $('#analysisTable tbody').append(createAnalysisRow());
    });

    $('#analysisTable').on('click', '.remove-analysis-row', function () {
        const rows = $('#analysisTable tbody .analysis-row');
        if (rows.length <= 1) {
            rows.find('input').val('');
            rows.find('select').val('');
            return;
        }
        $(this).closest('tr').remove();
    });

    $('#template_selector').on('change', function () {
        const templateId = ($(this).val() || '').trim();
        $('#template_id').val(templateId);
        if (isEditMode) {
            return;
        }
        const nextUrl = templateId
            ? `${BASE_PATH}/daily-report/create?template_id=${encodeURIComponent(templateId)}`
            : `${BASE_PATH}/daily-report/create`;
        window.location.href = nextUrl;
    });

    $('.rt-btn').on('click', function () {
        const cmd = $(this).data('cmd');
        if (!cmd) {
            return;
        }
        document.execCommand(cmd, false, null);
        syncEditorToField();
    });

    $('.rt-link-btn').on('click', function () {
        const url = window.prompt('リンクURLを入力してください', 'https://');
        if (!url) {
            return;
        }
        document.execCommand('createLink', false, url);
        syncEditorToField();
    });

    $('#content_editor').on('input blur', function () {
        syncEditorToField();
    });

    $('#reportForm').off('submit').on('submit', function (e) {
        e.preventDefault();
        syncEditorToField();

        $(this).find('.is-invalid').removeClass('is-invalid');
        $(this).find('.invalid-feedback').text('');

        const permissions = [];
        $('#user_permissions option:selected').each(function () {
            const [type, id] = ($(this).val() || '').split(':');
            if (type && id) permissions.push({ type, id: parseInt(id, 10) });
        });
        $('#organization_permissions option:selected').each(function () {
            const [type, id] = ($(this).val() || '').split(':');
            if (type && id) permissions.push({ type, id: parseInt(id, 10) });
        });

        let tags = $('#tags').val().trim();
        tags = tags ? tags.split(',').map(t => t.trim()).filter(Boolean) : [];

        const schedules = [];
        $('input[name="schedules[]"]:checked').each(function () {
            schedules.push(parseInt($(this).val(), 10));
        });

        const tasks = [];
        $('input[name="tasks[]"]:checked').each(function () {
            tasks.push(parseInt($(this).val(), 10));
        });

        const detailItems = [];
        $('#detailItemsContainer .detail-item-row').each(function () {
            const title = $(this).find('.detail-item-title').val().trim();
            const value = $(this).find('.detail-item-value').val().trim();
            const sectionKey = ($(this).find('.detail-item-key').val() || '').trim();
            const inputType = ($(this).find('.detail-item-type').val() || $(this).data('input-type') || 'textarea').toString();
            if (!title && !value) return;
            detailItems.push({
                section_key: sectionKey,
                title,
                value,
                input_type: inputType
            });
        });

        const activities = [];
        $('#activityTable tbody .activity-row').each(function () {
            const startTime = ($(this).find('.activity-start').val() || '').trim();
            const endTime = ($(this).find('.activity-end').val() || '').trim();
            const activityType = ($(this).find('.activity-type').val() || '').trim();
            const subject = ($(this).find('.activity-subject').val() || '').trim();
            const result = ($(this).find('.activity-result').val() || '').trim();
            const memo = ($(this).find('.activity-memo').val() || '').trim();
            if (!startTime && !endTime && !activityType && !subject && !result && !memo) return;
            activities.push({
                start_time: startTime,
                end_time: endTime,
                activity_type: activityType,
                subject,
                result,
                memo
            });
        });

        const analysisEntries = [];
        $('#analysisTable tbody .analysis-row').each(function () {
            const projectId = ($(this).find('.analysis-project').val() || '').trim();
            const industryId = ($(this).find('.analysis-industry').val() || '').trim();
            const productId = ($(this).find('.analysis-product').val() || '').trim();
            const processId = ($(this).find('.analysis-process').val() || '').trim();
            const activityType = ($(this).find('.analysis-activity-type').val() || '').trim();
            const plannedAmount = ($(this).find('.analysis-planned-amount').val() || '').trim();
            const actualAmount = ($(this).find('.analysis-actual-amount').val() || '').trim();
            const plannedHours = ($(this).find('.analysis-planned-hours').val() || '').trim();
            const actualHours = ($(this).find('.analysis-actual-hours').val() || '').trim();
            const quantity = ($(this).find('.analysis-quantity').val() || '').trim();
            const memo = ($(this).find('.analysis-memo').val() || '').trim();
            if (!projectId && !industryId && !productId && !processId && !activityType && !plannedAmount && !actualAmount && !plannedHours && !actualHours && !quantity && !memo) {
                return;
            }
            analysisEntries.push({
                project_id: projectId || null,
                industry_id: industryId || null,
                product_id: productId || null,
                process_id: processId || null,
                activity_type: activityType,
                planned_amount: plannedAmount,
                actual_amount: actualAmount,
                planned_hours: plannedHours,
                actual_hours: actualHours,
                quantity: quantity,
                memo: memo
            });
        });

        const deleteAttachmentIds = [];
        $('.delete-attachment-check:checked').each(function () {
            const id = parseInt($(this).val(), 10);
            if (!Number.isNaN(id) && id > 0) {
                deleteAttachmentIds.push(id);
            }
        });

        const isDraft = $('#is_draft').is(':checked');
        const payload = {
            report_date: $('#report_date').val(),
            title: $('#title').val(),
            content: $('#content').val(),
            content_format: 'html',
            summary_text: $('#summary_text').val(),
            issues_text: $('#issues_text').val(),
            tomorrow_plan_text: $('#tomorrow_plan_text').val(),
            reflection_text: $('#reflection_text').val(),
            work_minutes: parseInt($('#work_minutes').val() || '0', 10),
            template_id: $('#template_id').val() || null,
            detail_items: detailItems,
            activities: activities,
            analysis_entries: analysisEntries,
            tags,
            permissions,
            schedules,
            tasks,
            delete_attachment_ids: deleteAttachmentIds,
            status: isDraft ? 'draft' : 'published'
        };

        const submitData = new FormData();
        submitData.append('payload', JSON.stringify(payload));
        const files = document.getElementById('attachments')?.files || [];
        for (let i = 0; i < files.length; i++) {
            submitData.append('attachments[]', files[i]);
        }

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: submitData,
            processData: false,
            contentType: false,
            dataType: 'json',
            beforeSend: function () {
                $('button[type="submit"]').prop('disabled', true);
            },
            success: function (response) {
                if (response.success) {
                    window.location.href = response.data.redirect;
                } else {
                    alert(response.error || 'エラーが発生しました');
                    if (response.validation) {
                        for (const field in response.validation) {
                            const errorMsg = response.validation[field];
                            if (field === 'content') {
                                $('#content_editor').addClass('is-invalid');
                                $('#content_editor').nextAll('.invalid-feedback').first().text(errorMsg);
                            } else {
                                $(`#${field}`).addClass('is-invalid');
                                $(`#${field}`).next('.invalid-feedback').text(errorMsg);
                            }
                        }
                    }
                }
            },
            error: function () {
                alert('ネットワークエラーが発生しました');
            },
            complete: function () {
                $('button[type="submit"]').prop('disabled', false);
            }
        });

        return false;
    });

    $('#deleteButton').on('click', function () {
        if (!confirm('本当にこの日報を削除しますか？この操作は元に戻せません。')) {
            return;
        }

        const reportId = $(this).data('id');
        $.ajax({
            url: `${BASE_PATH}/api/daily-report/${reportId}`,
            type: 'DELETE',
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    window.location.href = response.data.redirect;
                } else {
                    alert(response.error || 'エラーが発生しました');
                }
            },
            error: function () {
                alert('ネットワークエラーが発生しました');
            }
        });
    });

    syncEditorToField();
});
</script>

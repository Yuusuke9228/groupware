<!-- views/webdatabase/records.php -->
<?php
$selectedSettings = isset($selectedViewSettings) && is_array($selectedViewSettings) ? $selectedViewSettings : [];
$selectedViewType = isset($selectedSettings['view_type']) ? (string)$selectedSettings['view_type'] : 'list';
if (!in_array($selectedViewType, ['list', 'aggregate', 'chart'], true)) {
    $selectedViewType = 'list';
}
$selectedVisibleFieldIds = [];
if (!empty($selectedSettings['visible_fields']) && is_array($selectedSettings['visible_fields'])) {
    foreach ($selectedSettings['visible_fields'] as $fid) {
        $selectedVisibleFieldIds[] = (int)$fid;
    }
}
$displayFields = [];
if (!empty($selectedVisibleFieldIds)) {
    foreach ($fields as $field) {
        if (in_array((int)$field['id'], $selectedVisibleFieldIds, true)) {
            $displayFields[] = $field;
        }
    }
}
if (empty($displayFields)) {
    foreach ($fields as $field) {
        if (!empty($field['is_title_field'])) {
            continue;
        }
        $displayFields[] = $field;
        if (count($displayFields) >= 5) {
            break;
        }
    }
}
$numberFields = [];
foreach ($fields as $field) {
    if (in_array((string)$field['type'], ['number', 'currency', 'percent'], true)) {
        $numberFields[] = $field;
    }
}
$aggregate = isset($selectedSettings['aggregate']) && is_array($selectedSettings['aggregate']) ? $selectedSettings['aggregate'] : [];
?>
<div class="container-fluid mt-4">
    <div class="row mb-3">
        <div class="col-md-6">
            <h1><?= htmlspecialchars($database['name']) ?> - レコード一覧</h1>
        </div>
        <div class="col-md-6 text-end">
            <div class="btn-group">
                <a href="<?= BASE_PATH ?>/webdatabase/create-record/<?= $database['id'] ?>" class="btn btn-primary">
                    <i class="fas fa-plus"></i> 新規レコード作成
                </a>
                <button type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="visually-hidden">ドロップダウン</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="<?= BASE_PATH ?>/webdatabase/export-csv/<?= $database['id'] ?>"><i class="fas fa-download"></i> CSVエクスポート</a></li>
                    <li><a class="dropdown-item" href="<?= BASE_PATH ?>/webdatabase/import-csv/<?= $database['id'] ?>"><i class="fas fa-upload"></i> CSVインポート</a></li>
                </ul>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label for="search-records" class="form-label form-label-sm mb-1">検索</label>
                    <input type="text" id="search-records" class="form-control form-control-sm" placeholder="レコードを検索...">
                </div>
                <div class="col-md-3">
                    <label for="view-selector" class="form-label form-label-sm mb-1">保存ビュー</label>
                    <select id="view-selector" class="form-select form-select-sm">
                        <option value="">標準ビュー</option>
                        <?php foreach (($views ?? []) as $view): ?>
                            <option
                                value="<?= (int)$view['id'] ?>"
                                data-settings='<?= htmlspecialchars((string)($view['settings'] ?? '{}'), ENT_QUOTES, 'UTF-8') ?>'
                                data-scope="<?= htmlspecialchars((string)($view['scope_type'] ?? 'private')) ?>"
                                data-organization-id="<?= (int)($view['organization_id'] ?? 0) ?>"
                                <?= (int)($selectedViewId ?? 0) === (int)$view['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string)$view['name']) ?>
                                <?php if (!empty($view['scope_type'])): ?>
                                    [<?= htmlspecialchars((string)$view['scope_type']) ?>]
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="view-type" class="form-label form-label-sm mb-1">ビュー種別</label>
                    <select id="view-type" class="form-select form-select-sm">
                        <option value="list" <?= $selectedViewType === 'list' ? 'selected' : '' ?>>一覧</option>
                        <option value="aggregate" <?= $selectedViewType === 'aggregate' ? 'selected' : '' ?>>集計</option>
                        <option value="chart" <?= $selectedViewType === 'chart' ? 'selected' : '' ?>>グラフ</option>
                    </select>
                </div>
                <div class="col-md-3 text-end">
                    <button class="btn btn-outline-secondary btn-sm" type="button" id="filter-button" data-bs-toggle="collapse" data-bs-target="#filter-panel"><i class="fas fa-filter"></i> フィルター</button>
                    <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#column-panel"><i class="fas fa-columns"></i> 表示カラム</button>
                </div>
            </div>
            <div class="row mt-3 g-2">
                <div class="col-md-4">
                    <label for="view-name" class="form-label form-label-sm mb-1">保存名</label>
                    <input type="text" id="view-name" class="form-control form-control-sm" placeholder="例: 月次売上分析">
                </div>
                <div class="col-md-2">
                    <label for="view-scope" class="form-label form-label-sm mb-1">範囲</label>
                    <select id="view-scope" class="form-select form-select-sm">
                        <option value="private">ユーザー</option>
                        <option value="organization">組織</option>
                        <option value="global">全体</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="view-organization" class="form-label form-label-sm mb-1">組織</label>
                    <select id="view-organization" class="form-select form-select-sm">
                        <option value="">組織を選択</option>
                        <?php foreach (($userOrganizations ?? []) as $org): ?>
                            <option value="<?= (int)$org['id'] ?>"><?= htmlspecialchars((string)$org['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex justify-content-end gap-2 align-items-end">
                    <button type="button" id="save-view-btn" class="btn btn-sm btn-primary">保存</button>
                    <button type="button" id="delete-view-btn" class="btn btn-sm btn-outline-danger">削除</button>
                </div>
            </div>
        </div>

        <div class="collapse" id="column-panel">
            <div class="card-body bg-light border-top">
                <div class="row g-2">
                    <?php foreach ($fields as $field): ?>
                        <div class="col-md-3 col-sm-4">
                            <div class="form-check">
                                <input class="form-check-input visible-field-check" type="checkbox" id="visible-field-<?= (int)$field['id'] ?>" value="<?= (int)$field['id'] ?>" <?= in_array((int)$field['id'], array_column($displayFields, 'id')) ? 'checked' : '' ?>>
                                <label class="form-check-label small" for="visible-field-<?= (int)$field['id'] ?>"><?= htmlspecialchars((string)$field['name']) ?></label>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="collapse" id="filter-panel">
            <div class="card-body bg-light border-top">
                <form id="filter-form">
                    <div class="row" id="filter-fields">
                        <?php foreach ($fields as $field): ?>
                            <?php if ($field['is_filterable']): ?>
                                <div class="col-md-4 mb-3">
                                    <label for="filter-<?= $field['id'] ?>" class="form-label"><?= htmlspecialchars($field['name']) ?></label>
                                    <?php if (in_array($field['type'], ['select', 'radio', 'checkbox'])): ?>
                                        <select class="form-select filter-field" id="filter-<?= $field['id'] ?>" name="filters[<?= $field['id'] ?>]">
                                            <option value="">すべて</option>
                                            <?php $options = json_decode($field['options'], true); if ($options) { foreach ($options as $option) { echo '<option value="' . htmlspecialchars($option['value']) . '">' . htmlspecialchars($option['label']) . '</option>'; } } ?>
                                        </select>
                                    <?php elseif ($field['type'] == 'user'): ?>
                                        <select class="form-select filter-field" id="filter-<?= $field['id'] ?>" name="filters[<?= $field['id'] ?>]"><option value="">すべて</option></select>
                                    <?php elseif ($field['type'] == 'organization'): ?>
                                        <select class="form-select filter-field" id="filter-<?= $field['id'] ?>" name="filters[<?= $field['id'] ?>]"><option value="">すべて</option></select>
                                    <?php else: ?>
                                        <input type="text" class="form-control filter-field" id="filter-<?= $field['id'] ?>" name="filters[<?= $field['id'] ?>]">
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-outline-secondary" id="reset-filter-btn">フィルターをリセット</button>
                        <button type="button" class="btn btn-primary" id="apply-filter-btn">適用</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="card mb-3 d-none" id="analytics-config-card">
        <div class="card-header">集計設定</div>
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-3">
                    <label for="aggregate-group-field" class="form-label form-label-sm mb-1">グループ項目</label>
                    <select id="aggregate-group-field" class="form-select form-select-sm">
                        <option value="">選択してください</option>
                        <?php foreach ($fields as $field): ?>
                            <option value="<?= (int)$field['id'] ?>" <?= (int)($aggregate['group_field_id'] ?? 0) === (int)$field['id'] ? 'selected' : '' ?>><?= htmlspecialchars((string)$field['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="aggregate-metric" class="form-label form-label-sm mb-1">集計方法</label>
                    <select id="aggregate-metric" class="form-select form-select-sm">
                        <option value="count" <?= ($aggregate['metric'] ?? 'count') === 'count' ? 'selected' : '' ?>>件数</option>
                        <option value="sum" <?= ($aggregate['metric'] ?? '') === 'sum' ? 'selected' : '' ?>>合計</option>
                        <option value="avg" <?= ($aggregate['metric'] ?? '') === 'avg' ? 'selected' : '' ?>>平均</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="aggregate-metric-field" class="form-label form-label-sm mb-1">数値項目</label>
                    <select id="aggregate-metric-field" class="form-select form-select-sm">
                        <option value="">(件数時は不要)</option>
                        <?php foreach ($numberFields as $field): ?>
                            <option value="<?= (int)$field['id'] ?>" <?= (int)($aggregate['metric_field_id'] ?? 0) === (int)$field['id'] ? 'selected' : '' ?>><?= htmlspecialchars((string)$field['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="aggregate-date-grain" class="form-label form-label-sm mb-1">日付単位</label>
                    <select id="aggregate-date-grain" class="form-select form-select-sm">
                        <option value="none" <?= ($aggregate['date_grain'] ?? 'none') === 'none' ? 'selected' : '' ?>>なし</option>
                        <option value="day" <?= ($aggregate['date_grain'] ?? '') === 'day' ? 'selected' : '' ?>>日</option>
                        <option value="month" <?= ($aggregate['date_grain'] ?? '') === 'month' ? 'selected' : '' ?>>月</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="chart-type" class="form-label form-label-sm mb-1">グラフ種別</label>
                    <select id="chart-type" class="form-select form-select-sm">
                        <option value="bar" <?= ($aggregate['chart_type'] ?? 'bar') === 'bar' ? 'selected' : '' ?>>棒</option>
                        <option value="line" <?= ($aggregate['chart_type'] ?? '') === 'line' ? 'selected' : '' ?>>折れ線</option>
                        <option value="pie" <?= ($aggregate['chart_type'] ?? '') === 'pie' ? 'selected' : '' ?>>円</option>
                    </select>
                </div>
            </div>
            <div class="text-end mt-3">
                <button type="button" class="btn btn-sm btn-primary" id="run-analytics-btn"><i class="fas fa-chart-bar me-1"></i>集計実行</button>
            </div>
        </div>
    </div>

    <div class="card" id="record-list-card">
        <div class="card-body">
            <div class="table-responsive" id="record-table-wrap">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr id="record-table-head">
                            <th>タイトル</th>
                            <?php foreach ($displayFields as $field): ?>
                                <th class="sortable" data-field="<?= (int)$field['id'] ?>" data-field-id="<?= (int)$field['id'] ?>"><?= htmlspecialchars((string)$field['name']) ?></th>
                            <?php endforeach; ?>
                            <th>作成者</th>
                            <th>作成日時</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody id="record-list">
                        <tr>
                            <td colspan="<?= count($displayFields) + 4 ?>" class="text-center">
                                <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="d-none" id="analytics-panel">
                <div class="table-responsive mb-3">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>分類</th>
                                <th>件数</th>
                                <th>値</th>
                            </tr>
                        </thead>
                        <tbody id="analytics-list"></tbody>
                    </table>
                </div>
                <div id="analytics-chart-wrap" class="d-none">
                    <canvas id="analytics-chart" height="110"></canvas>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3" id="record-pagination-wrap">
                <div>
                    <span id="total-records">0</span> 件中 <span id="showing-records">0-0</span> 件を表示
                </div>
                <nav aria-label="Page navigation">
                    <ul class="pagination" id="pagination"></ul>
                </nav>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between mt-3">
        <a href="<?= BASE_PATH ?>/webdatabase/fields/<?= $database['id'] ?>" class="btn btn-secondary">フィールド設定に戻る</a>
        <div class="btn-group">
            <a href="<?= BASE_PATH ?>/webdatabase/export-csv/<?= $database['id'] ?>" class="btn btn-outline-primary"><i class="fas fa-download"></i> CSVエクスポート</a>
            <a href="<?= BASE_PATH ?>/webdatabase/import-csv/<?= $database['id'] ?>" class="btn btn-outline-primary"><i class="fas fa-upload"></i> CSVインポート</a>
        </div>
    </div>
</div>

<template id="record-row-template">
    <tr>
        <td><a href="<?= BASE_PATH ?>/webdatabase/view/{{database_id}}/{{id}}">{{title}}</a></td>
        {{field_values}}
        <td>{{creator_name}}</td>
        <td>{{created_at}}</td>
        <td>
            <div class="btn-group" role="group">
                <a href="<?= BASE_PATH ?>/webdatabase/edit/{{database_id}}/{{id}}" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i> 編集</a>
                <button class="btn btn-sm btn-outline-danger btn-delete" data-url="<?= BASE_PATH ?>/api/webdatabase/record/{{database_id}}/{{id}}" data-confirm="このレコードを削除しますか？"><i class="fas fa-trash"></i> 削除</button>
            </div>
        </td>
    </tr>
</template>

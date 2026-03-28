<?php
$isEdit = isset($template) && $template !== null;
$formTitle = $isEdit ? 'テンプレート編集' : '新規テンプレート作成';
$formAction = $isEdit ? BASE_PATH . "/api/daily-report/template/{$template['id']}" : BASE_PATH . "/api/daily-report/template";
$submitButtonText = $isEdit ? '更新する' : '作成する';
$templateContentFormat = $isEdit && ($template['content_format'] ?? 'text') === 'html' ? 'html' : 'text';
$templateRawContent = $isEdit ? (string)($template['content'] ?? '') : '';
$templateEditorInitialHtml = $templateContentFormat === 'html'
    ? $templateRawContent
    : nl2br(htmlspecialchars($templateRawContent, ENT_QUOTES, 'UTF-8'));

$templateSections = [];
if ($isEdit && !empty($template['sections']) && is_array($template['sections'])) {
    $templateSections = $template['sections'];
}
if (empty($templateSections)) {
    $templateSections = [
        [
            'section_key' => 'summary',
            'title' => '本日の成果',
            'input_type' => 'textarea',
            'is_required' => 1,
            'placeholder_text' => '本日の主な成果を入力してください'
        ],
        [
            'section_key' => 'issues',
            'title' => '課題・問題点',
            'input_type' => 'textarea',
            'is_required' => 0,
            'placeholder_text' => '課題や相談事項'
        ],
        [
            'section_key' => 'tomorrow',
            'title' => '明日の予定',
            'input_type' => 'textarea',
            'is_required' => 0,
            'placeholder_text' => '明日の予定'
        ]
    ];
}
?>

<div class="container-fluid pt-4 px-4">
    <div class="row g-4">
        <div class="col-12">
            <div class="bg-light rounded p-4 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="mb-0"><?= $formTitle ?></h3>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="<?= BASE_PATH ?>/daily-report" class="btn btn-outline-secondary btn-sm">ダッシュボード</a>
                        <a href="<?= BASE_PATH ?>/daily-report/templates" class="btn btn-secondary">戻る</a>
                    </div>
                </div>

                <form id="templateForm" method="POST" action="<?= $formAction ?>">
                    <input type="hidden" id="content_format" value="<?= htmlspecialchars($templateContentFormat) ?>">
                    <div class="card mb-4">
                        <div class="card-header"><strong>基本設定</strong></div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="title" class="form-label">テンプレート名 <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="title" name="title" value="<?= $isEdit ? htmlspecialchars($template['title']) : '' ?>" required maxlength="100">
                                <div class="invalid-feedback"></div>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">説明</label>
                                <input type="text" class="form-control" id="description" value="<?= $isEdit ? htmlspecialchars($template['description'] ?? '') : '' ?>" maxlength="255" placeholder="テンプレートの用途を記載">
                            </div>

                            <div class="mb-3">
                                <label for="content" class="form-label">自由記述テンプレート本文</label>
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
                                <div id="content_editor" class="form-control" contenteditable="true" style="min-height: 220px; overflow-y: auto;"><?= $templateEditorInitialHtml ?></div>
                                <textarea class="d-none" id="content" name="content" rows="6"></textarea>
                                <div class="form-text">構造化項目だけで運用する場合は空欄でも利用できます。</div>
                            </div>

                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="is_public" name="is_public" <?= ($isEdit && !empty($template['is_public'])) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="is_public">このテンプレートを全ユーザーに公開する</label>
                            </div>

                            <div class="mb-3">
                                <label for="organization_ids" class="form-label">配布先組織</label>
                                <select class="form-select select2" id="organization_ids" name="organization_ids[]" multiple>
                                    <?php foreach (($organizations ?? []) as $org): ?>
                                        <option value="<?= (int)$org['id'] ?>" <?= in_array((int)$org['id'], $templateOrganizationIds ?? [], true) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($org['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">未選択時は公開設定に従います。</div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <strong>構造化項目</strong>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="addSectionBtn">
                                <i class="fas fa-plus me-1"></i>項目追加
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm mb-0" id="templateSectionTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="min-width:120px;">項目キー</th>
                                            <th style="min-width:180px;">項目名</th>
                                            <th style="min-width:140px;">入力種別</th>
                                            <th>プレースホルダ</th>
                                            <th style="width:100px;">必須</th>
                                            <th style="width:60px;"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($templateSections as $section): ?>
                                            <tr class="section-row">
                                                <td><input type="text" class="form-control form-control-sm section-key" value="<?= htmlspecialchars($section['section_key'] ?? '') ?>" placeholder="daily_work"></td>
                                                <td><input type="text" class="form-control form-control-sm section-title" value="<?= htmlspecialchars($section['title'] ?? '') ?>" placeholder="本日の業務"></td>
                                                <td>
                                                    <select class="form-select form-select-sm section-type">
                                                        <?php $type = $section['input_type'] ?? 'textarea'; ?>
                                                        <option value="textarea" <?= $type === 'textarea' ? 'selected' : '' ?>>複数行</option>
                                                        <option value="text" <?= $type === 'text' ? 'selected' : '' ?>>1行テキスト</option>
                                                        <option value="checklist" <?= $type === 'checklist' ? 'selected' : '' ?>>チェックリスト</option>
                                                        <option value="number" <?= $type === 'number' ? 'selected' : '' ?>>数値</option>
                                                        <option value="rating" <?= $type === 'rating' ? 'selected' : '' ?>>評価</option>
                                                        <option value="toggle" <?= $type === 'toggle' ? 'selected' : '' ?>>ON/OFF</option>
                                                    </select>
                                                </td>
                                                <td><input type="text" class="form-control form-control-sm section-placeholder" value="<?= htmlspecialchars($section['placeholder_text'] ?? '') ?>" placeholder="入力ヒント"></td>
                                                <td class="text-center"><input type="checkbox" class="form-check-input section-required" <?= !empty($section['is_required']) ? 'checked' : '' ?>></td>
                                                <td><button type="button" class="btn btn-outline-danger btn-sm remove-section"><i class="fas fa-trash"></i></button></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="text-center mt-4">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fas fa-save me-2"></i><?= $submitButtonText ?>
                        </button>
                        <?php if ($isEdit): ?>
                            <button type="button" class="btn btn-danger ms-2" id="deleteButton" data-id="<?= $template['id'] ?>">
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

    $('.select2').select2({
        placeholder: '選択してください',
        width: '100%'
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

    function sectionRowTemplate() {
        return `
            <tr class="section-row">
                <td><input type="text" class="form-control form-control-sm section-key" placeholder="daily_work"></td>
                <td><input type="text" class="form-control form-control-sm section-title" placeholder="本日の業務"></td>
                <td>
                    <select class="form-select form-select-sm section-type">
                        <option value="textarea">複数行</option>
                        <option value="text">1行テキスト</option>
                        <option value="checklist">チェックリスト</option>
                        <option value="number">数値</option>
                        <option value="rating">評価</option>
                        <option value="toggle">ON/OFF</option>
                    </select>
                </td>
                <td><input type="text" class="form-control form-control-sm section-placeholder" placeholder="入力ヒント"></td>
                <td class="text-center"><input type="checkbox" class="form-check-input section-required"></td>
                <td><button type="button" class="btn btn-outline-danger btn-sm remove-section"><i class="fas fa-trash"></i></button></td>
            </tr>`;
    }

    $('#addSectionBtn').on('click', function () {
        $('#templateSectionTable tbody').append(sectionRowTemplate());
    });

    $('#templateSectionTable').on('click', '.remove-section', function () {
        const rows = $('#templateSectionTable tbody .section-row');
        if (rows.length <= 1) {
            rows.find('input[type="text"]').val('');
            rows.find('.section-type').val('textarea');
            rows.find('.section-required').prop('checked', false);
            return;
        }
        $(this).closest('tr').remove();
    });

    $('#templateForm').off('submit').on('submit', function (e) {
        e.preventDefault();
        syncEditorToField();

        const sections = [];
        $('#templateSectionTable tbody .section-row').each(function () {
            const sectionKey = ($(this).find('.section-key').val() || '').trim();
            const title = ($(this).find('.section-title').val() || '').trim();
            const inputType = ($(this).find('.section-type').val() || 'textarea').trim();
            const placeholderText = ($(this).find('.section-placeholder').val() || '').trim();
            const required = $(this).find('.section-required').is(':checked');

            if (!title && !sectionKey) {
                return;
            }

            sections.push({
                section_key: sectionKey,
                title: title,
                input_type: inputType,
                placeholder_text: placeholderText,
                is_required: required
            });
        });

        const formData = {
            title: $('#title').val(),
            description: $('#description').val(),
            content: $('#content').val(),
            content_format: 'html',
            is_public: $('#is_public').is(':checked'),
            organization_ids: $('#organization_ids').val() || [],
            sections: sections,
            isEdit: <?= json_encode($isEdit) ?>
        };

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(formData),
            dataType: 'json',
            beforeSend: function () {
                $('button[type="submit"]').prop('disabled', true);
            },
            success: function (response) {
                if (response.success) {
                    toastr.success(response.message || 'テンプレートを保存しました');
                    setTimeout(function () {
                        window.location.href = response.data.redirect;
                    }, 600);
                } else {
                    toastr.error(response.error || 'エラーが発生しました');
                }
            },
            error: function () {
                toastr.error('ネットワークエラーが発生しました');
            },
            complete: function () {
                $('button[type="submit"]').prop('disabled', false);
            }
        });
        return false;
    });

    syncEditorToField();

    $('#deleteButton').on('click', function () {
        if (!confirm('本当にこのテンプレートを削除しますか？この操作は元に戻せません。')) {
            return;
        }

        const templateId = $(this).data('id');
        $.ajax({
            url: `${BASE_PATH}/api/daily-report/template/${templateId}`,
            type: 'DELETE',
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    toastr.success(response.message || 'テンプレートを削除しました');
                    setTimeout(function () {
                        window.location.href = response.data.redirect;
                    }, 600);
                } else {
                    toastr.error(response.error || 'エラーが発生しました');
                }
            },
            error: function () {
                toastr.error('ネットワークエラーが発生しました');
            }
        });
    });
});
</script>

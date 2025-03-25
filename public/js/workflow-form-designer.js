/**
 * GroupWare - ワークフローフォームデザイナーJS
 */

const FormDesigner = {
    // フォームフィールド定義リスト
    fieldDefinitions: [],

    // フィールドカウンター
    fieldCounter: 0,

    // 初期化
    init: function () {
        this.templateId = $('#template-id').val();

        // 既存のフォーム定義を読み込む
        this.loadFormDefinitions();

        // ドラッガブル要素の初期化
        this.initDraggable();

        // ドロップゾーンの初期化
        this.initDroppable();

        // イベントハンドラの設定
        this.setupEventHandlers();
    },

    // フォーム定義の読み込み
    loadFormDefinitions: function () {
        // APIからフォーム定義を取得
        App.apiGet(`/workflow/templates/${this.templateId}/form`)
            .then(response => {
                if (response.success) {
                    this.fieldDefinitions = response.data.form_definitions || [];

                    // フィールドカウンターを初期化（既存の最大値より大きい値に）
                    this.updateFieldCounter();

                    // 既存のフィールドを表示
                    this.renderExistingFields();
                } else {
                    App.showNotification('フォーム定義の読み込みに失敗しました', 'error');
                }
            })
            .catch(error => {
                console.error('Failed to load form definitions:', error);
                App.showNotification('フォーム定義の読み込みに失敗しました', 'error');
            });
    },

    // フィールドカウンターの更新
    updateFieldCounter: function () {
        if (this.fieldDefinitions.length > 0) {
            // field_idからカウンター部分を抽出して最大値を取得
            const counterRegex = /field(\d+)$/;
            let maxCounter = 0;

            this.fieldDefinitions.forEach(field => {
                const match = field.field_id.match(counterRegex);
                if (match && match[1]) {
                    const counter = parseInt(match[1], 10);
                    if (counter > maxCounter) {
                        maxCounter = counter;
                    }
                }
            });

            this.fieldCounter = maxCounter + 1;
        } else {
            this.fieldCounter = 1;
        }
    },

    // 既存のフィールドを表示
    renderExistingFields: function () {
        const container = $('#form-container');
        container.empty();

        if (this.fieldDefinitions.length > 0) {
            this.fieldDefinitions.sort((a, b) => a.sort_order - b.sort_order);

            this.fieldDefinitions.forEach(field => {
                const fieldHtml = this.createFieldHtml(field);
                container.append(fieldHtml);
            });

            // 削除ボタンのイベントを再設定
            this.setupFieldEvents();
        } else {
            container.html('<div class="text-center p-5 text-muted">フォーム要素をドラッグ＆ドロップしてください</div>');
        }
    },

    // ドラッガブル要素の初期化
    initDraggable: function () {
        $('.form-item').draggable({
            connectToSortable: '#form-container',
            helper: 'clone',
            revert: 'invalid',
            start: function (event, ui) {
                ui.helper.addClass('dragging');
            },
            stop: function (event, ui) {
                ui.helper.removeClass('dragging');
            }
        });

        // フォームコンテナをソータブルにする
        $('#form-container').sortable({
            placeholder: 'form-item-placeholder',
            update: function (event, ui) {
                // ドロップ時に新しいフィールドを作成
                if (ui.item.hasClass('form-item') && !ui.item.hasClass('existing-field')) {
                    const fieldType = ui.item.data('type');
                    const newField = FormDesigner.createNewField(fieldType);

                    // 元のドラッグ要素を新しいフィールドに置き換え
                    ui.item.replaceWith(newField);

                    // 新しいフィールドのイベントを設定
                    FormDesigner.setupFieldEvents();

                    // フィールド定義の並び順を更新
                    FormDesigner.updateFieldOrder();
                }

                // 並び順の更新
                FormDesigner.updateFieldOrder();
            }
        });
    },

    // ドロップゾーンの初期化
    initDroppable: function () {
        $('#form-container').droppable({
            accept: '.form-item',
            drop: function (event, ui) {
                // ソータブルで処理するため、ここでは何もしない
            }
        });
    },

    // イベントハンドラの設定
    setupEventHandlers: function () {
        // フォーム保存ボタン
        $('#btn-save-form').on('click', () => {
            this.saveFormDefinitions();
        });

        // フィールドのイベント設定
        this.setupFieldEvents();
    },

    // フィールドのイベント設定
    setupFieldEvents: function () {
        // 編集ボタンクリック
        $('.btn-edit-field').off('click').on('click', function (e) {
            e.preventDefault();
            e.stopPropagation();

            const fieldId = $(this).closest('.form-field').data('field-id');
            FormDesigner.showFieldEditModal(fieldId);
        });

        // 削除ボタンクリック
        $('.btn-delete-field').off('click').on('click', function (e) {
            e.preventDefault();
            e.stopPropagation();

            const fieldId = $(this).closest('.form-field').data('field-id');
            FormDesigner.deleteField(fieldId);
        });
    },

    // 新しいフィールドを作成
    createNewField: function (fieldType) {
        const fieldId = 'field' + this.fieldCounter++;
        let label = this.getDefaultLabelForType(fieldType);

        // 新しいフィールド定義を作成
        const fieldDefinition = {
            id: null, // API保存時に割り当てられる
            template_id: this.templateId,
            field_id: fieldId,
            field_type: fieldType,
            label: label,
            placeholder: '',
            help_text: '',
            options: fieldType === 'select' || fieldType === 'radio' || fieldType === 'checkbox' ? '[]' : null,
            validation: '{}',
            is_required: false,
            sort_order: this.fieldDefinitions.length
        };

        // フィールド定義リストに追加
        this.fieldDefinitions.push(fieldDefinition);

        // HTMLを生成
        return this.createFieldHtml(fieldDefinition);
    },

    // フィールドタイプに応じたデフォルトラベルを取得
    getDefaultLabelForType: function (fieldType) {
        switch (fieldType) {
            case 'text':
                return 'テキスト入力';
            case 'textarea':
                return 'テキストエリア';
            case 'select':
                return 'セレクトボックス';
            case 'radio':
                return 'ラジオボタン';
            case 'checkbox':
                return 'チェックボックス';
            case 'date':
                return '日付';
            case 'number':
                return '数値';
            case 'file':
                return 'ファイル';
            case 'heading':
                return '見出し';
            case 'hidden':
                return '隠しフィールド';
            default:
                return 'フィールド';
        }
    },

    // フィールドのHTMLを生成
    createFieldHtml: function (field) {
        const isRequired = field.is_required ? '<span class="text-danger">*</span>' : '';
        const requiredClass = field.is_required ? 'required-field' : '';
        const helpText = field.help_text ? `<small class="form-text text-muted">${field.help_text}</small>` : '';

        // フィールドタイプに応じたHTMLを生成
        let fieldHtml = '';
        switch (field.field_type) {
            case 'text':
                fieldHtml = `
                    <div class="mb-3">
                        <label for="${field.field_id}" class="form-label">${field.label} ${isRequired}</label>
                        <input type="text" class="form-control ${requiredClass}" id="${field.field_id}" name="${field.field_id}" placeholder="${field.placeholder}" readonly>
                        ${helpText}
                    </div>
                `;
                break;

            case 'textarea':
                fieldHtml = `
                    <div class="mb-3">
                        <label for="${field.field_id}" class="form-label">${field.label} ${isRequired}</label>
                        <textarea class="form-control ${requiredClass}" id="${field.field_id}" name="${field.field_id}" rows="3" placeholder="${field.placeholder}" readonly></textarea>
                        ${helpText}
                    </div>
                `;
                break;

            case 'select':
                fieldHtml = `
                    <div class="mb-3">
                        <label for="${field.field_id}" class="form-label">${field.label} ${isRequired}</label>
                        <select class="form-select ${requiredClass}" id="${field.field_id}" name="${field.field_id}" readonly>
                            <option value="">${field.placeholder || '選択してください'}</option>
                        </select>
                        ${helpText}
                    </div>
                `;
                break;

            case 'radio':
                fieldHtml = `
                    <div class="mb-3">
                        <label class="form-label">${field.label} ${isRequired}</label>
                        <div class="form-check">
                            <input class="form-check-input ${requiredClass}" type="radio" name="${field.field_id}" id="${field.field_id}_1" value="option1" disabled>
                            <label class="form-check-label" for="${field.field_id}_1">
                                オプション 1
                            </label>
                        </div>
                        ${helpText}
                    </div>
                `;
                break;

            case 'checkbox':
                fieldHtml = `
                    <div class="mb-3">
                        <label class="form-label">${field.label} ${isRequired}</label>
                        <div class="form-check">
                            <input class="form-check-input ${requiredClass}" type="checkbox" name="${field.field_id}" id="${field.field_id}_1" value="option1" disabled>
                            <label class="form-check-label" for="${field.field_id}_1">
                                オプション 1
                            </label>
                        </div>
                        ${helpText}
                    </div>
                `;
                break;

            case 'date':
                fieldHtml = `
                    <div class="mb-3">
                        <label for="${field.field_id}" class="form-label">${field.label} ${isRequired}</label>
                        <input type="date" class="form-control ${requiredClass}" id="${field.field_id}" name="${field.field_id}" readonly>
                        ${helpText}
                    </div>
                `;
                break;

            case 'number':
                fieldHtml = `
                    <div class="mb-3">
                        <label for="${field.field_id}" class="form-label">${field.label} ${isRequired}</label>
                        <input type="number" class="form-control ${requiredClass}" id="${field.field_id}" name="${field.field_id}" placeholder="${field.placeholder}" readonly>
                        ${helpText}
                    </div>
                `;
                break;

            case 'file':
                fieldHtml = `
                    <div class="mb-3">
                        <label for="${field.field_id}" class="form-label">${field.label} ${isRequired}</label>
                        <input type="file" class="form-control ${requiredClass}" id="${field.field_id}" name="${field.field_id}" readonly>
                        ${helpText}
                    </div>
                `;
                break;

            case 'heading':
                fieldHtml = `
                    <div class="mb-3">
                        <h4>${field.label}</h4>
                        ${helpText}
                    </div>
                `;
                break;

            case 'hidden':
                fieldHtml = `
                    <div class="mb-3">
                        <div class="alert alert-secondary">
                            <i class="fas fa-eye-slash"></i> 隠しフィールド: ${field.label}
                        </div>
                    </div>
                `;
                break;
        }

        // フィールドをラッパーで囲む
        return `
            <div class="form-field card mb-3" data-field-id="${field.field_id}">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>${field.label} [${field.field_type}]</span>
                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-primary btn-edit-field">
                            <i class="fas fa-edit"></i> 編集
                        </button>
                        <button type="button" class="btn btn-sm btn-danger btn-delete-field">
                            <i class="fas fa-trash"></i> 削除
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    ${fieldHtml}
                </div>
            </div>
        `;
    },

    // フィールド編集モーダルを表示
    showFieldEditModal: function (fieldId) {
        // フィールド定義を取得
        const field = this.fieldDefinitions.find(f => f.field_id === fieldId);
        if (!field) return;

        // モーダルのフォームにフィールドの値を設定
        $('#field-id').val(field.id);
        $('#field-key').val(field.field_id);
        $('#field-type').val(field.field_type);
        $('#field-label').val(field.label);
        $('#field-placeholder').val(field.placeholder);
        $('#field-help').val(field.help_text);
        $('#field-required').prop('checked', field.is_required);

        // フィールドタイプに応じて追加設定を表示
        this.toggleFieldTypeOptions(field.field_type);

        // 選択肢オプションの設定
        if (field.field_type === 'select' || field.field_type === 'radio' || field.field_type === 'checkbox') {
            const options = field.options ? JSON.parse(field.options) : [];

            // 選択肢コンテナをクリア
            $('#options-container').empty();

            if (options.length > 0) {
                options.forEach((option, index) => {
                    this.addOptionField(option.value, option.label);
                });
            } else {
                // デフォルトで空の選択肢を1つ追加
                this.addOptionField('', '');
            }
        }

        // バリデーションの設定
        if (field.validation) {
            const validation = JSON.parse(field.validation);

            $('#validation-required').prop('checked', validation.required || false);
            $('#validation-min-length').val(validation.minLength || '');
            $('#validation-max-length').val(validation.maxLength || '');
            $('#validation-pattern').val(validation.pattern || '');
            $('#validation-min').val(validation.min || '');
            $('#validation-max').val(validation.max || '');
        }

        // モーダルを表示
        $('#field-edit-modal').modal('show');
    },

    // フィールドタイプに応じた追加設定の表示切替
    toggleFieldTypeOptions: function (fieldType) {
        // 全ての追加設定を非表示
        $('.field-type-options').hide();

        // フィールドタイプに応じた設定を表示
        switch (fieldType) {
            case 'text':
            case 'textarea':
                $('#text-options').show();
                $('#validation-text-options').show();
                break;

            case 'select':
            case 'radio':
            case 'checkbox':
                $('#options-section').show();
                break;

            case 'number':
                $('#number-options').show();
                break;

            case 'date':
                $('#date-options').show();
                break;

            case 'file':
                $('#file-options').show();
                break;
        }
    },

    // 選択肢フィールドを追加
    addOptionField: function (value, label) {
        const index = $('#options-container .option-row').length;

        const optionHtml = `
            <div class="row option-row mb-2">
                <div class="col">
                    <input type="text" class="form-control" name="option-value-${index}" value="${value}" placeholder="値">
                </div>
                <div class="col">
                    <input type="text" class="form-control" name="option-label-${index}" value="${label}" placeholder="表示名">
                </div>
                <div class="col-auto">
                    <button type="button" class="btn btn-danger btn-sm btn-remove-option">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        `;

        $('#options-container').append(optionHtml);

        // 削除ボタンのイベント設定
        $('.btn-remove-option').off('click').on('click', function () {
            $(this).closest('.option-row').remove();
        });
    },

    // フィールドを削除
    deleteField: function (fieldId) {
        if (confirm('このフィールドを削除してもよろしいですか？')) {
            // フィールド定義からフィールドを削除
            const index = this.fieldDefinitions.findIndex(f => f.field_id === fieldId);
            if (index !== -1) {
                const field = this.fieldDefinitions[index];

                // APIに保存済みの場合は削除APIを呼び出し
                if (field.id) {
                    App.apiDelete(`/workflow/templates/${this.templateId}/form-fields/${field.id}`)
                        .then(response => {
                            if (response.success) {
                                // 成功したらフィールドを削除
                                this.fieldDefinitions.splice(index, 1);

                                // 画面からフィールドを削除
                                $(`.form-field[data-field-id="${fieldId}"]`).remove();

                                // フィールドの並び順を更新
                                this.updateFieldOrder();

                                App.showNotification('フィールドを削除しました', 'success');
                            } else {
                                App.showNotification(response.error || 'フィールドの削除に失敗しました', 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Failed to delete field:', error);
                            App.showNotification('フィールドの削除に失敗しました', 'error');
                        });
                } else {
                    // APIに保存されていない場合は、ローカルのみ削除
                    this.fieldDefinitions.splice(index, 1);

                    // 画面からフィールドを削除
                    $(`.form-field[data-field-id="${fieldId}"]`).remove();

                    // フィールドの並び順を更新
                    this.updateFieldOrder();

                    App.showNotification('フィールドを削除しました', 'success');
                }
            }
        }
    },

    // フィールドの並び順を更新
    updateFieldOrder: function () {
        // 画面上のフィールドの並び順に合わせてフィールド定義を更新
        const fieldIds = $('.form-field').map(function () {
            return $(this).data('field-id');
        }).get();

        fieldIds.forEach((fieldId, index) => {
            const field = this.fieldDefinitions.find(f => f.field_id === fieldId);
            if (field) {
                field.sort_order = index;
            }
        });
    },

    // フォーム定義を保存
    saveFormDefinitions: function () {
        // フォーム定義を更新順にソート
        this.fieldDefinitions.sort((a, b) => a.sort_order - b.sort_order);

        // API呼び出しでフォーム定義を保存
        App.apiPost(`/workflow/templates/${this.templateId}/form`, {
            form_definitions: this.fieldDefinitions
        })
            .then(response => {
                if (response.success) {
                    // 保存成功時は新しいフィールド定義を設定
                    this.fieldDefinitions = response.data.form_definitions || [];

                    // 画面を更新
                    this.renderExistingFields();

                    App.showNotification(response.message || 'フォーム定義を保存しました', 'success');
                } else {
                    App.showNotification(response.error || 'フォーム定義の保存に失敗しました', 'error');
                }
            })
            .catch(error => {
                console.error('Failed to save form definitions:', error);
                App.showNotification('フォーム定義の保存に失敗しました', 'error');
            });
    }
};

// DOMが読み込まれたら初期化
$(document).ready(function () {
    FormDesigner.init();

    // フィールドタイプのセレクトボックスが変更されたら追加設定の表示を切り替え
    $('#field-type').on('change', function () {
        const fieldType = $(this).val();
        FormDesigner.toggleFieldTypeOptions(fieldType);
    });

    // オプション追加ボタン
    $('#btn-add-option').on('click', function () {
        FormDesigner.addOptionField('', '');
    });

    // フィールド編集フォームの送信
    $('#field-edit-form').on('submit', function (e) {
        e.preventDefault();

        // フォームからフィールドデータを取得
        const fieldId = $('#field-id').val();
        const fieldKey = $('#field-key').val();
        const fieldType = $('#field-type').val();
        const label = $('#field-label').val();
        const placeholder = $('#field-placeholder').val();
        const helpText = $('#field-help').val();
        const isRequired = $('#field-required').is(':checked');

        // バリデーションを構築
        const validation = {};
        if ($('#validation-required').is(':checked')) validation.required = true;
        if ($('#validation-min-length').val()) validation.minLength = parseInt($('#validation-min-length').val());
        if ($('#validation-max-length').val()) validation.maxLength = parseInt($('#validation-max-length').val());
        if ($('#validation-pattern').val()) validation.pattern = $('#validation-pattern').val();
        if ($('#validation-min').val()) validation.min = parseFloat($('#validation-min').val());
        if ($('#validation-max').val()) validation.max = parseFloat($('#validation-max').val());

        // 選択肢オプションを構築
        let options = [];
        if (fieldType === 'select' || fieldType === 'radio' || fieldType === 'checkbox') {
            $('.option-row').each(function (index) {
                const value = $(this).find(`[name^="option-value-"]`).val();
                const label = $(this).find(`[name^="option-label-"]`).val();

                if (value || label) {
                    options.push({
                        value: value,
                        label: label || value
                    });
                }
            });
        }

        // フィールド定義を更新
        const index = FormDesigner.fieldDefinitions.findIndex(f => f.field_id === fieldKey);
        if (index !== -1) {
            const field = FormDesigner.fieldDefinitions[index];

            field.field_type = fieldType;
            field.label = label;
            field.placeholder = placeholder;
            field.help_text = helpText;
            field.is_required = isRequired;
            field.validation = JSON.stringify(validation);

            if (fieldType === 'select' || fieldType === 'radio' || fieldType === 'checkbox') {
                field.options = JSON.stringify(options);
            }

            // APIに保存
            const apiUrl = field.id
                ? `/workflow/templates/${FormDesigner.templateId}/form-fields/${field.id}`
                : `/workflow/templates/${FormDesigner.templateId}/form-fields`;

            App.apiPost(apiUrl, field)
                .then(response => {
                    if (response.success) {
                        // 成功したらフィールドIDを更新
                        if (!field.id) {
                            field.id = response.data.id;
                        }

                        // 画面を更新
                        FormDesigner.renderExistingFields();

                        // モーダルを閉じる
                        $('#field-edit-modal').modal('hide');

                        App.showNotification(response.message || 'フィールドを保存しました', 'success');
                    } else {
                        App.showNotification(response.error || 'フィールドの保存に失敗しました', 'error');
                    }
                })
                .catch(error => {
                    console.error('Failed to save field:', error);
                    App.showNotification('フィールドの保存に失敗しました', 'error');
                });
        }
    });
});
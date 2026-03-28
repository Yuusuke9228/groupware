/**
 * WEB Database Field - JavaScriptファイル
 * フィールド設定ページで使用される機能を提供します
 */

// WEBデータベースフィールド名前空間
const WebDatabaseField = {
    // 初期化
    init: function() {
        this.setupEventListeners();
        this.initLayoutBuilder();
    },

    // イベントリスナーを設定
    setupEventListeners: function() {
        // フィールドタイプの変更時の選択肢入力欄の表示・非表示
        $('#field-type, #edit-field-type').on('change', function() {
            const fieldType = $(this).val();
            const isEdit = $(this).attr('id') === 'edit-field-type';
            const optionsContainer = isEdit ? '#edit-options-container' : '#options-container';

            // すべての条件付きコンテナを非表示
            $(optionsContainer).addClass('d-none');
            $('#relation-container, #lookup-container, #calc-container').addClass('d-none');
            if (isEdit) {
                $('#edit-relation-container, #edit-lookup-container, #edit-calc-container').addClass('d-none');
            }

            if (['select', 'radio', 'checkbox'].includes(fieldType)) {
                $(optionsContainer).removeClass('d-none');
            } else if (fieldType === 'relation') {
                const container = isEdit ? '#edit-relation-container' : '#relation-container';
                $(container).removeClass('d-none');
                WebDatabaseField.loadDatabaseList(isEdit ? '#edit-field-relation-database' : '#field-relation-database');
            } else if (fieldType === 'lookup') {
                const container = isEdit ? '#edit-lookup-container' : '#lookup-container';
                $(container).removeClass('d-none');
            } else if (fieldType === 'calc') {
                const container = isEdit ? '#edit-calc-container' : '#calc-container';
                $(container).removeClass('d-none');
            }
        });

        $('#field-lookup-relation').on('change', function() {
            const relationFieldId = $(this).val();
            if (relationFieldId) {
                WebDatabaseField.loadRelationFields(relationFieldId, '#field-lookup-target');
            }
        });

        $('#edit-field-lookup-relation').on('change', function() {
            const relationFieldId = $(this).val();
            if (relationFieldId) {
                WebDatabaseField.loadRelationFields(relationFieldId, '#edit-field-lookup-target');
            }
        });

        // フィールド作成モーダルの保存ボタン
        $('#save-field-btn').off('click').on('click', function() {
            WebDatabaseField.saveField();
        });

        // フィールド編集ボタン
        $(document).on('click', '.edit-field-btn', function() {
            const fieldId = $(this).data('field-id');
            WebDatabaseField.loadFieldDetails(fieldId);
        });

        // フィールド削除ボタン
        $(document).on('click', '.delete-field-btn', function() {
            const fieldId = $(this).data('field-id');
            if (confirm('このフィールドを削除しますか？関連するデータも削除されます。')) {
                WebDatabaseField.deleteField(fieldId);
            }
        });

        // フィールド更新ボタン
        $('#update-field-btn').off('click').on('click', function() {
            WebDatabaseField.updateField();
        });

        $('#save-field-layout-btn').on('click', function() {
            WebDatabaseField.saveFieldLayout();
        });

        // タイトルフィールドの制御（排他的に選択できるようにする）
        $(document).on('change', '#field-title, #edit-field-title', function() {
            if ($(this).prop('checked')) {
                const fieldId = $(this).closest('form').find('input[name="id"]').val();
                // 編集モードの場合のみAPIを呼び出す
                if (fieldId) {
                    WebDatabaseField.updateTitleField(fieldId);
                }
            }
        });
    },

    // フィールドを保存
    saveField: function() {
        const form = $('#add-field-form');
        const url = form.attr('action');
        const formData = new FormData(form[0]);

        // オプション値の処理
        const fieldType = $('#field-type').val();
        if (['select', 'radio', 'checkbox'].includes(fieldType)) {
            const optionsText = $('#field-options').val();
            const optionsArray = this.parseOptionsText(optionsText);
            formData.set('options', JSON.stringify(optionsArray));
        }

        // APIリクエスト
        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                $('#save-field-btn').prop('disabled', true);
                form.find('.is-invalid').removeClass('is-invalid');
                form.find('.invalid-feedback').text('');
            },
            success: function(response) {
                if (response.success) {
                    App.showNotification(response.message, 'success');
                    $('#add-field-modal').modal('hide');
                    location.reload();
                } else {
                    App.showNotification(response.error, 'error');

                    // バリデーションエラーの表示
                    if (response.validation) {
                        for (const field in response.validation) {
                            const input = form.find('[name="' + field + '"]');
                            input.addClass('is-invalid');
                            input.next('.invalid-feedback').text(response.validation[field]);
                        }
                    }
                }
            },
            error: function() {
                App.showNotification('エラーが発生しました', 'error');
            },
            complete: function() {
                $('#save-field-btn').prop('disabled', false);
            }
        });
        return false;
    },

    // フィールド詳細を取得
    loadFieldDetails: function(fieldId) {
        // APIリクエスト
        $.ajax({
            url: BASE_PATH + '/api/webdatabase/fields/' + fieldId,
            type: 'GET',
            beforeSend: function() {
                // ローディング表示
            },
            success: function(response) {
                if (response.success) {
                    WebDatabaseField.populateFieldEditForm(response.data);
                    $('#edit-field-modal').modal('show');
                } else {
                    App.showNotification(response.error, 'error');
                }
            },
            error: function() {
                App.showNotification('フィールド情報の取得に失敗しました', 'error');
            }
        });
    },

    // フィールド編集フォームに値を設定
    populateFieldEditForm: function(field) {
        const form = $('#edit-field-form');

        // フォームのアクションを設定
        form.attr('action', BASE_PATH + '/api/webdatabase/fields/' + field.id);
        
        // 隠しフィールドにIDを設定
        form.find('input[name="id"]').val(field.id);

        // フォームに値を設定
        $('#edit-field-name').val(field.name);
        $('#edit-field-type').val(field.type).trigger('change');
        $('#edit-field-description').val(field.description);
        $('#edit-field-default').val(field.default_value);
        $('#edit-field-required').prop('checked', field.required == 1);
        $('#edit-field-unique').prop('checked', field.unique_value == 1);
        $('#edit-field-title').prop('checked', field.is_title_field == 1);
        $('#edit-field-filterable').prop('checked', field.is_filterable == 1);
        $('#edit-field-sortable').prop('checked', field.is_sortable == 1);
        $('#edit-field-relation-type').val(field.relation_type || 'one_to_many');
        $('#edit-field-calc-formula').val(field.calc_formula || '');

        if (field.relation_database_id) {
            WebDatabaseField.loadDatabaseList('#edit-field-relation-database');
            setTimeout(function() {
                $('#edit-field-relation-database').val(field.relation_database_id);
            }, 100);
        }
        if (field.lookup_relation_field_id) {
            $('#edit-field-lookup-relation').val(field.lookup_relation_field_id);
            WebDatabaseField.loadRelationFields(field.lookup_relation_field_id, '#edit-field-lookup-target');
            setTimeout(function() {
                $('#edit-field-lookup-target').val(field.lookup_target_field_id || '');
            }, 100);
        }

        // 選択肢の設定
        if (['select', 'radio', 'checkbox'].includes(field.type) && field.options) {
            const options = JSON.parse(field.options);
            let optionsText = '';

            options.forEach(function(option) {
                optionsText += option.label + '\n';
            });

            $('#edit-field-options').val(optionsText.trim());
            $('#edit-options-container').removeClass('d-none');
        } else {
            $('#edit-options-container').addClass('d-none');
        }
    },

    // フィールドを更新
    updateField: function() {
        const form = $('#edit-field-form');
        const url = form.attr('action');
        const formData = new FormData(form[0]);

        // オプション値の処理
        const fieldType = $('#edit-field-type').val();
        if (['select', 'radio', 'checkbox'].includes(fieldType)) {
            const optionsText = $('#edit-field-options').val();
            const optionsArray = this.parseOptionsText(optionsText);
            formData.set('options', JSON.stringify(optionsArray));
        }

        // APIリクエスト
        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                $('#update-field-btn').prop('disabled', true);
                form.find('.is-invalid').removeClass('is-invalid');
                form.find('.invalid-feedback').text('');
            },
            success: function(response) {
                if (response.success) {
                    App.showNotification(response.message, 'success');
                    $('#edit-field-modal').modal('hide');
                    location.reload();
                } else {
                    App.showNotification(response.error, 'error');

                    // バリデーションエラーの表示
                    if (response.validation) {
                        for (const field in response.validation) {
                            const input = form.find('[name="' + field + '"]');
                            input.addClass('is-invalid');
                            input.next('.invalid-feedback').text(response.validation[field]);
                        }
                    }
                }
            },
            error: function() {
                App.showNotification('エラーが発生しました', 'error');
            },
            complete: function() {
                $('#update-field-btn').prop('disabled', false);
            }
        });
    },

    // フィールドを削除
    deleteField: function(fieldId) {
        // APIリクエスト
        $.ajax({
            url: BASE_PATH + '/api/webdatabase/fields/' + fieldId,
            type: 'DELETE',
            success: function(response) {
                if (response.success) {
                    App.showNotification(response.message, 'success');
                    // フィールド行を削除
                    $('#field-' + fieldId).fadeOut(300, function() {
                        $(this).remove();

                        // フィールドがなくなった場合のメッセージを表示
                        if ($('#field-list tr').length === 0) {
                            $('#field-list').html('<tr><td colspan="7" class="text-center">フィールドがありません。フィールドを追加してください。</td></tr>');
                        }
                    });
                } else {
                    App.showNotification(response.error, 'error');
                }
            },
            error: function() {
                App.showNotification('フィールドの削除に失敗しました', 'error');
            }
        });
    },

    // タイトルフィールドを更新（他のフィールドのタイトルフラグをリセット）
    updateTitleField: function(fieldId) {
        // APIリクエスト
        $.ajax({
            url: BASE_PATH + '/api/webdatabase/fields/set-title',
            type: 'POST',
            data: { field_id: fieldId },
            success: function(response) {
                if (!response.success) {
                    App.showNotification(response.error, 'error');
                }
            },
            error: function() {
                App.showNotification('タイトルフィールドの設定に失敗しました', 'error');
            }
        });
    },

    // 選択肢テキストを解析してオプション配列に変換
    parseOptionsText: function(text) {
        if (!text) return [];

        const lines = text.split('\n').filter(line => line.trim() !== '');
        return lines.map(function(line, index) {
            return {
                value: 'option_' + (index + 1),
                label: line.trim()
            };
        });
    },

    // リレーション用: データベース一覧を読み込む
    loadDatabaseList: function(selectId) {
        $.ajax({
            url: BASE_PATH + '/api/webdatabase/all-databases',
            type: 'GET',
            success: function(response) {
                if (response.success && response.data) {
                    const $select = $(selectId);
                    $select.find('option:gt(0)').remove();
                    response.data.forEach(function(db) {
                        $select.append('<option value="' + db.id + '">' + $('<span>').text(db.name).html() + '</option>');
                    });
                }
            }
        });
    },

    // ルックアップ用: リレーション先のフィールド一覧を読み込む
    loadRelationFields: function(relationFieldId, targetSelectId) {
        // まずリレーションフィールドの設定を取得
        $.ajax({
            url: BASE_PATH + '/api/webdatabase/fields/' + relationFieldId,
            type: 'GET',
            success: function(response) {
                if (response.success && response.data) {
                    const relDbId = response.data.relation_database_id;
                    if (relDbId) {
                        // リレーション先DBのフィールド一覧を取得
                        $.ajax({
                            url: BASE_PATH + '/api/webdatabase/' + relDbId + '/fields',
                            type: 'GET',
                            success: function(resp) {
                                if (resp.success && resp.data) {
                                    const $select = $(targetSelectId);
                                    $select.find('option:gt(0)').remove();
                                    resp.data.forEach(function(f) {
                                        $select.append('<option value="' + f.id + '">' + $('<span>').text(f.name).html() + ' (' + f.type + ')</option>');
                                    });
                                }
                            }
                        });
                    }
                }
            }
        });
    },

    initLayoutBuilder: function() {
        const tbody = document.getElementById('field-list');
        if (!tbody) {
            return;
        }

        if (window.Sortable) {
            Sortable.create(tbody, {
                handle: '.drag-handle',
                animation: 120,
                onEnd: function() {
                    WebDatabaseField.renumberFieldRows();
                }
            });
        }
        this.renumberFieldRows();
    },

    renumberFieldRows: function() {
        $('#field-list tr[data-field-id]').each(function(index) {
            $(this).find('.field-order').text(index + 1);
        });
    },

    collectLayoutItems: function() {
        const items = [];
        $('#field-list tr[data-field-id]').each(function(index) {
            const row = $(this);
            const fieldId = parseInt(row.data('field-id'), 10);
            const fieldType = (row.data('field-type') || '').toString();
            if (!fieldId) {
                return;
            }
            items.push({
                field_id: fieldId,
                sort_order: index + 1,
                section: (row.find('.layout-section-input').val() || '基本情報').toString().trim() || '基本情報',
                hidden: row.find('.layout-hidden-input').is(':checked') ? 1 : 0,
                required: row.find('.layout-required-input').is(':checked') ? 1 : 0,
                child_table: fieldType === 'relation' && row.find('.layout-child-table-input').is(':checked') ? 1 : 0,
                child_summary_field_id: fieldType === 'relation' ? (parseInt(row.find('.layout-child-summary-input').val() || '0', 10) || null) : null,
                relation_filter_field_id: fieldType === 'relation' ? (parseInt(row.find('.layout-relation-filter-input').val() || '0', 10) || null) : null
            });
        });
        return items;
    },

    saveFieldLayout: function() {
        const pathParts = window.location.pathname.split('/');
        const databaseId = pathParts[pathParts.length - 1];
        const items = this.collectLayoutItems();
        if (!items.length) {
            App.showNotification('保存対象のフィールドがありません', 'error');
            return;
        }

        const btn = $('#save-field-layout-btn');
        btn.prop('disabled', true);
        $.ajax({
            url: `${BASE_PATH}/api/webdatabase/${databaseId}/fields/layout`,
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ items }),
            success: function(response) {
                if (response.success) {
                    App.showNotification(response.message || 'フォームレイアウトを保存しました', 'success');
                    setTimeout(function() {
                        window.location.reload();
                    }, 300);
                } else {
                    App.showNotification(response.error || 'フォームレイアウト保存に失敗しました', 'error');
                }
            },
            error: function() {
                App.showNotification('フォームレイアウト保存中に通信エラーが発生しました', 'error');
            },
            complete: function() {
                btn.prop('disabled', false);
            }
        });
    }
};

// DOMが読み込まれたらフィールド機能を初期化
$(document).ready(function() {
    WebDatabaseField.init();
});

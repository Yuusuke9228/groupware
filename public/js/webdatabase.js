/**
 * WEB Database - メインJavaScript
 */

// WEBデータベース名前空間
const WebDatabase = {
    // 現在のページ情報
    currentPage: 1,
    totalPages: 1,

    // 検索・フィルター・ソート条件
    searchTerm: '',
    filters: {},
    sortField: null,
    sortOrder: 'asc',

    // 初期化
    init: function () {
        // 現在のページを判断して初期化処理
        const path = window.location.pathname;

        if (path.includes('/webdatabase')) {
            // 共通の初期化処理
            this.setupEventListeners();

            // ページ固有の初期化処理
            if (path.endsWith('/webdatabase') || path.endsWith('/webdatabase/')) {
                this.initDatabaseList();
            } else if (path.includes('/webdatabase/create')) {
                this.initDatabaseForm();
            } else if (path.includes('/webdatabase/edit/')) {
                this.initDatabaseForm();
            } else if (path.includes('/webdatabase/fields/')) {
                this.initFieldsPage();
            } else if (path.includes('/webdatabase/records/')) {
                this.initRecordsPage();
            } else if (path.includes('/webdatabase/create-record/')) {
                this.initRecordForm();
            } else if (path.includes('/webdatabase/edit/') && path.includes('/')) {
                this.initRecordForm();
            } else if (path.includes('/webdatabase/view/')) {
                this.initRecordView();
            }
        }
    },

    // イベントリスナーを設定
    setupEventListeners: function () {
        // データベース検索
        $('#search-databases').on('input', function () {
            WebDatabase.searchTerm = $(this).val();
            WebDatabase.loadDatabases();
        });

        // レコード検索
        $('#search-records').on('input', function () {
            WebDatabase.searchTerm = $(this).val();
            WebDatabase.loadRecords();
        });

        // フィルター適用
        $('#apply-filter-btn').on('click', function () {
            WebDatabase.filters = $('#filter-form').serializeObject();
            WebDatabase.currentPage = 1;
            WebDatabase.loadRecords();
        });

        // フィルターリセット
        $('#reset-filter-btn').on('click', function () {
            $('#filter-form')[0].reset();
            WebDatabase.filters = {};
            WebDatabase.loadRecords();
        });

        // アイコン選択時のプレビュー更新
        $('#icon').on('change', function () {
            const iconClass = $(this).val();
            $('#icon-preview').attr('class', 'fas fa-' + iconClass);
        });

        // カラー選択時のテキスト更新
        $('#color').on('input', function () {
            $('#color-text').val($(this).val());
        });

        // フィールドタイプの変更時の選択肢入力欄の表示・非表示
        $(document).on('change', '#field-type, #edit-field-type', function () {
            const fieldType = $(this).val();
            const optionsContainer = $(this).attr('id') === 'field-type' ? '#options-container' : '#edit-options-container';

            if (['select', 'radio', 'checkbox'].includes(fieldType)) {
                $(optionsContainer).removeClass('d-none');
            } else {
                $(optionsContainer).addClass('d-none');
            }
        });

        // フィールド作成モーダルの保存ボタン
        $('#save-field-btn').on('click', function () {
            const form = $('#add-field-form');
            const url = form.attr('action');
            const data = form.serialize();

            // オプション値の処理
            const fieldType = $('#field-type').val();
            if (['select', 'radio', 'checkbox'].includes(fieldType)) {
                const optionsText = $('#field-options').val();
                const optionsArray = WebDatabase.parseOptionsText(optionsText);
                // hidden fieldでオプションを送信
                form.append('<input type="hidden" name="options" value=\'' + JSON.stringify(optionsArray) + '\'>');
            }

            // APIリクエスト
            $.ajax({
                url: url,
                type: 'POST',
                data: data,
                beforeSend: function () {
                    $('#save-field-btn').prop('disabled', true);
                    form.find('.is-invalid').removeClass('is-invalid');
                    form.find('.invalid-feedback').text('');
                },
                success: function (response) {
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
                error: function () {
                    App.showNotification('エラーが発生しました', 'error');
                },
                complete: function () {
                    $('#save-field-btn').prop('disabled', false);
                    // hidden fieldを削除
                    form.find('input[name="options"]').remove();
                }
            });
        });

        // フィールド編集ボタン
        $(document).on('click', '.edit-field-btn', function () {
            const fieldId = $(this).data('field-id');
            WebDatabase.loadFieldDetails(fieldId);
        });

        // フィールド削除ボタン
        $(document).on('click', '.delete-field-btn', function () {
            const fieldId = $(this).data('field-id');
            if (confirm('このフィールドを削除しますか？関連するデータも削除されます。')) {
                WebDatabase.deleteField(fieldId);
            }
        });

        // フィールド更新ボタン
        $('#update-field-btn').on('click', function () {
            const form = $('#edit-field-form');
            const url = form.attr('action');
            const data = form.serialize();

            // オプション値の処理
            const fieldType = $('#edit-field-type').val();
            if (['select', 'radio', 'checkbox'].includes(fieldType)) {
                const optionsText = $('#edit-field-options').val();
                const optionsArray = WebDatabase.parseOptionsText(optionsText);
                // hidden fieldでオプションを送信
                form.append('<input type="hidden" name="options" value=\'' + JSON.stringify(optionsArray) + '\'>');
            }

            // APIリクエスト
            $.ajax({
                url: url,
                type: 'POST',
                data: data,
                beforeSend: function () {
                    $('#update-field-btn').prop('disabled', true);
                    form.find('.is-invalid').removeClass('is-invalid');
                    form.find('.invalid-feedback').text('');
                },
                success: function (response) {
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
                error: function () {
                    App.showNotification('エラーが発生しました', 'error');
                },
                complete: function () {
                    $('#update-field-btn').prop('disabled', false);
                    // hidden fieldを削除
                    form.find('input[name="options"]').remove();
                }
            });
        });
    },

    // データベース一覧ページの初期化
    initDatabaseList: function () {
        this.loadDatabases();
    },

    // データベース一覧を取得
    loadDatabases: function () {
        const params = {
            search: this.searchTerm,
            page: this.currentPage,
            limit: 12
        };

        // APIリクエスト
        $.ajax({
            url: BASE_PATH + '/api/webdatabase',
            type: 'GET',
            data: params,
            beforeSend: function () {
                $('#database-list').html('<div class="col-12 text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');
            },
            success: function (response) {
                if (response.success) {
                    WebDatabase.renderDatabaseList(response.data.databases);
                    WebDatabase.totalPages = response.data.pagination.total_pages;
                    WebDatabase.renderPagination();
                } else {
                    App.showNotification(response.error, 'error');
                }
            },
            error: function () {
                App.showNotification('データの取得に失敗しました', 'error');
            }
        });
    },

    // データベース一覧を表示
    renderDatabaseList: function (databases) {
        const container = $('#database-list');
        container.empty();

        if (databases.length === 0) {
            container.html('<div class="col-12 text-center py-5"><p class="text-muted">データベースがありません。新しいデータベースを作成してください。</p></div>');
            return;
        }

        // テンプレートを使用してデータベースカードを表示
        const template = $('#database-card-template').html();

        databases.forEach(function (database) {
            let card = template
                .replace(/{{id}}/g, database.id)
                .replace(/{{name}}/g, database.name)
                .replace(/{{description}}/g, database.description || '')
                .replace(/{{icon}}/g, database.icon)
                .replace(/{{color}}/g, database.color)
                .replace(/{{creator_name}}/g, database.creator_name);

            container.append(card);
        });
    },

    // ページネーションを表示
    renderPagination: function () {
        // 必要に応じて実装
    },

    // データベースフォームの初期化
    initDatabaseForm: function () {
        // 既に実装済みの機能
        // - アイコン選択時のプレビュー更新
        // - カラー選択時のテキスト更新
    },

    // フィールド設定ページの初期化
    initFieldsPage: function () {
        // 既に実装済みの機能
        // - フィールド追加モーダルでのフィールドタイプに応じた表示切替
        // - フィールド追加/編集/削除機能
    },

    // フィールド詳細を取得
    loadFieldDetails: function (fieldId) {
        // APIリクエスト
        $.ajax({
            url: BASE_PATH + '/api/webdatabase/fields/' + fieldId,
            type: 'GET',
            success: function (response) {
                if (response.success) {
                    WebDatabase.populateFieldEditForm(response.data);
                } else {
                    App.showNotification(response.error, 'error');
                }
            },
            error: function () {
                App.showNotification('フィールド情報の取得に失敗しました', 'error');
            }
        });
    },

    // フィールド編集フォームに値を設定
    populateFieldEditForm: function (field) {
        const form = $('#edit-field-form');

        // フォームのアクションを設定
        form.attr('action', BASE_PATH + '/api/webdatabase/fields/' + field.id);

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

        // 選択肢の設定
        if (['select', 'radio', 'checkbox'].includes(field.type) && field.options) {
            const options = JSON.parse(field.options);
            let optionsText = '';

            options.forEach(function (option) {
                optionsText += option.label + '\n';
            });

            $('#edit-field-options').val(optionsText.trim());
            $('#edit-options-container').removeClass('d-none');
        } else {
            $('#edit-options-container').addClass('d-none');
        }

        // モーダルを表示
        $('#edit-field-modal').modal('show');
    },

    // フィールドを削除
    deleteField: function (fieldId) {
        // APIリクエスト
        $.ajax({
            url: BASE_PATH + '/api/webdatabase/fields/' + fieldId,
            type: 'DELETE',
            success: function (response) {
                if (response.success) {
                    App.showNotification(response.message, 'success');
                    // フィールド行を削除
                    $('#field-' + fieldId).fadeOut(300, function () {
                        $(this).remove();

                        // フィールドがなくなった場合のメッセージを表示
                        if ($('#field-list tr').length === 0) {
                            $('#field-list').html('<tr><td colspan="6" class="text-center">フィールドがありません。フィールドを追加してください。</td></tr>');
                        }
                    });
                } else {
                    App.showNotification(response.error, 'error');
                }
            },
            error: function () {
                App.showNotification('フィールドの削除に失敗しました', 'error');
            }
        });
    },

    // 選択肢テキストを解析してオプション配列に変換
    parseOptionsText: function (text) {
        if (!text) return [];

        const lines = text.split('\n').filter(line => line.trim() !== '');
        return lines.map(function (line, index) {
            return {
                value: 'option_' + (index + 1),
                label: line.trim()
            };
        });
    },

    // レコード一覧ページの初期化
    initRecordsPage: function () {
        // ユーザーフィールドとオーガニゼーションフィールドの初期化
        this.initUserFieldOptions();
        this.initOrganizationFieldOptions();

        // レコード一覧を読み込み
        this.loadRecords();
    },

    // レコード一覧を取得
    loadRecords: function () {
        // URLからデータベースIDを取得
        const pathSegments = window.location.pathname.split('/');
        const databaseId = pathSegments[pathSegments.length - 1];

        const params = {
            search: this.searchTerm,
            filters: this.filters,
            sort: this.sortField,
            order: this.sortOrder,
            page: this.currentPage,
            limit: 20
        };

        // APIリクエスト
        $.ajax({
            url: BASE_PATH + '/api/webdatabase/' + databaseId + '/records',
            type: 'GET',
            data: params,
            beforeSend: function () {
                $('#record-list').html('<tr><td colspan="10" class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></td></tr>');
            },
            success: function (response) {
                if (response.success) {
                    WebDatabase.renderRecordList(response.data.records, databaseId);
                    WebDatabase.totalPages = response.data.pagination.total_pages;
                    WebDatabase.renderRecordPagination(response.data.pagination);
                } else {
                    App.showNotification(response.error, 'error');
                }
            },
            error: function () {
                App.showNotification('データの取得に失敗しました', 'error');
            }
        });
    },

    // レコード一覧を表示
    renderRecordList: function (records, databaseId) {
        const container = $('#record-list');
        container.empty();

        if (records.length === 0) {
            const colSpan = $('#record-list').closest('table').find('th').length;
            container.html('<tr><td colspan="' + colSpan + '" class="text-center">レコードがありません。新しいレコードを作成してください。</td></tr>');
            return;
        }

        // テンプレートを使用してレコード行を表示
        const template = $('#record-row-template').html();
        const displayFields = [];

        // 表示するフィールドを取得
        $('#record-list').closest('table').find('th').each(function (index) {
            if (index > 0 && index < $('#record-list').closest('table').find('th').length - 3) {
                displayFields.push($(this).text());
            }
        });

        records.forEach(function (record) {
            let row = template
                .replace(/{{id}}/g, record.id)
                .replace(/{{database_id}}/g, databaseId)
                .replace(/{{title}}/g, record.title)
                .replace(/{{creator_name}}/g, record.creator_name)
                .replace(/{{created_at}}/g, WebDatabase.formatDateTime(record.created_at));

            // フィールド値の置換
            let fieldValuesHtml = '';
            // ここでフィールド値を表示する処理を実装

            row = row.replace(/{{field_values}}/g, fieldValuesHtml);

            container.append(row);
        });
    },

    // レコードのページネーション表示
    renderRecordPagination: function (pagination) {
        $('#total-records').text(pagination.total);
        $('#showing-records').text((pagination.current_page - 1) * pagination.limit + 1 + '-' + Math.min(pagination.current_page * pagination.limit, pagination.total));

        const paginationContainer = $('#pagination');
        paginationContainer.empty();

        // 前へボタン
        paginationContainer.append(`
            <li class="page-item ${pagination.current_page === 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${pagination.current_page - 1}" aria-label="Previous">
                    <span aria-hidden="true">&laquo;</span>
                </a>
            </li>
        `);

        // ページ番号
        const maxVisiblePages = 5;
        const startPage = Math.max(1, pagination.current_page - Math.floor(maxVisiblePages / 2));
        const endPage = Math.min(pagination.total_pages, startPage + maxVisiblePages - 1);

        for (let i = startPage; i <= endPage; i++) {
            paginationContainer.append(`
                <li class="page-item ${i === pagination.current_page ? 'active' : ''}">
                    <a class="page-link" href="#" data-page="${i}">${i}</a>
                </li>
            `);
        }

        // 次へボタン
        paginationContainer.append(`
            <li class="page-item ${pagination.current_page === pagination.total_pages ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${pagination.current_page + 1}" aria-label="Next">
                    <span aria-hidden="true">&raquo;</span>
                </a>
            </li>
        `);

        // ページネーションクリックイベント
        $('.page-link').on('click', function (e) {
            e.preventDefault();
            WebDatabase.currentPage = parseInt($(this).data('page'));
            WebDatabase.loadRecords();
        });
    },

    // レコードフォームの初期化
    initRecordForm: function () {
        // ユーザーフィールドとオーガニゼーションフィールドの初期化
        this.initUserFieldOptions();
        this.initOrganizationFieldOptions();
    },

    // レコード詳細ページの初期化
    initRecordView: function () {
        // ユーザーフィールドとオーガニゼーションフィールドの表示
        this.loadUserFieldValues();
        this.loadOrganizationFieldValues();
    },

    // ユーザーフィールドの選択肢を読み込み
    initUserFieldOptions: function () {
        // APIでユーザー一覧を取得
        $.ajax({
            url: BASE_PATH + '/api/active-users',
            type: 'GET',
            success: function (response) {
                if (response.success) {
                    const users = response.data;

                    // 各ユーザーフィールドにオプションを追加
                    $('.user-select').each(function () {
                        const select = $(this);
                        const selectedValue = select.data('selected');

                        users.forEach(function (user) {
                            const option = $('<option></option>')
                                .val(user.id)
                                .text(user.display_name);

                            if (selectedValue == user.id) {
                                option.prop('selected', true);
                            }

                            select.append(option);
                        });
                    });
                }
            }
        });
    },

    // 組織フィールドの選択肢を読み込み
    initOrganizationFieldOptions: function () {
        // APIで組織一覧を取得
        $.ajax({
            url: BASE_PATH + '/api/organizations',
            type: 'GET',
            success: function (response) {
                if (response.success) {
                    const organizations = response.data.organizations;

                    // 各組織フィールドにオプションを追加
                    $('.organization-select').each(function () {
                        const select = $(this);
                        const selectedValue = select.data('selected');

                        organizations.forEach(function (org) {
                            const option = $('<option></option>')
                                .val(org.id)
                                .text(org.name);

                            if (selectedValue == org.id) {
                                option.prop('selected', true);
                            }

                            select.append(option);
                        });
                    });
                }
            }
        });
    },

    // ユーザーフィールド値を表示用に変換
    loadUserFieldValues: function () {
        $('.user-display').each(function () {
            const span = $(this);
            const userId = span.data('user-id');

            if (userId) {
                // APIでユーザー情報を取得
                $.ajax({
                    url: BASE_PATH + '/api/users/' + userId,
                    type: 'GET',
                    success: function (response) {
                        if (response.success) {
                            span.text(response.data.user.display_name);
                        } else {
                            span.text('ユーザーが見つかりません');
                        }
                    },
                    error: function () {
                        span.text('ユーザー情報の取得に失敗しました');
                    }
                });
            } else {
                span.text('未選択');
            }
        });
    },

    // 組織フィールド値を表示用に変換
    loadOrganizationFieldValues: function () {
        $('.organization-display').each(function () {
            const span = $(this);
            const orgId = span.data('org-id');

            if (orgId) {
                // APIで組織情報を取得
                $.ajax({
                    url: BASE_PATH + '/api/organizations/' + orgId,
                    type: 'GET',
                    success: function (response) {
                        if (response.success) {
                            span.text(response.data.name);
                        } else {
                            span.text('組織が見つかりません');
                        }
                    },
                    error: function () {
                        span.text('組織情報の取得に失敗しました');
                    }
                });
            } else {
                span.text('未選択');
            }
        });
    },

    // 日時フォーマット
    formatDateTime: function (datetime) {
        if (!datetime) return '';

        const date = new Date(datetime);
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');

        return `${year}/${month}/${day} ${hours}:${minutes}`;
    }
};

// フォームデータをオブジェクトに変換するユーティリティ
$.fn.serializeObject = function () {
    var o = {};
    var a = this.serializeArray();
    $.each(a, function () {
        if (o[this.name]) {
            if (!o[this.name].push) {
                o[this.name] = [o[this.name]];
            }
            o[this.name].push(this.value || '');
        } else {
            o[this.name] = this.value || '';
        }
    });
    return o;
};

// DOMが読み込まれたらWEBデータベースを初期化
$(document).ready(function () {
    WebDatabase.init();
});
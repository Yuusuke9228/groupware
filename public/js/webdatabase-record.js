/**
 * WEB Database Record - JavaScriptファイル
 */

// WEBデータベースレコード名前空間
const WebDatabaseRecord = {
    // 現在のページ情報
    currentPage: 1,
    totalPages: 1,

    // 検索・フィルター・ソート条件
    searchTerm: '',
    filters: {},
    sortField: null,
    sortOrder: 'asc',
    viewId: null,

    // 初期化
    init: function () {
        console.log('Init');
        // 現在のページを判断して初期化処理
        const path = window.location.pathname;

        if (path.includes('/webdatabase/records/')) {
            this.initRecordList();
            this.initUserFieldOptions();
            this.initOrganizationFieldOptions();
        } else if (path.includes('/webdatabase/create-record/') || path.includes('/webdatabase/edit/')) {
            this.initRecordForm();
        } else if (path.includes('/webdatabase/view/')) {
            this.initRecordView();
        }
    },

    // レコード一覧ページの初期化
    initRecordList: function () {
        // イベントリスナー設定
        this.setupRecordListEventListeners();
        const selectedViewId = ($('#view-selector').val() || '').trim();
        if (selectedViewId) {
            this.viewId = selectedViewId;
            $('#view-selector').trigger('change');
            return;
        }
        // レコード一覧を読み込み
        this.loadRecords();
    },

    // レコード一覧のイベントリスナー設定
    setupRecordListEventListeners: function () {
        // レコード検索
        $('#search-records').on('input', function () {
            WebDatabaseRecord.searchTerm = $(this).val();
            WebDatabaseRecord.currentPage = 1;
            WebDatabaseRecord.loadRecords();
        });

        // フィルター適用
        // $('#apply-filter-btn').off('click').on('click', function () {
        //     console.log('Apply filter button clicked');
        //     const formData = $('#filter-form').serializeArray();
        //     WebDatabaseRecord.filters = {};

        //     formData.forEach(function (item) {
        //         // filters[fieldId] 形式の名前をパース
        //         const match = item.name.match(/filters\[(\d+)\]/);
        //         if (match && match[1] && item.value) {
        //             WebDatabaseRecord.filters[match[1]] = item.value;
        //         }
        //     });

        //     WebDatabaseRecord.currentPage = 1;
        //     WebDatabaseRecord.loadRecords();
        // });
        $('#apply-filter-btn').on('click', function () {
            // フィルターデータを収集
            WebDatabaseRecord.filters = {};

            $('.filter-field').each(function () {
                const input = $(this);
                const value = input.val();

                if (value) {
                    // フィールドIDを取得
                    const fieldId = input.attr('id').replace('filter-', '');
                    WebDatabaseRecord.filters[fieldId] = value;
                }
            });

            WebDatabaseRecord.currentPage = 1;
            WebDatabaseRecord.loadRecords();
        });

        // フィルターリセット
        $('#reset-filter-btn').on('click', function () {
            $('#filter-form')[0].reset();
            WebDatabaseRecord.filters = {};
            WebDatabaseRecord.currentPage = 1;
            WebDatabaseRecord.loadRecords();
        });

        $('#view-selector').on('change', function () {
            const selected = $(this).find('option:selected');
            const id = selected.val() || '';
            WebDatabaseRecord.viewId = id || null;
            if (!id) {
                return;
            }

            const settingsText = selected.attr('data-settings') || '{}';
            let settings = {};
            try {
                settings = JSON.parse(settingsText);
            } catch (e) {
                settings = {};
            }
            WebDatabaseRecord.applyViewSettings(settings);

            const selectedText = selected.text() || '';
            $('#view-name').val(selectedText.replace(/\s*\[.*\]\s*$/, '').trim());
            $('#view-scope').val(selected.attr('data-scope') || 'private');
            $('#view-organization').val((selected.attr('data-organization-id') || '').toString());
        });

        $('#save-view-btn').on('click', function () {
            WebDatabaseRecord.saveCurrentView();
        });

        $('#delete-view-btn').on('click', function () {
            WebDatabaseRecord.deleteSelectedView();
        });

        // 削除ボタンのイベント処理（動的に追加される要素）
        $(document).off('click', '.btn-delete').on('click', '.btn-delete', function () {
            const url = $(this).data('url');
            const confirmMessage = $(this).data('confirm');

            if (confirm(confirmMessage)) {
                $.ajax({
                    url: url,
                    type: 'DELETE',
                    success: function (response) {
                        if (response.success) {
                            App.showNotification(response.message, 'success');
                            WebDatabaseRecord.loadRecords();
                        } else {
                            App.showNotification(response.error, 'error');
                        }
                        return false;
                    },
                    error: function () {
                        App.showNotification('エラーが発生しました', 'error');
                    }
                });
                return false;
            }
            return false;
        });

        // ソートヘッダーのクリックイベント
        $(document).on('click', '.sortable', function () {
            const field = $(this).data('field');

            // 同じフィールドがクリックされた場合はソート順を反転
            if (WebDatabaseRecord.sortField === field) {
                WebDatabaseRecord.sortOrder = WebDatabaseRecord.sortOrder === 'asc' ? 'desc' : 'asc';
            } else {
                WebDatabaseRecord.sortField = field;
                WebDatabaseRecord.sortOrder = 'asc';
            }

            WebDatabaseRecord.loadRecords();
        });
    },

    // レコード一覧を取得
    // loadRecords: function () {
    //     // URLからデータベースIDを取得
    //     const pathParts = window.location.pathname.split('/');
    //     const databaseId = pathParts[pathParts.length - 1];

    //     const params = {
    //         search: this.searchTerm,
    //         filters: this.filters,
    //         sort: this.sortField,
    //         order: this.sortOrder,
    //         page: this.currentPage,
    //         limit: 20
    //     };

    //     // APIリクエスト
    //     $.ajax({
    //         url: BASE_PATH + '/api/webdatabase/' + databaseId + '/records',
    //         type: 'GET',
    //         data: params,
    //         beforeSend: function () {
    //             const columnCount = $('#record-list').closest('table').find('th').length;
    //             $('#record-list').html(`<tr><td colspan="${columnCount}" class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></td></tr>`);
    //         },
    //         success: function (response) {
    //             if (response.success) {
    //                 WebDatabaseRecord.renderRecordList(response.data.records, databaseId);
    //                 WebDatabaseRecord.totalPages = response.data.pagination.total_pages;
    //                 WebDatabaseRecord.renderRecordPagination(response.data.pagination);

    //                 // ソートマークの更新
    //                 WebDatabaseRecord.updateSortIndicators();
    //             } else {
    //                 App.showNotification(response.error, 'error');
    //             }
    //         },
    //         error: function () {
    //             App.showNotification('データの取得に失敗しました', 'error');
    //         }
    //     });
    // },
    loadRecords: function () {
        // URLからデータベースIDを取得
        const pathParts = window.location.pathname.split('/');
        const databaseId = pathParts[pathParts.length - 1];

        // パラメータを準備
        const params = {
            page: this.currentPage,
            limit: 20
        };

        // 検索条件を追加
        if (this.searchTerm) {
            params.search = this.searchTerm;
        }

        // フィルター条件をJSON文字列として追加
        if (Object.keys(this.filters).length > 0) {
            params.filter_json = JSON.stringify(this.filters);
        }

        // ソート条件を追加
        if (this.sortField) {
            params.sort = this.sortField;
            params.order = this.sortOrder;
        }
        if (this.viewId) {
            params.view_id = this.viewId;
        }

        // デバッグログ
        console.log('Filter params:', params);
        console.log('Filter JSON:', params.filter_json || 'none');

        // APIリクエスト
        $.ajax({
            url: BASE_PATH + '/api/webdatabase/' + databaseId + '/records',
            type: 'GET',
            data: params,
            beforeSend: function () {
                const columnCount = $('#record-list').closest('table').find('th').length;
                $('#record-list').html(`<tr><td colspan="${columnCount}" class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></td></tr>`);
            },
            success: function (response) {
                if (response.success) {
                    WebDatabaseRecord.renderRecordList(response.data.records, databaseId);
                    WebDatabaseRecord.totalPages = response.data.pagination.total_pages;
                    WebDatabaseRecord.renderRecordPagination(response.data.pagination);
                } else {
                    App.showNotification(response.error, 'error');
                }
            },
            error: function (xhr, status, error) {
                console.error("API Error:", error);
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
        const rowTemplate = $('#record-row-template').html();

        // 表示するフィールドを取得
        const displayFields = [];
        $('#record-list').closest('table').find('th').each(function (index) {
            if (index > 0 && index < $('#record-list').closest('table').find('th').length - 3) {
                displayFields.push($(this).text().trim());
            }
        });

        records.forEach(function (record) {
            let row = rowTemplate
                .replace(/\{\{id\}\}/g, record.id)
                .replace(/\{\{database_id\}\}/g, databaseId)
                .replace(/\{\{created_at\}\}/g, WebDatabaseRecord.formatDateTime(record.created_at));

            // タイトルフィールドの処理
            let titleDisplay = '';
            if (record.title) {
                // ハイフンで区切られたタイトルを空白で囲む（見やすさのため）
                titleDisplay = record.title.replace(/\s*-\s*/g, ' - ');
            } else {
                titleDisplay = `ID: ${record.id}`;
            }

            row = row.replace(/\{\{title\}\}/g, titleDisplay);
            row = row.replace(/\{\{creator_name\}\}/g, record.creator_name || '');

            // フィールド値のプレースホルダーを置換
            row = row.replace(/\{\{field_values\}\}/g, '');

            container.append(row);
        });
    },

    // レコードのページネーション表示
    renderRecordPagination: function (pagination) {
        $('#total-records').text(pagination.total);

        const start = (pagination.current_page - 1) * pagination.limit + 1;
        const end = Math.min(pagination.current_page * pagination.limit, pagination.total);
        $('#showing-records').text(`${start}-${end}`);

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
            const page = parseInt($(this).data('page'));
            WebDatabaseRecord.currentPage = page;
            WebDatabaseRecord.loadRecords();
        });
    },

    applyViewSettings: function (settings) {
        if (!settings || typeof settings !== 'object') {
            return;
        }

        const search = settings.search || '';
        this.searchTerm = search;
        $('#search-records').val(search);

        this.filters = {};
        $('.filter-field').each(function () {
            $(this).val('');
        });
        const filters = settings.filters || {};
        Object.keys(filters).forEach((fieldId) => {
            const value = filters[fieldId];
            this.filters[fieldId] = value;
            $(`#filter-${fieldId}`).val(value);
        });

        this.sortField = settings.sort || null;
        this.sortOrder = (settings.order || 'asc') === 'desc' ? 'desc' : 'asc';
        this.currentPage = 1;
        this.loadRecords();
    },

    collectCurrentSettings: function () {
        const filters = {};
        $('.filter-field').each(function () {
            const input = $(this);
            const value = input.val();
            if (!value) {
                return;
            }
            const fieldId = (input.attr('id') || '').replace('filter-', '');
            if (!fieldId) {
                return;
            }
            filters[fieldId] = value;
        });

        return {
            search: this.searchTerm || '',
            filters: filters,
            sort: this.sortField || null,
            order: this.sortOrder || 'asc'
        };
    },

    saveCurrentView: function () {
        const pathParts = window.location.pathname.split('/');
        const databaseId = pathParts[pathParts.length - 1];
        const name = ($('#view-name').val() || '').trim();
        if (!name) {
            App.showNotification('保存名を入力してください', 'error');
            return;
        }

        const selectedId = $('#view-selector').val() || null;
        const scopeType = ($('#view-scope').val() || 'private').trim();
        const organizationId = ($('#view-organization').val() || '').trim();
        const payload = {
            id: selectedId ? parseInt(selectedId, 10) : null,
            name: name,
            type: 'list',
            scope_type: scopeType,
            organization_id: organizationId || null,
            settings: this.collectCurrentSettings()
        };

        $.ajax({
            url: `${BASE_PATH}/api/webdatabase/${databaseId}/views`,
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(payload),
            success: function (response) {
                if (response.success) {
                    App.showNotification(response.message || 'ビューを保存しました', 'success');
                    window.location.reload();
                } else {
                    App.showNotification(response.error || 'ビュー保存に失敗しました', 'error');
                }
            },
            error: function () {
                App.showNotification('通信エラーが発生しました', 'error');
            }
        });
    },

    deleteSelectedView: function () {
        const selectedId = $('#view-selector').val() || '';
        if (!selectedId) {
            App.showNotification('削除対象のビューを選択してください', 'error');
            return;
        }
        if (!confirm('このビューを削除しますか？')) {
            return;
        }

        $.ajax({
            url: `${BASE_PATH}/api/webdatabase/views/${selectedId}`,
            type: 'DELETE',
            success: function (response) {
                if (response.success) {
                    App.showNotification(response.message || 'ビューを削除しました', 'success');
                    window.location.reload();
                } else {
                    App.showNotification(response.error || 'ビュー削除に失敗しました', 'error');
                }
            },
            error: function () {
                App.showNotification('通信エラーが発生しました', 'error');
            }
        });
    },

    // ソートインジケーターの更新
    updateSortIndicators: function () {
        $('.sortable').removeClass('sorting-asc sorting-desc');

        if (WebDatabaseRecord.sortField) {
            const th = $(`.sortable[data-field="${WebDatabaseRecord.sortField}"]`);
            th.addClass(WebDatabaseRecord.sortOrder === 'asc' ? 'sorting-asc' : 'sorting-desc');
        }
    },

    // レコードフォームの初期化
    initRecordForm: function () {
        // ユーザーフィールドとオーガニゼーションフィールドの初期化
        this.initUserFieldOptions();
        this.initOrganizationFieldOptions();
        this.initRelationFields();

        // フォーム送信処理
        $('#record-form').on('submit', function (e) {
            e.preventDefault();

            // フォームデータの取得
            const formData = new FormData(this);

            // APIリクエスト
            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                beforeSend: function () {
                    $('button[type="submit"]').prop('disabled', true);
                    $('.is-invalid').removeClass('is-invalid');
                    $('.invalid-feedback').text('');
                },
                success: function (response) {
                    if (response.success) {
                        App.showNotification(response.message, 'success');

                        // リダイレクト
                        if (response.redirect) {
                            setTimeout(function () {
                                window.location.href = response.redirect;
                            }, 1000);
                        }
                    } else {
                        App.showNotification(response.error, 'error');

                        // バリデーションエラーの表示
                        if (response.validation) {
                            for (const field in response.validation) {
                                let input;
                                if (field.startsWith('fields.')) {
                                    // フィールドエラーの場合
                                    const fieldId = field.split('.')[1];
                                    input = $(`[name="fields[${fieldId}]"]`);
                                } else {
                                    input = $(`[name="${field}"]`);
                                }

                                if (input.length) {
                                    input.addClass('is-invalid');
                                    input.next('.invalid-feedback').text(response.validation[field]);
                                }
                            }
                        }
                    }
                },
                error: function () {
                    App.showNotification('エラーが発生しました', 'error');
                },
                complete: function () {
                    $('button[type="submit"]').prop('disabled', false);
                }
            });
        });

        // ファイルアップロード表示名の更新
        $('input[type="file"]').on('change', function () {
            const fileName = $(this).val().split('\\').pop();
            $(this).next('.custom-file-label').text(fileName || 'ファイルを選択...');
        });
    },

    // レコード詳細ページの初期化
    initRecordView: function () {
        // ユーザーフィールドとオーガニゼーションフィールドの表示
        this.initUserFieldDisplay();
        this.initOrganizationFieldDisplay();

        // 削除ボタンのイベント処理
        // $('.btn-delete').off('click').on('click', function () {
        //     const url = $(this).data('url');
        //     const confirmMessage = $(this).data('confirm');
        //     const redirect = $(this).data('redirect');

        //     if (confirm(confirmMessage)) {
        //         $.ajax({
        //             url: url,
        //             type: 'DELETE',
        //             success: function (response) {
        //                 if (response.success) {
        //                     App.showNotification(response.message, 'success');

        //                     // リダイレクト
        //                     if (redirect) {
        //                         setTimeout(function () {
        //                             window.location.href = redirect;
        //                         }, 1000);
        //                         return false;
        //                     }
        //                     return false;
        //                 } else {
        //                     App.showNotification(response.error, 'error');
        //                 }
        //             },
        //             error: function () {
        //                 App.showNotification('エラーが発生しました', 'error');
        //             }
        //         });
        //         return false;
        //     }
        //     return false;
        // });
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

    // ユーザーフィールド値の表示
    initUserFieldDisplay: function () {
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

    // 組織フィールド値の表示
    initOrganizationFieldDisplay: function () {
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
    },

    // リレーションフィールドの初期化
    initRelationFields: function () {
        $('.relation-field-container').each(function () {
            const $container = $(this);
            const fieldId = $container.data('field-id');
            const relationDb = $container.data('relation-db');
            const $select = $container.find('.relation-select');

            if (!relationDb) return;

            // リレーション先レコードを取得
            $.ajax({
                url: BASE_PATH + '/api/webdatabase/relation-targets/' + relationDb,
                type: 'GET',
                success: function (response) {
                    if (response.success && response.data) {
                        response.data.forEach(function (record) {
                            $select.append(
                                '<option value="' + record.id + '">' +
                                $('<span>').text(record.title || 'ID:' + record.id).html() +
                                '</option>'
                            );
                        });

                        // Select2で検索可能にする
                        if ($.fn.select2) {
                            $select.select2({
                                placeholder: 'レコードを選択',
                                allowClear: true,
                                width: '100%'
                            });
                        }
                    }
                }
            });
        });
    }
};

// DOMが読み込まれたらレコード機能を初期化
$(document).ready(function () {
    WebDatabaseRecord.init();
});

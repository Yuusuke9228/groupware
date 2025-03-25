/**
 * GroupWare - ワークフロー管理JS
 */

const Workflow = {
    // 初期化
    init: function () {
        const page = $('[data-page-type]').data('page-type');

        // ページタイプに応じた初期化
        switch (page) {
            case 'index':
                this.initIndex();
                break;
            case 'templates':
                this.initTemplates();
                break;
            case 'template_form':
                this.initTemplateForm();
                break;
            case 'form_designer':
                this.initFormDesigner();
                break;
            case 'route_designer':
                this.initRouteDesigner();
                break;
            case 'requests':
                this.initRequests();
                break;
            case 'approvals':
                this.initApprovals();
                break;
            case 'request_form':
                this.initRequestForm();
                break;
            case 'request_view':
                this.initRequestView();
                break;
            case 'delegates':
                this.initDelegates();
                break;
        }

        // 共通のイベントハンドラ
        this.initCommonHandlers();
    },

    // 共通イベントハンドラ
    initCommonHandlers: function () {
        // Select2の初期化
        if ($.fn.select2) {
            $('.select2').select2({
                theme: 'bootstrap-5'
            });
        }

        // Flatpickrの初期化
        if (typeof flatpickr !== 'undefined') {
            flatpickr('.date-picker', {
                dateFormat: 'Y-m-d',
                locale: 'ja',
                disableMobile: true
            });
        }

        // Tooltipの初期化
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    },

    // インデックスページの初期化
    initIndex: function () {
        // 統計情報の取得
        this.loadWorkflowStats();
    },

    // テンプレート一覧ページの初期化
    initTemplates: function () {
        // データテーブルの初期化
        this.initTemplateTable();

        // 新規作成ボタンのイベントハンドラ
        $('#btn-create-template').on('click', function () {
            window.location.href = BASE_PATH + '/workflow/create-template';
        });

        // 検索フォームのイベントハンドラ
        $('#search-form').on('submit', function (e) {
            e.preventDefault();
            const searchTerm = $('#search-input').val();
            window.location.href = BASE_PATH + '/workflow/templates?search=' + encodeURIComponent(searchTerm);
        });

        // 検索クリアボタン
        $('#search-clear').on('click', function () {
            window.location.href = BASE_PATH + '/workflow/templates';
        });
    },

    // テンプレートデータテーブルの初期化
    initTemplateTable: function () {
        $('#templates-table').DataTable({
            responsive: true,
            language: {
                url: BASE_PATH + '/js/vendor/dataTables.japanese.json'
            },
            columnDefs: [
                { orderable: false, targets: -1 } // 最後の列（操作）はソート不可
            ]
        });
    },

    // テンプレートフォームの初期化
    initTemplateForm: function () {
        // フォーム送信前のバリデーション
        $('form').on('submit', function (e) {
            if (!Workflow.validateTemplateForm()) {
                e.preventDefault();
            }
        });
    },

    // テンプレートフォームのバリデーション
    validateTemplateForm: function () {
        let isValid = true;

        // 必須フィールドのチェック
        if (!$('#name').val().trim()) {
            $('#name').addClass('is-invalid');
            isValid = false;
        } else {
            $('#name').removeClass('is-invalid');
        }

        return isValid;
    },

    // フォームデザイナーの初期化
    initFormDesigner: function () {
        // フォームデザイナーの初期化はworkflow-form-designer.jsで実装
    },

    // 承認経路デザイナーの初期化
    initRouteDesigner: function () {
        // 承認経路デザイナーの初期化はworkflow-route-designer.jsで実装
    },

    // 申請一覧の初期化
    initRequests: function () {
        // データテーブルの初期化
        $('#requests-table').DataTable({
            responsive: true,
            language: {
                url: BASE_PATH + '/js/vendor/dataTables.japanese.json'
            },
            columnDefs: [
                { orderable: false, targets: -1 } // 最後の列（操作）はソート不可
            ]
        });

        // フィルタフォームのイベントハンドラ
        $('#filter-form').on('submit', function (e) {
            e.preventDefault();

            let url = BASE_PATH + '/workflow/requests?';
            const status = $('#filter-status').val();
            const templateId = $('#filter-template').val();
            const search = $('#filter-search').val();

            if (status) url += `status=${status}&`;
            if (templateId) url += `template_id=${templateId}&`;
            if (search) url += `search=${encodeURIComponent(search)}&`;

            window.location.href = url;
        });

        // フィルタクリアボタン
        $('#filter-clear').on('click', function () {
            window.location.href = BASE_PATH + '/workflow/requests';
        });
    },

    // 承認待ち一覧の初期化
    initApprovals: function () {
        // データテーブルの初期化
        $('#approvals-table').DataTable({
            responsive: true,
            language: {
                url: BASE_PATH + '/js/vendor/dataTables.japanese.json'
            },
            columnDefs: [
                { orderable: false, targets: -1 } // 最後の列（操作）はソート不可
            ]
        });

        // フィルタフォームのイベントハンドラ
        $('#filter-form').on('submit', function (e) {
            e.preventDefault();

            let url = BASE_PATH + '/workflow/approvals?';
            const templateId = $('#filter-template').val();
            const search = $('#filter-search').val();

            if (templateId) url += `template_id=${templateId}&`;
            if (search) url += `search=${encodeURIComponent(search)}&`;

            window.location.href = url;
        });

        // フィルタクリアボタン
        $('#filter-clear').on('click', function () {
            window.location.href = BASE_PATH + '/workflow/approvals';
        });
    },

    // 申請フォームの初期化
    initRequestForm: function () {
        // フォーム送信前のバリデーション
        $('form').on('submit', function (e) {
            if (!Workflow.validateRequestForm()) {
                e.preventDefault();
            }
        });

        // ドラフト保存ボタン
        $('#btn-save-draft').on('click', function () {
            $('#status').val('draft');
        });

        // 申請提出ボタン
        $('#btn-submit-request').on('click', function () {
            $('#status').val('pending');
        });
    },

    // 申請フォームのバリデーション
    validateRequestForm: function () {
        let isValid = true;

        // タイトルは必須
        if (!$('#title').val().trim()) {
            $('#title').addClass('is-invalid');
            isValid = false;
        } else {
            $('#title').removeClass('is-invalid');
        }

        // 必須フィールドのバリデーション
        $('.required-field').each(function () {
            if (!$(this).val().trim()) {
                $(this).addClass('is-invalid');
                isValid = false;
            } else {
                $(this).removeClass('is-invalid');
            }
        });

        return isValid;
    },

    // 申請詳細ページの初期化
    initRequestView: function () {
        // 承認ボタンクリック時
        $('#btn-approve').on('click', function () {
            $('#action').val('approved');
            $('#approval-form').submit();
        });

        // 却下ボタンクリック時
        $('#btn-reject').on('click', function () {
            $('#action').val('rejected');
            $('#approval-form').submit();
        });

        // コメント送信ボタンクリック時
        $('#btn-add-comment').on('click', function () {
            const comment = $('#comment').val().trim();
            if (!comment) return;

            const requestId = $('#request-id').val();
            Workflow.addComment(requestId, comment);
        });

        // PDFエクスポートボタン
        $('#btn-export-pdf').on('click', function () {
            const requestId = $('#request-id').val();
            Workflow.exportPdf(requestId);
        });

        // CSVエクスポートボタン
        $('#btn-export-csv').on('click', function () {
            const requestId = $('#request-id').val();
            Workflow.exportCsv(requestId);
        });

        // キャンセルボタン
        $('#btn-cancel-request').on('click', function () {
            if (confirm('この申請をキャンセルしますか？')) {
                const requestId = $('#request-id').val();
                Workflow.cancelRequest(requestId);
            }
        });
    },

    // 代理承認設定ページの初期化
    initDelegates: function () {
        // データテーブルの初期化
        $('#delegates-table').DataTable({
            responsive: true,
            language: {
                url: BASE_PATH + '/js/vendor/dataTables.japanese.json'
            },
            columnDefs: [
                { orderable: false, targets: -1 } // 最後の列（操作）はソート不可
            ]
        });

        // 新規作成フォームの初期化
        $('#delegate-form').on('submit', function (e) {
            e.preventDefault();

            if (!Workflow.validateDelegateForm()) {
                return;
            }

            const formData = $(this).serialize();
            Workflow.addDelegation(formData);
        });
    },

    // 代理承認フォームのバリデーション
    validateDelegateForm: function () {
        let isValid = true;

        // 必須フィールドのチェック
        if (!$('#delegate_id').val()) {
            $('#delegate_id').addClass('is-invalid');
            isValid = false;
        } else {
            $('#delegate_id').removeClass('is-invalid');
        }

        if (!$('#start_date').val()) {
            $('#start_date').addClass('is-invalid');
            isValid = false;
        } else {
            $('#start_date').removeClass('is-invalid');
        }

        if (!$('#end_date').val()) {
            $('#end_date').addClass('is-invalid');
            isValid = false;
        } else {
            $('#end_date').removeClass('is-invalid');
        }

        // 日付の妥当性チェック
        if ($('#start_date').val() && $('#end_date').val()) {
            const startDate = new Date($('#start_date').val());
            const endDate = new Date($('#end_date').val());

            if (startDate > endDate) {
                $('#end_date').addClass('is-invalid');
                $('#end_date').next('.invalid-feedback').text('終了日は開始日以降の日付を指定してください');
                isValid = false;
            }
        }

        return isValid;
    },

    // 統計情報の取得
    loadWorkflowStats: function () {
        App.apiGet('/workflow/stats')
            .then(response => {
                if (response.success) {
                    this.renderWorkflowStats(response.data);
                }
            })
            .catch(error => {
                console.error('Failed to load workflow stats:', error);
            });
    },

    // 統計情報の表示
    renderWorkflowStats: function (data) {
        // ダッシュボード統計表示
        $('#total-templates').text(data.templates_count || 0);
        $('#total-requests').text(data.requests_count || 0);
        $('#pending-approvals').text(data.pending_approvals || 0);
        $('#my-requests').text(data.my_requests || 0);

        // 円グラフ表示（Chart.jsを使用）
        if (typeof Chart !== 'undefined' && data.status_stats) {
            const ctx = document.getElementById('status-chart');
            if (ctx) {
                new Chart(ctx, {
                    type: 'pie',
                    data: {
                        labels: ['下書き', '承認待ち', '承認済み', '却下', 'キャンセル'],
                        datasets: [{
                            data: [
                                data.status_stats.draft || 0,
                                data.status_stats.pending || 0,
                                data.status_stats.approved || 0,
                                data.status_stats.rejected || 0,
                                data.status_stats.cancelled || 0
                            ],
                            backgroundColor: [
                                '#6c757d', // 下書き
                                '#ffc107', // 承認待ち
                                '#28a745', // 承認済み
                                '#dc3545', // 却下
                                '#17a2b8'  // キャンセル
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        legend: {
                            position: 'bottom'
                        }
                    }
                });
            }
        }

        // 最近の申請リスト表示
        if (data.recent_requests && data.recent_requests.length > 0) {
            const container = $('#recent-requests');
            container.empty();

            data.recent_requests.forEach(request => {
                const statusBadge = this.getStatusBadge(request.status);
                const date = new Date(request.created_at).toLocaleDateString('ja-JP');

                container.append(`
                    <div class="list-group-item">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1">${request.title}</h6>
                            <small>${date}</small>
                        </div>
                        <p class="mb-1">${request.template_name}</p>
                        <div class="d-flex justify-content-between">
                            <small>${request.requester_name}</small>
                            ${statusBadge}
                        </div>
                    </div>
                `);
            });
        } else {
            $('#recent-requests').html('<div class="list-group-item">申請はありません</div>');
        }
    },

    // コメント追加
    addComment: function (requestId, comment) {
        App.apiPost(`/workflow/requests/${requestId}/comments`, { comment: comment })
            .then(response => {
                if (response.success) {
                    // コメント入力欄をクリア
                    $('#comment').val('');

                    // コメント一覧を更新
                    this.renderComments(response.data.comments);

                    // 通知表示
                    App.showNotification(response.message, 'success');
                } else {
                    App.showNotification(response.error, 'error');
                }
            })
            .catch(error => {
                console.error('Failed to add comment:', error);
                App.showNotification('コメントの追加に失敗しました', 'error');
            });
    },

    // コメント一覧の表示
    renderComments: function (comments) {
        const container = $('#comments-container');
        container.empty();

        if (comments && comments.length > 0) {
            comments.forEach(comment => {
                const date = new Date(comment.created_at).toLocaleString('ja-JP');

                container.append(`
                    <div class="card mb-2">
                        <div class="card-header d-flex justify-content-between">
                            <span>${comment.user_name}</span>
                            <small>${date}</small>
                        </div>
                        <div class="card-body">
                            <p class="card-text">${comment.comment}</p>
                        </div>
                    </div>
                `);
            });
        } else {
            container.html('<p class="text-muted">コメントはありません</p>');
        }
    },

    // PDFエクスポート
    exportPdf: function (requestId) {
        window.location.href = BASE_PATH + `/api/workflow/requests/${requestId}/export/pdf`;
    },

    // CSVエクスポート
    exportCsv: function (requestId) {
        window.location.href = BASE_PATH + `/api/workflow/requests/${requestId}/export/csv`;
    },

    // 申請キャンセル
    cancelRequest: function (requestId) {
        App.apiDelete(`/workflow/requests/${requestId}`)
            .then(response => {
                if (response.success) {
                    App.showNotification(response.message, 'success');

                    if (response.redirect) {
                        setTimeout(() => {
                            window.location.href = response.redirect;
                        }, 1500);
                    }
                } else {
                    App.showNotification(response.error, 'error');
                }
            })
            .catch(error => {
                console.error('Failed to cancel request:', error);
                App.showNotification('申請のキャンセルに失敗しました', 'error');
            });
    },

    // 代理承認設定追加
    addDelegation: function (formData) {
        $.ajax({
            url: BASE_PATH + '/api/workflow/delegates',
            type: 'POST',
            data: formData,
            success: function (response) {
                if (response.success) {
                    App.showNotification(response.message, 'success');

                    if (response.redirect) {
                        setTimeout(() => {
                            window.location.href = response.redirect;
                        }, 1500);
                    }
                } else {
                    App.showNotification(response.error, 'error');
                }
            },
            error: function (xhr, status, error) {
                console.error('Failed to add delegation:', error);
                App.showNotification('代理承認設定の追加に失敗しました', 'error');
            }
        });
    },

    // ステータスに応じたバッジHTMLを取得
    getStatusBadge: function (status) {
        switch (status) {
            case 'draft':
                return '<span class="badge bg-secondary">下書き</span>';
            case 'pending':
                return '<span class="badge bg-warning">承認待ち</span>';
            case 'approved':
                return '<span class="badge bg-success">承認済み</span>';
            case 'rejected':
                return '<span class="badge bg-danger">却下</span>';
            case 'cancelled':
                return '<span class="badge bg-info">キャンセル</span>';
            default:
                return '<span class="badge bg-secondary">不明</span>';
        }
    }
};

// DOMが読み込まれたら初期化
$(document).ready(function () {
    Workflow.init();
});
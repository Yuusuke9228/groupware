/**
 * GroupWare - 日報機能用JavaScript
 */

// 日報機能の名前空間
const DailyReport = {
    // 初期化
    init: function () {
        // 現在のページによって処理を分岐
        const path = window.location.pathname;

        if (path.includes('/daily-report/create') || path.includes('/daily-report/edit')) {
            this.initForm();
        } else if (path.includes('/daily-report/view')) {
            this.initView();
        } else if (path.includes('/daily-report/list')) {
            this.initList();
        } else if (path.includes('/daily-report/templates')) {
            this.initTemplates();
        } else if (path.includes('/daily-report/stats')) {
            this.initStats();
        } else {
            // ダッシュボード
            this.initDashboard();
        }
    },

    // 日報作成・編集フォームの初期化
    initForm: function () {
        // Select2の初期化
        $('.select2').select2({
            placeholder: '選択してください',
            width: '100%'
        });

        // フォーム送信イベント
        $('#reportForm').on('submit', function (e) {
            e.preventDefault();

            // バリデーションリセット
            $(this).find('.is-invalid').removeClass('is-invalid');
            $(this).find('.invalid-feedback').text('');

            // 公開範囲設定の整形
            const permissions = [];

            // ユーザー権限
            $('#user_permissions option:selected').each(function () {
                const value = $(this).val();
                const [type, id] = value.split(':');
                permissions.push({
                    type: type,
                    id: parseInt(id)
                });
            });

            // 組織権限
            $('#organization_permissions option:selected').each(function () {
                const value = $(this).val();
                const [type, id] = value.split(':');
                permissions.push({
                    type: type,
                    id: parseInt(id)
                });
            });

            // タグの処理
            let tags = $('#tags').val().trim();
            if (tags) {
                tags = tags.split(',').map(tag => tag.trim()).filter(tag => tag);
            } else {
                tags = [];
            }

            // スケジュールを配列に変換
            const schedules = [];
            $('input[name="schedules[]"]:checked').each(function () {
                schedules.push(parseInt($(this).val()));
            });

            // タスクを配列に変換
            const tasks = [];
            $('input[name="tasks[]"]:checked').each(function () {
                tasks.push(parseInt($(this).val()));
            });

            // 下書きフラグ
            const isDraft = $('#is_draft').is(':checked');

            // フォームデータの作成
            const formData = {
                report_date: $('#report_date').val(),
                title: $('#title').val(),
                content: $('#content').val(),
                tags: tags,
                permissions: permissions,
                schedules: schedules,
                tasks: tasks,
                status: isDraft ? 'draft' : 'published'
            };

            // API呼び出し
            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(formData),
                dataType: 'json',
                beforeSend: function () {
                    // 送信ボタンを無効化
                    $('button[type="submit"]').prop('disabled', true);
                },
                success: function (response) {
                    if (response.success) {
                        // 成功時は詳細ページにリダイレクト
                        window.location.href = response.data.redirect;
                    } else {
                        // エラーメッセージを表示
                        alert(response.error || 'エラーが発生しました');

                        // バリデーションエラーの場合
                        if (response.validation) {
                            for (const field in response.validation) {
                                const errorMsg = response.validation[field];
                                $(`#${field}`).addClass('is-invalid');
                                $(`#${field}`).next('.invalid-feedback').text(errorMsg);
                            }
                        }
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Error:', error);
                    alert('ネットワークエラーが発生しました');
                },
                complete: function () {
                    // 送信ボタンを有効化
                    $('button[type="submit"]').prop('disabled', false);
                }
            });
        });

        // 削除ボタンの処理
        $('#deleteButton').on('click', function () {
            if (confirm('本当にこの日報を削除しますか？この操作は元に戻せません。')) {
                const reportId = $(this).data('id');

                // 削除API呼び出し
                $.ajax({
                    url: `${BASE_PATH}/api/daily-report/${reportId}`,
                    type: 'DELETE',
                    dataType: 'json',
                    success: function (response) {
                        if (response.success) {
                            // 成功時は一覧ページにリダイレクト
                            window.location.href = response.data.redirect;
                        } else {
                            // エラーメッセージを表示
                            alert(response.error || 'エラーが発生しました');
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error('Error:', error);
                        alert('ネットワークエラーが発生しました');
                    }
                });
            }
        });

        // テンプレート選択時
        $('#template_id').on('change', function () {
            const templateId = $(this).val();
            if (templateId) {
                window.location.href = `${BASE_PATH}/daily-report/create?template_id=${templateId}`;
            }
        });
    },

    // 日報詳細表示の初期化
    initView: function () {
        // いいねボタンの処理
        $('#likeButton').on('click', function () {
            const reportId = $(this).data('id');

            // いいねAPI呼び出し
            $.ajax({
                url: `${BASE_PATH}/api/daily-report/${reportId}/like`,
                type: 'POST',
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        // いいねボタンの見た目を更新
                        if (response.data.has_liked) {
                            $('#likeButton').removeClass('btn-outline-danger').addClass('btn-danger');
                        } else {
                            $('#likeButton').removeClass('btn-danger').addClass('btn-outline-danger');
                        }

                        // いいね数を更新
                        $('#likeCount').text(response.data.likes_count);
                    } else {
                        alert(response.error || 'エラーが発生しました');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Error:', error);
                    alert('ネットワークエラーが発生しました');
                }
            });
        });

        // コメント送信処理
        $('#commentForm').on('submit', function (e) {
            e.preventDefault();

            const reportId = $('[name="report_id"]').val();
            const comment = $('[name="comment"]').val();

            if (!comment.trim()) {
                alert('コメントを入力してください');
                return;
            }

            // コメント送信API呼び出し
            $.ajax({
                url: `${BASE_PATH}/api/daily-report/${reportId}/comment`,
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ comment: comment }),
                dataType: 'json',
                beforeSend: function () {
                    $('button[type="submit"]').prop('disabled', true);
                },
                success: function (response) {
                    if (response.success) {
                        // コメント欄を更新
                        const commentList = $('#commentList');
                        commentList.empty();

                        if (response.data.comments && response.data.comments.length > 0) {
                            response.data.comments.forEach(function (comment) {
                                const commentHtml = `
                                    <div class="d-flex mb-3">
                                        <div class="flex-shrink-0">
                                            <div class="avatar">
                                                <span>${comment.display_name.substring(0, 1)}</span>
                                            </div>
                                        </div>
                                        <div class="ms-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <h6 class="mb-0">${comment.display_name}</h6>
                                                <small class="text-muted">${new Date(comment.created_at).toLocaleString()}</small>
                                            </div>
                                            <p class="mb-0">${comment.comment.replace(/\n/g, '<br>')}</p>
                                        </div>
                                    </div>
                                `;
                                commentList.append(commentHtml);
                            });
                        } else {
                            commentList.html('<p class="text-center">まだコメントはありません</p>');
                        }

                        // コメント入力欄をクリア
                        $('[name="comment"]').val('');
                    } else {
                        alert(response.error || 'エラーが発生しました');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Error:', error);
                    alert('ネットワークエラーが発生しました');
                },
                complete: function () {
                    $('button[type="submit"]').prop('disabled', false);
                }
            });
        });
    },

    // 日報一覧の初期化
    initList: function () {
        // フィルターのリセットボタン
        $('#resetFilter').on('click', function () {
            $('#start_date').val('');
            $('#end_date').val('');
            $('#user_id').val('');
            $('#tag_id').val('');
            $('#search').val('');
            $('#filterForm').submit();
        });

        // 読み取り可能な日報の総数取得
        const fetchTotalCount = function () {
            const filters = {};
            if ($('#start_date').val()) filters.start_date = $('#start_date').val();
            if ($('#end_date').val()) filters.end_date = $('#end_date').val();
            if ($('#user_id').val()) filters.user_id = $('#user_id').val();
            if ($('#tag_id').val()) filters.tag_id = $('#tag_id').val();
            if ($('#search').val()) filters.search = $('#search').val();

            // APIで総数を取得
            $.ajax({
                url: `${BASE_PATH}/api/daily-report/count`,
                type: 'GET',
                data: filters,
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        $('#totalCount').text(response.data.count);
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Error fetching total count:', error);
                }
            });
        };

        // 読み取り可能な日報の総数が0の場合、APIで取得
        if ($('#totalCount').text() === '0' && $('#user_id').val() && $('#user_id').val() !== $('#current_user_id').val()) {
            fetchTotalCount();
        }
    },

    // テンプレート管理の初期化
    initTemplates: function () {
        // テンプレート編集フォーム
        $('#templateForm').on('submit', function (e) {
            e.preventDefault();

            const formData = {
                title: $('#title').val(),
                content: $('#content').val(),
                is_public: $('#is_public').is(':checked')
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
                        window.location.href = response.data.redirect;
                    } else {
                        alert(response.error || 'エラーが発生しました');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Error:', error);
                    alert('ネットワークエラーが発生しました');
                },
                complete: function () {
                    $('button[type="submit"]').prop('disabled', false);
                }
            });
        });

        // テンプレート削除
        $('.delete-template').on('click', function () {
            if (confirm('本当にこのテンプレートを削除しますか？')) {
                const templateId = $(this).data('id');

                $.ajax({
                    url: `${BASE_PATH}/api/daily-report/template/${templateId}`,
                    type: 'DELETE',
                    dataType: 'json',
                    success: function (response) {
                        if (response.success) {
                            window.location.reload();
                        } else {
                            alert(response.error || 'エラーが発生しました');
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error('Error:', error);
                        alert('ネットワークエラーが発生しました');
                    }
                });
            }
        });
    },

    // 統計情報の初期化
    initStats: function () {
        // 期間変更時のデータ取得
        $('.stats-date-filter').on('change', function () {
            $('#statsFilterForm').submit();
        });

        // グラフ描画
        if (typeof Chart !== 'undefined' && $('#monthlyReportsChart').length > 0) {
            const ctx = document.getElementById('monthlyReportsChart').getContext('2d');

            // データは埋め込み変数から取得することを想定
            const monthlyData = window.monthlyStats || [];

            const labels = [];
            const counts = [];

            monthlyData.forEach(function (item) {
                // YYYY-MM形式を「YYYY年MM月」形式に変換
                const [year, month] = item.month.split('-');
                labels.push(`${year}年${month}月`);
                counts.push(item.count);
            });

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: '日報数',
                        data: counts,
                        backgroundColor: 'rgba(54, 162, 235, 0.5)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    },
                    plugins: {
                        title: {
                            display: true,
                            text: '月別日報作成数'
                        }
                    }
                }
            });
        }
    },

    // ダッシュボードの初期化
    initDashboard: function () {
        // 新規日報作成ボタン
        $('#createTodayReport').on('click', function () {
            window.location.href = `${BASE_PATH}/daily-report/create?date=${$(this).data('date')}`;
        });

        // テンプレート選択モーダル
        $('#templateModal').on('show.bs.modal', function () {
            // 必要に応じてテンプレート一覧をAPIで取得
        });
    }
};

// DOMが読み込まれたら初期化
$(document).ready(function () {
    DailyReport.init();
});
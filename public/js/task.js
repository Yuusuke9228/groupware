/**
 * GroupWare - タスク管理 JS
 */

// タスク管理機能を管理するオブジェクト
const Task = {
    // 設定
    config: {
        dateFormat: 'YYYY-MM-DD'
    },

    // 初期化
    init: function () {
        // イベントリスナーを設定
        this.setupEventListeners();

        // Select2の初期化
        this.initSelect2();

        // フラットピッカーの初期化
        this.initFlatpickr();

        // データテーブルの初期化
        this.initDataTables();

        // タスク概要のグラフを初期化
        this.initTaskCharts();
    },

    // イベントリスナーを設定
    setupEventListeners: function () {
        // ボード作成フォーム - 所有者タイプ変更時
        $('#ownerType').on('change', function () {
            const ownerType = $(this).val();
            $('#teamOwnerId, #organizationOwnerId, #userOwnerId').addClass('d-none');

            if (ownerType === 'team') {
                $('#teamOwnerId').removeClass('d-none');
            } else if (ownerType === 'organization') {
                $('#organizationOwnerId').removeClass('d-none');
            } else {
                $('#userOwnerId').removeClass('d-none');
            }
        });

        // チーム作成/編集 - メンバー役割変更
        $(document).on('change', '.member-role-select', function () {
            const userId = $(this).data('user-id');
            const role = $(this).val();

            // 非表示の入力フィールドを更新
            $(`#memberRole-${userId}`).val(role);
        });

        // マイタスク - フィルター適用
        $('.task-filter').on('click', function (e) {
            e.preventDefault();
            const type = $(this).data('type');
            const value = $(this).data('value');

            // 現在のURLからパラメータを取得
            const url = new URL(window.location.href);
            const params = new URLSearchParams(url.search);

            // パラメータを設定
            params.set(type, value);

            // 新しいURLに遷移
            window.location.href = `${url.pathname}?${params.toString()}`;
        });

        // マイタスク - フィルタークリア
        $('.clear-filter').on('click', function (e) {
            e.preventDefault();
            window.location.href = window.location.pathname;
        });
    },

    // Select2の初期化
    initSelect2: function () {
        if ($.fn.select2) {
            $('.select2').select2({
                theme: 'bootstrap-5',
                width: '100%'
            });

            // 担当者選択
            $('.select2-users').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: '選択してください'
            });

            // ラベル選択
            $('.select2-labels').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: 'ラベルを選択',
                templateResult: function (label) {
                    if (!label.id) return label.text;

                    const color = $(label.element).data('color') || '#cccccc';
                    return $(`<span><i class="fas fa-tag" style="color: ${color}"></i> ${label.text}</span>`);
                }
            });
        }
    },

    // Flatpickrの初期化
    initFlatpickr: function () {
        if (typeof flatpickr !== 'undefined') {
            // 日付選択
            flatpickr('.datepicker', {
                dateFormat: 'Y-m-d',
                locale: 'ja',
                disableMobile: true
            });
        }
    },

    // DataTablesの初期化
    initDataTables: function () {
        if ($.fn.DataTable) {
            $('.datatable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/Japanese.json'
                },
                pageLength: 25,
                responsive: true
            });
        }
    },

    // タスク概要のグラフを初期化
    initTaskCharts: function () {
        // Chart.jsが利用可能かチェック
        if (typeof Chart === 'undefined') return;

        // タスク状態チャート
        const statusCtx = document.getElementById('taskStatusChart');
        if (statusCtx) {
            const statusData = JSON.parse(statusCtx.dataset.values || '[]');
            const statusLabels = JSON.parse(statusCtx.dataset.labels || '[]');
            const statusColors = [
                '#6c757d', // secondary - 未対応
                '#0d6efd', // primary - 処理中
                '#198754', // success - 完了
                '#ffc107'  // warning - 保留
            ];

            new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: statusLabels,
                    datasets: [{
                        data: statusData,
                        backgroundColor: statusColors
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                font: {
                                    size: 12
                                }
                            }
                        }
                    }
                }
            });
        }

        // タスク優先度チャート
        const priorityCtx = document.getElementById('taskPriorityChart');
        if (priorityCtx) {
            const priorityData = JSON.parse(priorityCtx.dataset.values || '[]');
            const priorityLabels = JSON.parse(priorityCtx.dataset.labels || '[]');
            const priorityColors = [
                '#dc3545', // danger - 最高
                '#fd7e14', // orange - 高
                '#0d6efd', // primary - 通常
                '#0dcaf0', // info - 低
                '#6c757d'  // secondary - 最低
            ];

            new Chart(priorityCtx, {
                type: 'doughnut',
                data: {
                    labels: priorityLabels,
                    datasets: [{
                        data: priorityData,
                        backgroundColor: priorityColors
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                font: {
                                    size: 12
                                }
                            }
                        }
                    }
                }
            });
        }

        // ボード別タスク数
        const boardsCtx = document.getElementById('taskBoardsChart');
        if (boardsCtx) {
            const boardsData = JSON.parse(boardsCtx.dataset.values || '[]');
            const boardsLabels = JSON.parse(boardsCtx.dataset.labels || '[]');

            new Chart(boardsCtx, {
                type: 'bar',
                data: {
                    labels: boardsLabels,
                    datasets: [{
                        label: 'タスク数',
                        data: boardsData,
                        backgroundColor: '#0d6efd'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        }
    },

    // チームメンバーを追加
    addTeamMember: function (userId, userName, role = 'member') {
        // 既に追加済みかチェック
        if ($(`#member-row-${userId}`).length > 0) {
            alert('このユーザーは既にメンバーに追加されています');
            return;
        }

        // メンバー行のHTML生成
        const memberHtml = `
            <tr id="member-row-${userId}">
                <td>
                    <input type="hidden" name="members[]" value="${userId}">
                    <input type="hidden" name="member_roles[${userId}]" id="memberRole-${userId}" value="${role}">
                    ${userName}
                </td>
                <td>
                    <select class="form-select form-select-sm member-role-select" data-user-id="${userId}">
                        <option value="member" ${role === 'member' ? 'selected' : ''}>メンバー</option>
                        <option value="admin" ${role === 'admin' ? 'selected' : ''}>管理者</option>
                    </select>
                </td>
                <td>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="Task.removeTeamMember(${userId})">
                        <i class="fas fa-times"></i>
                    </button>
                </td>
            </tr>
        `;

        // メンバーテーブルに追加
        $('#team-members-table tbody').append(memberHtml);

        // 「メンバーがいません」行を非表示
        $('#no-members-row').hide();
    },

    // チームメンバーを削除
    removeTeamMember: function (userId) {
        // 行を削除
        $(`#member-row-${userId}`).remove();

        // メンバーがいなくなったら「メンバーがいません」行を表示
        if ($('#team-members-table tbody tr').length === 1) {
            $('#no-members-row').show();
        }
    },

    // リストと担当者を取得（カード作成/編集用）
    fetchBoardDetails: function (boardId) {
        if (!boardId) return;

        // ボード詳細をAPI取得
        $.ajax({
            url: `${BASE_PATH}/api/task/boards/${boardId}`,
            type: 'GET',
            success: function (response) {
                if (response.success) {
                    const board = response.data;

                    // リスト選択肢を設定
                    const listSelect = $('#list_id');
                    listSelect.empty();

                    for (const list of board.lists) {
                        listSelect.append(`<option value="${list.id}">${list.name}</option>`);
                    }

                    // 担当者選択肢を設定
                    const assigneesSelect = $('#assignees');
                    assigneesSelect.empty();

                    for (const member of board.members) {
                        assigneesSelect.append(`<option value="${member.user_id}">${member.display_name}</option>`);
                    }

                    // Select2を再初期化
                    if ($.fn.select2) {
                        listSelect.select2({
                            theme: 'bootstrap-5',
                            width: '100%'
                        });

                        assigneesSelect.select2({
                            theme: 'bootstrap-5',
                            width: '100%',
                            placeholder: '担当者を選択'
                        });
                    }
                } else {
                    alert('ボード情報の取得に失敗しました');
                }
            },
            error: function () {
                alert('通信エラーが発生しました');
            }
        });
    }
};

// DOMが読み込まれたら初期化
$(document).ready(function () {
    Task.init();
});
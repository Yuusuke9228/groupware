/**
 * GroupWare - ホーム・ダッシュボード JS
 */

// ホーム画面の機能を管理するオブジェクト
const Home = {
    // 設定
    config: {
        refreshInterval: 60000 // 未読数を更新する間隔（ミリ秒）
    },

    // 初期化
    init: function () {
        // イベントリスナーを設定
        this.setupEventListeners();

        // 未読数の定期更新を開始
        this.startUnreadCountUpdates();

        // タスク概要の円グラフを初期化
        this.initTaskCharts();
    },

    // イベントリスナーを設定
    setupEventListeners: function () {
        // 未読通知をクリックで既読にする
        $(document).on('click', '.notification-item', function (e) {
            const notificationId = $(this).data('id');
            if (notificationId) {
                Home.markNotificationAsRead(notificationId);
            }
        });

        // 週移動ボタンのクリックイベント
        $(document).on('click', '.btn-week-nav', function (e) {
            e.preventDefault();
            const targetDate = $(this).data('date');
            if (targetDate) {
                window.location.href = BASE_PATH + '/?date=' + targetDate;
            }
        });

        // タスク概要タブのクリックイベント
        $(document).on('click', '.task-summary-tab', function (e) {
            e.preventDefault();
            $('.task-summary-tab').removeClass('active');
            $(this).addClass('active');

            const target = $(this).data('target');
            $('.task-summary-content').hide();
            $(`#${target}`).show();
        });
    },

    // 未読数の定期更新を開始
    startUnreadCountUpdates: function () {
        // 初回実行
        this.updateUnreadCounts();

        // 定期的に実行
        setInterval(() => {
            this.updateUnreadCounts();
        }, this.config.refreshInterval);
    },

    // 未読数を更新
    updateUnreadCounts: function () {
        App.apiGet('/home/unread-counts')
            .then(response => {
                if (response.success) {
                    // 未読メッセージ数を更新
                    const unreadMessages = response.data.unread_messages;
                    $('.message-unread-badge').text(unreadMessages > 0 ? unreadMessages : '');
                    $('.message-unread-badge').toggleClass('d-none', unreadMessages <= 0);

                    // 未読通知数を更新
                    const unreadNotifications = response.data.unread_notifications;
                    $('.notification-unread-badge').text(unreadNotifications > 0 ? unreadNotifications : '');
                    $('.notification-unread-badge').toggleClass('d-none', unreadNotifications <= 0);
                }
            })
            .catch(error => {
                console.error('Error updating unread counts:', error);
            });
    },

    // 通知を既読にする
    markNotificationAsRead: function (notificationId) {
        App.apiPost(`/notifications/${notificationId}/read`)
            .then(response => {
                if (response.success) {
                    // 成功したら未読数を更新
                    this.updateUnreadCounts();
                }
            })
            .catch(error => {
                console.error('Error marking notification as read:', error);
            });
    },

    // タスク概要の円グラフを初期化
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
    }
};

// DOMが読み込まれたら初期化
$(document).ready(function () {
    Home.init();
});
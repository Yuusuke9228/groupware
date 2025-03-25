/**
 * 通知管理用JavaScript
 */
document.addEventListener('DOMContentLoaded', function () {
    // 通知一覧ページの通知アイテムクリックイベント
    const notificationItems = document.querySelectorAll('.notification-item');
    notificationItems.forEach(item => {
        item.addEventListener('click', function () {
            const notificationId = this.getAttribute('data-id');

            // 既に既読の場合は何もしない
            if (!this.classList.contains('bg-light')) {
                return;
            }

            // 既読にするAPI呼び出し
            fetch(`${BASE_PATH}/api/notifications/${notificationId}/read`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                }
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // 見た目を既読に変更
                        this.classList.remove('bg-light');

                        // 未読バッジを削除
                        const badge = this.querySelector('.badge');
                        if (badge) {
                            badge.remove();
                        }

                        // 未読件数を更新
                        updateUnreadCount();
                    }
                });
        });
    });

    // すべて既読ボタンのクリックイベント
    const markAllReadBtn = document.getElementById('markAllReadBtn');
    if (markAllReadBtn) {
        markAllReadBtn.addEventListener('click', function () {
            // API呼び出し
            fetch(`${BASE_PATH}/api/notifications/read-all`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                }
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // すべての通知を既読表示に
                        notificationItems.forEach(item => {
                            item.classList.remove('bg-light');
                            const badge = item.querySelector('.badge');
                            if (badge) {
                                badge.remove();
                            }
                        });

                        // 未読件数を更新
                        updateUnreadCount();

                        // 成功メッセージを表示
                        toastr.success('すべての通知を既読にしました');
                    }
                });
        });
    }

    // 通知設定フォームの送信イベント
    const userNotificationSettingsForm = document.getElementById('userNotificationSettingsForm');
    if (userNotificationSettingsForm) {
        userNotificationSettingsForm.addEventListener('submit', function (e) {
            e.preventDefault();

            // フォームデータの収集
            const formData = {
                notify_schedule: document.getElementById('notify_schedule').checked,
                notify_workflow: document.getElementById('notify_workflow').checked,
                notify_message: document.getElementById('notify_message').checked,
                email_notify: document.getElementById('email_notify').checked
            };

            // API呼び出し
            fetch(`${BASE_PATH}/api/notifications/settings`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            })
                .then(response => response.json())
                .then(data => {
                    const successAlert = document.getElementById('settingsSuccessAlert');
                    const errorAlert = document.getElementById('settingsErrorAlert');

                    if (data.success) {
                        successAlert.classList.remove('d-none');
                        errorAlert.classList.add('d-none');

                        // 3秒後に成功メッセージを非表示
                        setTimeout(() => {
                            successAlert.classList.add('d-none');
                        }, 3000);
                    } else {
                        errorAlert.textContent = data.error || '設定の保存に失敗しました。';
                        errorAlert.classList.remove('d-none');
                        successAlert.classList.add('d-none');
                    }
                })
                .catch(error => {
                    const errorAlert = document.getElementById('settingsErrorAlert');
                    errorAlert.textContent = '通信エラーが発生しました。';
                    errorAlert.classList.remove('d-none');
                    document.getElementById('settingsSuccessAlert').classList.add('d-none');
                });
        });
    }

    // 未読通知数の更新関数
    function updateUnreadCount() {
        fetch(`${BASE_PATH}/api/notifications/unread`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // ヘッダーの通知バッジを更新
                    const headerBadge = document.querySelector('#notificationDropdown .badge');
                    if (headerBadge) {
                        if (data.data.unread_count > 0) {
                            headerBadge.textContent = data.data.unread_count;
                            headerBadge.style.display = '';
                        } else {
                            headerBadge.style.display = 'none';
                        }
                    }
                }
            });
    }

    // 新規通知のポーリング (5分ごと)
    function startNotificationPolling() {
        setInterval(() => {
            fetch(`${BASE_PATH}/api/notifications/unread`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // ヘッダーの通知バッジを更新
                        const headerBadge = document.querySelector('#notificationDropdown .badge');
                        if (headerBadge) {
                            if (data.data.unread_count > 0) {
                                headerBadge.textContent = data.data.unread_count;
                                headerBadge.style.display = '';
                            } else {
                                headerBadge.style.display = 'none';
                            }
                        }

                        // 新規通知があればトースト通知
                        const currentCount = parseInt(headerBadge ? headerBadge.textContent || '0' : '0');
                        if (data.data.unread_count > currentCount && data.data.notifications.length > 0) {
                            const latestNotification = data.data.notifications[0];
                            toastr.info(
                                latestNotification.content,
                                latestNotification.title,
                                {
                                    timeOut: 5000,
                                    onclick: function () {
                                        if (latestNotification.link) {
                                            window.location.href = BASE_PATH + latestNotification.link;
                                        } else {
                                            window.location.href = BASE_PATH + '/notifications';
                                        }
                                    }
                                }
                            );
                        }
                    }
                });
        }, 300000); // 5分 = 300,000ミリ秒
    }

    // ポーリングを開始
    startNotificationPolling();
});
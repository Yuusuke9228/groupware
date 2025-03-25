/**
 * GroupWare - メッセージ機能用JavaScript
 */

const Message = {
    // 初期化
    init: function () {
        // イベントリスナーの登録
        this.setupEventListeners();

        // Select2の初期化
        this.initSelect2();

        // 未読メッセージ数の定期確認
        this.startUnreadCountCheck();
    },

    // イベントリスナーの登録
    setupEventListeners: function () {
        // スターを付ける/外す
        $(document).on('click', '.btn-toggle-star', function (e) {
            e.preventDefault();

            const messageId = $(this).data('message-id');
            const isStarred = $(this).data('starred') == 1;
            const newStarred = !isStarred;
            const button = $(this);

            $.ajax({
                url: BASE_PATH + '/api/messages/' + messageId + '/star',
                type: 'POST',
                data: JSON.stringify({ starred: newStarred }),
                contentType: 'application/json',
                success: function (response) {
                    if (response.success) {
                        toastr.success(response.message || (newStarred ? 'スターを付けました' : 'スターを外しました'));

                        // ボタン表示を更新
                        button.data('starred', newStarred ? 1 : 0);

                        if (button.find('i').length > 0) {
                            if (newStarred) {
                                button.find('i').removeClass('far').addClass('fas text-warning');
                            } else {
                                button.find('i').removeClass('fas text-warning').addClass('far');
                            }
                        }
                    } else {
                        toastr.error(response.error || 'エラーが発生しました');
                    }
                },
                error: function (xhr, status, error) {
                    toastr.error('エラーが発生しました');
                    console.error(error);
                },
            });
            return false; // 確実にフォーム送信を防ぐ
        });

        // 既読にする
        $(document).on('click', '.btn-mark-as-read', function (e) {
            e.preventDefault();

            const messageId = $(this).data('message-id');
            const row = $(this).closest('tr');

            $.ajax({
                url: BASE_PATH + '/api/messages/' + messageId + '/read',
                type: 'POST',
                success: function (response) {
                    if (response.success) {
                        toastr.success(response.message || 'メッセージを既読にしました');

                        // 行の表示を更新（太字解除）
                        row.removeClass('fw-bold');

                        // ドロップダウンメニューの項目を変更
                        const dropdownItem = row.find('.btn-mark-as-read');
                        dropdownItem.removeClass('btn-mark-as-read').addClass('btn-mark-as-unread');
                        dropdownItem.html('<i class="fas fa-circle me-2"></i>未読にする');

                        // 未読カウントを更新
                        Message.updateUnreadCount();
                    } else {
                        toastr.error(response.error || 'エラーが発生しました');
                    }
                },
                error: function (xhr, status, error) {
                    toastr.error('エラーが発生しました');
                    console.error(error);
                }
            });
            
        });

        // 未読にする
        $(document).on('click', '.btn-mark-as-unread', function (e) {
            e.preventDefault();

            const messageId = $(this).data('message-id');
            const row = $(this).closest('tr');

            $.ajax({
                url: BASE_PATH + '/api/messages/' + messageId + '/unread',
                type: 'POST',
                success: function (response) {
                    if (response.success) {
                        toastr.success(response.message || 'メッセージを未読にしました');

                        // 行の表示を更新（太字にする）
                        row.addClass('fw-bold');

                        // ドロップダウンメニューの項目を変更
                        const dropdownItem = row.find('.btn-mark-as-unread');
                        dropdownItem.removeClass('btn-mark-as-unread').addClass('btn-mark-as-read');
                        dropdownItem.html('<i class="fas fa-check-circle me-2"></i>既読にする');

                        // 未読カウントを更新
                        Message.updateUnreadCount();
                    } else {
                        toastr.error(response.error || 'エラーが発生しました');
                    }
                },
                error: function (xhr, status, error) {
                    toastr.error('エラーが発生しました');
                    console.error(error);
                }
            });
            return false; // 確実にフォーム送信を防ぐ
        });

        // メッセージ削除
        $(document).on('click', '.btn-delete-message', function (e) {
            e.preventDefault();

            if (!confirm('このメッセージを削除してもよろしいですか？')) {
                return;
            }

            const messageId = $(this).data('message-id');
            const row = $(this).closest('tr');

            $.ajax({
                url: BASE_PATH + '/api/messages/' + messageId,
                type: 'DELETE',
                success: function (response) {
                    if (response.success) {
                        toastr.success(response.message || 'メッセージを削除しました');

                        // 行を非表示にする
                        row.fadeOut(300, function () {
                            // 行数カウントを更新
                            const tbody = row.closest('tbody');
                            if (tbody.find('tr:visible').length === 0) {
                                tbody.html('<tr><td colspan="6" class="text-center py-4">メッセージがありません</td></tr>');
                            }
                        });

                        // 未読カウントを更新
                        Message.updateUnreadCount();
                    } else {
                        toastr.error(response.error || 'エラーが発生しました');
                    }
                },
                error: function (xhr, status, error) {
                    toastr.error('エラーが発生しました');
                    console.error(error);
                }
            });
        });
    },

    // Select2の初期化
    initSelect2: function () {
        // 宛先（ユーザー）選択
        if ($('.select2-users').length) {
            $('.select2-users').select2({
                theme: 'bootstrap-5',
                placeholder: 'ユーザーを選択...',
                allowClear: true
            });
        }

        // 宛先（組織）選択
        if ($('.select2-organizations').length) {
            $('.select2-organizations').select2({
                theme: 'bootstrap-5',
                placeholder: '組織を選択...',
                allowClear: true
            });
        }
    },

    // 未読メッセージ数の更新
    updateUnreadCount: function () {
        $.ajax({
            url: BASE_PATH + '/api/messages/unread-count',
            type: 'GET',
            success: function (response) {
                if (response.success) {
                    const unreadCount = response.data.unread_count;

                    // サイドバーの未読バッジを更新
                    const sidebarBadge = $('.list-group-item:contains("受信トレイ") .badge');
                    if (unreadCount > 0) {
                        if (sidebarBadge.length) {
                            sidebarBadge.text(unreadCount);
                        } else {
                            $('.list-group-item:contains("受信トレイ")').append('<span class="badge bg-danger float-end">' + unreadCount + '</span>');
                        }
                    } else {
                        sidebarBadge.remove();
                    }

                    // ページタイトルの未読バッジを更新
                    const titleBadge = $('.card-header h5:contains("受信トレイ") .badge');
                    if (unreadCount > 0) {
                        if (titleBadge.length) {
                            titleBadge.text(unreadCount);
                        } else {
                            $('.card-header h5:contains("受信トレイ")').append('<span class="badge bg-danger">' + unreadCount + '</span>');
                        }
                    } else {
                        titleBadge.remove();
                    }
                }
            }
        });
    },

    // 未読メッセージ数の定期確認
    startUnreadCountCheck: function () {
        // 5分ごとに未読メッセージ数を確認
        setInterval(() => {
            Message.updateUnreadCount();
        }, 5 * 60 * 1000);
    }
};

// ページロード時に初期化
$(document).ready(function () {
    Message.init();
});
/**
 * GroupWare - スケジュール管理JS
 */

const Schedule = {
    // カレンダーインスタンス
    calendar: null,

    // 現在の表示モード（day/week/month）
    currentView: 'month',

    // 現在表示中のユーザーID
    currentUserId: null,

    // 初期化
    init: function () {
        console.log("Schedule.init called");
        const page = $('[data-page-type]').data('page-type');

        // 現在のユーザーIDを取得
        this.currentUserId = $('#current-user-id').val() || null;

        // ページタイプに応じた初期化
        switch (page) {
            case 'day':
                this.currentView = 'day';
                this.initDay();
                break;
            case 'week':
                this.currentView = 'week';
                this.initWeek();
                break;
            case 'month':
                this.currentView = 'month';
                this.initMonth();
                break;
            case 'create':
                this.initForm();
                break;
            case 'edit':
                this.initForm();
                break;
            case 'view':
                this.initView();
                break;
            case 'calendar':
                this.initCalendar();
                break;
        }

        // 共通のイベントハンドラ
        this.initCommonHandlers();
    },

    // 共通のイベントハンドラを設定
    initCommonHandlers: function () {
        window.addEventListener('popstate', function (event) {
            if (event.state) {
                if (event.state.page === 'month' && event.state.year && event.state.month) {
                    $('#current-year').val(event.state.year);
                    $('#current-month').val(event.state.month);
                    Schedule.loadMonthSchedules(event.state.year, event.state.month, $('#user-id').val());
                } else if (event.state.page === 'week' && event.state.date) {
                    $('#current-date').val(event.state.date);
                    Schedule.loadWeekSchedules(event.state.date, $('#user-id').val());
                } else if (event.state.page === 'day' && event.state.date) {
                    $('#current-date').val(event.state.date);
                    Schedule.loadDaySchedules(event.state.date, $('#user-id').val());
                }
            }
        });
        // 新規作成ボタンのイベントハンドラ
        $('#btn-create-schedule').on('click', function () {
            const date = $(this).data('date') || moment().format('YYYY-MM-DD');
            if ($('#schedule-modal').length) {
                // モーダルがある場合はモーダルで表示
                Schedule.showCreateModal(date, '09:00', false);
            } else {
                // モーダルがない場合は従来の遷移
                window.location.href = BASE_PATH + '/schedule/create?date=' + date;
            }
        });

        // 表示切替ボタン
        $('.btn-view-switcher').on('click', function () {
            const view = $(this).data('view');
            const date = $('#current-date').val() || moment().format('YYYY-MM-DD');

            // ビューに応じたURLに遷移
            switch (view) {
                case 'day':
                    window.location.href = BASE_PATH + '/schedule/day?date=' + date;
                    break;
                case 'week':
                    window.location.href = BASE_PATH + '/schedule/week?date=' + date;
                    break;
                case 'month':
                    const monthDate = moment(date, 'YYYY-MM-DD');
                    window.location.href = BASE_PATH + '/schedule/month?year=' + monthDate.year() + '&month=' + (monthDate.month() + 1);
                    break;
            }
        });

        // ユーザー切替セレクトボックス
        $('#user-selector').on('change', function () {
            const userId = $(this).val();
            const currentPath = window.location.pathname;
            const urlParams = new URLSearchParams(window.location.search);

            urlParams.set('user_id', userId);

            window.location.href = currentPath + '?' + urlParams.toString();
        });
    },

    // 日表示の初期化
    initDay: function () {
        const initialDate = $('#current-date').val();
        const userId = $('#user-id').val();

        // スケジュールデータを取得
        this.loadDaySchedules(initialDate, userId);

        // 前日・翌日ボタン
        $('.btn-prev-day').off('click').on('click', function () {
            // 現在の値を取得（クリックの都度最新の値を使用）
            const currentDate = $('#current-date').val();
            const userId = $('#user-id').val();

            const prevDate = moment(currentDate, 'YYYY-MM-DD').subtract(1, 'days').format('YYYY-MM-DD');
            // const prevDate = moment(date, 'YYYY-MM-DD').subtract(1, 'days').format('YYYY-MM-DD');

            // URLパラメータ更新
            const newUrl = BASE_PATH + '/schedule/day?date=' + prevDate + '&user_id=' + userId;

            // ページをリロードせずに内容を更新
            $('#current-date').val(prevDate);
            Schedule.loadDaySchedules(prevDate, userId);

            // URLを変更（履歴に追加）
            window.history.pushState({}, '', newUrl);

            // タイトル更新
            const formattedDate = moment(prevDate).format('YYYY年M月D日');
            const dayOfWeek = ['日', '月', '火', '水', '木', '金', '土'][moment(prevDate).day()];
            $('h1.h3').text(`${formattedDate} (${dayOfWeek}) - スケジュール管理`);
        });

        $('.btn-next-day').off('click').on('click', function () {
            // 現在の値を取得（クリックの都度最新の値を使用）
            const currentDate = $('#current-date').val();
            const userId = $('#user-id').val();

            const nextDate = moment(currentDate, 'YYYY-MM-DD').add(1, 'days').format('YYYY-MM-DD');
            // const nextDate = moment(date, 'YYYY-MM-DD').add(1, 'days').format('YYYY-MM-DD');

            // URLパラメータ更新
            const newUrl = BASE_PATH + '/schedule/day?date=' + nextDate + '&user_id=' + userId;

            // ページをリロードせずに内容を更新
            $('#current-date').val(nextDate);
            Schedule.loadDaySchedules(nextDate, userId);

            // URLを変更（履歴に追加）
            window.history.pushState({}, '', newUrl);

            // タイトル更新
            const formattedDate = moment(nextDate).format('YYYY年M月D日');
            const dayOfWeek = ['日', '月', '火', '水', '木', '金', '土'][moment(nextDate).day()];
            $('h1.h3').text(`${formattedDate} (${dayOfWeek}) - スケジュール管理`);
        });

        // 今日ボタン
        $('.btn-today').on('click', function () {
            const today = moment().format('YYYY-MM-DD');

            // URLパラメータ更新
            const newUrl = BASE_PATH + '/schedule/day?date=' + today + '&user_id=' + userId;

            // ページをリロードせずに内容を更新
            $('#current-date').val(today);
            Schedule.loadDaySchedules(today, userId);

            // URLを変更（履歴に追加）
            window.history.pushState({}, '', newUrl);

            // タイトル更新
            const formattedDate = moment(today).format('YYYY年M月D日');
            const dayOfWeek = ['日', '月', '火', '水', '木', '金', '土'][moment(today).day()];
            $('h1.h3').text(`${formattedDate} (${dayOfWeek}) - スケジュール管理`);
        });

        // モーダル初期化
        this.initScheduleModal();
    },

    // 週表示の初期化
    initWeek: function () {
        const initialDate = $('#current-date').val();
        const userId = $('#user-id').val();

        // スケジュールデータを取得
        this.loadWeekSchedules(initialDate, userId);

        // 前週・翌週ボタン
        $('.btn-prev-week').on('click', function () {
            // 現在の値を取得（クリックの都度最新の値を使用）
            const currentDate = $('#current-date').val();
            const userId = $('#user-id').val();

            const prevWeek = moment(currentDate, 'YYYY-MM-DD').subtract(7, 'days').format('YYYY-MM-DD');

            // const prevWeek = moment(date, 'YYYY-MM-DD').subtract(7, 'days').format('YYYY-MM-DD');

            // URLパラメータ更新
            const newUrl = BASE_PATH + '/schedule/week?date=' + prevWeek + '&user_id=' + userId;

            // ページをリロードせずに内容を更新
            $('#current-date').val(prevWeek);
            Schedule.loadWeekSchedules(prevWeek, userId);

            // URLを変更（履歴に追加）
            window.history.pushState({}, '', newUrl);

            // タイトル更新 - 週の開始日と終了日を計算
            const weekStart = moment(prevWeek).startOf('week').add(1, 'days'); // 月曜開始
            const weekEnd = moment(weekStart).add(6, 'days');
            const formattedWeek = weekStart.format('YYYY年M月D日') + '～' + weekEnd.format('M月D日');
            $('h1.h3').text(`${formattedWeek} - スケジュール管理`);
        });

        $('.btn-next-week').off('click').on('click', function () {
            // 現在の値を取得（クリックの都度最新の値を使用）
            const currentDate = $('#current-date').val();
            const userId = $('#user-id').val();

            const nextWeek = moment(currentDate, 'YYYY-MM-DD').add(7, 'days').format('YYYY-MM-DD');
            // const nextWeek = moment(date, 'YYYY-MM-DD').add(7, 'days').format('YYYY-MM-DD');

            // URLパラメータ更新
            const newUrl = BASE_PATH + '/schedule/week?date=' + nextWeek + '&user_id=' + userId;

            // ページをリロードせずに内容を更新
            $('#current-date').val(nextWeek);
            Schedule.loadWeekSchedules(nextWeek, userId);

            // URLを変更（履歴に追加）
            window.history.pushState({}, '', newUrl);

            // タイトル更新 - 週の開始日と終了日を計算
            const weekStart = moment(nextWeek).startOf('week').add(1, 'days'); // 月曜開始
            const weekEnd = moment(weekStart).add(6, 'days');
            const formattedWeek = weekStart.format('YYYY年M月D日') + '～' + weekEnd.format('M月D日');
            $('h1.h3').text(`${formattedWeek} - スケジュール管理`);
        });

        // 今週ボタン
        $('.btn-this-week').on('click', function () {
            const today = moment().format('YYYY-MM-DD');

            // URLパラメータ更新
            const newUrl = BASE_PATH + '/schedule/week?date=' + today + '&user_id=' + userId;

            // ページをリロードせずに内容を更新
            $('#current-date').val(today);
            Schedule.loadWeekSchedules(today, userId);

            // URLを変更（履歴に追加）
            window.history.pushState({}, '', newUrl);

            // タイトル更新 - 週の開始日と終了日を計算
            const weekStart = moment(today).startOf('week').add(1, 'days'); // 月曜開始
            const weekEnd = moment(weekStart).add(6, 'days');
            const formattedWeek = weekStart.format('YYYY年M月D日') + '～' + weekEnd.format('M月D日');
            $('h1.h3').text(`${formattedWeek} - スケジュール管理`);
        });

        // モーダル初期化
        this.initScheduleModal();
    },

    // 月表示の初期化
    initMonth: function () {
        // const year = $('#current-year').val();
        // const month = $('#current-month').val();
        // const userId = $('#user-id').val();
        const initialYear = $('#current-year').val();
        const initialMonth = $('#current-month').val();
        const userId = $('#user-id').val();

        // スケジュールデータを取得
        this.loadMonthSchedules(initialYear, initialMonth, userId);

        // 前月・翌月ボタン
        $('.btn-prev-month').on('click', function () {
            // 現在の値を取得（クリックの都度最新の値を使用）
            const currentYear = $('#current-year').val();
            const currentMonth = $('#current-month').val();
            const userId = $('#user-id').val();

            // const prevMonth = moment(`${year}-${month}-01`, 'YYYY-MM-DD').subtract(1, 'months');
            const prevMonth = moment(`${currentYear}-${currentMonth}-01`, 'YYYY-MM-DD').subtract(1, 'months');
            const newYear = prevMonth.year();
            const newMonth = prevMonth.month() + 1;

            // URLパラメータ更新
            const newUrl = BASE_PATH + `/schedule/month?year=${newYear}&month=${newMonth}&user_id=${userId}`;

            // ページをリロードせずに内容を更新
            $('#current-year').val(newYear);
            $('#current-month').val(newMonth);
            Schedule.loadMonthSchedules(newYear, newMonth, userId);

            // URLを変更（履歴に追加）
            window.history.pushState({}, '', newUrl);

            // タイトル更新
            const monthNames = ['', '1月', '2月', '3月', '4月', '5月', '6月', '7月', '8月', '9月', '10月', '11月', '12月'];
            $('h1.h3').text(`${newYear}年${monthNames[newMonth]} - スケジュール管理`);
        });

        $('.btn-next-month').on('click', function () {
            // 現在の値を取得（クリックの都度最新の値を使用）
            const currentYear = $('#current-year').val();
            const currentMonth = $('#current-month').val();
            const userId = $('#user-id').val();

            // const nextMonth = moment(`${year}-${month}-01`, 'YYYY-MM-DD').add(1, 'months');
            const nextMonth = moment(`${currentYear}-${currentMonth}-01`, 'YYYY-MM-DD').add(1, 'months');
            const newYear = nextMonth.year();
            const newMonth = nextMonth.month() + 1;

            // URLパラメータ更新
            const newUrl = BASE_PATH + `/schedule/month?year=${newYear}&month=${newMonth}&user_id=${userId}`;

            // ページをリロードせずに内容を更新
            $('#current-year').val(newYear);
            $('#current-month').val(newMonth);
            Schedule.loadMonthSchedules(newYear, newMonth, userId);

            // URLを変更（履歴に追加）
            window.history.pushState({}, '', newUrl);

            // タイトル更新
            const monthNames = ['', '1月', '2月', '3月', '4月', '5月', '6月', '7月', '8月', '9月', '10月', '11月', '12月'];
            $('h1.h3').text(`${newYear}年${monthNames[newMonth]} - スケジュール管理`);
        });

        // 今月ボタン
        $('.btn-this-month').on('click', function () {
            const today = moment();
            const newYear = today.year();
            const newMonth = today.month() + 1;

            // URLパラメータ更新
            const newUrl = BASE_PATH + `/schedule/month?year=${newYear}&month=${newMonth}&user_id=${userId}`;

            // ページをリロードせずに内容を更新
            $('#current-year').val(newYear);
            $('#current-month').val(newMonth);
            Schedule.loadMonthSchedules(newYear, newMonth, userId);

            // URLを変更（履歴に追加）
            window.history.pushState({}, '', newUrl);

            // タイトル更新
            const monthNames = ['', '1月', '2月', '3月', '4月', '5月', '6月', '7月', '8月', '9月', '10月', '11月', '12月'];
            $('h1.h3').text(`${newYear}年${monthNames[newMonth]} - スケジュール管理`);
        });

        // 日付セルのクリックイベント
        $(document).on('click', '.calendar-day', function (e) {
            // スケジュールアイテムのクリックは除外
            if ($(e.target).closest('.schedule-item, .more-schedules').length === 0) {
                const date = $(this).data('date');
                if (date) {
                    if ($('#schedule-modal').length) {
                        // モーダルがある場合はモーダルで表示
                        Schedule.showCreateModal(date, '09:00', true);
                    } else {
                        // モーダルがない場合は従来の遷移
                        window.location.href = BASE_PATH + '/schedule/day?date=' + date + '&user_id=' + userId;
                    }
                }
            }
        });

        // モーダル初期化
        this.initScheduleModal();
    },

    // モーダル表示用の関数を追加
    initScheduleModal: function () {
        console.log("Initializing schedule modal");

        // 日付クリックイベント
        $(document).on('click', '.day-cell, .week-day-content, .schedule-hour', function (e) {
            // スケジュールアイテムのクリックは除外
            if ($(e.target).closest('.schedule-item').length === 0) {
                const date = $(this).data('date');
                const hour = $(this).data('hour');

                // 日付と時間からデフォルト値を設定
                let defaultDate = date || moment().format('YYYY-MM-DD');
                let defaultTime = hour ? hour.toString().padStart(2, '0') + ':00' : '00:00';
                let allDay = hour === undefined;

                // モーダルを表示
                Schedule.showCreateModal(defaultDate, defaultTime, allDay);
            }
        });

        // スケジュールアイテムクリック
        $(document).on('click', '.schedule-item', function (e) {
            e.preventDefault();
            e.stopPropagation();

            const scheduleId = $(this).data('id');
            if (scheduleId) {
                Schedule.showViewModal(scheduleId);
            } else {
                console.error("Schedule item clicked without ID");
            }
        });

        // モーダルの各種ボタン処理
        $('#schedule-modal').on('hidden.bs.modal', function () {
            $(this).find('form').get(0)?.reset();
            $(this).find('.is-invalid').removeClass('is-invalid');
            $(this).find('.invalid-feedback').text('');
        });

        // フォーム送信イベントハンドラ
        $('#schedule-form').off('submit').on('submit', function (e) {
            e.preventDefault();

            // 日付と時間を結合
            const startDate = $('#start_time_date').val();
            const startTime = $('#start_time_time').val();
            $('#start_time').val(startDate + ' ' + startTime);

            const endDate = $('#end_time_date').val();
            const endTime = $('#end_time_time').val();
            $('#end_time').val(endDate + ' ' + endTime);

            // フォームデータ取得
            const formData = $(this).serialize();
            const url = $(this).attr('action');

            console.log("Submitting form to:", url, "with data:", formData);

            // API送信
            $.ajax({
                url: url,
                type: 'POST',
                data: formData,
                beforeSend: function () {
                    $('#schedule-form button[type="submit"]').prop('disabled', true);
                },
                success: function (response) {
                    console.log("Form submission response:", response);
                    if (response.success) {
                        App.showNotification(response.message || 'スケジュールを保存しました', 'success');
                        $('#schedule-modal').modal('hide');

                        // カレンダー再読み込み
                        Schedule.reloadCurrentView();
                    } else {
                        App.showNotification(response.error || 'エラーが発生しました', 'error');

                        // バリデーションエラー表示
                        if (response.validation) {
                            for (const field in response.validation) {
                                const errorMsg = response.validation[field];
                                const input = $('#' + field);
                                input.addClass('is-invalid');
                                input.next('.invalid-feedback').text(errorMsg);
                            }
                        }
                    }
                },
                error: function (xhr, status, error) {
                    console.error("Form submission error:", xhr, status, error);
                    App.showNotification('エラーが発生しました', 'error');
                },
                complete: function () {
                    $('#schedule-form button[type="submit"]').prop('disabled', false);
                }
            });
            return false; // 確実にフォーム送信を防ぐ
        });

        // 削除ボタンのイベントハンドラ
        $('.delete-btn').on('click', function () {
            const scheduleId = $(this).data('id');
            if (!scheduleId) {
                console.error("Delete button clicked without schedule ID");
                return;
            }

            if (confirm('このスケジュールを削除しますか？')) {
                console.log("Deleting schedule:", scheduleId);
                App.apiDelete('/schedule/' + scheduleId)
                    .then(response => {
                        console.log("Delete response:", response);
                        if (response.success) {
                            App.showNotification(response.message || 'スケジュールを削除しました', 'success');
                            $('#schedule-modal').modal('hide');
                            Schedule.reloadCurrentView();
                        } else {
                            App.showNotification(response.error || 'エラーが発生しました', 'error');
                        }
                    })
                    .catch(error => {
                        console.error("Delete error:", error);
                        App.showNotification('エラーが発生しました', 'error');
                    });
            }
        });

        // 終日フラグのイベントハンドラ
        $(document).on('change', '#all_day', function () {
            if ($(this).is(':checked')) {
                $('.time-picker').hide();
                $('#start_time_time').val('00:00');
                $('#end_time_time').val('23:59');
            } else {
                $('.time-picker').show();
            }
        });

        // 公開範囲のイベントハンドラ
        $(document).on('change', '#visibility', function () {
            if ($(this).val() === 'specific') {
                $('.organization-select-container').show();
            } else {
                $('.organization-select-container').hide();
            }

            // 参加者選択は常に表示するため、ここでの処理は不要
        });

        // 繰り返しタイプのイベントハンドラ
        $(document).on('change', '#repeat_type', function () {
            if ($(this).val() !== 'none') {
                $('.repeat-options').show();
            } else {
                $('.repeat-options').hide();
            }
        });
    },

    // 新規作成モーダル表示
    showCreateModal: function (defaultDate, defaultTime, allDay) {
        console.log("Showing create modal with date:", defaultDate, "time:", defaultTime);

        // モーダルタイトル設定
        $('#schedule-modal-title').text('新規スケジュール作成');

        // フォームリセット
        $('#schedule-form').attr('action', BASE_PATH + '/api/schedule');
        $('#schedule-form').get(0).reset();
        $('#schedule-id').val('');

        // デフォルト値設定
        $('#start_time_date').val(defaultDate);
        $('#start_time_time').val(defaultTime);

        // 終了時間は開始から1時間後
        let endTime = moment(defaultTime, 'HH:mm').add(1, 'hour').format('HH:mm');
        $('#end_time_date').val(defaultDate);
        $('#end_time_time').val(endTime);

        // 終日フラグ
        $('#all_day').prop('checked', allDay);
        if (allDay) {
            $('#start_time_time').val('00:00');
            $('#end_time_time').val('23:59');
            $('.time-picker').hide();
        } else {
            $('.time-picker').show();
        }

        // 表示/非表示設定
        $('.delete-btn').hide();
        $('.edit-mode').show();
        $('.view-mode').hide();

        // 参加者と共有組織のSelect2をリセット
        if ($('#participants').data('select2')) {
            $('#participants').val(null).trigger('change');
        }

        if ($('#organizations').data('select2')) {
            $('#organizations').val(null).trigger('change');
        }

        // 公開範囲による表示/非表示
        if ($('#visibility').val() === 'specific') {
            $('.organization-select-container').show();
        } else {
            $('.organization-select-container').hide();
        }

        // モーダル表示
        $('#schedule-modal').modal('show');

        // モーダル表示後に初期化を実行
        setTimeout(() => {
            // 日付ピッカーと時間ピッカーの初期化
            this.initDateTimePickers();

            // 参加者選択の初期化
            this.initParticipantSelect();
        }, 500);
    },

    // 詳細表示モーダル
    showViewModal: function (scheduleId) {
        console.log("Showing view modal for schedule:", scheduleId);

        // スケジュールデータを取得
        App.apiGet('/schedule/' + scheduleId)
            .then(response => {
                console.log("Schedule data:", response);
                if (response.success) {
                    const schedule = response.data;

                    // モーダルタイトル設定
                    $('#schedule-modal-title').text('スケジュール詳細');

                    // 詳細表示モードに
                    $('.edit-mode').hide();
                    $('.view-mode').show();

                    // データ表示
                    $('#view-title').text(schedule.title);

                    // 日時表示処理
                    const startDateTime = moment(schedule.start_time);
                    const endDateTime = moment(schedule.end_time);
                    const isSameDay = startDateTime.isSame(endDateTime, 'day');
                    const isAllDay = schedule.all_day == 1;
                    console.log('isAllDay: ' + isAllDay);

                    let dateTimeText = '';
                    if (!isAllDay) {
                        if (isSameDay) {
                            dateTimeText = startDateTime.format('YYYY年M月D日') + ' (終日)';
                        } else {
                            dateTimeText = startDateTime.format('YYYY年M月D日') + ' ～ ' +
                                endDateTime.format('YYYY年M月D日') + ' (終日)';
                        }
                    } else {
                        if (isSameDay) {
                            dateTimeText = startDateTime.format('YYYY年M月D日 HH:mm') + ' ～ ' +
                                endDateTime.format('HH:mm');
                        } else {
                            dateTimeText = startDateTime.format('YYYY年M月D日 HH:mm') + ' ～ ' +
                                endDateTime.format('YYYY年M月D日 HH:mm');
                        }
                    }
                    $('#view-datetime').text(dateTimeText);

                    // その他の情報表示
                    $('#view-location').text(schedule.location || '');
                    $('#view-description').text(schedule.description || '');
                    $('#view-creator').text(schedule.creator_name || '');

                    // 参加者リスト
                    if (schedule.participants && schedule.participants.length > 0) {
                        let participantsList = '';
                        schedule.participants.forEach(p => {
                            const statusBadge = this.getParticipationStatusBadge(p.participation_status);
                            participantsList += `<li class="list-group-item d-flex justify-content-between align-items-center">
                                ${p.display_name} ${statusBadge}
                            </li>`;
                        });
                        $('#view-participants').html(participantsList);
                        $('.participants-section').show();
                    } else {
                        $('.participants-section').hide();
                    }

                    // 編集・削除ボタン表示制御
                    const currentUserId = $('#current-user-id').val();
                    console.log('currentUserId:' + currentUserId);
                    console.log('schedule.creator_id:' + schedule.creator_id);
                    if (schedule.creator_id == currentUserId || $('.is-admin').length > 0) {
                        $('.delete-btn').show();
                        $('#edit-schedule-btn').show();
                        // 編集ボタンのクリックイベント
                        $('#edit-schedule-btn').off('click').on('click', function () {
                            Schedule.showEditModal(schedule);
                        });
                        // 削除ボタンの設定
                        $('.delete-btn').data('id', schedule.id);
                    } else {
                        $('.delete-btn').hide();
                        $('#edit-schedule-btn').hide();
                    }

                    // モーダル表示
                    $('#schedule-modal').modal('show');
                } else {
                    App.showNotification('スケジュールの読み込みに失敗しました', 'error');
                }
            })
            .catch(error => {
                console.error("Error loading schedule:", error);
                App.showNotification('スケジュールの読み込みに失敗しました', 'error');
            });
    },

    // 編集モーダル表示
    showEditModal: function (schedule) {
        console.log("Showing edit modal for schedule:", schedule);

        // モーダルタイトル設定
        $('#schedule-modal-title').text('スケジュール編集');

        // フォーム設定
        $('#schedule-form').attr('action', BASE_PATH + '/api/schedule/' + schedule.id);
        $('#schedule-id').val(schedule.id);

        // フォームに値を設定
        $('#title').val(schedule.title);
        $('#description').val(schedule.description || '');
        $('#location').val(schedule.location || '');
        $('#priority').val(schedule.priority || 'normal');
        $('#visibility').val(schedule.visibility || 'public');
        $('#status').val(schedule.status || 'scheduled');
        $('#repeat_type').val(schedule.repeat_type || 'none');

        // 日時設定
        const startDateTime = moment(schedule.start_time);
        const endDateTime = moment(schedule.end_time);

        $('#start_time_date').val(startDateTime.format('YYYY-MM-DD'));
        $('#start_time_time').val(startDateTime.format('HH:mm'));
        $('#end_time_date').val(endDateTime.format('YYYY-MM-DD'));
        $('#end_time_time').val(endDateTime.format('HH:mm'));

        // 終日設定
        const isAllDay = schedule.all_day == 1;
        $('#all_day').prop('checked', isAllDay);
        if (!isAllDay) {
            $('.time-picker').hide();
        } else {
            $('.time-picker').show();
        }

        // 表示/非表示設定
        $('.edit-mode').show();
        $('.view-mode').hide();
        $('.delete-btn').show();
        $('.delete-btn').data('id', schedule.id);

        // 繰り返し設定の表示/非表示
        if (schedule.repeat_type && schedule.repeat_type !== 'none') {
            $('.repeat-options').show();
            $('#repeat_end_date').val(schedule.repeat_end_date || '');
        } else {
            $('.repeat-options').hide();
        }

        // 公開範囲に応じた表示/非表示
        if (schedule.visibility === 'specific') {
            $('.organization-select-container').show();
        } else {
            $('.organization-select-container').hide();
        }

        // モーダル表示
        $('#schedule-modal').modal('show');

        // モーダル表示後に初期化を実行
        setTimeout(() => {
            // 日付ピッカーと時間ピッカーの初期化
            this.initDateTimePickers();

            // 参加者選択の初期化
            this.initParticipantSelect();

            // 参加者データをセット
            if (schedule.participants && schedule.participants.length > 0) {
                // Select2が初期化されるのを待つ
                setTimeout(() => {
                    // セレクトボックスをクリア
                    $('#participants').empty();

                    // 参加者のオプションを追加
                    schedule.participants.forEach(participant => {
                        const option = new Option(
                            participant.display_name,
                            participant.id,
                            true,
                            true
                        );
                        $('#participants').append(option);
                    });

                    // Select2の表示を更新
                    $('#participants').trigger('change');
                }, 500);
            }

            // 共有組織データをセット
            if (schedule.organizations && schedule.organizations.length > 0) {
                // Select2が初期化されるのを待つ
                setTimeout(() => {
                    // セレクトボックスをクリア
                    $('#organizations').empty();

                    // 組織のオプションを追加
                    schedule.organizations.forEach(org => {
                        const option = new Option(
                            org.name,
                            org.id,
                            true,
                            true
                        );
                        $('#organizations').append(option);
                    });

                    // Select2の表示を更新
                    $('#organizations').trigger('change');
                }, 500);
            }
        }, 500);
    },

    // 現在の表示を再読み込み
    reloadCurrentView: function () {
        console.log("Reloading current view:", this.currentView);
        switch (this.currentView) {
            case 'day':
                const dayDate = $('#current-date').val();
                const dayUserId = $('#user-id').val();
                this.loadDaySchedules(dayDate, dayUserId);
                break;
            case 'week':
                const weekDate = $('#current-date').val();
                const weekUserId = $('#user-id').val();
                this.loadWeekSchedules(weekDate, weekUserId);
                break;
            case 'month':
                const year = $('#current-year').val();
                const month = $('#current-month').val();
                const monthUserId = $('#user-id').val();
                this.loadMonthSchedules(year, month, monthUserId);
                break;
        }
    },

    // フォームページの初期化
    initForm: function () {
        // 日付時間ピッカーの初期化
        this.initDateTimePickers();

        // 参加者セレクトの初期化
        this.initParticipantSelect();

        // 組織共有セレクトの初期化
        this.initOrganizationSelect();

        // 終日フラグの処理
        $('#all_day').on('change', function () {
            if ($(this).is(':checked')) {
                // 終日の場合は時間部分を非表示にして00:00-23:59に設定
                $('.time-picker').hide();
                $('#start_time_time').val('00:00');
                $('#end_time_time').val('23:59');
            } else {
                // 終日でない場合は時間部分を表示
                $('.time-picker').show();
            }
        });

        // 初期表示（チェックボックスの状態に合わせる）
        if ($('#all_day').is(':checked')) {
            $('.time-picker').hide();
        }

        // 繰り返し設定の表示切替
        $('#repeat_type').on('change', function () {
            if ($(this).val() !== 'none') {
                $('.repeat-options').show();
            } else {
                $('.repeat-options').hide();
                $('#repeat_end_date').val('');
            }
        });

        // 公開範囲の変更処理
        $('#visibility').on('change', function () {
            if ($(this).val() === 'specific') {
                $('.organization-select-container').show();
            } else {
                $('.organization-select-container').hide();
            }
        });

        // 日付の連動（開始日を変更したら終了日も自動更新）
        $('#start_time_date').on('change', function () {
            if ($('#end_time_date').val() < $(this).val()) {
                $('#end_time_date').val($(this).val());
            }
        });

        // 時間の連動（開始時間を変更したら終了時間も自動更新 - 同日の場合）
        $('#start_time_time').on('change', function () {
            if ($('#start_time_date').val() === $('#end_time_date').val() &&
                $('#end_time_time').val() <= $(this).val()) {
                // 開始時間より1時間後を設定
                let startTime = moment($(this).val(), 'HH:mm');
                let endTime = moment(startTime).add(1, 'hour');
                $('#end_time_time').val(endTime.format('HH:mm'));
            }
        });

        // フォーム送信前のバリデーション
        $('form').on('submit', function (e) {
            if (!Schedule.validateForm()) {
                e.preventDefault();
                return false;
            }

            // 日付と時間を結合
            const startDate = $('#start_time_date').val();
            const startTime = $('#start_time_time').val();
            $('#start_time').val(startDate + ' ' + startTime);

            const endDate = $('#end_time_date').val();
            const endTime = $('#end_time_time').val();
            $('#end_time').val(endDate + ' ' + endTime);

            // return true;
        });
    },

    // 日付時間ピッカーの初期化
    initDateTimePickers: function () {
        console.log("Initializing date/time pickers");

        // 既存のピッカーがあれば破棄
        $('.date-picker').each(function () {
            if (this._flatpickr) {
                this._flatpickr.destroy();
            }
        });

        $('.time-picker').each(function () {
            if (this._flatpickr) {
                this._flatpickr.destroy();
            }
        });

        // 日付ピッカーの初期化
        flatpickr("#start_time_date, #end_time_date, #repeat_end_date", {
            dateFormat: 'Y-m-d',
            locale: 'ja',
            disableMobile: true,
            onChange: function (selectedDates, dateStr, instance) {
                // 開始日が変更された場合、終了日も更新（終了日が開始日より前の場合）
                if (instance.element.id === 'start_time_date') {
                    const endDateEl = document.getElementById('end_time_date');
                    if (endDateEl && endDateEl._flatpickr) {
                        const endDate = endDateEl._flatpickr.selectedDates[0];
                        if (endDate < selectedDates[0]) {
                            endDateEl._flatpickr.setDate(dateStr);
                        }
                    }
                }
            }
        });

        // 時間ピッカーの初期化
        flatpickr("#start_time_time, #end_time_time", {
            enableTime: true,
            noCalendar: true,
            dateFormat: 'H:i',
            time_24hr: true,
            minuteIncrement: 15,
            disableMobile: true,
            onChange: function (selectedDates, dateStr, instance) {
                // 開始時間が変更された場合、終了時間も更新
                if (instance.element.id === 'start_time_time') {
                    const startDateEl = document.getElementById('start_time_date');
                    const endDateEl = document.getElementById('end_time_date');
                    const endTimeEl = document.getElementById('end_time_time');

                    if (startDateEl && endDateEl && endTimeEl && endTimeEl._flatpickr) {
                        if (startDateEl.value === endDateEl.value) {
                            const startHour = parseInt(dateStr.split(':')[0], 10);
                            const startMinute = parseInt(dateStr.split(':')[1], 10);

                            const endTime = endTimeEl._flatpickr.selectedDates[0];
                            if (endTime) {
                                const endHour = endTime.getHours();
                                const endMinute = endTime.getMinutes();

                                // 開始時間が終了時間以降の場合、終了時間を1時間後に設定
                                if (startHour > endHour || (startHour === endHour && startMinute >= endMinute)) {
                                    const newEndTime = new Date();
                                    newEndTime.setHours(startHour + 1);
                                    newEndTime.setMinutes(startMinute);
                                    endTimeEl._flatpickr.setDate(newEndTime);
                                }
                            }
                        }
                    }
                }
            }
        });

        console.log("Date/time pickers initialized");
    },

    // 詳細ページの初期化
    initView: function () {
        const scheduleId = $('#schedule-id').val();

        // 参加ステータス変更ボタン
        $('.btn-participation-status').on('click', function () {
            const status = $(this).data('status');
            Schedule.updateParticipationStatus(scheduleId, status);
        });
    },

    // カレンダーページの初期化（FullCalendar使用）
    initCalendar: function () {
        const calendarEl = document.getElementById('calendar');

        if (!calendarEl) return;

        // FullCalendarの初期化
        this.calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            locale: 'ja',
            buttonText: {
                today: '今日',
                month: '月',
                week: '週',
                day: '日'
            },
            navLinks: true,
            editable: false,
            dayMaxEvents: true,
            events: (info, successCallback, failureCallback) => {
                // 指定期間のスケジュールを取得
                const userId = $('#user-id').val();

                App.apiGet('/schedule/range', {
                    start_date: moment(info.start).format('YYYY-MM-DD'),
                    end_date: moment(info.end).format('YYYY-MM-DD'),
                    user_id: userId
                })
                    .then(response => {
                        if (response.success) {
                            // FullCalendar形式にデータを変換
                            const events = response.data.map(schedule => {
                                return {
                                    id: schedule.id,
                                    title: schedule.title,
                                    start: schedule.start_time,
                                    end: schedule.end_time,
                                    allDay: schedule.all_day == 1,
                                    backgroundColor: this.getPriorityColor(schedule.priority),
                                    url: BASE_PATH + '/schedule/view/' + schedule.id
                                };
                            });

                            successCallback(events);
                        } else {
                            failureCallback(response.error);
                        }
                    })
                    .catch(error => {
                        failureCallback(error);
                    });
            },
            dateClick: (info) => {
                // 日付クリック時のイベント
                if ($('#schedule-modal').length) {
                    // モーダルがある場合はモーダルで表示
                    this.showCreateModal(info.dateStr, '09:00', true);
                } else {
                    // モーダルがない場合は従来の遷移
                    window.location.href = BASE_PATH + '/schedule/day?date=' + info.dateStr;
                }
            },
            eventClick: (info) => {
                // スケジュールクリック時のイベント
                info.jsEvent.preventDefault();
                if ($('#schedule-modal').length) {
                    // モーダルがある場合はモーダルで表示
                    this.showViewModal(info.event.id);
                }
            }
        });

        this.calendar.render();

        // ユーザー切替時にカレンダーを再読み込み
        $('#user-selector').on('change', function () {
            Schedule.calendar.refetchEvents();
        });
    },

    // 参加者セレクトの初期化
    initParticipantSelect: function () {
        console.log("Initializing participant select");

        // Select2が利用可能か確認
        if (typeof $.fn.select2 === 'undefined') {
            console.error("Select2 is not loaded! Make sure jQuery and Select2 are properly included.");
            return;
        }

        try {
            // 参加者選択の初期化
            $('#participants').select2({
                placeholder: '参加者を選択してください',
                allowClear: true,
                width: '100%',
                ajax: {
                    url: BASE_PATH + '/api/active-users',
                    dataType: 'json',
                    delay: 250,
                    processResults: function (data) {
                        console.log("User data received:", data);
                        if (data.success && Array.isArray(data.data)) {
                            return {
                                results: data.data.map(user => ({
                                    id: user.id,
                                    text: user.display_name + ' (' + user.username + ')'
                                }))
                            };
                        } else {
                            console.error("Invalid user data format:", data);
                            return { results: [] };
                        }
                    },
                    cache: true
                }
            });

            // 組織選択の初期化
            $('#organizations').select2({
                placeholder: '組織を選択してください',
                allowClear: true,
                width: '100%',
                ajax: {
                    url: BASE_PATH + '/api/organizations',
                    dataType: 'json',
                    delay: 250,
                    processResults: function (data) {
                        console.log("Organization data received:", data);
                        if (data.success && Array.isArray(data.data)) {
                            return {
                                results: data.data.map(org => ({
                                    id: org.id,
                                    text: org.name + ' (' + org.code + ')'
                                }))
                            };
                        } else {
                            console.error("Invalid organization data format:", data);
                            return { results: [] };
                        }
                    },
                    cache: true
                }
            });

            console.log("Select2 components initialized");
        } catch (e) {
            console.error("Error initializing Select2:", e);
        }
    },

    // 組織共有セレクトの初期化
    initOrganizationSelect: function () {
        // 既に initParticipantSelect で初期化されているので何もしない
    },

    // 日単位スケジュールの読み込み
    loadDaySchedules: function (date, userId) {
        console.log("Loading day schedules for date:", date, "user:", userId);
        App.apiGet('/api/schedule/day', { date: date, user_id: userId })
            .then(response => {
                console.log("Day schedules response:", response);
                if (response.success) {
                    const schedules = response.data;
                    this.renderDaySchedules(schedules, date);
                } else {
                    App.showNotification('スケジュールの読み込みに失敗しました', 'error');
                }
            })
            .catch(error => {
                console.error("Error loading day schedules:", error);
                App.showNotification('スケジュールの読み込みに失敗しました', 'error');
            });
    },

    // 日単位スケジュールの表示メソッドを修正
    renderDaySchedules: function (schedules, date) {
        const container = $('#day-schedule-container');
        console.log('日表示のスケジュール:', schedules);

        if (!Array.isArray(schedules) || schedules.length === 0) {
            container.html('<div class="alert alert-info">スケジュールはありません</div>');
            return;
        }

        // 時間帯ごとにスケジュールを整理
        const timeSlots = {};
        const allDaySchedules = [];
        // 時間枠をまたぐスケジュール
        const timeSpans = [];

        // 時間帯の初期化（1時間ごと）
        for (let hour = 0; hour < 24; hour++) {
            const timeStr = hour.toString().padStart(2, '0') + ':00';
            timeSlots[timeStr] = [];
        }

        // スケジュールを時間帯に振り分け
        schedules.forEach(schedule => {
            if (schedule.all_day == 1) {
                // 終日スケジュール
                allDaySchedules.push(schedule);
            } else {
                // 時間指定スケジュール
                const startTime = moment(schedule.start_time);
                const endTime = moment(schedule.end_time);
                const startHour = parseInt(startTime.format('H'));
                const endHour = parseInt(endTime.format('H')) + (endTime.format('mm') > '00' ? 1 : 0);

                // 1時間以上にまたがるスケジュールの場合
                if (startHour < endHour) {
                    // スパンデータを作成
                    const spanData = {
                        schedule: schedule,
                        startTime: startTime,
                        endTime: endTime,
                        startHour: startHour,
                        endHour: endHour,
                        topPosition: this.calculateTimePosition(startTime),
                        height: this.calculateDurationHeight(startTime, endTime)
                    };

                    // スパンリストに追加
                    timeSpans.push(spanData);
                }

                // いずれの場合も開始時間の時間帯にも追加（バックアップ表示用）
                const hourStr = startTime.format('HH').substring(0, 2) + ':00';
                timeSlots[hourStr].push(schedule);
            }
        });

        // HTML生成
        let html = '';

        // 終日スケジュール
        if (allDaySchedules.length > 0) {
            html += '<div class="mb-3">';
            html += '<h5 class="mb-2">終日</h5>';
            html += '<div class="list-group">';

            allDaySchedules.forEach(schedule => {
                html += this.renderScheduleListItem(schedule);
            });

            html += '</div></div>';
        }

        // 時間帯ごとのスケジュール
        html += '<div class="schedule-timeline">';

        for (let hour = 0; hour < 24; hour++) {
            const timeStr = hour.toString().padStart(2, '0') + ':00';
            const scheduleItems = timeSlots[timeStr];

            html += '<div class="schedule-hour" id="hour-' + hour + '">';
            html += '<div class="schedule-time">' + timeStr + '</div>';
            html += '<div class="schedule-items position-relative">';

            // この時間帯をまたぐタイムスパンスケジュールを表示
            const hourSpans = timeSpans.filter(span =>
                span.startHour <= hour && span.endHour > hour
            );

            // 各時間帯で最初に表示される時のみレンダリング
            hourSpans.forEach(span => {
                if (span.startHour === hour) {
                    html += this.renderDayScheduleTimespan(span.schedule, span.height, span.topPosition);
                }
            });

            // 通常の時間枠スケジュール（バックアップ表示）
            // 後で追加されるため、既にタイムスパンとして表示されているスケジュールは除外
            const displayItems = scheduleItems.filter(item =>
                !timeSpans.some(span => span.schedule.id === item.id)
            );

            if (displayItems.length > 0) {
                displayItems.forEach(schedule => {
                    html += this.renderScheduleTimelineItem(schedule);
                });
            }

            // 空スロットはクリック可能エリアとして残しておく
            html += '<div class="empty-slot" data-hour="' + hour + '" data-date="' + date + '"></div>';

            html += '</div></div>';
        }

        html += '</div>';

        container.html(html);

        // 空スロットのクリックイベント
        $('.empty-slot').on('click', function () {
            const hour = $(this).data('hour');
            const clickDate = $(this).data('date');
            const newTime = hour.toString().padStart(2, '0') + ':00';

            if ($('#schedule-modal').length) {
                // モーダルがある場合はモーダルで表示
                Schedule.showCreateModal(clickDate, newTime, false);
            } else {
                // モーダルがない場合は従来の遷移
                window.location.href = BASE_PATH + '/schedule/create?date=' + clickDate + '&time=' + newTime;
            }
        });

        // スケジュールアイテムのクリックイベント
        $('.schedule-item, .schedule-timespan').on('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            const scheduleId = $(this).data('id');
            if (scheduleId) {
                // モーダルがある場合はモーダルで表示
                if ($('#schedule-modal').length) {
                    Schedule.showViewModal(scheduleId);
                } else {
                    window.location.href = BASE_PATH + '/schedule/view/' + scheduleId;
                }
            }
        });
    },

    // 日表示用時間スパンスケジュールのレンダリング
    renderDayScheduleTimespan: function (schedule, height, topPosition) {
        const startTime = moment(schedule.start_time).format('HH:mm');
        const endTime = moment(schedule.end_time).format('HH:mm');
        const timeDisplay = startTime + ' - ' + endTime;
        const priorityClass = this.getPriorityClass(schedule.priority);

        return `
        <div class="schedule-item schedule-timespan ${priorityClass}" 
             style="position: absolute; top: ${topPosition % 60}px; height: ${height}px; left: 0; right: 0; z-index: 10;" 
             data-id="${schedule.id}">
            <div class="schedule-title">${schedule.title}</div>
            <div class="schedule-time">${timeDisplay}</div>
        </div>
    `;
    },

    // 週単位スケジュールの読み込み
    loadWeekSchedules: function (date, userId) {
        console.log("Loading week schedules for date:", date, "user:", userId);
        App.apiGet('/api/schedule/week', { date: date, user_id: userId })
            .then(response => {
                console.log("Week schedules response:", response);
                if (response.success) {
                    const data = response.data;
                    this.renderWeekSchedules(data.schedules, data.week_dates);
                } else {
                    App.showNotification('スケジュールの読み込みに失敗しました', 'error');
                }
            })
            .catch(error => {
                console.error("Error loading week schedules:", error);
                App.showNotification('スケジュールの読み込みに失敗しました', 'error');
            });
    },

    // 週単位スケジュールの表示
    renderWeekSchedules: function (schedules, weekDates) {
        const container = $('#week-schedule-container');

        // 日付ごとにスケジュールを整理
        const dailySchedules = {};
        weekDates.forEach(date => {
            dailySchedules[date] = {
                allDay: [],
                timeSlots: {},
                // スパンとして表示するスケジュール
                timeSpans: []
            };

            // 時間帯の初期化（1時間ごと）
            for (let hour = 0; hour < 24; hour++) {
                const timeStr = hour.toString().padStart(2, '0') + ':00';
                dailySchedules[date].timeSlots[timeStr] = [];
            }
        });

        // スケジュールを日付と時間帯に振り分け
        if (Array.isArray(schedules)) {
            schedules.forEach(schedule => {
                const startDate = moment(schedule.start_time).format('YYYY-MM-DD');
                const endDate = moment(schedule.end_time).format('YYYY-MM-DD');

                // 複数日にまたがるスケジュールは各日に表示
                for (let d = 0; d < weekDates.length; d++) {
                    const currentDate = weekDates[d];

                    // スケジュールの期間と現在の日付が重なるかチェック
                    if (currentDate >= startDate && currentDate <= endDate) {
                        if (schedule.all_day == 1) {
                            // 終日スケジュール
                            dailySchedules[currentDate].allDay.push(schedule);
                        } else {
                            // 時間指定スケジュール
                            // 現在の日付が開始日である場合
                            if (currentDate === startDate) {
                                // スパンデータを追加
                                const startTime = moment(schedule.start_time);
                                let endTime;

                                if (currentDate === endDate) {
                                    // 同じ日に終わる場合
                                    endTime = moment(schedule.end_time);
                                } else {
                                    // 翌日以降に終わる場合、当日の23:59まで
                                    endTime = moment(currentDate + ' 23:59:59');
                                }

                                // 開始時間の時間帯
                                const startHour = startTime.format('HH:00');

                                // スパンデータを作成
                                const spanData = {
                                    schedule: schedule,
                                    startTime: startTime,
                                    endTime: endTime,
                                    startHour: parseInt(startTime.format('H')),
                                    endHour: parseInt(endTime.format('H')) + (endTime.format('mm') > '00' ? 1 : 0),
                                    topPosition: this.calculateTimePosition(startTime),
                                    height: this.calculateDurationHeight(startTime, endTime)
                                };

                                // スパンリストに追加
                                dailySchedules[currentDate].timeSpans.push(spanData);

                                // 開始時間の時間帯にも追加（バックアップ）
                                dailySchedules[currentDate].timeSlots[startHour].push(schedule);
                            } else if (currentDate === endDate) {
                                // 終了日の場合（開始日とは別の日）
                                const startTime = moment(currentDate + ' 00:00:00');
                                const endTime = moment(schedule.end_time);

                                // スパンデータを作成
                                const spanData = {
                                    schedule: schedule,
                                    startTime: startTime,
                                    endTime: endTime,
                                    startHour: 0,
                                    endHour: parseInt(endTime.format('H')) + (endTime.format('mm') > '00' ? 1 : 0),
                                    topPosition: 0,
                                    height: this.calculateDurationHeight(startTime, endTime)
                                };

                                // スパンリストに追加
                                dailySchedules[currentDate].timeSpans.push(spanData);

                                // 0時の時間帯にも追加（バックアップ）
                                dailySchedules[currentDate].timeSlots['00:00'].push(schedule);
                            } else {
                                // 開始日でも終了日でもない場合（中間の日）
                                const startTime = moment(currentDate + ' 00:00:00');
                                const endTime = moment(currentDate + ' 23:59:59');

                                // スパンデータを作成
                                const spanData = {
                                    schedule: schedule,
                                    startTime: startTime,
                                    endTime: endTime,
                                    startHour: 0,
                                    endHour: 24,
                                    topPosition: 0,
                                    height: 60 * 24 // 24時間分の高さ
                                };

                                // スパンリストに追加
                                dailySchedules[currentDate].timeSpans.push(spanData);

                                // 0時の時間帯にも追加（バックアップ）
                                dailySchedules[currentDate].timeSlots['00:00'].push(schedule);
                            }
                        }
                    }
                }
            });
        }

        // HTML生成
        let html = '<div class="week-schedule">';

        // 曜日ヘッダー
        html += '<div class="week-header">';
        html += '<div class="week-time-column"></div>'; // 時間列のヘッダー

        weekDates.forEach(date => {
            const dayOfWeek = moment(date).format('ddd');
            const dayOfMonth = moment(date).format('D');
            const isToday = moment().format('YYYY-MM-DD') === date;
            const todayClass = isToday ? 'today' : '';

            html += `<div class="week-day ${todayClass}" data-date="${date}">
                    <div class="week-day-name">${dayOfWeek}</div>
                    <div class="week-day-number">${dayOfMonth}</div>
                </div>`;
        });

        html += '</div>';

        // 終日スケジュール行
        html += '<div class="week-all-day-row">';
        html += '<div class="week-time-column">終日</div>';

        weekDates.forEach(date => {
            const allDayItems = dailySchedules[date].allDay;
            const isToday = moment().format('YYYY-MM-DD') === date;
            const todayClass = isToday ? 'today' : '';

            html += `<div class="week-day-content ${todayClass}" data-date="${date}">`;

            if (allDayItems.length > 0) {
                allDayItems.forEach(schedule => {
                    html += this.renderWeekScheduleItem(schedule, true);
                });
            }

            html += '</div>';
        });

        html += '</div>';

        // 時間帯行
        for (let hour = 0; hour < 24; hour++) {
            const timeStr = hour.toString().padStart(2, '0') + ':00';

            html += '<div class="week-hour-row">';
            html += `<div class="week-time-column">${timeStr}</div>`;

            weekDates.forEach(date => {
                const hourItems = dailySchedules[date].timeSlots[timeStr];
                const isToday = moment().format('YYYY-MM-DD') === date;
                const todayClass = isToday ? 'today' : '';

                html += `<div class="week-day-content ${todayClass}" data-date="${date}" data-hour="${hour}">`;

                // この時間帯のスパンスケジュールを表示
                const timeSpans = dailySchedules[date].timeSpans.filter(span =>
                    span.startHour <= hour && span.endHour > hour
                );

                // 各時間帯で最初に表示される時のみレンダリング
                timeSpans.forEach(span => {
                    if (span.startHour === hour) {
                        html += this.renderWeekScheduleTimespan(span.schedule, span.height);
                    }
                });

                html += '</div>';
            });

            html += '</div>';
        }

        html += '</div>';

        container.html(html);

        // セルのクリックイベント
        $('.week-day-content').on('click', function (e) {
            // スケジュールアイテムのクリックは除外
            if ($(e.target).closest('.schedule-item, .schedule-timespan').length === 0) {
                const date = $(this).data('date');
                const hour = $(this).data('hour');

                if (date) {
                    if (hour !== undefined) {
                        const time = hour.toString().padStart(2, '0') + ':00';
                        // モーダルを使用するよう変更
                        if ($('#schedule-modal').length) {
                            Schedule.showCreateModal(date, time, false);
                        } else {
                            window.location.href = BASE_PATH + '/schedule/create?date=' + date + '&time=' + time;
                        }
                    } else {
                        // モーダルを使用するよう変更
                        if ($('#schedule-modal').length) {
                            Schedule.showCreateModal(date, '00:00', true);
                        } else {
                            window.location.href = BASE_PATH + '/schedule/create?date=' + date + '&all_day=1';
                        }
                    }
                }
            }
        });

        // スケジュールアイテムのクリックイベント - この部分が問題です
        $('.schedule-item, .schedule-timespan').on('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            const scheduleId = $(this).data('id');
            if (scheduleId) {
                // モーダルを使用するよう変更
                if ($('#schedule-modal').length) {
                    Schedule.showViewModal(scheduleId);
                } else {
                    window.location.href = BASE_PATH + '/schedule/view/' + scheduleId;
                }
            }
        });
    },

    // 時間位置を計算（分単位で）
    calculateTimePosition: function (time) {
        const minutes = parseInt(time.format('H')) * 60 + parseInt(time.format('m'));
        // 1分あたりの高さを計算（60pxが1時間の高さと仮定）
        return (minutes / 60) * 60;
    },

    // 時間範囲の高さを計算
    calculateDurationHeight: function (startTime, endTime) {
        const startMinutes = parseInt(startTime.format('H')) * 60 + parseInt(startTime.format('m'));
        const endMinutes = parseInt(endTime.format('H')) * 60 + parseInt(endTime.format('m'));
        const durationMinutes = endMinutes - startMinutes;

        // 1分あたりの高さを計算（60pxが1時間の高さと仮定）
        return Math.max(30, (durationMinutes / 60) * 60); // 最小高さ30px
    },

    // 週表示用スケジュールアイテムのレンダリング（時間枠付き）
    renderWeekScheduleItemWithSpan: function (schedule, isAllDay, durationHours) {
        const startTime = moment(schedule.start_time).format('HH:mm');
        const endTime = moment(schedule.end_time).format('HH:mm');
        const timeDisplay = startTime + ' - ' + endTime;
        const priorityClass = this.getPriorityClass(schedule.priority);

        // セルの高さは1時間あたり60pxと仮定
        const heightStyle = `height: ${durationHours * 60 - 4}px;`; // 境界線の余白を引く

        // スタイル属性を追加
        return `
        <div class="schedule-item ${priorityClass} time-span" data-id="${schedule.id}" style="${heightStyle}">
            <div class="schedule-title">${schedule.title}</div>
            <div class="schedule-time">${timeDisplay}</div>
        </div>
    `;
    },
    // 週表示用時間スパンスケジュールのレンダリング
    renderWeekScheduleTimespan: function (schedule, height) {
        const startTime = moment(schedule.start_time).format('HH:mm');
        const endTime = moment(schedule.end_time).format('HH:mm');
        const timeDisplay = startTime + ' - ' + endTime;
        const priorityClass = this.getPriorityClass(schedule.priority);

        return `
        <div class="schedule-item schedule-timespan ${priorityClass}"
             style="height: ${height}px;" 
             data-id="${schedule.id}">
            <div class="schedule-title">${schedule.title}</div>
            <div class="schedule-time">${timeDisplay}</div>
        </div>
    `;
    },

    // 月単位スケジュールの読み込み
    loadMonthSchedules: function (year, month, userId) {
        console.log("Loading month schedules for year:", year, "month:", month, "user:", userId);
        App.apiGet('/api/schedule/month', { year: year, month: month, user_id: userId })
            .then(response => {
                console.log("Month schedules response:", response);
                if (response.success) {
                    const data = response.data;
                    this.renderMonthSchedules(data.schedules, year, month, data.days_in_month, data.first_day_of_week);
                } else {
                    App.showNotification('スケジュールの読み込みに失敗しました', 'error');
                }
            })
            .catch(error => {
                console.error("Error loading month schedules:", error);
                App.showNotification('スケジュールの読み込みに失敗しました', 'error');
            });
    },

    // 月単位スケジュールの表示
    renderMonthSchedules: function (schedules, year, month, daysInMonth, firstDayOfWeek) {
        const container = $('#month-schedule-container');

        // 日付ごとにスケジュールを整理
        const dailySchedules = {};
        for (let day = 1; day <= daysInMonth; day++) {
            const dateStr = year + '-' + month.toString().padStart(2, '0') + '-' + day.toString().padStart(2, '0');
            dailySchedules[dateStr] = [];
        }

        // スケジュールを日付に振り分け
        if (Array.isArray(schedules)) {
            schedules.forEach(schedule => {
                const startDate = moment(schedule.start_time).format('YYYY-MM-DD');
                const endDate = moment(schedule.end_time).format('YYYY-MM-DD');

                // スケジュールの期間内の各日に振り分け
                const start = moment(startDate);
                const end = moment(endDate);

                for (let m = moment(start); m.isSameOrBefore(end); m.add(1, 'days')) {
                    const currentDate = m.format('YYYY-MM-DD');

                    // 表示対象月のみ処理
                    if (m.month() + 1 == month && m.year() == year) {
                        if (dailySchedules[currentDate]) {
                            dailySchedules[currentDate].push(schedule);
                        }
                    }
                }
            });
        }

        // カレンダーHTML生成
        let html = '<div class="month-calendar">';

        // 曜日ヘッダー
        html += '<div class="week-row header-row">';
        const dayNames = ['日', '月', '火', '水', '木', '金', '土'];
        dayNames.forEach(day => {
            html += `<div class="day-cell day-name">${day}</div>`;
        });
        html += '</div>';

        // カレンダー本体
        let dayCount = 1;
        let cellCount = 0;

        // 最大6週間分のカレンダーを生成
        for (let week = 0; week < 6; week++) {
            html += '<div class="week-row">';

            // 1週間7日分のセルを生成
            for (let weekday = 0; weekday < 7; weekday++) {
                cellCount++;

                // 1日以前または月末以降の空白セル
                if ((week === 0 && weekday < firstDayOfWeek) || dayCount > daysInMonth) {
                    html += '<div class="day-cell empty-cell"></div>';
                } else {
                    const date = year + '-' + month.toString().padStart(2, '0') + '-' + dayCount.toString().padStart(2, '0');
                    const isToday = moment().format('YYYY-MM-DD') === date;
                    const todayClass = isToday ? 'today' : '';
                    const daySchedules = dailySchedules[date] || [];
                    const dayOfWeek = weekday; // 0(日)～6(土)
                    const weekendClass = (dayOfWeek === 0 || dayOfWeek === 6) ? 'weekend' : '';

                    html += `<div class="day-cell calendar-day ${todayClass} ${weekendClass}" data-date="${date}">`;
                    html += `<div class="day-number">${dayCount}</div>`;
                    html += '<div class="day-content">';

                    // 最大3件まで表示
                    const maxDisplay = 3;
                    const displaySchedules = daySchedules.slice(0, maxDisplay);
                    const remainingCount = Math.max(0, daySchedules.length - maxDisplay);

                    displaySchedules.forEach(schedule => {
                        html += this.renderMonthScheduleItem(schedule);
                    });

                    if (remainingCount > 0) {
                        html += `<div class="more-schedules">他 ${remainingCount} 件</div>`;
                    }

                    html += '</div></div>';

                    dayCount++;
                }
            }

            html += '</div>';

            // 月の最終日を表示したら終了
            if (dayCount > daysInMonth) {
                break;
            }
        }

        html += '</div>';

        container.html(html);

        // 「他 ○ 件」クリック時のイベント
        $('.more-schedules').on('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            const date = $(this).closest('.calendar-day').data('date');
            window.location.href = BASE_PATH + '/schedule/day?date=' + date;
        });
    },

    // スケジュールリストアイテムのレンダリング
    renderScheduleListItem: function (schedule) {
        const startTime = moment(schedule.start_time).format('HH:mm');
        const endTime = moment(schedule.end_time).format('HH:mm');
        const timeDisplay = schedule.all_day == 1 ? '終日' : startTime + ' - ' + endTime;
        const priorityClass = this.getPriorityClass(schedule.priority);

        return `
            <div class="list-group-item list-group-item-action ${priorityClass} schedule-item" data-id="${schedule.id}">
                <div class="d-flex w-100 justify-content-between">
                    <h5 class="mb-1">${schedule.title}</h5>
                    <small>${timeDisplay}</small>
                </div>
                <p class="mb-1">${schedule.description || ''}</p>
                <small>${schedule.creator_name}</small>
            </div>
        `;
    },

    // タイムライン用スケジュールアイテムのレンダリング
    renderScheduleTimelineItem: function (schedule) {
        const startTime = moment(schedule.start_time).format('HH:mm');
        const endTime = moment(schedule.end_time).format('HH:mm');
        const timeDisplay = startTime + ' - ' + endTime;
        const priorityClass = this.getPriorityClass(schedule.priority);

        return `
            <div class="schedule-item ${priorityClass}" data-id="${schedule.id}">
                <div class="schedule-time">${timeDisplay}</div>
                <div class="schedule-title">${schedule.title}</div>
            </div>
        `;
    },

    // 週表示用スケジュールアイテムのレンダリング
    renderWeekScheduleItem: function (schedule, isAllDay) {
        const startTime = moment(schedule.start_time).format('HH:mm');
        const endTime = moment(schedule.end_time).format('HH:mm');
        const timeDisplay = isAllDay ? '' : startTime + ' - ' + endTime;
        const priorityClass = this.getPriorityClass(schedule.priority);

        return `
            <div class="schedule-item ${priorityClass}" data-id="${schedule.id}">
                <div class="schedule-title">${schedule.title}</div>
                ${timeDisplay ? '<div class="schedule-time">' + timeDisplay + '</div>' : ''}
            </div>
        `;
    },

    // 月表示用スケジュールアイテムのレンダリング
    renderMonthScheduleItem: function (schedule) {
        const priorityClass = this.getPriorityClass(schedule.priority);
        const allDayClass = schedule.all_day == 1 ? 'all-day' : '';

        return `
            <div class="schedule-item ${priorityClass} ${allDayClass}" data-id="${schedule.id}">
                ${schedule.title}
            </div>
        `;
    },

    // フォームのバリデーション
    validateForm: function () {
        let isValid = true;

        // 必須フィールドのチェック
        const requiredFields = ['title', 'start_time_date', 'end_time_date'];

        requiredFields.forEach(field => {
            const input = $('#' + field);

            if (!input.val().trim()) {
                input.addClass('is-invalid');
                input.next('.invalid-feedback').text('この項目は必須です');
                isValid = false;
            } else {
                input.removeClass('is-invalid');
            }
        });

        // 終日でない場合は時間も必須
        if ($('#all_day').is(':checked')) {
            const timeFields = ['start_time_time', 'end_time_time'];

            timeFields.forEach(field => {
                const input = $('#' + field);

                if (!input.val().trim()) {
                    input.addClass('is-invalid');
                    input.next('.invalid-feedback').text('この項目は必須です');
                    isValid = false;
                } else {
                    input.removeClass('is-invalid');
                }
            });
        }

        // 開始日時と終了日時の整合性チェック
        const startDate = $('#start_time_date').val();
        const startTime = $('#start_time_time').val() || '00:00';
        const endDate = $('#end_time_date').val();
        const endTime = $('#end_time_time').val() || '23:59';

        const startDateTime = new Date(startDate + ' ' + startTime);
        const endDateTime = new Date(endDate + ' ' + endTime);

        if (startDateTime > endDateTime) {
            $('#end_time_date').addClass('is-invalid');
            $('#end_time_date').next('.invalid-feedback').text('終了日時は開始日時以降にしてください');
            isValid = false;
        }

        // 繰り返し設定の整合性チェック
        if ($('#repeat_type').val() !== 'none' && !$('#repeat_end_date').val()) {
            $('#repeat_end_date').addClass('is-invalid');
            $('#repeat_end_date').next('.invalid-feedback').text('繰り返し終了日を指定してください');
            isValid = false;
        }

        return isValid;
    },

    // 参加ステータスを更新
    updateParticipationStatus: function (scheduleId, status) {
        App.apiPost('/api/schedule/' + scheduleId + '/participation-status', { status: status })
            .then(response => {
                if (response.success) {
                    App.showNotification(response.message || '参加ステータスを更新しました', 'success');

                    // ステータス表示を更新
                    this.updateParticipationStatusDisplay(status);
                } else {
                    App.showNotification(response.error || 'エラーが発生しました', 'error');
                }
            })
            .catch(error => {
                App.showNotification('エラーが発生しました', 'error');
                console.error(error);
            });
    },

    // 参加ステータス表示を更新
    updateParticipationStatusDisplay: function (status) {
        // ステータス表示テキスト
        const statusText = {
            'pending': '未回答',
            'accepted': '参加',
            'declined': '不参加',
            'tentative': '未定'
        };

        // ステータス表示クラス
        const statusClass = {
            'pending': 'bg-secondary',
            'accepted': 'bg-success',
            'declined': 'bg-danger',
            'tentative': 'bg-warning'
        };

        // 現在のステータス表示を更新
        $('#participation-status').text(statusText[status] || '未回答');
        $('#participation-status')
            .removeClass('bg-secondary bg-success bg-danger bg-warning')
            .addClass(statusClass[status] || 'bg-secondary');

        // ボタン状態を更新
        $('.btn-participation-status').removeClass('active');
        $(`.btn-participation-status[data-status="${status}"]`).addClass('active');
    },

    // 参加ステータスのバッジを取得
    getParticipationStatusBadge: function (status) {
        switch (status) {
            case 'accepted':
                return '<span class="badge bg-success">参加</span>';
            case 'declined':
                return '<span class="badge bg-danger">不参加</span>';
            case 'tentative':
                return '<span class="badge bg-warning">未定</span>';
            default:
                return '<span class="badge bg-secondary">未回答</span>';
        }
    },

    // 優先度に応じたカラークラスを取得
    getPriorityClass: function (priority) {
        switch (priority) {
            case 'high':
                return 'priority-high';
            case 'low':
                return 'priority-low';
            default:
                return 'priority-normal';
        }
    },

    // 優先度に応じたカラーコードを取得
    getPriorityColor: function (priority) {
        switch (priority) {
            case 'high':
                return '#d9534f'; // 赤（高）
            case 'low':
                return '#5bc0de'; // 青（低）
            default:
                return '#5cb85c'; // 緑（通常）
        }
    }
};
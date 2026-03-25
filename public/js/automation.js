(function () {
    function withSeconds(t) {
        if (!t) return '09:00:00';
        return t.length === 5 ? (t + ':00') : t;
    }

    $(document).on('submit', '#automation-create-form', function (e) {
        e.preventDefault();
        const payload = Object.fromEntries(new FormData(this).entries());
        payload.run_at = withSeconds(payload.run_at);

        $.ajax({
            url: BASE_PATH + '/api/automation/jobs',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(payload),
            success: function (res) {
                if (res.success) {
                    toastr.success(res.message || '作成しました');
                    window.location.reload();
                } else {
                    toastr.error(res.error || '作成に失敗しました');
                }
            },
            error: function () {
                toastr.error('通信エラー');
            }
        });
    });

    $(document).on('click', '.toggle-job', function () {
        const id = $(this).data('id');
        const active = Number($(this).data('active')) === 1;

        $.ajax({
            url: BASE_PATH + '/api/automation/jobs/' + id + '/toggle',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ is_active: !active }),
            success: function (res) {
                if (res.success) {
                    toastr.success(res.message || '更新しました');
                    window.location.reload();
                } else {
                    toastr.error(res.error || '更新に失敗しました');
                }
            },
            error: function () {
                toastr.error('通信エラー');
            }
        });
    });

    $(document).on('click', '.run-job-now', function () {
        const id = $(this).data('id');
        $.ajax({
            url: BASE_PATH + '/api/automation/jobs/' + id + '/run',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({}),
            success: function (res) {
                if (res.success) {
                    toastr.success(res.message || '実行しました');
                } else {
                    toastr.error(res.error || res.message || '実行に失敗しました');
                }
            },
            error: function () {
                toastr.error('通信エラー');
            }
        });
    });

    $(document).on('click', '#run-due-jobs', function () {
        $.ajax({
            url: BASE_PATH + '/api/automation/run-due',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({}),
            success: function (res) {
                if (res.success) {
                    toastr.success(res.message || '実行しました');
                    setTimeout(function () {
                        window.location.reload();
                    }, 800);
                } else {
                    toastr.error(res.error || '実行に失敗しました');
                }
            },
            error: function () {
                toastr.error('通信エラー');
            }
        });
    });
})();

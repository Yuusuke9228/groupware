(function () {
    function serializeIntegrationSettings(form) {
        return {
            feed_enabled: form.querySelector('#feed_enabled').checked ? 1 : 0,
            allow_ics_import: form.querySelector('#allow_ics_import').checked ? 1 : 0,
            include_public: form.querySelector('#include_public').checked ? 1 : 0,
            include_participant: form.querySelector('#include_participant').checked ? 1 : 0,
            include_organization: form.querySelector('#include_organization').checked ? 1 : 0,
            include_private: form.querySelector('#include_private').checked ? 1 : 0
        };
    }

    function copyText(targetSelector) {
        const input = document.querySelector(targetSelector);
        if (!input) {
            return;
        }

        input.select();
        input.setSelectionRange(0, 99999);
        document.execCommand('copy');

        if (typeof toastr !== 'undefined') {
            toastr.success('URLをコピーしました');
        }
    }

    $(document).on('submit', '#ics-import-form', function (e) {
        e.preventDefault();
        const payload = Object.fromEntries(new FormData(this).entries());

        $.ajax({
            url: BASE_PATH + '/api/integrations/import-ics',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(payload),
            success: function (res) {
                if (res.success) {
                    toastr.success(res.message || '取り込みました');
                } else {
                    toastr.error(res.error || '取り込みに失敗しました');
                }
            },
            error: function () {
                toastr.error('通信エラー');
            }
        });
    });

    $(document).on('submit', '#integration-settings-form', function (e) {
        e.preventDefault();

        $.ajax({
            url: BASE_PATH + '/api/integrations/settings',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(serializeIntegrationSettings(this)),
            success: function (res) {
                if (!res.success) {
                    toastr.error(res.message || '設定の保存に失敗しました');
                    return;
                }

                if (res.data && res.data.feed_url) {
                    $('#feed-url').val(res.data.feed_url);
                }

                toastr.success(res.message || '設定を保存しました');
            },
            error: function () {
                toastr.error('通信エラー');
            }
        });
    });

    $(document).on('click', '#regenerate-token-btn', function () {
        if (!window.confirm('購読トークンを再発行します。旧URLは無効になります。続行しますか？')) {
            return;
        }

        $.ajax({
            url: BASE_PATH + '/api/integrations/regenerate-token',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({}),
            success: function (res) {
                if (!res.success) {
                    toastr.error(res.message || 'トークン再発行に失敗しました');
                    return;
                }

                if (res.data && res.data.feed_url) {
                    $('#feed-url').val(res.data.feed_url);
                }

                toastr.success(res.message || 'トークンを再発行しました');
            },
            error: function () {
                toastr.error('通信エラー');
            }
        });
    });

    $(document).on('click', '.copy-feed-url', function () {
        copyText($(this).data('target'));
    });
})();

/**
 * WEB Database Export - JavaScriptファイル
 */

// エクスポート用名前空間
const WebDatabaseExport = {
    // 初期化
    init: function () {
        this.initUserFieldOptions();
        this.initOrganizationFieldOptions();
        this.setupEventListeners();
    },

    // イベントリスナーを設定
    setupEventListeners: function () {
        // エクスポートフォーム送信
        $('#export-form').on('submit', function (e) {
            // ユーザーがフィールドを選択しているか確認
            const checkedFields = $('input[name="export_fields[]"]:checked');
            if (checkedFields.length === 0) {
                e.preventDefault();
                App.showNotification('少なくとも1つのフィールドを選択してください', 'error');
                return false;
            }

            // 進行中メッセージを表示
            App.showNotification('CSVエクスポートを開始しました...', 'info');

            // 通常のフォーム送信を許可（フォームのアクションURLにリダイレクト）
            return true;
        });
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
    }
};

// DOMが読み込まれたらエクスポート機能を初期化
$(document).ready(function () {
    WebDatabaseExport.init();
});
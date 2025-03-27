/**
 * WEB Database Import - JavaScriptファイル
 */

// インポート用名前空間
const WebDatabaseImport = {
    // CSV解析結果
    parsedData: null,

    // 初期化
    init: function () {
        this.setupEventListeners();
    },

    // イベントリスナーを設定
    setupEventListeners: function () {
        // CSVファイル選択時のプレビュー表示
        $('#csv_file').on('change', function (e) {
            const file = e.target.files[0];
            if (file) {
                WebDatabaseImport.previewCSV(file);
            } else {
                $('#preview-container').hide();
            }
        });

        // インポートフォーム送信
        $('#import-form').on('submit', function (e) {
            e.preventDefault();

            // フォームデータの取得
            const formData = new FormData(this);

            // マッピング情報を追加
            const mappings = {};
            $('.field-mapping-select').each(function () {
                const columnIndex = $(this).data('column-index');
                const fieldId = $(this).val();
                if (fieldId) {
                    mappings[columnIndex] = fieldId;
                }
            });

            formData.append('field_mapping', JSON.stringify(mappings));

            // インポート処理の実行
            WebDatabaseImport.importCSV(formData);
        });
    },

    // CSVプレビュー表示
    previewCSV: function (file) {
        const reader = new FileReader();

        reader.onload = function (e) {
            const content = e.target.result;

            // CSVのパース
            Papa.parse(content, {
                header: true,
                skipEmptyLines: true,
                encoding: 'UTF-8',
                complete: function (results) {
                    WebDatabaseImport.parsedData = results;
                    WebDatabaseImport.showPreview(results.data);
                },
                error: function (error) {
                    App.showNotification('CSVの解析に失敗しました: ' + error.message, 'error');
                }
            });
        };

        reader.onerror = function () {
            App.showNotification('ファイルの読み込みに失敗しました', 'error');
        };

        reader.readAsText(file);
    },

    // プレビュー表示
    showPreview: function (data) {
        if (!data || data.length === 0) {
            App.showNotification('CSVデータが空または無効です', 'error');
            return;
        }

        // ヘッダー行の表示
        const headers = Object.keys(data[0]);
        let headerHtml = '';
        let mappingHtml = '';

        headers.forEach(function (header, index) {
            headerHtml += '<th>' + header + '</th>';

            // フィールドマッピング用のセレクトボックス
            mappingHtml += '<td><select class="form-select form-select-sm field-mapping-select" data-column-index="' + index + '">';
            mappingHtml += '<option value="">マッピングなし</option>';

            // データベースフィールドとのマッピング選択肢
            databaseFields.forEach(function (field) {
                // ヘッダー名とフィールド名が一致する場合は選択状態に
                const selected = (header === field.name) ? 'selected' : '';
                mappingHtml += '<option value="' + field.id + '" ' + selected + '>' + field.name + '</option>';
            });

            mappingHtml += '</select></td>';
        });

        $('#csv-headers').html(headerHtml);
        $('#field-mapping').html(mappingHtml);

        // プレビューデータの表示（最大5行）
        let previewHtml = '';
        const previewRows = data.slice(0, 5);

        previewRows.forEach(function (row) {
            previewHtml += '<tr>';
            headers.forEach(function (header) {
                previewHtml += '<td>' + (row[header] || '') + '</td>';
            });
            previewHtml += '</tr>';
        });

        $('#csv-preview').html(previewHtml);
        $('#preview-container').show();
    },

    // CSVインポート実行
    importCSV: function (formData) {
        // インポート処理中メッセージ
        App.showNotification('インポート処理を実行中です...', 'info');
        $('#import-btn').prop('disabled', true);

        // APIリクエスト
        $.ajax({
            url: $('#import-form').attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                if (response.success) {
                    App.showNotification(response.message, 'success');

                    // 結果詳細の表示
                    if (response.data.errors > 0) {
                        const errorDetails = response.data.error_details.join('<br>');
                        $('#import-form').after(
                            '<div class="alert alert-warning mt-3">' +
                            '<h5>インポート警告</h5>' +
                            '<p>' + response.data.imported + '件のレコードをインポートしました。' +
                            response.data.errors + '件のエラーが発生しました。</p>' +
                            '<div class="mt-2">' + errorDetails + '</div>' +
                            '</div>'
                        );
                    } else {
                        // 成功時は3秒後にレコード一覧ページへリダイレクト
                        setTimeout(function () {
                            window.location.href = BASE_PATH + '/webdatabase/records/' + WebDatabaseImport.getDatabaseId();
                        }, 3000);
                    }
                } else {
                    App.showNotification(response.error, 'error');
                }
            },
            error: function () {
                App.showNotification('インポート処理中にエラーが発生しました', 'error');
            },
            complete: function () {
                $('#import-btn').prop('disabled', false);
            }
        });
    },

    // URLからデータベースIDを取得
    getDatabaseId: function () {
        const pathParts = window.location.pathname.split('/');
        return pathParts[pathParts.indexOf('import-csv') - 1];
    }
};

// DOMが読み込まれたらインポート機能を初期化
$(document).ready(function () {
    WebDatabaseImport.init();
});
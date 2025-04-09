<?php
// 編集モードかどうかを判定
$isEdit = isset($template) && $template !== null;
$formTitle = $isEdit ? 'テンプレート編集' : '新規テンプレート作成';
$formAction = $isEdit ? BASE_PATH . "/api/daily-report/template/{$template['id']}" : BASE_PATH . "/api/daily-report/template";
$submitButtonText = $isEdit ? '更新する' : '作成する';
?>

<div class="container-fluid pt-4 px-4">
    <div class="row g-4">
        <div class="col-12">
            <div class="bg-light rounded p-4 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="mb-0"><?= $formTitle ?></h3>
                    <div>
                        <a href="<?= BASE_PATH ?>/daily-report/templates" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>キャンセル
                        </a>
                    </div>
                </div>

                <form id="templateForm" method="POST" action="<?= $formAction ?>">
                    <!-- タイトル -->
                    <div class="mb-3">
                        <label for="title" class="form-label">テンプレート名 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title"
                            value="<?= $isEdit ? htmlspecialchars($template['title']) : '' ?>"
                            required maxlength="100">
                        <div class="invalid-feedback"></div>
                    </div>

                    <!-- 内容 -->
                    <div class="mb-3">
                        <label for="content" class="form-label">テンプレート内容 <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="content" name="content" rows="15" required><?= $isEdit ? htmlspecialchars($template['content']) : '' ?></textarea>
                        <div class="invalid-feedback"></div>
                        <div class="form-text">
                            タイトルや見出しには「#」や「##」を使用すると視認性が高まります。
                            項目の区切りには改行を使用してください。
                        </div>
                    </div>

                    <!-- 公開設定 -->
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="is_public" name="is_public" <?= ($isEdit && $template['is_public']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_public">
                            このテンプレートを全ユーザーに公開する
                        </label>
                        <div class="form-text">
                            公開テンプレートは全ユーザーが使用できます。チェックしない場合は自分だけが使用できます。
                        </div>
                    </div>

                    <!-- ボタン -->
                    <div class="text-center mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i><?= $submitButtonText ?>
                        </button>
                        <?php if ($isEdit): ?>
                            <button type="button" class="btn btn-danger ms-2" id="deleteButton" data-id="<?= $template['id'] ?>">
                                <i class="fas fa-trash me-2"></i>削除
                            </button>
                        <?php endif; ?>
                    </div>
                </form>

                <!-- テンプレート作成時のヒントカード -->
                <div class="card mt-5">
                    <div class="card-header">
                        <h5 class="mb-0">テンプレート作成のヒント</h5>
                    </div>
                    <div class="card-body">
                        <h6>テンプレートの例</h6>
                        <pre class="bg-light p-3 border rounded">
# 日報タイトル

## 本日の業務内容
1. 
2. 
3. 

## 達成したこと


## 課題・問題点


## 明日の予定
1. 
2. 

## 連絡事項

</pre>
                        <div class="alert alert-info mt-3">
                            <i class="fas fa-lightbulb me-2"></i>
                            <b>効果的なテンプレート作成のポイント</b>
                            <ul class="mb-0 mt-2">
                                <li>必要な情報が漏れなく報告できるようにセクションを分ける</li>
                                <li>箇条書きや番号付きリストを活用して見やすくする</li>
                                <li>必須項目と任意項目を明確にする</li>
                                <li>プロジェクトや部署ごとに特化したテンプレートを作成する</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // デバッグ情報
        console.log('テンプレートフォーム初期化:');
        console.log('isEdit:', <?= json_encode($isEdit) ?>);
        console.log('formAction:', '<?= $formAction ?>');

        // フォーム送信処理
        $('#templateForm').off('submit').on('submit', function(e) {
            e.preventDefault();

            // デバッグログ
            console.log('フォーム送信開始:', $(this).attr('action'));

            // バリデーションリセット
            $(this).find('.is-invalid').removeClass('is-invalid');
            $(this).find('.invalid-feedback').text('');

            // フォームデータの作成
            const formData = {
                title: $('#title').val(),
                content: $('#content').val(),
                is_public: $('#is_public').is(':checked')
            };

            console.log('送信データ:', formData);

            // API呼び出し
            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(formData),
                dataType: 'json',
                beforeSend: function() {
                    // 送信ボタンを無効化
                    $('button[type="submit"]').prop('disabled', true);
                },
                success: function(response) {
                    console.log('API応答:', response);

                    if (response.success) {
                        // 成功時はリダイレクト
                        toastr.success(response.message || 'テンプレートを保存しました');
                        setTimeout(function() {
                            window.location.href = response.data.redirect;
                        }, 1000);
                    } else {
                        // エラーメッセージを表示
                        toastr.error(response.error || 'エラーが発生しました');

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
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                    console.error('Response:', xhr.responseText);
                    toastr.error('ネットワークエラーが発生しました');
                },
                complete: function() {
                    // 送信ボタンを有効化
                    $('button[type="submit"]').prop('disabled', false);
                }
            });
            return false;
        });

        // 削除ボタンの処理
        $('#deleteButton').on('click', function() {
            if (confirm('本当にこのテンプレートを削除しますか？この操作は元に戻せません。')) {
                const templateId = $(this).data('id');

                // 削除API呼び出し
                $.ajax({
                    url: `${BASE_PATH}/api/daily-report/template/${templateId}`,
                    type: 'DELETE',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // 成功時は一覧ページにリダイレクト
                            toastr.success(response.message || 'テンプレートを削除しました');
                            setTimeout(function() {
                                window.location.href = response.data.redirect;
                            }, 1000);
                        } else {
                            // エラーメッセージを表示
                            toastr.error(response.error || 'エラーが発生しました');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error:', error);
                        console.error('Response:', xhr.responseText);
                        toastr.error('ネットワークエラーが発生しました');
                    }
                });
            }
        });
    });
</script>
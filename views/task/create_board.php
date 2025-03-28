<div class="container-fluid mt-3">
    <div class="row mb-3">
        <div class="col-md-8">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_PATH; ?>/task">タスク管理</a></li>
                    <li class="breadcrumb-item active" aria-current="page">タスクボード作成</li>
                </ol>
            </nav>
            <h4 class="mb-3">タスクボード作成</h4>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0">ボード情報</h5>
                </div>
                <div class="card-body">
                    <form id="boardForm" method="POST" action="#">
                        <div class="mb-3">
                            <label for="boardName" class="form-label">ボード名 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="boardName" name="name" required>
                            <div class="invalid-feedback">ボード名を入力してください。</div>
                        </div>

                        <div class="mb-3">
                            <label for="boardDescription" class="form-label">説明</label>
                            <textarea class="form-control" id="boardDescription" name="description" rows="3"></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="ownerType" class="form-label">所有者タイプ <span class="text-danger">*</span></label>
                            <select class="form-select" id="ownerType" name="owner_type" required>
                                <option value="user">個人用</option>
                                <option value="team">チーム用</option>
                                <option value="organization">組織用</option>
                            </select>
                        </div>

                        <div class="mb-3" id="teamSelectContainer" style="display: none;">
                            <label for="teamSelect" class="form-label">チーム <span class="text-danger">*</span></label>
                            <select class="form-select" id="teamSelect" name="team_id">
                                <option value="">選択してください</option>
                                <?php foreach ($teams as $team): ?>
                                    <option value="<?php echo $team['id']; ?>"><?php echo htmlspecialchars($team['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">チームを選択してください。</div>
                        </div>

                        <div class="mb-3" id="organizationSelectContainer" style="display: none;">
                            <label for="organizationSelect" class="form-label">組織 <span class="text-danger">*</span></label>
                            <select class="form-select" id="organizationSelect" name="organization_id">
                                <option value="">選択してください</option>
                                <?php foreach ($organizations as $org): ?>
                                    <option value="<?php echo $org['id']; ?>"><?php echo htmlspecialchars($org['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">組織を選択してください。</div>
                        </div>

                        <div class="mb-3">
                            <label for="backgroundColor" class="form-label">背景色</label>
                            <input type="color" class="form-control form-control-color" id="backgroundColor" name="background_color" value="#f0f2f5">
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="isPublic" name="is_public">
                            <label class="form-check-label" for="isPublic">公開ボード（すべてのユーザーが閲覧可能）</label>
                        </div>

                        <div class="d-flex justify-content-end">
                            <a href="<?php echo BASE_PATH; ?>/task" class="btn btn-secondary me-2">キャンセル</a>
                            <button type="submit" class="btn btn-primary">ボードを作成</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">ボード作成ガイド</h5>
                </div>
                <div class="card-body">
                    <p>タスクボードでは、カンバン方式でタスクを管理できます。</p>

                    <h6 class="mt-3">所有者タイプについて</h6>
                    <ul>
                        <li><strong>個人用</strong>：自分だけが使用するボード</li>
                        <li><strong>チーム用</strong>：選択したチームのメンバーが共有するボード</li>
                        <li><strong>組織用</strong>：選択した組織のメンバーが共有するボード</li>
                    </ul>

                    <h6 class="mt-3">作成のポイント</h6>
                    <ul>
                        <li>ボード名は分かりやすく簡潔に</li>
                        <li>目的や利用方法を説明に記載しましょう</li>
                        <li>適切な所有者タイプを選択しましょう</li>
                    </ul>

                    <div class="alert alert-info mt-3">
                        <i class="fas fa-info-circle"></i> ボード作成後もメンバーの追加・削除や設定変更は可能です。
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        console.log('TEST')
        // 所有者タイプの切り替え
        $('#ownerType').on('change', function() {
            const ownerType = $(this).val();

            // 全ての選択肢を非表示
            $('#teamSelectContainer, #organizationSelectContainer').hide();
            $('#teamSelect, #organizationSelect').prop('required', false);

            // 選択されたタイプに応じて表示
            if (ownerType === 'team') {
                $('#teamSelectContainer').show();
                $('#teamSelect').prop('required', true);
            } else if (ownerType === 'organization') {
                $('#organizationSelectContainer').show();
                $('#organizationSelect').prop('required', true);
            }
        });

        // ※※※ 重要：submit イベントが２度バインドされないように既存のハンドラを取り除く ※※※
        $('#boardForm').off('submit');

        // フォーム送信処理
        $('#boardForm').on('submit', function(e) {
            // デフォルトのフォーム送信をキャンセル
            e.preventDefault();
            e.stopPropagation();

            // バリデーション
            let isValid = true;

            if ($('#boardName').val().trim() === '') {
                $('#boardName').addClass('is-invalid');
                isValid = false;
            } else {
                $('#boardName').removeClass('is-invalid');
            }

            const ownerType = $('#ownerType').val();
            let ownerId;

            if (ownerType === 'team') {
                ownerId = $('#teamSelect').val();
                if (!ownerId) {
                    $('#teamSelect').addClass('is-invalid');
                    isValid = false;
                } else {
                    $('#teamSelect').removeClass('is-invalid');
                }
            } else if (ownerType === 'organization') {
                ownerId = $('#organizationSelect').val();
                if (!ownerId) {
                    $('#organizationSelect').addClass('is-invalid');
                    isValid = false;
                } else {
                    $('#organizationSelect').removeClass('is-invalid');
                }
            } else {
                // 個人用の場合は、現在のユーザーIDを使用
                ownerId = '<?php echo $auth->id(); ?>';
            }

            if (!isValid) return false;

            // 送信ボタンを無効化して二重送信を防止
            const submitBtn = $(this).find('button[type="submit"]');
            submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> 処理中...');

            // フォームデータの取得
            const formData = {
                name: $('#boardName').val(),
                description: $('#boardDescription').val(),
                owner_type: ownerType,
                owner_id: ownerId,
                background_color: $('#backgroundColor').val(),
                is_public: $('#isPublic').is(':checked')
            };

            console.log('送信データ:', formData); // デバッグ用

            // API呼び出し
            $.ajax({
                url: BASE_PATH + '/api/task/boards',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(formData),
                success: function(response) {
                    console.log('レスポンス:', response); // デバッグ用

                    if (response.success) {
                        toastr.success(response.message || 'ボードを作成しました');

                        // リダイレクト
                        if (response.redirect) {
                            window.location.href = response.redirect;
                        } else {
                            window.location.href = BASE_PATH + '/task';
                        }
                    } else {
                        toastr.error(response.error || 'ボードの作成に失敗しました');
                        // エラー時はボタンを元に戻す
                        submitBtn.prop('disabled', false).html('ボードを作成');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('エラー詳細:', xhr.responseText); // デバッグ用
                    toastr.error('サーバーとの通信に失敗しました');
                    // エラー時はボタンを元に戻す
                    submitBtn.prop('disabled', false).html('ボードを作成');
                }
            });

            return false; // フォーム送信をキャンセル
        });

        // フォームのネイティブ送信も防止（念のため）
        document.getElementById('boardForm').addEventListener('submit', function(e) {
            e.preventDefault();
            return false;
        }, true);
    });
</script>
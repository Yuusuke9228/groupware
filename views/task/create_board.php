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
                    <!-- フォームのmethod属性とaction属性を修正 -->
                    <form id="boardForm" method="post">
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

                        <div class="mb-3 d-none" id="teamSelectContainer">
                            <label for="teamSelect" class="form-label">チーム <span class="text-danger">*</span></label>
                            <select class="form-select" id="teamSelect" name="team_id">
                                <option value="">選択してください</option>
                                <?php foreach ($teams as $team): ?>
                                    <option value="<?php echo $team['id']; ?>"><?php echo htmlspecialchars($team['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">チームを選択してください。</div>
                        </div>

                        <div class="mb-3 d-none" id="organizationSelectContainer">
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
                            <button type="button" class="btn btn-primary" id="submitBtn">ボードを作成</button>
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
    // DOMContentLoadedイベントを使用して、DOM読み込み後に処理を開始
    document.addEventListener('DOMContentLoaded', function() {
        // jQueryが定義されているか確認
        if (typeof jQuery === 'undefined') {
            console.error('jQuery is not loaded! Adding jQuery from CDN...');

            // jQueryを動的に読み込む
            var script = document.createElement('script');
            script.src = 'https://code.jquery.com/jquery-3.6.4.min.js';
            script.integrity = 'sha256-oP6HI9z1XaZNBrJURtCoUT5SUnxFr8s3BzRl+cbzUq8=';
            script.crossOrigin = 'anonymous';

            script.onload = function() {
                console.log('jQuery loaded successfully. Initializing form...');
                initializeForm();
            };

            document.head.appendChild(script);
        } else {
            console.log('jQuery is already loaded. Initializing form...');
            initializeForm();
        }
    });

    // フォームの初期化とイベントハンドラの設定
    function initializeForm() {
        // BASE_PATH変数がない場合に定義（念のため）
        if (typeof BASE_PATH === 'undefined') {
            BASE_PATH = '<?php echo BASE_PATH; ?>';
        }

        console.log('BASE_PATH:', BASE_PATH); // デバッグ用

        // 所有者タイプの切り替え
        jQuery('#ownerType').on('change', function() {
            const ownerType = jQuery(this).val();

            // 全ての選択肢を非表示
            jQuery('#teamSelectContainer, #organizationSelectContainer').addClass('d-none');
            jQuery('#teamSelect, #organizationSelect').prop('required', false);

            // 選択されたタイプに応じて表示
            if (ownerType === 'team') {
                jQuery('#teamSelectContainer').removeClass('d-none');
                jQuery('#teamSelect').prop('required', true);
            } else if (ownerType === 'organization') {
                jQuery('#organizationSelectContainer').removeClass('d-none');
                jQuery('#organizationSelect').prop('required', true);
            }
        });

        // 送信ボタンがクリックされたときの処理
        jQuery('#submitBtn').on('click', function() {
            submitBoardForm();
        });

        // フォームが送信されたときの処理
        jQuery('#boardForm').on('submit', function(e) {
            e.preventDefault();
            submitBoardForm();
        });

        // フォーム送信処理を関数化
        function submitBoardForm() {
            // バリデーション
            let isValid = true;

            if (jQuery('#boardName').val().trim() === '') {
                jQuery('#boardName').addClass('is-invalid');
                isValid = false;
            } else {
                jQuery('#boardName').removeClass('is-invalid');
            }

            const ownerType = jQuery('#ownerType').val();
            let ownerId;

            if (ownerType === 'team') {
                ownerId = jQuery('#teamSelect').val();
                if (!ownerId) {
                    jQuery('#teamSelect').addClass('is-invalid');
                    isValid = false;
                } else {
                    jQuery('#teamSelect').removeClass('is-invalid');
                }
            } else if (ownerType === 'organization') {
                ownerId = jQuery('#organizationSelect').val();
                if (!ownerId) {
                    jQuery('#organizationSelect').addClass('is-invalid');
                    isValid = false;
                } else {
                    jQuery('#organizationSelect').removeClass('is-invalid');
                }
            } else {
                // 個人用の場合は、現在のユーザーIDを使用
                ownerId = '<?php echo $auth->id(); ?>';
            }

            if (!isValid) return false;

            // 送信ボタンを無効化して二重送信を防止
            const submitBtn = jQuery('#submitBtn');
            submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> 処理中...');

            // フォームデータの取得
            const formData = {
                name: jQuery('#boardName').val(),
                description: jQuery('#boardDescription').val(),
                owner_type: ownerType,
                owner_id: ownerId,
                background_color: jQuery('#backgroundColor').val(),
                is_public: jQuery('#isPublic').is(':checked'),
                created_by: '<?php echo $auth->id(); ?>'
            };

            console.log('送信データ:', formData); // デバッグ用
            console.log('送信先URL:', BASE_PATH + '/api/task/boards'); // デバッグ用

            // XMLHttpRequestを使用してAPI呼び出し（jQuery.ajaxの代わりに）
            const xhr = new XMLHttpRequest();
            xhr.open('POST', BASE_PATH + '/api/task/boards', true);
            xhr.setRequestHeader('Content-Type', 'application/json');

            xhr.onload = function() {
                if (xhr.status >= 200 && xhr.status < 300) {
                    const response = JSON.parse(xhr.responseText);
                    console.log('レスポンス:', response); // デバッグ用

                    if (response.success) {
                        // 成功メッセージを表示
                        if (typeof toastr !== 'undefined') {
                            toastr.success(response.message || 'ボードを作成しました');
                        } else {
                            alert(response.message || 'ボードを作成しました');
                        }

                        // リダイレクト
                        if (response.redirect) {
                            window.location.href = response.redirect;
                        } else {
                            window.location.href = BASE_PATH + '/task';
                        }
                    } else {
                        // エラーメッセージを表示
                        if (typeof toastr !== 'undefined') {
                            toastr.error(response.error || 'ボードの作成に失敗しました');
                        } else {
                            alert(response.error || 'ボードの作成に失敗しました');
                        }

                        // エラー時はボタンを元に戻す
                        submitBtn.prop('disabled', false).html('ボードを作成');
                    }
                } else {
                    console.error('エラー詳細:', xhr.responseText); // デバッグ用

                    // エラーメッセージを表示
                    if (typeof toastr !== 'undefined') {
                        toastr.error('サーバーとの通信に失敗しました');
                    } else {
                        alert('サーバーとの通信に失敗しました');
                    }

                    // エラー時はボタンを元に戻す
                    submitBtn.prop('disabled', false).html('ボードを作成');
                }
                return false;
            };

            xhr.onerror = function() {
                console.error('エラー: ネットワークエラーが発生しました'); // デバッグ用
                alert('サーバーとの通信に失敗しました');
                submitBtn.prop('disabled', false).html('ボードを作成');
            };

            xhr.send(JSON.stringify(formData));
            return false;
        }
        return false;
    }
</script>
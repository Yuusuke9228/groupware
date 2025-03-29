<div class="container-fluid mt-3">
    <div class="row mb-3">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_PATH; ?>/task">タスク管理</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo BASE_PATH; ?>/task/board/<?php echo $board['id']; ?>"><?php echo htmlspecialchars($board['name']); ?></a></li>
                    <li class="breadcrumb-item active" aria-current="page">新規カード作成</li>
                </ol>
            </nav>
            <h4 class="mb-3">新規カード作成</h4>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form id="createCardForm">
                        <input type="hidden" name="list_id" value="<?php echo $list['id']; ?>">

                        <div class="mb-3">
                            <label for="cardTitle" class="form-label">タイトル <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="cardTitle" name="title" required>
                        </div>

                        <div class="mb-3">
                            <label for="cardDescription" class="form-label">説明</label>
                            <textarea class="form-control" id="cardDescription" name="description" rows="4"></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="cardStatus" class="form-label">ステータス</label>
                                <select class="form-select" id="cardStatus" name="status">
                                    <option value="not_started" selected>未対応</option>
                                    <option value="in_progress">処理中</option>
                                    <option value="completed">完了</option>
                                    <option value="deferred">保留</option>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="cardPriority" class="form-label">優先度</label>
                                <select class="form-select" id="cardPriority" name="priority">
                                    <option value="highest">最高</option>
                                    <option value="high">高</option>
                                    <option value="normal" selected>通常</option>
                                    <option value="low">低</option>
                                    <option value="lowest">最低</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="cardDueDate" class="form-label">期限日</label>
                                <input type="date" class="form-control" id="cardDueDate" name="due_date">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="cardProgress" class="form-label">進捗率</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="cardProgress" name="progress" min="0" max="100" value="0">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="cardList" class="form-label">リスト</label>
                            <select class="form-select" id="cardList" name="list_id">
                                <?php foreach ($lists as $l): ?>
                                    <option value="<?php echo $l['id']; ?>" <?php echo $list['id'] == $l['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($l['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="cardAssignees" class="form-label">担当者</label>
                            <select class="form-select select2-users" id="cardAssignees" name="assignees[]" multiple>
                                <?php foreach ($members as $member): ?>
                                    <option value="<?php echo $member['user_id']; ?>" <?php echo $member['user_id'] == $auth->id() ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($member['display_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="cardLabels" class="form-label">ラベル</label>
                            <select class="form-select select2-labels" id="cardLabels" name="labels[]" multiple>
                                <?php foreach ($labels as $label): ?>
                                    <option value="<?php echo $label['id']; ?>" data-color="<?php echo $label['color']; ?>">
                                        <?php echo htmlspecialchars($label['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="cardColor" class="form-label">カード色</label>
                            <input type="color" class="form-control form-control-color" id="cardColor" name="color" value="#ffffff">
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <a href="<?php echo BASE_PATH; ?>/task/board/<?php echo $board['id']; ?>" class="btn btn-secondary me-2">キャンセル</a>
                            <button type="submit" class="btn btn-primary">作成</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">ボード情報</h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">ボード名</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($board['name']); ?></dd>

                        <dt class="col-sm-4">リスト名</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($list['name']); ?></dd>

                        <dt class="col-sm-4">所有者タイプ</dt>
                        <dd class="col-sm-8">
                            <?php
                            switch ($board['owner_type']) {
                                case 'user':
                                    echo '<span class="badge bg-secondary">個人</span>';
                                    break;
                                case 'team':
                                    echo '<span class="badge bg-primary">チーム</span>';
                                    break;
                                case 'organization':
                                    echo '<span class="badge bg-info">組織</span>';
                                    break;
                                default:
                                    echo $board['owner_type'];
                            }
                            ?>
                        </dd>
                    </dl>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0">ヘルプ</h5>
                </div>
                <div class="card-body">
                    <h6>カードの使い方</h6>
                    <ul>
                        <li>タスクのタイトルは具体的に記述しましょう</li>
                        <li>説明には詳細な情報や参考リンクなどを記入できます</li>
                        <li>期限日は実現可能な日程を設定しましょう</li>
                        <li>担当者を明確にすることで責任を明確にできます</li>
                        <li>ラベルを使ってタスクを分類すると管理がしやすくなります</li>
                    </ul>

                    <hr>

                    <h6>ステータスの種類</h6>
                    <ul class="list-unstyled">
                        <li><span class="badge bg-secondary me-1">未対応</span> これから始めるタスク</li>
                        <li><span class="badge bg-primary me-1">処理中</span> 現在作業中のタスク</li>
                        <li><span class="badge bg-success me-1">完了</span> 完了したタスク</li>
                        <li><span class="badge bg-warning me-1">保留</span> 一時的に中断しているタスク</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Select2の初期化
        if ($.fn.select2) {
            $('#cardAssignees').select2({
                placeholder: '担当者を選択',
                width: '100%'
            });

            $('#cardLabels').select2({
                placeholder: 'ラベルを選択',
                width: '100%',
                templateResult: function(label) {
                    if (!label.id) return label.text;

                    const color = $(label.element).data('color') || '#cccccc';
                    return $(`<span><i class="fas fa-tag" style="color: ${color}"></i> ${label.text}</span>`);
                }
            });
        }

        // フォーム送信時の処理
        $('#createCardForm').on('submit', function(e) {
            e.preventDefault();

            const form = $(this);
            const formData = {};

            // フォームデータを収集
            $.each(form.serializeArray(), function(_, field) {
                if (field.name.endsWith('[]')) {
                    const key = field.name.slice(0, -2);
                    if (!formData[key]) formData[key] = [];
                    formData[key].push(field.value);
                } else {
                    formData[field.name] = field.value;
                }
            });

            // バリデーション
            if (!formData.title.trim()) {
                toastr.error('タイトルを入力してください');
                return false;
            }

            // 送信ボタンを無効化
            const submitBtn = form.find('button[type="submit"]');
            submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> 作成中...');

            // API呼び出し
            $.ajax({
                url: BASE_PATH + '/api/task/cards',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(formData),
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message || 'カードを作成しました');

                        // ボードページにリダイレクト
                        setTimeout(function() {
                            window.location.href = BASE_PATH + '/task/board/<?php echo $board['id']; ?>';
                        }, 1000);
                    } else {
                        toastr.error(response.error || 'カードの作成に失敗しました');
                        submitBtn.prop('disabled', false).html('作成');
                    }
                },
                error: function() {
                    toastr.error('サーバーとの通信に失敗しました');
                    submitBtn.prop('disabled', false).html('作成');
                }
            });

            return false;
        });
    });
</script>
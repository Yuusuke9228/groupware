<?php
// 編集モードかどうかを判定
$isEdit = isset($report);
$formTitle = $isEdit ? '日報編集' : '日報作成';
$formAction = $isEdit ? BASE_PATH . "/api/daily-report/{$report['id']}" : BASE_PATH . "/api/daily-report";
$submitButtonText = $isEdit ? '更新する' : '保存する';

// 日報の日付
$reportDate = $isEdit ? $report['report_date'] : ($date ?? date('Y-m-d'));

// 下書きかどうか
$isDraft = $isEdit ? ($report['status'] == 'draft') : false;

// タグを取得
$reportTags = [];
if ($isEdit && !empty($report['tags'])) {
    foreach ($report['tags'] as $tag) {
        $reportTags[] = $tag['name'];
    }
}

// 権限設定
$permissions = [];
if ($isEdit && !empty($report['permissions'])) {
    foreach ($report['permissions'] as $permission) {
        $permissions[] = [
            'type' => $permission['target_type'],
            'id' => $permission['target_id']
        ];
    }
}

// スケジュールを取得
$selectedSchedules = [];
if ($isEdit && !empty($report['schedules'])) {
    foreach ($report['schedules'] as $schedule) {
        $selectedSchedules[] = $schedule['id'];
    }
}

// タスクを取得
$selectedTasks = [];
if ($isEdit && !empty($report['tasks'])) {
    foreach ($report['tasks'] as $task) {
        $selectedTasks[] = $task['id'];
    }
}
?>

<div class="container-fluid pt-4 px-4">
    <div class="row g-4">
        <div class="col-12">
            <div class="bg-light rounded p-4 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="mb-0"><?= $formTitle ?></h3>
                    <div>
                        <a href="<?= BASE_PATH ?>/daily-report" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>キャンセル
                        </a>
                    </div>
                </div>

                <form id="reportForm" method="POST" action="<?= $formAction ?>">
                    <!-- 日付 -->
                    <div class="mb-3">
                        <label for="report_date" class="form-label">日付 <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="report_date" name="report_date"
                            value="<?= $reportDate ?>" required>
                        <div class="invalid-feedback"></div>
                    </div>

                    <!-- タイトル -->
                    <div class="mb-3">
                        <label for="title" class="form-label">タイトル <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title"
                            value="<?= $isEdit ? htmlspecialchars($report['title']) : ($template ? htmlspecialchars($template['title']) : '') ?>"
                            required maxlength="100">
                        <div class="invalid-feedback"></div>
                    </div>

                    <!-- 内容 -->
                    <div class="mb-3">
                        <label for="content" class="form-label">内容 <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="content" name="content" rows="10" required><?= $isEdit ? htmlspecialchars($report['content']) : ($template ? htmlspecialchars($template['content']) : '') ?></textarea>
                        <div class="invalid-feedback"></div>
                    </div>

                    <!-- 追加情報（アコーディオンで表示） -->
                    <div class="accordion mb-4" id="reportAccordion">
                        <!-- タグ設定 -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingTags">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#collapseTags" aria-expanded="false" aria-controls="collapseTags">
                                    タグ設定
                                </button>
                            </h2>
                            <div id="collapseTags" class="accordion-collapse collapse" aria-labelledby="headingTags"
                                data-bs-parent="#reportAccordion">
                                <div class="accordion-body">
                                    <div class="mb-3">
                                        <label for="tags" class="form-label">タグ（カンマ区切りで入力）</label>
                                        <input type="text" class="form-control" id="tags" name="tags"
                                            value="<?= implode(',', $reportTags) ?>">
                                        <div class="form-text">複数のタグを付ける場合はカンマ（,）で区切ってください</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 公開範囲設定 -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingPermissions">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#collapsePermissions" aria-expanded="false" aria-controls="collapsePermissions">
                                    公開範囲設定
                                </button>
                            </h2>
                            <div id="collapsePermissions" class="accordion-collapse collapse" aria-labelledby="headingPermissions"
                                data-bs-parent="#reportAccordion">
                                <div class="accordion-body">
                                    <div class="mb-3">
                                        <label class="form-label">ユーザー</label>
                                        <select class="form-select select2" id="user_permissions" multiple>
                                            <?php foreach ($users as $u): ?>
                                                <?php if ($u['id'] != $this->auth->id()): ?>
                                                    <?php
                                                    $selected = '';
                                                    if ($isEdit) {
                                                        foreach ($permissions as $p) {
                                                            if ($p['type'] == 'user' && $p['id'] == $u['id']) {
                                                                $selected = 'selected';
                                                                break;
                                                            }
                                                        }
                                                    }
                                                    ?>
                                                    <option value="user:<?= $u['id'] ?>" <?= $selected ?>><?= htmlspecialchars($u['display_name']) ?></option>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">組織</label>
                                        <select class="form-select select2" id="organization_permissions" multiple>
                                            <?php foreach ($organizations as $org): ?>
                                                <?php
                                                $selected = '';
                                                if ($isEdit) {
                                                    foreach ($permissions as $p) {
                                                        if ($p['type'] == 'organization' && $p['id'] == $org['id']) {
                                                            $selected = 'selected';
                                                            break;
                                                        }
                                                    }
                                                }
                                                ?>
                                                <option value="organization:<?= $org['id'] ?>" <?= $selected ?>><?= htmlspecialchars($org['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <!-- 隠しフィールドに権限情報を格納 -->
                                    <input type="hidden" id="permissions" name="permissions" value="">
                                </div>
                            </div>
                        </div>

                        <!-- スケジュール関連付け -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingSchedules">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#collapseSchedules" aria-expanded="false" aria-controls="collapseSchedules">
                                    スケジュール関連付け
                                </button>
                            </h2>
                            <div id="collapseSchedules" class="accordion-collapse collapse" aria-labelledby="headingSchedules"
                                data-bs-parent="#reportAccordion">
                                <div class="accordion-body">
                                    <?php if (empty($schedules)): ?>
                                        <p class="text-center">関連付け可能なスケジュールがありません</p>
                                    <?php else: ?>
                                        <div class="list-group mb-3">
                                            <?php foreach ($schedules as $schedule): ?>
                                                <label class="list-group-item">
                                                    <input class="form-check-input me-1" type="checkbox" name="schedules[]"
                                                        value="<?= $schedule['id'] ?>"
                                                        <?= in_array($schedule['id'], $selectedSchedules) ? 'checked' : '' ?>>
                                                    <div>
                                                        <strong><?= htmlspecialchars($schedule['title']) ?></strong><br>
                                                        <small class="text-muted">
                                                            <?= date('H:i', strtotime($schedule['start_time'])) ?> -
                                                            <?= date('H:i', strtotime($schedule['end_time'])) ?>
                                                        </small>
                                                    </div>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- タスク関連付け -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingTasks">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#collapseTasks" aria-expanded="false" aria-controls="collapseTasks">
                                    タスク関連付け
                                </button>
                            </h2>
                            <div id="collapseTasks" class="accordion-collapse collapse" aria-labelledby="headingTasks"
                                data-bs-parent="#reportAccordion">
                                <div class="accordion-body">
                                    <?php if (empty($tasks)): ?>
                                        <p class="text-center">関連付け可能な完了済みタスクがありません</p>
                                    <?php else: ?>
                                        <div class="list-group mb-3">
                                            <?php foreach ($tasks as $task): ?>
                                                <label class="list-group-item">
                                                    <input class="form-check-input me-1" type="checkbox" name="tasks[]"
                                                        value="<?= $task['id'] ?>"
                                                        <?= in_array($task['id'], $selectedTasks) ? 'checked' : '' ?>>
                                                    <div>
                                                        <strong><?= htmlspecialchars($task['title']) ?></strong><br>
                                                        <small class="text-muted"><?= htmlspecialchars($task['board_name']) ?> / <?= htmlspecialchars($task['list_name']) ?></small>
                                                    </div>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 下書き設定 -->
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="is_draft" name="is_draft" <?= $isDraft ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_draft">
                            下書きとして保存する
                        </label>
                    </div>

                    <!-- ボタン -->
                    <div class="text-center mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i><?= $submitButtonText ?>
                        </button>
                        <?php if ($isEdit): ?>
                            <button type="button" class="btn btn-danger ms-2" id="deleteButton" data-id="<?= $report['id'] ?>">
                                <i class="fas fa-trash me-2"></i>削除
                            </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Select2 初期化
        $('.select2').select2({
            placeholder: '選択してください',
            width: '100%'
        });

        // フォーム送信処理
        $('#reportForm').off('submit').on('submit', function(e) {
            e.preventDefault();

            // バリデーションリセット
            $(this).find('.is-invalid').removeClass('is-invalid');
            $(this).find('.invalid-feedback').text('');

            // 公開範囲設定の整形
            const permissions = [];

            // ユーザー権限
            $('#user_permissions option:selected').each(function() {
                const value = $(this).val();
                const [type, id] = value.split(':');
                permissions.push({
                    type: type,
                    id: parseInt(id)
                });
            });

            // 組織権限
            $('#organization_permissions option:selected').each(function() {
                const value = $(this).val();
                const [type, id] = value.split(':');
                permissions.push({
                    type: type,
                    id: parseInt(id)
                });
            });

            // JSONに変換して隠しフィールドに格納
            $('#permissions').val(JSON.stringify(permissions));

            // タグの処理
            let tags = $('#tags').val().trim();
            if (tags) {
                tags = tags.split(',').map(tag => tag.trim()).filter(tag => tag);
            } else {
                tags = [];
            }

            // スケジュールを配列に変換
            const schedules = [];
            $('input[name="schedules[]"]:checked').each(function() {
                schedules.push(parseInt($(this).val()));
            });

            // タスクを配列に変換
            const tasks = [];
            $('input[name="tasks[]"]:checked').each(function() {
                tasks.push(parseInt($(this).val()));
            });

            // 下書きフラグ
            const isDraft = $('#is_draft').is(':checked');

            // フォームデータの作成
            const formData = {
                report_date: $('#report_date').val(),
                title: $('#title').val(),
                content: $('#content').val(),
                tags: tags,
                permissions: permissions,
                schedules: schedules,
                tasks: tasks,
                status: isDraft ? 'draft' : 'published'
            };

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
                    if (response.success) {
                        // 成功時は詳細ページにリダイレクト
                        window.location.href = response.data.redirect;
                    } else {
                        // エラーメッセージを表示
                        alert(response.error || 'エラーが発生しました');

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
                    alert('ネットワークエラーが発生しました');
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
            if (confirm('本当にこの日報を削除しますか？この操作は元に戻せません。')) {
                const reportId = $(this).data('id');

                // 削除API呼び出し
                $.ajax({
                    url: `${BASE_PATH}/api/daily-report/${reportId}`,
                    type: 'DELETE',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // 成功時は一覧ページにリダイレクト
                            window.location.href = response.data.redirect;
                        } else {
                            // エラーメッセージを表示
                            alert(response.error || 'エラーが発生しました');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error:', error);
                        alert('ネットワークエラーが発生しました');
                    }
                });
            }
        });
    });
</script>
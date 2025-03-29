<div class="container-fluid mt-3">
    <div class="row mb-3">
        <div class="col-md-8">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_PATH; ?>/task">タスク管理</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo BASE_PATH; ?>/task/teams">チーム管理</a></li>
                    <li class="breadcrumb-item active" aria-current="page">チーム編集</li>
                </ol>
            </nav>
            <div class="d-flex justify-content-between align-items-center">
                <h4>チーム編集</h4>
                <a href="<?php echo BASE_PATH; ?>/task/team/<?php echo $team['id']; ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> チーム詳細へ戻る
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form id="editTeamForm" method="post">
                        <input type="hidden" name="id" value="<?php echo $team['id']; ?>">
                        <div class="mb-3">
                            <label for="teamName" class="form-label">チーム名 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="teamName" name="name" value="<?php echo htmlspecialchars($team['name']); ?>" required>
                            <div class="invalid-feedback">チーム名を入力してください</div>
                        </div>

                        <div class="mb-3">
                            <label for="teamDescription" class="form-label">説明</label>
                            <textarea class="form-control" id="teamDescription" name="description" rows="3"><?php echo htmlspecialchars($team['description'] ?? ''); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">メンバー</label>
                            <div class="card">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="mb-0">チームメンバー</h6>
                                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addMemberModal">
                                            <i class="fas fa-user-plus"></i> メンバー追加
                                        </button>
                                    </div>

                                    <div class="table-responsive">
                                        <table class="table table-sm" id="team-members-table">
                                            <thead>
                                                <tr>
                                                    <th>ユーザー</th>
                                                    <th>役割</th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($members)): ?>
                                                    <tr id="no-members-row">
                                                        <td colspan="3" class="text-center">メンバーがいません</td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($members as $member): ?>
                                                        <tr id="member-row-<?php echo $member['user_id']; ?>">
                                                            <td>
                                                                <input type="hidden" name="members[]" value="<?php echo $member['user_id']; ?>">
                                                                <input type="hidden" name="member_roles[<?php echo $member['user_id']; ?>]" id="memberRole-<?php echo $member['user_id']; ?>" value="<?php echo $member['role']; ?>">
                                                                <?php echo htmlspecialchars($member['display_name']); ?>
                                                                <?php if ($member['user_id'] == $auth->id()): ?>
                                                                    <span class="badge bg-info ms-1">あなた</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <select class="form-select form-select-sm member-role-select" data-user-id="<?php echo $member['user_id']; ?>" <?php echo $member['user_id'] == $auth->id() ? 'disabled' : ''; ?>>
                                                                    <option value="member" <?php echo $member['role'] === 'member' ? 'selected' : ''; ?>>メンバー</option>
                                                                    <option value="admin" <?php echo $member['role'] === 'admin' ? 'selected' : ''; ?>>管理者</option>
                                                                </select>
                                                            </td>
                                                            <td>
                                                                <?php if ($member['user_id'] != $auth->id()): ?>
                                                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="Task.removeTeamMember(<?php echo $member['user_id']; ?>)">
                                                                        <i class="fas fa-times"></i>
                                                                    </button>
                                                                <?php endif; ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-between">
                            <button type="button" class="btn btn-danger" id="deleteTeamBtn">
                                <i class="fas fa-trash"></i> チーム削除
                            </button>
                            <div>
                                <a href="<?php echo BASE_PATH; ?>/task/team/<?php echo $team['id']; ?>" class="btn btn-secondary me-md-2">キャンセル</a>
                                <button type="submit" class="btn btn-primary">更新</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0">チーム情報</h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">作成者</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($team['creator_name'] ?? '不明'); ?></dd>

                        <dt class="col-sm-4">作成日</dt>
                        <dd class="col-sm-8"><?php echo date('Y年m月d日', strtotime($team['created_at'])); ?></dd>

                        <dt class="col-sm-4">メンバー</dt>
                        <dd class="col-sm-8"><?php echo count($members); ?>人</dd>
                    </dl>
                </div>
            </div>

            <div class="card shadow-sm mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">チーム機能について</h5>
                </div>
                <div class="card-body">
                    <p>チームは、組織の枠を超えて特定のプロジェクトやタスクに取り組むためのグループです。</p>
                    <ul>
                        <li>チームにはタスクボードを作成できます</li>
                        <li>複数の組織から任意のメンバーを追加できます</li>
                        <li>チーム内でタスクの割り当てや進捗管理ができます</li>
                    </ul>
                    <p>効率的なコラボレーションのために、最適なメンバー構成を考えましょう！</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- メンバー追加モーダル -->
<div class="modal fade" id="addMemberModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">メンバー追加</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="memberSelect" class="form-label">ユーザーを選択</label>
                    <select class="form-select" id="memberSelect">
                        <option value="">選択してください</option>
                        <?php foreach ($users as $user): ?>
                            <?php
                            // 既にメンバーに追加されているかチェック
                            $isAlreadyMember = false;
                            foreach ($members as $member) {
                                if ($member['user_id'] == $user['id']) {
                                    $isAlreadyMember = true;
                                    break;
                                }
                            }
                            if (!$isAlreadyMember):
                            ?>
                                <option value="<?php echo $user['id']; ?>" data-name="<?php echo htmlspecialchars($user['display_name']); ?>">
                                    <?php echo htmlspecialchars($user['display_name']); ?> (<?php echo htmlspecialchars($user['email']); ?>)
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="memberRole" class="form-label">役割</label>
                    <select class="form-select" id="memberRole">
                        <option value="member">メンバー</option>
                        <option value="admin">管理者</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                <button type="button" class="btn btn-primary" id="addMemberBtn">追加</button>
            </div>
        </div>
    </div>
</div>

<!-- 削除確認モーダル -->
<div class="modal fade" id="deleteTeamModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">チーム削除の確認</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>チーム「<?php echo htmlspecialchars($team['name']); ?>」を削除しますか？</p>
                <div class="alert alert-danger">
                    <p class="mb-1">この操作は取り消せません。以下のデータも削除されます：</p>
                    <ul class="mb-0">
                        <li>チームのすべてのタスクボード</li>
                        <li>チームに関連するすべてのタスク</li>
                        <li>チームのメンバーシップ情報</li>
                    </ul>
                </div>
                <div class="form-check mt-3">
                    <input class="form-check-input" type="checkbox" id="confirmDelete">
                    <label class="form-check-label" for="confirmDelete">
                        チームの削除による影響を理解し、削除を実行します
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn" disabled>削除</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // メンバー追加ボタンのイベント
        document.getElementById('addMemberBtn').addEventListener('click', function() {
            const memberSelect = document.getElementById('memberSelect');
            const memberRole = document.getElementById('memberRole');

            if (!memberSelect.value) {
                alert('ユーザーを選択してください');
                return;
            }

            const userId = memberSelect.value;
            const userName = memberSelect.options[memberSelect.selectedIndex].dataset.name;
            const role = memberRole.value;

            // すでに追加されているかチェック
            const existingMember = document.querySelector(`input[name="members[]"][value="${userId}"]`);
            if (existingMember) {
                alert('このユーザーはすでに追加されています');
                return;
            }

            // メンバーリストに追加
            Task.addTeamMember(userId, userName, role);

            // モーダルを閉じる
            const modal = bootstrap.Modal.getInstance(document.getElementById('addMemberModal'));
            modal.hide();
        });

        // フォーム送信時の処理
        document.getElementById('editTeamForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const form = this;
            const submitBtn = form.querySelector('button[type="submit"]');

            // 名前の入力チェック
            const nameInput = form.querySelector('input[name="name"]');
            if (!nameInput.value.trim()) {
                alert('チーム名を入力してください');
                nameInput.focus();
                return;
            }

            const formData = new FormData(form);
            const jsonData = {};

            // FormDataをJSONに変換
            for (const [key, value] of formData.entries()) {
                if (key.includes('[]')) {
                    const cleanKey = key.replace('[]', '');
                    if (!jsonData[cleanKey]) {
                        jsonData[cleanKey] = [];
                    }
                    jsonData[cleanKey].push(value);
                } else if (key.includes('[') && key.includes(']')) {
                    const matches = key.match(/([^\[]+)\[([^\]]+)\]/);
                    if (matches && matches.length === 3) {
                        const objKey = matches[1];
                        const objSubKey = matches[2];
                        if (!jsonData[objKey]) {
                            jsonData[objKey] = {};
                        }
                        jsonData[objKey][objSubKey] = value;
                    }
                } else {
                    jsonData[key] = value;
                }
            }

            // ボタンを無効化
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> 処理中...';

            // APIリクエスト
            fetch(`${BASE_PATH}/api/task/teams/${jsonData.id}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(jsonData)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // 成功メッセージを表示
                        toastr.success(data.message || 'チームを更新しました');

                        // リダイレクト
                        if (data.redirect) {
                            window.location.href = data.redirect;
                        }
                    } else {
                        // エラーメッセージを表示
                        toastr.error(data.error || 'エラーが発生しました');

                        // ボタンを元に戻す
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '更新';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    toastr.error('通信エラーが発生しました');

                    // ボタンを元に戻す
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '更新';
                });
        });

        // チーム削除ボタンのイベント
        document.getElementById('deleteTeamBtn').addEventListener('click', function() {
            document.getElementById('deleteTeamModal').classList.add('show');
            document.getElementById('deleteTeamModal').style.display = 'block';
            document.getElementById('deleteTeamModal').setAttribute('aria-modal', 'true');
            document.querySelector('.modal-backdrop').classList.add('show');
        });

        // 削除確認チェックボックスのイベント
        document.getElementById('confirmDelete').addEventListener('change', function() {
            document.getElementById('confirmDeleteBtn').disabled = !this.checked;
        });

        // 削除確定ボタンのイベント
        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            const teamId = <?php echo $team['id']; ?>;

            // ボタンを無効化
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> 処理中...';

            // APIリクエスト
            fetch(`${BASE_PATH}/api/task/teams/${teamId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // 成功メッセージを表示
                        toastr.success(data.message || 'チームを削除しました');

                        // リダイレクト
                        if (data.redirect) {
                            window.location.href = data.redirect;
                        } else {
                            window.location.href = BASE_PATH + '/task/teams';
                        }
                    } else {
                        // エラーメッセージを表示
                        toastr.error(data.error || 'エラーが発生しました');

                        // ボタンを元に戻す
                        this.disabled = false;
                        this.innerHTML = '削除';

                        // モーダルを閉じる
                        const modal = bootstrap.Modal.getInstance(document.getElementById('deleteTeamModal'));
                        if (modal) modal.hide();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    toastr.error('通信エラーが発生しました');

                    // ボタンを元に戻す
                    this.disabled = false;
                    this.innerHTML = '削除';

                    // モーダルを閉じる
                    const modal = bootstrap.Modal.getInstance(document.getElementById('deleteTeamModal'));
                    if (modal) modal.hide();
                });
        });
    });
</script>
<div class="container-fluid mt-3">
    <div class="row mb-3">
        <div class="col-md-8">
            <div class="d-flex justify-content-between align-items-center">
                <h4>チーム作成</h4>
                <a href="<?php echo BASE_PATH; ?>/task/teams" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> チーム一覧へ戻る
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form id="createTeamForm" method="post" action="<?php echo BASE_PATH; ?>/api/task/teams">
                        <div class="mb-3">
                            <label for="teamName" class="form-label">チーム名 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="teamName" name="name" required>
                            <div class="invalid-feedback">チーム名を入力してください</div>
                        </div>

                        <div class="mb-3">
                            <label for="teamDescription" class="form-label">説明</label>
                            <textarea class="form-control" id="teamDescription" name="description" rows="3"></textarea>
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

                                    <!-- 自分は必ず管理者として追加 -->
                                    <div class="list-group" id="membersList">
                                        <div class="list-group-item d-flex justify-content-between align-items-center">
                                            <div>
                                                <input type="hidden" name="members[]" value="<?php echo $auth->id(); ?>">
                                                <input type="hidden" name="member_roles[<?php echo $auth->id(); ?>]" value="admin">
                                                <strong><?php echo $auth->user()['display_name']; ?></strong>
                                                <span class="badge bg-primary ms-2">管理者</span>
                                            </div>
                                            <div class="text-muted">
                                                <small>あなた</small>
                                            </div>
                                        </div>
                                    </div>

                                    <div id="noMembersMessage" class="text-center text-muted py-3 d-none">
                                        メンバーが追加されていません
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="<?php echo BASE_PATH; ?>/task/teams" class="btn btn-secondary me-md-2">キャンセル</a>
                            <button type="submit" class="btn btn-primary">チームを作成</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm">
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
                            <?php if ($user['id'] != $auth->id()): ?>
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
            const roleText = role === 'admin' ? '管理者' : 'メンバー';
            const roleBadgeClass = role === 'admin' ? 'bg-primary' : 'bg-secondary';

            // すでに追加されているかチェック
            const existingMember = document.querySelector(`input[name="members[]"][value="${userId}"]`);
            if (existingMember) {
                alert('このユーザーはすでに追加されています');
                return;
            }

            // メンバーリストに追加
            const membersList = document.getElementById('membersList');

            const listItem = document.createElement('div');
            listItem.className = 'list-group-item d-flex justify-content-between align-items-center';
            listItem.innerHTML = `
            <div>
                <input type="hidden" name="members[]" value="${userId}">
                <input type="hidden" name="member_roles[${userId}]" value="${role}">
                <strong>${userName}</strong>
                <span class="badge ${roleBadgeClass} ms-2">${roleText}</span>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger remove-member" data-id="${userId}">
                <i class="fas fa-times"></i>
            </button>
        `;

            membersList.appendChild(listItem);

            // メッセージの表示/非表示
            document.getElementById('noMembersMessage').classList.add('d-none');

            // モーダルを閉じる
            bootstrap.Modal.getInstance(document.getElementById('addMemberModal')).hide();

            // セレクトをリセット
            memberSelect.value = '';
            memberRole.value = 'member';
        });

        // メンバー削除ボタンのイベント（動的に追加された要素）
        document.addEventListener('click', function(e) {
            if (e.target.closest('.remove-member')) {
                const button = e.target.closest('.remove-member');
                const listItem = button.closest('.list-group-item');

                listItem.remove();

                // メンバーリストが空かチェック
                const membersList = document.getElementById('membersList');
                if (membersList.children.length <= 1) { // 自分以外いない
                    document.getElementById('noMembersMessage').classList.remove('d-none');
                }
            }
        });

        // フォーム送信時の処理
        document.getElementById('createTeamForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const form = this;
            const submitBtn = form.querySelector('button[type="submit"]');
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
            fetch(form.action, {
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
                        alert(data.message || 'チームを作成しました');

                        // リダイレクト
                        if (data.redirect) {
                            window.location.href = data.redirect;
                        }
                    } else {
                        // エラーメッセージを表示
                        alert(data.error || 'エラーが発生しました');

                        // ボタンを元に戻す
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = 'チームを作成';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('通信エラーが発生しました');

                    // ボタンを元に戻す
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'チームを作成';
                });
        });
    });
</script>
<?php
// views/task/edit_board.php - タスクボード編集画面
?>
<div class="container-fluid mt-3">
    <div class="row mb-3">
        <div class="col-md-8">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_PATH; ?>/task">タスク管理</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo BASE_PATH; ?>/task/board/<?php echo $board['id']; ?>"><?php echo htmlspecialchars($board['name']); ?></a></li>
                    <li class="breadcrumb-item active" aria-current="page">ボード編集</li>
                </ol>
            </nav>
            <h4 class="mb-3">タスクボード編集</h4>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0">ボード情報</h5>
                </div>
                <div class="card-body">
                    <form id="boardForm" method="POST">
                        <input type="hidden" id="boardId" name="board_id" value="<?php echo $board['id']; ?>">

                        <div class="mb-3">
                            <label for="boardName" class="form-label">ボード名 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="boardName" name="name" value="<?php echo htmlspecialchars($board['name']); ?>" required>
                            <div class="invalid-feedback">ボード名を入力してください。</div>
                        </div>

                        <div class="mb-3">
                            <label for="boardDescription" class="form-label">説明</label>
                            <textarea class="form-control" id="boardDescription" name="description" rows="3"><?php echo htmlspecialchars($board['description'] ?? ''); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="backgroundColor" class="form-label">背景色</label>
                            <input type="color" class="form-control form-control-color" id="backgroundColor" name="background_color" value="<?php echo $board['background_color'] ?? '#f0f2f5'; ?>">
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="isPublic" name="is_public" <?php echo $board['is_public'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="isPublic">公開ボード（すべてのユーザーが閲覧可能）</label>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <button type="button" class="btn btn-danger" id="deleteBoard">
                                <i class="fas fa-trash"></i> ボード削除
                            </button>
                            <div>
                                <a href="<?php echo BASE_PATH; ?>/task/board/<?php echo $board['id']; ?>" class="btn btn-secondary me-2">キャンセル</a>
                                <button type="submit" class="btn btn-primary">更新</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ボードメンバー管理 -->
            <div class="card shadow-sm mt-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">メンバー管理</h5>
                    <?php if ($canEdit): ?>
                        <button type="button" class="btn btn-sm btn-primary" id="showAddMemberModal">
                            <i class="fas fa-user-plus"></i> メンバー追加
                        </button>
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>ユーザー</th>
                                    <th>役割</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($members)): ?>
                                    <tr>
                                        <td colspan="3" class="text-center py-3">メンバーがいません</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($members as $member): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar me-2"><?php echo mb_substr($member['display_name'], 0, 1); ?></div>
                                                    <div>
                                                        <div><?php echo htmlspecialchars($member['display_name']); ?></div>
                                                        <small class="text-muted"><?php echo htmlspecialchars($member['email']); ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <?php
                                                $roleClass = '';
                                                $roleName = '';
                                                switch ($member['role']) {
                                                    case 'admin':
                                                        $roleClass = 'bg-primary';
                                                        $roleName = '管理者';
                                                        break;
                                                    case 'editor':
                                                        $roleClass = 'bg-success';
                                                        $roleName = '編集者';
                                                        break;
                                                    default:
                                                        $roleClass = 'bg-secondary';
                                                        $roleName = '閲覧者';
                                                        break;
                                                }
                                                ?>
                                                <span class="badge <?php echo $roleClass; ?>"><?php echo $roleName; ?></span>
                                            </td>
                                            <td class="text-end">
                                                <?php if ($canEdit && $member['user_id'] != $this->auth->id()): ?>
                                                    <div class="btn-group btn-group-sm">
                                                        <button type="button" class="btn btn-outline-primary change-role"
                                                            data-user-id="<?php echo $member['user_id']; ?>"
                                                            data-user-name="<?php echo htmlspecialchars($member['display_name']); ?>"
                                                            data-role="<?php echo $member['role']; ?>">
                                                            <i class="fas fa-user-cog"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-outline-danger remove-member"
                                                            data-user-id="<?php echo $member['user_id']; ?>"
                                                            data-user-name="<?php echo htmlspecialchars($member['display_name']); ?>">
                                                            <i class="fas fa-user-times"></i>
                                                        </button>
                                                    </div>
                                                <?php elseif ($member['user_id'] == $this->auth->id()): ?>
                                                    <span class="badge bg-info">あなた</span>
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

        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">ボード情報</h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">作成日</dt>
                        <dd class="col-sm-8"><?php echo date('Y年m月d日', strtotime($board['created_at'])); ?></dd>

                        <dt class="col-sm-4">更新日</dt>
                        <dd class="col-sm-8"><?php echo date('Y年m月d日', strtotime($board['updated_at'])); ?></dd>

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

                        <?php if ($board['owner_type'] != 'user'): ?>
                            <dt class="col-sm-4">所有者</dt>
                            <dd class="col-sm-8">
                                <?php
                                if ($board['owner_type'] == 'team') {
                                    foreach ($teams as $team) {
                                        if ($team['id'] == $board['owner_id']) {
                                            echo htmlspecialchars($team['name']);
                                            break;
                                        }
                                    }
                                } else if ($board['owner_type'] == 'organization') {
                                    foreach ($organizations as $org) {
                                        if ($org['id'] == $board['owner_id']) {
                                            echo htmlspecialchars($org['name']);
                                            break;
                                        }
                                    }
                                }
                                ?>
                            </dd>
                        <?php endif; ?>
                    </dl>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">ヘルプ</h5>
                </div>
                <div class="card-body">
                    <h6>メンバーの役割について</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <span class="badge bg-primary me-1">管理者</span>
                            ボードの設定変更、メンバー管理、すべてのカード編集が可能
                        </li>
                        <li class="mb-2">
                            <span class="badge bg-success me-1">編集者</span>
                            カードの作成・編集・移動が可能、ボード設定の変更は不可
                        </li>
                        <li>
                            <span class="badge bg-secondary me-1">閲覧者</span>
                            ボードとカードの閲覧のみ可能
                        </li>
                    </ul>

                    <hr>

                    <h6>ボード削除について</h6>
                    <p class="small text-danger mb-0">
                        ボードを削除すると、含まれるすべてのリスト、カード、コメント、添付ファイルなどのデータが完全に削除されます。この操作は元に戻すことができません。
                    </p>
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
                <form id="addMemberForm">
                    <div class="mb-3">
                        <label for="memberUser" class="form-label">ユーザーを選択</label>
                        <select class="form-select" id="memberUser" name="user_id" required>
                            <option value="">選択してください</option>
                            <!-- 動的に読み込まれる -->
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="memberRole" class="form-label">役割</label>
                        <select class="form-select" id="memberRole" name="role">
                            <option value="viewer">閲覧者</option>
                            <option value="editor">編集者</option>
                            <option value="admin">管理者</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                <button type="button" class="btn btn-primary" id="addMember">追加</button>
            </div>
        </div>
    </div>
</div>

<!-- 役割変更モーダル -->
<div class="modal fade" id="changeRoleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">役割変更</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="changeRoleForm">
                    <input type="hidden" id="changeRoleUserId" name="user_id">
                    <p>メンバー「<span id="changeRoleUserName"></span>」の役割を変更します。</p>
                    <div class="mb-3">
                        <label for="newRole" class="form-label">新しい役割</label>
                        <select class="form-select" id="newRole" name="role">
                            <option value="viewer">閲覧者</option>
                            <option value="editor">編集者</option>
                            <option value="admin">管理者</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                <button type="button" class="btn btn-primary" id="updateRole">更新</button>
            </div>
        </div>
    </div>
</div>

<!-- 削除確認モーダル -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteConfirmTitle">削除の確認</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="deleteConfirmBody">
                <!-- 動的に内容が変わる -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">削除</button>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript処理 -->
<script>
    // DOMが完全に読み込まれた後に実行
    document.addEventListener('DOMContentLoaded', function() {
        // 必要なイベントリスナーを設定

        // jQueryが読み込まれているか確認
        if (typeof jQuery !== 'undefined') {
            initializeWithJQuery();
        } else {
            // jQueryが読み込まれていない場合はVanilla JSで代替
            initializeWithoutJQuery();
        }
    });

    function initializeWithJQuery() {
        // アクティブユーザー取得（メンバー追加用）
        function loadActiveUsers() {
            $.ajax({
                url: BASE_PATH + '/api/active-users',
                type: 'GET',
                success: function(response) {
                    if (response.success) {
                        const users = response.data;
                        const select = $('#memberUser');
                        select.empty();
                        select.append('<option value="">選択してください</option>');

                        // 既存メンバーIDを取得
                        const existingMembers = [];
                        $('tr').each(function() {
                            const userId = $(this).find('.remove-member').data('user-id');
                            if (userId) existingMembers.push(userId.toString());
                        });

                        // 未追加のメンバーのみを選択肢として表示
                        for (const user of users) {
                            if (!existingMembers.includes(user.id.toString())) {
                                select.append(`<option value="${user.id}">${user.display_name} (${user.email})</option>`);
                            }
                        }
                    }
                },
                error: function() {
                    alert('ユーザー一覧の取得に失敗しました');
                }
            });
        }

        // メンバー追加モーダルを表示
        $('#showAddMemberModal').on('click', function() {
            loadActiveUsers();
            $('#addMemberModal').modal('show');
        });

        // メンバー追加
        $('#addMember').on('click', function() {
            const userId = $('#memberUser').val();
            const role = $('#memberRole').val();

            if (!userId) {
                alert('ユーザーを選択してください');
                return;
            }

            const data = {
                user_id: userId,
                role: role
            };

            $.ajax({
                url: BASE_PATH + '/api/task/boards/<?php echo $board['id']; ?>/members',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(data),
                success: function(response) {
                    if (response.success) {
                        alert(response.message || 'メンバーを追加しました');
                        $('#addMemberModal').modal('hide');

                        // ページをリロード
                        setTimeout(function() {
                            window.location.reload();
                        }, 1000);
                    } else {
                        alert(response.error || 'メンバーの追加に失敗しました');
                    }
                },
                error: function() {
                    alert('サーバーとの通信に失敗しました');
                }
            });
        });

        // 役割変更ボタンがクリックされたとき
        $('.change-role').on('click', function() {
            const userId = $(this).data('user-id');
            const userName = $(this).data('user-name');
            const currentRole = $(this).data('role');

            $('#changeRoleUserId').val(userId);
            $('#changeRoleUserName').text(userName);
            $('#newRole').val(currentRole);

            $('#changeRoleModal').modal('show');
        });

        // 役割更新
        $('#updateRole').on('click', function() {
            const userId = $('#changeRoleUserId').val();
            const role = $('#newRole').val();

            const data = {
                user_id: userId,
                role: role
            };

            $.ajax({
                url: BASE_PATH + '/api/task/boards/<?php echo $board['id']; ?>/members/role',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(data),
                success: function(response) {
                    if (response.success) {
                        alert(response.message || 'メンバーの役割を更新しました');
                        $('#changeRoleModal').modal('hide');

                        // ページをリロード
                        setTimeout(function() {
                            window.location.reload();
                        }, 1000);
                    } else {
                        alert(response.error || 'メンバーの役割更新に失敗しました');
                    }
                },
                error: function() {
                    alert('サーバーとの通信に失敗しました');
                }
            });
        });

        // メンバー削除ボタンがクリックされたとき
        $('.remove-member').on('click', function() {
            const userId = $(this).data('user-id');
            const userName = $(this).data('user-name');

            $('#deleteConfirmTitle').text('メンバー削除の確認');
            $('#deleteConfirmBody').html(`
            <p>メンバー「${userName}」をボードから削除しますか？</p>
            <p>このユーザーはボードにアクセスできなくなります。</p>
        `);

            $('#confirmDelete').off('click').on('click', function() {
                $.ajax({
                    url: BASE_PATH + '/api/task/boards/<?php echo $board['id']; ?>/members',
                    type: 'DELETE',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        user_id: userId
                    }),
                    success: function(response) {
                        if (response.success) {
                            alert(response.message || 'メンバーを削除しました');
                            $('#deleteConfirmModal').modal('hide');

                            // ページをリロード
                            setTimeout(function() {
                                window.location.reload();
                            }, 1000);
                        } else {
                            alert(response.error || 'メンバーの削除に失敗しました');
                            $('#deleteConfirmModal').modal('hide');
                        }
                    },
                    error: function() {
                        alert('サーバーとの通信に失敗しました');
                        $('#deleteConfirmModal').modal('hide');
                    }
                });
            });

            $('#deleteConfirmModal').modal('show');
        });

        // ボード削除ボタンがクリックされたとき
        $('#deleteBoard').on('click', function() {
            $('#deleteConfirmTitle').text('ボード削除の確認');
            $('#deleteConfirmBody').html(`
            <p>ボード「<?php echo htmlspecialchars($board['name']); ?>」を削除しますか？</p>
            <p class="text-danger">この操作は取り消せません。ボード内のすべてのリスト、カード、コメント、チェックリスト、添付ファイルなどが完全に削除されます。</p>
            <div class="alert alert-warning">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="confirmBoardDelete">
                    <label class="form-check-label" for="confirmBoardDelete">
                        このボードを完全に削除することを理解し、同意します。
                    </label>
                </div>
            </div>
        `);

            $('#confirmDelete').prop('disabled', true);

            $(document).on('change', '#confirmBoardDelete', function() {
                $('#confirmDelete').prop('disabled', !$(this).is(':checked'));
            });

            $('#confirmDelete').off('click').on('click', function() {
                if (!$('#confirmBoardDelete').is(':checked')) return;

                $.ajax({
                    url: BASE_PATH + '/api/task/boards/<?php echo $board['id']; ?>',
                    type: 'DELETE',
                    success: function(response) {
                        if (response.success) {
                            alert(response.message || 'ボードを削除しました');
                            $('#deleteConfirmModal').modal('hide');

                            // リダイレクト
                            if (response.redirect) {
                                window.location.href = response.redirect;
                            } else {
                                window.location.href = BASE_PATH + '/task';
                            }
                        } else {
                            alert(response.error || 'ボードの削除に失敗しました');
                            $('#deleteConfirmModal').modal('hide');
                        }
                    },
                    error: function() {
                        alert('サーバーとの通信に失敗しました');
                        $('#deleteConfirmModal').modal('hide');
                    }
                });
            });

            $('#deleteConfirmModal').modal('show');
        });

        // ボード更新フォームの送信
        $('#boardForm').on('submit', function(e) {
            e.preventDefault();

            const formData = {
                name: $('#boardName').val(),
                description: $('#boardDescription').val(),
                background_color: $('#backgroundColor').val(),
                is_public: $('#isPublic').is(':checked')
            };

            if (!formData.name.trim()) {
                $('#boardName').addClass('is-invalid');
                return false;
            }

            const submitBtn = $(this).find('button[type="submit"]');
            submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> 更新中...');

            $.ajax({
                url: BASE_PATH + '/api/task/boards/<?php echo $board['id']; ?>',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(formData),
                success: function(response) {
                    if (response.success) {
                        alert(response.message || 'ボードを更新しました');

                        // リダイレクト
                        if (response.redirect) {
                            window.location.href = response.redirect;
                        } else {
                            window.location.href = BASE_PATH + '/task/board/<?php echo $board['id']; ?>';
                        }
                    } else {
                        alert(response.error || 'ボードの更新に失敗しました');
                        submitBtn.prop('disabled', false).text('更新');
                    }
                },
                error: function() {
                    alert('サーバーとの通信に失敗しました');
                    submitBtn.prop('disabled', false).text('更新');
                }
            });

            return false;
        });
    }

    function initializeWithoutJQuery() {
        // Vanilla JSでの実装 (jQuery非依存)

        // DOMから要素を取得する関数
        function $(selector) {
            return document.querySelector(selector);
        }

        function $$(selector) {
            return document.querySelectorAll(selector);
        }

        // モーダルのオープン・クローズ
        function showModal(modalId) {
            const modal = $(modalId);
            if (modal) {
                modal.classList.add('show');
                modal.style.display = 'block';
                document.body.classList.add('modal-open');
            }
        }

        function hideModal(modalId) {
            const modal = $(modalId);
            if (modal) {
                modal.classList.remove('show');
                modal.style.display = 'none';
                document.body.classList.remove('modal-open');
            }
        }

        // Ajaxリクエスト処理
        function ajax(options) {
            const xhr = new XMLHttpRequest();
            xhr.open(options.type || 'GET', options.url);

            if (options.contentType) {
                xhr.setRequestHeader('Content-Type', options.contentType);
            }

            xhr.onload = function() {
                if (xhr.status >= 200 && xhr.status < 300) {
                    let response;
                    try {
                        response = JSON.parse(xhr.responseText);
                    } catch (e) {
                        response = xhr.responseText;
                    }
                    if (options.success) options.success(response);
                } else {
                    if (options.error) options.error(xhr);
                }
            };

            xhr.onerror = function() {
                if (options.error) options.error(xhr);
            };

            if (options.data) {
                xhr.send(typeof options.data === 'string' ? options.data : JSON.stringify(options.data));
            } else {
                xhr.send();
            }
        }

        // ボードフォーム送信処理
        const boardForm = $('#boardForm');
        if (boardForm) {
            boardForm.addEventListener('submit', function(e) {
                e.preventDefault();

                const formData = {
                    name: $('#boardName').value,
                    description: $('#boardDescription').value,
                    background_color: $('#backgroundColor').value,
                    is_public: $('#isPublic').checked
                };

                if (!formData.name.trim()) {
                    $('#boardName').classList.add('is-invalid');
                    return false;
                }

                const submitBtn = $('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> 更新中...';
                }

                ajax({
                    url: BASE_PATH + '/api/task/boards/<?php echo $board['id']; ?>',
                    type: 'POST',
                    contentType: 'application/json',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            alert(response.message || 'ボードを更新しました');

                            // リダイレクト
                            if (response.redirect) {
                                window.location.href = response.redirect;
                            } else {
                                window.location.href = BASE_PATH + '/task/board/<?php echo $board['id']; ?>';
                            }
                        } else {
                            alert(response.error || 'ボードの更新に失敗しました');
                            if (submitBtn) {
                                submitBtn.disabled = false;
                                submitBtn.textContent = '更新';
                            }
                        }
                    },
                    error: function() {
                        alert('サーバーとの通信に失敗しました');
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.textContent = '更新';
                        }
                    }
                });

                return false;
            });
        }

        // メンバー追加モーダル表示
        const showAddMemberBtn = $('#showAddMemberModal');
        if (showAddMemberBtn) {
            showAddMemberBtn.addEventListener('click', function() {
                // アクティブユーザー取得
                ajax({
                    url: BASE_PATH + '/api/active-users',
                    type: 'GET',
                    success: function(response) {
                        if (response.success) {
                            const users = response.data;
                            const select = $('#memberUser');
                            if (select) {
                                // 選択肢をクリア
                                select.innerHTML = '<option value="">選択してください</option>';

                                // 既存メンバーIDを取得
                                const existingMembers = [];
                                const memberRows = $('.remove-member');
                                memberRows.forEach(function(btn) {
                                    const userId = btn.getAttribute('data-user-id');
                                    if (userId) existingMembers.push(userId.toString());
                                });

                                // 未追加のメンバーのみを選択肢として表示
                                for (const user of users) {
                                    if (!existingMembers.includes(user.id.toString())) {
                                        const option = document.createElement('option');
                                        option.value = user.id;
                                        option.textContent = `${user.display_name} (${user.email})`;
                                        select.appendChild(option);
                                    }
                                }
                            }
                        }

                        // モーダルを表示
                        showModal('#addMemberModal');
                    },
                    error: function() {
                        alert('ユーザー一覧の取得に失敗しました');
                    }
                });
            });
        }

        // メンバー追加処理
        const addMemberBtn = $('#addMember');
        if (addMemberBtn) {
            addMemberBtn.addEventListener('click', function() {
                const userId = $('#memberUser').value;
                const role = $('#memberRole').value;

                if (!userId) {
                    alert('ユーザーを選択してください');
                    return;
                }

                const data = {
                    user_id: userId,
                    role: role
                };

                ajax({
                    url: BASE_PATH + '/api/task/boards/<?php echo $board['id']; ?>/members',
                    type: 'POST',
                    contentType: 'application/json',
                    data: data,
                    success: function(response) {
                        if (response.success) {
                            alert(response.message || 'メンバーを追加しました');
                            hideModal('#addMemberModal');

                            // ページをリロード
                            setTimeout(function() {
                                window.location.reload();
                            }, 1000);
                        } else {
                            alert(response.error || 'メンバーの追加に失敗しました');
                        }
                    },
                    error: function() {
                        alert('サーバーとの通信に失敗しました');
                    }
                });
            });
        }

        // 役割変更処理
        const changeRoleButtons = $('.change-role');
        changeRoleButtons.forEach(function(btn) {
            btn.addEventListener('click', function() {
                const userId = this.getAttribute('data-user-id');
                const userName = this.getAttribute('data-user-name');
                const currentRole = this.getAttribute('data-role');

                const userIdField = $('#changeRoleUserId');
                const userNameSpan = $('#changeRoleUserName');
                const roleSelect = $('#newRole');

                if (userIdField) userIdField.value = userId;
                if (userNameSpan) userNameSpan.textContent = userName;
                if (roleSelect) roleSelect.value = currentRole;

                showModal('#changeRoleModal');
            });
        });

        // 役割更新処理
        const updateRoleBtn = $('#updateRole');
        if (updateRoleBtn) {
            updateRoleBtn.addEventListener('click', function() {
                const userId = $('#changeRoleUserId').value;
                const role = $('#newRole').value;

                const data = {
                    user_id: userId,
                    role: role
                };

                ajax({
                    url: BASE_PATH + '/api/task/boards/<?php echo $board['id']; ?>/members/role',
                    type: 'POST',
                    contentType: 'application/json',
                    data: data,
                    success: function(response) {
                        if (response.success) {
                            alert(response.message || 'メンバーの役割を更新しました');
                            hideModal('#changeRoleModal');

                            // ページをリロード
                            setTimeout(function() {
                                window.location.reload();
                            }, 1000);
                        } else {
                            alert(response.error || 'メンバーの役割更新に失敗しました');
                        }
                    },
                    error: function() {
                        alert('サーバーとの通信に失敗しました');
                    }
                });
            });
        }

        // メンバー削除処理
        const removeMemberButtons = $('.remove-member');
        removeMemberButtons.forEach(function(btn) {
            btn.addEventListener('click', function() {
                const userId = this.getAttribute('data-user-id');
                const userName = this.getAttribute('data-user-name');

                const titleEl = $('#deleteConfirmTitle');
                const bodyEl = $('#deleteConfirmBody');
                const confirmBtn = $('#confirmDelete');

                if (titleEl) titleEl.textContent = 'メンバー削除の確認';
                if (bodyEl) {
                    bodyEl.innerHTML = `
                    <p>メンバー「${userName}」をボードから削除しますか？</p>
                    <p>このユーザーはボードにアクセスできなくなります。</p>
                `;
                }

                if (confirmBtn) {
                    // 以前のイベントリスナーを削除（できるだけ）
                    const newConfirmBtn = confirmBtn.cloneNode(true);
                    confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);

                    // 新しいイベントリスナーを追加
                    newConfirmBtn.addEventListener('click', function() {
                        ajax({
                            url: BASE_PATH + '/api/task/boards/<?php echo $board['id']; ?>/members',
                            type: 'DELETE',
                            contentType: 'application/json',
                            data: {
                                user_id: userId
                            },
                            success: function(response) {
                                if (response.success) {
                                    alert(response.message || 'メンバーを削除しました');
                                    hideModal('#deleteConfirmModal');

                                    // ページをリロード
                                    setTimeout(function() {
                                        window.location.reload();
                                    }, 1000);
                                } else {
                                    alert(response.error || 'メンバーの削除に失敗しました');
                                    hideModal('#deleteConfirmModal');
                                }
                            },
                            error: function() {
                                alert('サーバーとの通信に失敗しました');
                                hideModal('#deleteConfirmModal');
                            }
                        });
                    });
                }

                showModal('#deleteConfirmModal');
            });
        });

        // ボード削除処理
        const deleteBoardBtn = $('#deleteBoard');
        if (deleteBoardBtn) {
            deleteBoardBtn.addEventListener('click', function() {
                const titleEl = $('#deleteConfirmTitle');
                const bodyEl = $('#deleteConfirmBody');
                const confirmBtn = $('#confirmDelete');

                if (titleEl) titleEl.textContent = 'ボード削除の確認';
                if (bodyEl) {
                    bodyEl.innerHTML = `
                    <p>ボード「<?php echo htmlspecialchars($board['name']); ?>」を削除しますか？</p>
                    <p class="text-danger">この操作は取り消せません。ボード内のすべてのリスト、カード、コメント、チェックリスト、添付ファイルなどが完全に削除されます。</p>
                    <div class="alert alert-warning">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="confirmBoardDelete">
                            <label class="form-check-label" for="confirmBoardDelete">
                                このボードを完全に削除することを理解し、同意します。
                            </label>
                        </div>
                    </div>
                `;
                }

                if (confirmBtn) {
                    confirmBtn.disabled = true;

                    // チェックボックスの変更イベント
                    setTimeout(function() {
                        const checkbox = $('#confirmBoardDelete');
                        if (checkbox) {
                            checkbox.addEventListener('change', function() {
                                if (confirmBtn) confirmBtn.disabled = !this.checked;
                            });
                        }
                    }, 100);

                    // 以前のイベントリスナーを削除（できるだけ）
                    const newConfirmBtn = confirmBtn.cloneNode(true);
                    confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);

                    // 新しいイベントリスナーを追加
                    newConfirmBtn.addEventListener('click', function() {
                        const checkbox = $('#confirmBoardDelete');
                        if (checkbox && !checkbox.checked) return;

                        ajax({
                            url: BASE_PATH + '/api/task/boards/<?php echo $board['id']; ?>',
                            type: 'DELETE',
                            success: function(response) {
                                if (response.success) {
                                    alert(response.message || 'ボードを削除しました');
                                    hideModal('#deleteConfirmModal');

                                    // リダイレクト
                                    if (response.redirect) {
                                        window.location.href = response.redirect;
                                    } else {
                                        window.location.href = BASE_PATH + '/task';
                                    }
                                } else {
                                    alert(response.error || 'ボードの削除に失敗しました');
                                    hideModal('#deleteConfirmModal');
                                }
                            },
                            error: function() {
                                alert('サーバーとの通信に失敗しました');
                                hideModal('#deleteConfirmModal');
                            }
                        });
                    });
                }

                showModal('#deleteConfirmModal');
            });
        }

        // モーダル閉じるボタン
        const closeButtons = $('.btn-close, .modal .btn-secondary');
        closeButtons.forEach(function(btn) {
            btn.addEventListener('click', function() {
                const modal = this.closest('.modal');
                if (modal) {
                    hideModal('#' + modal.id);
                }
            });
        });
    }
</script>
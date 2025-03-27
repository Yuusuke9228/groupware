<!-- views/webdatabase/record_view.php -->
<div class="container-fluid mt-4">
    <div class="row mb-3">
        <div class="col">
            <h1>レコード詳細</h1>
            <h5 class="text-muted"><?= htmlspecialchars($database['name']) ?></h5>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><?= htmlspecialchars($record['title']) ?></h5>
                <div>
                    <a href="<?= BASE_PATH ?>/webdatabase/edit/<?= $database['id'] ?>/<?= $record['id'] ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-edit"></i> 編集
                    </a>
                    <button class="btn btn-danger btn-sm btn-delete" data-url="<?= BASE_PATH ?>/api/webdatabase/record/<?= $database['id'] ?>/<?= $record['id'] ?>" data-confirm="このレコードを削除しますか？" data-redirect="<?= BASE_PATH ?>/webdatabase/records/<?= $database['id'] ?>">
                        <i class="fas fa-trash"></i> 削除
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-6">
                    <table class="table table-sm">
                        <tr>
                            <th width="30%">作成者</th>
                            <td><?= htmlspecialchars($record['creator_name']) ?></td>
                        </tr>
                        <tr>
                            <th>作成日時</th>
                            <td><?= htmlspecialchars($record['created_at']) ?></td>
                        </tr>
                        <?php if ($record['updater_id']): ?>
                            <tr>
                                <th>更新者</th>
                                <td><?= htmlspecialchars($record['updater_name']) ?></td>
                            </tr>
                            <tr>
                                <th>更新日時</th>
                                <td><?= htmlspecialchars($record['updated_at']) ?></td>
                            </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>

            <h5 class="mb-3">レコード内容</h5>
            <div class="table-responsive">
                <table class="table">
                    <tbody>
                        <?php foreach ($fields as $field): ?>
                            <tr>
                                <th width="20%" class="bg-light"><?= htmlspecialchars($field['name']) ?></th>
                                <td>
                                    <?php
                                    // フィールド値を表示
                                    $value = isset($recordData[$field['id']]) ? $recordData[$field['id']] : '';

                                    if (empty($value)) {
                                        echo '<span class="text-muted">未設定</span>';
                                    } else {
                                        // フィールドタイプに応じた表示
                                        switch ($field['type']) {
                                            case 'textarea':
                                                echo nl2br(htmlspecialchars($value));
                                                break;

                                            case 'select':
                                            case 'radio':
                                                $options = json_decode($field['options'], true);
                                                $displayValue = $value;
                                                if (is_array($options)) {
                                                    foreach ($options as $option) {
                                                        if ($option['value'] == $value) {
                                                            $displayValue = $option['label'];
                                                            break;
                                                        }
                                                    }
                                                }
                                                echo htmlspecialchars($displayValue);
                                                break;

                                            case 'checkbox':
                                                $options = json_decode($field['options'], true);
                                                $checkedValues = is_array($value) ? $value : [$value];
                                                $displayValues = [];

                                                if (is_array($options)) {
                                                    foreach ($options as $option) {
                                                        if (in_array($option['value'], $checkedValues)) {
                                                            $displayValues[] = $option['label'];
                                                        }
                                                    }
                                                }

                                                echo htmlspecialchars(implode(', ', $displayValues));
                                                break;

                                            case 'file':
                                                if (is_array($value)) {
                                                    if (isset($value[0])) {
                                                        // 複数ファイル
                                                        foreach ($value as $file) {
                                                            echo '<div class="mb-1"><a href="' . BASE_PATH . '/' . htmlspecialchars($file['path']) . '" target="_blank">';
                                                            echo '<i class="fas fa-file"></i> ' . htmlspecialchars($file['name']) . '</a> ';
                                                            echo '<span class="text-muted">(' . number_format($file['size'] / 1024, 1) . ' KB)</span></div>';
                                                        }
                                                    } else {
                                                        // 単一ファイル
                                                        echo '<a href="' . BASE_PATH . '/' . htmlspecialchars($value['path']) . '" target="_blank">';
                                                        echo '<i class="fas fa-file"></i> ' . htmlspecialchars($value['name']) . '</a> ';
                                                        echo '<span class="text-muted">(' . number_format($value['size'] / 1024, 1) . ' KB)</span>';
                                                    }
                                                } else {
                                                    echo htmlspecialchars($value);
                                                }
                                                break;

                                            case 'user':
                                                echo '<span class="user-display" data-user-id="' . htmlspecialchars($value) . '">' . htmlspecialchars($value) . '</span>';
                                                break;

                                            case 'organization':
                                                echo '<span class="organization-display" data-org-id="' . htmlspecialchars($value) . '">' . htmlspecialchars($value) . '</span>';
                                                break;

                                            default:
                                                echo htmlspecialchars($value);
                                        }
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between mt-3">
        <a href="<?= BASE_PATH ?>/webdatabase/records/<?= $database['id'] ?>" class="btn btn-secondary">レコード一覧に戻る</a>
        <div class="btn-group">
            <a href="<?= BASE_PATH ?>/webdatabase/edit/<?= $database['id'] ?>/<?= $record['id'] ?>" class="btn btn-primary">
                <i class="fas fa-edit"></i> 編集
            </a>
            <button type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="visually-hidden">ドロップダウン</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><button class="dropdown-item btn-delete" data-url="<?= BASE_PATH ?>/api/webdatabase/<?= $database['id'] ?>/<?= $record['id'] ?>" data-confirm="このレコードを削除しますか？" data-redirect="<?= BASE_PATH ?>/webdatabase/records/<?= $database['id'] ?>">
                        <i class="fas fa-trash text-danger"></i> 削除
                    </button></li>
            </ul>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // ユーザーフィールド値を表示用に変換
        document.querySelectorAll('.user-display').forEach(function(element) {
            const userId = element.dataset.userId;
            if (userId) {
                fetch(`${BASE_PATH}/api/users/${userId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            element.textContent = data.data.user.display_name;
                        } else {
                            element.textContent = 'ユーザーが見つかりません';
                        }
                    })
                    .catch(() => {
                        element.textContent = 'ユーザー情報の取得に失敗しました';
                    });
            } else {
                element.textContent = '未選択';
            }
        });

        // 組織フィールド値を表示用に変換
        document.querySelectorAll('.organization-display').forEach(function(element) {
            const orgId = element.dataset.orgId;
            if (orgId) {
                fetch(`${BASE_PATH}/api/organizations/${orgId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            element.textContent = data.data.name;
                        } else {
                            element.textContent = '組織が見つかりません';
                        }
                    })
                    .catch(() => {
                        element.textContent = '組織情報の取得に失敗しました';
                    });
            } else {
                element.textContent = '未選択';
            }
        });

        // 削除ボタンのイベント処理
        document.querySelectorAll('.btn-delete').forEach(function(button) {
            button.addEventListener('click', function() {
                const url = this.dataset.url;
                const confirmMessage = this.dataset.confirm;
                const redirect = this.dataset.redirect;

                if (confirm(confirmMessage)) {
                    fetch(url, {
                            method: 'DELETE',
                            credentials: 'same-origin'
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                App.showNotification(data.message, 'success');
                                if (redirect) {
                                    setTimeout(function() {
                                        window.location.href = redirect;
                                    }, 1000);
                                }
                            } else {
                                App.showNotification(data.error, 'error');
                            }
                        })
                        .catch(() => {
                            App.showNotification('エラーが発生しました', 'error');
                        });
                }
            });
        });
    });
</script>
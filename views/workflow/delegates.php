<?php
// views/workflow/delegates.php
$pageTitle = '代理承認設定 - GroupWare';
?>
<div class="container-fluid" data-page-type="delegates">
    <div class="row mb-4">
        <div class="col">
            <h1 class="h3">代理承認設定</h1>
        </div>
    </div>

    <div class="row">
        <!-- 代理承認設定一覧 -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">代理承認設定一覧</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="delegates-table" class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>代理人</th>
                                    <th>対象テンプレート</th>
                                    <th>期間</th>
                                    <th>状態</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (isset($delegations) && !empty($delegations)): ?>
                                    <?php foreach ($delegations as $delegation): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($delegation['delegate_name']); ?></td>
                                            <td><?php echo $delegation['template_id'] ? htmlspecialchars($delegation['template_name']) : 'すべて'; ?></td>
                                            <td>
                                                <?php echo date('Y/m/d', strtotime($delegation['start_date'])); ?> ～
                                                <?php echo date('Y/m/d', strtotime($delegation['end_date'])); ?>
                                            </td>
                                            <td>
                                                <?php if ($delegation['status'] === 'active'): ?>
                                                    <span class="badge bg-success">有効</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">無効</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-sm btn-danger btn-delete-delegation" data-id="<?php echo $delegation['id']; ?>">
                                                        <i class="fas fa-trash"></i> 削除
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center">代理承認設定はありません</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- 新規代理承認設定フォーム -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">新規代理承認設定</h5>
                </div>
                <div class="card-body">
                    <form id="delegate-form">
                        <div class="mb-3">
                            <label for="delegate_id" class="form-label">代理人 <span class="text-danger">*</span></label>
                            <select class="form-select" id="delegate_id" name="delegate_id" required>
                                <option value="">選択してください</option>
                                <?php foreach ($users as $user): ?>
                                    <?php if ($user['id'] != $this->auth->id()): ?>
                                        <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['display_name']); ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">代理人を選択してください</div>
                        </div>

                        <div class="mb-3">
                            <label for="template_id" class="form-label">対象テンプレート</label>
                            <select class="form-select" id="template_id" name="template_id">
                                <option value="">すべて</option>
                                <?php foreach ($templates as $template): ?>
                                    <option value="<?php echo $template['id']; ?>"><?php echo htmlspecialchars($template['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="start_date" class="form-label">開始日 <span class="text-danger">*</span></label>
                            <input type="date" class="form-control date-picker" id="start_date" name="start_date" required>
                            <div class="invalid-feedback">開始日を指定してください</div>
                        </div>

                        <div class="mb-3">
                            <label for="end_date" class="form-label">終了日 <span class="text-danger">*</span></label>
                            <input type="date" class="form-control date-picker" id="end_date" name="end_date" required>
                            <div class="invalid-feedback">終了日を指定してください</div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">設定を追加</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 代理承認設定削除確認モーダル -->
<div class="modal fade" id="delete-delegation-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">代理承認設定の削除</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>この代理承認設定を削除しますか？</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
                <button type="button" class="btn btn-danger" id="confirm-delete-delegation">削除</button>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // 日付ピッカーの初期化
        if (typeof flatpickr !== 'undefined') {
            flatpickr('.date-picker', {
                dateFormat: 'Y-m-d',
                locale: 'ja',
                minDate: 'today'
            });
        }

        // 削除ボタンクリック時
        $('.btn-delete-delegation').on('click', function() {
            const delegationId = $(this).data('id');
            $('#confirm-delete-delegation').data('id', delegationId);
            $('#delete-delegation-modal').modal('show');
        });

        // 削除確認
        $('#confirm-delete-delegation').on('click', function() {
            const delegationId = $(this).data('id');

            // APIリクエスト
            App.apiDelete(`/workflow/delegates/${delegationId}`)
                .then(response => {
                    if (response.success) {
                        App.showNotification(response.message || '代理承認設定を削除しました', 'success');
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        App.showNotification(response.error || '削除に失敗しました', 'error');
                    }
                })
                .catch(error => {
                    console.error('Failed to delete delegation:', error);
                    App.showNotification('削除に失敗しました', 'error');
                });

            $('#delete-delegation-modal').modal('hide');
        });
    });
</script>
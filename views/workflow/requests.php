<?php
// views/workflow/requests.php
$pageTitle = '申請一覧 - GroupWare';
?>
<div class="container-fluid" data-page-type="requests">
    <div class="row mb-4">
        <div class="col">
            <h1 class="h3">申請一覧</h1>
        </div>
        <div class="col-auto">
            <!-- 新規申請ボタン -->
            <a href="<?php echo BASE_PATH; ?>/workflow/templates" class="btn btn-primary">
                <i class="fas fa-plus"></i> 新規申請
            </a>
        </div>
    </div>

    <!-- フィルターカード -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">検索・フィルター</h5>
        </div>
        <div class="card-body">
            <form id="filter-form" class="row g-3">
                <div class="col-md-4">
                    <label for="filter-status" class="form-label">ステータス</label>
                    <select class="form-select" id="filter-status" name="status">
                        <option value="">すべて</option>
                        <option value="draft" <?php echo isset($filters['status']) && $filters['status'] === 'draft' ? 'selected' : ''; ?>>下書き</option>
                        <option value="pending" <?php echo isset($filters['status']) && $filters['status'] === 'pending' ? 'selected' : ''; ?>>承認待ち</option>
                        <option value="approved" <?php echo isset($filters['status']) && $filters['status'] === 'approved' ? 'selected' : ''; ?>>承認済み</option>
                        <option value="rejected" <?php echo isset($filters['status']) && $filters['status'] === 'rejected' ? 'selected' : ''; ?>>却下</option>
                        <option value="cancelled" <?php echo isset($filters['status']) && $filters['status'] === 'cancelled' ? 'selected' : ''; ?>>キャンセル</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="filter-template" class="form-label">テンプレート</label>
                    <select class="form-select" id="filter-template" name="template_id">
                        <option value="">すべて</option>
                        <?php foreach ($templates as $template): ?>
                            <option value="<?php echo $template['id']; ?>" <?php echo isset($filters['template_id']) && $filters['template_id'] == $template['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($template['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="filter-search" class="form-label">検索</label>
                    <input type="text" class="form-control" id="filter-search" name="search" placeholder="申請番号、タイトル" value="<?php echo isset($filters['search']) ? htmlspecialchars($filters['search']) : ''; ?>">
                </div>
                <div class="col-12">
                    <div class="d-flex justify-content-end">
                        <button type="button" id="filter-clear" class="btn btn-outline-secondary me-2">クリア</button>
                        <button type="submit" class="btn btn-primary">検索</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- 申請一覧テーブル -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="requests-table" class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>申請番号</th>
                            <th>タイトル</th>
                            <th>テンプレート</th>
                            <th>申請者</th>
                            <th>ステータス</th>
                            <th>申請日</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (isset($requests) && !empty($requests)): ?>
                            <?php foreach ($requests as $request): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($request['request_number']); ?></td>
                                    <td><?php echo htmlspecialchars($request['title']); ?></td>
                                    <td><?php echo htmlspecialchars($request['template_name']); ?></td>
                                    <td><?php echo htmlspecialchars($request['requester_name']); ?></td>
                                    <td>
                                        <?php
                                        switch ($request['status']) {
                                            case 'draft':
                                                echo '<span class="badge bg-secondary">下書き</span>';
                                                break;
                                            case 'pending':
                                                echo '<span class="badge bg-warning">承認待ち</span>';
                                                break;
                                            case 'approved':
                                                echo '<span class="badge bg-success">承認済み</span>';
                                                break;
                                            case 'rejected':
                                                echo '<span class="badge bg-danger">却下</span>';
                                                break;
                                            case 'cancelled':
                                                echo '<span class="badge bg-info">キャンセル</span>';
                                                break;
                                            default:
                                                echo '<span class="badge bg-secondary">不明</span>';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo date('Y/m/d H:i', strtotime($request['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="<?php echo BASE_PATH; ?>/workflow/view/<?php echo $request['id']; ?>" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i> 詳細
                                            </a>
                                            <?php if ($request['status'] === 'draft' && $request['requester_id'] == $this->auth->id()): ?>
                                                <a href="<?php echo BASE_PATH; ?>/workflow/edit/<?php echo $request['id']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-edit"></i> 編集
                                                </a>
                                            <?php endif; ?>
                                            <?php if (($request['status'] === 'draft' || $request['status'] === 'pending') && $request['requester_id'] == $this->auth->id()): ?>
                                                <button type="button" class="btn btn-sm btn-danger btn-cancel-request" data-id="<?php echo $request['id']; ?>">
                                                    <i class="fas fa-times"></i> キャンセル
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">申請がありません</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- ページネーション -->
            <?php if (isset($totalPages) && $totalPages > 1): ?>
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php
                        $url = BASE_PATH . '/workflow/requests?';
                        if (isset($filters['status'])) $url .= 'status=' . $filters['status'] . '&';
                        if (isset($filters['template_id'])) $url .= 'template_id=' . $filters['template_id'] . '&';
                        if (isset($filters['search'])) $url .= 'search=' . urlencode($filters['search']) . '&';
                        ?>

                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo $url . 'page=' . ($page - 1); ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>

                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                <a class="page-link" href="<?php echo $url . 'page=' . $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>

                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo $url . 'page=' . ($page + 1); ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- 申請キャンセル確認モーダル -->
<div class="modal fade" id="cancel-request-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">申請をキャンセル</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>この申請をキャンセルしますか？</p>
                <p class="text-danger">※キャンセル後は元に戻せません。</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
                <button type="button" class="btn btn-danger" id="confirm-cancel-request">キャンセル</button>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // キャンセルボタンクリック時
        $('.btn-cancel-request').on('click', function() {
            const requestId = $(this).data('id');
            $('#confirm-cancel-request').data('id', requestId);
            $('#cancel-request-modal').modal('show');
        });

        // キャンセル確認
        $('#confirm-cancel-request').on('click', function() {
            const requestId = $(this).data('id');
            Workflow.cancelRequest(requestId);
            $('#cancel-request-modal').modal('hide');
        });
    });
</script>
<?php
// views/workflow/approvals.php
$pageTitle = '承認待ち一覧 - GroupWare';
?>
<div class="container-fluid" data-page-type="approvals">
    <div class="row mb-4">
        <div class="col">
            <h1 class="h3">承認待ち一覧</h1>
        </div>
    </div>

    <!-- フィルターカード -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">検索・フィルター</h5>
        </div>
        <div class="card-body">
            <form id="filter-form" class="row g-3">
                <div class="col-md-6">
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
                <div class="col-md-6">
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

    <!-- 承認待ち申請一覧テーブル -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="approvals-table" class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>申請番号</th>
                            <th>タイトル</th>
                            <th>テンプレート</th>
                            <th>申請者</th>
                            <th>ステップ</th>
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
                                        <span class="badge bg-info">ステップ <?php echo $request['current_step']; ?></span>
                                    </td>
                                    <td><?php echo date('Y/m/d H:i', strtotime($request['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="<?php echo BASE_PATH; ?>/workflow/view/<?php echo $request['id']; ?>" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i> 確認・承認
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">承認待ちの申請はありません</td>
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
                        $url = BASE_PATH . '/workflow/approvals?';
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
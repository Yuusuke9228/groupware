<?php
// views/workflow/templates.php
$pageTitle = 'ワークフローテンプレート - GroupWare';
?>
<div class="container-fluid" data-page-type="templates">
    <div class="row mb-4">
        <div class="col">
            <h1 class="h3">ワークフローテンプレート</h1>
        </div>
        <div class="col-auto">
            <?php if ($this->auth->isAdmin()): ?>
                <a href="<?php echo BASE_PATH; ?>/workflow/create-template" class="btn btn-primary" id="btn-create-template">
                    <i class="fas fa-plus"></i> 新規テンプレート作成
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <form id="search-form" class="row g-3">
                <div class="col-md-6">
                    <div class="input-group">
                        <input type="text" id="search-input" class="form-control" placeholder="テンプレートを検索..."
                            value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                        <button class="btn btn-outline-secondary" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                        <button class="btn btn-outline-secondary" type="button" id="search-clear">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="templates-table" class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>テンプレート名</th>
                            <th>作成者</th>
                            <th>ステータス</th>
                            <th>作成日</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (isset($templates) && is_array($templates)): ?>
                            <?php foreach ($templates as $template): ?>
                                <tr>
                                    <td><?php echo $template['id']; ?></td>
                                    <td><?php echo htmlspecialchars($template['name']); ?></td>
                                    <td><?php echo htmlspecialchars($template['creator_name']); ?></td>
                                    <td>
                                        <?php if ($template['status'] === 'active'): ?>
                                            <span class="badge bg-success">有効</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">無効</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('Y/m/d', strtotime($template['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <?php if ($this->auth->isAdmin()): ?>
                                                <a href="<?php echo BASE_PATH; ?>/workflow/design-form/<?php echo $template['id']; ?>" class="btn btn-sm btn-info" title="フォームデザイン">
                                                    <i class="fas fa-edit"></i> フォーム
                                                </a>
                                                <a href="<?php echo BASE_PATH; ?>/workflow/design-route/<?php echo $template['id']; ?>" class="btn btn-sm btn-warning" title="承認経路設定">
                                                    <i class="fas fa-route"></i> 経路
                                                </a>
                                                <a href="<?php echo BASE_PATH; ?>/workflow/edit-template/<?php echo $template['id']; ?>" class="btn btn-sm btn-primary" title="テンプレート編集">
                                                    <i class="fas fa-cog"></i> 設定
                                                </a>
                                                <button type="button" class="btn btn-sm btn-danger btn-delete"
                                                    data-url="<?php echo BASE_PATH; ?>/api/workflow/templates/<?php echo $template['id']; ?>"
                                                    data-confirm="テンプレート「<?php echo htmlspecialchars($template['name']); ?>」を削除しますか？">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php else: ?>
                                                <a href="<?php echo BASE_PATH; ?>/workflow/create/<?php echo $template['id']; ?>" class="btn btn-sm btn-success" title="新規申請">
                                                    <i class="fas fa-plus"></i> 申請
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            <?php if (isset($totalPages) && $totalPages > 1): ?>
                <nav aria-label="ページネーション">
                    <ul class="pagination justify-content-center mb-0">
                        <?php
                        // 前のページリンク
                        if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo BASE_PATH; ?>/workflow/templates?page=<?php echo ($page - 1); ?><?php echo isset($_GET['search']) ? '&search=' . htmlspecialchars($_GET['search']) : ''; ?>">前へ</a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled"><span class="page-link">前へ</span></li>
                        <?php endif;

                        // ページ番号リンク
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $startPage + 4);
                        if ($endPage - $startPage < 4) {
                            $startPage = max(1, $endPage - 4);
                        }

                        for ($i = $startPage; $i <= $endPage; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="<?php echo BASE_PATH; ?>/workflow/templates?page=<?php echo $i; ?><?php echo isset($_GET['search']) ? '&search=' . htmlspecialchars($_GET['search']) : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor;

                        // 次のページリンク
                        if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo BASE_PATH; ?>/workflow/templates?page=<?php echo ($page + 1); ?><?php echo isset($_GET['search']) ? '&search=' . htmlspecialchars($_GET['search']) : ''; ?>">次へ</a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled"><span class="page-link">次へ</span></li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>
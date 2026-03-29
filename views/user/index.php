<?php
// views/user/index.php
$pageTitle = 'ユーザー管理';
?>
<div class="container-fluid" data-page-type="index">
    <div class="row mb-4">
        <div class="col">
            <h1 class="h3">ユーザー管理</h1>
        </div>
        <!--
        <div class="col-auto">
            <button id="btn-create-user" class="btn btn-primary">
                <i class="fas fa-plus"></i> 新規ユーザー作成
            </button>
        </div>
-->
        <div class="col-auto">
            <?php if ($this->auth->isAdmin()): ?>
                <button id="btn-create-user" class="btn btn-primary">
                    <i class="fas fa-plus"></i> 新規ユーザー作成
                </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <form id="search-form" class="row g-3">
                <div class="col-md-6">
                    <div class="input-group">
                        <input type="text" id="search-input" class="form-control" placeholder="ユーザーを検索..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
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
                <table id="users-table" class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>ユーザー名</th>
                            <th>氏名</th>
                            <th>メールアドレス</th>
                            <th>主組織</th>
                            <th>状態</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($users)): ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo (int)$user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['display_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars(!empty($user['organization_name']) ? $user['organization_name'] : '-'); ?></td>
                                    <td>
                                        <?php if ($user['status'] === 'active'): ?>
                                            <span class="badge bg-success">有効</span>
                                        <?php elseif ($user['status'] === 'inactive'): ?>
                                            <span class="badge bg-warning">無効</span>
                                        <?php elseif ($user['status'] === 'suspended'): ?>
                                            <span class="badge bg-danger">停止</span>
                                        <?php else: ?>
                                            <?php echo htmlspecialchars($user['status']); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="<?php echo BASE_PATH; ?>/users/view/<?php echo (int)$user['id']; ?>" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i> 詳細
                                            </a>
                                            <?php if ($this->auth->isAdmin() || $this->auth->id() == $user['id']): ?>
                                                <a href="<?php echo BASE_PATH; ?>/users/edit/<?php echo (int)$user['id']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-edit"></i> 編集
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($this->auth->isAdmin() && $this->auth->id() != $user['id']): ?>
                                                <button type="button" class="btn btn-sm btn-danger btn-delete" data-url="<?php echo BASE_PATH; ?>/api/users/<?php echo (int)$user['id']; ?>" data-confirm="ユーザー「<?php echo htmlspecialchars($user['display_name']); ?>」を削除しますか？">
                                                    <i class="fas fa-trash"></i> 削除
                                                </button>
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
            <nav aria-label="ページネーション">
                <ul class="pagination justify-content-center mb-0">
                    <?php
                    // ページネーションリンクの生成
                    if (isset($totalPages) && $totalPages > 1) {
                        $buildPageUrl = function (int $targetPage) {
                            $params = ['page' => max(1, $targetPage)];
                            if (isset($_GET['search']) && is_scalar($_GET['search'])) {
                                $searchText = trim((string)$_GET['search']);
                                if ($searchText !== '') {
                                    $params['search'] = $searchText;
                                }
                            }
                            $query = http_build_query($params);
                            return BASE_PATH . '/users' . ($query !== '' ? ('?' . $query) : '');
                        };

                        // 前のページリンク
                        if ($page > 1) {
                            echo '<li class="page-item"><a class="page-link" href="' . htmlspecialchars($buildPageUrl($page - 1), ENT_QUOTES, 'UTF-8') . '">前へ</a></li>';
                        } else {
                            echo '<li class="page-item disabled"><span class="page-link">前へ</span></li>';
                        }

                        // ページ番号リンク
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $startPage + 4);
                        if ($endPage - $startPage < 4) {
                            $startPage = max(1, $endPage - 4);
                        }

                        for ($i = $startPage; $i <= $endPage; $i++) {
                            if ($i == $page) {
                                echo '<li class="page-item active"><span class="page-link">' . $i . '</span></li>';
                            } else {
                                echo '<li class="page-item"><a class="page-link" href="' . htmlspecialchars($buildPageUrl($i), ENT_QUOTES, 'UTF-8') . '">' . $i . '</a></li>';
                            }
                        }

                        // 次のページリンク
                        if ($page < $totalPages) {
                            echo '<li class="page-item"><a class="page-link" href="' . htmlspecialchars($buildPageUrl($page + 1), ENT_QUOTES, 'UTF-8') . '">次へ</a></li>';
                        } else {
                            echo '<li class="page-item disabled"><span class="page-link">次へ</span></li>';
                        }
                    }
                    ?>
                </ul>
            </nav>
        </div>
    </div>
</div>

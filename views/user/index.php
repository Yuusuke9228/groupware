<?php
// views/user/index.php
$pageTitle = 'ユーザー管理 - GroupWare';
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
                        <!-- データはJSで動的に生成 -->
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
                        $url = '?';
                        if (isset($_GET['search'])) {
                            $url .= 'search=' . urlencode($_GET['search']) . '&';
                        }

                        // 前のページリンク
                        if ($page > 1) {
                            echo '<li class="page-item"><a class="page-link" href="' . $url . 'page=' . ($page - 1) . '">前へ</a></li>';
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
                                echo '<li class="page-item"><a class="page-link" href="' . $url . 'page=' . $i . '">' . $i . '</a></li>';
                            }
                        }

                        // 次のページリンク
                        if ($page < $totalPages) {
                            echo '<li class="page-item"><a class="page-link" href="' . $url . 'page=' . ($page + 1) . '">次へ</a></li>';
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
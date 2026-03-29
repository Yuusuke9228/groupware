<?php
$pageTitle = '申請テンプレート選択';
?>
<div class="container-fluid" data-page-type="template_selector">
    <div class="row align-items-center mb-4">
        <div class="col">
            <h1 class="h3 mb-1">新規申請</h1>
            <p class="text-muted mb-0">申請したいテンプレートを選択してください。</p>
        </div>
        <div class="col-auto">
            <a href="<?php echo BASE_PATH; ?>/workflow/requests" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> 申請一覧へ戻る
            </a>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form class="row g-3" method="get" action="<?php echo BASE_PATH; ?>/workflow/create">
                <div class="col-md-8">
                    <label for="search" class="form-label">テンプレート検索</label>
                    <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars((string)$search); ?>" placeholder="テンプレート名や説明で検索">
                </div>
                <div class="col-md-4 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> 検索
                    </button>
                    <a href="<?php echo BASE_PATH; ?>/workflow/create" class="btn btn-outline-secondary">クリア</a>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-4">
        <?php if (!empty($templates)): ?>
            <?php foreach ($templates as $template): ?>
                <div class="col-md-6 col-xl-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body d-flex flex-column">
                            <h2 class="h5 mb-2"><?php echo htmlspecialchars($template['name']); ?></h2>
                            <p class="text-muted small mb-3"><?php echo nl2br(htmlspecialchars((string)($template['description'] ?? '説明はありません'))); ?></p>
                            <div class="mt-auto d-flex justify-content-between align-items-center">
                                <span class="badge bg-light text-dark">作成者: <?php echo htmlspecialchars((string)$template['creator_name']); ?></span>
                                <a href="<?php echo BASE_PATH; ?>/workflow/create/<?php echo (int)$template['id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-file-signature"></i> このテンプレートで申請
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info mb-0">申請可能なテンプレートがありません。</div>
            </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($totalPages) && $totalPages > 1): ?>
        <?php
        $buildPageUrl = function (int $targetPage) use ($search) {
            $params = ['page' => max(1, $targetPage)];
            if (is_scalar($search)) {
                $searchText = trim((string)$search);
                $decodedSearch = html_entity_decode($searchText, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $isSuspicious = preg_match('/(?:<|>|script|javascript:|pagespeed|data-pagespeed|onload=|onerror=)/iu', $decodedSearch);
                if ($searchText !== '' && !$isSuspicious) {
                    $params['search'] = $searchText;
                }
            }

            $query = http_build_query($params);
            return BASE_PATH . '/workflow/create' . ($query !== '' ? ('?' . $query) : '');
        };
        ?>
        <nav class="mt-4" aria-label="申請テンプレートページネーション">
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?php echo $page === $i ? 'active' : ''; ?>">
                        <a class="page-link" href="<?php echo htmlspecialchars($buildPageUrl($i), ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>

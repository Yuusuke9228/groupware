<?php
$pageTitle = 'アドレス帳 - TeamSpace';
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h4 class="mb-0"><i class="fas fa-address-book me-2"></i>アドレス帳</h4>
        <a href="<?= BASE_PATH ?>/address-book/create" class="btn btn-success btn-sm">
            <i class="fas fa-plus me-1"></i>連絡先を追加
        </a>
    </div>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['flash_message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['flash_error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <?php if ($contacts === null): ?>
        <!-- テーブルが未作成 -->
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-database fa-3x text-muted mb-3"></i>
                <h5>アドレス帳の初期設定</h5>
                <p class="text-muted mb-3">アドレス帳を使用するには、データベーステーブルの作成が必要です。<br>管理者にお問い合わせください。</p>
                <?php if ($this->auth->isAdmin()): ?>
                    <div class="alert alert-info text-start" style="max-width:700px;margin:0 auto;">
                        <strong><i class="fas fa-info-circle me-1"></i>管理者向け:</strong> 以下のSQLを実行してテーブルを作成してください。
                        <pre class="mt-2 p-2 bg-light rounded" style="font-size:12px;overflow-x:auto;">CREATE TABLE IF NOT EXISTS address_book (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    name_kana VARCHAR(100) DEFAULT '',
    company VARCHAR(200) DEFAULT '',
    department VARCHAR(100) DEFAULT '',
    position_title VARCHAR(100) DEFAULT '',
    email VARCHAR(200) DEFAULT '',
    phone VARCHAR(50) DEFAULT '',
    mobile VARCHAR(50) DEFAULT '',
    fax VARCHAR(50) DEFAULT '',
    postal_code VARCHAR(20) DEFAULT '',
    address TEXT,
    url VARCHAR(500) DEFAULT '',
    category VARCHAR(50) DEFAULT '',
    memo TEXT,
    created_by INT,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_name (name),
    INDEX idx_company (company),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;</pre>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <!-- 検索・フィルタ -->
        <div class="card mb-3">
            <div class="card-body py-2">
                <form method="get" class="no-ajax d-flex gap-2 align-items-center flex-wrap">
                    <div class="flex-grow-1" style="min-width:200px;">
                        <input type="text" class="form-control form-control-sm" name="q" placeholder="名前・会社名・メール・電話で検索..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <?php if (!empty($categories)): ?>
                    <select name="category" class="form-select form-select-sm" style="max-width:180px;">
                        <option value="">すべてのカテゴリ</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>" <?= $currentCategory === $cat ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i></button>
                    <?php if ($search || $currentCategory): ?>
                        <a href="<?= BASE_PATH ?>/address-book" class="btn btn-outline-secondary btn-sm">クリア</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <?php if (empty($contacts)): ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-address-book fa-3x text-muted mb-3"></i>
                    <h5>連絡先がありません</h5>
                    <p class="text-muted">「連絡先を追加」ボタンから新しい連絡先を登録してください。</p>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>名前</th>
                                <th class="d-none d-md-table-cell">会社名</th>
                                <th class="d-none d-lg-table-cell">部署</th>
                                <th>メール</th>
                                <th class="d-none d-md-table-cell">電話</th>
                                <th class="d-none d-lg-table-cell">カテゴリ</th>
                                <th style="width:80px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($contacts as $c): ?>
                            <tr>
                                <td>
                                    <a href="<?= BASE_PATH ?>/address-book/view/<?= $c['id'] ?>" class="text-decoration-none fw-bold">
                                        <?= htmlspecialchars($c['name']) ?>
                                    </a>
                                    <?php if (!empty($c['name_kana'])): ?>
                                        <br><small class="text-muted"><?= htmlspecialchars($c['name_kana']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="d-none d-md-table-cell"><?= htmlspecialchars($c['company'] ?? '') ?></td>
                                <td class="d-none d-lg-table-cell"><?= htmlspecialchars($c['department'] ?? '') ?></td>
                                <td>
                                    <?php if (!empty($c['email'])): ?>
                                        <a href="mailto:<?= htmlspecialchars($c['email']) ?>"><?= htmlspecialchars($c['email']) ?></a>
                                    <?php endif; ?>
                                </td>
                                <td class="d-none d-md-table-cell"><?= htmlspecialchars($c['phone'] ?? '') ?></td>
                                <td class="d-none d-lg-table-cell">
                                    <?php if (!empty($c['category'])): ?>
                                        <span class="badge bg-light text-dark"><?= htmlspecialchars($c['category']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= BASE_PATH ?>/address-book/edit/<?= $c['id'] ?>" class="btn btn-outline-primary" title="編集"><i class="fas fa-edit"></i></a>
                                        <a href="<?= BASE_PATH ?>/address-book/delete/<?= $c['id'] ?>" class="btn btn-outline-danger" title="削除" onclick="return confirm('この連絡先を削除しますか？')"><i class="fas fa-trash"></i></a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="text-muted small mt-2"><?= count($contacts) ?>件の連絡先</div>
        <?php endif; ?>
    <?php endif; ?>
</div>

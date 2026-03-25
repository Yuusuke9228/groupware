<?php
$pageTitle = ($mode === 'edit' ? '連絡先の編集' : '連絡先の追加') . ' - TeamSpace';
$action = $mode === 'edit'
    ? BASE_PATH . '/address-book/update/' . $contact['id']
    : BASE_PATH . '/address-book/store';
?>
<div class="container-fluid" style="max-width:800px;">
    <h4 class="mb-3">
        <i class="fas fa-<?= $mode === 'edit' ? 'edit' : 'plus' ?> me-2"></i>
        <?= $mode === 'edit' ? '連絡先の編集' : '連絡先の追加' ?>
    </h4>

    <form method="post" action="<?= $action ?>" class="no-ajax">
        <div class="card mb-3">
            <div class="card-header"><strong>基本情報</strong></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">名前 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($contact['name'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">フリガナ</label>
                        <input type="text" class="form-control" name="name_kana" value="<?= htmlspecialchars($contact['name_kana'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">会社名</label>
                        <input type="text" class="form-control" name="company" value="<?= htmlspecialchars($contact['company'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">カテゴリ</label>
                        <input type="text" class="form-control" name="category" value="<?= htmlspecialchars($contact['category'] ?? '') ?>" placeholder="例: 取引先、社内、個人">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">部署</label>
                        <input type="text" class="form-control" name="department" value="<?= htmlspecialchars($contact['department'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">役職</label>
                        <input type="text" class="form-control" name="position_title" value="<?= htmlspecialchars($contact['position_title'] ?? '') ?>">
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><strong>連絡先</strong></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">メールアドレス</label>
                        <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($contact['email'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">URL</label>
                        <input type="url" class="form-control" name="url" value="<?= htmlspecialchars($contact['url'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">電話番号</label>
                        <input type="tel" class="form-control" name="phone" value="<?= htmlspecialchars($contact['phone'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">携帯電話</label>
                        <input type="tel" class="form-control" name="mobile" value="<?= htmlspecialchars($contact['mobile'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">FAX</label>
                        <input type="tel" class="form-control" name="fax" value="<?= htmlspecialchars($contact['fax'] ?? '') ?>">
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><strong>住所</strong></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">郵便番号</label>
                        <input type="text" class="form-control" name="postal_code" value="<?= htmlspecialchars($contact['postal_code'] ?? '') ?>" placeholder="000-0000">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">住所</label>
                        <input type="text" class="form-control" name="address" value="<?= htmlspecialchars($contact['address'] ?? '') ?>">
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><strong>メモ</strong></div>
            <div class="card-body">
                <textarea class="form-control" name="memo" rows="3"><?= htmlspecialchars($contact['memo'] ?? '') ?></textarea>
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>保存</button>
            <a href="<?= BASE_PATH ?>/address-book" class="btn btn-outline-secondary">キャンセル</a>
        </div>
    </form>
</div>

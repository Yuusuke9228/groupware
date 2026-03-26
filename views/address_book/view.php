<?php $pageTitle = htmlspecialchars($contact['name']) . ' - アドレス帳'; ?>
<div class="container-fluid" style="max-width:800px;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0"><i class="fas fa-user me-2"></i><?= htmlspecialchars($contact['name']) ?></h4>
        <div class="btn-group btn-group-sm">
            <a href="<?= BASE_PATH ?>/address-book/export-vcard/<?= $contact['id'] ?>" class="btn btn-outline-success" title="vCardエクスポート"><i class="fas fa-download me-1"></i>vCard</a>
            <a href="<?= BASE_PATH ?>/address-book/edit/<?= $contact['id'] ?>" class="btn btn-outline-primary"><i class="fas fa-edit me-1"></i>編集</a>
            <a href="<?= BASE_PATH ?>/address-book" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>一覧へ</a>
        </div>
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

    <!-- 名刺画像セクション -->
    <?php if (!empty($businessCard)): ?>
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong><i class="fas fa-id-card me-1"></i>名刺</strong>
            <div class="btn-group btn-group-sm">
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="document.getElementById('replaceCardInput').click();" title="画像を差し替え">
                    <i class="fas fa-sync-alt me-1"></i>差し替え
                </button>
                <button type="button" class="btn btn-outline-danger btn-sm" onclick="deleteBusinessCard(<?= $contact['id'] ?>)" title="名刺画像を削除">
                    <i class="fas fa-trash me-1"></i>削除
                </button>
            </div>
        </div>
        <div class="card-body text-center p-4">
            <div style="background:linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);border-radius:12px;padding:20px;box-shadow:0 8px 25px rgba(0,0,0,0.15);display:inline-block;max-width:100%;position:relative;">
                <img src="<?= BASE_PATH ?>/address-book/card-image/<?= $contact['id'] ?>?t=<?= time() ?>" alt="名刺画像"
                     style="max-width:100%;max-height:400px;border-radius:8px;display:block;">
            </div>
            <?php if (!empty($businessCard['ocr_raw_text'])): ?>
                <div class="mt-3">
                    <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#ocrTextCollapse">
                        <i class="fas fa-file-alt me-1"></i>OCRテキストを表示
                    </button>
                    <div class="collapse mt-2" id="ocrTextCollapse">
                        <div class="card card-body text-start" style="background:#f8f9fa;font-size:0.85rem;">
                            <pre class="mb-0" style="white-space:pre-wrap;word-break:break-all;"><?= htmlspecialchars($businessCard['ocr_raw_text']) ?></pre>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <!-- 隠しファイル入力（差し替え用） -->
    <form id="replaceCardForm" class="no-ajax" style="display:none;">
        <input type="file" id="replaceCardInput" accept="image/*" capture="environment" onchange="replaceBusinessCard(<?= $contact['id'] ?>, this)">
    </form>
    <?php else: ?>
    <!-- 名刺がない場合のアップロードセクション -->
    <div class="card mb-3">
        <div class="card-body text-center py-4">
            <div class="text-muted mb-3">
                <i class="fas fa-id-card fa-2x mb-2 d-block"></i>
                名刺画像が登録されていません
            </div>
            <button type="button" class="btn btn-outline-primary btn-sm" onclick="document.getElementById('uploadCardInput').click();">
                <i class="fas fa-upload me-1"></i>名刺画像をアップロード
            </button>
            <form id="uploadCardForm" class="no-ajax" style="display:none;">
                <input type="file" id="uploadCardInput" accept="image/*" capture="environment" onchange="uploadBusinessCard(<?= $contact['id'] ?>, this)">
            </form>
        </div>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
            <table class="table table-borderless mb-0">
                <?php
                $fields = [
                    'name_kana' => 'フリガナ', 'company' => '会社名', 'department' => '部署',
                    'position_title' => '役職', 'email' => 'メール', 'phone' => '電話',
                    'mobile' => '携帯', 'fax' => 'FAX', 'postal_code' => '郵便番号',
                    'address' => '住所', 'url' => 'URL', 'category' => 'カテゴリ', 'memo' => 'メモ'
                ];
                foreach ($fields as $key => $label):
                    $val = $contact[$key] ?? '';
                    if ($val === '') continue;
                ?>
                <tr>
                    <th style="width:120px;color:var(--text-secondary);"><?= $label ?></th>
                    <td>
                        <?php if ($key === 'email'): ?>
                            <a href="mailto:<?= htmlspecialchars($val) ?>"><?= htmlspecialchars($val) ?></a>
                        <?php elseif ($key === 'url'): ?>
                            <a href="<?= htmlspecialchars($val) ?>" target="_blank"><?= htmlspecialchars($val) ?></a>
                        <?php elseif ($key === 'phone' || $key === 'mobile'): ?>
                            <a href="tel:<?= htmlspecialchars($val) ?>"><?= htmlspecialchars($val) ?></a>
                        <?php else: ?>
                            <?= nl2br(htmlspecialchars($val)) ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
            </div>
        </div>
        <div class="card-footer small text-muted">
            登録日: <?= date('Y/m/d H:i', strtotime($contact['created_at'])) ?>
            | 更新日: <?= date('Y/m/d H:i', strtotime($contact['updated_at'])) ?>
        </div>
    </div>
</div>

<script>
function uploadBusinessCard(contactId, input) {
    if (!input.files || !input.files[0]) return;
    const formData = new FormData();
    formData.append('business_card_image', input.files[0]);

    fetch('<?= BASE_PATH ?>/address-book/upload-card/' + contactId, {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'アップロードに失敗しました。');
        }
    })
    .catch(() => alert('アップロードに失敗しました。'));
}

function replaceBusinessCard(contactId, input) {
    uploadBusinessCard(contactId, input);
}

function deleteBusinessCard(contactId) {
    if (!confirm('名刺画像を削除しますか？')) return;

    fetch('<?= BASE_PATH ?>/address-book/delete-card/' + contactId, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || '削除に失敗しました。');
        }
    })
    .catch(() => alert('削除に失敗しました。'));
}
</script>

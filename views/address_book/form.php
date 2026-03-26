<?php
$pageTitle = ($mode === 'edit' ? '連絡先の編集' : ($mode === 'create_from_card' ? '名刺から連絡先を追加' : '連絡先の追加')) ;
$isFromCard = ($mode === 'create_from_card');
$action = $mode === 'edit'
    ? BASE_PATH . '/address-book/update/' . $contact['id']
    : ($isFromCard ? BASE_PATH . '/address-book/store-from-card' : BASE_PATH . '/address-book/store');
?>
<div class="container-fluid" style="max-width:800px;">
    <h4 class="mb-3">
        <i class="fas fa-<?= $mode === 'edit' ? 'edit' : ($isFromCard ? 'id-card' : 'plus') ?> me-2"></i>
        <?= $mode === 'edit' ? '連絡先の編集' : ($isFromCard ? '名刺から連絡先を追加' : '連絡先の追加') ?>
    </h4>

    <?php if ($isFromCard): ?>
    <!-- 名刺アップロードセクション -->
    <div class="card mb-3" id="businessCardUploadSection">
        <div class="card-header"><strong><i class="fas fa-camera me-1"></i>名刺画像のアップロード</strong></div>
        <div class="card-body">
            <div id="cardDropZone" class="text-center p-4" style="border:2px dashed #dee2e6;border-radius:12px;cursor:pointer;transition:all 0.3s;"
                 ondragover="event.preventDefault();this.style.borderColor='#0d6efd';this.style.background='#f0f7ff';"
                 ondragleave="this.style.borderColor='#dee2e6';this.style.background='';"
                 ondrop="handleCardDrop(event)"
                 onclick="document.getElementById('cardFileInput').click();">
                <div id="cardDropPlaceholder">
                    <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3 d-block"></i>
                    <p class="text-muted mb-1">名刺画像をドラッグ＆ドロップ、またはクリックして選択</p>
                    <small class="text-muted">JPEG, PNG, GIF, WebP, BMP (最大10MB)</small>
                    <br><small class="text-muted mt-1 d-block">スマートフォンではカメラで直接撮影も可能です</small>
                </div>
                <div id="cardPreviewContainer" style="display:none;">
                    <div style="background:linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);border-radius:12px;padding:16px;box-shadow:0 4px 12px rgba(0,0,0,0.15);display:inline-block;max-width:100%;">
                        <img id="cardPreviewImg" src="" alt="名刺プレビュー" style="max-width:100%;max-height:300px;border-radius:8px;">
                    </div>
                </div>
            </div>
            <input type="file" id="cardFileInput" accept="image/*" capture="environment" style="display:none;" onchange="handleCardSelect(this)">

            <div id="cardActions" style="display:none;" class="mt-3 d-flex gap-2 justify-content-center flex-wrap">
                <button type="button" class="btn btn-primary btn-sm" onclick="runOcr()" id="ocrButton">
                    <i class="fas fa-magic me-1"></i>OCR読み取り
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="resetCardUpload()">
                    <i class="fas fa-redo me-1"></i>別の画像を選択
                </button>
            </div>

            <div id="ocrProgress" style="display:none;" class="mt-3 text-center">
                <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                <span>OCR読み取り中...</span>
            </div>

            <div id="ocrResult" style="display:none;" class="mt-3">
                <div class="alert alert-success" id="ocrSuccessMsg" style="display:none;">
                    <i class="fas fa-check-circle me-1"></i>OCR読み取りが完了しました。下記のフォームに自動入力されました。内容を確認して保存してください。
                </div>
                <div class="alert alert-warning" id="ocrFailMsg" style="display:none;">
                    <i class="fas fa-exclamation-triangle me-1"></i>OCRの実行に失敗しました。名刺画像を参考に手動で入力してください。
                </div>
                <div id="duplicateWarning" style="display:none;" class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-1"></i><strong>類似する連絡先が見つかりました：</strong>
                    <ul id="duplicateList" class="mb-0 mt-1"></ul>
                </div>
                <div class="mt-2">
                    <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#rawOcrText">
                        <i class="fas fa-file-alt me-1"></i>OCR生テキストを表示
                    </button>
                    <div class="collapse mt-2" id="rawOcrText">
                        <pre class="p-2 bg-light rounded" style="font-size:12px;max-height:200px;overflow:auto;" id="ocrRawTextContent"></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($mode === 'edit' && !empty($businessCard)): ?>
    <!-- 編集時の名刺画像表示 -->
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong><i class="fas fa-id-card me-1"></i>名刺画像</strong>
            <div class="btn-group btn-group-sm">
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="document.getElementById('editCardInput').click();">
                    <i class="fas fa-sync-alt me-1"></i>差し替え
                </button>
            </div>
        </div>
        <div class="card-body text-center">
            <div style="background:#f8f9fa;border-radius:8px;padding:12px;display:inline-block;max-width:100%;box-shadow:0 2px 8px rgba(0,0,0,0.1);">
                <img src="<?= BASE_PATH ?>/address-book/card-image/<?= $contact['id'] ?>?t=<?= time() ?>" alt="名刺画像"
                     style="max-width:100%;max-height:250px;border-radius:6px;">
            </div>
        </div>
    </div>
    <?php endif; ?>

    <form method="post" action="<?= $action ?>" class="no-ajax" enctype="multipart/form-data" id="contactForm">
        <?php if ($isFromCard): ?>
            <input type="hidden" name="temp_card_filename" id="tempCardFilename" value="">
            <input type="hidden" name="ocr_raw_text" id="ocrRawTextHidden" value="">
        <?php endif; ?>

        <?php if ($mode === 'edit'): ?>
            <input type="file" id="editCardInput" name="business_card_image" accept="image/*" capture="environment" style="display:none;">
        <?php endif; ?>

        <div class="card mb-3">
            <div class="card-header"><strong>基本情報</strong></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">名前 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" id="field_name" value="<?= htmlspecialchars($contact['name'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">フリガナ</label>
                        <input type="text" class="form-control" name="name_kana" id="field_name_kana" value="<?= htmlspecialchars($contact['name_kana'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">会社名</label>
                        <input type="text" class="form-control" name="company" id="field_company" value="<?= htmlspecialchars($contact['company'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">カテゴリ</label>
                        <input type="text" class="form-control" name="category" id="field_category" value="<?= htmlspecialchars($contact['category'] ?? '') ?>" placeholder="例: 取引先、社内、個人">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">部署</label>
                        <input type="text" class="form-control" name="department" id="field_department" value="<?= htmlspecialchars($contact['department'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">役職</label>
                        <input type="text" class="form-control" name="position_title" id="field_position_title" value="<?= htmlspecialchars($contact['position_title'] ?? '') ?>">
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
                        <input type="email" class="form-control" name="email" id="field_email" value="<?= htmlspecialchars($contact['email'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">URL</label>
                        <input type="url" class="form-control" name="url" id="field_url" value="<?= htmlspecialchars($contact['url'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">電話番号</label>
                        <input type="tel" class="form-control" name="phone" id="field_phone" value="<?= htmlspecialchars($contact['phone'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">携帯電話</label>
                        <input type="tel" class="form-control" name="mobile" id="field_mobile" value="<?= htmlspecialchars($contact['mobile'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">FAX</label>
                        <input type="tel" class="form-control" name="fax" id="field_fax" value="<?= htmlspecialchars($contact['fax'] ?? '') ?>">
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
                        <input type="text" class="form-control" name="postal_code" id="field_postal_code" value="<?= htmlspecialchars($contact['postal_code'] ?? '') ?>" placeholder="000-0000">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">住所</label>
                        <input type="text" class="form-control" name="address" id="field_address" value="<?= htmlspecialchars($contact['address'] ?? '') ?>">
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><strong>メモ</strong></div>
            <div class="card-body">
                <textarea class="form-control" name="memo" id="field_memo" rows="3"><?= htmlspecialchars($contact['memo'] ?? '') ?></textarea>
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>保存</button>
            <a href="<?= BASE_PATH ?>/address-book" class="btn btn-outline-secondary">キャンセル</a>
        </div>
    </form>
</div>

<?php if ($isFromCard): ?>
<script>
let currentTempFilename = '';

function handleCardDrop(e) {
    e.preventDefault();
    e.target.closest('#cardDropZone').style.borderColor = '#dee2e6';
    e.target.closest('#cardDropZone').style.background = '';

    const files = e.dataTransfer.files;
    if (files.length > 0) {
        processCardFile(files[0]);
    }
}

function handleCardSelect(input) {
    if (input.files && input.files[0]) {
        processCardFile(input.files[0]);
    }
}

function processCardFile(file) {
    // プレビュー表示
    const reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById('cardPreviewImg').src = e.target.result;
        document.getElementById('cardDropPlaceholder').style.display = 'none';
        document.getElementById('cardPreviewContainer').style.display = 'block';
        document.getElementById('cardActions').style.display = 'flex';
    };
    reader.readAsDataURL(file);

    // サーバーにアップロード
    const formData = new FormData();
    formData.append('business_card_image', file);

    document.getElementById('ocrProgress').style.display = 'none';
    document.getElementById('ocrResult').style.display = 'none';

    fetch('<?= BASE_PATH ?>/address-book/upload-and-ocr', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            currentTempFilename = data.temp_filename;
            document.getElementById('tempCardFilename').value = data.temp_filename;

            // 自動的にOCR結果がある場合はフォームに入力
            if (data.ocr_status === 'completed' && data.parsed_fields) {
                applyOcrFields(data.parsed_fields);
                document.getElementById('ocrRawTextContent').textContent = data.raw_text || '';
                document.getElementById('ocrRawTextHidden').value = data.raw_text || '';
                document.getElementById('ocrResult').style.display = 'block';
                document.getElementById('ocrSuccessMsg').style.display = 'block';
                document.getElementById('ocrFailMsg').style.display = 'none';

                // 重複チェック
                if (data.duplicates && data.duplicates.length > 0) {
                    showDuplicates(data.duplicates);
                }
            }
        }
    })
    .catch(err => {
        console.error('Upload error:', err);
    });
}

function runOcr() {
    if (!currentTempFilename) {
        alert('先に名刺画像をアップロードしてください。');
        return;
    }

    document.getElementById('ocrProgress').style.display = 'block';
    document.getElementById('ocrButton').disabled = true;

    // 再度OCR実行（アップロード時に既に実行済みだが明示的に再実行可能）
    const formData = new FormData();
    formData.append('business_card_image', document.getElementById('cardFileInput').files[0]);

    fetch('<?= BASE_PATH ?>/address-book/upload-and-ocr', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        document.getElementById('ocrProgress').style.display = 'none';
        document.getElementById('ocrButton').disabled = false;
        document.getElementById('ocrResult').style.display = 'block';

        if (data.success && data.ocr_status === 'completed') {
            currentTempFilename = data.temp_filename;
            document.getElementById('tempCardFilename').value = data.temp_filename;
            document.getElementById('ocrSuccessMsg').style.display = 'block';
            document.getElementById('ocrFailMsg').style.display = 'none';
            document.getElementById('ocrRawTextContent').textContent = data.raw_text || '';
            document.getElementById('ocrRawTextHidden').value = data.raw_text || '';
            applyOcrFields(data.parsed_fields);

            if (data.duplicates && data.duplicates.length > 0) {
                showDuplicates(data.duplicates);
            }
        } else {
            document.getElementById('ocrSuccessMsg').style.display = 'none';
            document.getElementById('ocrFailMsg').style.display = 'block';
        }
    })
    .catch(() => {
        document.getElementById('ocrProgress').style.display = 'none';
        document.getElementById('ocrButton').disabled = false;
        document.getElementById('ocrResult').style.display = 'block';
        document.getElementById('ocrSuccessMsg').style.display = 'none';
        document.getElementById('ocrFailMsg').style.display = 'block';
    });
}

function applyOcrFields(fields) {
    const fieldMap = ['name', 'name_kana', 'company', 'department', 'position_title', 'email', 'phone', 'mobile', 'fax', 'postal_code', 'address', 'url'];

    fieldMap.forEach(function(key) {
        const el = document.getElementById('field_' + key);
        if (el && fields[key]) {
            // 既存の値がなければ上書き、あれば上書きしない
            if (!el.value.trim()) {
                el.value = fields[key];
                el.style.backgroundColor = '#f0fff4';
                setTimeout(function() { el.style.backgroundColor = ''; }, 3000);
            }
        }
    });
}

function showDuplicates(duplicates) {
    const container = document.getElementById('duplicateWarning');
    const list = document.getElementById('duplicateList');
    list.innerHTML = '';

    duplicates.forEach(function(dup) {
        const li = document.createElement('li');
        const link = document.createElement('a');
        link.href = '<?= BASE_PATH ?>/address-book/view/' + dup.id;
        link.target = '_blank';
        link.textContent = dup.name + (dup.company ? ' (' + dup.company + ')' : '') + (dup.email ? ' - ' + dup.email : '');
        li.appendChild(link);
        list.appendChild(li);
    });

    container.style.display = 'block';
}

function resetCardUpload() {
    document.getElementById('cardDropPlaceholder').style.display = 'block';
    document.getElementById('cardPreviewContainer').style.display = 'none';
    document.getElementById('cardActions').style.display = 'none';
    document.getElementById('ocrResult').style.display = 'none';
    document.getElementById('ocrProgress').style.display = 'none';
    document.getElementById('duplicateWarning').style.display = 'none';
    document.getElementById('cardFileInput').value = '';
    currentTempFilename = '';
    document.getElementById('tempCardFilename').value = '';
}
</script>
<?php endif; ?>

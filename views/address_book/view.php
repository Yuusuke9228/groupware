<?php $pageTitle = htmlspecialchars($contact['name']) . ' - アドレス帳'; ?>
<div class="container-fluid" style="max-width:800px;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0"><i class="fas fa-user me-2"></i><?= htmlspecialchars($contact['name']) ?></h4>
        <div class="btn-group btn-group-sm">
            <a href="<?= BASE_PATH ?>/address-book/edit/<?= $contact['id'] ?>" class="btn btn-outline-primary"><i class="fas fa-edit me-1"></i>編集</a>
            <a href="<?= BASE_PATH ?>/address-book" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>一覧へ</a>
        </div>
    </div>

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

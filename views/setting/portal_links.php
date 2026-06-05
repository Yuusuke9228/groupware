<!-- views/setting/portal_links.php -->
<div class="container">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="h3 mb-2"><?= htmlspecialchars(tr_text('ポータルリンク設定', 'Portal link settings')) ?></h1>
            <p class="text-muted"><?= htmlspecialchars(tr_text('ホームに表示する社内システム入口リンクを管理します。', 'Manage internal system entry links shown on Home.')) ?></p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3">
            <div class="card">
                <div class="card-header"><?= htmlspecialchars(t('settings.menu')) ?></div>
                <div class="list-group list-group-flush">
                    <a href="<?= BASE_PATH ?>/settings" class="list-group-item list-group-item-action"><?= htmlspecialchars(t('settings.menu.basic')) ?></a>
                    <a href="<?= BASE_PATH ?>/settings/smtp" class="list-group-item list-group-item-action"><?= htmlspecialchars(t('settings.menu.smtp')) ?></a>
                    <a href="<?= BASE_PATH ?>/settings/notification" class="list-group-item list-group-item-action"><?= htmlspecialchars(t('settings.menu.notification')) ?></a>
                    <a href="<?= BASE_PATH ?>/settings/portal-links" class="list-group-item list-group-item-action active"><?= htmlspecialchars(tr_text('ポータルリンク', 'Portal links')) ?></a>
                    <a href="<?= BASE_PATH ?>/settings/security" class="list-group-item list-group-item-action"><?= htmlspecialchars(t('settings.menu.security')) ?></a>
                    <a href="<?= BASE_PATH ?>/settings/security#backup-management" class="list-group-item list-group-item-action"><?= htmlspecialchars(t('settings.menu.backup')) ?></a>
                </div>
            </div>
        </div>

        <div class="col-md-9">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0"><?= htmlspecialchars(tr_text('新規ポータルリンク', 'New portal link')) ?></h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-success d-none" id="portalLinkSuccessAlert"></div>
                    <div class="alert alert-danger d-none" id="portalLinkErrorAlert"></div>

                    <form id="portalLinkForm">
                        <input type="hidden" name="id" id="portal_link_id" value="">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="portal_link_title" class="form-label"><?= htmlspecialchars(tr_text('タイトル', 'Title')) ?> <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="portal_link_title" name="title" maxlength="120" required>
                            </div>
                            <div class="col-md-6">
                                <label for="portal_link_url" class="form-label">URL <span class="text-danger">*</span></label>
                                <input type="url" class="form-control" id="portal_link_url" name="url" placeholder="https://example.com/" required>
                            </div>
                            <div class="col-md-6">
                                <label for="portal_link_icon_class" class="form-label"><?= htmlspecialchars(tr_text('アイコン', 'Icon')) ?></label>
                                <input type="text" class="form-control" id="portal_link_icon_class" name="icon_class" value="fas fa-link" placeholder="fas fa-link">
                                <div class="form-text"><?= htmlspecialchars(tr_text('Font Awesome のクラスを指定できます。例: fas fa-building', 'Use Font Awesome classes. Example: fas fa-building')) ?></div>
                            </div>
                            <div class="col-md-3">
                                <label for="portal_link_target" class="form-label"><?= htmlspecialchars(tr_text('開き方', 'Open in')) ?></label>
                                <select class="form-select" id="portal_link_target" name="target">
                                    <option value="_blank"><?= htmlspecialchars(tr_text('新しいタブ', 'New tab')) ?></option>
                                    <option value="_self"><?= htmlspecialchars(tr_text('同じタブ', 'Same tab')) ?></option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="portal_link_sort_order" class="form-label"><?= htmlspecialchars(tr_text('表示順', 'Sort order')) ?></label>
                                <input type="number" class="form-control" id="portal_link_sort_order" name="sort_order" value="0">
                            </div>
                            <div class="col-md-12">
                                <label for="portal_link_description" class="form-label"><?= htmlspecialchars(tr_text('説明', 'Description')) ?></label>
                                <textarea class="form-control" id="portal_link_description" name="description" rows="2"></textarea>
                            </div>
                            <div class="col-md-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="portal_link_is_active" name="is_active" checked>
                                    <label class="form-check-label" for="portal_link_is_active"><?= htmlspecialchars(tr_text('ホームに表示する', 'Show on Home')) ?></label>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-3">
                            <button type="submit" class="btn btn-primary" id="portalLinkSubmitBtn"><?= htmlspecialchars(tr_text('登録', 'Create')) ?></button>
                            <button type="button" class="btn btn-outline-secondary d-none" id="portalLinkCancelBtn"><?= htmlspecialchars(tr_text('キャンセル', 'Cancel')) ?></button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><?= htmlspecialchars(tr_text('登録済みリンク', 'Registered links')) ?></h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th><?= htmlspecialchars(tr_text('表示順', 'Sort')) ?></th>
                                    <th><?= htmlspecialchars(tr_text('タイトル', 'Title')) ?></th>
                                    <th>URL</th>
                                    <th><?= htmlspecialchars(tr_text('状態', 'Status')) ?></th>
                                    <th><?= htmlspecialchars(tr_text('操作', 'Actions')) ?></th>
                                </tr>
                            </thead>
                            <tbody id="portalLinkTableBody">
                                <?php if (empty($portalLinks)): ?>
                                    <tr><td colspan="5" class="text-center text-muted"><?= htmlspecialchars(tr_text('ポータルリンクはまだ登録されていません。', 'No portal links have been registered.')) ?></td></tr>
                                <?php else: ?>
                                    <?php foreach ($portalLinks as $link): ?>
                                        <tr data-portal-link='<?= htmlspecialchars(json_encode($link, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') ?>'>
                                            <td><?= (int)$link['sort_order'] ?></td>
                                            <td>
                                                <i class="<?= htmlspecialchars($link['icon_class'] ?: 'fas fa-link') ?> me-1"></i>
                                                <?= htmlspecialchars($link['title']) ?>
                                                <?php if (!empty($link['description'])): ?>
                                                    <div class="small text-muted"><?= htmlspecialchars($link['description']) ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td><a href="<?= htmlspecialchars($link['url']) ?>" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars($link['url']) ?></a></td>
                                            <td>
                                                <?php if ((int)$link['is_active'] === 1): ?>
                                                    <span class="badge bg-success"><?= htmlspecialchars(tr_text('表示中', 'Visible')) ?></span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary"><?= htmlspecialchars(tr_text('非表示', 'Hidden')) ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-outline-primary portal-link-edit"><?= htmlspecialchars(tr_text('編集', 'Edit')) ?></button>
                                                <button type="button" class="btn btn-sm btn-outline-danger portal-link-delete" data-id="<?= (int)$link['id'] ?>"><?= htmlspecialchars(tr_text('削除', 'Delete')) ?></button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <p class="small text-muted mb-0">
                        <?= htmlspecialchars(tr_text('変更後はホームを再読み込みすると社内システム入口に反映されます。', 'Changes appear in Internal systems on Home after reloading Home.')) ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

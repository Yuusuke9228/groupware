<?php
$isJaLocale = get_locale() === 'ja';
$currentUserId = (int)$this->auth->id();

$personalBoards = [];
$teamBoards = [];
$organizationBoards = [];
foreach (($boards ?? []) as $board) {
    $type = $board['owner_type'] ?? 'user';
    if ($type === 'team') {
        $teamBoards[] = $board;
    } elseif ($type === 'organization') {
        $organizationBoards[] = $board;
    } else {
        $personalBoards[] = $board;
    }
}
?>

<style>
.vb-index-wrap { padding: 16px 0 24px; }
.vb-board-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 14px; }
.vb-board-card { border: 1px solid #dbe3ef; border-radius: 12px; background: #fff; transition: box-shadow .2s, transform .2s; }
.vb-board-card:hover { box-shadow: 0 10px 20px rgba(30, 55, 90, .08); transform: translateY(-1px); }
.vb-board-link { display: block; color: inherit; text-decoration: none; padding: 14px; }
.vb-board-meta { font-size: 12px; color: #5f6f86; margin-top: 6px; }
.vb-board-actions { padding: 0 14px 12px; }
.vb-section-title { display: flex; align-items: center; gap: 8px; margin: 20px 0 10px; font-size: 17px; }
.vb-empty { border: 1px dashed #c8d6ea; border-radius: 10px; color: #4c5d74; padding: 16px; background: #f7fafe; }
.vb-owner-badge { font-size: 11px; padding: 2px 8px; border-radius: 999px; background: #eef4ff; color: #315d9b; }
@media (max-width: 768px) {
    .vb-index-wrap { padding-top: 10px; }
}
</style>

<div class="container-fluid vb-index-wrap">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h4 class="mb-1"><?= htmlspecialchars(tr_text('Visual Boards', 'Visual Boards')) ?></h4>
            <div class="text-muted small"><?= htmlspecialchars(tr_text('思考整理用のノードボード（カンバンとは別機能）', 'Node-based boards for structuring ideas (separate from Kanban task boards).')) ?></div>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= BASE_PATH ?>/visual-boards/create-board" class="btn btn-primary">
                <i class="fas fa-plus-circle me-1"></i><?= htmlspecialchars(tr_text('新規Visual Board', 'New Visual Board')) ?>
            </a>
        </div>
    </div>

    <section>
        <h5 class="vb-section-title">
            <i class="fas fa-user"></i><?= htmlspecialchars(tr_text('個人ボード', 'Personal Boards')) ?>
        </h5>
        <?php if (empty($personalBoards)): ?>
            <div class="vb-empty"><?= htmlspecialchars(tr_text('個人ボードはまだありません。', 'No personal boards yet.')) ?></div>
        <?php else: ?>
            <div class="vb-board-grid">
                <?php foreach ($personalBoards as $board): ?>
                    <article class="vb-board-card">
                        <a class="vb-board-link" href="<?= BASE_PATH ?>/visual-boards/board/<?= (int)$board['id'] ?>">
                            <div class="d-flex align-items-center justify-content-between gap-2">
                                <strong><?= htmlspecialchars($board['name']) ?></strong>
                                <span class="vb-owner-badge"><?= htmlspecialchars(tr_text('個人', 'Personal')) ?></span>
                            </div>
                            <?php if (!empty($board['description'])): ?>
                                <div class="small text-muted mt-2"><?= htmlspecialchars(mb_strimwidth((string)$board['description'], 0, 90, '...')) ?></div>
                            <?php endif; ?>
                            <div class="vb-board-meta">
                                <span><?= htmlspecialchars(tr_text('テンプレート', 'Template')) ?>: <?= htmlspecialchars($board['template_key']) ?></span><br>
                                <span><?= htmlspecialchars(tr_text('更新', 'Updated')) ?>: <?= htmlspecialchars(date('Y-m-d H:i', strtotime((string)$board['updated_at']))) ?></span>
                            </div>
                        </a>
                        <?php if ($this->auth->isAdmin() || (int)$board['created_by'] === $currentUserId): ?>
                            <div class="vb-board-actions">
                                <button class="btn btn-sm btn-outline-danger js-delete-vb" data-board-id="<?= (int)$board['id'] ?>" data-board-name="<?= htmlspecialchars($board['name']) ?>">
                                    <i class="fas fa-trash-alt me-1"></i><?= htmlspecialchars(tr_text('削除', 'Delete')) ?>
                                </button>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section>
        <h5 class="vb-section-title">
            <i class="fas fa-users"></i><?= htmlspecialchars(tr_text('チームボード', 'Team Boards')) ?>
        </h5>
        <?php if (empty($teamBoards)): ?>
            <div class="vb-empty"><?= htmlspecialchars(tr_text('チームボードはまだありません。', 'No team boards yet.')) ?></div>
        <?php else: ?>
            <div class="vb-board-grid">
                <?php foreach ($teamBoards as $board): ?>
                    <article class="vb-board-card">
                        <a class="vb-board-link" href="<?= BASE_PATH ?>/visual-boards/board/<?= (int)$board['id'] ?>">
                            <div class="d-flex align-items-center justify-content-between gap-2">
                                <strong><?= htmlspecialchars($board['name']) ?></strong>
                                <span class="vb-owner-badge"><?= htmlspecialchars(tr_text('チーム', 'Team')) ?></span>
                            </div>
                            <?php if (!empty($board['description'])): ?>
                                <div class="small text-muted mt-2"><?= htmlspecialchars(mb_strimwidth((string)$board['description'], 0, 90, '...')) ?></div>
                            <?php endif; ?>
                            <div class="vb-board-meta">
                                <span><?= htmlspecialchars(tr_text('オーナーID', 'Owner ID')) ?>: <?= (int)$board['owner_id'] ?></span><br>
                                <span><?= htmlspecialchars(tr_text('更新', 'Updated')) ?>: <?= htmlspecialchars(date('Y-m-d H:i', strtotime((string)$board['updated_at']))) ?></span>
                            </div>
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section>
        <h5 class="vb-section-title">
            <i class="fas fa-sitemap"></i><?= htmlspecialchars(tr_text('組織ボード', 'Organization Boards')) ?>
        </h5>
        <?php if (empty($organizationBoards)): ?>
            <div class="vb-empty"><?= htmlspecialchars(tr_text('組織ボードはまだありません。', 'No organization boards yet.')) ?></div>
        <?php else: ?>
            <div class="vb-board-grid">
                <?php foreach ($organizationBoards as $board): ?>
                    <article class="vb-board-card">
                        <a class="vb-board-link" href="<?= BASE_PATH ?>/visual-boards/board/<?= (int)$board['id'] ?>">
                            <div class="d-flex align-items-center justify-content-between gap-2">
                                <strong><?= htmlspecialchars($board['name']) ?></strong>
                                <span class="vb-owner-badge"><?= htmlspecialchars(tr_text('組織', 'Organization')) ?></span>
                            </div>
                            <?php if (!empty($board['description'])): ?>
                                <div class="small text-muted mt-2"><?= htmlspecialchars(mb_strimwidth((string)$board['description'], 0, 90, '...')) ?></div>
                            <?php endif; ?>
                            <div class="vb-board-meta">
                                <span><?= htmlspecialchars(tr_text('オーナーID', 'Owner ID')) ?>: <?= (int)$board['owner_id'] ?></span><br>
                                <span><?= htmlspecialchars(tr_text('更新', 'Updated')) ?>: <?= htmlspecialchars(date('Y-m-d H:i', strtotime((string)$board['updated_at']))) ?></span>
                            </div>
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<script>
(function () {
    const deleteButtons = Array.from(document.querySelectorAll('.js-delete-vb'));
    if (!deleteButtons.length) {
        return;
    }

    const t = {
        confirm: <?= json_encode(tr_text('ボード「{name}」を削除します。よろしいですか？', 'Delete board "{name}"?')) ?>,
        failed: <?= json_encode(tr_text('削除に失敗しました。', 'Failed to delete board.')) ?>,
        communication: <?= json_encode(tr_text('通信エラーが発生しました。', 'A communication error occurred.')) ?>
    };

    deleteButtons.forEach((button) => {
        button.addEventListener('click', async () => {
            const boardId = Number(button.dataset.boardId || 0);
            const boardName = String(button.dataset.boardName || '');
            if (!boardId) return;

            const message = t.confirm.replace('{name}', boardName);
            if (!window.confirm(message)) return;

            button.disabled = true;
            try {
                const res = await fetch(`${BASE_PATH}/api/visual-boards/boards/${boardId}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const json = await res.json().catch(() => ({}));
                if (!res.ok || !json.success) {
                    alert(json.error || t.failed);
                    button.disabled = false;
                    return;
                }
                window.location.reload();
            } catch (err) {
                alert(t.communication);
                button.disabled = false;
            }
        });
    });
})();
</script>

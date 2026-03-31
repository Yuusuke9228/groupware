<?php
$userId = (int)$this->auth->id();
?>

<style>
.vb-create-wrap { max-width: 920px; margin: 18px auto; }
.vb-create-card { border: 1px solid #d9e3f0; border-radius: 14px; box-shadow: 0 6px 18px rgba(29, 53, 87, 0.06); }
.vb-template-grid { display: grid; gap: 10px; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); }
.vb-template-item { border: 1px solid #c8d8ee; border-radius: 10px; padding: 10px 12px; cursor: pointer; background: #fff; transition: border-color .2s, background .2s; }
.vb-template-item.active { border-color: #2f73d9; background: #edf4ff; }
.vb-helper { font-size: 12px; color: #5f6f86; }
</style>

<div class="container-fluid">
    <div class="vb-create-wrap">
        <nav aria-label="breadcrumb" class="mb-2">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= BASE_PATH ?>/visual-boards"><?= htmlspecialchars(tr_text('Visual Boards', 'Visual Boards')) ?></a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars(tr_text('新規作成', 'Create')) ?></li>
            </ol>
        </nav>

        <div class="card vb-create-card">
            <div class="card-body p-4">
                <h4 class="mb-1"><?= htmlspecialchars(tr_text('Visual Boardを作成', 'Create Visual Board')) ?></h4>
                <p class="text-muted mb-4"><?= htmlspecialchars(tr_text('思考整理用ボードを作成します。既存のタスクカンバンとは別機能です。', 'Create a node-based board for idea structuring. This is separate from Kanban task boards.')) ?></p>

                <form id="vbCreateForm" novalidate>
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label for="vbName" class="form-label"><?= htmlspecialchars(tr_text('ボード名', 'Board name')) ?> <span class="text-danger">*</span></label>
                            <input type="text" id="vbName" class="form-control" required maxlength="120" placeholder="<?= htmlspecialchars(tr_text('例: 2026年Q3施策整理', 'e.g. Q3 2026 Initiative Planning')) ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="vbOwnerType" class="form-label"><?= htmlspecialchars(tr_text('共有範囲', 'Sharing scope')) ?></label>
                            <select id="vbOwnerType" class="form-select">
                                <option value="user"><?= htmlspecialchars(tr_text('個人', 'Personal')) ?></option>
                                <option value="team"><?= htmlspecialchars(tr_text('チーム', 'Team')) ?></option>
                                <option value="organization"><?= htmlspecialchars(tr_text('組織', 'Organization')) ?></option>
                            </select>
                        </div>
                        <div class="col-md-6" id="vbTeamWrap" style="display:none;">
                            <label for="vbTeam" class="form-label"><?= htmlspecialchars(tr_text('チーム', 'Team')) ?></label>
                            <select id="vbTeam" class="form-select">
                                <option value=""><?= htmlspecialchars(tr_text('選択してください', 'Select')) ?></option>
                                <?php foreach (($teams ?? []) as $team): ?>
                                    <option value="<?= (int)$team['id'] ?>"><?= htmlspecialchars($team['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6" id="vbOrgWrap" style="display:none;">
                            <label for="vbOrg" class="form-label"><?= htmlspecialchars(tr_text('組織', 'Organization')) ?></label>
                            <select id="vbOrg" class="form-select">
                                <option value=""><?= htmlspecialchars(tr_text('選択してください', 'Select')) ?></option>
                                <?php foreach (($organizations ?? []) as $org): ?>
                                    <option value="<?= (int)$org['id'] ?>"><?= htmlspecialchars($org['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label for="vbDescription" class="form-label"><?= htmlspecialchars(tr_text('説明', 'Description')) ?></label>
                            <textarea id="vbDescription" class="form-control" rows="3" maxlength="1000"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label for="vbTaskBoard" class="form-label"><?= htmlspecialchars(tr_text('関連タスクプロジェクト', 'Related task project')) ?></label>
                            <select id="vbTaskBoard" class="form-select">
                                <option value=""><?= htmlspecialchars(tr_text('未設定', 'Not linked')) ?></option>
                                <?php foreach (($taskBoards ?? []) as $taskBoard): ?>
                                    <option value="<?= (int)$taskBoard['id'] ?>"><?= htmlspecialchars($taskBoard['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="vb-helper mt-1"><?= htmlspecialchars(tr_text('設定すると、ノードのタスク連携候補をこのプロジェクト内に絞り込みます。', 'When set, task-link candidates in nodes are filtered to this project.')) ?></div>
                        </div>
                        <div class="col-12">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                                <label class="form-label mb-0"><?= htmlspecialchars(tr_text('テンプレート', 'Template')) ?></label>
                                <span class="vb-helper"><?= htmlspecialchars(tr_text('作成後に自由編集できます', 'You can fully edit after creation')) ?></span>
                            </div>
                            <div class="vb-template-grid" id="vbTemplateGrid">
                                <button type="button" class="vb-template-item active" data-template="mind_map">
                                    <strong>Mind Map</strong>
                                    <div class="small text-muted mt-1"><?= htmlspecialchars(tr_text('中心トピック + 枝分かれ', 'Central topic + branches')) ?></div>
                                </button>
                                <button type="button" class="vb-template-item" data-template="flowchart">
                                    <strong>Flowchart</strong>
                                    <div class="small text-muted mt-1"><?= htmlspecialchars(tr_text('手順フロー', 'Step-by-step flow')) ?></div>
                                </button>
                                <button type="button" class="vb-template-item" data-template="brainstorm">
                                    <strong>Brainstorm</strong>
                                    <div class="small text-muted mt-1"><?= htmlspecialchars(tr_text('アイデア発散', 'Divergent ideas')) ?></div>
                                </button>
                                <button type="button" class="vb-template-item" data-template="planning">
                                    <strong>Planning</strong>
                                    <div class="small text-muted mt-1"><?= htmlspecialchars(tr_text('計画要素の整理', 'Planning structure')) ?></div>
                                </button>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="vbPublic">
                                <label class="form-check-label" for="vbPublic"><?= htmlspecialchars(tr_text('閲覧用として公開する（編集権限は別）', 'Make board viewable to all users (edit permission remains separate)')) ?></label>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-primary" id="vbCreateSubmit">
                            <i class="fas fa-plus-circle me-1"></i><?= htmlspecialchars(tr_text('作成', 'Create')) ?>
                        </button>
                        <a href="<?= BASE_PATH ?>/visual-boards" class="btn btn-outline-secondary"><?= htmlspecialchars(tr_text('キャンセル', 'Cancel')) ?></a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const form = document.getElementById('vbCreateForm');
    if (!form) return;

    const ownerType = document.getElementById('vbOwnerType');
    const teamWrap = document.getElementById('vbTeamWrap');
    const orgWrap = document.getElementById('vbOrgWrap');
    const teamSelect = document.getElementById('vbTeam');
    const orgSelect = document.getElementById('vbOrg');
    const submitBtn = document.getElementById('vbCreateSubmit');
    const nameInput = document.getElementById('vbName');
    const descriptionInput = document.getElementById('vbDescription');
    const publicCheck = document.getElementById('vbPublic');
    const taskBoardSelect = document.getElementById('vbTaskBoard');
    const templateGrid = document.getElementById('vbTemplateGrid');
    const userId = <?= (int)$userId ?>;

    const text = {
        teamRequired: <?= json_encode(tr_text('チームを選択してください。', 'Please select a team.')) ?>,
        orgRequired: <?= json_encode(tr_text('組織を選択してください。', 'Please select an organization.')) ?>,
        nameRequired: <?= json_encode(tr_text('ボード名を入力してください。', 'Please enter a board name.')) ?>,
        creating: <?= json_encode(tr_text('作成中...', 'Creating...')) ?>,
        create: <?= json_encode(tr_text('作成', 'Create')) ?>,
        failed: <?= json_encode(tr_text('作成に失敗しました。', 'Failed to create board.')) ?>,
        communication: <?= json_encode(tr_text('通信エラーが発生しました。', 'A communication error occurred.')) ?>
    };

    let templateKey = 'mind_map';

    function syncOwnerType() {
        const type = ownerType.value;
        teamWrap.style.display = (type === 'team') ? '' : 'none';
        orgWrap.style.display = (type === 'organization') ? '' : 'none';
    }

    ownerType.addEventListener('change', syncOwnerType);
    syncOwnerType();

    templateGrid.querySelectorAll('.vb-template-item').forEach((button) => {
        button.addEventListener('click', () => {
            templateGrid.querySelectorAll('.vb-template-item').forEach((item) => item.classList.remove('active'));
            button.classList.add('active');
            templateKey = button.dataset.template || 'mind_map';
        });
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const name = (nameInput.value || '').trim();
        if (!name) {
            alert(text.nameRequired);
            nameInput.focus();
            return;
        }

        const type = ownerType.value;
        let ownerId = userId;
        if (type === 'team') {
            ownerId = Number(teamSelect.value || 0);
            if (!ownerId) {
                alert(text.teamRequired);
                return;
            }
        }
        if (type === 'organization') {
            ownerId = Number(orgSelect.value || 0);
            if (!ownerId) {
                alert(text.orgRequired);
                return;
            }
        }

        submitBtn.disabled = true;
        submitBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>${text.creating}`;

        try {
            const response = await fetch(`${BASE_PATH}/api/visual-boards/boards`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    name,
                    description: descriptionInput.value || '',
                    owner_type: type,
                    owner_id: ownerId,
                    linked_task_board_id: Number(taskBoardSelect?.value || 0) || null,
                    template_key: templateKey,
                    is_public: publicCheck.checked
                })
            });
            const json = await response.json().catch(() => ({}));
            if (!response.ok || !json.success) {
                alert(json.error || text.failed);
                submitBtn.disabled = false;
                submitBtn.textContent = text.create;
                return;
            }
            window.location.href = json.redirect || `${BASE_PATH}/visual-boards`;
        } catch (err) {
            alert(text.communication);
            submitBtn.disabled = false;
            submitBtn.textContent = text.create;
        }
    });
})();
</script>

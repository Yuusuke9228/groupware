<?php
$boardId = (int)$board['id'];
$canEditBool = !empty($canEdit);
?>

<style>
.vb-shell { height: calc(100vh - 166px); min-height: 560px; display: grid; grid-template-columns: 300px 1fr 280px; gap: 12px; padding: 12px 10px 14px; }
.vb-panel { border: 1px solid #d8e2ef; border-radius: 12px; background: #fff; overflow: hidden; display: flex; flex-direction: column; min-height: 0; }
.vb-panel-header { padding: 10px 12px; border-bottom: 1px solid #e7edf6; background: #f8fbff; font-weight: 600; font-size: 14px; }
.vb-panel-body { padding: 10px 12px; overflow: auto; min-height: 0; flex: 1 1 auto; }
.vb-toolbar { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 10px; }
.vb-toolbar .btn { padding: 6px 10px; font-size: 12px; }
.vb-status { font-size: 12px; color: #5f6f86; }
.vb-board-wrap { border: 1px solid #dbe5f2; border-radius: 12px; position: relative; overflow: hidden; background:
        radial-gradient(circle at 1px 1px, rgba(157, 179, 210, .28) 1px, transparent 0),
        linear-gradient(135deg, #fafdff 0%, #f4f8fe 100%);
    background-size: 22px 22px, auto; }
.vb-canvas-panel .vb-panel-body { padding: 8px; display: flex; overflow: hidden; }
.vb-canvas-panel .vb-board-wrap { flex: 1 1 auto; height: 100%; min-height: 520px; }
.vb-board-stage { width: 100%; height: 100%; position: relative; touch-action: none; user-select: none; }
.vb-world { position: absolute; top: 0; left: 0; transform-origin: 0 0; width: 100%; height: 100%; }
.vb-edge-layer { position: absolute; inset: 0; width: 100%; height: 100%; overflow: visible; pointer-events: auto; z-index: 1; }
.vb-node-layer { position: absolute; inset: 0; z-index: 2; }
.vb-node { position: absolute; border: 1px solid #c9d6ea; border-radius: 10px; box-shadow: 0 8px 16px rgba(25, 40, 60, .08); background: #fff4c2; min-width: 120px; min-height: 56px; }
.vb-node.selected { border-color: #2569d8; box-shadow: 0 0 0 2px rgba(37, 105, 216, .22), 0 8px 16px rgba(25, 40, 60, .08); }
.vb-node.connect-source { border-color: #d79b16; box-shadow: 0 0 0 2px rgba(215, 155, 22, .28), 0 8px 16px rgba(25, 40, 60, .08); }
.vb-edge-path { fill: none; stroke: #5d759d; stroke-width: 2; stroke-linecap: round; vector-effect: non-scaling-stroke; }
.vb-edge-hit { fill: none; stroke: transparent; stroke-width: 18; stroke-linecap: round; vector-effect: non-scaling-stroke; cursor: pointer; pointer-events: stroke; }
.vb-node-head { display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 6px 8px; cursor: move; border-bottom: 1px solid rgba(0,0,0,.08); font-size: 12px; font-weight: 600; }
.vb-node-title { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; flex: 1; }
.vb-node-actions { display: inline-flex; align-items: center; gap: 6px; }
.vb-node-actions button { border: 0; background: transparent; color: #587099; padding: 0; font-size: 12px; }
.vb-node-body { padding: 6px 8px; font-size: 12px; color: #4b5e7c; max-height: 86px; overflow: hidden; }
.vb-task-chip { display: inline-flex; align-items: center; gap: 4px; margin-top: 4px; padding: 2px 6px; border-radius: 999px; background: rgba(37, 105, 216, .1); color: #234f98; font-size: 11px; max-width: 100%; }
.vb-task-chip span { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.vb-inspector-field { margin-bottom: 10px; }
.vb-inspector-field label { font-size: 12px; font-weight: 600; margin-bottom: 4px; display: block; }
.vb-inspector-field input,.vb-inspector-field textarea,.vb-inspector-field select { font-size: 13px; }
.vb-kbd { border: 1px solid #cfd8e6; border-bottom-width: 2px; border-radius: 5px; padding: 0 6px; background: #fff; font-size: 11px; margin-right: 4px; display: inline-block; }
.vb-member-list { margin: 0; padding-left: 16px; font-size: 12px; color: #4f6078; }
.vb-footer-note { font-size: 12px; color: #5b6c86; margin-top: 10px; }
@media (max-width: 1200px) {
    .vb-shell { grid-template-columns: 260px 1fr; }
    .vb-panel-right { grid-column: 1 / -1; min-height: 220px; }
}
@media (max-width: 900px) {
    .vb-shell { grid-template-columns: 1fr; height: auto; min-height: 0; }
    .vb-canvas-panel { order: 1; }
    .vb-panel-left { order: 2; }
    .vb-panel-right { order: 3; }
    .vb-panel { min-height: 200px; }
    .vb-canvas-panel .vb-board-wrap { height: 70vh; min-height: 420px; }
    .vb-toolbar .btn { flex: 1 1 calc(50% - 6px); }
}
</style>

<div class="container-fluid">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-2">
        <div>
            <h4 class="mb-1"><?= htmlspecialchars($board['name']) ?></h4>
            <div class="text-muted small"><?= htmlspecialchars(tr_text('Visual Boards（ノードベースの思考整理）', 'Visual Boards (node-based idea structuring)')) ?></div>
            <?php if (!empty($board['linked_task_board_id']) && !empty($board['linked_task_board_name'])): ?>
                <div class="text-muted small mt-1">
                    <?= htmlspecialchars(tr_text('関連タスクプロジェクト: ', 'Related task project: ')) ?>
                    <strong><?= htmlspecialchars($board['linked_task_board_name']) ?></strong>
                </div>
            <?php endif; ?>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="<?= BASE_PATH ?>/visual-boards" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i><?= htmlspecialchars(tr_text('一覧へ戻る', 'Back to list')) ?>
            </a>
            <a href="<?= BASE_PATH ?>/help#sec-visual-boards" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-question-circle me-1"></i><?= htmlspecialchars(tr_text('ヘルプ', 'Help')) ?>
            </a>
            <?php if (!empty($board['linked_task_board_id'])): ?>
                <a href="<?= BASE_PATH ?>/task/board/<?= (int)$board['linked_task_board_id'] ?>" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-tasks me-1"></i><?= htmlspecialchars(tr_text('タスクプロジェクト', 'Task project')) ?>
                </a>
            <?php endif; ?>
            <a href="<?= BASE_PATH ?>/visual-boards/export/json/<?= $boardId ?>" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-file-code me-1"></i>JSON
            </a>
            <a href="<?= BASE_PATH ?>/visual-boards/export/pdf/<?= $boardId ?>" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-file-pdf me-1"></i>PDF
            </a>
        </div>
    </div>

    <div class="vb-shell"
         id="vbApp"
         data-board-id="<?= $boardId ?>"
         data-can-edit="<?= $canEditBool ? '1' : '0' ?>">

        <aside class="vb-panel vb-panel-left">
            <div class="vb-panel-header"><?= htmlspecialchars(tr_text('操作', 'Actions')) ?></div>
            <div class="vb-panel-body">
                <div class="vb-toolbar">
                    <button class="btn btn-sm btn-primary" id="vbAddRootBtn" <?= $canEditBool ? '' : 'disabled' ?>>
                        <i class="fas fa-plus me-1"></i><?= htmlspecialchars(tr_text('ルート追加', 'Add root')) ?>
                    </button>
                    <button class="btn btn-sm btn-outline-primary" id="vbAddChildBtn" <?= $canEditBool ? '' : 'disabled' ?>>
                        <i class="fas fa-level-down-alt me-1"></i><?= htmlspecialchars(tr_text('子ノード', 'Add child')) ?>
                    </button>
                    <button class="btn btn-sm btn-outline-primary" id="vbAddSiblingBtn" <?= $canEditBool ? '' : 'disabled' ?>>
                        <i class="fas fa-level-up-alt me-1"></i><?= htmlspecialchars(tr_text('兄弟ノード', 'Add sibling')) ?>
                    </button>
                    <button class="btn btn-sm btn-outline-primary" id="vbConnectBtn" <?= $canEditBool ? '' : 'disabled' ?>>
                        <i class="fas fa-link me-1"></i><?= htmlspecialchars(tr_text('接続線', 'Connect')) ?>
                    </button>
                    <button class="btn btn-sm btn-outline-secondary" id="vbAutoLayoutBtn" <?= $canEditBool ? '' : 'disabled' ?>>
                        <i class="fas fa-project-diagram me-1"></i><?= htmlspecialchars(tr_text('自動レイアウト', 'Auto layout')) ?>
                    </button>
                    <button class="btn btn-sm btn-outline-secondary" id="vbUndoBtn" <?= $canEditBool ? '' : 'disabled' ?>>
                        <i class="fas fa-undo me-1"></i>Undo
                    </button>
                    <button class="btn btn-sm btn-outline-secondary" id="vbRedoBtn" <?= $canEditBool ? '' : 'disabled' ?>>
                        <i class="fas fa-redo me-1"></i>Redo
                    </button>
                    <button class="btn btn-sm btn-outline-secondary" id="vbExportPngBtn">
                        <i class="fas fa-image me-1"></i>PNG
                    </button>
                    <button class="btn btn-sm btn-success" id="vbSaveBtn" <?= $canEditBool ? '' : 'disabled' ?>>
                        <i class="fas fa-save me-1"></i><?= htmlspecialchars(tr_text('保存', 'Save')) ?>
                    </button>
                </div>

                <div class="vb-status" id="vbStatus"></div>
                <hr>
                <div class="small mb-2"><strong><?= htmlspecialchars(tr_text('ショートカット', 'Shortcuts')) ?></strong></div>
                <div class="small mb-1"><span class="vb-kbd">Tab</span><?= htmlspecialchars(tr_text('子ノード追加', 'Add child node')) ?></div>
                <div class="small mb-1"><span class="vb-kbd">Enter</span><?= htmlspecialchars(tr_text('兄弟ノード追加', 'Add sibling node')) ?></div>
                <div class="small mb-1"><span class="vb-kbd">Del</span><?= htmlspecialchars(tr_text('ノード削除', 'Delete node')) ?></div>
                <div class="small mb-1"><span class="vb-kbd">Ctrl</span>+<span class="vb-kbd">Z</span> Undo</div>
                <div class="small mb-1"><span class="vb-kbd">Ctrl</span>+<span class="vb-kbd">Y</span> Redo</div>
                <div class="small mb-1"><span class="vb-kbd">Ctrl</span>+<span class="vb-kbd">S</span> Save</div>
                <hr>
                <div class="small mb-2"><strong><?= htmlspecialchars(tr_text('メンバー', 'Members')) ?></strong></div>
                <ul class="vb-member-list">
                    <?php foreach (($members ?? []) as $member): ?>
                        <li><?= htmlspecialchars($member['display_name']) ?> (<?= htmlspecialchars($member['role']) ?>)</li>
                    <?php endforeach; ?>
                </ul>
                <div class="vb-footer-note"><?= htmlspecialchars(tr_text('Visual Boardsはタスクカンバンと別データで管理されます。', 'Visual Boards data is managed separately from Kanban task boards.')) ?></div>
            </div>
        </aside>

        <section class="vb-panel vb-canvas-panel">
            <div class="vb-panel-header d-flex align-items-center justify-content-between">
                <span><?= htmlspecialchars(tr_text('キャンバス', 'Canvas')) ?></span>
                <div class="small text-muted">
                    <span id="vbZoomLabel">100%</span>
                    <button class="btn btn-sm btn-link py-0 px-1" id="vbFitBtn"><?= htmlspecialchars(tr_text('全体表示', 'Fit')) ?></button>
                </div>
            </div>
            <div class="vb-panel-body p-2">
                <div class="vb-board-wrap">
                    <div class="vb-board-stage" id="vbStage">
                        <div class="vb-world" id="vbWorld">
                            <svg class="vb-edge-layer" id="vbEdgeLayer" aria-hidden="true"></svg>
                            <div class="vb-node-layer" id="vbNodeLayer"></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <aside class="vb-panel vb-panel-right">
            <div class="vb-panel-header"><?= htmlspecialchars(tr_text('ノード詳細', 'Node Inspector')) ?></div>
            <div class="vb-panel-body">
                <div id="vbNoSelection" class="text-muted small"><?= htmlspecialchars(tr_text('ノードを選択すると編集できます。', 'Select a node to edit.')) ?></div>
                <div id="vbInspector" style="display:none;">
                    <div class="vb-inspector-field">
                        <label for="vbNodeTitle"><?= htmlspecialchars(tr_text('タイトル', 'Title')) ?></label>
                        <input type="text" class="form-control form-control-sm" id="vbNodeTitle" maxlength="255" <?= $canEditBool ? '' : 'disabled' ?>>
                    </div>
                    <div class="vb-inspector-field">
                        <label for="vbNodeContent"><?= htmlspecialchars(tr_text('内容', 'Content')) ?></label>
                        <textarea class="form-control form-control-sm" id="vbNodeContent" rows="4" <?= $canEditBool ? '' : 'disabled' ?>></textarea>
                    </div>
                    <div class="vb-inspector-field">
                        <label for="vbNodeTask"><?= htmlspecialchars(tr_text('タスク連携', 'Linked task')) ?></label>
                        <select class="form-select form-select-sm" id="vbNodeTask" <?= $canEditBool ? '' : 'disabled' ?>>
                            <option value=""><?= htmlspecialchars(tr_text('未連携', 'Not linked')) ?></option>
                        </select>
                    </div>
                    <div class="vb-inspector-field">
                        <label for="vbNodeParent"><?= htmlspecialchars(tr_text('親ノード', 'Parent node')) ?></label>
                        <select class="form-select form-select-sm" id="vbNodeParent" <?= $canEditBool ? '' : 'disabled' ?>>
                            <option value=""><?= htmlspecialchars(tr_text('なし（ルート）', 'None (root)')) ?></option>
                        </select>
                    </div>
                    <div class="vb-inspector-field">
                        <label for="vbNodeColor"><?= htmlspecialchars(tr_text('色', 'Color')) ?></label>
                        <input type="color" class="form-control form-control-color" id="vbNodeColor" <?= $canEditBool ? '' : 'disabled' ?>>
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" id="vbNodeCollapsed" <?= $canEditBool ? '' : 'disabled' ?>>
                        <label for="vbNodeCollapsed" class="form-check-label"><?= htmlspecialchars(tr_text('折りたたみ', 'Collapsed')) ?></label>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <button class="btn btn-sm btn-primary" id="vbApplyNodeBtn" <?= $canEditBool ? '' : 'disabled' ?>><?= htmlspecialchars(tr_text('反映', 'Apply')) ?></button>
                        <button class="btn btn-sm btn-outline-secondary" id="vbDuplicateNodeBtn" <?= $canEditBool ? '' : 'disabled' ?>><?= htmlspecialchars(tr_text('複製', 'Duplicate')) ?></button>
                        <button class="btn btn-sm btn-outline-danger" id="vbDeleteNodeBtn" <?= $canEditBool ? '' : 'disabled' ?>><?= htmlspecialchars(tr_text('削除', 'Delete')) ?></button>
                        <a class="btn btn-sm btn-outline-secondary" id="vbOpenTaskBtn" target="_blank" style="display:none;" rel="noopener">
                            <?= htmlspecialchars(tr_text('タスクを開く', 'Open task')) ?>
                        </a>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</div>

<script>
(function () {
    const appEl = document.getElementById('vbApp');
    if (!appEl) return;

    const boardId = Number(appEl.dataset.boardId || 0);
    const canEdit = appEl.dataset.canEdit === '1';
    const statusEl = document.getElementById('vbStatus');
    const stageEl = document.getElementById('vbStage');
    const worldEl = document.getElementById('vbWorld');
    const edgeLayerEl = document.getElementById('vbEdgeLayer');
    const nodeLayerEl = document.getElementById('vbNodeLayer');
    const zoomLabelEl = document.getElementById('vbZoomLabel');
    const taskSelectEl = document.getElementById('vbNodeTask');
    const noSelectionEl = document.getElementById('vbNoSelection');
    const inspectorEl = document.getElementById('vbInspector');
    const openTaskBtnEl = document.getElementById('vbOpenTaskBtn');
    const titleInput = document.getElementById('vbNodeTitle');
    const contentInput = document.getElementById('vbNodeContent');
    const colorInput = document.getElementById('vbNodeColor');
    const collapsedInput = document.getElementById('vbNodeCollapsed');
    const parentSelectEl = document.getElementById('vbNodeParent');

    const text = {
        loading: <?= json_encode(tr_text('読み込み中...', 'Loading...')) ?>,
        loaded: <?= json_encode(tr_text('読み込み完了', 'Loaded.')) ?>,
        saveOk: <?= json_encode(tr_text('保存しました', 'Saved.')) ?>,
        saveFailed: <?= json_encode(tr_text('保存に失敗しました', 'Failed to save.')) ?>,
        communication: <?= json_encode(tr_text('通信エラーが発生しました。', 'A communication error occurred.')) ?>,
        node: <?= json_encode(tr_text('ノード', 'Node')) ?>,
        deleteConfirm: <?= json_encode(tr_text('選択中のノードを削除します。よろしいですか？', 'Delete selected node?')) ?>,
        boardReadonly: <?= json_encode(tr_text('このボードは閲覧専用です。', 'This board is read-only.')) ?>,
        connectOn: <?= json_encode(tr_text('接続モード: 接続元ノードを選択してください', 'Connect mode: select source node')) ?>,
        connectSelectTarget: <?= json_encode(tr_text('接続先ノードを選択してください', 'Select target node')) ?>,
        connectSourceFixed: <?= json_encode(tr_text('接続元を固定しました。接続先ノードを選択してください', 'Source fixed. Select a target node.')) ?>,
        connectOff: <?= json_encode(tr_text('接続モードを終了しました', 'Connect mode disabled')) ?>,
        connectAdded: <?= json_encode(tr_text('接続線を追加しました', 'Connection added')) ?>,
        connectAddedContinue: <?= json_encode(tr_text('接続線を追加しました。続けて接続先を選択できます', 'Connection added. You can keep selecting targets.')) ?>,
        connectExists: <?= json_encode(tr_text('同じ接続線はすでに存在します', 'This connection already exists')) ?>,
        connectRemoved: <?= json_encode(tr_text('接続線を解除しました', 'Connection removed')) ?>,
        connectRemovedContinue: <?= json_encode(tr_text('接続線を解除しました。続けて接続先を選択できます', 'Connection removed. You can keep selecting targets.')) ?>,
        connectNotRemovable: <?= json_encode(tr_text('この接続線は解除できませんでした', 'This connection could not be removed')) ?>,
        fitDone: <?= json_encode(tr_text('全体表示に合わせました', 'Fitted to screen')) ?>,
        invalidParent: <?= json_encode(tr_text('親ノードの設定が不正です。', 'Invalid parent node selection.')) ?>
    };
    const notLinkedText = <?= json_encode(tr_text('未連携', 'Not linked')) ?>;

    const state = {
        board: null,
        nodes: [],
        edges: [],
        tasks: [],
        selectedNodeId: null,
        connectMode: false,
        connectSourceId: null,
        scale: 1,
        panX: 0,
        panY: 0,
        history: [],
        historyIndex: -1,
        saveTimer: null,
        lastSaveAt: 0
    };

    const historyLimit = 60;

    function setStatus(message, isError) {
        if (!statusEl) return;
        statusEl.textContent = message || '';
        statusEl.style.color = isError ? '#c0392b' : '#5f6f86';
    }

    function clamp(value, min, max) {
        return Math.max(min, Math.min(max, value));
    }

    function nextClientId() {
        return 'c' + Date.now().toString(36) + Math.random().toString(36).slice(2, 8);
    }

    function toClientId(node) {
        if (node.client_id) return String(node.client_id);
        return 'n' + String(node.id);
    }

    function deepCloneBoardState() {
        return JSON.parse(JSON.stringify({
            nodes: state.nodes,
            edges: state.edges,
            selectedNodeId: state.selectedNodeId
        }));
    }

    function pushHistory() {
        const snapshot = deepCloneBoardState();
        if (state.historyIndex >= 0) {
            const current = state.history[state.historyIndex];
            if (JSON.stringify(current) === JSON.stringify(snapshot)) {
                return;
            }
        }
        state.history = state.history.slice(0, state.historyIndex + 1);
        state.history.push(snapshot);
        if (state.history.length > historyLimit) {
            state.history.shift();
        }
        state.historyIndex = state.history.length - 1;
    }

    function applySnapshot(snapshot) {
        state.nodes = snapshot.nodes || [];
        state.edges = snapshot.edges || [];
        state.selectedNodeId = snapshot.selectedNodeId || null;
        renderAll();
        scheduleSave();
    }

    function undo() {
        if (!canEdit || state.historyIndex <= 0) return;
        state.historyIndex -= 1;
        applySnapshot(JSON.parse(JSON.stringify(state.history[state.historyIndex])));
    }

    function redo() {
        if (!canEdit || state.historyIndex >= state.history.length - 1) return;
        state.historyIndex += 1;
        applySnapshot(JSON.parse(JSON.stringify(state.history[state.historyIndex])));
    }

    function findNode(nodeId) {
        return state.nodes.find((node) => Number(node.id) === Number(nodeId)) || null;
    }

    function getVisibleNodeSet() {
        const nodeMap = new Map();
        state.nodes.forEach((node) => nodeMap.set(Number(node.id), node));
        const visible = new Set();

        function isVisible(node) {
            let parentId = node.parent_id ? Number(node.parent_id) : null;
            while (parentId) {
                const parent = nodeMap.get(parentId);
                if (!parent) break;
                if (Number(parent.is_collapsed) === 1) return false;
                parentId = parent.parent_id ? Number(parent.parent_id) : null;
            }
            return true;
        }

        state.nodes.forEach((node) => {
            if (isVisible(node)) visible.add(Number(node.id));
        });
        return visible;
    }

    function syncWorldTransform() {
        worldEl.style.transform = `translate(${state.panX}px, ${state.panY}px) scale(${state.scale})`;
        zoomLabelEl.textContent = `${Math.round(state.scale * 100)}%`;
    }

    function selectNode(nodeId) {
        state.selectedNodeId = nodeId ? Number(nodeId) : null;
        renderNodes();
        syncInspector();
    }

    function createNode(options) {
        const node = {
            id: Date.now() + Math.floor(Math.random() * 1000),
            client_id: nextClientId(),
            parent_id: options.parent_id ? Number(options.parent_id) : null,
            linked_task_id: options.linked_task_id ? Number(options.linked_task_id) : null,
            linked_task_title: options.linked_task_title || null,
            node_type: options.node_type || 'note',
            title: options.title || text.node,
            content: options.content || '',
            x: Number(options.x || 0),
            y: Number(options.y || 0),
            width: Number(options.width || 220),
            height: Number(options.height || 96),
            color: options.color || '#fff4c2',
            is_collapsed: options.is_collapsed ? 1 : 0,
            sort_order: options.sort_order || (state.nodes.length + 1)
        };
        state.nodes.push(node);
        return node;
    }

    function removeNode(nodeId) {
        const targetId = Number(nodeId);
        const descendants = new Set([targetId]);
        let changed = true;
        while (changed) {
            changed = false;
            state.nodes.forEach((node) => {
                const pid = node.parent_id ? Number(node.parent_id) : null;
                if (pid && descendants.has(pid) && !descendants.has(Number(node.id))) {
                    descendants.add(Number(node.id));
                    changed = true;
                }
            });
        }
        state.nodes = state.nodes.filter((node) => !descendants.has(Number(node.id)));
        state.edges = state.edges.filter((edge) => !descendants.has(Number(edge.source_node_id)) && !descendants.has(Number(edge.target_node_id)));
        if (state.selectedNodeId && descendants.has(Number(state.selectedNodeId))) {
            state.selectedNodeId = null;
        }
    }

    function addChildNode() {
        if (!canEdit) return;
        const parent = findNode(state.selectedNodeId);
        if (!parent) return;
        pushHistory();
        const node = createNode({
            parent_id: parent.id,
            x: Number(parent.x) + 280,
            y: Number(parent.y) + 120,
            color: '#e8f5e9',
            title: text.node
        });
        selectNode(node.id);
        renderAll();
        scheduleSave();
    }

    function addSiblingNode() {
        if (!canEdit) return;
        const selected = findNode(state.selectedNodeId);
        if (!selected) return;
        pushHistory();
        const node = createNode({
            parent_id: selected.parent_id ? Number(selected.parent_id) : null,
            x: Number(selected.x),
            y: Number(selected.y) + 130,
            color: '#f3e5f5',
            title: text.node
        });
        selectNode(node.id);
        renderAll();
        scheduleSave();
    }

    function addRootNode() {
        if (!canEdit) return;
        pushHistory();
        const node = createNode({
            parent_id: null,
            x: 120 + (state.nodes.length * 20),
            y: 120 + (state.nodes.length * 20),
            color: '#fff4c2',
            title: text.node
        });
        selectNode(node.id);
        renderAll();
        scheduleSave();
    }

    function setConnectMode(active, silent) {
        state.connectMode = !!active;
        state.connectSourceId = null;
        const btn = document.getElementById('vbConnectBtn');
        if (!btn) return;

        if (active) {
            btn.classList.remove('btn-outline-primary');
            btn.classList.add('btn-warning');
            if (!silent) setStatus(text.connectOn, false);
        } else {
            btn.classList.remove('btn-warning');
            btn.classList.add('btn-outline-primary');
            if (!silent) setStatus(text.connectOff, false);
        }
    }

    function toggleConnectMode() {
        if (!canEdit) return;
        const next = !state.connectMode;
        setConnectMode(next, false);
        if (next && state.selectedNodeId) {
            state.connectSourceId = Number(state.selectedNodeId);
            setStatus(text.connectSourceFixed, false);
        }
    }

    function removeConnectionByPair(sourceId, targetId) {
        let changed = false;
        const source = Number(sourceId);
        const target = Number(targetId);

        const beforeManual = state.edges.length;
        state.edges = state.edges.filter((edge) => {
            return !(Number(edge.source_node_id) === source && Number(edge.target_node_id) === target);
        });
        if (state.edges.length !== beforeManual) {
            changed = true;
        }

        const targetNode = findNode(target);
        if (targetNode && Number(targetNode.parent_id || 0) === source) {
            targetNode.parent_id = null;
            changed = true;
        }

        return changed;
    }

    function handleConnectNode(nodeId) {
        if (!canEdit || !state.connectMode) return;

        if (!state.connectSourceId) {
            state.connectSourceId = Number(nodeId);
            selectNode(nodeId);
            setStatus(text.connectSourceFixed, false);
            return;
        }

        const sourceId = Number(state.connectSourceId);
        const targetId = Number(nodeId);
        if (sourceId === targetId) return;

        const exists = collectRenderableEdges().some((edge) =>
            Number(edge.source_node_id) === sourceId && Number(edge.target_node_id) === targetId
        );
        if (exists) {
            pushHistory();
            const removed = removeConnectionByPair(sourceId, targetId);
            if (removed) {
                setStatus(text.connectRemovedContinue, false);
                renderAll();
                scheduleSave();
            } else {
                setStatus(text.connectNotRemovable, true);
            }
            state.connectSourceId = sourceId;
            return;
        }

        pushHistory();
        state.edges.push({
            id: Date.now() + Math.floor(Math.random() * 1000),
            source_node_id: sourceId,
            target_node_id: targetId,
            line_style: 'solid',
            label: null
        });
        state.connectSourceId = sourceId;
        setStatus(text.connectAddedContinue, false);
        renderAll();
        scheduleSave();
    }

    function fitToScreen(attempt) {
        const retry = Number(attempt || 0);
        if (!state.nodes.length) {
            state.scale = 1;
            state.panX = 0;
            state.panY = 0;
            syncWorldTransform();
            setStatus(text.fitDone, false);
            return;
        }
        const bounds = getNodeBounds(state.nodes);
        if (!bounds) return;

        const rect = stageEl.getBoundingClientRect();
        if (rect.width < 40 || rect.height < 40) {
            if (retry < 6) {
                setTimeout(() => fitToScreen(retry + 1), 120);
            }
            return;
        }
        const margin = 80;
        const width = Math.max(1, bounds.maxX - bounds.minX + margin);
        const height = Math.max(1, bounds.maxY - bounds.minY + margin);
        const sx = rect.width / width;
        const sy = rect.height / height;
        state.scale = clamp(Math.min(sx, sy), 0.3, 2.1);

        const centerX = (bounds.minX + bounds.maxX) / 2;
        const centerY = (bounds.minY + bounds.maxY) / 2;
        state.panX = (rect.width / 2) - (centerX * state.scale);
        state.panY = (rect.height / 2) - (centerY * state.scale);
        syncWorldTransform();
        setStatus(text.fitDone, false);
    }

    function getNodeBounds(nodes) {
        if (!nodes.length) return null;
        let minX = Number.POSITIVE_INFINITY;
        let minY = Number.POSITIVE_INFINITY;
        let maxX = Number.NEGATIVE_INFINITY;
        let maxY = Number.NEGATIVE_INFINITY;
        nodes.forEach((node) => {
            const x = Number(node.x);
            const y = Number(node.y);
            const w = Number(node.width || 220);
            const h = Number(node.height || 96);
            minX = Math.min(minX, x);
            minY = Math.min(minY, y);
            maxX = Math.max(maxX, x + w);
            maxY = Math.max(maxY, y + h);
        });
        return { minX, minY, maxX, maxY };
    }

    function computeEdgeCurve(source, target) {
        const sw = Number(source.width || 220);
        const sh = Number(source.height || 96);
        const tw = Number(target.width || 220);
        const th = Number(target.height || 96);

        const sourceCenterX = Number(source.x) + (sw / 2);
        const targetCenterX = Number(target.x) + (tw / 2);
        const leftToRight = targetCenterX >= sourceCenterX;

        const sx = Number(source.x) + (leftToRight ? sw : 0);
        const sy = Number(source.y) + (sh / 2);
        const tx = Number(target.x) + (leftToRight ? 0 : tw);
        const ty = Number(target.y) + (th / 2);

        const direction = leftToRight ? 1 : -1;
        const horizontalGap = Math.abs(tx - sx);
        const controlOffset = clamp((horizontalGap * 0.45), 64, 280);
        const verticalBias = clamp((ty - sy) * 0.2, -70, 70);

        const c1x = sx + (controlOffset * direction);
        const c1y = sy + verticalBias;
        const c2x = tx - (controlOffset * direction);
        const c2y = ty - verticalBias;

        return { sx, sy, tx, ty, c1x, c1y, c2x, c2y };
    }

    function edgeCurvePath(curve) {
        return `M ${curve.sx} ${curve.sy} C ${curve.c1x} ${curve.c1y}, ${curve.c2x} ${curve.c2y}, ${curve.tx} ${curve.ty}`;
    }

    function renderEdges() {
        edgeLayerEl.innerHTML = '';
        const visible = getVisibleNodeSet();
        const nodeMap = new Map();
        state.nodes.forEach((node) => nodeMap.set(Number(node.id), node));

        collectRenderableEdges().forEach((edge) => {
            const source = nodeMap.get(Number(edge.source_node_id));
            const target = nodeMap.get(Number(edge.target_node_id));
            if (!source || !target) return;
            if (!visible.has(Number(source.id)) || !visible.has(Number(target.id))) return;

            const curve = computeEdgeCurve(source, target);
            const pathData = edgeCurvePath(curve);

            const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            path.setAttribute('d', pathData);
            path.setAttribute('class', 'vb-edge-path');
            if ((edge.line_style || 'solid') === 'dashed') {
                path.setAttribute('stroke-dasharray', '6 4');
            }
            edgeLayerEl.appendChild(path);

            if (!canEdit) return;

            const hitPath = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            hitPath.setAttribute('d', pathData);
            hitPath.setAttribute('class', 'vb-edge-hit');
            hitPath.addEventListener('pointerdown', (ev) => {
                ev.stopPropagation();
            });
            hitPath.addEventListener('click', (ev) => {
                ev.preventDefault();
                ev.stopPropagation();
                pushHistory();
                const removed = removeConnectionByPair(edge.source_node_id, edge.target_node_id);
                if (!removed) {
                    setStatus(text.connectNotRemovable, true);
                    return;
                }
                setStatus(text.connectRemoved, false);
                renderAll();
                scheduleSave();
            });
            edgeLayerEl.appendChild(hitPath);
        });
    }

    function collectRenderableEdges() {
        const merged = [];
        const seen = new Set();

        state.edges.forEach((edge) => {
            const sourceId = Number(edge.source_node_id || 0);
            const targetId = Number(edge.target_node_id || 0);
            if (!sourceId || !targetId || sourceId === targetId) return;
            const key = `${sourceId}:${targetId}`;
            if (seen.has(key)) return;
            seen.add(key);
            merged.push({
                source_node_id: sourceId,
                target_node_id: targetId,
                line_style: edge.line_style || 'solid'
            });
        });

        state.nodes.forEach((node) => {
            const nodeId = Number(node.id || 0);
            const parentId = Number(node.parent_id || 0);
            if (!nodeId || !parentId || nodeId === parentId) return;
            const key = `${parentId}:${nodeId}`;
            if (seen.has(key)) return;
            seen.add(key);
            merged.push({
                source_node_id: parentId,
                target_node_id: nodeId,
                line_style: 'solid'
            });
        });

        return merged;
    }

    function isDescendantNode(nodeId, possibleAncestorId) {
        let cursor = findNode(nodeId);
        let guard = 0;
        while (cursor && cursor.parent_id && guard < 2000) {
            const parentId = Number(cursor.parent_id);
            if (parentId === Number(possibleAncestorId)) {
                return true;
            }
            cursor = findNode(parentId);
            guard += 1;
        }
        return false;
    }

    function fillParentOptions(selectedNode) {
        if (!parentSelectEl || !selectedNode) return;
        const selectedId = Number(selectedNode.id);
        const currentParentId = selectedNode.parent_id ? Number(selectedNode.parent_id) : 0;
        const options = [`<option value="">${escapeHtml(<?= json_encode(tr_text('なし（ルート）', 'None (root)')) ?>)}</option>`];

        state.nodes.forEach((candidate) => {
            const candidateId = Number(candidate.id);
            if (candidateId === selectedId) return;
            if (isDescendantNode(candidateId, selectedId)) return;
            const label = `${candidate.title || text.node} (#${candidateId})`;
            options.push(`<option value="${candidateId}">${escapeHtml(label)}</option>`);
        });

        parentSelectEl.innerHTML = options.join('');
        parentSelectEl.value = currentParentId ? String(currentParentId) : '';
    }

    function renderNodes() {
        nodeLayerEl.innerHTML = '';
        const visible = getVisibleNodeSet();
        const fragment = document.createDocumentFragment();

        state.nodes.forEach((node) => {
            if (!visible.has(Number(node.id))) return;

            const nodeEl = document.createElement('article');
            nodeEl.className = 'vb-node'
                + (Number(state.selectedNodeId) === Number(node.id) ? ' selected' : '')
                + (state.connectMode && Number(state.connectSourceId) === Number(node.id) ? ' connect-source' : '');
            nodeEl.style.left = `${Number(node.x)}px`;
            nodeEl.style.top = `${Number(node.y)}px`;
            nodeEl.style.width = `${Math.max(120, Number(node.width || 220))}px`;
            nodeEl.style.minHeight = `${Math.max(56, Number(node.height || 96))}px`;
            nodeEl.style.background = node.color || '#fff4c2';
            nodeEl.dataset.nodeId = String(node.id);

            const headEl = document.createElement('div');
            headEl.className = 'vb-node-head';

            const titleEl = document.createElement('div');
            titleEl.className = 'vb-node-title';
            titleEl.textContent = node.title || text.node;
            titleEl.title = node.title || text.node;
            headEl.appendChild(titleEl);

            const actionsEl = document.createElement('div');
            actionsEl.className = 'vb-node-actions';
            const collapseBtn = document.createElement('button');
            collapseBtn.type = 'button';
            collapseBtn.innerHTML = Number(node.is_collapsed) === 1 ? '<i class="fas fa-angle-down"></i>' : '<i class="fas fa-angle-up"></i>';
            collapseBtn.title = 'Collapse';
            collapseBtn.addEventListener('click', (ev) => {
                ev.stopPropagation();
                if (!canEdit) return;
                pushHistory();
                node.is_collapsed = Number(node.is_collapsed) === 1 ? 0 : 1;
                renderAll();
                scheduleSave();
            });
            actionsEl.appendChild(collapseBtn);
            headEl.appendChild(actionsEl);

            const bodyEl = document.createElement('div');
            bodyEl.className = 'vb-node-body';
            bodyEl.textContent = node.content || '';

            if (node.linked_task_id) {
                const taskChip = document.createElement('div');
                taskChip.className = 'vb-task-chip';
                taskChip.innerHTML = `<i class="fas fa-link"></i><span>${escapeHtml(node.linked_task_title || ('#' + node.linked_task_id))}</span>`;
                bodyEl.appendChild(taskChip);
            }

            nodeEl.appendChild(headEl);
            nodeEl.appendChild(bodyEl);

            nodeEl.addEventListener('pointerdown', (ev) => {
                if (ev.pointerType === 'mouse' && ev.button !== 0) return;
                if (state.connectMode && canEdit) {
                    ev.preventDefault();
                    handleConnectNode(node.id);
                    return;
                }
                selectNode(node.id);
                if (!canEdit) return;
                if (ev.target.closest('button')) return;
                startNodeDrag(ev, node);
            });

            nodeEl.addEventListener('click', (ev) => {
                ev.stopPropagation();
                selectNode(node.id);
            });

            nodeEl.addEventListener('dblclick', () => {
                if (!canEdit) return;
                const nextTitle = window.prompt(<?= json_encode(tr_text('ノード名を入力してください', 'Enter node title')) ?>, node.title || text.node);
                if (nextTitle === null) return;
                pushHistory();
                node.title = nextTitle.trim() || text.node;
                renderNodes();
                syncInspector();
                scheduleSave();
            });

            fragment.appendChild(nodeEl);
        });

        nodeLayerEl.appendChild(fragment);
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function renderAll() {
        renderEdges();
        renderNodes();
        syncInspector();
    }

    function syncInspector() {
        const node = findNode(state.selectedNodeId);
        if (!node) {
            noSelectionEl.style.display = '';
            inspectorEl.style.display = 'none';
            openTaskBtnEl.style.display = 'none';
            return;
        }
        noSelectionEl.style.display = 'none';
        inspectorEl.style.display = '';
        titleInput.value = node.title || '';
        contentInput.value = node.content || '';
        colorInput.value = node.color || '#fff4c2';
        collapsedInput.checked = Number(node.is_collapsed) === 1;
        taskSelectEl.value = node.linked_task_id ? String(node.linked_task_id) : '';
        fillParentOptions(node);

        if (node.linked_task_id) {
            openTaskBtnEl.href = `${BASE_PATH}/task/card/${node.linked_task_id}`;
            openTaskBtnEl.style.display = '';
        } else {
            openTaskBtnEl.style.display = 'none';
        }
    }

    function fillTaskOptions(tasks) {
        state.tasks = Array.isArray(tasks) ? tasks : [];
        const selected = taskSelectEl.value || '';
        const options = [`<option value="">${escapeHtml(notLinkedText)}</option>`];
        state.tasks.forEach((task) => {
            const title = `${task.title} (${task.board_name || ''})`;
            options.push(`<option value="${task.id}">${escapeHtml(title)}</option>`);
        });
        taskSelectEl.innerHTML = options.join('');
        taskSelectEl.value = selected;
    }

    function scheduleSave() {
        if (!canEdit) return;
        if (state.saveTimer) {
            clearTimeout(state.saveTimer);
        }
        state.saveTimer = setTimeout(() => {
            saveBoard();
        }, 900);
    }

    async function saveBoard(manual) {
        if (!canEdit) return;
        if (!boardId) return;

        const payload = {
            nodes: state.nodes.map((node, index) => {
                const parentNode = node.parent_id ? findNode(node.parent_id) : null;
                return {
                    id: node.id,
                    client_id: toClientId(node),
                    parent_id: node.parent_id ? Number(node.parent_id) : null,
                    parent_client_id: parentNode ? toClientId(parentNode) : null,
                    linked_task_id: node.linked_task_id ? Number(node.linked_task_id) : null,
                    node_type: node.node_type || 'note',
                    title: node.title || text.node,
                    content: node.content || '',
                    x: Number(node.x || 0),
                    y: Number(node.y || 0),
                    width: Number(node.width || 220),
                    height: Number(node.height || 96),
                    color: node.color || '#fff4c2',
                    is_collapsed: Number(node.is_collapsed) === 1 ? 1 : 0,
                    sort_order: index + 1
                };
            }),
            edges: state.edges.map((edge) => {
                const source = findNode(edge.source_node_id);
                const target = findNode(edge.target_node_id);
                return {
                    source_node_id: edge.source_node_id,
                    target_node_id: edge.target_node_id,
                    source_client_id: source ? toClientId(source) : null,
                    target_client_id: target ? toClientId(target) : null,
                    line_style: edge.line_style || 'solid',
                    label: edge.label || null
                };
            })
        };

        try {
            const res = await fetch(`${BASE_PATH}/api/visual-boards/${boardId}/sync`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(payload)
            });
            const json = await res.json().catch(() => ({}));
            if (!res.ok || !json.success) {
                if (manual) alert(json.error || text.saveFailed);
                setStatus(json.error || text.saveFailed, true);
                return;
            }

            if (json.data && Array.isArray(json.data.nodes)) {
                state.nodes = json.data.nodes.map((node) => ({
                    ...node,
                    id: Number(node.id),
                    parent_id: node.parent_id ? Number(node.parent_id) : null,
                    linked_task_id: node.linked_task_id ? Number(node.linked_task_id) : null,
                    x: Number(node.x || 0),
                    y: Number(node.y || 0),
                    width: Number(node.width || 220),
                    height: Number(node.height || 96),
                    is_collapsed: Number(node.is_collapsed) === 1 ? 1 : 0,
                    client_id: 'n' + String(node.id)
                }));
            }
            if (json.data && Array.isArray(json.data.edges)) {
                state.edges = json.data.edges.map((edge) => ({
                    ...edge,
                    id: Number(edge.id),
                    source_node_id: Number(edge.source_node_id),
                    target_node_id: Number(edge.target_node_id)
                }));
            }
            state.lastSaveAt = Date.now();
            setStatus(text.saveOk, false);
            renderAll();
        } catch (err) {
            setStatus(text.communication, true);
            if (manual) alert(text.communication);
        }
    }

    async function fetchBoardData() {
        setStatus(text.loading, false);
        try {
            const res = await fetch(`${BASE_PATH}/api/visual-boards/${boardId}/data`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const json = await res.json().catch(() => ({}));
            if (!res.ok || !json.success) {
                setStatus(json.error || text.communication, true);
                return;
            }
            state.board = json.data.board || null;
            state.nodes = (json.data.nodes || []).map((node) => ({
                ...node,
                id: Number(node.id),
                parent_id: node.parent_id ? Number(node.parent_id) : null,
                linked_task_id: node.linked_task_id ? Number(node.linked_task_id) : null,
                x: Number(node.x || 0),
                y: Number(node.y || 0),
                width: Number(node.width || 220),
                height: Number(node.height || 96),
                is_collapsed: Number(node.is_collapsed) === 1 ? 1 : 0,
                client_id: 'n' + String(node.id)
            }));
            state.edges = (json.data.edges || []).map((edge) => ({
                ...edge,
                id: Number(edge.id),
                source_node_id: Number(edge.source_node_id),
                target_node_id: Number(edge.target_node_id)
            }));
            fillTaskOptions(json.data.tasks || []);
            pushHistory();
            renderAll();
            fitToScreen();
            setStatus(text.loaded, false);
        } catch (err) {
            setStatus(text.communication, true);
        }
    }

    function startNodeDrag(event, node) {
        let dragging = true;
        const pointerId = event.pointerId;
        const startX = event.clientX;
        const startY = event.clientY;
        const originX = Number(node.x);
        const originY = Number(node.y);

        function onMove(ev) {
            if (!dragging) return;
            if (Number(ev.pointerId) !== Number(pointerId)) return;
            const dx = (ev.clientX - startX) / state.scale;
            const dy = (ev.clientY - startY) / state.scale;
            node.x = Math.round((originX + dx) * 100) / 100;
            node.y = Math.round((originY + dy) * 100) / 100;
            renderEdges();
            renderNodes();
            syncInspector();
        }

        function onUp(ev) {
            if (!dragging) return;
            if (Number(ev.pointerId) !== Number(pointerId)) return;
            dragging = false;
            document.removeEventListener('pointermove', onMove);
            document.removeEventListener('pointerup', onUp);
            document.removeEventListener('pointercancel', onUp);
            pushHistory();
            scheduleSave();
        }

        document.addEventListener('pointermove', onMove, { passive: true });
        document.addEventListener('pointerup', onUp, { passive: true });
        document.addEventListener('pointercancel', onUp, { passive: true });
    }

    function setupStagePanZoom() {
        const pointers = new Map();
        let panAnchor = null;
        let pinchAnchor = null;
        const blankTap = new Map();

        function toStagePoint(clientX, clientY) {
            const rect = stageEl.getBoundingClientRect();
            return {
                x: clientX - rect.left,
                y: clientY - rect.top
            };
        }

        function beginPan(pointerId) {
            const pointer = pointers.get(pointerId);
            if (!pointer) return;
            panAnchor = {
                pointerId,
                startClientX: pointer.x,
                startClientY: pointer.y,
                startPanX: state.panX,
                startPanY: state.panY
            };
        }

        function beginPinch() {
            const entries = Array.from(pointers.values());
            if (entries.length < 2) return;
            const p1 = entries[0];
            const p2 = entries[1];
            const centerClientX = (p1.x + p2.x) / 2;
            const centerClientY = (p1.y + p2.y) / 2;
            const center = toStagePoint(centerClientX, centerClientY);
            const dist = Math.hypot(p2.x - p1.x, p2.y - p1.y) || 1;
            pinchAnchor = {
                startDistance: dist,
                startScale: state.scale,
                worldCenterX: (center.x - state.panX) / state.scale,
                worldCenterY: (center.y - state.panY) / state.scale
            };
        }

        stageEl.addEventListener('pointerdown', (ev) => {
            if (ev.pointerType === 'mouse' && ev.button !== 0) return;
            if (ev.target.closest('.vb-node') || ev.target.closest('.vb-edge-hit')) return;
            stageEl.setPointerCapture(ev.pointerId);
            blankTap.set(ev.pointerId, {
                x: ev.clientX,
                y: ev.clientY,
                moved: false,
                startedWithSinglePointer: pointers.size === 0
            });
            pointers.set(ev.pointerId, { id: ev.pointerId, x: ev.clientX, y: ev.clientY });

            if (pointers.size === 1) {
                beginPan(ev.pointerId);
                pinchAnchor = null;
            } else if (pointers.size >= 2) {
                beginPinch();
                panAnchor = null;
            }
        });

        stageEl.addEventListener('pointermove', (ev) => {
            if (!pointers.has(ev.pointerId)) return;
            pointers.set(ev.pointerId, { id: ev.pointerId, x: ev.clientX, y: ev.clientY });
            const tap = blankTap.get(ev.pointerId);
            if (tap && (Math.abs(ev.clientX - tap.x) > 4 || Math.abs(ev.clientY - tap.y) > 4)) {
                tap.moved = true;
            }

            if (pinchAnchor && pointers.size >= 2) {
                const entries = Array.from(pointers.values());
                const p1 = entries[0];
                const p2 = entries[1];
                const centerClientX = (p1.x + p2.x) / 2;
                const centerClientY = (p1.y + p2.y) / 2;
                const center = toStagePoint(centerClientX, centerClientY);
                const dist = Math.hypot(p2.x - p1.x, p2.y - p1.y) || pinchAnchor.startDistance;
                state.scale = clamp(pinchAnchor.startScale * (dist / pinchAnchor.startDistance), 0.2, 2.8);
                state.panX = center.x - (pinchAnchor.worldCenterX * state.scale);
                state.panY = center.y - (pinchAnchor.worldCenterY * state.scale);
                syncWorldTransform();
                return;
            }

            if (panAnchor && Number(panAnchor.pointerId) === Number(ev.pointerId)) {
                state.panX = panAnchor.startPanX + (ev.clientX - panAnchor.startClientX);
                state.panY = panAnchor.startPanY + (ev.clientY - panAnchor.startClientY);
                syncWorldTransform();
            }
        });

        function endPointer(ev) {
            const tap = blankTap.get(ev.pointerId);
            pointers.delete(ev.pointerId);
            blankTap.delete(ev.pointerId);

            if (pointers.size >= 2) {
                beginPinch();
                panAnchor = null;
                return;
            }

            pinchAnchor = null;
            if (pointers.size === 1) {
                const rest = Array.from(pointers.values())[0];
                beginPan(rest.id);
            } else {
                panAnchor = null;
                if (tap && tap.startedWithSinglePointer && !tap.moved) {
                    selectNode(null);
                }
            }
        }

        stageEl.addEventListener('pointerup', endPointer, { passive: true });
        stageEl.addEventListener('pointercancel', endPointer, { passive: true });
        stageEl.addEventListener('pointerleave', (ev) => {
            if (ev.pointerType !== 'mouse') return;
            endPointer(ev);
        }, { passive: true });

        stageEl.addEventListener('wheel', (ev) => {
            ev.preventDefault();
            const rect = stageEl.getBoundingClientRect();
            const cx = ev.clientX - rect.left;
            const cy = ev.clientY - rect.top;
            const beforeX = (cx - state.panX) / state.scale;
            const beforeY = (cy - state.panY) / state.scale;
            const ratio = ev.deltaY < 0 ? 1.08 : 0.92;
            state.scale = clamp(state.scale * ratio, 0.2, 2.8);
            state.panX = cx - (beforeX * state.scale);
            state.panY = cy - (beforeY * state.scale);
            syncWorldTransform();
        }, { passive: false });
    }

    function applyInspector() {
        const node = findNode(state.selectedNodeId);
        if (!node || !canEdit) return;

        const nextParentId = parentSelectEl && parentSelectEl.value ? Number(parentSelectEl.value) : null;
        if (nextParentId && (nextParentId === Number(node.id) || isDescendantNode(nextParentId, Number(node.id)))) {
            window.alert(text.invalidParent);
            fillParentOptions(node);
            return;
        }

        const oldParentId = node.parent_id ? Number(node.parent_id) : null;
        pushHistory();
        node.title = (titleInput.value || '').trim() || text.node;
        node.content = contentInput.value || '';
        node.color = colorInput.value || '#fff4c2';
        node.is_collapsed = collapsedInput.checked ? 1 : 0;
        node.parent_id = nextParentId || null;
        node.linked_task_id = taskSelectEl.value ? Number(taskSelectEl.value) : null;
        const linkedTask = state.tasks.find((task) => Number(task.id) === Number(node.linked_task_id));
        node.linked_task_title = linkedTask ? linkedTask.title : null;

        if (oldParentId && Number(oldParentId) !== Number(node.parent_id || 0)) {
            state.edges = state.edges.filter((edge) => {
                return !(
                    Number(edge.source_node_id) === Number(oldParentId) &&
                    Number(edge.target_node_id) === Number(node.id) &&
                    !edge.label
                );
            });
        }

        renderAll();
        scheduleSave();
    }

    function deleteSelectedNode() {
        if (!canEdit) return;
        const node = findNode(state.selectedNodeId);
        if (!node) return;
        if (!window.confirm(text.deleteConfirm)) return;
        pushHistory();
        removeNode(node.id);
        renderAll();
        scheduleSave();
    }

    function duplicateSelectedNode() {
        if (!canEdit) return;
        const source = findNode(state.selectedNodeId);
        if (!source) return;
        pushHistory();
        const clone = createNode({
            parent_id: source.parent_id ? Number(source.parent_id) : null,
            linked_task_id: source.linked_task_id ? Number(source.linked_task_id) : null,
            linked_task_title: source.linked_task_title || null,
            node_type: source.node_type || 'note',
            title: (source.title || text.node) + ' Copy',
            content: source.content || '',
            x: Number(source.x) + 40,
            y: Number(source.y) + 40,
            width: Number(source.width || 220),
            height: Number(source.height || 96),
            color: source.color || '#fff4c2',
            is_collapsed: Number(source.is_collapsed) === 1 ? 1 : 0
        });
        selectNode(clone.id);
        renderAll();
        scheduleSave();
    }

    function exportPng() {
        const visible = state.nodes.slice();
        if (!visible.length) return;
        const bounds = getNodeBounds(visible);
        if (!bounds) return;
        const padding = 30;
        const width = Math.ceil(bounds.maxX - bounds.minX + padding * 2);
        const height = Math.ceil(bounds.maxY - bounds.minY + padding * 2);
        const canvas = document.createElement('canvas');
        canvas.width = Math.min(5000, width);
        canvas.height = Math.min(4000, height);
        const ctx = canvas.getContext('2d');
        if (!ctx) return;
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, canvas.width, canvas.height);

        const shiftX = padding - bounds.minX;
        const shiftY = padding - bounds.minY;
        const nodeMap = new Map();
        visible.forEach((node) => nodeMap.set(Number(node.id), node));

        ctx.strokeStyle = '#5d759d';
        ctx.lineWidth = 2;
        collectRenderableEdges().forEach((edge) => {
            const s = nodeMap.get(Number(edge.source_node_id));
            const t = nodeMap.get(Number(edge.target_node_id));
            if (!s || !t) return;
            const curve = computeEdgeCurve(s, t);
            ctx.beginPath();
            ctx.moveTo(curve.sx + shiftX, curve.sy + shiftY);
            ctx.bezierCurveTo(
                curve.c1x + shiftX,
                curve.c1y + shiftY,
                curve.c2x + shiftX,
                curve.c2y + shiftY,
                curve.tx + shiftX,
                curve.ty + shiftY
            );
            ctx.stroke();
        });

        visible.forEach((node) => {
            const x = Number(node.x) + shiftX;
            const y = Number(node.y) + shiftY;
            const w = Math.max(120, Number(node.width || 220));
            const h = Math.max(56, Number(node.height || 96));
            roundRect(ctx, x, y, w, h, 8, node.color || '#fff4c2', '#b8c7dc');
            ctx.fillStyle = '#23364f';
            ctx.font = 'bold 13px sans-serif';
            ctx.fillText((node.title || text.node).slice(0, 32), x + 8, y + 20);
            ctx.fillStyle = '#45617f';
            ctx.font = '12px sans-serif';
            ctx.fillText((node.content || '').slice(0, 55), x + 8, y + 38);
        });

        const link = document.createElement('a');
        link.href = canvas.toDataURL('image/png');
        link.download = `visual-board-${boardId}-${new Date().toISOString().slice(0,19).replace(/[:T]/g, '-')}.png`;
        document.body.appendChild(link);
        link.click();
        link.remove();
    }

    function roundRect(ctx, x, y, w, h, r, fill, stroke) {
        ctx.beginPath();
        ctx.moveTo(x + r, y);
        ctx.arcTo(x + w, y, x + w, y + h, r);
        ctx.arcTo(x + w, y + h, x, y + h, r);
        ctx.arcTo(x, y + h, x, y, r);
        ctx.arcTo(x, y, x + w, y, r);
        ctx.closePath();
        ctx.fillStyle = fill;
        ctx.fill();
        ctx.strokeStyle = stroke;
        ctx.lineWidth = 1;
        ctx.stroke();
    }

    function bindEvents() {
        document.getElementById('vbAddRootBtn').addEventListener('click', addRootNode);
        document.getElementById('vbAddChildBtn').addEventListener('click', addChildNode);
        document.getElementById('vbAddSiblingBtn').addEventListener('click', addSiblingNode);
        document.getElementById('vbConnectBtn').addEventListener('click', toggleConnectMode);
        document.getElementById('vbAutoLayoutBtn').addEventListener('click', async () => {
            if (!canEdit) return;
            try {
                const res = await fetch(`${BASE_PATH}/api/visual-boards/${boardId}/auto-layout`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({})
                });
                const json = await res.json().catch(() => ({}));
                if (!res.ok || !json.success) {
                    setStatus(json.error || text.communication, true);
                    return;
                }
                if (json.data && Array.isArray(json.data.nodes)) {
                    pushHistory();
                    const map = new Map();
                    json.data.nodes.forEach((node) => map.set(Number(node.id), node));
                    state.nodes = state.nodes.map((node) => {
                        const layoutNode = map.get(Number(node.id));
                        if (!layoutNode) return node;
                        return {
                            ...node,
                            x: Number(layoutNode.x || node.x),
                            y: Number(layoutNode.y || node.y)
                        };
                    });
                    renderAll();
                    scheduleSave();
                }
            } catch (err) {
                setStatus(text.communication, true);
            }
        });
        document.getElementById('vbUndoBtn').addEventListener('click', undo);
        document.getElementById('vbRedoBtn').addEventListener('click', redo);
        document.getElementById('vbSaveBtn').addEventListener('click', () => saveBoard(true));
        document.getElementById('vbExportPngBtn').addEventListener('click', exportPng);
        document.getElementById('vbFitBtn').addEventListener('click', fitToScreen);
        document.getElementById('vbApplyNodeBtn').addEventListener('click', applyInspector);
        document.getElementById('vbDuplicateNodeBtn').addEventListener('click', duplicateSelectedNode);
        document.getElementById('vbDeleteNodeBtn').addEventListener('click', deleteSelectedNode);

        document.addEventListener('keydown', (ev) => {
            if (ev.target && (ev.target.tagName === 'INPUT' || ev.target.tagName === 'TEXTAREA' || ev.target.tagName === 'SELECT' || ev.target.isContentEditable)) {
                return;
            }

            if (ev.key === 'Tab' && canEdit) {
                ev.preventDefault();
                addChildNode();
            } else if (ev.key === 'Enter' && canEdit) {
                ev.preventDefault();
                addSiblingNode();
            } else if ((ev.key === 'Delete' || ev.key === 'Backspace') && canEdit) {
                ev.preventDefault();
                deleteSelectedNode();
            } else if ((ev.ctrlKey || ev.metaKey) && ev.key.toLowerCase() === 'z' && !ev.shiftKey && canEdit) {
                ev.preventDefault();
                undo();
            } else if (((ev.ctrlKey || ev.metaKey) && ev.key.toLowerCase() === 'y') || ((ev.ctrlKey || ev.metaKey) && ev.shiftKey && ev.key.toLowerCase() === 'z')) {
                ev.preventDefault();
                if (canEdit) redo();
            } else if ((ev.ctrlKey || ev.metaKey) && ev.key.toLowerCase() === 's') {
                ev.preventDefault();
                if (canEdit) saveBoard(true);
            }
        });

        setupStagePanZoom();
        window.addEventListener('resize', () => {
            if (!state.nodes.length) return;
            fitToScreen();
        });

        if (!canEdit) {
            setStatus(text.boardReadonly, false);
        }
    }

    bindEvents();
    syncWorldTransform();
    fetchBoardData();
})();
</script>

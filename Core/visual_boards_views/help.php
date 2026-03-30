<style>
.vb-help-wrap { max-width: 980px; margin: 18px auto 28px; }
.vb-help-card { border: 1px solid #d9e4f2; border-radius: 12px; background: #fff; padding: 18px 20px; margin-bottom: 12px; }
.vb-help-card h5 { margin-bottom: 10px; }
.vb-help-card ul { margin-bottom: 0; }
</style>

<div class="container-fluid">
    <div class="vb-help-wrap">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
            <h4 class="mb-0"><?= htmlspecialchars(tr_text('Visual Boards ヘルプ', 'Visual Boards Help')) ?></h4>
            <a href="<?= BASE_PATH ?>/visual-boards" class="btn btn-outline-secondary btn-sm"><?= htmlspecialchars(tr_text('Visual Boardsへ戻る', 'Back to Visual Boards')) ?></a>
        </div>

        <section class="vb-help-card">
            <h5><?= htmlspecialchars(tr_text('機能概要', 'Overview')) ?></h5>
            <p class="mb-0"><?= htmlspecialchars(tr_text('Visual Boards は、思考整理や構造化のためのノードベース機能です。既存のタスクカンバンとは用途・データともに分離されています。', 'Visual Boards is a node-based feature for idea structuring. It is separated from the existing Kanban task module in both purpose and data model.')) ?></p>
        </section>

        <section class="vb-help-card">
            <h5><?= htmlspecialchars(tr_text('基本操作', 'Basic operations')) ?></h5>
            <ul>
                <li><?= htmlspecialchars(tr_text('キャンバスのドラッグでパン、マウスホイールでズームできます。', 'Drag canvas background to pan and use mouse wheel to zoom.')) ?></li>
                <li><?= htmlspecialchars(tr_text('ノードを選択して Tab で子ノード、Enter で兄弟ノードを追加できます。', 'Select a node and press Tab to add child, Enter to add sibling.')) ?></li>
                <li><?= htmlspecialchars(tr_text('接続線は「接続線」ボタンで開始し、2つのノードを順に選択します。', 'Use "Connect" button and select source/target nodes in order.')) ?></li>
                <li><?= htmlspecialchars(tr_text('自動レイアウト、折りたたみ、Undo/Redo に対応しています。', 'Auto layout, collapse, and undo/redo are supported.')) ?></li>
            </ul>
        </section>

        <section class="vb-help-card">
            <h5><?= htmlspecialchars(tr_text('共有と権限', 'Sharing and permissions')) ?></h5>
            <ul>
                <li><?= htmlspecialchars(tr_text('個人 / チーム / 組織の単位でボードを作成できます。', 'Boards can be created for personal, team, or organization scope.')) ?></li>
                <li><?= htmlspecialchars(tr_text('編集可否はボード権限に従います。閲覧専用の場合は編集操作が無効化されます。', 'Editability follows board permissions. Editing controls are disabled in read-only mode.')) ?></li>
            </ul>
        </section>

        <section class="vb-help-card">
            <h5><?= htmlspecialchars(tr_text('タスク連携と出力', 'Task link and export')) ?></h5>
            <ul>
                <li><?= htmlspecialchars(tr_text('ノード単位で既存タスクを関連付けできます。', 'You can link existing tasks per node.')) ?></li>
                <li><?= htmlspecialchars(tr_text('JSON / PDF / PNG の形式で出力できます。', 'Export is available in JSON / PDF / PNG.')) ?></li>
            </ul>
        </section>
    </div>
</div>

<?php
namespace Core;

use Models\Team;
use Models\User;
use Models\VisualBoard;

class VisualBoardModule extends Controller
{
    private $db;
    private $visualBoardModel;
    private $teamModel;
    private $userModel;
    private static $schemaReady = false;

    public function __construct()
    {
        parent::__construct();
        $this->db = Database::getInstance();
        $this->visualBoardModel = new VisualBoard();
        $this->teamModel = new Team();
        $this->userModel = new User();
        $this->ensureSchema();
    }

    private function ensureSchema()
    {
        if (self::$schemaReady) {
            return;
        }

        $sqlList = [
            "CREATE TABLE IF NOT EXISTS visual_boards (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(120) NOT NULL,
                description TEXT NULL,
                template_key ENUM('mind_map','flowchart','brainstorm','planning') NOT NULL DEFAULT 'mind_map',
                owner_type ENUM('user','team','organization') NOT NULL,
                owner_id INT NOT NULL,
                is_public TINYINT(1) NOT NULL DEFAULT 0,
                created_by INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_visual_boards_owner (owner_type, owner_id),
                INDEX idx_visual_boards_updated (updated_at),
                CONSTRAINT fk_visual_boards_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS visual_board_members (
                board_id INT NOT NULL,
                user_id INT NOT NULL,
                role ENUM('admin','editor','viewer') NOT NULL DEFAULT 'viewer',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (board_id, user_id),
                INDEX idx_visual_board_members_user (user_id),
                CONSTRAINT fk_visual_board_members_board FOREIGN KEY (board_id) REFERENCES visual_boards(id) ON DELETE CASCADE,
                CONSTRAINT fk_visual_board_members_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS visual_board_nodes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                board_id INT NOT NULL,
                parent_id INT NULL,
                linked_task_id INT NULL,
                node_type ENUM('topic','idea','action','note') NOT NULL DEFAULT 'note',
                title VARCHAR(255) NOT NULL,
                content TEXT NULL,
                x DECIMAL(10,2) NOT NULL DEFAULT 0,
                y DECIMAL(10,2) NOT NULL DEFAULT 0,
                width INT NOT NULL DEFAULT 220,
                height INT NOT NULL DEFAULT 96,
                color VARCHAR(20) NOT NULL DEFAULT '#fff4c2',
                is_collapsed TINYINT(1) NOT NULL DEFAULT 0,
                sort_order INT NOT NULL DEFAULT 0,
                created_by INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_visual_board_nodes_board (board_id, sort_order),
                INDEX idx_visual_board_nodes_parent (parent_id),
                INDEX idx_visual_board_nodes_task (linked_task_id),
                CONSTRAINT fk_visual_board_nodes_board FOREIGN KEY (board_id) REFERENCES visual_boards(id) ON DELETE CASCADE,
                CONSTRAINT fk_visual_board_nodes_parent FOREIGN KEY (parent_id) REFERENCES visual_board_nodes(id) ON DELETE SET NULL,
                CONSTRAINT fk_visual_board_nodes_task FOREIGN KEY (linked_task_id) REFERENCES task_cards(id) ON DELETE SET NULL,
                CONSTRAINT fk_visual_board_nodes_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE IF NOT EXISTS visual_board_edges (
                id INT AUTO_INCREMENT PRIMARY KEY,
                board_id INT NOT NULL,
                source_node_id INT NOT NULL,
                target_node_id INT NOT NULL,
                label VARCHAR(255) NULL,
                line_style ENUM('solid','dashed') NOT NULL DEFAULT 'solid',
                created_by INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_visual_board_edges_board (board_id),
                CONSTRAINT fk_visual_board_edges_board FOREIGN KEY (board_id) REFERENCES visual_boards(id) ON DELETE CASCADE,
                CONSTRAINT fk_visual_board_edges_source FOREIGN KEY (source_node_id) REFERENCES visual_board_nodes(id) ON DELETE CASCADE,
                CONSTRAINT fk_visual_board_edges_target FOREIGN KEY (target_node_id) REFERENCES visual_board_nodes(id) ON DELETE CASCADE,
                CONSTRAINT fk_visual_board_edges_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        ];

        foreach ($sqlList as $sql) {
            try {
                $this->db->execute($sql);
            } catch (\Exception $e) {
                error_log('Visual board schema ensure failed: ' . $e->getMessage());
            }
        }

        self::$schemaReady = true;
    }

    public function index()
    {
        $this->requireAuthForPage();
        $userId = (int)$this->auth->id();
        $boards = $this->visualBoardModel->getUserBoards($userId);
        $teams = $this->teamModel->getUserTeams($userId);
        $organizations = $this->userModel->getUserOrganizations($userId);

        $this->renderTemplate('index', [
            'title' => tr_text('Visual Boards', 'Visual Boards'),
            'boards' => $boards,
            'teams' => $teams,
            'organizations' => $organizations
        ]);
    }

    public function createBoard()
    {
        $this->requireAuthForPage();
        $userId = (int)$this->auth->id();
        $teams = $this->teamModel->getUserTeams($userId);
        $organizations = $this->userModel->getUserOrganizations($userId);

        $this->renderTemplate('create_board', [
            'title' => tr_text('Visual Board作成', 'Create Visual Board'),
            'teams' => $teams,
            'organizations' => $organizations
        ]);
    }

    public function board($params)
    {
        $this->requireAuthForPage();
        $boardId = isset($params['id']) ? (int)$params['id'] : 0;
        $userId = (int)$this->auth->id();
        if ($boardId <= 0 || !$this->visualBoardModel->canUserAccessBoard($boardId, $userId)) {
            $this->redirect(BASE_PATH . '/visual-boards');
        }

        $board = $this->visualBoardModel->getBoard($boardId);
        if (!$board) {
            $this->redirect(BASE_PATH . '/visual-boards');
        }

        $canEdit = $this->auth->isAdmin() || $this->visualBoardModel->canUserEditBoard($boardId, $userId);

        $this->renderTemplate('board', [
            'title' => $board['name'] . ' - ' . tr_text('Visual Boards', 'Visual Boards'),
            'board' => $board,
            'members' => $this->visualBoardModel->getBoardMembers($boardId),
            'tasks' => $this->visualBoardModel->getBoardAvailableTasks($boardId, $userId, 300),
            'canEdit' => $canEdit
        ]);
    }

    public function help()
    {
        $this->requireAuthForPage();
        $this->renderTemplate('help', [
            'title' => tr_text('Visual Boards ヘルプ', 'Visual Boards Help')
        ]);
    }

    public function exportJson($params)
    {
        $this->requireAuthForPage();
        $boardId = isset($params['id']) ? (int)$params['id'] : 0;
        $userId = (int)$this->auth->id();
        if ($boardId <= 0 || !$this->visualBoardModel->canUserAccessBoard($boardId, $userId)) {
            http_response_code(403);
            echo tr_text('権限がありません', 'Permission denied.');
            exit;
        }

        $payload = $this->visualBoardModel->getExportPayload($boardId);
        $filename = 'visual-board-' . $boardId . '-' . date('Ymd-His') . '.json';
        header('Content-Type: application/json; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public function exportPdf($params)
    {
        $this->requireAuthForPage();
        $boardId = isset($params['id']) ? (int)$params['id'] : 0;
        $userId = (int)$this->auth->id();
        if ($boardId <= 0 || !$this->visualBoardModel->canUserAccessBoard($boardId, $userId)) {
            http_response_code(403);
            echo tr_text('権限がありません', 'Permission denied.');
            exit;
        }

        $payload = $this->visualBoardModel->getExportPayload($boardId);
        $board = $payload['board'] ?? null;
        if (!$board) {
            http_response_code(404);
            echo tr_text('ボードが見つかりません', 'Board not found.');
            exit;
        }

        $autoload = __DIR__ . '/../vendor/autoload.php';
        if (!file_exists($autoload)) {
            http_response_code(500);
            echo tr_text('PDF出力ライブラリが見つかりません', 'PDF library not found.');
            exit;
        }
        require_once $autoload;

        $pdf = new \TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('TeamSpace');
        $pdf->SetAuthor((string)($board['creator_name'] ?? ''));
        $pdf->SetTitle((string)$board['name']);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(10, 10, 10);
        $pdf->AddPage();
        $pdf->SetFont('dejavusans', '', 10);

        $pdf->Cell(0, 8, tr_text('Visual Boards エクスポート', 'Visual Boards Export'), 0, 1, 'L');
        $pdf->SetFont('dejavusans', '', 9);
        $pdf->Cell(0, 6, tr_text('ボード名: ', 'Board: ') . $board['name'], 0, 1, 'L');
        $pdf->Cell(0, 6, tr_text('出力日時: ', 'Exported at: ') . date('Y-m-d H:i:s'), 0, 1, 'L');
        $pdf->Ln(3);

        $nodes = $payload['nodes'] ?? [];
        $edges = $payload['edges'] ?? [];
        if (empty($nodes)) {
            $pdf->Cell(0, 8, tr_text('ノードがありません。', 'No nodes found.'), 0, 1, 'L');
        } else {
            $this->drawPdfBoard($pdf, $nodes, $edges);
        }

        $filename = 'visual-board-' . $boardId . '-' . date('Ymd-His') . '.pdf';
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $pdf->Output('', 'S');
        exit;
    }

    public function apiCreateBoard($params, $data)
    {
        if (!$this->auth->check()) {
            return ['error' => tr_text('認証が必要です', 'Authentication is required.'), 'code' => 401];
        }

        $userId = (int)$this->auth->id();
        $name = trim((string)($data['name'] ?? ''));
        $ownerType = (string)($data['owner_type'] ?? '');
        $ownerId = (int)($data['owner_id'] ?? 0);
        $templateKey = (string)($data['template_key'] ?? 'mind_map');
        $validTemplates = ['mind_map', 'flowchart', 'brainstorm', 'planning'];

        if ($name === '') {
            return ['error' => tr_text('ボード名は必須です', 'Board name is required.'), 'code' => 400];
        }
        if (!in_array($ownerType, ['user', 'team', 'organization'], true) || $ownerId <= 0) {
            return ['error' => tr_text('所有者設定が不正です', 'Owner setting is invalid.'), 'code' => 400];
        }
        if (!in_array($templateKey, $validTemplates, true)) {
            $templateKey = 'mind_map';
        }

        if ($ownerType === 'user' && $ownerId !== $userId) {
            return ['error' => tr_text('個人ボードは自分のみ作成できます', 'You can create personal boards only for yourself.'), 'code' => 403];
        }
        if ($ownerType === 'team' && !$this->teamModel->isUserTeamMember($ownerId, $userId)) {
            return ['error' => tr_text('このチームで作成する権限がありません', 'No permission for this team.'), 'code' => 403];
        }
        if ($ownerType === 'organization') {
            $orgIds = array_map('intval', $this->userModel->getUserOrganizationIds($userId));
            if (!in_array($ownerId, $orgIds, true)) {
                return ['error' => tr_text('この組織で作成する権限がありません', 'No permission for this organization.'), 'code' => 403];
            }
        }

        $boardId = $this->visualBoardModel->createBoard([
            'name' => $name,
            'description' => trim((string)($data['description'] ?? '')),
            'template_key' => $templateKey,
            'owner_type' => $ownerType,
            'owner_id' => $ownerId,
            'is_public' => !empty($data['is_public']),
            'created_by' => $userId
        ]);

        if (!$boardId) {
            return ['error' => tr_text('ボード作成に失敗しました', 'Failed to create board.'), 'code' => 500];
        }

        return [
            'success' => true,
            'message' => tr_text('Visual Boardを作成しました', 'Visual Board created.'),
            'redirect' => BASE_PATH . '/visual-boards/board/' . $boardId,
            'data' => ['id' => $boardId]
        ];
    }

    public function apiDeleteBoard($params)
    {
        if (!$this->auth->check()) {
            return ['error' => tr_text('認証が必要です', 'Authentication is required.'), 'code' => 401];
        }
        $boardId = isset($params['id']) ? (int)$params['id'] : 0;
        $userId = (int)$this->auth->id();
        if ($boardId <= 0) {
            return ['error' => tr_text('ボードIDが不正です', 'Invalid board ID.'), 'code' => 400];
        }
        if (!$this->auth->isAdmin() && !$this->visualBoardModel->isUserBoardAdmin($boardId, $userId)) {
            return ['error' => tr_text('権限がありません', 'Permission denied.'), 'code' => 403];
        }
        if (!$this->visualBoardModel->deleteBoard($boardId)) {
            return ['error' => tr_text('ボード削除に失敗しました', 'Failed to delete board.'), 'code' => 500];
        }

        return ['success' => true, 'message' => tr_text('ボードを削除しました', 'Board deleted.')];
    }

    public function apiGetBoardData($params)
    {
        if (!$this->auth->check()) {
            return ['error' => tr_text('認証が必要です', 'Authentication is required.'), 'code' => 401];
        }
        $boardId = isset($params['id']) ? (int)$params['id'] : 0;
        $userId = (int)$this->auth->id();
        if ($boardId <= 0 || !$this->visualBoardModel->canUserAccessBoard($boardId, $userId)) {
            return ['error' => tr_text('権限がありません', 'Permission denied.'), 'code' => 403];
        }

        $board = $this->visualBoardModel->getBoard($boardId);
        if (!$board) {
            return ['error' => tr_text('ボードが見つかりません', 'Board not found.'), 'code' => 404];
        }

        return [
            'success' => true,
            'data' => [
                'board' => $board,
                'nodes' => $this->visualBoardModel->getBoardNodes($boardId),
                'edges' => $this->visualBoardModel->getBoardEdges($boardId),
                'members' => $this->visualBoardModel->getBoardMembers($boardId),
                'tasks' => $this->visualBoardModel->getBoardAvailableTasks($boardId, $userId, 300),
                'can_edit' => $this->auth->isAdmin() || $this->visualBoardModel->canUserEditBoard($boardId, $userId)
            ]
        ];
    }

    public function apiSyncBoard($params, $data)
    {
        if (!$this->auth->check()) {
            return ['error' => tr_text('認証が必要です', 'Authentication is required.'), 'code' => 401];
        }
        $boardId = isset($params['id']) ? (int)$params['id'] : 0;
        $userId = (int)$this->auth->id();
        if ($boardId <= 0) {
            return ['error' => tr_text('ボードIDが不正です', 'Invalid board ID.'), 'code' => 400];
        }
        if (!$this->auth->isAdmin() && !$this->visualBoardModel->canUserEditBoard($boardId, $userId)) {
            return ['error' => tr_text('編集権限がありません', 'No edit permission.'), 'code' => 403];
        }

        $nodes = (isset($data['nodes']) && is_array($data['nodes'])) ? $data['nodes'] : [];
        $edges = (isset($data['edges']) && is_array($data['edges'])) ? $data['edges'] : [];
        if (count($nodes) > 1200 || count($edges) > 2500) {
            return ['error' => tr_text('データ件数が多すぎます', 'Too many items.'), 'code' => 413];
        }

        if (!$this->visualBoardModel->replaceBoardGraph($boardId, $nodes, $edges, $userId)) {
            return ['error' => tr_text('保存に失敗しました', 'Failed to save board.'), 'code' => 500];
        }

        return [
            'success' => true,
            'message' => tr_text('保存しました', 'Saved.'),
            'data' => [
                'nodes' => $this->visualBoardModel->getBoardNodes($boardId),
                'edges' => $this->visualBoardModel->getBoardEdges($boardId)
            ]
        ];
    }

    public function apiAutoLayout($params, $data)
    {
        if (!$this->auth->check()) {
            return ['error' => tr_text('認証が必要です', 'Authentication is required.'), 'code' => 401];
        }
        $boardId = isset($params['id']) ? (int)$params['id'] : 0;
        $userId = (int)$this->auth->id();
        if ($boardId <= 0) {
            return ['error' => tr_text('ボードIDが不正です', 'Invalid board ID.'), 'code' => 400];
        }
        if (!$this->auth->isAdmin() && !$this->visualBoardModel->canUserEditBoard($boardId, $userId)) {
            return ['error' => tr_text('編集権限がありません', 'No edit permission.'), 'code' => 403];
        }

        $nodes = $this->visualBoardModel->applyAutoLayout($boardId);
        return [
            'success' => true,
            'message' => tr_text('自動レイアウトを適用しました', 'Auto layout applied.'),
            'data' => ['nodes' => $nodes]
        ];
    }

    public function apiTaskOptions($params)
    {
        if (!$this->auth->check()) {
            return ['error' => tr_text('認証が必要です', 'Authentication is required.'), 'code' => 401];
        }
        $boardId = isset($params['id']) ? (int)$params['id'] : 0;
        $userId = (int)$this->auth->id();
        if ($boardId <= 0 || !$this->visualBoardModel->canUserAccessBoard($boardId, $userId)) {
            return ['error' => tr_text('権限がありません', 'Permission denied.'), 'code' => 403];
        }

        return [
            'success' => true,
            'data' => ['tasks' => $this->visualBoardModel->getBoardAvailableTasks($boardId, $userId, 300)]
        ];
    }

    private function requireAuthForPage()
    {
        if (!$this->auth->check()) {
            $this->redirect(BASE_PATH . '/login');
        }
    }

    private function renderTemplate($template, $data = [])
    {
        $this->sendNoCacheHeaders();
        ob_start();
        extract($data);

        require __DIR__ . '/../views/layouts/header.php';
        $viewPath = __DIR__ . '/visual_boards_views/' . $template . '.php';
        if (!file_exists($viewPath)) {
            throw new \RuntimeException('Visual board view not found: ' . $template);
        }
        require $viewPath;
        require __DIR__ . '/../views/layouts/footer.php';

        echo RuntimeI18n::translateHtml((string)ob_get_clean());
    }

    private function drawPdfBoard(\TCPDF $pdf, $nodes, $edges)
    {
        $minX = null;
        $maxX = null;
        $minY = null;
        $maxY = null;
        foreach ($nodes as $node) {
            $x = (float)$node['x'];
            $y = (float)$node['y'];
            $w = max(120, (float)($node['width'] ?? 220));
            $h = max(60, (float)($node['height'] ?? 96));
            $minX = $minX === null ? $x : min($minX, $x);
            $maxX = $maxX === null ? ($x + $w) : max($maxX, $x + $w);
            $minY = $minY === null ? $y : min($minY, $y);
            $maxY = $maxY === null ? ($y + $h) : max($maxY, $y + $h);
        }

        $canvasX = 12.0;
        $canvasY = 35.0;
        $canvasW = 270.0;
        $canvasH = 150.0;
        $spanX = max(1.0, ($maxX - $minX));
        $spanY = max(1.0, ($maxY - $minY));
        $scale = min($canvasW / $spanX, $canvasH / $spanY);

        $pointMap = [];
        foreach ($nodes as $node) {
            $x = (float)$node['x'];
            $y = (float)$node['y'];
            $w = max(120, (float)($node['width'] ?? 220));
            $h = max(60, (float)($node['height'] ?? 96));

            $sx = $canvasX + (($x - $minX) * $scale);
            $sy = $canvasY + (($y - $minY) * $scale);
            $sw = max(20, $w * $scale);
            $sh = max(14, $h * $scale);

            $pointMap[(int)$node['id']] = [
                'cx' => $sx + ($sw / 2.0),
                'cy' => $sy + ($sh / 2.0),
                'x' => $sx,
                'y' => $sy,
                'w' => $sw,
                'h' => $sh,
                'title' => $node['title']
            ];
        }

        $pdf->SetDrawColor(130, 130, 130);
        foreach ($edges as $edge) {
            $source = $pointMap[(int)$edge['source_node_id']] ?? null;
            $target = $pointMap[(int)$edge['target_node_id']] ?? null;
            if (!$source || !$target) {
                continue;
            }
            if (($edge['line_style'] ?? 'solid') === 'dashed') {
                $pdf->SetLineStyle(['dash' => '2,2', 'width' => 0.3, 'color' => [130, 130, 130]]);
            } else {
                $pdf->SetLineStyle(['dash' => 0, 'width' => 0.3, 'color' => [130, 130, 130]]);
            }
            $pdf->Line($source['cx'], $source['cy'], $target['cx'], $target['cy']);
        }

        foreach ($pointMap as $point) {
            $pdf->SetFillColor(255, 248, 225);
            $pdf->SetDrawColor(190, 190, 190);
            $pdf->RoundedRect($point['x'], $point['y'], $point['w'], $point['h'], 1.5, '1111', 'DF');
            $pdf->SetXY($point['x'] + 1, $point['y'] + 1);
            $pdf->SetFont('dejavusans', '', 7);
            $pdf->MultiCell(
                $point['w'] - 2,
                3.5,
                mb_substr((string)$point['title'], 0, 60),
                0,
                'L',
                false,
                1,
                '',
                '',
                true,
                0,
                false,
                true,
                14
            );
        }

        $pdf->SetLineStyle(['dash' => 0, 'width' => 0.2, 'color' => [190, 190, 190]]);
        $pdf->Rect($canvasX, $canvasY, $canvasW, $canvasH);
        $pdf->SetXY($canvasX, $canvasY + $canvasH + 2);
        $pdf->SetFont('dejavusans', '', 8);
        $pdf->MultiCell(
            0,
            5,
            tr_text('ノード数: ', 'Nodes: ') . count($nodes) . ' / ' . tr_text('接続線数: ', 'Edges: ') . count($edges),
            0,
            'L',
            false,
            1
        );
    }
}

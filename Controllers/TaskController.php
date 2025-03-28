<?php
// controllers/TaskController.php
namespace Controllers;

use Core\Controller;
use Core\Database;
use Core\Auth;
use Models\Task;
use Models\Team;
use Models\User;
use Models\Organization;

class TaskController extends Controller
{
    private $db;
    private $taskModel;
    private $teamModel;
    private $userModel;
    private $organizationModel;

    public function __construct()
    {
        parent::__construct();
        $this->db = Database::getInstance();
        $this->taskModel = new Task();
        $this->teamModel = new Team();
        $this->userModel = new User();
        $this->organizationModel = new Organization();

        // 認証チェック
        if (!$this->auth->check()) {
            $this->redirect(BASE_PATH . '/login');
        }
    }

    /**
     * タスク管理のダッシュボードを表示
     */
    public function index()
    {
        $userId = $this->auth->id();

        // ユーザーのタスク概要を取得
        $taskSummary = $this->taskModel->getUserTasksSummary($userId);

        // ユーザーのタスクボード一覧を取得
        $boards = $this->taskModel->getUserBoards($userId);

        // ユーザーが所属しているチーム一覧を取得
        $teams = $this->teamModel->getUserTeams($userId);

        // ユーザーが所属している組織一覧を取得
        $organizationModel = new \Models\Organization();
        $organizations = $organizationModel->getAll();

        // ユーザーの直近のタスク5件を取得
        $upcomingTasks = $this->taskModel->getUserUpcomingTasks($userId, 5);

        // ユーザーの遅延しているタスク5件を取得
        $overdueTasks = $this->taskModel->getUserOverdueTasks($userId, 5);

        $viewData = [
            'title' => 'タスク管理',
            'taskSummary' => $taskSummary,
            'boards' => $boards,
            'teams' => $teams,
            'organizations' => $organizations,
            'upcomingTasks' => $upcomingTasks,
            'overdueTasks' => $overdueTasks,
            'jsFiles' => ['task.js']
        ];

        $this->view('task/index', $viewData);
    }

    /**
     * 個人用タスクボード一覧を表示
     */
    public function myBoards()
    {
        $userId = $this->auth->id();

        // 個人用タスクボード一覧を取得
        $boards = $this->taskModel->getUserBoards($userId);

        $viewData = [
            'title' => '個人タスクボード',
            'boards' => $boards,
            'jsFiles' => ['task.js']
        ];

        $this->view('task/my_boards', $viewData);
    }

    /**
     * チームタスクボード一覧を表示
     */
    public function teamBoards()
    {
        $userId = $this->auth->id();

        // ユーザーが所属しているチーム一覧を取得
        $teams = $this->teamModel->getUserTeams($userId);

        // 各チームのタスクボード一覧を取得
        $teamBoards = [];
        foreach ($teams as $team) {
            $boards = $this->taskModel->getTeamBoards($team['id']);
            if (!empty($boards)) {
                $teamBoards[$team['id']] = [
                    'team' => $team,
                    'boards' => $boards
                ];
            }
        }

        $viewData = [
            'title' => 'チームタスクボード',
            'teams' => $teams,
            'teamBoards' => $teamBoards,
            'jsFiles' => ['task.js']
        ];

        $this->view('task/team_boards', $viewData);
    }

    /**
     * 組織タスクボード一覧を表示
     */
    public function organizationBoards()
    {
        $userId = $this->auth->id();

        // ユーザーが所属している組織一覧を取得
        $userOrgs = $this->userModel->getUserOrganizations($userId);

        // 各組織のタスクボード一覧を取得
        $orgBoards = [];
        foreach ($userOrgs as $org) {
            $boards = $this->taskModel->getOrganizationBoards($org['id']);
            if (!empty($boards)) {
                $orgBoards[$org['id']] = [
                    'organization' => $org,
                    'boards' => $boards
                ];
            }
        }

        $viewData = [
            'title' => '組織タスクボード',
            'organizations' => $userOrgs,
            'orgBoards' => $orgBoards,
            'jsFiles' => ['task.js']
        ];

        $this->view('task/organization_boards', $viewData);
    }

    /**
     * チーム一覧を表示
     */
    public function teams()
    {
        $userId = $this->auth->id();

        // ユーザーが所属しているチーム一覧を取得
        $teams = $this->teamModel->getUserTeams($userId);

        $viewData = [
            'title' => 'チーム管理',
            'teams' => $teams,
            'jsFiles' => ['task.js']
        ];

        $this->view('task/teams', $viewData);
    }

    /**
     * チーム作成画面を表示
     */
    public function createTeam()
    {
        // アクティブユーザー一覧を取得（メンバー追加用）
        $users = $this->userModel->getActiveUsers();

        $viewData = [
            'title' => 'チーム作成',
            'users' => $users,
            'jsFiles' => ['task.js']
        ];

        $this->view('task/create_team', $viewData);
    }

    /**
     * チーム編集画面を表示
     */
    public function editTeam($params)
    {
        $teamId = $params['id'] ?? null;
        if (!$teamId) {
            $this->redirect(BASE_PATH . '/task/teams');
        }

        $userId = $this->auth->id();

        // チーム情報を取得
        $team = $this->teamModel->getById($teamId);
        if (!$team) {
            $this->redirect(BASE_PATH . '/task/teams');
        }

        // チームメンバーを取得
        $members = $this->teamModel->getMembers($teamId);

        // ユーザーが管理者かどうかチェック
        if (!$this->teamModel->isUserTeamAdmin($teamId, $userId) && !$this->auth->isAdmin()) {
            $this->redirect(BASE_PATH . '/task/teams');
        }

        // アクティブユーザー一覧を取得（メンバー追加用）
        $users = $this->userModel->getActiveUsers();

        $viewData = [
            'title' => 'チーム編集',
            'team' => $team,
            'members' => $members,
            'users' => $users,
            'jsFiles' => ['task.js']
        ];

        $this->view('task/edit_team', $viewData);
    }

    /**
     * チームの詳細を表示
     */
    public function viewTeam($params)
    {
        $teamId = $params['id'] ?? null;
        if (!$teamId) {
            $this->redirect(BASE_PATH . '/task/teams');
        }

        // チーム情報を取得
        $team = $this->teamModel->getById($teamId);
        if (!$team) {
            $this->redirect(BASE_PATH . '/task/teams');
        }

        // チームメンバーを取得
        $members = $this->teamModel->getMembers($teamId);

        // チームのタスクボード一覧を取得
        $boards = $this->taskModel->getTeamBoards($teamId);

        $viewData = [
            'title' => $team['name'] . ' - チーム詳細',
            'team' => $team,
            'members' => $members,
            'boards' => $boards,
            'jsFiles' => ['task.js']
        ];

        $this->view('task/view_team', $viewData);
    }

    /**
     * タスクボード作成画面を表示
     */
    public function createBoard()
    {
        $userId = $this->auth->id();

        // ユーザーが所属しているチーム一覧を取得
        $teams = $this->teamModel->getUserTeams($userId);

        // ユーザーが所属している組織一覧を取得
        $userOrgs = $this->userModel->getUserOrganizations($userId);

        $viewData = [
            'title' => 'タスクボード作成',
            'teams' => $teams,
            'organizations' => $userOrgs,
            'jsFiles' => ['task.js']
        ];

        $this->view('task/create_board', $viewData);
    }

    /**
     * タスクボード編集画面を表示
     */
    public function editBoard($params)
    {
        $boardId = $params['id'] ?? null;
        if (!$boardId) {
            $this->redirect(BASE_PATH . '/task');
        }

        $userId = $this->auth->id();

        // ボード情報を取得
        $board = $this->taskModel->getBoard($boardId);
        if (!$board) {
            $this->redirect(BASE_PATH . '/task');
        }

        // ユーザーがボードにアクセス可能かチェック
        if (!$this->taskModel->canUserAccessBoard($boardId, $userId)) {
            $this->redirect(BASE_PATH . '/task');
        }

        // ユーザーがボードを編集可能かチェック
        $canEdit = $this->taskModel->canUserEditCard(null, $userId);

        // ユーザーが所属しているチーム一覧を取得
        $teams = $this->teamModel->getUserTeams($userId);

        // ユーザーが所属している組織一覧を取得
        $userOrgs = $this->userModel->getUserOrganizations($userId);

        // ボードメンバーを取得
        $members = $this->taskModel->getBoardMembers($boardId);

        $viewData = [
            'title' => 'タスクボード編集',
            'board' => $board,
            'teams' => $teams,
            'organizations' => $userOrgs,
            'members' => $members,
            'canEdit' => $canEdit,
            'jsFiles' => ['task.js']
        ];

        $this->view('task/edit_board', $viewData);
    }

    /**
     * タスクボードを表示
     */
    public function board($params)
    {
        $boardId = $params['id'] ?? null;
        if (!$boardId) {
            $this->redirect(BASE_PATH . '/task');
        }

        $userId = $this->auth->id();

        // ボード情報を取得
        $board = $this->taskModel->getBoard($boardId);
        if (!$board) {
            $this->redirect(BASE_PATH . '/task');
        }

        // ユーザーがボードにアクセス可能かチェック
        if (!$this->taskModel->canUserAccessBoard($boardId, $userId)) {
            $this->redirect(BASE_PATH . '/task');
        }

        // ボードのリスト一覧を取得
        $lists = [];
        $boardLists = $this->taskModel->getBoardLists($boardId);

        foreach ($boardLists as $list) {
            $list['cards'] = $this->taskModel->getListCards($list['id']);
            $lists[] = $list;
        }

        // ボードのラベル一覧を取得
        $labels = $this->taskModel->getBoardLabels($boardId);

        // ボードのメンバー一覧を取得
        $members = $this->taskModel->getBoardMembers($boardId);

        // タスク概要統計情報を取得
        $summary = $this->taskModel->getBoardTasksSummary($boardId);

        // ユーザーがボードを編集可能かチェック
        $canEdit = $this->taskModel->isUserBoardAdmin($boardId, $userId);

        // 管理者権限があれば編集可能
        if ($this->auth->isAdmin()) {
            $canEdit = true;
        }

        $viewData = [
            'title' => $board['name'] . ' - タスクボード',
            'board' => $board,
            'lists' => $lists,
            'labels' => $labels,
            'members' => $members,
            'summary' => $summary,
            'canEdit' => $canEdit,
            'jsFiles' => ['task-board.js']
        ];

        $this->view('task/view_board', $viewData);
    }

    /**
     * カード詳細を表示
     */
    public function card($params)
    {
        $cardId = $params['id'] ?? null;
        if (!$cardId) {
            $this->redirect(BASE_PATH . '/task');
        }

        $userId = $this->auth->id();

        // カード情報を取得
        $card = $this->taskModel->getCardWithRelations($cardId);
        if (!$card) {
            $this->redirect(BASE_PATH . '/task');
        }

        // リストからボードIDを取得
        $list = $this->taskModel->getList($card['list_id']);
        if (!$list) {
            $this->redirect(BASE_PATH . '/task');
        }

        $boardId = $list['board_id'];

        // ユーザーがボードにアクセス可能かチェック
        if (!$this->taskModel->canUserAccessBoard($boardId, $userId)) {
            $this->redirect(BASE_PATH . '/task');
        }

        // ボード情報を取得
        $board = $this->taskModel->getBoard($boardId);

        // ボードのリスト一覧を取得
        $lists = $this->taskModel->getBoardLists($boardId);

        // ボードのラベル一覧を取得
        $labels = $this->taskModel->getBoardLabels($boardId);

        // ボードのメンバー一覧を取得
        $members = $this->taskModel->getBoardMembers($boardId);

        // ユーザーがカードを編集可能かチェック
        $canEdit = $this->taskModel->canUserEditCard($cardId, $userId);

        $viewData = [
            'title' => $card['title'] . ' - カード詳細',
            'card' => $card,
            'board' => $board,
            'lists' => $lists,
            'labels' => $labels,
            'members' => $members,
            'canEdit' => $canEdit,
            'jsFiles' => ['task.js']
        ];

        $this->view('task/view_card', $viewData);
    }

    /**
     * カード作成画面を表示
     */
    public function createCard($params)
    {
        $listId = $params['list_id'] ?? null;
        if (!$listId) {
            $this->redirect(BASE_PATH . '/task');
        }

        $userId = $this->auth->id();

        // リスト情報を取得
        $list = $this->taskModel->getList($listId);
        if (!$list) {
            $this->redirect(BASE_PATH . '/task');
        }

        $boardId = $list['board_id'];

        // ユーザーがボードにアクセス可能かチェック
        if (!$this->taskModel->canUserAccessBoard($boardId, $userId)) {
            $this->redirect(BASE_PATH . '/task');
        }

        // ボード情報を取得
        $board = $this->taskModel->getBoard($boardId);

        // ボードのリスト一覧を取得
        $lists = $this->taskModel->getBoardLists($boardId);

        // ボードのラベル一覧を取得
        $labels = $this->taskModel->getBoardLabels($boardId);

        // ボードのメンバー一覧を取得
        $members = $this->taskModel->getBoardMembers($boardId);

        $viewData = [
            'title' => '新規カード作成',
            'list' => $list,
            'board' => $board,
            'lists' => $lists,
            'labels' => $labels,
            'members' => $members,
            'jsFiles' => ['task.js']
        ];

        $this->view('task/create_card', $viewData);
    }

    /**
     * カード編集画面を表示
     */
    public function editCard($params)
    {
        $cardId = $params['id'] ?? null;
        if (!$cardId) {
            $this->redirect(BASE_PATH . '/task');
        }

        $userId = $this->auth->id();

        // カード情報を取得
        $card = $this->taskModel->getCardWithRelations($cardId);
        if (!$card) {
            $this->redirect(BASE_PATH . '/task');
        }

        // リストからボードIDを取得
        $list = $this->taskModel->getList($card['list_id']);
        if (!$list) {
            $this->redirect(BASE_PATH . '/task');
        }

        $boardId = $list['board_id'];

        // ユーザーがボードにアクセス可能かチェック
        if (!$this->taskModel->canUserAccessBoard($boardId, $userId)) {
            $this->redirect(BASE_PATH . '/task');
        }

        // ユーザーがカードを編集可能かチェック
        if (!$this->taskModel->canUserEditCard($cardId, $userId)) {
            $this->redirect(BASE_PATH . '/task/card/' . $cardId);
        }

        // ボード情報を取得
        $board = $this->taskModel->getBoard($boardId);

        // ボードのリスト一覧を取得
        $lists = $this->taskModel->getBoardLists($boardId);

        // ボードのラベル一覧を取得
        $labels = $this->taskModel->getBoardLabels($boardId);

        // ボードのメンバー一覧を取得
        $members = $this->taskModel->getBoardMembers($boardId);

        $viewData = [
            'title' => 'カード編集',
            'card' => $card,
            'board' => $board,
            'lists' => $lists,
            'labels' => $labels,
            'members' => $members,
            'jsFiles' => ['task.js']
        ];

        $this->view('task/edit_card', $viewData);
    }

    /**
     * ユーザーのマイタスク一覧を表示
     */
    public function myTasks()
    {
        $userId = $this->auth->id();

        // フィルター条件
        $filters = [];
        if (isset($_GET['status'])) $filters['status'] = $_GET['status'];
        if (isset($_GET['priority'])) $filters['priority'] = $_GET['priority'];
        if (isset($_GET['due_date'])) $filters['due_date'] = $_GET['due_date'];
        if (isset($_GET['board_id'])) $filters['board_id'] = $_GET['board_id'];

        // タスク一覧を取得
        $tasks = $this->taskModel->getUserTasks($userId, $filters);

        // ユーザーのタスクボード一覧を取得
        $boards = $this->taskModel->getUserBoards($userId);

        $viewData = [
            'title' => 'マイタスク',
            'tasks' => $tasks,
            'boards' => $boards,
            'filters' => $filters,
            'jsFiles' => ['task.js']
        ];

        $this->view('task/my_tasks', $viewData);
    }

    /**
     * チェックリスト項目の状態を更新
     */
    public function apiUpdateChecklistItem($params, $data)
    {
        $userId = $this->auth->id();
        $itemId = $params['id'] ?? null;

        if (!$itemId) {
            return ['error' => 'チェックリスト項目IDが指定されていません', 'code' => 400];
        }

        if (!isset($data['is_checked'])) {
            return ['error' => 'チェック状態が指定されていません', 'code' => 400];
        }

        // ユーザーがこの項目を編集する権限があるかチェック
        // （現在のシンプルな実装ではチェックなし）

        $result = $this->taskModel->updateChecklistItem($itemId, [
            'is_checked' => (bool)$data['is_checked']
        ]);

        if ($result) {
            return [
                'success' => true,
                'message' => 'チェックリスト項目を更新しました'
            ];
        } else {
            return [
                'error' => 'チェックリスト項目の更新に失敗しました',
                'code' => 500
            ];
        }
    }

    /**
     * API: チームを作成
     */
    public function apiCreateTeam($params, $data)
    {
        $userId = $this->auth->id();

        // バリデーション
        if (empty($data['name'])) {
            return ['error' => 'チーム名は必須です', 'code' => 400];
        }

        $teamData = [
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'members' => $data['members'] ?? [],
            'member_roles' => $data['member_roles'] ?? []
        ];

        $teamId = $this->teamModel->create($teamData, $userId);

        if ($teamId) {
            $team = $this->teamModel->getById($teamId);

            return [
                'success' => true,
                'data' => $team,
                'message' => 'チームを作成しました',
                'redirect' => BASE_PATH . '/task/team/' . $teamId
            ];
        } else {
            return [
                'error' => 'チームの作成に失敗しました',
                'code' => 500
            ];
        }
    }

    /**
     * API: チームを更新
     */
    public function apiUpdateTeam($params, $data)
    {
        $userId = $this->auth->id();
        $teamId = $params['id'] ?? null;

        if (!$teamId) {
            return ['error' => 'チームIDが指定されていません', 'code' => 400];
        }

        // バリデーション
        if (empty($data['name'])) {
            return ['error' => 'チーム名は必須です', 'code' => 400];
        }

        // ユーザーがチームの管理者かチェック
        if (!$this->teamModel->isUserTeamAdmin($teamId, $userId) && !$this->auth->isAdmin()) {
            return ['error' => '権限がありません', 'code' => 403];
        }

        $teamData = [
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'members' => $data['members'] ?? [],
            'member_roles' => $data['member_roles'] ?? []
        ];

        $result = $this->teamModel->update($teamId, $teamData);

        if ($result) {
            $team = $this->teamModel->getById($teamId);

            return [
                'success' => true,
                'data' => $team,
                'message' => 'チームを更新しました',
                'redirect' => BASE_PATH . '/task/team/' . $teamId
            ];
        } else {
            return [
                'error' => 'チームの更新に失敗しました',
                'code' => 500
            ];
        }
    }

    /**
     * API: チームを削除
     */
    public function apiDeleteTeam($params)
    {
        $userId = $this->auth->id();
        $teamId = $params['id'] ?? null;

        if (!$teamId) {
            return ['error' => 'チームIDが指定されていません', 'code' => 400];
        }

        // ユーザーがチームの管理者かチェック
        if (!$this->teamModel->isUserTeamAdmin($teamId, $userId) && !$this->auth->isAdmin()) {
            return ['error' => '権限がありません', 'code' => 403];
        }

        $result = $this->teamModel->delete($teamId);

        if ($result) {
            return [
                'success' => true,
                'message' => 'チームを削除しました',
                'redirect' => BASE_PATH . '/task/teams'
            ];
        } else {
            return [
                'error' => 'チームの削除に失敗しました',
                'code' => 500
            ];
        }
    }

    /**
     * API: タスクボードを作成
     */
    public function apiCreateBoard($params, $data)
    {
        $userId = $this->auth->id();

        // バリデーション
        if (empty($data['name'])) {
            return ['error' => 'ボード名は必須です', 'code' => 400];
        }

        if (empty($data['owner_type']) || empty($data['owner_id'])) {
            return ['error' => '所有者タイプと所有者IDは必須です', 'code' => 400];
        }

        $boardData = [
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'owner_type' => $data['owner_type'],
            'owner_id' => $data['owner_id'],
            'is_public' => isset($data['is_public']) ? (bool)$data['is_public'] : false,
            'background_color' => $data['background_color'] ?? '#f0f2f5',
            'created_by' => $userId
        ];

        // 所有者タイプに応じた権限チェック
        if ($data['owner_type'] == 'team') {
            if (!$this->teamModel->isUserTeamMember($data['owner_id'], $userId)) {
                return ['error' => 'このチームにボードを作成する権限がありません', 'code' => 403];
            }
        } else if ($data['owner_type'] == 'organization') {
            $userOrgs = $this->userModel->getUserOrganizationIds($userId);
            if (!in_array($data['owner_id'], $userOrgs)) {
                return ['error' => 'この組織にボードを作成する権限がありません', 'code' => 403];
            }
        }

        $boardId = $this->taskModel->createBoard($boardData);

        if ($boardId) {
            return [
                'success' => true,
                'data' => ['id' => $boardId],
                'message' => 'タスクボードを作成しました',
                'redirect' => BASE_PATH . '/task/board/' . $boardId
            ];
        } else {
            return [
                'error' => 'タスクボードの作成に失敗しました',
                'code' => 500
            ];
        }
    }

    /**
     * API: タスクボードを更新
     */
    public function apiUpdateBoard($params, $data)
    {
        $userId = $this->auth->id();
        $boardId = $params['id'] ?? null;

        if (!$boardId) {
            return ['error' => 'ボードIDが指定されていません', 'code' => 400];
        }

        // バリデーション
        if (empty($data['name'])) {
            return ['error' => 'ボード名は必須です', 'code' => 400];
        }

        // ボード情報を取得
        $board = $this->taskModel->getBoard($boardId);
        if (!$board) {
            return ['error' => 'ボードが見つかりません', 'code' => 404];
        }

        // ユーザーがボードを編集可能かチェック
        if (!$this->taskModel->isUserBoardAdmin($boardId, $userId) && !$this->auth->isAdmin()) {
            return ['error' => '権限がありません', 'code' => 403];
        }

        $boardData = [
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'is_public' => isset($data['is_public']) ? (bool)$data['is_public'] : false,
            'background_color' => $data['background_color'] ?? '#f0f2f5'
        ];

        $result = $this->taskModel->updateBoard($boardId, $boardData);

        if ($result) {
            $updatedBoard = $this->taskModel->getBoard($boardId);

            return [
                'success' => true,
                'data' => $updatedBoard,
                'message' => 'タスクボードを更新しました',
                'redirect' => BASE_PATH . '/task/board/' . $boardId
            ];
        } else {
            return [
                'error' => 'タスクボードの更新に失敗しました',
                'code' => 500
            ];
        }
    }

    /**
     * API: タスクボードを削除
     */
    public function apiDeleteBoard($params)
    {
        $userId = $this->auth->id();
        $boardId = $params['id'] ?? null;

        if (!$boardId) {
            return ['error' => 'ボードIDが指定されていません', 'code' => 400];
        }

        // ボード情報を取得
        $board = $this->taskModel->getBoard($boardId);
        if (!$board) {
            return ['error' => 'ボードが見つかりません', 'code' => 404];
        }

        // ユーザーがボードを編集可能かチェック
        if (!$this->taskModel->isUserBoardAdmin($boardId, $userId) && !$this->auth->isAdmin()) {
            return ['error' => '権限がありません', 'code' => 403];
        }

        $result = $this->taskModel->deleteBoard($boardId);

        if ($result) {
            return [
                'success' => true,
                'message' => 'タスクボードを削除しました',
                'redirect' => BASE_PATH . '/task'
            ];
        } else {
            return [
                'error' => 'タスクボードの削除に失敗しました',
                'code' => 500
            ];
        }
    }

    /**
     * API: リストの順序を更新
     */
    public function apiUpdateListOrder($params, $data)
    {
        $userId = $this->auth->id();
        $listId = $params['id'] ?? null;

        if (!$listId || !isset($data['position'])) {
            return ['error' => 'リストIDまたは新しい位置が指定されていません', 'code' => 400];
        }

        // リスト情報を取得
        $list = $this->taskModel->getList($listId);
        if (!$list) {
            return ['error' => 'リストが見つかりません', 'code' => 404];
        }

        // ボードIDを取得
        $boardId = $list['board_id'];

        // ユーザーがボードを編集可能かチェック
        if (!$this->taskModel->isUserBoardAdmin($boardId, $userId) && !$this->auth->isAdmin()) {
            return ['error' => '権限がありません', 'code' => 403];
        }

        $result = $this->taskModel->updateListOrder($listId, $data['position']);

        if ($result) {
            return [
                'success' => true,
                'message' => 'リストの順序を更新しました'
            ];
        } else {
            return [
                'error' => 'リストの順序更新に失敗しました',
                'code' => 500
            ];
        }
    }

    /**
     * API: カードを作成
     */
    public function apiCreateCard($params, $data)
    {
        $userId = $this->auth->id();
        $listId = $data['list_id'] ?? null;

        if (!$listId) {
            return ['error' => 'リストIDが指定されていません', 'code' => 400];
        }

        // バリデーション
        if (empty($data['title'])) {
            return ['error' => 'カードタイトルは必須です', 'code' => 400];
        }

        // リスト情報を取得
        $list = $this->taskModel->getList($listId);
        if (!$list) {
            return ['error' => 'リストが見つかりません', 'code' => 404];
        }

        // ボードIDを取得
        $boardId = $list['board_id'];

        // ユーザーがボードにアクセス可能かチェック
        if (!$this->taskModel->canUserAccessBoard($boardId, $userId)) {
            return ['error' => '権限がありません', 'code' => 403];
        }

        $cardId = $this->taskModel->createCard($listId, $data, $userId);

        if ($cardId) {
            $card = $this->taskModel->getCardWithRelations($cardId);

            return [
                'success' => true,
                'data' => $card,
                'message' => 'カードを作成しました'
            ];
        } else {
            return [
                'error' => 'カードの作成に失敗しました',
                'code' => 500
            ];
        }
    }

    /**
     * API: カードを更新
     */
    public function apiUpdateCard($params, $data)
    {
        $userId = $this->auth->id();
        $cardId = $params['id'] ?? null;

        if (!$cardId) {
            return ['error' => 'カードIDが指定されていません', 'code' => 400];
        }

        // バリデーション
        if (empty($data['title'])) {
            return ['error' => 'カードタイトルは必須です', 'code' => 400];
        }

        // カード情報を取得
        $card = $this->taskModel->getCard($cardId);
        if (!$card) {
            return ['error' => 'カードが見つかりません', 'code' => 404];
        }

        // ユーザーがカードを編集可能かチェック
        if (!$this->taskModel->canUserEditCard($cardId, $userId)) {
            return ['error' => '権限がありません', 'code' => 403];
        }

        $result = $this->taskModel->updateCard($cardId, $data, $userId);

        if ($result) {
            $updatedCard = $this->taskModel->getCardWithRelations($cardId);

            return [
                'success' => true,
                'data' => $updatedCard,
                'message' => 'カードを更新しました'
            ];
        } else {
            return [
                'error' => 'カードの更新に失敗しました',
                'code' => 500
            ];
        }
    }

    /**
     * API: カードを削除
     */
    public function apiDeleteCard($params)
    {
        $userId = $this->auth->id();
        $cardId = $params['id'] ?? null;

        if (!$cardId) {
            return ['error' => 'カードIDが指定されていません', 'code' => 400];
        }

        // カード情報を取得
        $card = $this->taskModel->getCard($cardId);
        if (!$card) {
            return ['error' => 'カードが見つかりません', 'code' => 404];
        }

        // ユーザーがカードを編集可能かチェック
        if (!$this->taskModel->canUserEditCard($cardId, $userId)) {
            return ['error' => '権限がありません', 'code' => 403];
        }

        $result = $this->taskModel->deleteCard($cardId, $userId);

        if ($result) {
            return [
                'success' => true,
                'message' => 'カードを削除しました'
            ];
        } else {
            return [
                'error' => 'カードの削除に失敗しました',
                'code' => 500
            ];
        }
    }

    /**
     * API: カードの順序とリストを更新
     */
    public function apiUpdateCardOrder($params, $data)
    {
        $userId = $this->auth->id();
        $cardId = $params['id'] ?? null;

        if (!$cardId || !isset($data['list_id']) || !isset($data['position'])) {
            return ['error' => 'カードID、リストID、または新しい位置が指定されていません', 'code' => 400];
        }

        // カード情報を取得
        $card = $this->taskModel->getCard($cardId);
        if (!$card) {
            return ['error' => 'カードが見つかりません', 'code' => 404];
        }

        // リスト情報を取得
        $list = $this->taskModel->getList($data['list_id']);
        if (!$list) {
            return ['error' => 'リストが見つかりません', 'code' => 404];
        }

        // ユーザーがボードにアクセス可能かチェック
        $boardId = $list['board_id'];
        if (!$this->taskModel->canUserAccessBoard($boardId, $userId)) {
            return ['error' => '権限がありません', 'code' => 403];
        }

        $result = $this->taskModel->updateCardOrder($cardId, $data['list_id'], $data['position'], $userId);

        if ($result) {
            return [
                'success' => true,
                'message' => 'カードを移動しました'
            ];
        } else {
            return [
                'error' => 'カードの移動に失敗しました',
                'code' => 500
            ];
        }
    }

    /**
     * API: ラベルを作成
     */
    public function apiCreateLabel($params, $data)
    {
        $userId = $this->auth->id();
        $boardId = $data['board_id'] ?? null;

        if (!$boardId) {
            return ['error' => 'ボードIDが指定されていません', 'code' => 400];
        }

        // バリデーション
        if (empty($data['name'])) {
            return ['error' => 'ラベル名は必須です', 'code' => 400];
        }

        // ユーザーがボードを編集可能かチェック
        if (!$this->taskModel->isUserBoardAdmin($boardId, $userId) && !$this->auth->isAdmin()) {
            return ['error' => '権限がありません', 'code' => 403];
        }

        $labelData = [
            'name' => $data['name'],
            'color' => $data['color'] ?? '#cccccc'
        ];

        $labelId = $this->taskModel->createLabel($boardId, $labelData);

        if ($labelId) {
            $label = $this->taskModel->getLabel($labelId);

            return [
                'success' => true,
                'data' => $label,
                'message' => 'ラベルを作成しました'
            ];
        } else {
            return [
                'error' => 'ラベルの作成に失敗しました',
                'code' => 500
            ];
        }
    }

    /**
     * API: ラベルを更新
     */
    public function apiUpdateLabel($params, $data)
    {
        $userId = $this->auth->id();
        $labelId = $params['id'] ?? null;

        if (!$labelId) {
            return ['error' => 'ラベルIDが指定されていません', 'code' => 400];
        }

        // バリデーション
        if (empty($data['name'])) {
            return ['error' => 'ラベル名は必須です', 'code' => 400];
        }

        // ラベル情報を取得
        $label = $this->taskModel->getLabel($labelId);
        if (!$label) {
            return ['error' => 'ラベルが見つかりません', 'code' => 404];
        }

        // ボードIDを取得
        $boardId = $label['board_id'];

        // ユーザーがボードを編集可能かチェック
        if (!$this->taskModel->isUserBoardAdmin($boardId, $userId) && !$this->auth->isAdmin()) {
            return ['error' => '権限がありません', 'code' => 403];
        }

        $labelData = [
            'name' => $data['name'],
            'color' => $data['color'] ?? '#cccccc'
        ];

        $result = $this->taskModel->updateLabel($labelId, $labelData);

        if ($result) {
            $updatedLabel = $this->taskModel->getLabel($labelId);

            return [
                'success' => true,
                'data' => $updatedLabel,
                'message' => 'ラベルを更新しました'
            ];
        } else {
            return [
                'error' => 'ラベルの更新に失敗しました',
                'code' => 500
            ];
        }
    }

    /**
     * API: ラベルを削除
     */
    public function apiDeleteLabel($params)
    {
        $userId = $this->auth->id();
        $labelId = $params['id'] ?? null;

        if (!$labelId) {
            return ['error' => 'ラベルIDが指定されていません', 'code' => 400];
        }

        // ラベル情報を取得
        $label = $this->taskModel->getLabel($labelId);
        if (!$label) {
            return ['error' => 'ラベルが見つかりません', 'code' => 404];
        }

        // ボードIDを取得
        $boardId = $label['board_id'];

        // ユーザーがボードを編集可能かチェック
        if (!$this->taskModel->isUserBoardAdmin($boardId, $userId) && !$this->auth->isAdmin()) {
            return ['error' => '権限がありません', 'code' => 403];
        }

        $result = $this->taskModel->deleteLabel($labelId);

        if ($result) {
            return [
                'success' => true,
                'message' => 'ラベルを削除しました'
            ];
        } else {
            return [
                'error' => 'ラベルの削除に失敗しました',
                'code' => 500
            ];
        }
    }

    /**
     * API: コメントを追加
     */
    public function apiAddComment($params, $data)
    {
        $userId = $this->auth->id();
        $cardId = $params['id'] ?? null;

        if (!$cardId) {
            return ['error' => 'カードIDが指定されていません', 'code' => 400];
        }

        // バリデーション
        if (empty($data['comment'])) {
            return ['error' => 'コメント内容は必須です', 'code' => 400];
        }

        // カード情報を取得
        $card = $this->taskModel->getCard($cardId);
        if (!$card) {
            return ['error' => 'カードが見つかりません', 'code' => 404];
        }

        // ユーザーがボードにアクセス可能かチェック
        $listId = $card['list_id'];
        $list = $this->taskModel->getList($listId);
        $boardId = $list['board_id'];

        if (!$this->taskModel->canUserAccessBoard($boardId, $userId)) {
            return ['error' => '権限がありません', 'code' => 403];
        }

        $commentId = $this->taskModel->addComment($cardId, $userId, $data['comment']);

        if ($commentId) {
            $comment = $this->taskModel->getComment($commentId);

            return [
                'success' => true,
                'data' => $comment,
                'message' => 'コメントを追加しました'
            ];
        } else {
            return [
                'error' => 'コメントの追加に失敗しました',
                'code' => 500
            ];
        }
    }

    /**
     * API: コメントを更新
     */
    public function apiUpdateComment($params, $data)
    {
        $userId = $this->auth->id();
        $commentId = $params['id'] ?? null;

        if (!$commentId) {
            return ['error' => 'コメントIDが指定されていません', 'code' => 400];
        }

        // バリデーション
        if (empty($data['comment'])) {
            return ['error' => 'コメント内容は必須です', 'code' => 400];
        }

        // コメント情報を取得
        $comment = $this->taskModel->getComment($commentId);
        if (!$comment) {
            return ['error' => 'コメントが見つかりません', 'code' => 404];
        }

        // 所有者チェック（自分のコメントのみ編集可能）
        if ($comment['user_id'] != $userId) {
            return ['error' => '他のユーザーのコメントは編集できません', 'code' => 403];
        }

        $result = $this->taskModel->updateComment($commentId, $data['comment'], $userId);

        if ($result) {
            $updatedComment = $this->taskModel->getComment($commentId);

            return [
                'success' => true,
                'data' => $updatedComment,
                'message' => 'コメントを更新しました'
            ];
        } else {
            return [
                'error' => 'コメントの更新に失敗しました',
                'code' => 500
            ];
        }
    }

    /**
     * API: コメントを削除
     */
    public function apiDeleteComment($params)
    {
        $userId = $this->auth->id();
        $commentId = $params['id'] ?? null;

        if (!$commentId) {
            return ['error' => 'コメントIDが指定されていません', 'code' => 400];
        }

        // コメント情報を取得
        $comment = $this->taskModel->getComment($commentId);
        if (!$comment) {
            return ['error' => 'コメントが見つかりません', 'code' => 404];
        }

        $result = $this->taskModel->deleteComment($commentId, $userId);

        if ($result) {
            return [
                'success' => true,
                'message' => 'コメントを削除しました'
            ];
        } else {
            return [
                'error' => 'コメントの削除に失敗しました',
                'code' => 500
            ];
        }
    }

    /**
     * API: ボードメンバーを追加
     */
    public function apiAddBoardMember($params, $data)
    {
        $userId = $this->auth->id();
        $boardId = $params['id'] ?? null;

        if (!$boardId) {
            return ['error' => 'ボードIDが指定されていません', 'code' => 400];
        }

        // バリデーション
        if (empty($data['user_id'])) {
            return ['error' => 'ユーザーIDは必須です', 'code' => 400];
        }

        // ボード情報を取得
        $board = $this->taskModel->getBoard($boardId);
        if (!$board) {
            return ['error' => 'ボードが見つかりません', 'code' => 404];
        }

        // ユーザーがボードを編集可能かチェック
        if (!$this->taskModel->isUserBoardAdmin($boardId, $userId) && !$this->auth->isAdmin()) {
            return ['error' => '権限がありません', 'code' => 403];
        }

        $role = $data['role'] ?? 'viewer';
        $result = $this->taskModel->addBoardMember($boardId, $data['user_id'], $role);

        if ($result) {
            // 追加したメンバー情報を取得
            $member = $this->userModel->getById($data['user_id']);

            return [
                'success' => true,
                'data' => [
                    'board_id' => $boardId,
                    'user_id' => $data['user_id'],
                    'role' => $role,
                    'display_name' => $member['display_name'] ?? ''
                ],
                'message' => 'メンバーを追加しました'
            ];
        } else {
            return [
                'error' => 'メンバーの追加に失敗しました',
                'code' => 500
            ];
        }
    }

    /**
     * API: ボードメンバーを削除
     */
    public function apiRemoveBoardMember($params, $data)
    {
        $userId = $this->auth->id();
        $boardId = $params['id'] ?? null;

        if (!$boardId || empty($data['user_id'])) {
            return ['error' => 'ボードIDまたはユーザーIDが指定されていません', 'code' => 400];
        }

        // ボード情報を取得
        $board = $this->taskModel->getBoard($boardId);
        if (!$board) {
            return ['error' => 'ボードが見つかりません', 'code' => 404];
        }

        // ユーザーがボードを編集可能かチェック
        if (!$this->taskModel->isUserBoardAdmin($boardId, $userId) && !$this->auth->isAdmin()) {
            return ['error' => '権限がありません', 'code' => 403];
        }

        // 自分自身を削除しようとしている場合はエラー
        if ($data['user_id'] == $userId) {
            return ['error' => '自分自身をボードから削除することはできません', 'code' => 400];
        }

        $result = $this->taskModel->removeBoardMember($boardId, $data['user_id']);

        if ($result) {
            return [
                'success' => true,
                'message' => 'メンバーを削除しました'
            ];
        } else {
            return [
                'error' => 'メンバーの削除に失敗しました',
                'code' => 500
            ];
        }
    }

    /**
     * API: ボードメンバーの役割を更新
     */
    public function apiUpdateBoardMemberRole($params, $data)
    {
        $userId = $this->auth->id();
        $boardId = $params['id'] ?? null;

        if (!$boardId || empty($data['user_id']) || empty($data['role'])) {
            return ['error' => '必要なパラメータが指定されていません', 'code' => 400];
        }

        // ボード情報を取得
        $board = $this->taskModel->getBoard($boardId);
        if (!$board) {
            return ['error' => 'ボードが見つかりません', 'code' => 404];
        }

        // ユーザーがボードを編集可能かチェック
        if (!$this->taskModel->isUserBoardAdmin($boardId, $userId) && !$this->auth->isAdmin()) {
            return ['error' => '権限がありません', 'code' => 403];
        }

        // 役割のバリデーション
        $validRoles = ['admin', 'editor', 'viewer'];
        if (!in_array($data['role'], $validRoles)) {
            return ['error' => '無効な役割です', 'code' => 400];
        }

        $result = $this->taskModel->addBoardMember($boardId, $data['user_id'], $data['role']);

        if ($result) {
            return [
                'success' => true,
                'message' => 'メンバーの役割を更新しました'
            ];
        } else {
            return [
                'error' => 'メンバーの役割更新に失敗しました',
                'code' => 500
            ];
        }
    }

    /**
     * API: チェックリストを作成
     */
    public function apiCreateChecklist($params, $data)
    {
        $userId = $this->auth->id();
        $cardId = $params['id'] ?? null;

        if (!$cardId) {
            return ['error' => 'カードIDが指定されていません', 'code' => 400];
        }

        // バリデーション
        if (empty($data['title'])) {
            return ['error' => 'チェックリスト名は必須です', 'code' => 400];
        }

        // カード情報を取得
        $card = $this->taskModel->getCard($cardId);
        if (!$card) {
            return ['error' => 'カードが見つかりません', 'code' => 404];
        }

        // ユーザーがカードを編集可能かチェック
        if (!$this->taskModel->canUserEditCard($cardId, $userId)) {
            return ['error' => '権限がありません', 'code' => 403];
        }

        $checklistId = $this->taskModel->createChecklist($cardId, $data['title']);

        if ($checklistId) {
            $checklist = $this->taskModel->getChecklist($checklistId);

            return [
                'success' => true,
                'data' => $checklist,
                'message' => 'チェックリストを作成しました'
            ];
        } else {
            return [
                'error' => 'チェックリストの作成に失敗しました',
                'code' => 500
            ];
        }
    }

    /**
     * API: チェックリスト項目を追加
     */
    public function apiAddChecklistItem($params, $data)
    {
        $userId = $this->auth->id();
        $checklistId = $params['id'] ?? null;

        if (!$checklistId) {
            return ['error' => 'チェックリストIDが指定されていません', 'code' => 400];
        }

        // バリデーション
        if (empty($data['content'])) {
            return ['error' => '項目内容は必須です', 'code' => 400];
        }

        // チェックリスト情報を取得
        $checklist = $this->taskModel->getChecklist($checklistId);
        if (!$checklist) {
            return ['error' => 'チェックリストが見つかりません', 'code' => 404];
        }

        // カード情報を取得
        $card = $this->taskModel->getCard($checklist['card_id']);
        if (!$card) {
            return ['error' => 'カードが見つかりません', 'code' => 404];
        }

        // ユーザーがカードを編集可能かチェック
        if (!$this->taskModel->canUserEditCard($card['id'], $userId)) {
            return ['error' => '権限がありません', 'code' => 403];
        }

        $itemId = $this->taskModel->addChecklistItem($checklistId, $data['content']);

        if ($itemId) {
            // 更新後のチェックリストを取得
            $updatedChecklist = $this->taskModel->getChecklist($checklistId);

            return [
                'success' => true,
                'data' => $updatedChecklist,
                'message' => 'チェックリスト項目を追加しました'
            ];
        } else {
            return [
                'error' => 'チェックリスト項目の追加に失敗しました',
                'code' => 500
            ];
        }
    }

    /**
     * API: ボード情報を取得
     */
    public function apiGetBoard($params)
    {
        $userId = $this->auth->id();
        $boardId = $params['id'] ?? null;

        if (!$boardId) {
            return ['error' => 'ボードIDが指定されていません', 'code' => 400];
        }

        // ボード情報を取得
        $board = $this->taskModel->getBoard($boardId);
        if (!$board) {
            return ['error' => 'ボードが見つかりません', 'code' => 404];
        }

        // ユーザーがボードにアクセス可能かチェック
        if (!$this->taskModel->canUserAccessBoard($boardId, $userId)) {
            return ['error' => '権限がありません', 'code' => 403];
        }

        // ボードのリスト一覧を取得
        $lists = [];
        $boardLists = $this->taskModel->getBoardLists($boardId);

        foreach ($boardLists as $list) {
            $list['cards'] = $this->taskModel->getListCards($list['id']);
            $lists[] = $list;
        }

        // ボードのラベル一覧を取得
        $labels = $this->taskModel->getBoardLabels($boardId);

        // ボードのメンバー一覧を取得
        $members = $this->taskModel->getBoardMembers($boardId);

        // タスク概要統計情報を取得
        $summary = $this->taskModel->getBoardTasksSummary($boardId);

        // ユーザーがボードを編集可能かチェック
        $canEdit = $this->taskModel->isUserBoardAdmin($boardId, $userId);

        // 管理者権限があれば編集可能
        if ($this->auth->isAdmin()) {
            $canEdit = true;
        }

        return [
            'success' => true,
            'data' => [
                'board' => $board,
                'lists' => $lists,
                'labels' => $labels,
                'members' => $members,
                'summary' => $summary,
                'can_edit' => $canEdit
            ]
        ];
    }

    /**
     * API: ボードのリスト一覧を取得
     */
    public function apiGetBoardLists($params)
    {
        $userId = $this->auth->id();
        $boardId = $params['id'] ?? null;

        if (!$boardId) {
            return ['error' => 'ボードIDが指定されていません', 'code' => 400];
        }

        // ボード情報を取得
        $board = $this->taskModel->getBoard($boardId);
        if (!$board) {
            return ['error' => 'ボードが見つかりません', 'code' => 404];
        }

        // ユーザーがボードにアクセス可能かチェック
        if (!$this->taskModel->canUserAccessBoard($boardId, $userId)) {
            return ['error' => '権限がありません', 'code' => 403];
        }

        $lists = $this->taskModel->getBoardLists($boardId);

        return [
            'success' => true,
            'data' => $lists
        ];
    }

    /**
     * API: リストのカード一覧を取得
     */
    public function apiGetListCards($params)
    {
        $userId = $this->auth->id();
        $listId = $params['id'] ?? null;

        if (!$listId) {
            return ['error' => 'リストIDが指定されていません', 'code' => 400];
        }

        // リスト情報を取得
        $list = $this->taskModel->getList($listId);
        if (!$list) {
            return ['error' => 'リストが見つかりません', 'code' => 404];
        }

        // ボードIDを取得
        $boardId = $list['board_id'];

        // ユーザーがボードにアクセス可能かチェック
        if (!$this->taskModel->canUserAccessBoard($boardId, $userId)) {
            return ['error' => '権限がありません', 'code' => 403];
        }

        $cards = $this->taskModel->getListCards($listId);

        return [
            'success' => true,
            'data' => $cards
        ];
    }

    /**
     * API: カード詳細を取得
     */
    public function apiGetCard($params)
    {
        $userId = $this->auth->id();
        $cardId = $params['id'] ?? null;

        if (!$cardId) {
            return ['error' => 'カードIDが指定されていません', 'code' => 400];
        }

        // カード情報を取得
        $card = $this->taskModel->getCardWithRelations($cardId);
        if (!$card) {
            return ['error' => 'カードが見つかりません', 'code' => 404];
        }

        // リストからボードIDを取得
        $list = $this->taskModel->getList($card['list_id']);
        if (!$list) {
            return ['error' => 'リストが見つかりません', 'code' => 404];
        }

        $boardId = $list['board_id'];

        // ユーザーがボードにアクセス可能かチェック
        if (!$this->taskModel->canUserAccessBoard($boardId, $userId)) {
            return ['error' => '権限がありません', 'code' => 403];
        }

        // カード情報に追加情報をセット
        $card['creator_name'] = '';
        if ($card['created_by']) {
            $creator = $this->userModel->getById($card['created_by']);
            if ($creator) {
                $card['creator_name'] = $creator['display_name'];
            }
        }

        return [
            'success' => true,
            'data' => $card
        ];
    }

    /**
     * API: ボードの概要情報を取得
     */
    public function apiGetBoardSummary($params)
    {
        $userId = $this->auth->id();
        $boardId = $params['id'] ?? null;

        if (!$boardId) {
            return ['error' => 'ボードIDが指定されていません', 'code' => 400];
        }

        // ボード情報を取得
        $board = $this->taskModel->getBoard($boardId);
        if (!$board) {
            return ['error' => 'ボードが見つかりません', 'code' => 404];
        }

        // ユーザーがボードにアクセス可能かチェック
        if (!$this->taskModel->canUserAccessBoard($boardId, $userId)) {
            return ['error' => 'このボードにアクセスする権限がありません', 'code' => 403];
        }

        // ボードの統計情報を取得
        $summary = $this->taskModel->getBoardTasksSummary($boardId);

        return [
            'success' => true,
            'data' => $summary
        ];
    }

    /**
     * API: リスト情報を取得
     */
    public function apiGetList($params)
    {
        $userId = $this->auth->id();
        $listId = $params['id'] ?? null;

        if (!$listId) {
            return ['error' => 'リストIDが指定されていません', 'code' => 400];
        }

        // リスト情報を取得
        $list = $this->taskModel->getList($listId);
        if (!$list) {
            return ['error' => 'リストが見つかりません', 'code' => 404];
        }

        // リストからボードIDを取得
        $boardId = $list['board_id'];

        // ユーザーがボードにアクセス可能かチェック
        if (!$this->taskModel->canUserAccessBoard($boardId, $userId)) {
            return ['error' => 'このリストにアクセスする権限がありません', 'code' => 403];
        }

        return [
            'success' => true,
            'data' => $list
        ];
    }

    /**
     * API: リストの内容を取得（カード一覧を含む）
     */
    public function apiGetListWithCards($params)
    {
        $userId = $this->auth->id();
        $listId = $params['id'] ?? null;

        if (!$listId) {
            return ['error' => 'リストIDが指定されていません', 'code' => 400];
        }

        // リスト情報を取得
        $list = $this->taskModel->getList($listId);
        if (!$list) {
            return ['error' => 'リストが見つかりません', 'code' => 404];
        }

        // リストからボードIDを取得
        $boardId = $list['board_id'];

        // ユーザーがボードにアクセス可能かチェック
        if (!$this->taskModel->canUserAccessBoard($boardId, $userId)) {
            return ['error' => 'このリストにアクセスする権限がありません', 'code' => 403];
        }

        // リストのカード一覧を取得
        $list['cards'] = $this->taskModel->getListCards($listId);

        return [
            'success' => true,
            'data' => $list
        ];
    }

    /**
     * API: リストを作成
     */
    public function apiCreateList($params, $data)
    {
        $userId = $this->auth->id();

        // バリデーション
        if (empty($data['board_id'])) {
            return ['error' => 'ボードIDは必須です', 'code' => 400];
        }

        if (empty($data['name'])) {
            return ['error' => 'リスト名は必須です', 'code' => 400];
        }

        $boardId = $data['board_id'];

        // ボード情報を取得
        $board = $this->taskModel->getBoard($boardId);
        if (!$board) {
            return ['error' => 'ボードが見つかりません', 'code' => 404];
        }

        // ユーザーがボードを編集可能かチェック
        if (!$this->taskModel->isUserBoardAdmin($boardId, $userId) && !$this->auth->isAdmin()) {
            return ['error' => 'このボードにリストを追加する権限がありません', 'code' => 403];
        }

        // リストを作成
        $listId = $this->taskModel->createList($boardId, $data);

        if ($listId) {
            // 作成したリストの情報を取得
            $list = $this->taskModel->getList($listId);

            // 空のカード配列を追加
            $list['cards'] = [];

            return [
                'success' => true,
                'message' => 'リストを作成しました',
                'data' => $list
            ];
        } else {
            return [
                'error' => 'リストの作成に失敗しました',
                'code' => 500
            ];
        }
    }

    /**
     * API: リストを更新
     */
    public function apiUpdateList($params, $data)
    {
        $userId = $this->auth->id();
        $listId = $params['id'] ?? null;

        if (!$listId) {
            return ['error' => 'リストIDが指定されていません', 'code' => 400];
        }

        // バリデーション
        if (empty($data['name'])) {
            return ['error' => 'リスト名は必須です', 'code' => 400];
        }

        // リスト情報を取得
        $list = $this->taskModel->getList($listId);
        if (!$list) {
            return ['error' => 'リストが見つかりません', 'code' => 404];
        }

        $boardId = $list['board_id'];

        // ユーザーがボードを編集可能かチェック
        if (!$this->taskModel->isUserBoardAdmin($boardId, $userId) && !$this->auth->isAdmin()) {
            return ['error' => 'このリストを編集する権限がありません', 'code' => 403];
        }

        // リストを更新
        $result = $this->taskModel->updateList($listId, $data);

        if ($result) {
            // 更新後のリスト情報を取得
            $updatedList = $this->taskModel->getList($listId);

            return [
                'success' => true,
                'message' => 'リストを更新しました',
                'data' => $updatedList
            ];
        } else {
            return [
                'error' => 'リストの更新に失敗しました',
                'code' => 500
            ];
        }
    }

    /**
     * API: リストを削除
     */
    public function apiDeleteList($params)
    {
        $userId = $this->auth->id();
        $listId = $params['id'] ?? null;

        if (!$listId) {
            return ['error' => 'リストIDが指定されていません', 'code' => 400];
        }

        // リスト情報を取得
        $list = $this->taskModel->getList($listId);
        if (!$list) {
            return ['error' => 'リストが見つかりません', 'code' => 404];
        }

        $boardId = $list['board_id'];

        // ユーザーがボードを編集可能かチェック
        if (!$this->taskModel->isUserBoardAdmin($boardId, $userId) && !$this->auth->isAdmin()) {
            return ['error' => 'このリストを削除する権限がありません', 'code' => 403];
        }

        // リストを削除
        $result = $this->taskModel->deleteList($listId);

        if ($result) {
            return [
                'success' => true,
                'message' => 'リストを削除しました'
            ];
        } else {
            return [
                'error' => 'リストの削除に失敗しました',
                'code' => 500
            ];
        }
    }
}

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
            $this->redirect(BASE_PATH . '/task/
            
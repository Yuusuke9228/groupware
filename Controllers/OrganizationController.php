<?php
// controllers/OrganizationController.php
namespace Controllers;

use Core\Controller;
use Core\Auth;
use Models\Organization;
use Models\User;

class OrganizationController extends Controller
{
    private $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = new Organization();

        // 認証チェック
        if (!$this->auth->check()) {
            $this->redirect(BASE_PATH . '/login');
        }
    }

    // 組織一覧ページを表示
    public function index()
    {
        // 組織ツリーを取得
        $organizations = $this->model->getAllAsTree();

        $viewData = [
            'title' => '組織管理',
            'organizations' => $organizations,
            'jsFiles' => ['organization.js']
        ];

        $this->view('organization/index', $viewData);
    }

    // 組織作成ページを表示
    public function create()
    {
        // 親組織の選択用に全組織を取得
        $organizations = $this->model->getAll();

        $viewData = [
            'title' => '新規組織作成',
            'organizations' => $organizations,
            'jsFiles' => ['organization.js']
        ];

        $this->view('organization/create', $viewData);
    }

    // 組織編集ページを表示
    public function edit($params)
    {
        $id = $params['id'] ?? null;
        if (!$id) {
            $this->redirect(BASE_PATH . '/organizations');
        }

        // 編集対象の組織を取得
        $organization = $this->model->getById($id);
        if (!$organization) {
            $this->redirect(BASE_PATH . '/organizations');
        }

        // 親組織の選択用に全組織を取得（自分自身と子孫は除外）
        $organizations = $this->model->getAll();
        $descendants = $this->model->getDescendants($id);
        $descendantIds = array_column($descendants, 'id');
        $descendantIds[] = $id; // 自分自身も除外

        $viewData = [
            'title' => '組織編集',
            'organization' => $organization,
            'organizations' => $organizations,
            'descendantIds' => $descendantIds,
            'jsFiles' => ['organization.js']
        ];

        $this->view('organization/edit', $viewData);
    }

    // 組織の詳細ページを表示
    public function viewDetails($params)
    {
        $id = $params['id'] ?? null;
        if (!$id) {
            $this->redirect(BASE_PATH . '/organizations');
        }

        // 組織情報を取得
        $organization = $this->model->getById($id);
        if (!$organization) {
            $this->redirect(BASE_PATH . '/organizations');
        }

        // 親組織情報を取得
        $parent = null;
        if ($organization['parent_id']) {
            $parent = $this->model->getById($organization['parent_id']);
        }

        // 子組織情報を取得
        $children = $this->model->getChildren($id);

        // 所属ユーザー情報を取得
        $userModel = new User();
        $users = $userModel->getUsersByOrganization($id);

        $viewData = [
            'title' => $organization['name'] . ' - 組織詳細',
            'organization' => $organization,
            'parent' => $parent,
            'children' => $children,
            'users' => $users,
            'jsFiles' => ['organization.js']
        ];

        $this->view('organization/view', $viewData);
    }
    

    // API: 全組織を取得
    public function apiGetAll()
    {
        // 認証チェック
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }

        $organizations = $this->model->getAll();
        return ['success' => true, 'data' => $organizations];
    }

    // API: 組織ツリーを取得
    public function apiGetTree()
    {
        // 認証チェック
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }

        $organizations = $this->model->getAllAsTree();
        return ['success' => true, 'data' => $organizations];
    }

    // API: 特定の組織を取得
    public function apiGetOne($params)
    {
        // 認証チェック
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }

        $id = $params['id'] ?? null;
        if (!$id) {
            return ['error' => 'Invalid ID', 'code' => 400];
        }

        $organization = $this->model->getById($id);
        if (!$organization) {
            return ['error' => 'Organization not found', 'code' => 404];
        }

        return ['success' => true, 'data' => $organization];
    }

    // API: 組織を作成
    public function apiCreate($params, $data)
    {
        // 認証チェック
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }

        // 権限チェック
        if (!$this->auth->isAdmin()) {
            return ['error' => 'Permission denied', 'code' => 403];
        }

        // バリデーション
        if (empty($data['name']) || empty($data['code'])) {
            return ['error' => 'Name and Code are required', 'code' => 400];
        }

        // コードの重複チェック
        if (!$this->model->isCodeUnique($data['code'])) {
            return ['error' => 'Code must be unique', 'code' => 400];
        }

        $id = $this->model->create($data);
        if (!$id) {
            return ['error' => 'Failed to create organization', 'code' => 500];
        }

        $organization = $this->model->getById($id);
        return ['success' => true, 'data' => $organization, 'message' => '組織を作成しました'];
    }

    // API: 組織を更新
    public function apiUpdate($params, $data)
    {
        // 認証チェック
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }

        // 権限チェック
        if (!$this->auth->isAdmin()) {
            return ['error' => 'Permission denied', 'code' => 403];
        }

        $id = $params['id'] ?? null;
        if (!$id) {
            return ['error' => 'Invalid ID', 'code' => 400];
        }

        // 組織の存在チェック
        $organization = $this->model->getById($id);
        if (!$organization) {
            return ['error' => 'Organization not found', 'code' => 404];
        }

        // バリデーション
        if (empty($data['name']) || empty($data['code'])) {
            return ['error' => 'Name and Code are required', 'code' => 400];
        }

        // コードの重複チェック（自分自身は除外）
        if (!$this->model->isCodeUnique($data['code'], $id)) {
            return ['error' => 'Code must be unique', 'code' => 400];
        }

        $success = $this->model->update($id, $data);
        if (!$success) {
            return ['error' => 'Failed to update organization', 'code' => 500];
        }

        $organization = $this->model->getById($id);
        return ['success' => true, 'data' => $organization, 'message' => '組織を更新しました'];
    }

    // API: 組織を削除
    public function apiDelete($params)
    {
        // 認証チェック
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }

        // 権限チェック
        if (!$this->auth->isAdmin()) {
            return ['error' => 'Permission denied', 'code' => 403];
        }

        $id = $params['id'] ?? null;
        if (!$id) {
            return ['error' => 'Invalid ID', 'code' => 400];
        }

        // 組織の存在チェック
        $organization = $this->model->getById($id);
        if (!$organization) {
            return ['error' => 'Organization not found', 'code' => 404];
        }

        $success = $this->model->delete($id);
        if (!$success) {
            return ['error' => 'Cannot delete organization with children or users', 'code' => 400];
        }

        return ['success' => true, 'message' => '組織を削除しました'];
    }

    // API: 組織の表示順を更新
    public function apiUpdateOrder($params, $data)
    {
        // 認証チェック
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }

        // 権限チェック
        if (!$this->auth->isAdmin()) {
            return ['error' => 'Permission denied', 'code' => 403];
        }

        $id = $params['id'] ?? null;
        if (!$id || !isset($data['newOrder'])) {
            return ['error' => 'Invalid parameters', 'code' => 400];
        }

        $success = $this->model->updateSortOrder($id, $data['newOrder']);
        if (!$success) {
            return ['error' => 'Failed to update order', 'code' => 500];
        }

        return ['success' => true, 'message' => '表示順を更新しました'];
    }

    // API: 組織のユーザー一覧を取得
    public function apiGetUsers($params)
    {
        // 認証チェック
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }

        $id = $params['id'] ?? null;
        if (!$id) {
            return ['error' => 'Invalid ID', 'code' => 400];
        }

        // 組織の存在チェック
        $organization = $this->model->getById($id);
        if (!$organization) {
            return ['error' => 'Organization not found', 'code' => 404];
        }

        // ユーザー一覧を取得
        $userModel = new User();
        $users = $userModel->getUsersByOrganization($id);

        return ['success' => true, 'data' => $users];
    }

    // API: コードの重複チェック
    public function apiCheckCodeUnique($params)
    {
        // 認証チェック
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }

        $code = $params['code'] ?? '';
        $id = $params['id'] ?? null;

        if (empty($code)) {
            return ['success' => false, 'data' => ['unique' => false]];
        }

        $isUnique = $this->model->isCodeUnique($code, $id);

        return ['success' => true, 'data' => ['unique' => $isUnique]];
    }
    
}

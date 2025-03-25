<?php
// controllers/UserController.php
namespace Controllers;

use Core\Controller;
use Core\Auth;
use Models\User;
use Models\Organization;

class UserController extends Controller
{
    private $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = new User();

        // 認証チェック（ログインページ以外で）
        if (!$this->auth->check()) {
            $this->redirect(BASE_PATH . '/login');
        }
    }

    // ユーザー一覧ページを表示
    public function index()
    {
        // ページネーション
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $page = max(1, $page);
        $limit = 20;

        // 検索条件
        $search = $_GET['search'] ?? null;

        // ユーザーリストを取得
        $users = $this->model->getAll($page, $limit, $search);
        $totalUsers = $this->model->getCount($search);
        $totalPages = ceil($totalUsers / $limit);

        $viewData = [
            'title' => 'ユーザー管理',
            'users' => $users,
            'totalUsers' => $totalUsers,
            'page' => $page,
            'totalPages' => $totalPages,
            'search' => $search,
            'jsFiles' => ['user.js']
        ];

        $this->view('user/index', $viewData);
    }

    // ユーザー作成ページを表示
    public function create()
    {
        // 権限チェック
        if (!$this->auth->isAdmin()) {
            $this->redirect(BASE_PATH . '/users');
        }

        // 組織リストを取得
        $orgModel = new Organization();
        $organizations = $orgModel->getAll();

        $viewData = [
            'title' => '新規ユーザー作成',
            'organizations' => $organizations,
            'jsFiles' => ['user.js']
        ];

        $this->view('user/create', $viewData);
    }

    public function edit($params)
    {
        $id = $params['id'] ?? null;
        if (!$id) {
            $this->redirect('/users');
        }

        // 権限チェック（管理者または自分自身の編集のみ許可）
        if (!$this->auth->isAdmin() && $this->auth->id() != $id) {
            $this->redirect('/users');
        }

        // ユーザー情報を取得
        $user = $this->model->getById($id);
        if (!$user) {
            $this->redirect('/users');
        }

        // ユーザーの所属組織を取得
        $userOrganizations = $this->model->getUserOrganizations($id);
        $userOrgIds = array_column($userOrganizations, 'id');

        // 主組織のID
        $primaryOrgId = null;
        foreach ($userOrganizations as $org) {
            if ($org['is_primary']) {
                $primaryOrgId = $org['id'];
                break;
            }
        }

        // 組織リストを取得
        $orgModel = new Organization();
        $organizations = $orgModel->getAll();

        // 追加の組織をカンマ区切りの文字列に変換
        $additionalOrganizations = array_filter($userOrgIds, function ($orgId) use ($primaryOrgId) {
            return $orgId != $primaryOrgId;
        });
        $additionalOrganizationsStr = implode(',', $additionalOrganizations);

        $viewData = [
            'title' => 'ユーザー編集',
            'user' => $user,
            'userOrganizations' => $userOrganizations,
            'userOrgIds' => $userOrgIds,
            'primaryOrgId' => $primaryOrgId,
            'additionalOrganizations' => $additionalOrganizationsStr,
            'organizations' => $organizations,
            'jsFiles' => ['user.js']
        ];

        $this->view('user/edit', $viewData);
    }

    // ユーザーの詳細ページを表示 (メソッド名を変更)
    public function viewDetails($params)
    {
        $id = $params['id'] ?? null;
        if (!$id) {
            $this->redirect(BASE_PATH . '/users');
        }

        // ユーザー情報を取得
        $user = $this->model->getById($id);
        if (!$user) {
            $this->redirect(BASE_PATH . '/users');
        }

        // ユーザーの所属組織を取得
        $userOrganizations = $this->model->getUserOrganizations($id);

        $viewData = [
            'title' => $user['display_name'] . ' - ユーザー詳細',
            'user' => $user,
            'userOrganizations' => $userOrganizations,
            'jsFiles' => ['user.js']
        ];

        $this->view('user/view', $viewData);
    }

    // パスワード変更ページを表示
    public function changePassword($params)
    {
        $id = $params['id'] ?? null;
        if (!$id) {
            $this->redirect(BASE_PATH . '/users');
        }

        // 権限チェック（管理者または自分自身のパスワード変更のみ許可）
        if (!$this->auth->isAdmin() && $this->auth->id() != $id) {
            $this->redirect(BASE_PATH . '/users');
        }

        // ユーザー情報を取得
        $user = $this->model->getById($id);
        if (!$user) {
            $this->redirect(BASE_PATH . '/users');
        }

        $viewData = [
            'title' => 'パスワード変更',
            'user' => $user,
            'jsFiles' => ['user.js']
        ];

        $this->view('user/change_password', $viewData);
    }

    // API: 全ユーザーを取得
    public function apiGetAll($params)
    {
        // 認証チェック
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }

        // ページネーション
        $page = $params['page'] ?? 1;
        $limit = $params['limit'] ?? 20;

        // 検索条件
        $search = $params['search'] ?? null;

        $users = $this->model->getAll($page, $limit, $search);
        $totalUsers = $this->model->getCount($search);
        $totalPages = ceil($totalUsers / $limit);

        return [
            'success' => true,
            'data' => [
                'users' => $users,
                'pagination' => [
                    'total' => $totalUsers,
                    'total_pages' => $totalPages,
                    'current_page' => $page,
                    'limit' => $limit
                ]
            ]
        ];
    }

    // API: 特定のユーザーを取得
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

        $user = $this->model->getById($id);
        if (!$user) {
            return ['error' => 'User not found', 'code' => 404];
        }

        // パスワードハッシュは返さない
        unset($user['password']);

        // ユーザーの所属組織を取得
        $userOrganizations = $this->model->getUserOrganizations($id);

        return [
            'success' => true,
            'data' => [
                'user' => $user,
                'organizations' => $userOrganizations
            ]
        ];
    }

    // API: ユーザーを作成
    public function apiCreate($params, $data)
    {
        // デバッグ用：受信データの内容を確認
        error_log('API Create User - Data received: ' . print_r($data, true));
        // 認証チェック
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }

        // 権限チェック
        if (!$this->auth->isAdmin()) {
            return ['error' => 'Permission denied', 'code' => 403];
        }

        // バリデーション
        if (
            empty($data['username']) || empty($data['password']) ||
            empty($data['email']) || empty($data['first_name']) ||
            empty($data['last_name'])
        ) {
            return ['error' => 'All required fields must be provided', 'code' => 400];
        }

        // ユーザー名とメールアドレスの重複チェック
        if ($this->model->getByUsername($data['username'])) {
            return ['error' => 'Username already exists', 'code' => 400];
        }

        if ($this->model->getByEmail($data['email'])) {
            return ['error' => 'Email already exists', 'code' => 400];
        }

        $id = $this->model->create($data);
        if (!$id) {
            return ['error' => 'Failed to create user', 'code' => 500];
        }

        $user = $this->model->getById($id);
        // パスワードハッシュは返さない
        unset($user['password']);

        return [
            'success' => true,
            'data' => $user,
            'message' => 'ユーザーを作成しました',
            'redirect' => BASE_PATH . '/users/view/' . $id
        ];
    }

    // API: ユーザーを更新
    public function apiUpdate($params, $data)
    {
        // 認証チェック
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }

        $id = $params['id'] ?? null;
        if (!$id) {
            return ['error' => 'Invalid ID', 'code' => 400];
        }

        // 権限チェック（管理者または自分自身の編集のみ許可）
        if (!$this->auth->isAdmin() && $this->auth->id() != $id) {
            return ['error' => 'Permission denied', 'code' => 403];
        }

        // ユーザーの存在チェック
        $user = $this->model->getById($id);
        if (!$user) {
            return ['error' => 'User not found', 'code' => 404];
        }

        // 一般ユーザーが編集できるフィールドを制限
        if (!$this->auth->isAdmin() && $this->auth->id() == $id) {
            // パスワード変更は専用APIで行う
            unset($data['password']);

            // 役割や状態は変更不可
            unset($data['role']);
            unset($data['status']);

            // ユーザー名も変更不可
            unset($data['username']);
        }

        // ユーザー名とメールアドレスの重複チェック
        if (
            isset($data['username']) && $data['username'] !== $user['username'] &&
            $this->model->getByUsername($data['username'])
        ) {
            return ['error' => 'Username already exists', 'code' => 400];
        }

        if (
            isset($data['email']) && $data['email'] !== $user['email'] &&
            $this->model->getByEmail($data['email'])
        ) {
            return ['error' => 'Email already exists', 'code' => 400];
        }

        // 組織データの処理
        if (isset($data['organization_id']) || isset($data['additional_organizations'])) {
            // 追加組織の処理
            $additionalOrganizations = [];
            if (isset($data['additional_organizations']) && !empty($data['additional_organizations'])) {
                if (is_string($data['additional_organizations'])) {
                    // カンマ区切りの文字列から配列を作成
                    $additionalOrganizations = array_filter(explode(',', $data['additional_organizations']));
                } elseif (is_array($data['additional_organizations'])) {
                    $additionalOrganizations = $data['additional_organizations'];
                }
            }

            // データの整形
            $data['additional_organizations'] = $additionalOrganizations;
        }

        $success = $this->model->update($id, $data);
        if (!$success) {
            return ['error' => 'Failed to update user', 'code' => 500];
        }

        $user = $this->model->getById($id);
        // パスワードハッシュは返さない
        unset($user['password']);

        return [
            'success' => true,
            'data' => $user,
            'message' => 'ユーザー情報を更新しました',
            'redirect' => BASE_PATH . '/users/view/' . $id
        ];
    }

    // API: ユーザーを削除
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

        // 自分自身は削除不可
        if ($this->auth->id() == $id) {
            return ['error' => 'Cannot delete yourself', 'code' => 400];
        }

        // ユーザーの存在チェック
        $user = $this->model->getById($id);
        if (!$user) {
            return ['error' => 'User not found', 'code' => 404];
        }

        $success = $this->model->delete($id);
        if (!$success) {
            return ['error' => 'Failed to delete user', 'code' => 500];
        }

        return [
            'success' => true,
            'message' => 'ユーザーを削除しました',
            'redirect' => BASE_PATH . '/users'
        ];
    }

    // API: パスワードを変更
    public function apiChangePassword($params, $data)
    {
        // 認証チェック
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }

        $id = $params['id'] ?? null;
        if (!$id) {
            return ['error' => 'Invalid ID', 'code' => 400];
        }

        // 権限チェック（管理者または自分自身のパスワード変更のみ許可）
        if (!$this->auth->isAdmin() && $this->auth->id() != $id) {
            return ['error' => 'Permission denied', 'code' => 403];
        }

        // ユーザーの存在チェック
        $user = $this->model->getById($id);
        if (!$user) {
            return ['error' => 'User not found', 'code' => 404];
        }

        // バリデーション
        if (empty($data['new_password'])) {
            return ['error' => 'New password is required', 'code' => 400];
        }

        // 自分自身のパスワード変更時は現在のパスワードが必要
        if ($this->auth->id() == $id && !$this->auth->isAdmin()) {
            if (empty($data['current_password'])) {
                return ['error' => 'Current password is required', 'code' => 400];
            }

            if (!password_verify($data['current_password'], $user['password'])) {
                return ['error' => 'Current password is incorrect', 'code' => 400];
            }
        }

        $success = $this->model->changePassword($id, $data['new_password']);
        if (!$success) {
            return ['error' => 'Failed to change password', 'code' => 500];
        }

        return [
            'success' => true,
            'message' => 'パスワードを変更しました',
            'redirect' => BASE_PATH . '/users/view/' . $id
        ];
    }

    // API: ユーザーの組織を取得
    public function apiGetUserOrganizations($params)
    {
        // 認証チェック
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }

        $id = $params['id'] ?? null;
        if (!$id) {
            return ['error' => 'Invalid ID', 'code' => 400];
        }

        // ユーザーの存在チェック
        $user = $this->model->getById($id);
        if (!$user) {
            return ['error' => 'User not found', 'code' => 404];
        }

        $organizations = $this->model->getUserOrganizations($id);

        return ['success' => true, 'data' => $organizations];
    }

    // API: 主組織を変更
    public function apiChangePrimaryOrganization($params, $data)
    {
        // 認証チェック
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }

        $id = $params['id'] ?? null;
        if (!$id || empty($data['organization_id'])) {
            return ['error' => 'Invalid parameters', 'code' => 400];
        }

        // 権限チェック（管理者または自分自身の主組織変更のみ許可）
        if (!$this->auth->isAdmin() && $this->auth->id() != $id) {
            return ['error' => 'Permission denied', 'code' => 403];
        }

        // ユーザーの存在チェック
        $user = $this->model->getById($id);
        if (!$user) {
            return ['error' => 'User not found', 'code' => 404];
        }

        // 組織の存在チェック
        $orgModel = new Organization();
        $organization = $orgModel->getById($data['organization_id']);
        if (!$organization) {
            return ['error' => 'Organization not found', 'code' => 404];
        }

        // ユーザーがこの組織に所属しているか確認
        $userOrganizations = $this->model->getUserOrganizationIds($id);
        if (!in_array($data['organization_id'], $userOrganizations)) {
            return ['error' => 'User is not a member of this organization', 'code' => 400];
        }

        $success = $this->model->changePrimaryOrganization($id, $data['organization_id']);
        if (!$success) {
            return ['error' => 'Failed to change primary organization', 'code' => 500];
        }

        return [
            'success' => true,
            'message' => '主組織を変更しました',
            'data' => [
                'user_id' => $id,
                'organization_id' => $data['organization_id'],
                'organization_name' => $organization['name']
            ]
        ];
    }

    // API: アクティブユーザー一覧を取得（選択リスト用）
    public function apiGetActiveUsers()
    {
        // 認証チェック
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }

        $users = $this->model->getActiveUsers();

        return ['success' => true, 'data' => $users];
    }
}

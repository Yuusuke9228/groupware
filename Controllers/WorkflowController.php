<?php
// controllers/WorkflowController.php
namespace Controllers;

use Core\Controller;
use Core\Database;
use Core\Auth;
use Models\Workflow;
use Models\User;
use Models\Organization;
use Models\Notification;

class WorkflowController extends Controller
{
    private $db;
    private $model;
    private $userModel;
    private $organizationModel;
    private $notification;

    public function __construct()
    {
        parent::__construct();
        $this->db = Database::getInstance();
        $this->model = new Workflow();
        $this->userModel = new User();
        $this->organizationModel = new Organization();
        $this->notification = new Notification();

        // 認証チェック
        if (!$this->auth->check()) {
            $this->redirect(BASE_PATH . '/login');
        }
    }

    /**
     * ワークフロー一覧ページを表示
     */
    public function index()
    {
        $viewData = [
            'title' => 'ワークフロー管理',
            'jsFiles' => ['workflow.js']
        ];

        $this->view('workflow/index', $viewData);
    }

    /**
     * ワークフローテンプレート一覧ページを表示
     */
    public function templates()
    {
        // ページネーション
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $page = max(1, $page);
        $limit = 20;

        // 検索条件
        $search = $_GET['search'] ?? null;

        // テンプレートリストを取得
        $templates = $this->model->getAllTemplates($page, $limit, $search);
        $totalTemplates = $this->model->getTemplateCount($search);
        $totalPages = ceil($totalTemplates / $limit);

        $viewData = [
            'title' => 'ワークフローテンプレート',
            'templates' => $templates,
            'totalTemplates' => $totalTemplates,
            'page' => $page,
            'totalPages' => $totalPages,
            'search' => $search,
            'jsFiles' => ['workflow.js']
        ];

        $this->view('workflow/templates', $viewData);
    }

    /**
     * テンプレート作成ページを表示
     */
    public function createTemplate()
    {
        // 権限チェック
        if (!$this->auth->isAdmin()) {
            $this->redirect(BASE_PATH . '/workflow/templates');
        }

        $viewData = [
            'title' => '新規テンプレート作成',
            'jsFiles' => ['workflow.js']
        ];

        $this->view('workflow/template_form', $viewData);
    }

    /**
     * テンプレート編集ページを表示
     */
    public function editTemplate($params)
    {
        // 権限チェック
        if (!$this->auth->isAdmin()) {
            $this->redirect(BASE_PATH . '/workflow/templates');
        }

        $id = $params['id'] ?? null;
        if (!$id) {
            $this->redirect(BASE_PATH . '/workflow/templates');
        }

        // テンプレート情報を取得
        $template = $this->model->getTemplateById($id);
        if (!$template) {
            $this->redirect(BASE_PATH . '/workflow/templates');
        }

        // フォーム定義を取得
        $formDefinitions = $this->model->getFormDefinitions($id);

        // 承認経路を取得
        $routeDefinitions = $this->model->getRouteDefinitions($id);

        $viewData = [
            'title' => 'テンプレート編集',
            'template' => $template,
            'formDefinitions' => $formDefinitions,
            'routeDefinitions' => $routeDefinitions,
            'jsFiles' => ['workflow.js']
        ];

        $this->view('workflow/template_form', $viewData);
    }

    /**
     * テンプレートのフォームデザイナーページを表示
     */
    public function designForm($params)
    {
        // 権限チェック
        if (!$this->auth->isAdmin()) {
            $this->redirect(BASE_PATH . '/workflow/templates');
        }

        $id = $params['id'] ?? null;
        if (!$id) {
            $this->redirect(BASE_PATH . '/workflow/templates');
        }

        // テンプレート情報を取得
        $template = $this->model->getTemplateById($id);
        if (!$template) {
            $this->redirect(BASE_PATH . '/workflow/templates');
        }

        // フォーム定義を取得
        $formDefinitions = $this->model->getFormDefinitions($id);

        $viewData = [
            'title' => 'フォームデザイン',
            'template' => $template,
            'formDefinitions' => $formDefinitions,
            'jsFiles' => ['workflow.js', 'workflow-form-designer.js']
        ];

        $this->view('workflow/form_designer', $viewData);
    }

    /**
     * テンプレートの承認経路設定ページを表示
     */
    public function designRoute($params)
    {
        // 権限チェック
        if (!$this->auth->isAdmin()) {
            $this->redirect(BASE_PATH . '/workflow/templates');
        }

        $id = $params['id'] ?? null;
        if (!$id) {
            $this->redirect(BASE_PATH . '/workflow/templates');
        }

        // テンプレート情報を取得
        $template = $this->model->getTemplateById($id);
        if (!$template) {
            $this->redirect(BASE_PATH . '/workflow/templates');
        }

        // 承認経路を取得
        $routeDefinitions = $this->model->getRouteDefinitions($id);

        // ユーザー一覧を取得
        $users = $this->userModel->getActiveUsers();

        // 組織一覧を取得
        $organizations = $this->organizationModel->getAll();

        $viewData = [
            'title' => '承認経路設定',
            'template' => $template,
            'routeDefinitions' => $routeDefinitions,
            'users' => $users,
            'organizations' => $organizations,
            'jsFiles' => ['workflow.js', 'workflow-route-designer.js']
        ];

        $this->view('workflow/route_designer', $viewData);
    }

    /**
     * 申請一覧ページを表示
     */
    public function requests()
    {
        // フィルタリング条件
        $filters = [
            'status' => $_GET['status'] ?? null,
            'template_id' => $_GET['template_id'] ?? null,
            'search' => $_GET['search'] ?? null,
        ];

        // 権限に基づいてフィルタリング
        $userId = $this->auth->id();
        $isAdmin = $this->auth->isAdmin();

        // 管理者以外は自分の申請のみ表示
        if (!$isAdmin) {
            $filters['requester_id'] = $userId;
        }

        // ページネーション
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $page = max(1, $page);
        $limit = 20;

        // 申請リストを取得
        $requests = $this->model->getRequests($filters, $page, $limit);
        $totalRequests = $this->model->getRequestCount($filters);
        $totalPages = ceil($totalRequests / $limit);

        // テンプレート一覧を取得（フィルター用）
        $templates = $this->model->getAllTemplates(1, 100);

        $viewData = [
            'title' => '申請一覧',
            'requests' => $requests,
            'totalRequests' => $totalRequests,
            'page' => $page,
            'totalPages' => $totalPages,
            'filters' => $filters,
            'templates' => $templates,
            'isAdmin' => $isAdmin,
            'jsFiles' => ['workflow.js']
        ];

        $this->view('workflow/requests', $viewData);
    }

    /**
     * 承認待ち一覧ページを表示
     */
    public function approvals()
    {
        $userId = $this->auth->id();

        // フィルタリング条件
        $filters = [
            'pending_approval' => true,
            'user_id' => $userId,
            'template_id' => $_GET['template_id'] ?? null,
            'search' => $_GET['search'] ?? null,
        ];

        // ページネーション
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $page = max(1, $page);
        $limit = 20;

        // 承認待ち申請リストを取得 (修正: ここでのクエリを確認)
        $requests = $this->model->getRequests($filters, $page, $limit);
        $totalRequests = $this->model->getRequestCount($filters);
        $totalPages = ceil($totalRequests / $limit);

        // テンプレート一覧を取得（フィルター用）
        $templates = $this->model->getAllTemplates(1, 100);

        $viewData = [
            'title' => '承認待ち一覧',
            'requests' => $requests,
            'totalRequests' => $totalRequests,
            'page' => $page,
            'totalPages' => $totalPages,
            'filters' => $filters,
            'templates' => $templates,
            'jsFiles' => ['workflow.js']
        ];

        $this->view('workflow/approvals', $viewData);
    }

    /**
     * 新規申請ページを表示
     */
    public function create($params)
    {
        $templateId = $params['id'] ?? null;
        if (!$templateId) {
            $this->redirect(BASE_PATH . '/workflow/templates');
        }

        // テンプレート情報を取得
        $template = $this->model->getTemplateById($templateId);
        if (!$template) {
            $this->redirect(BASE_PATH . '/workflow/templates');
        }

        // フォーム定義を取得
        $formDefinitions = $this->model->getFormDefinitions($templateId);

        $viewData = [
            'title' => '新規申請作成',
            'template' => $template,
            'formDefinitions' => $formDefinitions,
            'jsFiles' => ['workflow.js']
        ];

        $this->view('workflow/request_form', $viewData);
    }

    /**
     * 申請編集ページを表示
     */
    public function edit($params)
    {
        $id = $params['id'] ?? null;
        if (!$id) {
            $this->redirect(BASE_PATH . '/workflow/requests');
        }

        // 申請情報を取得
        $request = $this->model->getRequestById($id);
        if (!$request) {
            $this->redirect(BASE_PATH . '/workflow/requests');
        }

        // 権限チェック（申請者のみ編集可能、ただしドラフト状態の場合のみ）
        if ($request['requester_id'] != $this->auth->id() || $request['status'] !== 'draft') {
            $this->redirect(BASE_PATH . '/workflow/requests');
        }

        // テンプレート情報を取得
        $template = $this->model->getTemplateById($request['template_id']);

        // フォーム定義を取得
        $formDefinitions = $this->model->getFormDefinitions($request['template_id']);

        // フォームデータを取得
        $formData = $this->model->getRequestData($id);

        $viewData = [
            'title' => '申請編集',
            'request' => $request,
            'template' => $template,
            'formDefinitions' => $formDefinitions,
            'formData' => $formData,
            'jsFiles' => ['workflow.js']
        ];

        $this->view('workflow/request_form', $viewData);
    }

    /**
     * 申請詳細ページを表示
     */
    public function viewDetails($params)
    {
        $id = $params['id'] ?? null;
        if (!$id) {
            $this->redirect(BASE_PATH . '/workflow/requests');
        }

        // 申請情報を取得
        $request = $this->model->getRequestById($id);
        if (!$request) {
            $this->redirect(BASE_PATH . '/workflow/requests');
        }

        // 権限チェック（管理者、申請者、承認者のみ閲覧可能）
        $userId = $this->auth->id();
        $isAdmin = $this->auth->isAdmin();
        $isRequester = ($request['requester_id'] == $userId);

        // 承認者かどうかチェック - 修正: 全ステップの承認者を対象にする
        $sql = "SELECT COUNT(*) as count FROM workflow_approvals 
            WHERE request_id = ? AND approver_id = ?";
        $isApprover = $this->db->fetch($sql, [$id, $userId])['count'] > 0;

        if (!$isAdmin && !$isRequester && !$isApprover) {
            $this->redirect(BASE_PATH . '/workflow/requests');
        }

        // テンプレート情報を取得
        $template = $this->model->getTemplateById($request['template_id']);

        // フォーム定義を取得
        $formDefinitions = $this->model->getFormDefinitions($request['template_id']);

        // フォームデータを取得
        $formData = $this->model->getRequestData($id);

        // 添付ファイルを取得
        $attachments = $this->model->getRequestAttachments($id);

        // 承認履歴を取得
        $approvals = $this->model->getRequestApprovals($id);

        // 現在のユーザーの承認タスクを取得 - 修正: 現在のステップのみに限定
        $currentApproval = null;
        if ($request['status'] === 'pending') {  // pending 状態の場合のみ
            $sql = "SELECT * FROM workflow_approvals 
                WHERE request_id = ? AND approver_id = ? AND status = 'pending' 
                AND step_number = ? LIMIT 1";
            $currentApproval = $this->db->fetch($sql, [
                $id,
                $userId,
                $request['current_step']
            ]);
        }

        // コメントを取得
        $comments = $this->model->getComments($id);

        $viewData = [
            'title' => '申請詳細：' . $request['title'],
            'request' => $request,
            'template' => $template,
            'formDefinitions' => $formDefinitions,
            'formData' => $formData,
            'attachments' => $attachments,
            'approvals' => $approvals,
            'currentApproval' => $currentApproval,
            'comments' => $comments,
            'isAdmin' => $isAdmin,
            'isRequester' => $isRequester,
            'isApprover' => $isApprover,
            'jsFiles' => ['workflow.js']
        ];

        $this->view('workflow/request_view', $viewData);
    }

    /**
     * 代理承認設定ページを表示
     */
    public function delegates()
    {
        // ユーザーIDを取得
        $userId = $this->auth->id();

        // 代理承認設定を取得
        $delegations = $this->model->getUserDelegations($userId);

        // ユーザー一覧を取得
        $users = $this->userModel->getActiveUsers();

        // テンプレート一覧を取得
        $templates = $this->model->getAllTemplates(1, 100);

        $viewData = [
            'title' => '代理承認設定',
            'delegations' => $delegations,
            'users' => $users,
            'templates' => $templates,
            'jsFiles' => ['workflow.js']
        ];

        $this->view('workflow/delegates', $viewData);
    }

    /* API メソッド */

    /**
     * API: 全テンプレートを取得
     */
    public function apiGetAllTemplates($params)
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

        $templates = $this->model->getAllTemplates($page, $limit, $search);
        $totalTemplates = $this->model->getTemplateCount($search);
        $totalPages = ceil($totalTemplates / $limit);

        return [
            'success' => true,
            'data' => [
                'templates' => $templates,
                'pagination' => [
                    'total' => $totalTemplates,
                    'total_pages' => $totalPages,
                    'current_page' => $page,
                    'limit' => $limit
                ]
            ]
        ];
    }

    /**
     * API: 特定のテンプレートを取得
     */
    public function apiGetTemplate($params)
    {
        // 認証チェック
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }

        $id = $params['id'] ?? null;
        if (!$id) {
            return ['error' => 'Invalid ID', 'code' => 400];
        }

        $template = $this->model->getTemplateById($id);
        if (!$template) {
            return ['error' => 'Template not found', 'code' => 404];
        }

        // フォーム定義を取得
        $formDefinitions = $this->model->getFormDefinitions($id);

        // 承認経路を取得
        $routeDefinitions = $this->model->getRouteDefinitions($id);

        return [
            'success' => true,
            'data' => [
                'template' => $template,
                'form_definitions' => $formDefinitions,
                'route_definitions' => $routeDefinitions
            ]
        ];
    }

    /**
     * API: テンプレートを作成
     */
    public function apiCreateTemplate($params, $data)
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
        if (empty($data['name'])) {
            return ['error' => 'Template name is required', 'code' => 400];
        }

        // 作成者IDを追加
        $data['creator_id'] = $this->auth->id();

        $id = $this->model->createTemplate($data);
        if (!$id) {
            return ['error' => 'Failed to create template', 'code' => 500];
        }

        $template = $this->model->getTemplateById($id);

        return [
            'success' => true,
            'data' => $template,
            'message' => 'テンプレートを作成しました',
            'redirect' => BASE_PATH . '/workflow/templates'
        ];
    }

    /**
     * API: テンプレートを更新
     */
    public function apiUpdateTemplate($params, $data)
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

        // テンプレートの存在チェック
        $template = $this->model->getTemplateById($id);
        if (!$template) {
            return ['error' => 'Template not found', 'code' => 404];
        }

        // バリデーション
        if (empty($data['name'])) {
            return ['error' => 'Template name is required', 'code' => 400];
        }

        $success = $this->model->updateTemplate($id, $data);
        if (!$success) {
            return ['error' => 'Failed to update template', 'code' => 500];
        }

        $template = $this->model->getTemplateById($id);

        return [
            'success' => true,
            'data' => $template,
            'message' => 'テンプレートを更新しました',
            'redirect' => BASE_PATH . '/workflow/templates'
        ];
    }

    /**
     * API: テンプレートを削除
     */
    public function apiDeleteTemplate($params)
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

        // テンプレートの存在チェック
        $template = $this->model->getTemplateById($id);
        if (!$template) {
            return ['error' => 'Template not found', 'code' => 404];
        }

        $success = $this->model->deleteTemplate($id);
        if (!$success) {
            return ['error' => 'Cannot delete template with existing requests', 'code' => 400];
        }

        return [
            'success' => true,
            'message' => 'テンプレートを削除しました',
            'redirect' => BASE_PATH . '/workflow/templates'
        ];
    }

    /**
     * API: フォームフィールドを追加
     */
    public function apiAddFormField($params, $data)
    {
        // 認証チェック
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }

        // 権限チェック
        if (!$this->auth->isAdmin()) {
            return ['error' => 'Permission denied', 'code' => 403];
        }

        $templateId = $params['id'] ?? null;
        if (!$templateId) {
            return ['error' => 'Invalid template ID', 'code' => 400];
        }

        // テンプレートの存在チェック
        $template = $this->model->getTemplateById($templateId);
        if (!$template) {
            return ['error' => 'Template not found', 'code' => 404];
        }

        // データに必須テンプレートIDを追加
        $data['template_id'] = $templateId;

        // バリデーション
        if (empty($data['field_id']) || empty($data['field_type']) || empty($data['label'])) {
            return ['error' => 'Field ID, type and label are required', 'code' => 400];
        }

        $id = $this->model->addFormField($data);
        if (!$id) {
            return ['error' => 'Failed to add form field', 'code' => 500];
        }

        return [
            'success' => true,
            'data' => [
                'id' => $id,
                'field_id' => $data['field_id'],
                'field_type' => $data['field_type'],
                'label' => $data['label']
            ],
            'message' => 'フォームフィールドを追加しました'
        ];
    }

    /**
     * API: フォームフィールドを更新
     */
    public function apiUpdateFormField($params, $data)
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
            return ['error' => 'Invalid field ID', 'code' => 400];
        }

        // バリデーション
        if (empty($data['field_id']) || empty($data['field_type']) || empty($data['label'])) {
            return ['error' => 'Field ID, type and label are required', 'code' => 400];
        }

        $success = $this->model->updateFormField($id, $data);
        if (!$success) {
            return ['error' => 'Failed to update form field', 'code' => 500];
        }

        return [
            'success' => true,
            'data' => [
                'id' => $id,
                'field_id' => $data['field_id'],
                'field_type' => $data['field_type'],
                'label' => $data['label']
            ],
            'message' => 'フォームフィールドを更新しました'
        ];
    }

    /**
     * API: フォームフィールドを削除
     */
    public function apiDeleteFormField($params)
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
            return ['error' => 'Invalid field ID', 'code' => 400];
        }

        $success = $this->model->deleteFormField($id);
        if (!$success) {
            return ['error' => 'Failed to delete form field', 'code' => 500];
        }

        return [
            'success' => true,
            'message' => 'フォームフィールドを削除しました'
        ];
    }

    /**
     * API: 承認ステップを追加
     */
    public function apiAddRouteStep($params, $data)
    {
        // 認証チェック
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }

        // 権限チェック
        if (!$this->auth->isAdmin()) {
            return ['error' => 'Permission denied', 'code' => 403];
        }

        $templateId = $params['id'] ?? null;
        if (!$templateId) {
            return ['error' => 'Invalid template ID', 'code' => 400];
        }

        // テンプレートの存在チェック
        $template = $this->model->getTemplateById($templateId);
        if (!$template) {
            return ['error' => 'Template not found', 'code' => 404];
        }

        // データに必須テンプレートIDを追加
        $data['template_id'] = $templateId;

        // バリデーション
        if (empty($data['step_number']) || empty($data['step_name']) || empty($data['approver_type'])) {
            return ['error' => 'Step number, name and approver type are required', 'code' => 400];
        }

        $id = $this->model->addRouteStep($data);
        if (!$id) {
            return ['error' => 'Failed to add route step', 'code' => 500];
        }

        return [
            'success' => true,
            'data' => [
                'id' => $id,
                'step_number' => $data['step_number'],
                'step_name' => $data['step_name'],
                'approver_type' => $data['approver_type']
            ],
            'message' => '承認ステップを追加しました'
        ];
    }

    /**
     * API: 承認ステップを更新
     */
    public function apiUpdateRouteStep($params, $data)
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
            return ['error' => 'Invalid step ID', 'code' => 400];
        }

        // バリデーション
        if (empty($data['step_number']) || empty($data['step_name']) || empty($data['approver_type'])) {
            return ['error' => 'Step number, name and approver type are required', 'code' => 400];
        }

        $success = $this->model->updateRouteStep($id, $data);
        if (!$success) {
            return ['error' => 'Failed to update route step', 'code' => 500];
        }

        return [
            'success' => true,
            'data' => [
                'id' => $id,
                'step_number' => $data['step_number'],
                'step_name' => $data['step_name'],
                'approver_type' => $data['approver_type']
            ],
            'message' => '承認ステップを更新しました'
        ];
    }

    /**
     * API: 承認ステップを削除
     */
    public function apiDeleteRouteStep($params)
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
            return ['error' => 'Invalid step ID', 'code' => 400];
        }

        $success = $this->model->deleteRouteStep($id);
        if (!$success) {
            return ['error' => 'Failed to delete route step', 'code' => 500];
        }

        return [
            'success' => true,
            'message' => '承認ステップを削除しました'
        ];
    }

    /**
     * API: 申請を作成
     */
    public function apiCreateRequest($params, $data)
    {
        // 認証チェック
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }

        // バリデーション
        if (empty($data['template_id']) || empty($data['title'])) {
            return ['error' => 'Template ID and title are required', 'code' => 400];
        }

        // 申請者IDを追加
        $data['requester_id'] = $this->auth->id();

        // アップロードディレクトリを確認・作成（public配下に指定）
        $uploadDir = realpath(__DIR__ . '/../public/uploads/workflow/');
        if (!file_exists($uploadDir)) {
            if (!mkdir($uploadDir, 0777, true)) {
                error_log("Failed to create upload directory: {$uploadDir}");
            } else {
                chmod($uploadDir, 0777); // 書き込み権限を確保
            }
        }
        error_log("Upload directory: {$uploadDir} exists: " . (file_exists($uploadDir) ? 'Yes' : 'No') . " writable: " . (is_writable($uploadDir) ? 'Yes' : 'No'));

        // ファイル処理
        if (isset($_FILES) && !empty($_FILES)) {
            $data['files'] = $this->processRequestFiles($_FILES);
        }

        try {
            $id = $this->model->createRequest($data);

            if (!$id) {
                return ['error' => 'Failed to create request', 'code' => 500];
            }

            $request = $this->model->getRequestById($id);

            // 申請が下書き以外の場合、通知を送信
            if ($data['status'] !== 'draft') {
                $this->sendWorkflowRequestNotifications($request['id'], 'create', $data);
            }

            return [
                'success' => true,
                'data' => $request,
                'message' => '申請を作成しました',
                'redirect' => BASE_PATH . '/workflow/requests'
            ];
        } catch (\Exception $e) {
            error_log("Exception in apiCreateRequest: " . $e->getMessage());
            return ['error' => $e->getMessage(), 'code' => 500];
        }
    }

    /**
     * API: 申請を更新
     */
    public function apiUpdateRequest($params, $data)
    {
        // 認証チェック
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }

        $id = $params['id'] ?? null;
        if (!$id) {
            return ['error' => 'Invalid ID', 'code' => 400];
        }

        // 申請の存在チェック
        $request = $this->model->getRequestById($id);
        if (!$request) {
            return ['error' => 'Request not found', 'code' => 404];
        }

        // 権限チェック（申請者のみ編集可能、ただしドラフト状態の場合のみ）
        if ($request['requester_id'] != $this->auth->id() || $request['status'] !== 'draft') {
            return ['error' => 'Permission denied', 'code' => 403];
        }

        // バリデーション
        if (empty($data['title'])) {
            return ['error' => 'Title is required', 'code' => 400];
        }

        // アップロードディレクトリを確認・作成（public配下に指定）
        $uploadDir = realpath(__DIR__ . '/../public/uploads/workflow/');
        if (!file_exists($uploadDir)) {
            if (!mkdir($uploadDir, 0777, true)) {
                error_log("Failed to create upload directory: {$uploadDir}");
            } else {
                chmod($uploadDir, 0777); // 書き込み権限を確保
            }
        }
        error_log("Upload directory: {$uploadDir} exists: " . (file_exists($uploadDir) ? 'Yes' : 'No') . " writable: " . (is_writable($uploadDir) ? 'Yes' : 'No'));

        // ファイル処理
        if (isset($_FILES) && !empty($_FILES)) {
            $data['files'] = $this->processRequestFiles($_FILES);
        }

        try {
            $success = $this->model->updateRequest($id, $data);

            if (!$success) {
                return ['error' => 'Failed to update request', 'code' => 500];
            }

            $request = $this->model->getRequestById($id);

            // 申請が下書きから申請中に変更された場合、通知を送信
            if ($data['status'] === 'pending' && $request['status'] === 'draft') {
                $this->sendWorkflowRequestNotifications($id, 'submit', $data);
            }

            return [
                'success' => true,
                'data' => $request,
                'message' => '申請を更新しました',
                'redirect' => BASE_PATH . '/workflow/requests'
            ];
        } catch (\Exception $e) {
            error_log("Exception in apiUpdateRequest: " . $e->getMessage());
            return ['error' => $e->getMessage(), 'code' => 500];
        }
    }

    /**
     * API: 申請を承認/却下
     */
    public function apiProcessApproval($params, $data)
    {
        // 認証チェック
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }

        $requestId = $params['id'] ?? null;
        if (!$requestId) {
            return ['error' => 'Invalid request ID', 'code' => 400];
        }

        // 申請の存在チェック
        $request = $this->model->getRequestById($requestId);
        if (!$request || $request['status'] !== 'pending') {
            return ['error' => 'Valid pending request not found', 'code' => 404];
        }

        // アクションチェック
        $action = $data['action'] ?? '';
        if ($action !== 'approved' && $action !== 'rejected') {
            return ['error' => 'Invalid action', 'code' => 400];
        }

        $userId = $this->auth->id();

        try {
            $success = $this->model->processApproval($requestId, $userId, $data);
            
            if (!$success) {
                return ['error' => 'Failed to process approval', 'code' => 500];
            }

            $actionText = $action === 'approved' ? '承認' : '却下';


            // 現在のステップを保存（後で比較するため）
            $currentStep = $request['current_step'];

            // 現在の承認タスクを取得（通知のため）
            $sql = "SELECT * FROM workflow_approvals 
                WHERE request_id = ? AND approver_id = ? AND status = 'pending' 
                AND step_number = ? LIMIT 1";
            $approval = $this->db->fetch($sql, [
                $requestId,
                $userId,
                $currentStep
            ]);

            // 処理後の申請情報を取得（ステータスや次のステップを確認するため）
            $updatedRequest = $this->model->getRequestById($requestId);

            if ($approval) {
                // 申請者に通知（承認/却下）
                $this->sendWorkflowApprovalNotification($requestId, $approval['id'], $action, $data['comment'] ?? null);

                // ステップが進んだ場合 (承認かつ、次のステップがある場合)
                if ($action === 'approved' && $updatedRequest['status'] === 'pending' && $updatedRequest['current_step'] > $currentStep) {
                    // 次のステップの承認者に通知
                    $sql = "SELECT * FROM workflow_approvals 
                        WHERE request_id = ? AND step_number = ? AND status = 'pending'";
                    $nextApprovals = $this->db->fetchAll($sql, [$requestId, $updatedRequest['current_step']]);

                    if ($nextApprovals) {
                        foreach ($nextApprovals as $nextApproval) {
                            $this->sendWorkflowStepNotification($requestId, $updatedRequest['current_step'], $nextApproval['approver_id']);
                        }
                    }
                }

                // 申請が完了した場合（承認完了）
                if ($updatedRequest['status'] === 'approved') {
                    $this->sendWorkflowCompletionNotification($requestId);
                }
            }

            return [
                'success' => true,
                'message' => '申請を' . $actionText . 'しました',
                'redirect' => BASE_PATH . '/workflow/approvals'
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage(), 'code' => 500];
        }
    }

    /**
     * API: 申請をキャンセル
     */
    public function apiCancelRequest($params)
    {
        // 認証チェック
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }

        $id = $params['id'] ?? null;
        if (!$id) {
            return ['error' => 'Invalid ID', 'code' => 400];
        }

        $userId = $this->auth->id();
        $success = $this->model->cancelRequest($id, $userId);
        
        if (!$success) {
            return ['error' => 'Failed to cancel request or permission denied', 'code' => 400];
        }

        return [
            'success' => true,
            'message' => '申請をキャンセルしました',
            'redirect' => BASE_PATH . '/workflow/requests'
        ];
    }

    /**
     * API: コメントを追加
     */
    public function apiAddComment($params, $data)
    {
        // 認証チェック
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }

        $requestId = $params['id'] ?? null;
        if (!$requestId) {
            return ['error' => 'Invalid request ID', 'code' => 400];
        }

        // 申請の存在チェック
        $request = $this->model->getRequestById($requestId);
        if (!$request) {
            return ['error' => 'Request not found', 'code' => 404];
        }

        // バリデーション
        if (empty($data['comment'])) {
            return ['error' => 'Comment is required', 'code' => 400];
        }

        $userId = $this->auth->id();
        $success = $this->model->addComment($requestId, $userId, $data['comment']);
        
        if (!$success) {
            return ['error' => 'Failed to add comment', 'code' => 500];
        }

        // 最新のコメント一覧を取得
        $comments = $this->model->getComments($requestId);

        // コメント通知を送信
        $this->sendCommentNotification($requestId, $userId, $data['comment']);

        return [
            'success' => true,
            'data' => [
                'comments' => $comments
            ],
            'message' => 'コメントを追加しました'
        ];
    }

    /**
     * API: 代理承認設定を追加
     */
    public function apiAddDelegation($params, $data)
    {
        // 認証チェック
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }

        // バリデーション
        if (empty($data['delegate_id']) || empty($data['start_date']) || empty($data['end_date'])) {
            return ['error' => 'Delegate, start date and end date are required', 'code' => 400];
        }

        // 自分自身を代理人にはできない
        $userId = $this->auth->id();
        if ($userId == $data['delegate_id']) {
            return ['error' => 'Cannot delegate to yourself', 'code' => 400];
        }

        // 日付の妥当性チェック
        $startDate = strtotime($data['start_date']);
        $endDate = strtotime($data['end_date']);
        
        if ($startDate > $endDate) {
            return ['error' => 'End date must be after start date', 'code' => 400];
        }

        // データにユーザーIDを追加
        $data['user_id'] = $userId;

        $success = $this->model->addDelegation($data);
        
        if (!$success) {
            return ['error' => 'Failed to add delegation', 'code' => 500];
        }

        return [
            'success' => true,
            'message' => '代理承認設定を追加しました',
            'redirect' => BASE_PATH . '/workflow/delegates'
        ];
    }

    /**
     * API: PDFエクスポート
     */
    public function apiExportPdf($params)
    {
        // 認証チェック
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }

        $id = $params['id'] ?? null;
        if (!$id) {
            return ['error' => 'Invalid ID', 'code' => 400];
        }

        // 申請データを取得
        $exportData = $this->model->getRequestExportData($id);
        if (!$exportData) {
            return ['error' => 'Request not found', 'code' => 404];
        }

        // 権限チェック（管理者、申請者、承認者のみエクスポート可能）
        $userId = $this->auth->id();
        $isAdmin = $this->auth->isAdmin();
        $isRequester = ($exportData['request']['requester_id'] == $userId);

        // 承認者かどうかチェック
        $sql = "SELECT COUNT(*) as count FROM workflow_approvals 
            WHERE request_id = ? AND approver_id = ?";
        $isApprover = $this->db->fetch($sql, [$id, $userId])['count'] > 0;

        if (!$isAdmin && !$isRequester && !$isApprover) {
            return ['error' => 'Permission denied', 'code' => 403];
        }

        // PDFエクスポート処理
        require_once __DIR__ . '/../vendor/autoload.php'; // TCPDFがインストールされていると仮定

        try {
            // PDFを生成
            $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

            // ドキュメント情報設定
            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetAuthor('GroupSession');
            $pdf->SetTitle('申請書: ' . $exportData['request']['title']);
            $pdf->SetSubject('Workflow Request');

            // デフォルトヘッダー/フッター設定
            $pdf->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
            $pdf->setFooterFont(array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

            // デフォルトのモノスペースフォント設定
            $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

            // マージン設定
            $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
            $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
            $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

            // 自動改ページ設定
            $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

            // イメージの倍率設定
            $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

            // 新しいPDFドキュメントを開始
            $pdf->AddPage();

            // ヘッダー
            $pdf->SetFont('helvetica', 'B', 16);
            $pdf->Cell(0, 10, '申請書: ' . $exportData['request']['title'], 0, 1, 'C');
            $pdf->Ln(10);

            // 基本情報
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 10, '基本情報', 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 10);

            $pdf->Cell(40, 7, '申請番号:', 0, 0, 'L');
            $pdf->Cell(0, 7, $exportData['request']['request_number'], 0, 1, 'L');

            $pdf->Cell(40, 7, 'テンプレート:', 0, 0, 'L');
            $pdf->Cell(0, 7, $exportData['request']['template_name'], 0, 1, 'L');

            $pdf->Cell(40, 7, '申請者:', 0, 0, 'L');
            $pdf->Cell(0, 7, $exportData['request']['requester_name'], 0, 1, 'L');

            $pdf->Cell(40, 7, 'ステータス:', 0, 0, 'L');
            $status = '';
            switch ($exportData['request']['status']) {
                case 'draft':
                    $status = '下書き';
                    break;
                case 'pending':
                    $status = '承認待ち';
                    break;
                case 'approved':
                    $status = '承認済み';
                    break;
                case 'rejected':
                    $status = '却下';
                    break;
                case 'cancelled':
                    $status = 'キャンセル';
                    break;
                default:
                    $status = $exportData['request']['status'];
                    break;
            }
            $pdf->Cell(0, 7, $status, 0, 1, 'L');

            $pdf->Cell(40, 7, '申請日時:', 0, 0, 'L');
            $pdf->Cell(0, 7, date('Y年m月d日 H:i', strtotime($exportData['request']['created_at'])), 0, 1, 'L');

            $pdf->Ln(5);

            // フォームデータ
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 10, '申請内容', 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 10);

            foreach ($exportData['form_definitions'] as $field) {
                $fieldId = $field['field_id'];
                if ($field['field_type'] === 'hidden' || $field['field_type'] === 'heading') {
                    continue;
                }

                if ($field['field_type'] === 'heading') {
                    $pdf->SetFont('helvetica', 'B', 11);
                    $pdf->Cell(0, 10, $field['label'], 0, 1, 'L');
                    $pdf->SetFont('helvetica', '', 10);
                    continue;
                }

                $fieldValue = isset($exportData['form_data'][$fieldId]) ? $exportData['form_data'][$fieldId] : '';

                $pdf->Cell(40, 7, $field['label'] . ':', 0, 0, 'L');

                switch ($field['field_type']) {
                    case 'textarea':
                        $pdf->MultiCell(0, 7, $fieldValue, 0, 'L');
                        break;

                    case 'select':
                    case 'radio':
                        $options = $field['options'] ? json_decode($field['options'], true) : [];
                        $selectedOption = array_filter($options, function ($opt) use ($fieldValue) {
                            return $opt['value'] === $fieldValue;
                        });
                        $displayValue = !empty($selectedOption) ? reset($selectedOption)['label'] : $fieldValue;
                        $pdf->Cell(0, 7, $displayValue, 0, 1, 'L');
                        break;

                    case 'checkbox':
                        $options = $field['options'] ? json_decode($field['options'], true) : [];
                        $checkedValues = is_array($fieldValue) ? $fieldValue : ($fieldValue ? [$fieldValue] : []);

                        if (!empty($checkedValues)) {
                            $displayValue = '';
                            foreach ($checkedValues as $value) {
                                $selectedOption = array_filter($options, function ($opt) use ($value) {
                                    return $opt['value'] === $value;
                                });
                                $displayValue .= (!empty($selectedOption) ? reset($selectedOption)['label'] : $value) . ', ';
                            }
                            $displayValue = rtrim($displayValue, ', ');
                            $pdf->Cell(0, 7, $displayValue, 0, 1, 'L');
                        } else {
                            $pdf->Cell(0, 7, '選択なし', 0, 1, 'L');
                        }
                        break;

                    case 'file':
                        $pdf->Cell(0, 7, '[添付ファイル]', 0, 1, 'L');
                        break;

                    default:
                        $pdf->Cell(0, 7, $fieldValue ?: '未入力', 0, 1, 'L');
                }
            }

            $pdf->Ln(5);

            // 承認履歴
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 10, '承認履歴', 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 10);

            if (!empty($exportData['approvals'])) {
                // 承認履歴テーブルのヘッダー
                $pdf->SetFillColor(230, 230, 230);
                $pdf->Cell(30, 7, 'ステップ', 1, 0, 'C', 1);
                $pdf->Cell(60, 7, '承認者', 1, 0, 'C', 1);
                $pdf->Cell(30, 7, 'ステータス', 1, 0, 'C', 1);
                $pdf->Cell(40, 7, '日時', 1, 0, 'C', 1);
                $pdf->Cell(0, 7, 'コメント', 1, 1, 'C', 1);

                $currentStep = 0;
                foreach ($exportData['approvals'] as $approval) {
                    $approverName = $approval['approver_name'];
                    if ($approval['delegate_id']) {
                        $approverName .= '（代理: ' . $approval['delegate_name'] . '）';
                    }

                    $approvalStatus = '';
                    switch ($approval['status']) {
                        case 'pending':
                            $approvalStatus = '承認待ち';
                            break;
                        case 'approved':
                            $approvalStatus = '承認';
                            break;
                        case 'rejected':
                            $approvalStatus = '却下';
                            break;
                        case 'skipped':
                            $approvalStatus = 'スキップ';
                            break;
                        default:
                            $approvalStatus = $approval['status'];
                            break;
                    }

                    $approvalDate = $approval['acted_at'] ? date('m/d H:i', strtotime($approval['acted_at'])) : '-';

                    $pdf->Cell(30, 7, 'ステップ ' . $approval['step_number'], 1, 0, 'C');
                    $pdf->Cell(60, 7, $approverName, 1, 0, 'L');
                    $pdf->Cell(30, 7, $approvalStatus, 1, 0, 'C');
                    $pdf->Cell(40, 7, $approvalDate, 1, 0, 'C');
                    $pdf->Cell(0, 7, $approval['comment'] ?: '-', 1, 1, 'L');
                }
            } else {
                $pdf->Cell(0, 7, '承認履歴はありません', 0, 1, 'L');
            }

            // 出力ディレクトリを確認し、存在しなければ作成
            $exportDir = __DIR__ . '/../exports/workflow/';
            if (!file_exists($exportDir)) {
                mkdir($exportDir, 0755, true);
            }

            $filename = 'request_' . $id . '.pdf';
            $filepath = $exportDir . $filename;

            // PDFファイルを保存
            $pdf->Output($filepath, 'F');

            return [
                'success' => true,
                'data' => [
                    'download_url' => BASE_PATH . '/exports/workflow/' . $filename
                ],
                'message' => 'PDFのエクスポートが完了しました'
            ];
        } catch (\Exception $e) {
            error_log('PDF Export Error: ' . $e->getMessage());
            return ['error' => 'PDFの生成に失敗しました', 'code' => 500];
        }
    }


    /**
     * API: CSVエクスポート
     */
    public function apiExportCsv($params)
    {
        // 認証チェック
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }

        $id = $params['id'] ?? null;
        if (!$id) {
            return ['error' => 'Invalid ID', 'code' => 400];
        }

        // 申請データを取得
        $exportData = $this->model->getRequestExportData($id);
        if (!$exportData) {
            return ['error' => 'Request not found', 'code' => 404];
        }

        // 権限チェック（管理者、申請者、承認者のみエクスポート可能）
        $userId = $this->auth->id();
        $isAdmin = $this->auth->isAdmin();
        $isRequester = ($exportData['request']['requester_id'] == $userId);

        // 承認者かどうかチェック
        $sql = "SELECT COUNT(*) as count FROM workflow_approvals 
            WHERE request_id = ? AND approver_id = ?";
        $isApprover = $this->db->fetch($sql, [$id, $userId])['count'] > 0;

        if (!$isAdmin && !$isRequester && !$isApprover) {
            return ['error' => 'Permission denied', 'code' => 403];
        }

        // CSVエクスポート処理
        try {
            // 出力ディレクトリを確認し、存在しなければ作成
            $exportDir = __DIR__ . '/../exports/workflow/';
            if (!file_exists($exportDir)) {
                mkdir($exportDir, 0755, true);
            }

            $filename = 'request_' . $id . '.csv';
            $filepath = $exportDir . $filename;

            // CSVファイルを開く
            $fp = fopen($filepath, 'w');

            // UTF-8 BOMを書き込む（Excelで開く場合の文字化け対策）
            fputs($fp, "\xEF\xBB\xBF");

            // 基本情報のヘッダーと値を書き込む
            fputcsv($fp, ['基本情報', '']);
            fputcsv($fp, ['申請番号', $exportData['request']['request_number']]);
            fputcsv($fp, ['テンプレート', $exportData['request']['template_name']]);
            fputcsv($fp, ['タイトル', $exportData['request']['title']]);
            fputcsv($fp, ['申請者', $exportData['request']['requester_name']]);

            // ステータスを日本語に変換
            $status = '';
            switch ($exportData['request']['status']) {
                case 'draft':
                    $status = '下書き';
                    break;
                case 'pending':
                    $status = '承認待ち';
                    break;
                case 'approved':
                    $status = '承認済み';
                    break;
                case 'rejected':
                    $status = '却下';
                    break;
                case 'cancelled':
                    $status = 'キャンセル';
                    break;
                default:
                    $status = $exportData['request']['status'];
                    break;
            }
            fputcsv($fp, ['ステータス', $status]);
            fputcsv($fp, ['申請日時', date('Y年m月d日 H:i', strtotime($exportData['request']['created_at']))]);

            // 空行
            fputcsv($fp, []);

            // 申請内容
            fputcsv($fp, ['申請内容', '']);

            foreach ($exportData['form_definitions'] as $field) {
                $fieldId = $field['field_id'];

                // 隠しフィールドはスキップ
                if ($field['field_type'] === 'hidden') {
                    continue;
                }

                // 見出しの場合
                if ($field['field_type'] === 'heading') {
                    fputcsv($fp, [$field['label'], '']);
                    continue;
                }

                $fieldValue = isset($exportData['form_data'][$fieldId]) ? $exportData['form_data'][$fieldId] : '';

                // フィールドタイプに応じた値の処理
                switch ($field['field_type']) {
                    case 'select':
                    case 'radio':
                        $options = $field['options'] ? json_decode($field['options'], true) : [];
                        $selectedOption = array_filter($options, function ($opt) use ($fieldValue) {
                            return $opt['value'] === $fieldValue;
                        });
                        $displayValue = !empty($selectedOption) ? reset($selectedOption)['label'] : $fieldValue;
                        fputcsv($fp, [$field['label'], $displayValue]);
                        break;

                    case 'checkbox':
                        $options = $field['options'] ? json_decode($field['options'], true) : [];
                        $checkedValues = is_array($fieldValue) ? $fieldValue : ($fieldValue ? [$fieldValue] : []);

                        if (!empty($checkedValues)) {
                            $displayValue = '';
                            foreach ($checkedValues as $value) {
                                $selectedOption = array_filter($options, function ($opt) use ($value) {
                                    return $opt['value'] === $value;
                                });
                                $displayValue .= (!empty($selectedOption) ? reset($selectedOption)['label'] : $value) . ', ';
                            }
                            $displayValue = rtrim($displayValue, ', ');
                            fputcsv($fp, [$field['label'], $displayValue]);
                        } else {
                            fputcsv($fp, [$field['label'], '選択なし']);
                        }
                        break;

                    case 'file':
                        fputcsv($fp, [$field['label'], '[添付ファイル]']);
                        break;

                    default:
                        fputcsv($fp, [$field['label'], $fieldValue ?: '未入力']);
                }
            }

            // 空行
            fputcsv($fp, []);

            // 承認履歴
            fputcsv($fp, ['承認履歴', '']);

            if (!empty($exportData['approvals'])) {
                // 承認履歴テーブルのヘッダー
                fputcsv($fp, ['ステップ', '承認者', 'ステータス', '日時', 'コメント']);

                foreach ($exportData['approvals'] as $approval) {
                    $approverName = $approval['approver_name'];
                    if ($approval['delegate_id']) {
                        $approverName .= '（代理: ' . $approval['delegate_name'] . '）';
                    }

                    $approvalStatus = '';
                    switch ($approval['status']) {
                        case 'pending':
                            $approvalStatus = '承認待ち';
                            break;
                        case 'approved':
                            $approvalStatus = '承認';
                            break;
                        case 'rejected':
                            $approvalStatus = '却下';
                            break;
                        case 'skipped':
                            $approvalStatus = 'スキップ';
                            break;
                        default:
                            $approvalStatus = $approval['status'];
                            break;
                    }

                    $approvalDate = $approval['acted_at'] ? date('Y/m/d H:i', strtotime($approval['acted_at'])) : '-';

                    fputcsv($fp, [
                        'ステップ ' . $approval['step_number'],
                        $approverName,
                        $approvalStatus,
                        $approvalDate,
                        $approval['comment'] ?: '-'
                    ]);
                }
            } else {
                fputcsv($fp, ['承認履歴はありません']);
            }

            // ファイルを閉じる
            fclose($fp);

            return [
                'success' => true,
                'data' => [
                    'download_url' => BASE_PATH . '/exports/workflow/' . $filename
                ],
                'message' => 'CSVのエクスポートが完了しました'
            ];
        } catch (\Exception $e) {
            error_log('CSV Export Error: ' . $e->getMessage());
            return ['error' => 'CSVの生成に失敗しました', 'code' => 500];
        }
    }

    private function processRequestFiles($files)
    {
        $processedFiles = [];

        // アップロードディレクトリをpublic配下に修正
        $uploadDir = realpath(__DIR__ . '/../public/uploads/workflow/');

        // アップロードディレクトリが存在しない場合は作成
        if (!file_exists($uploadDir)) {
            if (!mkdir($uploadDir, 0777, true)) {
                error_log("Failed to create upload directory: {$uploadDir}");
                return $processedFiles;
            }
            chmod($uploadDir, 0777); // 書き込み権限を確保
        }

        error_log("Upload directory: {$uploadDir} exists: " . (file_exists($uploadDir) ? 'Yes' : 'No') . " writable: " . (is_writable($uploadDir) ? 'Yes' : 'No'));
        error_log("Processing attachment files: " . print_r($files, true));

        foreach ($files as $fieldId => $fileInfo) {
            // ファイルフィールドでない場合はスキップ
            if (!is_array($fileInfo) || !isset($fileInfo['name'])) {
                continue;
            }

            // 単一ファイルの場合
            if (is_string($fileInfo['name'])) {
                $fileName = $fileInfo['name'];
                $tmpName = $fileInfo['tmp_name'];
                $fileSize = $fileInfo['size'];
                $fileType = $fileInfo['type'];
                $fileError = $fileInfo['error'];

                error_log("Processing single file: {$fileName}, Error: {$fileError}");

                // ファイルがアップロードされている場合のみ処理
                if (!empty($fileName) && $fileError === 0 && is_uploaded_file($tmpName)) {
                    // ファイル名をサニタイズ
                    $safeName = $this->sanitizeFileName($fileName);

                    // 一意のファイル名を生成
                    $uniqueName = uniqid() . '_' . $safeName;
                    $filePath = $uploadDir . '/' . $uniqueName;

                    error_log("Moving file from {$tmpName} to {$filePath}");

                    // ファイルを移動
                    if (move_uploaded_file($tmpName, $filePath)) {
                        error_log("File moved successfully");
                        $processedFiles[$fieldId] = [
                            'name' => $fileName,
                            'path' => 'uploads/workflow/' . $uniqueName,
                            'size' => $fileSize,
                            'type' => $fileType
                        ];
                    } else {
                        $errorMsg = error_get_last();
                        error_log("Failed to move file: " . ($errorMsg ? $errorMsg['message'] : 'Unknown error'));
                    }
                }
            }
            // 複数ファイルの場合
            elseif (is_array($fileInfo['name'])) {
                $processedFiles[$fieldId] = [];

                error_log("Processing multiple files: " . count($fileInfo['name']));

                for ($i = 0; $i < count($fileInfo['name']); $i++) {
                    $fileName = $fileInfo['name'][$i];
                    $tmpName = $fileInfo['tmp_name'][$i];
                    $fileSize = $fileInfo['size'][$i];
                    $fileType = $fileInfo['type'][$i];
                    $fileError = $fileInfo['error'][$i];

                    error_log("Processing file #{$i}: {$fileName}, Error: {$fileError}");

                    // ファイルがアップロードされている場合のみ処理
                    if (!empty($fileName) && $fileError === 0 && is_uploaded_file($tmpName)) {
                        // ファイル名をサニタイズ
                        $safeName = $this->sanitizeFileName($fileName);

                        // 一意のファイル名を生成
                        $uniqueName = uniqid() . '_' . $safeName;
                        $filePath = $uploadDir . '/' . $uniqueName;

                        error_log("Moving file from {$tmpName} to {$filePath}");

                        // ファイルを移動
                        if (move_uploaded_file($tmpName, $filePath)) {
                            error_log("File moved successfully");
                            $processedFiles[$fieldId][] = [
                                'name' => $fileName,
                                'path' => 'uploads/workflow/' . $uniqueName,
                                'size' => $fileSize,
                                'type' => $fileType
                            ];
                        } else {
                            $errorMsg = error_get_last();
                            error_log("Failed to move file: " . ($errorMsg ? $errorMsg['message'] : 'Unknown error'));
                        }
                    }
                }
            }
        }

        error_log("Processed files: " . print_r($processedFiles, true));
        return $processedFiles;
    }

    /**
     * ファイル名をサニタイズして安全なファイル名に変換
     * 
     * @param string $filename 元のファイル名
     * @return string サニタイズされたファイル名
     */
    private function sanitizeFileName($filename)
    {
        // ファイル名と拡張子を分離
        $pathInfo = pathinfo($filename);
        $extension = isset($pathInfo['extension']) ? '.' . $pathInfo['extension'] : '';
        $basename = $pathInfo['filename'];

        // 日本語や特殊文字を含むファイル名を英数字とアンダースコアに変換
        $basename = preg_replace('/[^\p{L}\p{N}_.-]/u', '_', $basename);

        // 空のファイル名の場合は代替名を使用
        if (empty($basename)) {
            $basename = 'file';
        }

        // 拡張子を付けて返す
        return $basename . $extension;
    }

    /**
     * API: 申請一覧を取得
     */
    public function apiGetRequests($params)
    {
        // 認証チェック
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }

        // フィルタリング条件
        $filters = [
            'status' => $params['status'] ?? null,
            'template_id' => $params['template_id'] ?? null,
            'search' => $params['search'] ?? null,
        ];

        // ページネーション
        $page = $params['page'] ?? 1;
        $limit = $params['limit'] ?? 20;

        // 自分が作成した申請のみ表示（管理者以外）
        if (!$this->auth->isAdmin()) {
            $filters['requester_id'] = $this->auth->id();
        }

        // 申請リストを取得
        $requests = $this->model->getRequests($filters, $page, $limit);
        $totalRequests = $this->model->getRequestCount($filters);
        $totalPages = ceil($totalRequests / $limit);

        return [
            'success' => true,
            'data' => [
                'requests' => $requests,
                'pagination' => [
                    'total' => $totalRequests,
                    'total_pages' => $totalPages,
                    'current_page' => $page,
                    'limit' => $limit
                ]
            ]
        ];
    }

    /**
     * API: ワークフロー統計情報を取得
     */
    public function apiGetStats()
    {
        // 認証チェック
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }

        $userId = $this->auth->id();
        $isAdmin = $this->auth->isAdmin();

        // テンプレート数
        $templateCount = $this->model->getTemplateCount();

        // 申請数（管理者は全て、一般ユーザーは自分のみ）
        $requestFilters = $isAdmin ? [] : ['requester_id' => $userId];
        $requestCount = $this->model->getRequestCount($requestFilters);

        // 承認待ち数
        $pendingFilters = [
            'pending_approval' => true,
            'user_id' => $userId
        ];
        $pendingCount = $this->model->getRequestCount($pendingFilters);

        // 自分の申請数
        $myRequestCount = $this->model->getRequestCount(['requester_id' => $userId]);

        // ステータス別統計
        $statusStats = [];
        $statuses = ['draft', 'pending', 'approved', 'rejected', 'cancelled'];

        foreach ($statuses as $status) {
            $filters = ['status' => $status];
            if (!$isAdmin) {
                $filters['requester_id'] = $userId;
            }
            $statusStats[$status] = $this->model->getRequestCount($filters);
        }

        // 最近の申請
        $recentFilters = $isAdmin ? [] : ['requester_id' => $userId];
        $recentRequests = $this->model->getRequests($recentFilters, 1, 5);

        return [
            'success' => true,
            'data' => [
                'templates_count' => $templateCount,
                'requests_count' => $requestCount,
                'pending_approvals' => $pendingCount,
                'my_requests' => $myRequestCount,
                'status_stats' => $statusStats,
                'recent_requests' => $recentRequests
            ]
        ];
    }

    /**
     * API: フォーム定義を一括保存
     */
    public function apiSaveFormDefinitions($params, $data)
    {
        // 認証チェック
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }

        // 権限チェック
        if (!$this->auth->isAdmin()) {
            return ['error' => 'Permission denied', 'code' => 403];
        }

        $templateId = $params['id'] ?? null;
        if (!$templateId) {
            return ['error' => 'Invalid template ID', 'code' => 400];
        }

        // テンプレートの存在チェック
        $template = $this->model->getTemplateById($templateId);
        if (!$template) {
            return ['error' => 'Template not found', 'code' => 404];
        }

        // フォーム定義のバリデーション
        if (!isset($data['form_definitions']) || !is_array($data['form_definitions'])) {
            return ['error' => 'Form definitions are required', 'code' => 400];
        }

        // 既存のフォーム定義を取得
        $existingFields = $this->model->getFormDefinitions($templateId);
        $existingFieldIds = array_column($existingFields, 'id');

        // トランザクション開始
        $this->db->beginTransaction();

        try {
            // 新しいフィールドの場合は追加、既存フィールドの場合は更新
            foreach ($data['form_definitions'] as $field) {
                if (empty($field['id']) || !in_array($field['id'], $existingFieldIds)) {
                    // 新規フィールド
                    $field['template_id'] = $templateId;
                    $this->model->addFormField($field);
                } else {
                    // 既存フィールド
                    $this->model->updateFormField($field['id'], $field);
                }
            }

            // 削除されたフィールドを特定して削除
            $newFieldIds = array_filter(array_column($data['form_definitions'], 'id'));
            foreach ($existingFieldIds as $existingId) {
                if (!in_array($existingId, $newFieldIds)) {
                    $this->model->deleteFormField($existingId);
                }
            }

            $this->db->commit();

            // 更新されたフォーム定義を取得
            $formDefinitions = $this->model->getFormDefinitions($templateId);

            return [
                'success' => true,
                'data' => [
                    'form_definitions' => $formDefinitions
                ],
                'message' => 'フォーム定義を保存しました'
            ];
        } catch (\Exception $e) {
            $this->db->rollBack();
            return [
                'error' => 'Failed to save form definitions: ' . $e->getMessage(),
                'code' => 500
            ];
        }
    }

    /**
     * API: 承認経路定義を一括保存
     */
    public function apiSaveRouteDefinitions($params, $data)
    {
        // 認証チェック
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }

        // 権限チェック
        if (!$this->auth->isAdmin()) {
            return ['error' => 'Permission denied', 'code' => 403];
        }

        $templateId = $params['id'] ?? null;
        if (!$templateId) {
            return ['error' => 'Invalid template ID', 'code' => 400];
        }

        // テンプレートの存在チェック
        $template = $this->model->getTemplateById($templateId);
        if (!$template) {
            return ['error' => 'Template not found', 'code' => 404];
        }

        // 承認経路定義のバリデーション
        if (!isset($data['route_definitions']) || !is_array($data['route_definitions'])) {
            return ['error' => 'Route definitions are required', 'code' => 400];
        }

        // 既存の承認経路定義を取得
        $existingSteps = $this->model->getRouteDefinitions($templateId);
        $existingStepIds = array_column($existingSteps, 'id');

        // トランザクション開始
        $this->db->beginTransaction();

        try {
            // 新しいステップの場合は追加、既存ステップの場合は更新
            foreach ($data['route_definitions'] as $step) {
                if (empty($step['id']) || !in_array($step['id'], $existingStepIds)) {
                    // 新規ステップ
                    $step['template_id'] = $templateId;
                    $this->model->addRouteStep($step);
                } else {
                    // 既存ステップ
                    $this->model->updateRouteStep($step['id'], $step);
                }
            }

            // 削除されたステップを特定して削除
            $newStepIds = array_filter(array_column($data['route_definitions'], 'id'));
            foreach ($existingStepIds as $existingId) {
                if (!in_array($existingId, $newStepIds)) {
                    $this->model->deleteRouteStep($existingId);
                }
            }

            $this->db->commit();

            // 更新された承認経路定義を取得
            $routeDefinitions = $this->model->getRouteDefinitions($templateId);

            return [
                'success' => true,
                'data' => [
                    'route_definitions' => $routeDefinitions
                ],
                'message' => '承認経路定義を保存しました'
            ];
        } catch (\Exception $e) {
            $this->db->rollBack();
            return [
                'error' => 'Failed to save route definitions: ' . $e->getMessage(),
                'code' => 500
            ];
        }
    }

    /**
     * API: 個別の申請を取得
     */
    public function apiGetRequest($params)
    {
        // 認証チェック
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }

        $id = $params['id'] ?? null;
        if (!$id) {
            return ['error' => 'Invalid ID', 'code' => 400];
        }

        // 申請の存在チェック
        $request = $this->model->getRequestById($id);
        if (!$request) {
            return ['error' => 'Request not found', 'code' => 404];
        }

        // 権限チェック（管理者、申請者、承認者のみ閲覧可能）
        $userId = $this->auth->id();
        $isAdmin = $this->auth->isAdmin();
        $isRequester = ($request['requester_id'] == $userId);

        // 承認者かどうかチェック
        $sql = "SELECT COUNT(*) as count FROM workflow_approvals 
            WHERE request_id = ? AND approver_id = ?";
        $isApprover = $this->db->fetch($sql, [$id, $userId])['count'] > 0;

        if (!$isAdmin && !$isRequester && !$isApprover) {
            return ['error' => 'Permission denied', 'code' => 403];
        }

        // フォーム定義を取得
        $formDefinitions = $this->model->getFormDefinitions($request['template_id']);

        // フォームデータを取得
        $formData = $this->model->getRequestData($id);

        // 添付ファイルを取得
        $attachments = $this->model->getRequestAttachments($id);

        // 承認履歴を取得
        $approvals = $this->model->getRequestApprovals($id);

        // コメントを取得
        $comments = $this->model->getComments($id);

        // 現在のユーザーの承認タスクを取得
        $currentApproval = null;
        if (!$isAdmin && !$isRequester) {
            $sql = "SELECT * FROM workflow_approvals 
                WHERE request_id = ? AND approver_id = ? AND status = 'pending' 
                AND step_number = ? LIMIT 1";
            $currentApproval = $this->db->fetch($sql, [
                $id,
                $userId,
                $request['current_step']
            ]);
        }

        return [
            'success' => true,
            'data' => [
                'request' => $request,
                'form_definitions' => $formDefinitions,
                'form_data' => $formData,
                'attachments' => $attachments,
                'approvals' => $approvals,
                'comments' => $comments,
                'current_approval' => $currentApproval,
                'is_admin' => $isAdmin,
                'is_requester' => $isRequester,
                'is_approver' => $isApprover
            ]
        ];
    }

    /**
     * ワークフロー申請の通知を送信
     * 
     * @param int $requestId 申請ID
     * @param string $action アクション（create, submit）
     * @param array $data 申請データ
     */
    private function sendWorkflowRequestNotifications($requestId, $action, $data)
    {
        // 申請情報を取得
        $request = $this->model->getRequestById($requestId);
        if (!$request) {
            return;
        }

        // テンプレート情報を取得
        $template = $this->model->getTemplateById($request['template_id']);
        if (!$template) {
            return;
        }

        // 申請者の情報を取得
        $requester = $this->userModel->getById($request['requester_id']);
        if (!$requester) {
            return;
        }

        // 現在のステップの承認者を取得
        $approvers = [];
        if ($request['current_step']) {
            $sql = "SELECT approver_id FROM workflow_approvals 
                    WHERE request_id = ? AND step_number = ? AND status = 'pending'";
            $approvalResults = $this->db->fetchAll($sql, [$requestId, $request['current_step']]);

            if ($approvalResults) {
                foreach ($approvalResults as $result) {
                    $approvers[] = $result['approver_id'];
                }
            }
        }

        // 通知タイトルと内容を準備
        $title = '';
        $content = '';

        switch ($action) {
            case 'create':
            case 'submit':
                $title = '新しいワークフロー申請が提出されました';
                $content = "{$requester['display_name']}さんから新しい申請が提出されました。\n";
                $content .= "申請タイプ: {$template['name']}\n";
                $content .= "タイトル: {$request['title']}\n";
                $content .= "申請番号: {$request['request_number']}";
                break;
        }

        // リンク
        $link = "/workflow/view/{$requestId}";

        // 作成者自身には通知しない
        $currentUserId = $this->auth->id();

        // 承認者に通知を送信
        foreach ($approvers as $approverId) {
            // 自分自身には通知しない
            if ($approverId == $currentUserId) {
                continue;
            }

            // 通知データ
            $notificationData = [
                'user_id' => $approverId,
                'type' => 'workflow',
                'title' => $title,
                'content' => $content,
                'link' => $link,
                'reference_id' => $requestId,
                'reference_type' => 'workflow_request'
            ];

            // 通知を送信
            $this->notification->create($notificationData);
        }
    }

    /**
     * ワークフロー承認の通知を送信
     * 
     * @param int $requestId 申請ID
     * @param int $approvalId 承認ID
     * @param string $action アクション（approved, rejected）
     * @param string $comment コメント
     */
    private function sendWorkflowApprovalNotification($requestId, $approvalId, $action, $comment = null)
    {
        // 申請情報を取得
        $request = $this->model->getRequestById($requestId);
        if (!$request) {
            return;
        }

        // 承認情報を取得
        $sql = "SELECT a.*, u.display_name as approver_name 
                FROM workflow_approvals a 
                JOIN users u ON a.approver_id = u.id 
                WHERE a.id = ? LIMIT 1";
        $approval = $this->db->fetch($sql, [$approvalId]);

        if (!$approval) {
            return;
        }

        // 通知タイトルと内容を準備
        $title = '';
        $content = '';

        switch ($action) {
            case 'approved':
                $title = 'ワークフロー申請が承認されました';
                $content = "{$approval['approver_name']}さんが申請を承認しました。\n";
                $content .= "申請タイトル: {$request['title']}\n";
                $content .= "申請番号: {$request['request_number']}";

                if ($comment) {
                    $content .= "\n\nコメント: {$comment}";
                }
                break;

            case 'rejected':
                $title = 'ワークフロー申請が却下されました';
                $content = "{$approval['approver_name']}さんが申請を却下しました。\n";
                $content .= "申請タイトル: {$request['title']}\n";
                $content .= "申請番号: {$request['request_number']}";

                if ($comment) {
                    $content .= "\n\nコメント: {$comment}";
                }
                break;
        }

        // リンク
        $link = "/workflow/view/{$requestId}";

        // 申請者に通知
        $notificationData = [
            'user_id' => $request['requester_id'],
            'type' => 'workflow',
            'title' => $title,
            'content' => $content,
            'link' => $link,
            'reference_id' => $requestId,
            'reference_type' => 'workflow_request'
        ];

        // 通知を送信
        $this->notification->create($notificationData);
    }

    /**
     * 次のステップの承認者に通知を送信
     * 
     * @param int $requestId 申請ID
     * @param int $stepNumber ステップ番号
     * @param int $approverId 承認者ID
     */
    private function sendWorkflowStepNotification($requestId, $stepNumber, $approverId)
    {
        // 申請情報を取得
        $request = $this->model->getRequestById($requestId);
        if (!$request) {
            return;
        }

        // 申請者の情報を取得
        $requester = $this->userModel->getById($request['requester_id']);
        if (!$requester) {
            return;
        }

        // 承認ステップの情報を取得
        $sql = "SELECT * FROM workflow_route_definitions 
                WHERE template_id = ? AND step_number = ? LIMIT 1";
        $step = $this->db->fetch($sql, [$request['template_id'], $stepNumber]);

        if (!$step) {
            return;
        }

        // 通知データ
        $notificationData = [
            'user_id' => $approverId,
            'type' => 'workflow',
            'title' => '承認待ちのワークフロー申請があります',
            'content' => "あなたの承認が必要なワークフロー申請があります。\n" .
                "申請者: {$requester['display_name']}\n" .
                "申請タイトル: {$request['title']}\n" .
                "申請番号: {$request['request_number']}\n" .
                "承認ステップ: {$step['step_name']}",
            'link' => "/workflow/view/{$requestId}",
            'reference_id' => $requestId,
            'reference_type' => 'workflow_request'
        ];

        // 通知を送信
        $this->notification->create($notificationData);
    }

    /**
     * コメント追加の通知を送信
     */
    private function sendCommentNotification($requestId, $commenterId, $comment)
    {
        // 申請情報を取得
        $request = $this->model->getRequestById($requestId);
        if (!$request) {
            return;
        }

        // コメント投稿者の情報を取得
        $commenter = $this->userModel->getById($commenterId);
        if (!$commenter) {
            return;
        }

        // 申請の関係者を取得（申請者と承認者）
        $sql = "SELECT DISTINCT user_id FROM (
                    SELECT requester_id as user_id FROM workflow_requests WHERE id = ?
                    UNION
                    SELECT approver_id as user_id FROM workflow_approvals WHERE request_id = ?
                ) as users";
        $relatedUsers = $this->db->fetchAll($sql, [$requestId, $requestId]);

        if (!$relatedUsers) {
            return;
        }

        // 通知タイトルと内容
        $title = 'ワークフロー申請にコメントが追加されました';
        $content = "{$commenter['display_name']}さんが申請にコメントを追加しました。\n" .
            "申請タイトル: {$request['title']}\n" .
            "申請番号: {$request['request_number']}\n\n" .
            "コメント: {$comment}";

        // リンク
        $link = "/workflow/view/{$requestId}";

        // 関係者に通知（自分以外）
        foreach ($relatedUsers as $user) {
            // 自分自身には通知しない
            if ($user['user_id'] == $commenterId) {
                continue;
            }

            // 通知データ
            $notificationData = [
                'user_id' => $user['user_id'],
                'type' => 'workflow',
                'title' => $title,
                'content' => $content,
                'link' => $link,
                'reference_id' => $requestId,
                'reference_type' => 'workflow_comment'
            ];

            // 通知を送信
            $this->notification->create($notificationData);
        }
    }

    /**
     * ワークフロー完了の通知を送信
     * 
     * @param int $requestId 申請ID
     */
    private function sendWorkflowCompletionNotification($requestId)
    {
        // 申請情報を取得
        $request = $this->model->getRequestById($requestId);
        if (!$request) {
            return;
        }

        // テンプレート情報を取得
        $template = $this->model->getTemplateById($request['template_id']);
        if (!$template) {
            return;
        }

        // 通知データ
        $notificationData = [
            'user_id' => $request['requester_id'],
            'type' => 'workflow',
            'title' => 'ワークフロー申請が承認されました',
            'content' => "あなたの申請「{$request['title']}」({$request['request_number']})がすべての承認ステップを完了し、承認されました。\n" .
                "申請タイプ: {$template['name']}",
            'link' => "/workflow/view/{$requestId}",
            'reference_id' => $requestId,
            'reference_type' => 'workflow_request'
        ];

        // 通知を送信
        $this->notification->create($notificationData);
    }
}

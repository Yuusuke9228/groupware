<?php
// WebDatabaseController.php
namespace Controllers;

use Core\Controller;
use Core\Database;
use Core\Auth;
use Models\WebDatabase;
use Models\WebDatabaseField;
use Models\WebDatabaseRecord;
use Models\User;
use Models\Organization;
use Services\WebDatabaseValidationService;

class WebDatabaseController extends Controller
{
    private $db;
    private $model;
    private $fieldModel;
    private $recordModel;
    private $userModel;
    private $organizationModel;

    public function __construct()
    {
        parent::__construct();
        $this->db = Database::getInstance();
        $this->model = new WebDatabase();
        $this->fieldModel = new WebDatabaseField();
        $this->recordModel = new WebDatabaseRecord();
        $this->userModel = new User();
        $this->organizationModel = new Organization();

        // 認証チェック
        if (!$this->auth->check()) {
            $this->redirect(BASE_PATH . '/login');
        }
    }

    /**
     * データベース一覧ページを表示
     */
    public function index()
    {
        $viewData = [
            'title' => 'WEBデータベース',
            'jsFiles' => ['webdatabase.js']
        ];

        $this->view('webdatabase/index', $viewData);
    }

    /**
     * データベース作成ページを表示
     */
    public function create()
    {
        $viewData = [
            'title' => '新規データベース作成',
            'jsFiles' => ['webdatabase.js']
        ];

        $this->view('webdatabase/create', $viewData);
    }

    /**
     * データベース編集ページを表示
     */
    public function edit($params)
    {
        $id = $params['id'] ?? null;
        if (!$id) {
            $this->redirect(BASE_PATH . '/webdatabase');
        }

        // データベース情報を取得
        $database = $this->model->getById($id);
        if (!$database) {
            $this->redirect(BASE_PATH . '/webdatabase');
        }

        $viewData = [
            'title' => 'データベース編集',
            'database' => $database,
            'jsFiles' => ['webdatabase.js']
        ];

        $this->view('webdatabase/edit', $viewData);
    }

    /**
     * フィールド設定ページを表示
     */
    public function fields($params)
    {
        $id = $params['id'] ?? null;
        if (!$id) {
            $this->redirect(BASE_PATH . '/webdatabase');
        }

        // データベース情報を取得
        $database = $this->model->getById($id);
        if (!$database) {
            $this->redirect(BASE_PATH . '/webdatabase');
        }

        // フィールド一覧を取得
        $fields = $this->fieldModel->getByDatabaseId($id);
        $formLayout = $this->getFormLayoutForDatabase((int)$id, $fields);

        $viewData = [
            'title' => 'フィールド設定',
            'database' => $database,
            'fields' => $fields,
            'formLayout' => $formLayout,
            'jsFiles' => ['webdatabase-field.js']
        ];

        $this->view('webdatabase/fields', $viewData);
    }

    /**
     * レコード一覧ページを表示
     */
    public function records($params)
    {
        $id = $params['id'] ?? null;
        if (!$id) {
            $this->redirect(BASE_PATH . '/webdatabase');
        }

        // データベース情報を取得
        $database = $this->model->getById($id);
        if (!$database) {
            $this->redirect(BASE_PATH . '/webdatabase');
        }

        // フィールド一覧を取得
        $fields = $this->fieldModel->getByDatabaseId($id);

        $userId = (int)$this->auth->id();
        $organizationIds = $this->userModel->getUserOrganizationIds($userId);
        $views = $this->getAccessibleViews((int)$id, $userId, $organizationIds, $this->auth->isAdmin());
        $selectedViewId = isset($_GET['view_id']) ? (int)$_GET['view_id'] : 0;
        $selectedView = null;
        foreach ($views as $view) {
            if ((int)$view['id'] === $selectedViewId) {
                $selectedView = $view;
                break;
            }
        }
        $selectedViewSettings = [];
        if ($selectedView && !empty($selectedView['settings'])) {
            $decoded = json_decode((string)$selectedView['settings'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $selectedViewSettings = $decoded;
            }
        }

        $formLayout = $this->getFormLayoutForDatabase((int)$id, $fields);
        $viewData = [
            'title' => $database['name'] . ' - レコード一覧',
            'database' => $database,
            'fields' => $fields,
            'formLayout' => $formLayout,
            'views' => $views,
            'selectedViewId' => $selectedViewId,
            'selectedViewSettings' => $selectedViewSettings,
            'userOrganizations' => $this->userModel->getUserOrganizations($userId),
            'isAdmin' => $this->auth->isAdmin(),
            'jsFiles' => ['webdatabase-record.js']
        ];

        $this->view('webdatabase/records', $viewData);
    }

    /**
     * レコード作成ページを表示
     */
    public function createRecord($params)
    {
        $id = $params['id'] ?? null;
        if (!$id) {
            $this->redirect(BASE_PATH . '/webdatabase');
        }

        // データベース情報を取得
        $database = $this->model->getById($id);
        if (!$database) {
            $this->redirect(BASE_PATH . '/webdatabase');
        }

        // フィールド一覧を取得
        $fields = $this->fieldModel->getByDatabaseId($id);

        $formLayout = $this->getFormLayoutForDatabase((int)$id, $fields);
        $viewData = [
            'title' => '新規レコード作成',
            'database' => $database,
            'fields' => $fields,
            'formLayout' => $formLayout,
            'recordData' => [],
            'relationData' => [],
            'jsFiles' => ['webdatabase-record.js']
        ];

        $this->view('webdatabase/record_form', $viewData);
    }

    /**
     * レコード編集ページを表示
     */
    public function editRecord($params)
    {
        $databaseId = $params['id'] ?? null;
        $recordId = $params['record_id'] ?? null;

        if (!$databaseId || !$recordId) {
            $this->redirect(BASE_PATH . '/webdatabase');
        }

        // データベース情報を取得
        $database = $this->model->getById($databaseId);
        if (!$database) {
            $this->redirect(BASE_PATH . '/webdatabase');
        }

        // フィールド一覧を取得
        $fields = $this->fieldModel->getByDatabaseId($databaseId);

        // レコード情報を取得
        $record = $this->recordModel->getById($recordId);
        if (!$record) {
            $this->redirect(BASE_PATH . '/webdatabase/records/' . $databaseId);
        }

        // レコードデータを取得
        $recordData = $this->recordModel->getRecordData($recordId);
        $relationData = [];
        foreach ($fields as $field) {
            if ((string)($field['type'] ?? '') !== 'relation') {
                continue;
            }
            $relatedRows = $this->recordModel->getRelatedRecords((int)$recordId, (int)$field['id']);
            foreach ($relatedRows as &$relatedRow) {
                $relatedRow['record_data'] = $this->recordModel->getRecordData((int)$relatedRow['target_record_id']);
            }
            unset($relatedRow);
            $relationData[(int)$field['id']] = $relatedRows;
        }

        $formLayout = $this->getFormLayoutForDatabase((int)$databaseId, $fields);
        $viewData = [
            'title' => 'レコード編集',
            'database' => $database,
            'fields' => $fields,
            'formLayout' => $formLayout,
            'record' => $record,
            'recordData' => $recordData,
            'relationData' => $relationData,
            'jsFiles' => ['webdatabase-record.js']
        ];

        $this->view('webdatabase/record_form', $viewData);
    }

    /**
     * レコード詳細ページを表示
     */
    public function viewRecord($params)
    {
        $databaseId = $params['id'] ?? null;
        $recordId = $params['record_id'] ?? null;

        if (!$databaseId || !$recordId) {
            $this->redirect(BASE_PATH . '/webdatabase');
        }

        // データベース情報を取得
        $database = $this->model->getById($databaseId);
        if (!$database) {
            $this->redirect(BASE_PATH . '/webdatabase');
        }

        // フィールド一覧を取得
        $fields = $this->fieldModel->getByDatabaseId($databaseId);

        // レコード情報を取得
        $record = $this->recordModel->getById($recordId);
        if (!$record) {
            $this->redirect(BASE_PATH . '/webdatabase/records/' . $databaseId);
        }

        // レコードデータを取得
        $recordData = $this->recordModel->getRecordData($recordId);

        $viewData = [
            'title' => 'レコード詳細',
            'database' => $database,
            'fields' => $fields,
            'record' => $record,
            'recordData' => $recordData,
            'jsFiles' => ['webdatabase-record.js']
        ];

        $this->view('webdatabase/record_view', $viewData);
    }

    /**
     * CSVエクスポートページを表示
     */
    public function exportCsv($params)
    {
        $databaseId = $params['id'] ?? null;
        if (!$databaseId) {
            $this->redirect(BASE_PATH . '/webdatabase');
        }

        // データベース情報を取得
        $database = $this->model->getById($databaseId);
        if (!$database) {
            $this->redirect(BASE_PATH . '/webdatabase');
        }

        // フィールド一覧を取得
        $fields = $this->fieldModel->getByDatabaseId($databaseId);

        $viewData = [
            'title' => 'CSVエクスポート',
            'database' => $database,
            'fields' => $fields,
            'jsFiles' => ['webdatabase.js', 'webdatabase-export.js']
        ];

        $this->view('webdatabase/export_csv', $viewData);
    }

    /**
     * CSVインポートページを表示
     */
    public function importCsv($params)
    {
        $databaseId = $params['id'] ?? null;
        if (!$databaseId) {
            $this->redirect(BASE_PATH . '/webdatabase');
        }

        // データベース情報を取得
        $database = $this->model->getById($databaseId);
        if (!$database) {
            $this->redirect(BASE_PATH . '/webdatabase');
        }

        // フィールド一覧を取得
        $fields = $this->fieldModel->getByDatabaseId($databaseId);

        $viewData = [
            'title' => 'CSVインポート',
            'database' => $database,
            'fields' => $fields,
            'jsFiles' => ['webdatabase.js', 'webdatabase-import.js']
        ];

        $this->view('webdatabase/import_csv', $viewData);
    }

    /* API メソッド */

    /**
     * API: データベース一覧を取得
     */
    public function apiGetDatabases($params)
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

        $databases = $this->model->getAll($page, $limit, $search);
        $totalDatabases = $this->model->getCount($search);
        $totalPages = ceil($totalDatabases / $limit);

        return [
            'success' => true,
            'data' => [
                'databases' => $databases,
                'pagination' => [
                    'total' => $totalDatabases,
                    'total_pages' => $totalPages,
                    'current_page' => $page,
                    'limit' => $limit
                ]
            ]
        ];
    }

    /**
     * API: データベースを作成
     */
    public function apiCreateDatabase($params, $data)
    {
        // 認証チェック
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }

        // バリデーション
        if (empty($data['name'])) {
            return ['error' => 'Database name is required', 'code' => 400];
        }

        // 作成者IDを追加
        $data['creator_id'] = $this->auth->id();

        $id = $this->model->create($data);
        if (!$id) {
            return ['error' => 'Failed to create database', 'code' => 500];
        }

        $database = $this->model->getById($id);

        return [
            'success' => true,
            'data' => $database,
            'message' => 'データベースを作成しました',
            'redirect' => BASE_PATH . '/webdatabase/fields/' . $id
        ];
    }

    /**
     * API: データベースを更新
     */
    public function apiUpdateDatabase($params, $data)
    {
        // 認証チェック
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }

        $id = $params['id'] ?? null;
        if (!$id) {
            return ['error' => 'Invalid ID', 'code' => 400];
        }

        // バリデーション
        if (empty($data['name'])) {
            return ['error' => 'Database name is required', 'code' => 400];
        }

        $success = $this->model->update($id, $data);
        if (!$success) {
            return ['error' => 'Failed to update database', 'code' => 500];
        }

        $database = $this->model->getById($id);

        return [
            'success' => true,
            'data' => $database,
            'message' => 'データベースを更新しました',
            'redirect' => BASE_PATH . '/webdatabase'
        ];
    }

    /**
     * API: データベースを削除
     */
    public function apiDeleteDatabase($params)
    {
        // 認証チェック
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }

        $id = $params['id'] ?? null;
        if (!$id) {
            return ['error' => 'Invalid ID', 'code' => 400];
        }

        $success = $this->model->delete($id);
        if (!$success) {
            return ['error' => 'Failed to delete database', 'code' => 500];
        }

        return [
            'success' => true,
            'message' => 'データベースを削除しました',
            'redirect' => BASE_PATH . '/webdatabase'
        ];
    }

    /**
     * API: フィールドを作成
     */
    public function apiCreateField($params, $data)
    {
        // 認証チェック
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }

        // バリデーション
        if (empty($data['name']) || empty($data['type'])) {
            return ['error' => 'Field name and type are required', 'code' => 400];
        }

        if (empty($data['database_id'])) {
            return ['error' => 'Database ID is required', 'code' => 400];
        }

        $database = $this->model->getById($data['database_id']);
        if (!$database) {
            return ['error' => 'Database not found', 'code' => 404];
        }

        try {
            $id = $this->fieldModel->create($data);
        } catch (\Throwable $e) {
            error_log('Failed to create web database field via API: ' . $e->getMessage());
            return ['error' => 'Failed to create field', 'code' => 500];
        }

        if (!$id) {
            return ['error' => 'Failed to create field', 'code' => 500];
        }

        $field = $this->fieldModel->getById($id);

        return [
            'success' => true,
            'data' => $field,
            'message' => 'フィールドを作成しました'
        ];
    }

    /**
     * API: フィールドを更新
     */
    public function apiUpdateField($params, $data)
    {
        // 認証チェック
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }

        $id = $params['id'] ?? null;
        if (!$id) {
            return ['error' => 'Invalid ID', 'code' => 400];
        }

        // バリデーション
        if (empty($data['name']) || empty($data['type'])) {
            return ['error' => 'Field name and type are required', 'code' => 400];
        }

        try {
            $success = $this->fieldModel->update($id, $data);
        } catch (\Throwable $e) {
            error_log('Failed to update web database field via API: ' . $e->getMessage());
            return ['error' => 'Failed to update field', 'code' => 500];
        }

        if (!$success) {
            return ['error' => 'Failed to update field', 'code' => 500];
        }

        $field = $this->fieldModel->getById($id);

        return [
            'success' => true,
            'data' => $field,
            'message' => 'フィールドを更新しました'
        ];
    }

    /**
     * API: フィールドを削除
     */
    public function apiDeleteField($params)
    {
        // 認証チェック
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }

        $id = $params['id'] ?? null;
        if (!$id) {
            return ['error' => 'Invalid ID', 'code' => 400];
        }

        try {
            $success = $this->fieldModel->delete($id);
        } catch (\Throwable $e) {
            error_log('Failed to delete web database field via API: ' . $e->getMessage());
            return ['error' => 'Failed to delete field', 'code' => 500];
        }

        if (!$success) {
            return ['error' => 'Failed to delete field', 'code' => 500];
        }

        return [
            'success' => true,
            'message' => 'フィールドを削除しました'
        ];
    }

    /**
     * API: レコード一覧を取得
     */
    public function apiGetRecords($params)
    {
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }

        $databaseId = isset($params['id']) ? (int)$params['id'] : 0;
        if ($databaseId <= 0) {
            return ['error' => 'Database ID is required', 'code' => 400];
        }

        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
        $page = $page > 0 ? $page : 1;
        $limit = ($limit > 0 && $limit <= 200) ? $limit : 20;

        $search = isset($_GET['search']) ? $_GET['search'] : null;
        $filters = [];
        if (isset($_GET['filter_json']) && !empty($_GET['filter_json'])) {
            $filterJson = $_GET['filter_json'];
            $decodedFilters = json_decode($filterJson, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decodedFilters)) {
                foreach ($decodedFilters as $fieldId => $value) {
                    if (is_numeric($fieldId) && !empty($value)) {
                        $filters[(int)$fieldId] = is_scalar($value) ? (string)$value : '';
                    }
                }
            }
        }

        foreach ($_GET as $key => $value) {
            if (preg_match('/^filter_(\d+)$/', $key, $matches)) {
                $fieldId = (int)$matches[1];
                if (!empty($value)) {
                    $filters[$fieldId] = $value;
                }
            }
        }

        $sort = isset($_GET['sort']) ? $_GET['sort'] : null;
        $order = isset($_GET['order']) ? strtolower($_GET['order']) : 'asc';
        if ($order !== 'asc' && $order !== 'desc') {
            $order = 'asc';
        }

        $fields = $this->fieldModel->getByDatabaseId($databaseId);
        $fieldsById = [];
        foreach ($fields as $field) {
            $fieldsById[(int)$field['id']] = $field;
        }

        $selectedViewId = isset($_GET['view_id']) ? (int)$_GET['view_id'] : 0;
        $selectedViewSettings = [];
        if ($selectedViewId > 0) {
            $userId = (int)$this->auth->id();
            $organizationIds = $this->userModel->getUserOrganizationIds($userId);
            $views = $this->getAccessibleViews($databaseId, $userId, $organizationIds, $this->auth->isAdmin());
            foreach ($views as $view) {
                if ((int)$view['id'] === $selectedViewId) {
                    $decoded = json_decode((string)($view['settings'] ?? '{}'), true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $selectedViewSettings = $decoded;
                    }
                    break;
                }
            }
        }

        if (empty($search) && !empty($selectedViewSettings['search'])) {
            $search = (string)$selectedViewSettings['search'];
        }
        if (empty($filters) && !empty($selectedViewSettings['filters']) && is_array($selectedViewSettings['filters'])) {
            foreach ($selectedViewSettings['filters'] as $fieldId => $value) {
                if (is_numeric($fieldId) && $value !== null && $value !== '') {
                    $filters[(int)$fieldId] = is_scalar($value) ? (string)$value : '';
                }
            }
        }
        if (!$sort && !empty($selectedViewSettings['sort'])) {
            $sort = (string)$selectedViewSettings['sort'];
        }
        if (!isset($_GET['order']) && !empty($selectedViewSettings['order'])) {
            $savedOrder = strtolower((string)$selectedViewSettings['order']);
            $order = in_array($savedOrder, ['asc', 'desc'], true) ? $savedOrder : 'asc';
        }

        $visibleFieldIds = $this->resolveVisibleFieldIds($fields, $selectedViewSettings);
        $records = $this->recordModel->getByDatabaseId($databaseId, $page, $limit, $search, $filters, $sort, $order);
        $totalRecords = $this->recordModel->getCountByDatabaseId($databaseId, $search, $filters);
        $totalPages = ceil($totalRecords / $limit);
        foreach ($records as &$record) {
            $record['field_values'] = $this->buildRecordFieldValues((int)$record['id'], $visibleFieldIds, $fieldsById);
        }
        unset($record);

        return [
            'success' => true,
            'data' => [
                'records' => $records,
                'visible_field_ids' => $visibleFieldIds,
                'view_settings' => $selectedViewSettings,
                'pagination' => [
                    'total' => $totalRecords,
                    'total_pages' => $totalPages,
                    'current_page' => (int)$page,
                    'limit' => (int)$limit
                ]
            ]
        ];
    }


    /**
     * API: レコードを作成
     */
    public function apiCreateRecord($params, $data)
    {
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }

        $databaseId = isset($params['id']) ? (int)$params['id'] : 0;
        if ($databaseId <= 0) {
            return ['error' => 'Database ID is required', 'code' => 400];
        }

        $data['creator_id'] = $this->auth->id();
        $data['database_id'] = $databaseId;
        $fieldValues = isset($data['fields']) && is_array($data['fields']) ? $data['fields'] : (isset($_POST['fields']) && is_array($_POST['fields']) ? $_POST['fields'] : []);
        $childTablePayload = $this->extractChildTablePayload($data);
        if (isset($_FILES) && !empty($_FILES)) {
            $data['files'] = $this->processRecordFiles($_FILES);
        }
        $fileValues = isset($data['files']) && is_array($data['files']) ? $data['files'] : [];
        $fields = $this->fieldModel->getByDatabaseId($databaseId);
        $validationErrors = $this->validateRecordInput($databaseId, $fields, $fieldValues, $fileValues, 0, []);
        if (!empty($validationErrors)) {
            return [
                'error' => '入力内容に不備があります',
                'validation' => $validationErrors,
                'code' => 422
            ];
        }

        $this->db->beginTransaction();
        try {
            $recordId = $this->recordModel->create($data);
            if (!$recordId) {
                throw new \Exception('Failed to create record');
            }

            $this->persistRecordFieldsAndRelations((int)$recordId, $databaseId, $fieldValues, $fileValues, (int)$this->auth->id());
            $this->syncChildTables((int)$recordId, $databaseId, $childTablePayload, (int)$this->auth->id());

            $this->db->commit();

            return [
                'success' => true,
                'data' => ['id' => $recordId],
                'message' => 'レコードを作成しました',
                'redirect' => BASE_PATH . '/webdatabase/records/' . $databaseId
            ];
        } catch (\Exception $e) {
            $this->db->rollBack();
            return ['error' => 'レコード作成に失敗しました: ' . $e->getMessage(), 'code' => 500];
        }
    }

    /**
     * API: レコードを更新
     */
    public function apiUpdateRecord($params, $data)
    {
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }

        $databaseId = isset($params['id']) ? (int)$params['id'] : 0;
        $recordId = isset($params['record_id']) ? (int)$params['record_id'] : 0;

        if ($databaseId <= 0 || $recordId <= 0) {
            return ['error' => 'Invalid IDs', 'code' => 400];
        }

        // レコードを取得して存在チェック
        $record = $this->recordModel->getById($recordId);
        if (!$record || $record['database_id'] != $databaseId) {
            return ['error' => 'Record not found', 'code' => 404];
        }

        $data['updater_id'] = $this->auth->id();
        $fieldValues = isset($data['fields']) && is_array($data['fields']) ? $data['fields'] : (isset($_POST['fields']) && is_array($_POST['fields']) ? $_POST['fields'] : []);
        $childTablePayload = $this->extractChildTablePayload($data);
        if (isset($_FILES) && !empty($_FILES)) {
            $data['files'] = $this->processRecordFiles($_FILES);
        }
        $fileValues = isset($data['files']) && is_array($data['files']) ? $data['files'] : [];
        $fields = $this->fieldModel->getByDatabaseId($databaseId);
        $existingValues = $this->recordModel->getRecordData($recordId);
        $validationErrors = $this->validateRecordInput($databaseId, $fields, $fieldValues, $fileValues, $recordId, $existingValues);
        if (!empty($validationErrors)) {
            return [
                'error' => '入力内容に不備があります',
                'validation' => $validationErrors,
                'code' => 422
            ];
        }

        $this->db->beginTransaction();

        try {
            $success = $this->recordModel->update($recordId, $data);
            if (!$success) {
                throw new \Exception('Failed to update record');
            }

            $this->persistRecordFieldsAndRelations((int)$recordId, $databaseId, $fieldValues, $fileValues, (int)$this->auth->id());
            $this->syncChildTables((int)$recordId, $databaseId, $childTablePayload, (int)$this->auth->id());

            $this->db->commit();

            return [
                'success' => true,
                'message' => 'レコードを更新しました',
                'redirect' => BASE_PATH . '/webdatabase/view/' . $databaseId . '/' . $recordId
            ];
        } catch (\Exception $e) {
            $this->db->rollBack();
            return ['error' => 'レコード更新に失敗しました: ' . $e->getMessage(), 'code' => 500];
        }
    }

    /**
     * API: レコードを削除
     */
    public function apiDeleteRecord($params)
    {
        // 認証チェック
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }

        $databaseId = $params['id'] ?? null;
        $recordId = $params['record_id'] ?? null;

        if (!$databaseId || !$recordId) {
            return ['error' => 'Invalid IDs', 'code' => 400];
        }

        // レコードを取得して存在チェック
        $record = $this->recordModel->getById($recordId);
        if (!$record || $record['database_id'] != $databaseId) {
            return ['error' => 'Record not found', 'code' => 404];
        }

        $success = $this->recordModel->delete($recordId);
        if (!$success) {
            return ['error' => 'Failed to delete record', 'code' => 500];
        }

        return [
            'success' => true,
            'message' => 'レコードを削除しました',
            'redirect' => BASE_PATH . '/webdatabase/records/' . $databaseId
        ];
    }

    /**
     * API: レコードをCSVエクスポート
     */
    public function apiExportCsv($params)
    {
        // 認証チェック
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }

        // パラメータを$_GETから直接取得
        $databaseId = isset($params['id']) ? $params['id'] : null;
        if (!$databaseId) {
            return ['error' => 'Database ID is required', 'code' => 400];
        }

        // データベース情報を取得
        $database = $this->model->getById($databaseId);
        if (!$database) {
            return ['error' => 'Database not found', 'code' => 404];
        }

        // フィールド一覧を取得
        $fields = $this->fieldModel->getByDatabaseId($databaseId);

        // 検索・フィルター条件
        $search = isset($_GET['search']) ? $_GET['search'] : null;

        // フィルター条件の処理
        $filters = [];

        // JSON形式のフィルターがある場合はそれを使用
        if (isset($_GET['filter_json']) && !empty($_GET['filter_json'])) {
            $filterJson = $_GET['filter_json'];
            $decodedFilters = json_decode($filterJson, true);

            // JSONデコード成功時のみ使用
            if (json_last_error() === JSON_ERROR_NONE && is_array($decodedFilters)) {
                foreach ($decodedFilters as $fieldId => $value) {
                    if (is_numeric($fieldId) && !empty($value)) {
                        $filters[$fieldId] = $value;
                    }
                }
            }
        }

        // 個別のfilter_N形式も確認
        foreach ($_GET as $key => $value) {
            if (preg_match('/^filter_(\d+)$/', $key, $matches)) {
                $fieldId = $matches[1];
                if (!empty($value)) {
                    $filters[$fieldId] = $value;
                }
            }
        }

        // エクスポートするフィールドを指定
        $exportFields = [];
        if (isset($_GET['export_fields']) && is_array($_GET['export_fields'])) {
            $exportFields = $_GET['export_fields'];
        }

        // CSVファイル名
        $filename = $database['name'] . '_' . date('Ymd_His') . '.csv';

        // HTTPヘッダー設定
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        // 出力バッファを開始
        ob_start();

        // UTF-8 BOMを出力
        echo "\xEF\xBB\xBF";

        // CSVファイルハンドルを作成
        $output = fopen('php://output', 'w');

        // ヘッダー行を出力
        $headers = ['ID', '作成日時', '作成者'];

        // エクスポートするフィールドのみ含める
        $fieldsToExport = [];
        foreach ($fields as $field) {
            if (empty($exportFields) || in_array($field['id'], $exportFields)) {
                $headers[] = $field['name'];
                $fieldsToExport[] = $field;
            }
        }

        fputcsv($output, $headers);

        // レコードをページングなしで全件取得
        $records = $this->recordModel->getAllByDatabaseId($databaseId, $search, $filters);

        // レコードをCSVに出力
        foreach ($records as $record) {
            // レコードデータを取得
            $recordData = $this->recordModel->getRecordData($record['id']);

            // 行データを作成
            $rowData = [
                $record['id'],
                $record['created_at'],
                $record['creator_name']
            ];

            // フィールドデータを追加
            foreach ($fieldsToExport as $field) {
                $value = isset($recordData[$field['id']]) ? $recordData[$field['id']] : '';

                // 選択肢タイプのフィールドの場合はラベルを取得
                if (in_array($field['type'], ['select', 'radio', 'checkbox']) && !empty($field['options'])) {
                    $options = json_decode($field['options'], true);
                    if (is_array($options)) {
                        foreach ($options as $option) {
                            if ($option['value'] == $value) {
                                $value = $option['label'];
                                break;
                            }
                        }
                    }
                }

                // ユーザーフィールドの場合はユーザー名を取得
                if ($field['type'] == 'user' && !empty($value)) {
                    $user = $this->userModel->getById($value);
                    $value = $user ? $user['display_name'] : $value;
                }

                // 組織フィールドの場合は組織名を取得
                if ($field['type'] == 'organization' && !empty($value)) {
                    $organization = $this->organizationModel->getById($value);
                    $value = $organization ? $organization['name'] : $value;
                }

                // ファイルフィールドの場合はファイル名を取得
                if ($field['type'] == 'file' && !empty($value) && is_array($value)) {
                    $fileNames = [];
                    foreach ($value as $file) {
                        $fileNames[] = $file['name'];
                    }
                    $value = implode(', ', $fileNames);
                }

                $rowData[] = $value;
            }

            fputcsv($output, $rowData);
        }

        // 出力バッファをフラッシュして終了
        ob_end_flush();
        exit;
    }


    /**
     * API: CSVインポート
     */
    public function apiImportCsv($params, $data)
    {
        // 認証チェック
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }

        $databaseId = $params['id'] ?? null;
        if (!$databaseId) {
            return ['error' => 'Database ID is required', 'code' => 400];
        }

        // CSVファイルをチェック
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            return ['error' => 'CSV file is required', 'code' => 400];
        }

        // データベース情報を取得
        $database = $this->model->getById($databaseId);
        if (!$database) {
            return ['error' => 'Database not found', 'code' => 404];
        }

        // フィールド一覧を取得
        $fields = $this->fieldModel->getByDatabaseId($databaseId);
        $fieldMap = [];
        foreach ($fields as $field) {
            $fieldMap[$field['name']] = $field;
        }

        // CSVファイルを開く
        $csvFile = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($csvFile, 'r');
        
        if (!$handle) {
            return ['error' => 'Failed to open CSV file', 'code' => 500];
        }

        // BOMがあれば削除
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            // BOMがなければポインタを先頭に戻す
            rewind($handle);
        }

        // ヘッダー行を取得
        $headers = fgetcsv($handle);
        if (!$headers) {
            return ['error' => 'Invalid CSV format', 'code' => 400];
        }

        // 文字コード変換（必要に応じて）
        $encoding = mb_detect_encoding(implode(',', $headers), ['UTF-8', 'SJIS-win', 'EUC-JP']);
        if ($encoding && $encoding !== 'UTF-8') {
            $headers = array_map(function($value) use ($encoding) {
                return mb_convert_encoding($value, 'UTF-8', $encoding);
            }, $headers);
        }

        // フィールドマッピングの検証
        if (isset($data['field_mapping']) && is_array($data['field_mapping'])) {
            $fieldMapping = $data['field_mapping'];
        } else {
            // ヘッダー名でフィールドを自動マッピング
            $fieldMapping = [];
            foreach ($headers as $index => $header) {
                // ヘッダー名からフィールドを特定
                foreach ($fields as $field) {
                    if (trim($header) === $field['name']) {
                        $fieldMapping[$index] = (string)$field['id'];
                        break;
                    }
                }
            }
        }

        // トランザクション開始
        $this->db->beginTransaction();

        try {
            $importCount = 0;
            $errorCount = 0;
            $errors = [];

            // CSVの各行を処理
            $rowNumber = 1; // ヘッダー行を除くため1から開始
            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;
                
                // 文字コード変換（必要に応じて）
                if ($encoding && $encoding !== 'UTF-8') {
                    $row = array_map(function($value) use ($encoding) {
                        return mb_convert_encoding($value, 'UTF-8', $encoding);
                    }, $row);
                }

                // レコードデータを準備
                $recordData = [
                    'database_id' => $databaseId,
                    'creator_id' => $this->auth->id(),
                    'fields' => []
                ];

                // フィールドデータを設定
                foreach ($fieldMapping as $columnIndex => $fieldId) {
                    if (isset($row[$columnIndex]) && $fieldId) {
                        $recordData['fields'][$fieldId] = $row[$columnIndex];
                    }
                }

                // レコードを作成
                try {
                    $recordId = $this->recordModel->create($recordData);
                    
                    // フィールドデータを保存
                    foreach ($recordData['fields'] as $fieldId => $value) {
                        $this->recordModel->saveFieldData($recordId, $fieldId, $value);
                    }
                    
                    $importCount++;
                } catch (\Exception $e) {
                    $errorCount++;
                    $errors[] = "行 {$rowNumber}: " . $e->getMessage();
                }
            }

            fclose($handle);
            $this->db->commit();

            return [
                'success' => true,
                'message' => "インポートが完了しました。{$importCount}件のレコードをインポートしました。" . ($errorCount > 0 ? "{$errorCount}件のエラーが発生しました。" : ""),
                'data' => [
                    'imported' => $importCount,
                    'errors' => $errorCount,
                    'error_details' => $errors
                ]
            ];
        } catch (\Exception $e) {
            $this->db->rollBack();
            return ['error' => 'インポート中にエラーが発生しました: ' . $e->getMessage(), 'code' => 500];
        }
    }

    /**
     * API: 特定のフィールド情報を取得
     */
    public function apiGetField($params)
    {
        // 認証チェック
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }

        $id = $params['id'] ?? null;
        if (!$id) {
            return ['error' => 'Field ID is required', 'code' => 400];
        }

        $field = $this->fieldModel->getById($id);
        if (!$field) {
            return ['error' => 'Field not found', 'code' => 404];
        }

        return [
            'success' => true,
            'data' => $field
        ];
    }

    /**
     * API: データベースに紐づくフィールド一覧を取得
     */
    public function apiGetDatabaseFields($params)
    {
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }
        $databaseId = isset($params['id']) ? (int)$params['id'] : 0;
        if ($databaseId <= 0) {
            return ['error' => 'Database ID is required', 'code' => 400];
        }

        $database = $this->model->getById($databaseId);
        if (!$database) {
            return ['error' => 'Database not found', 'code' => 404];
        }

        $fields = $this->fieldModel->getByDatabaseId($databaseId);
        return [
            'success' => true,
            'data' => $fields
        ];
    }

    /**
     * API: タイトルフィールド設定
     */
    public function apiSetTitleField($params, $data)
    {
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }
        $fieldId = isset($data['field_id']) ? (int)$data['field_id'] : 0;
        if ($fieldId <= 0) {
            return ['error' => 'field_id is required', 'code' => 400];
        }

        $field = $this->fieldModel->getById($fieldId);
        if (!$field) {
            return ['error' => 'Field not found', 'code' => 404];
        }

        $this->db->execute(
            "UPDATE web_database_fields SET is_title_field = 0, updated_at = CURRENT_TIMESTAMP WHERE database_id = ?",
            [(int)$field['database_id']]
        );
        $this->db->execute(
            "UPDATE web_database_fields SET is_title_field = 1, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
            [$fieldId]
        );

        return ['success' => true, 'message' => 'タイトルフィールドを更新しました'];
    }

    /**
     * API: フォームレイアウト取得
     */
    public function apiGetFormLayout($params)
    {
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }
        $databaseId = isset($params['id']) ? (int)$params['id'] : 0;
        if ($databaseId <= 0) {
            return ['error' => 'Database ID is required', 'code' => 400];
        }

        $fields = $this->fieldModel->getByDatabaseId($databaseId);
        return [
            'success' => true,
            'data' => $this->getFormLayoutForDatabase($databaseId, $fields)
        ];
    }

    /**
     * API: フォームレイアウト保存
     */
    public function apiSaveFieldLayout($params, $data)
    {
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }
        $databaseId = isset($params['id']) ? (int)$params['id'] : 0;
        if ($databaseId <= 0) {
            return ['error' => 'Database ID is required', 'code' => 400];
        }

        $items = $data['items'] ?? [];
        if (!is_array($items)) {
            $decoded = json_decode((string)$items, true);
            $items = is_array($decoded) ? $decoded : [];
        }
        if (empty($items)) {
            return ['error' => 'レイアウト項目が空です', 'code' => 400];
        }

        $saved = $this->saveFormLayoutForDatabase($databaseId, $items, (int)$this->auth->id());
        if (!$saved) {
            return ['error' => 'レイアウト保存に失敗しました', 'code' => 500];
        }

        return [
            'success' => true,
            'message' => 'フォームレイアウトを保存しました'
        ];
    }

    /**
     * API: 集計・グラフ用データ取得
     */
    public function apiGetAnalytics($params)
    {
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }
        $databaseId = isset($params['id']) ? (int)$params['id'] : 0;
        if ($databaseId <= 0) {
            return ['error' => 'Database ID is required', 'code' => 400];
        }

        $groupFieldId = isset($_GET['group_field_id']) ? (int)$_GET['group_field_id'] : 0;
        if ($groupFieldId <= 0) {
            return ['error' => 'group_field_id is required', 'code' => 400];
        }

        $metric = strtolower((string)($_GET['metric'] ?? 'count'));
        if (!in_array($metric, ['count', 'sum', 'avg'], true)) {
            $metric = 'count';
        }
        $metricFieldId = isset($_GET['metric_field_id']) ? (int)$_GET['metric_field_id'] : 0;
        $dateGrain = strtolower((string)($_GET['date_grain'] ?? 'none'));
        if (!in_array($dateGrain, ['none', 'day', 'month'], true)) {
            $dateGrain = 'none';
        }

        $filters = [];
        if (isset($_GET['filter_json']) && !empty($_GET['filter_json'])) {
            $decoded = json_decode((string)$_GET['filter_json'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                foreach ($decoded as $fieldId => $value) {
                    if (is_numeric($fieldId) && $value !== null && $value !== '') {
                        $filters[(int)$fieldId] = (string)$value;
                    }
                }
            }
        }

        $result = $this->buildAnalyticsDataset($databaseId, $groupFieldId, $metric, $metricFieldId, $dateGrain, $filters);
        if (!$result['success']) {
            return ['error' => $result['error'], 'code' => 400];
        }

        return [
            'success' => true,
            'data' => $result['data']
        ];
    }

    /**
     * レコードファイルの処理
     */
    private function processRecordFiles($files)
    {
        $processedFiles = [];
        $uploadDir = realpath(__DIR__ . '/../public/uploads/webdatabase/');

        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        foreach ($files as $fieldId => $fileInfo) {
            // フィールドIDの形式をチェック（数値かどうか）
            if (!is_numeric($fieldId)) {
                continue;
            }

            if (is_string($fileInfo['name'])) {
                // 単一ファイル
                $fileName = $fileInfo['name'];
                $tmpName = $fileInfo['tmp_name'];
                $fileSize = $fileInfo['size'];
                $fileType = $fileInfo['type'];
                $fileError = $fileInfo['error'];

                if (!empty($fileName) && $fileError === 0 && is_uploaded_file($tmpName)) {
                    $safeName = $this->sanitizeFileName($fileName);
                    $uniqueName = uniqid() . '_' . $safeName;
                    $filePath = $uploadDir . '/' . $uniqueName;

                    if (move_uploaded_file($tmpName, $filePath)) {
                        $processedFiles[$fieldId] = [
                            'name' => $fileName,
                            'path' => 'uploads/webdatabase/' . $uniqueName,
                            'size' => $fileSize,
                            'type' => $fileType
                        ];
                    }
                }
            } elseif (is_array($fileInfo['name'])) {
                // 複数ファイル
                $processedFiles[$fieldId] = [];

                for ($i = 0; $i < count($fileInfo['name']); $i++) {
                    $fileName = $fileInfo['name'][$i];
                    $tmpName = $fileInfo['tmp_name'][$i];
                    $fileSize = $fileInfo['size'][$i];
                    $fileType = $fileInfo['type'][$i];
                    $fileError = $fileInfo['error'][$i];

                    if (!empty($fileName) && $fileError === 0 && is_uploaded_file($tmpName)) {
                        $safeName = $this->sanitizeFileName($fileName);
                        $uniqueName = uniqid() . '_' . $safeName;
                        $filePath = $uploadDir . '/' . $uniqueName;

                        if (move_uploaded_file($tmpName, $filePath)) {
                            $processedFiles[$fieldId][] = [
                                'name' => $fileName,
                                'path' => 'uploads/webdatabase/' . $uniqueName,
                                'size' => $fileSize,
                                'type' => $fileType
                            ];
                        }
                    }
                }
            }
        }

        return $processedFiles;
    }

    /**
     * ファイル名をサニタイズ
     */
    private function sanitizeFileName($filename)
    {
        $pathInfo = pathinfo($filename);
        $extension = isset($pathInfo['extension']) ? '.' . $pathInfo['extension'] : '';
        $basename = $pathInfo['filename'];

        // 非ASCII文字を維持しながら危険な文字を除去
        $basename = preg_replace('/[^\p{L}\p{N}_.-]/u', '_', $basename);

        if (empty($basename)) {
            $basename = 'file';
        }

        return $basename . $extension;
    }

    private function getFormLayoutForDatabase($databaseId, array $fields)
    {
        $items = [];
        foreach ($fields as $field) {
            $items[] = [
                'field_id' => (int)$field['id'],
                'sort_order' => (int)$field['sort_order'],
                'section' => '基本情報',
                'hidden' => false,
                'child_table' => false,
                'child_summary_field_id' => null
            ];
        }

        usort($items, static function ($a, $b) {
            return ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0);
        });

        if (!$this->hasTable('web_database_form_layouts')) {
            return ['items' => $items];
        }

        $row = $this->db->fetch(
            "SELECT settings FROM web_database_form_layouts WHERE database_id = ? ORDER BY is_default DESC, updated_at DESC, id DESC LIMIT 1",
            [$databaseId]
        );
        if (!$row || empty($row['settings'])) {
            return ['items' => $items];
        }

        $decoded = json_decode((string)$row['settings'], true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            return ['items' => $items];
        }
        $savedItems = isset($decoded['items']) && is_array($decoded['items']) ? $decoded['items'] : [];
        if (empty($savedItems)) {
            return ['items' => $items];
        }

        $savedByFieldId = [];
        foreach ($savedItems as $saved) {
            $fieldId = isset($saved['field_id']) ? (int)$saved['field_id'] : 0;
            if ($fieldId <= 0) {
                continue;
            }
            $savedByFieldId[$fieldId] = [
                'field_id' => $fieldId,
                'sort_order' => isset($saved['sort_order']) ? (int)$saved['sort_order'] : 0,
                'section' => isset($saved['section']) ? trim((string)$saved['section']) : '基本情報',
                'hidden' => !empty($saved['hidden']),
                'child_table' => !empty($saved['child_table']),
                'child_summary_field_id' => isset($saved['child_summary_field_id']) ? (int)$saved['child_summary_field_id'] : null,
                'relation_filter_field_id' => isset($saved['relation_filter_field_id']) ? (int)$saved['relation_filter_field_id'] : null
            ];
        }

        $merged = [];
        foreach ($items as $defaultItem) {
            $fieldId = (int)$defaultItem['field_id'];
            if (isset($savedByFieldId[$fieldId])) {
                $merged[] = array_merge($defaultItem, $savedByFieldId[$fieldId]);
            } else {
                $merged[] = $defaultItem;
            }
        }

        usort($merged, static function ($a, $b) {
            return ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0);
        });

        return ['items' => $merged];
    }

    private function saveFormLayoutForDatabase($databaseId, array $items, $creatorId)
    {
        if (!$this->hasTable('web_database_form_layouts')) {
            $this->ensureFormLayoutTable();
        }
        if (!$this->hasTable('web_database_form_layouts')) {
            return false;
        }

        $fields = $this->fieldModel->getByDatabaseId($databaseId);
        $validFieldIds = [];
        foreach ($fields as $field) {
            $validFieldIds[(int)$field['id']] = true;
        }

        $normalized = [];
        $sortOrder = 1;
        foreach ($items as $item) {
            $fieldId = isset($item['field_id']) ? (int)$item['field_id'] : 0;
            if ($fieldId <= 0 || !isset($validFieldIds[$fieldId])) {
                continue;
            }

            $relationFilterFieldId = isset($item['relation_filter_field_id']) ? (int)$item['relation_filter_field_id'] : 0;
            if ($relationFilterFieldId > 0 && !isset($validFieldIds[$relationFilterFieldId])) {
                $relationFilterFieldId = 0;
            }

            $normalized[] = [
                'field_id' => $fieldId,
                'sort_order' => $sortOrder++,
                'section' => trim((string)($item['section'] ?? '基本情報')) ?: '基本情報',
                'hidden' => !empty($item['hidden']),
                'child_table' => !empty($item['child_table']),
                'child_summary_field_id' => isset($item['child_summary_field_id']) && (int)$item['child_summary_field_id'] > 0 ? (int)$item['child_summary_field_id'] : null,
                'relation_filter_field_id' => $relationFilterFieldId > 0 ? $relationFilterFieldId : null
            ];

            $required = !empty($item['required']) ? 1 : 0;
            $this->db->execute(
                "UPDATE web_database_fields SET sort_order = ?, required = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND database_id = ?",
                [$sortOrder - 1, $required, $fieldId, $databaseId]
            );
        }

        if (empty($normalized)) {
            return false;
        }

        $settings = json_encode(['items' => $normalized], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($settings === false) {
            return false;
        }

        $existing = $this->db->fetch(
            "SELECT id FROM web_database_form_layouts WHERE database_id = ? AND name = 'default' LIMIT 1",
            [$databaseId]
        );

        if ($existing) {
            return (bool)$this->db->execute(
                "UPDATE web_database_form_layouts SET settings = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
                [$settings, (int)$existing['id']]
            );
        }

        return (bool)$this->db->execute(
            "INSERT INTO web_database_form_layouts (database_id, name, settings, is_default, creator_id, created_at, updated_at)
             VALUES (?, 'default', ?, 1, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)",
            [$databaseId, $settings, $creatorId]
        );
    }

    private function ensureFormLayoutTable()
    {
        $this->db->execute(
            "CREATE TABLE IF NOT EXISTS web_database_form_layouts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                database_id INT NOT NULL,
                name VARCHAR(100) NOT NULL DEFAULT 'default',
                settings LONGTEXT NOT NULL,
                is_default BOOLEAN NOT NULL DEFAULT 1,
                creator_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_web_database_form_layouts (database_id, name),
                INDEX idx_web_database_form_layouts_default (database_id, is_default),
                CONSTRAINT fk_web_database_form_layouts_database FOREIGN KEY (database_id) REFERENCES web_databases(id) ON DELETE CASCADE,
                CONSTRAINT fk_web_database_form_layouts_creator FOREIGN KEY (creator_id) REFERENCES users(id) ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
    }

    private function resolveVisibleFieldIds(array $fields, array $viewSettings)
    {
        $fieldIds = [];
        foreach ($fields as $field) {
            $fieldIds[] = (int)$field['id'];
        }

        $visible = [];
        if (!empty($viewSettings['visible_fields']) && is_array($viewSettings['visible_fields'])) {
            foreach ($viewSettings['visible_fields'] as $fieldId) {
                $fid = (int)$fieldId;
                if ($fid > 0 && in_array($fid, $fieldIds, true)) {
                    $visible[] = $fid;
                }
            }
        }
        if (!empty($visible)) {
            return array_values(array_unique($visible));
        }

        $fallback = [];
        foreach ($fields as $field) {
            if (!empty($field['is_title_field'])) {
                continue;
            }
            $fallback[] = (int)$field['id'];
            if (count($fallback) >= 5) {
                break;
            }
        }
        if (empty($fallback)) {
            foreach ($fields as $field) {
                $fallback[] = (int)$field['id'];
                if (count($fallback) >= 5) {
                    break;
                }
            }
        }
        return $fallback;
    }

    private function buildRecordFieldValues($recordId, array $visibleFieldIds, array $fieldsById)
    {
        $recordData = $this->recordModel->getRecordData($recordId);
        $values = [];
        foreach ($visibleFieldIds as $fieldId) {
            $fieldId = (int)$fieldId;
            if ($fieldId <= 0 || !isset($fieldsById[$fieldId])) {
                continue;
            }
            $field = $fieldsById[$fieldId];
            $raw = $recordData[$fieldId] ?? null;
            $values[$fieldId] = $this->formatFieldValueForDisplay($recordId, $field, $raw);
        }
        return $values;
    }

    private function formatFieldValueForDisplay($recordId, array $field, $rawValue)
    {
        if ($rawValue === null || $rawValue === '') {
            return '';
        }

        $type = (string)($field['type'] ?? 'text');
        if ($type === 'relation') {
            $related = $this->recordModel->getRelatedRecords((int)$recordId, (int)$field['id']);
            $titles = [];
            foreach ($related as $item) {
                $titles[] = (string)($item['title'] ?? ('ID:' . (int)$item['target_record_id']));
            }
            return implode(', ', $titles);
        }

        if (in_array($type, ['select', 'radio', 'checkbox'], true) && !empty($field['options'])) {
            $decoded = json_decode((string)$field['options'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $values = $type === 'checkbox' ? explode(',', (string)$rawValue) : [(string)$rawValue];
                $labels = [];
                foreach ($decoded as $option) {
                    $ov = (string)($option['value'] ?? '');
                    if (in_array($ov, $values, true)) {
                        $labels[] = (string)($option['label'] ?? $ov);
                    }
                }
                if (!empty($labels)) {
                    return implode(', ', $labels);
                }
            }
        }

        if ($type === 'user') {
            static $userCache = [];
            $userId = (int)$rawValue;
            if ($userId > 0) {
                if (!array_key_exists($userId, $userCache)) {
                    $user = $this->userModel->getById($userId);
                    $userCache[$userId] = $user ? (string)$user['display_name'] : (string)$rawValue;
                }
                return $userCache[$userId];
            }
        }

        if ($type === 'organization') {
            static $orgCache = [];
            $orgId = (int)$rawValue;
            if ($orgId > 0) {
                if (!array_key_exists($orgId, $orgCache)) {
                    $org = $this->organizationModel->getById($orgId);
                    $orgCache[$orgId] = $org ? (string)$org['name'] : (string)$rawValue;
                }
                return $orgCache[$orgId];
            }
        }

        if ($type === 'file' && is_array($rawValue)) {
            $files = isset($rawValue[0]) ? $rawValue : [$rawValue];
            $names = [];
            foreach ($files as $file) {
                if (is_array($file) && !empty($file['name'])) {
                    $names[] = (string)$file['name'];
                }
            }
            return implode(', ', $names);
        }

        if ($type === 'currency') {
            return number_format((float)$rawValue);
        }

        if ($type === 'percent') {
            return rtrim(rtrim((string)$rawValue, '0'), '.') . '%';
        }

        return is_scalar($rawValue) ? (string)$rawValue : json_encode($rawValue, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function extractChildTablePayload(array $data)
    {
        $raw = $data['child_tables'] ?? ($_POST['child_tables'] ?? []);
        if (!is_array($raw)) {
            return [];
        }

        $result = [];
        foreach ($raw as $fieldId => $rows) {
            if (!is_numeric($fieldId)) {
                continue;
            }
            if (is_string($rows)) {
                $decoded = json_decode($rows, true);
                $rows = (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : [];
            }
            if (!is_array($rows)) {
                $rows = [];
            }
            $result[(int)$fieldId] = $rows;
        }
        return $result;
    }

    private function validateRecordInput(
        $databaseId,
        array $fields,
        array $fieldValues,
        array $fileValues,
        $recordId = 0,
        array $existingValues = []
    ) {
        $databaseId = (int)$databaseId;
        $recordId = (int)$recordId;

        $uniqueChecker = function (int $fieldId, string $value) use ($databaseId, $recordId): bool {
            $params = [$databaseId, $fieldId, $value];
            $sql = "SELECT d.id
                    FROM web_database_record_data d
                    INNER JOIN web_database_records r ON r.id = d.record_id
                    WHERE r.database_id = ? AND d.field_id = ? AND d.value = ?";
            if ($recordId > 0) {
                $sql .= " AND r.id <> ?";
                $params[] = $recordId;
            }
            $sql .= " LIMIT 1";
            $dup = $this->db->fetch($sql, $params);
            return !empty($dup);
        };

        $errors = WebDatabaseValidationService::validateRecordFields(
            $fields,
            $fieldValues,
            $fileValues,
            $existingValues,
            $uniqueChecker
        );

        foreach ($fields as $field) {
            $fieldId = (int)($field['id'] ?? 0);
            if ($fieldId <= 0 || (string)($field['type'] ?? '') !== 'relation') {
                continue;
            }

            $rawValue = $fieldValues[$fieldId] ?? null;
            $targetIds = [];
            if (is_array($rawValue)) {
                foreach ($rawValue as $targetId) {
                    $targetId = (int)$targetId;
                    if ($targetId > 0) {
                        $targetIds[] = $targetId;
                    }
                }
            } elseif ($rawValue !== null && $rawValue !== '') {
                $targetId = (int)$rawValue;
                if ($targetId > 0) {
                    $targetIds[] = $targetId;
                }
            }
            $targetIds = array_values(array_unique($targetIds));
            if (empty($targetIds)) {
                continue;
            }

            if (($field['relation_type'] ?? '') === 'one_to_one' && count($targetIds) > 1) {
                $errors['fields.' . $fieldId] = '1対1リレーションでは1件のみ選択できます';
                continue;
            }

            $targetDbId = (int)($field['relation_database_id'] ?? 0);
            if ($targetDbId <= 0) {
                $errors['fields.' . $fieldId] = 'リレーション設定が不正です';
                continue;
            }

            $placeholders = implode(',', array_fill(0, count($targetIds), '?'));
            $params = array_merge([$targetDbId], $targetIds);
            $row = $this->db->fetch(
                "SELECT COUNT(*) AS cnt FROM web_database_records WHERE database_id = ? AND id IN ({$placeholders})",
                $params
            );
            $cnt = isset($row['cnt']) ? (int)$row['cnt'] : 0;
            if ($cnt !== count($targetIds)) {
                $errors['fields.' . $fieldId] = '参照先レコードの一部が存在しません';
            }
        }

        return $errors;
    }

    private function persistRecordFieldsAndRelations($recordId, $databaseId, array $fieldValues, array $fileValues, $actorId)
    {
        $fields = $this->fieldModel->getByDatabaseId($databaseId);
        $fieldsById = [];
        foreach ($fields as $field) {
            $fieldsById[(int)$field['id']] = $field;
        }

        foreach ($fieldsById as $fieldId => $field) {
            $type = (string)($field['type'] ?? 'text');
            $value = $fieldValues[$fieldId] ?? null;

            if ($type === 'relation') {
                $targetIds = [];
                if (is_array($value)) {
                    foreach ($value as $targetId) {
                        $tid = (int)$targetId;
                        if ($tid > 0) {
                            $targetIds[] = $tid;
                        }
                    }
                } elseif ($value !== null && $value !== '') {
                    $tid = (int)$value;
                    if ($tid > 0) {
                        $targetIds[] = $tid;
                    }
                }
                $targetIds = array_values(array_unique($targetIds));
                if (!empty($field['relation_database_id'])) {
                    $this->recordModel->saveRelations(
                        (int)$recordId,
                        (int)$fieldId,
                        $targetIds,
                        (int)$field['relation_database_id']
                    );
                }
                $this->recordModel->saveFieldData((int)$recordId, (int)$fieldId, implode(',', $targetIds));
                continue;
            }

            if ($type === 'file') {
                if (array_key_exists($fieldId, $fileValues)) {
                    $this->recordModel->saveFieldData((int)$recordId, (int)$fieldId, '', $fileValues[$fieldId]);
                }
                continue;
            }

            if ($type === 'checkbox' && is_array($value)) {
                $value = implode(',', array_map('strval', $value));
            } elseif (is_array($value)) {
                $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $value = $encoded === false ? '' : $encoded;
            }

            if ($type === 'auto_number' && ($value === null || $value === '')) {
                $value = 'R' . str_pad((string)$recordId, 8, '0', STR_PAD_LEFT);
            }

            $this->recordModel->saveFieldData((int)$recordId, (int)$fieldId, (string)($value ?? ''));
        }

        foreach ($fieldsById as $fieldId => $field) {
            if ((string)$field['type'] === 'lookup'
                && !empty($field['lookup_relation_field_id'])
                && !empty($field['lookup_target_field_id'])) {
                $lookupValue = $this->recordModel->getLookupValue(
                    (int)$recordId,
                    (int)$field['lookup_relation_field_id'],
                    (int)$field['lookup_target_field_id']
                );
                $this->recordModel->saveFieldData((int)$recordId, (int)$fieldId, (string)($lookupValue ?? ''));
            }
        }
    }

    private function syncChildTables($parentRecordId, $databaseId, array $childTablePayload, $actorId)
    {
        if (empty($childTablePayload)) {
            return;
        }

        $parentFields = $this->fieldModel->getByDatabaseId($databaseId);
        $parentFieldsById = [];
        foreach ($parentFields as $field) {
            $parentFieldsById[(int)$field['id']] = $field;
        }

        $layout = $this->getFormLayoutForDatabase((int)$databaseId, $parentFields);
        $layoutByFieldId = [];
        foreach (($layout['items'] ?? []) as $item) {
            $layoutByFieldId[(int)$item['field_id']] = $item;
        }

        foreach ($childTablePayload as $relationFieldId => $rows) {
            $relationFieldId = (int)$relationFieldId;
            if ($relationFieldId <= 0 || !isset($parentFieldsById[$relationFieldId])) {
                continue;
            }

            $relationField = $parentFieldsById[$relationFieldId];
            if ((string)$relationField['type'] !== 'relation' || empty($relationField['relation_database_id'])) {
                continue;
            }

            $layoutItem = $layoutByFieldId[$relationFieldId] ?? [];
            if (empty($layoutItem['child_table'])) {
                continue;
            }

            $targetDatabaseId = (int)$relationField['relation_database_id'];
            $targetIds = [];
            $rowOrder = 0;
            foreach ((array)$rows as $row) {
                if (!is_array($row)) {
                    continue;
                }
                if (!empty($row['_delete'])) {
                    continue;
                }

                $childRecordId = isset($row['record_id']) ? (int)$row['record_id'] : 0;
                if ($childRecordId <= 0) {
                    $childRecordId = (int)$this->recordModel->create([
                        'database_id' => $targetDatabaseId,
                        'creator_id' => $actorId
                    ]);
                } else {
                    $existing = $this->recordModel->getById($childRecordId);
                    if (!$existing || (int)$existing['database_id'] !== $targetDatabaseId) {
                        continue;
                    }
                    $this->recordModel->update($childRecordId, ['updater_id' => $actorId]);
                }

                if ($childRecordId <= 0) {
                    continue;
                }

                $childFields = isset($row['fields']) && is_array($row['fields']) ? $row['fields'] : [];
                $this->persistRecordFieldsAndRelations($childRecordId, $targetDatabaseId, $childFields, [], $actorId);

                $targetIds[] = $childRecordId;
                $rowOrder++;
            }

            $this->recordModel->saveRelations(
                (int)$parentRecordId,
                $relationFieldId,
                $targetIds,
                $targetDatabaseId
            );
        }
    }

    private function buildAnalyticsDataset($databaseId, $groupFieldId, $metric, $metricFieldId, $dateGrain, array $filters = [])
    {
        $fields = $this->fieldModel->getByDatabaseId($databaseId);
        $fieldsById = [];
        foreach ($fields as $field) {
            $fieldsById[(int)$field['id']] = $field;
        }
        if (!isset($fieldsById[$groupFieldId])) {
            return ['success' => false, 'error' => 'グループ化フィールドが存在しません'];
        }
        if (in_array($metric, ['sum', 'avg'], true) && $metricFieldId > 0 && !isset($fieldsById[$metricFieldId])) {
            return ['success' => false, 'error' => '集計対象フィールドが存在しません'];
        }

        $records = $this->db->fetchAll("SELECT id FROM web_database_records WHERE database_id = ?", [$databaseId]);
        if (empty($records)) {
            return ['success' => true, 'data' => ['rows' => [], 'labels' => [], 'values' => []]];
        }
        $recordIds = array_map(static function ($row) {
            return (int)$row['id'];
        }, $records);
        $placeholders = implode(',', array_fill(0, count($recordIds), '?'));
        $recordDataRows = $this->db->fetchAll(
            "SELECT record_id, field_id, value FROM web_database_record_data WHERE record_id IN ({$placeholders})",
            $recordIds
        );

        $recordData = [];
        foreach ($recordDataRows as $row) {
            $rid = (int)$row['record_id'];
            $fid = (int)$row['field_id'];
            $recordData[$rid][$fid] = $row['value'];
        }

        $groupField = $fieldsById[$groupFieldId];
        $relationMap = [];
        $relationFieldIds = [];
        if ((string)$groupField['type'] === 'relation') {
            $relationFieldIds[] = $groupFieldId;
        }
        foreach ($filters as $filterFieldId => $_value) {
            $filterFieldId = (int)$filterFieldId;
            if ($filterFieldId > 0 && isset($fieldsById[$filterFieldId]) && (string)$fieldsById[$filterFieldId]['type'] === 'relation') {
                $relationFieldIds[] = $filterFieldId;
            }
        }
        $relationFieldIds = array_values(array_unique($relationFieldIds));
        if (!empty($relationFieldIds)) {
            $relationFieldPlaceholders = implode(',', array_fill(0, count($relationFieldIds), '?'));
            $params = array_merge($relationFieldIds, $recordIds);
            $relationRows = $this->db->fetchAll(
                "SELECT source_field_id, source_record_id, target_record_id, target_database_id
                 FROM web_database_relations
                 WHERE source_field_id IN ({$relationFieldPlaceholders})
                   AND source_record_id IN ({$placeholders})
                 ORDER BY sort_order ASC, id ASC",
                $params
            );
            foreach ($relationRows as $row) {
                $fieldId = (int)$row['source_field_id'];
                $sourceId = (int)$row['source_record_id'];
                if (!isset($relationMap[$fieldId])) {
                    $relationMap[$fieldId] = [];
                }
                if (!isset($relationMap[$fieldId][$sourceId])) {
                    $relationMap[$fieldId][$sourceId] = [];
                }
                $relationMap[$fieldId][$sourceId][] = [
                    'target_record_id' => (int)$row['target_record_id'],
                    'target_database_id' => (int)$row['target_database_id']
                ];
            }
        }

        $buckets = [];
        foreach ($recordIds as $recordId) {
            if (!$this->passesAnalyticsFilters($recordId, $filters, $fieldsById, $recordData, $relationMap)) {
                continue;
            }

            $groupKeys = $this->extractAnalyticsGroupKeys(
                $recordId,
                $groupField,
                $recordData[$recordId] ?? [],
                $relationMap[$groupFieldId][$recordId] ?? [],
                $dateGrain
            );
            if (empty($groupKeys)) {
                $groupKeys = ['未設定'];
            }

            $metricValue = 1.0;
            if (in_array($metric, ['sum', 'avg'], true)) {
                $rawMetric = ($recordData[$recordId][$metricFieldId] ?? '0');
                $metricValue = is_numeric($rawMetric) ? (float)$rawMetric : 0.0;
            }

            foreach ($groupKeys as $groupKey) {
                if (!isset($buckets[$groupKey])) {
                    $buckets[$groupKey] = ['count' => 0, 'sum' => 0.0];
                }
                $buckets[$groupKey]['count']++;
                if ($metric !== 'count') {
                    $buckets[$groupKey]['sum'] += $metricValue;
                }
            }
        }

        $rows = [];
        foreach ($buckets as $label => $bucket) {
            $value = $bucket['count'];
            if ($metric === 'sum') {
                $value = $bucket['sum'];
            } elseif ($metric === 'avg') {
                $value = $bucket['count'] > 0 ? $bucket['sum'] / $bucket['count'] : 0;
            }
            $rows[] = [
                'label' => (string)$label,
                'count' => (int)$bucket['count'],
                'value' => round((float)$value, 2)
            ];
        }

        usort($rows, static function ($a, $b) {
            return $b['value'] <=> $a['value'];
        });

        return [
            'success' => true,
            'data' => [
                'rows' => $rows,
                'labels' => array_values(array_map(static function ($row) {
                    return $row['label'];
                }, $rows)),
                'values' => array_values(array_map(static function ($row) {
                    return $row['value'];
                }, $rows))
            ]
        ];
    }

    private function passesAnalyticsFilters($recordId, array $filters, array $fieldsById, array $recordData, array $relationMap)
    {
        if (empty($filters)) {
            return true;
        }
        foreach ($filters as $fieldId => $expected) {
            $fieldId = (int)$fieldId;
            if ($fieldId <= 0 || !isset($fieldsById[$fieldId])) {
                continue;
            }
            $field = $fieldsById[$fieldId];
            $expectedText = trim((string)$expected);
            if ($expectedText === '') {
                continue;
            }

            if ((string)$field['type'] === 'relation') {
                $hit = false;
                foreach (($relationMap[$fieldId][$recordId] ?? []) as $relation) {
                    if ((string)$relation['target_record_id'] === $expectedText) {
                        $hit = true;
                        break;
                    }
                }
                if (!$hit) {
                    return false;
                }
                continue;
            }

            $actual = (string)($recordData[$recordId][$fieldId] ?? '');
            if ($actual === '' || mb_stripos($actual, $expectedText) === false) {
                return false;
            }
        }
        return true;
    }

    private function extractAnalyticsGroupKeys($recordId, array $groupField, array $recordData, array $relationRows, $dateGrain)
    {
        $type = (string)($groupField['type'] ?? 'text');
        if ($type === 'relation') {
            $keys = [];
            foreach ($relationRows as $relation) {
                $title = $this->resolveRecordTitle((int)$relation['target_record_id'], (int)$relation['target_database_id']);
                $keys[] = $title ?: ('ID:' . (int)$relation['target_record_id']);
            }
            return array_values(array_unique($keys));
        }

        $raw = (string)($recordData[(int)$groupField['id']] ?? '');
        if ($raw === '') {
            return [];
        }

        if (in_array($type, ['date', 'datetime'], true)) {
            $ts = strtotime($raw);
            if ($ts !== false) {
                if ($dateGrain === 'month') {
                    return [date('Y-m', $ts)];
                }
                if ($dateGrain === 'day') {
                    return [date('Y-m-d', $ts)];
                }
            }
        }
        return [$raw];
    }

    private function resolveRecordTitle($recordId, $databaseId)
    {
        static $titleCache = [];
        $cacheKey = $databaseId . ':' . $recordId;
        if (array_key_exists($cacheKey, $titleCache)) {
            return $titleCache[$cacheKey];
        }

        $titleFields = $this->db->fetchAll(
            "SELECT id FROM web_database_fields WHERE database_id = ? AND is_title_field = 1 ORDER BY sort_order ASC, id ASC",
            [$databaseId]
        );
        if (empty($titleFields)) {
            $titleCache[$cacheKey] = 'ID:' . $recordId;
            return $titleCache[$cacheKey];
        }

        $parts = [];
        foreach ($titleFields as $titleField) {
            $row = $this->db->fetch(
                "SELECT value FROM web_database_record_data WHERE record_id = ? AND field_id = ? LIMIT 1",
                [$recordId, (int)$titleField['id']]
            );
            if ($row && $row['value'] !== null && $row['value'] !== '') {
                $parts[] = (string)$row['value'];
            }
        }
        $titleCache[$cacheKey] = !empty($parts) ? implode(' - ', $parts) : ('ID:' . $recordId);
        return $titleCache[$cacheKey];
    }

    // ========================================
    // リレーション API
    // ========================================

    /**
     * API: リレーション先レコード一覧を取得（選択用）
     */
    public function apiGetRelationTargets($params)
    {
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }

        $targetDbId = $params['id'] ?? $_GET['database_id'] ?? null;
        $search = $_GET['search'] ?? null;
        $filterFieldId = isset($_GET['filter_field_id']) ? (int)$_GET['filter_field_id'] : 0;
        $filterValue = isset($_GET['filter_value']) ? trim((string)$_GET['filter_value']) : '';

        if (!$targetDbId) {
            return ['error' => 'Target database ID is required', 'code' => 400];
        }

        $records = $this->recordModel->getRelationTargetRecords((int)$targetDbId, $search);
        if ($filterFieldId > 0 && $filterValue !== '') {
            $records = array_values(array_filter($records, function ($record) use ($filterFieldId, $filterValue) {
                $data = $this->recordModel->getRecordData((int)$record['id']);
                $raw = isset($data[$filterFieldId]) ? (string)$data[$filterFieldId] : '';
                return $raw !== '' && mb_stripos($raw, $filterValue) !== false;
            }));
        }

        return [
            'success' => true,
            'data' => $records
        ];
    }

    /**
     * API: レコードのリレーション先を取得
     */
    public function apiGetRelatedRecords($params)
    {
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }

        $recordId = $params['record_id'] ?? null;
        $fieldId = $params['field_id'] ?? $_GET['field_id'] ?? null;

        if (!$recordId || !$fieldId) {
            return ['error' => 'Record ID and Field ID are required', 'code' => 400];
        }

        $related = $this->recordModel->getRelatedRecords((int)$recordId, (int)$fieldId);

        return [
            'success' => true,
            'data' => $related
        ];
    }

    /**
     * API: リレーションを保存
     */
    public function apiSaveRelations($params, $data)
    {
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }

        $recordId = $params['record_id'] ?? $data['record_id'] ?? null;
        $fieldId = $data['field_id'] ?? null;
        $targetIds = $data['target_record_ids'] ?? [];
        $targetDbId = $data['target_database_id'] ?? null;

        if (!$recordId || !$fieldId || !$targetDbId) {
            return ['error' => 'Missing required parameters', 'code' => 400];
        }

        $this->recordModel->saveRelations(
            (int)$recordId,
            (int)$fieldId,
            array_map('intval', (array)$targetIds),
            (int)$targetDbId
        );

        return [
            'success' => true,
            'message' => 'リレーションを保存しました'
        ];
    }

    /**
     * API: ルックアップ値を取得
     */
    public function apiGetLookupValue($params)
    {
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }

        $recordId = $params['record_id'] ?? $_GET['record_id'] ?? null;
        $relationFieldId = $_GET['relation_field_id'] ?? null;
        $targetFieldId = $_GET['target_field_id'] ?? null;

        if (!$recordId || !$relationFieldId || !$targetFieldId) {
            return ['error' => 'Missing required parameters', 'code' => 400];
        }

        $value = $this->recordModel->getLookupValue(
            (int)$recordId,
            (int)$relationFieldId,
            (int)$targetFieldId
        );

        return [
            'success' => true,
            'data' => ['value' => $value]
        ];
    }

    /**
     * API: 逆リレーションを取得
     */
    public function apiGetReverseRelations($params)
    {
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }

        $recordId = $params['record_id'] ?? null;

        if (!$recordId) {
            return ['error' => 'Record ID is required', 'code' => 400];
        }

        $relations = $this->recordModel->getReverseRelations((int)$recordId);

        return [
            'success' => true,
            'data' => $relations
        ];
    }

    /**
     * API: 全データベース一覧取得（リレーション設定用）
     */
    public function apiGetAllDatabases()
    {
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }

        $databases = $this->model->getAll(1, 100);

        return [
            'success' => true,
            'data' => $databases
        ];
    }

    /**
     * API: ビュー一覧を取得
     */
    public function apiGetViews($params)
    {
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }

        $databaseId = (int)($params['id'] ?? 0);
        if ($databaseId <= 0) {
            return ['error' => 'Database ID is required', 'code' => 400];
        }

        $userId = (int)$this->auth->id();
        $organizationIds = $this->userModel->getUserOrganizationIds($userId);
        $views = $this->getAccessibleViews($databaseId, $userId, $organizationIds, $this->auth->isAdmin());

        return [
            'success' => true,
            'data' => $views
        ];
    }

    /**
     * API: ビューを保存
     */
    public function apiSaveView($params, $data)
    {
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }
        if (!$this->hasTable('web_database_views')) {
            return ['error' => 'ビュー機能テーブルがありません', 'code' => 500];
        }

        $databaseId = (int)($params['id'] ?? 0);
        if ($databaseId <= 0) {
            return ['error' => 'Database ID is required', 'code' => 400];
        }

        $name = trim((string)($data['name'] ?? ''));
        if ($name === '') {
            return ['error' => 'ビュー名は必須です', 'code' => 400];
        }

        $userId = (int)$this->auth->id();
        $viewId = (int)($data['id'] ?? 0);
        $type = trim((string)($data['type'] ?? 'list'));
        $description = trim((string)($data['description'] ?? ''));
        $isDefault = !empty($data['is_default']) ? 1 : 0;
        $settings = $data['settings'] ?? [];
        if (!is_array($settings)) {
            $decoded = json_decode((string)$settings, true);
            $settings = (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : [];
        }
        $settingsJson = json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($settingsJson === false) {
            $settingsJson = '{}';
        }

        $hasScopeType = $this->hasColumn('web_database_views', 'scope_type');
        $hasOrganizationId = $this->hasColumn('web_database_views', 'organization_id');
        $scopeType = 'private';
        $organizationId = null;
        if ($hasScopeType) {
            $scopeType = trim((string)($data['scope_type'] ?? 'private'));
            if (!in_array($scopeType, ['private', 'organization', 'global'], true)) {
                $scopeType = 'private';
            }
            if ($scopeType === 'global' && !$this->auth->isAdmin()) {
                return ['error' => '全体ビューは管理者のみ作成できます', 'code' => 403];
            }
        }
        if ($hasOrganizationId && $scopeType === 'organization') {
            $organizationId = (int)($data['organization_id'] ?? 0);
            if ($organizationId <= 0) {
                return ['error' => '組織ビューでは組織IDが必要です', 'code' => 400];
            }
            if (!$this->auth->isAdmin()) {
                $allowedOrgIds = array_map('intval', $this->userModel->getUserOrganizationIds($userId));
                if (!in_array($organizationId, $allowedOrgIds, true)) {
                    return ['error' => '所属していない組織のビューは作成できません', 'code' => 403];
                }
            }
        }

        try {
            $existing = null;
            if ($viewId > 0) {
                $existing = $this->db->fetch(
                    "SELECT * FROM web_database_views WHERE id = ? AND database_id = ? LIMIT 1",
                    [$viewId, $databaseId]
                );
                if (!$existing) {
                    return ['error' => '対象ビューが見つかりません', 'code' => 404];
                }
                if ((int)$existing['creator_id'] !== $userId && !$this->auth->isAdmin()) {
                    return ['error' => 'このビューを更新する権限がありません', 'code' => 403];
                }
            }

            $this->db->beginTransaction();
            if ($isDefault) {
                $this->db->execute(
                    "UPDATE web_database_views SET is_default = 0 WHERE database_id = ?",
                    [$databaseId]
                );
            }

            if ($existing) {
                $sql = "UPDATE web_database_views
                        SET name = ?, description = ?, type = ?, settings = ?, is_default = ?, updated_at = CURRENT_TIMESTAMP";
                $paramsSql = [$name, $description !== '' ? $description : null, $type, $settingsJson, $isDefault];
                if ($hasScopeType) {
                    $sql .= ", scope_type = ?";
                    $paramsSql[] = $scopeType;
                }
                if ($hasOrganizationId) {
                    $sql .= ", organization_id = ?";
                    $paramsSql[] = $scopeType === 'organization' ? $organizationId : null;
                }
                $sql .= " WHERE id = ?";
                $paramsSql[] = $viewId;
                $this->db->execute($sql, $paramsSql);
            } else {
                if ($hasScopeType && $hasOrganizationId) {
                    $this->db->execute(
                        "INSERT INTO web_database_views (database_id, name, description, type, settings, scope_type, organization_id, is_default, creator_id, created_at, updated_at)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)",
                        [$databaseId, $name, $description !== '' ? $description : null, $type, $settingsJson, $scopeType, $scopeType === 'organization' ? $organizationId : null, $isDefault, $userId]
                    );
                } else {
                    $this->db->execute(
                        "INSERT INTO web_database_views (database_id, name, description, type, settings, is_default, creator_id, created_at, updated_at)
                         VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)",
                        [$databaseId, $name, $description !== '' ? $description : null, $type, $settingsJson, $isDefault, $userId]
                    );
                }
                $viewId = (int)$this->db->lastInsertId();
            }
            $this->db->commit();

            $saved = $this->db->fetch("SELECT * FROM web_database_views WHERE id = ? LIMIT 1", [$viewId]);
            return [
                'success' => true,
                'message' => 'ビューを保存しました',
                'data' => $saved
            ];
        } catch (\Throwable $e) {
            try {
                $this->db->rollBack();
            } catch (\Throwable $ignore) {
            }
            error_log('Error saving web database view: ' . $e->getMessage());
            return ['error' => 'ビューの保存に失敗しました', 'code' => 500];
        }
    }

    /**
     * API: ビューを削除
     */
    public function apiDeleteView($params)
    {
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }
        if (!$this->hasTable('web_database_views')) {
            return ['error' => 'ビュー機能テーブルがありません', 'code' => 500];
        }

        $viewId = (int)($params['view_id'] ?? 0);
        if ($viewId <= 0) {
            return ['error' => 'View ID is required', 'code' => 400];
        }

        $view = $this->db->fetch("SELECT * FROM web_database_views WHERE id = ? LIMIT 1", [$viewId]);
        if (!$view) {
            return ['error' => 'ビューが見つかりません', 'code' => 404];
        }
        $userId = (int)$this->auth->id();
        if ((int)$view['creator_id'] !== $userId && !$this->auth->isAdmin()) {
            return ['error' => 'このビューを削除する権限がありません', 'code' => 403];
        }

        $ok = $this->db->execute("DELETE FROM web_database_views WHERE id = ?", [$viewId]);
        if (!$ok) {
            return ['error' => 'ビューの削除に失敗しました', 'code' => 500];
        }

        return [
            'success' => true,
            'message' => 'ビューを削除しました'
        ];
    }

    /**
     * API: WebDBデモサンプルを投入
     */
    public function apiSetupDemoSamples($params, $data)
    {
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }
        if (!$this->auth->isAdmin()) {
            return ['error' => '権限がありません', 'code' => 403];
        }

        $result = $this->seedDemoWebDatabases((int)$this->auth->id());
        if (!$result['success']) {
            return ['error' => $result['error'] ?? 'サンプル投入に失敗しました', 'code' => 500];
        }

        return [
            'success' => true,
            'message' => 'WebDBデモサンプルを投入しました',
            'data' => $result
        ];
    }

    private function getAccessibleViews($databaseId, $userId, array $organizationIds = [], $isAdmin = false)
    {
        if (!$this->hasTable('web_database_views')) {
            return [];
        }

        $hasScopeType = $this->hasColumn('web_database_views', 'scope_type');
        $hasOrganizationId = $this->hasColumn('web_database_views', 'organization_id');

        if (!$hasScopeType || !$hasOrganizationId) {
            $sql = "SELECT v.*, u.display_name as creator_name, NULL as organization_name
                    FROM web_database_views v
                    LEFT JOIN users u ON u.id = v.creator_id
                    WHERE v.database_id = ? AND v.creator_id = ?
                    ORDER BY v.is_default DESC, v.updated_at DESC, v.id DESC";
            return $this->db->fetchAll($sql, [$databaseId, $userId]);
        }

        $params = [$databaseId];
        $where = "v.database_id = ? AND (";
        if ($isAdmin) {
            $where .= "1 = 1";
        } else {
            $where .= "v.creator_id = ? OR v.scope_type = 'global'";
            $params[] = $userId;
            $orgIds = array_values(array_unique(array_map('intval', $organizationIds)));
            $orgIds = array_filter($orgIds, static function ($id) {
                return $id > 0;
            });
            if (!empty($orgIds)) {
                $placeholders = implode(',', array_fill(0, count($orgIds), '?'));
                $where .= " OR (v.scope_type = 'organization' AND v.organization_id IN ({$placeholders}))";
                foreach ($orgIds as $orgId) {
                    $params[] = $orgId;
                }
            }
        }
        $where .= ")";

        $sql = "SELECT v.*, u.display_name as creator_name, o.name as organization_name
                FROM web_database_views v
                LEFT JOIN users u ON u.id = v.creator_id
                LEFT JOIN organizations o ON o.id = v.organization_id
                WHERE {$where}
                ORDER BY v.is_default DESC, v.updated_at DESC, v.id DESC";

        return $this->db->fetchAll($sql, $params);
    }

    private function hasTable($tableName)
    {
        if (!preg_match('/^[A-Za-z0-9_]+$/', (string)$tableName)) {
            return false;
        }
        $row = $this->db->fetch("SHOW TABLES LIKE '{$tableName}'");
        return !empty($row);
    }

    private function hasColumn($tableName, $columnName)
    {
        if (!preg_match('/^[A-Za-z0-9_]+$/', (string)$tableName)) {
            return false;
        }
        if (!preg_match('/^[A-Za-z0-9_]+$/', (string)$columnName)) {
            return false;
        }
        $row = $this->db->fetch("SHOW COLUMNS FROM {$tableName} LIKE '{$columnName}'");
        return !empty($row);
    }

    private function seedDemoWebDatabases($creatorId)
    {
        $requiredTables = [
            'web_databases',
            'web_database_fields',
            'web_database_records',
            'web_database_record_data',
            'web_database_views',
            'web_database_relations'
        ];
        foreach ($requiredTables as $table) {
            if (!$this->hasTable($table)) {
                return ['success' => false, 'error' => "{$table} テーブルが不足しています"];
            }
        }
        if (!$this->hasColumn('web_database_fields', 'relation_database_id')) {
            return ['success' => false, 'error' => 'WebDBリレーション機能のマイグレーションが未適用です'];
        }

        try {
            $owner = $this->db->fetch("SELECT id FROM users ORDER BY id ASC LIMIT 1");
            $effectiveCreatorId = (int)($owner['id'] ?? $creatorId);
            if ($effectiveCreatorId <= 0) {
                return ['success' => false, 'error' => 'ユーザーが存在しません'];
            }

            $organizationId = null;
            if ($this->hasTable('user_organizations')) {
                $orgRow = $this->db->fetch(
                    "SELECT organization_id FROM user_organizations WHERE user_id = ? ORDER BY organization_id ASC LIMIT 1",
                    [$effectiveCreatorId]
                );
                if ($orgRow && !empty($orgRow['organization_id'])) {
                    $organizationId = (int)$orgRow['organization_id'];
                }
            }

            $this->db->beginTransaction();

            $customerDbId = $this->ensureDemoDatabase('デモ_顧客管理', '営業向けの顧客台帳サンプル', $effectiveCreatorId, 'users', '#3b82f6');
            $productDbId = $this->ensureDemoDatabase('デモ_商品マスタ', '商品・サービス管理サンプル', $effectiveCreatorId, 'box', '#16a34a');
            $dealDbId = $this->ensureDemoDatabase('デモ_案件管理', '案件進捗と予実を管理するサンプル', $effectiveCreatorId, 'briefcase', '#f59e0b');
            $activityDbId = $this->ensureDemoDatabase('デモ_活動履歴', '案件に紐づく活動記録サンプル', $effectiveCreatorId, 'clipboard-list', '#8b5cf6');
            $salesDbId = $this->ensureDemoDatabase('デモ_売上', '売上ヘッダ + 売上明細(親子入力)サンプル', $effectiveCreatorId, 'file-invoice-dollar', '#ef4444');
            $salesDetailDbId = $this->ensureDemoDatabase('デモ_売上明細', '売上明細の子テーブルサンプル', $effectiveCreatorId, 'table-list', '#f97316');

            $customerNameField = $this->ensureDemoField($customerDbId, '顧客名', 'text', 1, ['required' => 1, 'is_title_field' => 1, 'is_filterable' => 1, 'is_sortable' => 1]);
            $customerIndustryField = $this->ensureDemoField($customerDbId, '業種', 'select', 2, ['is_filterable' => 1, 'options' => [['label' => '製造業', 'value' => 'manufacturing'], ['label' => 'IT', 'value' => 'it'], ['label' => '小売', 'value' => 'retail']]]);
            $customerOwnerField = $this->ensureDemoField($customerDbId, '担当者', 'user', 3, ['is_filterable' => 1]);
            $customerRankField = $this->ensureDemoField($customerDbId, 'ランク', 'select', 4, ['is_filterable' => 1, 'options' => [['label' => 'A', 'value' => 'A'], ['label' => 'B', 'value' => 'B'], ['label' => 'C', 'value' => 'C']]]);
            $customerLastContactField = $this->ensureDemoField($customerDbId, '最終接触日', 'date', 5, ['is_sortable' => 1]);

            $productNameField = $this->ensureDemoField($productDbId, '商品名', 'text', 1, ['required' => 1, 'is_title_field' => 1, 'is_filterable' => 1, 'is_sortable' => 1]);
            $productCategoryField = $this->ensureDemoField($productDbId, 'カテゴリ', 'select', 2, ['is_filterable' => 1, 'options' => [['label' => 'SaaS', 'value' => 'saas'], ['label' => '保守', 'value' => 'maintenance'], ['label' => '製造設備', 'value' => 'equipment']]]);
            $productPriceField = $this->ensureDemoField($productDbId, '単価', 'number', 3, ['is_sortable' => 1]);
            $productHoursField = $this->ensureDemoField($productDbId, '標準工数', 'number', 4, ['is_sortable' => 1]);

            $dealNameField = $this->ensureDemoField($dealDbId, '案件名', 'text', 1, ['required' => 1, 'is_title_field' => 1, 'is_filterable' => 1, 'is_sortable' => 1]);
            $dealCustomerField = $this->ensureDemoField($dealDbId, '顧客', 'relation', 2, ['relation_database_id' => $customerDbId, 'relation_type' => 'many_to_many', 'is_filterable' => 1]);
            $dealOwnerField = $this->ensureDemoField($dealDbId, '主担当', 'user', 3, ['is_filterable' => 1]);
            $dealStatusField = $this->ensureDemoField($dealDbId, 'ステータス', 'select', 4, ['is_filterable' => 1, 'options' => [['label' => '提案', 'value' => 'proposal'], ['label' => '見積', 'value' => 'quote'], ['label' => '交渉', 'value' => 'negotiation'], ['label' => '受注', 'value' => 'won']]]);
            $dealAmountField = $this->ensureDemoField($dealDbId, '見込金額', 'number', 5, ['is_sortable' => 1, 'is_filterable' => 1]);
            $dealDateField = $this->ensureDemoField($dealDbId, '受注予定日', 'date', 6, ['is_sortable' => 1]);
            $dealProductField = $this->ensureDemoField($dealDbId, '関連商品', 'relation', 7, ['relation_database_id' => $productDbId, 'relation_type' => 'many_to_many', 'is_filterable' => 1]);
            $dealMemoField = $this->ensureDemoField($dealDbId, '進捗メモ', 'textarea', 8, []);

            $activityTitleField = $this->ensureDemoField($activityDbId, '活動名', 'text', 1, ['required' => 1, 'is_title_field' => 1, 'is_filterable' => 1]);
            $activityDealField = $this->ensureDemoField($activityDbId, '案件', 'relation', 2, ['relation_database_id' => $dealDbId, 'relation_type' => 'many_to_many', 'is_filterable' => 1]);
            $activityDateField = $this->ensureDemoField($activityDbId, '実施日', 'date', 3, ['is_filterable' => 1, 'is_sortable' => 1]);
            $activityTypeField = $this->ensureDemoField($activityDbId, '種別', 'select', 4, ['is_filterable' => 1, 'options' => [['label' => '商談', 'value' => 'meeting'], ['label' => '訪問', 'value' => 'visit'], ['label' => '電話', 'value' => 'call'], ['label' => 'メール', 'value' => 'mail']]]);
            $activityHoursField = $this->ensureDemoField($activityDbId, '工数', 'number', 5, ['is_sortable' => 1]);
            $activityBodyField = $this->ensureDemoField($activityDbId, '内容', 'textarea', 6, []);

            $salesSlipNoField = $this->ensureDemoField($salesDbId, '伝票番号', 'text', 1, ['required' => 1, 'is_title_field' => 1, 'is_filterable' => 1, 'is_sortable' => 1]);
            $salesDateField = $this->ensureDemoField($salesDbId, '売上日', 'date', 2, ['required' => 1, 'is_filterable' => 1, 'is_sortable' => 1]);
            $salesCustomerField = $this->ensureDemoField($salesDbId, '顧客', 'relation', 3, ['relation_database_id' => $customerDbId, 'relation_type' => 'many_to_many', 'is_filterable' => 1]);
            $salesOwnerField = $this->ensureDemoField($salesDbId, '担当者', 'user', 4, ['is_filterable' => 1]);
            $salesStatusField = $this->ensureDemoField($salesDbId, 'ステータス', 'select', 5, ['is_filterable' => 1, 'options' => [['label' => '下書き', 'value' => 'draft'], ['label' => '確定', 'value' => 'confirmed']]]);
            $salesTotalField = $this->ensureDemoField($salesDbId, '売上合計', 'currency', 6, ['is_filterable' => 1, 'is_sortable' => 1]);
            $salesDetailField = $this->ensureDemoField($salesDbId, '売上明細', 'relation', 7, ['relation_database_id' => $salesDetailDbId, 'relation_type' => 'many_to_many', 'is_filterable' => 0]);
            $salesNoteField = $this->ensureDemoField($salesDbId, '備考', 'textarea', 8, []);

            $salesDetailTitleField = $this->ensureDemoField($salesDetailDbId, '明細名', 'text', 1, ['required' => 1, 'is_title_field' => 1, 'is_filterable' => 1]);
            $salesDetailProductField = $this->ensureDemoField($salesDetailDbId, '商品', 'relation', 2, ['relation_database_id' => $productDbId, 'relation_type' => 'many_to_many', 'is_filterable' => 1]);
            $salesDetailQtyField = $this->ensureDemoField($salesDetailDbId, '数量', 'number', 3, ['is_sortable' => 1]);
            $salesDetailUnitPriceField = $this->ensureDemoField($salesDetailDbId, '単価', 'currency', 4, ['is_sortable' => 1]);
            $salesDetailAmountField = $this->ensureDemoField($salesDetailDbId, '金額', 'currency', 5, ['is_sortable' => 1]);
            $salesDetailMemoField = $this->ensureDemoField($salesDetailDbId, 'メモ', 'text', 6, []);

            $customerRecords = [];
            $customerRecords['株式会社青空製作所'] = $this->ensureDemoRecord($customerDbId, $customerNameField, '株式会社青空製作所', $effectiveCreatorId, [
                $customerIndustryField => 'manufacturing',
                $customerOwnerField => (string)$effectiveCreatorId,
                $customerRankField => 'A',
                $customerLastContactField => date('Y-m-d', strtotime('-7 day'))
            ]);
            $customerRecords['ネクストIT株式会社'] = $this->ensureDemoRecord($customerDbId, $customerNameField, 'ネクストIT株式会社', $effectiveCreatorId, [
                $customerIndustryField => 'it',
                $customerOwnerField => (string)$effectiveCreatorId,
                $customerRankField => 'B',
                $customerLastContactField => date('Y-m-d', strtotime('-3 day'))
            ]);
            $customerRecords['みなと商事'] = $this->ensureDemoRecord($customerDbId, $customerNameField, 'みなと商事', $effectiveCreatorId, [
                $customerIndustryField => 'retail',
                $customerOwnerField => (string)$effectiveCreatorId,
                $customerRankField => 'A',
                $customerLastContactField => date('Y-m-d', strtotime('-1 day'))
            ]);

            $productRecords = [];
            $productRecords['クラウド在庫管理'] = $this->ensureDemoRecord($productDbId, $productNameField, 'クラウド在庫管理', $effectiveCreatorId, [
                $productCategoryField => 'saas',
                $productPriceField => '1200000',
                $productHoursField => '80'
            ]);
            $productRecords['保守サポート'] = $this->ensureDemoRecord($productDbId, $productNameField, '保守サポート', $effectiveCreatorId, [
                $productCategoryField => 'maintenance',
                $productPriceField => '300000',
                $productHoursField => '24'
            ]);
            $productRecords['製造ライン点検'] = $this->ensureDemoRecord($productDbId, $productNameField, '製造ライン点検', $effectiveCreatorId, [
                $productCategoryField => 'equipment',
                $productPriceField => '850000',
                $productHoursField => '56'
            ]);

            $dealRecords = [];
            $dealRecords['青空製作所_在庫刷新案件'] = $this->ensureDemoRecord($dealDbId, $dealNameField, '青空製作所_在庫刷新案件', $effectiveCreatorId, [
                $dealOwnerField => (string)$effectiveCreatorId,
                $dealStatusField => 'negotiation',
                $dealAmountField => '2400000',
                $dealDateField => date('Y-m-d', strtotime('+15 day')),
                $dealMemoField => 'PoC実施済み。見積提示後に最終調整中。'
            ]);
            $dealRecords['ネクストIT_保守更新'] = $this->ensureDemoRecord($dealDbId, $dealNameField, 'ネクストIT_保守更新', $effectiveCreatorId, [
                $dealOwnerField => (string)$effectiveCreatorId,
                $dealStatusField => 'quote',
                $dealAmountField => '600000',
                $dealDateField => date('Y-m-d', strtotime('+30 day')),
                $dealMemoField => '契約条件の確認中。'
            ]);
            $dealRecords['みなと商事_点検導入'] = $this->ensureDemoRecord($dealDbId, $dealNameField, 'みなと商事_点検導入', $effectiveCreatorId, [
                $dealOwnerField => (string)$effectiveCreatorId,
                $dealStatusField => 'proposal',
                $dealAmountField => '980000',
                $dealDateField => date('Y-m-d', strtotime('+45 day')),
                $dealMemoField => '初回提案提出済み。'
            ]);

            $this->ensureRelation($dealRecords['青空製作所_在庫刷新案件'], $dealCustomerField, $customerDbId, $customerRecords['株式会社青空製作所']);
            $this->ensureRelation($dealRecords['ネクストIT_保守更新'], $dealCustomerField, $customerDbId, $customerRecords['ネクストIT株式会社']);
            $this->ensureRelation($dealRecords['みなと商事_点検導入'], $dealCustomerField, $customerDbId, $customerRecords['みなと商事']);
            $this->ensureRelation($dealRecords['青空製作所_在庫刷新案件'], $dealProductField, $productDbId, $productRecords['クラウド在庫管理']);
            $this->ensureRelation($dealRecords['青空製作所_在庫刷新案件'], $dealProductField, $productDbId, $productRecords['保守サポート']);
            $this->ensureRelation($dealRecords['ネクストIT_保守更新'], $dealProductField, $productDbId, $productRecords['保守サポート']);
            $this->ensureRelation($dealRecords['みなと商事_点検導入'], $dealProductField, $productDbId, $productRecords['製造ライン点検']);

            $activityMeeting = $this->ensureDemoRecord($activityDbId, $activityTitleField, '青空製作所 定例商談', $effectiveCreatorId, [
                $activityDateField => date('Y-m-d', strtotime('-2 day')),
                $activityTypeField => 'meeting',
                $activityHoursField => '2.5',
                $activityBodyField => '要件と導入スケジュールを調整。'
            ]);
            $activityCall = $this->ensureDemoRecord($activityDbId, $activityTitleField, 'ネクストIT 契約電話', $effectiveCreatorId, [
                $activityDateField => date('Y-m-d', strtotime('-1 day')),
                $activityTypeField => 'call',
                $activityHoursField => '1.0',
                $activityBodyField => '見積条件の説明と質疑対応。'
            ]);
            $activityVisit = $this->ensureDemoRecord($activityDbId, $activityTitleField, 'みなと商事 現地訪問', $effectiveCreatorId, [
                $activityDateField => date('Y-m-d'),
                $activityTypeField => 'visit',
                $activityHoursField => '3.0',
                $activityBodyField => '現場確認と改善提案のヒアリング。'
            ]);

            $this->ensureRelation($activityMeeting, $activityDealField, $dealDbId, $dealRecords['青空製作所_在庫刷新案件']);
            $this->ensureRelation($activityCall, $activityDealField, $dealDbId, $dealRecords['ネクストIT_保守更新']);
            $this->ensureRelation($activityVisit, $activityDealField, $dealDbId, $dealRecords['みなと商事_点検導入']);

            $salesDetailRecords = [];
            $salesDetailRecords[] = $this->ensureDemoRecord($salesDetailDbId, $salesDetailTitleField, 'SL-202603-001-1', $effectiveCreatorId, [
                $salesDetailQtyField => '2',
                $salesDetailUnitPriceField => '1200000',
                $salesDetailAmountField => '2400000',
                $salesDetailMemoField => 'クラウド在庫管理 2ライセンス'
            ]);
            $this->ensureRelation($salesDetailRecords[0], $salesDetailProductField, $productDbId, $productRecords['クラウド在庫管理']);

            $salesDetailRecords[] = $this->ensureDemoRecord($salesDetailDbId, $salesDetailTitleField, 'SL-202603-001-2', $effectiveCreatorId, [
                $salesDetailQtyField => '1',
                $salesDetailUnitPriceField => '300000',
                $salesDetailAmountField => '300000',
                $salesDetailMemoField => '保守サポート 1年'
            ]);
            $this->ensureRelation($salesDetailRecords[1], $salesDetailProductField, $productDbId, $productRecords['保守サポート']);

            $salesDetailRecords[] = $this->ensureDemoRecord($salesDetailDbId, $salesDetailTitleField, 'SL-202603-002-1', $effectiveCreatorId, [
                $salesDetailQtyField => '1',
                $salesDetailUnitPriceField => '850000',
                $salesDetailAmountField => '850000',
                $salesDetailMemoField => '製造ライン点検 導入'
            ]);
            $this->ensureRelation($salesDetailRecords[2], $salesDetailProductField, $productDbId, $productRecords['製造ライン点検']);

            $salesRecord1 = $this->ensureDemoRecord($salesDbId, $salesSlipNoField, 'SL-202603-001', $effectiveCreatorId, [
                $salesDateField => date('Y-m-d', strtotime('-7 day')),
                $salesOwnerField => (string)$effectiveCreatorId,
                $salesStatusField => 'confirmed',
                $salesTotalField => '2700000',
                $salesNoteField => '青空製作所向け一式'
            ]);
            $this->ensureRelation($salesRecord1, $salesCustomerField, $customerDbId, $customerRecords['株式会社青空製作所']);
            $this->ensureRelation($salesRecord1, $salesDetailField, $salesDetailDbId, $salesDetailRecords[0]);
            $this->ensureRelation($salesRecord1, $salesDetailField, $salesDetailDbId, $salesDetailRecords[1]);

            $salesRecord2 = $this->ensureDemoRecord($salesDbId, $salesSlipNoField, 'SL-202603-002', $effectiveCreatorId, [
                $salesDateField => date('Y-m-d', strtotime('-3 day')),
                $salesOwnerField => (string)$effectiveCreatorId,
                $salesStatusField => 'draft',
                $salesTotalField => '850000',
                $salesNoteField => 'みなと商事 初回導入'
            ]);
            $this->ensureRelation($salesRecord2, $salesCustomerField, $customerDbId, $customerRecords['みなと商事']);
            $this->ensureRelation($salesRecord2, $salesDetailField, $salesDetailDbId, $salesDetailRecords[2]);

            $salesLayoutItems = [
                ['field_id' => $salesSlipNoField, 'section' => '基本情報', 'hidden' => 0, 'required' => 1],
                ['field_id' => $salesDateField, 'section' => '基本情報', 'hidden' => 0, 'required' => 1],
                ['field_id' => $salesCustomerField, 'section' => '基本情報', 'hidden' => 0, 'required' => 1],
                ['field_id' => $salesOwnerField, 'section' => '基本情報', 'hidden' => 0, 'required' => 1],
                ['field_id' => $salesStatusField, 'section' => '基本情報', 'hidden' => 0, 'required' => 1],
                ['field_id' => $salesTotalField, 'section' => '基本情報', 'hidden' => 0, 'required' => 1],
                ['field_id' => $salesDetailField, 'section' => '売上明細', 'hidden' => 0, 'required' => 0, 'child_table' => 1, 'child_summary_field_id' => $salesDetailAmountField],
                ['field_id' => $salesNoteField, 'section' => '補足', 'hidden' => 0, 'required' => 0],
            ];
            $this->saveFormLayoutForDatabase($salesDbId, $salesLayoutItems, $effectiveCreatorId);
            $salesDetailLayoutItems = [
                ['field_id' => $salesDetailTitleField, 'section' => '明細', 'hidden' => 0, 'required' => 1],
                ['field_id' => $salesDetailProductField, 'section' => '明細', 'hidden' => 0, 'required' => 1],
                ['field_id' => $salesDetailQtyField, 'section' => '明細', 'hidden' => 0, 'required' => 1],
                ['field_id' => $salesDetailUnitPriceField, 'section' => '明細', 'hidden' => 0, 'required' => 1],
                ['field_id' => $salesDetailAmountField, 'section' => '明細', 'hidden' => 0, 'required' => 1],
                ['field_id' => $salesDetailMemoField, 'section' => '補足', 'hidden' => 0, 'required' => 0],
            ];
            $this->saveFormLayoutForDatabase($salesDetailDbId, $salesDetailLayoutItems, $effectiveCreatorId);

            $this->ensureDemoView($dealDbId, $effectiveCreatorId, '自分の案件ビュー', 'list', [
                'search' => '',
                'filters' => [$dealOwnerField => (string)$effectiveCreatorId],
                'sort' => (string)$dealAmountField,
                'order' => 'desc'
            ], 1, 'private', null);
            if ($organizationId !== null) {
                $this->ensureDemoView($dealDbId, $effectiveCreatorId, '組織案件ビュー', 'list', [
                    'search' => '',
                    'filters' => [],
                    'sort' => (string)$dealDateField,
                    'order' => 'asc'
                ], 0, 'organization', $organizationId);
            }
            $this->ensureDemoView($dealDbId, $effectiveCreatorId, '全社案件サマリー', 'custom', [
                'search' => '',
                'filters' => [],
                'sort' => (string)$dealAmountField,
                'order' => 'desc',
                'graph' => [
                    'type' => 'bar',
                    'group_field' => (string)$dealStatusField,
                    'metric' => 'count'
                ]
            ], 0, 'global', null);
            $this->ensureDemoView($activityDbId, $effectiveCreatorId, '月次活動ビュー', 'list', [
                'search' => '',
                'filters' => [],
                'sort' => (string)$activityDateField,
                'order' => 'desc'
            ], 1, 'global', null);
            $this->ensureDemoView($salesDbId, $effectiveCreatorId, '売上一覧', 'list', [
                'search' => '',
                'filters' => [],
                'sort' => (string)$salesDateField,
                'order' => 'desc',
                'view_type' => 'list',
                'visible_fields' => [$salesDateField, $salesCustomerField, $salesOwnerField, $salesStatusField, $salesTotalField]
            ], 1, 'global', null);
            $this->ensureDemoView($salesDbId, $effectiveCreatorId, '売上_月次集計', 'custom', [
                'search' => '',
                'filters' => [],
                'sort' => null,
                'order' => 'desc',
                'view_type' => 'aggregate',
                'visible_fields' => [$salesDateField, $salesCustomerField, $salesStatusField, $salesTotalField],
                'aggregate' => [
                    'group_field_id' => $salesDateField,
                    'metric' => 'sum',
                    'metric_field_id' => $salesTotalField,
                    'date_grain' => 'month',
                    'chart_type' => 'bar'
                ]
            ], 0, 'global', null);
            $this->ensureDemoView($salesDbId, $effectiveCreatorId, '売上_月次グラフ', 'custom', [
                'search' => '',
                'filters' => [],
                'sort' => null,
                'order' => 'desc',
                'view_type' => 'chart',
                'visible_fields' => [$salesDateField, $salesCustomerField, $salesStatusField, $salesTotalField],
                'aggregate' => [
                    'group_field_id' => $salesDateField,
                    'metric' => 'sum',
                    'metric_field_id' => $salesTotalField,
                    'date_grain' => 'month',
                    'chart_type' => 'bar'
                ]
            ], 0, 'global', null);

            $this->db->commit();
            return [
                'success' => true,
                'database_ids' => [
                    'customers' => $customerDbId,
                    'products' => $productDbId,
                    'deals' => $dealDbId,
                    'activities' => $activityDbId,
                    'sales' => $salesDbId,
                    'sales_details' => $salesDetailDbId
                ]
            ];
        } catch (\Throwable $e) {
            try {
                $this->db->rollBack();
            } catch (\Throwable $ignore) {
            }
            error_log('Error seeding web database demo samples: ' . $e->getMessage());
            return ['success' => false, 'error' => 'デモサンプル投入中にエラーが発生しました'];
        }
    }

    private function ensureDemoDatabase($name, $description, $creatorId, $icon = 'database', $color = '#3498db')
    {
        $existing = $this->db->fetch(
            "SELECT id FROM web_databases WHERE name = ? LIMIT 1",
            [$name]
        );
        if ($existing) {
            return (int)$existing['id'];
        }

        $this->db->execute(
            "INSERT INTO web_databases (name, description, icon, color, is_public, creator_id, created_at, updated_at)
             VALUES (?, ?, ?, ?, 1, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)",
            [$name, $description, $icon, $color, $creatorId]
        );
        return (int)$this->db->lastInsertId();
    }

    private function ensureDemoField($databaseId, $name, $type, $sortOrder, array $meta = [])
    {
        $existing = $this->db->fetch(
            "SELECT id FROM web_database_fields WHERE database_id = ? AND name = ? LIMIT 1",
            [$databaseId, $name]
        );
        if ($existing) {
            return (int)$existing['id'];
        }

        $options = $meta['options'] ?? null;
        if (is_array($options)) {
            $options = json_encode($options, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $columns = [
            'database_id', 'name', 'description', 'type', 'options',
            'required', 'unique_value', 'default_value', 'validation',
            'sort_order', 'is_title_field', 'is_filterable', 'is_sortable',
            'created_at', 'updated_at'
        ];
        $values = [
            $databaseId,
            $name,
            $meta['description'] ?? null,
            $type,
            $options,
            !empty($meta['required']) ? 1 : 0,
            !empty($meta['unique_value']) ? 1 : 0,
            $meta['default_value'] ?? null,
            null,
            (int)$sortOrder,
            !empty($meta['is_title_field']) ? 1 : 0,
            !empty($meta['is_filterable']) ? 1 : 0,
            !empty($meta['is_sortable']) ? 1 : 0
        ];
        $placeholders = array_fill(0, count($values), '?');
        $placeholders[] = 'CURRENT_TIMESTAMP';
        $placeholders[] = 'CURRENT_TIMESTAMP';

        if ($this->hasColumn('web_database_fields', 'relation_database_id')) {
            array_splice($columns, 13, 0, ['relation_database_id', 'relation_field_id', 'relation_type', 'lookup_relation_field_id', 'lookup_target_field_id', 'calc_formula']);
            array_splice($values, 13, 0, [
                !empty($meta['relation_database_id']) ? (int)$meta['relation_database_id'] : null,
                !empty($meta['relation_field_id']) ? (int)$meta['relation_field_id'] : null,
                $meta['relation_type'] ?? null,
                !empty($meta['lookup_relation_field_id']) ? (int)$meta['lookup_relation_field_id'] : null,
                !empty($meta['lookup_target_field_id']) ? (int)$meta['lookup_target_field_id'] : null,
                $meta['calc_formula'] ?? null
            ]);
            array_splice($placeholders, 13, 0, ['?', '?', '?', '?', '?', '?']);
        }

        $sql = "INSERT INTO web_database_fields (" . implode(', ', $columns) . ")
                VALUES (" . implode(', ', $placeholders) . ")";
        $this->db->execute($sql, $values);
        return (int)$this->db->lastInsertId();
    }

    private function ensureDemoRecord($databaseId, $titleFieldId, $titleValue, $creatorId, array $fieldValues = [])
    {
        $existing = $this->db->fetch(
            "SELECT r.id
             FROM web_database_records r
             JOIN web_database_record_data d ON d.record_id = r.id
             WHERE r.database_id = ? AND d.field_id = ? AND d.value = ?
             LIMIT 1",
            [$databaseId, $titleFieldId, $titleValue]
        );

        if ($existing) {
            $recordId = (int)$existing['id'];
        } else {
            $this->db->execute(
                "INSERT INTO web_database_records (database_id, creator_id, updater_id, created_at, updated_at)
                 VALUES (?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)",
                [(int)$databaseId, (int)$creatorId, (int)$creatorId]
            );
            $recordId = (int)$this->db->lastInsertId();
        }

        if ($recordId <= 0) {
            throw new \RuntimeException('Failed to create or resolve demo record ID');
        }
        $recordCheck = $this->db->fetch(
            "SELECT id FROM web_database_records WHERE id = ? AND database_id = ? LIMIT 1",
            [$recordId, $databaseId]
        );
        if (!$recordCheck) {
            throw new \RuntimeException('Resolved demo record is invalid');
        }

        $this->upsertRecordData($recordId, $titleFieldId, (string)$titleValue);
        foreach ($fieldValues as $fieldId => $value) {
            $normalizedValue = '';
            if (is_scalar($value) || $value === null) {
                $normalizedValue = (string)$value;
            } else {
                $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $normalizedValue = $encoded !== false ? $encoded : '';
            }
            $this->upsertRecordData($recordId, (int)$fieldId, $normalizedValue);
        }

        return $recordId;
    }

    private function upsertRecordData($recordId, $fieldId, $value)
    {
        $recordCheck = $this->db->fetch("SELECT id FROM web_database_records WHERE id = ? LIMIT 1", [$recordId]);
        if (!$recordCheck) {
            throw new \RuntimeException("Invalid record_id for record data upsert: {$recordId}");
        }

        $existing = $this->db->fetch(
            "SELECT id FROM web_database_record_data WHERE record_id = ? AND field_id = ? LIMIT 1",
            [$recordId, $fieldId]
        );
        if ($existing) {
            $this->db->execute(
                "UPDATE web_database_record_data SET value = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
                [(string)$value, (int)$existing['id']]
            );
            return;
        }
        $this->db->execute(
            "INSERT INTO web_database_record_data (record_id, field_id, value, created_at, updated_at)
             VALUES (?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)",
            [$recordId, $fieldId, (string)$value]
        );
    }

    private function ensureRelation($sourceRecordId, $sourceFieldId, $targetDatabaseId, $targetRecordId)
    {
        $exists = $this->db->fetch(
            "SELECT id FROM web_database_relations
             WHERE source_record_id = ? AND source_field_id = ? AND target_record_id = ?
             LIMIT 1",
            [$sourceRecordId, $sourceFieldId, $targetRecordId]
        );
        if ($exists) {
            return;
        }
        $this->db->execute(
            "INSERT INTO web_database_relations (source_record_id, source_field_id, target_record_id, target_database_id, sort_order, created_at)
             VALUES (?, ?, ?, ?, 0, CURRENT_TIMESTAMP)",
            [$sourceRecordId, $sourceFieldId, $targetRecordId, $targetDatabaseId]
        );
    }

    private function ensureDemoView($databaseId, $creatorId, $name, $type, array $settings, $isDefault = 0, $scopeType = 'private', $organizationId = null)
    {
        if (!$this->hasTable('web_database_views')) {
            return;
        }

        $settingsJson = json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($settingsJson === false) {
            $settingsJson = '{}';
        }

        $existing = $this->db->fetch(
            "SELECT id FROM web_database_views WHERE database_id = ? AND name = ? LIMIT 1",
            [$databaseId, $name]
        );

        $hasScopeType = $this->hasColumn('web_database_views', 'scope_type');
        $hasOrganizationId = $this->hasColumn('web_database_views', 'organization_id');

        if ($existing) {
            if ($hasScopeType && $hasOrganizationId) {
                $this->db->execute(
                    "UPDATE web_database_views
                     SET type = ?, settings = ?, is_default = ?, scope_type = ?, organization_id = ?, updated_at = CURRENT_TIMESTAMP
                     WHERE id = ?",
                    [$type, $settingsJson, $isDefault ? 1 : 0, $scopeType, $scopeType === 'organization' ? $organizationId : null, (int)$existing['id']]
                );
            } else {
                $this->db->execute(
                    "UPDATE web_database_views
                     SET type = ?, settings = ?, is_default = ?, updated_at = CURRENT_TIMESTAMP
                     WHERE id = ?",
                    [$type, $settingsJson, $isDefault ? 1 : 0, (int)$existing['id']]
                );
            }
            return;
        }

        if ($hasScopeType && $hasOrganizationId) {
            $this->db->execute(
                "INSERT INTO web_database_views
                 (database_id, name, description, type, settings, scope_type, organization_id, is_default, creator_id, created_at, updated_at)
                 VALUES (?, ?, NULL, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)",
                [$databaseId, $name, $type, $settingsJson, $scopeType, $scopeType === 'organization' ? $organizationId : null, $isDefault ? 1 : 0, $creatorId]
            );
        } else {
            $this->db->execute(
                "INSERT INTO web_database_views
                 (database_id, name, description, type, settings, is_default, creator_id, created_at, updated_at)
                 VALUES (?, ?, NULL, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)",
                [$databaseId, $name, $type, $settingsJson, $isDefault ? 1 : 0, $creatorId]
            );
        }
    }
}

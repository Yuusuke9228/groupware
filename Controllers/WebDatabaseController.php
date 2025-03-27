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

        $viewData = [
            'title' => 'フィールド設定',
            'database' => $database,
            'fields' => $fields,
            'jsFiles' => ['webdatabase.js', 'webdatabase-field.js']
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

        $viewData = [
            'title' => $database['name'] . ' - レコード一覧',
            'database' => $database,
            'fields' => $fields,
            'jsFiles' => ['webdatabase.js', 'webdatabase-record.js']
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

        $viewData = [
            'title' => '新規レコード作成',
            'database' => $database,
            'fields' => $fields,
            'jsFiles' => ['webdatabase.js', 'webdatabase-record.js']
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

        $viewData = [
            'title' => 'レコード編集',
            'database' => $database,
            'fields' => $fields,
            'record' => $record,
            'recordData' => $recordData,
            'jsFiles' => ['webdatabase.js', 'webdatabase-record.js']
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
            'jsFiles' => ['webdatabase.js', 'webdatabase-record.js']
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

        $id = $this->fieldModel->create($data);
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

        $success = $this->fieldModel->update($id, $data);
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

        $success = $this->fieldModel->delete($id);
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
        // 認証チェック
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }

        // パラメータを$_GETから直接取得
        $databaseId = isset($params['id']) ? $params['id'] : null;
        if (!$databaseId) {
            return ['error' => 'Database ID is required', 'code' => 400];
        }

        // RAWリクエストのデバッグ
        error_log('Raw $_GET: ' . json_encode($_GET));
        error_log('Raw $_REQUEST: ' . json_encode($_REQUEST));

        // ページネーション
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;

        // 検索条件
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
            error_log('Decoded filter_json: ' . json_encode($decodedFilters));
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

        // デバッグ用
        error_log('GET params: ' . json_encode($_GET));
        error_log('Processed filters: ' . json_encode($filters));

        // ソート条件
        $sort = isset($_GET['sort']) ? $_GET['sort'] : null;
        $order = isset($_GET['order']) ? strtolower($_GET['order']) : 'asc';
        if ($order !== 'asc' && $order !== 'desc') {
            $order = 'asc';
        }

        error_log('page: ' . $page . ', limit: ' . $limit . ', search: ' . $search . ', filters: ' . json_encode($filters) . ', sort: ' . $sort . ', order: ' . $order);

        // データベースからレコードを取得
        $records = $this->recordModel->getByDatabaseId($databaseId, $page, $limit, $search, $filters, $sort, $order);
        $totalRecords = $this->recordModel->getCountByDatabaseId($databaseId, $search, $filters);
        $totalPages = ceil($totalRecords / $limit);

        return [
            'success' => true,
            'data' => [
                'records' => $records,
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
        // 認証チェック
        if (!$this->auth->check()) {
            return ['error' => 'Unauthorized', 'code' => 401];
        }

        $databaseId = $params['id'] ?? null;
        if (!$databaseId) {
            return ['error' => 'Database ID is required', 'code' => 400];
        }

        // 現在のユーザーIDを追加
        $data['creator_id'] = $this->auth->id();
        $data['database_id'] = $databaseId;

        // ファイル処理
        if (isset($_FILES) && !empty($_FILES)) {
            $data['files'] = $this->processRecordFiles($_FILES);
        }

        // トランザクション開始
        $this->db->beginTransaction();

        try {
            // レコードを作成
            $recordId = $this->recordModel->create($data);
            if (!$recordId) {
                throw new \Exception('Failed to create record');
            }

            // レコードデータを保存
            if (isset($data['fields']) && is_array($data['fields'])) {
                foreach ($data['fields'] as $fieldId => $value) {
                    $this->recordModel->saveFieldData($recordId, $fieldId, $value);
                }
            }

            $this->db->commit();

            return [
                'success' => true,
                'data' => ['id' => $recordId],
                'message' => 'レコードを作成しました',
                'redirect' => BASE_PATH . '/webdatabase/records/' . $databaseId
            ];
        } catch (\Exception $e) {
            $this->db->rollBack();
            return ['error' => $e->getMessage(), 'code' => 500];
        }
    }

    /**
     * API: レコードを更新
     */
    public function apiUpdateRecord($params, $data)
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

        // 更新者IDを追加
        $data['updater_id'] = $this->auth->id();

        // ファイル処理
        if (isset($_FILES) && !empty($_FILES)) {
            $data['files'] = $this->processRecordFiles($_FILES);
        }

        // トランザクション開始
        $this->db->beginTransaction();

        try {
            // レコードを更新
            $success = $this->recordModel->update($recordId, $data);
            if (!$success) {
                throw new \Exception('Failed to update record');
            }

            // レコードデータを保存
            if (isset($data['fields']) && is_array($data['fields'])) {
                foreach ($data['fields'] as $fieldId => $value) {
                    $this->recordModel->saveFieldData($recordId, $fieldId, $value);
                }
            }

            $this->db->commit();

            return [
                'success' => true,
                'message' => 'レコードを更新しました',
                'redirect' => BASE_PATH . '/webdatabase/view/' . $databaseId . '/' . $recordId
            ];
        } catch (\Exception $e) {
            $this->db->rollBack();
            return ['error' => $e->getMessage(), 'code' => 500];
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
}
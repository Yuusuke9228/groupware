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

        $databaseId = $params['id'] ?? null;
        if (!$databaseId) {
            return ['error' => 'Database ID is required', 'code' => 400];
        }

        // ページネーション
        $page = $params['page'] ?? 1;
        $limit = $params['limit'] ?? 20;

        // 検索・フィルター・ソート条件
        $search = $params['search'] ?? null;
        $filters = $params['filters'] ?? [];
        $sort = $params['sort'] ?? null;
        $order = $params['order'] ?? 'asc';

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
                    'current_page' => $page,
                    'limit' => $limit
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

        $basename = preg_replace('/[^\p{L}\p{N}_.-]/u', '_', $basename);

        if (empty($basename)) {
            $basename = 'file';
        }

        return $basename . $extension;
    }
}

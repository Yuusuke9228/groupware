<?php
// Controllers/CsvImportController.php
namespace Controllers;

use Core\Controller;
use Core\Database;

class CsvImportController extends Controller
{
    private $db;

    public function __construct()
    {
        parent::__construct();

        if (!$this->auth->check()) {
            $this->redirect(BASE_PATH . '/login');
        }

        if (!$this->auth->isAdmin()) {
            $this->redirect(BASE_PATH . '/');
        }

        $this->db = Database::getInstance();
    }

    /**
     * CSVインポート画面を表示
     */
    public function index()
    {
        $this->view('admin/csv_import', [
            'title' => 'CSVインポート',
            'csrf_token' => $this->generateCsrfToken(),
        ]);
    }

    /**
     * サンプルCSVダウンロード
     */
    public function downloadSample($type)
    {
        $samples = [
            'users' => [
                'filename' => 'sample_users.csv',
                'headers' => ['username', 'password', 'display_name', 'email', 'role', 'organization_code'],
                'rows' => [
                    ['yamada.taro', 'Password123', '山田太郎', 'yamada@example.com', 'user', 'SALES001'],
                    ['suzuki.hanako', 'Password456', '鈴木花子', 'suzuki@example.com', 'admin', 'SALES002'],
                ],
            ],
            'organizations' => [
                'filename' => 'sample_organizations.csv',
                'headers' => ['code', 'name', 'parent_code', 'description', 'sort_order'],
                'rows' => [
                    ['SALES', '営業部', '', '営業活動を担当', '1'],
                    ['SALES001', '営業1課', 'SALES', '国内営業担当', '1'],
                    ['SALES002', '営業2課', 'SALES', '海外営業担当', '2'],
                ],
            ],
            'address-book' => [
                'filename' => 'sample_address_book.csv',
                'headers' => ['name', 'name_kana', 'company', 'department', 'position_title', 'email', 'phone', 'mobile', 'category'],
                'rows' => [
                    ['田中一郎', 'タナカイチロウ', '株式会社サンプル', '営業部', '部長', 'tanaka@example.com', '03-1234-5678', '090-1234-5678', '取引先'],
                    ['佐藤次郎', 'サトウジロウ', '株式会社テスト', '開発部', '課長', 'sato@example.com', '06-9876-5432', '080-9876-5432', '取引先'],
                ],
            ],
        ];

        if (!isset($samples[$type])) {
            http_response_code(404);
            echo 'Not found';
            exit;
        }

        $sample = $samples[$type];

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $sample['filename'] . '"');

        // UTF-8 BOM を出力（Excel対応）
        echo "\xEF\xBB\xBF";

        $fp = fopen('php://output', 'w');
        fputcsv($fp, $sample['headers']);
        foreach ($sample['rows'] as $row) {
            fputcsv($fp, $row);
        }
        fclose($fp);
        exit;
    }

    /**
     * ユーザーCSVインポート
     */
    public function importUsers()
    {
        if (!$this->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->json(['success' => false, 'message' => 'CSRFトークンが無効です。'], 403);
        }

        $rows = $this->parseCsvUpload('csv_file');
        if (isset($rows['error'])) {
            $this->json(['success' => false, 'message' => $rows['error']]);
        }

        if (empty($rows)) {
            $this->json(['success' => false, 'message' => 'CSVファイルにデータがありません。']);
        }

        $requiredColumns = ['username', 'password', 'display_name'];
        $header = array_keys($rows[0]);
        foreach ($requiredColumns as $col) {
            if (!in_array($col, $header)) {
                $this->json(['success' => false, 'message' => "必須列「{$col}」がCSVに見つかりません。"]);
            }
        }

        // プレビューモード
        if (!empty($_POST['preview'])) {
            $preview = array_slice($rows, 0, 5);
            $this->json(['success' => true, 'preview' => $preview, 'total' => count($rows)]);
        }

        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        $this->db->beginTransaction();
        try {
            foreach ($rows as $i => $row) {
                $lineNum = $i + 2; // ヘッダー行 + 0-index
                $username = trim($row['username'] ?? '');
                $password = trim($row['password'] ?? '');
                $displayName = trim($row['display_name'] ?? '');
                $email = trim($row['email'] ?? '');
                $role = trim($row['role'] ?? 'user');
                $organizationId = trim($row['organization_id'] ?? '');
                $organizationCode = trim($row['organization_code'] ?? '');

                // バリデーション
                if (empty($username) || empty($password) || empty($displayName)) {
                    $errors[] = "{$lineNum}行目: username, password, display_name は必須です。";
                    $errorCount++;
                    continue;
                }

                if (!in_array($role, ['admin', 'manager', 'user'])) {
                    $role = 'user';
                }

                // 重複チェック
                $existing = $this->db->fetch("SELECT id FROM users WHERE username = ?", [$username]);
                if ($existing) {
                    $errors[] = "{$lineNum}行目: ユーザー名「{$username}」は既に存在します。";
                    $errorCount++;
                    continue;
                }

                if (!empty($email)) {
                    $existingEmail = $this->db->fetch("SELECT id FROM users WHERE email = ?", [$email]);
                    if ($existingEmail) {
                        $errors[] = "{$lineNum}行目: メールアドレス「{$email}」は既に使用されています。";
                        $errorCount++;
                        continue;
                    }
                }

                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $orgId = null;
                if (!empty($organizationId) && is_numeric($organizationId)) {
                    $orgId = (int)$organizationId;
                } elseif (!empty($organizationCode)) {
                    $organization = $this->db->fetch("SELECT id FROM organizations WHERE code = ?", [$organizationCode]);
                    if (!$organization) {
                        $errors[] = "{$lineNum}行目: 組織コード「{$organizationCode}」が見つかりません。";
                        $errorCount++;
                        continue;
                    }
                    $orgId = (int)$organization['id'];
                }

                $this->db->execute(
                    "INSERT INTO users (username, password, display_name, email, role, organization_id, first_name, last_name, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, '', '', 'active', NOW(), NOW())",
                    [$username, $hashedPassword, $displayName, $email, $role, $orgId]
                );
                $successCount++;
            }
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            $this->json(['success' => false, 'message' => 'インポート中にエラーが発生しました: ' . $e->getMessage()]);
        }

        $this->json([
            'success' => true,
            'message' => "インポート完了: 成功 {$successCount}件、エラー {$errorCount}件",
            'successCount' => $successCount,
            'errorCount' => $errorCount,
            'errors' => array_slice($errors, 0, 20),
        ]);
    }

    /**
     * 組織CSVインポート
     */
    public function importOrganizations()
    {
        if (!$this->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->json(['success' => false, 'message' => 'CSRFトークンが無効です。'], 403);
        }

        $rows = $this->parseCsvUpload('csv_file');
        if (isset($rows['error'])) {
            $this->json(['success' => false, 'message' => $rows['error']]);
        }

        if (empty($rows)) {
            $this->json(['success' => false, 'message' => 'CSVファイルにデータがありません。']);
        }

        $requiredColumns = ['name'];
        $header = array_keys($rows[0]);
        foreach ($requiredColumns as $col) {
            if (!in_array($col, $header)) {
                $this->json(['success' => false, 'message' => "必須列「{$col}」がCSVに見つかりません。"]);
            }
        }

        // プレビューモード
        if (!empty($_POST['preview'])) {
            $preview = array_slice($rows, 0, 5);
            $this->json(['success' => true, 'preview' => $preview, 'total' => count($rows)]);
        }

        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        $this->db->beginTransaction();
        try {
            foreach ($rows as $i => $row) {
                $lineNum = $i + 2;
                $name = trim($row['name'] ?? '');
                $parentId = trim($row['parent_id'] ?? '');
                $parentCode = trim($row['parent_code'] ?? '');
                $description = trim($row['description'] ?? '');
                $sortOrder = trim($row['sort_order'] ?? '0');
                $code = trim($row['code'] ?? '');

                if (empty($name)) {
                    $errors[] = "{$lineNum}行目: name は必須です。";
                    $errorCount++;
                    continue;
                }

                $pId = null;
                if (!empty($parentCode)) {
                    $parent = $this->db->fetch("SELECT id, level FROM organizations WHERE code = ?", [$parentCode]);
                    if (!$parent) {
                        $errors[] = "{$lineNum}行目: 親組織コード「{$parentCode}」が見つかりません。";
                        $errorCount++;
                        continue;
                    }
                    $pId = (int)$parent['id'];
                } elseif (!empty($parentId) && is_numeric($parentId)) {
                    $pId = (int)$parentId;
                }

                if ($code === '') {
                    $code = $this->generateOrgCode($name);
                } elseif ($this->db->fetch("SELECT id FROM organizations WHERE code = ?", [$code])) {
                    $errors[] = "{$lineNum}行目: 組織コード「{$code}」は既に存在します。";
                    $errorCount++;
                    continue;
                }

                // 同一親配下の同名組織のみ重複扱いにする
                if ($pId === null) {
                    $existing = $this->db->fetch(
                        "SELECT id FROM organizations WHERE name = ? AND parent_id IS NULL",
                        [$name]
                    );
                } else {
                    $existing = $this->db->fetch(
                        "SELECT id FROM organizations WHERE name = ? AND parent_id = ?",
                        [$name, $pId]
                    );
                }
                if ($existing) {
                    $errors[] = "{$lineNum}行目: 同じ階層に組織名「{$name}」が既に存在します。";
                    $errorCount++;
                    continue;
                }

                $order = is_numeric($sortOrder) ? (int)$sortOrder : 0;

                // level を決定
                $level = 1;
                if ($pId) {
                    $parent = $this->db->fetch("SELECT level FROM organizations WHERE id = ?", [$pId]);
                    if ($parent) {
                        $level = (int)$parent['level'] + 1;
                    }
                }

                $this->db->execute(
                    "INSERT INTO organizations (name, code, parent_id, level, sort_order, description, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())",
                    [$name, $code, $pId, $level, $order, $description]
                );
                $successCount++;
            }
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            $this->json(['success' => false, 'message' => 'インポート中にエラーが発生しました: ' . $e->getMessage()]);
        }

        $this->json([
            'success' => true,
            'message' => "インポート完了: 成功 {$successCount}件、エラー {$errorCount}件",
            'successCount' => $successCount,
            'errorCount' => $errorCount,
            'errors' => array_slice($errors, 0, 20),
        ]);
    }

    /**
     * アドレス帳CSVインポート
     */
    public function importAddressBook()
    {
        if (!$this->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->json(['success' => false, 'message' => 'CSRFトークンが無効です。'], 403);
        }

        $rows = $this->parseCsvUpload('csv_file');
        if (isset($rows['error'])) {
            $this->json(['success' => false, 'message' => $rows['error']]);
        }

        if (empty($rows)) {
            $this->json(['success' => false, 'message' => 'CSVファイルにデータがありません。']);
        }

        $requiredColumns = ['name'];
        $header = array_keys($rows[0]);
        foreach ($requiredColumns as $col) {
            if (!in_array($col, $header)) {
                $this->json(['success' => false, 'message' => "必須列「{$col}」がCSVに見つかりません。"]);
            }
        }

        // プレビューモード
        if (!empty($_POST['preview'])) {
            $preview = array_slice($rows, 0, 5);
            $this->json(['success' => true, 'preview' => $preview, 'total' => count($rows)]);
        }

        $successCount = 0;
        $errorCount = 0;
        $errors = [];
        $currentUserId = $this->auth->id();

        $this->db->beginTransaction();
        try {
            foreach ($rows as $i => $row) {
                $lineNum = $i + 2;
                $name = trim($row['name'] ?? '');

                if (empty($name)) {
                    $errors[] = "{$lineNum}行目: name は必須です。";
                    $errorCount++;
                    continue;
                }

                $nameKana = trim($row['name_kana'] ?? '');
                $company = trim($row['company'] ?? '');
                $department = trim($row['department'] ?? '');
                $positionTitle = trim($row['position_title'] ?? '');
                $email = trim($row['email'] ?? '');
                $phone = trim($row['phone'] ?? '');
                $mobile = trim($row['mobile'] ?? '');
                $category = trim($row['category'] ?? '');

                $this->db->execute(
                    "INSERT INTO address_book (name, name_kana, company, department, position_title, email, phone, mobile, category, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())",
                    [$name, $nameKana, $company, $department, $positionTitle, $email, $phone, $mobile, $category, $currentUserId]
                );
                $successCount++;
            }
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            $this->json(['success' => false, 'message' => 'インポート中にエラーが発生しました: ' . $e->getMessage()]);
        }

        $this->json([
            'success' => true,
            'message' => "インポート完了: 成功 {$successCount}件、エラー {$errorCount}件",
            'successCount' => $successCount,
            'errorCount' => $errorCount,
            'errors' => array_slice($errors, 0, 20),
        ]);
    }

    /**
     * CSVファイルをアップロードしてパースする
     */
    private function parseCsvUpload($fieldName)
    {
        if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => 'ファイルサイズがサーバーの上限を超えています。',
                UPLOAD_ERR_FORM_SIZE => 'ファイルサイズがフォームの上限を超えています。',
                UPLOAD_ERR_PARTIAL => 'ファイルが部分的にしかアップロードされていません。',
                UPLOAD_ERR_NO_FILE => 'ファイルが選択されていません。',
            ];
            $code = $_FILES[$fieldName]['error'] ?? UPLOAD_ERR_NO_FILE;
            return ['error' => $errorMessages[$code] ?? 'ファイルアップロードに失敗しました。'];
        }

        $file = $_FILES[$fieldName];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'csv') {
            return ['error' => 'CSVファイルのみアップロード可能です。'];
        }

        $content = file_get_contents($file['tmp_name']);
        if ($content === false) {
            return ['error' => 'ファイルの読み込みに失敗しました。'];
        }

        // UTF-8 BOM を除去
        if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
            $content = substr($content, 3);
        }

        // Shift-JIS / EUC-JP を検出してUTF-8に変換
        $encoding = mb_detect_encoding($content, ['UTF-8', 'SJIS-win', 'SJIS', 'EUC-JP', 'ISO-8859-1'], true);
        if ($encoding && $encoding !== 'UTF-8') {
            $content = mb_convert_encoding($content, 'UTF-8', $encoding);
        }

        // 一時ファイルに書き戻してfgetcsvで読み込む
        $tmpFile = tmpfile();
        fwrite($tmpFile, $content);
        rewind($tmpFile);

        $header = fgetcsv($tmpFile);
        if (!$header) {
            fclose($tmpFile);
            return ['error' => 'CSVヘッダーの読み取りに失敗しました。'];
        }

        // ヘッダーのトリム
        $header = array_map('trim', $header);

        $rows = [];
        while (($line = fgetcsv($tmpFile)) !== false) {
            // 空行をスキップ
            if (count($line) === 1 && empty(trim($line[0] ?? ''))) {
                continue;
            }
            $row = [];
            foreach ($header as $idx => $col) {
                $row[$col] = $line[$idx] ?? '';
            }
            $rows[] = $row;
        }
        fclose($tmpFile);

        return $rows;
    }

    /**
     * 組織コードを自動生成
     */
    private function generateOrgCode($name)
    {
        // ローマ字や英数字はそのまま使用、日本語はランダムコードを生成
        $code = preg_replace('/[^a-zA-Z0-9]/', '', $name);
        if (empty($code)) {
            $code = 'ORG' . strtoupper(substr(md5($name . microtime()), 0, 6));
        }
        $code = strtoupper(substr($code, 0, 15));

        // ユニーク確認
        $base = $code;
        $suffix = 1;
        while ($this->db->fetch("SELECT id FROM organizations WHERE code = ?", [$code])) {
            $code = substr($base, 0, 12) . str_pad($suffix, 3, '0', STR_PAD_LEFT);
            $suffix++;
        }

        return $code;
    }
}

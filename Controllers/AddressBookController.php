<?php
namespace Controllers;

use Core\Controller;
use Core\Database;

class AddressBookController extends Controller
{
    private $db;
    private $uploadDir;

    public function __construct()
    {
        parent::__construct();
        $this->db = Database::getInstance();
        $this->uploadDir = __DIR__ . '/../uploads/business_cards/';

        if (!$this->auth->check()) {
            $this->redirect(BASE_PATH . '/login');
        }
    }

    public function index()
    {
        $search = trim($_GET['q'] ?? '');
        $category = $_GET['category'] ?? '';

        $params = [];
        $where = ['1=1'];

        if ($search !== '') {
            $where[] = "(ab.name LIKE ? OR ab.company LIKE ? OR ab.email LIKE ? OR ab.phone LIKE ?)";
            $params = array_merge($params, ["%$search%", "%$search%", "%$search%", "%$search%"]);
        }
        if ($category !== '') {
            $where[] = "ab.category = ?";
            $params[] = $category;
        }

        $whereClause = implode(' AND ', $where);

        // テーブルが存在するか確認
        $contacts = [];
        $categories = [];
        try {
            $contacts = $this->db->fetchAll(
                "SELECT ab.* FROM address_book ab WHERE {$whereClause} ORDER BY ab.name_kana ASC, ab.name ASC LIMIT 500",
                $params
            );
            $catRows = $this->db->fetchAll("SELECT DISTINCT category FROM address_book WHERE category IS NOT NULL AND category != '' ORDER BY category");
            $categories = array_column($catRows, 'category');
        } catch (\Exception $e) {
            // テーブルが存在しない場合
            $contacts = null;
        }

        $this->view('address_book/index', [
            'title' => 'アドレス帳',
            'contacts' => $contacts,
            'categories' => $categories,
            'search' => $search,
            'currentCategory' => $category
        ]);
    }

    public function create()
    {
        $this->view('address_book/form', [
            'title' => '連絡先の追加',
            'contact' => null,
            'mode' => 'create',
            'businessCard' => null
        ]);
    }

    public function store()
    {
        $data = $_POST;
        try {
            $this->db->execute(
                "INSERT INTO address_book (name, name_kana, company, department, position_title, email, phone, mobile, fax, postal_code, address, url, category, memo, has_business_card, business_card_image, created_by, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())",
                [
                    $data['name'] ?? '',
                    $data['name_kana'] ?? '',
                    $data['company'] ?? '',
                    $data['department'] ?? '',
                    $data['position_title'] ?? '',
                    $data['email'] ?? '',
                    $data['phone'] ?? '',
                    $data['mobile'] ?? '',
                    $data['fax'] ?? '',
                    $data['postal_code'] ?? '',
                    $data['address'] ?? '',
                    $data['url'] ?? '',
                    $data['category'] ?? '',
                    $data['memo'] ?? '',
                    0,
                    null,
                    $this->auth->id()
                ]
            );

            $contactId = $this->db->lastInsertId();

            // 名刺画像がアップロードされた場合
            if (!empty($_FILES['business_card_image']) && $_FILES['business_card_image']['error'] === UPLOAD_ERR_OK) {
                $this->processCardUpload($contactId, $_FILES['business_card_image']);
            }

            $_SESSION['flash_message'] = '連絡先を追加しました。';
            $this->redirect(BASE_PATH . '/address-book');
        } catch (\Exception $e) {
            $_SESSION['flash_error'] = '保存に失敗しました: ' . $e->getMessage();
            $this->redirect(BASE_PATH . '/address-book/create');
        }
    }

    public function show($params)
    {
        $id = $params['id'] ?? 0;
        try {
            $contact = $this->db->fetch("SELECT * FROM address_book WHERE id = ?", [$id]);
        } catch (\Exception $e) {
            $contact = null;
        }

        if (!$contact) {
            $_SESSION['flash_error'] = '連絡先が見つかりません。';
            $this->redirect(BASE_PATH . '/address-book');
            return;
        }

        // 名刺情報を取得
        $businessCard = null;
        try {
            $businessCard = $this->db->fetch(
                "SELECT * FROM business_cards WHERE contact_id = ? ORDER BY created_at DESC LIMIT 1",
                [$id]
            );
        } catch (\Exception $e) {
            // テーブルが無い場合は無視
        }

        parent::view('address_book/view', [
            'title' => htmlspecialchars($contact['name']) . ' - アドレス帳',
            'contact' => $contact,
            'businessCard' => $businessCard
        ]);
    }

    public function edit($params)
    {
        $id = $params['id'] ?? 0;
        try {
            $contact = $this->db->fetch("SELECT * FROM address_book WHERE id = ?", [$id]);
        } catch (\Exception $e) {
            $contact = null;
        }

        if (!$contact) {
            $_SESSION['flash_error'] = '連絡先が見つかりません。';
            $this->redirect(BASE_PATH . '/address-book');
            return;
        }

        // 名刺情報を取得
        $businessCard = null;
        try {
            $businessCard = $this->db->fetch(
                "SELECT * FROM business_cards WHERE contact_id = ? ORDER BY created_at DESC LIMIT 1",
                [$id]
            );
        } catch (\Exception $e) {
            // ignore
        }

        $this->view('address_book/form', [
            'title' => '連絡先の編集',
            'contact' => $contact,
            'mode' => 'edit',
            'businessCard' => $businessCard
        ]);
    }

    public function update($params)
    {
        $id = $params['id'] ?? 0;
        $data = $_POST;
        try {
            $this->db->execute(
                "UPDATE address_book SET name=?, name_kana=?, company=?, department=?, position_title=?, email=?, phone=?, mobile=?, fax=?, postal_code=?, address=?, url=?, category=?, memo=?, updated_at=NOW() WHERE id=?",
                [
                    $data['name'] ?? '',
                    $data['name_kana'] ?? '',
                    $data['company'] ?? '',
                    $data['department'] ?? '',
                    $data['position_title'] ?? '',
                    $data['email'] ?? '',
                    $data['phone'] ?? '',
                    $data['mobile'] ?? '',
                    $data['fax'] ?? '',
                    $data['postal_code'] ?? '',
                    $data['address'] ?? '',
                    $data['url'] ?? '',
                    $data['category'] ?? '',
                    $data['memo'] ?? '',
                    $id
                ]
            );

            // 名刺画像がアップロードされた場合
            if (!empty($_FILES['business_card_image']) && $_FILES['business_card_image']['error'] === UPLOAD_ERR_OK) {
                $this->processCardUpload($id, $_FILES['business_card_image']);
            }

            $_SESSION['flash_message'] = '連絡先を更新しました。';
        } catch (\Exception $e) {
            $_SESSION['flash_error'] = '更新に失敗しました。';
        }
        $this->redirect(BASE_PATH . '/address-book');
    }

    public function delete($params)
    {
        $id = $params['id'] ?? 0;
        try {
            // 名刺画像ファイルも削除
            $cards = $this->db->fetchAll("SELECT image_path FROM business_cards WHERE contact_id = ?", [$id]);
            foreach ($cards as $card) {
                $filePath = $this->uploadDir . $card['image_path'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
            $this->db->execute("DELETE FROM address_book WHERE id = ?", [$id]);
            $_SESSION['flash_message'] = '連絡先を削除しました。';
        } catch (\Exception $e) {
            $_SESSION['flash_error'] = '削除に失敗しました。';
        }
        $this->redirect(BASE_PATH . '/address-book');
    }

    /**
     * 名刺画像アップロード（AJAX）
     */
    public function uploadBusinessCard($params)
    {
        $id = $params['id'] ?? 0;

        if (empty($_FILES['business_card_image']) || $_FILES['business_card_image']['error'] !== UPLOAD_ERR_OK) {
            $this->json(['success' => false, 'message' => '画像ファイルがありません。'], 400);
            return;
        }

        try {
            $contact = $this->db->fetch("SELECT * FROM address_book WHERE id = ?", [$id]);
            if (!$contact) {
                $this->json(['success' => false, 'message' => '連絡先が見つかりません。'], 404);
                return;
            }

            $result = $this->processCardUpload($id, $_FILES['business_card_image']);
            $this->json([
                'success' => true,
                'message' => '名刺画像をアップロードしました。',
                'image_url' => BASE_PATH . '/address-book/card-image/' . $id,
                'card_id' => $result['card_id']
            ]);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => 'アップロードに失敗しました: ' . $e->getMessage()], 500);
        }
    }

    /**
     * 名刺画像アップロード（複数枚一括）
     */
    public function uploadBusinessCardBatch($params)
    {
        $id = $params['id'] ?? 0;

        if (empty($_FILES['business_card_images'])) {
            $this->json(['success' => false, 'message' => '画像ファイルがありません。'], 400);
            return;
        }

        try {
            $contact = $this->db->fetch("SELECT * FROM address_book WHERE id = ?", [$id]);
            if (!$contact) {
                $this->json(['success' => false, 'message' => '連絡先が見つかりません。'], 404);
                return;
            }

            $files = $_FILES['business_card_images'];
            $results = [];
            $count = is_array($files['name']) ? count($files['name']) : 0;

            for ($i = 0; $i < $count; $i++) {
                if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;

                $file = [
                    'name' => $files['name'][$i],
                    'type' => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error' => $files['error'][$i],
                    'size' => $files['size'][$i]
                ];
                $result = $this->processCardUpload($id, $file);
                $results[] = $result;
            }

            $this->json([
                'success' => true,
                'message' => count($results) . '枚の名刺画像をアップロードしました。',
                'results' => $results
            ]);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => 'アップロードに失敗しました: ' . $e->getMessage()], 500);
        }
    }

    /**
     * 名刺OCR処理
     */
    public function ocrBusinessCard($params)
    {
        $id = $params['id'] ?? 0;

        try {
            $card = $this->db->fetch(
                "SELECT bc.*, ab.name as contact_name FROM business_cards bc JOIN address_book ab ON ab.id = bc.contact_id WHERE bc.contact_id = ? ORDER BY bc.created_at DESC LIMIT 1",
                [$id]
            );

            if (!$card) {
                $this->json(['success' => false, 'message' => '名刺画像が見つかりません。'], 404);
                return;
            }

            $imagePath = $this->uploadDir . $card['image_path'];
            if (!file_exists($imagePath)) {
                $this->json(['success' => false, 'message' => '画像ファイルが見つかりません。'], 404);
                return;
            }

            // OCR実行
            $ocrResult = $this->performOcr($imagePath);

            // OCR結果を保存
            $this->db->execute(
                "UPDATE business_cards SET ocr_raw_text = ?, ocr_status = ? WHERE id = ?",
                [$ocrResult['raw_text'], $ocrResult['status'], $card['id']]
            );

            // テキストからフィールドを解析
            $parsedFields = $this->parseOcrText($ocrResult['raw_text']);

            // 重複チェック
            $duplicates = $this->checkDuplicates($parsedFields, $id);

            $this->json([
                'success' => true,
                'ocr_status' => $ocrResult['status'],
                'raw_text' => $ocrResult['raw_text'],
                'parsed_fields' => $parsedFields,
                'duplicates' => $duplicates,
                'message' => $ocrResult['status'] === 'completed' ? 'OCR読み取りが完了しました。' : 'OCRの実行に失敗しました。手動で入力してください。'
            ]);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => 'OCR処理に失敗しました: ' . $e->getMessage()], 500);
        }
    }

    /**
     * 名刺画像表示
     */
    public function previewBusinessCard($params)
    {
        $id = $params['id'] ?? 0;

        try {
            $card = $this->db->fetch(
                "SELECT * FROM business_cards WHERE contact_id = ? ORDER BY created_at DESC LIMIT 1",
                [$id]
            );

            if (!$card) {
                http_response_code(404);
                exit;
            }

            $filePath = $this->uploadDir . $card['image_path'];
            if (!file_exists($filePath)) {
                http_response_code(404);
                exit;
            }

            $mimeType = mime_content_type($filePath);
            header('Content-Type: ' . $mimeType);
            header('Content-Length: ' . filesize($filePath));
            header('Cache-Control: public, max-age=86400');
            readfile($filePath);
            exit;
        } catch (\Exception $e) {
            http_response_code(500);
            exit;
        }
    }

    /**
     * 名刺画像削除
     */
    public function deleteBusinessCard($params)
    {
        $id = $params['id'] ?? 0;

        try {
            $cards = $this->db->fetchAll("SELECT * FROM business_cards WHERE contact_id = ?", [$id]);
            foreach ($cards as $card) {
                $filePath = $this->uploadDir . $card['image_path'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            $this->db->execute("DELETE FROM business_cards WHERE contact_id = ?", [$id]);
            $this->db->execute(
                "UPDATE address_book SET has_business_card = 0, business_card_image = NULL WHERE id = ?",
                [$id]
            );

            // AJAXリクエストの場合
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                $this->json(['success' => true, 'message' => '名刺画像を削除しました。']);
            } else {
                $_SESSION['flash_message'] = '名刺画像を削除しました。';
                $this->redirect(BASE_PATH . '/address-book/view/' . $id);
            }
        } catch (\Exception $e) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                $this->json(['success' => false, 'message' => '削除に失敗しました。'], 500);
            } else {
                $_SESSION['flash_error'] = '名刺画像の削除に失敗しました。';
                $this->redirect(BASE_PATH . '/address-book/view/' . $id);
            }
        }
    }

    /**
     * vCardエクスポート
     */
    public function exportVcard($params)
    {
        $id = $params['id'] ?? 0;

        try {
            $contact = $this->db->fetch("SELECT * FROM address_book WHERE id = ?", [$id]);
            if (!$contact) {
                $_SESSION['flash_error'] = '連絡先が見つかりません。';
                $this->redirect(BASE_PATH . '/address-book');
                return;
            }

            $vcard = "BEGIN:VCARD\r\n";
            $vcard .= "VERSION:3.0\r\n";
            $vcard .= "FN:" . ($contact['name'] ?? '') . "\r\n";
            $vcard .= "N:" . ($contact['name'] ?? '') . ";;;;\r\n";
            if (!empty($contact['company'])) {
                $vcard .= "ORG:" . $contact['company'];
                if (!empty($contact['department'])) {
                    $vcard .= ";" . $contact['department'];
                }
                $vcard .= "\r\n";
            }
            if (!empty($contact['position_title'])) {
                $vcard .= "TITLE:" . $contact['position_title'] . "\r\n";
            }
            if (!empty($contact['email'])) {
                $vcard .= "EMAIL;TYPE=WORK:" . $contact['email'] . "\r\n";
            }
            if (!empty($contact['phone'])) {
                $vcard .= "TEL;TYPE=WORK:" . $contact['phone'] . "\r\n";
            }
            if (!empty($contact['mobile'])) {
                $vcard .= "TEL;TYPE=CELL:" . $contact['mobile'] . "\r\n";
            }
            if (!empty($contact['fax'])) {
                $vcard .= "TEL;TYPE=FAX:" . $contact['fax'] . "\r\n";
            }
            if (!empty($contact['address'])) {
                $vcard .= "ADR;TYPE=WORK:;;" . $contact['address'] . ";;;;" . ($contact['postal_code'] ?? '') . "\r\n";
            }
            if (!empty($contact['url'])) {
                $vcard .= "URL:" . $contact['url'] . "\r\n";
            }
            if (!empty($contact['memo'])) {
                $vcard .= "NOTE:" . str_replace(["\r\n", "\n"], "\\n", $contact['memo']) . "\r\n";
            }
            $vcard .= "END:VCARD\r\n";

            $filename = preg_replace('/[^a-zA-Z0-9_\-\p{L}]/u', '_', $contact['name']) . '.vcf';

            header('Content-Type: text/vcard; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . strlen($vcard));
            echo $vcard;
            exit;
        } catch (\Exception $e) {
            $_SESSION['flash_error'] = 'エクスポートに失敗しました。';
            $this->redirect(BASE_PATH . '/address-book');
        }
    }

    /**
     * 名刺から新規連絡先作成ページ
     */
    public function createFromCard()
    {
        $this->view('address_book/form', [
            'title' => '名刺から連絡先を追加',
            'contact' => null,
            'mode' => 'create_from_card',
            'businessCard' => null
        ]);
    }

    /**
     * 名刺アップロード → 一時保存 → OCR → フォーム表示
     */
    public function uploadAndOcr()
    {
        if (empty($_FILES['business_card_image']) || $_FILES['business_card_image']['error'] !== UPLOAD_ERR_OK) {
            $this->json(['success' => false, 'message' => '画像ファイルがありません。'], 400);
            return;
        }

        try {
            $file = $_FILES['business_card_image'];

            // ファイルバリデーション
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/bmp'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mimeType, $allowedTypes)) {
                $this->json(['success' => false, 'message' => '対応していない画像形式です。JPEG, PNG, GIF, WebP, BMPに対応しています。'], 400);
                return;
            }

            if ($file['size'] > 10 * 1024 * 1024) {
                $this->json(['success' => false, 'message' => 'ファイルサイズが大きすぎます（上限: 10MB）。'], 400);
                return;
            }

            // 一時保存
            $ext = $this->getImageExtension($mimeType);
            $filename = 'temp_' . uniqid() . '_' . time() . '.' . $ext;
            $destPath = $this->uploadDir . $filename;

            if (!is_dir($this->uploadDir)) {
                mkdir($this->uploadDir, 0755, true);
            }

            if (!move_uploaded_file($file['tmp_name'], $destPath)) {
                $this->json(['success' => false, 'message' => 'ファイルの保存に失敗しました。'], 500);
                return;
            }

            // OCR実行
            $ocrResult = $this->performOcr($destPath);
            $parsedFields = $this->parseOcrText($ocrResult['raw_text']);

            // 重複チェック
            $duplicates = $this->checkDuplicates($parsedFields);

            $this->json([
                'success' => true,
                'temp_filename' => $filename,
                'image_url' => BASE_PATH . '/address-book/temp-card-image/' . $filename,
                'ocr_status' => $ocrResult['status'],
                'raw_text' => $ocrResult['raw_text'],
                'parsed_fields' => $parsedFields,
                'duplicates' => $duplicates,
                'message' => $ocrResult['status'] === 'completed' ? 'OCR読み取りが完了しました。' : 'OCRの実行に失敗しました。手動で入力してください。'
            ]);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => '処理に失敗しました: ' . $e->getMessage()], 500);
        }
    }

    /**
     * 一時名刺画像表示
     */
    public function tempCardImage($params)
    {
        $filename = $params['filename'] ?? '';
        $filename = basename($filename); // セキュリティ: ディレクトリトラバーサル防止

        if (strpos($filename, 'temp_') !== 0) {
            http_response_code(403);
            exit;
        }

        $filePath = $this->uploadDir . $filename;
        if (!file_exists($filePath)) {
            http_response_code(404);
            exit;
        }

        $mimeType = mime_content_type($filePath);
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }

    /**
     * 名刺からの連絡先保存
     */
    public function storeFromCard()
    {
        $data = $_POST;
        $tempFilename = $data['temp_card_filename'] ?? '';

        try {
            $this->db->execute(
                "INSERT INTO address_book (name, name_kana, company, department, position_title, email, phone, mobile, fax, postal_code, address, url, category, memo, has_business_card, business_card_image, created_by, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())",
                [
                    $data['name'] ?? '',
                    $data['name_kana'] ?? '',
                    $data['company'] ?? '',
                    $data['department'] ?? '',
                    $data['position_title'] ?? '',
                    $data['email'] ?? '',
                    $data['phone'] ?? '',
                    $data['mobile'] ?? '',
                    $data['fax'] ?? '',
                    $data['postal_code'] ?? '',
                    $data['address'] ?? '',
                    $data['url'] ?? '',
                    $data['category'] ?? '',
                    $data['memo'] ?? '',
                    !empty($tempFilename) ? 1 : 0,
                    null,
                    $this->auth->id()
                ]
            );

            $contactId = $this->db->lastInsertId();

            // 一時ファイルを正式ファイルに移動
            if (!empty($tempFilename)) {
                $tempPath = $this->uploadDir . basename($tempFilename);
                if (file_exists($tempPath)) {
                    $ext = pathinfo($tempFilename, PATHINFO_EXTENSION);
                    $newFilename = 'card_' . $contactId . '_' . time() . '.' . $ext;
                    $newPath = $this->uploadDir . $newFilename;
                    rename($tempPath, $newPath);

                    // business_cards テーブルに保存
                    $this->db->execute(
                        "INSERT INTO business_cards (contact_id, image_path, ocr_raw_text, ocr_status) VALUES (?, ?, ?, ?)",
                        [$contactId, $newFilename, $data['ocr_raw_text'] ?? '', 'completed']
                    );

                    $this->db->execute(
                        "UPDATE address_book SET has_business_card = 1, business_card_image = ? WHERE id = ?",
                        [$newFilename, $contactId]
                    );
                }
            }

            $_SESSION['flash_message'] = '名刺から連絡先を追加しました。';
            $this->redirect(BASE_PATH . '/address-book/view/' . $contactId);
        } catch (\Exception $e) {
            $_SESSION['flash_error'] = '保存に失敗しました: ' . $e->getMessage();
            $this->redirect(BASE_PATH . '/address-book/create-from-card');
        }
    }

    // ========================================
    // Private methods
    // ========================================

    /**
     * 名刺画像アップロード処理
     */
    private function processCardUpload($contactId, $file)
    {
        // ファイルバリデーション
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/bmp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedTypes)) {
            throw new \Exception('対応していない画像形式です。');
        }

        if ($file['size'] > 10 * 1024 * 1024) {
            throw new \Exception('ファイルサイズが大きすぎます（上限: 10MB）。');
        }

        // 保存先ディレクトリ確認
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }

        $ext = $this->getImageExtension($mimeType);
        $filename = 'card_' . $contactId . '_' . time() . '.' . $ext;
        $destPath = $this->uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            throw new \Exception('ファイルの保存に失敗しました。');
        }

        // 既存の名刺画像があれば削除
        $oldCards = $this->db->fetchAll("SELECT * FROM business_cards WHERE contact_id = ?", [$contactId]);
        foreach ($oldCards as $old) {
            $oldPath = $this->uploadDir . $old['image_path'];
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
        }
        $this->db->execute("DELETE FROM business_cards WHERE contact_id = ?", [$contactId]);

        // DBに保存
        $this->db->execute(
            "INSERT INTO business_cards (contact_id, image_path, ocr_status) VALUES (?, ?, 'pending')",
            [$contactId, $filename]
        );
        $cardId = $this->db->lastInsertId();

        // address_bookテーブル更新
        $this->db->execute(
            "UPDATE address_book SET has_business_card = 1, business_card_image = ? WHERE id = ?",
            [$filename, $contactId]
        );

        return ['card_id' => $cardId, 'filename' => $filename];
    }

    /**
     * OCR実行
     */
    private function performOcr($imagePath)
    {
        $rawText = '';
        $status = 'failed';

        // Tesseract OCRを試行
        $tesseractPath = $this->findTesseract();
        if ($tesseractPath) {
            $outputBase = tempnam(sys_get_temp_dir(), 'ocr_');
            $outputFile = $outputBase . '.txt';

            // 日本語+英語でOCR実行
            $cmd = escapeshellcmd($tesseractPath) . ' '
                 . escapeshellarg($imagePath) . ' '
                 . escapeshellarg($outputBase)
                 . ' -l jpn+eng 2>&1';

            exec($cmd, $output, $returnCode);

            // jpn+engが失敗した場合はengだけで再試行
            if ($returnCode !== 0) {
                $cmd = escapeshellcmd($tesseractPath) . ' '
                     . escapeshellarg($imagePath) . ' '
                     . escapeshellarg($outputBase)
                     . ' -l eng 2>&1';
                exec($cmd, $output, $returnCode);
            }

            // それも失敗した場合は言語指定なしで
            if ($returnCode !== 0) {
                $cmd = escapeshellcmd($tesseractPath) . ' '
                     . escapeshellarg($imagePath) . ' '
                     . escapeshellarg($outputBase)
                     . ' 2>&1';
                exec($cmd, $output, $returnCode);
            }

            if ($returnCode === 0 && file_exists($outputFile)) {
                $rawText = file_get_contents($outputFile);
                $status = 'completed';
                unlink($outputFile);
            }

            // 一時ファイルのクリーンアップ
            if (file_exists($outputBase)) {
                unlink($outputBase);
            }
        }

        return [
            'raw_text' => $rawText,
            'status' => $status
        ];
    }

    /**
     * Tesseractの実行パスを検出
     */
    private function findTesseract()
    {
        $paths = [
            '/usr/bin/tesseract',
            '/usr/local/bin/tesseract',
            '/opt/homebrew/bin/tesseract',
            'tesseract'
        ];

        foreach ($paths as $path) {
            exec('which ' . escapeshellarg($path) . ' 2>/dev/null', $output, $returnCode);
            if ($returnCode === 0) {
                return trim(implode('', $output));
            }
            $output = [];

            // 直接パスの場合
            if (file_exists($path) && is_executable($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * OCRテキストからフィールドを解析
     */
    private function parseOcrText($text)
    {
        $fields = [
            'name' => '',
            'name_kana' => '',
            'company' => '',
            'department' => '',
            'position_title' => '',
            'email' => '',
            'phone' => '',
            'mobile' => '',
            'fax' => '',
            'postal_code' => '',
            'address' => '',
            'url' => ''
        ];

        if (empty($text)) {
            return $fields;
        }

        $lines = array_map('trim', explode("\n", $text));
        $lines = array_filter($lines, function($line) { return $line !== ''; });

        // メールアドレス
        if (preg_match('/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/', $text, $m)) {
            $fields['email'] = $m[0];
        }

        // URL
        if (preg_match('/https?:\/\/[a-zA-Z0-9.\-\/\?=&_%+#]+/', $text, $m)) {
            $fields['url'] = $m[0];
        } elseif (preg_match('/(?:www\.)[a-zA-Z0-9.\-\/]+\.[a-zA-Z]{2,}/', $text, $m)) {
            $fields['url'] = 'http://' . $m[0];
        }

        // 郵便番号
        if (preg_match('/〒?\s*(\d{3}[\-ー]\d{4})/', $text, $m)) {
            $fields['postal_code'] = str_replace('ー', '-', $m[1]);
        }

        // 電話番号パターン
        $phonePatterns = [];
        if (preg_match_all('/(?:TEL|Tel|tel|電話|Phone|ph)[\s:：]*([0-9\-（）().\s]{8,20})/', $text, $m)) {
            $phonePatterns['phone'] = preg_replace('/[\s（）()]/', '', $m[1][0]);
            $fields['phone'] = trim(preg_replace('/[（()）\s]/', '', $m[1][0]));
        }
        if (preg_match('/(?:FAX|Fax|fax|ファックス|ファクス)[\s:：]*([0-9\-（）().\s]{8,20})/', $text, $m)) {
            $fields['fax'] = trim(preg_replace('/[（()）\s]/', '', $m[1]));
        }
        if (preg_match('/(?:携帯|Mobile|MOBILE|Cell|cell)[\s:：]*([0-9\-（）().\s]{8,20})/', $text, $m)) {
            $fields['mobile'] = trim(preg_replace('/[（()）\s]/', '', $m[1]));
        }

        // ラベルなしの電話番号を取得
        if (empty($fields['phone'])) {
            if (preg_match('/(?:^|\s)(0\d{1,4}[\-]\d{1,4}[\-]\d{3,4})/', $text, $m)) {
                $fields['phone'] = $m[1];
            }
        }

        // 住所（郵便番号の後に続く文字列）
        if (preg_match('/〒?\s*\d{3}[\-ー]\d{4}\s*(.+?)(?:\n|TEL|Tel|FAX|Fax|電話|$)/s', $text, $m)) {
            $fields['address'] = trim($m[1]);
        } elseif (preg_match('/((?:東京|北海道|大阪|京都|神奈川|埼玉|千葉|愛知|福岡|兵庫|静岡|広島|茨城|新潟|宮城|長野|岐阜|群馬|栃木|岡山|三重|熊本|鹿児島|山口|愛媛|長崎|奈良|青森|岩手|秋田|山形|福島|富山|石川|福井|山梨|滋賀|和歌山|鳥取|島根|徳島|香川|高知|佐賀|大分|宮崎|沖縄)(?:都|道|府|県).+?)(?:\n|TEL|Tel|$)/u', $text, $m)) {
            $fields['address'] = trim($m[1]);
        }

        // 部署・役職パターン
        $positionKeywords = ['代表取締役', '社長', '副社長', '専務', '常務', '取締役', '部長', '課長', '係長', '主任', 'マネージャー', 'ディレクター', 'CEO', 'CTO', 'CFO', 'COO', 'VP', 'Director', 'Manager', 'President', 'Chairman'];
        $deptKeywords = ['事業部', '営業部', '開発部', '企画部', '総務部', '人事部', '経理部', '技術部', '製造部', '品質管理', '研究開発', '部', '課', '室', 'グループ', 'チーム', 'Division', 'Department', 'Dept'];

        foreach ($lines as $line) {
            foreach ($positionKeywords as $kw) {
                if (mb_strpos($line, $kw) !== false && empty($fields['position_title'])) {
                    $fields['position_title'] = $line;
                    break;
                }
            }
            foreach ($deptKeywords as $kw) {
                if (mb_strpos($line, $kw) !== false && empty($fields['department'])) {
                    // 部署名だけで、他のフィールドに含まれていない場合
                    if ($line !== ($fields['position_title'] ?? '')) {
                        $fields['department'] = $line;
                    }
                    break;
                }
            }
        }

        // 会社名推定：株式会社、有限会社、合同会社を含む行
        foreach ($lines as $line) {
            if (preg_match('/(株式会社|有限会社|合同会社|合資会社|一般社団法人|一般財団法人|NPO法人|LLC|Inc\.|Corp\.|Co\.,?\s*Ltd\.?|Ltd\.?|Corporation)/u', $line)) {
                $fields['company'] = $line;
                break;
            }
        }

        // 名前推定：カタカナ行をフリガナ候補、その前後の行を名前候補
        foreach ($lines as $i => $line) {
            // カタカナのみの行（フリガナ）
            if (preg_match('/^[\p{Katakana}ー\s　]+$/u', $line) && mb_strlen($line) >= 2) {
                $fields['name_kana'] = $line;
                // 次の行が漢字を含む名前の可能性
                if (isset($lines[$i + 1]) && preg_match('/[\p{Han}]/u', $lines[$i + 1]) && mb_strlen($lines[$i + 1]) <= 20) {
                    $nextLine = $lines[$i + 1];
                    // 会社名や部署と被っていなければ名前として採用
                    if ($nextLine !== $fields['company'] && $nextLine !== $fields['department'] && $nextLine !== $fields['position_title']) {
                        $fields['name'] = $nextLine;
                    }
                }
                break;
            }
        }

        // 名前がまだ取れていなければ、フリガナの前の行を試す
        if (empty($fields['name'])) {
            foreach ($lines as $i => $line) {
                if (preg_match('/^[\p{Katakana}ー\s　]+$/u', $line) && mb_strlen($line) >= 2) {
                    if (isset($lines[$i - 1]) && preg_match('/[\p{Han}]/u', $lines[$i - 1]) && mb_strlen($lines[$i - 1]) <= 20) {
                        $prevLine = $lines[$i - 1];
                        if ($prevLine !== $fields['company'] && $prevLine !== $fields['department']) {
                            $fields['name'] = $prevLine;
                        }
                    }
                    break;
                }
            }
        }

        return $fields;
    }

    /**
     * 重複チェック
     */
    private function checkDuplicates($parsedFields, $excludeId = null)
    {
        $duplicates = [];

        try {
            $conditions = [];
            $params = [];

            if (!empty($parsedFields['email'])) {
                $conditions[] = "email = ?";
                $params[] = $parsedFields['email'];
            }
            if (!empty($parsedFields['phone'])) {
                $conditions[] = "phone = ?";
                $params[] = $parsedFields['phone'];
            }
            if (!empty($parsedFields['mobile'])) {
                $conditions[] = "mobile = ?";
                $params[] = $parsedFields['mobile'];
            }

            if (empty($conditions)) {
                return $duplicates;
            }

            $where = '(' . implode(' OR ', $conditions) . ')';
            if ($excludeId) {
                $where .= ' AND id != ?';
                $params[] = $excludeId;
            }

            $duplicates = $this->db->fetchAll(
                "SELECT id, name, company, email, phone FROM address_book WHERE {$where} LIMIT 5",
                $params
            );
        } catch (\Exception $e) {
            // ignore
        }

        return $duplicates;
    }

    /**
     * MIMEタイプから拡張子を取得
     */
    private function getImageExtension($mimeType)
    {
        $map = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/bmp' => 'bmp'
        ];
        return $map[$mimeType] ?? 'jpg';
    }
}

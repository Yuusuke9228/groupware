<?php
namespace Controllers;

use Core\Controller;
use Core\Database;

class AddressBookController extends Controller
{
    private $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = Database::getInstance();

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
            'mode' => 'create'
        ]);
    }

    public function store()
    {
        $data = $_POST;
        try {
            $this->db->execute(
                "INSERT INTO address_book (name, name_kana, company, department, position_title, email, phone, mobile, fax, postal_code, address, url, category, memo, created_by, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())",
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
                    $this->auth->id()
                ]
            );
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

        parent::view('address_book/view', [
            'title' => htmlspecialchars($contact['name']) . ' - アドレス帳',
            'contact' => $contact
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

        $this->view('address_book/form', [
            'title' => '連絡先の編集',
            'contact' => $contact,
            'mode' => 'edit'
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
            $this->db->execute("DELETE FROM address_book WHERE id = ?", [$id]);
            $_SESSION['flash_message'] = '連絡先を削除しました。';
        } catch (\Exception $e) {
            $_SESSION['flash_error'] = '削除に失敗しました。';
        }
        $this->redirect(BASE_PATH . '/address-book');
    }
}

<?php
namespace Controllers;

use Core\Controller;
use Core\Database;
use Models\Organization;

class FacilityController extends Controller
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
        $date = $_GET['date'] ?? date('Y-m-d');

        // 施設一覧を取得
        $facilities = [];
        $reservations = [];
        try {
            $facilities = $this->db->fetchAll("SELECT * FROM facilities ORDER BY sort_order ASC, name ASC");
            $reservations = $this->db->fetchAll(
                "SELECT fr.*, f.name as facility_name, u.display_name as reserver_name
                 FROM facility_reservations fr
                 JOIN facilities f ON f.id = fr.facility_id
                 JOIN users u ON u.id = fr.user_id
                 WHERE DATE(fr.start_time) = ?
                 ORDER BY fr.start_time ASC",
                [$date]
            );
        } catch (\Exception $e) {
            $facilities = null;
        }

        $this->view('facility/index', [
            'title' => '施設予約',
            'facilities' => $facilities,
            'reservations' => $reservations,
            'date' => $date,
            'jsFiles' => ['schedule.js']
        ]);
    }

    public function create()
    {
        $facilityId = $_GET['facility_id'] ?? '';
        $date = $_GET['date'] ?? date('Y-m-d');
        $startTime = $_GET['start'] ?? '';

        $facilities = [];
        try {
            $facilities = $this->db->fetchAll("SELECT * FROM facilities ORDER BY sort_order ASC, name ASC");
        } catch (\Exception $e) {
            $facilities = [];
        }

        $this->view('facility/form', [
            'title' => '施設予約の作成',
            'reservation' => null,
            'facilities' => $facilities,
            'selectedFacilityId' => $facilityId,
            'date' => $date,
            'startTime' => $startTime,
            'mode' => 'create'
        ]);
    }

    public function store()
    {
        $data = $_POST;
        try {
            $this->db->execute(
                "INSERT INTO facility_reservations (facility_id, user_id, title, start_time, end_time, memo, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())",
                [
                    $data['facility_id'],
                    $this->auth->id(),
                    $data['title'] ?? '',
                    $data['start_time'],
                    $data['end_time'],
                    $data['memo'] ?? ''
                ]
            );
            $_SESSION['flash_message'] = '施設を予約しました。';
        } catch (\Exception $e) {
            $_SESSION['flash_error'] = '予約に失敗しました: ' . $e->getMessage();
        }
        $this->redirect(BASE_PATH . '/facility');
    }

    public function delete($params)
    {
        $id = $params['id'] ?? 0;
        try {
            $this->db->execute("DELETE FROM facility_reservations WHERE id = ? AND user_id = ?", [$id, $this->auth->id()]);
            $_SESSION['flash_message'] = '予約を取り消しました。';
        } catch (\Exception $e) {
            $_SESSION['flash_error'] = '取り消しに失敗しました。';
        }
        $this->redirect(BASE_PATH . '/facility');
    }

    // 管理者用: 施設の追加
    public function manage()
    {
        if (!$this->auth->isAdmin()) {
            $this->redirect(BASE_PATH . '/facility');
            return;
        }

        $facilities = [];
        try {
            $facilities = $this->db->fetchAll("SELECT * FROM facilities ORDER BY sort_order ASC, name ASC");
        } catch (\Exception $e) {
            $facilities = null;
        }

        $this->view('facility/manage', [
            'title' => '施設管理',
            'facilities' => $facilities
        ]);
    }

    public function addFacility()
    {
        if (!$this->auth->isAdmin()) {
            $this->redirect(BASE_PATH . '/facility');
            return;
        }

        $data = $_POST;
        try {
            $this->db->execute(
                "INSERT INTO facilities (name, description, capacity, sort_order, created_at) VALUES (?, ?, ?, ?, NOW())",
                [$data['name'], $data['description'] ?? '', $data['capacity'] ?? 0, $data['sort_order'] ?? 0]
            );
            $_SESSION['flash_message'] = '施設を追加しました。';
        } catch (\Exception $e) {
            $_SESSION['flash_error'] = '追加に失敗しました: ' . $e->getMessage();
        }
        $this->redirect(BASE_PATH . '/facility/manage');
    }

    public function deleteFacility($params)
    {
        if (!$this->auth->isAdmin()) {
            $this->redirect(BASE_PATH . '/facility');
            return;
        }

        $id = $params['id'] ?? 0;
        try {
            $this->db->execute("DELETE FROM facilities WHERE id = ?", [$id]);
            $_SESSION['flash_message'] = '施設を削除しました。';
        } catch (\Exception $e) {
            $_SESSION['flash_error'] = '削除に失敗しました。';
        }
        $this->redirect(BASE_PATH . '/facility/manage');
    }
}

<?php
namespace Controllers;

use Core\Controller;
use Core\Database;
use Core\FeatureGate;

class FacilityController extends Controller
{
    private $db;
    private $featureGate;

    public function __construct()
    {
        parent::__construct();
        $this->db = Database::getInstance();
        $this->featureGate = new FeatureGate();
        if (!$this->auth->check()) $this->redirect(BASE_PATH . '/login');
        $this->featureGate->enforceAccess('facility', $this->auth->user());
    }

    public function index()
    {
        $date = $_GET['date'] ?? date('Y-m-d');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$date)) $date = date('Y-m-d');
        $viewMode = $_GET['view'] ?? 'day';
        if (!in_array($viewMode, ['day', 'week', 'month'], true)) $viewMode = 'day';
        list($rangeStart, $rangeEnd) = $this->dateRange($date, $viewMode);

        $facilities = [];
        $reservations = [];
        try {
            $facilities = $this->db->fetchAll('SELECT * FROM facilities ORDER BY sort_order ASC, name ASC');
            $reservations = $this->db->fetchAll(
                'SELECT fr.*, f.name as facility_name, u.display_name as reserver_name
                 FROM facility_reservations fr
                 JOIN facilities f ON f.id = fr.facility_id
                 JOIN users u ON u.id = fr.user_id
                 WHERE fr.start_time <= ? AND fr.end_time >= ?
                 ORDER BY fr.start_time ASC',
                [$rangeEnd, $rangeStart]
            );
        } catch (\Exception $e) {
            $facilities = null;
        }

        $this->view('facility/index', [
            'title' => tr_text('施設予約', 'Facility reservations'),
            'facilities' => $facilities,
            'reservations' => $reservations,
            'date' => $date,
            'viewMode' => $viewMode,
            'rangeStart' => $rangeStart,
            'rangeEnd' => $rangeEnd,
            'calendarEvents' => $this->formatCalendarEvents($reservations),
            'canCreateReservation' => $this->featureGate->canCreate('facility', $this->auth->user()),
            'jsFiles' => []
        ]);
    }

    public function create()
    {
        $this->featureGate->enforceCreate('facility', $this->auth->user(), BASE_PATH . '/facility');
        $facilityId = $_GET['facility_id'] ?? '';
        $date = $_GET['date'] ?? date('Y-m-d');
        $startTime = $_GET['start'] ?? '';
        $facilities = [];
        try { $facilities = $this->db->fetchAll('SELECT * FROM facilities ORDER BY sort_order ASC, name ASC'); } catch (\Exception $e) { $facilities = []; }
        $this->view('facility/form', ['title' => tr_text('施設予約の作成', 'Create facility reservation'),'reservation' => null,'facilities' => $facilities,'selectedFacilityId' => $facilityId,'date' => $date,'startTime' => $startTime,'mode' => 'create']);
    }

    public function store()
    {
        $this->featureGate->enforceCreate('facility', $this->auth->user(), BASE_PATH . '/facility');
        $data = $_POST;
        try {
            $this->db->execute('INSERT INTO facility_reservations (facility_id, user_id, title, start_time, end_time, memo, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())', [$data['facility_id'], $this->auth->id(), $data['title'] ?? '', $data['start_time'], $data['end_time'], $data['memo'] ?? '']);
            $_SESSION['flash_message'] = tr_text('施設を予約しました。', 'Facility reserved.');
        } catch (\Exception $e) { $_SESSION['flash_error'] = tr_text('予約に失敗しました: ', 'Reservation failed: ') . $e->getMessage(); }
        $this->redirect(BASE_PATH . '/facility');
    }

    public function delete($params)
    {
        $id = $params['id'] ?? 0;
        try { $this->db->execute('DELETE FROM facility_reservations WHERE id = ? AND (user_id = ? OR ? = 1)', [$id, $this->auth->id(), $this->auth->isAdmin() ? 1 : 0]); $_SESSION['flash_message'] = tr_text('予約を取り消しました。', 'Reservation cancelled.'); }
        catch (\Exception $e) { $_SESSION['flash_error'] = tr_text('取り消しに失敗しました。', 'Cancellation failed.'); }
        $this->redirect(BASE_PATH . '/facility');
    }

    public function manage()
    {
        if (!$this->auth->isAdmin()) { $this->redirect(BASE_PATH . '/facility'); return; }
        $facilities = [];
        try { $facilities = $this->db->fetchAll('SELECT * FROM facilities ORDER BY sort_order ASC, name ASC'); } catch (\Exception $e) { $facilities = null; }
        $this->view('facility/manage', ['title' => tr_text('施設管理', 'Facility management'), 'facilities' => $facilities]);
    }

    public function addFacility()
    {
        if (!$this->auth->isAdmin()) { $this->redirect(BASE_PATH . '/facility'); return; }
        $data = $_POST;
        try { $this->db->execute('INSERT INTO facilities (name, description, capacity, sort_order, created_at) VALUES (?, ?, ?, ?, NOW())', [$data['name'], $data['description'] ?? '', $data['capacity'] ?? 0, $data['sort_order'] ?? 0]); $_SESSION['flash_message'] = tr_text('施設を追加しました。', 'Facility added.'); }
        catch (\Exception $e) { $_SESSION['flash_error'] = tr_text('追加に失敗しました: ', 'Add failed: ') . $e->getMessage(); }
        $this->redirect(BASE_PATH . '/facility/manage');
    }

    public function deleteFacility($params)
    {
        if (!$this->auth->isAdmin()) { $this->redirect(BASE_PATH . '/facility'); return; }
        $id = $params['id'] ?? 0;
        try { $this->db->execute('DELETE FROM facilities WHERE id = ?', [$id]); $_SESSION['flash_message'] = tr_text('施設を削除しました。', 'Facility deleted.'); }
        catch (\Exception $e) { $_SESSION['flash_error'] = tr_text('削除に失敗しました。', 'Delete failed.'); }
        $this->redirect(BASE_PATH . '/facility/manage');
    }

    private function dateRange($date, $viewMode)
    {
        if ($viewMode === 'week') {
            $ts = strtotime($date);
            $start = date('Y-m-d 00:00:00', strtotime('-' . ((int)date('N', $ts) - 1) . ' days', $ts));
            return [$start, date('Y-m-d 23:59:59', strtotime($start . ' +6 days'))];
        }
        if ($viewMode === 'month') return [date('Y-m-01 00:00:00', strtotime($date)), date('Y-m-t 23:59:59', strtotime($date))];
        return [$date . ' 00:00:00', $date . ' 23:59:59'];
    }

    private function formatCalendarEvents(array $reservations)
    {
        $events = [];
        foreach ($reservations as $row) {
            $events[] = ['id' => (int)$row['id'], 'title' => '[' . $row['facility_name'] . '] ' . $row['title'], 'start' => $row['start_time'], 'end' => $row['end_time'], 'url' => BASE_PATH . '/facility/create?facility_id=' . (int)$row['facility_id'] . '&date=' . date('Y-m-d', strtotime($row['start_time'])), 'extendedProps' => ['facility_name' => $row['facility_name'], 'reserver_name' => $row['reserver_name']]];
        }
        return $events;
    }
}

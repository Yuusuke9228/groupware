<?php
namespace Controllers;

use Core\Controller;
use Models\UnifiedSearch;

class SearchController extends Controller
{
    private $searchModel;

    public function __construct()
    {
        parent::__construct();
        $this->searchModel = new UnifiedSearch();

        if (!$this->auth->check()) {
            $this->redirect(BASE_PATH . '/login');
        }
    }

    public function index()
    {
        $query = trim($_GET['q'] ?? '');
        $results = [
            'messages' => [],
            'workflow' => [],
            'schedules' => [],
            'tasks' => [],
            'total' => 0
        ];

        if ($query !== '') {
            $results = $this->searchModel->searchAll($this->auth->id(), $query, 15);
        }

        $this->view('search/index', [
            'title' => '全文検索',
            'query' => $query,
            'results' => $results
        ]);
    }

    public function apiSearch($params)
    {
        $query = trim($params['q'] ?? ($_GET['q'] ?? ''));
        if ($query === '') {
            return [
                'success' => true,
                'data' => [
                    'messages' => [],
                    'workflow' => [],
                    'schedules' => [],
                    'tasks' => [],
                    'total' => 0
                ]
            ];
        }

        $limit = isset($params['limit']) ? max(1, min(50, (int)$params['limit'])) : 10;
        $results = $this->searchModel->searchAll($this->auth->id(), $query, $limit);

        return [
            'success' => true,
            'data' => $results
        ];
    }
}

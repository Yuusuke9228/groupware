<?php
namespace Controllers;

use Core\Controller;
use Models\AutomationJob;

class AutomationController extends Controller
{
    private $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = new AutomationJob();

        if (!$this->auth->check()) {
            $this->redirect(BASE_PATH . '/login');
        }
    }

    public function index()
    {
        $jobs = $this->model->getAll($this->auth->id(), $this->auth->isAdmin());

        $this->view('automation/index', [
            'title' => '繰り返し業務の自動化',
            'jobs' => $jobs,
            'jsFiles' => ['automation.js']
        ]);
    }

    public function apiCreate($params, $data)
    {
        if (empty($data['name']) || empty($data['job_type'])) {
            return ['success' => false, 'error' => 'name と job_type は必須です'];
        }

        $config = [
            'template_id' => $data['template_id'] ?? null,
            'requester_id' => $data['requester_id'] ?? null,
            'user_id' => $data['user_id'] ?? null,
            'title_prefix' => $data['title_prefix'] ?? null,
            'days_before' => $data['days_before'] ?? null,
        ];

        $id = $this->model->create([
            'name' => $data['name'],
            'job_type' => $data['job_type'],
            'frequency' => $data['frequency'] ?? 'daily',
            'run_at' => $data['run_at'] ?? '09:00:00',
            'weekday' => $data['weekday'] ?? null,
            'day_of_month' => $data['day_of_month'] ?? null,
            'config_json' => $config
        ], $this->auth->id());

        if (!$id) {
            return ['success' => false, 'error' => 'ジョブ作成に失敗しました'];
        }

        return [
            'success' => true,
            'message' => '自動化ジョブを作成しました',
            'data' => ['id' => $id]
        ];
    }

    public function apiToggle($params, $data)
    {
        $id = $params['id'] ?? null;
        if (!$id) {
            return ['success' => false, 'error' => 'IDが必要です'];
        }

        $active = !empty($data['is_active']);
        $ok = $this->model->toggle($id, $active, $this->auth->id(), $this->auth->isAdmin());

        return $ok
            ? ['success' => true, 'message' => '状態を更新しました']
            : ['success' => false, 'error' => '状態更新に失敗しました'];
    }

    public function apiRunNow($params)
    {
        $id = $params['id'] ?? null;
        if (!$id) {
            return ['success' => false, 'error' => 'IDが必要です'];
        }

        $result = $this->model->runJobNow($id, $this->auth->id(), $this->auth->isAdmin());
        return [
            'success' => $result['success'],
            'message' => $result['message']
        ];
    }

    public function apiRunDue()
    {
        if (!$this->auth->isAdmin()) {
            return ['success' => false, 'error' => '管理者のみ実行できます'];
        }

        $result = $this->model->runDueJobs(50);
        return [
            'success' => true,
            'data' => $result,
            'message' => "処理: {$result['processed']}件 / 成功: {$result['success']}件 / 失敗: {$result['failed']}件"
        ];
    }
}

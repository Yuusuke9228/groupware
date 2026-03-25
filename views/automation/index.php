<div class="container-fluid mt-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">繰り返し業務の自動化</h4>
        <?php if ($this->auth->isAdmin()): ?>
            <button class="btn btn-sm btn-outline-primary" id="run-due-jobs">期限到来ジョブを実行</button>
        <?php endif; ?>
    </div>

    <div class="card mb-4">
        <div class="card-header"><strong>新規ジョブ作成</strong></div>
        <div class="card-body">
            <form id="automation-create-form" class="no-ajax">
                <div class="row g-2">
                    <div class="col-md-3">
                        <input type="text" class="form-control" name="name" placeholder="ジョブ名" required>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" name="job_type" required>
                            <option value="periodic_request">定期申請</option>
                            <option value="periodic_report">定期レポート</option>
                            <option value="deadline_reminder">期限リマインド</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" name="frequency">
                            <option value="daily">毎日</option>
                            <option value="weekly">毎週</option>
                            <option value="monthly">毎月</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="time" class="form-control" name="run_at" value="09:00" required>
                    </div>
                    <div class="col-md-1">
                        <input type="number" class="form-control" name="weekday" min="1" max="7" placeholder="週">
                    </div>
                    <div class="col-md-1">
                        <input type="number" class="form-control" name="day_of_month" min="1" max="28" placeholder="日">
                    </div>
                    <div class="col-md-1 d-grid">
                        <button class="btn btn-primary" type="submit">追加</button>
                    </div>
                </div>
                <div class="row g-2 mt-2">
                    <div class="col-md-2"><input type="number" class="form-control" name="template_id" placeholder="テンプレID"></div>
                    <div class="col-md-2"><input type="number" class="form-control" name="requester_id" placeholder="申請者ID"></div>
                    <div class="col-md-2"><input type="number" class="form-control" name="user_id" placeholder="対象ユーザーID"></div>
                    <div class="col-md-3"><input type="text" class="form-control" name="title_prefix" placeholder="件名プレフィックス"></div>
                    <div class="col-md-2"><input type="number" class="form-control" name="days_before" placeholder="何日前"></div>
                    <div class="col-md-1 text-end small text-muted">週:1-7</div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><strong>ジョブ一覧</strong></div>
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>名前</th>
                        <th>タイプ</th>
                        <th>頻度</th>
                        <th>次回実行</th>
                        <th>状態</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($jobs)): ?>
                        <tr><td colspan="7" class="text-center">ジョブはありません</td></tr>
                    <?php else: ?>
                        <?php foreach ($jobs as $job): ?>
                            <tr>
                                <td><?php echo (int)$job['id']; ?></td>
                                <td><?php echo htmlspecialchars($job['name']); ?></td>
                                <td><?php echo htmlspecialchars($job['job_type']); ?></td>
                                <td><?php echo htmlspecialchars($job['frequency']); ?> / <?php echo htmlspecialchars(substr($job['run_at'], 0, 5)); ?></td>
                                <td><?php echo htmlspecialchars((string)$job['next_run_at']); ?></td>
                                <td>
                                    <?php if ($job['is_active']): ?>
                                        <span class="badge bg-success">有効</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">無効</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary run-job-now" data-id="<?php echo (int)$job['id']; ?>">今すぐ実行</button>
                                    <button class="btn btn-sm btn-outline-secondary toggle-job" data-id="<?php echo (int)$job['id']; ?>" data-active="<?php echo $job['is_active'] ? 1 : 0; ?>">
                                        <?php echo $job['is_active'] ? '停止' : '再開'; ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

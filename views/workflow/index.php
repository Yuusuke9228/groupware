<?php
// views/workflow/index.php
$pageTitle = 'ワークフロー管理';
?>
<div class="container-fluid" data-page-type="index">
    <?php include __DIR__ . '/submenu.php'; ?>

    <div class="row">
        <div class="col">
            <h1 class="h3 mb-4">ワークフロー管理</h1>
        </div>
    </div>

    <!-- 統計情報 -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="card-title mb-1">テンプレート</h5>
                            <h2 class="mb-0" id="total-templates">-</h2>
                        </div>
                        <div class="text-end">
                            <span class="display-5 text-primary">
                                <i class="fas fa-file-alt"></i>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="<?php echo BASE_PATH; ?>/workflow/templates" class="text-decoration-none">
                        <small>テンプレート一覧 <i class="fas fa-arrow-right"></i></small>
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="card-title mb-1">総申請数</h5>
                            <h2 class="mb-0" id="total-requests">-</h2>
                        </div>
                        <div class="text-end">
                            <span class="display-5 text-info">
                                <i class="fas fa-clipboard-list"></i>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="<?php echo BASE_PATH; ?>/workflow/requests" class="text-decoration-none">
                        <small>申請一覧 <i class="fas fa-arrow-right"></i></small>
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="card-title mb-1">承認待ち</h5>
                            <h2 class="mb-0" id="pending-approvals">-</h2>
                        </div>
                        <div class="text-end">
                            <span class="display-5 text-warning">
                                <i class="fas fa-check-double"></i>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="<?php echo BASE_PATH; ?>/workflow/approvals" class="text-decoration-none">
                        <small>承認待ち一覧 <i class="fas fa-arrow-right"></i></small>
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="card-title mb-1">自分の申請</h5>
                            <h2 class="mb-0" id="my-requests">-</h2>
                        </div>
                        <div class="text-end">
                            <span class="display-5 text-success">
                                <i class="fas fa-user-edit"></i>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="<?php echo BASE_PATH; ?>/workflow/requests?requester_id=<?php echo $this->auth->id(); ?>" class="text-decoration-none">
                        <small>自分の申請一覧 <i class="fas fa-arrow-right"></i></small>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <!-- ステータス分布 -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">申請ステータス分布</h5>
                </div>
                <div class="card-body">
                    <canvas id="status-chart" width="400" height="300"></canvas>
                </div>
            </div>
        </div>

        <!-- 最近の申請 -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">最近の申請</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush" id="recent-requests">
                        <div class="list-group-item text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="<?php echo BASE_PATH; ?>/workflow/requests" class="text-decoration-none">
                        全ての申請を表示 <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- クイックアクション -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">クイックアクション</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <a href="<?php echo BASE_PATH; ?>/workflow/create-template" class="btn btn-primary w-100">
                                <i class="fas fa-plus"></i> 新規テンプレート作成
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="<?php echo BASE_PATH; ?>/workflow/approvals" class="btn btn-warning w-100">
                                <i class="fas fa-check"></i> 承認待ち確認
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="<?php echo BASE_PATH; ?>/workflow/delegates" class="btn btn-info w-100">
                                <i class="fas fa-user-friends"></i> 代理承認設定
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="<?php echo BASE_PATH; ?>/workflow/templates" class="btn btn-success w-100">
                                <i class="fas fa-file-alt"></i> 申請テンプレート選択
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
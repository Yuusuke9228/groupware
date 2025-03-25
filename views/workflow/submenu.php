<?php
// views/workflow/submenu.php

// 現在のサブページを判定
$subPage = '';
$requestUri = $_SERVER['REQUEST_URI'];

if (strpos($requestUri, '/workflow/templates') !== false) {
    $subPage = 'templates';
} elseif (strpos($requestUri, '/workflow/requests') !== false) {
    $subPage = 'requests';
} elseif (strpos($requestUri, '/workflow/approvals') !== false) {
    $subPage = 'approvals';
} elseif (strpos($requestUri, '/workflow/delegates') !== false) {
    $subPage = 'delegates';
} elseif (strpos($requestUri, '/workflow') !== false && strlen($requestUri) <= strlen(BASE_PATH . '/workflow') + 2) {
    $subPage = 'dashboard';
}
?>

<div class="card mb-4">
    <div class="card-body p-0">
        <div class="nav nav-pills nav-fill flex-column flex-sm-row">
            <a class="nav-link <?php echo $subPage === 'dashboard' ? 'active' : ''; ?>" href="<?php echo BASE_PATH; ?>/workflow">
                <i class="fas fa-tachometer-alt"></i> ダッシュボード
            </a>
            <a class="nav-link <?php echo $subPage === 'templates' ? 'active' : ''; ?>" href="<?php echo BASE_PATH; ?>/workflow/templates">
                <i class="fas fa-file-alt"></i> テンプレート
            </a>
            <a class="nav-link <?php echo $subPage === 'requests' ? 'active' : ''; ?>" href="<?php echo BASE_PATH; ?>/workflow/requests">
                <i class="fas fa-clipboard-list"></i> 申請一覧
            </a>
            <a class="nav-link <?php echo $subPage === 'approvals' ? 'active' : ''; ?>" href="<?php echo BASE_PATH; ?>/workflow/approvals">
                <i class="fas fa-check-double"></i> 承認待ち
            </a>
            <a class="nav-link <?php echo $subPage === 'delegates' ? 'active' : ''; ?>" href="<?php echo BASE_PATH; ?>/workflow/delegates">
                <i class="fas fa-user-friends"></i> 代理承認設定
            </a>
        </div>
    </div>
</div>
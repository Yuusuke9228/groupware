<?php
// views/layouts/header.php

// ページタイトルを設定
$pageTitle = $title ?? 'GroupWare Sample';

// 現在のページを取得
$currentPage = '';
$requestUri = $_SERVER['REQUEST_URI'];

// ベースパスを除去してページ判定
$basePath = BASE_PATH;
if ($basePath && strpos($requestUri, $basePath) === 0) {
    $requestUri = substr($requestUri, strlen($basePath));
}

// ホームページ判定
if ($requestUri === '/' || $requestUri === '') {
    $currentPage = 'home';
} elseif (strpos($requestUri, '/organizations') !== false) {
    $currentPage = 'organizations';
} elseif (strpos($requestUri, '/users') !== false) {
    $currentPage = 'users';
} elseif (strpos($requestUri, '/schedule') !== false) {
    $currentPage = 'schedule';
} elseif (strpos($requestUri, '/workflow') !== false) {
    $currentPage = 'workflow';
} elseif (strpos($requestUri, '/messages') !== false) {
    $currentPage = 'messages';
}

// 現在のユーザー情報
$currentUser = \Core\Auth::getInstance()->user();

// 未読メッセージ数を取得
$unreadMessageCount = 0;
$unreadNotificationCount = 0;

if ($currentUser) {
    $messageModel = new \Models\Message();
    $unreadMessageCount = $messageModel->getUnreadCount($currentUser['id']);

    $notificationModel = new \Models\Notification();
    $unreadNotificationCount = $notificationModel->getUnreadCount($currentUser['id']);
}
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <!-- Toastr CSS (通知用) -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- jstree CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/jstree/3.3.12/themes/default/style.min.css" rel="stylesheet">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">

    <!-- Flatpickr CSS -->
    <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_blue.css" rel="stylesheet">
    <!-- FullCalendar CSS -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.css" rel="stylesheet">

    <!-- カスタムCSS -->
    <link href="<?php echo BASE_PATH; ?>/css/style.css" rel="stylesheet">
    <link href="<?php echo BASE_PATH; ?>/css/home.css" rel="stylesheet">
    <!-- views/layouts/header.php の最後に追加 -->
    <style>
        /* モーダル内のselect2対応 */
        .select2-container {
            z-index: 10000;
        }

        .select2-dropdown {
            z-index: 10001;
        }

        /* フラットピッカー対応 */
        .flatpickr-calendar {
            z-index: 10002 !important;
        }

        /* 選択不可の状態を解除 */
        #schedule-modal input:not([disabled]),
        #schedule-modal select:not([disabled]),
        #schedule-modal textarea:not([disabled]) {
            background-color: #fff;
            opacity: 1;
        }
    </style>
</head>

<body data-is-admin="<?php echo $this->auth->isAdmin() ? 'true' : 'false'; ?>" data-user-id="<?php echo $this->auth->id(); ?>">
    <!-- ナビゲーションバー -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo BASE_PATH; ?>/">GroupWare</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <?php if ($currentUser): ?>

                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link <?php echo $currentPage === 'home' ? 'active' : ''; ?>" href="<?php echo BASE_PATH; ?>/">
                                <i class="fas fa-home"></i> ホーム
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $currentPage === 'messages' ? 'active' : ''; ?>" href="<?php echo BASE_PATH; ?>/messages/inbox">
                                <i class="far fa-envelope"></i> メッセージ
                                <?php if ($unreadMessageCount > 0): ?>
                                    <span class="badge bg-danger message-unread-badge"><?php echo $unreadMessageCount; ?></span>
                                <?php else: ?>
                                    <span class="badge bg-danger message-unread-badge d-none"></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $currentPage === 'schedule' ? 'active' : ''; ?>" href="<?php echo BASE_PATH; ?>/schedule">
                                <i class="far fa-calendar-alt"></i> スケジュール
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle <?php echo strpos($requestUri, '/workflow') !== false ? 'active' : ''; ?>" href="#" id="workflowDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-tasks"></i> ワークフロー
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="workflowDropdown">
                                <li><a class="dropdown-item" href="<?php echo BASE_PATH; ?>/workflow">ダッシュボード</a></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_PATH; ?>/workflow/requests">申請一覧</a></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_PATH; ?>/workflow/approvals">承認待ち一覧</a></li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item" href="<?php echo BASE_PATH; ?>/workflow/templates">テンプレート管理</a></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_PATH; ?>/workflow/delegates">代理承認設定</a></li>
                            </ul>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $currentPage === 'organizations' ? 'active' : ''; ?>" href="<?php echo BASE_PATH; ?>/organizations">
                                <i class="far fa-building"></i> 組織管理
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $currentPage === 'users' ? 'active' : ''; ?>" href="<?php echo BASE_PATH; ?>/users">
                                <i class="far fa-user"></i> ユーザー管理
                            </a>
                        </li>
                    </ul>
                    <ul class="navbar-nav">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-bell"></i>
                                <?php if ($unreadNotificationCount > 0): ?>
                                    <span class="badge bg-danger notification-unread-badge"><?php echo $unreadNotificationCount; ?></span>
                                <?php else: ?>
                                    <span class="badge bg-danger notification-unread-badge d-none"></span>
                                <?php endif; ?>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end notification-dropdown" aria-labelledby="notificationDropdown" style="width: 300px; max-height: 400px; overflow-y: auto;">
                                <div class="dropdown-header d-flex justify-content-between align-items-center">
                                    <span>通知</span>
                                    <a href="<?php echo BASE_PATH; ?>/notifications" class="text-decoration-none small">すべて表示</a>
                                </div>
                                <div class="dropdown-divider"></div>
                                <div id="notification-list">
                                    <?php
                                    $notifications = [];
                                    if ($currentUser) {
                                        $notificationModel = new \Models\Notification();
                                        $notifications = $notificationModel->getUnread($currentUser['id'], 5);
                                    }
                                    if (empty($notifications)):
                                    ?>
                                        <div class="dropdown-item text-center text-muted">
                                            未読の通知はありません
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($notifications as $notification): ?>
                                            <a href="<?php echo BASE_PATH . $notification['link']; ?>" class="dropdown-item notification-item d-flex align-items-center" data-id="<?php echo $notification['id']; ?>">
                                                <div class="flex-grow-1">
                                                    <div class="fw-bold text-truncate"><?php echo htmlspecialchars($notification['title']); ?></div>
                                                    <div class="small text-muted text-truncate"><?php echo htmlspecialchars($notification['content']); ?></div>
                                                    <div class="small text-muted"><?php echo date('Y年m月d日 H:i', strtotime($notification['created_at'])); ?></div>
                                                </div>
                                            </a>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <div class="dropdown-divider"></div>
                                <div class="dropdown-item text-center">
                                    <a href="<?php echo BASE_PATH; ?>/notifications" class="btn btn-sm btn-primary w-100">すべての通知を見る</a>
                                </div>
                            </div>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($currentUser['display_name']); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="<?php echo BASE_PATH; ?>/users/view/<?php echo $currentUser['id']; ?>">プロフィール</a></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_PATH; ?>/users/change-password/<?php echo $currentUser['id']; ?>">パスワード変更</a></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_PATH; ?>/notifications/settings">通知設定</a></li>
                                <?php if ($this->auth->isAdmin()): ?>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li><a class="dropdown-item" href="<?php echo BASE_PATH; ?>/settings">システム設定</a></li>
                                <?php endif; ?>

                                <li><a class="dropdown-item" href="<?php echo BASE_PATH; ?>/workflow/approvals">承認待ち一覧</a></li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item" href="<?php echo BASE_PATH; ?>/logout">ログアウト</a></li>
                            </ul>
                        </li>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- メインコンテンツ -->
    <div class="container-fluid mt-4" id="main-content">
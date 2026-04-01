<?php
// views/layouts/header.php

$pageTitle = isset($title) ? $title : $appName;

$currentPage = '';
$requestUri = $_SERVER['REQUEST_URI'];

$basePath = BASE_PATH;
if ($basePath && strpos($requestUri, $basePath) === 0) {
    $requestUri = substr($requestUri, strlen($basePath));
}

$pageMap = [
    '/' => 'home', '' => 'home',
    '/organizations' => 'organizations', '/users' => 'users',
    '/schedule' => 'schedule', '/workflow' => 'workflow',
    '/messages' => 'messages', '/search' => 'search',
    '/task' => 'task', '/daily-report' => 'daily-report', '/visual-boards' => 'visual-boards',
    '/webdatabase' => 'webdatabase', '/notifications' => 'notifications',
    '/integrations' => 'integrations', '/settings' => 'settings',
    '/bulletin' => 'bulletin',
    '/address-book' => 'address-book', '/facility' => 'facility',
    '/drive' => 'drive', '/files' => 'files', '/help' => 'help'
];

foreach ($pageMap as $path => $page) {
    if ($path === '/' || $path === '') {
        if ($requestUri === '/' || $requestUri === '') {
            $currentPage = $page;
            break;
        }
    } elseif (strpos($requestUri, $path) !== false) {
        $currentPage = $page;
        break;
    }
}

$currentUser = \Core\Auth::getInstance()->user();
$locale = get_locale();
$dataLocale = current_data_locale();
$isJaLocale = $locale === 'ja';
$languageRedirect = urlencode((string)($_SERVER['REQUEST_URI'] ?? (BASE_PATH . '/')));

$unreadMessageCount = 0;
$unreadNotificationCount = 0;

// システム設定からアプリ名・会社名を取得
$settingModel = new \Models\Setting();
$appName = $settingModel->getAppName();
$companyName = $settingModel->getCompanyName();
$appVersion = '';
$configPath = __DIR__ . '/../../config/config.php';
if (file_exists($configPath)) {
    $cfg = require $configPath;
    $appVersion = (string)($cfg['app']['version'] ?? '');
}
if ($appVersion === '') {
    $appVersion = (string)$settingModel->get('app_version', '');
}
$pwaEnabled = filter_var((string)$settingModel->get('pwa_enabled', '0'), FILTER_VALIDATE_BOOLEAN);
$pwaThemeColor = (string)$settingModel->get('pwa_theme_color', '#2b7de9');
$pwaAppName = (string)$settingModel->get('pwa_app_name', $appName);

if ($currentUser) {
    $messageModel = new \Models\Message();
    $unreadMessageCount = $messageModel->getUnreadCount($currentUser['id']);
    $notificationModel = new \Models\Notification();
    $unreadNotificationCount = $notificationModel->getUnreadCount($currentUser['id']);
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($locale); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate, max-age=0">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <meta name="theme-color" content="<?php echo htmlspecialchars($pwaThemeColor); ?>">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="<?php echo htmlspecialchars($pwaAppName); ?>">
    <title><?php echo htmlspecialchars($pageTitle); ?> - <?php echo htmlspecialchars($appName); ?></title>
    <link rel="icon" type="image/svg+xml" href="<?php echo BASE_PATH; ?>/img_icon/favicon.svg">
    <?php if ($pwaEnabled): ?>
    <link rel="manifest" href="<?php echo BASE_PATH; ?>/manifest.json">
    <link rel="apple-touch-icon" href="<?php echo BASE_PATH; ?>/public/icons/pwa-192.png">
    <?php endif; ?>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <!-- Toastr CSS -->
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
    <?php
    $styleCssVersion = @filemtime(__DIR__ . '/../../public/css/style.css') ?: time();
    $homeCssVersion = @filemtime(__DIR__ . '/../../public/css/home.css') ?: $styleCssVersion;
    $taskCssVersion = @filemtime(__DIR__ . '/../../public/css/task.css') ?: $styleCssVersion;
    $runtimeI18nJsVersion = @filemtime(__DIR__ . '/../../public/js/runtime-i18n.js') ?: $styleCssVersion;
    ?>
    <link href="<?php echo BASE_PATH; ?>/css/style.css?v=<?php echo $styleCssVersion; ?>" rel="stylesheet">
    <link href="<?php echo BASE_PATH; ?>/css/home.css?v=<?php echo $homeCssVersion; ?>" rel="stylesheet">
    <link href="<?php echo BASE_PATH; ?>/css/task.css?v=<?php echo $taskCssVersion; ?>" rel="stylesheet">

    <!-- JSのベースパス設定 -->
    <script>
        var BASE_PATH = "<?php echo BASE_PATH; ?>";
        var APP_LOCALE = "<?php echo htmlspecialchars($locale, ENT_QUOTES); ?>";
        var APP_DATA_LOCALE = "<?php echo htmlspecialchars($dataLocale, ENT_QUOTES); ?>";
        var RUNTIME_I18N_ENDPOINT = BASE_PATH + '/api/i18n/translate';
        var APP_I18N = <?php
            echo json_encode(
                \Core\I18n::messagesForJs([
                    'js.datatable.empty',
                    'js.datatable.searching',
                    'js.datatable.remove_all',
                    'js.error.communication',
                    'js.settings.save_failed',
                    'js.settings.saved',
                    'js.settings.processing',
                    'js.settings.send_test',
                    'js.settings.sending',
                    'js.settings.test_email_required',
                    'js.settings.test_email_success',
                    'js.settings.test_email_failed',
                    'js.settings.process_email_now',
                    'js.settings.process_email_success',
                    'js.settings.process_email_failed',
                    'js.backup.run_confirm',
                    'js.backup.run_failed',
                    'js.backup.run_success',
                    'js.backup.download',
                    'settings.backup.none',
                    'settings.backup.status_success',
                    'settings.backup.status_failed',
                    'settings.backup.status_running',
                    'js.common.select_placeholder',
                    'js.common.delete_confirm',
                    'js.common.deleted',
                    'js.common.saved',
                    'js.common.generic_error',
                ]),
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
        ?>;

        window.tJs = function (key, replace) {
            var text = (APP_I18N && APP_I18N[key]) ? APP_I18N[key] : key;
            if (replace && typeof replace === 'object') {
                Object.keys(replace).forEach(function (k) {
                    text = text.replace(new RegExp('\\{' + k + '\\}', 'g'), String(replace[k]));
                });
            }
            return text;
        };

        window.tLiteral = function (jaText, enText) {
            return APP_LOCALE === 'ja' ? jaText : enText;
        };

        window.getDataTablesLanguageOption = function () {
            if (APP_LOCALE === 'ja') {
                return { url: BASE_PATH + '/js/vendor/dataTables.japanese.json' };
            }
            return {};
        };

        window.getAppLocale = function () {
            return APP_LOCALE || 'ja';
        };

        window.getAppDataLocale = function () {
            return APP_DATA_LOCALE || 'ja-JP';
        };
    </script>
    <script src="<?php echo BASE_PATH; ?>/js/runtime-i18n.js?v=<?php echo $runtimeI18nJsVersion; ?>"></script>

    <!-- 共通ライブラリ -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
    <?php if ($isJaLocale): ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/locale/ja.js"></script>
    <?php endif; ?>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jstree/3.3.12/jstree.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <?php if ($isJaLocale): ?>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/ja.js"></script>
    <?php endif; ?>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.2/main.min.js"></script>
    <?php if ($isJaLocale): ?>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.2/locales/ja.js"></script>
    <?php endif; ?>

    <script>
        // Bootstrap 5 への移行後も既存の jQuery モーダル呼び出しを動かす互換層。
        if (window.jQuery && window.bootstrap && typeof jQuery.fn.modal !== 'function') {
            jQuery.fn.modal = function(action) {
                return this.each(function() {
                    var modal = bootstrap.Modal.getOrCreateInstance(this);
                    if (action === 'hide') {
                        modal.hide();
                    } else if (action === 'toggle') {
                        modal.toggle();
                    } else {
                        modal.show();
                    }
                });
            };
        }
    </script>
</head>

<body data-is-admin="<?php echo $this->auth->isAdmin() ? 'true' : 'false'; ?>" data-user-id="<?php echo $this->auth->id(); ?>" data-locale="<?php echo htmlspecialchars($locale); ?>">

<?php if ($currentUser): ?>
    <!-- ========== Top Header Bar ========== -->
    <header class="gw-header">
        <a class="gw-header-logo" href="<?php echo BASE_PATH; ?>/">
            <img src="<?php echo BASE_PATH; ?>/img_icon/favicon.svg" alt="<?php echo htmlspecialchars($appName); ?>" style="height:28px;margin-right:8px;border-radius:6px;">
            <span class="d-none d-sm-inline"><?php echo htmlspecialchars($appName); ?></span>
        </a>

        <!-- 検索 -->
        <div class="gw-header-search position-relative d-none d-md-block">
            <form class="no-ajax" method="get" action="<?php echo BASE_PATH; ?>/search">
                <input class="form-control" type="search" name="q" placeholder="<?php echo htmlspecialchars(t('header.search_placeholder')); ?>" value="<?php echo htmlspecialchars(isset($_GET['q']) ? $_GET['q'] : ''); ?>" autocomplete="off">
                <i class="fas fa-search search-icon"></i>
            </form>
        </div>

        <!-- 右側アクション -->
        <div class="gw-header-actions">
            <!-- 検索（モバイル） -->
            <a href="<?php echo BASE_PATH; ?>/search" class="gw-header-btn d-md-none" title="<?php echo htmlspecialchars(t('header.search')); ?>">
                <i class="fas fa-search"></i>
            </a>

            <!-- ヘルプ -->
            <a href="<?php echo BASE_PATH; ?>/help" class="gw-header-btn" title="<?php echo htmlspecialchars(t('header.help')); ?>" target="_blank">
                <i class="fas fa-question-circle"></i>
            </a>

            <div class="dropdown">
                <button class="gw-header-btn" type="button" id="localeDropdown" data-bs-toggle="dropdown" aria-expanded="false" title="<?php echo htmlspecialchars(t('lang.switch')); ?>">
                    <?php echo strtoupper($locale); ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="localeDropdown">
                    <li><a class="dropdown-item" href="<?php echo BASE_PATH; ?>/locale/ja?redirect=<?php echo $languageRedirect; ?>"><?php echo htmlspecialchars(t('lang.ja')); ?></a></li>
                    <li><a class="dropdown-item" href="<?php echo BASE_PATH; ?>/locale/en?redirect=<?php echo $languageRedirect; ?>"><?php echo htmlspecialchars(t('lang.en')); ?></a></li>
                </ul>
            </div>

            <!-- 通知 -->
            <div class="dropdown">
                <button class="gw-header-btn" type="button" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false" title="<?php echo htmlspecialchars(t('header.notifications')); ?>">
                    <i class="fas fa-bell"></i>
                    <?php if ($unreadNotificationCount > 0): ?>
                        <span class="badge notification-unread-badge"><?php echo $unreadNotificationCount; ?></span>
                    <?php else: ?>
                        <span class="badge notification-unread-badge d-none"></span>
                    <?php endif; ?>
                </button>
                <div class="dropdown-menu dropdown-menu-end notification-dropdown" aria-labelledby="notificationDropdown">
                    <div class="dropdown-header d-flex justify-content-between align-items-center">
                        <span><?php echo htmlspecialchars(t('header.notifications')); ?></span>
                        <a href="<?php echo BASE_PATH; ?>/notifications" class="text-decoration-none small"><?php echo htmlspecialchars(t('header.notifications_all')); ?></a>
                    </div>
                    <div id="notification-list">
                        <?php
                        $notifications = [];
                        if ($currentUser) {
                            $notificationModel = new \Models\Notification();
                            $notifications = $notificationModel->getUnread($currentUser['id'], 5);
                        }
                        if (empty($notifications)):
                        ?>
                            <div class="gw-empty-state py-4">
                                <i class="far fa-bell-slash"></i>
                                <?php echo htmlspecialchars(t('header.notifications_empty')); ?>
                            </div>
                        <?php else: ?>
                            <?php foreach ($notifications as $notification): ?>
                                <a href="<?php echo BASE_PATH . $notification['link']; ?>" class="gw-list-item notification-item" data-id="<?php echo $notification['id']; ?>">
                                    <div class="gw-list-content">
                                        <div class="gw-list-title"><?php echo htmlspecialchars($notification['title']); ?></div>
                                        <div class="gw-list-desc"><?php echo htmlspecialchars($notification['content']); ?></div>
                                    </div>
                                    <span class="gw-list-time"><?php echo date('m/d H:i', strtotime($notification['created_at'])); ?></span>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div style="padding:8px 12px;border-top:1px solid var(--border-light);">
                        <a href="<?php echo BASE_PATH; ?>/notifications" class="btn btn-sm btn-primary w-100"><?php echo htmlspecialchars(t('header.notifications_view_all')); ?></a>
                    </div>
                </div>
            </div>

            <!-- ユーザーメニュー -->
            <div class="dropdown">
                <button class="gw-user-btn" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="gw-user-avatar">
                        <?php echo mb_substr($currentUser['display_name'], 0, 1); ?>
                    </span>
                    <span class="user-name-text"><?php echo htmlspecialchars($currentUser['display_name']); ?></span>
                    <i class="fas fa-chevron-down" style="font-size:10px;opacity:0.7;"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                    <li><a class="dropdown-item" href="<?php echo BASE_PATH; ?>/users/view/<?php echo $currentUser['id']; ?>"><i class="fas fa-user me-2"></i><?php echo htmlspecialchars(t('header.profile')); ?></a></li>
                    <li><a class="dropdown-item" href="<?php echo BASE_PATH; ?>/users/change-password/<?php echo $currentUser['id']; ?>"><i class="fas fa-key me-2"></i><?php echo htmlspecialchars(t('header.change_password')); ?></a></li>
                    <li><a class="dropdown-item" href="<?php echo BASE_PATH; ?>/notifications/settings"><i class="fas fa-cog me-2"></i><?php echo htmlspecialchars(t('header.notification_settings')); ?></a></li>
                    <?php if ($this->auth->isAdmin()): ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_PATH; ?>/settings"><i class="fas fa-sliders-h me-2"></i><?php echo htmlspecialchars(t('header.system_settings')); ?></a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_PATH; ?>/organizations"><i class="fas fa-sitemap me-2"></i><?php echo htmlspecialchars(t('header.organization_management')); ?></a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_PATH; ?>/users"><i class="fas fa-users me-2"></i><?php echo htmlspecialchars(t('header.user_management')); ?></a></li>
                    <?php endif; ?>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="<?php echo BASE_PATH; ?>/help" target="_blank"><i class="fas fa-question-circle me-2"></i><?php echo htmlspecialchars(t('header.help')); ?></a></li>
                    <li><a class="dropdown-item" href="<?php echo BASE_PATH; ?>/terms" target="_blank"><i class="fas fa-file-contract me-2"></i><?php echo htmlspecialchars(t('header.terms')); ?></a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="<?php echo BASE_PATH; ?>/logout"><i class="fas fa-sign-out-alt me-2"></i><?php echo htmlspecialchars(t('header.logout')); ?></a></li>
                </ul>
            </div>
        </div>
    </header>

    <!-- ========== Module Navigation (サイボウズ風アイコンメニュー) ========== -->
    <nav class="gw-module-nav" id="moduleNav">
        <!-- モバイル: 左スクロールインジケータ -->
        <div class="nav-scroll-indicator nav-scroll-left" id="navScrollLeft">
            <i class="fas fa-chevron-left"></i>
        </div>
        <ul class="gw-module-list" id="moduleList">
            <li class="gw-module-item">
                <a class="gw-module-link <?php echo $currentPage === 'home' ? 'active' : ''; ?>" href="<?php echo BASE_PATH; ?>/">
                    <img src="<?php echo BASE_PATH; ?>/img_icon/icon_portal.svg" alt="" class="gw-module-icon">
                    <span class="gw-module-label"><?php echo htmlspecialchars(t('menu.top')); ?></span>
                </a>
            </li>
            <li class="gw-module-item">
                <a class="gw-module-link <?php echo $currentPage === 'schedule' ? 'active' : ''; ?>" href="<?php echo BASE_PATH; ?>/schedule">
                    <img src="<?php echo BASE_PATH; ?>/img_icon/icon_schedule.svg" alt="" class="gw-module-icon">
                    <span class="gw-module-label"><?php echo htmlspecialchars(t('menu.schedule')); ?></span>
                </a>
            </li>
            <li class="gw-module-item">
                <a class="gw-module-link <?php echo $currentPage === 'messages' ? 'active' : ''; ?>" href="<?php echo BASE_PATH; ?>/messages/inbox">
                    <img src="<?php echo BASE_PATH; ?>/img_icon/icon_message.svg" alt="" class="gw-module-icon">
                    <span class="gw-module-label"><?php echo htmlspecialchars(t('menu.messages')); ?></span>
                    <?php if ($unreadMessageCount > 0): ?>
                        <span class="gw-module-badge"><?php echo $unreadMessageCount; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="gw-module-item">
                <a class="gw-module-link <?php echo $currentPage === 'workflow' ? 'active' : ''; ?>" href="<?php echo BASE_PATH; ?>/workflow">
                    <img src="<?php echo BASE_PATH; ?>/img_icon/icon_workflow.svg" alt="" class="gw-module-icon">
                    <span class="gw-module-label"><?php echo htmlspecialchars(t('menu.workflow')); ?></span>
                </a>
            </li>
            <li class="gw-module-item">
                <a class="gw-module-link <?php echo $currentPage === 'task' ? 'active' : ''; ?>" href="<?php echo BASE_PATH; ?>/task">
                    <img src="<?php echo BASE_PATH; ?>/img_icon/icon_todo.svg" alt="" class="gw-module-icon">
                    <span class="gw-module-label"><?php echo htmlspecialchars(t('menu.task')); ?></span>
                </a>
            </li>
            <li class="gw-module-item">
                <a class="gw-module-link <?php echo $currentPage === 'daily-report' ? 'active' : ''; ?>" href="<?php echo BASE_PATH; ?>/daily-report">
                    <img src="<?php echo BASE_PATH; ?>/img_icon/icon_report.svg" alt="" class="gw-module-icon">
                    <span class="gw-module-label"><?php echo htmlspecialchars(t('menu.daily_report')); ?></span>
                </a>
            </li>
            <li class="gw-module-item">
                <a class="gw-module-link <?php echo $currentPage === 'visual-boards' ? 'active' : ''; ?>" href="<?php echo BASE_PATH; ?>/visual-boards">
                    <img src="<?php echo BASE_PATH; ?>/img_icon/icon_project_management.svg" alt="" class="gw-module-icon">
                    <span class="gw-module-label"><?php echo htmlspecialchars(tr_text('Visual Boards', 'Visual Boards')); ?></span>
                </a>
            </li>
            <li class="gw-module-item">
                <a class="gw-module-link <?php echo $currentPage === 'bulletin' ? 'active' : ''; ?>" href="<?php echo BASE_PATH; ?>/bulletin">
                    <img src="<?php echo BASE_PATH; ?>/img_icon/icon_bulletin.svg" alt="" class="gw-module-icon" onerror="this.style.display='none';this.nextElementSibling.style.display='inline-block';">
                    <i class="fas fa-clipboard-list" style="display:none;font-size:24px;color:#5b9bd5;"></i>
                    <span class="gw-module-label"><?php echo htmlspecialchars(t('menu.bulletin')); ?></span>
                </a>
            </li>
            <li class="gw-module-item">
                <a class="gw-module-link <?php echo $currentPage === 'webdatabase' ? 'active' : ''; ?>" href="<?php echo BASE_PATH; ?>/webdatabase">
                    <img src="<?php echo BASE_PATH; ?>/img_icon/icon_appsuite.svg" alt="" class="gw-module-icon">
                    <span class="gw-module-label"><?php echo htmlspecialchars(t('menu.webdatabase')); ?></span>
                </a>
            </li>
            <li class="gw-module-item">
                <a class="gw-module-link <?php echo $currentPage === 'address-book' ? 'active' : ''; ?>" href="<?php echo BASE_PATH; ?>/address-book">
                    <img src="<?php echo BASE_PATH; ?>/img_icon/icon_address_book.svg" alt="" class="gw-module-icon">
                    <span class="gw-module-label"><?php echo htmlspecialchars(t('menu.address_book')); ?></span>
                </a>
            </li>
            <li class="gw-module-item">
                <a class="gw-module-link <?php echo $currentPage === 'facility' ? 'active' : ''; ?>" href="<?php echo BASE_PATH; ?>/facility">
                    <img src="<?php echo BASE_PATH; ?>/img_icon/icon_equipment_reservation.svg" alt="" class="gw-module-icon">
                    <span class="gw-module-label"><?php echo htmlspecialchars(t('menu.facility')); ?></span>
                </a>
            </li>
            <li class="gw-module-item">
                <a class="gw-module-link <?php echo $currentPage === 'drive' ? 'active' : ''; ?>" href="<?php echo BASE_PATH; ?>/drive">
                    <img src="<?php echo BASE_PATH; ?>/img_icon/icon_cabinet.svg" alt="" class="gw-module-icon" onerror="this.style.display='none';this.nextElementSibling.style.display='inline-block';">
                    <i class="fas fa-cloud" style="display:none;font-size:24px;color:#5b9bd5;"></i>
                    <span class="gw-module-label"><?php echo htmlspecialchars(tr_text('Drive', 'Drive')); ?></span>
                </a>
            </li>
            <li class="gw-module-item">
                <a class="gw-module-link <?php echo $currentPage === 'files' ? 'active' : ''; ?>" href="<?php echo BASE_PATH; ?>/files">
                    <img src="<?php echo BASE_PATH; ?>/img_icon/icon_cabinet.svg" alt="" class="gw-module-icon" onerror="this.style.display='none';this.nextElementSibling.style.display='inline-block';">
                    <i class="fas fa-folder-open" style="display:none;font-size:24px;color:#f0ad4e;"></i>
                    <span class="gw-module-label"><?php echo htmlspecialchars(t('menu.file_management')); ?></span>
                </a>
            </li>
            <li class="gw-module-item">
                <a class="gw-module-link <?php echo $currentPage === 'integrations' ? 'active' : ''; ?>" href="<?php echo BASE_PATH; ?>/integrations">
                    <img src="<?php echo BASE_PATH; ?>/img_icon/icon_smartphone.png" alt="" class="gw-module-icon">
                    <span class="gw-module-label"><?php echo htmlspecialchars(t('menu.integrations')); ?></span>
                </a>
            </li>
            <li class="gw-module-item">
                <a class="gw-module-link <?php echo $currentPage === 'notifications' ? 'active' : ''; ?>" href="<?php echo BASE_PATH; ?>/notifications">
                    <img src="<?php echo BASE_PATH; ?>/img_icon/icon_info.svg" alt="" class="gw-module-icon">
                    <span class="gw-module-label"><?php echo htmlspecialchars(t('header.notifications')); ?></span>
                    <?php if ($unreadNotificationCount > 0): ?>
                        <span class="gw-module-badge"><?php echo $unreadNotificationCount; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <?php if ($this->auth->isAdmin()): ?>
            <li class="gw-module-item">
                <a class="gw-module-link <?php echo $currentPage === 'settings' ? 'active' : ''; ?>" href="<?php echo BASE_PATH; ?>/settings">
                    <img src="<?php echo BASE_PATH; ?>/img_icon/icon_management_function.png" alt="" class="gw-module-icon">
                    <span class="gw-module-label"><?php echo htmlspecialchars(t('header.system_settings')); ?></span>
                </a>
            </li>
            <?php endif; ?>
        </ul>
        <!-- モバイル: 右スクロールインジケータ -->
        <div class="nav-scroll-indicator nav-scroll-right" id="navScrollRight">
            <i class="fas fa-chevron-right"></i>
        </div>
    </nav>
<?php endif; ?>

    <!-- メインコンテンツ -->
    <div class="<?php echo $currentUser ? '' : 'container'; ?>" id="main-content">

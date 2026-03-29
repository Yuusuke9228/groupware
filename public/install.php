<?php
/**
 * GroupWare Installer - Web-based Setup Wizard
 *
 * This file handles first-time setup of the application.
 * It creates the database, configuration files, and admin account.
 */

// Prevent execution if already installed
$rootDir = dirname(__DIR__);
if (file_exists($rootDir . '/install.lock') || file_exists($rootDir . '/config/database.php')) {
    header('Location: index.php');
    exit;
}

session_start();
date_default_timezone_set('Asia/Tokyo');
mb_internal_encoding('UTF-8');

// CSRF token management
if (empty($_SESSION['install_csrf_token'])) {
    $_SESSION['install_csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['install_csrf_token'];

function verifyCsrf(): bool {
    return isset($_POST['csrf_token']) && hash_equals($_SESSION['install_csrf_token'], $_POST['csrf_token']);
}

// ========== AJAX: Test Database Connection ==========
if (isset($_POST['action']) && $_POST['action'] === 'test_db') {
    header('Content-Type: application/json');
    if (!verifyCsrf()) {
        echo json_encode(['success' => false, 'message' => 'CSRF検証に失敗しました。']);
        exit;
    }
    $host = $_POST['db_host'] ?? 'localhost';
    $port = (int)($_POST['db_port'] ?? 3306);
    $user = $_POST['db_user'] ?? '';
    $pass = $_POST['db_pass'] ?? '';
    $name = $_POST['db_name'] ?? '';

    try {
        $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5
        ]);
        // Try to create/select the database
        $safeName = preg_replace('/[^a-zA-Z0-9_]/', '', $name);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$safeName}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
        $pdo->exec("USE `{$safeName}`");
        echo json_encode(['success' => true, 'message' => 'データベースへの接続に成功しました。']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => '接続エラー: ' . $e->getMessage()]);
    }
    exit;
}

// ========== Step Processing ==========
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$step = max(1, min(5, $step));
$errors = [];
$success = '';

// Process POST submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install_step'])) {
    if (!verifyCsrf()) {
        $errors[] = 'CSRF検証に失敗しました。ページを再読み込みしてください。';
    } else {
        $postStep = (int)$_POST['install_step'];

        switch ($postStep) {
            case 1:
                // Requirements check - just advance
                header('Location: install.php?step=2');
                exit;

            case 2:
                // Save DB config to session
                $_SESSION['install_db'] = [
                    'host' => trim($_POST['db_host'] ?? 'localhost'),
                    'port' => (int)($_POST['db_port'] ?? 3306),
                    'name' => trim($_POST['db_name'] ?? ''),
                    'user' => trim($_POST['db_user'] ?? ''),
                    'pass' => $_POST['db_pass'] ?? '',
                ];
                // Validate
                if (empty($_SESSION['install_db']['name'])) {
                    $errors[] = 'データベース名を入力してください。';
                }
                if (empty($_SESSION['install_db']['user'])) {
                    $errors[] = 'データベースユーザー名を入力してください。';
                }
                if (empty($errors)) {
                    // Test connection and import schema
                    try {
                        $db = $_SESSION['install_db'];
                        $dsn = "mysql:host={$db['host']};port={$db['port']};charset=utf8mb4";
                        $pdo = new PDO($dsn, $db['user'], $db['pass'], [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                        ]);
                        $safeName = preg_replace('/[^a-zA-Z0-9_]/', '', $db['name']);
                        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$safeName}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
                        $pdo->exec("USE `{$safeName}`");

                        // Import schema
                        $schemaFile = $rootDir . '/db/schema.sql';
                        if (file_exists($schemaFile)) {
                            $sql = file_get_contents($schemaFile);
                            // Remove CREATE DATABASE and USE statements (we already selected the DB)
                            $sql = preg_replace('/^\s*CREATE\s+DATABASE\s+.*?;\s*$/mi', '', $sql);
                            $sql = preg_replace('/^\s*USE\s+.*?;\s*$/mi', '', $sql);
                            $pdo->exec($sql);
                        } else {
                            $errors[] = 'スキーマファイルが見つかりません: db/schema.sql';
                        }
                    } catch (PDOException $e) {
                        $errors[] = 'データベースエラー: ' . $e->getMessage();
                    }
                }
                if (empty($errors)) {
                    header('Location: install.php?step=3');
                    exit;
                }
                $step = 2;
                break;

            case 3:
                // Admin account
                $_SESSION['install_admin'] = [
                    'display_name' => trim($_POST['admin_display_name'] ?? ''),
                    'username' => trim($_POST['admin_username'] ?? ''),
                    'email' => trim($_POST['admin_email'] ?? ''),
                    'password' => $_POST['admin_password'] ?? '',
                ];
                $admin = $_SESSION['install_admin'];
                if (empty($admin['display_name'])) $errors[] = '表示名を入力してください。';
                if (empty($admin['username'])) $errors[] = 'ユーザー名を入力してください。';
                if (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $admin['username'])) $errors[] = 'ユーザー名は半角英数字・アンダースコア3~50文字で入力してください。';
                if (empty($admin['email']) || !filter_var($admin['email'], FILTER_VALIDATE_EMAIL)) $errors[] = '有効なメールアドレスを入力してください。';
                if (strlen($admin['password']) < 8) $errors[] = 'パスワードは8文字以上で入力してください。';
                if (!preg_match('/[A-Za-z]/', $admin['password']) || !preg_match('/[0-9]/', $admin['password'])) {
                    $errors[] = 'パスワードには英字と数字の両方を含めてください。';
                }
                if ($admin['password'] !== ($_POST['admin_password_confirm'] ?? '')) $errors[] = 'パスワードが一致しません。';

                if (empty($errors)) {
                    header('Location: install.php?step=4');
                    exit;
                }
                $step = 3;
                break;

            case 4:
                // App settings
                $_SESSION['install_settings'] = [
                    'app_name' => trim($_POST['app_name'] ?? 'TeamSpace'),
                    'company_name' => trim($_POST['company_name'] ?? ''),
                    'timezone' => trim($_POST['timezone'] ?? 'Asia/Tokyo'),
                ];
                if (empty($_SESSION['install_settings']['app_name'])) $errors[] = 'アプリケーション名を入力してください。';
                if (empty($errors)) {
                    header('Location: install.php?step=5');
                    exit;
                }
                $step = 4;
                break;

            case 5:
                // Final: Write config files, create admin, insert settings
                $db = $_SESSION['install_db'] ?? null;
                $admin = $_SESSION['install_admin'] ?? null;
                $settings = $_SESSION['install_settings'] ?? null;

                if (!$db || !$admin || !$settings) {
                    $errors[] = 'セッションデータが不足しています。最初からやり直してください。';
                    $step = 1;
                    break;
                }

                try {
                    // 1. Write config/database.php
                    $dbConfig = "<?php\n// config/database.php\nreturn [\n"
                        . "    'host' => " . var_export($db['host'], true) . ",\n"
                        . "    'dbname' => " . var_export($db['name'], true) . ",\n"
                        . "    'username' => " . var_export($db['user'], true) . ",\n"
                        . "    'password' => " . var_export($db['pass'], true) . ",\n"
                        . "    'charset' => 'utf8mb4_general_ci',\n"
                        . "    'port' => " . $db['port'] . "\n"
                        . "];\n";
                    file_put_contents($rootDir . '/config/database.php', $dbConfig);

                    // 2. Write config/config.php
                    $appUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
                    $appUrl = preg_replace('#/public$#', '', $appUrl);
                    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                    $fullUrl = $protocol . '://' . $_SERVER['HTTP_HOST'] . $appUrl;

                    $appConfig = "<?php\n// config/config.php\nreturn [\n"
                        . "    'app' => [\n"
                        . "        'name' => " . var_export($settings['app_name'], true) . ",\n"
                        . "        'version' => '1.2.0',\n"
                        . "        'timezone' => " . var_export($settings['timezone'], true) . ",\n"
                        . "        'debug' => true,\n"
                        . "        'url' => " . var_export($fullUrl, true) . "\n"
                        . "    ],\n"
                        . "    'auth' => [\n"
                        . "        'session_name' => 'gsession_user',\n"
                        . "        'session_lifetime' => 86400, // 24時間\n"
                        . "        'remember_me_days' => 30\n"
                        . "    ],\n"
                        . "    'upload' => [\n"
                        . "        'max_size' => 10485760, // 10MB\n"
                        . "        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx']\n"
                        . "    ]\n"
                        . "];\n\n\n"
                        . "// ベースパス設定\n"
                        . "if (!defined('BASE_PATH')) {\n"
                        . "    define('BASE_PATH', '');\n"
                        . "}\n";
                    file_put_contents($rootDir . '/config/config.php', $appConfig);

                    // 3. Connect to DB and insert admin + settings
                    $dsn = "mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset=utf8mb4";
                    $pdo = new PDO($dsn, $db['user'], $db['pass'], [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                    ]);

                    // Insert admin user
                    $hashedPassword = password_hash($admin['password'], PASSWORD_DEFAULT);
                    $nameParts = explode(' ', $admin['display_name'], 2);
                    $lastName = $nameParts[0];
                    $firstName = $nameParts[1] ?? '';

                    $stmt = $pdo->prepare("INSERT INTO users (username, password, email, first_name, last_name, display_name, status, role, created_at) VALUES (?, ?, ?, ?, ?, ?, 'active', 'admin', NOW())");
                    $stmt->execute([
                        $admin['username'],
                        $hashedPassword,
                        $admin['email'],
                        $firstName,
                        $lastName,
                        $admin['display_name']
                    ]);

                    // Insert settings
                    $settingsData = [
                        ['app_name', $settings['app_name'], 'アプリケーション名'],
                        ['company_name', $settings['company_name'], '会社名'],
                        ['timezone', $settings['timezone'], 'タイムゾーン'],
                    ];
                    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value, description) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
                    foreach ($settingsData as $row) {
                        $stmt->execute($row);
                    }

                    // 4. Create install.lock
                    file_put_contents($rootDir . '/install.lock', date('Y-m-d H:i:s') . "\nInstalled by: " . $admin['username']);

                    // Clear session install data
                    unset($_SESSION['install_db'], $_SESSION['install_admin'], $_SESSION['install_settings']);

                    $success = 'インストールが完了しました。';
                } catch (PDOException $e) {
                    $errors[] = 'データベースエラー: ' . $e->getMessage();
                    // Clean up partial config files on failure
                    @unlink($rootDir . '/config/database.php');
                    @unlink($rootDir . '/config/config.php');
                    @unlink($rootDir . '/install.lock');
                    $step = 5;
                } catch (\Exception $e) {
                    $errors[] = 'エラー: ' . $e->getMessage();
                    @unlink($rootDir . '/config/database.php');
                    @unlink($rootDir . '/config/config.php');
                    @unlink($rootDir . '/install.lock');
                    $step = 5;
                }
                break;
        }
    }
}

// ========== Requirements Check Data ==========
function checkRequirements(): array {
    $rootDir = dirname(__DIR__);
    $checks = [];

    // PHP Version
    $checks[] = [
        'name' => 'PHP バージョン (>= 7.4)',
        'detail' => 'PHP ' . PHP_VERSION,
        'pass' => version_compare(PHP_VERSION, '7.4.0', '>=')
    ];

    // Extensions
    $extensions = [
        'pdo' => 'PDO',
        'pdo_mysql' => 'PDO MySQL',
        'mbstring' => 'Mbstring',
        'json' => 'JSON',
        'fileinfo' => 'Fileinfo',
    ];
    foreach ($extensions as $ext => $label) {
        $checks[] = [
            'name' => "{$label} エクステンション",
            'detail' => $ext,
            'pass' => extension_loaded($ext)
        ];
    }
    // GD or Imagick
    $gdOrImagick = extension_loaded('gd') || extension_loaded('imagick');
    $detail = extension_loaded('gd') ? 'GD' : (extension_loaded('imagick') ? 'Imagick' : '未検出');
    $checks[] = [
        'name' => 'GD または Imagick エクステンション',
        'detail' => $detail,
        'pass' => $gdOrImagick
    ];

    // Writable directories
    $dirs = ['config/', 'uploads/', 'exports/'];
    foreach ($dirs as $dir) {
        $fullPath = $rootDir . '/' . $dir;
        $checks[] = [
            'name' => "{$dir} 書き込み権限",
            'detail' => $fullPath,
            'pass' => is_dir($fullPath) && is_writable($fullPath)
        ];
    }

    return $checks;
}

$requirements = checkRequirements();
$allPassed = !in_array(false, array_column($requirements, 'pass'));

// Timezone list for step 4
$timezones = [
    'Asia/Tokyo' => 'アジア/東京 (JST)',
    'Asia/Shanghai' => 'アジア/上海 (CST)',
    'Asia/Seoul' => 'アジア/ソウル (KST)',
    'Asia/Singapore' => 'アジア/シンガポール (SGT)',
    'Asia/Kolkata' => 'アジア/コルカタ (IST)',
    'Europe/London' => 'ヨーロッパ/ロンドン (GMT)',
    'Europe/Berlin' => 'ヨーロッパ/ベルリン (CET)',
    'America/New_York' => 'アメリカ/ニューヨーク (EST)',
    'America/Los_Angeles' => 'アメリカ/ロサンゼルス (PST)',
    'Pacific/Auckland' => '太平洋/オークランド (NZST)',
    'UTC' => 'UTC',
];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>インストールウィザード</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root { --primary: #4f46e5; --primary-hover: #4338ca; }
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Helvetica Neue', Arial, 'Hiragino Kaku Gothic ProN', 'Hiragino Sans', Meiryo, sans-serif; }
        .installer-card { max-width: 720px; width: 100%; margin: 2rem; }
        .card { border: none; border-radius: 1rem; box-shadow: 0 20px 60px rgba(0,0,0,.2); }
        .card-header { background: var(--primary); border-radius: 1rem 1rem 0 0 !important; padding: 1.5rem 2rem; }
        .card-body { padding: 2rem; }
        .step-indicators { display: flex; justify-content: center; gap: .5rem; margin-bottom: 1.5rem; }
        .step-dot { width: 12px; height: 12px; border-radius: 50%; background: #d1d5db; transition: all .3s; }
        .step-dot.active { background: var(--primary); transform: scale(1.2); }
        .step-dot.done { background: #10b981; }
        .check-item { display: flex; align-items: center; padding: .5rem 0; border-bottom: 1px solid #f3f4f6; }
        .check-item:last-child { border-bottom: none; }
        .check-icon { font-size: 1.2rem; margin-right: .75rem; }
        .check-pass { color: #10b981; }
        .check-fail { color: #ef4444; }
        .btn-primary { background: var(--primary); border-color: var(--primary); }
        .btn-primary:hover { background: var(--primary-hover); border-color: var(--primary-hover); }
        .btn-outline-primary { color: var(--primary); border-color: var(--primary); }
        .btn-outline-primary:hover { background: var(--primary); border-color: var(--primary); }
        .form-control:focus, .form-select:focus { border-color: var(--primary); box-shadow: 0 0 0 .2rem rgba(79,70,229,.25); }
        .password-strength { height: 4px; border-radius: 2px; margin-top: .25rem; transition: all .3s; }
        .strength-weak { background: #ef4444; width: 33%; }
        .strength-medium { background: #f59e0b; width: 66%; }
        .strength-strong { background: #10b981; width: 100%; }
        .success-icon { font-size: 4rem; color: #10b981; }
        #db-test-result { display: none; }
    </style>
</head>
<body>
<div class="installer-card">
    <div class="card">
        <div class="card-header text-white text-center">
            <h3 class="mb-1"><i class="bi bi-gear-wide-connected me-2"></i>GroupWare インストーラー</h3>
            <small class="opacity-75">セットアップウィザード</small>
        </div>
        <div class="card-body">

            <!-- Step Indicators -->
            <div class="step-indicators">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <div class="step-dot <?= $i < $step ? 'done' : ($i === $step ? 'active' : '') ?>"></div>
                <?php endfor; ?>
            </div>

            <!-- Errors -->
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    <?php foreach ($errors as $err): ?>
                        <div><?= htmlspecialchars($err) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- ===== STEP 1: Requirements ===== -->
            <?php if ($step === 1): ?>
                <h5 class="mb-3"><i class="bi bi-clipboard-check me-2"></i>ステップ 1: 環境チェック</h5>
                <p class="text-muted mb-3">インストールに必要な要件を確認いたします。</p>

                <div class="mb-4">
                    <?php foreach ($requirements as $req): ?>
                        <div class="check-item">
                            <span class="check-icon <?= $req['pass'] ? 'check-pass' : 'check-fail' ?>">
                                <i class="bi <?= $req['pass'] ? 'bi-check-circle-fill' : 'bi-x-circle-fill' ?>"></i>
                            </span>
                            <div>
                                <div class="fw-medium"><?= htmlspecialchars($req['name']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($req['detail']) ?></small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <input type="hidden" name="install_step" value="1">
                    <?php if ($allPassed): ?>
                        <div class="alert alert-success"><i class="bi bi-check2-all me-1"></i>すべての要件を満たしています。次のステップへお進みください。</div>
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-arrow-right me-1"></i>次へ</button>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-1"></i>一部の要件が満たされていません。上記の問題を解決してからページを再読み込みしてください。</div>
                        <div class="text-end">
                            <a href="install.php" class="btn btn-outline-primary"><i class="bi bi-arrow-clockwise me-1"></i>再チェック</a>
                        </div>
                    <?php endif; ?>
                </form>

            <!-- ===== STEP 2: Database ===== -->
            <?php elseif ($step === 2): ?>
                <h5 class="mb-3"><i class="bi bi-database me-2"></i>ステップ 2: データベース設定</h5>
                <p class="text-muted mb-3">MySQLデータベースの接続情報を入力してください。</p>

                <?php $db = $_SESSION['install_db'] ?? ['host' => 'localhost', 'port' => 3306, 'name' => '', 'user' => 'root', 'pass' => '']; ?>

                <form method="post" id="db-form">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <input type="hidden" name="install_step" value="2">

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">データベースホスト</label>
                            <input type="text" class="form-control" name="db_host" id="db_host" value="<?= htmlspecialchars($db['host']) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">ポート</label>
                            <input type="number" class="form-control" name="db_port" id="db_port" value="<?= (int)$db['port'] ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">データベース名</label>
                            <input type="text" class="form-control" name="db_name" id="db_name" value="<?= htmlspecialchars($db['name']) ?>" required placeholder="例: groupware">
                            <div class="form-text">データベースが存在しない場合は自動的に作成されます。</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">ユーザー名</label>
                            <input type="text" class="form-control" name="db_user" id="db_user" value="<?= htmlspecialchars($db['user']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">パスワード</label>
                            <input type="password" class="form-control" name="db_pass" id="db_pass" value="<?= htmlspecialchars($db['pass']) ?>">
                        </div>
                    </div>

                    <div class="mt-3">
                        <button type="button" class="btn btn-outline-primary btn-sm" id="btn-test-db">
                            <i class="bi bi-plug me-1"></i>接続テスト
                        </button>
                        <span id="db-test-result" class="ms-2 small"></span>
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <a href="install.php?step=1" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>戻る</a>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-arrow-right me-1"></i>次へ</button>
                    </div>
                </form>

            <!-- ===== STEP 3: Admin Account ===== -->
            <?php elseif ($step === 3): ?>
                <h5 class="mb-3"><i class="bi bi-person-badge me-2"></i>ステップ 3: 管理者アカウント作成</h5>
                <p class="text-muted mb-3">システム管理者のアカウント情報を設定してください。</p>

                <?php $admin = $_SESSION['install_admin'] ?? ['display_name' => '', 'username' => '', 'email' => '', 'password' => '']; ?>

                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <input type="hidden" name="install_step" value="3">

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">表示名</label>
                            <input type="text" class="form-control" name="admin_display_name" value="<?= htmlspecialchars($admin['display_name']) ?>" required placeholder="例: 管理者 太郎">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">ユーザー名</label>
                            <input type="text" class="form-control" name="admin_username" value="<?= htmlspecialchars($admin['username']) ?>" required pattern="[a-zA-Z0-9_]{3,50}" placeholder="例: admin">
                            <div class="form-text">半角英数字・アンダースコア (3~50文字)</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">メールアドレス</label>
                            <input type="email" class="form-control" name="admin_email" value="<?= htmlspecialchars($admin['email']) ?>" required placeholder="例: admin@example.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">パスワード</label>
                            <input type="password" class="form-control" name="admin_password" id="admin_password" required minlength="8">
                            <div class="password-strength" id="password-strength"></div>
                            <div class="form-text">8文字以上、英字と数字を含めてください。</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">パスワード (確認)</label>
                            <input type="password" class="form-control" name="admin_password_confirm" id="admin_password_confirm" required minlength="8">
                            <div class="form-text" id="password-match"></div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <a href="install.php?step=2" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>戻る</a>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-arrow-right me-1"></i>次へ</button>
                    </div>
                </form>

            <!-- ===== STEP 4: App Settings ===== -->
            <?php elseif ($step === 4): ?>
                <h5 class="mb-3"><i class="bi bi-sliders me-2"></i>ステップ 4: アプリケーション設定</h5>
                <p class="text-muted mb-3">基本的なアプリケーション設定を行ってください。</p>

                <?php $settings = $_SESSION['install_settings'] ?? ['app_name' => 'TeamSpace', 'company_name' => '', 'timezone' => 'Asia/Tokyo']; ?>

                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <input type="hidden" name="install_step" value="4">

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">アプリケーション名</label>
                            <input type="text" class="form-control" name="app_name" value="<?= htmlspecialchars($settings['app_name']) ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">会社名 / 組織名</label>
                            <input type="text" class="form-control" name="company_name" value="<?= htmlspecialchars($settings['company_name']) ?>" placeholder="例: 株式会社サンプル">
                        </div>
                        <div class="col-12">
                            <label class="form-label">タイムゾーン</label>
                            <select class="form-select" name="timezone">
                                <?php foreach ($timezones as $tz => $label): ?>
                                    <option value="<?= $tz ?>" <?= $settings['timezone'] === $tz ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <a href="install.php?step=3" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>戻る</a>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-arrow-right me-1"></i>次へ</button>
                    </div>
                </form>

            <!-- ===== STEP 5: Complete ===== -->
            <?php elseif ($step === 5): ?>

                <?php if ($success): ?>
                    <div class="text-center py-4">
                        <div class="success-icon mb-3"><i class="bi bi-check-circle-fill"></i></div>
                        <h4 class="mb-2">インストール完了</h4>
                        <p class="text-muted mb-4">
                            グループウェアのセットアップが正常に完了いたしました。<br>
                            以下のボタンからログインページへ移動できます。
                        </p>
                        <div class="alert alert-warning small">
                            <i class="bi bi-shield-lock me-1"></i>
                            セキュリティのため、<code>public/install.php</code> ファイルを削除することを推奨いたします。
                        </div>
                        <a href="index.php" class="btn btn-primary btn-lg mt-2">
                            <i class="bi bi-box-arrow-in-right me-1"></i>ログインページへ
                        </a>
                    </div>
                <?php else: ?>
                    <h5 class="mb-3"><i class="bi bi-rocket-takeoff me-2"></i>ステップ 5: インストール実行</h5>
                    <p class="text-muted mb-3">以下の内容でインストールを実行いたします。内容をご確認の上、「インストール実行」ボタンを押してください。</p>

                    <?php
                    $db = $_SESSION['install_db'] ?? [];
                    $admin = $_SESSION['install_admin'] ?? [];
                    $appSettings = $_SESSION['install_settings'] ?? [];
                    ?>

                    <div class="mb-3">
                        <h6 class="text-muted"><i class="bi bi-database me-1"></i>データベース</h6>
                        <table class="table table-sm table-borderless mb-0">
                            <tr><td class="text-muted" style="width:140px">ホスト</td><td><?= htmlspecialchars($db['host'] ?? '') ?>:<?= (int)($db['port'] ?? 3306) ?></td></tr>
                            <tr><td class="text-muted">データベース名</td><td><?= htmlspecialchars($db['name'] ?? '') ?></td></tr>
                            <tr><td class="text-muted">ユーザー名</td><td><?= htmlspecialchars($db['user'] ?? '') ?></td></tr>
                        </table>
                    </div>
                    <div class="mb-3">
                        <h6 class="text-muted"><i class="bi bi-person-badge me-1"></i>管理者アカウント</h6>
                        <table class="table table-sm table-borderless mb-0">
                            <tr><td class="text-muted" style="width:140px">表示名</td><td><?= htmlspecialchars($admin['display_name'] ?? '') ?></td></tr>
                            <tr><td class="text-muted">ユーザー名</td><td><?= htmlspecialchars($admin['username'] ?? '') ?></td></tr>
                            <tr><td class="text-muted">メール</td><td><?= htmlspecialchars($admin['email'] ?? '') ?></td></tr>
                        </table>
                    </div>
                    <div class="mb-4">
                        <h6 class="text-muted"><i class="bi bi-sliders me-1"></i>アプリケーション設定</h6>
                        <table class="table table-sm table-borderless mb-0">
                            <tr><td class="text-muted" style="width:140px">アプリ名</td><td><?= htmlspecialchars($appSettings['app_name'] ?? '') ?></td></tr>
                            <tr><td class="text-muted">会社名</td><td><?= htmlspecialchars($appSettings['company_name'] ?? '-') ?></td></tr>
                            <tr><td class="text-muted">タイムゾーン</td><td><?= htmlspecialchars($appSettings['timezone'] ?? '') ?></td></tr>
                        </table>
                    </div>

                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                        <input type="hidden" name="install_step" value="5">
                        <div class="d-flex justify-content-between">
                            <a href="install.php?step=4" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>戻る</a>
                            <button type="submit" class="btn btn-primary" id="btn-install" onclick="this.disabled=true;this.innerHTML='<span class=\'spinner-border spinner-border-sm me-1\'></span>インストール中...';this.form.submit();">
                                <i class="bi bi-download me-1"></i>インストール実行
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            <?php endif; ?>

        </div>
    </div>
</div>

<script>
// DB Connection Test
document.addEventListener('DOMContentLoaded', function() {
    const btnTest = document.getElementById('btn-test-db');
    if (btnTest) {
        btnTest.addEventListener('click', function() {
            const result = document.getElementById('db-test-result');
            result.style.display = 'inline';
            result.innerHTML = '<span class="text-muted"><span class="spinner-border spinner-border-sm"></span> 接続中...</span>';
            btnTest.disabled = true;

            const formData = new FormData();
            formData.append('action', 'test_db');
            formData.append('csrf_token', '<?= $csrfToken ?>');
            formData.append('db_host', document.getElementById('db_host').value);
            formData.append('db_port', document.getElementById('db_port').value);
            formData.append('db_name', document.getElementById('db_name').value);
            formData.append('db_user', document.getElementById('db_user').value);
            formData.append('db_pass', document.getElementById('db_pass').value);

            fetch('install.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        result.innerHTML = '<span class="text-success"><i class="bi bi-check-circle"></i> ' + data.message + '</span>';
                    } else {
                        result.innerHTML = '<span class="text-danger"><i class="bi bi-x-circle"></i> ' + data.message + '</span>';
                    }
                    btnTest.disabled = false;
                })
                .catch(() => {
                    result.innerHTML = '<span class="text-danger"><i class="bi bi-x-circle"></i> 通信エラーが発生しました。</span>';
                    btnTest.disabled = false;
                });
        });
    }

    // Password strength indicator
    const pwField = document.getElementById('admin_password');
    const pwConfirm = document.getElementById('admin_password_confirm');
    if (pwField) {
        pwField.addEventListener('input', function() {
            const bar = document.getElementById('password-strength');
            const val = this.value;
            bar.className = 'password-strength';
            if (val.length === 0) { bar.style.width = '0'; return; }
            let score = 0;
            if (val.length >= 8) score++;
            if (/[A-Za-z]/.test(val) && /[0-9]/.test(val)) score++;
            if (/[^A-Za-z0-9]/.test(val) && val.length >= 10) score++;
            if (score <= 1) bar.classList.add('strength-weak');
            else if (score === 2) bar.classList.add('strength-medium');
            else bar.classList.add('strength-strong');
        });
    }
    if (pwConfirm) {
        pwConfirm.addEventListener('input', function() {
            const msg = document.getElementById('password-match');
            if (this.value && pwField.value !== this.value) {
                msg.innerHTML = '<span class="text-danger">パスワードが一致しません</span>';
            } else if (this.value) {
                msg.innerHTML = '<span class="text-success">一致しています</span>';
            } else {
                msg.innerHTML = '';
            }
        });
    }
});
</script>
</body>
</html>

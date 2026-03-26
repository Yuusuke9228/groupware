<?php
// views/auth/login.php
$settingModel = new \Models\Setting();
$appName = $settingModel->getAppName();
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#2b7de9">
    <title>ログイン - <?php echo htmlspecialchars($appName); ?></title>
    <link rel="icon" type="image/svg+xml" href="<?php echo BASE_PATH; ?>/img_icon/favicon.svg">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">

    <style>
        :root {
            --primary: #2b7de9;
            --primary-dark: #1a5dc0;
            --primary-gradient: linear-gradient(135deg, #2b7de9 0%, #1a5dc0 100%);
        }

        * { box-sizing: border-box; }

        html, body {
            height: 100%;
            margin: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", "Hiragino Sans", "Noto Sans JP", sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--primary-gradient);
            min-height: 100vh;
            padding: 20px;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 8px 40px rgba(0,0,0,0.15);
            padding: 40px 36px;
            animation: slideUp 0.4s ease;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .login-logo {
            width: 64px;
            height: 64px;
            background: var(--primary-gradient);
            color: #fff;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin: 0 auto 16px;
            box-shadow: 0 4px 12px rgba(43, 125, 233, 0.3);
        }

        .login-title {
            text-align: center;
            font-size: 22px;
            font-weight: 700;
            color: #202124;
            margin-bottom: 4px;
        }

        .login-subtitle {
            text-align: center;
            font-size: 14px;
            color: #5f6368;
            margin-bottom: 28px;
        }

        .form-floating {
            margin-bottom: 0;
        }

        .form-floating:first-of-type .form-control {
            border-bottom-left-radius: 0;
            border-bottom-right-radius: 0;
            border-bottom: none;
        }

        .form-floating:last-of-type .form-control {
            border-top-left-radius: 0;
            border-top-right-radius: 0;
        }

        .form-control {
            border: 1px solid #e0e0e0;
            font-size: 14px;
            padding: 16px 12px;
            transition: all 0.2s;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(43, 125, 233, 0.1);
        }

        .form-floating label {
            font-size: 13px;
            color: #5f6368;
        }

        .btn-login {
            width: 100%;
            padding: 12px;
            font-size: 15px;
            font-weight: 600;
            background: var(--primary-gradient);
            border: none;
            border-radius: 10px;
            color: #fff;
            margin-top: 20px;
            transition: all 0.2s;
            box-shadow: 0 2px 8px rgba(43, 125, 233, 0.3);
        }

        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 16px rgba(43, 125, 233, 0.4);
            background: linear-gradient(135deg, #3a8cf0 0%, #2068d0 100%);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .form-check {
            margin-top: 16px;
        }

        .form-check-input:checked {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .form-check-label {
            font-size: 13px;
            color: #5f6368;
        }

        .alert-danger {
            border: none;
            background: #fce8e6;
            color: #ea4335;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 16px;
        }

        .login-footer {
            text-align: center;
            margin-top: 24px;
            font-size: 12px;
            color: #80868b;
        }

        .login-hint {
            text-align: center;
            margin-top: 16px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
            font-size: 12px;
            color: #5f6368;
        }

        @media (max-width: 480px) {
            .login-card {
                padding: 32px 24px;
                border-radius: 12px;
            }
        }
    </style>
</head>

<body>
    <main class="login-card">
        <form action="<?php echo BASE_PATH; ?>/login<?php echo isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : ''; ?>" method="post">
            <div class="login-logo">
                <i class="fas fa-th-large"></i>
            </div>
            <h1 class="login-title"><img src="<?php echo BASE_PATH; ?>/img_icon/favicon.svg" alt="" style="height:36px;border-radius:8px;margin-right:8px;vertical-align:middle;"><?php echo htmlspecialchars($appName); ?></h1>
            <p class="login-subtitle">アカウントにログイン</p>

            <?php if (isset($_SESSION['login_error'])): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-circle me-1"></i>
                    <?php echo htmlspecialchars($_SESSION['login_error']); ?>
                    <?php unset($_SESSION['login_error']); ?>
                </div>
            <?php endif; ?>

            <div class="form-floating">
                <input type="text" class="form-control" id="username" name="username" placeholder="ユーザー名" required autofocus>
                <label for="username"><i class="fas fa-user me-1"></i> ユーザー名</label>
            </div>
            <div class="form-floating">
                <input type="password" class="form-control" id="password" name="password" placeholder="パスワード" required>
                <label for="password"><i class="fas fa-lock me-1"></i> パスワード</label>
            </div>

            <div class="form-check text-start">
                <input class="form-check-input" type="checkbox" value="1" id="remember" name="remember">
                <label class="form-check-label" for="remember">ログイン状態を保持する</label>
            </div>

            <button class="btn btn-login" type="submit">
                <i class="fas fa-sign-in-alt me-1"></i> ログイン
            </button>


            <?php
            $configFile = __DIR__ . '/../../config/config.php';
            $demoMode = false;
            if (file_exists($configFile)) {
                $cfg = include $configFile;
                $demoMode = !empty($cfg['app']['demo_mode']);
            }
            if ($demoMode):
            ?>
            <div class="demo-hint" style="margin-top:20px;padding:16px;background:linear-gradient(135deg,#fff8e1,#fff3cd);border:1px solid #ffc107;border-radius:10px;font-size:13px;">
                <div style="font-weight:700;color:#856404;margin-bottom:10px;font-size:14px;">
                    <i class="fas fa-info-circle me-1"></i> デモサイトへようこそ
                </div>
                <p style="margin:0 0 10px;color:#664d03;">以下のアカウントでログインしてお試しいただけます。</p>
                <table style="width:100%;font-size:12px;border-collapse:collapse;">
                    <tr style="border-bottom:1px solid rgba(0,0,0,0.1);">
                        <td style="padding:4px 0;font-weight:600;color:#856404;">管理者</td>
                        <td style="padding:4px 8px;"><code style="background:#fff;padding:2px 6px;border-radius:4px;">admin</code></td>
                        <td style="padding:4px 0;"><code style="background:#fff;padding:2px 6px;border-radius:4px;">demo1234</code></td>
                    </tr>
                    <tr style="border-bottom:1px solid rgba(0,0,0,0.1);">
                        <td style="padding:4px 0;font-weight:600;color:#856404;">一般①</td>
                        <td style="padding:4px 8px;"><code style="background:#fff;padding:2px 6px;border-radius:4px;">yamada</code></td>
                        <td style="padding:4px 0;"><code style="background:#fff;padding:2px 6px;border-radius:4px;">demo1234</code></td>
                    </tr>
                    <tr style="border-bottom:1px solid rgba(0,0,0,0.1);">
                        <td style="padding:4px 0;font-weight:600;color:#856404;">一般②</td>
                        <td style="padding:4px 8px;"><code style="background:#fff;padding:2px 6px;border-radius:4px;">tanaka</code></td>
                        <td style="padding:4px 0;"><code style="background:#fff;padding:2px 6px;border-radius:4px;">demo1234</code></td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0;font-weight:600;color:#856404;">一般③</td>
                        <td style="padding:4px 8px;"><code style="background:#fff;padding:2px 6px;border-radius:4px;">suzuki</code></td>
                        <td style="padding:4px 0;"><code style="background:#fff;padding:2px 6px;border-radius:4px;">demo1234</code></td>
                    </tr>
                </table>
                <p style="margin:10px 0 0;color:#856404;font-size:11px;">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    デモ環境のデータは定期的にリセットされます。本番データの入力はお控えください。
                </p>
            </div>
            <?php endif; ?>

            <div class="login-footer">
                &copy; 2024-<?php echo date('Y'); ?> Yuusuke9228. All rights reserved.
            </div>
        </form>
    </main>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

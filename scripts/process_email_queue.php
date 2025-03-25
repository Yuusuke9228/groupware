<?php
// scripts/process_email_queue.php
// メール送信キュー処理バッチスクリプト
// crontabに登録して定期実行 (例: * * * * * php /path/to/process_email_queue.php)

// アプリケーションルートディレクトリから相対的に設定
$root = dirname(__DIR__);

// 基本設定
date_default_timezone_set('Asia/Tokyo');
ini_set('display_errors', true);
error_reporting(E_ALL);
mb_internal_encoding('UTF-8');

// オートローダー設定
spl_autoload_register(function ($class) use ($root) {
    // 名前空間を考慮してファイルパスに変換
    $path = str_replace('\\', DIRECTORY_SEPARATOR, $class);
    $file = $root . DIRECTORY_SEPARATOR . $path . '.php';

    // 標準パスでファイルを探す
    if (file_exists($file)) {
        require_once $file;
        return true;
    }

    // 小文字のパスで試す
    $lowercasePath = strtolower($path);
    $file = $root . DIRECTORY_SEPARATOR . $lowercasePath . '.php';

    if (file_exists($file)) {
        require_once $file;
        return true;
    }

    // 大文字と小文字を入れ替えたパスで試す (Core -> core)
    $altPath = str_ireplace('Core', 'core', $path);
    $file = $root . DIRECTORY_SEPARATOR . $altPath . '.php';

    if (file_exists($file)) {
        require_once $file;
        return true;
    }

    return false;
});

// ベースパス定義
if (!defined('BASE_PATH')) {
    define('BASE_PATH', '');
}

// 処理開始
echo "Email Queue Processing Started: " . date('Y-m-d H:i:s') . PHP_EOL;

// 通知モデルのインスタンス作成
$notification = new Models\Notification();

// 一度に処理するメール数
$limit = 20;

// メール送信処理の実行
$result = $notification->processEmailQueue($limit);

// 処理結果の出力
echo "Processing completed: " . PHP_EOL;
echo "Success: " . ($result['success'] ? 'Yes' : 'No') . PHP_EOL;
echo "Message: " . $result['message'] . PHP_EOL;
echo "Processed: " . $result['processed'] . PHP_EOL;
echo "Success count: " . $result['success_count'] . PHP_EOL;
echo "Failed count: " . $result['failed_count'] . PHP_EOL;
echo "End time: " . date('Y-m-d H:i:s') . PHP_EOL;

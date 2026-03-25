<?php
$root = dirname(__DIR__);

date_default_timezone_set('Asia/Tokyo');
ini_set('display_errors', true);
error_reporting(E_ALL);
mb_internal_encoding('UTF-8');

spl_autoload_register(function ($class) use ($root) {
    $path = str_replace('\\', DIRECTORY_SEPARATOR, $class);
    $file = $root . DIRECTORY_SEPARATOR . $path . '.php';
    if (file_exists($file)) {
        require_once $file;
        return true;
    }
    $lower = $root . DIRECTORY_SEPARATOR . strtolower($path) . '.php';
    if (file_exists($lower)) {
        require_once $lower;
        return true;
    }
    $alt = $root . DIRECTORY_SEPARATOR . str_ireplace('Core', 'core', $path) . '.php';
    if (file_exists($alt)) {
        require_once $alt;
        return true;
    }
    return false;
});

if (!defined('BASE_PATH')) {
    define('BASE_PATH', '');
}

echo "Calendar Import Processing Started: " . date('Y-m-d H:i:s') . PHP_EOL;
$service = new Services\CalendarImportService();
$results = $service->syncDueSubscriptions(50);

echo 'Subscriptions processed: ' . count($results) . PHP_EOL;
foreach ($results as $result) {
    echo sprintf(
        "#%d %s%s",
        (int)($result['subscription_id'] ?? 0),
        $result['success'] ? 'OK ' : 'NG ',
        $result['message'] ?? ''
    ) . PHP_EOL;
}
echo "End time: " . date('Y-m-d H:i:s') . PHP_EOL;

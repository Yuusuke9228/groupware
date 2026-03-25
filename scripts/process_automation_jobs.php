<?php
// cron: php scripts/process_automation_jobs.php

date_default_timezone_set('Asia/Tokyo');

spl_autoload_register(function ($class) {
    $path = str_replace('\\', DIRECTORY_SEPARATOR, $class);
    $file = __DIR__ . '/../' . $path . '.php';

    if (file_exists($file)) {
        require_once $file;
        return true;
    }

    $lowercasePath = strtolower($path);
    $file = __DIR__ . '/../' . $lowercasePath . '.php';

    if (file_exists($file)) {
        require_once $file;
        return true;
    }

    $altPath = str_ireplace('Core', 'core', $path);
    $file = __DIR__ . '/../' . $altPath . '.php';

    if (file_exists($file)) {
        require_once $file;
        return true;
    }

    return false;
});

try {
    $model = new \Models\AutomationJob();
    $result = $model->runDueJobs(100);
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
} catch (\Throwable $e) {
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
}

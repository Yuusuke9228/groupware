#!/usr/bin/env php
<?php

// TeamSpace demo data rebuild script
// usage:
//   php scripts/rebuild_demo_data.php --mode=refresh --years=3
//   php scripts/rebuild_demo_data.php --mode=rebuild --years=3

if (php_sapi_name() !== 'cli') {
    fwrite(STDERR, "This script must be run from CLI.\n");
    exit(1);
}
@set_time_limit(0);

$rootDir = dirname(__DIR__);

spl_autoload_register(function ($class) use ($rootDir) {
    $path = str_replace('\\', DIRECTORY_SEPARATOR, $class);
    $candidates = [
        $rootDir . DIRECTORY_SEPARATOR . $path . '.php',
        $rootDir . DIRECTORY_SEPARATOR . strtolower($path) . '.php',
        $rootDir . DIRECTORY_SEPARATOR . str_ireplace('Core', 'core', $path) . '.php',
    ];

    foreach ($candidates as $file) {
        if (file_exists($file)) {
            require_once $file;
            return true;
        }
    }

    return false;
});

$options = getopt('', ['mode::', 'years::', 'user-id::']);
$mode = strtolower((string)($options['mode'] ?? 'refresh'));
$years = (int)($options['years'] ?? 3);
$years = max(1, min(5, $years));

if (!in_array($mode, ['refresh', 'rebuild'], true)) {
    fwrite(STDERR, "Invalid mode. Use refresh or rebuild.\n");
    exit(1);
}

try {
    $db = Core\Database::getInstance();

    $actorId = isset($options['user-id']) ? (int)$options['user-id'] : 0;
    if ($actorId <= 0) {
        $row = $db->fetch("SELECT id FROM users WHERE status = 'active' ORDER BY FIELD(role, 'admin', 'manager', 'user'), id ASC LIMIT 1");
        $actorId = (int)($row['id'] ?? 0);
    }

    if ($actorId <= 0) {
        fwrite(STDERR, "No active user found.\n");
        exit(1);
    }

    $service = new Services\DemoDataService();
    if ($mode === 'rebuild') {
        $result = $service->rebuildAllDemoData($actorId, $years);
    } else {
        $result = $service->refreshFutureDemoData($actorId, $years);
    }

    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
    if (empty($result['success'])) {
        exit(1);
    }
} catch (Throwable $e) {
    fwrite(STDERR, 'Demo data script failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

exit(0);

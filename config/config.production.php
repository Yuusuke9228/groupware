<?php
// config/config.php - 本番(デモサイト)用設定
return [
    'app' => [
        'name' => 'TeamSpace Demo',
        'version' => '1.2.0',
        'timezone' => 'Asia/Tokyo',
        'debug' => false,
        'url' => 'https://groupware.yuus-program.com',
        'demo_mode' => true,
    ],
    'auth' => [
        'session_name' => 'gsession_user',
        'session_lifetime' => 86400,
        'remember_me_days' => 30
    ],
    'upload' => [
        'max_size' => 10485760,
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx']
    ]
];

// ベースパス設定
if (!defined('BASE_PATH')) {
    define('BASE_PATH', '');
}

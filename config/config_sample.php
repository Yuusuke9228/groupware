<?php
// config/config.php
return [
    'app' => [
        'name' => 'GroupWare Sample',
        'version' => '1.0.0',
        'timezone' => 'Asia/Tokyo',
        'debug' => true,
        'url' => 'http://localhost/groupware'
    ],
    'auth' => [
        'session_name' => 'gsession_user',
        'session_lifetime' => 86400, // 24時間
        'remember_me_days' => 30
    ],
    'upload' => [
        'max_size' => 10485760, // 10MB
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx']
    ]
];


// ベースパス設定
if (!defined('BASE_PATH')) {
    define('BASE_PATH', '');
}


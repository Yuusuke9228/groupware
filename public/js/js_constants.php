<?php
// public/js/js_constants.php
// JavaScriptで使用する定数をPHPから出力するファイル

// 出力する内容を JS として解釈させる
header('Content-Type: application/javascript');

// ベースパスを設定
$basePath = dirname(dirname($_SERVER['SCRIPT_NAME']));
if ($basePath === '/') {
    $basePath = '';
}

// エスケープ（必要に応じて）
$escapedBasePath = addslashes($basePath);

// JS コードとして出力
echo <<<EOT
// JavaScript の定数
const BASE_PATH = "{$escapedBasePath}";
EOT;

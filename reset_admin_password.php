<?php
// reset_admin_password.php
// データベース設定を読み込む
$dbConfig = require_once __DIR__ . '/config/database.php';

try {
    // データベース接続
    $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};port={$dbConfig['port']}";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ];
    
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $options);
    
    // 新しいパスワードハッシュを生成
    $password = 'admin123';
    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    echo "Generated hash for 'admin123': $hash\n";
    
    // テスト検証
    $verified = password_verify($password, $hash);
    echo "Verification test: " . ($verified ? "Success" : "Failed") . "\n";
    
    // adminユーザーのパスワードを更新
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
    $result = $stmt->execute([$hash]);
    
    if ($result) {
        echo "Admin password updated successfully!\n";
    } else {
        echo "Failed to update admin password.\n";
    }
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}

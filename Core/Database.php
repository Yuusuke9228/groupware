<?php
// core/Database.php
namespace Core;

class Database {
    private static $instance = null;
    private $pdo;
    
    private function __construct() {
        $dbConfig = require_once __DIR__ . '/../config/database.php';
        
        try {
            $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};port={$dbConfig['port']}";
            $options = [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $this->pdo = new \PDO($dsn, $dbConfig['username'], $dbConfig['password'], $options);
        } catch (\PDOException $e) {
            throw new \Exception("データベース接続エラー: " . $e->getMessage());
        }
    }
    
    // シングルトンパターンでインスタンスを取得
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    // PDOインスタンスを取得
    public function getConnection() {
        return $this->pdo;
    }
    
    // SELECT文の実行
    public function query($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    // 1行取得
    public function fetch($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    // 全行取得
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    // INSERT, UPDATE, DELETE実行
    public function execute($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }
    
    // 最後に挿入されたID取得
    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }
    
    // トランザクション開始
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }
    
    // コミット
    public function commit() {
        return $this->pdo->commit();
    }
    
    // ロールバック
    public function rollBack() {
        return $this->pdo->rollBack();
    }
}

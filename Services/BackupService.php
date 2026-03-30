<?php

namespace Services;

use Models\BackupHistory;
use Models\Setting;

class BackupService
{
    private $historyModel;
    private $settingModel;
    private $rootPath;

    public function __construct()
    {
        $this->historyModel = new BackupHistory();
        $this->settingModel = new Setting();
        $this->rootPath = dirname(__DIR__);
    }

    public function run($executedBy)
    {
        $backupId = $this->historyModel->createRunning($executedBy);
        $storageDir = $this->getStoragePath();

        try {
            $this->ensureStorageDir($storageDir);

            $timestamp = date('Ymd_His');
            $fileName = sprintf('backup_%s_%d.zip', $timestamp, (int)$backupId);
            $zipPath = $storageDir . DIRECTORY_SEPARATOR . $fileName;

            $sqlDumpPath = $this->createDatabaseDump($backupId, $timestamp);

            $zip = new \ZipArchive();
            if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException('ZIPファイルを作成できません。');
            }

            $zip->addFile($sqlDumpPath, 'database/' . basename($sqlDumpPath));
            $this->addDirectoryIfExists($zip, $this->rootPath . '/uploads', 'uploads');
            $this->addDirectoryIfExists($zip, $this->rootPath . '/public/uploads', 'public_uploads');

            $metadata = [
                'backup_id' => (int)$backupId,
                'created_at' => date('c'),
                'executed_by' => (int)$executedBy,
                'includes' => ['database_dump', 'uploads', 'public/uploads'],
            ];
            $zip->addFromString('metadata.json', json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            $zip->close();

            @chmod($zipPath, 0600);
            @unlink($sqlDumpPath);

            clearstatcache(true, $zipPath);
            $size = @filesize($zipPath);
            if ($size === false) {
                $size = 0;
            }

            $this->historyModel->markSuccess($backupId, $fileName, $zipPath, $size);

            return [
                'id' => (int)$backupId,
                'file_name' => $fileName,
                'file_size' => (int)$size,
                'status' => 'success',
            ];
        } catch (\Throwable $e) {
            $this->historyModel->markFailed($backupId, $e->getMessage());
            throw $e;
        }
    }

    public function getHistory($limit = 50)
    {
        return $this->historyModel->listRecent($limit);
    }

    public function streamDownload($backupId)
    {
        $backup = $this->historyModel->find($backupId);
        if (!$backup) {
            throw new \RuntimeException('バックアップ履歴が見つかりません。');
        }

        if (($backup['status'] ?? '') !== 'success') {
            throw new \RuntimeException('成功したバックアップのみダウンロードできます。');
        }

        $path = (string)($backup['file_path'] ?? '');
        if ($path === '' || !is_file($path)) {
            throw new \RuntimeException('バックアップファイルが存在しません。');
        }

        $realPath = realpath($path);
        $realStorage = realpath($this->getStoragePath());
        if ($realPath === false || $realStorage === false || strpos($realPath, $realStorage) !== 0) {
            throw new \RuntimeException('無効なバックアップファイルです。');
        }

        $fileName = (string)($backup['file_name'] ?? basename($realPath));
        $fileSize = (int)filesize($realPath);

        header('Content-Type: application/zip');
        header('Content-Length: ' . $fileSize);
        header('Content-Disposition: attachment; filename="' . rawurlencode($fileName) . '"; filename*=UTF-8\'\'' . rawurlencode($fileName));
        header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');

        readfile($realPath);
        exit;
    }

    private function getStoragePath()
    {
        $settingPath = trim((string)$this->settingModel->get('backup_storage_path', ''));
        if ($settingPath === '') {
            return $this->rootPath . '/storage/backups';
        }

        if ($settingPath[0] === '/' || preg_match('/^[A-Za-z]:\\\\/', $settingPath)) {
            return rtrim($settingPath, DIRECTORY_SEPARATOR);
        }

        return rtrim($this->rootPath . '/' . ltrim($settingPath, '/'), DIRECTORY_SEPARATOR);
    }

    private function ensureStorageDir($path)
    {
        if (!is_dir($path) && !mkdir($path, 0700, true) && !is_dir($path)) {
            throw new \RuntimeException('バックアップ保存ディレクトリを作成できません。');
        }

        if (!is_writable($path)) {
            throw new \RuntimeException('バックアップ保存ディレクトリに書き込みできません。');
        }
    }

    private function createDatabaseDump($backupId, $timestamp)
    {
        $dbConfigPath = $this->rootPath . '/config/database.php';
        if (!is_file($dbConfigPath)) {
            throw new \RuntimeException('database.php が見つかりません。');
        }

        $dbConfig = require $dbConfigPath;
        $host = (string)($dbConfig['host'] ?? 'localhost');
        $port = (int)($dbConfig['port'] ?? 3306);
        $username = (string)($dbConfig['username'] ?? '');
        $password = (string)($dbConfig['password'] ?? '');
        $dbName = (string)($dbConfig['dbname'] ?? ($dbConfig['database'] ?? ''));

        if ($username === '' || $dbName === '') {
            throw new \RuntimeException('DB接続設定が不足しています。');
        }

        $tmpPath = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . sprintf('groupware_backup_%s_%d.sql', $timestamp, (int)$backupId);
        $mysqldump = $this->findMysqldumpBinary();
        if ($mysqldump === null) {
            $this->createDatabaseDumpWithPhp($dbConfig, $dbName, $tmpPath);
            return $tmpPath;
        }

        $cmd = sprintf(
            '%s --single-transaction --quick --routines --triggers --skip-lock-tables -h %s -P %d -u %s %s',
            escapeshellcmd($mysqldump),
            escapeshellarg($host),
            $port,
            escapeshellarg($username),
            escapeshellarg($dbName)
        );

        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['file', $tmpPath, 'w'],
            2 => ['pipe', 'w'],
        ];

        $env = $_ENV;
        if ($password !== '') {
            $env['MYSQL_PWD'] = $password;
        }

        $process = proc_open($cmd, $descriptorSpec, $pipes, $this->rootPath, $env);
        if (!is_resource($process)) {
            throw new \RuntimeException('mysqldump を実行できません。');
        }

        fclose($pipes[0]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);
        if ($exitCode !== 0) {
            @unlink($tmpPath);
            throw new \RuntimeException('DBダンプに失敗しました: ' . trim((string)$stderr));
        }

        if (!is_file($tmpPath) || filesize($tmpPath) === 0) {
            @unlink($tmpPath);
            throw new \RuntimeException('DBダンプ結果が空です。');
        }

        return $tmpPath;
    }

    private function findMysqldumpBinary()
    {
        $bin = trim((string)@shell_exec('command -v mysqldump 2>/dev/null'));
        if ($bin === '') {
            return null;
        }

        return $bin;
    }

    private function createDatabaseDumpWithPhp($dbConfig, $dbName, $tmpPath)
    {
        $host = (string)($dbConfig['host'] ?? 'localhost');
        $port = (int)($dbConfig['port'] ?? 3306);
        $username = (string)($dbConfig['username'] ?? '');
        $password = (string)($dbConfig['password'] ?? '');
        $charset = (string)($dbConfig['charset'] ?? 'utf8mb4');
        if (strpos($charset, '_') !== false) {
            $charset = explode('_', $charset)[0];
        }
        if ($charset === '') {
            $charset = 'utf8mb4';
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $host,
            $port,
            $dbName,
            $charset
        );

        $pdo = new \PDO($dsn, $username, $password, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        ]);

        $fp = fopen($tmpPath, 'wb');
        if ($fp === false) {
            throw new \RuntimeException('一時ダンプファイルを作成できません。');
        }

        fwrite($fp, "-- TeamSpace backup dump\n");
        fwrite($fp, "-- Generated at: " . date('c') . "\n\n");
        fwrite($fp, "SET NAMES {$charset};\n");
        fwrite($fp, "SET FOREIGN_KEY_CHECKS=0;\n\n");

        $tables = $pdo->query('SHOW FULL TABLES')->fetchAll(\PDO::FETCH_NUM);
        foreach ($tables as $tableRow) {
            $tableName = (string)($tableRow[0] ?? '');
            $tableType = strtoupper((string)($tableRow[1] ?? 'BASE TABLE'));
            if ($tableName === '') {
                continue;
            }

            if ($tableType === 'VIEW') {
                $viewInfo = $pdo->query('SHOW CREATE VIEW `' . str_replace('`', '``', $tableName) . '`')->fetch(\PDO::FETCH_ASSOC);
                if (!$viewInfo) {
                    continue;
                }
                $createSql = $viewInfo['Create View'] ?? array_values($viewInfo)[1] ?? '';
                if ($createSql === '') {
                    continue;
                }
                fwrite($fp, "DROP VIEW IF EXISTS `{$tableName}`;\n");
                fwrite($fp, $createSql . ";\n\n");
                continue;
            }

            $createInfo = $pdo->query('SHOW CREATE TABLE `' . str_replace('`', '``', $tableName) . '`')->fetch(\PDO::FETCH_ASSOC);
            if (!$createInfo) {
                continue;
            }
            $createSql = $createInfo['Create Table'] ?? array_values($createInfo)[1] ?? '';
            if ($createSql === '') {
                continue;
            }

            fwrite($fp, "DROP TABLE IF EXISTS `{$tableName}`;\n");
            fwrite($fp, $createSql . ";\n\n");

            $stmt = $pdo->query('SELECT * FROM `' . str_replace('`', '``', $tableName) . '`');
            $columns = [];
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                if ($columns === []) {
                    $columns = array_keys($row);
                }

                $values = [];
                foreach ($columns as $col) {
                    $value = $row[$col] ?? null;
                    if ($value === null) {
                        $values[] = 'NULL';
                    } elseif (is_bool($value)) {
                        $values[] = $value ? '1' : '0';
                    } else {
                        $values[] = $pdo->quote((string)$value);
                    }
                }

                $columnSql = '`' . implode('`,`', array_map(static function ($col) {
                    return str_replace('`', '``', $col);
                }, $columns)) . '`';

                fwrite(
                    $fp,
                    'INSERT INTO `' . str_replace('`', '``', $tableName) . '` (' . $columnSql . ') VALUES (' . implode(', ', $values) . ");\n"
                );
            }

            fwrite($fp, "\n");
        }

        fwrite($fp, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($fp);

        if (!is_file($tmpPath) || filesize($tmpPath) === 0) {
            @unlink($tmpPath);
            throw new \RuntimeException('DBダンプ結果が空です。');
        }
    }

    private function addDirectoryIfExists(\ZipArchive $zip, $sourceDir, $zipRoot)
    {
        if (!is_dir($sourceDir)) {
            return;
        }

        $sourceDir = rtrim($sourceDir, DIRECTORY_SEPARATOR);
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $itemPath = $item->getPathname();
            $relative = substr($itemPath, strlen($sourceDir) + 1);
            $entryName = $zipRoot . '/' . str_replace(DIRECTORY_SEPARATOR, '/', $relative);

            if ($item->isDir()) {
                $zip->addEmptyDir($entryName);
            } elseif ($item->isFile()) {
                $zip->addFile($itemPath, $entryName);
            }
        }
    }
}

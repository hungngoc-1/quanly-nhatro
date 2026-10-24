<?php

namespace App\Config;

use App\Helpers\CoreHelper;
use PDO;
use PDOException;

class Database
{
    private static ?Database $instance = null;
    private ?PDO $connection = null;

    private function __construct()
    {
        CoreHelper::loadEnv();
        $this->connect();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function getConnection(): PDO
    {
        return self::getInstance()->connection;
    }

    /**
     * Cho phép inject PDO kết nối giả lập khi chạy Unit Test
     */
    public static function setConnection(?PDO $pdo): void
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        self::$instance->connection = $pdo;
    }

    private function connect(): void
    {
        $host = CoreHelper::env('DB_HOST', '127.0.0.1');
        $port = CoreHelper::env('DB_PORT', '3306');
        $dbname = CoreHelper::env('DB_DATABASE', 'quanly_nhatro');
        $username = CoreHelper::env('DB_USERNAME', 'root');
        $password = CoreHelper::env('DB_PASSWORD', '');
        $charset = CoreHelper::env('DB_CHARSET', 'utf8mb4');

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";
            $this->connection = new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            // Mã lỗi 1049: Unknown database trên WAMP MySQL -> Tự động tạo DB và nạp database.sql
            if ((int) $e->getCode() === 1049 || str_contains($e->getMessage(), 'Unknown database')) {
                try {
                    $serverDsn = "mysql:host={$host};port={$port};charset={$charset}";
                    $pdoServer = new PDO($serverDsn, $username, $password, $options);
                    $this->importDatabaseSchema($pdoServer, $dbname);

                    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";
                    $this->connection = new PDO($dsn, $username, $password, $options);
                    return;
                } catch (PDOException $innerException) {
                    $this->renderConnectionError($innerException, $host, $port, $dbname, $username);
                }
            }

            $this->renderConnectionError($e, $host, $port, $dbname, $username);
        }
    }

    /**
     * Tự động tạo database và import file database.sql nếu chưa tồn tại trên WAMP
     */
    public function importDatabaseSchema(PDO $pdo, string $dbname): void
    {
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$dbname}`");

        $sqlFile = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'database.sql';
        if (file_exists($sqlFile)) {
            $sql = file_get_contents($sqlFile);
            if ($sql !== false) {
                $pdo->exec($sql);
            }
        }
    }

    private function renderConnectionError(PDOException $e, string $host, string $port, string $dbname, string $username): never
    {
        if (PHP_SAPI === 'cli') {
            throw $e;
        }

        http_response_code(500);
        echo '<!DOCTYPE html><html lang="vi"><head><meta charset="utf-8"><title>Lỗi kết nối Cơ sở dữ liệu WAMP</title>';
        echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"></head>';
        echo '<body class="bg-light d-flex align-items-center min-vh-100"><div class="container"><div class="card shadow-sm border-danger mx-auto" style="max-width:650px;">';
        echo '<div class="card-header bg-danger text-white fw-bold">Không thể kết nối MySQL trên WAMP Server</div>';
        echo '<div class="card-body"><p class="mb-2">Vui lòng kiểm tra biểu tượng <strong>Wampserver</strong> dưới khay hệ thống đã chuyển sang <strong>Màu Xanh Lá</strong> và dịch vụ MySQL đang chạy.</p>';
        echo '<ul class="small text-muted"><li>Host: <code>' . htmlspecialchars($host . ':' . $port) . '</code></li>';
        echo '<li>Database: <code>' . htmlspecialchars($dbname) . '</code></li>';
        echo '<li>User: <code>' . htmlspecialchars($username) . '</code></li></ul>';
        echo '<div class="alert alert-warning small mb-0"><strong>Chi tiết lỗi:</strong> ' . htmlspecialchars($e->getMessage()) . '</div>';
        echo '</div></div></div></body></html>';
        exit;
    }
}

<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $driver = strtolower((string) env('DB_DRIVER', 'sqlite'));

        try {
            self::$connection = $driver === 'mysql'
                ? self::mysqlConnection()
                : self::sqliteConnection();
        } catch (PDOException $exception) {
            throw new RuntimeException('Nao foi possivel conectar ao banco de dados: ' . $exception->getMessage(), 0, $exception);
        }

        self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        self::$connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        if ($driver === 'sqlite') {
            self::$connection->exec('PRAGMA foreign_keys = ON');
            self::$connection->exec('PRAGMA busy_timeout = 5000');
            self::$connection->exec('PRAGMA journal_mode = WAL');
        }

        return self::$connection;
    }

    private static function sqliteConnection(): PDO
    {
        $databasePath = base_path((string) env('DB_DATABASE', 'database/database.sqlite'));
        $directory = dirname($databasePath);

        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        if (!file_exists($databasePath)) {
            touch($databasePath);
        }

        return new PDO('sqlite:' . $databasePath);
    }

    private static function mysqlConnection(): PDO
    {
        $host = (string) env('DB_HOST', '127.0.0.1');
        $port = (string) env('DB_PORT', '3306');
        $database = (string) env('DB_NAME', 'site_professor');
        $user = (string) env('DB_USER', 'root');
        $password = (string) env('DB_PASS', '');

        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $database);

        return new PDO($dsn, $user, $password, [
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
}

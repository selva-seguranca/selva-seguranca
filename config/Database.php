<?php

namespace Config;

use PDO;
use PDOException;
use RuntimeException;

class Database {
    private static $instance = null;
    private static $envLoaded = false;
    private $connection;

    private function __construct() {
        self::loadEnvironment();

        $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
        $port = $_ENV['DB_PORT'] ?? '5432';
        $db   = $_ENV['DB_DATABASE'] ?? 'selva_seguranca';
        $user = $_ENV['DB_USERNAME'] ?? 'postgres';
        $pass = $_ENV['DB_PASSWORD'] ?? 'postgres';

        $dsnParts = [
            "host=$host",
            "port=$port",
            "dbname=$db",
        ];

        $sslmode = $_ENV['DB_SSLMODE'] ?? (str_contains($host, 'supabase.co') ? 'require' : '');
        if ($sslmode !== '') {
            $dsnParts[] = "sslmode=$sslmode";
        }

        $dsn = 'pgsql:' . implode(';', $dsnParts);

        try {
            $this->connection = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            throw new RuntimeException('Database connection error: ' . $e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    private static function loadEnvironment() {
        if (self::$envLoaded) {
            return;
        }

        $envFile = __DIR__ . '/../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);

                if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) {
                    continue;
                }

                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = self::normalizeEnvValue($value);

                $_ENV[$name] = $value;
                putenv("$name=$value");
            }
        }

        self::$envLoaded = true;
    }

    private static function normalizeEnvValue($value) {
        $value = trim($value);
        $firstChar = substr($value, 0, 1);
        $lastChar = substr($value, -1);

        if (($firstChar === '"' && $lastChar === '"') || ($firstChar === "'" && $lastChar === "'")) {
            return substr($value, 1, -1);
        }

        return $value;
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance->connection;
    }
}

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

        $host = Env::get('DB_HOST', '127.0.0.1');
        $port = Env::get('DB_PORT', '5432');
        $db   = Env::get('DB_DATABASE', 'selva_seguranca');
        $user = Env::get('DB_USERNAME', 'postgres');
        $pass = Env::get('DB_PASSWORD', 'postgres');

        $dsnParts = [
            "host=$host",
            "port=$port",
            "dbname=$db",
        ];

        $sslmode = Env::get(
            'DB_SSLMODE',
            stripos((string) $host, 'supabase') !== false ? 'require' : ''
        );
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

        Env::load();

        self::$envLoaded = true;
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance->connection;
    }
}

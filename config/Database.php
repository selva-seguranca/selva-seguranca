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
        $config = self::resolveConnectionConfig();
        self::validateConnectionConfig($config);

        try {
            $this->connection = new PDO($config['dsn'], $config['user'], $config['pass'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => $config['emulate_prepares'],
            ]);
        } catch (PDOException $e) {
            error_log(sprintf(
                '[Database] connection failed host=%s port=%s db=%s message=%s',
                $config['host'],
                $config['port'],
                $config['db'],
                $e->getMessage()
            ));
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

    private static function resolveConnectionConfig() {
        $databaseUrl = trim((string) Env::get(
            'DATABASE_URL',
            Env::get('POSTGRES_URL', Env::get('SUPABASE_DB_URL', ''))
        ));

        $config = $databaseUrl !== ''
            ? self::parseDatabaseUrl($databaseUrl)
            : self::buildConfigFromDiscreteVariables();

        $config = self::normalizeSupabasePoolerConfig($config);
        $config['emulate_prepares'] = self::shouldEmulatePrepares($config);

        return $config;
    }

    private static function buildConfigFromDiscreteVariables() {
        $host = Env::get('DB_HOST', '127.0.0.1');
        $port = Env::get('DB_PORT', '5432');
        $db = Env::get('DB_DATABASE', 'selva_seguranca');
        $user = Env::get('DB_USERNAME', 'postgres');
        $pass = Env::get('DB_PASSWORD', 'postgres');
        $sslmode = Env::get(
            'DB_SSLMODE',
            stripos((string) $host, 'supabase') !== false ? 'require' : ''
        );

        return [
            'host' => $host,
            'port' => $port,
            'db' => $db,
            'user' => $user,
            'pass' => $pass,
            'sslmode' => $sslmode,
            'dsn' => self::buildDsn($host, $port, $db, $sslmode),
        ];
    }

    private static function parseDatabaseUrl($databaseUrl) {
        $parts = parse_url($databaseUrl);
        if ($parts === false || empty($parts['host'])) {
            throw new RuntimeException('DATABASE_URL invalida.');
        }

        parse_str($parts['query'] ?? '', $query);

        $host = $parts['host'];
        $port = isset($parts['port']) ? (string) $parts['port'] : '5432';
        $db = ltrim((string) ($parts['path'] ?? '/postgres'), '/');
        $user = urldecode((string) ($parts['user'] ?? 'postgres'));
        $pass = urldecode((string) ($parts['pass'] ?? ''));
        $sslmode = trim((string) ($query['sslmode'] ?? (stripos((string) $host, 'supabase') !== false ? 'require' : '')));

        return [
            'host' => $host,
            'port' => $port,
            'db' => $db !== '' ? $db : 'postgres',
            'user' => $user,
            'pass' => $pass,
            'sslmode' => $sslmode,
            'dsn' => self::buildDsn($host, $port, $db !== '' ? $db : 'postgres', $sslmode),
        ];
    }

    private static function buildDsn($host, $port, $db, $sslmode) {
        $dsnParts = [
            "host=$host",
            "port=$port",
            "dbname=$db",
        ];

        if ($sslmode !== '') {
            $dsnParts[] = "sslmode=$sslmode";
        }

        return 'pgsql:' . implode(';', $dsnParts);
    }

    private static function shouldEmulatePrepares($config) {
        $configured = strtolower(trim((string) Env::get('DB_EMULATE_PREPARES', '')));
        if ($configured !== '') {
            return in_array($configured, ['1', 'true', 'yes', 'on'], true);
        }

        return (string) $config['port'] === '6543';
    }

    private static function normalizeSupabasePoolerConfig($config) {
        $host = trim((string) ($config['host'] ?? ''));
        $user = trim((string) ($config['user'] ?? ''));

        if (!preg_match('/\.pooler\.supabase\.com$/i', $host)) {
            return $config;
        }

        if ($user !== 'postgres') {
            return $config;
        }

        $projectRef = self::inferSupabaseProjectRef($host);
        if ($projectRef === '') {
            return $config;
        }

        $config['user'] = 'postgres.' . $projectRef;
        $config['dsn'] = self::buildDsn($config['host'], $config['port'], $config['db'], $config['sslmode']);

        return $config;
    }

    private static function inferSupabaseProjectRef($host) {
        $candidates = [
            trim((string) Env::get('SUPABASE_URL', '')),
            trim((string) Env::get('NEXT_PUBLIC_SUPABASE_URL', '')),
            trim((string) Env::get('DB_HOST', '')),
        ];

        foreach ($candidates as $candidate) {
            if ($candidate === '') {
                continue;
            }

            if (preg_match('#https://([a-z0-9-]+)\.supabase\.co/?#i', $candidate, $matches)) {
                return $matches[1];
            }

            if (preg_match('/^db\.([a-z0-9-]+)\.supabase\.co$/i', $candidate, $matches)) {
                return $matches[1];
            }
        }

        if (preg_match('/^postgres\.([a-z0-9-]+)$/i', trim((string) Env::get('DB_USERNAME', '')), $matches)) {
            return $matches[1];
        }

        if (preg_match('/^postgres\.([a-z0-9-]+):/i', trim((string) Env::get('DATABASE_URL', '')), $matches)) {
            return $matches[1];
        }

        return '';
    }

    private static function validateConnectionConfig($config) {
        $port = trim((string) ($config['port'] ?? ''));
        if ($port === '' || !ctype_digit($port) || (int) $port < 1 || (int) $port > 65535) {
            throw new RuntimeException('DB_PORT invalido. Use 5432 para conexao direta local ou 6543 para o Transaction Pooler do Supabase.');
        }

        $host = trim((string) ($config['host'] ?? ''));
        if (
            Env::isTruthy('VERCEL')
            && preg_match('/^db\.[^.]+\.supabase\.co$/i', $host)
            && $port === '6543'
        ) {
            throw new RuntimeException(
                'Configuracao invalida na Vercel: db.<project-ref>.supabase.co:6543 nao funciona ai. ' .
                'Use o host do Transaction Pooler em pooler.supabase.com ou cole a DATABASE_URL exata do botao Connect do Supabase.'
            );
        }
    }

    public static function getDebugInfo() {
        self::loadEnvironment();

        $config = self::resolveConnectionConfig();

        return [
            'connection_source' => trim((string) Env::get(
                'DATABASE_URL',
                Env::get('POSTGRES_URL', Env::get('SUPABASE_DB_URL', ''))
            )) !== '' ? 'database_url' : 'discrete_variables',
            'host' => $config['host'],
            'port' => $config['port'],
            'db' => $config['db'],
            'user' => $config['user'],
            'sslmode' => $config['sslmode'],
            'emulate_prepares' => $config['emulate_prepares'],
            'database_url_present' => trim((string) Env::get(
                'DATABASE_URL',
                Env::get('POSTGRES_URL', Env::get('SUPABASE_DB_URL', ''))
            )) !== '',
            'vercel' => Env::isTruthy('VERCEL'),
            'app_env' => Env::get('APP_ENV', ''),
            'pdo_loaded' => extension_loaded('pdo'),
            'pdo_pgsql_loaded' => extension_loaded('pdo_pgsql'),
            'pgsql_loaded' => extension_loaded('pgsql'),
        ];
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance->connection;
    }
}

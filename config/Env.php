<?php

namespace Config;

class Env {
    private static $loaded = false;

    public static function load() {
        if (self::$loaded) {
            return;
        }

        $envFile = dirname(__DIR__) . '/.env';
        if (!is_file($envFile)) {
            self::$loaded = true;
            return;
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            self::$loaded = true;
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) {
                continue;
            }

            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);

            if ($name === '' || self::existsInRuntime($name)) {
                continue;
            }

            $normalizedValue = self::normalizeValue($value);
            $_ENV[$name] = $normalizedValue;
            $_SERVER[$name] = $normalizedValue;
            putenv($name . '=' . $normalizedValue);
        }

        self::$loaded = true;
    }

    public static function get($name, $default = null) {
        self::load();

        if (array_key_exists($name, $_ENV)) {
            return $_ENV[$name];
        }

        $runtimeValue = getenv($name);
        if ($runtimeValue !== false) {
            return $runtimeValue;
        }

        if (array_key_exists($name, $_SERVER)) {
            return $_SERVER[$name];
        }

        return $default;
    }

    public static function isTruthy($name) {
        $value = self::get($name);

        if ($value === null) {
            return false;
        }

        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }

    private static function existsInRuntime($name) {
        return array_key_exists($name, $_ENV)
            || array_key_exists($name, $_SERVER)
            || getenv($name) !== false;
    }

    private static function normalizeValue($value) {
        $value = trim($value);
        $firstChar = substr($value, 0, 1);
        $lastChar = substr($value, -1);

        if (($firstChar === '"' && $lastChar === '"') || ($firstChar === "'" && $lastChar === "'")) {
            return substr($value, 1, -1);
        }

        return $value;
    }
}

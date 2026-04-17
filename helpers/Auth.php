<?php

namespace Helpers;

class Auth {
    public static function check() {
        return isset($_SESSION['user_id']);
    }

    public static function requireLogin($redirect = '/login') {
        if (self::check()) {
            return;
        }

        self::redirect($redirect);
    }

    public static function currentProfile() {
        return trim((string) ($_SESSION['user_perfil'] ?? ''));
    }

    public static function hasAnyProfile(array $profiles) {
        return in_array(self::currentProfile(), $profiles, true);
    }

    public static function requireAnyProfile(array $profiles, $redirect = null) {
        self::requireLogin();

        if (self::hasAnyProfile($profiles)) {
            return;
        }

        self::redirect($redirect ?? self::defaultRedirectPath());
    }

    private static function defaultRedirectPath() {
        return self::currentProfile() === 'Vigilante' ? '/vigilante/ronda' : '/';
    }

    private static function redirect($path) {
        header('Location: ' . $path);
        exit;
    }
}

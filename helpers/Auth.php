<?php

namespace Helpers;

use Config\Database;
use PDO;
use Throwable;

class Auth {
    private const REMEMBER_COOKIE = 'selva_remember';
    private const REMEMBER_DAYS = 30;

    private static $rememberSchemaEnsured = false;

    public static function bootstrap() {
        if (self::check()) {
            self::ensureRememberTokenForActiveSession();
            return;
        }

        self::restoreRememberedSession();
    }

    public static function check() {
        return !empty($_SESSION['user_id']);
    }

    public static function loginUser(array $user) {
        self::deleteCurrentRememberToken();
        self::clearRememberCookie();
        self::startUserSession($user);
        self::persistRememberToken((string) $user['id']);
    }

    public static function logoutUser() {
        self::deleteCurrentRememberToken();
        self::clearRememberCookie();
        self::clearSession();
    }

    public static function requireLogin() {
        if (self::check()) {
            return;
        }

        self::redirect('/login');
    }

    public static function currentProfile() {
        return $_SESSION['user_perfil'] ?? null;
    }

    public static function hasAnyProfile(array $profiles) {
        if (!self::check()) {
            return false;
        }

        return in_array((string) self::currentProfile(), $profiles, true);
    }

    public static function requireAnyProfile(array $profiles) {
        if (!self::check()) {
            self::redirect('/login');
        }

        if (!self::hasAnyProfile($profiles)) {
            self::redirect(self::defaultRedirectPath());
        }
    }

    public static function defaultRedirectPath() {
        return self::currentProfile() === 'Vigilante' ? '/vigilante/ronda' : '/';
    }

    public static function redirect($path) {
        header('Location: ' . $path);
        exit;
    }

    private static function restoreRememberedSession() {
        $rememberCookie = self::parseRememberCookie($_COOKIE[self::REMEMBER_COOKIE] ?? null);
        if ($rememberCookie === null) {
            return;
        }

        try {
            $db = Database::getInstance();
            self::ensureRememberTokenSchema($db);

            $stmt = $db->prepare(
                "SELECT apl.usuario_id AS id, apl.token_hash, u.nome, p.nome AS perfil
                 FROM auth_persistent_logins apl
                 JOIN usuarios u ON u.id = apl.usuario_id
                 JOIN perfis p ON p.id = u.perfil_id
                 WHERE apl.selector = :selector
                   AND apl.expires_at > CURRENT_TIMESTAMP
                   AND u.ativo = true
                 LIMIT 1"
            );
            $stmt->bindValue(':selector', $rememberCookie['selector']);
            $stmt->execute();
            $rememberedUser = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$rememberedUser) {
                self::clearRememberCookie();
                return;
            }

            $validatorHash = hash('sha256', $rememberCookie['validator']);
            if (!hash_equals((string) $rememberedUser['token_hash'], $validatorHash)) {
                self::deleteRememberTokenBySelector($rememberCookie['selector']);
                self::clearRememberCookie();
                return;
            }

            self::startUserSession($rememberedUser);
            self::rotateRememberToken($rememberCookie['selector'], (string) $rememberedUser['id']);
        } catch (Throwable $e) {
            error_log('[Auth::restoreRememberedSession] ' . $e->getMessage());
        }
    }

    private static function ensureRememberTokenForActiveSession() {
        $rememberCookie = self::parseRememberCookie($_COOKIE[self::REMEMBER_COOKIE] ?? null);
        if ($rememberCookie !== null) {
            return;
        }

        if (!empty($_COOKIE[self::REMEMBER_COOKIE])) {
            self::clearRememberCookie();
        }

        self::persistRememberToken((string) ($_SESSION['user_id'] ?? ''));
    }

    private static function startUserSession(array $user) {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (string) ($user['id'] ?? '');
        $_SESSION['user_nome'] = (string) ($user['nome'] ?? '');
        $_SESSION['user_perfil'] = (string) ($user['perfil'] ?? '');
    }

    private static function clearSession() {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            setcookie(session_name(), '', self::cookieOptions(time() - 3600));
        }

        session_destroy();
    }

    private static function rotateRememberToken($selector, $userId) {
        self::deleteRememberTokenBySelector($selector);
        self::persistRememberToken($userId);
    }

    private static function persistRememberToken($userId) {
        if ($userId === '') {
            return;
        }

        try {
            $db = Database::getInstance();
            self::ensureRememberTokenSchema($db);

            $selector = bin2hex(random_bytes(9));
            $validator = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', time() + (self::REMEMBER_DAYS * 86400));

            $stmt = $db->prepare(
                "INSERT INTO auth_persistent_logins (
                    usuario_id,
                    selector,
                    token_hash,
                    expires_at
                ) VALUES (
                    :usuario_id,
                    :selector,
                    :token_hash,
                    :expires_at
                )"
            );
            $stmt->bindValue(':usuario_id', $userId);
            $stmt->bindValue(':selector', $selector);
            $stmt->bindValue(':token_hash', hash('sha256', $validator));
            $stmt->bindValue(':expires_at', $expiresAt);
            $stmt->execute();

            setcookie(
                self::REMEMBER_COOKIE,
                $selector . ':' . $validator,
                self::cookieOptions(time() + (self::REMEMBER_DAYS * 86400))
            );
            $_COOKIE[self::REMEMBER_COOKIE] = $selector . ':' . $validator;
        } catch (Throwable $e) {
            error_log('[Auth::persistRememberToken] ' . $e->getMessage());
        }
    }

    private static function deleteCurrentRememberToken() {
        $rememberCookie = self::parseRememberCookie($_COOKIE[self::REMEMBER_COOKIE] ?? null);
        if ($rememberCookie === null) {
            return;
        }

        try {
            $db = Database::getInstance();
            self::ensureRememberTokenSchema($db);
            self::deleteRememberTokenBySelector($rememberCookie['selector'], $db);
        } catch (Throwable $e) {
            error_log('[Auth::deleteCurrentRememberToken] ' . $e->getMessage());
        }
    }

    private static function deleteRememberTokenBySelector($selector, ?PDO $db = null) {
        $db = $db ?: Database::getInstance();
        self::ensureRememberTokenSchema($db);

        $stmt = $db->prepare("DELETE FROM auth_persistent_logins WHERE selector = :selector");
        $stmt->bindValue(':selector', $selector);
        $stmt->execute();
    }

    private static function clearRememberCookie() {
        setcookie(self::REMEMBER_COOKIE, '', self::cookieOptions(time() - 3600));
        unset($_COOKIE[self::REMEMBER_COOKIE]);
    }

    private static function parseRememberCookie($cookieValue) {
        if (!is_string($cookieValue) || $cookieValue === '' || strpos($cookieValue, ':') === false) {
            return null;
        }

        [$selector, $validator] = explode(':', $cookieValue, 2);
        if ($selector === '' || $validator === '') {
            return null;
        }

        return [
            'selector' => $selector,
            'validator' => $validator,
        ];
    }

    private static function cookieOptions($expires) {
        return [
            'expires' => $expires,
            'path' => '/',
            'secure' => self::isHttpsRequest(),
            'httponly' => true,
            'samesite' => 'Lax',
        ];
    }

    private static function isHttpsRequest() {
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return true;
        }

        if ((string) ($_SERVER['SERVER_PORT'] ?? '') === '443') {
            return true;
        }

        return strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
    }

    private static function ensureRememberTokenSchema(PDO $db) {
        if (self::$rememberSchemaEnsured) {
            return;
        }

        $db->exec(
            "CREATE TABLE IF NOT EXISTS auth_persistent_logins (
                id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                usuario_id UUID NOT NULL REFERENCES usuarios(id) ON DELETE CASCADE,
                selector VARCHAR(32) NOT NULL UNIQUE,
                token_hash VARCHAR(64) NOT NULL,
                expires_at TIMESTAMP NOT NULL,
                criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                ultimo_uso_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )"
        );

        $db->exec("ALTER TABLE auth_persistent_logins ADD COLUMN IF NOT EXISTS ultimo_uso_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_auth_persistent_logins_usuario_id ON auth_persistent_logins (usuario_id)");
        $db->exec("DELETE FROM auth_persistent_logins WHERE expires_at <= CURRENT_TIMESTAMP");

        self::$rememberSchemaEnsured = true;
    }
}

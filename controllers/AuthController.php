<?php

namespace Controllers;

use Config\Database;
use Config\Env;
use Helpers\Auth;
use Helpers\View;
use Throwable;

class AuthController {
    public function showLoginForm() {
        if (Auth::check()) {
            header('Location: ' . Auth::defaultRedirectPath());
            exit;
        }

        $error = $_SESSION['login_error'] ?? null;
        unset($_SESSION['login_error']);

        View::render('auth/login', ['error' => $error]);
    }

    public function login() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /login");
            exit;
        }

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email === '' || $password === '') {
            $_SESSION['login_error'] = "Preencha todos os campos.";
            header("Location: /login");
            exit;
        }

        try {
            $db = Database::getInstance();
            $stmt = $db->prepare(
                "SELECT u.id, u.nome, u.senha_hash, p.nome as perfil
                 FROM usuarios u
                 JOIN perfis p ON u.perfil_id = p.id
                 WHERE u.email = :email AND u.ativo = true"
            );
            $stmt->bindValue(':email', $email);
            $stmt->execute();
            $user = $stmt->fetch();
        } catch (Throwable $e) {
            error_log('[AuthController@login] ' . $e->getMessage());
            $_SESSION['login_error'] = $this->getDatabaseConnectionErrorMessage($e);
            header("Location: /login");
            exit;
        }

        if ($user && password_verify($password, $user['senha_hash'])) {
            Auth::loginUser($user);

            header('Location: ' . Auth::defaultRedirectPath());
            exit;
        }

        $_SESSION['login_error'] = "E-mail ou senha inválidos.";
        header("Location: /login");
        exit;
    }

    public function logout() {
        Auth::logoutUser();
        header("Location: /login");
        exit;
    }

    private function getDatabaseConnectionErrorMessage(Throwable $e) {
        if ($this->shouldExposeDatabaseError()) {
            return 'Falha na conexão com o banco: ' . $e->getMessage();
        }

        if ($this->isLikelyInvalidSupabaseVercelConfiguration()) {
            return 'Na Vercel, use a connection string do Supabase Transaction Pooler (porta 6543) ou a DATABASE_URL do botão Connect.';
        }

        return 'Não foi possível conectar ao banco de dados. Revise a configuração do Supabase.';
    }

    private function isLikelyInvalidSupabaseVercelConfiguration() {
        if (!Env::isTruthy('VERCEL')) {
            return false;
        }

        $databaseUrl = trim((string) Env::get('DATABASE_URL', Env::get('POSTGRES_URL', '')));
        if ($databaseUrl !== '') {
            return (bool) preg_match('/@db\.[^.]+\.supabase\.co:5432(\/|$)/i', $databaseUrl);
        }

        $host = trim((string) Env::get('DB_HOST', ''));
        $port = trim((string) Env::get('DB_PORT', '5432'));

        return (bool) preg_match('/^db\.[^.]+\.supabase\.co$/i', $host) && $port === '5432';
    }

    private function shouldExposeDatabaseError() {
        return Env::isTruthy('APP_DEBUG') || Env::isTruthy('DB_DEBUG_MESSAGE');
    }
}

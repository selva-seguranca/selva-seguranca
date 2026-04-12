<?php

namespace Controllers;

use Config\Database;
use Helpers\View;
use Throwable;

class AuthController {
    public function showLoginForm() {
        if (isset($_SESSION['user_id'])) {
            header("Location: /");
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
            $_SESSION['login_error'] = "Nao foi possivel conectar ao banco de dados. Revise a configuracao do Supabase.";
            header("Location: /login");
            exit;
        }

        if ($user && password_verify($password, $user['senha_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_nome'] = $user['nome'];
            $_SESSION['user_perfil'] = $user['perfil'];

            header("Location: /");
            exit;
        }

        $_SESSION['login_error'] = "E-mail ou senha invalidos.";
        header("Location: /login");
        exit;
    }

    public function logout() {
        session_destroy();
        header("Location: /login");
        exit;
    }
}

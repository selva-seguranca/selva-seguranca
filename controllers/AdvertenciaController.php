<?php

namespace Controllers;

use Helpers\Auth;
use Helpers\View;
use Models\PortalRepository;
use Throwable;

class AdvertenciaController {
    public function index() {
        Auth::requireAnyProfile(['Coordenador Geral', 'Administrador']);

        $advertenciasControl = [
            'vigilantes' => [],
            'ocorrencias' => [],
            'advertencias' => [],
            'resumo' => [
                'total' => 0,
                'mes_atual' => 0,
                'graves' => 0,
                'evolucao' => 0,
            ],
        ];
        $flash = $this->consumeFlash();
        $dbWarning = null;

        try {
            $repository = new PortalRepository();
            $advertenciasControl = $repository->getRhWarningControlData();
        } catch (Throwable $e) {
            $dbWarning = 'Não foi possível carregar o controle de advertências direto do banco.';
        }

        View::render('advertencias/index', [
            'pageTitle' => 'Controle de Advertências',
            'advertenciasControl' => $advertenciasControl,
            'advertenciaSuccess' => $flash['success'],
            'advertenciaError' => $flash['error'],
            'dbWarning' => $dbWarning,
        ]);
    }

    public function store() {
        Auth::requireAnyProfile(['Coordenador Geral', 'Administrador']);

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            header('Location: /advertencias');
            exit;
        }

        try {
            $repository = new PortalRepository();
            $repository->createCollaboratorWarning(
                $_POST,
                $_SESSION['user_id'] ?? null,
                $_SESSION['user_nome'] ?? ''
            );

            $_SESSION['advertencia_success'] = 'ADVERTÊNCIA REGISTRADA COM SUCESSO!';
        } catch (Throwable $e) {
            $_SESSION['advertencia_error'] = $e->getMessage();
        }

        header('Location: /advertencias');
        exit;
    }

    private function consumeFlash() {
        $success = $_SESSION['advertencia_success'] ?? null;
        $error = $_SESSION['advertencia_error'] ?? null;

        unset($_SESSION['advertencia_success'], $_SESSION['advertencia_error']);

        return [
            'success' => $success,
            'error' => $error,
        ];
    }
}

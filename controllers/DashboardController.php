<?php

namespace Controllers;

use Helpers\View;
use Models\PortalRepository;
use Throwable;

class DashboardController {
    public function index() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: /login");
            exit;
        }

        $perfil = $_SESSION['user_perfil'] ?? '';
        if ($perfil === 'Vigilante') {
            header("Location: /vigilante/ronda");
            exit;
        }

        $stats = [
            'vigilantes_em_campo' => 0,
            'veiculos_em_ronda' => 0,
            'manutencoes_criticas' => 0,
            'ocorrencias_hoje' => 0,
        ];
        $rondasAtivas = [];
        $ocorrenciasRecentes = [];
        $dbWarning = null;

        try {
            $repository = new PortalRepository();
            $stats = $repository->getDashboardStats();
            $rondasAtivas = $repository->getActiveRounds();
            $ocorrenciasRecentes = $repository->getRecentOccurrences();
        } catch (Throwable $e) {
            $dbWarning = 'Nao foi possivel carregar os indicadores em tempo real do banco.';
        }

        View::render('dashboard/index', [
            'perfil' => $perfil,
            'nome' => $_SESSION['user_nome'],
            'pageTitle' => 'Dashboard',
            'stats' => $stats,
            'rondasAtivas' => $rondasAtivas,
            'ocorrenciasRecentes' => $ocorrenciasRecentes,
            'dbWarning' => $dbWarning,
        ]);
    }
}

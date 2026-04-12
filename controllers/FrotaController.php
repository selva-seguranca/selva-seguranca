<?php

namespace Controllers;

use Helpers\View;
use Models\PortalRepository;
use Throwable;

class FrotaController {
    public function index() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: /login");
            exit;
        }

        $veiculos = [];
        $dbWarning = null;

        try {
            $repository = new PortalRepository();
            $veiculos = $repository->getVehicles();
        } catch (Throwable $e) {
            $dbWarning = 'Nao foi possivel carregar a frota direto do banco.';
        }

        View::render('frota/index', [
            'pageTitle' => 'Gestao de Frota',
            'veiculos' => $veiculos,
            'dbWarning' => $dbWarning,
        ]);
    }
}

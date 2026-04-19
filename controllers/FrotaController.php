<?php

namespace Controllers;

use Helpers\Auth;
use Helpers\View;
use Models\PortalRepository;
use Throwable;

class FrotaController {
    public function index() {
        Auth::requireAnyProfile(['Coordenador Geral', 'Administrador']);

        $veiculos = [];
        $dbWarning = null;

        try {
            $repository = new PortalRepository();
            $veiculos = $repository->getVehicles();
        } catch (Throwable $e) {
            $dbWarning = 'Não foi possível carregar a frota direto do banco.';
        }

        View::render('frota/index', [
            'pageTitle' => 'Gestão de Frota',
            'veiculos' => $veiculos,
            'dbWarning' => $dbWarning,
        ]);
    }
}

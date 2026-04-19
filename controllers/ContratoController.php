<?php

namespace Controllers;

use Helpers\Auth;
use Helpers\View;
use Models\PortalRepository;
use Throwable;

class ContratoController {
    public function index() {
        Auth::requireAnyProfile(['Coordenador Geral', 'Administrador']);

        $contratos = [];
        $dbWarning = null;

        try {
            $repository = new PortalRepository();
            $contratos = $repository->getContracts();
        } catch (Throwable $e) {
            $dbWarning = 'Não foi possível carregar os contratos direto do banco.';
        }

        View::render('contratos/index', [
            'pageTitle' => 'Gestão de Contratos',
            'contratos' => $contratos,
            'dbWarning' => $dbWarning,
        ]);
    }
}

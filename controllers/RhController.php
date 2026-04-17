<?php

namespace Controllers;

use Helpers\Auth;
use Helpers\View;
use Models\PortalRepository;
use Throwable;

class RhController {
    public function index() {
        Auth::requireAnyProfile(['Coordenador Geral', 'Administrador']);

        $colaboradores = [];
        $kpis = [
            'total_ativos' => 0,
            'em_ferias' => 0,
            'advertencias_recentes' => 0,
        ];
        $dbWarning = null;

        try {
            $repository = new PortalRepository();
            $colaboradores = $repository->getCollaborators();
            $kpis = $repository->getRhKpis();
        } catch (Throwable $e) {
            $dbWarning = 'Nao foi possivel carregar os dados de RH direto do banco.';
        }

        View::render('rh/index', [
            'pageTitle' => 'Recursos Humanos',
            'colaboradores' => $colaboradores,
            'kpis' => $kpis,
            'dbWarning' => $dbWarning,
        ]);
    }
}

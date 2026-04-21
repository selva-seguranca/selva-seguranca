<?php

namespace Controllers;

use Helpers\Auth;
use Helpers\View;
use Models\PortalRepository;
use Throwable;

class ReciclagemController {
    public function index() {
        Auth::requireAnyProfile(['Coordenador Geral', 'Administrador']);

        $vigilantes = [];
        $resumo = [
            'total' => 0,
            'em_alerta' => 0,
            'vencidas' => 0,
            'em_dia' => 0,
            'sem_data' => 0,
        ];
        $dbWarning = null;

        try {
            $repository = new PortalRepository();
            $vigilantes = $repository->getVigilanteRecyclingCards();
            $resumo = $this->buildSummary($vigilantes);
        } catch (Throwable $e) {
            $dbWarning = 'Não foi possível carregar os dados de reciclagem direto do banco.';
        }

        View::render('reciclagem/index', [
            'pageTitle' => 'Reciclagem',
            'vigilantes' => $vigilantes,
            'resumo' => $resumo,
            'dbWarning' => $dbWarning,
        ]);
    }

    private function buildSummary(array $vigilantes) {
        $summary = [
            'total' => count($vigilantes),
            'em_alerta' => 0,
            'vencidas' => 0,
            'em_dia' => 0,
            'sem_data' => 0,
        ];

        foreach ($vigilantes as $vigilante) {
            $status = $vigilante['status_reciclagem'] ?? 'sem_data';

            if ($status === 'alerta') {
                $summary['em_alerta']++;
            } elseif ($status === 'vencida') {
                $summary['vencidas']++;
            } elseif ($status === 'em_dia') {
                $summary['em_dia']++;
            } else {
                $summary['sem_data']++;
            }
        }

        return $summary;
    }
}

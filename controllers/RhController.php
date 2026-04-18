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
        $modulosRh = $this->buildRhModules([]);
        $kpis = [
            'total_ativos' => 0,
            'em_ferias' => 0,
            'advertencias_recentes' => 0,
        ];
        $dbWarning = null;

        try {
            $repository = new PortalRepository();
            $colaboradores = $repository->getCollaborators();
            $modulosRh = $this->buildRhModules($colaboradores);
            $kpis = $repository->getRhKpis();
        } catch (Throwable $e) {
            $dbWarning = 'Nao foi possivel carregar os dados de RH direto do banco.';
        }

        View::render('rh/index', [
            'pageTitle' => 'Recursos Humanos',
            'colaboradores' => $colaboradores,
            'modulosRh' => $modulosRh,
            'kpis' => $kpis,
            'dbWarning' => $dbWarning,
        ]);
    }

    private function buildRhModules(array $colaboradores) {
        $modulos = [
            'seguranca_privada' => [
                'slug' => 'seguranca_privada',
                'title' => 'COLABORADORES SELVA SEGURANCA PRIVADA',
                'subtitle' => 'Base administrativa, operacional e tecnica da operacao principal.',
                'roles' => ['Administrativo', 'Vigilante', 'Tecnico'],
                'colaboradores' => [],
                'role_counts' => [
                    'Administrativo' => 0,
                    'Vigilante' => 0,
                    'Tecnico' => 0,
                ],
            ],
            'servicos_terceirizacoes' => [
                'slug' => 'servicos_terceirizacoes',
                'title' => 'COLABORADORES SELVA SERVICOS E TERCEIRIZACOES',
                'subtitle' => 'Equipe dedicada a postos de apoio, portaria e servicos terceirizados.',
                'roles' => ['Porteiro', 'Vigitante'],
                'colaboradores' => [],
                'role_counts' => [
                    'Porteiro' => 0,
                    'Vigitante' => 0,
                ],
            ],
        ];

        foreach ($colaboradores as $colaborador) {
            $moduleKey = $this->resolveRhModuleKey($colaborador);
            $modulos[$moduleKey]['colaboradores'][] = $colaborador;

            foreach ($modulos[$moduleKey]['roles'] as $role) {
                if ($this->matchesRhRole($colaborador, $role)) {
                    $modulos[$moduleKey]['role_counts'][$role]++;
                }
            }
        }

        return array_values($modulos);
    }

    private function resolveRhModuleKey(array $colaborador) {
        $haystack = $this->normalizeRhText(
            ($colaborador['cargo'] ?? '') . ' ' . ($colaborador['departamento'] ?? '')
        );

        if (
            strpos($haystack, 'porteiro') !== false
            || strpos($haystack, 'vigitante') !== false
            || (
                strpos($haystack, 'vigilante') !== false
                && (
                    strpos($haystack, 'terceir') !== false
                    || strpos($haystack, 'portaria') !== false
                    || strpos($haystack, 'servico') !== false
                )
            )
        ) {
            return 'servicos_terceirizacoes';
        }

        return 'seguranca_privada';
    }

    private function matchesRhRole(array $colaborador, $role) {
        $haystack = $this->normalizeRhText(
            ($colaborador['cargo'] ?? '') . ' ' . ($colaborador['departamento'] ?? '')
        );
        $role = $this->normalizeRhText($role);

        if ($role === 'administrativo') {
            return strpos($haystack, 'administrativo') !== false;
        }

        if ($role === 'tecnico') {
            return strpos($haystack, 'tecnico') !== false;
        }

        if ($role === 'vigilante') {
            return strpos($haystack, 'vigilante') !== false;
        }

        if ($role === 'porteiro') {
            return strpos($haystack, 'porteiro') !== false;
        }

        if ($role === 'vigitante') {
            return strpos($haystack, 'vigitante') !== false
                || (
                    strpos($haystack, 'vigilante') !== false
                    && (
                        strpos($haystack, 'terceir') !== false
                        || strpos($haystack, 'portaria') !== false
                        || strpos($haystack, 'servico') !== false
                    )
                );
        }

        return false;
    }

    private function normalizeRhText($value) {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        if (function_exists('iconv')) {
            $transliterated = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);

            if ($transliterated !== false) {
                $value = $transliterated;
            }
        }

        return strtolower($value);
    }
}

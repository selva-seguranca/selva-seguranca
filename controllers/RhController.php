<?php

namespace Controllers;

use Helpers\Auth;
use Helpers\MediaStorage;
use Helpers\View;
use Models\PortalRepository;
use Throwable;

class RhController {
    public function index() {
        Auth::requireAnyProfile(['Coordenador Geral', 'Administrador']);

        $colaboradores = [];
        $modulosRh = $this->buildRhModules([]);
        $createModalState = $this->consumeCreateModalState();
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
            'formError' => $createModalState['formError'],
            'successMessage' => $createModalState['successMessage'],
            'accessInfo' => $createModalState['accessInfo'],
            'old' => $createModalState['old'],
            'isCreateModalOpen' => $createModalState['isOpen'],
            'kpis' => $kpis,
            'dbWarning' => $dbWarning,
        ]);
    }

    public function create() {
        Auth::requireAnyProfile(['Coordenador Geral', 'Administrador']);
        header('Location: /rh?modal=novo-colaborador');
        exit;
    }

    public function store() {
        Auth::requireAnyProfile(['Coordenador Geral', 'Administrador']);

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            header('Location: /rh/colaboradores/novo');
            exit;
        }

        $_SESSION['rh_form_old'] = $this->collectOldFormData($_POST);
        $storedFiles = [];

        try {
            $photo = $this->storeOptionalFile($_FILES['foto_colaborador'] ?? null, 'colaboradores/fotos');
            if ($photo === null) {
                throw new \RuntimeException('Selecione a foto do colaborador e aplique o crop antes de salvar.');
            }

            if ($photo !== null) {
                $storedFiles[] = $photo;
            }

            $repository = new PortalRepository();
            $result = $repository->createCollaboratorRegistration($_POST, [
                'foto' => $photo,
            ]);

            unset($_SESSION['rh_form_old']);
            $_SESSION['rh_form_success'] = 'Colaborador cadastrado com sucesso.';
            $_SESSION['rh_form_access'] = $result['access'] ?? null;

            header('Location: /rh?modal=novo-colaborador');
            exit;
        } catch (Throwable $e) {
            foreach (array_reverse($storedFiles) as $storedFile) {
                MediaStorage::delete($storedFile);
            }

            $_SESSION['rh_form_error'] = $e->getMessage();
            header('Location: /rh?modal=novo-colaborador');
            exit;
        }
    }

    private function consumeCreateModalState() {
        $modalParam = trim((string) ($_GET['modal'] ?? ''));
        $formError = $_SESSION['rh_form_error'] ?? null;
        $successMessage = $_SESSION['rh_form_success'] ?? null;
        $accessInfo = $_SESSION['rh_form_access'] ?? null;
        $old = $_SESSION['rh_form_old'] ?? $this->defaultFormData();

        unset($_SESSION['rh_form_error'], $_SESSION['rh_form_success'], $_SESSION['rh_form_access'], $_SESSION['rh_form_old']);

        return [
            'formError' => $formError,
            'successMessage' => $successMessage,
            'accessInfo' => $accessInfo,
            'old' => $old,
            'isOpen' => $modalParam === 'novo-colaborador' || $formError !== null || $successMessage !== null,
        ];
    }

    private function defaultFormData() {
        return [
            'tipo_cadastro' => 'vigilante',
            'funcao_administrativa' => 'Administrativo',
            'tipo_vinculo' => 'CLT',
            'situacao' => 'Ativo',
            'curso_formacao' => 'Sim',
            'situacao_reciclagem' => 'Valida',
            'outros_cursos' => [],
            'fator_rh' => '+',
        ];
    }

    private function collectOldFormData(array $input) {
        $old = $this->defaultFormData();

        foreach ($input as $key => $value) {
            if ($key === 'senha_provisoria') {
                continue;
            }

            $old[$key] = $value;
        }

        $old['outros_cursos'] = array_values(array_filter(
            is_array($input['outros_cursos'] ?? null) ? $input['outros_cursos'] : [],
            'is_string'
        ));

        return $old;
    }

    private function storeOptionalFile($file, $folder) {
        if (!is_array($file) || (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE)) {
            return null;
        }

        return MediaStorage::store($file, $folder);
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

<?php

namespace Controllers;

use Config\Env;
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
        $actionFlash = $this->consumeActionFlash();
        $kpis = [
            'total_ativos' => 0,
            'em_ferias' => 0,
            'advertencias_recentes' => 0,
        ];
        $dbWarning = null;
        $viewCollaborator = null;
        $viewCollaboratorId = trim((string) ($_GET['view'] ?? ''));
        $editCollaboratorId = $createModalState['editCollaboratorId'];

        try {
            $repository = new PortalRepository();
            $colaboradores = $repository->getCollaborators();
            $modulosRh = $this->buildRhModules($colaboradores);
            $kpis = $repository->getRhKpis();

            if ($createModalState['formMode'] === 'edit') {
                if ($editCollaboratorId === '') {
                    $createModalState['formMode'] = 'create';
                    $createModalState['isOpen'] = false;
                    $createModalState['old'] = $this->defaultFormData();

                    if ($actionFlash['error'] === null) {
                        $actionFlash['error'] = 'Selecione um colaborador válido para editar.';
                    }
                } else {
                    $editCollaborator = $repository->getCollaboratorDetails($editCollaboratorId);

                    if ($editCollaborator === null) {
                        $createModalState['formMode'] = 'create';
                        $createModalState['isOpen'] = false;
                        $createModalState['editCollaboratorId'] = '';
                        $createModalState['existingPhotoUrl'] = '';
                        $createModalState['old'] = $this->defaultFormData();

                        if ($actionFlash['error'] === null) {
                            $actionFlash['error'] = 'Cadastro do colaborador não encontrado.';
                        }
                    } else {
                        if ($createModalState['hasOldInput']) {
                            $createModalState['old']['colaborador_id'] = $editCollaborator['collaborator_id'] ?? $editCollaboratorId;

                            if (($createModalState['old']['foto_url_atual'] ?? '') === '') {
                                $createModalState['old']['foto_url_atual'] = $editCollaborator['foto_url'] ?? '';
                            }
                        } else {
                            $createModalState['old'] = $this->mapCollaboratorToFormData($editCollaborator);
                        }

                        $createModalState['editCollaboratorId'] = (string) ($editCollaborator['collaborator_id'] ?? $editCollaboratorId);
                        $createModalState['existingPhotoUrl'] = trim((string) ($createModalState['old']['foto_url_atual'] ?? ($editCollaborator['foto_url'] ?? '')));
                    }
                }
            }

            if ($viewCollaboratorId !== '') {
                $viewCollaborator = $repository->getCollaboratorDetails($viewCollaboratorId);

                if ($viewCollaborator === null && $actionFlash['error'] === null) {
                    $actionFlash['error'] = 'Cadastro do colaborador não encontrado.';
                }
            }
        } catch (Throwable $e) {
            $dbWarning = 'Não foi possível carregar os dados de RH direto do banco.';
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
            'formMode' => $createModalState['formMode'],
            'editCollaboratorId' => $createModalState['editCollaboratorId'],
            'existingPhotoUrl' => $createModalState['existingPhotoUrl'],
            'actionSuccess' => $actionFlash['success'],
            'actionError' => $actionFlash['error'],
            'viewCollaborator' => $viewCollaborator,
            'isViewModalOpen' => $viewCollaborator !== null,
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

        $_SESSION['rh_form_mode'] = 'create';
        $_SESSION['rh_form_edit_id'] = '';
        $_SESSION['rh_form_existing_photo_url'] = '';
        $_SESSION['rh_form_old'] = $this->collectOldFormData($_POST);
        $storedFiles = [];

        try {
            $photo = $this->storeOptionalFile(
                $_FILES['foto_colaborador'] ?? null,
                'colaboradores/fotos',
                trim((string) Env::get('SUPABASE_COLLABORATORS_BUCKET', Env::get('SUPABASE_COLABORADORES_BUCKET', 'colaboradores')))
            );
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

            unset($_SESSION['rh_form_mode'], $_SESSION['rh_form_edit_id'], $_SESSION['rh_form_existing_photo_url'], $_SESSION['rh_form_old']);
            $_SESSION['rh_form_success'] = 'COLABORADOR SALVO COM SUCESSO!';
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

    public function update() {
        Auth::requireAnyProfile(['Coordenador Geral', 'Administrador']);

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            header('Location: /rh');
            exit;
        }

        $collaboratorId = trim((string) ($_POST['colaborador_id'] ?? ''));
        if ($collaboratorId === '') {
            $_SESSION['rh_action_error'] = 'Selecione um colaborador válido para editar.';
            header('Location: /rh');
            exit;
        }

        $_SESSION['rh_form_mode'] = 'edit';
        $_SESSION['rh_form_edit_id'] = $collaboratorId;
        $_SESSION['rh_form_existing_photo_url'] = trim((string) ($_POST['foto_url_atual'] ?? ''));
        $_SESSION['rh_form_old'] = $this->collectOldFormData($_POST);

        $storedFiles = [];

        try {
            $repository = new PortalRepository();

            if ($repository->getCollaboratorDetails($collaboratorId) === null) {
                throw new \RuntimeException('Cadastro do colaborador não encontrado.');
            }

            $photo = $this->storeOptionalFile(
                $_FILES['foto_colaborador'] ?? null,
                'colaboradores/fotos',
                trim((string) Env::get('SUPABASE_COLLABORATORS_BUCKET', Env::get('SUPABASE_COLABORADORES_BUCKET', 'colaboradores')))
            );

            if ($photo !== null) {
                $storedFiles[] = $photo;
            }

            $result = $repository->updateCollaboratorRegistration($collaboratorId, $_POST, [
                'foto' => $photo,
            ]);

            unset($_SESSION['rh_form_mode'], $_SESSION['rh_form_edit_id'], $_SESSION['rh_form_existing_photo_url'], $_SESSION['rh_form_old']);

            if (!empty($result['photo_changed']) && !empty($result['previous_photo_url'])) {
                try {
                    $this->deleteCollaboratorPhoto($result['previous_photo_url']);
                } catch (Throwable $cleanupError) {
                }
            }

            $_SESSION['rh_action_success'] = 'collaborator_updated';
            header('Location: /rh');
            exit;
        } catch (Throwable $e) {
            foreach (array_reverse($storedFiles) as $storedFile) {
                MediaStorage::delete($storedFile);
            }

            $_SESSION['rh_form_error'] = $e->getMessage();
            header('Location: /rh?modal=editar-colaborador&edit=' . urlencode($collaboratorId));
            exit;
        }
    }

    public function destroy() {
        Auth::requireAnyProfile(['Coordenador Geral', 'Administrador']);

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            header('Location: /rh');
            exit;
        }

        $collaboratorId = trim((string) ($_POST['colaborador_id'] ?? ''));

        if ($collaboratorId === '') {
            $_SESSION['rh_action_error'] = 'Selecione um colaborador válido para excluir.';
            header('Location: /rh');
            exit;
        }

        try {
            $repository = new PortalRepository();
            $collaborator = $repository->getCollaboratorDetails($collaboratorId);

            if ($collaborator === null) {
                throw new \RuntimeException('Cadastro do colaborador não encontrado.');
            }

            if (($collaborator['user_id'] ?? null) === ($_SESSION['user_id'] ?? null)) {
                throw new \RuntimeException('Não é possível excluir o usuário atualmente logado.');
            }

            $result = $repository->deleteCollaboratorRegistration($collaboratorId);
            $this->deleteCollaboratorPhoto($result['photo_url'] ?? null);

            $_SESSION['rh_action_success'] = 'Colaborador excluído com sucesso.';
        } catch (Throwable $e) {
            $_SESSION['rh_action_error'] = $e->getMessage();
        }

        header('Location: /rh');
        exit;
    }

    private function consumeCreateModalState() {
        $modalParam = trim((string) ($_GET['modal'] ?? ''));
        $sessionMode = trim((string) ($_SESSION['rh_form_mode'] ?? ''));
        $editParam = trim((string) ($_GET['edit'] ?? ''));
        $storedEditId = trim((string) ($_SESSION['rh_form_edit_id'] ?? ''));
        $formError = $_SESSION['rh_form_error'] ?? null;
        $successMessage = $_SESSION['rh_form_success'] ?? null;
        $accessInfo = $_SESSION['rh_form_access'] ?? null;
        $hasOldInput = is_array($_SESSION['rh_form_old'] ?? null);
        $old = $hasOldInput ? $_SESSION['rh_form_old'] : $this->defaultFormData();
        $formMode = ($modalParam === 'editar-colaborador' || $sessionMode === 'edit') ? 'edit' : 'create';
        $editCollaboratorId = $editParam !== '' ? $editParam : $storedEditId;
        $existingPhotoUrl = trim((string) ($_SESSION['rh_form_existing_photo_url'] ?? ($old['foto_url_atual'] ?? '')));

        unset(
            $_SESSION['rh_form_error'],
            $_SESSION['rh_form_success'],
            $_SESSION['rh_form_access'],
            $_SESSION['rh_form_old'],
            $_SESSION['rh_form_mode'],
            $_SESSION['rh_form_edit_id'],
            $_SESSION['rh_form_existing_photo_url']
        );

        return [
            'formError' => $formError,
            'successMessage' => $successMessage,
            'accessInfo' => $accessInfo,
            'old' => $old,
            'hasOldInput' => $hasOldInput,
            'formMode' => $formMode,
            'editCollaboratorId' => $editCollaboratorId,
            'existingPhotoUrl' => $existingPhotoUrl,
            'isOpen' => in_array($modalParam, ['novo-colaborador', 'editar-colaborador'], true) || $formError !== null || $successMessage !== null,
        ];
    }

    private function consumeActionFlash() {
        $success = $_SESSION['rh_action_success'] ?? null;
        $error = $_SESSION['rh_action_error'] ?? null;

        unset($_SESSION['rh_action_success'], $_SESSION['rh_action_error']);

        return [
            'success' => $success,
            'error' => $error,
        ];
    }

    private function defaultFormData() {
        return [
            'colaborador_id' => '',
            'tipo_cadastro' => 'vigilante',
            'funcao_administrativa' => 'Administrativo',
            'tipo_vinculo' => 'CLT',
            'situacao' => 'Ativo',
            'curso_formacao' => 'Sim',
            'situacao_reciclagem' => 'Valida',
            'outros_cursos' => [],
            'fator_rh' => '+',
            'foto_url_atual' => '',
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

    private function mapCollaboratorToFormData(array $collaborator) {
        $registrationType = ($collaborator['tipo_cadastro'] ?? '') === 'vigilante'
            || ($collaborator['perfil'] ?? '') === 'Vigilante'
            || ($collaborator['cargo'] ?? '') === 'Vigilante'
            ? 'vigilante'
            : 'financeiro_administrativo';

        return array_merge($this->defaultFormData(), [
            'colaborador_id' => (string) ($collaborator['collaborator_id'] ?? ''),
            'tipo_cadastro' => $registrationType,
            'funcao_administrativa' => (($collaborator['cargo'] ?? '') === 'Financeiro') ? 'Financeiro' : 'Administrativo',
            'email_acesso' => (string) ($collaborator['email'] ?? ''),
            'nome_completo' => (string) ($collaborator['nome'] ?? ''),
            'cpf' => (string) ($collaborator['cpf'] ?? ''),
            'rg' => (string) ($collaborator['rg'] ?? ''),
            'data_nascimento' => (string) ($collaborator['data_nascimento'] ?? ''),
            'telefone_principal' => (string) ($collaborator['telefone_principal'] ?? ''),
            'telefone_familiar' => (string) ($collaborator['telefone_familiar'] ?? ''),
            'cep' => (string) ($collaborator['cep'] ?? ''),
            'logradouro' => (string) ($collaborator['logradouro'] ?? ''),
            'numero' => (string) ($collaborator['numero'] ?? ''),
            'bairro' => (string) ($collaborator['bairro'] ?? ''),
            'complemento' => (string) ($collaborator['complemento'] ?? ''),
            'cidade' => (string) ($collaborator['cidade'] ?? ''),
            'uf' => (string) ($collaborator['uf'] ?? ''),
            'nome_mae' => (string) ($collaborator['nome_mae'] ?? ''),
            'tipo_sanguineo' => (string) ($collaborator['tipo_sanguineo'] ?? ''),
            'fator_rh' => (string) ($collaborator['fator_rh'] ?? '+'),
            'tipo_vinculo' => (string) ($collaborator['tipo_vinculo'] ?? 'CLT'),
            'data_admissao' => (string) ($collaborator['data_admissao'] ?? ''),
            'numero_admissao' => (string) ($collaborator['numero_admissao'] ?? ''),
            'situacao' => (string) ($collaborator['situacao'] ?? 'Ativo'),
            'numero_cnv' => (string) ($collaborator['numero_cnv'] ?? ''),
            'validade_cnv' => (string) ($collaborator['validade_cnv'] ?? ''),
            'curso_formacao' => !empty($collaborator['curso_formacao_concluido']) ? 'Sim' : 'Nao',
            'data_ultima_reciclagem' => (string) ($collaborator['data_ultima_reciclagem'] ?? ''),
            'situacao_reciclagem' => (string) ($collaborator['situacao_reciclagem'] ?? 'Valida'),
            'outros_cursos' => array_values(array_filter([
                !empty($collaborator['curso_escolta_armada']) ? 'escolta_armada' : null,
                !empty($collaborator['curso_seguranca_eventos']) ? 'seguranca_eventos' : null,
                !empty($collaborator['curso_seguranca_vip']) ? 'seguranca_vip' : null,
            ])),
            'foto_url_atual' => (string) ($collaborator['foto_url'] ?? ''),
        ]);
    }

    private function storeOptionalFile($file, $folder, $bucket = null) {
        if (!is_array($file) || (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE)) {
            return null;
        }

        return MediaStorage::store($file, $folder, $bucket);
    }

    private function deleteCollaboratorPhoto($photoUrl) {
        $photoUrl = trim((string) $photoUrl);
        if ($photoUrl === '') {
            return;
        }

        $bucket = trim((string) Env::get(
            'SUPABASE_COLLABORATORS_BUCKET',
            Env::get('SUPABASE_COLABORADORES_BUCKET', 'colaboradores')
        ));

        if (strpos($photoUrl, '/uploads/') === 0) {
            MediaStorage::delete([
                'driver' => 'local',
                'path' => dirname(__DIR__) . '/public' . str_replace('/', DIRECTORY_SEPARATOR, $photoUrl),
            ]);
            return;
        }

        $urlPath = parse_url($photoUrl, PHP_URL_PATH);
        if (!is_string($urlPath) || $urlPath === '') {
            return;
        }

        $customPublicPath = parse_url((string) Env::get('SUPABASE_STORAGE_PUBLIC_URL', ''), PHP_URL_PATH);
        if (is_string($customPublicPath) && $customPublicPath !== '') {
            $customPublicPath = rtrim($customPublicPath, '/');
            if (strpos($urlPath, $customPublicPath . '/') === 0) {
                $objectPath = rawurldecode(substr($urlPath, strlen($customPublicPath . '/')));
                MediaStorage::delete([
                    'driver' => 'supabase',
                    'object_path' => $objectPath,
                    'bucket' => $bucket,
                ]);
                return;
            }
        }

        $bucketPrefix = '/storage/v1/object/public/' . rawurlencode($bucket) . '/';
        if (strpos($urlPath, $bucketPrefix) === false) {
            return;
        }

        $objectPath = rawurldecode(substr($urlPath, strpos($urlPath, $bucketPrefix) + strlen($bucketPrefix)));
        if ($objectPath === '') {
            return;
        }

        MediaStorage::delete([
            'driver' => 'supabase',
            'object_path' => $objectPath,
            'bucket' => $bucket,
        ]);
    }

    private function buildRhModules(array $colaboradores) {
        $modulos = [
            'seguranca_privada' => [
                'slug' => 'seguranca_privada',
                'title' => 'COLABORADORES SELVA SEGURANÇA PRIVADA',
                'subtitle' => 'Base administrativa, operacional e técnica da operação principal.',
                'roles' => ['Administrativo', 'Vigilante', 'Técnico'],
                'colaboradores' => [],
                'role_counts' => [
                    'Administrativo' => 0,
                    'Vigilante' => 0,
                    'Técnico' => 0,
                ],
            ],
            'servicos_terceirizacoes' => [
                'slug' => 'servicos_terceirizacoes',
                'title' => 'COLABORADORES SELVA SERVIÇOS E TERCEIRIZAÇÕES',
                'subtitle' => 'Equipe dedicada a postos de apoio, portaria e serviços terceirizados.',
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

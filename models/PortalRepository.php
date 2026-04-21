<?php

namespace Models;

use Config\Database;
use DateTimeImmutable;
use PDO;
use RuntimeException;
use Throwable;

class PortalRepository {
    private static $legacySeedCleanupAttempted = false;
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();

        if (!self::$legacySeedCleanupAttempted) {
            self::$legacySeedCleanupAttempted = true;
            $this->cleanupLegacyMockCollaborator();
        }
    }

    public function getDashboardStats() {
        return [
            'vigilantes_em_campo' => (int) $this->fetchValue(
                "SELECT COUNT(*) FROM rondas WHERE status = 'em_andamento'"
            ),
            'veiculos_em_ronda' => (int) $this->fetchValue(
                "SELECT COUNT(DISTINCT veiculo_id) FROM rondas WHERE status = 'em_andamento' AND veiculo_id IS NOT NULL"
            ),
            'manutencoes_criticas' => (int) $this->fetchValue(
                "SELECT COUNT(*) FROM veiculos
                 WHERE (km_prox_troca_oleo IS NOT NULL AND km_atual >= km_prox_troca_oleo - 1000)
                    OR (km_prox_revisao IS NOT NULL AND km_atual >= km_prox_revisao - 2000)"
            ),
            'ocorrencias_hoje' => (int) $this->fetchValue(
                "SELECT COUNT(*) FROM ocorrencias WHERE data_hora::date = CURRENT_DATE"
            ),
        ];
    }

    public function getActiveRounds($limit = 5) {
        $rows = $this->fetchAll(
            "SELECT r.id, u.nome AS vigilante, v.modelo, v.placa, r.data_inicio, r.status
             FROM rondas r
             JOIN vigilantes vg ON vg.id = r.vigilante_id
             JOIN usuarios u ON u.id = vg.usuario_id
             LEFT JOIN veiculos v ON v.id = r.veiculo_id
             WHERE r.status = 'em_andamento'
             ORDER BY r.data_inicio DESC
             LIMIT :limit",
            [':limit' => (int) $limit],
            [':limit' => PDO::PARAM_INT]
        );

        return array_map(function ($row) {
            $row['veiculo'] = $row['modelo'] && $row['placa']
                ? $row['modelo'] . ' - ' . $row['placa']
                : 'Sem veículo';
            $row['status_label'] = $this->formatRoundStatus($row['status']);
            return $row;
        }, $rows);
    }

    public function getRecentOccurrences($limit = 5) {
        $rows = $this->fetchAll(
            "SELECT o.id, o.tipo, o.descricao, o.data_hora, u.nome AS vigilante
             FROM ocorrencias o
             JOIN rondas r ON r.id = o.ronda_id
             JOIN vigilantes vg ON vg.id = r.vigilante_id
             JOIN usuarios u ON u.id = vg.usuario_id
             ORDER BY o.data_hora DESC
             LIMIT :limit",
            [':limit' => (int) $limit],
            [':limit' => PDO::PARAM_INT]
        );

        return array_map(function ($row) {
            $row['tipo_label'] = $this->formatOccurrenceType($row['tipo']);
            return $row;
        }, $rows);
    }

    public function getRhKpis() {
        return [
            'total_ativos' => (int) $this->fetchValue("SELECT COUNT(*) FROM usuarios WHERE ativo = true"),
            'em_ferias' => 0,
            'advertencias_recentes' => 0,
        ];
    }

    public function getCollaborators() {
        return $this->fetchAll(
            "SELECT c.id AS collaborator_id,
                    u.id AS user_id,
                    u.nome,
                    cd.foto_url,
                    COALESCE(c.cargo, CASE WHEN p.nome = 'Vigilante' THEN 'Vigilante' ELSE p.nome END) AS cargo,
                    COALESCE(c.departamento, CASE WHEN p.nome = 'Vigilante' THEN 'Operacional' ELSE 'Administrativo' END) AS departamento,
                    COALESCE(cd.situacao, CASE WHEN u.ativo THEN 'Ativo' ELSE 'Inativo' END) AS status
             FROM usuarios u
             JOIN perfis p ON p.id = u.perfil_id
             LEFT JOIN colaboradores c ON c.usuario_id = u.id
             LEFT JOIN colaborador_detalhes cd ON cd.colaborador_id = c.id
             ORDER BY u.ativo DESC, u.nome ASC"
        );
    }

    public function getCollaboratorDetails($collaboratorId) {
        $this->ensureCollaboratorRegistrationSchema();

        $row = $this->fetchOne(
            "SELECT c.id AS collaborator_id,
                    u.id AS user_id,
                    u.nome,
                    u.email,
                    p.nome AS perfil,
                    u.ativo,
                    c.cargo,
                    c.departamento,
                    c.data_admissao,
                    cd.tipo_cadastro,
                    cd.foto_url,
                    cd.cpf,
                    cd.rg,
                    cd.data_nascimento,
                    cd.telefone_principal,
                    cd.telefone_familiar,
                    cd.cep,
                    cd.logradouro,
                    cd.numero,
                    cd.bairro,
                    cd.complemento,
                    cd.cidade,
                    cd.uf,
                    cd.endereco_completo,
                    cd.nome_mae,
                    cd.tipo_sanguineo,
                    cd.fator_rh,
                    cd.tipo_vinculo,
                    cd.numero_admissao,
                    cd.situacao,
                    cd.banco_nome,
                    cd.agencia_bancaria,
                    cd.conta_bancaria,
                    cd.tipo_conta,
                    cd.chave_pix,
                    cd.titular_conta,
                    v.numero_cnv,
                    v.validade_cnv,
                    v.curso_formacao_concluido,
                    v.data_ultima_reciclagem,
                    v.situacao_reciclagem,
                    v.curso_escolta_armada,
                    v.curso_seguranca_eventos,
                    v.curso_seguranca_vip
             FROM colaboradores c
             JOIN usuarios u ON u.id = c.usuario_id
             JOIN perfis p ON p.id = u.perfil_id
             LEFT JOIN colaborador_detalhes cd ON cd.colaborador_id = c.id
             LEFT JOIN vigilantes v ON v.usuario_id = u.id
             WHERE c.id = :collaborator_id
             LIMIT 1",
            [':collaborator_id' => $collaboratorId]
        );

        if ($row === null) {
            return null;
        }

        $row['ativo'] = $this->toBoolean($row['ativo'] ?? false);
        $row['curso_formacao_concluido'] = $this->toBoolean($row['curso_formacao_concluido'] ?? false);
        $row['curso_escolta_armada'] = $this->toBoolean($row['curso_escolta_armada'] ?? false);
        $row['curso_seguranca_eventos'] = $this->toBoolean($row['curso_seguranca_eventos'] ?? false);
        $row['curso_seguranca_vip'] = $this->toBoolean($row['curso_seguranca_vip'] ?? false);
        $row['outros_cursos'] = array_values(array_filter([
            $row['curso_escolta_armada'] ? 'Escolta armada' : null,
            $row['curso_seguranca_eventos'] ? 'Segurança de Eventos' : null,
            $row['curso_seguranca_vip'] ? 'Segurança VIP' : null,
        ]));

        return $row;
    }

    public function deleteCollaboratorRegistration($collaboratorId) {
        $target = $this->fetchOne(
            "SELECT c.id AS collaborator_id,
                    c.usuario_id,
                    cd.foto_url,
                    EXISTS(
                        SELECT 1
                        FROM vigilantes v
                        JOIN rondas r ON r.vigilante_id = v.id
                        WHERE v.usuario_id = c.usuario_id
                    ) AS has_rounds,
                    EXISTS(
                        SELECT 1
                        FROM vigilantes v
                        JOIN checklist_veiculos cv ON cv.vigilante_id = v.id
                        WHERE v.usuario_id = c.usuario_id
                    ) AS has_checklists
             FROM colaboradores c
             LEFT JOIN colaborador_detalhes cd ON cd.colaborador_id = c.id
             WHERE c.id = :collaborator_id
             LIMIT 1",
            [':collaborator_id' => $collaboratorId]
        );

        if ($target === null) {
            throw new RuntimeException('Cadastro do colaborador não encontrado.');
        }

        if ($this->toBoolean($target['has_rounds'] ?? false) || $this->toBoolean($target['has_checklists'] ?? false)) {
            throw new RuntimeException('Não é possível excluir este colaborador porque existem registros operacionais vinculados ao cadastro.');
        }

        try {
            $this->db->beginTransaction();

            $this->run(
                "DELETE FROM usuarios WHERE id = :usuario_id",
                [':usuario_id' => $target['usuario_id']]
            );

            $this->db->commit();

            return [
                'photo_url' => $target['foto_url'] ?? null,
            ];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

    public function createCollaboratorRegistration(array $payload, array $media = []) {
        $this->ensureCollaboratorRegistrationSchema();

        $tipoCadastro = $this->normalizeRegistrationType($payload['tipo_cadastro'] ?? 'vigilante');
        $nomeCompleto = trim((string) ($payload['nome_completo'] ?? ''));
        $cpf = $this->normalizeDigits($payload['cpf'] ?? '');
        $fotoUrl = $media['foto']['url'] ?? null;

        if ($nomeCompleto === '') {
            throw new RuntimeException('Informe o nome completo do colaborador.');
        }

        if ($fotoUrl === null || $fotoUrl === '') {
            throw new RuntimeException('A foto do colaborador é obrigatória para este cadastro.');
        }

        if (strlen($cpf) !== 11) {
            throw new RuntimeException('Informe um CPF válido com 11 dígitos.');
        }

        if ($this->collaboratorCpfExists($cpf)) {
            throw new RuntimeException('Já existe um colaborador cadastrado com este CPF.');
        }

        foreach ([
            'rg' => 'Informe o RG do colaborador.',
            'data_nascimento' => 'Informe a data de nascimento.',
            'telefone_principal' => 'Informe o telefone principal.',
            'telefone_familiar' => 'Informe o telefone familiar.',
            'cep' => 'Informe o CEP do endereço.',
            'logradouro' => 'Informe o logradouro do endereço.',
            'numero' => 'Informe o número do endereço.',
            'bairro' => 'Informe o bairro.',
            'cidade' => 'Informe a cidade.',
            'uf' => 'Informe a UF.',
            'nome_mae' => 'Informe o nome da mãe.',
            'tipo_sanguineo' => 'Informe o tipo sanguíneo.',
            'fator_rh' => 'Informe o fator RH.',
            'tipo_vinculo' => 'Informe o tipo de vínculo.',
            'data_admissao' => 'Informe a data de admissão.',
            'numero_admissao' => 'Informe o número da admissão.',
            'situacao' => 'Informe a situação do colaborador.',
        ] as $field => $message) {
            if ($this->nullIfBlank($payload[$field] ?? null) === null) {
                throw new RuntimeException($message);
            }
        }

        if ($tipoCadastro === 'vigilante') {
            foreach ([
                'numero_cnv' => 'Informe o número da CNV.',
                'validade_cnv' => 'Informe a validade da CNV.',
                'curso_formacao' => 'Informe se o colaborador possui curso de formação.',
                'data_ultima_reciclagem' => 'Informe a data da última reciclagem.',
                'situacao_reciclagem' => 'Informe a situação da reciclagem.',
            ] as $field => $message) {
                if ($this->nullIfBlank($payload[$field] ?? null) === null) {
                    throw new RuntimeException($message);
                }
            }
        }

        $emailAcesso = trim((string) ($payload['email_acesso'] ?? ''));
        if ($emailAcesso === '') {
            $emailAcesso = ($tipoCadastro === 'vigilante' ? 'vigilante.' : 'colaborador.') . $cpf . '@selva.local';
        }

        if (!filter_var($emailAcesso, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Informe um e-mail de acesso válido.');
        }

        if ($this->emailExists($emailAcesso)) {
            throw new RuntimeException('Já existe um usuário com este e-mail de acesso.');
        }

        $senhaProvisoria = trim((string) ($payload['senha_provisoria'] ?? ''));
        $senhaFoiGerada = false;
        if ($senhaProvisoria === '') {
            $senhaProvisoria = $this->generateTemporaryPassword();
            $senhaFoiGerada = true;
        }

        if (strlen($senhaProvisoria) < 6) {
            throw new RuntimeException('A senha provisória deve ter pelo menos 6 caracteres.');
        }

        $situacao = $this->normalizeEmploymentStatus($payload['situacao'] ?? 'Ativo');
        $cargo = $this->resolveCollaboratorRole($tipoCadastro, $payload['funcao_administrativa'] ?? null);
        $departamento = $tipoCadastro === 'vigilante'
            ? 'Operacional'
            : ($cargo === 'Financeiro' ? 'Financeiro' : 'Administrativo');
        $perfilNome = $tipoCadastro === 'vigilante' ? 'Vigilante' : 'Colaborador Interno';
        $perfilId = $this->findProfileIdByName($perfilNome);

        if ($perfilId === null) {
            throw new RuntimeException('O perfil necessário para este cadastro não foi encontrado no banco.');
        }

        $outrosCursos = is_array($payload['outros_cursos'] ?? null) ? $payload['outros_cursos'] : [];

        $tipoSanguineo = strtoupper(trim((string) ($payload['tipo_sanguineo'] ?? '')));
        $fatorRh = trim((string) ($payload['fator_rh'] ?? ''));

        try {
            $this->db->beginTransaction();

            $usuario = $this->run(
                "INSERT INTO usuarios (nome, email, senha_hash, perfil_id, ativo)
                 VALUES (:nome, :email, :senha_hash, :perfil_id, :ativo)
                 RETURNING id",
                [
                    ':nome' => $nomeCompleto,
                    ':email' => $emailAcesso,
                    ':senha_hash' => password_hash($senhaProvisoria, PASSWORD_DEFAULT),
                    ':perfil_id' => $perfilId,
                    ':ativo' => $situacao === 'Ativo',
                ],
                [
                    ':perfil_id' => PDO::PARAM_INT,
                    ':ativo' => PDO::PARAM_BOOL,
                ]
            )->fetch();

            $colaborador = $this->run(
                "INSERT INTO colaboradores (usuario_id, cargo, departamento, data_admissao)
                 VALUES (:usuario_id, :cargo, :departamento, :data_admissao)
                 RETURNING id",
                [
                    ':usuario_id' => $usuario['id'],
                    ':cargo' => $cargo,
                    ':departamento' => $departamento,
                    ':data_admissao' => $this->nullIfBlank($payload['data_admissao'] ?? null),
                ]
            )->fetch();

            $this->run(
                "INSERT INTO colaborador_detalhes (
                    colaborador_id,
                    tipo_cadastro,
                    foto_url,
                    cpf,
                    rg,
                    data_nascimento,
                    telefone_principal,
                    telefone_familiar,
                    cep,
                    logradouro,
                    numero,
                    bairro,
                    complemento,
                    cidade,
                    uf,
                    endereco_completo,
                    nome_mae,
                    tipo_sanguineo,
                    fator_rh,
                    tipo_vinculo,
                    numero_admissao,
                    situacao,
                    banco_nome,
                    agencia_bancaria,
                    conta_bancaria,
                    tipo_conta,
                    chave_pix,
                    titular_conta
                 ) VALUES (
                    :colaborador_id,
                    :tipo_cadastro,
                    :foto_url,
                    :cpf,
                    :rg,
                    :data_nascimento,
                    :telefone_principal,
                    :telefone_familiar,
                    :cep,
                    :logradouro,
                    :numero,
                    :bairro,
                    :complemento,
                    :cidade,
                    :uf,
                    :endereco_completo,
                    :nome_mae,
                    :tipo_sanguineo,
                    :fator_rh,
                    :tipo_vinculo,
                    :numero_admissao,
                    :situacao,
                    :banco_nome,
                    :agencia_bancaria,
                    :conta_bancaria,
                    :tipo_conta,
                    :chave_pix,
                    :titular_conta
                 )",
                [
                    ':colaborador_id' => $colaborador['id'],
                    ':tipo_cadastro' => $tipoCadastro,
                    ':foto_url' => $fotoUrl,
                    ':cpf' => $cpf,
                    ':rg' => $this->nullIfBlank($payload['rg'] ?? null),
                    ':data_nascimento' => $this->nullIfBlank($payload['data_nascimento'] ?? null),
                    ':telefone_principal' => $this->nullIfBlank($payload['telefone_principal'] ?? null),
                    ':telefone_familiar' => $this->nullIfBlank($payload['telefone_familiar'] ?? null),
                    ':cep' => $this->nullIfBlank($payload['cep'] ?? null),
                    ':logradouro' => $this->nullIfBlank($payload['logradouro'] ?? null),
                    ':numero' => $this->nullIfBlank($payload['numero'] ?? null),
                    ':bairro' => $this->nullIfBlank($payload['bairro'] ?? null),
                    ':complemento' => $this->nullIfBlank($payload['complemento'] ?? null),
                    ':cidade' => $this->nullIfBlank($payload['cidade'] ?? null),
                    ':uf' => $this->nullIfBlank($payload['uf'] ?? null),
                    ':endereco_completo' => $this->buildAddressLine($payload),
                    ':nome_mae' => $this->nullIfBlank($payload['nome_mae'] ?? null),
                    ':tipo_sanguineo' => $tipoSanguineo !== '' ? $tipoSanguineo : null,
                    ':fator_rh' => $fatorRh !== '' ? $fatorRh : null,
                    ':tipo_vinculo' => $this->nullIfBlank($payload['tipo_vinculo'] ?? null),
                    ':numero_admissao' => $this->nullIfBlank($payload['numero_admissao'] ?? null),
                    ':situacao' => $situacao,
                    ':banco_nome' => $this->nullIfBlank($payload['banco_nome'] ?? null),
                    ':agencia_bancaria' => $this->nullIfBlank($payload['agencia_bancaria'] ?? null),
                    ':conta_bancaria' => $this->nullIfBlank($payload['conta_bancaria'] ?? null),
                    ':tipo_conta' => $this->nullIfBlank($payload['tipo_conta'] ?? null),
                    ':chave_pix' => $this->nullIfBlank($payload['chave_pix'] ?? null),
                    ':titular_conta' => $this->nullIfBlank($payload['titular_conta'] ?? null),
                ]
            );

            if ($tipoCadastro === 'vigilante') {
                $cursoFormacao = strtolower(trim((string) ($payload['curso_formacao'] ?? 'nao'))) === 'sim';

                $this->run(
                    "INSERT INTO vigilantes (
                        usuario_id,
                        cnh,
                        validade_cnh,
                        formacao,
                        validade_reciclagem,
                        numero_cnv,
                        validade_cnv,
                        curso_formacao_concluido,
                        data_ultima_reciclagem,
                        situacao_reciclagem,
                        curso_escolta_armada,
                        curso_seguranca_eventos,
                        curso_seguranca_vip
                     ) VALUES (
                        :usuario_id,
                        :cnh,
                        :validade_cnh,
                        :formacao,
                        :validade_reciclagem,
                        :numero_cnv,
                        :validade_cnv,
                        :curso_formacao_concluido,
                        :data_ultima_reciclagem,
                        :situacao_reciclagem,
                        :curso_escolta_armada,
                        :curso_seguranca_eventos,
                        :curso_seguranca_vip
                     )",
                    [
                        ':usuario_id' => $usuario['id'],
                        ':cnh' => null,
                        ':validade_cnh' => null,
                        ':formacao' => $cursoFormacao ? 'Curso de formação concluído' : null,
                        ':validade_reciclagem' => null,
                        ':numero_cnv' => $this->nullIfBlank($payload['numero_cnv'] ?? null),
                        ':validade_cnv' => $this->nullIfBlank($payload['validade_cnv'] ?? null),
                        ':curso_formacao_concluido' => $cursoFormacao,
                        ':data_ultima_reciclagem' => $this->nullIfBlank($payload['data_ultima_reciclagem'] ?? null),
                        ':situacao_reciclagem' => $this->nullIfBlank($payload['situacao_reciclagem'] ?? null),
                        ':curso_escolta_armada' => in_array('escolta_armada', $outrosCursos, true),
                        ':curso_seguranca_eventos' => in_array('seguranca_eventos', $outrosCursos, true),
                        ':curso_seguranca_vip' => in_array('seguranca_vip', $outrosCursos, true),
                    ],
                    [
                        ':curso_formacao_concluido' => PDO::PARAM_BOOL,
                        ':curso_escolta_armada' => PDO::PARAM_BOOL,
                        ':curso_seguranca_eventos' => PDO::PARAM_BOOL,
                        ':curso_seguranca_vip' => PDO::PARAM_BOOL,
                    ]
                );
            }

            $this->db->commit();

            return [
                'user_id' => $usuario['id'],
                'collaborator_id' => $colaborador['id'],
                'access' => [
                    'email' => $emailAcesso,
                    'password' => $senhaProvisoria,
                    'generated_password' => $senhaFoiGerada,
                ],
            ];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

    public function updateCollaboratorRegistration($collaboratorId, array $payload, array $media = []) {
        $this->ensureCollaboratorRegistrationSchema();

        $target = $this->fetchOne(
            "SELECT c.id AS collaborator_id,
                    c.usuario_id,
                    cd.tipo_cadastro,
                    cd.foto_url,
                    u.email,
                    EXISTS(
                        SELECT 1
                        FROM vigilantes v
                        WHERE v.usuario_id = c.usuario_id
                    ) AS has_vigilante
             FROM colaboradores c
             JOIN usuarios u ON u.id = c.usuario_id
             LEFT JOIN colaborador_detalhes cd ON cd.colaborador_id = c.id
             WHERE c.id = :collaborator_id
             LIMIT 1",
            [':collaborator_id' => $collaboratorId]
        );

        if ($target === null) {
            throw new RuntimeException('Cadastro do colaborador não encontrado.');
        }

        $existingType = $this->normalizeRegistrationType(
            $target['tipo_cadastro'] ?? ($this->toBoolean($target['has_vigilante'] ?? false) ? 'vigilante' : 'financeiro_administrativo')
        );
        $tipoCadastro = $this->normalizeRegistrationType($payload['tipo_cadastro'] ?? $existingType);

        if ($tipoCadastro !== $existingType) {
            throw new RuntimeException('Não é permitido alterar o tipo de cadastro de um colaborador existente.');
        }

        $nomeCompleto = trim((string) ($payload['nome_completo'] ?? ''));
        $cpf = $this->normalizeDigits($payload['cpf'] ?? '');
        $fotoUrl = trim((string) ($media['foto']['url'] ?? ($payload['foto_url_atual'] ?? ($target['foto_url'] ?? ''))));

        if ($nomeCompleto === '') {
            throw new RuntimeException('Informe o nome completo do colaborador.');
        }

        if ($fotoUrl === '') {
            throw new RuntimeException('A foto do colaborador é obrigatória para este cadastro.');
        }

        if (strlen($cpf) !== 11) {
            throw new RuntimeException('Informe um CPF válido com 11 dígitos.');
        }

        if ($this->collaboratorCpfExistsForOther($cpf, $collaboratorId)) {
            throw new RuntimeException('Já existe um colaborador cadastrado com este CPF.');
        }

        foreach ([
            'rg' => 'Informe o RG do colaborador.',
            'data_nascimento' => 'Informe a data de nascimento.',
            'telefone_principal' => 'Informe o telefone principal.',
            'telefone_familiar' => 'Informe o telefone familiar.',
            'cep' => 'Informe o CEP do endereço.',
            'logradouro' => 'Informe o logradouro do endereço.',
            'numero' => 'Informe o número do endereço.',
            'bairro' => 'Informe o bairro.',
            'cidade' => 'Informe a cidade.',
            'uf' => 'Informe a UF.',
            'nome_mae' => 'Informe o nome da mãe.',
            'tipo_sanguineo' => 'Informe o tipo sanguíneo.',
            'fator_rh' => 'Informe o fator RH.',
            'tipo_vinculo' => 'Informe o tipo de vínculo.',
            'data_admissao' => 'Informe a data de admissão.',
            'numero_admissao' => 'Informe o número da admissão.',
            'situacao' => 'Informe a situação do colaborador.',
        ] as $field => $message) {
            if ($this->nullIfBlank($payload[$field] ?? null) === null) {
                throw new RuntimeException($message);
            }
        }

        if ($tipoCadastro === 'vigilante') {
            foreach ([
                'numero_cnv' => 'Informe o número da CNV.',
                'validade_cnv' => 'Informe a validade da CNV.',
                'curso_formacao' => 'Informe se o colaborador possui curso de formação.',
                'data_ultima_reciclagem' => 'Informe a data da última reciclagem.',
                'situacao_reciclagem' => 'Informe a situação da reciclagem.',
            ] as $field => $message) {
                if ($this->nullIfBlank($payload[$field] ?? null) === null) {
                    throw new RuntimeException($message);
                }
            }
        }

        $emailAcesso = trim((string) ($payload['email_acesso'] ?? ''));
        if ($emailAcesso === '') {
            $emailAcesso = trim((string) ($target['email'] ?? ''));
        }
        if ($emailAcesso === '') {
            $emailAcesso = ($tipoCadastro === 'vigilante' ? 'vigilante.' : 'colaborador.') . $cpf . '@selva.local';
        }

        if (!filter_var($emailAcesso, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Informe um e-mail de acesso válido.');
        }

        if ($this->emailExistsForOther($emailAcesso, $target['usuario_id'])) {
            throw new RuntimeException('Já existe um usuário com este e-mail de acesso.');
        }

        $senhaProvisoria = trim((string) ($payload['senha_provisoria'] ?? ''));
        if ($senhaProvisoria !== '' && strlen($senhaProvisoria) < 6) {
            throw new RuntimeException('A senha provisória deve ter pelo menos 6 caracteres.');
        }

        $situacao = $this->normalizeEmploymentStatus($payload['situacao'] ?? 'Ativo');
        $cargo = $this->resolveCollaboratorRole($tipoCadastro, $payload['funcao_administrativa'] ?? null);
        $departamento = $tipoCadastro === 'vigilante'
            ? 'Operacional'
            : ($cargo === 'Financeiro' ? 'Financeiro' : 'Administrativo');
        $perfilNome = $tipoCadastro === 'vigilante' ? 'Vigilante' : 'Colaborador Interno';
        $perfilId = $this->findProfileIdByName($perfilNome);

        if ($perfilId === null) {
            throw new RuntimeException('O perfil necessário para este cadastro não foi encontrado no banco.');
        }

        $outrosCursos = is_array($payload['outros_cursos'] ?? null) ? $payload['outros_cursos'] : [];
        $tipoSanguineo = strtoupper(trim((string) ($payload['tipo_sanguineo'] ?? '')));
        $fatorRh = trim((string) ($payload['fator_rh'] ?? ''));

        try {
            $this->db->beginTransaction();

            $userParams = [
                ':usuario_id' => $target['usuario_id'],
                ':nome' => $nomeCompleto,
                ':email' => $emailAcesso,
                ':perfil_id' => $perfilId,
                ':ativo' => $situacao === 'Ativo',
            ];
            $userTypes = [
                ':perfil_id' => PDO::PARAM_INT,
                ':ativo' => PDO::PARAM_BOOL,
            ];

            if ($senhaProvisoria !== '') {
                $userParams[':senha_hash'] = password_hash($senhaProvisoria, PASSWORD_DEFAULT);

                $this->run(
                    "UPDATE usuarios
                     SET nome = :nome,
                         email = :email,
                         senha_hash = :senha_hash,
                         perfil_id = :perfil_id,
                         ativo = :ativo
                     WHERE id = :usuario_id",
                    $userParams,
                    $userTypes
                );
            } else {
                $this->run(
                    "UPDATE usuarios
                     SET nome = :nome,
                         email = :email,
                         perfil_id = :perfil_id,
                         ativo = :ativo
                     WHERE id = :usuario_id",
                    $userParams,
                    $userTypes
                );
            }

            $this->run(
                "UPDATE colaboradores
                 SET cargo = :cargo,
                     departamento = :departamento,
                     data_admissao = :data_admissao
                 WHERE id = :collaborator_id",
                [
                    ':collaborator_id' => $collaboratorId,
                    ':cargo' => $cargo,
                    ':departamento' => $departamento,
                    ':data_admissao' => $this->nullIfBlank($payload['data_admissao'] ?? null),
                ]
            );

            $this->run(
                "INSERT INTO colaborador_detalhes (
                    colaborador_id,
                    tipo_cadastro,
                    foto_url,
                    cpf,
                    rg,
                    data_nascimento,
                    telefone_principal,
                    telefone_familiar,
                    cep,
                    logradouro,
                    numero,
                    bairro,
                    complemento,
                    cidade,
                    uf,
                    endereco_completo,
                    nome_mae,
                    tipo_sanguineo,
                    fator_rh,
                    tipo_vinculo,
                    numero_admissao,
                    situacao,
                    banco_nome,
                    agencia_bancaria,
                    conta_bancaria,
                    tipo_conta,
                    chave_pix,
                    titular_conta
                 ) VALUES (
                    :colaborador_id,
                    :tipo_cadastro,
                    :foto_url,
                    :cpf,
                    :rg,
                    :data_nascimento,
                    :telefone_principal,
                    :telefone_familiar,
                    :cep,
                    :logradouro,
                    :numero,
                    :bairro,
                    :complemento,
                    :cidade,
                    :uf,
                    :endereco_completo,
                    :nome_mae,
                    :tipo_sanguineo,
                    :fator_rh,
                    :tipo_vinculo,
                    :numero_admissao,
                    :situacao,
                    :banco_nome,
                    :agencia_bancaria,
                    :conta_bancaria,
                    :tipo_conta,
                    :chave_pix,
                    :titular_conta
                 )
                 ON CONFLICT (colaborador_id) DO UPDATE SET
                    tipo_cadastro = EXCLUDED.tipo_cadastro,
                    foto_url = EXCLUDED.foto_url,
                    cpf = EXCLUDED.cpf,
                    rg = EXCLUDED.rg,
                    data_nascimento = EXCLUDED.data_nascimento,
                    telefone_principal = EXCLUDED.telefone_principal,
                    telefone_familiar = EXCLUDED.telefone_familiar,
                    cep = EXCLUDED.cep,
                    logradouro = EXCLUDED.logradouro,
                    numero = EXCLUDED.numero,
                    bairro = EXCLUDED.bairro,
                    complemento = EXCLUDED.complemento,
                    cidade = EXCLUDED.cidade,
                    uf = EXCLUDED.uf,
                    endereco_completo = EXCLUDED.endereco_completo,
                    nome_mae = EXCLUDED.nome_mae,
                    tipo_sanguineo = EXCLUDED.tipo_sanguineo,
                    fator_rh = EXCLUDED.fator_rh,
                    tipo_vinculo = EXCLUDED.tipo_vinculo,
                    numero_admissao = EXCLUDED.numero_admissao,
                    situacao = EXCLUDED.situacao,
                    banco_nome = EXCLUDED.banco_nome,
                    agencia_bancaria = EXCLUDED.agencia_bancaria,
                    conta_bancaria = EXCLUDED.conta_bancaria,
                    tipo_conta = EXCLUDED.tipo_conta,
                    chave_pix = EXCLUDED.chave_pix,
                    titular_conta = EXCLUDED.titular_conta,
                    atualizado_em = CURRENT_TIMESTAMP",
                [
                    ':colaborador_id' => $collaboratorId,
                    ':tipo_cadastro' => $tipoCadastro,
                    ':foto_url' => $fotoUrl,
                    ':cpf' => $cpf,
                    ':rg' => $this->nullIfBlank($payload['rg'] ?? null),
                    ':data_nascimento' => $this->nullIfBlank($payload['data_nascimento'] ?? null),
                    ':telefone_principal' => $this->nullIfBlank($payload['telefone_principal'] ?? null),
                    ':telefone_familiar' => $this->nullIfBlank($payload['telefone_familiar'] ?? null),
                    ':cep' => $this->nullIfBlank($payload['cep'] ?? null),
                    ':logradouro' => $this->nullIfBlank($payload['logradouro'] ?? null),
                    ':numero' => $this->nullIfBlank($payload['numero'] ?? null),
                    ':bairro' => $this->nullIfBlank($payload['bairro'] ?? null),
                    ':complemento' => $this->nullIfBlank($payload['complemento'] ?? null),
                    ':cidade' => $this->nullIfBlank($payload['cidade'] ?? null),
                    ':uf' => $this->nullIfBlank($payload['uf'] ?? null),
                    ':endereco_completo' => $this->buildAddressLine($payload),
                    ':nome_mae' => $this->nullIfBlank($payload['nome_mae'] ?? null),
                    ':tipo_sanguineo' => $tipoSanguineo !== '' ? $tipoSanguineo : null,
                    ':fator_rh' => $fatorRh !== '' ? $fatorRh : null,
                    ':tipo_vinculo' => $this->nullIfBlank($payload['tipo_vinculo'] ?? null),
                    ':numero_admissao' => $this->nullIfBlank($payload['numero_admissao'] ?? null),
                    ':situacao' => $situacao,
                    ':banco_nome' => $this->nullIfBlank($payload['banco_nome'] ?? null),
                    ':agencia_bancaria' => $this->nullIfBlank($payload['agencia_bancaria'] ?? null),
                    ':conta_bancaria' => $this->nullIfBlank($payload['conta_bancaria'] ?? null),
                    ':tipo_conta' => $this->nullIfBlank($payload['tipo_conta'] ?? null),
                    ':chave_pix' => $this->nullIfBlank($payload['chave_pix'] ?? null),
                    ':titular_conta' => $this->nullIfBlank($payload['titular_conta'] ?? null),
                ]
            );

            if ($tipoCadastro === 'vigilante') {
                $cursoFormacao = strtolower(trim((string) ($payload['curso_formacao'] ?? 'nao'))) === 'sim';

                $vigilanteParams = [
                    ':usuario_id' => $target['usuario_id'],
                    ':cnh' => null,
                    ':validade_cnh' => null,
                    ':formacao' => $cursoFormacao ? 'Curso de formação concluído' : null,
                    ':validade_reciclagem' => null,
                    ':numero_cnv' => $this->nullIfBlank($payload['numero_cnv'] ?? null),
                    ':validade_cnv' => $this->nullIfBlank($payload['validade_cnv'] ?? null),
                    ':curso_formacao_concluido' => $cursoFormacao,
                    ':data_ultima_reciclagem' => $this->nullIfBlank($payload['data_ultima_reciclagem'] ?? null),
                    ':situacao_reciclagem' => $this->nullIfBlank($payload['situacao_reciclagem'] ?? null),
                    ':curso_escolta_armada' => in_array('escolta_armada', $outrosCursos, true),
                    ':curso_seguranca_eventos' => in_array('seguranca_eventos', $outrosCursos, true),
                    ':curso_seguranca_vip' => in_array('seguranca_vip', $outrosCursos, true),
                ];
                $vigilanteTypes = [
                    ':curso_formacao_concluido' => PDO::PARAM_BOOL,
                    ':curso_escolta_armada' => PDO::PARAM_BOOL,
                    ':curso_seguranca_eventos' => PDO::PARAM_BOOL,
                    ':curso_seguranca_vip' => PDO::PARAM_BOOL,
                ];

                $updated = $this->run(
                    "UPDATE vigilantes
                     SET cnh = :cnh,
                         validade_cnh = :validade_cnh,
                         formacao = :formacao,
                         validade_reciclagem = :validade_reciclagem,
                         numero_cnv = :numero_cnv,
                         validade_cnv = :validade_cnv,
                         curso_formacao_concluido = :curso_formacao_concluido,
                         data_ultima_reciclagem = :data_ultima_reciclagem,
                         situacao_reciclagem = :situacao_reciclagem,
                         curso_escolta_armada = :curso_escolta_armada,
                         curso_seguranca_eventos = :curso_seguranca_eventos,
                         curso_seguranca_vip = :curso_seguranca_vip
                     WHERE usuario_id = :usuario_id",
                    $vigilanteParams,
                    $vigilanteTypes
                );

                if ($updated->rowCount() === 0) {
                    $this->run(
                        "INSERT INTO vigilantes (
                            usuario_id,
                            cnh,
                            validade_cnh,
                            formacao,
                            validade_reciclagem,
                            numero_cnv,
                            validade_cnv,
                            curso_formacao_concluido,
                            data_ultima_reciclagem,
                            situacao_reciclagem,
                            curso_escolta_armada,
                            curso_seguranca_eventos,
                            curso_seguranca_vip
                         ) VALUES (
                            :usuario_id,
                            :cnh,
                            :validade_cnh,
                            :formacao,
                            :validade_reciclagem,
                            :numero_cnv,
                            :validade_cnv,
                            :curso_formacao_concluido,
                            :data_ultima_reciclagem,
                            :situacao_reciclagem,
                            :curso_escolta_armada,
                            :curso_seguranca_eventos,
                            :curso_seguranca_vip
                         )",
                        $vigilanteParams,
                        $vigilanteTypes
                    );
                }
            }

            $this->db->commit();

            return [
                'collaborator_id' => $collaboratorId,
                'photo_url' => $fotoUrl,
                'previous_photo_url' => $target['foto_url'] ?? null,
                'photo_changed' => ($target['foto_url'] ?? null) !== $fotoUrl,
            ];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

    public function getScaleEntries(DateTimeImmutable $startDate, DateTimeImmutable $endDate) {
        $rows = $this->fetchAll(
            "SELECT r.id, u.nome AS vigilante, r.data_inicio, r.status
             FROM rondas r
             JOIN vigilantes vg ON vg.id = r.vigilante_id
             JOIN usuarios u ON u.id = vg.usuario_id
             WHERE r.data_inicio::date BETWEEN :start_date AND :end_date
             ORDER BY r.data_inicio ASC",
            [
                ':start_date' => $startDate->format('Y-m-d'),
                ':end_date' => $endDate->format('Y-m-d'),
            ]
        );

        return array_map(function ($row) {
            $date = new DateTimeImmutable($row['data_inicio']);

            return [
                'id' => $row['id'],
                'vigilante' => $row['vigilante'],
                'data' => $date->format('Y-m-d'),
                'turno' => $this->formatShift($date),
                'status' => $this->formatRoundStatus($row['status']),
            ];
        }, $rows);
    }

    public function getVehicles() {
        $rows = $this->fetchAll(
            "SELECT id, placa, modelo, km_atual, km_prox_revisao, km_prox_troca_oleo, status
             FROM veiculos
             ORDER BY km_atual DESC, modelo ASC"
        );

        return array_map(function ($row) {
            return [
                'id' => $row['id'],
                'placa' => $row['placa'],
                'modelo' => $row['modelo'],
                'km_atual' => (int) $row['km_atual'],
                'prox_revisao' => $row['km_prox_revisao'] !== null ? (int) $row['km_prox_revisao'] : null,
                'prox_oleo' => $row['km_prox_troca_oleo'] !== null ? (int) $row['km_prox_troca_oleo'] : null,
                'status' => $this->formatVehicleStatus($row['status']),
            ];
        }, $rows);
    }

    public function getContracts() {
        $rows = $this->fetchAll(
            "SELECT c.id,
                    COALESCE(cli.nome_razao_social, u.nome, 'Sem vínculo') AS cliente,
                    c.tipo,
                    c.valor,
                    c.status,
                    COALESCE(c.data_fim, c.data_inicio) AS vencimento
             FROM contratos c
             LEFT JOIN clientes cli ON cli.id = c.cliente_id
             LEFT JOIN colaboradores col ON col.id = c.colaborador_id
             LEFT JOIN usuarios u ON u.id = col.usuario_id
             ORDER BY c.criado_em DESC"
        );

        return array_map(function ($row) {
            return [
                'id' => $row['id'],
                'cliente' => $row['cliente'],
                'tipo' => $this->formatContractType($row['tipo']),
                'valor' => $row['valor'] !== null ? (float) $row['valor'] : 0,
                'status' => $this->humanize($row['status']),
                'vencimento' => $row['vencimento'],
            ];
        }, $rows);
    }

    public function getFinancialSummary() {
        $receitasMes = (float) $this->fetchValue(
            "SELECT COALESCE(SUM(valor), 0)
             FROM financeiro
             WHERE LOWER(tipo) = 'receita'
               AND date_trunc('month', data_vencimento) = date_trunc('month', CURRENT_DATE)"
        );

        $despesasMes = (float) $this->fetchValue(
            "SELECT COALESCE(SUM(valor), 0)
             FROM financeiro
             WHERE LOWER(tipo) = 'despesa'
               AND date_trunc('month', data_vencimento) = date_trunc('month', CURRENT_DATE)"
        );

        $recebidoMes = (float) $this->fetchValue(
            "SELECT COALESCE(SUM(valor), 0)
             FROM financeiro
             WHERE LOWER(tipo) = 'receita'
               AND data_pagamento IS NOT NULL
               AND date_trunc('month', data_pagamento) = date_trunc('month', CURRENT_DATE)"
        );

        $aPagar = (float) $this->fetchValue(
            "SELECT COALESCE(SUM(valor), 0)
             FROM financeiro
             WHERE LOWER(tipo) = 'despesa'
               AND data_pagamento IS NULL"
        );

        $aReceber = (float) $this->fetchValue(
            "SELECT COALESCE(SUM(valor), 0)
             FROM financeiro
             WHERE LOWER(tipo) = 'receita'
               AND data_pagamento IS NULL"
        );

        $atrasados = (int) $this->fetchValue(
            "SELECT COUNT(*)
             FROM financeiro
             WHERE data_pagamento IS NULL
               AND data_vencimento < CURRENT_DATE"
        );

        return [
            'receitas_mes' => $receitasMes,
            'despesas_mes' => $despesasMes,
            'saldo_previsto_mes' => $receitasMes - $despesasMes,
            'recebido_mes' => $recebidoMes,
            'a_pagar' => $aPagar,
            'a_receber' => $aReceber,
            'atrasados' => $atrasados,
        ];
    }

    public function getFinancialEntries($limit = 20) {
        $rows = $this->fetchAll(
            "SELECT id, tipo, descricao, valor, data_vencimento, data_pagamento, status, criado_em
             FROM financeiro
             ORDER BY
                CASE
                    WHEN data_pagamento IS NULL AND data_vencimento < CURRENT_DATE THEN 0
                    WHEN data_pagamento IS NULL THEN 1
                    ELSE 2
                END,
                data_vencimento ASC,
                criado_em DESC
             LIMIT :limit",
            [':limit' => (int) $limit],
            [':limit' => PDO::PARAM_INT]
        );

        return array_map(function ($row) {
            return $this->mapFinancialEntry($row);
        }, $rows);
    }

    public function getUpcomingFinancialEntries($limit = 6) {
        $rows = $this->fetchAll(
            "SELECT id, tipo, descricao, valor, data_vencimento, data_pagamento, status, criado_em
             FROM financeiro
             WHERE data_pagamento IS NULL
             ORDER BY
                CASE WHEN data_vencimento < CURRENT_DATE THEN 0 ELSE 1 END,
                data_vencimento ASC,
                criado_em DESC
             LIMIT :limit",
            [':limit' => (int) $limit],
            [':limit' => PDO::PARAM_INT]
        );

        return array_map(function ($row) {
            return $this->mapFinancialEntry($row);
        }, $rows);
    }

    public function getChecklistVehicles() {
        return $this->fetchAll(
            "SELECT id, placa, modelo, km_atual, km_prox_troca_oleo
             FROM veiculos
             WHERE LOWER(COALESCE(status, 'disponivel')) = 'disponivel'
             ORDER BY modelo ASC, placa ASC"
        );
    }

    public function getActiveRoundByUserId($userId) {
        return $this->fetchOne(
            "SELECT r.id, r.data_inicio, r.status, u.nome AS vigilante, v.modelo, v.placa,
                    cv.km_inicial, cv.foto_painel_url, cv.combustivel_nivel
             FROM rondas r
             JOIN vigilantes vg ON vg.id = r.vigilante_id
             JOIN usuarios u ON u.id = vg.usuario_id
             LEFT JOIN veiculos v ON v.id = r.veiculo_id
             LEFT JOIN checklist_veiculos cv ON cv.ronda_id = r.id
             WHERE vg.usuario_id = :user_id
               AND r.status = 'em_andamento'
             ORDER BY r.data_inicio DESC
             LIMIT 1",
            [':user_id' => $userId]
        );
    }

    public function getRoundByIdForUser($roundId, $userId) {
        return $this->fetchOne(
            "SELECT r.id, r.data_inicio, r.status, u.nome AS vigilante, v.modelo, v.placa,
                    cv.km_inicial, cv.foto_painel_url, cv.combustivel_nivel
             FROM rondas r
             JOIN vigilantes vg ON vg.id = r.vigilante_id
             JOIN usuarios u ON u.id = vg.usuario_id
             LEFT JOIN veiculos v ON v.id = r.veiculo_id
             LEFT JOIN checklist_veiculos cv ON cv.ronda_id = r.id
             WHERE r.id = :round_id
               AND vg.usuario_id = :user_id
             LIMIT 1",
            [
                ':round_id' => $roundId,
                ':user_id' => $userId,
            ]
        );
    }

    public function startRoundFromChecklist($userId, $payload, $photoUrl = null) {
        $kmInicial = isset($payload['km_inicial']) ? (int) $payload['km_inicial'] : 0;
        $veiculoId = $payload['veiculo_id'] ?? '';

        if ($veiculoId === '' || $kmInicial <= 0) {
            throw new RuntimeException('Preencha a viatura e a quilometragem antes de iniciar a ronda.');
        }

        $vigilanteId = $this->findVigilanteIdByUserId($userId);
        if ($vigilanteId === null) {
            throw new RuntimeException('Seu usuário não possui cadastro de vigilante.');
        }

        try {
            $this->db->beginTransaction();

            $vehicle = $this->fetchOne(
                "SELECT id, km_atual, status
                 FROM veiculos
                 WHERE id = :id
                 FOR UPDATE",
                [':id' => $veiculoId]
            );

            if ($vehicle === null) {
                throw new RuntimeException('Veículo não encontrado.');
            }

            if (strtolower((string) $vehicle['status']) !== 'disponivel') {
                throw new RuntimeException('Este veículo não está disponível para iniciar uma nova ronda.');
            }

            if ($kmInicial < (int) $vehicle['km_atual']) {
                throw new RuntimeException('A quilometragem informada não pode ser menor que a atual do veículo.');
            }

            $round = $this->run(
                "INSERT INTO rondas (vigilante_id, veiculo_id, status)
                 VALUES (:vigilante_id, :veiculo_id, 'em_andamento')
                 RETURNING id, data_inicio, status",
                [
                    ':vigilante_id' => $vigilanteId,
                    ':veiculo_id' => $veiculoId,
                ]
            )->fetch();

            $this->run(
                "INSERT INTO checklist_veiculos (
                    vigilante_id,
                    veiculo_id,
                    ronda_id,
                    km_inicial,
                    foto_painel_url,
                    combustivel_nivel,
                    condicao_pneus,
                    condicao_iluminacao,
                    condicao_freios,
                    observacoes
                 ) VALUES (
                    :vigilante_id,
                    :veiculo_id,
                    :ronda_id,
                    :km_inicial,
                    :foto_painel_url,
                    :combustivel_nivel,
                    :condicao_pneus,
                    :condicao_iluminacao,
                    :condicao_freios,
                    :observacoes
                 )",
                [
                    ':vigilante_id' => $vigilanteId,
                    ':veiculo_id' => $veiculoId,
                    ':ronda_id' => $round['id'],
                    ':km_inicial' => $kmInicial,
                    ':foto_painel_url' => $photoUrl,
                    ':combustivel_nivel' => $payload['combustivel'] ?? null,
                    ':condicao_pneus' => $payload['pneus'] ?? null,
                    ':condicao_iluminacao' => $payload['iluminacao'] ?? null,
                    ':condicao_freios' => null,
                    ':observacoes' => null,
                ]
            );

            $this->run(
                "UPDATE veiculos
                 SET km_atual = :km_atual,
                     status = 'em_uso'
                 WHERE id = :veiculo_id",
                [
                    ':km_atual' => $kmInicial,
                    ':veiculo_id' => $veiculoId,
                ],
                [':km_atual' => PDO::PARAM_INT]
            );

            $this->db->commit();

            return $round;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

    public function finishRound($roundId, $userId) {
        $roundId = is_string($roundId) ? trim($roundId) : $roundId;

        if ($roundId === null || $roundId === '') {
            $activeRound = $this->getActiveRoundByUserId($userId);
            $roundId = $activeRound['id'] ?? null;
        }

        if ($roundId === null || $roundId === '') {
            throw new RuntimeException('Nenhuma ronda ativa foi encontrada para finalização.');
        }

        try {
            $this->db->beginTransaction();

            $round = $this->fetchOne(
                "SELECT r.id, r.veiculo_id, r.status
                 FROM rondas r
                 JOIN vigilantes vg ON vg.id = r.vigilante_id
                 WHERE r.id = :round_id
                   AND vg.usuario_id = :user_id
                 FOR UPDATE",
                [
                    ':round_id' => $roundId,
                    ':user_id' => $userId,
                ]
            );

            if ($round === null) {
                throw new RuntimeException('Ronda não encontrada para este vigilante.');
            }

            if (strtolower((string) $round['status']) !== 'em_andamento') {
                throw new RuntimeException('Esta ronda ja foi finalizada.');
            }

            $this->run(
                "UPDATE rondas
                 SET status = 'concluido',
                     data_fim = CURRENT_TIMESTAMP
                 WHERE id = :round_id",
                [':round_id' => $roundId]
            );

            if (!empty($round['veiculo_id'])) {
                $this->run(
                    "UPDATE veiculos
                     SET status = 'disponivel'
                     WHERE id = :veiculo_id",
                    [':veiculo_id' => $round['veiculo_id']]
                );
            }

            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

    public function registrarOcorrencia($rondaId, $dados) {
        return $this->run(
            "INSERT INTO ocorrencias (
                ronda_id, tipo, descricao, latitude, longitude, foto_url, video_url
            ) VALUES (
                :ronda_id, :tipo, :descricao, :latitude, :longitude, :foto_url, :video_url
            ) RETURNING id, data_hora",
            [
                ':ronda_id' => $rondaId,
                ':tipo' => $dados['tipo'] ?? 'outros',
                ':descricao' => $dados['descricao'] ?? '',
                ':latitude' => $dados['latitude'] ?? null,
                ':longitude' => $dados['longitude'] ?? null,
                ':foto_url' => $dados['foto_url'] ?? null,
                ':video_url' => $dados['video_url'] ?? null,
            ]
        )->fetch();
    }

    private function ensureCollaboratorRegistrationSchema() {
        $this->db->exec(
            "INSERT INTO perfis (nome) VALUES
                ('Coordenador Geral'),
                ('Administrador'),
                ('Colaborador Interno'),
                ('Vigilante')
             ON CONFLICT (nome) DO NOTHING"
        );

        $this->db->exec(
            "CREATE TABLE IF NOT EXISTS colaborador_detalhes (
                id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                colaborador_id UUID NOT NULL UNIQUE REFERENCES colaboradores(id) ON DELETE CASCADE,
                tipo_cadastro VARCHAR(50) NOT NULL DEFAULT 'financeiro_administrativo',
                foto_url VARCHAR(255),
                cpf VARCHAR(14) NOT NULL UNIQUE,
                rg VARCHAR(30),
                data_nascimento DATE,
                telefone_principal VARCHAR(20),
                telefone_familiar VARCHAR(20),
                cep VARCHAR(9),
                logradouro VARCHAR(150),
                numero VARCHAR(20),
                bairro VARCHAR(100),
                complemento VARCHAR(100),
                cidade VARCHAR(100),
                uf CHAR(2),
                endereco_completo TEXT,
                nome_mae VARCHAR(150),
                tipo_sanguineo VARCHAR(3),
                fator_rh VARCHAR(1),
                tipo_vinculo VARCHAR(30),
                numero_admissao VARCHAR(30),
                situacao VARCHAR(30) DEFAULT 'Ativo',
                banco_nome VARCHAR(100),
                agencia_bancaria VARCHAR(20),
                conta_bancaria VARCHAR(30),
                tipo_conta VARCHAR(30),
                chave_pix VARCHAR(150),
                titular_conta VARCHAR(150),
                criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )"
        );

        $this->db->exec("ALTER TABLE vigilantes ADD COLUMN IF NOT EXISTS numero_cnv VARCHAR(30)");
        $this->db->exec("ALTER TABLE vigilantes ADD COLUMN IF NOT EXISTS validade_cnv DATE");
        $this->db->exec("ALTER TABLE vigilantes ADD COLUMN IF NOT EXISTS curso_formacao_concluido BOOLEAN DEFAULT false");
        $this->db->exec("ALTER TABLE vigilantes ADD COLUMN IF NOT EXISTS data_ultima_reciclagem DATE");
        $this->db->exec("ALTER TABLE vigilantes ADD COLUMN IF NOT EXISTS situacao_reciclagem VARCHAR(30)");
        $this->db->exec("ALTER TABLE vigilantes ADD COLUMN IF NOT EXISTS curso_escolta_armada BOOLEAN DEFAULT false");
        $this->db->exec("ALTER TABLE vigilantes ADD COLUMN IF NOT EXISTS curso_seguranca_eventos BOOLEAN DEFAULT false");
        $this->db->exec("ALTER TABLE vigilantes ADD COLUMN IF NOT EXISTS curso_seguranca_vip BOOLEAN DEFAULT false");
        $this->db->exec("ALTER TABLE colaborador_detalhes ADD COLUMN IF NOT EXISTS banco_nome VARCHAR(100)");
        $this->db->exec("ALTER TABLE colaborador_detalhes ADD COLUMN IF NOT EXISTS agencia_bancaria VARCHAR(20)");
        $this->db->exec("ALTER TABLE colaborador_detalhes ADD COLUMN IF NOT EXISTS conta_bancaria VARCHAR(30)");
        $this->db->exec("ALTER TABLE colaborador_detalhes ADD COLUMN IF NOT EXISTS tipo_conta VARCHAR(30)");
        $this->db->exec("ALTER TABLE colaborador_detalhes ADD COLUMN IF NOT EXISTS chave_pix VARCHAR(150)");
        $this->db->exec("ALTER TABLE colaborador_detalhes ADD COLUMN IF NOT EXISTS titular_conta VARCHAR(150)");
    }

    private function findProfileIdByName($profileName) {
        $result = $this->fetchValue(
            "SELECT id FROM perfis WHERE nome = :nome LIMIT 1",
            [':nome' => $profileName]
        );

        return $result !== false ? (int) $result : null;
    }

    private function emailExists($email) {
        return (bool) $this->fetchValue(
            "SELECT 1 FROM usuarios WHERE email = :email LIMIT 1",
            [':email' => $email]
        );
    }

    private function emailExistsForOther($email, $userId) {
        return (bool) $this->fetchValue(
            "SELECT 1
             FROM usuarios
             WHERE email = :email
               AND id <> :user_id
             LIMIT 1",
            [
                ':email' => $email,
                ':user_id' => $userId,
            ]
        );
    }

    private function collaboratorCpfExists($cpf) {
        return (bool) $this->fetchValue(
            "SELECT 1 FROM colaborador_detalhes WHERE cpf = :cpf LIMIT 1",
            [':cpf' => $cpf]
        );
    }

    private function collaboratorCpfExistsForOther($cpf, $collaboratorId) {
        return (bool) $this->fetchValue(
            "SELECT 1
             FROM colaborador_detalhes
             WHERE cpf = :cpf
               AND colaborador_id <> :collaborator_id
             LIMIT 1",
            [
                ':cpf' => $cpf,
                ':collaborator_id' => $collaboratorId,
            ]
        );
    }

    private function normalizeRegistrationType($type) {
        $type = strtolower(trim((string) $type));

        return $type === 'vigilante' ? 'vigilante' : 'financeiro_administrativo';
    }

    private function normalizeEmploymentStatus($status) {
        $status = strtolower(trim((string) $status));

        $map = [
            'ativo' => 'Ativo',
            'inativo' => 'Inativo',
            'afastado' => 'Afastado',
        ];

        return $map[$status] ?? 'Ativo';
    }

    private function resolveCollaboratorRole($registrationType, $administrativeRole) {
        if ($registrationType === 'vigilante') {
            return 'Vigilante';
        }

        $role = strtolower(trim((string) $administrativeRole));

        if ($role === 'financeiro') {
            return 'Financeiro';
        }

        return 'Administrativo';
    }

    private function buildAddressLine(array $payload) {
        $parts = [
            trim((string) ($payload['logradouro'] ?? '')),
            trim((string) ($payload['numero'] ?? '')),
            trim((string) ($payload['bairro'] ?? '')),
            trim((string) ($payload['complemento'] ?? '')),
            trim((string) ($payload['cidade'] ?? '')),
            trim((string) ($payload['uf'] ?? '')),
            trim((string) ($payload['cep'] ?? '')),
        ];

        $parts = array_values(array_filter($parts, function ($value) {
            return $value !== '';
        }));

        return empty($parts) ? null : implode(', ', $parts);
    }

    private function normalizeDigits($value) {
        return preg_replace('/\D+/', '', (string) $value);
    }

    private function nullIfBlank($value) {
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }

    private function generateTemporaryPassword() {
        return strtoupper(substr(bin2hex(random_bytes(6)), 0, 10));
    }

    private function findVigilanteIdByUserId($userId) {
        $result = $this->fetchValue(
            "SELECT id FROM vigilantes WHERE usuario_id = :user_id LIMIT 1",
            [':user_id' => $userId]
        );

        return $result !== false ? $result : null;
    }

    private function cleanupLegacyMockCollaborator() {
        try {
            $mockUser = $this->fetchOne(
                "SELECT
                    u.id AS usuario_id,
                    v.id AS vigilante_id
                 FROM usuarios u
                 LEFT JOIN vigilantes v ON v.usuario_id = u.id
                 JOIN perfis p ON p.id = u.perfil_id
                 WHERE u.email = :email
                   AND p.nome = 'Vigilante'
                 LIMIT 1",
                [
                    ':email' => 'joao@selvaseguranca.com',
                ]
            );

            if ($mockUser === null) {
                return;
            }

            $this->db->beginTransaction();

            if (!empty($mockUser['vigilante_id'])) {
                $this->run(
                    "DELETE FROM ocorrencias
                     WHERE ronda_id IN (
                         SELECT id
                         FROM rondas
                         WHERE vigilante_id = :vigilante_id
                     )",
                    [':vigilante_id' => $mockUser['vigilante_id']]
                );

                $this->run(
                    "DELETE FROM checklist_veiculos
                     WHERE vigilante_id = :vigilante_id",
                    [':vigilante_id' => $mockUser['vigilante_id']]
                );

                $this->run(
                    "DELETE FROM rondas
                     WHERE vigilante_id = :vigilante_id",
                    [':vigilante_id' => $mockUser['vigilante_id']]
                );
            }

            $this->run(
                "DELETE FROM logs_auditoria
                 WHERE usuario_id = :usuario_id",
                [':usuario_id' => $mockUser['usuario_id']]
            );

            $this->run(
                "DELETE FROM usuarios
                 WHERE id = :usuario_id",
                [':usuario_id' => $mockUser['usuario_id']]
            );

            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            error_log('[PortalRepository] failed to clean legacy mock collaborator: ' . $e->getMessage());
        }
    }

    private function fetchAll($sql, $params = [], $types = []) {
        return $this->run($sql, $params, $types)->fetchAll();
    }

    private function fetchOne($sql, $params = [], $types = []) {
        $row = $this->run($sql, $params, $types)->fetch();
        return $row === false ? null : $row;
    }

    private function fetchValue($sql, $params = [], $types = []) {
        return $this->run($sql, $params, $types)->fetchColumn();
    }

    private function run($sql, $params = [], $types = []) {
        $stmt = $this->db->prepare($sql);

        foreach ($params as $key => $value) {
            $type = $types[$key] ?? (is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);

            if ($value === null) {
                $type = PDO::PARAM_NULL;
            }

            $stmt->bindValue($key, $value, $type);
        }

        $stmt->execute();

        return $stmt;
    }

    private function formatVehicleStatus($status) {
        $map = [
            'disponivel' => 'Disponível',
            'em_uso' => 'Em Uso',
            'manutencao' => 'Manutenção',
        ];

        $key = strtolower((string) $status);

        return $map[$key] ?? $this->humanize($key);
    }

    private function formatRoundStatus($status) {
        $map = [
            'em_andamento' => 'Em Rota',
            'concluido' => 'Concluído',
        ];

        $key = strtolower((string) $status);

        return $map[$key] ?? $this->humanize($key);
    }

    private function formatContractType($type) {
        $map = [
            'cliente' => 'Contrato com Cliente',
            'colaborador' => 'Contrato de Colaborador',
        ];

        $key = strtolower((string) $type);

        return $map[$key] ?? $this->humanize($key);
    }

    private function mapFinancialEntry($row) {
        $status = $this->resolveFinancialStatus(
            $row['status'] ?? '',
            $row['data_vencimento'] ?? null,
            $row['data_pagamento'] ?? null
        );

        return [
            'id' => $row['id'],
            'tipo' => strtolower((string) $row['tipo']),
            'tipo_label' => $this->formatFinancialType($row['tipo'] ?? ''),
            'descricao' => $row['descricao'],
            'valor' => $row['valor'] !== null ? (float) $row['valor'] : 0,
            'data_vencimento' => $row['data_vencimento'],
            'data_pagamento' => $row['data_pagamento'],
            'status' => $status,
            'status_label' => $this->formatFinancialStatus($status),
        ];
    }

    private function resolveFinancialStatus($status, $dueDate, $paymentDate) {
        $key = strtolower(trim((string) $status));

        if ($key === 'pago') {
            return 'pago';
        }

        if ($paymentDate !== null && $paymentDate !== '') {
            return 'pago';
        }

        if ($dueDate !== null && $dueDate !== '' && $dueDate < date('Y-m-d')) {
            return 'atrasado';
        }

        if ($key !== '') {
            return $key;
        }

        return 'pendente';
    }

    private function formatFinancialType($type) {
        $map = [
            'receita' => 'Receita',
            'despesa' => 'Despesa',
        ];

        $key = strtolower((string) $type);

        return $map[$key] ?? $this->humanize($key);
    }

    private function formatOccurrenceType($type) {
        $map = [
            'suspeita' => 'Atividade Suspeita',
            'invasao' => 'Invasão / Arrombamento',
            'veiculo_suspeito' => 'Veículo Suspeito',
            'pane' => 'Pane Mecânica / Elétrica',
            'apoio' => 'Solicitação de Apoio',
            'outros' => 'Outros',
        ];

        $key = strtolower((string) $type);

        return $map[$key] ?? $this->humanize($key);
    }

    private function formatFinancialStatus($status) {
        $map = [
            'pendente' => 'Pendente',
            'pago' => 'Pago',
            'atrasado' => 'Atrasado',
        ];

        $key = strtolower((string) $status);

        return $map[$key] ?? $this->humanize($key);
    }

    private function formatShift(DateTimeImmutable $date) {
        return ((int) $date->format('H') >= 18 || (int) $date->format('H') < 6) ? 'Noite' : 'Dia';
    }

    private function humanize($value) {
        $value = str_replace('_', ' ', strtolower((string) $value));
        return ucwords($value);
    }

    private function toBoolean($value) {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (int) $value !== 0;
        }

        return in_array(strtolower(trim((string) $value)), ['1', 't', 'true', 'yes', 'on'], true);
    }
}

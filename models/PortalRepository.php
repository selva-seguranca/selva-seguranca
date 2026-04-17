<?php

namespace Models;

use Config\Database;
use DateTimeImmutable;
use PDO;
use RuntimeException;
use Throwable;

class PortalRepository {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
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
                : 'Sem veiculo';
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
            $row['tipo_label'] = $this->humanize($row['tipo']);
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
            "SELECT u.id,
                    u.nome,
                    COALESCE(c.cargo, CASE WHEN p.nome = 'Vigilante' THEN 'Vigilante' ELSE p.nome END) AS cargo,
                    COALESCE(c.departamento, CASE WHEN p.nome = 'Vigilante' THEN 'Operacional' ELSE 'Administrativo' END) AS departamento,
                    CASE WHEN u.ativo THEN 'Ativo' ELSE 'Inativo' END AS status
             FROM usuarios u
             JOIN perfis p ON p.id = u.perfil_id
             LEFT JOIN colaboradores c ON c.usuario_id = u.id
             ORDER BY u.ativo DESC, u.nome ASC"
        );
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
                    COALESCE(cli.nome_razao_social, u.nome, 'Sem vinculo') AS cliente,
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
            throw new RuntimeException('Seu usuario nao possui cadastro de vigilante.');
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
                throw new RuntimeException('Veiculo nao encontrado.');
            }

            if (strtolower((string) $vehicle['status']) !== 'disponivel') {
                throw new RuntimeException('Este veiculo nao esta disponivel para iniciar uma nova ronda.');
            }

            if ($kmInicial < (int) $vehicle['km_atual']) {
                throw new RuntimeException('A quilometragem informada nao pode ser menor que a atual do veiculo.');
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
            throw new RuntimeException('Nenhuma ronda ativa foi encontrada para finalizacao.');
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
                throw new RuntimeException('Ronda nao encontrada para este vigilante.');
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

    private function findVigilanteIdByUserId($userId) {
        $result = $this->fetchValue(
            "SELECT id FROM vigilantes WHERE usuario_id = :user_id LIMIT 1",
            [':user_id' => $userId]
        );

        return $result !== false ? $result : null;
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
            'disponivel' => 'Disponivel',
            'em_uso' => 'Em Uso',
            'manutencao' => 'Manutencao',
        ];

        $key = strtolower((string) $status);

        return $map[$key] ?? $this->humanize($key);
    }

    private function formatRoundStatus($status) {
        $map = [
            'em_andamento' => 'Em Rota',
            'concluido' => 'Concluido',
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
}

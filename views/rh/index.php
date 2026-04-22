<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 flex items-center justify-between">
        <div>
            <p class="text-sm font-medium text-gray-500 mb-1">Colaboradores Ativos</p>
            <h3 class="text-3xl font-bold text-gray-800"><?= $kpis['total_ativos'] ?></h3>
        </div>
        <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center text-blue-600">
            <i class="ph ph-users text-2xl"></i>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 flex items-center justify-between">
        <div>
            <p class="text-sm font-medium text-gray-500 mb-1">Em Férias</p>
            <h3 class="text-3xl font-bold text-gray-800"><?= $kpis['em_ferias'] ?></h3>
        </div>
        <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center text-yellow-600">
            <i class="ph ph-sun text-2xl"></i>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 flex items-center justify-between">
        <div>
            <p class="text-sm font-medium text-gray-500 mb-1">Advertências Mês</p>
            <h3 class="text-3xl font-bold text-brand-red"><?= $kpis['advertencias_recentes'] ?></h3>
        </div>
        <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center text-brand-red">
            <i class="ph ph-warning-octagon text-2xl"></i>
        </div>
    </div>
</div>

<?php
    $statusClassMap = [
        'Ativo' => 'bg-green-100 text-green-800',
        'Inativo' => 'bg-gray-200 text-gray-800',
        'Afastado' => 'bg-yellow-100 text-yellow-800',
    ];
    $isCreateModalOpen = !empty($isCreateModalOpen);
    $isViewModalOpen = !empty($isViewModalOpen);
    $formMode = ($formMode ?? 'create') === 'edit' ? 'edit' : 'create';
    $isEditMode = $formMode === 'edit';
    $editCollaboratorId = trim((string) ($editCollaboratorId ?? ''));
    $actionSuccessMessage = trim((string) ($actionSuccess ?? ''));
    $isUpdateSuccessToast = $actionSuccessMessage === 'collaborator_updated';
    $actionSuccessHtml = $isUpdateSuccessToast
        ? 'ALTERAÇÕES SALVAS COM SUCESSO!'
        : htmlspecialchars($actionSuccessMessage);
    $moduleToneMap = [
        'seguranca_privada' => [
            'container' => 'border-red-100',
            'badge' => 'bg-red-50 text-brand-red border border-red-100',
            'avatar' => 'bg-red-100 text-brand-red',
            'role' => 'bg-red-50 text-brand-red border border-red-100',
        ],
        'servicos_terceirizacoes' => [
            'container' => 'border-blue-100',
            'badge' => 'bg-blue-50 text-blue-700 border border-blue-100',
            'avatar' => 'bg-blue-100 text-blue-700',
            'role' => 'bg-blue-50 text-blue-700 border border-blue-100',
        ],
    ];
    $advertenciasControl = is_array($advertenciasControl ?? null) ? $advertenciasControl : [];
    $advertenciaVigilantes = is_array($advertenciasControl['vigilantes'] ?? null) ? $advertenciasControl['vigilantes'] : [];
    $advertenciaOcorrencias = is_array($advertenciasControl['ocorrencias'] ?? null) ? $advertenciasControl['ocorrencias'] : [];
    $advertenciaHistorico = is_array($advertenciasControl['advertencias'] ?? null) ? $advertenciasControl['advertencias'] : [];
    $advertenciaResumo = is_array($advertenciasControl['resumo'] ?? null) ? $advertenciasControl['resumo'] : [];
    $advertenciaSuccessMessage = trim((string) ($advertenciaSuccess ?? ''));
    $advertenciaErrorMessage = trim((string) ($advertenciaError ?? ''));
    $motivosAdvertencia = [
        'Atraso ou falta injustificada',
        'Abandono de posto',
        'Descumprimento de procedimento',
        'Uso indevido de equipamento',
        'Falha na comunicação',
        'Conduta inadequada',
        'Negligência operacional',
        'Registro de ronda incompleto',
        'Outros',
    ];
    $formatRhDate = function ($value, $fallback = 'Não informado') {
        $value = trim((string) $value);
        if ($value === '') {
            return $fallback;
        }

        $datePart = substr($value, 0, 10);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $datePart) === 1) {
            try {
                return (new DateTimeImmutable($datePart))->format('d/m/Y');
            } catch (Throwable $e) {
            }
        }

        return $value;
    };
    $formatRhDateTime = function ($value, $fallback = 'Não informado') {
        $value = trim((string) $value);
        if ($value === '') {
            return $fallback;
        }

        try {
            return (new DateTimeImmutable($value))->format('d/m/Y H:i');
        } catch (Throwable $e) {
            return $value;
        }
    };
    $shortText = function ($value, $limit = 90) {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            return mb_strlen($value) > $limit ? mb_substr($value, 0, $limit - 3) . '...' : $value;
        }

        return strlen($value) > $limit ? substr($value, 0, $limit - 3) . '...' : $value;
    };
    $classificacaoToneMap = [
        'Leve' => 'bg-blue-50 text-blue-700 border-blue-100',
        'Média' => 'bg-amber-50 text-amber-700 border-amber-100',
        'Grave' => 'bg-red-50 text-brand-red border-red-100',
    ];
    $medidaToneMap = [
        'Advertência' => 'bg-gray-50 text-gray-700 border-gray-200',
        'Suspensão' => 'bg-orange-50 text-orange-700 border-orange-100',
        'Desligamento' => 'bg-red-50 text-brand-red border-red-100',
    ];
?>

<?php if (!empty($dbWarning)): ?>
    <div class="mb-6 rounded-2xl border border-yellow-200 bg-yellow-50 px-5 py-4 text-sm text-yellow-800">
        <div class="flex items-start gap-3">
            <i class="ph ph-warning-circle mt-0.5 text-lg"></i>
            <div>
                <p class="font-semibold">Aviso ao carregar RH</p>
                <p class="mt-1"><?= htmlspecialchars($dbWarning) ?></p>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($actionError)): ?>
    <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700">
        <div class="flex items-start gap-3">
            <i class="ph ph-warning-circle mt-0.5 text-lg"></i>
            <div>
                <p class="font-semibold">Não foi possível concluir a ação.</p>
                <p class="mt-1"><?= htmlspecialchars($actionError) ?></p>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($actionSuccessMessage !== '' && !$isUpdateSuccessToast): ?>
    <div class="mb-6 rounded-2xl border border-green-200 bg-green-50 px-5 py-4 text-sm text-green-800">
        <div class="flex items-start gap-3">
            <i class="ph ph-check-circle mt-0.5 text-lg"></i>
            <div>
                <p class="font-semibold"><?= $actionSuccessHtml ?></p>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($advertenciaErrorMessage !== ''): ?>
    <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700">
        <div class="flex items-start gap-3">
            <i class="ph ph-warning-circle mt-0.5 text-lg"></i>
            <div>
                <p class="font-semibold">Não foi possível registrar a advertência.</p>
                <p class="mt-1"><?= htmlspecialchars($advertenciaErrorMessage, ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($advertenciaSuccessMessage !== ''): ?>
    <div class="mb-6 rounded-2xl border border-green-200 bg-green-50 px-5 py-4 text-sm text-green-800">
        <div class="flex items-start gap-3">
            <i class="ph ph-check-circle mt-0.5 text-lg"></i>
            <div>
                <p class="font-semibold"><?= htmlspecialchars($advertenciaSuccessMessage, ENT_QUOTES, 'UTF-8') ?></p>
                <p class="mt-1 text-xs text-green-700">O registro foi vinculado à ocorrência selecionada e gravado no histórico do colaborador.</p>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($isUpdateSuccessToast): ?>
    <div
        id="rh-action-success-toast"
        class="fixed left-1/2 top-1/2 z-[80] w-[calc(100%-1.5rem)] max-w-xl -translate-x-1/2 -translate-y-1/2 rounded-2xl border border-green-200 bg-green-50 px-5 py-4 text-sm text-green-800 shadow-2xl transition-all duration-300"
        role="alert"
        aria-live="polite"
    >
        <div class="flex items-start gap-3">
            <span class="mt-0.5 inline-flex h-9 w-9 items-center justify-center rounded-full bg-green-100 text-green-700">
                <i class="ph ph-check-circle text-xl"></i>
            </span>
            <div class="min-w-0">
                <p class="font-semibold tracking-[0.04em]"><?= $actionSuccessHtml ?></p>
                <p class="mt-1 text-xs text-green-700">Os dados do colaborador foram atualizados no banco e o alerta será fechado automaticamente.</p>
            </div>
        </div>
    </div>
<?php endif; ?>

<section id="controle-advertencias" class="mb-8 rounded-3xl border border-red-100 bg-white shadow-sm">
    <div class="border-b border-gray-100 px-6 py-5">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-brand-red">Controle disciplinar</p>
                <h3 class="mt-2 text-2xl font-bold text-gray-950">Controle de Advertências</h3>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-gray-500">
                    Registre advertências formais, vincule à ocorrência real do sistema e mantenha histórico administrativo por vigilante.
                </p>
            </div>

            <div class="grid grid-cols-2 gap-2 text-center sm:grid-cols-4 xl:min-w-[460px]">
                <div class="rounded-2xl bg-gray-50 px-4 py-3">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-gray-400">Total</p>
                    <p class="mt-1 text-xl font-black text-gray-900"><?= (int) ($advertenciaResumo['total'] ?? 0) ?></p>
                </div>
                <div class="rounded-2xl bg-red-50 px-4 py-3">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-brand-red">Mês</p>
                    <p class="mt-1 text-xl font-black text-brand-red"><?= (int) ($advertenciaResumo['mes_atual'] ?? 0) ?></p>
                </div>
                <div class="rounded-2xl bg-orange-50 px-4 py-3">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-orange-700">Graves</p>
                    <p class="mt-1 text-xl font-black text-orange-700"><?= (int) ($advertenciaResumo['graves'] ?? 0) ?></p>
                </div>
                <div class="rounded-2xl bg-gray-950 px-4 py-3">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-gray-300">Evolução</p>
                    <p class="mt-1 text-xl font-black text-white"><?= (int) ($advertenciaResumo['evolucao'] ?? 0) ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-6 p-6 2xl:grid-cols-[minmax(0,1fr)_520px]">
        <form action="/rh/advertencias" method="POST" class="rounded-3xl border border-gray-200 bg-gray-50 p-5">
            <div class="flex items-start gap-3">
                <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-brand-red text-white">
                    <i class="ph ph-warning-octagon text-2xl"></i>
                </span>
                <div>
                    <h4 class="text-lg font-bold text-gray-900">Registrar advertência</h4>
                    <p class="mt-1 text-sm text-gray-500">Todos os campos são obrigatórios e a ocorrência deve pertencer ao vigilante selecionado.</p>
                </div>
            </div>

            <div class="mt-5 grid gap-4 lg:grid-cols-2">
                <div class="lg:col-span-2">
                    <label for="advertencia-colaborador" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Colaborador vigilante</label>
                    <select id="advertencia-colaborador" name="colaborador_id" required class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm outline-none transition-colors focus:border-brand-red">
                        <option value="">Selecione o vigilante</option>
                        <?php foreach ($advertenciaVigilantes as $vigilante): ?>
                            <option
                                value="<?= htmlspecialchars((string) ($vigilante['collaborator_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                data-vigilante-id="<?= htmlspecialchars((string) ($vigilante['vigilante_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                data-cpf="<?= htmlspecialchars((string) ($vigilante['cpf'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                            >
                                <?= htmlspecialchars((string) ($vigilante['nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" id="advertencia-vigilante-id" name="vigilante_id" value="">
                    <?php if (empty($advertenciaVigilantes)): ?>
                        <p class="mt-2 text-xs text-red-600">Nenhum vigilante disponível para advertência.</p>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="advertencia-cpf" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">CPF automático</label>
                    <input id="advertencia-cpf" type="text" readonly class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-500 outline-none" placeholder="Selecione o vigilante">
                </div>

                <div>
                    <label for="advertencia-posto" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Posto de serviço</label>
                    <input id="advertencia-posto" type="text" name="posto_servico" required maxlength="150" class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm outline-none transition-colors focus:border-brand-red" placeholder="Ex: Portaria principal">
                </div>

                <div>
                    <label for="advertencia-ocorrencia" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Vínculo com ocorrência do sistema</label>
                    <select id="advertencia-ocorrencia" name="ocorrencia_id" required class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm outline-none transition-colors focus:border-brand-red">
                        <option value="">Selecione primeiro o vigilante</option>
                        <?php foreach ($advertenciaOcorrencias as $ocorrencia): ?>
                            <?php
                                $occurrenceDate = substr((string) ($ocorrencia['data_hora'] ?? ''), 0, 10);
                                $occurrenceLabel = $formatRhDateTime($ocorrencia['data_hora'] ?? null)
                                    . ' - ' . (string) ($ocorrencia['tipo_label'] ?? 'Ocorrência')
                                    . ' - ' . $shortText($ocorrencia['descricao'] ?? '', 70);
                            ?>
                            <option
                                value="<?= htmlspecialchars((string) ($ocorrencia['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                data-vigilante-id="<?= htmlspecialchars((string) ($ocorrencia['vigilante_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                data-date="<?= htmlspecialchars($occurrenceDate, ENT_QUOTES, 'UTF-8') ?>"
                            >
                                <?= htmlspecialchars($occurrenceLabel, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p id="advertencia-ocorrencia-empty" class="mt-2 hidden text-xs text-red-600">Nenhuma ocorrência encontrada para este vigilante.</p>
                </div>

                <div>
                    <label for="advertencia-data-ocorrencia" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Data da ocorrência</label>
                    <input id="advertencia-data-ocorrencia" type="date" name="data_ocorrencia" readonly required class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-500 outline-none">
                </div>

                <div>
                    <label for="advertencia-data" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Data da advertência</label>
                    <input id="advertencia-data" type="date" name="data_advertencia" required value="<?= htmlspecialchars(date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>" class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm outline-none transition-colors focus:border-brand-red">
                </div>

                <div>
                    <label for="advertencia-tipo" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Tipo de advertência</label>
                    <select id="advertencia-tipo" name="tipo_advertencia" required class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm outline-none transition-colors focus:border-brand-red">
                        <option value="Escrita">Escrita</option>
                        <option value="Verbal">Verbal</option>
                    </select>
                </div>

                <div>
                    <label for="advertencia-motivo" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Motivo padronizado</label>
                    <select id="advertencia-motivo" name="motivo" required class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm outline-none transition-colors focus:border-brand-red">
                        <option value="">Selecione o motivo</option>
                        <?php foreach ($motivosAdvertencia as $motivoAdvertencia): ?>
                            <option value="<?= htmlspecialchars($motivoAdvertencia, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($motivoAdvertencia, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="advertencia-classificacao" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Classificação da falta</label>
                    <select id="advertencia-classificacao" name="classificacao_falta" required class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm outline-none transition-colors focus:border-brand-red">
                        <option value="Leve">Leve</option>
                        <option value="Média">Média</option>
                        <option value="Grave">Grave</option>
                    </select>
                </div>

                <div>
                    <label for="advertencia-medida" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Evolução disciplinar</label>
                    <select id="advertencia-medida" name="medida_disciplinar" required class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm outline-none transition-colors focus:border-brand-red">
                        <option value="Advertência">Advertência</option>
                        <option value="Suspensão">Suspensão</option>
                        <option value="Desligamento">Desligamento</option>
                    </select>
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Responsável pela aplicação</label>
                    <input type="text" readonly value="<?= htmlspecialchars((string) ($_SESSION['user_nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-500 outline-none">
                </div>

                <div class="lg:col-span-2">
                    <label for="advertencia-descricao" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Descrição detalhada</label>
                    <textarea id="advertencia-descricao" name="descricao" required rows="5" class="w-full resize-y rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm outline-none transition-colors focus:border-brand-red" placeholder="Descreva objetivamente o fato, impacto operacional e providências tomadas."></textarea>
                </div>
            </div>

            <button type="submit" class="mt-5 inline-flex w-full items-center justify-center rounded-2xl bg-brand-red px-5 py-3 text-sm font-bold text-white transition-colors hover:bg-red-700">
                <i class="ph ph-file-plus mr-2 text-lg"></i>
                Registrar advertência
            </button>
        </form>

        <div class="rounded-3xl border border-gray-200 bg-white p-5">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h4 class="text-lg font-bold text-gray-900">Histórico disciplinar</h4>
                    <p class="mt-1 text-sm text-gray-500">Últimos registros com vínculo de ocorrência para prova administrativa.</p>
                </div>
                <span class="inline-flex rounded-full border border-red-100 bg-red-50 px-3 py-1 text-xs font-semibold text-brand-red">
                    <?= count($advertenciaHistorico) ?> registro(s)
                </span>
            </div>

            <?php if (empty($advertenciaHistorico)): ?>
                <div class="mt-5 rounded-2xl border border-dashed border-gray-200 bg-gray-50 p-5 text-sm text-gray-500">
                    Nenhuma advertência registrada até o momento.
                </div>
            <?php else: ?>
                <div class="mt-5 max-h-[640px] space-y-3 overflow-y-auto pr-1">
                    <?php foreach ($advertenciaHistorico as $advertencia): ?>
                        <?php
                            $classificacao = (string) ($advertencia['classificacao_falta'] ?? '');
                            $medida = (string) ($advertencia['medida_disciplinar'] ?? '');
                        ?>
                        <article class="rounded-2xl border border-gray-200 bg-gray-50 p-4">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div class="min-w-0">
                                    <h5 class="font-bold text-gray-950"><?= htmlspecialchars((string) ($advertencia['colaborador_nome'] ?? 'Vigilante'), ENT_QUOTES, 'UTF-8') ?></h5>
                                    <p class="mt-1 text-xs text-gray-500">
                                        CPF <?= htmlspecialchars((string) ($advertencia['cpf'] ?? 'Não informado'), ENT_QUOTES, 'UTF-8') ?>
                                        <span class="mx-1 text-gray-300">|</span>
                                        Advertência em <?= htmlspecialchars($formatRhDate($advertencia['data_advertencia'] ?? null), ENT_QUOTES, 'UTF-8') ?>
                                    </p>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold <?= $classificacaoToneMap[$classificacao] ?? 'bg-gray-50 text-gray-700 border-gray-200' ?>">
                                        <?= htmlspecialchars($classificacao, ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                    <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold <?= $medidaToneMap[$medida] ?? 'bg-gray-50 text-gray-700 border-gray-200' ?>">
                                        <?= htmlspecialchars($medida, ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </div>
                            </div>

                            <div class="mt-3 grid gap-3 text-sm sm:grid-cols-2">
                                <div class="rounded-xl bg-white px-3 py-2">
                                    <p class="text-[10px] font-semibold uppercase tracking-wide text-gray-400">Motivo</p>
                                    <p class="mt-1 text-gray-800"><?= htmlspecialchars((string) ($advertencia['motivo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                                </div>
                                <div class="rounded-xl bg-white px-3 py-2">
                                    <p class="text-[10px] font-semibold uppercase tracking-wide text-gray-400">Posto / ocorrência</p>
                                    <p class="mt-1 text-gray-800">
                                        <?= htmlspecialchars((string) ($advertencia['posto_servico'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                        <span class="text-gray-400">-</span>
                                        <?= htmlspecialchars($formatRhDate($advertencia['data_ocorrencia'] ?? null), ENT_QUOTES, 'UTF-8') ?>
                                    </p>
                                </div>
                            </div>

                            <p class="mt-3 text-sm leading-6 text-gray-600"><?= htmlspecialchars($shortText($advertencia['descricao'] ?? '', 180), ENT_QUOTES, 'UTF-8') ?></p>

                            <div class="mt-3 rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs text-gray-500">
                                <p>
                                    Ocorrência vinculada:
                                    <span class="font-semibold text-gray-700"><?= htmlspecialchars((string) ($advertencia['ocorrencia_tipo_label'] ?? 'Ocorrência'), ENT_QUOTES, 'UTF-8') ?></span>
                                    em <?= htmlspecialchars($formatRhDateTime($advertencia['ocorrencia_data_hora'] ?? null), ENT_QUOTES, 'UTF-8') ?>.
                                </p>
                                <p class="mt-1">Responsável: <?= htmlspecialchars((string) ($advertencia['responsavel_nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<div class="mb-6 flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
    <div class="min-w-0">
        <h3 class="text-xl font-semibold text-gray-800">Módulos de Colaboradores</h3>
        <div class="mt-4 max-w-2xl">
            <label for="rh-collaborator-search" class="mb-2 block text-sm font-semibold text-gray-700">Pesquisar colaborador</label>
            <div class="relative">
                <i class="ph ph-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-lg text-gray-400"></i>
                <input
                    type="search"
                    id="rh-collaborator-search"
                    class="w-full rounded-2xl border border-gray-200 bg-white py-3 pl-12 pr-4 text-sm text-gray-800 shadow-sm outline-none transition-colors placeholder:text-gray-400 focus:border-brand-red focus:ring-2 focus:ring-red-100"
                    placeholder="Digite nome ou CPF"
                    autocomplete="off"
                >
            </div>
            <p class="mt-2 text-xs text-gray-500">A busca filtra por nome em tempo real e também localiza pelo CPF digitado.</p>
        </div>
    </div>
    <a
        href="/rh?modal=novo-colaborador"
        data-open-collaborator-modal
        class="inline-flex items-center justify-center rounded-lg bg-brand-red px-4 py-2 font-medium text-white shadow transition-colors hover:bg-red-700"
    >
        <i class="ph ph-plus-circle text-lg mr-2"></i>
        Novo Colaborador
    </a>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
    <?php foreach ($modulosRh as $modulo): ?>
        <?php $tone = $moduleToneMap[$modulo['slug']] ?? $moduleToneMap['seguranca_privada']; ?>
        <section class="bg-white rounded-2xl shadow-sm border overflow-hidden <?= $tone['container'] ?>" data-rh-module="<?= htmlspecialchars($modulo['slug'], ENT_QUOTES, 'UTF-8') ?>">
            <div class="border-b border-gray-100 px-6 py-5">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <div class="inline-flex items-center rounded-full px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] <?= $tone['badge'] ?>">
                            Módulo RH
                        </div>
                        <h4 class="mt-3 text-lg font-bold text-gray-900"><?= htmlspecialchars($modulo['title']) ?></h4>
                        <p class="mt-2 text-sm text-gray-500"><?= htmlspecialchars($modulo['subtitle']) ?></p>
                    </div>

                    <div class="rounded-2xl bg-gray-50 px-4 py-3 text-right">
                        <p class="text-xs uppercase tracking-wide text-gray-400">Colaboradores</p>
                        <p class="mt-1 text-2xl font-black text-gray-900"><?= count($modulo['colaboradores']) ?></p>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    <?php foreach ($modulo['areas'] as $area): ?>
                        <button
                            type="button"
                            data-rh-area-filter="<?= htmlspecialchars($area, ENT_QUOTES, 'UTF-8') ?>"
                            aria-pressed="false"
                            class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold transition-all <?= $tone['role'] ?>"
                        >
                            <?= htmlspecialchars($area) ?>
                            <span class="ml-2 rounded-full bg-white/80 px-2 py-0.5 text-[11px] font-bold text-gray-700">
                                <?= (int) ($modulo['area_counts'][$area] ?? 0) ?>
                            </span>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php if (empty($modulo['colaboradores'])): ?>
                <div class="p-6 text-sm text-gray-500">
                    Nenhum colaborador enquadrado neste módulo com base nos cargos e departamentos atuais.
                </div>
            <?php else: ?>
                <div class="hidden max-h-[430px] overflow-y-auto 2xl:block">
                    <table class="w-full text-left border-collapse">
                        <thead class="sticky top-0 z-10">
                            <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                                <th class="px-6 py-4 font-medium">Nome</th>
                                <th class="px-6 py-4 font-medium">Cargo</th>
                                <th class="px-6 py-4 font-medium">Status</th>
                                <th class="px-4 py-4 font-medium text-right whitespace-nowrap">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm">
                            <?php foreach ($modulo['colaboradores'] as $c): ?>
                            <?php $photoUrl = trim((string) ($c['foto_url'] ?? '')); ?>
                            <?php $searchCpf = preg_replace('/\D+/', '', (string) ($c['cpf'] ?? '')); ?>
                            <tr
                                class="hover:bg-gray-50 transition-colors"
                                data-rh-area-row="<?= htmlspecialchars((string) ($c['rh_area'] ?? 'Administrativo'), ENT_QUOTES, 'UTF-8') ?>"
                                data-rh-search-row
                                data-rh-search-name="<?= htmlspecialchars((string) ($c['nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                data-rh-search-cpf="<?= htmlspecialchars($searchCpf, ENT_QUOTES, 'UTF-8') ?>"
                            >
                                <td class="px-6 py-4 align-top font-medium text-gray-800">
                                    <div class="flex min-w-0 items-start gap-3">
                                        <div class="flex h-11 w-11 min-h-11 min-w-11 shrink-0 aspect-square items-center justify-center overflow-hidden rounded-full border border-white/80 text-xs font-bold shadow-sm <?= $photoUrl !== '' ? 'bg-white' : $tone['avatar'] ?>">
                                            <?php if ($photoUrl !== ''): ?>
                                                <img src="<?= htmlspecialchars($photoUrl) ?>" alt="Foto de <?= htmlspecialchars($c['nome']) ?>" class="block h-full w-full rounded-full object-cover" loading="lazy">
                                            <?php else: ?>
                                                <?= htmlspecialchars(substr($c['nome'], 0, 1)) ?>
                                            <?php endif; ?>
                                        </div>
                                        <span class="block min-w-0 whitespace-normal break-words leading-6"><?= htmlspecialchars($c['nome']) ?></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 align-top whitespace-normal break-words leading-6 text-gray-600"><?= htmlspecialchars($c['cargo']) ?></td>
                                <td class="px-6 py-4 align-top">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $statusClassMap[$c['status']] ?? 'bg-gray-200 text-gray-800' ?>">
                                        <?= htmlspecialchars($c['status']) ?>
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-right align-top whitespace-nowrap">
                                    <?php if (!empty($c['collaborator_id'])): ?>
                                        <div class="inline-flex min-w-[124px] items-center justify-end gap-2">
                                            <a
                                                href="/rh?view=<?= urlencode((string) $c['collaborator_id']) ?>"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition-colors hover:border-blue-200 hover:text-blue-600"
                                                title="Visualizar cadastro"
                                            >
                                                <i class="ph ph-eye text-lg"></i>
                                            </a>
                                            <a
                                                href="/rh?modal=editar-colaborador&edit=<?= urlencode((string) $c['collaborator_id']) ?>"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition-colors hover:border-amber-200 hover:text-amber-600"
                                                title="Editar cadastro"
                                            >
                                                <i class="ph ph-pencil-simple text-lg"></i>
                                            </a>
                                            <form method="POST" action="/rh/colaboradores/excluir" data-delete-collaborator-form class="inline-flex">
                                                <input type="hidden" name="colaborador_id" value="<?= htmlspecialchars((string) $c['collaborator_id']) ?>">
                                                <button
                                                    type="submit"
                                                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition-colors hover:border-red-200 hover:text-brand-red"
                                                    title="Excluir colaborador"
                                                >
                                                    <i class="ph ph-trash text-lg"></i>
                                                </button>
                                            </form>
                                        </div>
                                    <?php else: ?>
                                        <div class="inline-flex min-w-[124px] items-center justify-end gap-2">
                                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-300" title="Cadastro indisponível">
                                                <i class="ph ph-eye-slash text-lg"></i>
                                            </span>
                                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-300" title="Cadastro indisponível">
                                                <i class="ph ph-pencil-simple text-lg"></i>
                                            </span>
                                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-300" title="Cadastro indisponível">
                                                <i class="ph ph-trash text-lg"></i>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="max-h-[610px] space-y-4 overflow-y-auto p-4 2xl:hidden">
                    <?php foreach ($modulo['colaboradores'] as $c): ?>
                        <?php $photoUrl = trim((string) ($c['foto_url'] ?? '')); ?>
                        <?php $searchCpf = preg_replace('/\D+/', '', (string) ($c['cpf'] ?? '')); ?>
                        <article
                            class="rounded-2xl border border-gray-200 p-4 shadow-sm"
                            data-rh-area-row="<?= htmlspecialchars((string) ($c['rh_area'] ?? 'Administrativo'), ENT_QUOTES, 'UTF-8') ?>"
                            data-rh-search-row
                            data-rh-search-name="<?= htmlspecialchars((string) ($c['nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                            data-rh-search-cpf="<?= htmlspecialchars($searchCpf, ENT_QUOTES, 'UTF-8') ?>"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <div class="flex items-start">
                                        <div class="mr-3 flex h-11 w-11 min-h-11 min-w-11 shrink-0 aspect-square items-center justify-center overflow-hidden rounded-full border border-white/80 text-xs font-bold shadow-sm <?= $photoUrl !== '' ? 'bg-white' : $tone['avatar'] ?>">
                                            <?php if ($photoUrl !== ''): ?>
                                                <img src="<?= htmlspecialchars($photoUrl) ?>" alt="Foto de <?= htmlspecialchars($c['nome']) ?>" class="block h-full w-full rounded-full object-cover" loading="lazy">
                                            <?php else: ?>
                                                <?= htmlspecialchars(substr($c['nome'], 0, 1)) ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="min-w-0">
                                            <h5 class="text-sm font-semibold leading-6 text-gray-900 break-words"><?= htmlspecialchars($c['nome']) ?></h5>
                                            <p class="mt-1 text-xs leading-5 text-gray-500 break-words"><?= htmlspecialchars($c['cargo']) ?></p>
                                        </div>
                                    </div>
                                </div>
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium <?= $statusClassMap[$c['status']] ?? 'bg-gray-200 text-gray-800' ?>">
                                    <?= htmlspecialchars($c['status']) ?>
                                </span>
                            </div>

                            <div class="mt-4 text-sm">
                                <div class="rounded-xl bg-gray-50 px-3 py-2">
                                    <p class="text-xs uppercase tracking-wide text-gray-400">Cargo</p>
                                    <p class="mt-1 font-medium text-gray-700"><?= htmlspecialchars($c['cargo']) ?></p>
                                </div>
                            </div>

                            <div class="mt-4 flex justify-end gap-2">
                                <?php if (!empty($c['collaborator_id'])): ?>
                                    <a
                                        href="/rh?view=<?= urlencode((string) $c['collaborator_id']) ?>"
                                        class="inline-flex items-center justify-center rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-600 transition-colors hover:border-blue-200 hover:text-blue-600"
                                        title="Visualizar cadastro"
                                    >
                                        <i class="ph ph-eye text-lg"></i>
                                    </a>
                                    <a
                                        href="/rh?modal=editar-colaborador&edit=<?= urlencode((string) $c['collaborator_id']) ?>"
                                        class="inline-flex items-center justify-center rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-600 transition-colors hover:border-amber-200 hover:text-amber-600"
                                        title="Editar cadastro"
                                    >
                                        <i class="ph ph-pencil-simple text-lg"></i>
                                    </a>
                                    <form method="POST" action="/rh/colaboradores/excluir" data-delete-collaborator-form class="inline-flex">
                                        <input type="hidden" name="colaborador_id" value="<?= htmlspecialchars((string) $c['collaborator_id']) ?>">
                                        <button type="submit" class="inline-flex items-center justify-center rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-600 transition-colors hover:border-red-200 hover:text-brand-red" title="Excluir colaborador">
                                            <i class="ph ph-trash text-lg"></i>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="inline-flex items-center justify-center rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-300">
                                        <i class="ph ph-eye-slash text-lg"></i>
                                    </span>
                                    <span class="inline-flex items-center justify-center rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-300">
                                        <i class="ph ph-pencil-simple text-lg"></i>
                                    </span>
                                    <span class="inline-flex items-center justify-center rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-300">
                                        <i class="ph ph-trash text-lg"></i>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
                <div data-rh-area-empty class="hidden p-6 text-sm text-gray-500">
                    Nenhum colaborador encontrado com os filtros atuais.
                </div>
            <?php endif; ?>
        </section>
    <?php endforeach; ?>
</div>

<div
    id="collaborator-view-modal"
    class="fixed inset-0 z-40 <?= $isViewModalOpen ? 'flex' : 'hidden' ?> items-start justify-center overflow-y-auto bg-black/60 p-3 sm:items-center sm:p-5"
    aria-hidden="<?= $isViewModalOpen ? 'false' : 'true' ?>"
>
    <button
        type="button"
        data-close-collaborator-view
        class="absolute inset-0 h-full w-full cursor-default"
        aria-label="Fechar visualização"
    ></button>

    <section class="relative z-10 my-1 flex max-h-[calc(100dvh-1.5rem)] w-full max-w-[1120px] flex-col overflow-hidden rounded-[24px] bg-gray-50 shadow-2xl sm:max-h-[calc(100vh-4rem)] sm:rounded-[30px]">
        <header class="sticky top-0 z-20 flex items-center justify-between border-b border-gray-200 bg-white px-4 py-3 sm:px-5 sm:py-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-brand-red">RH / Colaborador</p>
                <h2 class="mt-1 text-xl font-bold text-gray-900 sm:text-2xl">Visualizar cadastro</h2>
            </div>

            <button
                type="button"
                data-close-collaborator-view
                class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-gray-200 text-gray-500 transition-colors hover:border-gray-300 hover:text-gray-800 sm:h-12 sm:w-12"
                aria-label="Fechar popup"
            >
                <i class="ph ph-x text-2xl"></i>
            </button>
        </header>

        <div class="flex-1 overflow-y-auto p-2.5 sm:p-5">
            <?php include __DIR__ . '/show.php'; ?>
        </div>
    </section>
</div>

<div
    id="collaborator-modal"
    data-modal-mode="<?= $isEditMode ? 'edit' : 'create' ?>"
    data-edit-id="<?= htmlspecialchars($editCollaboratorId, ENT_QUOTES, 'UTF-8') ?>"
    class="fixed inset-0 z-40 <?= $isCreateModalOpen ? 'flex' : 'hidden' ?> items-start justify-center overflow-y-auto bg-black/60 p-3 sm:items-center sm:p-5"
    aria-hidden="<?= $isCreateModalOpen ? 'false' : 'true' ?>"
>
    <button
        type="button"
        data-close-collaborator-modal
        class="absolute inset-0 h-full w-full cursor-default"
        aria-label="Fechar cadastro"
    ></button>

    <section class="relative z-10 my-1 flex max-h-[calc(100dvh-1.5rem)] w-full max-w-[1120px] flex-col overflow-hidden rounded-[24px] bg-gray-50 shadow-2xl sm:max-h-[calc(100vh-4rem)] sm:rounded-[30px]">
        <header class="sticky top-0 z-20 flex items-center justify-between border-b border-gray-200 bg-white px-4 py-3 sm:px-5 sm:py-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-brand-red">RH / Cadastro</p>
                <h2 class="mt-1 text-xl font-bold text-gray-900 sm:text-2xl"><?= $isEditMode ? 'Editar colaborador' : 'Novo colaborador' ?></h2>
            </div>

            <button
                type="button"
                data-close-collaborator-modal
                class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-gray-200 text-gray-500 transition-colors hover:border-gray-300 hover:text-gray-800 sm:h-12 sm:w-12"
                aria-label="Fechar popup"
            >
                <i class="ph ph-x text-2xl"></i>
            </button>
        </header>

        <div class="flex-1 overflow-y-auto p-2.5 sm:p-5">
            <?php include __DIR__ . '/create.php'; ?>
        </div>
    </section>
</div>

<div
    id="collaborator-delete-modal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 p-4"
    aria-hidden="true"
>
    <button
        type="button"
        data-close-collaborator-delete
        class="absolute inset-0 h-full w-full cursor-default"
        aria-label="Fechar confirmação de exclusão"
    ></button>

    <section class="relative z-10 w-full max-w-md rounded-[28px] border border-red-100 bg-white p-6 shadow-2xl sm:p-7">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-red-50 text-brand-red">
            <i class="ph ph-trash text-2xl"></i>
        </div>

        <div class="mt-5 text-center">
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-brand-red">Confirmar exclusão</p>
            <h3 class="mt-3 text-lg font-bold text-gray-900 sm:text-xl">TEM CERTEZA QUE DESEJA DELETAR ESSE COLABORADOR?</h3>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-2">
            <button
                type="button"
                id="collaborator-delete-confirm-button"
                class="inline-flex items-center justify-center rounded-xl bg-brand-red px-4 py-3 text-sm font-semibold text-white transition-colors hover:bg-red-700"
            >
                DELETAR
            </button>
            <button
                type="button"
                data-close-collaborator-delete
                class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-semibold text-gray-700 transition-colors hover:border-gray-300 hover:text-gray-900"
            >
                CANCELAR
            </button>
        </div>
    </section>
</div>

<script>
    (() => {
        const body = document.body;
        const collaboratorModal = document.getElementById('collaborator-modal');
        const collaboratorViewModal = document.getElementById('collaborator-view-modal');
        const collaboratorDeleteModal = document.getElementById('collaborator-delete-modal');
        const collaboratorOpeners = document.querySelectorAll('[data-open-collaborator-modal]');
        const collaboratorClosers = document.querySelectorAll('[data-close-collaborator-modal]');
        const collaboratorViewClosers = document.querySelectorAll('[data-close-collaborator-view]');
        const collaboratorDeleteClosers = document.querySelectorAll('[data-close-collaborator-delete]');
        const collaboratorDeleteForms = document.querySelectorAll('[data-delete-collaborator-form]');
        const collaboratorDeleteConfirmButton = document.getElementById('collaborator-delete-confirm-button');
        const areaFilterButtons = document.querySelectorAll('[data-rh-area-filter]');
        const collaboratorSearchInput = document.getElementById('rh-collaborator-search');
        const advertenciaColaboradorSelect = document.getElementById('advertencia-colaborador');
        const advertenciaVigilanteInput = document.getElementById('advertencia-vigilante-id');
        const advertenciaCpfInput = document.getElementById('advertencia-cpf');
        const advertenciaOcorrenciaSelect = document.getElementById('advertencia-ocorrencia');
        const advertenciaDataOcorrenciaInput = document.getElementById('advertencia-data-ocorrencia');
        const advertenciaOcorrenciaEmpty = document.getElementById('advertencia-ocorrencia-empty');
        const cropModal = document.getElementById('crop-modal');
        const successToast = document.getElementById('rh-action-success-toast');
        let pendingDeleteForm = null;

        if (!collaboratorModal) {
            return;
        }

        function isCropModalOpen() {
            return cropModal && !cropModal.classList.contains('hidden');
        }

        function isViewModalOpen() {
            return collaboratorViewModal && !collaboratorViewModal.classList.contains('hidden');
        }

        function isDeleteModalOpen() {
            return collaboratorDeleteModal && !collaboratorDeleteModal.classList.contains('hidden');
        }

        function syncBodyScroll() {
            body.classList.toggle(
                'overflow-hidden',
                !collaboratorModal.classList.contains('hidden') || isViewModalOpen() || isDeleteModalOpen() || isCropModalOpen()
            );
        }

        function syncModalUrl(isOpen) {
            const url = new URL(window.location.href);
            const modalMode = collaboratorModal.dataset.modalMode || 'create';
            const editId = collaboratorModal.dataset.editId || '';

            if (isOpen) {
                if (modalMode === 'edit' && editId !== '') {
                    url.searchParams.set('modal', 'editar-colaborador');
                    url.searchParams.set('edit', editId);
                } else {
                    url.searchParams.set('modal', 'novo-colaborador');
                    url.searchParams.delete('edit');
                }
            } else {
                url.searchParams.delete('modal');
                url.searchParams.delete('edit');
            }

            window.history.replaceState({}, '', url.toString());
        }

        function syncViewUrl(isOpen) {
            const url = new URL(window.location.href);

            if (!isOpen) {
                url.searchParams.delete('view');
            }

            window.history.replaceState({}, '', url.toString());
        }

        function setCollaboratorModalOpen(isOpen, syncUrl = true) {
            collaboratorModal.classList.toggle('hidden', !isOpen);
            collaboratorModal.classList.toggle('flex', isOpen);
            collaboratorModal.setAttribute('aria-hidden', isOpen ? 'false' : 'true');

            if (syncUrl) {
                syncModalUrl(isOpen);
            }

            syncBodyScroll();
        }

        function setCollaboratorViewModalOpen(isOpen, syncUrl = true) {
            if (!collaboratorViewModal) {
                return;
            }

            collaboratorViewModal.classList.toggle('hidden', !isOpen);
            collaboratorViewModal.classList.toggle('flex', isOpen);
            collaboratorViewModal.setAttribute('aria-hidden', isOpen ? 'false' : 'true');

            if (syncUrl) {
                syncViewUrl(isOpen);
            }

            syncBodyScroll();
        }

        function setCollaboratorDeleteModalOpen(isOpen) {
            if (!collaboratorDeleteModal) {
                return;
            }

            collaboratorDeleteModal.classList.toggle('hidden', !isOpen);
            collaboratorDeleteModal.classList.toggle('flex', isOpen);
            collaboratorDeleteModal.setAttribute('aria-hidden', isOpen ? 'false' : 'true');

            if (!isOpen) {
                pendingDeleteForm = null;
                if (collaboratorDeleteConfirmButton) {
                    collaboratorDeleteConfirmButton.disabled = false;
                }
            }

            syncBodyScroll();
        }

        collaboratorOpeners.forEach((trigger) => {
            trigger.addEventListener('click', (event) => {
                if ((collaboratorModal.dataset.modalMode || 'create') === 'edit') {
                    return;
                }

                event.preventDefault();
                collaboratorModal.dataset.modalMode = 'create';
                collaboratorModal.dataset.editId = '';
                setCollaboratorViewModalOpen(false);
                setCollaboratorModalOpen(true);
            });
        });

        collaboratorClosers.forEach((trigger) => {
            trigger.addEventListener('click', () => {
                setCollaboratorModalOpen(false);
            });
        });

        collaboratorViewClosers.forEach((trigger) => {
            trigger.addEventListener('click', () => {
                setCollaboratorViewModalOpen(false);
            });
        });

        collaboratorDeleteClosers.forEach((trigger) => {
            trigger.addEventListener('click', () => {
                setCollaboratorDeleteModalOpen(false);
            });
        });

        collaboratorDeleteForms.forEach((form) => {
            form.addEventListener('submit', (event) => {
                event.preventDefault();
                pendingDeleteForm = form;
                setCollaboratorDeleteModalOpen(true);
            });
        });

        if (collaboratorDeleteConfirmButton) {
            collaboratorDeleteConfirmButton.addEventListener('click', () => {
                if (!pendingDeleteForm) {
                    setCollaboratorDeleteModalOpen(false);
                    return;
                }

                collaboratorDeleteConfirmButton.disabled = true;
                pendingDeleteForm.submit();
            });
        }

        function syncAdvertenciaOccurrenceOptions() {
            if (!advertenciaColaboradorSelect || !advertenciaOcorrenciaSelect) {
                return;
            }

            const selectedOption = advertenciaColaboradorSelect.options[advertenciaColaboradorSelect.selectedIndex];
            const selectedVigilanteId = selectedOption ? (selectedOption.dataset.vigilanteId || '') : '';
            let visibleOccurrences = 0;

            if (advertenciaVigilanteInput) {
                advertenciaVigilanteInput.value = selectedVigilanteId;
            }

            if (advertenciaCpfInput) {
                advertenciaCpfInput.value = selectedOption && selectedOption.value ? (selectedOption.dataset.cpf || '') : '';
            }

            Array.from(advertenciaOcorrenciaSelect.options).forEach((option) => {
                if (option.value === '') {
                    option.hidden = false;
                    option.disabled = false;
                    option.textContent = selectedVigilanteId ? 'Selecione a ocorrência' : 'Selecione primeiro o vigilante';
                    return;
                }

                const isVisible = selectedVigilanteId !== '' && option.dataset.vigilanteId === selectedVigilanteId;
                option.hidden = !isVisible;
                option.disabled = !isVisible;

                if (isVisible) {
                    visibleOccurrences++;
                }
            });

            const selectedOccurrence = advertenciaOcorrenciaSelect.options[advertenciaOcorrenciaSelect.selectedIndex];
            if (selectedOccurrence && selectedOccurrence.value !== '' && selectedOccurrence.disabled) {
                advertenciaOcorrenciaSelect.value = '';
            }

            if (!selectedVigilanteId || visibleOccurrences === 0) {
                advertenciaOcorrenciaSelect.value = '';
            }

            if (advertenciaDataOcorrenciaInput && advertenciaOcorrenciaSelect.value === '') {
                advertenciaDataOcorrenciaInput.value = '';
            }

            if (advertenciaOcorrenciaEmpty) {
                advertenciaOcorrenciaEmpty.classList.toggle('hidden', !selectedVigilanteId || visibleOccurrences > 0);
            }
        }

        function syncAdvertenciaOccurrenceDate() {
            if (!advertenciaOcorrenciaSelect || !advertenciaDataOcorrenciaInput) {
                return;
            }

            const selectedOption = advertenciaOcorrenciaSelect.options[advertenciaOcorrenciaSelect.selectedIndex];
            advertenciaDataOcorrenciaInput.value = selectedOption && selectedOption.value ? (selectedOption.dataset.date || '') : '';
        }

        if (advertenciaColaboradorSelect) {
            advertenciaColaboradorSelect.addEventListener('change', () => {
                syncAdvertenciaOccurrenceOptions();
                syncAdvertenciaOccurrenceDate();
            });
        }

        if (advertenciaOcorrenciaSelect) {
            advertenciaOcorrenciaSelect.addEventListener('change', syncAdvertenciaOccurrenceDate);
        }

        function normalizeSearchText(value) {
            return String(value || '')
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .toLowerCase()
                .trim();
        }

        function normalizeSearchDigits(value) {
            return String(value || '').replace(/\D/g, '');
        }

        function getSearchState() {
            const rawSearch = collaboratorSearchInput ? collaboratorSearchInput.value : '';
            const text = normalizeSearchText(rawSearch);
            const digits = normalizeSearchDigits(rawSearch);

            return {
                text,
                digits,
                hasLetters: /[a-z]/.test(text),
                isActive: text !== '' || digits !== '',
            };
        }

        function rowMatchesSearch(row, searchState) {
            if (!searchState.isActive) {
                return true;
            }

            const rowName = normalizeSearchText(row.dataset.rhSearchName || '');
            const rowCpf = normalizeSearchDigits(row.dataset.rhSearchCpf || '');
            const matchesName = searchState.text !== '' && rowName.includes(searchState.text);
            const matchesCpf = searchState.digits !== '' && rowCpf.includes(searchState.digits);

            if (searchState.digits !== '' && !searchState.hasLetters) {
                return matchesCpf;
            }

            return matchesName || matchesCpf;
        }

        function syncAreaFilter(moduleElement, activeArea) {
            const rows = moduleElement.querySelectorAll('[data-rh-area-row]');
            const emptyState = moduleElement.querySelector('[data-rh-area-empty]');
            const searchState = getSearchState();
            let hasVisibleRows = false;

            rows.forEach((row) => {
                const matchesArea = activeArea === '' || row.dataset.rhAreaRow === activeArea;
                const isVisible = matchesArea && rowMatchesSearch(row, searchState);
                row.classList.toggle('hidden', !isVisible);

                if (isVisible) {
                    hasVisibleRows = true;
                }
            });

            moduleElement.querySelectorAll('[data-rh-area-filter]').forEach((button) => {
                const isActive = activeArea !== '' && button.dataset.rhAreaFilter === activeArea;
                button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                button.classList.toggle('ring-2', isActive);
                button.classList.toggle('ring-brand-red', isActive);
                button.classList.toggle('ring-offset-2', isActive);
            });

            if (emptyState) {
                emptyState.classList.toggle('hidden', hasVisibleRows);
            }
        }

        function syncAllRhFilters() {
            document.querySelectorAll('[data-rh-module]').forEach((moduleElement) => {
                syncAreaFilter(moduleElement, moduleElement.dataset.rhActiveArea || '');
            });
        }

        areaFilterButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const moduleElement = button.closest('[data-rh-module]');

                if (!moduleElement) {
                    return;
                }

                const selectedArea = button.dataset.rhAreaFilter || '';
                const currentArea = moduleElement.dataset.rhActiveArea || '';
                const nextArea = currentArea === selectedArea ? '' : selectedArea;

                moduleElement.dataset.rhActiveArea = nextArea;
                syncAreaFilter(moduleElement, nextArea);
            });
        });

        if (collaboratorSearchInput) {
            collaboratorSearchInput.addEventListener('input', syncAllRhFilters);
        }

        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') {
                return;
            }

            if (isCropModalOpen()) {
                return;
            }

            if (!collaboratorModal.classList.contains('hidden')) {
                setCollaboratorModalOpen(false);
                return;
            }

            if (isDeleteModalOpen()) {
                setCollaboratorDeleteModalOpen(false);
                return;
            }

            if (isViewModalOpen()) {
                setCollaboratorViewModalOpen(false);
            }
        });

        if (successToast) {
            window.setTimeout(() => {
                successToast.classList.add('pointer-events-none', 'scale-95', 'opacity-0');

                window.setTimeout(() => {
                    successToast.remove();
                }, 320);
            }, 4200);
        }

        syncBodyScroll();
        syncAdvertenciaOccurrenceOptions();
        syncAdvertenciaOccurrenceDate();
    })();
</script>

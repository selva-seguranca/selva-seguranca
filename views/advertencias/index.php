<?php
    $advertenciasControl = is_array($advertenciasControl ?? null) ? $advertenciasControl : [];
    $advertenciaVigilantes = is_array($advertenciasControl['vigilantes'] ?? null) ? $advertenciasControl['vigilantes'] : [];
    $advertenciaOcorrencias = is_array($advertenciasControl['ocorrencias'] ?? null) ? $advertenciasControl['ocorrencias'] : [];
    $advertenciaHistorico = is_array($advertenciasControl['advertencias'] ?? null) ? $advertenciasControl['advertencias'] : [];
    $ocorrenciaHistorico = is_array($advertenciasControl['ocorrencias_registradas'] ?? null) ? $advertenciasControl['ocorrencias_registradas'] : [];
    $advertenciaResumo = is_array($advertenciasControl['resumo'] ?? null) ? $advertenciasControl['resumo'] : [];
    $advertenciaSuccessMessage = trim((string) ($advertenciaSuccess ?? ''));
    $advertenciaErrorMessage = trim((string) ($advertenciaError ?? ''));
    $ocorrenciaSuccessMessage = trim((string) ($ocorrenciaSuccess ?? ''));
    $ocorrenciaErrorMessage = trim((string) ($ocorrenciaError ?? ''));
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
    $tiposOcorrencia = [
        'Atraso',
        'Falta',
        'Conduta inadequada',
        'Falha operacional',
        'Descumprimento de procedimento',
        'Abandono de posto',
        'Comunicação inadequada',
        'Outro',
    ];
    $formatDate = function ($value, $fallback = 'Não informado') {
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
    $formatDateTime = function ($value, $fallback = 'Não informado') {
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
    $shortText = function ($value, $limit = 120) {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            return mb_strlen($value) > $limit ? mb_substr($value, 0, $limit - 3) . '...' : $value;
        }

        return strlen($value) > $limit ? substr($value, 0, $limit - 3) . '...' : $value;
    };
    $buildInitials = function ($value) {
        $value = trim((string) $value);
        if ($value === '') {
            return 'V';
        }

        $parts = preg_split('/\s+/', $value) ?: [];
        $initials = '';
        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }

            $initials .= function_exists('mb_substr') ? mb_substr($part, 0, 1) : substr($part, 0, 1);
            if (strlen($initials) >= 2) {
                break;
            }
        }

        return strtoupper($initials !== '' ? $initials : 'V');
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
                <p class="font-semibold">Aviso ao carregar o módulo</p>
                <p class="mt-1"><?= htmlspecialchars($dbWarning, ENT_QUOTES, 'UTF-8') ?></p>
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

<?php if ($ocorrenciaErrorMessage !== ''): ?>
    <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700">
        <div class="flex items-start gap-3">
            <i class="ph ph-warning-circle mt-0.5 text-lg"></i>
            <div>
                <p class="font-semibold">Não foi possível salvar a ocorrência.</p>
                <p class="mt-1"><?= htmlspecialchars($ocorrenciaErrorMessage, ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($advertenciaSuccessMessage !== ''): ?>
    <div
        id="advertencia-success-toast"
        data-auto-dismiss-toast
        class="fixed left-1/2 top-1/2 z-[90] w-[calc(100%-1.5rem)] max-w-xl -translate-x-1/2 -translate-y-1/2 rounded-2xl border border-green-200 bg-green-50 px-5 py-4 text-sm text-green-800 shadow-2xl transition-all duration-300"
        role="alert"
        aria-live="polite"
    >
        <div class="flex items-start gap-3">
            <span class="mt-0.5 inline-flex h-9 w-9 items-center justify-center rounded-full bg-green-100 text-green-700">
                <i class="ph ph-check-circle text-xl"></i>
            </span>
            <div class="min-w-0">
                <p class="font-semibold tracking-[0.04em]"><?= htmlspecialchars($advertenciaSuccessMessage, ENT_QUOTES, 'UTF-8') ?></p>
                <p class="mt-1 text-xs text-green-700">O registro foi salvo no banco, o formulário foi resetado e o PDF ficou disponível no histórico.</p>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($ocorrenciaSuccessMessage !== ''): ?>
    <div
        id="ocorrencia-success-toast"
        data-auto-dismiss-toast
        class="fixed left-1/2 top-1/2 z-[90] w-[calc(100%-1.5rem)] max-w-xl -translate-x-1/2 -translate-y-1/2 rounded-2xl border border-green-200 bg-green-50 px-5 py-4 text-sm text-green-800 shadow-2xl transition-all duration-300"
        role="alert"
        aria-live="polite"
    >
        <div class="flex items-start gap-3">
            <span class="mt-0.5 inline-flex h-9 w-9 items-center justify-center rounded-full bg-green-100 text-green-700">
                <i class="ph ph-check-circle text-xl"></i>
            </span>
            <div class="min-w-0">
                <p class="font-semibold tracking-[0.04em]"><?= htmlspecialchars($ocorrenciaSuccessMessage, ENT_QUOTES, 'UTF-8') ?></p>
                <p class="mt-1 text-xs text-green-700">A ocorrência foi salva no banco, os campos foram limpos e o card já está disponível na lista.</p>
            </div>
        </div>
    </div>
<?php endif; ?>

<section class="rounded-3xl border border-red-100 bg-white shadow-sm">
    <div class="border-b border-gray-100 px-6 py-5">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-brand-red">Gestão disciplinar</p>
                <h2 class="mt-2 text-2xl font-bold text-gray-950">Ocorrências e Advertências</h2>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-gray-500">
                    Registre ocorrências administrativas e advertências formais, acompanhe os históricos e mantenha o controle disciplinar por vigilante.
                </p>
            </div>

            <div class="grid grid-cols-2 gap-2 text-center sm:grid-cols-4 xl:min-w-[460px]">
                <div class="rounded-2xl bg-gray-50 px-4 py-3">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-gray-400">Advertências</p>
                    <p class="mt-1 text-xl font-black text-gray-900"><?= (int) ($advertenciaResumo['advertencias_total'] ?? 0) ?></p>
                </div>
                <div class="rounded-2xl bg-blue-50 px-4 py-3">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-blue-700">Ocorrências</p>
                    <p class="mt-1 text-xl font-black text-blue-700"><?= (int) ($advertenciaResumo['ocorrencias_total'] ?? 0) ?></p>
                </div>
                <div class="rounded-2xl bg-red-50 px-4 py-3">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-brand-red">Graves</p>
                    <p class="mt-1 text-xl font-black text-brand-red"><?= (int) ($advertenciaResumo['graves_total'] ?? 0) ?></p>
                </div>
                <div class="rounded-2xl bg-gray-950 px-4 py-3">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-gray-300">Evolução</p>
                    <p class="mt-1 text-xl font-black text-white"><?= (int) ($advertenciaResumo['evolucao_total'] ?? 0) ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="flex flex-col gap-6 p-6">
        <div class="order-2 grid gap-6 2xl:grid-cols-[minmax(0,1fr)_520px]">
            <form id="advertencia-form" action="/advertencias" method="POST" class="rounded-3xl border border-gray-200 bg-gray-50 p-5">
                <div class="flex items-start gap-3">
                    <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-brand-red text-white">
                        <i class="ph ph-warning-octagon text-2xl"></i>
                    </span>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Registrar advertência</h3>
                        <p class="mt-1 text-sm text-gray-500">Todos os campos são obrigatórios e a ocorrência deve pertencer ao vigilante selecionado.</p>
                    </div>
                </div>

                <div class="mt-5 grid gap-4 lg:grid-cols-2">
                    <div class="lg:col-span-2">
                        <label for="advertencia-colaborador" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Colaborador vigilante</label>
                        <input
                            id="advertencia-colaborador"
                            type="text"
                            required
                            autocomplete="off"
                            aria-autocomplete="list"
                            aria-controls="advertencia-vigilantes-suggestions"
                            aria-expanded="false"
                            class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm outline-none transition-colors focus:border-brand-red"
                            placeholder="Digite o nome do vigilante"
                        >
                        <div id="advertencia-vigilantes-suggestions" class="mt-1 hidden overflow-hidden rounded-xl border border-gray-200 bg-white text-sm"></div>
                        <input type="hidden" id="advertencia-colaborador-id" name="colaborador_id" value="">
                        <input type="hidden" id="advertencia-vigilante-id" name="vigilante_id" value="">
                        <?php if (empty($advertenciaVigilantes)): ?>
                            <p class="mt-2 text-xs text-red-600">Nenhum vigilante disponível para advertência.</p>
                        <?php else: ?>
                            <p class="mt-2 text-xs text-gray-500">Digite o nome do vigilante para filtrar a lista e carregar CPF e ocorrências.</p>
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
                                    $occurrenceLabel = $formatDateTime($ocorrencia['data_hora'] ?? null)
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
                        <h3 class="text-lg font-bold text-gray-900">Histórico de advertências</h3>
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
                                        <h4 class="font-bold text-gray-950"><?= htmlspecialchars((string) ($advertencia['colaborador_nome'] ?? 'Vigilante'), ENT_QUOTES, 'UTF-8') ?></h4>
                                        <p class="mt-1 text-xs text-gray-500">
                                            CPF <?= htmlspecialchars((string) ($advertencia['cpf'] ?? 'Não informado'), ENT_QUOTES, 'UTF-8') ?>
                                            <span class="mx-1 text-gray-300">|</span>
                                            Advertência em <?= htmlspecialchars($formatDate($advertencia['data_advertencia'] ?? null), ENT_QUOTES, 'UTF-8') ?>
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
                                            <?= htmlspecialchars($formatDate($advertencia['data_ocorrencia'] ?? null), ENT_QUOTES, 'UTF-8') ?>
                                        </p>
                                    </div>
                                </div>

                                <p class="mt-3 text-sm leading-6 text-gray-600"><?= htmlspecialchars($shortText($advertencia['descricao'] ?? '', 180), ENT_QUOTES, 'UTF-8') ?></p>

                                <div class="mt-3 rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs text-gray-500">
                                    <p>
                                        Ocorrência vinculada:
                                        <span class="font-semibold text-gray-700"><?= htmlspecialchars((string) ($advertencia['ocorrencia_tipo_label'] ?? 'Ocorrência'), ENT_QUOTES, 'UTF-8') ?></span>
                                        em <?= htmlspecialchars($formatDateTime($advertencia['ocorrencia_data_hora'] ?? null), ENT_QUOTES, 'UTF-8') ?>.
                                    </p>
                                    <p class="mt-1">Responsável: <?= htmlspecialchars((string) ($advertencia['responsavel_nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                                </div>

                                <?php if (!empty($advertencia['arquivo_pdf_url'])): ?>
                                    <?php $pdfQuery = '/advertencias/pdf?id=' . rawurlencode((string) ($advertencia['id'] ?? '')); ?>
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        <a
                                            href="<?= htmlspecialchars($pdfQuery, ENT_QUOTES, 'UTF-8') ?>"
                                            target="_blank"
                                            rel="noopener"
                                            class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-gray-700 transition-colors hover:border-brand-red hover:text-brand-red"
                                        >
                                            <i class="ph ph-printer text-base"></i>
                                            Visualizar / imprimir PDF
                                        </a>
                                        <a
                                            href="<?= htmlspecialchars($pdfQuery . '&download=1', ENT_QUOTES, 'UTF-8') ?>"
                                            class="inline-flex items-center gap-1.5 rounded-xl bg-brand-red px-3 py-2 text-xs font-semibold text-white transition-colors hover:bg-red-700"
                                        >
                                            <i class="ph ph-download-simple text-base"></i>
                                            Baixar PDF
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <p class="mt-3 rounded-xl border border-dashed border-gray-200 bg-white px-3 py-2 text-xs text-gray-400">
                                        PDF ainda não disponível para este registro.
                                    </p>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="order-1 grid gap-6 2xl:grid-cols-[minmax(0,1fr)_520px]">
            <form id="ocorrencia-form" action="/advertencias/ocorrencias" method="POST" class="rounded-3xl border border-gray-200 bg-gray-50 p-5">
                <div class="flex items-start gap-3">
                    <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-blue-600 text-white">
                        <i class="ph ph-note-pencil text-2xl"></i>
                    </span>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Registrar ocorrência</h3>
                        <p class="mt-1 text-sm text-gray-500">Use este formulário para registrar ocorrências administrativas do vigilante, separadas da operação de ronda.</p>
                    </div>
                </div>

                <div class="mt-5 grid gap-4 lg:grid-cols-2">
                    <div class="lg:col-span-2">
                        <label for="ocorrencia-colaborador" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Colaborador vigilante</label>
                        <input
                            id="ocorrencia-colaborador"
                            type="text"
                            required
                            autocomplete="off"
                            aria-autocomplete="list"
                            aria-controls="ocorrencia-vigilantes-suggestions"
                            aria-expanded="false"
                            class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm outline-none transition-colors focus:border-blue-600"
                            placeholder="Digite o nome do vigilante"
                        >
                        <div id="ocorrencia-vigilantes-suggestions" class="mt-1 hidden overflow-hidden rounded-xl border border-gray-200 bg-white text-sm"></div>
                        <input type="hidden" id="ocorrencia-colaborador-id" name="colaborador_id" value="">
                        <input type="hidden" id="ocorrencia-vigilante-id" name="vigilante_id" value="">
                        <?php if (empty($advertenciaVigilantes)): ?>
                            <p class="mt-2 text-xs text-red-600">Nenhum vigilante disponível para ocorrência.</p>
                        <?php else: ?>
                            <p class="mt-2 text-xs text-gray-500">Digite o nome do vigilante para filtrar a lista e carregar o CPF automaticamente.</p>
                        <?php endif; ?>
                    </div>

                    <div>
                        <label for="ocorrencia-cpf" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">CPF automático</label>
                        <input id="ocorrencia-cpf" type="text" readonly class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-500 outline-none" placeholder="Selecione o vigilante">
                    </div>

                    <div>
                        <label for="ocorrencia-posto" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Posto de serviço</label>
                        <input id="ocorrencia-posto" type="text" name="posto_servico" required maxlength="150" class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm outline-none transition-colors focus:border-blue-600" placeholder="Ex: Portaria principal">
                    </div>

                    <div>
                        <label for="ocorrencia-data" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Data da ocorrência</label>
                        <input id="ocorrencia-data" type="date" name="data_ocorrencia" required value="<?= htmlspecialchars(date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>" class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm outline-none transition-colors focus:border-blue-600">
                    </div>

                    <div>
                        <label for="ocorrencia-tipo" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Tipo da ocorrência</label>
                        <input id="ocorrencia-tipo" type="text" name="tipo_ocorrencia" list="ocorrencia-tipos-list" required maxlength="80" class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm outline-none transition-colors focus:border-blue-600" placeholder="Ex: Falha operacional">
                        <datalist id="ocorrencia-tipos-list">
                            <?php foreach ($tiposOcorrencia as $tipoOcorrencia): ?>
                                <option value="<?= htmlspecialchars($tipoOcorrencia, ENT_QUOTES, 'UTF-8') ?>"></option>
                            <?php endforeach; ?>
                        </datalist>
                    </div>

                    <div>
                        <label for="ocorrencia-classificacao" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Classificação da ocorrência</label>
                        <select id="ocorrencia-classificacao" name="classificacao" required class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm outline-none transition-colors focus:border-blue-600">
                            <option value="Leve">Leve</option>
                            <option value="Média">Média</option>
                            <option value="Grave">Grave</option>
                        </select>
                    </div>

                    <div class="lg:col-span-2">
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Responsável pelo registro</label>
                        <input type="text" readonly value="<?= htmlspecialchars((string) ($_SESSION['user_nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-500 outline-none">
                    </div>

                    <div class="lg:col-span-2">
                        <label for="ocorrencia-descricao" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Descrição detalhada</label>
                        <textarea id="ocorrencia-descricao" name="descricao" required rows="5" class="w-full resize-y rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm outline-none transition-colors focus:border-blue-600" placeholder="Descreva a ocorrência, o impacto e o contexto do fato registrado."></textarea>
                    </div>
                </div>

                <button type="submit" class="mt-5 inline-flex w-full items-center justify-center rounded-2xl bg-blue-600 px-5 py-3 text-sm font-bold uppercase tracking-[0.04em] text-white transition-colors hover:bg-blue-700">
                    <i class="ph ph-floppy-disk mr-2 text-lg"></i>
                    Salvar ocorrência
                </button>
            </form>

            <div class="rounded-3xl border border-gray-200 bg-white p-5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Ocorrências registradas</h3>
                        <p class="mt-1 text-sm text-gray-500">Cards com os registros administrativos salvos no banco de dados.</p>
                    </div>
                    <span class="inline-flex rounded-full border border-blue-100 bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                        <?= count($ocorrenciaHistorico) ?> registro(s)
                    </span>
                </div>

                <?php if (empty($ocorrenciaHistorico)): ?>
                    <div class="mt-5 rounded-2xl border border-dashed border-gray-200 bg-gray-50 p-5 text-sm text-gray-500">
                        Nenhuma ocorrência registrada até o momento.
                    </div>
                <?php else: ?>
                    <div class="mt-5 max-h-[640px] space-y-3 overflow-y-auto pr-1">
                        <?php foreach ($ocorrenciaHistorico as $ocorrenciaRegistro): ?>
                            <?php
                                $classificacaoOcorrencia = (string) ($ocorrenciaRegistro['classificacao'] ?? '');
                                $fotoUrl = trim((string) ($ocorrenciaRegistro['foto_url'] ?? ''));
                                $nomeColaborador = (string) ($ocorrenciaRegistro['colaborador_nome'] ?? 'Vigilante');
                                $cargoColaborador = trim((string) ($ocorrenciaRegistro['cargo'] ?? 'Vigilante'));
                            ?>
                            <article class="rounded-2xl border border-gray-200 bg-gray-50 p-4">
                                <div class="flex gap-4">
                                    <?php if ($fotoUrl !== ''): ?>
                                        <img src="<?= htmlspecialchars($fotoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($nomeColaborador, ENT_QUOTES, 'UTF-8') ?>" class="h-16 w-16 rounded-2xl object-cover">
                                    <?php else: ?>
                                        <div class="inline-flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-blue-100 text-lg font-black text-blue-700">
                                            <?= htmlspecialchars($buildInitials($nomeColaborador), ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                    <?php endif; ?>

                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                            <div class="min-w-0">
                                                <h4 class="font-bold text-gray-950"><?= htmlspecialchars($nomeColaborador, ENT_QUOTES, 'UTF-8') ?></h4>
                                                <p class="mt-1 text-xs text-gray-500">
                                                    CPF <?= htmlspecialchars((string) ($ocorrenciaRegistro['cpf'] ?? 'Não informado'), ENT_QUOTES, 'UTF-8') ?>
                                                    <span class="mx-1 text-gray-300">|</span>
                                                    <?= htmlspecialchars($cargoColaborador !== '' ? $cargoColaborador : 'Vigilante', ENT_QUOTES, 'UTF-8') ?>
                                                </p>
                                            </div>
                                            <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold <?= $classificacaoToneMap[$classificacaoOcorrencia] ?? 'bg-gray-50 text-gray-700 border-gray-200' ?>">
                                                <?= htmlspecialchars($classificacaoOcorrencia !== '' ? $classificacaoOcorrencia : 'Leve', ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        </div>

                                        <div class="mt-3 grid gap-3 text-sm sm:grid-cols-2">
                                            <div class="rounded-xl bg-white px-3 py-2">
                                                <p class="text-[10px] font-semibold uppercase tracking-wide text-gray-400">Tipo da ocorrência</p>
                                                <p class="mt-1 text-gray-800"><?= htmlspecialchars((string) ($ocorrenciaRegistro['tipo_ocorrencia'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                                            </div>
                                            <div class="rounded-xl bg-white px-3 py-2">
                                                <p class="text-[10px] font-semibold uppercase tracking-wide text-gray-400">Posto / data</p>
                                                <p class="mt-1 text-gray-800">
                                                    <?= htmlspecialchars((string) ($ocorrenciaRegistro['posto_servico'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                                    <span class="text-gray-400">-</span>
                                                    <?= htmlspecialchars($formatDate($ocorrenciaRegistro['data_ocorrencia'] ?? null), ENT_QUOTES, 'UTF-8') ?>
                                                </p>
                                            </div>
                                        </div>

                                        <p class="mt-3 text-sm leading-6 text-gray-600"><?= htmlspecialchars($shortText($ocorrenciaRegistro['descricao'] ?? '', 180), ENT_QUOTES, 'UTF-8') ?></p>
                                        <p class="mt-3 text-xs text-gray-500">Responsável: <?= htmlspecialchars((string) ($ocorrenciaRegistro['responsavel_nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<script>
    (() => {
        const successToasts = Array.from(document.querySelectorAll('[data-auto-dismiss-toast]'));
        const warningForm = document.getElementById('advertencia-form');
        const warningOccurrenceSelect = document.getElementById('advertencia-ocorrencia');
        const warningOccurrenceDateInput = document.getElementById('advertencia-data-ocorrencia');
        const warningOccurrenceEmpty = document.getElementById('advertencia-ocorrencia-empty');
        const collaborators = <?= json_encode(array_map(static function ($vigilante) {
            return [
                'name' => (string) ($vigilante['nome'] ?? ''),
                'collaboratorId' => (string) ($vigilante['collaborator_id'] ?? ''),
                'vigilanteId' => (string) ($vigilante['vigilante_id'] ?? ''),
                'cpf' => (string) ($vigilante['cpf'] ?? ''),
            ];
        }, $advertenciaVigilantes), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?: '[]' ?>;

        function normalizeText(value) {
            return String(value || '')
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .toLowerCase()
                .trim();
        }

        function findSelectedCollaborator(rawValue) {
            const typedName = normalizeText(rawValue);

            if (typedName === '') {
                return null;
            }

            const exactMatch = collaborators.find((collaborator) => normalizeText(collaborator.name) === typedName);
            if (exactMatch) {
                return exactMatch;
            }

            const prefixMatches = collaborators.filter((collaborator) => normalizeText(collaborator.name).startsWith(typedName));
            if (prefixMatches.length === 1) {
                return prefixMatches[0];
            }

            const partialMatches = collaborators.filter((collaborator) => normalizeText(collaborator.name).includes(typedName));
            return partialMatches.length === 1 ? partialMatches[0] : null;
        }

        function getMatchingCollaborators(rawValue) {
            const typedName = normalizeText(rawValue);

            if (typedName === '') {
                return [];
            }

            const startsWithMatches = collaborators.filter((collaborator) => normalizeText(collaborator.name).startsWith(typedName));
            const containsMatches = collaborators.filter((collaborator) => {
                const normalizedName = normalizeText(collaborator.name);
                return normalizedName.includes(typedName) && !normalizedName.startsWith(typedName);
            });

            return [...startsWithMatches, ...containsMatches].slice(0, 8);
        }

        function bindCollaboratorLookup(config) {
            const form = document.getElementById(config.formId);
            const input = document.getElementById(config.inputId);
            const suggestions = document.getElementById(config.suggestionsId);
            const collaboratorIdInput = document.getElementById(config.collaboratorIdId);
            const vigilanteIdInput = document.getElementById(config.vigilanteIdId);
            const cpfInput = document.getElementById(config.cpfInputId);
            let currentSuggestions = [];
            let activeSuggestionIndex = -1;

            if (!input) {
                return {
                    sync() {
                        return null;
                    },
                };
            }

            function hideSuggestions() {
                currentSuggestions = [];
                activeSuggestionIndex = -1;

                if (!suggestions) {
                    return;
                }

                suggestions.classList.add('hidden');
                suggestions.innerHTML = '';
                input.setAttribute('aria-expanded', 'false');
            }

            function updateActiveSuggestion() {
                if (!suggestions) {
                    return;
                }

                Array.from(suggestions.querySelectorAll('button')).forEach((button, index) => {
                    const isActive = index === activeSuggestionIndex;
                    button.classList.toggle('bg-red-50', isActive);
                    button.classList.toggle('text-brand-red', isActive);
                });
            }

            function sync() {
                const selectedCollaborator = findSelectedCollaborator(input.value);

                if (collaboratorIdInput) {
                    collaboratorIdInput.value = selectedCollaborator ? selectedCollaborator.collaboratorId : '';
                }

                if (vigilanteIdInput) {
                    vigilanteIdInput.value = selectedCollaborator ? selectedCollaborator.vigilanteId : '';
                }

                if (cpfInput) {
                    cpfInput.value = selectedCollaborator ? selectedCollaborator.cpf : '';
                }

                input.setCustomValidity('');

                if (typeof config.onChange === 'function') {
                    config.onChange(selectedCollaborator);
                }

                return selectedCollaborator;
            }

            function selectCollaborator(collaborator) {
                if (!collaborator) {
                    return;
                }

                input.value = collaborator.name;
                hideSuggestions();
                sync();
            }

            function renderSuggestions() {
                if (!suggestions) {
                    return;
                }

                currentSuggestions = getMatchingCollaborators(input.value);
                activeSuggestionIndex = -1;
                suggestions.innerHTML = '';

                if (currentSuggestions.length === 0) {
                    hideSuggestions();
                    return;
                }

                currentSuggestions.forEach((collaborator) => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'block w-full px-4 py-2.5 text-left font-semibold text-gray-800 transition-colors hover:bg-red-50 focus:bg-red-50 focus:outline-none';
                    button.textContent = collaborator.name;
                    button.addEventListener('mousedown', (event) => event.preventDefault());
                    button.addEventListener('click', () => selectCollaborator(collaborator));
                    suggestions.appendChild(button);
                });

                suggestions.classList.remove('hidden');
                input.setAttribute('aria-expanded', 'true');
            }

            input.addEventListener('input', () => {
                renderSuggestions();
                sync();
            });

            input.addEventListener('change', sync);
            input.addEventListener('focus', renderSuggestions);
            input.addEventListener('blur', () => {
                window.setTimeout(hideSuggestions, 120);
            });

            input.addEventListener('keydown', (event) => {
                if (!suggestions || suggestions.classList.contains('hidden')) {
                    return;
                }

                if (event.key === 'Escape') {
                    hideSuggestions();
                    return;
                }

                if (event.key === 'ArrowDown') {
                    event.preventDefault();
                    activeSuggestionIndex = (activeSuggestionIndex + 1) % currentSuggestions.length;
                    updateActiveSuggestion();
                    return;
                }

                if (event.key === 'ArrowUp') {
                    event.preventDefault();
                    activeSuggestionIndex = activeSuggestionIndex <= 0 ? currentSuggestions.length - 1 : activeSuggestionIndex - 1;
                    updateActiveSuggestion();
                    return;
                }

                if (event.key === 'Enter' && activeSuggestionIndex >= 0) {
                    event.preventDefault();
                    selectCollaborator(currentSuggestions[activeSuggestionIndex]);
                }
            });

            document.addEventListener('mousedown', (event) => {
                if (
                    suggestions
                    && !input.contains(event.target)
                    && !suggestions.contains(event.target)
                ) {
                    hideSuggestions();
                }
            });

            if (form) {
                form.addEventListener('submit', (event) => {
                    const selectedCollaborator = sync();

                    if (selectedCollaborator) {
                        return;
                    }

                    event.preventDefault();
                    input.setCustomValidity(config.invalidMessage);
                    input.reportValidity();
                });
            }

            sync();

            return { sync };
        }

        function syncWarningOccurrenceOptions(selectedCollaborator) {
            if (!warningOccurrenceSelect) {
                return;
            }

            const selectedVigilanteId = selectedCollaborator ? selectedCollaborator.vigilanteId : '';
            let visibleOccurrences = 0;

            Array.from(warningOccurrenceSelect.options).forEach((option) => {
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

            const selectedOccurrence = warningOccurrenceSelect.options[warningOccurrenceSelect.selectedIndex];
            if (selectedOccurrence && selectedOccurrence.value !== '' && selectedOccurrence.disabled) {
                warningOccurrenceSelect.value = '';
            }

            if (!selectedVigilanteId || visibleOccurrences === 0) {
                warningOccurrenceSelect.value = '';
            }

            if (warningOccurrenceDateInput && warningOccurrenceSelect.value === '') {
                warningOccurrenceDateInput.value = '';
            }

            if (warningOccurrenceEmpty) {
                warningOccurrenceEmpty.classList.toggle('hidden', !selectedVigilanteId || visibleOccurrences > 0);
            }
        }

        function syncWarningOccurrenceDate() {
            if (!warningOccurrenceSelect || !warningOccurrenceDateInput) {
                return;
            }

            const selectedOption = warningOccurrenceSelect.options[warningOccurrenceSelect.selectedIndex];
            warningOccurrenceDateInput.value = selectedOption && selectedOption.value ? (selectedOption.dataset.date || '') : '';
        }

        bindCollaboratorLookup({
            formId: 'advertencia-form',
            inputId: 'advertencia-colaborador',
            suggestionsId: 'advertencia-vigilantes-suggestions',
            collaboratorIdId: 'advertencia-colaborador-id',
            vigilanteIdId: 'advertencia-vigilante-id',
            cpfInputId: 'advertencia-cpf',
            invalidMessage: 'Digite ou selecione um vigilante válido da lista.',
            onChange: (selectedCollaborator) => {
                syncWarningOccurrenceOptions(selectedCollaborator);
                syncWarningOccurrenceDate();
            },
        });

        bindCollaboratorLookup({
            formId: 'ocorrencia-form',
            inputId: 'ocorrencia-colaborador',
            suggestionsId: 'ocorrencia-vigilantes-suggestions',
            collaboratorIdId: 'ocorrencia-colaborador-id',
            vigilanteIdId: 'ocorrencia-vigilante-id',
            cpfInputId: 'ocorrencia-cpf',
            invalidMessage: 'Digite ou selecione um vigilante válido da lista.',
        });

        if (warningOccurrenceSelect) {
            warningOccurrenceSelect.addEventListener('change', syncWarningOccurrenceDate);
        }

        successToasts.forEach((toast) => {
            window.setTimeout(() => {
                toast.classList.add('pointer-events-none', 'scale-95', 'opacity-0');

                window.setTimeout(() => {
                    toast.remove();
                }, 320);
            }, 4200);
        });

        syncWarningOccurrenceOptions(findSelectedCollaborator((document.getElementById('advertencia-colaborador') || {}).value || ''));
        syncWarningOccurrenceDate();
    })();
</script>

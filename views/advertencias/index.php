<?php
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
                <p class="font-semibold">Aviso ao carregar advertências</p>
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

<?php if ($advertenciaSuccessMessage !== ''): ?>
    <div
        id="advertencia-success-toast"
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

<section class="rounded-3xl border border-red-100 bg-white shadow-sm">
    <div class="border-b border-gray-100 px-6 py-5">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-brand-red">Controle disciplinar</p>
                <h2 class="mt-2 text-2xl font-bold text-gray-950">Controle de Advertências</h2>
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
                    <h3 class="text-lg font-bold text-gray-900">Histórico disciplinar</h3>
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
</section>

<script>
    (() => {
        const successToast = document.getElementById('advertencia-success-toast');
        const warningForm = document.getElementById('advertencia-form');
        const collaboratorInput = document.getElementById('advertencia-colaborador');
        const collaboratorIdInput = document.getElementById('advertencia-colaborador-id');
        const collaboratorSuggestions = document.getElementById('advertencia-vigilantes-suggestions');
        const vigilanteInput = document.getElementById('advertencia-vigilante-id');
        const cpfInput = document.getElementById('advertencia-cpf');
        const occurrenceSelect = document.getElementById('advertencia-ocorrencia');
        const occurrenceDateInput = document.getElementById('advertencia-data-ocorrencia');
        const occurrenceEmpty = document.getElementById('advertencia-ocorrencia-empty');
        let currentSuggestions = [];
        let activeSuggestionIndex = -1;
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

        function findTypedCollaborator() {
            const typedName = normalizeText(collaboratorInput ? collaboratorInput.value : '');

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

        function getMatchingCollaborators() {
            const typedName = normalizeText(collaboratorInput ? collaboratorInput.value : '');

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

        function hideCollaboratorSuggestions() {
            currentSuggestions = [];
            activeSuggestionIndex = -1;

            if (!collaboratorSuggestions) {
                return;
            }

            collaboratorSuggestions.classList.add('hidden');
            collaboratorSuggestions.innerHTML = '';

            if (collaboratorInput) {
                collaboratorInput.setAttribute('aria-expanded', 'false');
            }
        }

        function updateActiveSuggestion() {
            if (!collaboratorSuggestions) {
                return;
            }

            Array.from(collaboratorSuggestions.querySelectorAll('button')).forEach((button, index) => {
                const isActive = index === activeSuggestionIndex;
                button.classList.toggle('bg-red-50', isActive);
                button.classList.toggle('text-brand-red', isActive);
            });
        }

        function selectCollaborator(collaborator) {
            if (!collaboratorInput || !collaborator) {
                return;
            }

            collaboratorInput.value = collaborator.name;
            hideCollaboratorSuggestions();
            syncOccurrenceOptions();
            syncOccurrenceDate();
        }

        function renderCollaboratorSuggestions() {
            if (!collaboratorInput || !collaboratorSuggestions) {
                return;
            }

            currentSuggestions = getMatchingCollaborators();
            activeSuggestionIndex = -1;
            collaboratorSuggestions.innerHTML = '';

            if (currentSuggestions.length === 0) {
                hideCollaboratorSuggestions();
                return;
            }

            currentSuggestions.forEach((collaborator, index) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'block w-full px-4 py-2.5 text-left font-semibold text-gray-800 transition-colors hover:bg-red-50 focus:bg-red-50 focus:outline-none';
                button.textContent = collaborator.name;
                button.addEventListener('mousedown', (event) => event.preventDefault());
                button.addEventListener('click', () => selectCollaborator(collaborator));
                collaboratorSuggestions.appendChild(button);
            });

            collaboratorSuggestions.classList.remove('hidden');
            collaboratorInput.setAttribute('aria-expanded', 'true');
        }

        function syncOccurrenceOptions() {
            if (!collaboratorInput || !occurrenceSelect) {
                return;
            }

            const selectedCollaborator = findTypedCollaborator();
            const selectedVigilanteId = selectedCollaborator ? selectedCollaborator.vigilanteId : '';
            let visibleOccurrences = 0;

            if (collaboratorIdInput) {
                collaboratorIdInput.value = selectedCollaborator ? selectedCollaborator.collaboratorId : '';
            }

            if (vigilanteInput) {
                vigilanteInput.value = selectedVigilanteId;
            }

            if (cpfInput) {
                cpfInput.value = selectedCollaborator ? selectedCollaborator.cpf : '';
            }

            collaboratorInput.setCustomValidity('');

            Array.from(occurrenceSelect.options).forEach((option) => {
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

            const selectedOccurrence = occurrenceSelect.options[occurrenceSelect.selectedIndex];
            if (selectedOccurrence && selectedOccurrence.value !== '' && selectedOccurrence.disabled) {
                occurrenceSelect.value = '';
            }

            if (!selectedVigilanteId || visibleOccurrences === 0) {
                occurrenceSelect.value = '';
            }

            if (occurrenceDateInput && occurrenceSelect.value === '') {
                occurrenceDateInput.value = '';
            }

            if (occurrenceEmpty) {
                occurrenceEmpty.classList.toggle('hidden', !selectedVigilanteId || visibleOccurrences > 0);
            }
        }

        function syncOccurrenceDate() {
            if (!occurrenceSelect || !occurrenceDateInput) {
                return;
            }

            const selectedOption = occurrenceSelect.options[occurrenceSelect.selectedIndex];
            occurrenceDateInput.value = selectedOption && selectedOption.value ? (selectedOption.dataset.date || '') : '';
        }

        if (collaboratorInput) {
            collaboratorInput.addEventListener('input', () => {
                renderCollaboratorSuggestions();
                syncOccurrenceOptions();
                syncOccurrenceDate();
            });

            collaboratorInput.addEventListener('change', () => {
                syncOccurrenceOptions();
                syncOccurrenceDate();
            });

            collaboratorInput.addEventListener('focus', renderCollaboratorSuggestions);

            collaboratorInput.addEventListener('blur', () => {
                window.setTimeout(hideCollaboratorSuggestions, 120);
            });

            collaboratorInput.addEventListener('keydown', (event) => {
                if (!collaboratorSuggestions || collaboratorSuggestions.classList.contains('hidden')) {
                    return;
                }

                if (event.key === 'Escape') {
                    hideCollaboratorSuggestions();
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
        }

        if (occurrenceSelect) {
            occurrenceSelect.addEventListener('change', syncOccurrenceDate);
        }

        if (warningForm) {
            warningForm.addEventListener('submit', (event) => {
                syncOccurrenceOptions();

                if (!collaboratorIdInput || collaboratorIdInput.value !== '') {
                    return;
                }

                event.preventDefault();
                collaboratorInput.setCustomValidity('Digite ou selecione um vigilante válido da lista.');
                collaboratorInput.reportValidity();
            });
        }

        document.addEventListener('mousedown', (event) => {
            if (
                collaboratorInput
                && collaboratorSuggestions
                && !collaboratorInput.contains(event.target)
                && !collaboratorSuggestions.contains(event.target)
            ) {
                hideCollaboratorSuggestions();
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

        syncOccurrenceOptions();
        syncOccurrenceDate();
    })();
</script>

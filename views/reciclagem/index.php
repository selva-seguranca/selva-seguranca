<?php
    $vigilantes = is_array($vigilantes ?? null) ? $vigilantes : [];
    $resumo = is_array($resumo ?? null) ? $resumo : [];

    $formatDate = function ($date) {
        $date = trim((string) $date);
        if ($date === '') {
            return 'Não informada';
        }

        $timestamp = strtotime($date);
        return $timestamp !== false ? date('d/m/Y', $timestamp) : 'Não informada';
    };

    $initials = function ($name) {
        $parts = preg_split('/\s+/', trim((string) $name));
        $letters = '';

        foreach ($parts as $part) {
            if ($part !== '') {
                $letters .= substr($part, 0, 1);
            }

            if (strlen($letters) >= 2) {
                break;
            }
        }

        return strtoupper($letters !== '' ? $letters : 'V');
    };

    $daysText = function ($days, $status) {
        if ($days === null) {
            return 'Sem prazo informado';
        }

        $days = (int) $days;

        if ($status === 'vencida') {
            return 'Vencida há ' . abs($days) . ' dia(s)';
        }

        if ($days === 0) {
            return 'Vence hoje';
        }

        return 'Vence em ' . $days . ' dia(s)';
    };

    $statusStyles = [
        'em_dia' => [
            'card' => 'border-green-100',
            'badge' => 'bg-green-100 text-green-800',
            'icon' => 'bg-green-100 text-green-700',
            'notice' => 'border-green-100 bg-green-50 text-green-800',
        ],
        'alerta' => [
            'card' => 'border-amber-200 ring-1 ring-amber-100',
            'badge' => 'bg-amber-100 text-amber-800',
            'icon' => 'bg-amber-100 text-amber-700',
            'notice' => 'border-amber-200 bg-amber-50 text-amber-900',
        ],
        'vencida' => [
            'card' => 'border-red-200 ring-1 ring-red-100',
            'badge' => 'bg-red-100 text-red-800',
            'icon' => 'bg-red-100 text-brand-red',
            'notice' => 'border-red-200 bg-red-50 text-red-800',
        ],
        'sem_data' => [
            'card' => 'border-gray-200',
            'badge' => 'bg-gray-100 text-gray-700',
            'icon' => 'bg-gray-100 text-gray-600',
            'notice' => 'border-gray-200 bg-gray-50 text-gray-700',
        ],
    ];

    $summaryCards = [
        ['label' => 'Vigilantes', 'value' => $resumo['total'] ?? 0, 'icon' => 'ph-shield-check', 'classes' => 'bg-blue-100 text-blue-700'],
        ['label' => 'Alertas 60 dias', 'value' => $resumo['em_alerta'] ?? 0, 'icon' => 'ph-bell-ringing', 'classes' => 'bg-amber-100 text-amber-700'],
        ['label' => 'Vencidas', 'value' => $resumo['vencidas'] ?? 0, 'icon' => 'ph-warning-octagon', 'classes' => 'bg-red-100 text-brand-red'],
        ['label' => 'Em dia', 'value' => $resumo['em_dia'] ?? 0, 'icon' => 'ph-check-circle', 'classes' => 'bg-green-100 text-green-700'],
    ];
?>

<div class="space-y-8">
    <section class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm">
        <div class="relative px-6 py-8 sm:px-8">
            <div class="absolute inset-y-0 right-0 hidden w-1/2 bg-gradient-to-l from-red-50 via-transparent to-transparent lg:block"></div>
            <div class="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                <div class="max-w-3xl">
                    <p class="text-xs font-semibold uppercase tracking-[0.28em] text-brand-red">Controle de reciclagem</p>
                    <h2 class="mt-3 text-3xl font-bold tracking-tight text-gray-950">Reciclagem dos cursos dos vigilantes</h2>
                    <p class="mt-3 text-sm leading-6 text-gray-500">
                        Acompanhe a data da reciclagem, o vencimento e os alertas automáticos que entram em atenção 60 dias antes do prazo de validade.
                    </p>
                </div>
                <div class="inline-flex h-20 w-20 items-center justify-center rounded-3xl bg-gray-950 text-white shadow-xl shadow-red-500/10">
                    <i class="ph ph-recycle text-4xl"></i>
                </div>
            </div>
        </div>
    </section>

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <?php foreach ($summaryCards as $card): ?>
            <article class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-sm font-medium text-gray-500"><?= htmlspecialchars($card['label'], ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="mt-2 text-3xl font-bold text-gray-950"><?= (int) $card['value'] ?></p>
                    </div>
                    <span class="inline-flex h-12 w-12 items-center justify-center rounded-full <?= $card['classes'] ?>">
                        <i class="ph <?= $card['icon'] ?> text-2xl"></i>
                    </span>
                </div>
            </article>
        <?php endforeach; ?>
    </section>

    <?php if (($resumo['em_alerta'] ?? 0) > 0): ?>
        <section class="rounded-3xl border border-amber-200 bg-amber-50 px-6 py-5 text-amber-900 shadow-sm">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700">
                    <i class="ph ph-bell-ringing text-2xl"></i>
                </span>
                <div>
                    <p class="font-semibold">Alerta de 60 dias acionado.</p>
                    <p class="mt-1 text-sm">Há <?= (int) $resumo['em_alerta'] ?> vigilante(s) com reciclagem vencendo em até 60 dias. Oriente a preparação para a nova reciclagem.</p>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <section>
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h3 class="text-xl font-bold text-gray-950">Cards dos vigilantes</h3>
                <p class="mt-1 text-sm text-gray-500">Cada card reflete o prazo atual cadastrado no RH.</p>
            </div>
        </div>

        <?php if (empty($vigilantes)): ?>
            <div class="rounded-3xl border border-gray-200 bg-white p-8 text-center shadow-sm">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-gray-100 text-gray-500">
                    <i class="ph ph-shield-warning text-3xl"></i>
                </div>
                <h4 class="mt-4 text-lg font-semibold text-gray-900">Nenhum vigilante encontrado</h4>
                <p class="mt-2 text-sm text-gray-500">Cadastre vigilantes no RH para acompanhar as reciclagens nesta área.</p>
            </div>
        <?php else: ?>
            <div class="grid gap-5 xl:grid-cols-2 2xl:grid-cols-3">
                <?php foreach ($vigilantes as $vigilante): ?>
                    <?php
                        $status = $vigilante['status_reciclagem'] ?? 'sem_data';
                        $style = $statusStyles[$status] ?? $statusStyles['sem_data'];
                        $photoUrl = trim((string) ($vigilante['foto_url'] ?? ''));
                        $nome = (string) ($vigilante['nome'] ?? '');
                        $situacao = trim((string) ($vigilante['situacao'] ?? ''));
                        $situacao = $situacao !== '' ? $situacao : (!empty($vigilante['ativo']) ? 'Ativo' : 'Inativo');
                        $editUrl = !empty($vigilante['collaborator_id'])
                            ? '/rh?modal=editar-colaborador&edit=' . urlencode((string) $vigilante['collaborator_id'])
                            : null;
                    ?>
                    <article class="rounded-3xl border <?= $style['card'] ?> bg-white p-6 shadow-sm">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex min-w-0 items-center gap-4">
                                <?php if ($photoUrl !== ''): ?>
                                    <img
                                        src="<?= htmlspecialchars($photoUrl, ENT_QUOTES, 'UTF-8') ?>"
                                        alt="Foto de <?= htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') ?>"
                                        class="h-16 w-16 shrink-0 rounded-2xl object-cover"
                                    >
                                <?php else: ?>
                                    <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-red-50 text-lg font-bold text-brand-red">
                                        <?= htmlspecialchars($initials($nome), ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                <?php endif; ?>

                                <div class="min-w-0">
                                    <h4 class="truncate text-lg font-bold text-gray-950"><?= htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') ?></h4>
                                    <p class="mt-1 text-sm text-gray-500">Vigilante · <?= htmlspecialchars($situacao, ENT_QUOTES, 'UTF-8') ?></p>
                                </div>
                            </div>

                            <span class="inline-flex shrink-0 items-center rounded-full px-3 py-1 text-xs font-semibold <?= $style['badge'] ?>">
                                <?= htmlspecialchars((string) ($vigilante['status_label'] ?? 'Sem vencimento'), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </div>

                        <div class="mt-6 grid gap-3 sm:grid-cols-2">
                            <div class="rounded-2xl bg-gray-50 px-4 py-3">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Data da reciclagem</p>
                                <p class="mt-1 font-semibold text-gray-900"><?= $formatDate($vigilante['data_reciclagem'] ?? null) ?></p>
                            </div>
                            <div class="rounded-2xl bg-gray-50 px-4 py-3">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Vencimento</p>
                                <p class="mt-1 font-semibold text-gray-900"><?= $formatDate($vigilante['validade_reciclagem'] ?? null) ?></p>
                            </div>
                            <div class="rounded-2xl bg-gray-50 px-4 py-3">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Prazo</p>
                                <p class="mt-1 font-semibold text-gray-900"><?= htmlspecialchars($daysText($vigilante['dias_para_vencimento'] ?? null, $status), ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                            <div class="rounded-2xl bg-gray-50 px-4 py-3">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">CNV</p>
                                <p class="mt-1 font-semibold text-gray-900"><?= htmlspecialchars(trim((string) ($vigilante['numero_cnv'] ?? '')) ?: 'Não informada', ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                        </div>

                        <div class="mt-5 rounded-2xl border px-4 py-3 text-sm <?= $style['notice'] ?>">
                            <?php if ($status === 'alerta'): ?>
                                <p class="font-semibold">Preparar nova reciclagem.</p>
                                <p class="mt-1">O prazo entrou na janela de alerta de 60 dias.</p>
                            <?php elseif ($status === 'vencida'): ?>
                                <p class="font-semibold">Reciclagem vencida.</p>
                                <p class="mt-1">Regularize a situação do vigilante antes da próxima escala operacional.</p>
                            <?php elseif ($status === 'em_dia'): ?>
                                <p class="font-semibold">Reciclagem em dia.</p>
                                <p class="mt-1">O alerta será acionado automaticamente quando faltarem 60 dias.</p>
                            <?php else: ?>
                                <p class="font-semibold">Datas incompletas.</p>
                                <p class="mt-1">Preencha a data da reciclagem e o vencimento no cadastro do vigilante.</p>
                            <?php endif; ?>
                        </div>

                        <div class="mt-5">
                            <?php if ($editUrl !== null): ?>
                                <a href="<?= htmlspecialchars($editUrl, ENT_QUOTES, 'UTF-8') ?>" class="inline-flex w-full items-center justify-center rounded-2xl border border-gray-200 px-4 py-3 text-sm font-semibold text-gray-700 transition-colors hover:border-brand-red hover:text-brand-red">
                                    <i class="ph ph-pencil-simple mr-2 text-lg"></i>
                                    Editar datas no RH
                                </a>
                            <?php else: ?>
                                <span class="inline-flex w-full items-center justify-center rounded-2xl border border-gray-200 px-4 py-3 text-sm font-semibold text-gray-400">
                                    Cadastro de RH não vinculado
                                </span>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

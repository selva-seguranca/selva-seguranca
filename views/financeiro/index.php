<?php
    $resumoCards = [
        [
            'label' => 'Receitas do Mês',
            'valor' => $summary['receitas_mes'] ?? 0,
            'icon' => 'ph-trend-up',
            'tone' => 'emerald',
            'suffix' => '',
        ],
        [
            'label' => 'Despesas do Mês',
            'valor' => $summary['despesas_mes'] ?? 0,
            'icon' => 'ph-trend-down',
            'tone' => 'rose',
            'suffix' => '',
        ],
        [
            'label' => 'Saldo Projetado',
            'valor' => $summary['saldo_previsto_mes'] ?? 0,
            'icon' => 'ph-wallet',
            'tone' => (($summary['saldo_previsto_mes'] ?? 0) >= 0 ? 'blue' : 'amber'),
            'suffix' => '',
        ],
        [
            'label' => 'Lançamentos Atrasados',
            'valor' => $summary['atrasados'] ?? 0,
            'icon' => 'ph-warning-circle',
            'tone' => (($summary['atrasados'] ?? 0) > 0 ? 'amber' : 'slate'),
            'suffix' => ' titulo(s)',
            'is_count' => true,
        ],
    ];

    $toneMap = [
        'emerald' => ['box' => 'bg-emerald-50 border-emerald-100', 'icon' => 'bg-emerald-500/10 text-emerald-600'],
        'rose' => ['box' => 'bg-rose-50 border-rose-100', 'icon' => 'bg-rose-500/10 text-rose-600'],
        'blue' => ['box' => 'bg-blue-50 border-blue-100', 'icon' => 'bg-blue-500/10 text-blue-600'],
        'amber' => ['box' => 'bg-amber-50 border-amber-100', 'icon' => 'bg-amber-500/10 text-amber-600'],
        'slate' => ['box' => 'bg-slate-50 border-slate-100', 'icon' => 'bg-slate-500/10 text-slate-600'],
    ];

    $fmtMoney = function ($value) {
        return 'R$ ' . number_format((float) $value, 2, ',', '.');
    };

    $fmtDate = function ($date) {
        if (!$date) {
            return 'N/D';
        }

        return date('d/m/Y', strtotime($date));
    };

    $statusStyles = [
        'pago' => 'bg-emerald-100 text-emerald-700',
        'pendente' => 'bg-blue-100 text-blue-700',
        'atrasado' => 'bg-amber-100 text-amber-700',
    ];
?>

<div class="space-y-6">
    <section class="rounded-3xl bg-gradient-to-br from-brand-dark via-gray-900 to-gray-800 p-6 text-white shadow-xl">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-2xl">
                <div class="mb-3 inline-flex items-center rounded-full bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-red-100">
                    Painel Financeiro
                </div>
                <h2 class="text-2xl font-bold leading-tight md:text-3xl">Visão consolidada do fluxo financeiro da operação</h2>
                <p class="mt-3 text-sm text-gray-300 md:text-base">
                    Acompanhe receitas, despesas, títulos pendentes e vencimentos em um único painel conectado ao banco de dados.
                </p>
            </div>

            <div class="grid grid-cols-2 gap-3 sm:flex sm:flex-wrap">
                <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                    <p class="text-xs uppercase tracking-wide text-gray-400">Recebido no Mês</p>
                    <p class="mt-1 text-lg font-semibold"><?= $fmtMoney($summary['recebido_mes'] ?? 0) ?></p>
                </div>
                <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                    <p class="text-xs uppercase tracking-wide text-gray-400">A Receber</p>
                    <p class="mt-1 text-lg font-semibold"><?= $fmtMoney($summary['a_receber'] ?? 0) ?></p>
                </div>
                <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                    <p class="text-xs uppercase tracking-wide text-gray-400">A Pagar</p>
                    <p class="mt-1 text-lg font-semibold"><?= $fmtMoney($summary['a_pagar'] ?? 0) ?></p>
                </div>
            </div>
        </div>
    </section>

    <section class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <?php foreach ($resumoCards as $card): ?>
            <?php $tone = $toneMap[$card['tone']]; ?>
            <article class="rounded-2xl border p-5 shadow-sm <?= $tone['box'] ?>">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-medium text-gray-500"><?= htmlspecialchars($card['label']) ?></p>
                        <p class="mt-3 text-2xl font-black text-gray-900">
                            <?php if (!empty($card['is_count'])): ?>
                                <?= (int) $card['valor'] ?><span class="ml-1 text-sm font-medium text-gray-500"><?= htmlspecialchars($card['suffix']) ?></span>
                            <?php else: ?>
                                <?= $fmtMoney($card['valor']) ?>
                            <?php endif; ?>
                        </p>
                    </div>

                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl <?= $tone['icon'] ?>">
                        <i class="ph <?= htmlspecialchars($card['icon']) ?> text-2xl"></i>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </section>

    <section class="grid grid-cols-1 gap-6 xl:grid-cols-[1.15fr_0.85fr]">
        <div class="rounded-3xl border border-gray-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-5">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Lançamentos Financeiros</h3>
                    <p class="mt-1 text-sm text-gray-500">Títulos ordenados por urgência e vencimento.</p>
                </div>
                <div class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-gray-600">
                    <?= count($lancamentos) ?> registro(s)
                </div>
            </div>

            <?php if (empty($lancamentos)): ?>
                <div class="px-6 py-10 text-sm text-gray-500">
                    Nenhum lançamento encontrado na tabela financeiro.
                </div>
            <?php else: ?>
                <div class="hidden overflow-x-auto md:block">
                    <table class="min-w-full divide-y divide-gray-100 text-left text-sm">
                        <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-6 py-4 font-semibold">Tipo</th>
                                <th class="px-6 py-4 font-semibold">Descrição</th>
                                <th class="px-6 py-4 font-semibold">Vencimento</th>
                                <th class="px-6 py-4 font-semibold">Pagamento</th>
                                <th class="px-6 py-4 font-semibold">Valor</th>
                                <th class="px-6 py-4 font-semibold">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach ($lancamentos as $item): ?>
                                <?php
                                    $tipoTone = $item['tipo'] === 'receita'
                                        ? 'bg-emerald-100 text-emerald-700'
                                        : 'bg-rose-100 text-rose-700';
                                ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold <?= $tipoTone ?>">
                                            <?= htmlspecialchars($item['tipo_label']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <p class="font-medium text-gray-900"><?= htmlspecialchars($item['descricao']) ?></p>
                                    </td>
                                    <td class="px-6 py-4 text-gray-600"><?= $fmtDate($item['data_vencimento']) ?></td>
                                    <td class="px-6 py-4 text-gray-600"><?= $fmtDate($item['data_pagamento']) ?></td>
                                    <td class="px-6 py-4 font-semibold text-gray-900"><?= $fmtMoney($item['valor']) ?></td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold <?= $statusStyles[$item['status']] ?? 'bg-gray-100 text-gray-700' ?>">
                                            <?= htmlspecialchars($item['status_label']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="space-y-4 p-4 md:hidden">
                    <?php foreach ($lancamentos as $item): ?>
                        <?php
                            $tipoTone = $item['tipo'] === 'receita'
                                ? 'bg-emerald-100 text-emerald-700'
                                : 'bg-rose-100 text-rose-700';
                        ?>
                        <article class="rounded-2xl border border-gray-200 p-4 shadow-sm">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold <?= $tipoTone ?>">
                                            <?= htmlspecialchars($item['tipo_label']) ?>
                                        </span>
                                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold <?= $statusStyles[$item['status']] ?? 'bg-gray-100 text-gray-700' ?>">
                                            <?= htmlspecialchars($item['status_label']) ?>
                                        </span>
                                    </div>
                                    <h4 class="mt-3 text-base font-semibold text-gray-900"><?= htmlspecialchars($item['descricao']) ?></h4>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs uppercase tracking-wide text-gray-400">Valor</p>
                                    <p class="text-sm font-bold text-gray-900"><?= $fmtMoney($item['valor']) ?></p>
                                </div>
                            </div>

                            <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                                <div class="rounded-xl bg-gray-50 px-3 py-2">
                                    <p class="text-xs uppercase tracking-wide text-gray-400">Vencimento</p>
                                    <p class="mt-1 font-medium text-gray-700"><?= $fmtDate($item['data_vencimento']) ?></p>
                                </div>
                                <div class="rounded-xl bg-gray-50 px-3 py-2">
                                    <p class="text-xs uppercase tracking-wide text-gray-400">Pagamento</p>
                                    <p class="mt-1 font-medium text-gray-700"><?= $fmtDate($item['data_pagamento']) ?></p>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="space-y-6">
            <section class="rounded-3xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-6 py-5">
                    <h3 class="text-lg font-semibold text-gray-900">Agenda Financeira</h3>
                    <p class="mt-1 text-sm text-gray-500">Próximos títulos abertos ou vencidos aguardando baixa.</p>
                </div>

                <?php if (empty($agendaFinanceira)): ?>
                    <div class="px-6 py-10 text-sm text-gray-500">
                        Nenhum título pendente encontrado.
                    </div>
                <?php else: ?>
                    <div class="divide-y divide-gray-100">
                        <?php foreach ($agendaFinanceira as $item): ?>
                            <?php
                                $tipoTone = $item['tipo'] === 'receita'
                                    ? 'text-emerald-600'
                                    : 'text-rose-600';
                            ?>
                            <article class="px-6 py-4">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <i class="ph <?= $item['tipo'] === 'receita' ? 'ph-arrow-circle-up-right' : 'ph-arrow-circle-down-right' ?> text-xl <?= $tipoTone ?>"></i>
                                            <p class="truncate font-semibold text-gray-900"><?= htmlspecialchars($item['descricao']) ?></p>
                                        </div>
                                        <p class="mt-2 text-sm text-gray-500">
                                            <?= htmlspecialchars($item['tipo_label']) ?> com vencimento em <?= $fmtDate($item['data_vencimento']) ?>
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-bold text-gray-900"><?= $fmtMoney($item['valor']) ?></p>
                                        <span class="mt-2 inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold <?= $statusStyles[$item['status']] ?? 'bg-gray-100 text-gray-700' ?>">
                                            <?= htmlspecialchars($item['status_label']) ?>
                                        </span>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900">Leitura Rápida</h3>
                <div class="mt-5 space-y-4">
                    <div class="rounded-2xl bg-gray-50 p-4">
                        <p class="text-xs uppercase tracking-wide text-gray-400">Disponível para receber</p>
                        <p class="mt-2 text-2xl font-black text-emerald-600"><?= $fmtMoney($summary['a_receber'] ?? 0) ?></p>
                    </div>
                    <div class="rounded-2xl bg-gray-50 p-4">
                        <p class="text-xs uppercase tracking-wide text-gray-400">Compromissos em aberto</p>
                        <p class="mt-2 text-2xl font-black text-rose-600"><?= $fmtMoney($summary['a_pagar'] ?? 0) ?></p>
                    </div>
                    <div class="rounded-2xl bg-gray-50 p-4">
                        <p class="text-xs uppercase tracking-wide text-gray-400">Resultado projetado</p>
                        <p class="mt-2 text-2xl font-black <?= (($summary['saldo_previsto_mes'] ?? 0) >= 0) ? 'text-blue-600' : 'text-amber-600' ?>">
                            <?= $fmtMoney($summary['saldo_previsto_mes'] ?? 0) ?>
                        </p>
                    </div>
                </div>
            </section>
        </div>
    </section>
</div>

<?php
$cards = [
    ['label' => 'Vigilantes em Campo', 'value' => $stats['vigilantes_em_campo'] ?? 0, 'icon' => 'ph-shield-check', 'tone' => 'green'],
    ['label' => 'Veiculos em Ronda', 'value' => $stats['veiculos_em_ronda'] ?? 0, 'icon' => 'ph-car', 'tone' => 'blue'],
    ['label' => 'Manutencoes Criticas', 'value' => $stats['manutencoes_criticas'] ?? 0, 'icon' => 'ph-bell-ringing', 'tone' => 'red'],
    ['label' => 'Ocorrencias Hoje', 'value' => $stats['ocorrencias_hoje'] ?? 0, 'icon' => 'ph-warning-circle', 'tone' => 'yellow'],
];
$tones = [
    'green' => ['bg' => 'bg-green-100', 'text' => 'text-green-600'],
    'blue' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-600'],
    'red' => ['bg' => 'bg-red-100', 'text' => 'text-brand-red'],
    'yellow' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-600'],
];
?>

<div class="grid grid-cols-1 gap-6 mb-8 md:grid-cols-2 lg:grid-cols-4">
    <?php foreach ($cards as $card): ?>
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500 mb-1"><?= htmlspecialchars($card['label']) ?></p>
                <h3 class="text-3xl font-bold text-gray-800"><?= (int) $card['value'] ?></h3>
            </div>
            <div class="w-12 h-12 rounded-full flex items-center justify-center <?= $tones[$card['tone']]['bg'] ?> <?= $tones[$card['tone']]['text'] ?>">
                <i class="ph <?= $card['icon'] ?> text-2xl"></i>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 lg:col-span-2 overflow-hidden">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
            <h3 class="text-lg font-semibold text-gray-800">Rondas em Andamento</h3>
            <span class="text-sm text-gray-500"><?= count($rondasAtivas) ?> registro(s)</span>
        </div>
        <?php if (empty($rondasAtivas)): ?>
            <div class="p-6 text-sm text-gray-500">Nenhuma ronda ativa encontrada no banco.</div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                            <th class="px-6 py-4 font-medium">Vigilante</th>
                            <th class="px-6 py-4 font-medium">Veiculo / Placa</th>
                            <th class="px-6 py-4 font-medium">Inicio</th>
                            <th class="px-6 py-4 font-medium">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm">
                        <?php foreach ($rondasAtivas as $ronda): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 font-medium text-gray-800"><?= htmlspecialchars($ronda['vigilante']) ?></td>
                                <td class="px-6 py-4 text-gray-600"><?= htmlspecialchars($ronda['veiculo']) ?></td>
                                <td class="px-6 py-4 text-gray-600"><?= date('d/m/Y H:i', strtotime($ronda['data_inicio'])) ?></td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <?= htmlspecialchars($ronda['status_label']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-6">Ultimas Ocorrencias</h3>
        <?php if (empty($ocorrenciasRecentes)): ?>
            <p class="text-sm text-gray-500">Nenhuma ocorrencia registrada ate o momento.</p>
        <?php else: ?>
            <div class="space-y-6">
                <?php foreach ($ocorrenciasRecentes as $ocorrencia): ?>
                    <div class="flex">
                        <div class="flex-shrink-0 mr-4">
                            <div class="w-3 h-3 bg-brand-red rounded-full mt-1.5 ring-4 ring-red-50"></div>
                        </div>
                        <div>
                            <h4 class="text-sm font-semibold text-gray-800"><?= htmlspecialchars($ocorrencia['tipo_label']) ?></h4>
                            <p class="text-xs text-gray-500 mb-1">
                                <?= htmlspecialchars($ocorrencia['vigilante']) ?> - <?= date('d/m/Y H:i', strtotime($ocorrencia['data_hora'])) ?>
                            </p>
                            <p class="text-sm text-gray-600"><?= htmlspecialchars($ocorrencia['descricao']) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="mb-6 flex justify-between items-center">
    <h3 class="text-xl font-semibold text-gray-800">Status dos Veículos</h3>
    <a href="#" class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 rounded-lg font-medium shadow transition-colors flex items-center">
        <i class="ph ph-wrench text-lg mr-2"></i>
        Nova Manutenção
    </a>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php if (empty($veiculos)): ?>
        <div class="col-span-full bg-white rounded-xl shadow-sm border border-gray-200 p-6 text-sm text-gray-500">
            Nenhum veículo encontrado no banco.
        </div>
    <?php endif; ?>
    <?php foreach($veiculos as $v): ?>
    <?php
        $proxOleo = $v['prox_oleo'] ?? 0;
        $proxRevisao = $v['prox_revisao'] ?? 0;
        $oleoUrgente = ($proxOleo > 0) && ($v['km_atual'] >= ($proxOleo - 1000));
        $revisaoUrgente = ($proxRevisao > 0) && ($v['km_atual'] >= ($proxRevisao - 2000));
        $barraOleo = $proxOleo > 0 ? min(100, ($v['km_atual'] / $proxOleo) * 100) : 0;
        $statusLabelMap = [
            'Disponivel' => 'Disponível',
            'Manutencao' => 'Manutenção',
        ];
        $statusLabel = $statusLabelMap[$v['status']] ?? $v['status'];
    ?>
    <div class="bg-white rounded-xl shadow-sm border <?= $oleoUrgente ? 'border-brand-red ring-1 ring-brand-red' : 'border-gray-200' ?> overflow-hidden flex flex-col">
        <div class="p-5 flex justify-between items-start bg-gray-50 border-b border-gray-100">
            <div>
                <h4 class="text-lg font-bold text-gray-800"><?= htmlspecialchars($v['modelo']) ?></h4>
                <p class="text-sm text-gray-500 font-mono tracking-wide"><?= htmlspecialchars($v['placa']) ?></p>
            </div>
            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold uppercase
                <?= $v['status'] == 'Em Uso' ? 'bg-green-100 text-green-800' : ($v['status'] == 'Manutencao' ? 'bg-red-100 text-red-800' : 'bg-gray-200 text-gray-800') ?>">
                <?= htmlspecialchars($statusLabel) ?>
            </span>
        </div>

        <div class="p-5 flex-1 space-y-4">
            <div>
                <p class="text-xs text-gray-500 uppercase tracking-wider font-semibold mb-1">KM Atual</p>
                <div class="text-2xl font-black text-gray-800 font-mono"><?= number_format($v['km_atual'], 0, ',', '.') ?> <span class="text-sm font-medium text-gray-400">km</span></div>
            </div>

            <div class="space-y-3 pt-2 border-t border-gray-100">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 flex items-center">
                        <i class="ph ph-drop text-gray-400 mr-2"></i> Próx. Óleo
                    </span>
                    <span class="text-sm font-bold <?= $oleoUrgente ? 'text-brand-red animate-pulse' : 'text-gray-800' ?>">
                        <?= $proxOleo > 0 ? number_format($proxOleo, 0, ',', '.') . ' km' : 'N/D' ?>
                    </span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-1.5">
                    <div class="<?= $oleoUrgente ? 'bg-brand-red' : 'bg-blue-500' ?> h-1.5 rounded-full" style="width: <?= $barraOleo ?>%"></div>
                </div>

                <div class="flex justify-between items-center pt-2">
                    <span class="text-sm text-gray-600 flex items-center">
                        <i class="ph ph-wrench text-gray-400 mr-2"></i> Próx. Revisão
                    </span>
                    <span class="text-sm font-bold <?= $revisaoUrgente ? 'text-orange-500' : 'text-gray-800' ?>">
                        <?= $proxRevisao > 0 ? number_format($proxRevisao, 0, ',', '.') . ' km' : 'N/D' ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="p-4 border-t border-gray-100 bg-gray-50 flex justify-end space-x-2">
            <button class="px-3 py-1.5 text-sm font-medium text-gray-600 hover:text-gray-900 bg-white border border-gray-300 rounded hover:bg-gray-100 transition-colors">Histórico</button>
            <button class="px-3 py-1.5 text-sm font-medium text-white bg-brand-red rounded hover:bg-red-700 transition-colors shadow">Registrar Óleo</button>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="flex justify-between items-center mb-6">
    <div class="flex space-x-4">
        <div class="bg-white px-4 py-2 rounded-lg shadow-sm border border-gray-100 flex items-center">
            <span class="w-3 h-3 bg-green-500 rounded-full mr-2"></span>
            <span class="text-sm font-medium text-gray-700">Contratos Ativos: <strong><?= count($contratos) ?></strong></span>
        </div>
    </div>
    <a href="#" class="bg-brand-red hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium shadow transition-colors flex items-center">
        <i class="ph ph-file-plus text-lg mr-2"></i>
        Novo Contrato
    </a>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
    <?php if (empty($contratos)): ?>
        <div class="col-span-full bg-white rounded-xl shadow-sm border border-gray-200 p-6 text-sm text-gray-500">
            Nenhum contrato encontrado no banco.
        </div>
    <?php endif; ?>
    <?php foreach($contratos as $c): ?>
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex flex-col hover:border-gray-300 transition-colors">
        <div class="flex justify-between items-start mb-4">
            <div class="w-12 h-12 bg-gray-100 text-gray-600 rounded-lg flex items-center justify-center">
                <i class="ph ph-buildings text-2xl"></i>
            </div>
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold <?= strtolower($c['status']) === 'ativo' ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-800' ?>">
                <?= htmlspecialchars($c['status']) ?>
            </span>
        </div>

        <h4 class="text-xl font-bold text-gray-800 mb-1 leading-tight"><?= htmlspecialchars($c['cliente']) ?></h4>
        <p class="text-sm text-gray-500 font-medium mb-4"><?= htmlspecialchars($c['tipo']) ?></p>

        <div class="space-y-2 pt-4 border-t border-gray-100 mt-auto">
            <div class="flex justify-between">
                <span class="text-sm text-gray-500">Valor Mensal:</span>
                <span class="text-sm font-bold text-gray-800">R$ <?= number_format($c['valor'], 2, ',', '.') ?></span>
            </div>
            <div class="flex justify-between">
                <span class="text-sm text-gray-500">Vencimento:</span>
                <span class="text-sm font-medium text-gray-700"><?= $c['vencimento'] ? date('d/m/Y', strtotime($c['vencimento'])) : 'N/D' ?></span>
            </div>
        </div>

        <div class="mt-6 flex space-x-2">
            <button class="flex-1 bg-gray-50 hover:bg-gray-100 text-gray-700 border border-gray-200 py-2 rounded-lg text-sm font-medium transition-colors">Ver Detalhes</button>
            <button class="flex-1 bg-gray-800 hover:bg-gray-900 text-white py-2 rounded-lg text-sm font-medium transition-colors shadow">PDF / Orçamento</button>
        </div>
    </div>
    <?php endforeach; ?>
</div>

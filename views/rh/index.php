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
            <p class="text-sm font-medium text-gray-500 mb-1">Em Ferias</p>
            <h3 class="text-3xl font-bold text-gray-800"><?= $kpis['em_ferias'] ?></h3>
        </div>
        <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center text-yellow-600">
            <i class="ph ph-sun text-2xl"></i>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 flex items-center justify-between">
        <div>
            <p class="text-sm font-medium text-gray-500 mb-1">Advertencias Mes</p>
            <h3 class="text-3xl font-bold text-brand-red"><?= $kpis['advertencias_recentes'] ?></h3>
        </div>
        <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center text-brand-red">
            <i class="ph ph-warning-octagon text-2xl"></i>
        </div>
    </div>
</div>

<div class="flex justify-between items-center mb-6">
    <h3 class="text-xl font-semibold text-gray-800">Quadro de Colaboradores</h3>
    <a href="#" class="bg-brand-red hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium shadow transition-colors flex items-center">
        <i class="ph ph-plus-circle text-lg mr-2"></i>
        Novo Colaborador
    </a>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <?php if (empty($colaboradores)): ?>
        <div class="p-6 text-sm text-gray-500">Nenhum colaborador encontrado no banco.</div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                        <th class="px-6 py-4 font-medium">Nome</th>
                        <th class="px-6 py-4 font-medium">Cargo</th>
                        <th class="px-6 py-4 font-medium">Departamento</th>
                        <th class="px-6 py-4 font-medium">Status</th>
                        <th class="px-6 py-4 font-medium text-right">Acoes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    <?php foreach($colaboradores as $c): ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 font-medium text-gray-800 flex items-center">
                            <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center text-gray-600 font-bold mr-3 text-xs">
                                <?= substr($c['nome'], 0, 1) ?>
                            </div>
                            <?= htmlspecialchars($c['nome']) ?>
                        </td>
                        <td class="px-6 py-4 text-gray-600"><?= htmlspecialchars($c['cargo']) ?></td>
                        <td class="px-6 py-4 text-gray-600"><?= htmlspecialchars($c['departamento']) ?></td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $c['status'] === 'Ativo' ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-800' ?>">
                                <?= htmlspecialchars($c['status']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <button class="text-gray-400 hover:text-blue-600 transition-colors mr-2" title="Editar"><i class="ph ph-pencil-simple text-lg"></i></button>
                            <button class="text-gray-400 hover:text-brand-red transition-colors" title="Advertir"><i class="ph ph-warning text-lg"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

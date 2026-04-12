<div class="flex justify-between items-center mb-6">
    <div class="flex space-x-3 text-sm">
        <button class="bg-white border border-gray-200 text-gray-700 px-4 py-2 rounded-lg font-medium shadow-sm hover:bg-gray-50">Visualizacao: Mensal</button>
        <span class="bg-gray-100 text-gray-600 px-4 py-2 rounded-lg font-medium"><?= htmlspecialchars($calendarTitle) ?></span>
    </div>
    <a href="#" class="bg-brand-red hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium shadow transition-colors flex items-center">
        <i class="ph ph-plus text-lg mr-2"></i>
        Novo Plantao
    </a>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">Plantoes do Mes</h3>
    <?php if (empty($plantoes)): ?>
        <p class="text-sm text-gray-500">Nenhuma ronda cadastrada para este periodo.</p>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach($plantoes as $p): ?>
            <div class="flex items-center justify-between p-4 border border-gray-100 rounded-lg bg-gray-50 hover:border-brand-red/30 transition-colors">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center text-brand-red">
                        <i class="ph ph-calendar-check text-2xl"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-gray-800"><?= htmlspecialchars($p['vigilante']) ?></h4>
                        <p class="text-xs text-gray-500">Data: <?= date('d/m/Y', strtotime($p['data'])) ?> | Turno: <?= htmlspecialchars($p['turno']) ?></p>
                    </div>
                </div>
                <div>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-gray-200 text-gray-700">
                        <?= htmlspecialchars($p['status']) ?>
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="grid grid-cols-7 border-b border-gray-200 bg-gray-50">
        <?php foreach(['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sab'] as $d): ?>
            <div class="py-3 text-center text-xs font-semibold text-gray-500 tracking-wider uppercase"><?= $d ?></div>
        <?php endforeach; ?>
    </div>

    <div class="grid grid-cols-7 bg-gray-200 gap-px auto-rows-[110px]">
        <?php foreach ($calendarDays as $day): ?>
            <div class="bg-white min-h-[100px] p-2 transition-colors <?= $day['isToday'] ? 'bg-red-50/30' : 'hover:bg-gray-50' ?> <?= !$day['isCurrentMonth'] ? 'opacity-40' : '' ?>">
                <span class="text-xs font-bold <?= $day['isToday'] ? 'text-brand-red' : 'text-gray-400' ?>">
                    <?= htmlspecialchars((string) $day['label']) ?>
                </span>

                <?php foreach (array_slice($day['events'], 0, 2) as $event): ?>
                    <div class="mt-1 p-1 bg-blue-100 border border-blue-200 rounded text-[10px] text-blue-700 font-semibold truncate">
                        <?= htmlspecialchars($event['vigilante']) ?> (<?= htmlspecialchars($event['turno']) ?>)
                    </div>
                <?php endforeach; ?>

                <?php if (count($day['events']) > 2): ?>
                    <div class="mt-1 text-[10px] text-gray-500">+<?= count($day['events']) - 2 ?> registro(s)</div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

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

<?php
    $statusClassMap = [
        'Ativo' => 'bg-green-100 text-green-800',
        'Inativo' => 'bg-gray-200 text-gray-800',
    ];
    $isCreateModalOpen = !empty($isCreateModalOpen);
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
?>

<div class="flex justify-between items-center mb-6">
    <h3 class="text-xl font-semibold text-gray-800">Modulos de Colaboradores</h3>
    <a
        href="/rh?modal=novo-colaborador"
        data-open-collaborator-modal
        class="bg-brand-red hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium shadow transition-colors flex items-center"
    >
        <i class="ph ph-plus-circle text-lg mr-2"></i>
        Novo Colaborador
    </a>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
    <?php foreach ($modulosRh as $modulo): ?>
        <?php $tone = $moduleToneMap[$modulo['slug']] ?? $moduleToneMap['seguranca_privada']; ?>
        <section class="bg-white rounded-2xl shadow-sm border overflow-hidden <?= $tone['container'] ?>">
            <div class="border-b border-gray-100 px-6 py-5">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <div class="inline-flex items-center rounded-full px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] <?= $tone['badge'] ?>">
                            Modulo RH
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
                    <?php foreach ($modulo['roles'] as $role): ?>
                        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold <?= $tone['role'] ?>">
                            <?= htmlspecialchars($role) ?>
                            <span class="ml-2 rounded-full bg-white/80 px-2 py-0.5 text-[11px] font-bold text-gray-700">
                                <?= (int) ($modulo['role_counts'][$role] ?? 0) ?>
                            </span>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php if (empty($modulo['colaboradores'])): ?>
                <div class="p-6 text-sm text-gray-500">
                    Nenhum colaborador enquadrado neste modulo com base nos cargos e departamentos atuais.
                </div>
            <?php else: ?>
                <div class="hidden lg:block overflow-x-auto">
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
                            <?php foreach ($modulo['colaboradores'] as $c): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 font-medium text-gray-800">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold mr-3 text-xs <?= $tone['avatar'] ?>">
                                            <?= substr($c['nome'], 0, 1) ?>
                                        </div>
                                        <span><?= htmlspecialchars($c['nome']) ?></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-gray-600"><?= htmlspecialchars($c['cargo']) ?></td>
                                <td class="px-6 py-4 text-gray-600"><?= htmlspecialchars($c['departamento']) ?></td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $statusClassMap[$c['status']] ?? 'bg-gray-200 text-gray-800' ?>">
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

                <div class="space-y-4 p-4 lg:hidden">
                    <?php foreach ($modulo['colaboradores'] as $c): ?>
                        <article class="rounded-2xl border border-gray-200 p-4 shadow-sm">
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <div class="flex items-center">
                                        <div class="w-9 h-9 rounded-full flex items-center justify-center font-bold mr-3 text-xs shrink-0 <?= $tone['avatar'] ?>">
                                            <?= substr($c['nome'], 0, 1) ?>
                                        </div>
                                        <div class="min-w-0">
                                            <h5 class="truncate text-sm font-semibold text-gray-900"><?= htmlspecialchars($c['nome']) ?></h5>
                                            <p class="truncate text-xs text-gray-500"><?= htmlspecialchars($c['cargo']) ?></p>
                                        </div>
                                    </div>
                                </div>
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium <?= $statusClassMap[$c['status']] ?? 'bg-gray-200 text-gray-800' ?>">
                                    <?= htmlspecialchars($c['status']) ?>
                                </span>
                            </div>

                            <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                                <div class="rounded-xl bg-gray-50 px-3 py-2">
                                    <p class="text-xs uppercase tracking-wide text-gray-400">Cargo</p>
                                    <p class="mt-1 font-medium text-gray-700"><?= htmlspecialchars($c['cargo']) ?></p>
                                </div>
                                <div class="rounded-xl bg-gray-50 px-3 py-2">
                                    <p class="text-xs uppercase tracking-wide text-gray-400">Departamento</p>
                                    <p class="mt-1 font-medium text-gray-700"><?= htmlspecialchars($c['departamento']) ?></p>
                                </div>
                            </div>

                            <div class="mt-4 flex justify-end gap-2">
                                <button class="inline-flex items-center justify-center rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-600 transition-colors hover:border-blue-200 hover:text-blue-600">
                                    <i class="ph ph-pencil-simple text-lg"></i>
                                </button>
                                <button class="inline-flex items-center justify-center rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-600 transition-colors hover:border-red-200 hover:text-brand-red">
                                    <i class="ph ph-warning text-lg"></i>
                                </button>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    <?php endforeach; ?>
</div>

<div
    id="collaborator-modal"
    class="fixed inset-0 z-40 <?= $isCreateModalOpen ? 'flex' : 'hidden' ?> items-center justify-center bg-black/60 p-3 sm:p-5"
    aria-hidden="<?= $isCreateModalOpen ? 'false' : 'true' ?>"
>
    <button
        type="button"
        data-close-collaborator-modal
        class="absolute inset-0 h-full w-full cursor-default"
        aria-label="Fechar cadastro"
    ></button>

    <section class="relative z-10 flex max-h-[calc(100vh-3rem)] w-full max-w-[1120px] flex-col overflow-hidden rounded-[28px] bg-gray-50 shadow-2xl sm:max-h-[calc(100vh-4rem)] sm:rounded-[30px]">
        <header class="sticky top-0 z-20 flex items-center justify-between border-b border-gray-200 bg-white px-4 py-4 sm:px-5">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-brand-red">RH / Cadastro</p>
                <h2 class="mt-1 text-xl font-bold text-gray-900 sm:text-2xl">Novo colaborador</h2>
            </div>

            <button
                type="button"
                data-close-collaborator-modal
                class="inline-flex h-12 w-12 items-center justify-center rounded-full border border-gray-200 text-gray-500 transition-colors hover:border-gray-300 hover:text-gray-800"
                aria-label="Fechar popup"
            >
                <i class="ph ph-x text-2xl"></i>
            </button>
        </header>

        <div class="flex-1 overflow-y-auto p-3 sm:p-5">
            <?php include __DIR__ . '/create.php'; ?>
        </div>
    </section>
</div>

<script>
    (() => {
        const body = document.body;
        const collaboratorModal = document.getElementById('collaborator-modal');
        const collaboratorOpeners = document.querySelectorAll('[data-open-collaborator-modal]');
        const collaboratorClosers = document.querySelectorAll('[data-close-collaborator-modal]');
        const cropModal = document.getElementById('crop-modal');

        if (!collaboratorModal) {
            return;
        }

        function isCropModalOpen() {
            return cropModal && !cropModal.classList.contains('hidden');
        }

        function syncBodyScroll() {
            body.classList.toggle('overflow-hidden', !collaboratorModal.classList.contains('hidden') || isCropModalOpen());
        }

        function syncModalUrl(isOpen) {
            const url = new URL(window.location.href);

            if (isOpen) {
                url.searchParams.set('modal', 'novo-colaborador');
            } else {
                url.searchParams.delete('modal');
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

        collaboratorOpeners.forEach((trigger) => {
            trigger.addEventListener('click', (event) => {
                event.preventDefault();
                setCollaboratorModalOpen(true);
            });
        });

        collaboratorClosers.forEach((trigger) => {
            trigger.addEventListener('click', () => {
                setCollaboratorModalOpen(false);
            });
        });

        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') {
                return;
            }

            if (isCropModalOpen()) {
                return;
            }

            if (!collaboratorModal.classList.contains('hidden')) {
                setCollaboratorModalOpen(false);
            }
        });

        syncBodyScroll();
    })();
</script>

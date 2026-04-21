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
            <p class="text-sm font-medium text-gray-500 mb-1">Em Férias</p>
            <h3 class="text-3xl font-bold text-gray-800"><?= $kpis['em_ferias'] ?></h3>
        </div>
        <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center text-yellow-600">
            <i class="ph ph-sun text-2xl"></i>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 flex items-center justify-between">
        <div>
            <p class="text-sm font-medium text-gray-500 mb-1">Advertências Mês</p>
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
        'Afastado' => 'bg-yellow-100 text-yellow-800',
    ];
    $isCreateModalOpen = !empty($isCreateModalOpen);
    $isViewModalOpen = !empty($isViewModalOpen);
    $formMode = ($formMode ?? 'create') === 'edit' ? 'edit' : 'create';
    $isEditMode = $formMode === 'edit';
    $editCollaboratorId = trim((string) ($editCollaboratorId ?? ''));
    $actionSuccessMessage = trim((string) ($actionSuccess ?? ''));
    $isUpdateSuccessToast = $actionSuccessMessage === 'collaborator_updated';
    $actionSuccessHtml = $isUpdateSuccessToast
        ? 'ALTERAÇÕES SALVAS COM SUCESSO!'
        : htmlspecialchars($actionSuccessMessage);
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

<?php if (!empty($dbWarning)): ?>
    <div class="mb-6 rounded-2xl border border-yellow-200 bg-yellow-50 px-5 py-4 text-sm text-yellow-800">
        <div class="flex items-start gap-3">
            <i class="ph ph-warning-circle mt-0.5 text-lg"></i>
            <div>
                <p class="font-semibold">Aviso ao carregar RH</p>
                <p class="mt-1"><?= htmlspecialchars($dbWarning) ?></p>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($actionError)): ?>
    <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700">
        <div class="flex items-start gap-3">
            <i class="ph ph-warning-circle mt-0.5 text-lg"></i>
            <div>
                <p class="font-semibold">Não foi possível concluir a ação.</p>
                <p class="mt-1"><?= htmlspecialchars($actionError) ?></p>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($actionSuccessMessage !== '' && !$isUpdateSuccessToast): ?>
    <div class="mb-6 rounded-2xl border border-green-200 bg-green-50 px-5 py-4 text-sm text-green-800">
        <div class="flex items-start gap-3">
            <i class="ph ph-check-circle mt-0.5 text-lg"></i>
            <div>
                <p class="font-semibold"><?= $actionSuccessHtml ?></p>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($isUpdateSuccessToast): ?>
    <div
        id="rh-action-success-toast"
        class="fixed left-1/2 top-1/2 z-[80] w-[calc(100%-1.5rem)] max-w-xl -translate-x-1/2 -translate-y-1/2 rounded-2xl border border-green-200 bg-green-50 px-5 py-4 text-sm text-green-800 shadow-2xl transition-all duration-300"
        role="alert"
        aria-live="polite"
    >
        <div class="flex items-start gap-3">
            <span class="mt-0.5 inline-flex h-9 w-9 items-center justify-center rounded-full bg-green-100 text-green-700">
                <i class="ph ph-check-circle text-xl"></i>
            </span>
            <div class="min-w-0">
                <p class="font-semibold tracking-[0.04em]"><?= $actionSuccessHtml ?></p>
                <p class="mt-1 text-xs text-green-700">Os dados do colaborador foram atualizados no banco e o alerta será fechado automaticamente.</p>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="flex justify-between items-center mb-6">
    <h3 class="text-xl font-semibold text-gray-800">Módulos de Colaboradores</h3>
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
        <section class="bg-white rounded-2xl shadow-sm border overflow-hidden <?= $tone['container'] ?>" data-rh-module="<?= htmlspecialchars($modulo['slug'], ENT_QUOTES, 'UTF-8') ?>">
            <div class="border-b border-gray-100 px-6 py-5">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <div class="inline-flex items-center rounded-full px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] <?= $tone['badge'] ?>">
                            Módulo RH
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
                    <?php foreach ($modulo['areas'] as $area): ?>
                        <button
                            type="button"
                            data-rh-area-filter="<?= htmlspecialchars($area, ENT_QUOTES, 'UTF-8') ?>"
                            aria-pressed="false"
                            class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold transition-all <?= $tone['role'] ?>"
                        >
                            <?= htmlspecialchars($area) ?>
                            <span class="ml-2 rounded-full bg-white/80 px-2 py-0.5 text-[11px] font-bold text-gray-700">
                                <?= (int) ($modulo['area_counts'][$area] ?? 0) ?>
                            </span>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php if (empty($modulo['colaboradores'])): ?>
                <div class="p-6 text-sm text-gray-500">
                    Nenhum colaborador enquadrado neste módulo com base nos cargos e departamentos atuais.
                </div>
            <?php else: ?>
                <div class="hidden max-h-[430px] overflow-y-auto 2xl:block">
                    <table class="w-full text-left border-collapse">
                        <thead class="sticky top-0 z-10">
                            <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                                <th class="px-6 py-4 font-medium">Nome</th>
                                <th class="px-6 py-4 font-medium">Cargo</th>
                                <th class="px-6 py-4 font-medium">Status</th>
                                <th class="px-4 py-4 font-medium text-right whitespace-nowrap">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm">
                            <?php foreach ($modulo['colaboradores'] as $c): ?>
                            <?php $photoUrl = trim((string) ($c['foto_url'] ?? '')); ?>
                            <tr class="hover:bg-gray-50 transition-colors" data-rh-area-row="<?= htmlspecialchars((string) ($c['rh_area'] ?? 'Administrativo'), ENT_QUOTES, 'UTF-8') ?>">
                                <td class="px-6 py-4 align-top font-medium text-gray-800">
                                    <div class="flex min-w-0 items-start gap-3">
                                        <div class="flex h-11 w-11 min-h-11 min-w-11 shrink-0 aspect-square items-center justify-center overflow-hidden rounded-full border border-white/80 text-xs font-bold shadow-sm <?= $photoUrl !== '' ? 'bg-white' : $tone['avatar'] ?>">
                                            <?php if ($photoUrl !== ''): ?>
                                                <img src="<?= htmlspecialchars($photoUrl) ?>" alt="Foto de <?= htmlspecialchars($c['nome']) ?>" class="block h-full w-full rounded-full object-cover" loading="lazy">
                                            <?php else: ?>
                                                <?= htmlspecialchars(substr($c['nome'], 0, 1)) ?>
                                            <?php endif; ?>
                                        </div>
                                        <span class="block min-w-0 whitespace-normal break-words leading-6"><?= htmlspecialchars($c['nome']) ?></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 align-top whitespace-normal break-words leading-6 text-gray-600"><?= htmlspecialchars($c['cargo']) ?></td>
                                <td class="px-6 py-4 align-top">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $statusClassMap[$c['status']] ?? 'bg-gray-200 text-gray-800' ?>">
                                        <?= htmlspecialchars($c['status']) ?>
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-right align-top whitespace-nowrap">
                                    <?php if (!empty($c['collaborator_id'])): ?>
                                        <div class="inline-flex min-w-[124px] items-center justify-end gap-2">
                                            <a
                                                href="/rh?view=<?= urlencode((string) $c['collaborator_id']) ?>"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition-colors hover:border-blue-200 hover:text-blue-600"
                                                title="Visualizar cadastro"
                                            >
                                                <i class="ph ph-eye text-lg"></i>
                                            </a>
                                            <a
                                                href="/rh?modal=editar-colaborador&edit=<?= urlencode((string) $c['collaborator_id']) ?>"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition-colors hover:border-amber-200 hover:text-amber-600"
                                                title="Editar cadastro"
                                            >
                                                <i class="ph ph-pencil-simple text-lg"></i>
                                            </a>
                                            <form method="POST" action="/rh/colaboradores/excluir" data-delete-collaborator-form class="inline-flex">
                                                <input type="hidden" name="colaborador_id" value="<?= htmlspecialchars((string) $c['collaborator_id']) ?>">
                                                <button
                                                    type="submit"
                                                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition-colors hover:border-red-200 hover:text-brand-red"
                                                    title="Excluir colaborador"
                                                >
                                                    <i class="ph ph-trash text-lg"></i>
                                                </button>
                                            </form>
                                        </div>
                                    <?php else: ?>
                                        <div class="inline-flex min-w-[124px] items-center justify-end gap-2">
                                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-300" title="Cadastro indisponível">
                                                <i class="ph ph-eye-slash text-lg"></i>
                                            </span>
                                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-300" title="Cadastro indisponível">
                                                <i class="ph ph-pencil-simple text-lg"></i>
                                            </span>
                                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-300" title="Cadastro indisponível">
                                                <i class="ph ph-trash text-lg"></i>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="max-h-[760px] space-y-4 overflow-y-auto p-4 2xl:hidden">
                    <?php foreach ($modulo['colaboradores'] as $c): ?>
                        <?php $photoUrl = trim((string) ($c['foto_url'] ?? '')); ?>
                        <article class="rounded-2xl border border-gray-200 p-4 shadow-sm" data-rh-area-row="<?= htmlspecialchars((string) ($c['rh_area'] ?? 'Administrativo'), ENT_QUOTES, 'UTF-8') ?>">
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <div class="flex items-start">
                                        <div class="mr-3 flex h-11 w-11 min-h-11 min-w-11 shrink-0 aspect-square items-center justify-center overflow-hidden rounded-full border border-white/80 text-xs font-bold shadow-sm <?= $photoUrl !== '' ? 'bg-white' : $tone['avatar'] ?>">
                                            <?php if ($photoUrl !== ''): ?>
                                                <img src="<?= htmlspecialchars($photoUrl) ?>" alt="Foto de <?= htmlspecialchars($c['nome']) ?>" class="block h-full w-full rounded-full object-cover" loading="lazy">
                                            <?php else: ?>
                                                <?= htmlspecialchars(substr($c['nome'], 0, 1)) ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="min-w-0">
                                            <h5 class="text-sm font-semibold leading-6 text-gray-900 break-words"><?= htmlspecialchars($c['nome']) ?></h5>
                                            <p class="mt-1 text-xs leading-5 text-gray-500 break-words"><?= htmlspecialchars($c['cargo']) ?></p>
                                        </div>
                                    </div>
                                </div>
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium <?= $statusClassMap[$c['status']] ?? 'bg-gray-200 text-gray-800' ?>">
                                    <?= htmlspecialchars($c['status']) ?>
                                </span>
                            </div>

                            <div class="mt-4 text-sm">
                                <div class="rounded-xl bg-gray-50 px-3 py-2">
                                    <p class="text-xs uppercase tracking-wide text-gray-400">Cargo</p>
                                    <p class="mt-1 font-medium text-gray-700"><?= htmlspecialchars($c['cargo']) ?></p>
                                </div>
                            </div>

                            <div class="mt-4 flex justify-end gap-2">
                                <?php if (!empty($c['collaborator_id'])): ?>
                                    <a
                                        href="/rh?view=<?= urlencode((string) $c['collaborator_id']) ?>"
                                        class="inline-flex items-center justify-center rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-600 transition-colors hover:border-blue-200 hover:text-blue-600"
                                        title="Visualizar cadastro"
                                    >
                                        <i class="ph ph-eye text-lg"></i>
                                    </a>
                                    <a
                                        href="/rh?modal=editar-colaborador&edit=<?= urlencode((string) $c['collaborator_id']) ?>"
                                        class="inline-flex items-center justify-center rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-600 transition-colors hover:border-amber-200 hover:text-amber-600"
                                        title="Editar cadastro"
                                    >
                                        <i class="ph ph-pencil-simple text-lg"></i>
                                    </a>
                                    <form method="POST" action="/rh/colaboradores/excluir" data-delete-collaborator-form class="inline-flex">
                                        <input type="hidden" name="colaborador_id" value="<?= htmlspecialchars((string) $c['collaborator_id']) ?>">
                                        <button type="submit" class="inline-flex items-center justify-center rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-600 transition-colors hover:border-red-200 hover:text-brand-red" title="Excluir colaborador">
                                            <i class="ph ph-trash text-lg"></i>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="inline-flex items-center justify-center rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-300">
                                        <i class="ph ph-eye-slash text-lg"></i>
                                    </span>
                                    <span class="inline-flex items-center justify-center rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-300">
                                        <i class="ph ph-pencil-simple text-lg"></i>
                                    </span>
                                    <span class="inline-flex items-center justify-center rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-300">
                                        <i class="ph ph-trash text-lg"></i>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
                <div data-rh-area-empty class="hidden p-6 text-sm text-gray-500">
                    Nenhum colaborador encontrado nesta área.
                </div>
            <?php endif; ?>
        </section>
    <?php endforeach; ?>
</div>

<div
    id="collaborator-view-modal"
    class="fixed inset-0 z-40 <?= $isViewModalOpen ? 'flex' : 'hidden' ?> items-start justify-center overflow-y-auto bg-black/60 p-3 sm:items-center sm:p-5"
    aria-hidden="<?= $isViewModalOpen ? 'false' : 'true' ?>"
>
    <button
        type="button"
        data-close-collaborator-view
        class="absolute inset-0 h-full w-full cursor-default"
        aria-label="Fechar visualização"
    ></button>

    <section class="relative z-10 my-1 flex max-h-[calc(100dvh-1.5rem)] w-full max-w-[1120px] flex-col overflow-hidden rounded-[24px] bg-gray-50 shadow-2xl sm:max-h-[calc(100vh-4rem)] sm:rounded-[30px]">
        <header class="sticky top-0 z-20 flex items-center justify-between border-b border-gray-200 bg-white px-4 py-3 sm:px-5 sm:py-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-brand-red">RH / Colaborador</p>
                <h2 class="mt-1 text-xl font-bold text-gray-900 sm:text-2xl">Visualizar cadastro</h2>
            </div>

            <button
                type="button"
                data-close-collaborator-view
                class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-gray-200 text-gray-500 transition-colors hover:border-gray-300 hover:text-gray-800 sm:h-12 sm:w-12"
                aria-label="Fechar popup"
            >
                <i class="ph ph-x text-2xl"></i>
            </button>
        </header>

        <div class="flex-1 overflow-y-auto p-2.5 sm:p-5">
            <?php include __DIR__ . '/show.php'; ?>
        </div>
    </section>
</div>

<div
    id="collaborator-modal"
    data-modal-mode="<?= $isEditMode ? 'edit' : 'create' ?>"
    data-edit-id="<?= htmlspecialchars($editCollaboratorId, ENT_QUOTES, 'UTF-8') ?>"
    class="fixed inset-0 z-40 <?= $isCreateModalOpen ? 'flex' : 'hidden' ?> items-start justify-center overflow-y-auto bg-black/60 p-3 sm:items-center sm:p-5"
    aria-hidden="<?= $isCreateModalOpen ? 'false' : 'true' ?>"
>
    <button
        type="button"
        data-close-collaborator-modal
        class="absolute inset-0 h-full w-full cursor-default"
        aria-label="Fechar cadastro"
    ></button>

    <section class="relative z-10 my-1 flex max-h-[calc(100dvh-1.5rem)] w-full max-w-[1120px] flex-col overflow-hidden rounded-[24px] bg-gray-50 shadow-2xl sm:max-h-[calc(100vh-4rem)] sm:rounded-[30px]">
        <header class="sticky top-0 z-20 flex items-center justify-between border-b border-gray-200 bg-white px-4 py-3 sm:px-5 sm:py-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-brand-red">RH / Cadastro</p>
                <h2 class="mt-1 text-xl font-bold text-gray-900 sm:text-2xl"><?= $isEditMode ? 'Editar colaborador' : 'Novo colaborador' ?></h2>
            </div>

            <button
                type="button"
                data-close-collaborator-modal
                class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-gray-200 text-gray-500 transition-colors hover:border-gray-300 hover:text-gray-800 sm:h-12 sm:w-12"
                aria-label="Fechar popup"
            >
                <i class="ph ph-x text-2xl"></i>
            </button>
        </header>

        <div class="flex-1 overflow-y-auto p-2.5 sm:p-5">
            <?php include __DIR__ . '/create.php'; ?>
        </div>
    </section>
</div>

<div
    id="collaborator-delete-modal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 p-4"
    aria-hidden="true"
>
    <button
        type="button"
        data-close-collaborator-delete
        class="absolute inset-0 h-full w-full cursor-default"
        aria-label="Fechar confirmação de exclusão"
    ></button>

    <section class="relative z-10 w-full max-w-md rounded-[28px] border border-red-100 bg-white p-6 shadow-2xl sm:p-7">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-red-50 text-brand-red">
            <i class="ph ph-trash text-2xl"></i>
        </div>

        <div class="mt-5 text-center">
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-brand-red">Confirmar exclusão</p>
            <h3 class="mt-3 text-lg font-bold text-gray-900 sm:text-xl">TEM CERTEZA QUE DESEJA DELETAR ESSE COLABORADOR?</h3>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-2">
            <button
                type="button"
                id="collaborator-delete-confirm-button"
                class="inline-flex items-center justify-center rounded-xl bg-brand-red px-4 py-3 text-sm font-semibold text-white transition-colors hover:bg-red-700"
            >
                DELETAR
            </button>
            <button
                type="button"
                data-close-collaborator-delete
                class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-semibold text-gray-700 transition-colors hover:border-gray-300 hover:text-gray-900"
            >
                CANCELAR
            </button>
        </div>
    </section>
</div>

<script>
    (() => {
        const body = document.body;
        const collaboratorModal = document.getElementById('collaborator-modal');
        const collaboratorViewModal = document.getElementById('collaborator-view-modal');
        const collaboratorDeleteModal = document.getElementById('collaborator-delete-modal');
        const collaboratorOpeners = document.querySelectorAll('[data-open-collaborator-modal]');
        const collaboratorClosers = document.querySelectorAll('[data-close-collaborator-modal]');
        const collaboratorViewClosers = document.querySelectorAll('[data-close-collaborator-view]');
        const collaboratorDeleteClosers = document.querySelectorAll('[data-close-collaborator-delete]');
        const collaboratorDeleteForms = document.querySelectorAll('[data-delete-collaborator-form]');
        const collaboratorDeleteConfirmButton = document.getElementById('collaborator-delete-confirm-button');
        const areaFilterButtons = document.querySelectorAll('[data-rh-area-filter]');
        const cropModal = document.getElementById('crop-modal');
        const successToast = document.getElementById('rh-action-success-toast');
        let pendingDeleteForm = null;

        if (!collaboratorModal) {
            return;
        }

        function isCropModalOpen() {
            return cropModal && !cropModal.classList.contains('hidden');
        }

        function isViewModalOpen() {
            return collaboratorViewModal && !collaboratorViewModal.classList.contains('hidden');
        }

        function isDeleteModalOpen() {
            return collaboratorDeleteModal && !collaboratorDeleteModal.classList.contains('hidden');
        }

        function syncBodyScroll() {
            body.classList.toggle(
                'overflow-hidden',
                !collaboratorModal.classList.contains('hidden') || isViewModalOpen() || isDeleteModalOpen() || isCropModalOpen()
            );
        }

        function syncModalUrl(isOpen) {
            const url = new URL(window.location.href);
            const modalMode = collaboratorModal.dataset.modalMode || 'create';
            const editId = collaboratorModal.dataset.editId || '';

            if (isOpen) {
                if (modalMode === 'edit' && editId !== '') {
                    url.searchParams.set('modal', 'editar-colaborador');
                    url.searchParams.set('edit', editId);
                } else {
                    url.searchParams.set('modal', 'novo-colaborador');
                    url.searchParams.delete('edit');
                }
            } else {
                url.searchParams.delete('modal');
                url.searchParams.delete('edit');
            }

            window.history.replaceState({}, '', url.toString());
        }

        function syncViewUrl(isOpen) {
            const url = new URL(window.location.href);

            if (!isOpen) {
                url.searchParams.delete('view');
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

        function setCollaboratorViewModalOpen(isOpen, syncUrl = true) {
            if (!collaboratorViewModal) {
                return;
            }

            collaboratorViewModal.classList.toggle('hidden', !isOpen);
            collaboratorViewModal.classList.toggle('flex', isOpen);
            collaboratorViewModal.setAttribute('aria-hidden', isOpen ? 'false' : 'true');

            if (syncUrl) {
                syncViewUrl(isOpen);
            }

            syncBodyScroll();
        }

        function setCollaboratorDeleteModalOpen(isOpen) {
            if (!collaboratorDeleteModal) {
                return;
            }

            collaboratorDeleteModal.classList.toggle('hidden', !isOpen);
            collaboratorDeleteModal.classList.toggle('flex', isOpen);
            collaboratorDeleteModal.setAttribute('aria-hidden', isOpen ? 'false' : 'true');

            if (!isOpen) {
                pendingDeleteForm = null;
                if (collaboratorDeleteConfirmButton) {
                    collaboratorDeleteConfirmButton.disabled = false;
                }
            }

            syncBodyScroll();
        }

        collaboratorOpeners.forEach((trigger) => {
            trigger.addEventListener('click', (event) => {
                if ((collaboratorModal.dataset.modalMode || 'create') === 'edit') {
                    return;
                }

                event.preventDefault();
                collaboratorModal.dataset.modalMode = 'create';
                collaboratorModal.dataset.editId = '';
                setCollaboratorViewModalOpen(false);
                setCollaboratorModalOpen(true);
            });
        });

        collaboratorClosers.forEach((trigger) => {
            trigger.addEventListener('click', () => {
                setCollaboratorModalOpen(false);
            });
        });

        collaboratorViewClosers.forEach((trigger) => {
            trigger.addEventListener('click', () => {
                setCollaboratorViewModalOpen(false);
            });
        });

        collaboratorDeleteClosers.forEach((trigger) => {
            trigger.addEventListener('click', () => {
                setCollaboratorDeleteModalOpen(false);
            });
        });

        collaboratorDeleteForms.forEach((form) => {
            form.addEventListener('submit', (event) => {
                event.preventDefault();
                pendingDeleteForm = form;
                setCollaboratorDeleteModalOpen(true);
            });
        });

        if (collaboratorDeleteConfirmButton) {
            collaboratorDeleteConfirmButton.addEventListener('click', () => {
                if (!pendingDeleteForm) {
                    setCollaboratorDeleteModalOpen(false);
                    return;
                }

                collaboratorDeleteConfirmButton.disabled = true;
                pendingDeleteForm.submit();
            });
        }

        function syncAreaFilter(moduleElement, activeArea) {
            const rows = moduleElement.querySelectorAll('[data-rh-area-row]');
            const emptyState = moduleElement.querySelector('[data-rh-area-empty]');
            let hasVisibleRows = false;

            rows.forEach((row) => {
                const isVisible = activeArea === '' || row.dataset.rhAreaRow === activeArea;
                row.classList.toggle('hidden', !isVisible);

                if (isVisible) {
                    hasVisibleRows = true;
                }
            });

            moduleElement.querySelectorAll('[data-rh-area-filter]').forEach((button) => {
                const isActive = activeArea !== '' && button.dataset.rhAreaFilter === activeArea;
                button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                button.classList.toggle('ring-2', isActive);
                button.classList.toggle('ring-brand-red', isActive);
                button.classList.toggle('ring-offset-2', isActive);
            });

            if (emptyState) {
                emptyState.classList.toggle('hidden', hasVisibleRows);
            }
        }

        areaFilterButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const moduleElement = button.closest('[data-rh-module]');

                if (!moduleElement) {
                    return;
                }

                const selectedArea = button.dataset.rhAreaFilter || '';
                const currentArea = moduleElement.dataset.rhActiveArea || '';
                const nextArea = currentArea === selectedArea ? '' : selectedArea;

                moduleElement.dataset.rhActiveArea = nextArea;
                syncAreaFilter(moduleElement, nextArea);
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
                return;
            }

            if (isDeleteModalOpen()) {
                setCollaboratorDeleteModalOpen(false);
                return;
            }

            if (isViewModalOpen()) {
                setCollaboratorViewModalOpen(false);
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

        syncBodyScroll();
    })();
</script>

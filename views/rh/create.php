<?php
    $old = is_array($old ?? null) ? $old : [];
    $formMode = ($formMode ?? 'create') === 'edit' ? 'edit' : 'create';
    $isEditMode = $formMode === 'edit';
    $selectedType = ($old['tipo_cadastro'] ?? 'vigilante') === 'vigilante' ? 'vigilante' : 'financeiro_administrativo';
    $selectedAdminRole = $old['funcao_administrativa'] ?? 'Administrativo';
    $selectedCourses = is_array($old['outros_cursos'] ?? null) ? $old['outros_cursos'] : [];
    $existingDocuments = is_array($old['documentos'] ?? null) ? $old['documentos'] : [];
    $selectedBloodType = $old['tipo_sanguineo'] ?? '';
    $selectedRhFactor = $old['fator_rh'] ?? '+';
    $selectedAccountType = $old['tipo_conta'] ?? '';
    $selectedRhModule = ($old['modulo_rh'] ?? 'seguranca_privada') === 'servicos_terceirizacoes'
        ? 'servicos_terceirizacoes'
        : 'seguranca_privada';
    $editCollaboratorId = trim((string) ($editCollaboratorId ?? ($old['colaborador_id'] ?? '')));
    $existingPhotoUrl = trim((string) ($existingPhotoUrl ?? ($old['foto_url_atual'] ?? '')));
    $formAction = $isEditMode ? '/rh/colaboradores/atualizar' : '/rh/colaboradores';
    $errorTitle = $isEditMode ? 'Não foi possível atualizar o cadastro.' : 'Não foi possível concluir o cadastro.';
    $accessDescription = $isEditMode
        ? 'Atualize os dados de acesso quando necessário. Deixe a senha em branco para manter a senha atual.'
        : 'Estes campos são opcionais. Se você deixar em branco, o sistema gera um e-mail interno e uma senha provisória.';
    $passwordLabel = $isEditMode ? 'Nova senha provisória' : 'Senha provisória';
    $passwordPlaceholder = $isEditMode ? 'Deixe em branco para manter a atual' : 'Deixe em branco para gerar';
    $submitLabel = $isEditMode ? 'Salvar alterações do colaborador' : 'Salvar novo colaborador';
    $submitIcon = $isEditMode ? 'ph-pencil-simple' : 'ph-floppy-disk';

    $oldValue = function ($key, $default = '') use ($old) {
        return htmlspecialchars((string) ($old[$key] ?? $default), ENT_QUOTES, 'UTF-8');
    };

    $isChecked = function ($key, $value, $default = false) use ($old) {
        if (!array_key_exists($key, $old)) {
            return $default;
        }

        return (string) $old[$key] === (string) $value;
    };

    $hasCourse = function ($value) use ($selectedCourses) {
        return in_array($value, $selectedCourses, true);
    };
?>

<link rel="stylesheet" href="https://unpkg.com/cropperjs@1.6.2/dist/cropper.min.css">

<?php if (!empty($formError)): ?>
    <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700">
        <div class="flex items-start gap-3">
            <i class="ph ph-warning-circle mt-0.5 text-lg"></i>
            <div>
                <p class="font-semibold"><?= $errorTitle ?></p>
                <p class="mt-1"><?= htmlspecialchars($formError) ?></p>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($successMessage)): ?>
    <div class="mb-6 rounded-2xl border border-green-200 bg-green-50 px-5 py-4 text-sm text-green-800">
        <div class="flex items-start gap-3">
            <i class="ph ph-check-circle mt-0.5 text-lg"></i>
            <div>
                <p class="font-semibold"><?= htmlspecialchars($successMessage) ?></p>
                <?php if (!empty($accessInfo['email']) && !empty($accessInfo['password'])): ?>
                    <div class="mt-3 grid gap-3 rounded-xl border border-green-200 bg-white/80 p-4 sm:grid-cols-2">
                        <div>
                            <p class="text-[11px] uppercase tracking-wide text-green-700">E-mail de acesso</p>
                            <p class="mt-1 font-semibold text-gray-900"><?= htmlspecialchars($accessInfo['email']) ?></p>
                        </div>
                        <div>
                            <p class="text-[11px] uppercase tracking-wide text-green-700">Senha provisória</p>
                            <p class="mt-1 font-semibold text-gray-900"><?= htmlspecialchars($accessInfo['password']) ?></p>
                        </div>
                    </div>
                    <p class="mt-3 text-xs text-green-700">
                        Guarde estes dados. A senha pode ter sido gerada automaticamente pelo sistema.
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($successMessage) && !$isEditMode): ?>
    <div
        id="rh-create-success-toast"
        class="fixed left-1/2 top-1/2 z-[90] w-[calc(100%-1.5rem)] max-w-xl -translate-x-1/2 -translate-y-1/2 rounded-2xl border border-green-200 bg-green-50 px-5 py-4 text-sm text-green-800 shadow-2xl transition-all duration-300"
        role="alert"
        aria-live="polite"
    >
        <div class="flex items-start gap-3">
            <span class="mt-0.5 inline-flex h-9 w-9 items-center justify-center rounded-full bg-green-100 text-green-700">
                <i class="ph ph-check-circle text-xl"></i>
            </span>
            <div class="min-w-0">
                <p class="font-semibold tracking-[0.04em]">COLABORADOR SALVO COM SUCESSO!</p>
                <p class="mt-1 text-xs text-green-700">O alerta será fechado automaticamente após o tempo de leitura.</p>
            </div>
        </div>
    </div>
<?php endif; ?>

<form id="rh-create-form" action="<?= $formAction ?>" method="POST" enctype="multipart/form-data" class="w-full">
    <?php if ($isEditMode): ?>
        <input type="hidden" name="colaborador_id" value="<?= htmlspecialchars($editCollaboratorId, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="tipo_cadastro" value="<?= htmlspecialchars($selectedType, ENT_QUOTES, 'UTF-8') ?>">
    <?php endif; ?>
    <input type="hidden" id="photo-current-url-input" name="foto_url_atual" value="<?= htmlspecialchars($existingPhotoUrl, ENT_QUOTES, 'UTF-8') ?>">

    <div class="space-y-6">
        <section class="rounded-3xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 px-6 py-5">
                <h3 class="text-lg font-bold text-gray-900">Tipo de cadastro</h3>
                <p class="mt-1 text-sm text-gray-500">
                    <?= $isEditMode
                        ? 'O tipo de cadastro fica bloqueado na edição para preservar o perfil e os registros vinculados.'
                        : 'Escolha qual fluxo deve ser aplicado neste colaborador.' ?>
                </p>
            </div>

            <div class="grid gap-4 px-6 py-6 md:grid-cols-2">
                <label data-type-card="vigilante" class="group rounded-2xl border p-5 transition-colors <?= $isEditMode ? 'cursor-default' : 'cursor-pointer' ?> <?= $selectedType === 'vigilante' ? 'border-brand-red bg-red-50' : 'border-gray-200 hover:border-red-200' ?>">
                    <input type="radio" name="<?= $isEditMode ? 'tipo_cadastro_visual' : 'tipo_cadastro' ?>" value="vigilante" class="sr-only js-registration-type" <?= $selectedType === 'vigilante' ? 'checked' : '' ?> <?= $isEditMode ? 'disabled' : '' ?>>
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-semibold text-gray-900">OPERACIONAL</p>
                            <p class="mt-2 text-sm text-gray-500">Abre CNV, reciclagem e cursos extras.</p>
                        </div>
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-full <?= $selectedType === 'vigilante' ? 'bg-brand-red text-white' : 'bg-gray-100 text-gray-500' ?>">
                            <i class="ph ph-shield-check text-xl"></i>
                        </span>
                    </div>
                </label>

                <label data-type-card="financeiro_administrativo" class="group rounded-2xl border p-5 transition-colors <?= $isEditMode ? 'cursor-default' : 'cursor-pointer' ?> <?= $selectedType === 'financeiro_administrativo' ? 'border-brand-red bg-red-50' : 'border-gray-200 hover:border-red-200' ?>">
                    <input type="radio" name="<?= $isEditMode ? 'tipo_cadastro_visual' : 'tipo_cadastro' ?>" value="financeiro_administrativo" class="sr-only js-registration-type" <?= $selectedType === 'financeiro_administrativo' ? 'checked' : '' ?> <?= $isEditMode ? 'disabled' : '' ?>>
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-semibold text-gray-900">ADMINISTRATIVO</p>
                            <p class="mt-2 text-sm text-gray-500">Mantém somente dados pessoais e profissionais básicos.</p>
                        </div>
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-full <?= $selectedType === 'financeiro_administrativo' ? 'bg-brand-red text-white' : 'bg-gray-100 text-gray-500' ?>">
                            <i class="ph ph-briefcase text-xl"></i>
                        </span>
                    </div>
                </label>
            </div>
        </section>

        <section class="rounded-3xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 px-6 py-5">
                <h3 class="text-lg font-bold text-gray-900">Módulo RH</h3>
                <p class="mt-1 text-sm text-gray-500">Escolha em qual área do RH este colaborador será exibido após salvar.</p>
            </div>

            <div class="grid gap-4 px-6 py-6 md:grid-cols-2">
                <label data-module-card="seguranca_privada" class="group cursor-pointer rounded-2xl border p-5 transition-colors <?= $selectedRhModule === 'seguranca_privada' ? 'border-brand-red bg-red-50' : 'border-gray-200 hover:border-red-200' ?>">
                    <input type="radio" name="modulo_rh" value="seguranca_privada" class="sr-only js-rh-module" <?= $selectedRhModule === 'seguranca_privada' ? 'checked' : '' ?>>
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-semibold text-gray-900">SELVA SEGURANÇA PRIVADA</p>
                            <p class="mt-2 text-sm text-gray-500">Envia o cadastro para o módulo principal da operação.</p>
                        </div>
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-full <?= $selectedRhModule === 'seguranca_privada' ? 'bg-brand-red text-white' : 'bg-gray-100 text-gray-500' ?>">
                            <i class="ph ph-shield-star text-xl"></i>
                        </span>
                    </div>
                </label>

                <label data-module-card="servicos_terceirizacoes" class="group cursor-pointer rounded-2xl border p-5 transition-colors <?= $selectedRhModule === 'servicos_terceirizacoes' ? 'border-brand-red bg-red-50' : 'border-gray-200 hover:border-red-200' ?>">
                    <input type="radio" name="modulo_rh" value="servicos_terceirizacoes" class="sr-only js-rh-module" <?= $selectedRhModule === 'servicos_terceirizacoes' ? 'checked' : '' ?>>
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-semibold text-gray-900">SELVA SERVIÇOS E TERCEIRIZAÇÕES</p>
                            <p class="mt-2 text-sm text-gray-500">Envia o cadastro para o módulo de serviços terceirizados.</p>
                        </div>
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-full <?= $selectedRhModule === 'servicos_terceirizacoes' ? 'bg-brand-red text-white' : 'bg-gray-100 text-gray-500' ?>">
                            <i class="ph ph-buildings text-xl"></i>
                        </span>
                    </div>
                </label>
            </div>
        </section>

        <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-bold text-gray-900">Acesso ao sistema</h3>
            <p class="mt-2 text-sm text-gray-500"><?= $accessDescription ?></p>

            <div class="mt-5 grid gap-4 md:grid-cols-2">
                <label class="space-y-2">
                    <span class="text-sm font-semibold text-gray-700">E-mail de acesso</span>
                    <input type="email" name="email_acesso" value="<?= $oldValue('email_acesso') ?>" placeholder="colaborador@empresa.com" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm outline-none transition-colors focus:border-brand-red">
                </label>

                <label class="space-y-2">
                    <span class="text-sm font-semibold text-gray-700"><?= $passwordLabel ?></span>
                    <input type="text" name="senha_provisoria" value="" placeholder="<?= $passwordPlaceholder ?>" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm outline-none transition-colors focus:border-brand-red">
                </label>
            </div>
        </section>

        <section class="rounded-3xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 px-6 py-5">
                <h3 class="text-lg font-bold text-gray-900">Dados pessoais</h3>
                <p class="mt-1 text-sm text-gray-500">Informações básicas do colaborador para o cadastro interno.</p>
            </div>

            <div class="grid gap-5 px-6 py-6 md:grid-cols-2 lg:grid-cols-12 lg:items-start">
                <div class="flex flex-col items-center md:col-span-2 lg:col-span-3 lg:row-span-2 lg:items-start">
                    <button
                        type="button"
                        id="photo-surface-button"
                        class="group relative h-44 w-44 overflow-hidden rounded-3xl border border-dashed border-gray-300 bg-gray-50 text-left transition-colors hover:border-brand-red hover:bg-red-50/40 focus:outline-none focus:ring-2 focus:ring-brand-red/20"
                    >
                        <img
                            id="photo-preview"
                            src="<?= htmlspecialchars($existingPhotoUrl, ENT_QUOTES, 'UTF-8') ?>"
                            alt="Preview da foto"
                            class="<?= $existingPhotoUrl !== '' ? '' : 'hidden ' ?>h-full w-full object-cover"
                        >
                        <div id="photo-placeholder" class="<?= $existingPhotoUrl !== '' ? 'hidden ' : '' ?>flex h-full w-full flex-col items-center justify-center px-6 text-center text-gray-400 transition-colors group-hover:text-brand-red">
                            <i class="ph ph-user-circle text-5xl"></i>
                            <p class="mt-4 text-xs font-semibold uppercase tracking-[0.16em] text-gray-400 group-hover:text-brand-red">Toque para escolher</p>
                        </div>
                    </button>

                    <input id="photo-source-input" type="file" accept="image/*" class="hidden">
                    <input id="photo-upload-input" type="file" name="foto_colaborador" accept="image/*" class="hidden">
                    <p id="photo-status" class="sr-only" aria-live="polite"><?= $existingPhotoUrl !== '' ? 'Foto atual carregada.' : 'Nenhuma imagem selecionada.' ?></p>
                </div>

                <label class="space-y-2 md:col-span-2 lg:col-span-9">
                    <span class="text-sm font-semibold text-gray-700">Nome completo</span>
                    <input type="text" name="nome_completo" value="<?= $oldValue('nome_completo') ?>" required class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm outline-none transition-colors focus:border-brand-red">
                </label>

                <label class="space-y-2 lg:col-span-4">
                    <span class="text-sm font-semibold text-gray-700">CPF</span>
                    <input type="text" name="cpf" value="<?= $oldValue('cpf') ?>" required maxlength="14" data-mask="cpf" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm outline-none transition-colors focus:border-brand-red">
                </label>

                <label class="space-y-2 lg:col-span-5">
                    <span class="text-sm font-semibold text-gray-700">RG</span>
                    <input type="text" name="rg" value="<?= $oldValue('rg') ?>" required class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm outline-none transition-colors focus:border-brand-red">
                </label>

                <label class="space-y-2 lg:col-span-6">
                    <span class="text-sm font-semibold text-gray-700">Data de nascimento</span>
                    <input type="date" name="data_nascimento" value="<?= $oldValue('data_nascimento') ?>" required class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm outline-none transition-colors focus:border-brand-red">
                </label>

                <label class="space-y-2 lg:col-span-6">
                    <span class="text-sm font-semibold text-gray-700">Nome da mãe</span>
                    <input type="text" name="nome_mae" value="<?= $oldValue('nome_mae') ?>" required class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm outline-none transition-colors focus:border-brand-red">
                </label>

                <label class="space-y-2 lg:col-span-6">
                    <span class="text-sm font-semibold text-gray-700">Telefone principal</span>
                    <input type="text" name="telefone_principal" value="<?= $oldValue('telefone_principal') ?>" required maxlength="15" data-mask="phone" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm outline-none transition-colors focus:border-brand-red">
                </label>

                <label class="space-y-2 lg:col-span-6">
                    <span class="text-sm font-semibold text-gray-700">Telefone familiar</span>
                    <input type="text" name="telefone_familiar" value="<?= $oldValue('telefone_familiar') ?>" required maxlength="15" data-mask="phone" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm outline-none transition-colors focus:border-brand-red">
                </label>

                <label class="space-y-2 lg:col-span-3">
                    <span class="text-sm font-semibold text-gray-700">Tipo sanguíneo</span>
                    <select name="tipo_sanguineo" required class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm outline-none transition-colors focus:border-brand-red">
                        <option value="">Selecione</option>
                        <?php foreach (['A', 'B', 'AB', 'O'] as $bloodType): ?>
                            <option value="<?= $bloodType ?>" <?= $selectedBloodType === $bloodType ? 'selected' : '' ?>><?= $bloodType ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label class="space-y-2 lg:col-span-3">
                    <span class="text-sm font-semibold text-gray-700">Fator RH</span>
                    <select name="fator_rh" required class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm outline-none transition-colors focus:border-brand-red">
                        <?php foreach (['+' => 'Positivo (+)', '-' => 'Negativo (-)'] as $factorValue => $factorLabel): ?>
                            <option value="<?= $factorValue ?>" <?= $selectedRhFactor === $factorValue ? 'selected' : '' ?>><?= $factorLabel ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label class="space-y-2 lg:col-span-4">
                    <span class="text-sm font-semibold text-gray-700">CEP</span>
                    <input type="text" name="cep" value="<?= $oldValue('cep') ?>" required maxlength="9" data-mask="cep" id="cep-input" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm outline-none transition-colors focus:border-brand-red">
                </label>

                <label class="space-y-2 lg:col-span-2">
                    <span class="text-sm font-semibold text-gray-700">UF</span>
                    <input type="text" name="uf" value="<?= $oldValue('uf') ?>" required maxlength="2" id="uf-input" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm uppercase outline-none transition-colors focus:border-brand-red">
                </label>

                <label class="space-y-2 md:col-span-2 lg:col-span-12">
                    <span class="text-sm font-semibold text-gray-700">Logradouro</span>
                    <input type="text" name="logradouro" value="<?= $oldValue('logradouro') ?>" required id="logradouro-input" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm outline-none transition-colors focus:border-brand-red">
                </label>

                <label class="space-y-2 lg:col-span-3">
                    <span class="text-sm font-semibold text-gray-700">Número</span>
                    <input type="text" name="numero" value="<?= $oldValue('numero') ?>" required class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm outline-none transition-colors focus:border-brand-red">
                </label>

                <label class="space-y-2 lg:col-span-3">
                    <span class="text-sm font-semibold text-gray-700">Bairro</span>
                    <input type="text" name="bairro" value="<?= $oldValue('bairro') ?>" required id="bairro-input" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm outline-none transition-colors focus:border-brand-red">
                </label>

                <label class="space-y-2 lg:col-span-3">
                    <span class="text-sm font-semibold text-gray-700">Complemento</span>
                    <input type="text" name="complemento" value="<?= $oldValue('complemento') ?>" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm outline-none transition-colors focus:border-brand-red">
                </label>

                <label class="space-y-2 lg:col-span-3">
                    <span class="text-sm font-semibold text-gray-700">Cidade</span>
                    <input type="text" name="cidade" value="<?= $oldValue('cidade') ?>" required id="cidade-input" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm outline-none transition-colors focus:border-brand-red">
                </label>
            </div>
        </section>

        <section class="rounded-3xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 px-6 py-5">
                <h3 class="text-lg font-bold text-gray-900">Dados bancários</h3>
                <p class="mt-1 text-sm text-gray-500">Informações opcionais para pagamentos e conferência financeira do colaborador.</p>
            </div>

            <div class="grid gap-5 px-6 py-6 md:grid-cols-2 lg:grid-cols-12">
                <label class="space-y-2 lg:col-span-4">
                    <span class="text-sm font-semibold text-gray-700">Banco</span>
                    <input type="text" name="banco_nome" value="<?= $oldValue('banco_nome') ?>" maxlength="100" placeholder="Nome do banco" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm outline-none transition-colors focus:border-brand-red">
                </label>

                <label class="space-y-2 lg:col-span-3">
                    <span class="text-sm font-semibold text-gray-700">Agência</span>
                    <input type="text" name="agencia_bancaria" value="<?= $oldValue('agencia_bancaria') ?>" maxlength="20" placeholder="0000" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm outline-none transition-colors focus:border-brand-red">
                </label>

                <label class="space-y-2 lg:col-span-3">
                    <span class="text-sm font-semibold text-gray-700">Conta</span>
                    <input type="text" name="conta_bancaria" value="<?= $oldValue('conta_bancaria') ?>" maxlength="30" placeholder="00000-0" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm outline-none transition-colors focus:border-brand-red">
                </label>

                <label class="space-y-2 lg:col-span-2">
                    <span class="text-sm font-semibold text-gray-700">Tipo de conta</span>
                    <select name="tipo_conta" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm outline-none transition-colors focus:border-brand-red">
                        <option value="" <?= $selectedAccountType === '' ? 'selected' : '' ?>>Selecione</option>
                        <?php foreach (['Corrente', 'Poupança', 'Salário', 'Pagamento'] as $accountType): ?>
                            <option value="<?= $accountType ?>" <?= $selectedAccountType === $accountType ? 'selected' : '' ?>><?= $accountType ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label class="space-y-2 lg:col-span-6">
                    <span class="text-sm font-semibold text-gray-700">Chave PIX</span>
                    <input type="text" name="chave_pix" value="<?= $oldValue('chave_pix') ?>" maxlength="150" placeholder="CPF, e-mail, telefone ou chave aleatória" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm outline-none transition-colors focus:border-brand-red">
                </label>

                <label class="space-y-2 lg:col-span-6">
                    <span class="text-sm font-semibold text-gray-700">Titular da conta</span>
                    <input type="text" name="titular_conta" value="<?= $oldValue('titular_conta') ?>" maxlength="150" placeholder="Nome do titular" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm outline-none transition-colors focus:border-brand-red">
                </label>
            </div>
        </section>

        <section class="rounded-3xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 px-6 py-5">
                <h3 class="text-lg font-bold text-gray-900">Documentos em PDF</h3>
                <p class="mt-1 text-sm text-gray-500">Anexe documentos do colaborador, como contrato, certificados ou comprovantes. Use somente arquivos PDF.</p>
            </div>

            <div class="space-y-5 px-6 py-6">
                <?php if (!empty($existingDocuments)): ?>
                    <div class="rounded-2xl border border-gray-200 bg-gray-50 p-4">
                        <p class="text-sm font-semibold text-gray-700">Documentos j&aacute; anexados</p>
                        <div class="mt-3 grid gap-3 md:grid-cols-2">
                            <?php foreach ($existingDocuments as $document): ?>
                                <?php $documentUrl = trim((string) ($document['arquivo_url'] ?? '')); ?>
                                <?php if ($documentUrl === '') { continue; } ?>
                                <a
                                    href="<?= htmlspecialchars($documentUrl, ENT_QUOTES, 'UTF-8') ?>"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="flex items-center gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-700 transition-colors hover:border-brand-red hover:text-brand-red"
                                >
                                    <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-red-50 text-brand-red">
                                        <i class="ph ph-file-pdf text-lg"></i>
                                    </span>
                                    <span class="min-w-0 flex-1 truncate"><?= htmlspecialchars((string) ($document['nome_original'] ?? 'documento.pdf'), ENT_QUOTES, 'UTF-8') ?></span>
                                    <i class="ph ph-arrow-square-out text-base"></i>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <label class="flex cursor-pointer flex-col items-center justify-center rounded-3xl border border-dashed border-gray-300 bg-gray-50 px-6 py-8 text-center transition-colors hover:border-brand-red hover:bg-red-50/40">
                    <input id="document-upload-input" type="file" name="documentos_pdf[]" accept="application/pdf,.pdf" multiple class="hidden">
                    <span class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-white text-brand-red shadow-sm">
                        <i class="ph ph-file-pdf text-2xl"></i>
                    </span>
                    <span class="mt-4 text-sm font-semibold text-gray-900">Selecionar documentos em PDF</span>
                    <span class="mt-1 text-xs text-gray-500">Anexe at&eacute; 10 arquivos PDF neste cadastro, respeitando o limite de <?= \Helpers\MediaStorage::getMaxAllowedFileSizeLabel() ?> por arquivo.</span>
                </label>

                <div id="document-selected-list" class="hidden rounded-2xl border border-gray-200 bg-white p-4 text-sm text-gray-700"></div>
                <p id="document-status" class="sr-only" aria-live="polite"></p>
            </div>
        </section>

        <section class="rounded-3xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 px-6 py-5">
                <h3 class="text-lg font-bold text-gray-900">Dados profissionais</h3>
                <p class="mt-1 text-sm text-gray-500">Dados de vínculo, admissão e situação atual do colaborador.</p>
            </div>

            <div class="grid gap-5 px-6 py-6 md:grid-cols-2">
                <div class="space-y-2 js-vigilante-only<?= $selectedType === 'vigilante' ? '' : ' hidden' ?>">
                    <span class="text-sm font-semibold text-gray-700">Função</span>
                    <input type="text" value="VIGILANTE" readonly class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm font-semibold text-gray-700">
                </div>

                <label class="space-y-2 js-admin-only<?= $selectedType === 'vigilante' ? ' hidden' : '' ?>">
                    <span class="text-sm font-semibold text-gray-700">Função</span>
                    <select name="funcao_administrativa" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm outline-none transition-colors focus:border-brand-red">
                        <option value="Administrativo" <?= $selectedAdminRole === 'Administrativo' ? 'selected' : '' ?>>Administrativo</option>
                        <option value="Financeiro" <?= $selectedAdminRole === 'Financeiro' ? 'selected' : '' ?>>Financeiro</option>
                    </select>
                </label>

                <label class="space-y-2">
                    <span class="text-sm font-semibold text-gray-700">Tipo de vínculo</span>
                    <select name="tipo_vinculo" required class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm outline-none transition-colors focus:border-brand-red">
                        <option value="CLT" <?= $isChecked('tipo_vinculo', 'CLT', true) ? 'selected' : '' ?>>CLT</option>
                        <option value="Freelancer" <?= $isChecked('tipo_vinculo', 'Freelancer') ? 'selected' : '' ?>>Freelancer</option>
                    </select>
                </label>

                <label class="space-y-2">
                    <span class="text-sm font-semibold text-gray-700">Data de admissão</span>
                    <input type="date" name="data_admissao" value="<?= $oldValue('data_admissao') ?>" required class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm outline-none transition-colors focus:border-brand-red">
                </label>

                <label class="space-y-2">
                    <span class="text-sm font-semibold text-gray-700">Número da admissão</span>
                    <input type="text" name="numero_admissao" value="<?= $oldValue('numero_admissao') ?>" required class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm outline-none transition-colors focus:border-brand-red">
                </label>

                <label class="space-y-2">
                    <span class="text-sm font-semibold text-gray-700">Situação</span>
                    <select name="situacao" required class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm outline-none transition-colors focus:border-brand-red">
                        <option value="Ativo" <?= $isChecked('situacao', 'Ativo', true) ? 'selected' : '' ?>>Ativo</option>
                        <option value="Inativo" <?= $isChecked('situacao', 'Inativo') ? 'selected' : '' ?>>Inativo</option>
                        <option value="Afastado" <?= $isChecked('situacao', 'Afastado') ? 'selected' : '' ?>>Afastado</option>
                    </select>
                </label>
            </div>
        </section>

        <section class="rounded-3xl border border-gray-200 bg-white shadow-sm js-vigilante-only<?= $selectedType === 'vigilante' ? '' : ' hidden' ?>">
            <div class="border-b border-gray-100 px-6 py-5">
                <h3 class="text-lg font-bold text-gray-900">Dados de formação do vigilante</h3>
                <p class="mt-1 text-sm text-gray-500">Campos específicos para CNV, reciclagem e cursos complementares.</p>
            </div>

            <div class="grid gap-5 px-6 py-6 md:grid-cols-2">
                <label class="space-y-2">
                    <span class="text-sm font-semibold text-gray-700">N&ordm; da CNV</span>
                    <input type="text" name="numero_cnv" value="<?= $oldValue('numero_cnv') ?>" data-required-for="vigilante" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm outline-none transition-colors focus:border-brand-red">
                </label>

                <label class="space-y-2">
                    <span class="text-sm font-semibold text-gray-700">Data de validade da CNV</span>
                    <input type="date" name="validade_cnv" value="<?= $oldValue('validade_cnv') ?>" data-required-for="vigilante" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm outline-none transition-colors focus:border-brand-red">
                </label>

                <label class="space-y-2">
                    <span class="text-sm font-semibold text-gray-700">Curso de formação</span>
                    <select name="curso_formacao" data-required-for="vigilante" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm outline-none transition-colors focus:border-brand-red">
                        <option value="Sim" <?= $isChecked('curso_formacao', 'Sim', true) ? 'selected' : '' ?>>Sim</option>
                        <option value="Nao" <?= $isChecked('curso_formacao', 'Nao') ? 'selected' : '' ?>>Não</option>
                    </select>
                </label>

                <label class="space-y-2">
                    <span class="text-sm font-semibold text-gray-700">Data da reciclagem</span>
                    <input type="date" name="data_ultima_reciclagem" value="<?= $oldValue('data_ultima_reciclagem') ?>" data-required-for="vigilante" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm outline-none transition-colors focus:border-brand-red">
                </label>

                <label class="space-y-2">
                    <span class="text-sm font-semibold text-gray-700">Data do vencimento da reciclagem</span>
                    <input type="date" name="validade_reciclagem" value="<?= $oldValue('validade_reciclagem') ?>" data-required-for="vigilante" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm outline-none transition-colors focus:border-brand-red">
                </label>

                <label class="space-y-2 md:col-span-2">
                    <span class="text-sm font-semibold text-gray-700">Situação da reciclagem</span>
                    <select name="situacao_reciclagem" data-required-for="vigilante" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm outline-none transition-colors focus:border-brand-red">
                        <option value="Valida" <?= $isChecked('situacao_reciclagem', 'Valida', true) ? 'selected' : '' ?>>Válida</option>
                        <option value="Vencida" <?= $isChecked('situacao_reciclagem', 'Vencida') ? 'selected' : '' ?>>Vencida</option>
                        <option value="Em andamento" <?= $isChecked('situacao_reciclagem', 'Em andamento') ? 'selected' : '' ?>>Em andamento</option>
                    </select>
                </label>

                <div class="space-y-3 md:col-span-2">
                    <span class="text-sm font-semibold text-gray-700">Possui outros cursos</span>
                    <div class="grid gap-3 md:grid-cols-3">
                        <label class="flex items-center gap-3 rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-700">
                            <input type="checkbox" name="outros_cursos[]" value="escolta_armada" <?= $hasCourse('escolta_armada') ? 'checked' : '' ?> class="h-4 w-4 rounded border-gray-300 text-brand-red focus:ring-brand-red">
                            Escolta armada
                        </label>
                        <label class="flex items-center gap-3 rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-700">
                            <input type="checkbox" name="outros_cursos[]" value="seguranca_eventos" <?= $hasCourse('seguranca_eventos') ? 'checked' : '' ?> class="h-4 w-4 rounded border-gray-300 text-brand-red focus:ring-brand-red">
                            Segurança de Eventos
                        </label>
                        <label class="flex items-center gap-3 rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-700">
                            <input type="checkbox" name="outros_cursos[]" value="seguranca_vip" <?= $hasCourse('seguranca_vip') ? 'checked' : '' ?> class="h-4 w-4 rounded border-gray-300 text-brand-red focus:ring-brand-red">
                            Segurança VIP
                        </label>
                    </div>
                </div>
            </div>
        </section>

        <div class="pt-1">
            <button type="submit" class="inline-flex w-full items-center justify-center rounded-2xl bg-brand-red px-4 py-4 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-red-700">
                <i class="ph <?= $submitIcon ?> mr-2 text-lg"></i>
                <?= $submitLabel ?>
            </button>
        </div>
    </div>
</form>

<div id="crop-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/70 px-4 py-8">
    <div class="w-full max-w-5xl rounded-3xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-gray-100 px-6 py-5">
            <div>
                <h3 class="text-lg font-bold text-gray-900">Ajustar foto do colaborador</h3>
                <p class="mt-1 text-sm text-gray-500">Posicione a imagem e aplique o recorte final.</p>
            </div>
            <button type="button" id="crop-close-button" class="inline-flex h-11 w-11 items-center justify-center rounded-full text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-700">
                <i class="ph ph-x text-2xl"></i>
            </button>
        </div>

        <div class="grid gap-6 px-6 py-6 lg:grid-cols-[minmax(0,1fr)_280px]">
            <div class="space-y-4">
                <div class="overflow-hidden rounded-3xl bg-gray-950/95 p-3">
                    <div class="h-[420px] overflow-hidden rounded-2xl bg-gray-900">
                        <img id="crop-image" src="" alt="Imagem para crop" class="block max-h-full w-full object-contain">
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-4">
                    <div class="flex flex-wrap items-center gap-3">
                        <div class="flex items-center gap-3">
                            <button type="button" id="crop-zoom-out-button" class="inline-flex h-11 w-11 items-center justify-center rounded-xl border border-gray-200 text-gray-700 transition-colors hover:border-gray-300 hover:bg-gray-50">
                                <i class="ph ph-minus text-xl"></i>
                            </button>
                            <button type="button" id="crop-zoom-in-button" class="inline-flex h-11 w-11 items-center justify-center rounded-xl border border-gray-200 text-gray-700 transition-colors hover:border-gray-300 hover:bg-gray-50">
                                <i class="ph ph-plus text-xl"></i>
                            </button>
                        </div>

                        <div class="flex flex-1 flex-wrap items-center justify-end gap-3">
                            <button type="button" id="crop-apply-button" class="inline-flex min-w-[180px] items-center justify-center rounded-xl bg-brand-red px-5 py-3 text-sm font-semibold text-white transition-colors hover:bg-red-700">
                                <i class="ph ph-check mr-2 text-lg"></i>
                                ACEITAR
                            </button>
                            <button type="button" id="crop-cancel-button" class="inline-flex min-w-[200px] items-center justify-center rounded-xl border border-gray-200 bg-white px-5 py-3 text-sm font-semibold text-gray-700 transition-colors hover:border-gray-300 hover:text-gray-900">
                                Escolher outra foto
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://unpkg.com/cropperjs@1.6.2/dist/cropper.min.js"></script>
<script>
    (() => {
        const maxPhotoUploadBytes = <?= \Helpers\MediaStorage::getMaxAllowedFileSizeBytes() ?>;
        const maxPhotoUploadLabel = <?= json_encode(\Helpers\MediaStorage::getMaxAllowedFileSizeLabel(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        const initialPhotoUrl = <?= json_encode($existingPhotoUrl, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        const body = document.body;
        const cropModal = document.getElementById('crop-modal');
        const cropImage = document.getElementById('crop-image');
        const cropApplyButton = document.getElementById('crop-apply-button');
        const cropCancelButton = document.getElementById('crop-cancel-button');
        const cropCloseButton = document.getElementById('crop-close-button');
        const cropZoomOutButton = document.getElementById('crop-zoom-out-button');
        const cropZoomInButton = document.getElementById('crop-zoom-in-button');
        const collaboratorModal = document.getElementById('collaborator-modal');
        const createSuccessToast = document.getElementById('rh-create-success-toast');
        const form = document.getElementById('rh-create-form');
        const photoSurfaceButton = document.getElementById('photo-surface-button');
        const photoSourceInput = document.getElementById('photo-source-input');
        const photoUploadInput = document.getElementById('photo-upload-input');
        const photoCurrentUrlInput = document.getElementById('photo-current-url-input');
        const photoSelectButton = document.getElementById('photo-select-button');
        const photoEditButton = document.getElementById('photo-edit-button');
        const photoClearButton = document.getElementById('photo-clear-button');
        const photoPreview = document.getElementById('photo-preview');
        const photoPlaceholder = document.getElementById('photo-placeholder');
        const photoStatus = document.getElementById('photo-status');
        const documentUploadInput = document.getElementById('document-upload-input');
        const documentSelectedList = document.getElementById('document-selected-list');
        const documentStatus = document.getElementById('document-status');
        const registrationTypeInputs = document.querySelectorAll('.js-registration-type');
        const registrationTypeCards = document.querySelectorAll('[data-type-card]');
        const rhModuleInputs = document.querySelectorAll('.js-rh-module');
        const rhModuleCards = document.querySelectorAll('[data-module-card]');
        const vigilanteBlocks = document.querySelectorAll('.js-vigilante-only');
        const adminBlocks = document.querySelectorAll('.js-admin-only');
        const vigilanteRequiredFields = document.querySelectorAll('[data-required-for="vigilante"]');
        const cepInput = document.getElementById('cep-input');
        const logradouroInput = document.getElementById('logradouro-input');
        const bairroInput = document.getElementById('bairro-input');
        const cidadeInput = document.getElementById('cidade-input');
        const ufInput = document.getElementById('uf-input');

        let cropper = null;
        let sourceImageUrl = null;
        let selectedDocumentFiles = [];
        const maxDocumentUploadCount = 10;

        function setPhotoError(message) {
            if (!photoStatus) {
                window.alert(message);
                return;
            }

            photoStatus.textContent = message;
            photoStatus.classList.remove('sr-only');
            photoStatus.classList.add('mt-3', 'text-center', 'text-xs');
            photoStatus.classList.add('text-brand-red');
        }

        function getSelectedRegistrationType() {
            const checkedInput = Array.from(registrationTypeInputs).find((input) => input.checked);
            return checkedInput ? checkedInput.value : 'vigilante';
        }

        function syncRegistrationTypeUI() {
            const currentType = getSelectedRegistrationType();
            const isVigilante = currentType === 'vigilante';

            registrationTypeCards.forEach((card) => {
                const isActive = card.dataset.typeCard === currentType;
                const iconBadge = card.querySelector('span');
                card.classList.toggle('border-brand-red', isActive);
                card.classList.toggle('bg-red-50', isActive);
                card.classList.toggle('border-gray-200', !isActive);
                card.classList.toggle('hover:border-red-200', !isActive);

                if (iconBadge) {
                    iconBadge.classList.toggle('bg-brand-red', isActive);
                    iconBadge.classList.toggle('text-white', isActive);
                    iconBadge.classList.toggle('bg-gray-100', !isActive);
                    iconBadge.classList.toggle('text-gray-500', !isActive);
                }
            });

            vigilanteBlocks.forEach((element) => {
                element.classList.toggle('hidden', !isVigilante);
            });

            adminBlocks.forEach((element) => {
                element.classList.toggle('hidden', isVigilante);
            });

            vigilanteRequiredFields.forEach((field) => {
                field.required = isVigilante;
            });
        }

        function getSelectedRhModule() {
            const checkedInput = Array.from(rhModuleInputs).find((input) => input.checked);
            return checkedInput ? checkedInput.value : 'seguranca_privada';
        }

        function syncRhModuleUI() {
            const currentModule = getSelectedRhModule();

            rhModuleCards.forEach((card) => {
                const isActive = card.dataset.moduleCard === currentModule;
                const iconBadge = card.querySelector('span');
                card.classList.toggle('border-brand-red', isActive);
                card.classList.toggle('bg-red-50', isActive);
                card.classList.toggle('border-gray-200', !isActive);
                card.classList.toggle('hover:border-red-200', !isActive);

                if (iconBadge) {
                    iconBadge.classList.toggle('bg-brand-red', isActive);
                    iconBadge.classList.toggle('text-white', isActive);
                    iconBadge.classList.toggle('bg-gray-100', !isActive);
                    iconBadge.classList.toggle('text-gray-500', !isActive);
                }
            });
        }

        function applyMask(value, maskType) {
            const digits = value.replace(/\D+/g, '');

            if (maskType === 'cpf') {
                return digits
                    .replace(/^(\d{3})(\d)/, '$1.$2')
                    .replace(/^(\d{3})\.(\d{3})(\d)/, '$1.$2.$3')
                    .replace(/\.(\d{3})(\d)/, '.$1-$2')
                    .slice(0, 14);
            }

            if (maskType === 'cep') {
                return digits.replace(/^(\d{5})(\d)/, '$1-$2').slice(0, 9);
            }

            if (maskType === 'phone') {
                if (digits.length <= 10) {
                    return digits
                        .replace(/^(\d{2})(\d)/, '($1) $2')
                        .replace(/(\d{4})(\d)/, '$1-$2')
                        .slice(0, 14);
                }

                return digits
                    .replace(/^(\d{2})(\d)/, '($1) $2')
                    .replace(/(\d{5})(\d)/, '$1-$2')
                    .slice(0, 15);
            }

            return value;
        }

        document.querySelectorAll('[data-mask]').forEach((input) => {
            input.addEventListener('input', (event) => {
                event.target.value = applyMask(event.target.value, event.target.dataset.mask);
            });
        });

        function setDocumentError(message) {
            if (!documentStatus) {
                window.alert(message);
                return;
            }

            documentStatus.textContent = message;
            documentStatus.classList.remove('sr-only', 'text-gray-500');
            documentStatus.classList.add('text-sm', 'font-semibold', 'text-brand-red');
        }

        function clearDocumentStatus() {
            if (!documentStatus) {
                return;
            }

            documentStatus.textContent = '';
            documentStatus.classList.add('sr-only');
            documentStatus.classList.remove('text-sm', 'font-semibold', 'text-brand-red', 'text-gray-500');
        }

        function escapeHtml(value) {
            return String(value).replace(/[&<>"']/g, (char) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            }[char]));
        }

        function getDocumentFileKey(file) {
            return [file.name, file.size, file.lastModified].join('|');
        }

        function syncDocumentUploadInput() {
            if (!documentUploadInput || typeof DataTransfer !== 'function') {
                return;
            }

            const fileTransfer = new DataTransfer();
            selectedDocumentFiles.forEach((file) => fileTransfer.items.add(file));
            documentUploadInput.files = fileTransfer.files;
        }

        function validateDocumentFile(file) {
            const isPdfByType = file.type === 'application/pdf';
            const isPdfByName = file.name.toLowerCase().endsWith('.pdf');

            if (!isPdfByType && !isPdfByName) {
                return 'Anexe somente documentos em PDF.';
            }

            if (file.size > maxPhotoUploadBytes) {
                return 'Cada PDF deve ter at\u00e9 ' + maxPhotoUploadLabel + '.';
            }

            return '';
        }

        function renderSelectedDocuments() {
            if (!documentUploadInput || !documentSelectedList) {
                return;
            }

            clearDocumentStatus();
            const files = selectedDocumentFiles;

            if (files.length === 0) {
                documentSelectedList.classList.add('hidden');
                documentSelectedList.innerHTML = '';
                return;
            }

            documentSelectedList.classList.remove('hidden');
            documentSelectedList.innerHTML = [
                '<p class="mb-3 text-sm font-semibold text-gray-700">Documentos selecionados</p>',
                ...files.map((file, index) => {
                    const sizeMb = (file.size / (1024 * 1024)).toFixed(2);
                    return '<div class="flex items-center gap-3 rounded-xl border border-gray-100 px-3 py-2">' +
                        '<span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-red-50 text-brand-red"><i class="ph ph-file-pdf text-base"></i></span>' +
                        '<span class="min-w-0 flex-1 truncate">' + escapeHtml(file.name) + '</span>' +
                        '<span class="text-xs text-gray-400">' + sizeMb + ' MB</span>' +
                        '<button type="button" data-remove-document-index="' + index + '" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-gray-400 transition-colors hover:bg-red-50 hover:text-brand-red" title="Remover documento"><i class="ph ph-x text-base"></i></button>' +
                    '</div>';
                })
            ].join('');

            documentSelectedList.querySelectorAll('[data-remove-document-index]').forEach((button) => {
                button.addEventListener('click', () => {
                    const index = Number(button.dataset.removeDocumentIndex);
                    selectedDocumentFiles = selectedDocumentFiles.filter((_, fileIndex) => fileIndex !== index);
                    syncDocumentUploadInput();
                    renderSelectedDocuments();
                });
            });
        }

        function mergeSelectedDocuments(newFiles) {
            if (!documentUploadInput || newFiles.length === 0) {
                return;
            }

            const validationError = newFiles.map(validateDocumentFile).find((message) => message !== '');
            if (validationError) {
                documentUploadInput.value = '';
                syncDocumentUploadInput();
                renderSelectedDocuments();
                setDocumentError(validationError);
                return;
            }

            const selectedKeys = new Set(selectedDocumentFiles.map(getDocumentFileKey));
            const mergedFiles = [...selectedDocumentFiles];

            newFiles.forEach((file) => {
                const fileKey = getDocumentFileKey(file);
                if (!selectedKeys.has(fileKey)) {
                    selectedKeys.add(fileKey);
                    mergedFiles.push(file);
                }
            });

            if (mergedFiles.length > maxDocumentUploadCount) {
                documentUploadInput.value = '';
                syncDocumentUploadInput();
                renderSelectedDocuments();
                setDocumentError('Selecione no m\u00e1ximo ' + maxDocumentUploadCount + ' documentos PDF neste cadastro.');
                return;
            }

            selectedDocumentFiles = mergedFiles;
            syncDocumentUploadInput();
            renderSelectedDocuments();
        }

        function updatePhotoPreview(file) {
            if (photoStatus) {
                photoStatus.classList.remove('text-brand-red');
                photoStatus.classList.add('sr-only');
                photoStatus.classList.remove('mt-3', 'text-center', 'text-xs');
            }

            if (!file) {
                photoPreview.src = '';
                photoPreview.classList.add('hidden');
                photoPlaceholder.classList.remove('hidden');
                if (photoEditButton) {
                    photoEditButton.disabled = true;
                }
                if (photoStatus) {
                    photoStatus.textContent = 'Nenhuma imagem selecionada.';
                }
                return;
            }

            const previewUrl = URL.createObjectURL(file);
            photoPreview.src = previewUrl;
            photoPreview.classList.remove('hidden');
            photoPlaceholder.classList.add('hidden');
            if (photoEditButton) {
                photoEditButton.disabled = false;
            }
            if (photoStatus) {
                photoStatus.textContent = 'Foto pronta para envio.';
            }
        }

        function destroyCropper() {
            if (cropper) {
                cropper.destroy();
                cropper = null;
            }
        }

        function closeCropModal() {
            destroyCropper();
            cropModal.classList.add('hidden');
            cropModal.classList.remove('flex');

            if (!collaboratorModal || collaboratorModal.classList.contains('hidden')) {
                body.classList.remove('overflow-hidden');
            }
        }

        function openCropModalFromFile(file) {
            if (!file) {
                return;
            }

            if (typeof window.Cropper !== 'function') {
                setPhotoError('O editor de foto não carregou corretamente. Recarregue a página e tente novamente.');
                return;
            }

            if (sourceImageUrl) {
                URL.revokeObjectURL(sourceImageUrl);
            }

            sourceImageUrl = URL.createObjectURL(file);
            cropModal.classList.remove('hidden');
            cropModal.classList.add('flex');
            body.classList.add('overflow-hidden');

            destroyCropper();
            cropImage.onload = () => {
                destroyCropper();
                cropper = new window.Cropper(cropImage, {
                    aspectRatio: 1,
                    viewMode: 1,
                    autoCropArea: 0.92,
                    background: false,
                    responsive: true,
                    dragMode: 'move',
                    zoomOnWheel: true,
                    zoomOnTouch: true,
                    guides: true,
                    center: true,
                    highlight: false,
                    cropBoxMovable: false,
                    cropBoxResizable: false,
                    toggleDragModeOnDblclick: false,
                    minContainerWidth: 320,
                    minContainerHeight: 320
                });
            };

            cropImage.onerror = () => {
                setPhotoError('Não foi possível abrir a foto selecionada.');
                closeCropModal();
            };

            cropImage.src = sourceImageUrl;
        }

        function openPhotoChooser() {
            photoSourceInput.click();
        }

        photoSurfaceButton.addEventListener('click', () => {
            const file = photoSourceInput.files && photoSourceInput.files[0];

            if (!file) {
                openPhotoChooser();
                return;
            }

            openCropModalFromFile(file);
        });

        if (photoSelectButton) {
            photoSelectButton.addEventListener('click', () => {
                openPhotoChooser();
            });
        }

        photoSourceInput.addEventListener('change', () => {
            const file = photoSourceInput.files && photoSourceInput.files[0];
            if (!file) {
                return;
            }

            openCropModalFromFile(file);
        });

        if (documentUploadInput) {
            documentUploadInput.addEventListener('change', () => {
                mergeSelectedDocuments(Array.from(documentUploadInput.files || []));
            });
        }

        if (photoEditButton) {
            photoEditButton.addEventListener('click', () => {
                const file = photoSourceInput.files && photoSourceInput.files[0];
                if (!file) {
                    return;
                }

                openCropModalFromFile(file);
            });
        }

        if (photoClearButton) {
            photoClearButton.addEventListener('click', () => {
                photoSourceInput.value = '';
                photoUploadInput.value = '';
                if (photoCurrentUrlInput) {
                    photoCurrentUrlInput.value = '';
                }
                updatePhotoPreview(null);
            });
        }

        cropZoomOutButton.addEventListener('click', () => {
            if (cropper) {
                cropper.zoom(-0.1);
            }
        });

        cropZoomInButton.addEventListener('click', () => {
            if (cropper) {
                cropper.zoom(0.1);
            }
        });

        cropApplyButton.addEventListener('click', () => {
            if (!cropper) {
                return;
            }

            const outputVariants = [
                { width: 720, height: 720, quality: 0.88 },
                { width: 640, height: 640, quality: 0.84 },
                { width: 560, height: 560, quality: 0.8 },
                { width: 480, height: 480, quality: 0.78 }
            ];

            const tryBuildBlob = (variantIndex = 0) => {
                if (variantIndex >= outputVariants.length) {
                    setPhotoError('Não foi possível preparar a foto dentro do limite de ' + maxPhotoUploadLabel + '. Escolha outra imagem e tente novamente.');
                    return;
                }

                const variant = outputVariants[variantIndex];
                const canvas = cropper.getCroppedCanvas({
                    width: variant.width,
                    height: variant.height,
                    fillColor: '#ffffff',
                    imageSmoothingEnabled: true,
                    imageSmoothingQuality: 'high'
                });

                if (!canvas) {
                    setPhotoError('Não foi possível gerar o recorte da foto.');
                    return;
                }

                canvas.toBlob((blob) => {
                    if (!blob) {
                        setPhotoError('Não foi possível gerar o arquivo final da foto.');
                        return;
                    }

                    if (blob.size > maxPhotoUploadBytes) {
                        tryBuildBlob(variantIndex + 1);
                        return;
                    }

                    const croppedFile = new File([blob], 'foto-colaborador.jpg', { type: 'image/jpeg' });
                    const fileTransfer = new DataTransfer();
                    fileTransfer.items.add(croppedFile);
                    photoUploadInput.files = fileTransfer.files;

                    updatePhotoPreview(croppedFile);
                    closeCropModal();
                }, 'image/jpeg', variant.quality);
            };

            tryBuildBlob();
        });

        cropCancelButton.addEventListener('click', () => {
            closeCropModal();
            openPhotoChooser();
        });

        cropCloseButton.addEventListener('click', closeCropModal);

        cropModal.addEventListener('click', (event) => {
            if (event.target === cropModal) {
                closeCropModal();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !cropModal.classList.contains('hidden')) {
                closeCropModal();
            }
        });

        registrationTypeInputs.forEach((input) => {
            input.addEventListener('change', syncRegistrationTypeUI);
        });

        rhModuleInputs.forEach((input) => {
            input.addEventListener('change', syncRhModuleUI);
        });

        async function lookupCep() {
            const cep = (cepInput.value || '').replace(/\D+/g, '');
            if (cep.length !== 8) {
                return;
            }

            try {
                const response = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
                if (!response.ok) {
                    return;
                }

                const data = await response.json();
                if (data.erro) {
                    return;
                }

                if (!logradouroInput.value) {
                    logradouroInput.value = data.logradouro || '';
                }
                if (!bairroInput.value) {
                    bairroInput.value = data.bairro || '';
                }
                if (!cidadeInput.value) {
                    cidadeInput.value = data.localidade || '';
                }
                if (!ufInput.value) {
                    ufInput.value = data.uf || '';
                }
            } catch (error) {
                console.error('Falha ao consultar CEP:', error);
            }
        }

        cepInput.addEventListener('blur', lookupCep);

        form.addEventListener('submit', (event) => {
            const hasUploadedPhoto = !!(photoUploadInput.files && photoUploadInput.files.length);
            const hasCurrentPhoto = !!(photoCurrentUrlInput && photoCurrentUrlInput.value.trim() !== '');

            if (!hasUploadedPhoto && !hasCurrentPhoto) {
                event.preventDefault();
                setPhotoError('Selecione a foto, ajuste o enquadramento e clique em ACEITAR antes de salvar.');
                photoSurfaceButton.focus();
            }
        });

        if (initialPhotoUrl && photoEditButton) {
            photoEditButton.disabled = false;
        }

        if (createSuccessToast) {
            window.setTimeout(() => {
                createSuccessToast.classList.add('pointer-events-none', 'scale-95', 'opacity-0');

                window.setTimeout(() => {
                    createSuccessToast.remove();
                }, 320);
            }, 4200);
        }

        syncRegistrationTypeUI();
        syncRhModuleUI();
    })();
</script>

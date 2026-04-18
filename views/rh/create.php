<?php
    $old = is_array($old ?? null) ? $old : [];
    $selectedType = $old['tipo_cadastro'] ?? 'vigilante';
    $selectedAdminRole = $old['funcao_administrativa'] ?? 'Administrativo';
    $selectedCourses = is_array($old['outros_cursos'] ?? null) ? $old['outros_cursos'] : [];
    $selectedBloodType = $old['tipo_sanguineo'] ?? '';
    $selectedRhFactor = $old['fator_rh'] ?? '+';

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
                <p class="font-semibold">Nao foi possivel concluir o cadastro.</p>
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
                            <p class="text-[11px] uppercase tracking-wide text-green-700">Senha provisoria</p>
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

<form id="rh-create-form" action="/rh/colaboradores" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
    <div class="space-y-6">
        <section class="rounded-3xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 px-6 py-5">
                <h3 class="text-lg font-bold text-gray-900">Tipo de cadastro</h3>
                <p class="mt-1 text-sm text-gray-500">Escolha qual fluxo deve ser aplicado neste colaborador.</p>
            </div>

            <div class="grid gap-4 px-6 py-6 md:grid-cols-2">
                <label data-type-card="vigilante" class="group cursor-pointer rounded-2xl border p-5 transition-colors <?= $selectedType === 'vigilante' ? 'border-brand-red bg-red-50' : 'border-gray-200 hover:border-red-200' ?>">
                    <input type="radio" name="tipo_cadastro" value="vigilante" class="sr-only js-registration-type" <?= $selectedType === 'vigilante' ? 'checked' : '' ?>>
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-semibold text-gray-900">Colaborador Vigilante</p>
                            <p class="mt-2 text-sm text-gray-500">Abre CNV, reciclagem, cursos extras e documentos internos.</p>
                        </div>
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-full <?= $selectedType === 'vigilante' ? 'bg-brand-red text-white' : 'bg-gray-100 text-gray-500' ?>">
                            <i class="ph ph-shield-check text-xl"></i>
                        </span>
                    </div>
                </label>

                <label data-type-card="financeiro_administrativo" class="group cursor-pointer rounded-2xl border p-5 transition-colors <?= $selectedType === 'financeiro_administrativo' ? 'border-brand-red bg-red-50' : 'border-gray-200 hover:border-red-200' ?>">
                    <input type="radio" name="tipo_cadastro" value="financeiro_administrativo" class="sr-only js-registration-type" <?= $selectedType === 'financeiro_administrativo' ? 'checked' : '' ?>>
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-semibold text-gray-900">Financeiro / Administrativo</p>
                            <p class="mt-2 text-sm text-gray-500">Mantem somente dados pessoais e profissionais basicos.</p>
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
                <h3 class="text-lg font-bold text-gray-900">Foto do colaborador</h3>
                <p class="mt-1 text-sm text-gray-500">Selecione a imagem, ajuste o crop e confirme antes de salvar.</p>
            </div>

            <div class="grid gap-6 px-6 py-6 lg:grid-cols-[220px_minmax(0,1fr)] lg:items-start">
                <div class="flex flex-col items-center">
                    <button
                        type="button"
                        id="photo-surface-button"
                        class="group relative h-44 w-44 overflow-hidden rounded-3xl border border-dashed border-gray-300 bg-gray-50 text-left transition-colors hover:border-brand-red hover:bg-red-50/40 focus:outline-none focus:ring-2 focus:ring-brand-red/20"
                    >
                        <img id="photo-preview" src="" alt="Preview da foto" class="hidden h-full w-full object-cover">
                        <div id="photo-placeholder" class="flex h-full w-full flex-col items-center justify-center px-6 text-center text-gray-400 transition-colors group-hover:text-brand-red">
                            <i class="ph ph-user-circle text-5xl"></i>
                            <p class="mt-3 text-sm font-medium">Sem foto recortada</p>
                            <p class="mt-2 text-xs font-semibold uppercase tracking-[0.16em] text-gray-400 group-hover:text-brand-red">Toque para escolher</p>
                        </div>
                    </button>
                </div>

                <div class="space-y-4">
                    <input id="photo-source-input" type="file" accept="image/*" class="hidden">
                    <input id="photo-upload-input" type="file" name="foto_colaborador" accept="image/*" class="hidden">

                    <div class="rounded-2xl border border-gray-200 bg-gray-50 p-4">
                        <p class="text-sm font-semibold text-gray-900">Fluxo da foto</p>
                        <p class="mt-2 text-sm text-gray-500">
                            1. Toque no quadro da foto ou em selecionar foto
                            <br>
                            2. Ajuste o enquadramento com arraste e zoom
                            <br>
                            3. Clique em ACEITAR
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <button type="button" id="photo-select-button" class="inline-flex items-center justify-center rounded-xl bg-brand-red px-4 py-3 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-red-700">
                            <i class="ph ph-image mr-2 text-lg"></i>
                            Selecionar foto
                        </button>
                        <button type="button" id="photo-edit-button" class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-semibold text-gray-700 transition-colors hover:border-gray-300 hover:text-gray-900" disabled>
                            <i class="ph ph-crop mr-2 text-lg"></i>
                            Editar crop
                        </button>
                        <button type="button" id="photo-clear-button" class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-semibold text-gray-500 transition-colors hover:border-gray-300 hover:text-gray-800">
                            <i class="ph ph-trash mr-2 text-lg"></i>
                            Limpar
                        </button>
                    </div>

                    <p id="photo-status" class="text-sm text-gray-500">Nenhuma imagem selecionada.</p>
                </div>
            </div>
        </section>

        <section class="rounded-3xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 px-6 py-5">
                <h3 class="text-lg font-bold text-gray-900">Dados pessoais</h3>
                <p class="mt-1 text-sm text-gray-500">Informacoes basicas do colaborador para o cadastro interno.</p>
            </div>

            <div class="grid gap-5 px-6 py-6 md:grid-cols-2">
                <label class="space-y-2 md:col-span-2">
                    <span class="text-sm font-semibold text-gray-700">Nome completo</span>
                    <input type="text" name="nome_completo" value="<?= $oldValue('nome_completo') ?>" required class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm outline-none transition-colors focus:border-brand-red">
                </label>

                <label class="space-y-2">
                    <span class="text-sm font-semibold text-gray-700">CPF</span>
                    <input type="text" name="cpf" value="<?= $oldValue('cpf') ?>" required maxlength="14" data-mask="cpf" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm outline-none transition-colors focus:border-brand-red">
                </label>

                <label class="space-y-2">
                    <span class="text-sm font-semibold text-gray-700">RG</span>
                    <input type="text" name="rg" value="<?= $oldValue('rg') ?>" required class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm outline-none transition-colors focus:border-brand-red">
                </label>

                <label class="space-y-2">
                    <span class="text-sm font-semibold text-gray-700">Data de nascimento</span>
                    <input type="date" name="data_nascimento" value="<?= $oldValue('data_nascimento') ?>" required class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm outline-none transition-colors focus:border-brand-red">
                </label>

                <label class="space-y-2">
                    <span class="text-sm font-semibold text-gray-700">Nome da mae</span>
                    <input type="text" name="nome_mae" value="<?= $oldValue('nome_mae') ?>" required class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm outline-none transition-colors focus:border-brand-red">
                </label>

                <label class="space-y-2">
                    <span class="text-sm font-semibold text-gray-700">Telefone principal</span>
                    <input type="text" name="telefone_principal" value="<?= $oldValue('telefone_principal') ?>" required maxlength="15" data-mask="phone" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm outline-none transition-colors focus:border-brand-red">
                </label>

                <label class="space-y-2">
                    <span class="text-sm font-semibold text-gray-700">Telefone familiar</span>
                    <input type="text" name="telefone_familiar" value="<?= $oldValue('telefone_familiar') ?>" required maxlength="15" data-mask="phone" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm outline-none transition-colors focus:border-brand-red">
                </label>

                <label class="space-y-2">
                    <span class="text-sm font-semibold text-gray-700">Tipo sanguineo</span>
                    <select name="tipo_sanguineo" required class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm outline-none transition-colors focus:border-brand-red">
                        <option value="">Selecione</option>
                        <?php foreach (['A', 'B', 'AB', 'O'] as $bloodType): ?>
                            <option value="<?= $bloodType ?>" <?= $selectedBloodType === $bloodType ? 'selected' : '' ?>><?= $bloodType ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label class="space-y-2">
                    <span class="text-sm font-semibold text-gray-700">Fator RH</span>
                    <select name="fator_rh" required class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm outline-none transition-colors focus:border-brand-red">
                        <?php foreach (['+' => 'Positivo (+)', '-' => 'Negativo (-)'] as $factorValue => $factorLabel): ?>
                            <option value="<?= $factorValue ?>" <?= $selectedRhFactor === $factorValue ? 'selected' : '' ?>><?= $factorLabel ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label class="space-y-2">
                    <span class="text-sm font-semibold text-gray-700">CEP</span>
                    <input type="text" name="cep" value="<?= $oldValue('cep') ?>" required maxlength="9" data-mask="cep" id="cep-input" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm outline-none transition-colors focus:border-brand-red">
                </label>

                <label class="space-y-2">
                    <span class="text-sm font-semibold text-gray-700">UF</span>
                    <input type="text" name="uf" value="<?= $oldValue('uf') ?>" required maxlength="2" id="uf-input" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm uppercase outline-none transition-colors focus:border-brand-red">
                </label>

                <label class="space-y-2 md:col-span-2">
                    <span class="text-sm font-semibold text-gray-700">Logradouro</span>
                    <input type="text" name="logradouro" value="<?= $oldValue('logradouro') ?>" required id="logradouro-input" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm outline-none transition-colors focus:border-brand-red">
                </label>

                <label class="space-y-2">
                    <span class="text-sm font-semibold text-gray-700">Numero</span>
                    <input type="text" name="numero" value="<?= $oldValue('numero') ?>" required class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm outline-none transition-colors focus:border-brand-red">
                </label>

                <label class="space-y-2">
                    <span class="text-sm font-semibold text-gray-700">Bairro</span>
                    <input type="text" name="bairro" value="<?= $oldValue('bairro') ?>" required id="bairro-input" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm outline-none transition-colors focus:border-brand-red">
                </label>

                <label class="space-y-2">
                    <span class="text-sm font-semibold text-gray-700">Complemento</span>
                    <input type="text" name="complemento" value="<?= $oldValue('complemento') ?>" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm outline-none transition-colors focus:border-brand-red">
                </label>

                <label class="space-y-2">
                    <span class="text-sm font-semibold text-gray-700">Cidade</span>
                    <input type="text" name="cidade" value="<?= $oldValue('cidade') ?>" required id="cidade-input" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm outline-none transition-colors focus:border-brand-red">
                </label>
            </div>
        </section>

        <section class="rounded-3xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 px-6 py-5">
                <h3 class="text-lg font-bold text-gray-900">Dados profissionais</h3>
                <p class="mt-1 text-sm text-gray-500">Dados de vinculo, admissao e situacao atual do colaborador.</p>
            </div>

            <div class="grid gap-5 px-6 py-6 md:grid-cols-2">
                <div class="space-y-2 js-vigilante-only<?= $selectedType === 'vigilante' ? '' : ' hidden' ?>">
                    <span class="text-sm font-semibold text-gray-700">Funcao</span>
                    <input type="text" value="VIGILANTE" readonly class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm font-semibold text-gray-700">
                </div>

                <label class="space-y-2 js-admin-only<?= $selectedType === 'vigilante' ? ' hidden' : '' ?>">
                    <span class="text-sm font-semibold text-gray-700">Funcao</span>
                    <select name="funcao_administrativa" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm outline-none transition-colors focus:border-brand-red">
                        <option value="Administrativo" <?= $selectedAdminRole === 'Administrativo' ? 'selected' : '' ?>>Administrativo</option>
                        <option value="Financeiro" <?= $selectedAdminRole === 'Financeiro' ? 'selected' : '' ?>>Financeiro</option>
                    </select>
                </label>

                <label class="space-y-2">
                    <span class="text-sm font-semibold text-gray-700">Tipo de vinculo</span>
                    <select name="tipo_vinculo" required class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm outline-none transition-colors focus:border-brand-red">
                        <option value="CLT" <?= $isChecked('tipo_vinculo', 'CLT', true) ? 'selected' : '' ?>>CLT</option>
                        <option value="Freelancer" <?= $isChecked('tipo_vinculo', 'Freelancer') ? 'selected' : '' ?>>Freelancer</option>
                    </select>
                </label>

                <label class="space-y-2">
                    <span class="text-sm font-semibold text-gray-700">Data de admissao</span>
                    <input type="date" name="data_admissao" value="<?= $oldValue('data_admissao') ?>" required class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm outline-none transition-colors focus:border-brand-red">
                </label>

                <label class="space-y-2">
                    <span class="text-sm font-semibold text-gray-700">Numero da admissao</span>
                    <input type="text" name="numero_admissao" value="<?= $oldValue('numero_admissao') ?>" required class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm outline-none transition-colors focus:border-brand-red">
                </label>

                <label class="space-y-2">
                    <span class="text-sm font-semibold text-gray-700">Situacao</span>
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
                <h3 class="text-lg font-bold text-gray-900">Dados de formacao do vigilante</h3>
                <p class="mt-1 text-sm text-gray-500">Campos especificos para CNV, reciclagem e cursos complementares.</p>
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
                    <span class="text-sm font-semibold text-gray-700">Curso de formacao</span>
                    <select name="curso_formacao" data-required-for="vigilante" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm outline-none transition-colors focus:border-brand-red">
                        <option value="Sim" <?= $isChecked('curso_formacao', 'Sim', true) ? 'selected' : '' ?>>Sim</option>
                        <option value="Nao" <?= $isChecked('curso_formacao', 'Nao') ? 'selected' : '' ?>>Nao</option>
                    </select>
                </label>

                <label class="space-y-2">
                    <span class="text-sm font-semibold text-gray-700">Data da ultima reciclagem</span>
                    <input type="date" name="data_ultima_reciclagem" value="<?= $oldValue('data_ultima_reciclagem') ?>" data-required-for="vigilante" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm outline-none transition-colors focus:border-brand-red">
                </label>

                <label class="space-y-2 md:col-span-2">
                    <span class="text-sm font-semibold text-gray-700">Situacao da reciclagem</span>
                    <select name="situacao_reciclagem" data-required-for="vigilante" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm outline-none transition-colors focus:border-brand-red">
                        <option value="Valida" <?= $isChecked('situacao_reciclagem', 'Valida', true) ? 'selected' : '' ?>>Valida</option>
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
                            Seguranca de Eventos
                        </label>
                        <label class="flex items-center gap-3 rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-700">
                            <input type="checkbox" name="outros_cursos[]" value="seguranca_vip" <?= $hasCourse('seguranca_vip') ? 'checked' : '' ?> class="h-4 w-4 rounded border-gray-300 text-brand-red focus:ring-brand-red">
                            Seguranca VIP
                        </label>
                    </div>
                </div>
            </div>
        </section>

        <section class="rounded-3xl border border-gray-200 bg-white shadow-sm js-vigilante-only<?= $selectedType === 'vigilante' ? '' : ' hidden' ?>">
            <div class="border-b border-gray-100 px-6 py-5">
                <h3 class="text-lg font-bold text-gray-900">Documentos internos</h3>
                <p class="mt-1 text-sm text-gray-500">Envie PDF, imagem, DOC ou DOCX para manter os anexos do colaborador.</p>
            </div>

            <div class="grid gap-5 px-6 py-6 md:grid-cols-2">
                <label class="space-y-2">
                    <span class="text-sm font-semibold text-gray-700">Termo de responsabilidade uso do app</span>
                    <input type="file" name="termo_responsabilidade" accept=".pdf,.doc,.docx,image/*" class="block w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-600 file:mr-4 file:rounded-lg file:border-0 file:bg-gray-100 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-gray-700">
                </label>

                <label class="space-y-2">
                    <span class="text-sm font-semibold text-gray-700">Contrato de trabalho</span>
                    <input type="file" name="contrato_trabalho" accept=".pdf,.doc,.docx,image/*" class="block w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-600 file:mr-4 file:rounded-lg file:border-0 file:bg-gray-100 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-gray-700">
                </label>

                <label class="space-y-2">
                    <span class="text-sm font-semibold text-gray-700">Ficha de EPI</span>
                    <input type="file" name="ficha_epi" accept=".pdf,.doc,.docx,image/*" class="block w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-600 file:mr-4 file:rounded-lg file:border-0 file:bg-gray-100 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-gray-700">
                </label>

                <label class="space-y-2">
                    <span class="text-sm font-semibold text-gray-700">Ordem de Servico</span>
                    <input type="file" name="ordem_servico" accept=".pdf,.doc,.docx,image/*" class="block w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-600 file:mr-4 file:rounded-lg file:border-0 file:bg-gray-100 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-gray-700">
                </label>

                <label class="space-y-2 md:col-span-2">
                    <span class="text-sm font-semibold text-gray-700">Regulamento Interno</span>
                    <input type="file" name="regulamento_interno" accept=".pdf,.doc,.docx,image/*" class="block w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-600 file:mr-4 file:rounded-lg file:border-0 file:bg-gray-100 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-gray-700">
                </label>
            </div>
        </section>
    </div>

    <aside class="space-y-6">
        <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-bold text-gray-900">Acesso ao sistema</h3>
            <p class="mt-2 text-sm text-gray-500">
                Estes campos sao opcionais. Se voce deixar em branco, o sistema gera um e-mail interno e uma senha provisoria.
            </p>

            <div class="mt-5 space-y-4">
                <label class="space-y-2">
                    <span class="text-sm font-semibold text-gray-700">E-mail de acesso</span>
                    <input type="email" name="email_acesso" value="<?= $oldValue('email_acesso') ?>" placeholder="colaborador@empresa.com" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm outline-none transition-colors focus:border-brand-red">
                </label>

                <label class="space-y-2">
                    <span class="text-sm font-semibold text-gray-700">Senha provisoria</span>
                    <input type="text" name="senha_provisoria" value="" placeholder="Deixe em branco para gerar" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm outline-none transition-colors focus:border-brand-red">
                </label>
            </div>
        </section>

        <div class="rounded-3xl border border-red-100 bg-red-50 p-6 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-brand-red">Acao</p>
            <h3 class="mt-2 text-lg font-bold text-gray-900">Salvar cadastro</h3>
            <p class="mt-2 text-sm text-gray-600">
                O cadastro cria o usuario do sistema, o colaborador no RH, os detalhes pessoais e, no caso de vigilante,
                tambem o registro operacional com documentacao.
            </p>

            <button type="submit" class="mt-5 inline-flex w-full items-center justify-center rounded-2xl bg-brand-red px-4 py-4 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-red-700">
                <i class="ph ph-floppy-disk mr-2 text-lg"></i>
                Salvar novo colaborador
            </button>
        </div>
    </aside>
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
            <div class="overflow-hidden rounded-3xl bg-gray-950/95 p-3">
                <div class="h-[420px] overflow-hidden rounded-2xl bg-gray-900">
                    <img id="crop-image" src="" alt="Imagem para crop" class="block max-h-full w-full object-contain">
                </div>
            </div>

            <div class="space-y-4">
                <div class="rounded-2xl border border-gray-200 bg-gray-50 p-4">
                    <p class="text-sm font-semibold text-gray-900">Dica de enquadramento</p>
                    <p class="mt-2 text-sm text-gray-500">
                        Arraste a foto para os lados, aproxime com zoom e priorize rosto e ombros.
                        O sistema salva um recorte quadrado padrao para o cadastro.
                    </p>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-4">
                    <p class="text-sm font-semibold text-gray-900">Controles</p>
                    <div class="mt-4 flex gap-3">
                        <button type="button" id="crop-zoom-out-button" class="inline-flex h-12 w-12 items-center justify-center rounded-2xl border border-gray-200 text-gray-700 transition-colors hover:border-gray-300 hover:bg-gray-50">
                            <i class="ph ph-minus text-xl"></i>
                        </button>
                        <button type="button" id="crop-zoom-in-button" class="inline-flex h-12 w-12 items-center justify-center rounded-2xl border border-gray-200 text-gray-700 transition-colors hover:border-gray-300 hover:bg-gray-50">
                            <i class="ph ph-plus text-xl"></i>
                        </button>
                    </div>
                    <p class="mt-3 text-xs text-gray-500">Use os botoes de zoom e arraste a imagem diretamente na area de recorte.</p>
                </div>

                <div class="grid gap-3">
                    <button type="button" id="crop-apply-button" class="inline-flex items-center justify-center rounded-2xl bg-brand-red px-4 py-4 text-sm font-semibold text-white transition-colors hover:bg-red-700">
                        <i class="ph ph-check mr-2 text-lg"></i>
                        ACEITAR
                    </button>
                    <button type="button" id="crop-cancel-button" class="inline-flex items-center justify-center rounded-2xl border border-gray-200 bg-white px-4 py-4 text-sm font-semibold text-gray-700 transition-colors hover:border-gray-300 hover:text-gray-900">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/cropperjs@1.6.2/dist/cropper.min.js"></script>
<script>
    (() => {
        const body = document.body;
        const cropModal = document.getElementById('crop-modal');
        const cropImage = document.getElementById('crop-image');
        const cropApplyButton = document.getElementById('crop-apply-button');
        const cropCancelButton = document.getElementById('crop-cancel-button');
        const cropCloseButton = document.getElementById('crop-close-button');
        const cropZoomOutButton = document.getElementById('crop-zoom-out-button');
        const cropZoomInButton = document.getElementById('crop-zoom-in-button');
        const collaboratorModal = document.getElementById('collaborator-modal');
        const form = document.getElementById('rh-create-form');
        const photoSurfaceButton = document.getElementById('photo-surface-button');
        const photoSourceInput = document.getElementById('photo-source-input');
        const photoUploadInput = document.getElementById('photo-upload-input');
        const photoSelectButton = document.getElementById('photo-select-button');
        const photoEditButton = document.getElementById('photo-edit-button');
        const photoClearButton = document.getElementById('photo-clear-button');
        const photoPreview = document.getElementById('photo-preview');
        const photoPlaceholder = document.getElementById('photo-placeholder');
        const photoStatus = document.getElementById('photo-status');
        const registrationTypeInputs = document.querySelectorAll('.js-registration-type');
        const registrationTypeCards = document.querySelectorAll('[data-type-card]');
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

        function setPhotoError(message) {
            photoStatus.textContent = message;
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

        function updatePhotoPreview(file) {
            photoStatus.classList.remove('text-brand-red');

            if (!file) {
                photoPreview.src = '';
                photoPreview.classList.add('hidden');
                photoPlaceholder.classList.remove('hidden');
                photoEditButton.disabled = true;
                photoStatus.textContent = 'Nenhuma imagem selecionada.';
                return;
            }

            const previewUrl = URL.createObjectURL(file);
            photoPreview.src = previewUrl;
            photoPreview.classList.remove('hidden');
            photoPlaceholder.classList.add('hidden');
            photoEditButton.disabled = false;
            photoStatus.textContent = 'Foto pronta para envio.';
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
                setPhotoError('O editor de foto nao carregou corretamente. Recarregue a pagina e tente novamente.');
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
                setPhotoError('Nao foi possivel abrir a foto selecionada.');
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

        photoSelectButton.addEventListener('click', () => {
            openPhotoChooser();
        });

        photoSourceInput.addEventListener('change', () => {
            const file = photoSourceInput.files && photoSourceInput.files[0];
            if (!file) {
                return;
            }

            openCropModalFromFile(file);
        });

        photoEditButton.addEventListener('click', () => {
            const file = photoSourceInput.files && photoSourceInput.files[0];
            if (!file) {
                return;
            }

            openCropModalFromFile(file);
        });

        photoClearButton.addEventListener('click', () => {
            photoSourceInput.value = '';
            photoUploadInput.value = '';
            updatePhotoPreview(null);
        });

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

            cropper.getCroppedCanvas({
                width: 720,
                height: 720,
                fillColor: '#ffffff'
            }).toBlob((blob) => {
                if (!blob) {
                    return;
                }

                const croppedFile = new File([blob], 'foto-colaborador.png', { type: 'image/png' });
                const fileTransfer = new DataTransfer();
                fileTransfer.items.add(croppedFile);
                photoUploadInput.files = fileTransfer.files;

                updatePhotoPreview(croppedFile);
                closeCropModal();
            }, 'image/png', 0.95);
        });

        [cropCancelButton, cropCloseButton].forEach((button) => {
            button.addEventListener('click', closeCropModal);
        });

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
            if (!photoUploadInput.files || !photoUploadInput.files.length) {
                event.preventDefault();
                setPhotoError('Selecione a foto, ajuste o enquadramento e clique em ACEITAR antes de salvar.');
                photoSurfaceButton.focus();
            }
        });

        syncRegistrationTypeUI();
    })();
</script>

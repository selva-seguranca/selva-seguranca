<?php
    $viewCollaborator = is_array($viewCollaborator ?? null) ? $viewCollaborator : null;

    if ($viewCollaborator === null) {
        return;
    }

    $detailValue = function ($value, $fallback = 'Não informado') {
        $value = trim((string) $value);
        return $value !== '' ? htmlspecialchars($value, ENT_QUOTES, 'UTF-8') : $fallback;
    };

    $statusClassMap = [
        'Ativo' => 'bg-green-100 text-green-800',
        'Inativo' => 'bg-gray-200 text-gray-800',
        'Afastado' => 'bg-yellow-100 text-yellow-800',
    ];

    $registrationTypeLabel = ($viewCollaborator['tipo_cadastro'] ?? '') === 'vigilante'
        ? 'Colaborador Vigilante'
        : 'Financeiro / Administrativo';
    $courses = is_array($viewCollaborator['outros_cursos'] ?? null) ? $viewCollaborator['outros_cursos'] : [];
    $photoUrl = trim((string) ($viewCollaborator['foto_url'] ?? ''));
    $statusLabel = trim((string) ($viewCollaborator['situacao'] ?? ($viewCollaborator['ativo'] ? 'Ativo' : 'Inativo')));
?>

<div class="space-y-6">
    <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-center">
            <div class="shrink-0">
                <?php if ($photoUrl !== ''): ?>
                    <img
                        src="<?= htmlspecialchars($photoUrl, ENT_QUOTES, 'UTF-8') ?>"
                        alt="Foto de <?= htmlspecialchars($viewCollaborator['nome'], ENT_QUOTES, 'UTF-8') ?>"
                        class="h-28 w-28 rounded-3xl object-cover shadow-sm"
                    >
                <?php else: ?>
                    <div class="flex h-28 w-28 items-center justify-center rounded-3xl bg-red-50 text-3xl font-bold text-brand-red shadow-sm">
                        <?= htmlspecialchars(substr((string) ($viewCollaborator['nome'] ?? 'C'), 0, 1), ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="min-w-0 flex-1">
                <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-brand-red">Cadastro do Colaborador</p>
                        <h3 class="mt-2 text-2xl font-bold text-gray-900"><?= htmlspecialchars((string) ($viewCollaborator['nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h3>
                        <p class="mt-2 text-sm text-gray-500">
                            <?= htmlspecialchars((string) ($viewCollaborator['cargo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                            <?php if (!empty($viewCollaborator['departamento'])): ?>
                                <span class="mx-2 text-gray-300">|</span>
                                <?= htmlspecialchars((string) ($viewCollaborator['departamento'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                            <?php endif; ?>
                        </p>
                    </div>

                    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold <?= $statusClassMap[$statusLabel] ?? 'bg-gray-200 text-gray-800' ?>">
                        <?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?>
                    </span>
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="rounded-2xl bg-gray-50 px-4 py-3">
                        <p class="text-[11px] uppercase tracking-wide text-gray-400">Tipo de cadastro</p>
                        <p class="mt-1 font-semibold text-gray-800"><?= $registrationTypeLabel ?></p>
                    </div>
                    <div class="rounded-2xl bg-gray-50 px-4 py-3">
                        <p class="text-[11px] uppercase tracking-wide text-gray-400">Perfil</p>
                        <p class="mt-1 font-semibold text-gray-800"><?= $detailValue($viewCollaborator['perfil'] ?? null) ?></p>
                    </div>
                    <div class="rounded-2xl bg-gray-50 px-4 py-3">
                        <p class="text-[11px] uppercase tracking-wide text-gray-400">E-mail de acesso</p>
                        <p class="mt-1 break-all font-semibold text-gray-800"><?= $detailValue($viewCollaborator['email'] ?? null) ?></p>
                    </div>
                    <div class="rounded-2xl bg-gray-50 px-4 py-3">
                        <p class="text-[11px] uppercase tracking-wide text-gray-400">Admissão</p>
                        <p class="mt-1 font-semibold text-gray-800"><?= $detailValue($viewCollaborator['data_admissao'] ?? null) ?></p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="grid gap-6 xl:grid-cols-2">
        <article class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">
            <h4 class="text-lg font-bold text-gray-900">Dados pessoais</h4>
            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-400">CPF</p>
                    <p class="mt-1 font-medium text-gray-800"><?= $detailValue($viewCollaborator['cpf'] ?? null) ?></p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-400">RG</p>
                    <p class="mt-1 font-medium text-gray-800"><?= $detailValue($viewCollaborator['rg'] ?? null) ?></p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-400">Data de nascimento</p>
                    <p class="mt-1 font-medium text-gray-800"><?= $detailValue($viewCollaborator['data_nascimento'] ?? null) ?></p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-400">Nome da mãe</p>
                    <p class="mt-1 font-medium text-gray-800"><?= $detailValue($viewCollaborator['nome_mae'] ?? null) ?></p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-400">Telefone principal</p>
                    <p class="mt-1 font-medium text-gray-800"><?= $detailValue($viewCollaborator['telefone_principal'] ?? null) ?></p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-400">Telefone familiar</p>
                    <p class="mt-1 font-medium text-gray-800"><?= $detailValue($viewCollaborator['telefone_familiar'] ?? null) ?></p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-400">Tipo sanguíneo</p>
                    <p class="mt-1 font-medium text-gray-800"><?= $detailValue($viewCollaborator['tipo_sanguineo'] ?? null) ?></p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-400">Fator RH</p>
                    <p class="mt-1 font-medium text-gray-800"><?= $detailValue($viewCollaborator['fator_rh'] ?? null) ?></p>
                </div>
            </div>
        </article>

        <article class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">
            <h4 class="text-lg font-bold text-gray-900">Endereço</h4>
            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-400">CEP</p>
                    <p class="mt-1 font-medium text-gray-800"><?= $detailValue($viewCollaborator['cep'] ?? null) ?></p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-400">UF</p>
                    <p class="mt-1 font-medium text-gray-800"><?= $detailValue($viewCollaborator['uf'] ?? null) ?></p>
                </div>
                <div class="sm:col-span-2">
                    <p class="text-xs uppercase tracking-wide text-gray-400">Logradouro</p>
                    <p class="mt-1 font-medium text-gray-800"><?= $detailValue($viewCollaborator['logradouro'] ?? null) ?></p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-400">Número</p>
                    <p class="mt-1 font-medium text-gray-800"><?= $detailValue($viewCollaborator['numero'] ?? null) ?></p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-400">Bairro</p>
                    <p class="mt-1 font-medium text-gray-800"><?= $detailValue($viewCollaborator['bairro'] ?? null) ?></p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-400">Complemento</p>
                    <p class="mt-1 font-medium text-gray-800"><?= $detailValue($viewCollaborator['complemento'] ?? null) ?></p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-400">Cidade</p>
                    <p class="mt-1 font-medium text-gray-800"><?= $detailValue($viewCollaborator['cidade'] ?? null) ?></p>
                </div>
                <div class="sm:col-span-2">
                    <p class="text-xs uppercase tracking-wide text-gray-400">Endereço completo</p>
                    <p class="mt-1 font-medium text-gray-800"><?= $detailValue($viewCollaborator['endereco_completo'] ?? null) ?></p>
                </div>
            </div>
        </article>
    </section>

    <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">
        <h4 class="text-lg font-bold text-gray-900">Dados profissionais</h4>
        <div class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div>
                <p class="text-xs uppercase tracking-wide text-gray-400">Cargo</p>
                <p class="mt-1 font-medium text-gray-800"><?= $detailValue($viewCollaborator['cargo'] ?? null) ?></p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-gray-400">Departamento</p>
                <p class="mt-1 font-medium text-gray-800"><?= $detailValue($viewCollaborator['departamento'] ?? null) ?></p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-gray-400">Tipo de vínculo</p>
                <p class="mt-1 font-medium text-gray-800"><?= $detailValue($viewCollaborator['tipo_vinculo'] ?? null) ?></p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-gray-400">Número da admissão</p>
                <p class="mt-1 font-medium text-gray-800"><?= $detailValue($viewCollaborator['numero_admissao'] ?? null) ?></p>
            </div>
        </div>
    </section>

    <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">
        <h4 class="text-lg font-bold text-gray-900">Dados bancários</h4>
        <div class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <div>
                <p class="text-xs uppercase tracking-wide text-gray-400">Banco</p>
                <p class="mt-1 font-medium text-gray-800"><?= $detailValue($viewCollaborator['banco_nome'] ?? null) ?></p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-gray-400">Agência</p>
                <p class="mt-1 font-medium text-gray-800"><?= $detailValue($viewCollaborator['agencia_bancaria'] ?? null) ?></p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-gray-400">Conta</p>
                <p class="mt-1 font-medium text-gray-800"><?= $detailValue($viewCollaborator['conta_bancaria'] ?? null) ?></p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-gray-400">Tipo de conta</p>
                <p class="mt-1 font-medium text-gray-800"><?= $detailValue($viewCollaborator['tipo_conta'] ?? null) ?></p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-gray-400">Chave PIX</p>
                <p class="mt-1 break-all font-medium text-gray-800"><?= $detailValue($viewCollaborator['chave_pix'] ?? null) ?></p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-gray-400">Titular da conta</p>
                <p class="mt-1 font-medium text-gray-800"><?= $detailValue($viewCollaborator['titular_conta'] ?? null) ?></p>
            </div>
        </div>
    </section>

    <?php if (($viewCollaborator['tipo_cadastro'] ?? '') === 'vigilante'): ?>
        <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">
            <h4 class="text-lg font-bold text-gray-900">Dados de vigilante</h4>
            <div class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-400">Número da CNV</p>
                    <p class="mt-1 font-medium text-gray-800"><?= $detailValue($viewCollaborator['numero_cnv'] ?? null) ?></p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-400">Validade da CNV</p>
                    <p class="mt-1 font-medium text-gray-800"><?= $detailValue($viewCollaborator['validade_cnv'] ?? null) ?></p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-400">Curso de formação</p>
                    <p class="mt-1 font-medium text-gray-800"><?= !empty($viewCollaborator['curso_formacao_concluido']) ? 'Sim' : 'Não' ?></p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-400">Última reciclagem</p>
                    <p class="mt-1 font-medium text-gray-800"><?= $detailValue($viewCollaborator['data_ultima_reciclagem'] ?? null) ?></p>
                </div>
                <div class="sm:col-span-2 xl:col-span-4">
                    <p class="text-xs uppercase tracking-wide text-gray-400">Situação da reciclagem</p>
                    <p class="mt-1 font-medium text-gray-800"><?= $detailValue($viewCollaborator['situacao_reciclagem'] ?? null) ?></p>
                </div>
            </div>

            <div class="mt-5">
                <p class="text-xs uppercase tracking-wide text-gray-400">Outros cursos</p>
                <?php if (empty($courses)): ?>
                    <p class="mt-2 font-medium text-gray-800">Nenhum curso adicional informado.</p>
                <?php else: ?>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <?php foreach ($courses as $course): ?>
                            <span class="inline-flex items-center rounded-full border border-red-100 bg-red-50 px-3 py-1 text-xs font-semibold text-brand-red">
                                <?= htmlspecialchars((string) $course, ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>
</div>

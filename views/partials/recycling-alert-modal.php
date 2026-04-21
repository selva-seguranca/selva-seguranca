<?php
    $recyclingAlerts = is_array($recyclingAlerts ?? null) ? $recyclingAlerts : [];

    if (empty($recyclingAlerts)) {
        return;
    }

    $formatAlertDate = function ($date) {
        $date = trim((string) $date);
        if ($date === '') {
            return 'Não informada';
        }

        $timestamp = strtotime($date);
        return $timestamp !== false ? date('d/m/Y', $timestamp) : 'Não informada';
    };

    $alertText = function (array $alert) {
        $status = (string) ($alert['status_reciclagem'] ?? '');
        $days = $alert['dias_para_vencimento'] ?? null;
        $level = $alert['alerta_reciclagem_nivel'] ?? null;

        if ($status === 'vencida') {
            return 'Reciclagem vencida há ' . abs((int) $days) . ' dia(s).';
        }

        if ($days !== null && (int) $days === 0) {
            return 'A reciclagem vence hoje. Alerta crítico de 30 dias.';
        }

        return 'Faltam ' . (int) $days . ' dia(s). Alerta de ' . htmlspecialchars((string) $level, ENT_QUOTES, 'UTF-8') . ' dias.';
    };

    $identityParts = [];
    foreach ($recyclingAlerts as $alert) {
        $identityParts[] = implode(':', [
            (string) ($alert['user_id'] ?? ''),
            (string) ($alert['validade_reciclagem'] ?? ''),
            (string) ($alert['alerta_reciclagem_nivel'] ?? ''),
            (string) ($alert['status_reciclagem'] ?? ''),
        ]);
    }

    $storageKeyBase = 'selva-recycling-alert:' . (string) ($_SESSION['user_id'] ?? 'guest') . ':' . md5(implode('|', $identityParts));
?>

<div
    id="recycling-alert-modal"
    data-recycling-alert-storage-base="<?= htmlspecialchars($storageKeyBase, ENT_QUOTES, 'UTF-8') ?>"
    class="fixed inset-0 z-[120] hidden items-center justify-center bg-black/70 p-4"
    role="alertdialog"
    aria-modal="true"
    aria-labelledby="recycling-alert-title"
>
    <div class="w-full max-w-2xl overflow-hidden rounded-[28px] border border-red-100 bg-white text-gray-900 shadow-2xl">
        <div class="border-b border-red-100 bg-gradient-to-br from-red-50 via-white to-amber-50 px-6 py-5">
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-start gap-4">
                    <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-brand-red text-white shadow-lg shadow-red-500/20">
                        <i class="ph ph-bell-ringing text-2xl"></i>
                    </span>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-brand-red">Alerta de reciclagem</p>
                        <h2 id="recycling-alert-title" class="mt-2 text-xl font-black text-gray-950">ATENÇÃO PARA O VENCIMENTO DA RECICLAGEM</h2>
                        <p class="mt-2 text-sm leading-6 text-gray-600">Este aviso aparecerá diariamente a partir de 00:00 até que a data de reciclagem seja atualizada no cadastro.</p>
                    </div>
                </div>

                <button
                    type="button"
                    data-close-recycling-alert
                    class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-500 transition-colors hover:border-gray-300 hover:text-gray-900"
                    aria-label="Fechar alerta"
                >
                    <i class="ph ph-x text-xl"></i>
                </button>
            </div>
        </div>

        <div class="max-h-[55vh] space-y-3 overflow-y-auto px-6 py-5">
            <?php foreach ($recyclingAlerts as $alert): ?>
                <?php
                    $status = (string) ($alert['status_reciclagem'] ?? '');
                    $isExpired = $status === 'vencida';
                    $level = $alert['alerta_reciclagem_nivel'] ?? null;
                    $tone = $isExpired || (string) $level === '30'
                        ? 'border-red-200 bg-red-50 text-red-900'
                        : ((string) $level === '60'
                            ? 'border-amber-200 bg-amber-50 text-amber-900'
                            : 'border-blue-200 bg-blue-50 text-blue-900');
                ?>
                <article class="rounded-2xl border px-4 py-3 <?= $tone ?>">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <h3 class="font-bold text-gray-950"><?= htmlspecialchars((string) ($alert['nome'] ?? 'Vigilante'), ENT_QUOTES, 'UTF-8') ?></h3>
                            <p class="mt-1 text-sm"><?= $alertText($alert) ?></p>
                        </div>
                        <span class="inline-flex shrink-0 items-center rounded-full bg-white/80 px-3 py-1 text-xs font-bold text-gray-900">
                            <?= htmlspecialchars((string) ($alert['status_label'] ?? 'Alerta'), ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </div>
                    <div class="mt-3 grid gap-2 text-xs font-semibold uppercase tracking-wide text-gray-500 sm:grid-cols-2">
                        <p>Reciclagem: <span class="text-gray-900"><?= htmlspecialchars($formatAlertDate($alert['data_reciclagem'] ?? null), ENT_QUOTES, 'UTF-8') ?></span></p>
                        <p>Vencimento: <span class="text-gray-900"><?= htmlspecialchars($formatAlertDate($alert['validade_reciclagem'] ?? null), ENT_QUOTES, 'UTF-8') ?></span></p>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="border-t border-gray-100 bg-gray-50 px-6 py-4">
            <button
                type="button"
                data-close-recycling-alert
                class="inline-flex w-full items-center justify-center rounded-2xl bg-brand-red px-4 py-3 text-sm font-bold text-white transition-colors hover:bg-red-700"
            >
                ESTOU CIENTE
            </button>
        </div>
    </div>
</div>

<script>
    (() => {
        const modal = document.getElementById('recycling-alert-modal');
        if (!modal) {
            return;
        }

        const storageKeyBase = modal.dataset.recyclingAlertStorageBase || '';
        const body = document.body;

        function getLocalDateKey() {
            const now = new Date();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');

            return `${now.getFullYear()}-${month}-${day}`;
        }

        function getStorageKey() {
            return `${storageKeyBase}:${getLocalDateKey()}`;
        }

        function openModal(force = false) {
            const storageKey = getStorageKey();
            if (!force && storageKeyBase && window.localStorage && localStorage.getItem(storageKey) === 'closed') {
                return;
            }

            modal.classList.remove('hidden');
            modal.classList.add('flex');
            body.classList.add('overflow-hidden');
        }

        function closeModal() {
            if (storageKeyBase && window.localStorage) {
                localStorage.setItem(getStorageKey(), 'closed');
            }

            modal.classList.add('hidden');
            modal.classList.remove('flex');
            body.classList.remove('overflow-hidden');
        }

        function scheduleMidnightAlert() {
            const now = new Date();
            const nextMidnight = new Date(now);
            nextMidnight.setHours(24, 0, 0, 0);
            const delay = Math.max(nextMidnight.getTime() - now.getTime(), 1000);

            window.setTimeout(() => {
                openModal(true);
                scheduleMidnightAlert();
            }, delay);
        }

        modal.querySelectorAll('[data-close-recycling-alert]').forEach((button) => {
            button.addEventListener('click', closeModal);
        });

        openModal();
        scheduleMidnightAlert();
    })();
</script>

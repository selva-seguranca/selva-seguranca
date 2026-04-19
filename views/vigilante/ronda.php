<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Iniciar Ronda - Selva Segurança</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            red: '#E50914',
                            dark: '#111111',
                            gray: '#222222',
                            light: '#f4f4f4'
                        }
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #111; color: white; }
    </style>
</head>
<body class="pb-20">

    <header class="bg-brand-gray p-4 flex items-center justify-between shadow-md">
        <div class="flex items-center space-x-3">
            <img src="/assets/img/logo.png" alt="Logo" class="h-8">
            <h1 class="font-semibold text-lg text-white tracking-wide">Área Operacional</h1>
        </div>
        <a href="/" class="text-gray-400 hover:text-white transition-colors">
            <i class="ph ph-x text-2xl"></i>
        </a>
    </header>

    <div class="p-5">
        <h2 class="text-xl font-bold mb-1">Checklist Inicial</h2>
        <p class="text-sm text-gray-400 mb-6">Preencha os dados do veículo antes de iniciar a ronda.</p>

        <?php if (!empty($dbWarning)): ?>
            <div class="mb-4 rounded-xl border border-amber-500/40 bg-amber-500/10 px-4 py-3 text-sm text-amber-200">
                <?= htmlspecialchars($dbWarning) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($formError)): ?>
            <div class="mb-4 rounded-xl border border-red-500/40 bg-red-500/10 px-4 py-3 text-sm text-red-200">
                <?= htmlspecialchars($formError) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($successMessage)): ?>
            <div class="mb-4 rounded-xl border border-emerald-500/40 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200">
                <?= htmlspecialchars($successMessage) ?>
            </div>
        <?php endif; ?>

        <form action="/vigilante/checklist" method="POST" class="space-y-6" enctype="multipart/form-data">
            <div class="bg-brand-gray p-4 rounded-xl border border-gray-800">
                <label class="block text-sm font-medium text-gray-300 mb-2">Veículo Selecionado</label>
                <select name="veiculo_id" required class="w-full bg-black border border-gray-700 text-white rounded-lg p-3 outline-none focus:border-brand-red">
                    <option value="">Selecione a viatura</option>
                    <?php foreach($veiculos as $v): ?>
                        <option value="<?= $v['id'] ?>" data-km="<?= $v['km_atual'] ?>" data-proxtroca="<?= $v['km_prox_troca_oleo'] ?>">
                            <?= htmlspecialchars($v['modelo'] . ' - ' . $v['placa']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (empty($veiculos)): ?>
                    <p class="mt-3 text-xs text-amber-300">Nenhum veículo disponível no banco para iniciar uma ronda.</p>
                <?php endif; ?>
            </div>

            <div class="bg-brand-gray p-4 rounded-xl border border-gray-800 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Quilometragem (KM)</label>
                    <input type="number" name="km_inicial" id="km_inicial" required min="0" placeholder="Ex: 50120"
                           class="w-full bg-black border border-gray-700 text-white rounded-lg p-3 outline-none focus:border-brand-red text-xl font-bold font-mono">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Foto do Painel</label>
                    <label for="foto_painel" class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-700 border-dashed rounded-lg cursor-pointer bg-black/50 hover:bg-black transition-colors">
                        <div class="flex flex-col items-center justify-center pt-5 pb-6">
                            <i class="ph ph-camera text-3xl mb-2 text-gray-400"></i>
                            <p class="text-sm text-gray-400">Clique para capturar</p>
                        </div>
                        <input id="foto_painel" type="file" name="foto_painel" accept="image/*" capture="environment" class="hidden" required />
                    </label>
                </div>
            </div>

            <div class="bg-brand-gray p-4 rounded-xl border border-gray-800 space-y-4">
                <h3 class="font-medium text-brand-red border-b border-gray-700 pb-2 mb-3 text-sm">Estado Geral</h3>

                <div>
                    <label class="block text-sm text-gray-300 mb-2">Combustível</label>
                    <select name="combustivel" class="w-full bg-black border border-gray-700 text-white rounded-lg p-3 outline-none focus:border-brand-red">
                        <option value="cheio">Cheio</option>
                        <option value="3/4">3/4</option>
                        <option value="1/2">Meio Tanque (1/2)</option>
                        <option value="1/4">1/4</option>
                        <option value="reserva">Reserva (Atenção!)</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-gray-300 mb-2">Pneus</label>
                        <select name="pneus" class="w-full bg-black border border-gray-700 text-white rounded-lg p-3 outline-none focus:border-brand-red">
                            <option value="bom">Bom</option>
                            <option value="regular">Regular</option>
                            <option value="ruim">Ruim</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-300 mb-2">Iluminação</label>
                        <select name="iluminacao" class="w-full bg-black border border-gray-700 text-white rounded-lg p-3 outline-none focus:border-brand-red">
                            <option value="bom">Funcionando</option>
                            <option value="com_defeito">Com defeito</option>
                        </select>
                    </div>
                </div>
            </div>

            <button type="submit" class="w-full bg-brand-red hover:bg-red-700 text-white font-bold py-4 rounded-xl shadow-lg border border-red-500/50 flex justify-center items-center space-x-2 fixed bottom-0 left-0 rounded-none z-50">
                <i class="ph ph-play-circle text-2xl"></i>
                <span>INICIAR RONDA AGORA</span>
            </button>
        </form>
    </div>

    <div id="maintenanceAlert" class="fixed inset-0 bg-black/90 z-[60] hidden flex-col items-center justify-center p-6 px-4">
        <div class="bg-brand-gray border border-red-500 w-full max-w-sm rounded-2xl p-6 text-center shadow-[0_0_40px_rgba(229,9,20,0.4)]">
            <div class="w-20 h-20 bg-red-500/20 rounded-full flex items-center justify-center mx-auto mb-4 animate-pulse">
                <i class="ph ph-warning text-4xl text-brand-red"></i>
            </div>
            <h2 class="text-2xl font-bold text-white mb-2">Atenção Necessária</h2>
            <p id="alertMessage" class="text-gray-300 text-sm mb-6 pb-6 border-b border-gray-700">O veículo atingiu o limite de quilometragem para troca de óleo preventiva.</p>

            <button type="button" onclick="document.getElementById('maintenanceAlert').style.display='none'" class="w-full bg-brand-red text-white py-3 rounded-lg font-bold">
                ESTOU CIENTE (CONTINUAR)
            </button>
        </div>
    </div>

    <script>
        const kmInput = document.getElementById('km_inicial');
        const vehicleSelect = document.querySelector('select[name="veiculo_id"]');
        const alertModal = document.getElementById('maintenanceAlert');
        const alertMessage = document.getElementById('alertMessage');

        kmInput.addEventListener('blur', function() {
            const selectedOption = vehicleSelect.options[vehicleSelect.selectedIndex];
            if (!selectedOption.value) return;

            const kmDigitado = parseInt(this.value, 10);
            const proxtroca = parseInt(selectedOption.getAttribute('data-proxtroca'), 10);

            if (!Number.isFinite(kmDigitado) || !Number.isFinite(proxtroca)) return;

            if (kmDigitado >= (proxtroca - 1000)) {
                const text = (kmDigitado >= proxtroca)
                    ? "Atenção: a troca de óleo deste veículo está vencida."
                    : `Atenção: a troca de óleo vencerá nos próximos ${proxtroca - kmDigitado} KM.`;

                alertMessage.textContent = text;
                alertModal.style.display = 'flex';
            }
        });
    </script>
</body>
</html>

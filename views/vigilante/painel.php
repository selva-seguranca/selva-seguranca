<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Ronda Ativa - Selva Seguranca</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: { red: '#E50914', dark: '#111111', gray: '#222222', light: '#f4f4f4' }
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #111; color: white; }
        .recording-pulse { animation: pulse-red 1.5s infinite; }
        @keyframes pulse-red {
            0% { box-shadow: 0 0 0 0 rgba(229, 9, 20, 0.7); }
            70% { box-shadow: 0 0 0 15px rgba(229, 9, 20, 0); }
            100% { box-shadow: 0 0 0 0 rgba(229, 9, 20, 0); }
        }
    </style>
</head>
<body class="pb-24">

    <header class="bg-gray-900 border-b border-gray-800 p-4 sticky top-0 z-50 flex items-center justify-between">
        <div class="flex items-center space-x-3">
            <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
            <h1 class="font-bold text-white">RONDA EM ANDAMENTO</h1>
        </div>
        <div>
            <span class="text-xs text-gray-400 font-mono" id="time-elapsed">00:00:00</span>
        </div>
    </header>

    <div class="p-5 space-y-6">
        <?php if (!empty($ronda)): ?>
            <div class="bg-brand-gray border border-gray-700 rounded-xl p-4">
                <p class="text-xs uppercase tracking-wider text-gray-400 mb-2">Ronda Atual</p>
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-lg font-semibold text-white"><?= htmlspecialchars(($ronda['modelo'] ?? 'Sem veiculo') . (!empty($ronda['placa']) ? ' - ' . $ronda['placa'] : '')) ?></p>
                        <p class="text-sm text-gray-400">Inicio: <?= date('d/m/Y H:i', strtotime($ronda['data_inicio'])) ?></p>
                    </div>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-500/20 text-green-300">
                        <?= htmlspecialchars($ronda['status']) ?>
                    </span>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($ronda['foto_painel_url']) || !empty($ronda['km_inicial']) || !empty($ronda['combustivel_nivel'])): ?>
            <div class="bg-brand-gray border border-gray-700 rounded-xl p-4">
                <p class="text-xs uppercase tracking-wider text-gray-400 mb-3">Checklist Inicial</p>
                <div class="flex gap-4 items-start">
                    <?php if (!empty($ronda['foto_painel_url'])): ?>
                        <img src="<?= htmlspecialchars($ronda['foto_painel_url']) ?>" alt="Foto do painel" class="w-24 h-24 rounded-xl object-cover border border-gray-700 shrink-0">
                    <?php endif; ?>
                    <div class="space-y-2 text-sm">
                        <?php if (!empty($ronda['km_inicial'])): ?>
                            <p class="text-gray-300">KM inicial: <span class="text-white font-semibold"><?= number_format((int) $ronda['km_inicial'], 0, ',', '.') ?> km</span></p>
                        <?php endif; ?>
                        <?php if (!empty($ronda['combustivel_nivel'])): ?>
                            <p class="text-gray-300">Combustivel: <span class="text-white font-semibold"><?= htmlspecialchars(ucfirst($ronda['combustivel_nivel'])) ?></span></p>
                        <?php endif; ?>
                        <?php if (!empty($ronda['foto_painel_url'])): ?>
                            <a href="<?= htmlspecialchars($ronda['foto_painel_url']) ?>" target="_blank" rel="noopener noreferrer" class="inline-flex text-sm text-brand-red font-medium">Abrir foto do painel</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="w-full h-48 bg-gray-800 rounded-xl border border-gray-700 flex flex-col items-center justify-center relative overflow-hidden">
            <div class="absolute inset-0 opacity-20" style="background-image: url('data:image/svg+xml,%3Csvg width=\'20\' height=\'20\' viewBox=\'0 0 20 20\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'%239C92AC\' fill-opacity=\'0.4\' fill-rule=\'evenodd\'%3E%3Ccircle cx=\'3\' cy=\'3\' r=\'3\'/%3E%3Ccircle cx=\'13\' cy=\'13\' r=\'3\'/%3E%3C/g%3E%3C/svg%3E');"></div>
            <i class="ph ph-map-pin-line text-4xl text-brand-red mb-2 z-10"></i>
            <p class="text-sm font-medium z-10" id="location-text">Obtendo localizacao GPS...</p>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <button class="bg-brand-gray border border-gray-700 rounded-xl p-5 flex flex-col items-center justify-center space-y-2 hover:bg-gray-800 transition-colors">
                <div class="w-12 h-12 bg-blue-500/20 rounded-full flex items-center justify-center text-blue-500">
                    <i class="ph ph-check-square-offset text-2xl"></i>
                </div>
                <span class="text-sm font-medium">Check-in Ponto</span>
            </button>

            <button onclick="openOcorrencia()" class="bg-brand-gray border border-gray-700 rounded-xl p-5 flex flex-col items-center justify-center space-y-2 hover:bg-gray-800 transition-colors">
                <div class="w-12 h-12 bg-yellow-500/20 rounded-full flex items-center justify-center text-yellow-500">
                    <i class="ph ph-warning-circle text-2xl"></i>
                </div>
                <span class="text-sm font-medium">Ocorrencia</span>
            </button>

            <button class="bg-brand-gray border border-gray-700 rounded-xl p-5 flex flex-col items-center justify-center space-y-2 hover:bg-gray-800 transition-colors col-span-2 relative overflow-hidden">
                <div class="absolute inset-0 w-full h-full bg-gradient-to-r from-red-500/5 to-transparent"></div>
                <div class="w-12 h-12 bg-red-500/20 rounded-full flex items-center justify-center text-brand-red mb-1">
                    <i class="ph ph-camera text-2xl"></i>
                </div>
                <span class="text-sm font-medium">Capturar Evidencia (Foto/Video)</span>
                <input type="file" capture="environment" accept="image/*,video/*" class="absolute inset-0 opacity-0 cursor-pointer">
            </button>
        </div>
    </div>

    <div class="fixed bottom-0 w-full bg-black border-t border-gray-800 p-4">
        <form action="/vigilante/ronda/finalizar" method="POST" onsubmit="return confirm('Deseja finalizar a ronda agora?');">
            <input type="hidden" name="ronda_id" value="<?= htmlspecialchars($ronda['id'] ?? '') ?>">
            <button type="submit" class="w-full bg-transparent border-2 border-brand-red text-brand-red font-bold py-3 rounded-lg hover:bg-brand-red hover:text-white transition-colors flex justify-center items-center">
                <i class="ph ph-stop-circle text-xl mr-2"></i>
                FINALIZAR RONDA
            </button>
        </form>
    </div>

    <script>
        if (navigator.geolocation) {
            navigator.geolocation.watchPosition(
                (pos) => {
                    document.getElementById('location-text').innerText = `Lat: ${pos.coords.latitude.toFixed(4)}, Lng: ${pos.coords.longitude.toFixed(4)}`;
                },
                () => {
                    document.getElementById('location-text').innerText = "Erro ao obter GPS.";
                },
                { enableHighAccuracy: true }
            );
        }

        let seconds = 0;
        setInterval(() => {
            seconds++;
            const h = Math.floor(seconds / 3600);
            const m = Math.floor((seconds % 3600) / 60);
            const s = seconds % 60;
            document.getElementById('time-elapsed').innerText =
                `${h.toString().padStart(2,'0')}:${m.toString().padStart(2,'0')}:${s.toString().padStart(2,'0')}`;
        }, 1000);

        function openOcorrencia() {
            alert("Nesta fase, este botao abrira a modal com campos detalhados de ocorrencia e upload de midia.");
        }
    </script>
</body>
</html>

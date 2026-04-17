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

            <label class="bg-brand-gray border border-gray-700 rounded-xl p-5 flex flex-col items-center justify-center space-y-2 hover:bg-gray-800 transition-colors col-span-2 relative overflow-hidden cursor-pointer">
                <div class="absolute inset-0 w-full h-full bg-gradient-to-r from-red-500/5 to-transparent"></div>
                <div class="w-12 h-12 bg-red-500/20 rounded-full flex items-center justify-center text-brand-red mb-1">
                    <i class="ph ph-camera text-2xl"></i>
                </div>
                <span class="text-sm font-medium">Capturar Evidencia (Foto/Video)</span>
                <input type="file" id="evidencia-direta" capture="environment" accept="image/*,video/*" class="absolute inset-0 opacity-0 cursor-pointer" onchange="enviarEvidenciaDireta(this)">
            </label>
        </div>
    </div>

    <!-- Modal Ocorrencia -->
    <div id="modal-ocorrencia" class="fixed inset-0 z-[100] hidden">
        <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" onclick="closeOcorrencia()"></div>
        <div class="absolute bottom-0 w-full bg-brand-gray border-t border-gray-700 rounded-t-3xl p-6 transform transition-transform translate-y-full" id="modal-content">
            <div class="w-12 h-1.5 bg-gray-600 rounded-full mx-auto mb-6"></div>
            
            <h2 class="text-xl font-bold mb-6 flex items-center">
                <i class="ph ph-warning-circle text-yellow-500 mr-2"></i>
                Nova Ocorrencia
            </h2>

            <form id="form-ocorrencia" class="space-y-4">
                <input type="hidden" name="ronda_id" value="<?= htmlspecialchars($ronda['id'] ?? '') ?>">
                <input type="hidden" name="latitude" id="input-lat">
                <input type="hidden" name="longitude" id="input-lng">

                <div>
                    <label class="block text-xs uppercase text-gray-400 mb-1">Tipo de Ocorrencia</label>
                    <select name="tipo" class="w-full bg-gray-900 border border-gray-700 rounded-lg p-3 text-white focus:border-brand-red outline-none">
                        <option value="suspeita">Atividade Suspeita</option>
                        <option value="invasao">Invasao / Arrombamento</option>
                        <option value="veiculo_suspeito">Veiculo Suspeito</option>
                        <option value="pane">Pane Mecanica / Eletrica</option>
                        <option value="apoio">Solicitacao de Apoio</option>
                        <option value="outros" selected>Outros</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs uppercase text-gray-400 mb-1">Descricao</label>
                    <textarea name="descricao" rows="3" placeholder="Descreva os detalhes..." class="w-full bg-gray-900 border border-gray-700 rounded-lg p-3 text-white focus:border-brand-red outline-none resize-none"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <label class="flex flex-col items-center justify-center border-2 border-dashed border-gray-700 rounded-xl p-4 hover:border-brand-red transition-colors cursor-pointer relative">
                        <i class="ph ph-image text-2xl mb-1 text-gray-400"></i>
                        <span class="text-[10px] uppercase font-bold text-gray-500">Adicionar Foto</span>
                        <input type="file" name="foto" accept="image/*" class="absolute inset-0 opacity-0 cursor-pointer">
                    </label>
                    <label class="flex flex-col items-center justify-center border-2 border-dashed border-gray-700 rounded-xl p-4 hover:border-brand-red transition-colors cursor-pointer relative">
                        <i class="ph ph-video-camera text-2xl mb-1 text-gray-400"></i>
                        <span class="text-[10px] uppercase font-bold text-gray-500">Adicionar Video</span>
                        <input type="file" name="video" accept="video/*" class="absolute inset-0 opacity-0 cursor-pointer">
                    </label>
                </div>

                <button type="submit" id="btn-submit-ocorrencia" class="w-full bg-brand-red text-white font-bold py-4 rounded-xl mt-4 flex items-center justify-center group">
                    <i class="ph ph-paper-plane-tilt text-xl mr-2 group-hover:translate-x-1 group-hover:-translate-y-1 transition-transform"></i>
                    ENVIAR RELATORIO
                </button>
            </form>
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
        let lastLat = null;
        let lastLng = null;

        if (navigator.geolocation) {
            navigator.geolocation.watchPosition(
                (pos) => {
                    lastLat = pos.coords.latitude;
                    lastLng = pos.coords.longitude;
                    document.getElementById('location-text').innerText = `Lat: ${lastLat.toFixed(4)}, Lng: ${lastLng.toFixed(4)}`;
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
            const modal = document.getElementById('modal-ocorrencia');
            const content = document.getElementById('modal-content');
            
            document.getElementById('input-lat').value = lastLat;
            document.getElementById('input-lng').value = lastLng;

            modal.classList.remove('hidden');
            setTimeout(() => {
                content.classList.remove('translate-y-full');
            }, 10);
        }

        function closeOcorrencia() {
            const modal = document.getElementById('modal-ocorrencia');
            const content = document.getElementById('modal-content');
            
            content.classList.add('translate-y-full');
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        }

        function enviarEvidenciaDireta(input) {
            if (!input.files || input.files.length === 0) return;
            
            if (confirm('Deseja enviar este arquivo como uma ocorrencia rapida?')) {
                const formData = new FormData();
                formData.append('evidencia', input.files[0]);
                formData.append('tipo', 'outros');
                formData.append('descricao', 'Evidencia capturada rapidamente pelo painel.');
                formData.append('latitude', lastLat);
                formData.append('longitude', lastLng);

                submitOcorrencia(formData);
            }
        }

        document.getElementById('form-ocorrencia').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            submitOcorrencia(formData);
        });

        async function submitOcorrencia(formData) {
            const btn = document.getElementById('btn-submit-ocorrencia');
            const originalText = btn ? btn.innerHTML : '';
            
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="ph ph-circle-notch animate-spin text-xl mr-2"></i> ENVIANDO...';
            }

            try {
                const response = await fetch('/vigilante/ocorrencia', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    alert('Ocorrencia registrada com sucesso!');
                    closeOcorrencia();
                    document.getElementById('form-ocorrencia').reset();
                } else {
                    alert('Erro: ' + result.error);
                }
            } catch (error) {
                alert('Erro na comunicacao com o servidor.');
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }
            }
        }
    </script>
</body>
</html>

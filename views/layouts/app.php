<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Selva Seguran&ccedil;a - CRM</title>
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
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<?php
    $currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    if (strpos($currentPath, '/public') === 0) {
        $currentPath = substr($currentPath, strlen('/public')) ?: '/';
    }

    $linkBaseClasses = 'flex items-center space-x-3 rounded-lg px-4 py-3 transition-colors';
    $linkActiveClasses = 'bg-brand-red text-white shadow-lg shadow-red-500/20';
    $linkInactiveClasses = 'text-gray-300 hover:text-white hover:bg-gray-800';

    $isDashboardActive = $currentPath === '/';
    $isRhActive = strpos($currentPath, '/rh') === 0;
    $isEscalasActive = strpos($currentPath, '/escalas') === 0;
    $isFrotaActive = strpos($currentPath, '/frota') === 0;
    $isContratosActive = strpos($currentPath, '/contratos') === 0;
    $isFinanceiroActive = strpos($currentPath, '/financeiro') === 0;
    $isVigilanteActive = strpos($currentPath, '/vigilante') === 0;
?>
<body class="bg-brand-light text-gray-800 min-h-screen flex">

    <!-- Sidebar -->
    <aside
        id="app-sidebar"
        class="fixed inset-y-0 left-0 z-30 flex w-64 -translate-x-full flex-col bg-brand-dark text-gray-300 shadow-xl transition-transform duration-200 md:static md:z-20 md:translate-x-0 md:shrink-0"
    >
        <button
            id="sidebar-close"
            type="button"
            class="absolute right-4 top-4 inline-flex h-10 w-10 items-center justify-center rounded-full text-gray-400 transition-colors hover:bg-gray-800 hover:text-white md:hidden"
            aria-label="Fechar menu"
        >
            <i class="ph ph-x text-2xl"></i>
        </button>

        <div class="h-20 flex items-center justify-center border-b border-gray-800 p-4">
            <img src="/assets/img/logo-icon.png" alt="Ícone Selva Segurança" class="h-full w-auto max-h-14 object-contain filter drop-shadow">
        </div>
        
        <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
            <a
                href="/"
                class="<?= $linkBaseClasses . ' ' . ($isDashboardActive ? $linkActiveClasses : $linkInactiveClasses) ?>"
                <?= $isDashboardActive ? 'aria-current="page"' : '' ?>
            >
                <i class="ph ph-squares-four text-xl"></i>
                <span class="font-medium">Dashboard</span>
            </a>
            
            <?php if ($_SESSION['user_perfil'] === 'Coordenador Geral' || $_SESSION['user_perfil'] === 'Administrador'): ?>
            <a
                href="/rh"
                class="<?= $linkBaseClasses . ' ' . ($isRhActive ? $linkActiveClasses : $linkInactiveClasses) ?>"
                <?= $isRhActive ? 'aria-current="page"' : '' ?>
            >
                <i class="ph ph-users text-xl"></i>
                <span class="font-medium">RH</span>
            </a>
            
            <a
                href="/escalas"
                class="<?= $linkBaseClasses . ' ' . ($isEscalasActive ? $linkActiveClasses : $linkInactiveClasses) ?>"
                <?= $isEscalasActive ? 'aria-current="page"' : '' ?>
            >
                <i class="ph ph-calendar text-xl"></i>
                <span class="font-medium">Escalas</span>
            </a>

            <a
                href="/frota"
                class="<?= $linkBaseClasses . ' ' . ($isFrotaActive ? $linkActiveClasses : $linkInactiveClasses) ?>"
                <?= $isFrotaActive ? 'aria-current="page"' : '' ?>
            >
                <i class="ph ph-car text-xl"></i>
                <span class="font-medium">Frota</span>
            </a>

            <a
                href="/contratos"
                class="<?= $linkBaseClasses . ' ' . ($isContratosActive ? $linkActiveClasses : $linkInactiveClasses) ?>"
                <?= $isContratosActive ? 'aria-current="page"' : '' ?>
            >
                <i class="ph ph-handshake text-xl"></i>
                <span class="font-medium">Contratos</span>
            </a>
            <?php endif; ?>

            <?php if ($_SESSION['user_perfil'] === 'Coordenador Geral'): ?>
            <a
                href="/financeiro"
                class="<?= $linkBaseClasses . ' ' . ($isFinanceiroActive ? $linkActiveClasses : $linkInactiveClasses) ?>"
                <?= $isFinanceiroActive ? 'aria-current="page"' : '' ?>
            >
                <i class="ph ph-coins text-xl"></i>
                <span class="font-medium">Financeiro</span>
            </a>
            <?php endif; ?>
            
            <a
                href="/vigilante/ronda"
                class="<?= $linkBaseClasses . ' md:hidden ' . ($isVigilanteActive ? $linkActiveClasses : $linkInactiveClasses) ?>"
                <?= $isVigilanteActive ? 'aria-current="page"' : '' ?>
            >
                <i class="ph ph-shield-check text-xl <?= $isVigilanteActive ? 'text-white' : 'text-brand-red' ?>"></i>
                <span class="font-medium">M&oacute;dulo Vigilante</span>
            </a>
        </nav>

        <div class="p-4 border-t border-gray-800">
            <a href="/logout" class="flex items-center space-x-3 text-gray-400 hover:text-white hover:bg-gray-800 rounded-lg px-4 py-3 transition-colors">
                <i class="ph ph-sign-out text-xl text-brand-red"></i>
                <span class="font-medium">Sair</span>
            </a>
        </div>
    </aside>

    <div
        id="sidebar-backdrop"
        class="fixed inset-0 z-20 hidden bg-black/50 backdrop-blur-sm md:hidden"
        aria-hidden="true"
    ></div>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col w-full">
        <!-- Header -->
        <header class="h-20 bg-white shadow-sm flex items-center justify-between px-6 z-10">
            <div class="flex items-center">
                <button
                    id="sidebar-toggle"
                    type="button"
                    class="mr-4 text-gray-600 focus:outline-none md:hidden"
                    aria-controls="app-sidebar"
                    aria-expanded="false"
                    aria-label="Abrir menu"
                >
                    <i id="sidebar-toggle-icon" class="ph ph-list text-2xl"></i>
                </button>
                <h1 class="text-xl font-semibold text-gray-800"><?= $pageTitle ?? 'Dashboard' ?></h1>
            </div>
            
            <div class="flex items-center space-x-4">
                <button class="relative p-2 text-gray-600 hover:bg-gray-100 rounded-full transition-colors">
                    <i class="ph ph-bell text-xl"></i>
                    <span class="absolute top-1 right-1 w-2.5 h-2.5 bg-brand-red rounded-full"></span>
                </button>
                
                <div class="flex items-center space-x-3 border-l pl-4 border-gray-200">
                    <div class="w-10 h-10 bg-brand-gray rounded-full flex items-center justify-center text-white font-bold">
                        <?= substr($_SESSION['user_nome'], 0, 1) ?>
                    </div>
                    <div class="hidden md:block">
                        <p class="text-sm font-semibold"><?= htmlspecialchars($_SESSION['user_nome']) ?></p>
                        <p class="text-xs text-gray-500"><?= htmlspecialchars($_SESSION['user_perfil']) ?></p>
                    </div>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <?php if (!empty($dbWarning)): ?>
                <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    <?= htmlspecialchars($dbWarning) ?>
                </div>
            <?php endif; ?>
            <?php include __DIR__ . '/../' . $contentView . '.php'; ?>
        </main>
    </div>

    <script>
        const body = document.body;
        const sidebar = document.getElementById('app-sidebar');
        const sidebarClose = document.getElementById('sidebar-close');
        const sidebarToggle = document.getElementById('sidebar-toggle');
        const sidebarToggleIcon = document.getElementById('sidebar-toggle-icon');
        const sidebarBackdrop = document.getElementById('sidebar-backdrop');
        const mobileBreakpoint = window.matchMedia('(min-width: 768px)');

        function setSidebarOpen(isOpen) {
            if (mobileBreakpoint.matches) {
                sidebar.classList.remove('-translate-x-full');
                sidebarBackdrop.classList.add('hidden');
                body.classList.remove('overflow-hidden');
                sidebarToggle.setAttribute('aria-expanded', 'false');
                sidebarToggle.setAttribute('aria-label', 'Abrir menu');
                sidebarToggleIcon.classList.remove('ph-x');
                sidebarToggleIcon.classList.add('ph-list');
                return;
            }

            sidebar.classList.toggle('-translate-x-full', !isOpen);
            sidebarBackdrop.classList.toggle('hidden', !isOpen);
            body.classList.toggle('overflow-hidden', isOpen);
            sidebarToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            sidebarToggle.setAttribute('aria-label', isOpen ? 'Fechar menu' : 'Abrir menu');
            sidebarToggleIcon.classList.toggle('ph-list', !isOpen);
            sidebarToggleIcon.classList.toggle('ph-x', isOpen);
        }

        function toggleSidebar() {
            const isOpen = sidebarToggle.getAttribute('aria-expanded') === 'true';
            setSidebarOpen(!isOpen);
        }

        sidebarToggle.addEventListener('click', toggleSidebar);
        sidebarClose.addEventListener('click', () => setSidebarOpen(false));
        sidebarBackdrop.addEventListener('click', () => setSidebarOpen(false));

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                setSidebarOpen(false);
            }
        });

        sidebar.querySelectorAll('a').forEach((link) => {
            link.addEventListener('click', () => setSidebarOpen(false));
        });

        if (typeof mobileBreakpoint.addEventListener === 'function') {
            mobileBreakpoint.addEventListener('change', () => setSidebarOpen(false));
        } else if (typeof mobileBreakpoint.addListener === 'function') {
            mobileBreakpoint.addListener(() => setSidebarOpen(false));
        }

        setSidebarOpen(false);
    </script>
</body>
</html>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Selva Segurança</title>
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
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-brand-dark text-white min-h-screen flex items-center justify-center relative overflow-hidden">
    
    <!-- Background elements -->
    <div class="absolute top-0 left-0 w-full h-full z-0 opacity-10">
        <div class="absolute w-96 h-96 bg-brand-red rounded-full blur-3xl -top-20 -left-20"></div>
        <div class="absolute w-96 h-96 bg-brand-red rounded-full blur-3xl bottom-10 right-10"></div>
    </div>

    <div class="relative z-10 w-full max-w-md px-6">
        <div class="bg-brand-gray/80 backdrop-blur-md rounded-2xl shadow-2xl p-8 border border-white/10">
            <div class="flex justify-center mb-8">
                <img src="/assets/img/logo-login.png" alt="Selva Segurança" class="h-40 w-auto max-w-full object-contain filter drop-shadow-lg">
            </div>

            <h2 class="text-2xl font-semibold text-center mb-6 text-gray-100">Portal de Acesso</h2>

            <?php if (isset($error) && !empty($error)): ?>
                <div class="bg-red-500/20 border border-red-500 text-red-300 px-4 py-3 rounded mb-6 text-sm flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form action="/login" method="POST" class="space-y-5">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-400 mb-1">E-mail</label>
                    <input type="email" id="email" name="email" required 
                           class="w-full px-4 py-3 rounded-lg bg-black/50 border border-gray-700 focus:border-brand-red focus:ring-1 focus:ring-brand-red text-white placeholder-gray-500 transition-colors"
                           placeholder="seu@email.com">
                </div>

                <div>
                    <div class="flex justify-between items-center mb-1">
                        <label for="password" class="block text-sm font-medium text-gray-400">Senha</label>
                        <a href="#" class="text-xs text-brand-red hover:text-red-400 transition-colors">Esqueceu a senha?</a>
                    </div>
                    <input type="password" id="password" name="password" required 
                           class="w-full px-4 py-3 rounded-lg bg-black/50 border border-gray-700 focus:border-brand-red focus:ring-1 focus:ring-brand-red text-white placeholder-gray-500 transition-colors"
                           placeholder="••••••••">
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full bg-brand-red hover:bg-red-700 text-white font-bold py-3 px-4 rounded-lg shadow-lg hover:shadow-red-500/30 transition-all duration-300 transform hover:-translate-y-0.5">
                        ENTRAR NO SISTEMA
                    </button>
                </div>
            </form>

            <div class="mt-8 text-center text-xs text-gray-500">
                <p>&copy; <?= date('Y') ?> Selva Segurança. Todos os direitos reservados.</p>
                <p class="mt-1">Acesso Restrito e Monitorado.</p>
            </div>
        </div>
    </div>
</body>
</html>

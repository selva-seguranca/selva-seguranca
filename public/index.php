<?php

$isHttpsRequest =
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (string) ($_SERVER['SERVER_PORT'] ?? '') === '443'
    || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';

session_set_cookie_params([
    'lifetime' => 60 * 60 * 24 * 30,
    'path' => '/',
    'secure' => $isHttpsRequest,
    'httponly' => true,
    'samesite' => 'Lax',
]);

session_start();

spl_autoload_register(function ($class) {
    $path = str_replace('\\', '/', $class) . '.php';
    $baseDir = dirname(__DIR__);
    $candidates = [
        $baseDir . '/' . $path,
    ];

    $segments = explode('/', $path);
    if (!empty($segments[0])) {
        $segments[0] = strtolower($segments[0]);
        $candidates[] = $baseDir . '/' . implode('/', $segments);
    }

    foreach (array_unique($candidates) as $file) {
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

\Helpers\Auth::bootstrap();

$router = require_once dirname(__DIR__) . '/routes/web.php';

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$router->dispatch($requestUri, $requestMethod);

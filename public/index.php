<?php

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

$router = require_once dirname(__DIR__) . '/routes/web.php';

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$router->dispatch($requestUri, $requestMethod);

<?php

session_start();

// Autoloader simples
spl_autoload_register(function ($class) {
    // Transforma backslashes em forward slashes
    $path = str_replace('\\', '/', $class);
    // Como public/index.php está na raiz da requisição, ajustamos o caminho base
    $file = __DIR__ . '/../' . $path . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Carrega as rotas
$router = require_once __DIR__ . '/../routes/web.php';

$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

$router->dispatch($requestUri, $requestMethod);

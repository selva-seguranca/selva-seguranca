<?php

namespace Routes;

class Router {
    private $routes = [];

    public function add($method, $path, $handler) {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'handler' => $handler
        ];
    }

    public function get($path, $handler) {
        $this->add('GET', $path, $handler);
    }

    public function post($path, $handler) {
        $this->add('POST', $path, $handler);
    }

    public function dispatch($requestUri, $requestMethod) {
        $parsedUrl = parse_url($requestUri);
        $path = $parsedUrl['path'] ?? '/';

        // Remove base path if necessary. For now, assuming root.
        // Se estiver rodando numa subpasta, precisamos tratar aqui.
        $basePath = '/public';
        if (strpos($path, $basePath) === 0) {
            $path = substr($path, strlen($basePath));
        }
        if ($path === '') {
            $path = '/';
        }

        foreach ($this->routes as $route) {
            if ($route['method'] === $requestMethod && $route['path'] === $path) {
                $handler = $route['handler'];
                
                if (is_callable($handler)) {
                    return call_user_func($handler);
                }

                if (is_string($handler) && strpos($handler, '@') !== false) {
                    list($controller, $method) = explode('@', $handler);
                    $controllerClass = "Controllers\\$controller";
                    
                    if (class_exists($controllerClass)) {
                        $controllerInstance = new $controllerClass();
                        if (method_exists($controllerInstance, $method)) {
                            return $controllerInstance->$method();
                        }
                    }
                }
            }
        }

        // 404
        http_response_code(404);
        echo "404 - Página não encontrada.";
    }
}

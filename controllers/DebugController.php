<?php

namespace Controllers;

use Config\Database;
use Config\Env;
use PDO;
use Throwable;

class DebugController {
    public function db() {
        if (!Env::isTruthy('APP_DEBUG') && !Env::isTruthy('DB_DEBUG_MESSAGE')) {
            http_response_code(404);
            echo '404 - Página não encontrada.';
            return;
        }

        $payload = Database::getDebugInfo();
        $payload['request_method'] = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $payload['request_uri'] = $_SERVER['REQUEST_URI'] ?? '/debug/db';

        try {
            $db = Database::getInstance();
            $payload['connect_ok'] = true;
            $payload['server_version'] = $db->getAttribute(PDO::ATTR_SERVER_VERSION);
            $payload['select_1'] = (int) $db->query('SELECT 1')->fetchColumn();
        } catch (Throwable $e) {
            $payload['connect_ok'] = false;
            $payload['error'] = $e->getMessage();
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}

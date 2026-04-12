<?php
require_once __DIR__ . '/config/Env.php';
require_once __DIR__ . '/config/Database.php';

use Config\Database;

try {
    echo "Iniciando teste de conexao...\n";
    $db = Database::getInstance();
    echo "Conexao bem-sucedida!\n";
    
    $stmt = $db->query("SELECT current_user, current_database()");
    print_r($stmt->fetch());
    
} catch (\Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    echo "Detalhes do Config (sem senha):\n";
    print_r(\Config\Database::getDebugInfo());
}

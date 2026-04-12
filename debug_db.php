<?php
require_once __DIR__ . '/config/Env.php';
require_once __DIR__ . '/config/Database.php';

use Config\Database;

try {
    echo "Diagnosticando configuracao de banco...\n";
    $info = Database::getDebugInfo();
    print_r($info);
    
    // Teste de conexao real
    $db = Database::getInstance();
    echo "\nConexao bem-sucedida!\n";
    
} catch (Exception $e) {
    echo "\nERRO NA CONEXAO: " . $e->getMessage() . "\n";
}

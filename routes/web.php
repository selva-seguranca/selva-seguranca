<?php

use Routes\Router;

$router = new Router();

// Auth Routes
$router->get('/login', 'AuthController@showLoginForm');
$router->post('/login', 'AuthController@login');
$router->get('/logout', 'AuthController@logout');
$router->get('/debug/db', 'DebugController@db');

// Dashboard Routes
$router->get('/', 'DashboardController@index');

// RH Routes
$router->get('/rh', 'RhController@index');
$router->get('/rh/colaboradores/novo', 'RhController@create');
$router->post('/rh/colaboradores', 'RhController@store');
$router->post('/rh/colaboradores/excluir', 'RhController@destroy');

// Escalas Routes
$router->get('/escalas', 'EscalaController@calendario');

// Frota Routes
$router->get('/frota', 'FrotaController@index');

// Contratos Routes
$router->get('/contratos', 'ContratoController@index');

// Financeiro Routes
$router->get('/financeiro', 'FinanceiroController@index');

// Vigilante Routes
$router->get('/vigilante/ronda', 'VigilanteController@preRonda');
$router->post('/vigilante/checklist', 'VigilanteController@submitChecklist');
    $router->get('/vigilante/painel', 'VigilanteController@painelAtivo');
    $router->post('/vigilante/ocorrencia', 'VigilanteController@registrarOcorrencia');
    $router->post('/vigilante/ronda/finalizar', 'VigilanteController@finalizarRonda');

return $router;

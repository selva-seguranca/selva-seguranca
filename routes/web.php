<?php

use Routes\Router;

$router = new Router();

// Auth Routes
$router->get('/login', 'AuthController@showLoginForm');
$router->post('/login', 'AuthController@login');
$router->get('/logout', 'AuthController@logout');

// Dashboard Routes
$router->get('/', 'DashboardController@index');

// RH Routes
$router->get('/rh', 'RhController@index');

// Escalas Routes
$router->get('/escalas', 'EscalaController@calendario');

// Frota Routes
$router->get('/frota', 'FrotaController@index');

// Contratos Routes
$router->get('/contratos', 'ContratoController@index');

// Vigilante Routes
$router->get('/vigilante/ronda', 'VigilanteController@preRonda');
$router->post('/vigilante/checklist', 'VigilanteController@submitChecklist');
$router->get('/vigilante/painel', 'VigilanteController@painelAtivo');
$router->post('/vigilante/ronda/finalizar', 'VigilanteController@finalizarRonda');

return $router;

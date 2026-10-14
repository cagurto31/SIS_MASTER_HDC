<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

$routes->get('/', static function () {
    return redirect()->to(session()->get('usuario_id') ? '/dashboard' : '/login');
});

$routes->get('login', 'AuthController::login');
$routes->post('login', 'AuthController::attempt');
$routes->get('logout', 'AuthController::logout');
$routes->get('forgot-password', 'AuthController::forgotPassword');
$routes->post('forgot-password', 'AuthController::sendResetLink');
$routes->get('reset-password/(:segment)', 'AuthController::resetPassword/$1');
$routes->post('reset-password/(:segment)', 'AuthController::updatePassword/$1');

$routes->get('mfa', 'AuthController::mfa');
$routes->post('mfa', 'AuthController::verifyMfa');
$routes->post('mfa/email', 'AuthController::sendMfaEmailCode');
$routes->get('branding/(:segment)', 'ConfigurationController::asset/$1');
$routes->get('branding/(:segment)/(:num)', 'ConfigurationController::asset/$1/$2');

$routes->group('', ['filter' => 'auth'], static function (RouteCollection $routes) {
    $routes->get('dashboard', 'DashboardController::index');
});

$routes->group('', ['filter' => 'auth'], static function (RouteCollection $routes) {
    $routes->get('master-usuarios', 'MasterUsuariosController::index');
    $routes->get('master-usuarios/crear', 'MasterUsuariosController::crear');
    $routes->post('master-usuarios/guardar', 'MasterUsuariosController::guardar');
    $routes->get('master-usuarios/editar/(:num)', 'MasterUsuariosController::editar/$1');
    $routes->post('master-usuarios/guardar/(:num)', 'MasterUsuariosController::guardar/$1');
    $routes->get('master-usuarios/toggle/(:num)', 'MasterUsuariosController::toggleActivo/$1');
    $routes->post('master-usuarios/reset-mfa/(:num)', 'MasterUsuariosController::resetMfa/$1');

    $routes->get('sistemas', 'SistemasController::index');
    $routes->get('sistemas/crear', 'SistemasController::crear');
    $routes->post('sistemas/guardar', 'SistemasController::guardar');
    $routes->get('sistemas/editar/(:num)', 'SistemasController::editar/$1');
    $routes->post('sistemas/guardar/(:num)', 'SistemasController::guardar/$1');
    $routes->get('sistemas/toggle/(:num)', 'SistemasController::toggle/$1');
    $routes->post('sistemas/mfa/(:num)', 'SistemasController::guardarMfa/$1');

    $routes->get('configuracion', 'ConfigurationController::index');
    $routes->post('configuracion/guardar', 'ConfigurationController::save');
    $routes->post('configuracion/guardar/(:num)', 'ConfigurationController::save/$1');
    $routes->post('configuracion/seleccionar/(:num)', 'ConfigurationController::select/$1');
    $routes->post('configuracion/mfa', 'ConfigurationController::saveMfa');
    $routes->post('configuracion/roles/guardar', 'ConfigurationController::saveRole');
    $routes->post('configuracion/roles/guardar/(:num)', 'ConfigurationController::saveRole/$1');
    $routes->get('configuracion/roles/toggle/(:num)', 'ConfigurationController::toggleRole/$1');
    $routes->post('configuracion/roles/eliminar/(:num)', 'ConfigurationController::deleteRole/$1');
    $routes->post('configuracion/perfiles/guardar', 'ConfigurationController::saveProfile');
    $routes->post('configuracion/perfiles/guardar/(:num)', 'ConfigurationController::saveProfile/$1');
    $routes->get('configuracion/perfiles/toggle/(:num)', 'ConfigurationController::toggleProfile/$1');
    $routes->post('configuracion/perfiles/eliminar/(:num)', 'ConfigurationController::deleteProfile/$1');
});

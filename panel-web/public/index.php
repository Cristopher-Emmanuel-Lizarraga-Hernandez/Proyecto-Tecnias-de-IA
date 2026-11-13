<?php
/**
 * public/index.php — Front controller.
 * Todas las peticiones pasan por aquí y se enrutan según ?controller=&action=
 * Ej: index.php?controller=tutor&action=registrar
 *
 * Todo requiere sesión iniciada, excepto las acciones listadas en
 * $accionesPublicas (solo el login).
 */

require_once __DIR__ . '/../app/helpers/seguridad.php';
iniciarSesionSegura();

$controllerNombre = $_GET['controller'] ?? 'dashboard';
$accion = $_GET['action'] ?? 'index';

$mapaControladores = [
    'dashboard' => __DIR__ . '/../app/controllers/DashboardController.php',
    'tutor'     => __DIR__ . '/../app/controllers/TutorController.php',
    'bitacora'  => __DIR__ . '/../app/controllers/BitacoraController.php',
    'auth'      => __DIR__ . '/../app/controllers/AuthController.php',
];

$mapaClases = [
    'dashboard' => 'DashboardController',
    'tutor'     => 'TutorController',
    'bitacora'  => 'BitacoraController',
    'auth'      => 'AuthController',
];

// Acciones que se pueden usar SIN haber iniciado sesión.
$accionesPublicas = [
    'auth' => ['login', 'autenticar'],
];

if (!is_string($controllerNombre) || !isset($mapaControladores[$controllerNombre])) {
    http_response_code(404);
    die('Controlador no encontrado');
}

// Solo nombres de acción normales (bloquea __construct y similares).
if (!is_string($accion) || !preg_match('/^[a-z][A-Za-z0-9]*$/', $accion)) {
    http_response_code(404);
    die('Acción no encontrada');
}

// Puerta de entrada: sin sesión, solo se permite lo público.
if (!in_array($accion, $accionesPublicas[$controllerNombre] ?? [], true)) {
    requerirLogin();
}

require_once $mapaControladores[$controllerNombre];
$clase = $mapaClases[$controllerNombre];
$controller = new $clase();

if (!method_exists($controller, $accion) || !is_callable([$controller, $accion])) {
    http_response_code(404);
    die('Acción no encontrada');
}

$controller->$accion();
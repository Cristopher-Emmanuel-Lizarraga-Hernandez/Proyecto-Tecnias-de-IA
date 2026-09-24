<?php
/**
 * public/index.php — Front controller.
 * Todas las peticiones pasan por aquí y se enrutan según ?controller=&action=
 * Ej: index.php?controller=tutor&action=registrar
 */

$controllerNombre = $_GET['controller'] ?? 'dashboard';
$accion = $_GET['action'] ?? 'index';

$mapaControladores = [
    'dashboard' => __DIR__ . '/../app/controllers/DashboardController.php',
    'tutor' => __DIR__ . '/../app/controllers/TutorController.php',
    'bitacora' => __DIR__ . '/../app/controllers/BitacoraController.php',
];

$mapaClases = [
    'dashboard' => 'DashboardController',
    'tutor' => 'TutorController',
    'bitacora' => 'BitacoraController',
];

if (!isset($mapaControladores[$controllerNombre])) {
    http_response_code(404);
    die('Controlador no encontrado');
}

require_once $mapaControladores[$controllerNombre];
$clase = $mapaClases[$controllerNombre];
$controller = new $clase();

if (!method_exists($controller, $accion)) {
    http_response_code(404);
    die('Acción no encontrada');
}

$controller->$accion();

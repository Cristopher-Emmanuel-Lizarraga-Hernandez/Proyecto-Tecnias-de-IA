<?php
/**
 * database/crear_admin.php
 * Crea (o actualiza la contraseña de) un usuario del panel desde la terminal.
 * La contraseña NUNCA queda escrita en el código: se guarda con password_hash.
 *
 * Uso:
 *     php database/crear_admin.php
 *     php database/crear_admin.php admin  "Ana Admin" ana@escuela.mx
 *     (el 1er argumento es el rol: admin | personal)
 */
if (PHP_SAPI !== 'cli') {
    exit('Solo se puede ejecutar desde la terminal.');
}

require_once __DIR__ . '/../panel-web/config/database.php';
require_once __DIR__ . '/../panel-web/app/models/Usuario.php';

function preguntar(string $texto): string
{
    echo $texto;
    return trim((string) fgets(STDIN));
}

$rol    = $argv[1] ?? 'admin';
$nombre = $argv[2] ?? preguntar('Nombre completo: ');
$correo = $argv[3] ?? preguntar('Correo: ');

if (!in_array($rol, ['admin', 'personal'], true)) {
    exit("Rol inválido. Usa: admin | personal\n");
}
if ($nombre === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    exit("Nombre o correo inválido.\n");
}

$password = preguntar('Contraseña (mínimo 8 caracteres): ');
if (strlen($password) < 8) {
    exit("La contraseña es muy corta.\n");
}

$modelo = new Usuario();
$existente = $modelo->buscarPorCorreo($correo);

if ($existente) {
    $modelo->actualizarPassword((int) $existente['id'], $password);
    echo "Contraseña actualizada para $correo\n";
} else {
    $id = $modelo->crear($nombre, $correo, $password, $rol);
    echo "Usuario creado (id=$id, rol=$rol): $correo\n";
}
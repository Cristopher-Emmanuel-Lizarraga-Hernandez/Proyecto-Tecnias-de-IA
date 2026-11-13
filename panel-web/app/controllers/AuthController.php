<?php
/**
 * app/controllers/AuthController.php
 * Inicio y cierre de sesión del panel.
 */
require_once __DIR__ . '/../models/Usuario.php';

class AuthController
{
    private const MAX_INTENTOS = 5;
    private const BLOQUEO_SEG  = 300; // 5 min
    // Hash válido de una contraseña inexistente: se usa cuando el correo no está registrado.
    private const HASH_FALSO   = '$2y$10$eUDj/GPNkzSjWuNhHBHfKeBZ6la7Oakxu6c9d4fgciwTnJ7r7x7W2';

    /** GET: muestra el formulario de login */
    public function login(): void
    {
        if (usuarioActual() !== null) {
            redirigir('index.php?controller=dashboard&action=index');
        }
        $error = $_SESSION['error_login'] ?? null;
        $aviso = $_SESSION['aviso_login'] ?? null;
        unset($_SESSION['error_login'], $_SESSION['aviso_login']);

        require __DIR__ . '/../views/auth/login.php';
    }

    /** POST: valida credenciales */
    public function autenticar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirigir('index.php?controller=auth&action=login');
        }
        verificarCsrf();

        // Freno básico contra adivinar contraseñas.
        if (($_SESSION['bloqueado_hasta'] ?? 0) > time()) {
            $this->fallo('Demasiados intentos. Espera unos minutos e inténtalo de nuevo.');
        }

        $correo   = trim((string) ($_POST['correo'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        $usuario = (new Usuario())->buscarPorCorreo($correo);

        // Si el correo no existe, se compara contra un hash falso para que
        // tarde lo mismo y no se pueda averiguar qué correos están registrados.
        $hash = $usuario['password_hash'] ?? self::HASH_FALSO;
        $ok = password_verify($password, $hash) && $usuario !== null;

        if (!$ok) {
            $_SESSION['intentos_login'] = ($_SESSION['intentos_login'] ?? 0) + 1;
            if ($_SESSION['intentos_login'] >= self::MAX_INTENTOS) {
                $_SESSION['bloqueado_hasta'] = time() + self::BLOQUEO_SEG;
                $_SESSION['intentos_login'] = 0;
            }
            $this->fallo('Correo o contraseña incorrectos.');
        }

        // Evita "session fixation": nueva ID de sesión al iniciar.
        session_regenerate_id(true);
        unset($_SESSION['intentos_login'], $_SESSION['bloqueado_hasta']);

        $_SESSION['usuario'] = [
            'id'     => (int) $usuario['id'],
            'nombre' => $usuario['nombre'],
            'rol'    => $usuario['rol'],
        ];
        $_SESSION['ultima_actividad'] = time();

        redirigir('index.php?controller=dashboard&action=index');
    }

    /** POST: cierra la sesión (por POST + CSRF para que un enlace ajeno no te saque) */
    public function logout(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirigir('index.php?controller=dashboard&action=index');
        }
        verificarCsrf();
        cerrarSesion();
        redirigir('index.php?controller=auth&action=login');
    }

    private function fallo(string $mensaje): never
    {
        $_SESSION['error_login'] = $mensaje;
        redirigir('index.php?controller=auth&action=login');
    }
}
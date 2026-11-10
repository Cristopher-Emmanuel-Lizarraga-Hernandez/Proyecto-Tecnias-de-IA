<?php
/**
 * app/helpers/seguridad.php
 * Sesión segura, control de acceso y protección CSRF.
 * Se carga una sola vez desde public/index.php.
 */

const SESION_INACTIVIDAD_MAX = 1800; // 30 min sin actividad cierran la sesión

function iniciarSesionSegura(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('pase_sid');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,       // JavaScript no puede leer la cookie
        'samesite' => 'Lax',      // la cookie no viaja en peticiones POST de otros sitios
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();

    // Cierra la sesión por inactividad.
    if (isset($_SESSION['usuario'])) {
        $ultima = $_SESSION['ultima_actividad'] ?? time();
        if (time() - $ultima > SESION_INACTIVIDAD_MAX) {
            cerrarSesion();
            session_start();
            $_SESSION['aviso_login'] = 'Tu sesión expiró por inactividad.';
        }
        $_SESSION['ultima_actividad'] = time();
    }
}

function cerrarSesion(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function usuarioActual(): ?array
{
    return $_SESSION['usuario'] ?? null;
}

function redirigir(string $url): never
{
    header('Location: ' . $url);
    exit;
}

/**
 * Exige sesión iniciada y, opcionalmente, uno de los roles indicados.
 * Úsala al inicio de cualquier acción que no deba ser pública.
 */
function requerirLogin(array $rolesPermitidos = []): void
{
    $u = usuarioActual();
    if ($u === null) {
        redirigir('index.php?controller=auth&action=login');
    }
    if ($rolesPermitidos && !in_array($u['rol'], $rolesPermitidos, true)) {
        http_response_code(403);
        exit('403 — No tienes permiso para realizar esta acción.');
    }
}

/* ---------- CSRF ---------- */

function csrfToken(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

/** Imprime el campo oculto que debe llevar todo formulario POST. */
function csrfCampo(): string
{
    return '<input type="hidden" name="_csrf" value="' . h(csrfToken()) . '">';
}

function verificarCsrf(): void
{
    $enviado = $_POST['_csrf'] ?? '';
    if (!is_string($enviado) || !hash_equals(csrfToken(), $enviado)) {
        http_response_code(419);
        exit('Solicitud inválida (token CSRF). Regresa, recarga la página e inténtalo de nuevo.');
    }
}

/** Atajo para escapar texto en HTML. */
function h(?string $texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
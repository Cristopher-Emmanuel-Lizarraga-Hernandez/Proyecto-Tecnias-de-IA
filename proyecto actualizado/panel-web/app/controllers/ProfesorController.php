<?php
require_once __DIR__ . '/../models/Profesor.php';

class ProfesorController
{
    private Profesor $profesor;
    private const URL = 'index.php?controller=profesor&action=index';
    public function __construct()
    {
        requerirLogin(['admin']);
        $this->profesor = new Profesor();
    }
    public function index(): void
    {
        $profesores = $this->profesor->listarTodos();
        $error = $_SESSION['profesor_error'] ?? null;
        $mensaje = $_SESSION['profesor_mensaje'] ?? null;
        unset($_SESSION['profesor_error'], $_SESSION['profesor_mensaje']);
        require __DIR__ . '/../views/profesores/index.php';
    }
    public function editar(): void
    {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $p = $this->profesor->buscarPorId((int)$id);
        if (!$p) { http_response_code(404); exit('Profesor no encontrado'); }
        require __DIR__ . '/../views/profesores/editar.php';
    }
    public function registrar(): void { $this->guardar(false); }
    public function actualizar(): void { $this->guardar(true); }
    public function cambiarEstado(): void
    {
        $this->validarPost();
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $activo = $_POST['activo'] ?? '';
        if (!$id || !in_array($activo, ['0', '1'], true) || !$this->profesor->buscarPorId($id)) {
            $this->fallo('Profesor o estado inválido.');
        }
        $this->profesor->cambiarEstado($id, (int)$activo);
        $_SESSION['profesor_mensaje'] = 'Estado actualizado.';
        redirigir(self::URL);
    }
    private function guardar(bool $editar): void
    {
        $this->validarPost();
        $nombre = is_string($_POST['nombre'] ?? null) ? trim($_POST['nombre']) : '';
        $correo = is_string($_POST['correo'] ?? null) ? trim($_POST['correo']) : '';
        if ($nombre === '' || mb_strlen($nombre) > 150 || strlen($correo) > 150 || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $this->fallo('Ingresa un nombre y correo válidos.');
        }
        try {
            if ($editar) {
                $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
                if (!$id || !$this->profesor->buscarPorId($id)) { $this->fallo('Profesor no encontrado.'); }
                $this->profesor->actualizar($id, $nombre, $correo);
            } else { $this->profesor->registrar($nombre, $correo); }
        } catch (PDOException $e) {
            if ((int)($e->errorInfo[1] ?? 0) === 1062) { $this->fallo('Ese correo ya está registrado.'); }
            error_log('Error al guardar profesor: ' . $e->getCode());
            $this->fallo('No se pudo guardar el profesor.');
        }
        $_SESSION['profesor_mensaje'] = 'Profesor guardado correctamente.';
        redirigir(self::URL);
    }
    private function validarPost(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Método no permitido'); }
        verificarCsrf();
    }
    private function fallo(string $mensaje): never
    {
        $_SESSION['profesor_error'] = $mensaje;
        redirigir(self::URL);
    }
}

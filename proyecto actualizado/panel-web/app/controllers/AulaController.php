<?php
require_once __DIR__ . '/../models/Aula.php';
class AulaController
{
    private Aula $aula;
    private const URL = 'index.php?controller=aula&action=index';
    public function __construct() { requerirLogin(['admin']); $this->aula = new Aula(); }
    public function index(): void
    {
        $aulas = $this->aula->listarTodos();
        $profesores = $this->aula->profesoresActivos();
        $error = $_SESSION['aula_error'] ?? null;
        $mensaje = $_SESSION['aula_mensaje'] ?? null;
        unset($_SESSION['aula_error'], $_SESSION['aula_mensaje']);
        require __DIR__ . '/../views/aulas/index.php';
    }
    public function editar(): void
    {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $a = $id ? $this->aula->buscarPorId($id) : null;
        if (!$a) { http_response_code(404); exit('Aula no encontrada'); }
        $profesores = $this->aula->profesoresActivos();
        require __DIR__ . '/../views/aulas/editar.php';
    }
    public function registrar(): void { $this->guardar(false); }
    public function actualizar(): void { $this->guardar(true); }
    private function guardar(bool $editar): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Método no permitido'); }
        verificarCsrf();
        $nombre = is_string($_POST['nombre'] ?? null) ? trim($_POST['nombre']) : '';
        $profesor = filter_input(INPUT_POST, 'profesor_id', FILTER_VALIDATE_INT);
        $id = $editar ? filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) : null;
        if ($nombre === '' || mb_strlen($nombre) > 50 || !$profesor || $profesor < 1) $this->fallo('Ingresa un nombre y selecciona un profesor activo.');
        if ($editar && (!$id || !$this->aula->buscarPorId($id))) $this->fallo('Aula no encontrada.');
        try {
            if (!$this->aula->guardar($id, $nombre, $profesor)) $this->fallo('El profesor seleccionado no está activo.');
        } catch (PDOException $e) {
            if ((int)($e->errorInfo[1] ?? 0) === 1062) $this->fallo('Ya existe un aula con ese nombre.');
            error_log('Error al guardar aula: ' . $e->getCode());
            $this->fallo('No se pudo guardar el aula.');
        }
        $_SESSION['aula_mensaje'] = 'Aula guardada correctamente.';
        redirigir(self::URL);
    }
    private function fallo(string $mensaje): never
    {
        $_SESSION['aula_error'] = $mensaje;
        redirigir(self::URL);
    }
}

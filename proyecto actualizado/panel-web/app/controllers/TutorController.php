<?php
/**
 * app/controllers/TutorController.php
 * Registro de tutores (con rostro tomado por la cámara del navegador) y listado.
 */
require_once __DIR__ . '/../models/Alumno.php';
require_once __DIR__ . '/../models/Tutor.php';

class TutorController
{
    private Alumno $alumnoModel;
    private Tutor $tutorModel;

    public function __construct()
    {
        $this->alumnoModel = new Alumno();
        $this->tutorModel = new Tutor();
    }

    /** Muestra el formulario de registro y la lista de tutores */
    public function index(): void
    {
        $tutores = $this->tutorModel->listarTodos();
        require __DIR__ . '/../views/tutores/registrar.php';
    }

    /**
     * Recibe el formulario + las fotos que tomó la cámara del navegador (en JSON)
     * y responde en JSON. Todo ocurre en una transacción: alumno y tutor solo quedan
     * guardados si el rostro se procesó bien.
     */
    public function registrarConRostro(): void
    {
        requerirLogin(['admin']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->responder(false, 'Método no permitido.', 405);
        }
        // Si las fotos superan post_max_size, PHP deja $_POST completamente vacío.
        if (empty($_POST) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
            $this->responder(false, 'Las fotos pesan demasiado para la configuración de PHP (post_max_size).', 413);
        }
        verificarCsrf();

        $nombreAlumno = trim((string) ($_POST['nombre_alumno'] ?? ''));
        $grupo = trim((string) ($_POST['grupo'] ?? ''));
        $nombreTutor = trim((string) ($_POST['nombre_tutor'] ?? ''));
        $parentesco = trim((string) ($_POST['parentesco'] ?? 'tutor'));
        if (!in_array($parentesco, ['padre', 'madre', 'tutor'], true)) {
            $parentesco = 'tutor';
        }

        if (($_POST['consentimiento'] ?? '') !== '1') {
            $this->responder(false, 'Falta la autorización del tutor para registrar su rostro.', 422);
        }
        if ($nombreAlumno === '' || $nombreTutor === '') {
            $this->responder(false, 'El nombre del alumno y del tutor son obligatorios.', 422);
        }
        if (mb_strlen($nombreAlumno) > 100 || mb_strlen($nombreTutor) > 100 || mb_strlen($grupo) > 100) {
            $this->responder(false, 'Algún dato es demasiado largo.', 422);
        }

        $fotos = json_decode((string) ($_POST['frames'] ?? '[]'), true);
        if (!is_array($fotos) || count($fotos) < 10 || count($fotos) > 40) {
            $this->responder(false, 'No llegaron suficientes fotos de la cámara.', 422);
        }

        $db = Database::getConexion();
        $tmp = null;
        $resultado = [false, 'Error inesperado.'];

        try {
            $tmp = $this->crearCarpetaTemporal();
            $this->guardarFotos($fotos, $tmp);

            // Alumno y tutor se insertan dentro de una transacción...
            $db->beginTransaction();
            $label = $this->tutorModel->siguienteEmbeddingLabel();
            $alumnoId = $this->alumnoModel->registrar($nombreAlumno, $grupo ?: null);
            $this->tutorModel->registrar($nombreTutor, $alumnoId, $label, $parentesco);

            // ...y solo se confirman si Python logra guardar el rostro y entrenar el modelo.
            [$ok, $detalle] = $this->entrenarRostro($label, $nombreTutor, $tmp);
            if (!$ok) {
                throw new RuntimeException($detalle);
            }
            $db->commit();
            $resultado = [true, "Tutor registrado con su rostro (label $label). Si main.py está abierto, reinícialo para que use el modelo nuevo."];
        } catch (PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log('[registro tutor] ' . $e->getMessage());
            $resultado = [false, 'Error de base de datos. No se guardó nada.'];
        } catch (RuntimeException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $resultado = [false, $e->getMessage()];
        } finally {
            // Las fotos crudas se borran siempre (son datos biométricos).
            if ($tmp !== null) {
                $this->borrarCarpeta($tmp);
            }
        }

        $this->responder($resultado[0], $resultado[1]);
    }

    /** Elimina un tutor (pendiente de mejorar: pasar a POST + CSRF y borrar su rostro del modelo) */
    public function eliminar(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id > 0) {
            $this->tutorModel->eliminar($id);
        }
        header('Location: index.php?controller=tutor&action=index');
        exit;
    }

    /* ---------------- utilidades privadas ---------------- */

    private function responder(bool $ok, string $mensaje, int $codigo = 200): never
    {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => $ok, 'mensaje' => $mensaje], JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function crearCarpetaTemporal(): string
    {
        $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pase_reg_' . bin2hex(random_bytes(8));
        if (!mkdir($dir, 0700)) {
            throw new RuntimeException('No se pudo crear una carpeta temporal.');
        }
        return $dir;
    }

    private function borrarCarpeta(string $dir): void
    {
        foreach (glob($dir . DIRECTORY_SEPARATOR . '*') ?: [] as $archivo) {
            @unlink($archivo);
        }
        @rmdir($dir);
    }

    /** Valida y guarda cada foto (base64 -> .jpg). Rechaza cualquier cosa que no sea un JPEG razonable. */
    private function guardarFotos(array $fotos, string $carpeta): void
    {
        foreach (array_values($fotos) as $i => $b64) {
            if (!is_string($b64)) {
                throw new RuntimeException('Las fotos recibidas no son válidas.');
            }
            $b64 = preg_replace('#^data:image/\w+;base64,#', '', $b64);
            $bin = base64_decode((string) $b64, true);
            if ($bin === false || strlen($bin) > 1_000_000 || strncmp($bin, "\xFF\xD8\xFF", 3) !== 0) {
                throw new RuntimeException('Una de las fotos no es válida.');
            }
            $info = @getimagesizefromstring($bin);
            if ($info === false || $info[2] !== IMAGETYPE_JPEG || $info[0] > 2000 || $info[1] > 2000) {
                throw new RuntimeException('Una de las fotos no es válida.');
            }
            file_put_contents($carpeta . DIRECTORY_SEPARATOR . sprintf('%03d.jpg', $i), $bin);
        }
    }

    /** Lanza registrar_web.py y devuelve [éxito, mensaje]. */
    private function entrenarRostro(int $label, string $nombre, string $carpetaFotos): array
    {
        set_time_limit(180);
        putenv('PYTHONIOENCODING=utf-8');
        $cfg = require __DIR__ . '/../../config/python.php';

        // Con arreglo, PHP no usa la shell: no hay inyección de comandos ni problemas de comillas.
        $cmd = [
            $cfg['python'], 'registrar_web.py',
            '--label', (string) $label,
            '--nombre', $nombre,
            '--imagenes', $carpetaFotos,
            '--minimo', (string) ($cfg['min_rostros'] ?? 15),
        ];

        $proc = proc_open($cmd, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $cfg['carpeta']);
        if (!is_resource($proc)) {
            return [false, 'No se pudo iniciar Python. Revisa la ruta en config/python.php.'];
        }
        fclose($pipes[0]);
        $salida = trim((string) stream_get_contents($pipes[1]));
        $errores = trim((string) stream_get_contents($pipes[2]));
        fclose($pipes[1]);
        fclose($pipes[2]);
        $codigo = proc_close($proc);

        if ($codigo === 0) {
            return [true, $salida];
        }
        return [false, $salida !== '' ? $salida : ($errores !== '' ? $errores : 'Python terminó con error.')];
    }
}
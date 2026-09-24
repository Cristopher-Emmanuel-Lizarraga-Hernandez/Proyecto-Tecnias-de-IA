<?php
/**
 * app/controllers/TutorController.php
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

    /** Muestra el formulario de registro */
    public function index(): void
    {
        $tutores = $this->tutorModel->listarTodos();
        require __DIR__ . '/../views/tutores/registrar.php';
    }

    /** Procesa el formulario (registro manual, sin cámara) */
    public function registrar(): void
    {
        $nombreAlumno = trim($_POST['nombre_alumno'] ?? '');
        $grupo = trim($_POST['grupo'] ?? '');
        $nombreTutor = trim($_POST['nombre_tutor'] ?? '');
        $parentesco = trim($_POST['parentesco'] ?? 'tutor');

        if ($nombreAlumno === '' || $nombreTutor === '') {
            $error = 'El nombre del alumno y del tutor son obligatorios.';
            $tutores = $this->tutorModel->listarTodos();
            require __DIR__ . '/../views/tutores/registrar.php';
            return;
        }

        $alumnoId = $this->alumnoModel->registrar($nombreAlumno, $grupo ?: null);
        $embeddingLabel = $this->tutorModel->siguienteEmbeddingLabel();
        $this->tutorModel->registrar($nombreTutor, $alumnoId, $embeddingLabel, $parentesco);

        // Nota: este registro manual crea al tutor en la base de datos, pero
        // el ENROLLMENT FACIAL (captura y entrenamiento del rostro) sigue
        // haciéndose con registrar.py en Python, usando este mismo
        // embedding_label para mantener la sincronía entre ambos sistemas.
        $mensaje = "Tutor registrado. Ahora corre: python registrar.py (usará el label $embeddingLabel automáticamente si sigues el mismo orden).";

        $tutores = $this->tutorModel->listarTodos();
        require __DIR__ . '/../views/tutores/registrar.php';
    }

    public function eliminar(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id > 0) {
            $this->tutorModel->eliminar($id);
        }
        header('Location: index.php?controller=tutor&action=index');
        exit;
    }
}

<?php
/**
 * app/controllers/DashboardController.php
 */
require_once __DIR__ . '/../models/BitacoraRecogida.php';
require_once __DIR__ . '/../models/Tutor.php';
require_once __DIR__ . '/../models/Alumno.php';

class DashboardController
{
    public function index(): void
    {
        $bitacoraModel = new BitacoraRecogida();
        $tutorModel = new Tutor();
        $alumnoModel = new Alumno();

        $recogidasHoy = $bitacoraModel->contarRecogidasHoy();
        $intentosNoAutorizados = $bitacoraModel->contarIntentosNoAutorizados();
        $totalTutores = count($tutorModel->listarTodos());
        $totalAlumnos = count($alumnoModel->listarTodos());
        $ultimosRegistros = $bitacoraModel->historial(5);

        require __DIR__ . '/../views/dashboard/index.php';
    }
}

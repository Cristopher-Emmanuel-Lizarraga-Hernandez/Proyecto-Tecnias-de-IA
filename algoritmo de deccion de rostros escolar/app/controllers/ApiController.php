<?php
namespace App\Controllers;

use App\Models\Tutor;
use App\Models\BitacoraRecogida;

class ApiController {

    /**
     * Endpoint POST: /api/validar-biometria
     * Recibe JSON desde Python con el ID del tutor identificado o el vector de embeddings
     */
    public function validarBiometria() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'mensaje' => 'Método no permitido']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        $tutorId = $input['tutor_id'] ?? null;
        $confianza = $input['confianza'] ?? 0; // Porcentaje de coincidencia biométrica

        if (!$tutorId) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'mensaje' => 'ID de tutor no proporcionado']);
            return;
        }

        $tutorModel = new Tutor();
        $tutor = $tutorModel->obtenerConAlumnos($tutorId);

        if (!$tutor) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'mensaje' => 'Tutor no encontrado o no autorizado']);
            return;
        }

        // Registrar cada alumno asociado al tutor en la bitácora
        $bitacoraModel = new BitacoraRecogida();
        $registros = [];

        foreach ($tutor['alumnos'] as $alumno) {
            $idEntrega = $bitacoraModel->registrarEntrega([
                'alumno_id' => $alumno['id'],
                'tutor_id' => $tutor['id'],
                'metodo' => 'RECONOCIMIENTO_FACIAL',
                'estado' => 'PENDIENTE_EN_AULA',
                'confianza_biometrica' => $confianza
            ]);

            $registros[] = [
                'entrega_id' => $idEntrega,
                'alumno' => $alumno['nombre_completo'],
                'aula' => $alumno['aula']
            ];
        }

        echo json_encode([
            'status' => 'success',
            'mensaje' => 'Validación biométrica exitosa. Notificación enviada al aula.',
            'tutor' => $tutor['nombre_completo'],
            'entregas' => $registros
        ]);
    }

    /**
     * Endpoint GET: /api/notificaciones-aula?aula_id=X
     * Usado por las pantallas de los salones para consulta en tiempo real
     */
    public function notificacionesAula() {
        header('Content-Type: application/json');

        $aulaId = $_GET['aula_id'] ?? null;
        $bitacoraModel = new BitacoraRecogida();

        $pendientes = $bitacoraModel->obtenerPendientesPorAula($aulaId);

        echo json_encode([
            'status' => 'success',
            'data' => $pendientes
        ]);
    }
}
<?php
namespace app\controllers;

use app\models\Tutor;
use app\models\BitacoraRecogida;

class ReconocimientoController {

    /**
     * Vista principal del punto de captura (Muestra transmisión de cámara)
     */
    public function captura() {
        // Renderiza la vista con el visor de cámara WebCam / ESP32-CAM
        require_once __DIR__ . '/../views/reconocimiento/captura.php';
    }

    /**
     * Vista y procesamiento del protocolo de respaldo por PIN
     */
    public function pin() {
        $error = null;
        $exito = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $codigoPin = trim($_POST['pin'] ?? '');

            if (empty($codigoPin)) {
                $error = "Por favor ingrese un código PIN válido.";
            } else {
                $tutorModel = new Tutor();
                $tutor = $tutorModel->buscarPorPin($codigoPin);

                if ($tutor) {
                    $bitacoraModel = new BitacoraRecogida();
                    foreach ($tutor['alumnos'] as $alumno) {
                        $bitacoraModel->registrarEntrega([
                            'alumno_id' => $alumno['id'],
                            'tutor_id' => $tutor['id'],
                            'metodo' => 'PIN_RESPALDO',
                            'estado' => 'PENDIENTE_EN_AULA',
                            'confianza_biometrica' => null
                        ]);
                    }
                    $exito = "Validación por PIN correcta para tutor: " . $tutor['nombre_completo'];
                } else {
                    $error = "Código PIN incorrecto o tutor no autorizado.";
                }
            }
        }

        require_once __DIR__ . '/../views/reconocimiento/pin.php';
    }
}
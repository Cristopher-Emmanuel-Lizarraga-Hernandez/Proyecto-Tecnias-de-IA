<?php
/**
 * app/controllers/DashboardController.php
 * Prepara los datos que muestra app/views/dashboard/index.php
 */
require_once __DIR__ . '/../models/BitacoraRecogida.php';

class DashboardController
{
    public function index(): void
    {
        $bitacoraModel = new BitacoraRecogida();

        $autorizadasHoy = $bitacoraModel->contarRecogidasHoy();

        // Tarjetas (KPIs) del dashboard
        $totales = [
            'hoy' => $autorizadasHoy,
            'facial' => $autorizadasHoy, // por ahora toda entrega se valida con reconocimiento facial
            'pin' => 0,                  // protocolo de respaldo por PIN: aún no implementado
            'no_autorizados' => $bitacoraModel->contarNoAutorizadosHoy(),
        ];

        // Filas de la tabla de bitácora
        $registrosBitacora = $bitacoraModel->historialDetallado(200);

        require __DIR__ . '/../views/dashboard/index.php';
    }
}

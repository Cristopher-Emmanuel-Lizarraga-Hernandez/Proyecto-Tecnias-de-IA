<?php
/**
 * app/controllers/BitacoraController.php
 */
namespace app\controllers;
require_once __DIR__ . '/../models/BitacoraRecogida.php';

class BitacoraController
{
    private BitacoraRecogida $bitacoraModel;

    public function __construct()
    {
        $this->bitacoraModel = new BitacoraRecogida();
    }

    public function index(): void
    {
        $registros = $this->bitacoraModel->historial(50);
        require __DIR__ . '/../views/bitacora/historial.php';
    }
}

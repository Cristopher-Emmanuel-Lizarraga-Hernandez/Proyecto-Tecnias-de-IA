<?php

require_once __DIR__ . '/../models/BitacoraRecogida.php';

class BitacoraController
{
    private BitacoraRecogida $bitacora;

    public function __construct()
    {
        $this->bitacora = new BitacoraRecogida();
    }
    
    public function index(): void
    {
        $registros = $this->bitacora->historialDetallado();

        require __DIR__ . '/../views/bitacora/historial.php';
    }
}
?>
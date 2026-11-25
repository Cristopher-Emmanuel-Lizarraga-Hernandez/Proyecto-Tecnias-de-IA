<?php
// Configuración para lanzar los scripts de Python desde el panel web.
// En 'python' pon la ruta que te muestre este comando en la terminal:  where python
return [
    'python'      => 'C:\\Users\\erikw\\AppData\\Local\\Programs\\Python\\Python311\\python.exe',
    'carpeta'     => dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'reconocimiento-facial',
    'min_rostros' => 15,   // de las 25 fotos, cuántas deben tener un rostro detectable
];
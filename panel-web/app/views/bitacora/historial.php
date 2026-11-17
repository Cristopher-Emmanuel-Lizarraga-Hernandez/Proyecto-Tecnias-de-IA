<?php
// app/views/bitacora/historial.php
// Variable que entrega BitacoraController: $registros
$registros = $registros ?? [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bitácora - Pase Inteligente Escolar</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="estilos.css">
    <script src="app.js" defer></script>
</head>
<body>
<?php require __DIR__ . '/../../partials/nav.php'; ?>

<main class="contenedor">
    <div class="pagina-cabecera">
        <div>
            <h1>Bitácora de recogidas</h1>
            <p class="subtitulo">Los últimos <?= count($registros) ?> intentos de recogida, autorizados o no.</p>
        </div>
    </div>

    <section class="panel" data-tabla="bitacora">
        <div class="panel-cabecera">
            <h2>Historial</h2>
            <div class="herramientas">
                <input class="campo" type="search" placeholder="Buscar tutor o alumno" aria-label="Buscar" data-buscar>
                <div class="segmentos" role="group" aria-label="Filtrar por estado">
                    <button type="button" data-filtro="todos" aria-pressed="true">Todas</button>
                    <button type="button" data-filtro="ok" aria-pressed="false">Autorizadas</button>
                    <button type="button" data-filtro="no" aria-pressed="false">No autorizadas</button>
                </div>
                <button type="button" class="boton" data-exportar>Exportar CSV</button>
                <button type="button" class="boton" data-imprimir>Imprimir</button>
            </div>
        </div>

        <?php if (empty($registros)): ?>
            <div class="vacio">
                <strong>Todavía no hay movimientos</strong>
                Cada vez que el punto de recogida analice a una persona, quedará registrado aquí.
            </div>
        <?php else: ?>
            <div class="tabla-scroll">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th>Fecha y hora</th>
                            <th>Tutor</th>
                            <th>Alumno</th>
                            <th title="Distancia del modelo LBPH: menor es mejor coincidencia">Distancia LBPH</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($registros as $r):
                        $autorizado = !empty($r['autorizado']);
                    ?>
                        <tr data-fila data-estado="<?= $autorizado ? 'ok' : 'no' ?>" class="<?= $autorizado ? '' : 'fila-alerta' ?>">
                            <td class="num"><?= htmlspecialchars(date('d/m/Y H:i:s', strtotime($r['fecha_hora']))) ?></td>
                            <td><?= htmlspecialchars($r['tutor'] ?? ($autorizado ? '—' : 'Persona no reconocida')) ?></td>
                            <td><?= htmlspecialchars($r['alumno'] ?? '—') ?></td>
                            <td class="num"><?= $r['confianza'] !== null ? number_format((float) $r['confianza'], 1) : '—' ?></td>
                            <td>
                                <?php if ($autorizado): ?>
                                    <span class="pildora ok">Autorizado</span>
                                <?php else: ?>
                                    <span class="pildora alerta">No autorizado</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="vacio" data-sin-resultados hidden>
                <strong>Sin resultados</strong>
                Prueba con otro nombre o cambia el filtro.
            </div>
            <div class="pie-tabla" data-contador></div>
        <?php endif; ?>
    </section>
</main>
</body>
</html>

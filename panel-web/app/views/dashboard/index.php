<?php
// app/views/dashboard/index.php
// Variables que entrega DashboardController: $totales, $registrosBitacora
$totales = $totales ?? [];
$registrosBitacora = $registrosBitacora ?? [];
$hoy = (int) ($totales['hoy'] ?? 0);
$facial = (int) ($totales['facial'] ?? 0);
$pin = (int) ($totales['pin'] ?? 0);
$noAutorizados = (int) ($totales['no_autorizados'] ?? 0);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resumen de hoy - Pase Inteligente Escolar</title>
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
            <h1>Resumen de hoy</h1>
            <p class="subtitulo">Entregas y alertas del día <?= date('d/m/Y') ?>.</p>
        </div>
        <a class="boton boton-primario" href="index.php?controller=bitacora&action=index">Ver bitácora completa</a>
    </div>

    <section class="resumen" aria-label="Resumen del día">
        <div class="cifra <?= $hoy > 0 ? 'hay-ok' : '' ?>">
            <div class="cifra-valor"><?= $hoy ?></div>
            <div class="cifra-nombre">Entregas autorizadas</div>
        </div>
        <div class="cifra">
            <div class="cifra-valor"><?= $facial ?></div>
            <div class="cifra-nombre">Con reconocimiento facial</div>
        </div>
        <div class="cifra">
            <div class="cifra-valor"><?= $pin ?></div>
            <div class="cifra-nombre">Con PIN de respaldo</div>
            <div class="cifra-nota">Aún no disponible</div>
        </div>
        <div class="cifra <?= $noAutorizados > 0 ? 'hay-alerta' : '' ?>">
            <div class="cifra-valor"><?= $noAutorizados ?></div>
            <div class="cifra-nombre">Intentos no autorizados</div>
        </div>
    </section>

    <section class="panel" data-tabla="entregas">
        <div class="panel-cabecera">
            <h2>Entregas recientes</h2>
            <div class="herramientas">
                <input class="campo" type="search" placeholder="Buscar alumno o tutor" aria-label="Buscar" data-buscar>
                <div class="segmentos" role="group" aria-label="Filtrar por estado">
                    <button type="button" data-filtro="todos" aria-pressed="true">Todas</button>
                    <button type="button" data-filtro="ok" aria-pressed="false">Autorizadas</button>
                    <button type="button" data-filtro="no" aria-pressed="false">No autorizadas</button>
                </div>
                <button type="button" class="boton" data-exportar>Exportar CSV</button>
                <button type="button" class="boton" data-imprimir>Imprimir</button>
            </div>
        </div>

        <?php if (empty($registrosBitacora)): ?>
            <div class="vacio">
                <strong>Todavía no hay entregas registradas</strong>
                Aparecerán aquí cuando el punto de recogida reconozca a un tutor.
            </div>
        <?php else: ?>
            <div class="tabla-scroll">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th>Fecha y hora</th>
                            <th>Alumno</th>
                            <th>Grupo</th>
                            <th>Tutor</th>
                            <th>Método</th>
                            <th title="Distancia del modelo LBPH: menor es mejor coincidencia">Distancia LBPH</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($registrosBitacora as $row):
                        $estado = $row['estado_entrega'] ?? '';
                        $esAlerta = ($estado === 'NO_AUTORIZADO');
                    ?>
                        <tr data-fila data-estado="<?= $esAlerta ? 'no' : 'ok' ?>" class="<?= $esAlerta ? 'fila-alerta' : '' ?>">
                            <td class="num"><?= htmlspecialchars(date('d/m/Y H:i:s', strtotime($row['fecha_hora_verificacion']))) ?></td>
                            <td><?= htmlspecialchars((string) $row['alumno_nombre']) ?></td>
                            <td><?php if (($row['grado_grupo'] ?? '—') !== '—'): ?><span class="etiqueta"><?= htmlspecialchars((string) $row['grado_grupo']) ?></span><?php else: ?>—<?php endif; ?></td>
                            <td><?= htmlspecialchars((string) $row['tutor_nombre']) ?></td>
                            <td><?= ($row['metodo_validacion'] ?? '') === 'RECONOCIMIENTO_FACIAL' ? 'Rostro' : 'PIN' ?></td>
                            <td class="num"><?= $row['confianza_biometrica'] !== null ? number_format((float) $row['confianza_biometrica'], 1) : '—' ?></td>
                            <td>
                                <?php if ($estado === 'ENTREGADO'): ?>
                                    <span class="pildora ok">Entregado</span>
                                <?php elseif ($esAlerta): ?>
                                    <span class="pildora alerta">No autorizado</span>
                                <?php else: ?>
                                    <span class="pildora neutra">En aula</span>
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

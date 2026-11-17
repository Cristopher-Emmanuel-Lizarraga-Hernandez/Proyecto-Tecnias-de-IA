<?php
// app/views/tutores/registrar.php
// Variable que entrega TutorController: $tutores
$tutores = $tutores ?? [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tutores - Pase Inteligente Escolar</title>
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
            <h1>Tutores</h1>
            <p class="subtitulo"><?= count($tutores) ?> tutor<?= count($tutores) === 1 ? '' : 'es' ?> con rostro registrado.</p>
        </div>
    </div>

    <p class="aviso">
        Para dar de alta a un tutor nuevo usa <strong>registrar_tutor.bat</strong> (o
        <strong>registrar.py</strong>). Escanea el rostro, entrena el modelo y guarda al
        tutor y a su alumno aquí.
    </p>

    <section class="panel" data-tabla="tutores">
        <div class="panel-cabecera">
            <h2>Tutores autorizados</h2>
            <div class="herramientas">
                <input class="campo" type="search" placeholder="Buscar tutor o alumno" aria-label="Buscar" data-buscar>
                <button type="button" class="boton" data-exportar>Exportar CSV</button>
            </div>
        </div>

        <?php if (empty($tutores)): ?>
            <div class="vacio">
                <strong>Aún no hay tutores registrados</strong>
                Registra el primero con registrar_tutor.bat.
            </div>
        <?php else: ?>
            <div class="tabla-scroll">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th>Tutor</th>
                            <th>Alumno</th>
                            <th>Parentesco</th>
                            <th title="Número con el que el modelo facial identifica a este tutor">ID facial</th>
                            <th class="derecha" data-sin-exportar><span class="no-imprimir">Acciones</span></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($tutores as $t): ?>
                        <tr data-fila>
                            <td>
                                <div class="persona">
                                    <span class="avatar" data-iniciales="<?= htmlspecialchars($t['nombre']) ?>" aria-hidden="true"></span>
                                    <?= htmlspecialchars($t['nombre']) ?>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($t['alumno_nombre']) ?></td>
                            <td><?= htmlspecialchars(ucfirst($t['parentesco'])) ?></td>
                            <td><span class="etiqueta"><?= (int) $t['embedding_label'] ?></span></td>
                            <td class="derecha" data-sin-exportar>
                                <a class="boton boton-enlace-peligro no-imprimir"
                                   href="index.php?controller=tutor&action=eliminar&id=<?= (int) $t['id'] ?>"
                                   onclick="return confirm('¿Eliminar a este tutor? Su historial en la bitácora se conserva.')">Eliminar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="vacio" data-sin-resultados hidden>
                <strong>Sin resultados</strong>
                Prueba con otro nombre.
            </div>
            <div class="pie-tabla" data-contador></div>
        <?php endif; ?>
    </section>
</main>
</body>
</html>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pase Inteligente Escolar — Tutores</title>
    <link rel="stylesheet" href="../public/estilos.css">
</head>
<body>
<?php require __DIR__ . '/../../partials/nav.php'; ?>

<main class="contenedor">
    <h1>Tutores registrados</h1>

    <p class="alerta alerta-ok">
        Para dar de alta a un tutor nuevo usa <strong>registrar.py</strong> (o
        <strong>registrar_tutor.bat</strong>): escanea el rostro, entrena el modelo y guarda
        al tutor y al alumno aquí.
    </p>

    <table class="tabla">
        <thead>
            <tr><th>Nombre</th><th>Alumno</th><th>Parentesco</th><th>Label facial</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($tutores as $t): ?>
            <tr>
                <td><?= htmlspecialchars($t['nombre']) ?></td>
                <td><?= htmlspecialchars($t['alumno_nombre']) ?></td>
                <td><?= htmlspecialchars($t['parentesco']) ?></td>
                <td><?= (int) $t['embedding_label'] ?></td>
                <td><a href="index.php?controller=tutor&action=eliminar&id=<?= (int) $t['id'] ?>"
                       onclick="return confirm('¿Eliminar este tutor?')">Eliminar</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</main>
</body>
</html>
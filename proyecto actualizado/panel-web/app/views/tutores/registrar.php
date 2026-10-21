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
    <h1>Registrar tutor</h1>

    <?php if (!empty($error)): ?>
        <p class="alerta alerta-error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <?php if (!empty($mensaje)): ?>
        <p class="alerta alerta-ok"><?= htmlspecialchars($mensaje) ?></p>
    <?php endif; ?>

    <form method="post" action="index.php?controller=tutor&action=registrar" class="formulario">
        <label>Nombre del alumno
            <input type="text" name="nombre_alumno" required>
        </label>
        <label>Grupo
            <input type="text" name="grupo" placeholder="ej. 3A">
        </label>
        <label>Nombre del tutor
            <input type="text" name="nombre_tutor" required>
        </label>
        <label>Parentesco
            <select name="parentesco">
                <option value="padre">Padre</option>
                <option value="madre">Madre</option>
                <option value="tutor">Tutor</option>
            </select>
        </label>
        <button type="submit">Registrar</button>
    </form>

    <h2>Tutores registrados</h2>
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
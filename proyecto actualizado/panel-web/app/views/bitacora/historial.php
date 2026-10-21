<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pase Inteligente Escolar — Bitácora</title>
    <link rel="stylesheet" href="../public/estilos.css">
</head>
<body>
<?php require __DIR__ . '/../../partials/nav.php'; ?>

<main class="contenedor">
    <h1>Bitácora de recogidas</h1>

    <table class="tabla">
        <thead>
            <tr><th>Fecha y hora</th><th>Tutor</th><th>Alumno</th><th>Confianza</th><th>Estado</th></tr>
        </thead>
        <tbody>
        <?php foreach ($registros as $r): ?>
            <tr class="<?= $r['autorizado'] ? 'fila-ok' : 'fila-alerta' ?>">
                <td><?= htmlspecialchars($r['fecha_hora']) ?></td>
                <td><?= htmlspecialchars($r['tutor'] ?? '—') ?></td>
                <td><?= htmlspecialchars($r['alumno'] ?? '—') ?></td>
                <td><?= htmlspecialchars((string) $r['confianza']) ?></td>
                <td><?= $r['autorizado'] ? 'Autorizado' : 'NO autorizado' ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($registros)): ?>
            <tr><td colspan="5">Aún no hay registros. Corre main.py --verificar para generar datos.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</main>
</body>
</html>
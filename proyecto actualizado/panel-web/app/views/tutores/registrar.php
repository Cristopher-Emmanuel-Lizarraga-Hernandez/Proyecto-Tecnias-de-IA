<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pase Inteligente Escolar — Tutores</title>
    <link rel="stylesheet" href="../public/estilos.css">
    <style>
        .camara-caja { margin: 1rem 0; }
        .camara-caja video { display: block; width: 100%; max-width: 420px; aspect-ratio: 4 / 3;
                             background: #111; border-radius: 10px; transform: scaleX(-1); /* efecto espejo */ }
        .camara-caja progress { width: 100%; max-width: 420px; margin-top: .5rem; }
        .check { display: flex; gap: .5rem; align-items: flex-start; margin: 1rem 0; }
        #estado-registro { margin: .75rem 0; font-weight: 600; }
        .estado-info  { color: #1d4ed8; }
        .estado-ok    { color: #166534; }
        .estado-error { color: #b91c1c; }
    </style>
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

    <form method="post" action="index.php?controller=tutor&action=registrarConRostro" class="formulario" id="form-registro">
        <?= csrfCampo() ?>
        <label>Nombre del alumno
            <input type="text" name="nombre_alumno" required maxlength="100">
        </label>
        <label>Aula del alumno
            <select name="aula_id" required>
                <option value="">Selecciona un aula</option>
                <?php foreach ($aulas as $aula): ?>
                    <option value="<?= (int) $aula['id'] ?>"><?= htmlspecialchars($aula['nombre'] . ' — ' . $aula['profesor'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <?php if (empty($aulas)): ?>
            <p class="alerta alerta-error">Primero registra un aula con un profesor activo.</p>
        <?php endif; ?>
        <label>Nombre del tutor
            <input type="text" name="nombre_tutor" required maxlength="100">
        </label>
        <label>Parentesco
            <select name="parentesco">
                <option value="padre">Padre</option>
                <option value="madre">Madre</option>
                <option value="tutor">Tutor</option>
            </select>
        </label>

        <label class="check">
            <input type="checkbox" name="consentimiento" value="1" required>
            <span>El tutor autoriza que se registre su imagen facial para identificarlo al recoger al alumno.</span>
        </label>

        <div class="camara-caja">
            <video id="video" autoplay playsinline muted></video>
            <canvas id="canvas" hidden></canvas>
            <progress id="barra" max="25" value="0" hidden></progress>
            <p id="estado-registro" class="estado-info">Cámara apagada. Pulsa «Encender cámara» para empezar.</p>
            <button type="button" id="btn-camara">Encender cámara</button>
        </div>

        <button type="submit" id="btn-registrar" disabled>Registrar y escanear rostro</button>
    </form>

    <h2>Tutores registrados</h2>
    <table class="tabla">
        <thead>
            <tr><th>Nombre</th><th>Alumno</th><th>Aula</th><th>Parentesco</th><th>Label facial</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($tutores as $t): ?>
            <tr>
                <td><?= htmlspecialchars($t['nombre']) ?></td>
                <td><?= htmlspecialchars($t['alumno_nombre']) ?></td>
                <td><?= htmlspecialchars($t['aula_nombre'] ?? 'Sin asignar', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($t['parentesco']) ?></td>
                <td><?= (int) $t['embedding_label'] ?></td>
                <td><a href="index.php?controller=tutor&action=eliminar&id=<?= (int) $t['id'] ?>"
                       onclick="return confirm('¿Eliminar este tutor?')">Eliminar</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</main>

<script src="../public/registro_camara.js"></script>
</body>
</html>
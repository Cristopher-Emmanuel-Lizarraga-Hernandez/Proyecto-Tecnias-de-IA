<!DOCTYPE html>
<html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Profesores</title><link rel="stylesheet" href="../public/estilos.css"></head>
<body>
<?php require __DIR__ . '/../../partials/nav.php'; ?>
<main class="contenedor">
<h1>Profesores</h1>
<?php if ($error): ?><p class="alerta alerta-error"><?= h($error) ?></p><?php endif; ?>
<?php if ($mensaje): ?><p class="alerta alerta-ok"><?= h($mensaje) ?></p><?php endif; ?>
<h2>Registrar profesor</h2>
<form class="formulario" method="post" action="index.php?controller=profesor&action=registrar">
    <?= csrfCampo() ?>
    <label>Nombre<input name="nombre" maxlength="150" required></label>
    <label>Correo<input type="email" name="correo" maxlength="150" required></label>
    <button type="submit">Registrar profesor</button>
</form>
<h2>Profesores registrados</h2>
<table class="tabla"><thead><tr><th>Nombre</th><th>Correo</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
<?php foreach ($profesores as $p): ?>
<tr><td><?= h($p['nombre']) ?></td><td><?= h($p['correo']) ?></td><td><?= $p['activo'] ? 'Activo' : 'Inactivo' ?></td><td>
<a href="index.php?controller=profesor&action=editar&id=<?= (int)$p['id'] ?>">Editar</a>
<form method="post" action="index.php?controller=profesor&action=cambiarEstado" style="display:inline">
<?= csrfCampo() ?><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><input type="hidden" name="activo" value="<?= $p['activo'] ? 0 : 1 ?>"><button type="submit"><?= $p['activo'] ? 'Desactivar' : 'Activar' ?></button>
</form></td></tr>
<?php endforeach; ?>
<?php if (!$profesores): ?><tr><td colspan="4">No hay profesores registrados.</td></tr><?php endif; ?>
</tbody></table></main></body></html>

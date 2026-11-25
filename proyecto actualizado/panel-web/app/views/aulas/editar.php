<!DOCTYPE html>
<html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Aulas</title><link rel="stylesheet" href="../public/estilos.css"></head>
<body><?php require __DIR__ . '/../../partials/nav.php'; ?>
<main class="contenedor">
<h1>Editar aula</h1>
<?php if (!$a['profesor_activo']): ?><p class="alerta alerta-error">El profesor actual está inactivo. Selecciona otro profesor activo.</p><?php endif; ?>
<?php if (!$profesores): ?><p>No hay profesores activos disponibles.</p><?php endif; ?>
<form class="formulario" method="post" action="index.php?controller=aula&action=actualizar">
<?= csrfCampo() ?><input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
<label>Nombre del aula<input name="nombre" maxlength="50" value="<?= h($a['nombre']) ?>" required></label>
<label>Profesor responsable<select name="profesor_id" required><option value="">Selecciona un profesor</option>
<?php foreach ($profesores as $p): ?>
<option value="<?= (int)$p['id'] ?>" <?= isset($a) && (int)$a['profesor_id'] === (int)$p['id'] ? 'selected' : '' ?>><?= h($p['nombre']) ?></option>
<?php endforeach; ?></select></label>
<button type="submit" <?= !$profesores ? 'disabled' : '' ?>>Guardar cambios</button><a href="index.php?controller=aula&action=index">Cancelar</a>
</form></main></body></html>

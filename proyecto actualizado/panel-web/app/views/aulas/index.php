<!DOCTYPE html>
<html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Aulas</title><link rel="stylesheet" href="../public/estilos.css"></head>
<body><?php require __DIR__ . '/../../partials/nav.php'; ?>
<main class="contenedor">
<h1>Aulas</h1>
<?php if ($error): ?><p class="alerta alerta-error"><?= h($error) ?></p><?php endif; ?>
<?php if ($mensaje): ?><p class="alerta alerta-ok"><?= h($mensaje) ?></p><?php endif; ?>
<h2>Registrar aula</h2>
<?php if (!$profesores): ?><p>Primero registra o activa un profesor.</p><?php endif; ?>
<form class="formulario" method="post" action="index.php?controller=aula&action=registrar">
<?= csrfCampo() ?><label>Nombre del aula<input name="nombre" maxlength="50" placeholder="Ej. 3A" required></label>
<label>Profesor responsable<select name="profesor_id" required><option value="">Selecciona un profesor</option>
<?php foreach ($profesores as $p): ?>
<option value="<?= (int)$p['id'] ?>" <?= isset($a) && (int)$a['profesor_id'] === (int)$p['id'] ? 'selected' : '' ?>><?= h($p['nombre']) ?></option>
<?php endforeach; ?></select></label>
<button type="submit" <?= !$profesores ? 'disabled' : '' ?>>Registrar aula</button></form>
<h2>Aulas registradas</h2>
<table class="tabla"><thead><tr><th>Aula</th><th>Profesor</th><th>Alumnos asignados</th><th>Acciones</th></tr></thead><tbody>
<?php foreach ($aulas as $a): ?>
<tr><td><?= h($a['nombre']) ?></td><td><?= h($a['profesor']) ?><?= !$a['profesor_activo'] ? ' (Inactivo: requiere reasignación)' : '' ?></td><td><?= (int)$a['total_alumnos'] ?></td><td><a href="index.php?controller=aula&action=editar&id=<?= (int)$a['id'] ?>">Editar</a></td></tr>
<?php endforeach; ?>
<?php if (!$aulas): ?><tr><td colspan="4">No hay aulas registradas.</td></tr><?php endif; ?>
</tbody></table></main></body></html>

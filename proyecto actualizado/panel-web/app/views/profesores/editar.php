<!DOCTYPE html>
<html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Editar profesor</title><link rel="stylesheet" href="../public/estilos.css"></head>
<body><?php require __DIR__ . '/../../partials/nav.php'; ?>
<main class="contenedor"><h1>Editar profesor</h1>
<form class="formulario" method="post" action="index.php?controller=profesor&action=actualizar">
<?= csrfCampo() ?><input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
<label>Nombre<input name="nombre" maxlength="150" value="<?= h($p['nombre']) ?>" required></label>
<label>Correo<input type="email" name="correo" maxlength="150" value="<?= h($p['correo']) ?>" required></label>
<button type="submit">Guardar cambios</button><a href="index.php?controller=profesor&action=index">Cancelar</a>
</form></main></body></html>

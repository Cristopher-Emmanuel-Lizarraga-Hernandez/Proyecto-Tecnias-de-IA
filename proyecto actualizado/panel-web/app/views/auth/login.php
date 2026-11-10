<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pase Inteligente Escolar — Iniciar sesión</title>
    <link rel="stylesheet" href="../public/estilos.css">
    <style>
        .login-caja { max-width: 380px; margin: 10vh auto 0; }
    </style>
</head>
<body>
<main class="contenedor login-caja">
    <h1>Pase Inteligente Escolar</h1>
    <p>Inicia sesión para continuar.</p>

    <?php if (!empty($error)): ?>
        <p class="alerta alerta-error"><?= h($error) ?></p>
    <?php endif; ?>
    <?php if (!empty($aviso)): ?>
        <p class="alerta alerta-ok"><?= h($aviso) ?></p>
    <?php endif; ?>

    <form method="post" action="index.php?controller=auth&action=autenticar" class="formulario">
        <?= csrfCampo() ?>
        <label>Correo
            <input type="email" name="correo" required autofocus autocomplete="username">
        </label>
        <label>Contraseña
            <input type="password" name="password" required autocomplete="current-password">
        </label>
        <button type="submit">Entrar</button>
    </form>
</main>
</body>
</html>
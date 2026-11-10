<?php $usuarioNav = usuarioActual(); ?>
<nav class="nav">
    <strong>Pase Inteligente Escolar</strong>
    <a href="index.php?controller=dashboard&action=index">Dashboard</a>
    <a href="index.php?controller=tutor&action=index">Tutores</a>
    <a href="index.php?controller=bitacora&action=index">Bitácora</a>

    <?php if ($usuarioNav): ?>
        <form method="post" action="index.php?controller=auth&action=logout" style="display:inline; margin-left:auto;">
            <?= csrfCampo() ?>
            <span><?= h($usuarioNav['nombre']) ?> (<?= h($usuarioNav['rol']) ?>)</span>
            <button type="submit">Salir</button>
        </form>
    <?php endif; ?>
</nav>
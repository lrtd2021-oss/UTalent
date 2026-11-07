<?php
// Si se está editando, el formulario se completa con esa oferta; si no, queda vacío (alta).
$campo = fn($nombre) => e($ofertaEditada[$nombre] ?? '');
?>
<div class="admin">
    <section class="tarjeta">
        <h1>Actualizar desde los portales</h1>
        <p>Trae las ofertas nuevas de cada fuente y borra las que ya cerraron. Puede demorar 2 o 3 minutos.</p>
        <form method="post" action="index.php?accion=actualizar">
            <input type="hidden" name="token" value="<?= $_SESSION['token'] ?>">
            <button type="submit">Actualizar ofertas</button>
        </form>
    </section>

    <form class="tarjeta formulario" method="post" action="index.php?accion=guardar">
        <h1><?= $ofertaEditada ? 'Editar oferta' : 'Nueva oferta' ?></h1>
        <input type="hidden" name="token" value="<?= $_SESSION['token'] ?>">
        <input type="hidden" name="id" value="<?= $campo('id') ?>">

        <input name="titulo" placeholder="Título del puesto *" value="<?= $campo('titulo') ?>" required maxlength="255">
        <input name="empresa" placeholder="Empresa u organismo *" value="<?= $campo('empresa') ?>" required maxlength="255">
        <select name="departamento">
            <?php foreach ([...DEPARTAMENTOS, 'Varios departamentos', 'Sin especificar'] as $departamento): ?>
                <option <?= ($ofertaEditada['departamento'] ?? 'Rocha') === $departamento ? 'selected' : '' ?>><?= $departamento ?></option>
            <?php endforeach; ?>
        </select>
        <select name="tipo">
            <option <?= ($ofertaEditada['tipo'] ?? '') === 'Privado' ? 'selected' : '' ?>>Privado</option>
            <option <?= ($ofertaEditada['tipo'] ?? '') === 'Público' ? 'selected' : '' ?>>Público</option>
        </select>
        <input name="salario" placeholder="Salario (vacío si no se publica)" value="<?= $campo('salario') ?>" maxlength="60">
        <input name="enlace" type="url" placeholder="Enlace a la oferta (https://...)" value="<?= $campo('enlace') ?>">
        <input name="fecha" type="date" value="<?= $campo('fecha') ?: date('Y-m-d') ?>">
        <label><input type="checkbox" name="postulacion_online" <?= !empty($ofertaEditada['postulacion_online']) ? 'checked' : '' ?>> Postulación online</label>
        <label><input type="checkbox" name="primer_empleo" <?= !empty($ofertaEditada['primer_empleo']) ? 'checked' : '' ?>> Primer empleo / pasantía</label>

        <button type="submit">Guardar</button>
        <?php if ($ofertaEditada): ?><a href="index.php?accion=admin">Cancelar</a><?php endif; ?>
    </form>
</div>

<form class="filtros" method="get">
    <input type="hidden" name="accion" value="admin">
    <input type="search" name="texto" placeholder="Filtrar por puesto o empresa" value="<?= e($_GET['texto'] ?? '') ?>">
    <button type="submit">Filtrar</button>
</form>

<div class="tabla">
    <table>
        <tr><th>Título</th><th>Empresa</th><th>Departamento</th><th>Fuente</th><th></th></tr>
        <?php foreach ($ofertas as $oferta): ?>
            <tr>
                <td><?= e($oferta['titulo']) ?></td>
                <td><?= e($oferta['empresa']) ?></td>
                <td><?= e($oferta['departamento']) ?></td>
                <td><?= e($oferta['fuente'] ?? 'Manual') ?></td>
                <td class="acciones">
                    <a href="index.php?accion=admin&id=<?= $oferta['id'] ?>">Editar</a>
                    <form method="post" action="index.php?accion=eliminar" onsubmit="return confirm('¿Eliminar esta oferta?')">
                        <input type="hidden" name="token" value="<?= $_SESSION['token'] ?>">
                        <input type="hidden" name="id" value="<?= $oferta['id'] ?>">
                        <button class="peligro">Eliminar</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>

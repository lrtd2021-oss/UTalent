<section class="portada">
    <h1>Encontrá tu primer trabajo</h1>
    <p>Ofertas de Uruguay Concursa, UTU y BuscoJobs en un solo lugar.</p>
</section>

<form class="filtros" method="get">
    <input type="search" name="texto" placeholder="Puesto o empresa (ej: programador)" value="<?= e($_GET['texto'] ?? '') ?>">

    <select name="departamento">
        <option value="">Todo el país</option>
        <?php foreach ([...DEPARTAMENTOS, 'Varios departamentos'] as $departamento): ?>
            <option <?= ($_GET['departamento'] ?? '') === $departamento ? 'selected' : '' ?>><?= $departamento ?></option>
        <?php endforeach; ?>
    </select>

    <select name="tipo">
        <option value="">Público y privado</option>
        <option <?= ($_GET['tipo'] ?? '') === 'Público' ? 'selected' : '' ?>>Público</option>
        <option <?= ($_GET['tipo'] ?? '') === 'Privado' ? 'selected' : '' ?>>Privado</option>
    </select>

    <label><input type="checkbox" name="con_salario" <?= isset($_GET['con_salario']) ? 'checked' : '' ?>> Con salario a la vista</label>
    <label><input type="checkbox" name="online" <?= isset($_GET['online']) ? 'checked' : '' ?>> Postulación online</label>
    <label><input type="checkbox" name="primer_empleo" <?= isset($_GET['primer_empleo']) ? 'checked' : '' ?>> Primer empleo / pasantía</label>

    <button type="submit">Buscar</button>
</form>

<p class="cantidad">
    <?= count($ofertas) ?> ofertas encontradas
    <?= count($ofertas) === 200 ? '(se muestran las 200 más recientes, usá los filtros para afinar)' : '' ?>
</p>

<div class="ofertas">
    <?php foreach ($ofertas as $oferta): ?>
        <article class="oferta">
            <span class="etiqueta <?= $oferta['tipo'] === 'Público' ? 'publico' : 'privado' ?>"><?= e($oferta['tipo']) ?></span>
            <h2 title="<?= e($oferta['titulo']) ?>"><?= e($oferta['titulo']) ?></h2>
            <p class="empresa"><?= e($oferta['empresa']) ?></p>
            <ul class="datos">
                <li>📍 <?= e($oferta['departamento']) ?></li>
                <li>💲 <?= e($oferta['salario'] ?? 'Salario no publicado') ?></li>
                <?php if ($oferta['postulacion_online']): ?><li>🖥️ Postulación online</li><?php endif; ?>
                <?php if ($oferta['primer_empleo']): ?><li>🎓 Primer empleo</li><?php endif; ?>
            </ul>
            <footer>
                <small><?= e($oferta['fuente'] ?? 'UTalent') ?> · <?= date('d/m/Y', strtotime($oferta['fecha'])) ?></small>
                <?php if ($oferta['enlace']): ?>
                    <a href="<?= e($oferta['enlace']) ?>" target="_blank" rel="noopener">Ver oferta →</a>
                <?php endif; ?>
            </footer>
        </article>
    <?php endforeach; ?>
</div>

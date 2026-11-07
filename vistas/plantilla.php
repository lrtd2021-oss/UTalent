<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>UTalent · Ofertas de empleo para estudiantes UTU</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
    <header class="encabezado">
        <a href="index.php" class="logo">UT<span>alent</span></a>
        <nav>
            <a href="index.php">Buscar</a>
            <a href="index.php?accion=admin">Administrar</a>
            <?php if (!empty($_SESSION['admin'])): ?>
                <a href="index.php?accion=salir">Salir</a>
            <?php endif; ?>
        </nav>
    </header>

    <main class="contenedor">
        <?php if ($mensaje): ?>
            <p class="mensaje"><?= e($mensaje) ?></p>
        <?php endif; ?>

        <?php require "vistas/$vista.php"; ?>
    </main>

    <footer class="pie">UTalent · Proyecto de Programación Full-Stack · Esc. Técnica Rocha · 2026</footer>
</body>
</html>

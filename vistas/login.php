<form class="tarjeta login" method="post" action="index.php?accion=entrar">
    <h1>Panel de administración</h1>
    <input type="hidden" name="token" value="<?= $_SESSION['token'] ?>">
    <input type="password" name="clave" placeholder="Contraseña" required autofocus>
    <button type="submit">Entrar</button>
</form>

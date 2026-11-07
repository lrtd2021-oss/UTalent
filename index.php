<?php
// Punto de entrada: todas las páginas pasan por acá (index.php?accion=...).
// httponly: JavaScript no puede leer la cookie de sesión; samesite: otras webs no la envían en sus formularios.
session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Lax']);
$_SESSION['token'] ??= bin2hex(random_bytes(16)); // token anti-CSRF para los formularios

require 'config.php';
require 'modelos/Oferta.php';
require 'controladores/OfertaControlador.php';
require 'Servicios/Fuente.php';
foreach (glob('Servicios/*.php') as $archivo) {
    require_once $archivo; // carga todas las fuentes que existan en la carpeta
}

$controlador = new OfertaControlador(new Oferta($pdo));

match ($_GET['accion'] ?? 'buscar') {
    'admin'      => $controlador->admin(),
    'entrar'     => $controlador->entrar(),
    'salir'      => $controlador->salir(),
    'guardar'    => $controlador->guardar(),
    'eliminar'   => $controlador->eliminar(),
    'actualizar' => $controlador->actualizar(),
    default      => $controlador->buscar(),
};

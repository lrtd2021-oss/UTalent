<?php
date_default_timezone_set('America/Montevideo');

// Datos de conexión de XAMPP (por defecto: usuario root sin contraseña).
$pdo = new PDO('mysql:host=localhost;dbname=utalent;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

// Contraseña del panel de administración, guardada como hash (nunca en texto plano).
// Para cambiarla: php -r "echo password_hash('nueva', PASSWORD_DEFAULT);" y pegar el resultado acá.
const CLAVE_ADMIN = '$2y$10$kiNj18qgLzqQ0BRnAWxxFuaIMNhbVix2PAs.p4iQO7wxbr0FSLtPO';

// Se usa en los filtros, en la validación del panel y para reconocer departamentos en los textos de cada portal.
const DEPARTAMENTOS = ['Artigas', 'Canelones', 'Cerro Largo', 'Colonia', 'Durazno', 'Flores', 'Florida',
    'Lavalleja', 'Maldonado', 'Montevideo', 'Paysandú', 'Río Negro', 'Rivera', 'Rocha', 'Salto',
    'San José', 'Soriano', 'Tacuarembó', 'Treinta y Tres'];

// Escapa texto antes de mostrarlo en HTML (evita que una oferta inyecte código en la página).
function e($texto) {
    return htmlspecialchars($texto ?? '', ENT_QUOTES, 'UTF-8');
}

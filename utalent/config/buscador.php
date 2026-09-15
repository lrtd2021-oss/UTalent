<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Terminos semilla
    |--------------------------------------------------------------------------
    |
    | Terminos que el Scheduler actualiza automaticamente cada cierto tiempo
    | (ver routes/console.php). No son lo mismo que los sinonimos de la tabla
    | "sinonimos": los sinonimos expanden una busqueda ya hecha por el
    | usuario, esta lista decide que busquedas automaticas dispara el
    | sistema en segundo plano.
    |
    */

    'terminos_semilla' => [
        'Programador',
        'Soporte Técnico',
        'Analista',
        'Redes',
        'Base de Datos',
    ],

];

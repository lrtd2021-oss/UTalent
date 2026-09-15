<?php

use App\Jobs\ActualizarOfertasJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Mantiene actualizadas las ofertas de los terminos semilla (config/buscador.php)
// sin depender de que un usuario dispare la busqueda.
foreach (config('buscador.terminos_semilla') as $termino) {
    Schedule::job(new ActualizarOfertasJob($termino))->everySixHours();
}

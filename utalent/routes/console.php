<?php

use App\Jobs\ActualizarTodasLasFuentesJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Barrido completo (todos los terminos semilla de config/buscador.php) cada
// 6 horas: mantiene las ofertas al dia y cierra las que ya no aparecen.
// Un solo Job, no uno por termino, porque el cierre necesita ver el
// resultado de TODOS los terminos antes de decidir que ya no existe.
Schedule::job(new ActualizarTodasLasFuentesJob())->everySixHours();

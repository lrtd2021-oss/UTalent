<?php

namespace App\Console\Commands;

use App\Jobs\ActualizarTodasLasFuentesJob;
use Illuminate\Console\Command;

/**
 * Dispara a mano el mismo barrido completo que corre el Scheduler cada 6
 * horas, sin esperar. Utilidad de prueba/operacion: el barrido completo es
 * el unico que puede cerrar ofertas (ver BuscadorService::actualizarFuentes).
 */
class ActualizarTodasLasFuentes extends Command
{
    protected $signature = 'buscador:actualizar-todo';

    protected $description = 'Despacha el barrido completo de todos los terminos semilla (el mismo que corre el Scheduler)';

    public function handle(): int
    {
        ActualizarTodasLasFuentesJob::dispatch();

        $this->info('Job de barrido completo despachado. Procesalo con: php artisan queue:work --once');

        return self::SUCCESS;
    }
}

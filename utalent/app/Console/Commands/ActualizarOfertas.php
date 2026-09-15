<?php

namespace App\Console\Commands;

use App\Jobs\ActualizarOfertasJob;
use Illuminate\Console\Command;

/**
 * Herramienta manual para despachar una actualizacion de ofertas sin esperar
 * al Scheduler. El procesamiento real ocurre en la cola: hace falta un
 * worker corriendo (php artisan queue:work) para que se ejecute.
 */
class ActualizarOfertas extends Command
{
    protected $signature = 'buscador:actualizar {termino}';

    protected $description = 'Despacha un Job para actualizar las ofertas de un termino de busqueda';

    public function handle(): int
    {
        $termino = $this->argument('termino');

        ActualizarOfertasJob::dispatch($termino);

        $this->info("Job despachado para '{$termino}'. Procesalo con: php artisan queue:work --once");

        return self::SUCCESS;
    }
}

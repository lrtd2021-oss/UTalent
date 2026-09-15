<?php

namespace App\Console\Commands;

use App\Services\BuscadorService;
use Illuminate\Console\Command;

/**
 * Dispara manualmente una actualizacion de ofertas para un termino de
 * busqueda. Sirve para probar la vertical completa (fuente -> normalizacion
 * -> persistencia) sin depender todavia de Queue/Scheduler.
 */
class ActualizarOfertas extends Command
{
    protected $signature = 'buscador:actualizar {termino}';

    protected $description = 'Consulta las fuentes de empleo activas por un termino y guarda los resultados';

    public function handle(BuscadorService $buscadorService): int
    {
        $termino = $this->argument('termino');

        $this->info("Buscando '{$termino}' en las fuentes activas...");

        $cantidad = $buscadorService->actualizarDesdeTermino($termino);

        $this->info("Listo: {$cantidad} ofertas guardadas o actualizadas.");

        return self::SUCCESS;
    }
}

<?php

namespace App\Jobs;

use App\Services\BuscadorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Barrido completo: recorre todos los terminos semilla para cada fuente
 * activa y, al terminar, cierra las ofertas que ya no aparecieron en
 * ninguno (ver BuscadorService::actualizarFuentes). Es lo que dispara el
 * Scheduler cada 6 horas; a diferencia de ActualizarOfertasJob (un solo
 * termino, sin cierre) este es el unico que puede marcar ofertas como
 * cerradas, porque es el unico que ve el conjunto completo de la fuente.
 */
class ActualizarTodasLasFuentesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int, int> Segundos de espera antes de cada reintento. */
    public array $backoff = [30, 120, 300];

    /**
     * 5 terminos semilla x (hasta ~30 ofertas x 15s de NormalizadorIA si
     * todas fueran nuevas, mas ~30s de margen por la llamada a la fuente)
     * cubre con margen el caso realista de una primera corrida con muchas
     * ofertas sin normalizar todavia. Ver retry_after en config/queue.php
     * (DB_QUEUE_RETRY_AFTER): debe quedar por encima de este valor.
     */
    public int $timeout = 1800;

    public function handle(BuscadorService $buscadorService): void
    {
        $buscadorService->actualizarFuentes();
    }
}

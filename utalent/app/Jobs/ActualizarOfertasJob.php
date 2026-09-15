<?php

namespace App\Jobs;

use App\Services\BuscadorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Ejecuta en segundo plano la actualizacion de ofertas para un termino. No
 * repite ninguna logica: delega todo en BuscadorService, que ya sabe
 * recorrer las fuentes activas y tolerar que alguna falle.
 */
class ActualizarOfertasJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int, int> Segundos de espera antes de cada reintento. */
    public array $backoff = [30, 120, 300];

    /**
     * Un solo termino: hasta ~30 ofertas (CntPorPagina de UruguayConcursaFuente)
     * x 15s de timeout de NormalizadorIA si todas fueran nuevas, mas ~30s de
     * margen para la llamada a la fuente. En la practica una API que responde
     * tarda segundos, no el timeout completo; este valor cubre con margen el
     * caso realista y, si igual se excede, el reintento del Job no duplica
     * nada porque guardarDesdeFuente es idempotente.
     */
    public int $timeout = 600;

    public function __construct(public readonly string $termino) {}

    public function handle(BuscadorService $buscadorService): void
    {
        $buscadorService->actualizarDesdeTermino($this->termino);
    }
}

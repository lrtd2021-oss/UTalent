<?php

namespace App\Services;

use App\Fuentes\FuenteEmpleoInterface;
use App\Repositories\FuenteRepositoryInterface;
use App\Repositories\OfertaRepositoryInterface;
use App\Repositories\SinonimoRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Orquesta el motor de busqueda: consulta cada fuente disponible, delega la
 * persistencia en los repositorios y arma los resultados finales. No conoce
 * el formato propio de ningun sitio externo (eso vive en cada
 * FuenteEmpleoInterface) ni como se guardan los datos (eso vive en los
 * repositorios).
 */
class BuscadorService
{
    /** @param FuenteEmpleoInterface[] $fuentesEmpleo */
    public function __construct(
        private readonly array $fuentesEmpleo,
        private readonly FuenteRepositoryInterface $fuenteRepository,
        private readonly OfertaRepositoryInterface $ofertaRepository,
        private readonly SinonimoRepositoryInterface $sinonimoRepository,
    ) {}

    /**
     * Consulta todas las fuentes por $termino y persiste lo encontrado.
     * Si una fuente falla, se registra el error y se sigue con las demas:
     * una fuente caida no debe tumbar la busqueda completa.
     *
     * @return int cantidad de ofertas guardadas (nuevas o actualizadas)
     */
    public function actualizarDesdeTermino(string $termino): int
    {
        $ofertasGuardadas = 0;

        foreach ($this->fuentesEmpleo as $fuenteEmpleo) {
            $fuente = $this->fuenteRepository->obtenerOCrear(
                $fuenteEmpleo->nombre(),
                $fuenteEmpleo->nombreVisible(),
                $fuenteEmpleo->tipo(),
            );

            try {
                foreach ($fuenteEmpleo->buscar($termino) as $ofertaDTO) {
                    $this->ofertaRepository->guardarDesdeFuente($fuente, $ofertaDTO);
                    $ofertasGuardadas++;
                }
            } catch (Throwable $e) {
                Log::warning("La fuente [{$fuenteEmpleo->nombre()}] fallo al buscar '{$termino}'", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $ofertasGuardadas;
    }

    /** Ofertas ya guardadas localmente que coincidan con $termino o sus sinonimos. */
    public function buscar(string $termino): Collection
    {
        $terminos = $this->sinonimoRepository->terminosRelacionados($termino);

        return $this->ofertaRepository->buscarPorTitulo($terminos);
    }
}

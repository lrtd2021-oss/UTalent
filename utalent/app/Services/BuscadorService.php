<?php

namespace App\Services;

use App\Fuentes\FuenteEmpleoInterface;
use App\IA\NormalizadorIAInterface;
use App\Models\Oferta;
use App\Repositories\FuenteRepositoryInterface;
use App\Repositories\OfertaRepositoryInterface;
use App\Repositories\SinonimoRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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
        private readonly NormalizadorIAInterface $normalizadorIA,
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

            if (! $fuente->activa) {
                continue;
            }

            try {
                foreach ($fuenteEmpleo->buscar($termino) as $ofertaDTO) {
                    $oferta = $this->ofertaRepository->guardarDesdeFuente($fuente, $ofertaDTO);
                    $ofertasGuardadas++;

                    if (! $oferta->ia_normalizado) {
                        $this->normalizarSiCorresponde($oferta);
                    }
                }
            } catch (Throwable $e) {
                Log::warning("La fuente [{$fuenteEmpleo->nombre()}] fallo al buscar '{$termino}'", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $ofertasGuardadas;
    }

    /**
     * Actualizacion completa: recorre TODOS los terminos semilla
     * (config('buscador.terminos_semilla')) para cada fuente activa y, solo
     * al terminar de verlos todos, cierra las ofertas de esa fuente que no
     * aparecieron en ninguno. Es la unica operacion que cierra ofertas: una
     * busqueda de un solo termino (actualizarDesdeTermino) nunca lo hace,
     * porque no representa una vista completa de la fuente.
     *
     * Si alguno de los terminos falla para una fuente, se omite el cierre de
     * esa fuente en esta corrida (no se puede afirmar "ya no existe" con
     * datos parciales) pero las ofertas que si se pudieron traer se guardan
     * igual.
     *
     * @return int cantidad de ofertas guardadas (nuevas o actualizadas)
     */
    public function actualizarFuentes(): int
    {
        $ofertasGuardadas = 0;
        $terminosSemilla = config('buscador.terminos_semilla');

        foreach ($this->fuentesEmpleo as $fuenteEmpleo) {
            $fuente = $this->fuenteRepository->obtenerOCrear(
                $fuenteEmpleo->nombre(),
                $fuenteEmpleo->nombreVisible(),
                $fuenteEmpleo->tipo(),
            );

            if (! $fuente->activa) {
                continue;
            }

            $externalIdsVistos = [];
            $barridoCompleto = true;

            foreach ($terminosSemilla as $termino) {
                try {
                    foreach ($fuenteEmpleo->buscar($termino) as $ofertaDTO) {
                        $oferta = $this->ofertaRepository->guardarDesdeFuente($fuente, $ofertaDTO);
                        $externalIdsVistos[] = $ofertaDTO->externalId;
                        $ofertasGuardadas++;

                        if (! $oferta->ia_normalizado) {
                            $this->normalizarSiCorresponde($oferta);
                        }
                    }
                } catch (Throwable $e) {
                    $barridoCompleto = false;
                    Log::warning("La fuente [{$fuenteEmpleo->nombre()}] fallo al buscar '{$termino}'", [
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            if (! $barridoCompleto) {
                Log::info("Se omite el cierre de ofertas de [{$fuenteEmpleo->nombre()}]: el barrido de esta corrida no se completo.");

                continue;
            }

            $cerradas = $this->ofertaRepository->cerrarNoVistas($fuente, array_unique($externalIdsVistos));

            if ($cerradas > 0) {
                Log::info("Se cerraron {$cerradas} oferta(s) de [{$fuenteEmpleo->nombre()}] que ya no aparecen en la fuente.");
            }
        }

        return $ofertasGuardadas;
    }

    /**
     * Intenta enriquecer una oferta via NormalizadorIA. Aislado en su propio
     * try/catch: si la implementacion de IA llegara a lanzar algo (no
     * deberia, su contrato es devolver null ante un fallo esperable), no se
     * pierde el resto del lote de ofertas que se esta procesando. Si la
     * normalizacion no se pudo completar, la oferta queda con
     * ia_normalizado=false para reintentarse en una proxima actualizacion.
     */
    private function normalizarSiCorresponde(Oferta $oferta): void
    {
        try {
            $normalizada = $this->normalizadorIA->normalizar($oferta->titulo, $oferta->descripcion_cruda);
        } catch (Throwable $e) {
            Log::warning("NormalizadorIA fallo de forma inesperada para la oferta [{$oferta->id}]", [
                'error' => $e->getMessage(),
            ]);

            return;
        }

        if ($normalizada !== null) {
            $this->ofertaRepository->guardarNormalizacion($oferta, $normalizada->seniority, $normalizada->tecnologias);
        }
    }

    /**
     * Ofertas ya guardadas localmente que coincidan con $termino (o sus
     * sinonimos) y cumplan $filtros. No consulta ninguna fuente externa: lee
     * exclusivamente de la base de datos.
     *
     * Claves posibles de $filtros: es_publico, salario_visible, modalidad,
     * departamento.
     */
    public function buscar(?string $termino, array $filtros = []): LengthAwarePaginator
    {
        $terminos = filled($termino) ? $this->sinonimoRepository->terminosRelacionados($termino) : [];

        return $this->ofertaRepository->buscar($terminos, $filtros);
    }
}

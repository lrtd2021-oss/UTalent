<?php

namespace App\Repositories;

use App\Fuentes\OfertaDTO;
use App\Models\Fuente;
use App\Models\Oferta;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface OfertaRepositoryInterface
{
    /**
     * Crea la oferta o actualiza la existente si la fuente ya la habia
     * importado antes (misma fuente + external_id).
     */
    public function guardarDesdeFuente(Fuente $fuente, OfertaDTO $ofertaDTO): Oferta;

    /**
     * Ofertas activas que coincidan con alguno de los $terminos (titulo) y
     * cumplan los $filtros dados. $terminos vacio no filtra por titulo;
     * $filtros vacio no aplica ningun filtro adicional.
     *
     * Claves posibles de $filtros: es_publico, salario_visible, modalidad,
     * departamento.
     */
    public function buscar(array $terminos, array $filtros): LengthAwarePaginator;
}

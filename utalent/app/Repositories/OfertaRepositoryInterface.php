<?php

namespace App\Repositories;

use App\Fuentes\OfertaDTO;
use App\Models\Fuente;
use App\Models\Oferta;
use Illuminate\Support\Collection;

interface OfertaRepositoryInterface
{
    /**
     * Crea la oferta o actualiza la existente si la fuente ya la habia
     * importado antes (misma fuente + external_id).
     */
    public function guardarDesdeFuente(Fuente $fuente, OfertaDTO $ofertaDTO): Oferta;

    /** Ofertas activas cuyo titulo contenga alguno de los terminos dados. */
    public function buscarPorTitulo(array $terminos): Collection;
}

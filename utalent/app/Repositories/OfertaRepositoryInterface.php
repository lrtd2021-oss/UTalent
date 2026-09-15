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
     * Guarda el resultado de NormalizadorIA sobre una oferta ya persistida y
     * la marca como normalizada (se haya podido enriquecer o no), para que
     * no se le vuelva a pedir a la IA en una proxima actualizacion.
     *
     * @param  string[]|null  $tecnologias
     */
    public function guardarNormalizacion(Oferta $oferta, ?string $seniority, ?array $tecnologias): void;

    /**
     * Marca como 'cerrada' las ofertas activas de $fuente cuyo external_id no
     * este en $externalIdsVistos. Pensado para usarse solo despues de un
     * barrido COMPLETO de todos los terminos semilla de esa fuente (nunca
     * despues de una busqueda de un solo termino): de lo contrario se
     * cerrarian ofertas que simplemente pertenecen a otro termino.
     *
     * Por seguridad, si $externalIdsVistos viene vacio no cierra nada (evita
     * que un barrido que no vio nada por error de programacion cierre todo
     * el historial de la fuente).
     *
     * @param  string[]  $externalIdsVistos
     * @return int cantidad de ofertas cerradas
     */
    public function cerrarNoVistas(Fuente $fuente, array $externalIdsVistos): int;

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

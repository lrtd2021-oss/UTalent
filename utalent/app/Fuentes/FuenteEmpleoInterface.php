<?php

namespace App\Fuentes;

/**
 * Punto de extension del motor de busqueda (patron Strategy). Cada sitio de
 * empleo se agrega implementando esta interfaz; BuscadorService no conoce
 * ningun detalle propio de un sitio en particular.
 */
interface FuenteEmpleoInterface
{
    /** Identificador estable de la fuente, ej: "uruguay_concursa". */
    public function nombre(): string;

    /** Nombre para mostrar en pantalla, ej: "Uruguay Concursa". */
    public function nombreVisible(): string;

    /** 'publico' o 'privado'. */
    public function tipo(): string;

    /** @return OfertaDTO[] */
    public function buscar(string $termino): array;
}

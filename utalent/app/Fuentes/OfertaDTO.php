<?php

namespace App\Fuentes;

/**
 * Modelo normalizado de UTalent para una oferta laboral recien obtenida de
 * una fuente externa, antes de persistirse. Cada FuenteEmpleoInterface debe
 * traducir el formato propio de su sitio a esta misma estructura: el resto
 * del sistema (BuscadorService, repositorios) solo conoce este DTO.
 */
final readonly class OfertaDTO
{
    public function __construct(
        public string $externalId,
        public string $titulo,
        public ?string $empresa,
        public ?string $departamento,
        public bool $esPublico,
        public bool $salarioVisible,
        public ?string $salarioTexto,
        public ?string $modalidad,
        public string $url,
        public ?string $fechaPublicacion,
        public ?string $fechaCierre,
        public ?string $descripcionCruda,
    ) {}
}

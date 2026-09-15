<?php

namespace App\IA;

/**
 * Resultado normalizado que devuelve NormalizadorIAInterface. Ninguno de los
 * dos campos es obligatorio: si la IA no pudo determinar algo, queda null.
 */
final readonly class OfertaNormalizada
{
    /** @param string[]|null $tecnologias */
    public function __construct(
        public ?string $seniority,
        public ?array $tecnologias,
    ) {}
}

<?php

namespace Tests\Fakes;

use App\IA\NormalizadorIAInterface;
use App\IA\OfertaNormalizada;

/**
 * Doble de NormalizadorIA para tests que no ponen foco en la IA: se comporta
 * como si el proveedor no estuviera configurado (siempre devuelve null, sin
 * generar trafico de red).
 */
class NormalizadorIANulo implements NormalizadorIAInterface
{
    public function normalizar(string $titulo, ?string $descripcionCruda): ?OfertaNormalizada
    {
        return null;
    }
}

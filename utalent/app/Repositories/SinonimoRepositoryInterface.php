<?php

namespace App\Repositories;

use App\Models\Sinonimo;
use Illuminate\Support\Collection;

interface SinonimoRepositoryInterface
{
    /** @return string[] Terminos equivalentes a $termino, incluido el mismo. */
    public function terminosRelacionados(string $termino): array;

    public function listar(): Collection;

    public function crear(array $datos): Sinonimo;

    public function actualizar(Sinonimo $sinonimo, array $datos): Sinonimo;

    public function eliminar(Sinonimo $sinonimo): void;
}

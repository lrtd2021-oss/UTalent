<?php

namespace App\Repositories;

use App\Models\Sinonimo;
use Illuminate\Support\Collection;

class EloquentSinonimoRepository implements SinonimoRepositoryInterface
{
    public function terminosRelacionados(string $termino): array
    {
        return Sinonimo::terminosEquivalentesA($termino);
    }

    public function listar(): Collection
    {
        return Sinonimo::all();
    }

    public function crear(array $datos): Sinonimo
    {
        return Sinonimo::create($datos);
    }

    public function actualizar(Sinonimo $sinonimo, array $datos): Sinonimo
    {
        $sinonimo->update($datos);

        return $sinonimo;
    }

    public function eliminar(Sinonimo $sinonimo): void
    {
        $sinonimo->delete();
    }
}

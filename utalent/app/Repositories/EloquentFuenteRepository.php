<?php

namespace App\Repositories;

use App\Models\Fuente;
use Illuminate\Support\Collection;

class EloquentFuenteRepository implements FuenteRepositoryInterface
{
    public function listar(): Collection
    {
        return Fuente::all();
    }

    public function listarActivas(): Collection
    {
        return Fuente::where('activa', true)->get();
    }

    public function obtenerOCrear(string $nombre, string $nombreVisible, string $tipo): Fuente
    {
        return Fuente::firstOrCreate(
            ['nombre' => $nombre],
            ['nombre_visible' => $nombreVisible, 'tipo' => $tipo, 'activa' => true]
        );
    }

    public function crear(array $datos): Fuente
    {
        return Fuente::create($datos);
    }

    public function actualizar(Fuente $fuente, array $datos): Fuente
    {
        $fuente->update($datos);

        return $fuente;
    }

    public function desactivar(Fuente $fuente): Fuente
    {
        $fuente->update(['activa' => false]);

        return $fuente;
    }
}

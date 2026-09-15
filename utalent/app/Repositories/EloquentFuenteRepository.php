<?php

namespace App\Repositories;

use App\Models\Fuente;
use Illuminate\Support\Collection;

class EloquentFuenteRepository implements FuenteRepositoryInterface
{
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
}

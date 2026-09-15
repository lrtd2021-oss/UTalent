<?php

namespace App\Repositories;

use App\Models\Fuente;
use Illuminate\Support\Collection;

interface FuenteRepositoryInterface
{
    public function listarActivas(): Collection;

    public function obtenerOCrear(string $nombre, string $nombreVisible, string $tipo): Fuente;
}

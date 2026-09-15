<?php

namespace App\Repositories;

use App\Models\Fuente;
use Illuminate\Support\Collection;

interface FuenteRepositoryInterface
{
    /** Todas las fuentes, activas e inactivas (para administrarlas). */
    public function listar(): Collection;

    public function listarActivas(): Collection;

    /** Usado por el Job: crea la fuente la primera vez que una Strategy corre, o reusa la existente. */
    public function obtenerOCrear(string $nombre, string $nombreVisible, string $tipo): Fuente;

    public function crear(array $datos): Fuente;

    public function actualizar(Fuente $fuente, array $datos): Fuente;

    /** Baja logica: la fuente deja de ser consultada, pero sus ofertas historicas se conservan. */
    public function desactivar(Fuente $fuente): Fuente;
}

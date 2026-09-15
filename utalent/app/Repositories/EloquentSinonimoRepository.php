<?php

namespace App\Repositories;

use App\Models\Sinonimo;

class EloquentSinonimoRepository implements SinonimoRepositoryInterface
{
    public function terminosRelacionados(string $termino): array
    {
        return Sinonimo::terminosEquivalentesA($termino);
    }
}

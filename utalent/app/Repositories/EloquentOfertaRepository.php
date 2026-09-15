<?php

namespace App\Repositories;

use App\Fuentes\OfertaDTO;
use App\Models\Fuente;
use App\Models\Oferta;
use Illuminate\Support\Collection;

class EloquentOfertaRepository implements OfertaRepositoryInterface
{
    public function guardarDesdeFuente(Fuente $fuente, OfertaDTO $ofertaDTO): Oferta
    {
        return Oferta::updateOrCreate(
            ['fuente_id' => $fuente->id, 'external_id' => $ofertaDTO->externalId],
            [
                'titulo' => $ofertaDTO->titulo,
                'empresa' => $ofertaDTO->empresa,
                'departamento' => $ofertaDTO->departamento,
                'es_publico' => $ofertaDTO->esPublico,
                'salario_visible' => $ofertaDTO->salarioVisible,
                'salario_texto' => $ofertaDTO->salarioTexto,
                'modalidad' => $ofertaDTO->modalidad,
                'url' => $ofertaDTO->url,
                'fecha_publicacion' => $ofertaDTO->fechaPublicacion,
                'fecha_cierre' => $ofertaDTO->fechaCierre,
                'descripcion_cruda' => $ofertaDTO->descripcionCruda,
                'estado' => 'activa',
            ]
        );
    }

    public function buscarPorTitulo(array $terminos): Collection
    {
        return Oferta::with('fuente')
            ->where('estado', 'activa')
            ->where(function ($query) use ($terminos) {
                foreach ($terminos as $termino) {
                    $query->orWhere('titulo', 'like', "%{$termino}%");
                }
            })
            ->latest('fecha_publicacion')
            ->get();
    }
}

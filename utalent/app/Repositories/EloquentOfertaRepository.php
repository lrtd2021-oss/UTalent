<?php

namespace App\Repositories;

use App\Fuentes\OfertaDTO;
use App\Models\Fuente;
use App\Models\Oferta;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

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

    public function guardarNormalizacion(Oferta $oferta, ?string $seniority, ?array $tecnologias): void
    {
        $oferta->update([
            'seniority' => $seniority,
            'tecnologias' => $tecnologias,
            'ia_normalizado' => true,
        ]);
    }

    public function cerrarNoVistas(Fuente $fuente, array $externalIdsVistos): int
    {
        if (empty($externalIdsVistos)) {
            return 0;
        }

        return Oferta::where('fuente_id', $fuente->id)
            ->where('estado', 'activa')
            ->whereNotIn('external_id', $externalIdsVistos)
            ->update(['estado' => 'cerrada']);
    }

    public function buscar(array $terminos, array $filtros): LengthAwarePaginator
    {
        return Oferta::with('fuente')
            ->where('estado', 'activa')
            ->when($terminos, function ($query) use ($terminos) {
                $query->where(function ($query) use ($terminos) {
                    foreach ($terminos as $termino) {
                        $query->orWhere('titulo', 'like', "%{$termino}%");
                    }
                });
            })
            ->when(array_key_exists('es_publico', $filtros), fn ($query) => $query->where('es_publico', $filtros['es_publico']))
            ->when(array_key_exists('salario_visible', $filtros), fn ($query) => $query->where('salario_visible', $filtros['salario_visible']))
            ->when(! empty($filtros['modalidad']), fn ($query) => $query->where('modalidad', $filtros['modalidad']))
            ->when(! empty($filtros['departamento']), fn ($query) => $query->where('departamento', $filtros['departamento']))
            ->latest('fecha_publicacion')
            ->paginate();
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Fuente;
use App\Repositories\FuenteRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FuenteController extends Controller
{
    public function __construct(private readonly FuenteRepositoryInterface $fuenteRepository) {}

    public function index(): JsonResponse
    {
        return response()->json($this->fuenteRepository->listar());
    }

    public function store(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'nombre' => 'required|string|max:255|unique:fuentes,nombre',
            'nombre_visible' => 'required|string|max:255',
            'tipo' => 'required|in:publico,privado',
            'activa' => 'boolean',
        ]);

        return response()->json($this->fuenteRepository->crear($datos), 201);
    }

    /**
     * "nombre" es el identificador tecnico que usa el Job para reconocer la
     * fuente (obtenerOCrear) y no se acepta aca: solo se puede modificar lo
     * que no rompe esa integracion.
     */
    public function update(Request $request, Fuente $fuente): JsonResponse
    {
        $datos = $request->validate([
            'nombre_visible' => 'sometimes|required|string|max:255',
            'tipo' => 'sometimes|required|in:publico,privado',
            'activa' => 'sometimes|boolean',
        ]);

        return response()->json($this->fuenteRepository->actualizar($fuente, $datos));
    }

    /** Baja logica: la fuente deja de usarse pero sus ofertas historicas quedan intactas. */
    public function destroy(Fuente $fuente): JsonResponse
    {
        return response()->json($this->fuenteRepository->desactivar($fuente));
    }
}

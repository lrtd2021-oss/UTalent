<?php

namespace App\Http\Controllers;

use App\Models\Sinonimo;
use App\Repositories\SinonimoRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SinonimoController extends Controller
{
    public function __construct(private readonly SinonimoRepositoryInterface $sinonimoRepository) {}

    public function index(): JsonResponse
    {
        return response()->json($this->sinonimoRepository->listar());
    }

    public function store(Request $request): JsonResponse
    {
        // Se normaliza antes de validar "unique": el modelo tambien lo hace
        // al guardar (Fase 2), pero si no se normaliza aca "Programador" no
        // detectaria como duplicado a un "programador" ya existente y la
        // base terminaria rechazando el guardado con un error 500 en vez
        // de un 422 claro.
        $this->normalizarTermino($request);

        $datos = $request->validate([
            'termino' => 'required|string|max:255|unique:sinonimos,termino',
            'grupo' => 'required|string|max:255',
        ]);

        return response()->json($this->sinonimoRepository->crear($datos), 201);
    }

    public function update(Request $request, Sinonimo $sinonimo): JsonResponse
    {
        $this->normalizarTermino($request);

        $datos = $request->validate([
            'termino' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('sinonimos', 'termino')->ignore($sinonimo->id)],
            'grupo' => 'sometimes|required|string|max:255',
        ]);

        return response()->json($this->sinonimoRepository->actualizar($sinonimo, $datos));
    }

    public function destroy(Sinonimo $sinonimo): JsonResponse
    {
        $this->sinonimoRepository->eliminar($sinonimo);

        return response()->json(null, 204);
    }

    private function normalizarTermino(Request $request): void
    {
        if ($request->filled('termino')) {
            $request->merge(['termino' => mb_strtolower(trim($request->input('termino')))]);
        }
    }
}

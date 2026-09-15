<?php

namespace App\Http\Controllers;

use App\Models\Fuente;
use App\Repositories\FuenteRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;

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
            'activa' => ['nullable', self::reglaBooleanoFlexible()],
        ]);

        if (array_key_exists('activa', $datos)) {
            $datos['activa'] = $request->boolean('activa');
        }

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
            'activa' => ['sometimes', self::reglaBooleanoFlexible()],
        ]);

        if (array_key_exists('activa', $datos)) {
            $datos['activa'] = $request->boolean('activa');
        }

        return response()->json($this->fuenteRepository->actualizar($fuente, $datos));
    }

    /**
     * La regla nativa "boolean" de Laravel solo acepta true/false/0/1 como
     * tipos reales, no las cadenas "true"/"false" que manda un formulario
     * HTML comun. Rule::in() con los dos formatos mezclados cubre ambos
     * casos: un booleano real de un body JSON y una cadena de un POST
     * form-encoded.
     */
    private static function reglaBooleanoFlexible(): In
    {
        return Rule::in([true, false, 0, 1, '0', '1', 'true', 'false']);
    }

    /** Baja logica: la fuente deja de usarse pero sus ofertas historicas quedan intactas. */
    public function destroy(Fuente $fuente): JsonResponse
    {
        return response()->json($this->fuenteRepository->desactivar($fuente));
    }
}

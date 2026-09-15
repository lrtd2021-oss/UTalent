<?php

namespace App\Http\Controllers;

use App\Models\Oferta;
use App\Services\BuscadorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OfertaController extends Controller
{
    public function __construct(private readonly BuscadorService $buscadorService) {}

    /**
     * Busca y filtra ofertas ya guardadas localmente. No consulta ninguna
     * fuente externa ni despacha ningun Job: BuscadorService::buscar() solo
     * lee de la base de datos.
     */
    public function index(Request $request): JsonResponse
    {
        // La regla "boolean" de Laravel solo acepta true/false/0/1 como tipos
        // reales, no las cadenas "true"/"false" que llegan por query string;
        // se listan explicitamente para que "?publico=true" valide bien.
        $valoresBooleanos = 'nullable|in:true,false,0,1';

        $validado = $request->validate([
            'q' => 'nullable|string|max:255',
            'publico' => $valoresBooleanos,
            'salario_visible' => $valoresBooleanos,
            'modalidad' => 'nullable|string|max:255',
            'departamento' => 'nullable|string|max:255',
        ]);

        // Solo se agrega un filtro si el cliente realmente lo mando: asi
        // "publico=false" y "no vino publico" se distinguen correctamente.
        $filtros = [];

        if ($request->has('publico')) {
            $filtros['es_publico'] = $request->boolean('publico');
        }

        if ($request->has('salario_visible')) {
            $filtros['salario_visible'] = $request->boolean('salario_visible');
        }

        if ($request->filled('modalidad')) {
            $filtros['modalidad'] = $validado['modalidad'];
        }

        if ($request->filled('departamento')) {
            $filtros['departamento'] = $validado['departamento'];
        }

        return response()->json($this->buscadorService->buscar($validado['q'] ?? null, $filtros));
    }

    /**
     * A diferencia de index(), devuelve la oferta este activa o cerrada: el
     * detalle debe poder mostrar "ya no esta disponible" en vez de un 404
     * para algo que existio. Una oferta inexistente si sigue dando 404
     * (route model binding se encarga solo).
     */
    public function show(Oferta $oferta): JsonResponse
    {
        return response()->json($oferta->load('fuente'));
    }
}

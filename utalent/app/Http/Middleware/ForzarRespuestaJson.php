<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * routes/api.php debe responder siempre en JSON (Decision 1 de la Fase 3),
 * incluidos los errores de validacion. Sin esto, un cliente que no manda
 * "Accept: application/json" (por ejemplo un curl simple) recibe un
 * redirect HTML en vez de un 422 con el detalle del error.
 */
class ForzarRespuestaJson
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}

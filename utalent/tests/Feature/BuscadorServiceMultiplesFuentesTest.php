<?php

namespace Tests\Feature;

use App\Fuentes\BuscoJobsFuente;
use App\Fuentes\UruguayConcursaFuente;
use App\Models\Oferta;
use App\Repositories\EloquentFuenteRepository;
use App\Repositories\EloquentOfertaRepository;
use App\Repositories\EloquentSinonimoRepository;
use App\Services\BuscadorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Fakes\NormalizadorIANulo;
use Tests\TestCase;

/**
 * Prueba de arquitectura: BuscadorService recibe dos Strategy reales
 * (UruguayConcursaFuente y BuscoJobsFuente) sin ningun condicional ni
 * conocimiento especifico de ninguna de las dos - el mismo constructor que
 * ya existia desde la Fase 1, ahora con un elemento mas en el arreglo.
 */
class BuscadorServiceMultiplesFuentesTest extends TestCase
{
    use RefreshDatabase;

    public function test_uruguay_concursa_y_buscojobs_conviven_sin_duplicarse_entre_si(): void
    {
        Http::fake([
            // Uruguay Concursa
            '*uruguayconcursa.gub.uy/api-backend/llamados/find' => Http::response([
                'ListaLlamados' => [[
                    'LlaId' => 42716,
                    'CarNom' => 'Programador Backend - UC',
                    'LlaTit' => 'Programador',
                    'Inciso' => 'Ministerio de Ejemplo',
                    'UnidadEjecutora' => '',
                    'LlaLugDes' => 'Departamento de Montevideo.',
                    'LlaRet' => '',
                    'LlaFchApeIns' => '2026-09-01',
                    'LlaFchCieIns' => '2026-09-30',
                    'LlaReqExc' => '',
                ]],
            ]),
            // BuscoJobs
            '*buscojobs.com.uy/ofertas/*' => Http::response(
                '<script id="__NEXT_DATA__">{"buildId":"build-test"}</script>'
            ),
            '*buscojobs.com.uy/_next/data/*' => Http::response([
                'pageProps' => ['resultadosIniciales' => ['count' => 1, 'ofertas' => [[
                    'IdOferta' => 276036,
                    'CargoVacante' => 'Programador Backend - BuscoJobs',
                    'NombreEmpresa' => 'Empresa Privada SA',
                    'Confidencial' => 0,
                    'Departamento' => ['Nombre' => 'Montevideo'],
                    'FechaInicio' => '2026-09-09T00:00:00.000Z',
                    'Descripcion' => 'Buscamos programador.',
                    'PermiteTeletrabajo' => 0,
                    'PermiteTrabajoHibrido' => 0,
                ]]]],
            ]),
        ]);

        $buscadorService = new BuscadorService(
            fuentesEmpleo: [new UruguayConcursaFuente(), new BuscoJobsFuente()],
            fuenteRepository: new EloquentFuenteRepository(),
            ofertaRepository: new EloquentOfertaRepository(),
            sinonimoRepository: new EloquentSinonimoRepository(),
            normalizadorIA: new NormalizadorIANulo(),
        );

        $guardadas = $buscadorService->actualizarDesdeTermino('Programador');

        $this->assertSame(2, $guardadas);
        $this->assertSame(2, Oferta::count());

        $this->assertDatabaseHas('fuentes', ['nombre' => 'uruguay_concursa', 'tipo' => 'publico']);
        $this->assertDatabaseHas('fuentes', ['nombre' => 'buscojobs', 'tipo' => 'privado']);

        $ofertaUC = Oferta::where('external_id', '42716')->firstOrFail();
        $ofertaBJ = Oferta::where('external_id', '276036')->firstOrFail();

        // Cada oferta queda atada a su propia fuente_id: el unique de la
        // base es compuesto (fuente_id + external_id), asi que aunque dos
        // fuentes distintas usaran el mismo external_id no colisionarian.
        $this->assertNotSame($ofertaUC->fuente_id, $ofertaBJ->fuente_id);
        $this->assertTrue($ofertaUC->es_publico);
        $this->assertFalse($ofertaBJ->es_publico);
    }
}

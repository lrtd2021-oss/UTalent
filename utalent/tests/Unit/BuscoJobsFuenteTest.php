<?php

namespace Tests\Unit;

use App\Fuentes\BuscoJobsFuente;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class BuscoJobsFuenteTest extends TestCase
{
    private function htmlConBuildId(string $buildId): string
    {
        return '<html><body><script id="__NEXT_DATA__" type="application/json">'
            .'{"buildId":"'.$buildId.'","props":{}}</script></body></html>';
    }

    private function htmlSinBuildId(): string
    {
        return '<html><body>pagina de mantenimiento</body></html>';
    }

    private function jsonConOfertas(array $ofertas): array
    {
        return [
            'pageProps' => [
                'resultadosIniciales' => [
                    'count' => count($ofertas),
                    'ofertas' => $ofertas,
                ],
            ],
        ];
    }

    private function ofertaDeEjemplo(array $overrides = []): array
    {
        return array_merge([
            'IdOferta' => 276036,
            'CargoVacante' => 'Desarrollador/a Web E-commerce',
            'NombreEmpresa' => 'Acme SA',
            'Confidencial' => 0,
            'Departamento' => ['Nombre' => 'Montevideo'],
            'FechaInicio' => '2026-09-09T13:10:30.439Z',
            'Descripcion' => 'Buscamos un desarrollador con experiencia en e-commerce.',
            'PermiteTeletrabajo' => 0,
            'PermiteTrabajoHibrido' => 0,
        ], $overrides);
    }

    public function test_busqueda_valida_devuelve_ofertadto_por_cada_resultado(): void
    {
        Http::fake([
            '*/_next/data/*' => Http::response($this->jsonConOfertas([
                $this->ofertaDeEjemplo(['IdOferta' => 1]),
                $this->ofertaDeEjemplo(['IdOferta' => 2]),
            ])),
            '*buscojobs.com.uy/ofertas/*' => Http::response($this->htmlConBuildId('build-abc')),
        ]);

        $resultados = (new BuscoJobsFuente())->buscar('Programador');

        $this->assertCount(2, $resultados);
    }

    public function test_mapeo_completo_de_una_oferta(): void
    {
        Http::fake([
            '*/_next/data/*' => Http::response($this->jsonConOfertas([$this->ofertaDeEjemplo()])),
            '*buscojobs.com.uy/ofertas/*' => Http::response($this->htmlConBuildId('build-abc')),
        ]);

        $oferta = (new BuscoJobsFuente())->buscar('Programador')[0];

        $this->assertSame('276036', $oferta->externalId);
        $this->assertSame('Desarrollador/a Web E-commerce', $oferta->titulo);
        $this->assertSame('Acme SA', $oferta->empresa);
        $this->assertSame('Montevideo', $oferta->departamento);
        $this->assertFalse($oferta->esPublico);
        $this->assertFalse($oferta->salarioVisible);
        $this->assertNull($oferta->salarioTexto);
        $this->assertSame('https://www.buscojobs.com.uy/oferta-ID-276036', $oferta->url);
        $this->assertSame('2026-09-09', $oferta->fechaPublicacion);
        $this->assertNull($oferta->fechaCierre);
        $this->assertSame('Buscamos un desarrollador con experiencia en e-commerce.', $oferta->descripcionCruda);
    }

    public function test_empleador_confidencial_queda_sin_nombre(): void
    {
        Http::fake([
            '*/_next/data/*' => Http::response($this->jsonConOfertas([
                $this->ofertaDeEjemplo(['Confidencial' => 1, 'NombreEmpresa' => 'Deberia ignorarse']),
            ])),
            '*buscojobs.com.uy/ofertas/*' => Http::response($this->htmlConBuildId('build-abc')),
        ]);

        $oferta = (new BuscoJobsFuente())->buscar('Programador')[0];

        $this->assertNull($oferta->empresa);
    }

    public function test_modalidad_presencial_cuando_ningun_flag_esta_activo(): void
    {
        Http::fake([
            '*/_next/data/*' => Http::response($this->jsonConOfertas([
                $this->ofertaDeEjemplo(['PermiteTeletrabajo' => 0, 'PermiteTrabajoHibrido' => 0]),
            ])),
            '*buscojobs.com.uy/ofertas/*' => Http::response($this->htmlConBuildId('build-abc')),
        ]);

        $this->assertSame('Presencial', (new BuscoJobsFuente())->buscar('Programador')[0]->modalidad);
    }

    public function test_modalidad_hibrida(): void
    {
        Http::fake([
            '*/_next/data/*' => Http::response($this->jsonConOfertas([
                $this->ofertaDeEjemplo(['PermiteTeletrabajo' => 0, 'PermiteTrabajoHibrido' => 1]),
            ])),
            '*buscojobs.com.uy/ofertas/*' => Http::response($this->htmlConBuildId('build-abc')),
        ]);

        $this->assertSame('Híbrido', (new BuscoJobsFuente())->buscar('Programador')[0]->modalidad);
    }

    public function test_modalidad_remota(): void
    {
        Http::fake([
            '*/_next/data/*' => Http::response($this->jsonConOfertas([
                $this->ofertaDeEjemplo(['PermiteTeletrabajo' => 1, 'PermiteTrabajoHibrido' => 0]),
            ])),
            '*buscojobs.com.uy/ofertas/*' => Http::response($this->htmlConBuildId('build-abc')),
        ]);

        $this->assertSame('Remoto', (new BuscoJobsFuente())->buscar('Programador')[0]->modalidad);
    }

    public function test_modalidad_remota_gana_cuando_ambos_flags_estan_activos(): void
    {
        Http::fake([
            '*/_next/data/*' => Http::response($this->jsonConOfertas([
                $this->ofertaDeEjemplo(['PermiteTeletrabajo' => 1, 'PermiteTrabajoHibrido' => 1]),
            ])),
            '*buscojobs.com.uy/ofertas/*' => Http::response($this->htmlConBuildId('build-abc')),
        ]);

        $this->assertSame('Remoto', (new BuscoJobsFuente())->buscar('Programador')[0]->modalidad);
    }

    public function test_usa_un_buildid_distinto_en_cada_ejecucion(): void
    {
        Http::fake([
            '*/_next/data/*' => Http::response($this->jsonConOfertas([$this->ofertaDeEjemplo()])),
            '*buscojobs.com.uy/ofertas/*' => Http::sequence()
                ->push($this->htmlConBuildId('build-uno'))
                ->push($this->htmlConBuildId('build-dos')),
        ]);

        $fuente = new BuscoJobsFuente();
        $fuente->buscar('Programador');
        $fuente->buscar('Programador');

        Http::assertSent(fn ($request) => str_contains($request->url(), '/_next/data/build-uno/'));
        Http::assertSent(fn ($request) => str_contains($request->url(), '/_next/data/build-dos/'));
    }

    public function test_sin_buildid_valido_lanza_excepcion_y_no_hace_la_segunda_peticion(): void
    {
        Http::fake([
            '*buscojobs.com.uy/ofertas/*' => Http::response($this->htmlSinBuildId()),
            '*/_next/data/*' => Http::response($this->jsonConOfertas([$this->ofertaDeEjemplo()])),
        ]);

        try {
            (new BuscoJobsFuente())->buscar('Programador');
            $this->fail('Deberia haber lanzado una excepcion.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('buildId', $e->getMessage());
        }

        Http::assertNotSent(fn ($request) => str_contains($request->url(), '_next/data'));
    }

    public function test_respuesta_http_fallida_del_endpoint_de_datos_lanza_excepcion(): void
    {
        Http::fake([
            '*buscojobs.com.uy/ofertas/*' => Http::response($this->htmlConBuildId('build-abc')),
            '*/_next/data/*' => Http::response(status: 500),
        ]);

        $this->expectException(RuntimeException::class);

        (new BuscoJobsFuente())->buscar('Programador');
    }

    public function test_json_con_formato_inesperado_lanza_excepcion(): void
    {
        Http::fake([
            '*buscojobs.com.uy/ofertas/*' => Http::response($this->htmlConBuildId('build-abc')),
            '*/_next/data/*' => Http::response(['algo' => 'inesperado']),
        ]);

        $this->expectException(RuntimeException::class);

        (new BuscoJobsFuente())->buscar('Programador');
    }

    public function test_cero_resultados_devuelve_arreglo_vacio_sin_lanzar(): void
    {
        Http::fake([
            '*buscojobs.com.uy/ofertas/*' => Http::response($this->htmlConBuildId('build-abc')),
            '*/_next/data/*' => Http::response($this->jsonConOfertas([])),
        ]);

        $this->assertSame([], (new BuscoJobsFuente())->buscar('UnTerminoRarisimo'));
    }
}

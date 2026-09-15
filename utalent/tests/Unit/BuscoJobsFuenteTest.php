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

    /**
     * Fase 9.2: el listado de BuscoJobs trunca la descripcion a 150
     * caracteres. Estas pruebas usan un patron de URL distinto para el
     * listado ('*_next/data/*ofertas/*') y para el detalle
     * ('*oferta-ID-*') porque ambos endpoints viven bajo "_next/data" y,
     * sin distinguirlos, el fake del listado interceptaria tambien la
     * peticion de detalle.
     */
    private function descripcionTruncada(): string
    {
        // 147 caracteres + "..." = 150, el mismo patron verificado contra
        // el sitio real: exactamente 150 caracteres terminados en "...".
        return str_repeat('a', 147).'...';
    }

    public function test_descripcion_truncada_con_detalle_valido_guarda_la_descripcion_completa(): void
    {
        $descripcionCompleta = str_repeat('Descripcion completa y detallada de la oferta. ', 5);

        Http::fake([
            '*buscojobs.com.uy/ofertas/*' => Http::response($this->htmlConBuildId('build-abc')),
            '*/_next/data/*/ofertas/*' => Http::response($this->jsonConOfertas([
                $this->ofertaDeEjemplo(['Descripcion' => $this->descripcionTruncada()]),
            ])),
            '*oferta-ID-*' => Http::response([
                'pageProps' => ['oferta' => ['Descripcion' => $descripcionCompleta]],
            ]),
        ]);

        $oferta = (new BuscoJobsFuente())->buscar('Programador')[0];

        $this->assertSame($descripcionCompleta, $oferta->descripcionCruda);
    }

    public function test_descripcion_truncada_con_detalle_404_conserva_la_descripcion_truncada(): void
    {
        $descripcionTruncada = $this->descripcionTruncada();

        Http::fake([
            '*buscojobs.com.uy/ofertas/*' => Http::response($this->htmlConBuildId('build-abc')),
            '*/_next/data/*/ofertas/*' => Http::response($this->jsonConOfertas([
                $this->ofertaDeEjemplo(['Descripcion' => $descripcionTruncada]),
            ])),
            '*oferta-ID-*' => Http::response(['notFound' => true], 404),
        ]);

        $oferta = (new BuscoJobsFuente())->buscar('Programador')[0];

        $this->assertSame($descripcionTruncada, $oferta->descripcionCruda);
    }

    public function test_descripcion_truncada_con_detalle_500_conserva_la_descripcion_truncada(): void
    {
        $descripcionTruncada = $this->descripcionTruncada();

        Http::fake([
            '*buscojobs.com.uy/ofertas/*' => Http::response($this->htmlConBuildId('build-abc')),
            '*/_next/data/*/ofertas/*' => Http::response($this->jsonConOfertas([
                $this->ofertaDeEjemplo(['Descripcion' => $descripcionTruncada]),
            ])),
            '*oferta-ID-*' => Http::response(status: 500),
        ]);

        $oferta = (new BuscoJobsFuente())->buscar('Programador')[0];

        $this->assertSame($descripcionTruncada, $oferta->descripcionCruda);
    }

    public function test_detalle_con_json_invalido_conserva_la_descripcion_truncada(): void
    {
        $descripcionTruncada = $this->descripcionTruncada();

        Http::fake([
            '*buscojobs.com.uy/ofertas/*' => Http::response($this->htmlConBuildId('build-abc')),
            '*/_next/data/*/ofertas/*' => Http::response($this->jsonConOfertas([
                $this->ofertaDeEjemplo(['Descripcion' => $descripcionTruncada]),
            ])),
            '*oferta-ID-*' => Http::response('esto no es json valido'),
        ]);

        $oferta = (new BuscoJobsFuente())->buscar('Programador')[0];

        $this->assertSame($descripcionTruncada, $oferta->descripcionCruda);
    }

    public function test_descripcion_no_truncada_no_pide_el_detalle(): void
    {
        Http::fake([
            '*buscojobs.com.uy/ofertas/*' => Http::response($this->htmlConBuildId('build-abc')),
            '*/_next/data/*/ofertas/*' => Http::response($this->jsonConOfertas([
                $this->ofertaDeEjemplo(['Descripcion' => 'Una descripcion corta y completa.']),
            ])),
            '*oferta-ID-*' => Http::response([
                'pageProps' => ['oferta' => ['Descripcion' => 'No deberia llegar a usarse.']],
            ]),
        ]);

        $oferta = (new BuscoJobsFuente())->buscar('Programador')[0];

        $this->assertSame('Una descripcion corta y completa.', $oferta->descripcionCruda);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'oferta-ID-'));
    }

    public function test_la_misma_oferta_en_dos_terminos_solo_pide_el_detalle_una_vez(): void
    {
        $descripcionCompleta = 'Descripcion completa, obtenida una sola vez para toda la corrida.';
        $descripcionTruncada = $this->descripcionTruncada();

        Http::fake([
            '*buscojobs.com.uy/ofertas/*' => Http::response($this->htmlConBuildId('build-abc')),
            '*/_next/data/*/ofertas/*' => Http::response($this->jsonConOfertas([
                $this->ofertaDeEjemplo(['IdOferta' => 276036, 'Descripcion' => $descripcionTruncada]),
            ])),
            '*oferta-ID-*' => Http::response([
                'pageProps' => ['oferta' => ['Descripcion' => $descripcionCompleta]],
            ]),
        ]);

        // Misma instancia, dos terminos: simula que la oferta 276036
        // aparece bajo dos terminos semilla en el mismo barrido (la misma
        // instancia de BuscoJobsFuente que reutiliza BuscadorService::
        // actualizarFuentes() a lo largo de todos los terminos semilla).
        $fuente = new BuscoJobsFuente();
        $primeraCorrida = $fuente->buscar('Programador');
        $segundaCorrida = $fuente->buscar('Desarrollador');

        $this->assertSame($descripcionCompleta, $primeraCorrida[0]->descripcionCruda);
        $this->assertSame($descripcionCompleta, $segundaCorrida[0]->descripcionCruda);
        // 2 corridas x (buildId + listado) = 4, mas el detalle UNA sola vez
        // (no 2) porque la misma instancia cachea por IdOferta.
        Http::assertSentCount(5);
    }

    public function test_el_fallo_del_detalle_de_una_oferta_no_afecta_a_las_demas(): void
    {
        $descripcionTruncada = $this->descripcionTruncada();

        Http::fake([
            '*buscojobs.com.uy/ofertas/*' => Http::response($this->htmlConBuildId('build-abc')),
            '*/_next/data/*/ofertas/*' => Http::response($this->jsonConOfertas([
                $this->ofertaDeEjemplo(['IdOferta' => 1, 'CargoVacante' => 'Oferta con detalle caido', 'Descripcion' => $descripcionTruncada]),
                $this->ofertaDeEjemplo(['IdOferta' => 2, 'CargoVacante' => 'Oferta con detalle ok', 'Descripcion' => $descripcionTruncada]),
            ])),
            '*oferta-ID-1*' => Http::response(status: 500),
            '*oferta-ID-2*' => Http::response([
                'pageProps' => ['oferta' => ['Descripcion' => 'Descripcion completa de la segunda oferta.']],
            ]),
        ]);

        $ofertas = (new BuscoJobsFuente())->buscar('Programador');

        $this->assertCount(2, $ofertas);
        $this->assertSame($descripcionTruncada, $ofertas[0]->descripcionCruda);
        $this->assertSame('Descripcion completa de la segunda oferta.', $ofertas[1]->descripcionCruda);
    }
}

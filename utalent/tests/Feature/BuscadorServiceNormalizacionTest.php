<?php

namespace Tests\Feature;

use App\Fuentes\FuenteEmpleoInterface;
use App\Fuentes\OfertaDTO;
use App\IA\NormalizadorIAInterface;
use App\IA\OfertaNormalizada;
use App\Models\Oferta;
use App\Repositories\EloquentFuenteRepository;
use App\Repositories\EloquentOfertaRepository;
use App\Repositories\EloquentSinonimoRepository;
use App\Services\BuscadorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fakes\NormalizadorIANulo;
use Tests\TestCase;

class BuscadorServiceNormalizacionTest extends TestCase
{
    use RefreshDatabase;

    private function fuenteConUnaOferta(string $externalId = '1', string $titulo = 'Programador Junior'): FuenteEmpleoInterface
    {
        return new class($externalId, $titulo) implements FuenteEmpleoInterface
        {
            public function __construct(private string $externalId, private string $titulo) {}

            public function nombre(): string
            {
                return 'fuente_prueba';
            }

            public function nombreVisible(): string
            {
                return 'Fuente de prueba';
            }

            public function tipo(): string
            {
                return 'privado';
            }

            public function buscar(string $termino): array
            {
                return [new OfertaDTO(
                    externalId: $this->externalId,
                    titulo: $this->titulo,
                    empresa: null,
                    departamento: null,
                    esPublico: false,
                    salarioVisible: false,
                    salarioTexto: null,
                    modalidad: null,
                    url: "https://ejemplo.test/{$this->externalId}",
                    fechaPublicacion: null,
                    fechaCierre: null,
                    descripcionCruda: 'Buscamos un desarrollador con experiencia.',
                )];
            }
        };
    }

    private function buscadorServiceCon(FuenteEmpleoInterface $fuente, NormalizadorIAInterface $normalizadorIA): BuscadorService
    {
        return new BuscadorService(
            fuentesEmpleo: [$fuente],
            fuenteRepository: new EloquentFuenteRepository(),
            ofertaRepository: new EloquentOfertaRepository(),
            sinonimoRepository: new EloquentSinonimoRepository(),
            normalizadorIA: $normalizadorIA,
        );
    }

    public function test_la_oferta_se_guarda_aunque_la_ia_no_este_disponible(): void
    {
        $buscadorService = $this->buscadorServiceCon($this->fuenteConUnaOferta(), new NormalizadorIANulo());

        $buscadorService->actualizarDesdeTermino('Programador');

        $this->assertDatabaseHas('ofertas', [
            'external_id' => '1',
            'titulo' => 'Programador Junior',
            'ia_normalizado' => false,
            'seniority' => null,
        ]);
    }

    public function test_una_oferta_ya_normalizada_no_vuelve_a_llamar_a_la_ia(): void
    {
        $normalizadorContador = new class implements NormalizadorIAInterface
        {
            public int $llamadas = 0;

            public function normalizar(string $titulo, ?string $descripcionCruda): ?OfertaNormalizada
            {
                $this->llamadas++;

                return new OfertaNormalizada('junior', ['PHP']);
            }
        };

        $buscadorService = $this->buscadorServiceCon($this->fuenteConUnaOferta(), $normalizadorContador);

        // Primera actualizacion: la oferta es nueva, se normaliza.
        $buscadorService->actualizarDesdeTermino('Programador');
        $this->assertSame(1, $normalizadorContador->llamadas);
        $this->assertDatabaseHas('ofertas', ['external_id' => '1', 'ia_normalizado' => true, 'seniority' => 'junior']);

        // Segunda actualizacion (mismo external_id, misma fuente): ya esta
        // normalizada, no se le vuelve a preguntar a la IA.
        $buscadorService->actualizarDesdeTermino('Programador');
        $this->assertSame(1, $normalizadorContador->llamadas);
    }

    public function test_una_ia_que_lanza_error_no_impide_procesar_las_demas_ofertas(): void
    {
        $fuenteConDosOfertas = new class implements FuenteEmpleoInterface
        {
            public function nombre(): string
            {
                return 'fuente_prueba';
            }

            public function nombreVisible(): string
            {
                return 'Fuente de prueba';
            }

            public function tipo(): string
            {
                return 'privado';
            }

            public function buscar(string $termino): array
            {
                return [
                    new OfertaDTO('1', 'Programador Junior', null, null, false, false, null, null, 'https://ejemplo.test/1', null, null, null),
                    new OfertaDTO('2', 'Programador Senior', null, null, false, false, null, null, 'https://ejemplo.test/2', null, null, null),
                ];
            }
        };

        $normalizadorQueSiempreFalla = new class implements NormalizadorIAInterface
        {
            public function normalizar(string $titulo, ?string $descripcionCruda): ?OfertaNormalizada
            {
                throw new \RuntimeException('Fallo inesperado de la integracion de IA');
            }
        };

        $buscadorService = $this->buscadorServiceCon($fuenteConDosOfertas, $normalizadorQueSiempreFalla);

        $guardadas = $buscadorService->actualizarDesdeTermino('Programador');

        $this->assertSame(2, $guardadas);
        $this->assertDatabaseCount('ofertas', 2);
        $this->assertDatabaseHas('fuentes', ['nombre' => 'fuente_prueba']);
    }

    public function test_la_busqueda_expone_seniority_y_tecnologias_sin_romper_si_faltan(): void
    {
        Oferta::factory()->create(['titulo' => 'Con IA', 'seniority' => 'senior', 'tecnologias' => ['PHP', 'Laravel'], 'ia_normalizado' => true]);
        Oferta::factory()->create(['titulo' => 'Sin IA', 'seniority' => null, 'tecnologias' => null, 'ia_normalizado' => false]);

        $respuesta = $this->getJson('/api/ofertas')->assertOk();

        $titulos = collect($respuesta->json('data'))->keyBy('titulo');
        $this->assertSame(['PHP', 'Laravel'], $titulos['Con IA']['tecnologias']);
        $this->assertNull($titulos['Sin IA']['tecnologias']);
    }
}

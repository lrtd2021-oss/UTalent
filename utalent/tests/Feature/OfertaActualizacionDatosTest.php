<?php

namespace Tests\Feature;

use App\Fuentes\FuenteEmpleoInterface;
use App\Fuentes\OfertaDTO;
use App\Models\Oferta;
use App\Repositories\EloquentFuenteRepository;
use App\Repositories\EloquentOfertaRepository;
use App\Repositories\EloquentSinonimoRepository;
use App\Services\BuscadorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fakes\NormalizadorIANulo;
use Tests\TestCase;

/**
 * Regresion de la Prioridad 5: si la fuente cambia datos de una oferta ya
 * importada (mismo external_id), la segunda actualizacion debe pisar los
 * datos viejos en la misma fila, no crear una fila nueva.
 */
class OfertaActualizacionDatosTest extends TestCase
{
    use RefreshDatabase;

    public function test_una_oferta_existente_se_actualiza_en_vez_de_duplicarse_cuando_cambian_sus_datos(): void
    {
        $fuenteEmpleo = new class implements FuenteEmpleoInterface
        {
            public int $llamadas = 0;

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
                $this->llamadas++;

                // Misma oferta (external_id "1"), pero la segunda vez la
                // fuente reporta titulo, salario y modalidad distintos.
                return [$this->llamadas === 1
                    ? new OfertaDTO('1', 'Programador Junior', 'Empresa A', 'Montevideo', false, false, null, 'Presencial', 'https://ejemplo.test/1', null, null, null)
                    : new OfertaDTO('1', 'Programador Senior', 'Empresa A', 'Montevideo', false, true, '$50.000', 'Online', 'https://ejemplo.test/1', null, null, null),
                ];
            }
        };

        $buscadorService = new BuscadorService(
            fuentesEmpleo: [$fuenteEmpleo],
            fuenteRepository: new EloquentFuenteRepository(),
            ofertaRepository: new EloquentOfertaRepository(),
            sinonimoRepository: new EloquentSinonimoRepository(),
            normalizadorIA: new NormalizadorIANulo(),
        );

        // 1. Primera importacion.
        $buscadorService->actualizarDesdeTermino('Programador');
        $this->assertSame(1, Oferta::count());
        $this->assertDatabaseHas('ofertas', ['external_id' => '1', 'titulo' => 'Programador Junior', 'modalidad' => 'Presencial']);

        // 2. y 3. La fuente cambio los datos; se ejecuta de nuevo la actualizacion.
        $buscadorService->actualizarDesdeTermino('Programador');

        // 4. Sigue siendo una sola oferta.
        $this->assertSame(1, Oferta::count());

        // 5. Los datos quedaron actualizados en esa misma fila.
        $this->assertDatabaseHas('ofertas', [
            'external_id' => '1',
            'titulo' => 'Programador Senior',
            'salario_visible' => true,
            'salario_texto' => '$50.000',
            'modalidad' => 'Online',
        ]);
    }
}

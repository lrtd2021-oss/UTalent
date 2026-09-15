<?php

namespace Tests\Feature;

use App\Fuentes\FuenteEmpleoInterface;
use App\Fuentes\OfertaDTO;
use App\Models\Fuente;
use App\Repositories\EloquentFuenteRepository;
use App\Repositories\EloquentOfertaRepository;
use App\Repositories\EloquentSinonimoRepository;
use App\Services\BuscadorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuscadorServiceResilienciaTest extends TestCase
{
    use RefreshDatabase;

    public function test_una_fuente_caida_no_impide_guardar_las_demas(): void
    {
        $fuenteQueFalla = new class implements FuenteEmpleoInterface
        {
            public function nombre(): string
            {
                return 'fuente_caida';
            }

            public function nombreVisible(): string
            {
                return 'Fuente Caida (prueba)';
            }

            public function tipo(): string
            {
                return 'privado';
            }

            public function buscar(string $termino): array
            {
                throw new \RuntimeException('El sitio no responde');
            }
        };

        $fuenteQueFunciona = new class implements FuenteEmpleoInterface
        {
            public function nombre(): string
            {
                return 'fuente_ok';
            }

            public function nombreVisible(): string
            {
                return 'Fuente OK (prueba)';
            }

            public function tipo(): string
            {
                return 'privado';
            }

            public function buscar(string $termino): array
            {
                return [new OfertaDTO(
                    externalId: '1',
                    titulo: 'Programador Junior',
                    empresa: 'Empresa de prueba',
                    departamento: 'Montevideo',
                    esPublico: false,
                    salarioVisible: false,
                    salarioTexto: null,
                    modalidad: 'Online',
                    url: 'https://ejemplo.test/1',
                    fechaPublicacion: null,
                    fechaCierre: null,
                    descripcionCruda: null,
                )];
            }
        };

        $buscadorService = new BuscadorService(
            fuentesEmpleo: [$fuenteQueFalla, $fuenteQueFunciona],
            fuenteRepository: new EloquentFuenteRepository(),
            ofertaRepository: new EloquentOfertaRepository(),
            sinonimoRepository: new EloquentSinonimoRepository(),
        );

        $guardadas = $buscadorService->actualizarDesdeTermino('Programador');

        $this->assertSame(1, $guardadas);
        $this->assertDatabaseCount('ofertas', 1);
        $this->assertDatabaseHas('fuentes', ['nombre' => 'fuente_caida']);
        $this->assertDatabaseHas('fuentes', ['nombre' => 'fuente_ok']);
    }
}

<?php

namespace Tests\Feature;

use App\Fuentes\FuenteEmpleoInterface;
use App\Fuentes\OfertaDTO;
use App\Models\Fuente;
use App\Models\Oferta;
use App\Repositories\EloquentFuenteRepository;
use App\Repositories\EloquentOfertaRepository;
use App\Repositories\EloquentSinonimoRepository;
use App\Services\BuscadorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Prueba el flujo completo pedido para la Fase 3: desactivar una fuente por
 * API debe sacarla de circulacion en el motor real (no solo cambiar un
 * campo en la base) y no debe afectar las ofertas ya guardadas.
 */
class FuenteDesactivacionFlujoTest extends TestCase
{
    use RefreshDatabase;

    public function test_desactivar_una_fuente_por_api_hace_que_el_motor_deje_de_consultarla_sin_perder_el_historico(): void
    {
        // 1. Crear la fuente y una oferta historica ya asociada a ella.
        $fuente = Fuente::create([
            'nombre' => 'fuente_prueba',
            'nombre_visible' => 'Fuente de prueba',
            'tipo' => 'privado',
            'activa' => true,
        ]);

        $ofertaHistorica = Oferta::create([
            'fuente_id' => $fuente->id,
            'external_id' => '1',
            'titulo' => 'Programador Junior',
            'es_publico' => false,
            'salario_visible' => false,
            'url' => 'https://ejemplo.test/1',
            'estado' => 'activa',
        ]);

        // 2. Confirmar que esta activa.
        $this->assertTrue($fuente->fresh()->activa);

        // 3. Desactivarla mediante el endpoint API.
        $this->deleteJson("/api/fuentes/{$fuente->id}")
            ->assertOk()
            ->assertJsonPath('activa', false);

        $this->assertFalse($fuente->fresh()->activa);

        // 4. Ejecutar una actualizacion usando esa misma fuente (por "nombre")
        // como Strategy, con una implementacion que delata si fue llamada.
        $fuenteEmpleo = new class implements FuenteEmpleoInterface
        {
            public bool $fueConsultada = false;

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
                $this->fueConsultada = true;

                return [new OfertaDTO(
                    externalId: '2',
                    titulo: 'Oferta nueva que no deberia guardarse',
                    empresa: null,
                    departamento: null,
                    esPublico: false,
                    salarioVisible: false,
                    salarioTexto: null,
                    modalidad: null,
                    url: 'https://ejemplo.test/2',
                    fechaPublicacion: null,
                    fechaCierre: null,
                    descripcionCruda: null,
                )];
            }
        };

        $buscadorService = new BuscadorService(
            fuentesEmpleo: [$fuenteEmpleo],
            fuenteRepository: new EloquentFuenteRepository(),
            ofertaRepository: new EloquentOfertaRepository(),
            sinonimoRepository: new EloquentSinonimoRepository(),
        );

        $guardadas = $buscadorService->actualizarDesdeTermino('Programador');

        // 5. BuscadorService no debe haber invocado la fuente inactiva.
        $this->assertFalse($fuenteEmpleo->fueConsultada);
        $this->assertSame(0, $guardadas);

        // 6. La oferta historica sigue en la base, intacta.
        $this->assertDatabaseHas('ofertas', [
            'id' => $ofertaHistorica->id,
            'titulo' => 'Programador Junior',
        ]);
        $this->assertSame(1, Oferta::where('fuente_id', $fuente->id)->count());
    }
}

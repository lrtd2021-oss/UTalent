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
use Tests\Fakes\NormalizadorIANulo;
use Tests\TestCase;

/**
 * BuscadorService::actualizarFuentes() es la unica operacion que puede
 * cerrar ofertas, y solo debe hacerlo con el conjunto COMPLETO de lo visto
 * en todos los terminos semilla de una corrida - nunca con el resultado de
 * un termino aislado.
 */
class BuscadorServiceCierreOfertasTest extends TestCase
{
    use RefreshDatabase;

    public static function ofertaDTO(string $externalId, string $titulo): OfertaDTO
    {
        return new OfertaDTO($externalId, $titulo, null, null, false, false, null, null, "https://ejemplo.test/{$externalId}", null, null, null);
    }

    private function buscadorServiceCon(FuenteEmpleoInterface $fuente): BuscadorService
    {
        return new BuscadorService(
            fuentesEmpleo: [$fuente],
            fuenteRepository: new EloquentFuenteRepository(),
            ofertaRepository: new EloquentOfertaRepository(),
            sinonimoRepository: new EloquentSinonimoRepository(),
            normalizadorIA: new NormalizadorIANulo(),
        );
    }

    public function test_cierra_una_oferta_que_no_aparecio_en_ningun_termino_del_barrido(): void
    {
        $fuente = Fuente::create(['nombre' => 'fuente_prueba', 'nombre_visible' => 'Fuente', 'tipo' => 'privado', 'activa' => true]);

        $ofertaQueYaNoExiste = Oferta::create([
            'fuente_id' => $fuente->id, 'external_id' => '999', 'titulo' => 'Oferta vieja',
            'es_publico' => false, 'salario_visible' => false, 'url' => 'https://ejemplo.test/999', 'estado' => 'activa',
        ]);

        $fuenteEmpleo = new class implements FuenteEmpleoInterface
        {
            public function nombre(): string
            {
                return 'fuente_prueba';
            }

            public function nombreVisible(): string
            {
                return 'Fuente';
            }

            public function tipo(): string
            {
                return 'privado';
            }

            public function buscar(string $termino): array
            {
                return [BuscadorServiceCierreOfertasTest::ofertaDTO('1', 'Programador Junior')];
            }
        };

        $this->buscadorServiceCon($fuenteEmpleo)->actualizarFuentes();

        $this->assertSame('cerrada', $ofertaQueYaNoExiste->fresh()->estado);
        $this->assertSame('activa', Oferta::where('external_id', '1')->first()->estado);
    }

    public function test_no_cierra_una_oferta_que_aparecio_en_un_termino_distinto_al_primero(): void
    {
        // Simula que dentro del mismo barrido, un termino distinto al
        // primero es el que efectivamente encuentra esta oferta: el cierre
        // no debe decidir nada hasta haber recorrido TODOS los terminos.
        $fuenteEmpleo = new class implements FuenteEmpleoInterface
        {
            public int $llamadas = 0;

            public function nombre(): string
            {
                return 'fuente_prueba';
            }

            public function nombreVisible(): string
            {
                return 'Fuente';
            }

            public function tipo(): string
            {
                return 'privado';
            }

            public function buscar(string $termino): array
            {
                $this->llamadas++;

                return match ($this->llamadas) {
                    1 => [BuscadorServiceCierreOfertasTest::ofertaDTO('1', 'Programador Junior')],
                    2 => [BuscadorServiceCierreOfertasTest::ofertaDTO('2', 'Tecnico en Redes')],
                    default => [],
                };
            }
        };

        $this->buscadorServiceCon($fuenteEmpleo)->actualizarFuentes();

        $this->assertSame('activa', Oferta::where('external_id', '1')->first()->estado);
        $this->assertSame('activa', Oferta::where('external_id', '2')->first()->estado);
    }

    public function test_si_un_termino_falla_no_cierra_nada_de_esa_fuente_en_esa_corrida(): void
    {
        $fuente = Fuente::create(['nombre' => 'fuente_prueba', 'nombre_visible' => 'Fuente', 'tipo' => 'privado', 'activa' => true]);

        $ofertaQueNoDeberiaCerrarse = Oferta::create([
            'fuente_id' => $fuente->id, 'external_id' => '999', 'titulo' => 'Oferta existente',
            'es_publico' => false, 'salario_visible' => false, 'url' => 'https://ejemplo.test/999', 'estado' => 'activa',
        ]);

        $fuenteEmpleo = new class implements FuenteEmpleoInterface
        {
            public int $llamadas = 0;

            public function nombre(): string
            {
                return 'fuente_prueba';
            }

            public function nombreVisible(): string
            {
                return 'Fuente';
            }

            public function tipo(): string
            {
                return 'privado';
            }

            public function buscar(string $termino): array
            {
                $this->llamadas++;

                if ($this->llamadas === 1) {
                    return [BuscadorServiceCierreOfertasTest::ofertaDTO('1', 'Programador Junior')];
                }

                throw new \RuntimeException('La fuente no responde en este termino');
            }
        };

        $this->buscadorServiceCon($fuenteEmpleo)->actualizarFuentes();

        // La oferta nueva que si se pudo traer antes del fallo se guarda igual.
        $this->assertSame('activa', Oferta::where('external_id', '1')->first()->estado);
        // Pero como el barrido no se completo, no se cierra nada de esta fuente.
        $this->assertSame('activa', $ofertaQueNoDeberiaCerrarse->fresh()->estado);
    }

    public function test_una_fuente_inactiva_no_participa_del_barrido_ni_de_su_cierre(): void
    {
        Fuente::create(['nombre' => 'fuente_prueba', 'nombre_visible' => 'Fuente', 'tipo' => 'privado', 'activa' => false]);

        $fuenteEmpleo = new class implements FuenteEmpleoInterface
        {
            public bool $fueConsultada = false;

            public function nombre(): string
            {
                return 'fuente_prueba';
            }

            public function nombreVisible(): string
            {
                return 'Fuente';
            }

            public function tipo(): string
            {
                return 'privado';
            }

            public function buscar(string $termino): array
            {
                $this->fueConsultada = true;

                return [];
            }
        };

        $this->buscadorServiceCon($fuenteEmpleo)->actualizarFuentes();

        $this->assertFalse($fuenteEmpleo->fueConsultada);
    }
}

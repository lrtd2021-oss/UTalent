<?php

namespace Tests\Unit;

use App\Models\Oferta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * `es_antigua` es una señal puramente visual (Fase 9.1): nunca debe influir
 * en `estado`, que sigue dependiendo solo del barrido de BuscadorService.
 */
class OfertaAntiguedadTest extends TestCase
{
    use RefreshDatabase;

    public function test_una_oferta_reciente_no_es_antigua(): void
    {
        $oferta = Oferta::factory()->create(['fecha_publicacion' => now()->subDays(30)]);

        $this->assertFalse($oferta->es_antigua);
    }

    public function test_una_oferta_justo_en_el_umbral_no_es_antigua(): void
    {
        $oferta = Oferta::factory()->create(['fecha_publicacion' => now()->subDays(180)]);

        $this->assertFalse($oferta->es_antigua);
    }

    public function test_una_oferta_que_supera_el_umbral_es_antigua(): void
    {
        $oferta = Oferta::factory()->create(['fecha_publicacion' => now()->subDays(181)]);

        $this->assertTrue($oferta->es_antigua);
    }

    public function test_una_oferta_sin_fecha_de_publicacion_no_es_antigua(): void
    {
        $oferta = Oferta::factory()->create(['fecha_publicacion' => null]);

        $this->assertFalse($oferta->es_antigua);
    }

    public function test_ser_antigua_no_cambia_el_estado_de_la_oferta(): void
    {
        $oferta = Oferta::factory()->create([
            'fecha_publicacion' => now()->subYears(3),
            'estado' => 'activa',
        ]);

        $this->assertTrue($oferta->es_antigua);
        $this->assertSame('activa', $oferta->fresh()->estado);
    }
}

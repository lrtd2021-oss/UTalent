<?php

namespace Tests\Feature;

use App\Models\Oferta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfertaDetalleApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_devuelve_una_oferta_activa_con_su_fuente(): void
    {
        $oferta = Oferta::factory()->create(['estado' => 'activa', 'titulo' => 'Programador Junior']);

        $this->getJson("/api/ofertas/{$oferta->id}")
            ->assertOk()
            ->assertJsonPath('titulo', 'Programador Junior')
            ->assertJsonPath('estado', 'activa')
            ->assertJsonPath('fuente.nombre', $oferta->fuente->nombre);
    }

    public function test_devuelve_una_oferta_cerrada_en_vez_de_ocultarla(): void
    {
        $oferta = Oferta::factory()->create(['estado' => 'cerrada']);

        $this->getJson("/api/ofertas/{$oferta->id}")
            ->assertOk()
            ->assertJsonPath('estado', 'cerrada');
    }

    public function test_una_oferta_inexistente_devuelve_404_json(): void
    {
        $this->getJson('/api/ofertas/999999')
            ->assertStatus(404)
            ->assertJson(fn ($json) => $json->has('message')->etc());
    }

    public function test_no_cambia_el_comportamiento_de_index(): void
    {
        Oferta::factory()->create(['estado' => 'activa']);
        Oferta::factory()->create(['estado' => 'cerrada']);

        $this->getJson('/api/ofertas')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }
}

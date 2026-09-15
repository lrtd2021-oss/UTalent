<?php

namespace Tests\Feature;

use App\Models\Fuente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FuenteApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_puede_listar_fuentes(): void
    {
        Fuente::factory()->count(2)->create();

        $this->getJson('/api/fuentes')->assertOk()->assertJsonCount(2);
    }

    public function test_puede_crear_una_fuente(): void
    {
        $respuesta = $this->postJson('/api/fuentes', [
            'nombre' => 'portal_ejemplo',
            'nombre_visible' => 'Portal Ejemplo',
            'tipo' => 'privado',
        ]);

        $respuesta->assertCreated()->assertJsonPath('nombre', 'portal_ejemplo');
        $this->assertDatabaseHas('fuentes', ['nombre' => 'portal_ejemplo', 'activa' => true]);
    }

    public function test_no_puede_crear_una_fuente_con_nombre_repetido(): void
    {
        Fuente::factory()->create(['nombre' => 'portal_ejemplo']);

        $this->postJson('/api/fuentes', [
            'nombre' => 'portal_ejemplo',
            'nombre_visible' => 'Otro nombre visible',
            'tipo' => 'privado',
        ])->assertStatus(422);
    }

    public function test_puede_actualizar_nombre_visible_tipo_y_activa(): void
    {
        $fuente = Fuente::factory()->create(['tipo' => 'privado', 'activa' => true]);

        $this->putJson("/api/fuentes/{$fuente->id}", [
            'nombre_visible' => 'Nuevo nombre',
            'tipo' => 'publico',
            'activa' => false,
        ])->assertOk()->assertJsonPath('nombre_visible', 'Nuevo nombre');

        $this->assertDatabaseHas('fuentes', [
            'id' => $fuente->id,
            'nombre_visible' => 'Nuevo nombre',
            'tipo' => 'publico',
            'activa' => false,
        ]);
    }

    public function test_no_puede_modificar_el_nombre_tecnico(): void
    {
        $fuente = Fuente::factory()->create(['nombre' => 'nombre_original']);

        $this->putJson("/api/fuentes/{$fuente->id}", [
            'nombre' => 'nombre_hackeado',
            'nombre_visible' => 'Nombre actualizado',
        ])->assertOk();

        $this->assertDatabaseHas('fuentes', ['id' => $fuente->id, 'nombre' => 'nombre_original']);
    }

    public function test_destroy_hace_baja_logica_y_no_borra_la_fila(): void
    {
        $fuente = Fuente::factory()->create(['activa' => true]);

        $this->deleteJson("/api/fuentes/{$fuente->id}")
            ->assertOk()
            ->assertJsonPath('activa', false);

        $this->assertDatabaseHas('fuentes', ['id' => $fuente->id, 'activa' => false]);
    }
}

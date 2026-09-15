<?php

namespace Tests\Feature;

use App\Models\Sinonimo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SinonimoApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_puede_listar_sinonimos(): void
    {
        Sinonimo::create(['termino' => 'programador', 'grupo' => 'programador']);
        Sinonimo::create(['termino' => 'developer', 'grupo' => 'programador']);

        $this->getJson('/api/sinonimos')->assertOk()->assertJsonCount(2);
    }

    public function test_crear_normaliza_el_termino_a_minusculas(): void
    {
        $this->postJson('/api/sinonimos', [
            'termino' => 'PROGRAMADOR',
            'grupo' => 'programador',
        ])->assertCreated()->assertJsonPath('termino', 'programador');

        $this->assertDatabaseHas('sinonimos', ['termino' => 'programador']);
    }

    public function test_no_permite_terminos_duplicados_sin_importar_mayusculas(): void
    {
        Sinonimo::create(['termino' => 'programador', 'grupo' => 'programador']);

        $this->postJson('/api/sinonimos', [
            'termino' => 'Programador',
            'grupo' => 'programador',
        ])->assertStatus(422);

        $this->assertSame(1, Sinonimo::count());
    }

    public function test_puede_actualizar_el_grupo_de_un_sinonimo(): void
    {
        $sinonimo = Sinonimo::create(['termino' => 'redes', 'grupo' => 'redes']);

        $this->putJson("/api/sinonimos/{$sinonimo->id}", [
            'grupo' => 'redes_y_telecom',
        ])->assertOk()->assertJsonPath('grupo', 'redes_y_telecom');
    }

    public function test_puede_eliminar_un_sinonimo(): void
    {
        $sinonimo = Sinonimo::create(['termino' => 'redes', 'grupo' => 'redes']);

        $this->deleteJson("/api/sinonimos/{$sinonimo->id}")->assertNoContent();

        $this->assertDatabaseMissing('sinonimos', ['id' => $sinonimo->id]);
    }
}

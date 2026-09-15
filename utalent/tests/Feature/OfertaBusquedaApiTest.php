<?php

namespace Tests\Feature;

use App\Models\Oferta;
use App\Models\Sinonimo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OfertaBusquedaApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_busca_por_termino_en_el_titulo(): void
    {
        Oferta::factory()->create(['titulo' => 'Programador Backend']);
        Oferta::factory()->create(['titulo' => 'Contador Junior']);

        $this->getJson('/api/ofertas?q=Programador')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.titulo', 'Programador Backend');
    }

    public function test_la_busqueda_expande_sinonimos_sin_importar_mayusculas(): void
    {
        Sinonimo::create(['termino' => 'programador', 'grupo' => 'programador']);
        Sinonimo::create(['termino' => 'developer', 'grupo' => 'programador']);

        Oferta::factory()->create(['titulo' => 'Developer Full Stack']);
        Oferta::factory()->create(['titulo' => 'Contador Junior']);

        $this->getJson('/api/ofertas?q=PROGRAMADOR')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.titulo', 'Developer Full Stack');
    }

    public function test_filtra_por_sector_publico(): void
    {
        Oferta::factory()->create(['titulo' => 'Oferta publica', 'es_publico' => true]);
        Oferta::factory()->create(['titulo' => 'Oferta privada', 'es_publico' => false]);

        $this->getJson('/api/ofertas?publico=true')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.titulo', 'Oferta publica');
    }

    public function test_filtra_por_salario_visible(): void
    {
        Oferta::factory()->create(['titulo' => 'Con salario', 'salario_visible' => true]);
        Oferta::factory()->create(['titulo' => 'Sin salario', 'salario_visible' => false]);

        $this->getJson('/api/ofertas?salario_visible=true')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.titulo', 'Con salario');
    }

    public function test_filtra_por_modalidad(): void
    {
        Oferta::factory()->create(['titulo' => 'Remota', 'modalidad' => 'Online']);
        Oferta::factory()->create(['titulo' => 'Presencial', 'modalidad' => 'Presencial']);

        $this->getJson('/api/ofertas?modalidad=Online')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.titulo', 'Remota');
    }

    public function test_filtra_por_departamento(): void
    {
        Oferta::factory()->create(['titulo' => 'En Montevideo', 'departamento' => 'Montevideo']);
        Oferta::factory()->create(['titulo' => 'En Rocha', 'departamento' => 'Rocha']);

        $this->getJson('/api/ofertas?departamento=Rocha')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.titulo', 'En Rocha');
    }

    public function test_combina_varios_filtros_con_and(): void
    {
        // Coincide con ambos filtros.
        Oferta::factory()->create([
            'titulo' => 'Programador publico en Montevideo',
            'es_publico' => true,
            'departamento' => 'Montevideo',
        ]);
        // Publico pero en otro departamento: no deberia aparecer.
        Oferta::factory()->create([
            'titulo' => 'Programador publico en Rocha',
            'es_publico' => true,
            'departamento' => 'Rocha',
        ]);
        // En Montevideo pero privado: no deberia aparecer.
        Oferta::factory()->create([
            'titulo' => 'Programador privado en Montevideo',
            'es_publico' => false,
            'departamento' => 'Montevideo',
        ]);

        $this->getJson('/api/ofertas?q=Programador&publico=true&departamento=Montevideo')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.titulo', 'Programador publico en Montevideo');
    }

    public function test_sin_filtros_devuelve_todas_las_ofertas_activas(): void
    {
        Oferta::factory()->count(3)->create();

        $this->getJson('/api/ofertas')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_una_busqueda_sin_coincidencias_devuelve_200_con_datos_vacios(): void
    {
        Oferta::factory()->create(['titulo' => 'Contador Junior']);

        $this->getJson('/api/ofertas?q=Programador')
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('total', 0);
    }

    public function test_las_ofertas_cerradas_no_aparecen_en_los_resultados(): void
    {
        Oferta::factory()->create(['titulo' => 'Oferta activa', 'estado' => 'activa']);
        Oferta::factory()->create(['titulo' => 'Oferta cerrada', 'estado' => 'cerrada']);

        $this->getJson('/api/ofertas')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.titulo', 'Oferta activa');
    }

    public function test_la_busqueda_no_genera_ningun_trafico_de_red(): void
    {
        Http::fake();

        Oferta::factory()->create(['titulo' => 'Programador Backend']);

        $this->getJson('/api/ofertas?q=Programador')->assertOk();

        Http::assertNothingSent();
    }
}

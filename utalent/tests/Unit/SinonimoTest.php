<?php

namespace Tests\Unit;

use App\Models\Sinonimo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SinonimoTest extends TestCase
{
    use RefreshDatabase;

    public function test_terminos_equivalentes_ignora_mayusculas_y_minusculas(): void
    {
        Sinonimo::create(['termino' => 'Programador', 'grupo' => 'programador']);
        Sinonimo::create(['termino' => 'Developer', 'grupo' => 'programador']);

        $equivalentes = Sinonimo::terminosEquivalentesA('PROGRAMADOR');

        $this->assertContains('programador', $equivalentes);
        $this->assertContains('developer', $equivalentes);
    }
}

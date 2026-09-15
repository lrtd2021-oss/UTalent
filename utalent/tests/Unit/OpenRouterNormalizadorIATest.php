<?php

namespace Tests\Unit;

use App\IA\OpenRouterNormalizadorIA;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenRouterNormalizadorIATest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['services.openrouter.key' => 'clave-de-prueba', 'services.openrouter.modelo' => 'modelo-de-prueba']);
    }

    private function fakearRespuesta(mixed $respuesta): void
    {
        Http::fake(['openrouter.ai/*' => $respuesta]);
    }

    private function contenido(array $datos): array
    {
        return ['choices' => [['message' => ['content' => json_encode($datos)]]]];
    }

    public function test_respuesta_valida_devuelve_seniority_y_tecnologias(): void
    {
        $this->fakearRespuesta(Http::response($this->contenido([
            'seniority' => 'senior',
            'tecnologias' => ['PHP', 'Laravel'],
        ])));

        $resultado = (new OpenRouterNormalizadorIA())->normalizar('Programador Senior', 'Buscamos un desarrollador...');

        $this->assertNotNull($resultado);
        $this->assertSame('senior', $resultado->seniority);
        $this->assertSame(['PHP', 'Laravel'], $resultado->tecnologias);
    }

    public function test_filtra_tecnologias_vacias_y_duplicadas_y_respeta_el_limite(): void
    {
        $this->fakearRespuesta(Http::response($this->contenido([
            'seniority' => 'junior',
            'tecnologias' => ['PHP', '', '  ', 'PHP', ' Laravel '],
        ])));

        $resultado = (new OpenRouterNormalizadorIA())->normalizar('Programador', null);

        $this->assertSame(['PHP', 'Laravel'], $resultado->tecnologias);
    }

    public function test_seniority_fuera_del_conjunto_permitido_queda_null(): void
    {
        $this->fakearRespuesta(Http::response($this->contenido([
            'seniority' => 'super-senior-master',
            'tecnologias' => ['PHP'],
        ])));

        $resultado = (new OpenRouterNormalizadorIA())->normalizar('Programador', null);

        $this->assertNull($resultado->seniority);
        $this->assertSame(['PHP'], $resultado->tecnologias);
    }

    public function test_tecnologias_que_no_son_un_array_quedan_null(): void
    {
        $this->fakearRespuesta(Http::response($this->contenido([
            'seniority' => 'junior',
            'tecnologias' => 'PHP, Laravel',
        ])));

        $resultado = (new OpenRouterNormalizadorIA())->normalizar('Programador', null);

        $this->assertSame('junior', $resultado->seniority);
        $this->assertNull($resultado->tecnologias);
    }

    public function test_json_malformado_devuelve_null_sin_lanzar(): void
    {
        $this->fakearRespuesta(Http::response(['choices' => [['message' => ['content' => 'esto no es json']]]]));

        $resultado = (new OpenRouterNormalizadorIA())->normalizar('Programador', null);

        $this->assertNull($resultado);
    }

    public function test_error_http_del_proveedor_devuelve_null_sin_lanzar(): void
    {
        $this->fakearRespuesta(Http::response(['error' => 'server error'], 500));

        $resultado = (new OpenRouterNormalizadorIA())->normalizar('Programador', null);

        $this->assertNull($resultado);
    }

    public function test_timeout_de_conexion_devuelve_null_sin_lanzar(): void
    {
        $this->fakearRespuesta(fn () => throw new ConnectionException('Connection timed out'));

        $resultado = (new OpenRouterNormalizadorIA())->normalizar('Programador', null);

        $this->assertNull($resultado);
    }

    public function test_sin_api_key_configurada_no_genera_trafico_y_devuelve_null(): void
    {
        config(['services.openrouter.key' => null]);
        Http::fake();

        $resultado = (new OpenRouterNormalizadorIA())->normalizar('Programador', null);

        $this->assertNull($resultado);
        Http::assertNothingSent();
    }
}

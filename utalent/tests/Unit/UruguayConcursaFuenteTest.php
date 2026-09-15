<?php

namespace Tests\Unit;

use App\Fuentes\UruguayConcursaFuente;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UruguayConcursaFuenteTest extends TestCase
{
    private function llamadoDeEjemplo(array $overrides = []): array
    {
        return array_merge([
            'LlaId' => 42716,
            'CarNom' => 'Programador Backend',
            'LlaTit' => 'Programador',
            'Inciso' => 'Ministerio de Ejemplo',
            'UnidadEjecutora' => '',
            'LlaLugDes' => 'Departamento de Montevideo.',
            'LlaRet' => '',
            'LlaFchApeIns' => '2026-09-01',
            'LlaFchCieIns' => '2026-09-30',
            'LlaReqExc' => '',
        ], $overrides);
    }

    /**
     * Fase 9.1: Uruguay Concursa no publica la modalidad laboral (remoto,
     * hibrido, presencial) de sus llamados. Antes se rellenaba con un texto
     * fijo ("Postulacion online...") que rompia el filtro de modalidad; el
     * mapeo correcto es simplemente no inventar el dato.
     */
    public function test_la_modalidad_es_null_porque_la_fuente_no_publica_modalidad_laboral(): void
    {
        Http::fake([
            '*uruguayconcursa.gub.uy/api-backend/llamados/find' => Http::response([
                'ListaLlamados' => [$this->llamadoDeEjemplo()],
            ]),
        ]);

        $oferta = (new UruguayConcursaFuente())->buscar('Programador')[0];

        $this->assertNull($oferta->modalidad);
    }
}

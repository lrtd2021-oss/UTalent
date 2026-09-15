<?php

namespace Tests\Feature;

use App\Jobs\ActualizarOfertasJob;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class ActualizarOfertasCommandTest extends TestCase
{
    public function test_el_comando_despacha_el_job_en_vez_de_procesar_directamente(): void
    {
        Bus::fake();

        $this->artisan('buscador:actualizar', ['termino' => 'Programador'])
            ->assertExitCode(0);

        Bus::assertDispatched(
            ActualizarOfertasJob::class,
            fn (ActualizarOfertasJob $job) => $job->termino === 'Programador'
        );
    }
}

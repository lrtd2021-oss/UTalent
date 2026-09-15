<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class RateLimitApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // El limitador cuenta por IP en el cache; se limpia para que otros
        // tests que ya pegaron contra /api/* en este mismo proceso no dejen
        // el contador a mitad de camino.
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Cache::flush();

        parent::tearDown();
    }

    public function test_la_api_corta_despues_de_60_peticiones_por_minuto(): void
    {
        for ($i = 1; $i <= 60; $i++) {
            $this->getJson('/api/fuentes')->assertOk();
        }

        $this->getJson('/api/fuentes')
            ->assertStatus(429)
            ->assertHeader('Retry-After');
    }
}

<?php

namespace App\Providers;

use App\Fuentes\UruguayConcursaFuente;
use App\IA\NormalizadorIAInterface;
use App\IA\OpenRouterNormalizadorIA;
use App\Repositories\EloquentFuenteRepository;
use App\Repositories\EloquentOfertaRepository;
use App\Repositories\EloquentSinonimoRepository;
use App\Repositories\FuenteRepositoryInterface;
use App\Repositories\OfertaRepositoryInterface;
use App\Repositories\SinonimoRepositoryInterface;
use App\Services\BuscadorService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(FuenteRepositoryInterface::class, EloquentFuenteRepository::class);
        $this->app->bind(OfertaRepositoryInterface::class, EloquentOfertaRepository::class);
        $this->app->bind(SinonimoRepositoryInterface::class, EloquentSinonimoRepository::class);
        $this->app->bind(NormalizadorIAInterface::class, OpenRouterNormalizadorIA::class);

        // Las fuentes activas del motor de busqueda. Sumar una fuente nueva
        // es agregarla a este arreglo (y crear su propia implementacion de
        // FuenteEmpleoInterface); nada mas del sistema cambia.
        $this->app->bind(BuscadorService::class, fn ($app) => new BuscadorService(
            fuentesEmpleo: [$app->make(UruguayConcursaFuente::class)],
            fuenteRepository: $app->make(FuenteRepositoryInterface::class),
            ofertaRepository: $app->make(OfertaRepositoryInterface::class),
            sinonimoRepository: $app->make(SinonimoRepositoryInterface::class),
            normalizadorIA: $app->make(NormalizadorIAInterface::class),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // No hay autenticacion todavia, asi que se limita por IP. 60/min es
        // el default que trae el propio scaffolding de Laravel para "api".
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)->by($request->ip()));
    }
}

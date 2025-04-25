<?php
namespace Modulo\Export\Src\Laravel;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Modulo\Export\Src\Contrato\BridgeInterface;
use Modulo\Export\Src\Core\Trie as RouteTrie;

class LaravelServicesProvider extends ServiceProvider implements BridgeInterface
{
    public function register(): void
    {
        // Podrías registrar el singleton del Trie aquí si lo deseas.
        $this->app->singleton(RouteTrie::class, function () {
            return new RouteTrie('laravel');
        });
    }

    /**
     * Puedes cargar rutas personalizadas aquí o desde archivos
     * Cargar rutas desde JSON (puede ser rutas asíncronas, websockets, etc.)
     * *$trie->importFromArray(...);  // o desde JSON, DB, etc.
     */
    public function boot(): void
    {
        /** @var RouteTrie $trie */
        $trie = $this->app->make(RouteTrie::class);

        $jsonPath = base_path('routes/async_routes.json');
    
        if (file_exists($jsonPath)) {
            $json = file_get_contents($jsonPath);
            $trie->importFromJson($json);
        }

        (new LaravelRouteBridge($trie))->registerRoutes();
    }

    public function map(): void
    {
        $this->mapHttpRoutes();
    }

    protected function mapHttpRoutes(): void
    {
        $trie = new RouteTrie('http');
        $bridge = new LaravelRouteBridge($trie);
        $bridge->registerRoutes();
    }
}

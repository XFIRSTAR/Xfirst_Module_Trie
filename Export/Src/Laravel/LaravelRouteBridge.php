<?php
namespace Modulo\Export\Src\Laravel;

use Illuminate\Support\Facades\Route;

use Modulo\Trie\Export\Contrato\BridgeInterface;
use Modulo\Src\Core\Trie as RouteTrie;

class LaravelRouteBridge implements BridgeInterface
{
    protected RouteTrie $trie;

    public function __construct(string $context = 'laravel')
    {
        $this->trie = new RouteTrie($context);
    }

    public function setContext(string $context): void
    {
        $this->trie = new RouteTrie($context);
    }

    /**
     * Registra todas las rutas del trie en el router de Laravel.
     */
    public function registerRoutes(): void
    {
        // foreach ($this->trie->exportRoutes() as $route) {
        //     Route::match([$route['method']], $route['uri'], $route['action'])->middleware($route['middleware']);
        // }
        foreach ($this->trie->exportRoutes() as $route) {
            $method = strtolower($route['method']);
            $uri = $route['uri'];
            $action = $route['action'];
            $middleware = $route['middleware'] ?? [];

            Route::$method($uri, $action)->middleware($middleware);
        }
    }

    public function getTrie(): RouteTrie
    {
        return $this->trie;
    }
}

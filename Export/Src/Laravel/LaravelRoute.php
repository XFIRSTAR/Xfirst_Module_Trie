<?php
namespace Modulo\Export\Src\Laravel;

use Illuminate\Support\Facades\Route;
use Modulo\Export\Src\Core\Trie as RouteTrie;

class LaravelRoute
{
    protected RouteTrie $trie;

    public function __construct(RouteTrie $trie)
    {
        $this->trie = $trie;
    }

    public function registerRoutes(): void
    {
        foreach ($this->trie->exportRoutes() as $route) {
            $method = strtolower($route['method']);
            $uri = $route['uri'];
            $action = $route['action'];
            $middlewares = $route['middleware'] ?? [];

            Route::$method($uri, $action)->middleware($middlewares);
        }
    }
}

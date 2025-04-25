<?php
namespace Modulo\Export\Src\CakePHP;

use Cake\Routing\RouteBuilder;
use Modulo\Export\Src\Core\Trie as RouteTrie;

class TrieCakeAdapter
{
    protected RouteTrie $trie;

    public function __construct(RouteTrie $trie)
    {
        $this->trie = $trie;
    }

    public function loadInto(RouteBuilder $routes): void
    {
        foreach ($this->trie->exportRoutes() as $route) {
            $method = strtolower($route['method']);
            $uri = $route['uri'];
            $action = $route['action'];

            // El método 'connect' no distingue métodos HTTP, así que usamos addRoutes
            if (method_exists($routes, $method)) {
                $routes->{$method}($uri, $action);
            } else {
                // Fallback genérico si el método específico no está
                $routes->connect($uri, $action);
            }
        }
    }
}

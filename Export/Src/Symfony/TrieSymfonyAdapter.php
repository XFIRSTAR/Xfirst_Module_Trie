<?php
namespace Modulo\Export\Src\Symfony;

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Modulo\Export\Src\Core\Trie as RouteTrie;

class TrieSymfonyAdapter 
{
    protected RouteTrie $trie;

    public function __construct(RouteTrie $trie)
    {
        $this->trie = $trie;
    }

    public function getRouteCollection(): RouteCollection
    {
        $collection = new RouteCollection();
        foreach ($this->trie->exportRoutes() as $data) {
            $defaults = ['_controller' => $data['action']];
            $methods = [$data['method']];
            $path = $data['uri'];

            $route = new Route($path, $defaults, [], [], '', [], $methods);
            $name = md5($data['method'] . $path);
            $collection->add($name, $route);
        }
        return $collection;
    }
}

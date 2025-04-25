<?php
namespace Modulo\Export\Src\Symfony;

use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\RouteCollection;
use Modulo\Export\Src\RouteTrieSymfonyAdapter;
use Modulo\Export\Src\Core\Trie as RouteTrie;

class TrieRouteLoader extends Loader
{
    private bool $loaded = false;

    public function load($resource, string $type = null): RouteCollection
    {
        if ($this->loaded) {
            throw new \RuntimeException('Las rutas Trie ya fueron cargadas.');
        }

        $trie = new RouteTrie('symfony');
        $adapter = new RouteTrieSymfonyAdapter($trie);

        $this->loaded = true;
        return $adapter->getRouteCollection();
    }

    public function supports($resource, string $type = null): bool
    {
        return $type === 'trie';
    }
}

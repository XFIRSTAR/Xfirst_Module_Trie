<?php
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Modulo\Export\Src\CakePHP\TrieCakeAdapter as RouteTrieCakeAdapter;
use Modulo\Export\Src\Core\Trie as RouteTrie;

Router::scope('/', function (RouteBuilder $routes) {
    $trie = new RouteTrie('cakephp');
    $adapter = new RouteTrieCakeAdapter($trie);
    $adapter->loadInto($routes);

    // Puedes seguir definiendo otras rutas normalmente si deseas
});

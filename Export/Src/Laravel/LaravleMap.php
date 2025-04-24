<?php
namespace Modulo\Export\Src\Laravel;

use Modulo\Export\Src\Laravel\LaravelRouteBridge;

class LaravleMap{

    public function map()
    {
        $bridge = new LaravelRouteBridge('api');
        $bridge->registerRoutes();
    }
}


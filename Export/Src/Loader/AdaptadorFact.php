<?php
namespace Modulo\Export\Src\Loader;

use Modulo\Export\Src\Core\Trie as RouteTrie;
use Modulo\Export\Src\Laravel\LaravelServicesProvider;
use Modulo\Export\Src\Symfony\TrieSymfonyAdapter;
use Modulo\Export\Src\Contrato\BridgeInterface;
use Modulo\Export\Src\CakePHP\TrieCakeAdapter;
use RuntimeException;

class AdaptadorFact
{
    public static function make(string $framework, RouteTrie $trie): BridgeInterface
    {
        return match (strtolower($framework)) {
            'laravel' => new LaravelServicesProvider($trie),
            'symfony' => new TrieSymfonyAdapter($trie),
            'cakephp' => new TrieCakeAdapter($trie),
            default => throw new RuntimeException("Framework no soportado: $framework"),
        };
    }
}

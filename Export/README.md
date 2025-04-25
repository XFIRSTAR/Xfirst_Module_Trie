# RouteTrieAdapter

Sistema de rutas optimizado basado en Trie, adaptable a múltiples frameworks PHP como **Laravel**, **Symfony**, y **CakePHP**.

---

## 🧪Ejemplo de Uso

**Laravel:**

use RouteTrieAdapter\Bridge\Laravel\RouteTrieLaravelAdapter;
use RouteTrieAdapter\Trie\RouteTrie;

$trie = new RouteTrie('api');
$trie->insert('GET', '/usuarios', [App\Http\Controllers\UsuarioController::class, 'index']);

$adapter = new RouteTrieLaravelAdapter();
$adapter->mount($trie); // Registra las rutas en Laravel

**Symfony:**

use RouteTrieAdapter\Bridge\Symfony\RouteTrieSymfonyAdapter;
use RouteTrieAdapter\Trie\RouteTrie;

$trie = new RouteTrie('web');
$trie->insert('GET', '/productos/{id}', [ProductoController::class, 'ver']);

$adapter = new RouteTrieSymfonyAdapter();
$routes = $adapter->mount($trie); // Devuelve RouteCollection

**CakePHP:**

use RouteTrieAdapter\Bridge\CakePHP\RouteTrieCakeAdapter;
use RouteTrieAdapter\Trie\RouteTrie;

$trie = new RouteTrie('admin');
$trie->insert('POST', '/usuarios/crear', ['Usuarios', 'crear']);

$adapter = new RouteTrieCakeAdapter();
$adapter->mount($trie); // Usa Router::connect() internamente

## ⚙️ Cache Contextual Dinámico

Cada instancia de Trie crea y guarda un archivo de caché único por contexto:

/storage/cache/rutas/api.cache
/storage/cache/rutas/web.cache
/storage/cache/rutas/{framework}.cache

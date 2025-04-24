<?php
namespace Modulo\Src\Core;

use Lib\Auditoria\Registro;
use InvalidArgumentException;
use RuntimeException;

/**
 * Clase que representa un nodo en un Trie para el enrutamiento de rutas.
 *
 * Esta clase se utiliza para almacenar información sobre las rutas
 * y sus métodos asociados en una estructura de árbol (Trie).
 */
class TrieNode {
    /**
     * Array de nodos hijos.
     *
     * Cada clave es un carácter que conduce a un nodo hijo.
     * @var TrieNode[]
     */
    public array $children = [];
    /**
     * Array de métodos asociados a la ruta.
     *
     * Este array almacena los métodos HTTP permitidos para la ruta
     * en este nodo, donde la clave es el método (por ejemplo, 'GET', 'POST')
     * y el valor es la información de la ruta asociada.
     * @var array
     */
    public array $methods = []; // [method => routeData]
    /**
     * Constructor de la clase TrieNode.
     *
     * Inicializa un nuevo nodo de Trie con arrays vacíos para
     * los hijos y los métodos.
     */
    public function __construct() {
        $this->children = [];
        $this->methods = [];
    }
}

/**
 * Clase que representa un Trie para el enrutamiento de rutas.
 *
 * Esta clase permite almacenar y gestionar rutas HTTP de manera eficiente,
 * utilizando una estructura de árbol (Trie). También proporciona funcionalidades
 * para el almacenamiento en caché de las rutas.
 */
class Trie {
    // Métodos HTTP permitidos
    protected const ALLOWED_METHODS = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'HEAD', 'OPTIONS'];
    
    // Nodo raíz del Trie
    protected TrieNode $root;
    
    // Ruta del archivo de caché
    protected string $cacheFile;
    
    // Indica si se debe guardar automáticamente la caché
    protected bool $autoSave = true;
    
    // Contexto de las rutas
    protected string $context;
    
    // Instancia única de la clase (Singleton)
    protected static ?self $instance = null;
    /**
     * Constructor de la clase RouteTrie.
     *
     * @param string $context Contexto para las rutas (por defecto es 'default').
     */
    public function __construct(string $context = 'XFIRST') {
        $this->context = $this->sanitizeContext($context);
        $this->root = new TrieNode();
        $this->cacheFile = $this->getCachePath($this->context);
        $this->initializeCache();
        $this->loadCache();
    }
    /**
     * Obtiene la instancia única de la clase RouteTrie (patrón Singleton).
     *
     * Si la instancia no existe, se crea una nueva.
     *
     * @return self Instancia única de la clase RouteTrie.
     */
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    /**
     * Reinicia la instancia única de la clase (opcional).
     *
     * Esto puede ser útil para restablecer el estado de la clase.
     *
     * @return void
     */
    public static function reset(): void {
        self::$instance = null;
    }
    /**
     * Sanitiza el contexto eliminando caracteres no permitidos.
     *
     * @param string $context Contexto a sanitizar.
     * @return string Contexto sanitizado.
     */
    protected function sanitizeContext(string $context): string {
        return preg_replace('/[^a-zA-Z0-9_\-]/', '', $context);
    }
    /**
     * Obtiene la ruta del archivo de caché.
     *
     * @param string $context Contexto para el que se obtiene la ruta de caché.
     * @return string Ruta del archivo de caché.
     * @throws RuntimeException Si no se puede crear el directorio de caché.
     */
    protected function getCachePath(string $context): string {
        $base = dirname(__DIR__, 2) . '/storage/cache/rutas';
        if (!is_dir($base) && !mkdir($base, 0755, true)) {
            throw new RuntimeException("No se pudo crear el directorio de caché: {$base}");
        }
        return "{$base}/{$context}.cache";
    }
    /**
     * Inicializa el archivo de caché si no existe.
     *
     * @return void
     */
    private function initializeCache(): void {
        if (!file_exists($this->cacheFile)) {
            Registro::registrarLog("Inicializando caché de rutas en: {$this->cacheFile}", 'rutas');
            $this->saveCache();
        }
    }
    /**
     * Establece si la caché debe guardarse automáticamente.
     *
     * @param bool $value Valor que indica si se debe habilitar o deshabilitar el guardado automático.
     * @return void
     */
    public function setAutoSave(bool $value): void {
        $this->autoSave = $value;
    }
    /**
     * Carga las rutas desde el archivo de caché.
     *
     * @return void
     * @throws RuntimeException Si hay un error al leer el archivo de caché o si el formato es inválido.
     */
    private function loadCache(): void {
        if (!file_exists($this->cacheFile) || !is_readable($this->cacheFile)) {
            Registro::registrarLog("Archivo de caché no accesible: {$this->cacheFile}", 'caché');
            return;
        }
        $data = file_get_contents($this->cacheFile);
        if ($data === false) {
            throw new RuntimeException("Error al leer el archivo de caché");
        }
        $routes = unserialize($data);
        if (!is_array($routes)) {
            throw new RuntimeException("Formato de caché inválido");
        }
        $this->importFromArray($routes);
    }
    /**
     * Inserta una nueva ruta en el Trie.
     *
     * @param string $method Método HTTP de la ruta.
     * @param string $uri URI de la ruta.
     * @param mixed $action Acción asociada a la ruta.
     * @param array $middleware Middleware opcional para la ruta.
     * @return void
     */
    public function insert(string $method, string $uri, $action, array $middleware = []): void {
        $this->addRoute($method, $uri, $action, $middleware);
    }
    /**
     * Inserta una nueva ruta de manera estática en el Trie.
     *
     * Este método permite agregar rutas sin necesidad de instanciar la clase.
     *
     * @param string $method Método HTTP de la ruta.
     * @param string $uri URI de la ruta.
     * @param mixed $action Acción asociada a la ruta.
     * @param array $middleware Middleware opcional para la ruta.
     * @return void
     */
    public static function insertStatic(string $method, string $uri, $action, array $middleware = []): void {
        self::getInstance()->addRoute($method, $uri, $action, $middleware);
    }
    
    /**
     * Despacha una solicitud HTTP a la ruta correspondiente.
     * #Versión Lite - Sino existe uno dedicado o esta en desarrollo
     *
     * Este método toma el método HTTP y la URI de la solicitud, valida que
     * sean correctos y luego recorre el Trie para encontrar la ruta correspondiente.
     * Si se encuentra la ruta, se ejecutan los middlewares asociados y, finalmente,
     * se ejecuta la acción correspondiente a la ruta.
     *
     * @param string $method El método HTTP de la solicitud (GET, POST, etc.).
     * @param string $uri La URI de la solicitud.
     * @return mixed El resultado de la acción ejecutada, si la acción es callable.
     * @throws RuntimeException Si la ruta no se encuentra o si el método no está permitido.
     */
    public function despacharLite(string $method, string $uri) {
        $method = strtoupper($method);
        $this->validateMethod($method);
        $this->validateUri($uri);
        $segments = $this->splitUri($uri);
        $node = $this->root;
        // Recorrido del Trie
        foreach ($segments as $segment) {
            if (!isset($node->children[$segment])) {
                throw new RuntimeException("Ruta no encontrada: {$uri}");
            }
            $node = $node->children[$segment];
        }
        if (!isset($node->methods[$method])) {
            throw new RuntimeException("Método no permitido para esta ruta: [{$method}] {$uri}");
        }
        $route = $node->methods[$method];
        $action = $route['action'];
        $middleware = $route['middleware'] ?? [];
        // Middleware (simple ejecución en cadena, puede ser más complejo)
        foreach ($middleware as $mw) {
            if (is_callable($mw)) {
                $result = $mw($method, $uri);
                if ($result === false) {
                    return; // Cortar ejecución si algún middleware lo decide
                }
            }
        }
        // Ejecutar acción
        if (is_callable($action)) {
            return call_user_func($action);
        }
        throw new RuntimeException("Acción no ejecutable para [{$method}] {$uri}");
    }
    ##
    /**
     * Middleware Ej
     * Si querés definir middlewares tipo:
    $authMiddleware = fn($method, $uri) => {
        if (!isset($_SESSION['user'])) {
            echo "No autorizado.";
            return false;
        }
    };
    Y registrar así:
    RouteTrie::insertStatic('GET', '/dashboard', [DashboardController::class, 'index'], [$authMiddleware]);
     */
    ##
    /**
     * Agrega una nueva ruta al Trie.
     *
     * @param string $method Método HTTP de la ruta.
     * @param string $uri URI de la ruta.
     * @param mixed $action Acción asociada a la ruta.
     * @param array $middleware Middleware opcional para la ruta.
     * @return void
     * @throws InvalidArgumentException Si el método, acción o URI son inválidos.
     */
    public function addRoute(string $method, string $uri, $action, array $middleware = []): void {
        $method = strtoupper($method);
        $this->validateMethod($method);
        $this->validateAction($action);
        $this->validateUri($uri);

        $segments = $this->splitUri($uri);
        $node = $this->root;

        if (!empty($segments)) {
            foreach ($segments as $segment) {
                if (!isset($node->children[$segment])) {
                    $node->children[$segment] = new TrieNode();
                }
                $node = $node->children[$segment];
            }
        }

        if (isset($node->methods[$method])) {
            if ($node->methods[$method]['action'] === $action) return; // ruta ya existe exactamente igual
            Registro::registrarLog("Ruta duplicada: [$method] $uri", 'rutas');
            return;
        }
        $node->methods[$method] = [
            'method' => $method,
            'uri' => $uri,
            'action' => $action,
            'middleware' => $middleware,
        ];
        // if ($this->autoSave) {
        if ($this->autoSave ?? false) {
            $this->saveCache();
        }
    }
    /**
     * Valida que el método HTTP sea permitido.
     *
     * @param string $method Método HTTP a validar.
     * @return void
     * @throws InvalidArgumentException Si el método no es permitido.
     */
    protected function validateMethod(string $method): void {
        if (!in_array($method, self::ALLOWED_METHODS)) {
            throw new InvalidArgumentException("Método HTTP no permitido: {$method}");
        }
    }
    /**
     * Valida que la acción sea una referencia válida a una clase y método,
     * un array con la clase y el método, o una función callable.
     *
     * La acción puede ser especificada de las siguientes maneras:
     * - Una cadena en el formato 'Clase@metodo', donde 'Clase' es el nombre de la clase
     *   y 'metodo' es el nombre del método que se desea llamar.
     * - Un array donde el primer elemento es el nombre de la clase y el segundo el nombre del método.
     * - Una función callable.
     *
     * Si la acción no es válida, se lanza una excepción InvalidArgumentException.
     *
     * @param mixed $action Referencia a la acción que se va a validar. Puede ser:
     *                      - string en el formato 'Clase@metodo'
     *                      - array con la clase y el método
     *                      - callable
     * @return void
     * @throws InvalidArgumentException Si la acción no es válida o si la clase/método no existe.
     */
    protected function validateAction(&$action): void {
        // Si es string con @ (ej: "HomeController@showLoginForm")
        if (is_string($action) && str_contains($action, '@')) {
            [$class, $method] = explode('@', $action, 2);
            if (!class_exists($class)) {
                throw new InvalidArgumentException("La clase {$class} no existe.");
            }
            if (!method_exists($class, $method)) {
                throw new InvalidArgumentException("El método {$method} no existe en la clase {$class}.");
            }
            $action = [$class, $method]; // Normalizamos como callable
            return;
        }
    
        // Si es un array tipo [Clase::class, 'método']
        if (is_array($action)) {
            if (count($action) !== 2 || !is_string($action[0]) || !is_string($action[1])) {
                throw new InvalidArgumentException("La acción en array debe ser [Clase::class, 'método']");
            }
            if (!class_exists($action[0])) {
                throw new InvalidArgumentException("La clase {$action[0]} no existe.");
            }
            if (!method_exists($action[0], $action[1])) {
                throw new InvalidArgumentException("El método {$action[1]} no existe en la clase {$action[0]}.");
            }
            return;
        }
    
        // Callable directo (ej: Closure, función global)
        if (is_callable($action)) {
            return;
        }
    
        // Si es string sin @ (como nombre de función global)
        if (is_string($action) && function_exists($action)) {
            return;
        }
    
        throw new InvalidArgumentException("Acción inválida: debe ser callable, array [Clase::class, 'método'], o string 'Clase@metodo'");
    }
    
    /**
     * Valida que la URI tenga un formato correcto.
     *
     * @param string $uri URI a validar.
     * @return void
     * @throws InvalidArgumentException Si la URI tiene un formato inválido.
     */
    protected function validateUri(string $uri): void {
        // if (!preg_match('/^(\/\w*|{\w+})*\/?$/', $uri)) {
        if (!preg_match('#^(/[\w\-\.\{\}]+)*\/?$#', $uri)) {
            throw new InvalidArgumentException("Formato de URI inválido: {$uri}");
        }
    }
    /**
     * Divide una URI en segmentos.
     *
     * @param string $uri URI a dividir.
     * @return array Array de segmentos de la URI.
     */
    protected function splitUri(string $uri): array {
        return array_filter(explode('/', trim($uri, '/')), fn($segment) => $segment !== '');
    }
    /**
     * Intenta hacer coincidir un método y una URI con las rutas registradas.
     *
     * @param string $method Método HTTP a coincidir.
     * @param string $uri URI a coincidir.
     * @return array|null Array con información de la ruta coincidente o null si no hay coincidencia.
     */
    public function match(string $method, string $uri): ?array {
        $params = [];
        $route = $this->matchRoute($method, $uri, $params);
        return $route ? $route + ['params' => $params] : null;
    }
    /**
     * Coincide una ruta en el Trie utilizando un método y una URI.
     *
     * @param string $method Método HTTP a coincidir.
     * @param string $uri URI a coincidir.
     * @param array $params Array para almacenar parámetros de la ruta.
     * @return array|null Array con información de la ruta coincidente o null si no hay coincidencia.
     */
    public function matchRoute(string $method, string $uri, array &$params = []): ?array {
        $segments = $this->splitUri($uri);
        return $this->matchRecursive($this->root, $segments, 0, $params, strtoupper($method));
    }
    /**
     * Método recursivo para hacer coincidir segmentos de URI en el Trie.
     *
     * @param TrieNode $node Nodo actual del Trie.
     * @param array $segments Segmentos de la URI a coincidir.
     * @param int $index Índice del segmento actual.
     * @param array $params Array para almacenar parámetros de la ruta.
     * @param string $method Método HTTP a coincidir.
     * @return array|null Array con información de la ruta coincidente o null si no hay coincidencia.
     */
    private function matchRecursive(TrieNode $node, array $segments, int $index, array &$params, string $method): ?array {
        if ($index >= count($segments)) {
            return $node->methods[$method] ?? null;
        }
        $segment = $segments[$index];
        // Coincidencia exacta
        if (isset($node->children[$segment])) {
            $result = $this->matchRecursive($node->children[$segment], $segments, $index + 1, $params, $method);
            if ($result) return $result;
        }
        // Coincidencia con parámetros
        foreach ($node->children as $key => $child) {
            if (preg_match('/^\{(.+)\}$/', $key, $matches)) {
                $params[$matches[1]] = $segment;
                $result = $this->matchRecursive($child, $segments, $index + 1, $params, $method);
                if ($result) return $result;
                unset($params[$matches[1]]);
            }
        }
        return null;
    }
    /**
     * Verifica si existe una ruta para un método y URI específicos.
     *
     * @param string $method Método HTTP a verificar.
     * @param string $uri URI a verificar.
     * @return bool True si existe la ruta, false en caso contrario.
     */
    public function hasRoute(string $method, string $uri): bool {
        return $this->match($method, $uri) !== null;
    }
    /**
     * Exporta todas las rutas desde el Trie a un array.
     *
     * @param TrieNode|null $node Nodo desde el cual comenzar la exportación.
     * @param string $prefix Prefijo para las rutas exportadas.
     * @return array Array de rutas exportadas.
     */
    public function exportRoutes(TrieNode $node = null, string $prefix = ''): array {
        $routes = [];
        $node = $node ?? $this->root;
        foreach ($node->methods as $method => $route) {
            $route['uri'] = $prefix ?: '/';
            $routes[] = $route;
        }
        foreach ($node->children as $segment => $child) {
            $routes = array_merge($routes, $this->exportRoutes($child, $prefix . '/' . $segment));
        }
        return $routes;
    }
    /**
     * Obtiene las rutas registradas para un método específico.
     *
     * @param string $method Método HTTP para el cual obtener las rutas.
     * @return array Array de rutas que coinciden con el método.
     */
    public function getRoutesByMethod(string $method): array {
        return array_filter(
            $this->exportRoutes(),
            fn($r) => $r['method'] === strtoupper($method)
        );
    }
    /**
     * Obtiene la ruta asociada a una acción específica.
     *
     * @param callable|array|string $action Acción a buscar.
     * @return string|null URI de la ruta asociada o null si no se encuentra.
     */
    public function getPath(callable|array|string $action): ?string {
        foreach ($this->exportRoutes() as $route) {
            if ($route['action'] === $action || (
                is_array($route['action']) && is_array($action) &&
                $route['action'][0] === $action[0] && $route['action'][1] === $action[1]
            )) {
                return $route['uri'];
            }
        }
        return null;
    }
    /**
     * Imprime el árbol de rutas para depuración.
     *
     * @param TrieNode|null $node Nodo desde el cual comenzar la depuración.
     * @param string $prefix Prefijo para las rutas depuradas.
     * @return void
     */
    public function debugTree(TrieNode $node = null, string $prefix = ''): void {
        $node = $node ?? $this->root;
        foreach ($node->methods as $method => $data) {
            echo "[$method] {$prefix}/ => " . (is_string($data['action']) ? $data['action'] : '[callable]') . "\n";
        }
        foreach ($node->children as $segment => $child) {
            $this->debugTree($child, $prefix . '/' . $segment);
        }
    }
    /**
     * Importa rutas desde un array.
     *
     * @param array $routes Array de rutas a importar.
     * @return void
     */
    public function importFromArray(array $routes): void {
        foreach ($routes as $r) {
            $this->addRoute(
                $r['method'] ?? 'GET',
                $r['uri'],
                $r['action'],
                $r['middleware'] ?? []
            );
        }
    }
    /**
     * Importa rutas desde un JSON.
     *
     * @param string $json Cadena JSON que contiene las rutas.
     * @return void
     * @throws InvalidArgumentException Si el JSON es inválido.
     */
    public function importFromJson(string $json): void {
        $array = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException("JSON inválido: " . json_last_error_msg());
        }
        $this->importFromArray($array);
    }
    /**
     * Guarda las rutas en el archivo de caché.
     *
     * @return void
     * @throws RuntimeException Si no se puede escribir en el archivo de caché.
     */
    private function saveCache(): void {
        $routes = $this->exportRoutes();
        $result = file_put_contents(
            $this->cacheFile,
            serialize($routes),
            LOCK_EX
        );
        if ($result === false) {
            throw new RuntimeException("No se pudo escribir en el archivo de caché");
        }
    }
    /**
     * Elimina el archivo de caché.
     *
     * @return void
     * @throws RuntimeException Si no se puede eliminar el archivo de caché.
     */
    public function clearCache(): void {
        if (file_exists($this->cacheFile) && !unlink($this->cacheFile)) {
            throw new RuntimeException("No se pudo eliminar el archivo de caché");
        }
        $this->saveCache();
    }
}
?>

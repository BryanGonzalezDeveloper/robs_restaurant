<?php

/**
 * ROBS - Sistema de Router
 * 
 * Maneja el enrutamiento de URLs a controladores y acciones
 */
class Router
{
    private array $routes = [];
    private array $middleware = [];
    private array $namedRoutes = [];
    
    /**
     * Registrar una ruta GET
     */
    public function get(string $path, $handler, string $name = null): void
    {
        $this->addRoute('GET', $path, $handler, $name);
    }
    
    /**
     * Registrar una ruta POST
     */
    public function post(string $path, $handler, string $name = null): void
    {
        $this->addRoute('POST', $path, $handler, $name);
    }
    
    /**
     * Registrar una ruta PUT
     */
    public function put(string $path, $handler, string $name = null): void
    {
        $this->addRoute('PUT', $path, $handler, $name);
    }
    
    /**
     * Registrar una ruta DELETE
     */
    public function delete(string $path, $handler, string $name = null): void
    {
        $this->addRoute('DELETE', $path, $handler, $name);
    }
    
    /**
     * Registrar un grupo de rutas con middleware
     */
    public function group(array $attributes, callable $callback): void
    {
        $previousMiddleware = $this->middleware;
        
        if (isset($attributes['middleware'])) {
            $this->middleware = array_merge($this->middleware, (array)$attributes['middleware']);
        }
        
        $callback($this);
        
        $this->middleware = $previousMiddleware;
    }
    
    /**
     * Agregar middleware a las próximas rutas
     */
    public function middleware($middleware): self
    {
        $this->middleware = array_merge($this->middleware, (array)$middleware);
        return $this;
    }
    
    /**
     * Agregar una ruta al sistema
     */
    private function addRoute(string $method, string $path, $handler, string $name = null): void
    {
        $route = [
            'method' => $method,
            'path' => $this->normalizePath($path),
            'handler' => $handler,
            'middleware' => $this->middleware,
            'regex' => $this->pathToRegex($path),
            'params' => []
        ];
        
        $this->routes[] = $route;
        
        if ($name) {
            $this->namedRoutes[$name] = $route;
        }
        
        // Reset middleware para la próxima ruta
        $this->middleware = [];
    }
    
    /**
     * Normalizar el path de la ruta
     */
    private function normalizePath(string $path): string
    {
        $path = trim($path, '/');
        return $path === '' ? '/' : '/' . $path;
    }
    
    /**
     * Convertir path a expresión regular
     */
    private function pathToRegex(string $path): string
    {
        // Escapar caracteres especiales
        $regex = preg_quote($path, '/');
        
        // Reemplazar parámetros {param} con grupos de captura
        $regex = preg_replace('/\\\{([^}]+)\\\}/', '([^/]+)', $regex);
        
        return '/^' . $regex . '$/';
    }
    
    /**
     * Manejar la petición HTTP
     */
    public function handleRequest(string $method, string $uri): void
    {
        $uri = $this->normalizePath($uri);
        
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && preg_match($route['regex'], $uri, $matches)) {
                // Extraer parámetros de la URL
                array_shift($matches); // Remover la coincidencia completa
                $params = $matches;
                
                // Ejecutar middleware
                foreach ($route['middleware'] as $middlewareClass) {
                    $this->executeMiddleware($middlewareClass);
                }
                
                // Ejecutar el handler
                $this->executeHandler($route['handler'], $params);
                return;
            }
        }
        
        // No se encontró la ruta
        $this->handleNotFound();
    }
    
    /**
     * Ejecutar middleware
     */
    private function executeMiddleware(string $middlewareClass): void
    {
        $middlewarePath = APP_PATH . '/Middleware/' . $middlewareClass . '.php';
        
        if (!file_exists($middlewarePath)) {
            throw new Exception("Middleware {$middlewareClass} no encontrado");
        }
        
        require_once $middlewarePath;
        
        if (!class_exists($middlewareClass)) {
            throw new Exception("Clase de middleware {$middlewareClass} no encontrada");
        }
        
        $middleware = new $middlewareClass();
        
        if (!method_exists($middleware, 'handle')) {
            throw new Exception("Método handle() no encontrado en middleware {$middlewareClass}");
        }
        
        $middleware->handle();
    }
    
    /**
     * Ejecutar el handler de la ruta
     */
    private function executeHandler($handler, array $params = []): void
    {
        if (is_string($handler)) {
            // Handler en formato "Controller@method"
            if (strpos($handler, '@') !== false) {
                list($controllerName, $method) = explode('@', $handler);
                $this->executeControllerMethod($controllerName, $method, $params);
            } else {
                throw new Exception("Formato de handler inválido: {$handler}");
            }
        } elseif (is_callable($handler)) {
            // Handler como función anónima
            call_user_func_array($handler, $params);
        } else {
            throw new Exception("Tipo de handler no soportado");
        }
    }
    
    /**
     * Ejecutar método de controlador
     */
    private function executeControllerMethod(string $controllerName, string $method, array $params = []): void
    {
        // Buscar el controlador en diferentes directorios
        $controllerPaths = [
            APP_PATH . '/Controllers/' . $controllerName . '.php',
            APP_PATH . '/Controllers/Auth/' . $controllerName . '.php',
            APP_PATH . '/Controllers/Branch/' . $controllerName . '.php',
            APP_PATH . '/Controllers/Menu/' . $controllerName . '.php',
            APP_PATH . '/Controllers/Order/' . $controllerName . '.php',
            APP_PATH . '/Controllers/Financial/' . $controllerName . '.php',
            APP_PATH . '/Controllers/Report/' . $controllerName . '.php',
            APP_PATH . '/Controllers/API/' . $controllerName . '.php'
        ];
        
        $controllerFile = null;
        foreach ($controllerPaths as $path) {
            if (file_exists($path)) {
                $controllerFile = $path;
                break;
            }
        }
        
        if (!$controllerFile) {
            throw new Exception("Controlador {$controllerName} no encontrado");
        }
        
        require_once $controllerFile;
        
        if (!class_exists($controllerName)) {
            throw new Exception("Clase {$controllerName} no encontrada");
        }
        
        $controller = new $controllerName();
        
        if (!method_exists($controller, $method)) {
            throw new Exception("Método {$method} no encontrado en {$controllerName}");
        }
        
        // Llamar al método del controlador con los parámetros
        call_user_func_array([$controller, $method], $params);
    }
    
    /**
     * Manejar ruta no encontrada (404)
     */
    private function handleNotFound(): void
    {
        http_response_code(404);
        
        $notFoundView = VIEWS_PATH . '/errors/404.php';
        if (file_exists($notFoundView)) {
            include $notFoundView;
        } else {
            echo "<h1>404 - Página no encontrada</h1>";
            echo "<p>La página que buscas no existe.</p>";
        }
    }
    
    /**
     * Generar URL por nombre de ruta
     */
    public function url(string $name, array $params = []): string
    {
        if (!isset($this->namedRoutes[$name])) {
            throw new Exception("Ruta nombrada '{$name}' no encontrada");
        }
        
        $route = $this->namedRoutes[$name];
        $path = $route['path'];
        
        // Reemplazar parámetros en el path
        foreach ($params as $key => $value) {
            $path = str_replace('{' . $key . '}', $value, $path);
        }
        
        return $path;
    }
    
    /**
     * Obtener todas las rutas registradas
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }
    
    /**
     * Redireccionar a una URL
     */
    public function redirect(string $url, int $statusCode = 302): void
    {
        header("Location: {$url}", true, $statusCode);
        exit;
    }
}
<?php
/**
 * ROBS - Restaurant Order, Billing & Sales
 * Bootstrap de la aplicación
 * 
 * Este archivo es el punto de entrada principal del sistema
 */

// Definir constantes de la aplicación
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('STORAGE_PATH', ROOT_PATH . '/storage');
define('VIEWS_PATH', APP_PATH . '/Views');
define('UPLOADS_PATH', PUBLIC_PATH . '/assets/uploads');

// Configuración de PHP
ini_set('display_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('America/Mexico_City');

// Configurar headers de seguridad
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Autoloader personalizado
spl_autoload_register(function ($class) {
    // Convertir namespace a ruta de archivo
    $file = APP_PATH . '/' . str_replace('\\', '/', $class) . '.php';
    
    if (file_exists($file)) {
        require_once $file;
        return true;
    }
    
    // Buscar en subdirectorios comunes
    $commonPaths = [
        APP_PATH . '/Core/',
        APP_PATH . '/Models/',
        APP_PATH . '/Controllers/',
        APP_PATH . '/Services/',
        APP_PATH . '/Utils/',
        APP_PATH . '/Middleware/'
    ];
    
    foreach ($commonPaths as $path) {
        $file = $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return true;
        }
    }
    
    return false;
});

// Cargar configuración desde archivo .env
function loadEnvironment() {
    $envFile = APP_PATH . '/Config/.env';
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos($line, '#') === 0 || strpos($line, '=') === false) {
                continue;
            }
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// Cargar configuración
loadEnvironment();

// Configurar manejo de errores
set_error_handler(function($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    
    $errorLog = STORAGE_PATH . '/logs/error.log';
    $logMessage = date('Y-m-d H:i:s') . " - ERROR: $message in $file on line $line\n";
    file_put_contents($errorLog, $logMessage, FILE_APPEND | LOCK_EX);
    
    // En desarrollo, mostrar el error
    if (isset($_ENV['APP_DEBUG']) && $_ENV['APP_DEBUG'] === 'true') {
        echo "<div style='color: red; padding: 10px; border: 1px solid red; margin: 10px;'>";
        echo "<strong>Error:</strong> $message<br>";
        echo "<strong>File:</strong> $file<br>";
        echo "<strong>Line:</strong> $line";
        echo "</div>";
    }
    
    return true;
});

// Configurar manejo de excepciones
set_exception_handler(function($exception) {
    $errorLog = STORAGE_PATH . '/logs/error.log';
    $logMessage = date('Y-m-d H:i:s') . " - EXCEPTION: " . $exception->getMessage() . 
                  " in " . $exception->getFile() . " on line " . $exception->getLine() . "\n";
    file_put_contents($errorLog, $logMessage, FILE_APPEND | LOCK_EX);
    
    // En desarrollo, mostrar la excepción
    if (isset($_ENV['APP_DEBUG']) && $_ENV['APP_DEBUG'] === 'true') {
        echo "<div style='color: red; padding: 10px; border: 1px solid red; margin: 10px;'>";
        echo "<strong>Exception:</strong> " . $exception->getMessage() . "<br>";
        echo "<strong>File:</strong> " . $exception->getFile() . "<br>";
        echo "<strong>Line:</strong> " . $exception->getLine() . "<br>";
        echo "<pre>" . $exception->getTraceAsString() . "</pre>";
        echo "</div>";
    } else {
        // En producción, mostrar página de error genérica
        http_response_code(500);
        include VIEWS_PATH . '/errors/500.php';
    }
});

try {
    // Inicializar sesión
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Cargar clases principales
    require_once APP_PATH . '/Core/Database.php';
    require_once APP_PATH . '/Core/Router.php';
    require_once APP_PATH . '/Core/Controller.php';
    require_once APP_PATH . '/Core/Model.php';
    require_once APP_PATH . '/Utils/Helpers.php';
    
    // Inicializar router
    $router = new Router();
    
    // Cargar rutas
    require_once APP_PATH . '/Config/Routes.php';
    
    // Obtener la URL solicitada
   $requestUri = $_SERVER['REQUEST_URI'];
$scriptName = $_SERVER['SCRIPT_NAME'];

// Remover query string primero
if (($pos = strpos($requestUri, '?')) !== false) {
    $requestUri = substr($requestUri, 0, $pos);
}

// Determinar el directorio base del proyecto
$projectDir = '/robs/public';
if (strpos($requestUri, $projectDir) === 0) {
    $requestUri = substr($requestUri, strlen($projectDir));
}

// Si la URL termina en /index.php, tratarla como /
if ($requestUri === '/index.php' || $requestUri === '/index.php/') {
    $requestUri = '/';
}

// Limpiar la URL
$requestUri = '/' . ltrim($requestUri, '/');

// Si está vacía, es la raíz
if ($requestUri === '//') {
    $requestUri = '/';
}


    
    // Obtener método HTTP
    $method = $_SERVER['REQUEST_METHOD'];
    
    // Procesar la ruta
    $router->handleRequest($method, $requestUri);
    
} catch (Exception $e) {
    // Log del error
    $errorLog = STORAGE_PATH . '/logs/error.log';
    $logMessage = date('Y-m-d H:i:s') . " - FATAL ERROR: " . $e->getMessage() . "\n";
    file_put_contents($errorLog, $logMessage, FILE_APPEND | LOCK_EX);
    
    // Mostrar error
    if (isset($_ENV['APP_DEBUG']) && $_ENV['APP_DEBUG'] === 'true') {
        echo "<h1>Error Fatal</h1>";
        echo "<p>" . $e->getMessage() . "</p>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    } else {
        http_response_code(500);
        echo "<h1>Error del Servidor</h1>";
        echo "<p>Ha ocurrido un error interno. Por favor contacte al administrador.</p>";
    }
}
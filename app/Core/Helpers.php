<?php

/**
 * ROBS - Funciones de Ayuda
 * 
 * Funciones utilitarias globales del sistema
 */

/**
 * Generar URL basada en ruta nombrada
 */
function route(string $name, array $params = []): string
{
    global $router;
    
    try {
        return $router->url($name, $params);
    } catch (Exception $e) {
        return '#';
    }
}

/**
 * Generar URL completa
 */
function url(string $path = ''): string
{
    $baseUrl = $_ENV['APP_URL'] ?? 'http://localhost';
    return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
}

/**
 * Generar URL para assets
 */
function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

/**
 * Obtener configuración
 */
function config(string $key, $default = null)
{
    static $config = [];
    
    if (empty($config)) {
        $config = [
            'app' => include APP_PATH . '/Config/App.php',
            'database' => include APP_PATH . '/Config/Database.php'
        ];
    }
    
    $keys = explode('.', $key);
    $value = $config;
    
    foreach ($keys as $k) {
        if (!is_array($value) || !array_key_exists($k, $value)) {
            return $default;
        }
        $value = $value[$k];
    }
    
    return $value;
}

/**
 * Obtener variable de entorno
 */
function env(string $key, $default = null)
{
    return $_ENV[$key] ?? $default;
}

/**
 * Escape HTML
 */
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/**
 * Formatear precio
 */
function formatPrice(float $amount, string $currency = '$'): string
{
    return $currency . number_format($amount, 2, '.', ',');
}

/**
 * Formatear fecha
 */
function formatDate($date, string $format = 'd/m/Y'): string
{
    if (is_string($date)) {
        $date = new DateTime($date);
    }
    
    if (!$date instanceof DateTime) {
        return '';
    }
    
    return $date->format($format);
}

/**
 * Formatear fecha y hora
 */
function formatDateTime($datetime, string $format = 'd/m/Y H:i'): string
{
    if (is_string($datetime)) {
        $datetime = new DateTime($datetime);
    }
    
    if (!$datetime instanceof DateTime) {
        return '';
    }
    
    return $datetime->format($format);
}

/**
 * Tiempo transcurrido en palabras
 */
function timeAgo($datetime): string
{
    if (is_string($datetime)) {
        $datetime = new DateTime($datetime);
    }
    
    if (!$datetime instanceof DateTime) {
        return '';
    }
    
    $now = new DateTime();
    $interval = $now->diff($datetime);
    
    if ($interval->days > 0) {
        return $interval->days . ' día' . ($interval->days > 1 ? 's' : '') . ' atrás';
    } elseif ($interval->h > 0) {
        return $interval->h . ' hora' . ($interval->h > 1 ? 's' : '') . ' atrás';
    } elseif ($interval->i > 0) {
        return $interval->i . ' minuto' . ($interval->i > 1 ? 's' : '') . ' atrás';
    } else {
        return 'Hace un momento';
    }
}

/**
 * Generar token CSRF
 */
function csrfToken(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Validar token CSRF
 */
function validateCsrfToken(string $token): bool
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Generar campo CSRF para formularios
 */
function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

/**
 * Redireccionar
 */
function redirect(string $url): void
{
    header("Location: $url");
    exit;
}

/**
 * Redireccionar a ruta nombrada
 */
function redirectToRoute(string $name, array $params = []): void
{
    redirect(route($name, $params));
}

/**
 * Redireccionar atrás
 */
function redirectBack(): void
{
    $referer = $_SERVER['HTTP_REFERER'] ?? route('dashboard');
    redirect($referer);
}

/**
 * Generar UUID v4
 */
function generateUuid(): string
{
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

/**
 * Generar código aleatorio
 */
function generateCode(int $length = 8, bool $includeNumbers = true, bool $includeLetters = true): string
{
    $characters = '';
    
    if ($includeNumbers) {
        $characters .= '0123456789';
    }
    
    if ($includeLetters) {
        $characters .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    }
    
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[rand(0, strlen($characters) - 1)];
    }
    
    return $code;
}

/**
 * Validar email
 */
function isValidEmail(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validar teléfono mexicano
 */
function isValidMexicanPhone(string $phone): bool
{
    $phone = preg_replace('/[^0-9]/', '', $phone);
    return preg_match('/^[0-9]{10}$/', $phone);
}

/**
 * Formatear teléfono mexicano
 */
function formatMexicanPhone(string $phone): string
{
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    if (strlen($phone) === 10) {
        return sprintf('(%s) %s-%s', 
            substr($phone, 0, 3), 
            substr($phone, 3, 3), 
            substr($phone, 6, 4)
        );
    }
    
    return $phone;
}

/**
 * Validar RFC mexicano
 */
function isValidRFC(string $rfc): bool
{
    $rfc = strtoupper(trim($rfc));
    
    // Persona física: 4 letras + 6 números + 3 caracteres (homoclave)
    $personaFisica = '/^[A-Z]{4}[0-9]{6}[A-Z0-9]{3}$/';
    
    // Persona moral: 3 letras + 6 números + 3 caracteres (homoclave)
    $personaMoral = '/^[A-Z]{3}[0-9]{6}[A-Z0-9]{3}$/';
    
    return preg_match($personaFisica, $rfc) || preg_match($personaMoral, $rfc);
}

/**
 * Limpiar texto para URLs
 */
function slugify(string $text): string
{
    $text = iconv('UTF-8', 'ASCII//TRANSLIT', $text);
    $text = preg_replace('/[^a-z0-9\-]/i', '-', $text);
    $text = preg_replace('/-+/', '-', $text);
    $text = trim($text, '-');
    
    return strtolower($text);
}

/**
 * Truncar texto
 */
function truncate(string $text, int $length = 100, string $suffix = '...'): string
{
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    
    return mb_substr($text, 0, $length) . $suffix;
}

/**
 * Convertir bytes a formato legible
 */
function formatBytes(int $bytes, int $precision = 2): string
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

/**
 * Verificar si es petición AJAX
 */
function isAjax(): bool
{
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Obtener IP real del cliente
 */
function getClientIp(): string
{
    $keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
    
    foreach ($keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ips = explode(',', $_SERVER[$key]);
            $ip = trim($ips[0]);
            
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

/**
 * Logging simple
 */
function logInfo(string $message, string $channel = 'app'): void
{
    $logFile = config('logging.channels.' . $channel, STORAGE_PATH . '/logs/app.log');
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] INFO: {$message}\n";
    
    if (!is_dir(dirname($logFile))) {
        mkdir(dirname($logFile), 0755, true);
    }
    
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

/**
 * Logging de errores
 */
function logError(string $message, string $channel = 'error'): void
{
    $logFile = config('logging.channels.' . $channel, STORAGE_PATH . '/logs/error.log');
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] ERROR: {$message}\n";
    
    if (!is_dir(dirname($logFile))) {
        mkdir(dirname($logFile), 0755, true);
    }
    
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

/**
 * Obtener usuario actual
 */
function auth(): ?array
{
    return $_SESSION['user'] ?? null;
}

/**
 * Verificar si usuario está autenticado
 */
function isAuth(): bool
{
    return isset($_SESSION['user']) && !empty($_SESSION['user']);
}

/**
 * Verificar si usuario tiene rol específico
 */
function hasRole(string $role): bool
{
    $user = auth();
    return $user && ($user['role'] ?? '') === $role;
}

/**
 * Verificar si usuario tiene alguno de los roles
 */
function hasAnyRole(array $roles): bool
{
    $user = auth();
    return $user && in_array($user['role'] ?? '', $roles);
}

/**
 * Obtener sucursal actual
 */
function currentBranch(): ?array
{
    return $_SESSION['branch'] ?? null;
}

/**
 * Traducir texto (función básica)
 */
function __(string $key, array $replace = []): string
{
    // Aquí puedes implementar un sistema de traducción más complejo
    $translations = [
        'welcome' => 'Bienvenido',
        'logout' => 'Cerrar Sesión',
        'login' => 'Iniciar Sesión',
        'dashboard' => 'Panel Principal',
        'orders' => 'Órdenes',
        'products' => 'Productos',
        'reports' => 'Reportes',
        'users' => 'Usuarios',
        'settings' => 'Configuración',
        'save' => 'Guardar',
        'cancel' => 'Cancelar',
        'delete' => 'Eliminar',
        'edit' => 'Editar',
        'create' => 'Crear',
        'search' => 'Buscar',
        'filter' => 'Filtrar',
        'export' => 'Exportar',
        'print' => 'Imprimir',
        'total' => 'Total',
        'subtotal' => 'Subtotal',
        'discount' => 'Descuento',
        'tax' => 'Impuesto',
        'tip' => 'Propina',
        'cash' => 'Efectivo',
        'card' => 'Tarjeta',
        'success' => 'Éxito',
        'error' => 'Error',
        'warning' => 'Advertencia',
        'info' => 'Información'
    ];
    
    $translation = $translations[$key] ?? $key;
    
    // Reemplazar variables
    foreach ($replace as $search => $replacement) {
        $translation = str_replace(':' . $search, $replacement, $translation);
    }
    
    return $translation;
}

/**
 * Debug - var_dump mejorado (solo en desarrollo)
 */
function dd(...$vars): void
{
    if (config('app.debug', false)) {
        echo '<div style="background: #f8f9fa; border: 1px solid #dee2e6; padding: 20px; margin: 10px; border-radius: 5px; font-family: monospace;">';
        echo '<h3 style="margin-top: 0; color: #495057;">Debug Output:</h3>';
        
        foreach ($vars as $var) {
            echo '<pre style="background: white; padding: 10px; border-radius: 3px; overflow-x: auto;">';
            var_dump($var);
            echo '</pre>';
        }
        
        echo '</div>';
    }
    
    die();
}

/**
 * Generar número de orden único
 */
function generateOrderNumber(int $branchId): string
{
    $date = date('ymd');
    $branch = str_pad($branchId, 2, '0', STR_PAD_LEFT);
    $random = rand(100, 999);
    
    return "ORD-{$date}-{$branch}-{$random}";
}

/**
 * Generar número de turno único
 */
function generateShiftNumber(int $branchId, int $userId): string
{
    $date = date('ymd');
    $branch = str_pad($branchId, 2, '0', STR_PAD_LEFT);
    $user = str_pad($userId, 3, '0', STR_PAD_LEFT);
    $time = date('Hi');
    
    return "SHF-{$date}-{$branch}-{$user}-{$time}";
}

/**
 * Calcular impuesto
 */
function calculateTax(float $amount, float $rate = null): float
{
    if ($rate === null) {
        $rate = config('pos.default_tax_rate', 0.16);
    }
    
    return round($amount * $rate, 2);
}

/**
 * Calcular propina sugerida
 */
function calculateSuggestedTip(float $amount, array $percentages = [10, 15, 20]): array
{
    $tips = [];
    
    foreach ($percentages as $percentage) {
        $tips[$percentage] = round($amount * ($percentage / 100), 2);
    }
    
    return $tips;
}

/**
 * Convertir minutos a formato H:i
 */
function minutesToTime(int $minutes): string
{
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;
    
    return sprintf('%02d:%02d', $hours, $mins);
}

/**
 * Convertir tiempo H:i a minutos
 */
function timeToMinutes(string $time): int
{
    list($hours, $minutes) = explode(':', $time);
    return ($hours * 60) + $minutes;
}

/**
 * Verificar si está en horario de operación
 */
function isOperatingHours(array $operatingHours = null): bool
{
    if (!$operatingHours) {
        $branch = currentBranch();
        $operatingHours = $branch['operating_hours'] ?? null;
    }
    
    if (!$operatingHours) {
        return true; // Si no hay horarios definidos, asumimos que está abierto
    }
    
    $currentDay = date('w'); // 0 = domingo, 6 = sábado
    $currentTime = date('H:i');
    
    $daySchedule = $operatingHours[$currentDay] ?? null;
    
    if (!$daySchedule || !$daySchedule['open']) {
        return false;
    }
    
    $openTime = $daySchedule['open_time'] ?? '00:00';
    $closeTime = $daySchedule['close_time'] ?? '23:59';
    
    return $currentTime >= $openTime && $currentTime <= $closeTime;
}

/**
 * Obtener días de la semana en español
 */
function getDaysOfWeek(): array
{
    return [
        0 => 'Domingo',
        1 => 'Lunes',
        2 => 'Martes',
        3 => 'Miércoles',
        4 => 'Jueves',
        5 => 'Viernes',
        6 => 'Sábado'
    ];
}

/**
 * Obtener meses en español
 */
function getMonths(): array
{
    return [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
    ];
}

/**
 * Validar que el archivo subido sea una imagen
 */
function isValidImage(array $file): bool
{
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize = config('uploads.max_size', 10485760); // 10MB
    
    return in_array($file['type'], $allowedTypes) && 
           $file['size'] <= $maxSize && 
           $file['error'] === UPLOAD_ERR_OK;
}

/**
 * Generar thumbnail de imagen
 */
function generateThumbnail(string $imagePath, int $width = 300, int $height = 300): string
{
    $pathInfo = pathinfo($imagePath);
    $thumbnailPath = $pathInfo['dirname'] . '/thumb_' . $pathInfo['basename'];
    
    if (file_exists($thumbnailPath)) {
        return $thumbnailPath;
    }
    
    $imageType = exif_imagetype($imagePath);
    
    switch ($imageType) {
        case IMAGETYPE_JPEG:
            $source = imagecreatefromjpeg($imagePath);
            break;
        case IMAGETYPE_PNG:
            $source = imagecreatefrompng($imagePath);
            break;
        case IMAGETYPE_GIF:
            $source = imagecreatefromgif($imagePath);
            break;
        default:
            return $imagePath; // Retornar imagen original si no se puede procesar
    }
    
    $originalWidth = imagesx($source);
    $originalHeight = imagesy($source);
    
    // Calcular dimensiones manteniendo proporción
    $ratio = min($width / $originalWidth, $height / $originalHeight);
    $newWidth = round($originalWidth * $ratio);
    $newHeight = round($originalHeight * $ratio);
    
    $thumbnail = imagecreatetruecolor($newWidth, $newHeight);
    
    // Preservar transparencia para PNG
    if ($imageType === IMAGETYPE_PNG) {
        imagealphablending($thumbnail, false);
        imagesavealpha($thumbnail, true);
    }
    
    imagecopyresampled($thumbnail, $source, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);
    
    // Guardar thumbnail
    switch ($imageType) {
        case IMAGETYPE_JPEG:
            imagejpeg($thumbnail, $thumbnailPath, 85);
            break;
        case IMAGETYPE_PNG:
            imagepng($thumbnail, $thumbnailPath, 8);
            break;
        case IMAGETYPE_GIF:
            imagegif($thumbnail, $thumbnailPath);
            break;
    }
    
    imagedestroy($source);
    imagedestroy($thumbnail);
    
    return $thumbnailPath;
}

/**
 * Enviar respuesta JSON
 */
function jsonResponse(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * Obtener configuración de la base de datos actual
 */
function getDatabaseConfig(): array
{
    $config = config('database.connections.' . config('database.default'));
    
    // Ocultar contraseña para seguridad
    $config['password'] = str_repeat('*', strlen($config['password']));
    
    return $config;
}

/**
 * Verificar conexión a la base de datos
 */
function testDatabaseConnection(): bool
{
    try {
        $db = Database::getInstance();
        $db->query("SELECT 1");
        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Limpiar caché del sistema
 */
function clearSystemCache(): bool
{
    $cacheDir = STORAGE_PATH . '/cache';
    
    if (!is_dir($cacheDir)) {
        return true;
    }
    
    $files = glob($cacheDir . '/*');
    
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
    
    return true;
}

/**
 * Obtener información del sistema
 */
function getSystemInfo(): array
{
    return [
        'app_name' => config('app.name'),
        'app_version' => config('app.version'),
        'app_env' => config('app.env'),
        'php_version' => PHP_VERSION,
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'database_connection' => testDatabaseConnection() ? 'OK' : 'Failed',
        'memory_usage' => formatBytes(memory_get_usage(true)),
        'memory_peak' => formatBytes(memory_get_peak_usage(true)),
        'disk_free_space' => formatBytes(disk_free_space('/')),
        'server_time' => date('Y-m-d H:i:s'),
        'timezone' => date_default_timezone_get(),
        'session_status' => session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive'
    ];
}

/**
 * Formatear estado de orden
 */
function formatOrderStatus(string $status): array
{
    $statuses = [
        'draft' => ['label' => 'Borrador', 'class' => 'badge-secondary'],
        'open' => ['label' => 'Abierta', 'class' => 'badge-primary'],
        'sent' => ['label' => 'Enviada', 'class' => 'badge-info'],
        'preparing' => ['label' => 'Preparando', 'class' => 'badge-warning'],
        'ready' => ['label' => 'Lista', 'class' => 'badge-success'],
        'delivered' => ['label' => 'Entregada', 'class' => 'badge-success'],
        'paid' => ['label' => 'Pagada', 'class' => 'badge-success'],
        'cancelled' => ['label' => 'Cancelada', 'class' => 'badge-danger']
    ];
    
    return $statuses[$status] ?? ['label' => ucfirst($status), 'class' => 'badge-secondary'];
}

/**
 * Formatear método de pago
 */
function formatPaymentMethod(string $method): string
{
    $methods = [
        'cash' => 'Efectivo',
        'card' => 'Tarjeta',
        'transfer' => 'Transferencia',
        'uber' => 'Uber Eats',
        'rappi' => 'Rappi',
        'didi' => 'DiDi Food',
        'credit' => 'Crédito'
    ];
    
    return $methods[$method] ?? ucfirst($method);
}

/**
 * Generar código QR para mesa
 */
function generateTableQR(int $tableId): string
{
    $branch = currentBranch();
    $baseUrl = config('app.url');
    $qrUrl = "{$baseUrl}/qr/table-{$branch['id']}-{$tableId}";
    
    return $qrUrl;
}

/**
 * Validar horario de funcionamiento
 */
function validateBusinessHours(): bool
{
    $currentHour = (int) date('H');
    $businessStart = config('pos.business_hours.start', 6);
    $businessEnd = config('pos.business_hours.end', 23);
    
    return $currentHour >= $businessStart && $currentHour <= $businessEnd;
}

/**
 * Calcular tiempo de preparación estimado
 */
function calculatePreparationTime(array $orderItems): int
{
    $totalTime = 0;
    $maxTime = 0;
    
    foreach ($orderItems as $item) {
        $itemTime = $item['preparation_time'] ?? 15; // 15 minutos por defecto
        $quantity = $item['quantity'] ?? 1;
        
        // Tiempo total acumulativo
        $totalTime += ($itemTime * $quantity);
        
        // Tiempo máximo individual
        $maxTime = max($maxTime, $itemTime);
    }
    
    // Usar el mayor entre tiempo máximo individual o promedio del total
    return max($maxTime, round($totalTime / count($orderItems)));
}

/**
 * Generar contraseña segura
 */
function generateSecurePassword(int $length = 12): string
{
    $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $lowercase = 'abcdefghijklmnopqrstuvwxyz';
    $numbers = '0123456789';
    $symbols = '!@#$%^&*()_+-=[]{}|;:,.<>?';
    
    $all = $uppercase . $lowercase . $numbers . $symbols;
    
    $password = '';
    
    // Asegurar al menos un carácter de cada tipo
    $password .= $uppercase[rand(0, strlen($uppercase) - 1)];
    $password .= $lowercase[rand(0, strlen($lowercase) - 1)];
    $password .= $numbers[rand(0, strlen($numbers) - 1)];
    $password .= $symbols[rand(0, strlen($symbols) - 1)];
    
    // Completar con caracteres aleatorios
    for ($i = 4; $i < $length; $i++) {
        $password .= $all[rand(0, strlen($all) - 1)];
    }
    
    // Mezclar la contraseña
    return str_shuffle($password);
}
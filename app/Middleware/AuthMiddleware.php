<?php

/**
 * ROBS - Middleware de Autenticación
 * 
 * Verifica que el usuario esté autenticado antes de acceder a rutas protegidas
 */
class AuthMiddleware
{
    /**
     * Manejar la verificación de autenticación
     */
    public function handle(): void
    {
        // Verificar si hay una sesión activa
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Verificar si el usuario está autenticado
        if (!$this->isAuthenticated()) {
            $this->handleUnauthenticated();
            return;
        }
        
        // Verificar si la sesión ha expirado
        if ($this->isSessionExpired()) {
            $this->handleSessionExpired();
            return;
        }
        
        // Actualizar último acceso
        $this->updateLastActivity();
        
        // Registrar acceso en logs de auditoría
        $this->logAccess();
    }
    
    /**
     * Verificar si el usuario está autenticado
     */
    private function isAuthenticated(): bool
    {
        return isset($_SESSION['user']) && 
               !empty($_SESSION['user']) && 
               isset($_SESSION['user']['id']) &&
               isset($_SESSION['authenticated']) &&
               $_SESSION['authenticated'] === true;
    }
    
    /**
     * Verificar si la sesión ha expirado
     */
    private function isSessionExpired(): bool
    {
        if (!isset($_SESSION['last_activity'])) {
            return true;
        }
        
        $sessionLifetime = config('app.session.lifetime', 480) * 60; // Convertir minutos a segundos
        $timeSinceLastActivity = time() - $_SESSION['last_activity'];
        
        return $timeSinceLastActivity > $sessionLifetime;
    }
    
    /**
     * Manejar usuario no autenticado
     */
    private function handleUnauthenticated(): void
    {
        // Limpiar cualquier sesión existente
        $this->clearSession();
        
        // Si es una petición AJAX, devolver respuesta JSON
        if ($this->isAjaxRequest()) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Sesión expirada. Por favor inicia sesión nuevamente.',
                'redirect' => '/login'
            ], 401);
            return;
        }
        
        // Guardar la URL solicitada para redireccionar después del login
        $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'];
        
        // Redireccionar al login
        header('Location: /login');
        exit;
    }
    
    /**
     * Manejar sesión expirada
     */
    private function handleSessionExpired(): void
    {
        // Registrar logout por expiración
        $this->logSessionExpired();
        
        // Limpiar sesión
        $this->clearSession();
        
        // Si es una petición AJAX, devolver respuesta JSON
        if ($this->isAjaxRequest()) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Tu sesión ha expirado. Por favor inicia sesión nuevamente.',
                'redirect' => '/login'
            ], 401);
            return;
        }
        
        // Redireccionar al login con mensaje
        $_SESSION['flash_message'] = [
            'type' => 'warning',
            'message' => 'Tu sesión ha expirado. Por favor inicia sesión nuevamente.'
        ];
        
        header('Location: /login');
        exit;
    }
    
    /**
     * Actualizar último acceso del usuario
     */
    private function updateLastActivity(): void
    {
        $_SESSION['last_activity'] = time();
        
        // Actualizar también en la base de datos periódicamente (cada 5 minutos)
        if (!isset($_SESSION['last_db_update']) || 
            (time() - $_SESSION['last_db_update']) > 300) {
            
            $this->updateUserLastLogin();
            $_SESSION['last_db_update'] = time();
        }
    }
    
    /**
     * Actualizar último login del usuario en la base de datos
     */
    private function updateUserLastLogin(): void
    {
        try {
            if (isset($_SESSION['user']['id'])) {
                $db = Database::getInstance();
                $db->update(
                    'users', 
                    ['last_login' => date('Y-m-d H:i:s')],
                    'id = ?',
                    [$_SESSION['user']['id']]
                );
            }
        } catch (Exception $e) {
            // Log error pero no interrumpir el flujo
            logError("Error actualizando último login: " . $e->getMessage());
        }
    }
    
    /**
     * Registrar acceso en logs de auditoría
     */
    private function logAccess(): void
    {
        try {
            // Solo loggear accesos importantes, no todas las peticiones
            $importantRoutes = ['/dashboard', '/orders', '/reports', '/users', '/products'];
            $currentRoute = $_SERVER['REQUEST_URI'];
            
            foreach ($importantRoutes as $route) {
                if (strpos($currentRoute, $route) === 0) {
                    $this->insertAuditLog('ACCESS', 'system', 0, [
                        'route' => $currentRoute,
                        'method' => $_SERVER['REQUEST_METHOD'],
                        'ip' => $this->getClientIp(),
                        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
                    ]);
                    break;
                }
            }
        } catch (Exception $e) {
            // Log error pero no interrumpir el flujo
            logError("Error en log de auditoría: " . $e->getMessage());
        }
    }
    
    /**
     * Registrar logout por expiración de sesión
     */
    private function logSessionExpired(): void
    {
        try {
            $this->insertAuditLog('SESSION_EXPIRED', 'users', $_SESSION['user']['id'] ?? 0, [
                'last_activity' => $_SESSION['last_activity'] ?? time(),
                'ip' => $this->getClientIp()
            ]);
        } catch (Exception $e) {
            logError("Error registrando expiración de sesión: " . $e->getMessage());
        }
    }
    
    /**
     * Insertar log de auditoría
     */
    private function insertAuditLog(string $action, string $table, int $recordId, array $data = []): void
    {
        try {
            $db = Database::getInstance();
            $db->insert('audit_logs', [
                'user_id' => $_SESSION['user']['id'] ?? 0,
                'action' => $action,
                'table_name' => $table,
                'record_id' => $recordId,
                'new_values' => json_encode($data),
                'ip_address' => $this->getClientIp(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
                'session_id' => session_id(),
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            logError("Error insertando audit log: " . $e->getMessage());
        }
    }
    
    /**
     * Limpiar datos de sesión
     */
    private function clearSession(): void
    {
        // Limpiar variables específicas del usuario
        unset($_SESSION['user']);
        unset($_SESSION['authenticated']);
        unset($_SESSION['last_activity']);
        unset($_SESSION['last_db_update']);
        unset($_SESSION['branch']);
        
        // Regenerar ID de sesión por seguridad
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }
    
    /**
     * Verificar si es una petición AJAX
     */
    private function isAjaxRequest(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Obtener IP real del cliente
     */
    private function getClientIp(): string
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
     * Enviar respuesta JSON
     */
    private function jsonResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Verificar si el usuario tiene un rol específico
     */
    public static function hasRole(string $role): bool
    {
        return isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === $role;
    }
    
    /**
     * Verificar si el usuario tiene alguno de los roles especificados
     */
    public static function hasAnyRole(array $roles): bool
    {
        return isset($_SESSION['user']['role']) && in_array($_SESSION['user']['role'], $roles);
    }
    
    /**
     * Obtener el usuario actual
     */
    public static function getCurrentUser(): ?array
    {
        return $_SESSION['user'] ?? null;
    }
    
    /**
     * Verificar si el usuario pertenece a una sucursal específica
     */
    public static function belongsToBranch(int $branchId): bool
    {
        $user = self::getCurrentUser();
        
        // El BOSS tiene acceso a todas las sucursales
        if ($user && $user['role'] === 'BOSS') {
            return true;
        }
        
        // Otros roles solo a su sucursal asignada
        return $user && isset($user['branch_id']) && $user['branch_id'] == $branchId;
    }
}
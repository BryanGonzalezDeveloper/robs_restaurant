<?php

/**
 * ROBS - Middleware de Roles
 * 
 * Verifica que el usuario tenga los permisos necesarios para acceder a una ruta específica
 * Uso: RoleMiddleware:BOSS,MANAGER o RoleMiddleware:CAJERO
 */
class RoleMiddleware
{
    private array $allowedRoles = [];
    private array $roleHierarchy = [
        'BOSS' => 4,
        'MANAGER' => 3,
        'CAJERO' => 2,
        'MESERO' => 1
    ];
    
    /**
     * Manejar la verificación de roles
     * 
     * @param string $roles Roles permitidos separados por coma (ej: "BOSS,MANAGER")
     */
    public function handle(string $roles = ''): void
    {
        // Verificar que hay una sesión activa
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Verificar que el usuario esté autenticado
        if (!$this->isAuthenticated()) {
            $this->handleUnauthorized('Usuario no autenticado');
            return;
        }
        
        // Parsear roles permitidos
        $this->parseAllowedRoles($roles);
        
        // Verificar rol del usuario
        if (!$this->hasRequiredRole()) {
            $this->handleForbidden();
            return;
        }
        
        // Verificar estado del usuario
        if (!$this->isUserActive()) {
            $this->handleInactiveUser();
            return;
        }
        
        // Verificar acceso a sucursal si aplica
        if (!$this->hasAccessToBranch()) {
            $this->handleBranchAccessDenied();
            return;
        }
        
        // Registrar acceso autorizado
        $this->logAuthorizedAccess();
    }
    
    /**
     * Verificar si el usuario está autenticado
     */
    private function isAuthenticated(): bool
    {
        return isset($_SESSION['user']) && 
               !empty($_SESSION['user']) && 
               isset($_SESSION['authenticated']) &&
               $_SESSION['authenticated'] === true;
    }
    
    /**
     * Parsear roles permitidos desde string
     */
    private function parseAllowedRoles(string $roles): void
    {
        if (empty($roles)) {
            $this->allowedRoles = [];
            return;
        }
        
        $this->allowedRoles = array_map('trim', explode(',', $roles));
    }
    
    /**
     * Verificar si el usuario tiene el rol requerido
     */
    private function hasRequiredRole(): bool
    {
        $userRole = $_SESSION['user']['role'] ?? null;
        
        if (!$userRole) {
            return false;
        }
        
        // Si no se especificaron roles, permitir acceso a usuarios autenticados
        if (empty($this->allowedRoles)) {
            return true;
        }
        
        // Verificar rol exacto
        if (in_array($userRole, $this->allowedRoles)) {
            return true;
        }
        
        // Verificar jerarquía de roles (opcional)
        return $this->hasHierarchicalAccess($userRole);
    }
    
    /**
     * Verificar acceso jerárquico de roles
     * El BOSS puede acceder a todo, MANAGER a roles inferiores, etc.
     */
    private function hasHierarchicalAccess(string $userRole): bool
    {
        $userLevel = $this->roleHierarchy[$userRole] ?? 0;
        
        foreach ($this->allowedRoles as $allowedRole) {
            $allowedLevel = $this->roleHierarchy[$allowedRole] ?? 0;
            
            // Si el nivel del usuario es mayor o igual al requerido, tiene acceso
            if ($userLevel >= $allowedLevel) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Verificar si el usuario está activo
     */
    private function isUserActive(): bool
    {
        try {
            $userId = $_SESSION['user']['id'] ?? null;
            if (!$userId) {
                return false;
            }
            
            $db = Database::getInstance();
            $user = $db->fetchOne(
                "SELECT is_active, locked_until FROM users WHERE id = ?", 
                [$userId]
            );
            
            if (!$user) {
                return false;
            }
            
            // Verificar si el usuario está activo
            if (!$user['is_active']) {
                return false;
            }
            
            // Verificar si el usuario está bloqueado temporalmente
            if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
                return false;
            }
            
            return true;
            
        } catch (Exception $e) {
            logError("Error verificando estado del usuario: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verificar acceso a sucursal
     */
    private function hasAccessToBranch(): bool
    {
        $userRole = $_SESSION['user']['role'] ?? null;
        $userBranchId = $_SESSION['user']['branch_id'] ?? null;
        
        // El BOSS tiene acceso a todas las sucursales
        if ($userRole === 'BOSS') {
            return true;
        }
        
        // Obtener ID de sucursal desde la URL si está presente
        $requestedBranchId = $this->extractBranchIdFromUrl();
        
        // Si no se especifica sucursal en la URL, permitir acceso
        if (!$requestedBranchId) {
            return true;
        }
        
        // Verificar que el usuario tenga acceso a la sucursal solicitada
        return $userBranchId && $userBranchId == $requestedBranchId;
    }
    
    /**
     * Extraer ID de sucursal desde la URL
     */
    private function extractBranchIdFromUrl(): ?int
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        
        // Buscar patrones como /branches/123 o /branch/123
        if (preg_match('/\/branch(?:es)?\/(\d+)/', $uri, $matches)) {
            return (int) $matches[1];
        }
        
        return null;
    }
    
    /**
     * Manejar usuario no autorizado (401)
     */
    private function handleUnauthorized(string $reason = ''): void
    {
        $this->logUnauthorizedAccess($reason);
        
        if ($this->isAjaxRequest()) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Acceso no autorizado. Por favor inicia sesión.',
                'redirect' => '/login'
            ], 401);
            return;
        }
        
        header('Location: /login');
        exit;
    }
    
    /**
     * Manejar acceso prohibido por rol (403)
     */
    private function handleForbidden(): void
    {
        $this->logForbiddenAccess();
        
        if ($this->isAjaxRequest()) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'No tienes permisos para acceder a esta sección.'
            ], 403);
            return;
        }
        
        http_response_code(403);
        include VIEWS_PATH . '/errors/403.php';
        exit;
    }
    
    /**
     * Manejar usuario inactivo
     */
    private function handleInactiveUser(): void
    {
        $this->logInactiveUserAccess();
        
        // Limpiar sesión
        session_destroy();
        
        if ($this->isAjaxRequest()) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Tu cuenta ha sido desactivada. Contacta al administrador.',
                'redirect' => '/login'
            ], 403);
            return;
        }
        
        $_SESSION['flash_message'] = [
            'type' => 'error',
            'message' => 'Tu cuenta ha sido desactivada. Contacta al administrador.'
        ];
        
        header('Location: /login');
        exit;
    }
    
    /**
     * Manejar acceso denegado a sucursal
     */
    private function handleBranchAccessDenied(): void
    {
        $this->logBranchAccessDenied();
        
        if ($this->isAjaxRequest()) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'No tienes acceso a esta sucursal.'
            ], 403);
            return;
        }
        
        $_SESSION['flash_message'] = [
            'type' => 'warning',
            'message' => 'No tienes acceso a esta sucursal.'
        ];
        
        header('Location: /dashboard');
        exit;
    }
    
    /**
     * Registrar acceso autorizado
     */
    private function logAuthorizedAccess(): void
    {
        try {
            // Solo loggear accesos a rutas administrativas importantes
            $adminRoutes = ['/users', '/branches', '/config', '/reports/comparative', '/analytics'];
            $currentRoute = $_SERVER['REQUEST_URI'] ?? '';
            
            foreach ($adminRoutes as $route) {
                if (strpos($currentRoute, $route) === 0) {
                    $this->insertAuditLog('AUTHORIZED_ACCESS', [
                        'route' => $currentRoute,
                        'required_roles' => $this->allowedRoles,
                        'user_role' => $_SESSION['user']['role'] ?? 'unknown'
                    ]);
                    break;
                }
            }
        } catch (Exception $e) {
            logError("Error en log de acceso autorizado: " . $e->getMessage());
        }
    }
    
    /**
     * Registrar acceso no autorizado
     */
    private function logUnauthorizedAccess(string $reason = ''): void
    {
        try {
            $this->insertAuditLog('UNAUTHORIZED_ACCESS', [
                'route' => $_SERVER['REQUEST_URI'] ?? '',
                'reason' => $reason,
                'required_roles' => $this->allowedRoles
            ]);
        } catch (Exception $e) {
            logError("Error en log de acceso no autorizado: " . $e->getMessage());
        }
    }
    
    /**
     * Registrar acceso prohibido por rol
     */
    private function logForbiddenAccess(): void
    {
        try {
            $this->insertAuditLog('FORBIDDEN_ACCESS', [
                'route' => $_SERVER['REQUEST_URI'] ?? '',
                'user_role' => $_SESSION['user']['role'] ?? 'unknown',
                'required_roles' => $this->allowedRoles
            ]);
        } catch (Exception $e) {
            logError("Error en log de acceso prohibido: " . $e->getMessage());
        }
    }
    
    /**
     * Registrar intento de acceso con usuario inactivo
     */
    private function logInactiveUserAccess(): void
    {
        try {
            $this->insertAuditLog('INACTIVE_USER_ACCESS', [
                'route' => $_SERVER['REQUEST_URI'] ?? '',
                'user_id' => $_SESSION['user']['id'] ?? 0
            ]);
        } catch (Exception $e) {
            logError("Error en log de usuario inactivo: " . $e->getMessage());
        }
    }
    
    /**
     * Registrar acceso denegado a sucursal
     */
    private function logBranchAccessDenied(): void
    {
        try {
            $requestedBranch = $this->extractBranchIdFromUrl();
            $this->insertAuditLog('BRANCH_ACCESS_DENIED', [
                'route' => $_SERVER['REQUEST_URI'] ?? '',
                'requested_branch' => $requestedBranch,
                'user_branch' => $_SESSION['user']['branch_id'] ?? null
            ]);
        } catch (Exception $e) {
            logError("Error en log de acceso a sucursal: " . $e->getMessage());
        }
    }
    
    /**
     * Insertar log de auditoría
     */
    private function insertAuditLog(string $action, array $data = []): void
    {
        try {
            $db = Database::getInstance();
            $db->insert('audit_logs', [
                'user_id' => $_SESSION['user']['id'] ?? 0,
                'action' => $action,
                'table_name' => 'system',
                'record_id' => 0,
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
     * Verificar si es una petición AJAX
     */
    private function isAjaxRequest(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Obtener IP del cliente
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
     * Métodos estáticos para usar en controladores
     */
    
    /**
     * Verificar si el usuario actual tiene un rol específico
     */
    public static function checkRole(string $role): bool
    {
        return isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === $role;
    }
    
    /**
     * Verificar si el usuario actual tiene alguno de los roles
     */
    public static function checkAnyRole(array $roles): bool
    {
        return isset($_SESSION['user']['role']) && in_array($_SESSION['user']['role'], $roles);
    }
    
    /**
     * Obtener nivel jerárquico del rol actual
     */
    public static function getCurrentRoleLevel(): int
    {
        $role = $_SESSION['user']['role'] ?? '';
        $hierarchy = [
            'BOSS' => 4,
            'MANAGER' => 3,
            'CAJERO' => 2,
            'MESERO' => 1
        ];
        
        return $hierarchy[$role] ?? 0;
    }
}
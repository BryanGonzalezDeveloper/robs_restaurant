<?php

/**
 * ROBS - Modelo Role
 * 
 * Maneja la lógica de roles y permisos del sistema
 */
class Role extends Model
{
    protected string $table = 'roles';
    protected string $primaryKey = 'id';
    protected bool $timestamps = true;
    
    protected array $fillable = [
        'name',
        'display_name',
        'description',
        'permissions',
        'is_active'
    ];
    
    protected array $guarded = [
        'id',
        'created_at',
        'updated_at'
    ];
    
    protected array $casts = [
        'id' => 'int',
        'permissions' => 'json',
        'is_active' => 'bool'
    ];
    
    // Constantes para los roles del sistema
    const BOSS = 'BOSS';
    const MANAGER = 'MANAGER';
    const CAJERO = 'CAJERO';
    const MESERO = 'MESERO';
    
    // Jerarquía de roles (mayor número = mayor autoridad)
    const HIERARCHY = [
        self::BOSS => 4,
        self::MANAGER => 3,
        self::CAJERO => 2,
        self::MESERO => 1
    ];
    
    /**
     * Buscar rol por nombre
     */
    public static function findByName(string $name): ?Role
    {
        return self::where('name', $name)->first();
    }
    
    /**
     * Obtener todos los roles activos
     */
    public static function getActive(): array
    {
        return self::where('is_active', true)
                   ->orderBy('name', 'asc')
                   ->get();
    }
    
    /**
     * Obtener roles para un select/dropdown
     */
    public static function getForSelect(): array
    {
        $roles = self::getActive();
        $options = [];
        
        foreach ($roles as $role) {
            $options[$role->getAttribute('id')] = $role->getAttribute('display_name');
        }
        
        return $options;
    }
    
    /**
     * Obtener roles que un usuario puede asignar según su rol
     */
    public static function getAssignableRoles(string $userRole): array
    {
        $userLevel = self::HIERARCHY[$userRole] ?? 0;
        $assignableRoles = [];
        
        $allRoles = self::getActive();
        
        foreach ($allRoles as $role) {
            $roleName = $role->getAttribute('name');
            $roleLevel = self::HIERARCHY[$roleName] ?? 0;
            
            // Un usuario solo puede asignar roles de nivel igual o inferior
            if ($userLevel >= $roleLevel) {
                // BOSS no puede asignar otro BOSS
                if ($userRole === self::BOSS && $roleName === self::BOSS) {
                    continue;
                }
                
                $assignableRoles[] = $role;
            }
        }
        
        return $assignableRoles;
    }
    
    /**
     * Verificar si un rol tiene un permiso específico
     */
    public function hasPermission(string $module, string $action): bool
    {
        $permissions = $this->getAttribute('permissions');
        
        if (!is_array($permissions)) {
            return false;
        }
        
        // Verificar si el módulo existe en los permisos
        if (!isset($permissions[$module])) {
            return false;
        }
        
        $modulePermissions = $permissions[$module];
        
        // Si es "all", tiene todos los permisos
        if ($modulePermissions === 'all' || (is_array($modulePermissions) && in_array('all', $modulePermissions))) {
            return true;
        }
        
        // Verificar si tiene el permiso específico
        if (is_array($modulePermissions)) {
            return in_array($action, $modulePermissions);
        }
        
        return false;
    }
    
    /**
     * Verificar si puede acceder a un módulo completo
     */
    public function canAccessModule(string $module): bool
    {
        $permissions = $this->getAttribute('permissions');
        
        if (!is_array($permissions)) {
            return false;
        }
        
        return isset($permissions[$module]);
    }
    
    /**
     * Obtener todos los permisos de un módulo
     */
    public function getModulePermissions(string $module): array
    {
        $permissions = $this->getAttribute('permissions');
        
        if (!is_array($permissions) || !isset($permissions[$module])) {
            return [];
        }
        
        $modulePermissions = $permissions[$module];
        
        if ($modulePermissions === 'all') {
            return ['all'];
        }
        
        return is_array($modulePermissions) ? $modulePermissions : [];
    }
    
    /**
     * Obtener usuarios con este rol
     */
    public function getUsers(): array
    {
        $roleId = $this->getAttribute('id');
        
        return User::where('role_id', $roleId)
                   ->where('is_active', true)
                   ->get();
    }
    
    /**
     * Contar usuarios con este rol
     */
    public function countUsers(): int
    {
        $db = Database::getInstance();
        $roleId = $this->getAttribute('id');
        
        return $db->count('users', 'role_id = ? AND is_active = 1', [$roleId]);
    }
    
    /**
     * Verificar si el rol se puede eliminar
     */
    public function canBeDeleted(): bool
    {
        // No se pueden eliminar los roles base del sistema
        $systemRoles = [self::BOSS, self::MANAGER, self::CAJERO, self::MESERO];
        $roleName = $this->getAttribute('name');
        
        if (in_array($roleName, $systemRoles)) {
            return false;
        }
        
        // No se puede eliminar si tiene usuarios asignados
        return $this->countUsers() === 0;
    }
    
    /**
     * Obtener nivel jerárquico del rol
     */
    public function getHierarchyLevel(): int
    {
        $roleName = $this->getAttribute('name');
        return self::HIERARCHY[$roleName] ?? 0;
    }
    
    /**
     * Verificar si es un rol superior a otro
     */
    public function isSuperiorTo(Role $otherRole): bool
    {
        return $this->getHierarchyLevel() > $otherRole->getHierarchyLevel();
    }
    
    /**
     * Verificar si es un rol inferior a otro
     */
    public function isInferiorTo(Role $otherRole): bool
    {
        return $this->getHierarchyLevel() < $otherRole->getHierarchyLevel();
    }
    
    /**
     * Obtener permisos por defecto para cada rol
     */
    public static function getDefaultPermissions(): array
    {
        return [
            self::BOSS => [
                'users' => ['create', 'read', 'update', 'delete'],
                'branches' => ['create', 'read', 'update', 'delete'],
                'products' => ['create', 'read', 'update', 'delete'],
                'orders' => ['create', 'read', 'update', 'delete'],
                'reports' => 'all',
                'financial' => 'all',
                'settings' => 'all',
                'discounts' => ['create', 'read', 'update', 'delete', 'apply'],
                'tips' => 'all',
                'inventory' => 'all'
            ],
            
            self::MANAGER => [
                'users' => ['create', 'read', 'update'],
                'products' => ['create', 'read', 'update', 'delete'],
                'orders' => ['create', 'read', 'update'],
                'reports' => ['daily', 'weekly', 'products', 'waiters'],
                'financial' => ['daily', 'shifts'],
                'settings' => ['branch', 'discounts', 'tips'],
                'discounts' => ['create', 'read', 'update', 'delete', 'apply'],
                'tips' => ['read', 'update', 'distribute'],
                'inventory' => ['read', 'update']
            ],
            
            self::CAJERO => [
                'orders' => ['create', 'read', 'update'],
                'payments' => ['create', 'read'],
                'shifts' => ['open', 'close'],
                'reports' => ['shift', 'daily'],
                'discounts' => ['apply_with_auth'],
                'tips' => ['add'],
                'financial' => ['cash_movements']
            ],
            
            self::MESERO => [
                'orders' => ['create', 'read', 'update_own'],
                'tables' => ['read', 'update'],
                'tips' => ['view_own'],
                'surveys' => ['manage'],
                'products' => ['read']
            ]
        ];
    }
    
    /**
     * Crear roles por defecto del sistema
     */
    public static function createDefaultRoles(): array
    {
        $defaultRoles = [
            [
                'name' => self::BOSS,
                'display_name' => 'Dueño',
                'description' => 'Acceso completo al sistema',
                'permissions' => self::getDefaultPermissions()[self::BOSS]
            ],
            [
                'name' => self::MANAGER,
                'display_name' => 'Gerente',
                'description' => 'Administración de sucursal',
                'permissions' => self::getDefaultPermissions()[self::MANAGER]
            ],
            [
                'name' => self::CAJERO,
                'display_name' => 'Cajero',
                'description' => 'Operaciones de caja y pagos',
                'permissions' => self::getDefaultPermissions()[self::CAJERO]
            ],
            [
                'name' => self::MESERO,
                'display_name' => 'Mesero',
                'description' => 'Toma de órdenes y servicio',
                'permissions' => self::getDefaultPermissions()[self::MESERO]
            ]
        ];
        
        $createdRoles = [];
        
        foreach ($defaultRoles as $roleData) {
            // Verificar si el rol ya existe
            $existingRole = self::findByName($roleData['name']);
            
            if (!$existingRole) {
                try {
                    $role = self::create($roleData);
                    $createdRoles[] = $role;
                } catch (Exception $e) {
                    logError("Error creando rol {$roleData['name']}: " . $e->getMessage());
                }
            } else {
                $createdRoles[] = $existingRole;
            }
        }
        
        return $createdRoles;
    }
    
    /**
     * Obtener descripción de permisos en formato legible
     */
    public function getPermissionsDescription(): string
    {
        $permissions = $this->getAttribute('permissions');
        
        if (!is_array($permissions)) {
            return 'Sin permisos definidos';
        }
        
        $descriptions = [];
        
        foreach ($permissions as $module => $actions) {
            $moduleName = ucfirst($module);
            
            if ($actions === 'all') {
                $descriptions[] = "{$moduleName}: Acceso completo";
            } elseif (is_array($actions)) {
                $actionsList = implode(', ', $actions);
                $descriptions[] = "{$moduleName}: {$actionsList}";
            }
        }
        
        return implode(' | ', $descriptions);
    }
    
    /**
     * Verificar si un usuario con este rol puede gestionar usuarios
     */
    public function canManageUsers(): bool
    {
        return $this->hasPermission('users', 'create') || 
               $this->hasPermission('users', 'update') || 
               $this->hasPermission('users', 'delete');
    }
    
    /**
     * Verificar si un usuario con este rol puede ver reportes
     */
    public function canViewReports(): bool
    {
        return $this->canAccessModule('reports');
    }
    
    /**
     * Verificar si un usuario con este rol puede manejar finanzas
     */
    public function canManageFinances(): bool
    {
        return $this->canAccessModule('financial');
    }
    
    /**
     * Validar estructura de permisos
     */
    public function validatePermissions(): array
    {
        $permissions = $this->getAttribute('permissions');
        $errors = [];
        
        if (!is_array($permissions)) {
            $errors[] = 'Los permisos deben ser un array JSON válido';
            return $errors;
        }
        
        $validModules = [
            'users', 'branches', 'products', 'orders', 'reports', 
            'financial', 'settings', 'discounts', 'tips', 'inventory'
        ];
        
        $validActions = [
            'create', 'read', 'update', 'delete', 'all',
            'apply', 'apply_with_auth', 'view_own', 'update_own',
            'open', 'close', 'manage', 'distribute'
        ];
        
        foreach ($permissions as $module => $actions) {
            if (!in_array($module, $validModules)) {
                $errors[] = "Módulo '{$module}' no es válido";
                continue;
            }
            
            if ($actions === 'all') {
                continue; // 'all' es válido
            }
            
            if (is_array($actions)) {
                foreach ($actions as $action) {
                    if (!in_array($action, $validActions)) {
                        $errors[] = "Acción '{$action}' en módulo '{$module}' no es válida";
                    }
                }
            } else {
                $errors[] = "Permisos del módulo '{$module}' deben ser 'all' o un array de acciones";
            }
        }
        
        return $errors;
    }
    
    /**
     * Validar antes de guardar
     */
    public function save(): bool
    {
        // Validar estructura de permisos
        $errors = $this->validatePermissions();
        
        if (!empty($errors)) {
            throw new Exception('Errores en permisos: ' . implode(', ', $errors));
        }
        
        // Validar que el nombre del rol sea único
        $name = $this->getAttribute('name');
        if ($name) {
            $existingRole = self::findByName($name);
            if ($existingRole && $existingRole->getAttribute('id') !== $this->getAttribute('id')) {
                throw new Exception('El nombre del rol ya existe');
            }
        }
        
        return parent::save();
    }
    
    /**
     * Eliminar rol con validaciones
     */
    public function delete(): bool
    {
        if (!$this->canBeDeleted()) {
            throw new Exception('Este rol no se puede eliminar porque es un rol del sistema o tiene usuarios asignados');
        }
        
        return parent::delete();
    }
    
    /**
     * Obtener resumen del rol para mostrar en listas
     */
    public function getSummary(): array
    {
        return [
            'id' => $this->getAttribute('id'),
            'name' => $this->getAttribute('name'),
            'display_name' => $this->getAttribute('display_name'),
            'description' => $this->getAttribute('description'),
            'users_count' => $this->countUsers(),
            'hierarchy_level' => $this->getHierarchyLevel(),
            'can_be_deleted' => $this->canBeDeleted(),
            'is_active' => $this->getAttribute('is_active'),
            'permissions_summary' => $this->getPermissionsDescription()
        ];
    }
    
    /**
     * Clonar rol con nuevo nombre
     */
    public function cloneRole(string $newName, string $newDisplayName): Role
    {
        $data = $this->toArray();
        
        // Remover campos que no se deben clonar
        unset($data['id'], $data['created_at'], $data['updated_at']);
        
        // Establecer nuevos nombres
        $data['name'] = $newName;
        $data['display_name'] = $newDisplayName;
        $data['description'] = 'Copia de: ' . $this->getAttribute('description');
        
        return self::create($data);
    }
    
    /**
     * Actualizar permisos de un módulo específico
     */
    public function updateModulePermissions(string $module, $permissions): bool
    {
        $currentPermissions = $this->getAttribute('permissions') ?: [];
        
        if ($permissions === null || (is_array($permissions) && empty($permissions))) {
            unset($currentPermissions[$module]);
        } else {
            $currentPermissions[$module] = $permissions;
        }
        
        return $this->update(['permissions' => $currentPermissions]);
    }
    
    /**
     * Agregar permiso a un módulo
     */
    public function addPermission(string $module, string $action): bool
    {
        $permissions = $this->getAttribute('permissions') ?: [];
        
        if (!isset($permissions[$module])) {
            $permissions[$module] = [];
        }
        
        if ($permissions[$module] === 'all') {
            return true; // Ya tiene todos los permisos
        }
        
        if (!is_array($permissions[$module])) {
            $permissions[$module] = [];
        }
        
        if (!in_array($action, $permissions[$module])) {
            $permissions[$module][] = $action;
        }
        
        return $this->update(['permissions' => $permissions]);
    }
    
    /**
     * Remover permiso de un módulo
     */
    public function removePermission(string $module, string $action): bool
    {
        $permissions = $this->getAttribute('permissions') ?: [];
        
        if (!isset($permissions[$module]) || $permissions[$module] === 'all') {
            return true;
        }
        
        if (is_array($permissions[$module])) {
            $permissions[$module] = array_diff($permissions[$module], [$action]);
            
            // Si no quedan permisos, remover el módulo completo
            if (empty($permissions[$module])) {
                unset($permissions[$module]);
            }
        }
        
        return $this->update(['permissions' => $permissions]);
    }
    
    /**
     * Comparar permisos con otro rol
     */
    public function comparePermissions(Role $otherRole): array
    {
        $thisPermissions = $this->getAttribute('permissions') ?: [];
        $otherPermissions = $otherRole->getAttribute('permissions') ?: [];
        
        $comparison = [
            'only_in_this' => [],
            'only_in_other' => [],
            'common' => [],
            'different' => []
        ];
        
        $allModules = array_unique(array_merge(
            array_keys($thisPermissions),
            array_keys($otherPermissions)
        ));
        
        foreach ($allModules as $module) {
            $thisModulePerms = $thisPermissions[$module] ?? null;
            $otherModulePerms = $otherPermissions[$module] ?? null;
            
            if ($thisModulePerms && !$otherModulePerms) {
                $comparison['only_in_this'][$module] = $thisModulePerms;
            } elseif (!$thisModulePerms && $otherModulePerms) {
                $comparison['only_in_other'][$module] = $otherModulePerms;
            } elseif ($thisModulePerms && $otherModulePerms) {
                if ($thisModulePerms === $otherModulePerms) {
                    $comparison['common'][$module] = $thisModulePerms;
                } else {
                    $comparison['different'][$module] = [
                        'this' => $thisModulePerms,
                        'other' => $otherModulePerms
                    ];
                }
            }
        }
        
        return $comparison;
    }
    
    /**
     * Exportar rol a formato JSON para backup
     */
    public function exportToJson(): string
    {
        $data = [
            'name' => $this->getAttribute('name'),
            'display_name' => $this->getAttribute('display_name'),
            'description' => $this->getAttribute('description'),
            'permissions' => $this->getAttribute('permissions'),
            'exported_at' => date('Y-m-d H:i:s'),
            'exported_by' => $_SESSION['user']['username'] ?? 'system'
        ];
        
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * Importar rol desde JSON
     */
    public static function importFromJson(string $json): Role
    {
        $data = json_decode($json, true);
        
        if (!$data) {
            throw new Exception('JSON inválido');
        }
        
        $required = ['name', 'display_name', 'permissions'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new Exception("Campo requerido '{$field}' no encontrado en JSON");
            }
        }
        
        // Verificar si el rol ya existe
        $existingRole = self::findByName($data['name']);
        if ($existingRole) {
            throw new Exception("El rol '{$data['name']}' ya existe");
        }
        
        // Crear rol
        return self::create([
            'name' => $data['name'],
            'display_name' => $data['display_name'],
            'description' => $data['description'] ?? 'Importado desde JSON',
            'permissions' => $data['permissions']
        ]);
    }
    
    /**
     * Obtener matriz de permisos para tabla de comparación
     */
    public static function getPermissionsMatrix(): array
    {
        $roles = self::getActive();
        $allModules = [];
        $matrix = [];
        
        // Recopilar todos los módulos únicos
        foreach ($roles as $role) {
            $permissions = $role->getAttribute('permissions') ?: [];
            $allModules = array_merge($allModules, array_keys($permissions));
        }
        $allModules = array_unique($allModules);
        sort($allModules);
        
        // Construir matriz
        foreach ($roles as $role) {
            $roleName = $role->getAttribute('name');
            $permissions = $role->getAttribute('permissions') ?: [];
            
            $matrix[$roleName] = [
                'display_name' => $role->getAttribute('display_name'),
                'permissions' => []
            ];
            
            foreach ($allModules as $module) {
                $modulePerms = $permissions[$module] ?? null;
                
                if ($modulePerms === 'all') {
                    $matrix[$roleName]['permissions'][$module] = 'Todos';
                } elseif (is_array($modulePerms)) {
                    $matrix[$roleName]['permissions'][$module] = implode(', ', $modulePerms);
                } else {
                    $matrix[$roleName]['permissions'][$module] = 'Sin acceso';
                }
            }
        }
        
        return [
            'modules' => $allModules,
            'roles' => $matrix
        ];
    }
}
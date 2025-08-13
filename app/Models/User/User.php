<?php

/**
 * ROBS - Modelo User
 * 
 * Maneja la lógica de usuarios del sistema
 */
class User extends Model
{
    protected string $table = 'users';
    protected string $primaryKey = 'id';
    protected bool $timestamps = true;
    
    protected array $fillable = [
        'username',
        'email', 
        'first_name',
        'last_name',
        'employee_code',
        'role_id',
        'branch_id',
        'phone',
        'hire_date',
        'salary',
        'commission_rate',
        'is_active'
    ];
    
    protected array $guarded = [
        'id',
        'password_hash',
        'last_login',
        'failed_login_attempts',
        'locked_until',
        'password_changed_at',
        'created_at',
        'updated_at'
    ];
    
    protected array $hidden = [
        'password_hash'
    ];
    
    protected array $casts = [
        'id' => 'int',
        'role_id' => 'int',
        'branch_id' => 'int',
        'salary' => 'float',
        'commission_rate' => 'float',
        'is_active' => 'bool',
        'failed_login_attempts' => 'int',
        'hire_date' => 'date',
        'last_login' => 'datetime',
        'locked_until' => 'datetime',
        'password_changed_at' => 'datetime'
    ];
    
    /**
     * Buscar usuario por username
     */
    public static function findByUsername(string $username): ?User
    {
        return self::where('username', $username)->first();
    }
    
    /**
     * Buscar usuario por email
     */
    public static function findByEmail(string $email): ?User
    {
        return self::where('email', $email)->first();
    }
    
    /**
     * Buscar usuario por código de empleado
     */
    public static function findByEmployeeCode(string $code): ?User
    {
        return self::where('employee_code', $code)->first();
    }
    
    /**
     * Obtener usuarios activos
     */
    public static function getActive(): array
    {
        return self::where('is_active', true)->get();
    }
    
    /**
     * Obtener usuarios por sucursal
     */
    public static function getByBranch(int $branchId): array
    {
        return self::where('branch_id', $branchId)
                   ->where('is_active', true)
                   ->get();
    }
    
    /**
     * Obtener usuarios por rol
     */
    public static function getByRole(string $roleName): array
    {
        $db = Database::getInstance();
        
        $sql = "SELECT u.* FROM users u 
                INNER JOIN roles r ON u.role_id = r.id 
                WHERE r.name = ? AND u.is_active = 1";
        
        $results = $db->fetchAll($sql, [$roleName]);
        
        return array_map(fn($data) => (new self())->newFromDatabase($data), $results);
    }
    
    /**
     * Verificar contraseña
     */
    public function verifyPassword(string $password): bool
    {
        $hash = $this->getAttribute('password_hash');
        return password_verify($password, $hash);
    }
    
    /**
     * Establecer nueva contraseña
     */
    public function setPassword(string $password): bool
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        return $this->update([
            'password_hash' => $hash,
            'password_changed_at' => date('Y-m-d H:i:s'),
            'failed_login_attempts' => 0,
            'locked_until' => null
        ]);
    }
    
    /**
     * Incrementar intentos fallidos de login
     */
    public function incrementFailedAttempts(): bool
    {
        $attempts = $this->getAttribute('failed_login_attempts') + 1;
        $maxAttempts = config('app.security.max_login_attempts', 5);
        
        $data = ['failed_login_attempts' => $attempts];
        
        // Bloquear usuario si excede intentos máximos
        if ($attempts >= $maxAttempts) {
            $lockoutDuration = config('app.security.lockout_duration', 900); // 15 minutos
            $data['locked_until'] = date('Y-m-d H:i:s', time() + $lockoutDuration);
        }
        
        return $this->update($data);
    }
    
    /**
     * Resetear intentos fallidos de login
     */
    public function resetFailedAttempts(): bool
    {
        return $this->update([
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'last_login' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Verificar si el usuario está bloqueado
     */
    public function isLocked(): bool
    {
        $lockedUntil = $this->getAttribute('locked_until');
        
        if (!$lockedUntil) {
            return false;
        }
        
        return strtotime($lockedUntil) > time();
    }
    
    /**
     * Verificar si el usuario está activo
     */
    public function isActive(): bool
    {
        return $this->getAttribute('is_active') === true;
    }
    
    /**
     * Obtener información del rol
     */
    public function getRole(): ?Role
    {
        $roleId = $this->getAttribute('role_id');
        
        if (!$roleId) {
            return null;
        }
        
        return Role::find($roleId);
    }
    
    /**
     * Obtener nombre del rol
     */
    public function getRoleName(): string
    {
        $role = $this->getRole();
        return $role ? $role->getAttribute('name') : 'Sin Rol';
    }
    
    /**
     * Verificar si tiene un rol específico
     */
    public function hasRole(string $roleName): bool
    {
        return $this->getRoleName() === $roleName;
    }
    
    /**
     * Verificar si tiene alguno de los roles especificados
     */
    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->getRoleName(), $roles);
    }
    
    /**
     * Obtener información de la sucursal
     */
    public function getBranch(): ?array
    {
        $branchId = $this->getAttribute('branch_id');
        
        if (!$branchId) {
            return null;
        }
        
        $db = Database::getInstance();
        return $db->fetchOne("SELECT * FROM branches WHERE id = ?", [$branchId]);
    }
    
    /**
     * Obtener nombre completo del usuario
     */
    public function getFullName(): string
    {
        $firstName = $this->getAttribute('first_name');
        $lastName = $this->getAttribute('last_name');
        
        return trim($firstName . ' ' . $lastName);
    }
    
    /**
     * Obtener nombre para mostrar
     */
    public function getDisplayName(): string
    {
        $fullName = $this->getFullName();
        return !empty($fullName) ? $fullName : $this->getAttribute('username');
    }
    
    /**
     * Verificar si puede acceder a una sucursal específica
     */
    public function canAccessBranch(int $branchId): bool
    {
        // El BOSS puede acceder a todas las sucursales
        if ($this->hasRole('BOSS')) {
            return true;
        }
        
        // Otros roles solo a su sucursal asignada
        return $this->getAttribute('branch_id') == $branchId;
    }
    
    /**
     * Obtener estadísticas del usuario (para meseros)
     */
    public function getStats(string $period = 'today'): array
    {
        if (!$this->hasRole('MESERO')) {
            return [];
        }
        
        $db = Database::getInstance();
        $userId = $this->getAttribute('id');
        
        switch ($period) {
            case 'today':
                $dateCondition = "DATE(o.created_at) = CURDATE()";
                break;
            case 'week':
                $dateCondition = "YEARWEEK(o.created_at) = YEARWEEK(NOW())";
                break;
            case 'month':
                $dateCondition = "YEAR(o.created_at) = YEAR(NOW()) AND MONTH(o.created_at) = MONTH(NOW())";
                break;
            default:
                $dateCondition = "DATE(o.created_at) = CURDATE()";
        }
        
        // Estadísticas de órdenes
        $orderStats = $db->fetchOne("
            SELECT 
                COUNT(*) as total_orders,
                COALESCE(SUM(o.total), 0) as total_sales,
                COALESCE(AVG(o.total), 0) as average_ticket
            FROM orders o 
            WHERE o.waiter_id = ? AND {$dateCondition} AND o.status = 'paid'
        ", [$userId]);
        
        // Estadísticas de propinas
        $tipStats = $db->fetchOne("
            SELECT 
                COUNT(*) as tip_count,
                COALESCE(SUM(t.amount), 0) as total_tips,
                COALESCE(AVG(t.amount), 0) as average_tip
            FROM tips t 
            WHERE t.waiter_id = ? AND {$dateCondition}
        ", [$userId]);
        
        return [
            'orders' => [
                'total' => (int) $orderStats['total_orders'],
                'sales' => (float) $orderStats['total_sales'],
                'average_ticket' => (float) $orderStats['average_ticket']
            ],
            'tips' => [
                'count' => (int) $tipStats['tip_count'],
                'total' => (float) $tipStats['total_tips'],
                'average' => (float) $tipStats['average_tip']
            ]
        ];
    }
    
    /**
     * Crear usuario con validaciones
     */
    public static function createUser(array $data): array
    {
        // Validar datos requeridos
        $required = ['username', 'email', 'first_name', 'last_name', 'role_id'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'message' => "El campo {$field} es requerido"];
            }
        }
        
        // Validar unicidad de username
        if (self::findByUsername($data['username'])) {
            return ['success' => false, 'message' => 'El nombre de usuario ya existe'];
        }
        
        // Validar unicidad de email
        if (self::findByEmail($data['email'])) {
            return ['success' => false, 'message' => 'El email ya está registrado'];
        }
        
        // Validar unicidad de código de empleado si se proporciona
        if (!empty($data['employee_code']) && self::findByEmployeeCode($data['employee_code'])) {
            return ['success' => false, 'message' => 'El código de empleado ya existe'];
        }
        
        // Generar contraseña temporal si no se proporciona
        if (empty($data['password'])) {
            $data['password'] = generateSecurePassword(8);
        }
        
        // Hash de la contraseña
        $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        $data['password_changed_at'] = date('Y-m-d H:i:s');
        
        // Generar código de empleado si no se proporciona
        if (empty($data['employee_code'])) {
            $data['employee_code'] = self::generateEmployeeCode($data['role_id']);
        }
        
        // Remover password del array ya que usamos password_hash
        $tempPassword = $data['password'];
        unset($data['password']);
        
        try {
            $user = self::create($data);
            
            return [
                'success' => true, 
                'message' => 'Usuario creado exitosamente',
                'user' => $user,
                'temporary_password' => $tempPassword
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al crear usuario: ' . $e->getMessage()];
        }
    }
    
    /**
     * Generar código de empleado único
     */
    private static function generateEmployeeCode(int $roleId): string
    {
        $db = Database::getInstance();
        
        // Obtener prefijo del rol
        $role = $db->fetchOne("SELECT name FROM roles WHERE id = ?", [$roleId]);
        $prefix = substr($role['name'] ?? 'EMP', 0, 3);
        
        // Generar número secuencial
        $lastCode = $db->fetchColumn(
            "SELECT employee_code FROM users WHERE employee_code LIKE ? ORDER BY employee_code DESC LIMIT 1",
            [$prefix . '%']
        );
        
        if ($lastCode) {
            $number = (int) substr($lastCode, 3) + 1;
        } else {
            $number = 1;
        }
        
        return $prefix . str_pad($number, 3, '0', STR_PAD_LEFT);
    }
    
    /**
     * Validar datos antes de guardar
     */
    public function save(): bool
    {
        // Validar email
        $email = $this->getAttribute('email');
        if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Email inválido');
        }
        
        // Validar teléfono mexicano si se proporciona
        $phone = $this->getAttribute('phone');
        if ($phone && !isValidMexicanPhone($phone)) {
            throw new Exception('Formato de teléfono inválido');
        }
        
        return parent::save();
    }
    
    /**
     * Convertir a array para sesión (sin datos sensibles)
     */
    public function toSessionArray(): array
    {
        $role = $this->getRole();
        $branch = $this->getBranch();
        
        return [
            'id' => $this->getAttribute('id'),
            'username' => $this->getAttribute('username'),
            'email' => $this->getAttribute('email'),
            'first_name' => $this->getAttribute('first_name'),
            'last_name' => $this->getAttribute('last_name'),
            'full_name' => $this->getFullName(),
            'employee_code' => $this->getAttribute('employee_code'),
            'role' => $role ? $role->getAttribute('name') : null,
            'role_id' => $this->getAttribute('role_id'),
            'role_display' => $role ? $role->getAttribute('display_name') : null,
            'branch_id' => $this->getAttribute('branch_id'),
            'branch_name' => $branch ? $branch['name'] : null,
            'is_active' => $this->getAttribute('is_active'),
            'last_login' => $this->getAttribute('last_login')
        ];
    }
}
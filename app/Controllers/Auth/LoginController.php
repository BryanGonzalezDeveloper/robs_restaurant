<?php

// namespace App\Controllers\Auth;

// use App\Core\Controller;
// use App\Models\User\User;

/**
 * LoginController - Maneja autenticación siguiendo las rutas definidas en Routes.php
 */
class LoginController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->setLayout('auth'); // Usar layout de autenticación
    }

    /**
     * Mostrar formulario de login
     * Ruta: GET /login -> LoginController@showLoginForm
     */
    public function showLoginForm(): void
    {
        // Si ya está autenticado, redirigir al dashboard
        if ($this->isAuthenticated()) {
            $this->redirect('/dashboard');
            return;
        }

        $data = [
            'page_title' => 'Iniciar Sesión - ROBS',
            'error' => $_SESSION['login_error'] ?? null,
            'success' => $_SESSION['login_success'] ?? null,
            'old_input' => $_SESSION['old_input'] ?? [],
            'branches' => $this->getActiveBranches()
        ];

        // Limpiar mensajes de sesión
        unset($_SESSION['login_error'], $_SESSION['login_success'], $_SESSION['old_input']);

        $this->view('auth.login', $data);
    }

    /**
     * Procesar autenticación
     * Ruta: POST /login -> LoginController@authenticate
     */
    public function authenticate(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/login');
            return;
        }

        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $remember = isset($_POST['remember']);
        $branchId = intval($_POST['branch_id'] ?? 0);

        // Validaciones básicas
        $errors = $this->validateLoginInput($username, $password, $branchId);
        
        if (!empty($errors)) {
            $_SESSION['login_error'] = implode('<br>', $errors);
            $_SESSION['old_input'] = ['username' => $username, 'branch_id' => $branchId];
            $this->redirect('/login');
            return;
        }

        try {
            // Buscar usuario
            $user = User::findByUsername($username);
            
            if (!$user || !$user->verifyPassword($password)) {
                $this->handleFailedLogin($username, 'Credenciales incorrectas');
                return;
            }

            // Verificar si el usuario está activo
            if (!$user->isActive()) {
                $this->handleFailedLogin($username, 'Usuario inactivo. Contacte al administrador');
                return;
            }

            // Verificar si está bloqueado
            if ($user->isLocked()) {
                $this->handleFailedLogin($username, 'Usuario bloqueado temporalmente');
                return;
            }

            // Verificar acceso a sucursal
            if (!$user->hasRole('BOSS') && !$user->canAccessBranch($branchId)) {
                $this->handleFailedLogin($username, 'No tiene permisos para acceder a esta sucursal');
                return;
            }

            // Login exitoso
            $this->createUserSession($user, $branchId, $remember);
            
            // Reset intentos fallidos
            $user->resetFailedAttempts();
            
            $_SESSION['login_success'] = "¡Bienvenido, {$user->getDisplayName()}!";
            
            // Redirigir según rol
            $redirectPath = $this->getDefaultRedirectPath($user->getRoleName());
            $this->redirect($redirectPath);

        } catch (\Exception $e) {
            logError("Error en login: " . $e->getMessage());
            $_SESSION['login_error'] = 'Error interno del sistema. Intente nuevamente.';
            $_SESSION['old_input'] = ['username' => $username, 'branch_id' => $branchId];
            $this->redirect('/login');
        }
    }

    /**
     * Cerrar sesión
     * Ruta: POST /logout -> LoginController@logout
     */
    public function logout(): void
    {
        if ($this->isAuthenticated()) {
            $userId = $_SESSION['user']['id'] ?? null;
            if ($userId) {
                // Log de logout
                logInfo("Usuario {$userId} cerró sesión", 'auth');
            }
        }

        // Destruir sesión
        session_destroy();
        
        // Iniciar nueva sesión para el mensaje
        session_start();
        $_SESSION['login_success'] = 'Sesión cerrada correctamente';
        
        $this->redirect('/login');
    }

    /**
     * Mostrar formulario de recuperación de contraseña
     * Ruta: GET /forgot-password -> LoginController@showForgotForm
     */
    public function showForgotForm(): void
    {
        $data = [
            'page_title' => 'Recuperar Contraseña - ROBS',
            'error' => $_SESSION['forgot_error'] ?? null,
            'success' => $_SESSION['forgot_success'] ?? null
        ];

        unset($_SESSION['forgot_error'], $_SESSION['forgot_success']);
        $this->view('auth.forgot-password', $data);
    }

    /**
     * Procesar solicitud de recuperación
     * Ruta: POST /forgot-password -> LoginController@sendResetLink
     */
    public function sendResetLink(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/forgot-password');
            return;
        }

        $email = trim($_POST['email'] ?? '');

        if (empty($email) || !isValidEmail($email)) {
            $_SESSION['forgot_error'] = 'Por favor, ingrese un email válido';
            $this->redirect('/forgot-password');
            return;
        }

        try {
            $user = User::findByEmail($email);
            
            // Por seguridad, siempre mostrar el mismo mensaje
            $_SESSION['forgot_success'] = 'Si el email existe en nuestro sistema, recibirá instrucciones para restablecer su contraseña.';
            
            if ($user) {
                // TODO: Implementar envío de email real
                logInfo("Solicitud de reset de password para user ID: {$user->getAttribute('id')}", 'auth');
            }
            
            $this->redirect('/forgot-password');

        } catch (\Exception $e) {
            logError("Error en forgot password: " . $e->getMessage());
            $_SESSION['forgot_error'] = 'Error interno del sistema. Intente nuevamente.';
            $this->redirect('/forgot-password');
        }
    }

    /**
     * Mostrar formulario para restablecer contraseña
     * Ruta: GET /reset-password/{token} -> LoginController@showResetForm
     */
    public function showResetForm(string $token): void
    {
        if (empty($token)) {
            $_SESSION['login_error'] = 'Token de recuperación inválido';
            $this->redirect('/login');
            return;
        }

        $data = [
            'page_title' => 'Restablecer Contraseña - ROBS',
            'token' => $token,
            'error' => $_SESSION['reset_error'] ?? null
        ];

        unset($_SESSION['reset_error']);
        $this->view('auth.reset-password', $data);
    }

    /**
     * Procesar restablecimiento de contraseña
     * Ruta: POST /reset-password -> LoginController@resetPassword
     */
    public function resetPassword(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/login');
            return;
        }

        $token = trim($_POST['token'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $confirmPassword = trim($_POST['confirm_password'] ?? '');

        // Validaciones
        $errors = [];
        
        if (empty($token)) {
            $errors[] = 'Token inválido';
        }

        if (empty($password)) {
            $errors[] = 'La contraseña es obligatoria';
        } elseif (strlen($password) < 8) {
            $errors[] = 'La contraseña debe tener al menos 8 caracteres';
        }

        if ($password !== $confirmPassword) {
            $errors[] = 'Las contraseñas no coinciden';
        }

        if (!empty($errors)) {
            $_SESSION['reset_error'] = implode('<br>', $errors);
            $this->redirect('/reset-password?token=' . urlencode($token));
            return;
        }

        // TODO: Implementar validación de token y actualización de contraseña
        $_SESSION['login_success'] = 'Contraseña restablecida correctamente. Puede iniciar sesión.';
        $this->redirect('/login');
    }

    // MÉTODOS PRIVADOS DE APOYO

    private function validateLoginInput(string $username, string $password, int $branchId): array
    {
        $errors = [];

        if (empty($username)) {
            $errors[] = 'El nombre de usuario es obligatorio';
        }

        if (empty($password)) {
            $errors[] = 'La contraseña es obligatoria';
        }

        if ($branchId <= 0) {
            $errors[] = 'Debe seleccionar una sucursal válida';
        }

        return $errors;
    }

    private function handleFailedLogin(string $username, string $message): void
    {
        // Intentar incrementar fallos del usuario
        try {
            $user = User::findByUsername($username);
            if ($user) {
                $user->incrementFailedAttempts();
            }
        } catch (\Exception $e) {
            logError("Error incrementando intentos fallidos: " . $e->getMessage());
        }

        logInfo("Failed login attempt for username: {$username} from IP: " . getClientIp(), 'auth');
        
        $_SESSION['login_error'] = $message;
        $_SESSION['old_input'] = ['username' => $username];
        $this->redirect('/login');
    }

    private function createUserSession(User $user, int $branchId, bool $remember = false): void
    {
        // Regenerar ID de sesión por seguridad
        session_regenerate_id(true);

        // Datos de la sesión usando el método del modelo
        $_SESSION['user'] = $user->toSessionArray();
        $_SESSION['authenticated'] = true;
        $_SESSION['branch_id'] = $branchId;
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();

        // Obtener datos de la sucursal
        $branchData = $this->getBranchData($branchId);
        if ($branchData) {
            $_SESSION['branch'] = $branchData;
        }

        // TODO: Implementar cookie "recordar" si es necesario
        if ($remember) {
            // Implementar lógica de remember token
        }
    }

    private function getDefaultRedirectPath(string $role): string
    {
        // Verificar si hay URL de intención guardada
        if (isset($_SESSION['intended_url'])) {
            $intended = $_SESSION['intended_url'];
            unset($_SESSION['intended_url']);
            return $intended;
        }

        // Redirección por rol
        switch ($role) {
            case 'BOSS':
                return '/dashboard';
            case 'MANAGER':
                return '/dashboard';
            case 'CAJERO':
                return '/orders/create'; // POS para cajeros
            case 'MESERO':
                return '/tablet'; // Vista tablet para meseros
            default:
                return '/dashboard';
        }
    }

    private function getActiveBranches(): array
    {
        try {
            $db = Database::getInstance();
            $sql = "SELECT id, name, address FROM branches WHERE is_active = 1 ORDER BY name";
            return $db->fetchAll($sql);
        } catch (\Exception $e) {
            logError("Error obteniendo sucursales: " . $e->getMessage());
            return [];
        }
    }

    private function getBranchData(int $branchId): ?array
    {
        try {
            $db = Database::getInstance();
            $sql = "SELECT * FROM branches WHERE id = ? AND is_active = 1";
            return $db->fetchOne($sql, [$branchId]);
        } catch (\Exception $e) {
            logError("Error obteniendo datos de sucursal: " . $e->getMessage());
            return null;
        }
    }
}
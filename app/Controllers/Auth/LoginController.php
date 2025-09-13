<?php

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

        $this->view('auth/login', $data);
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
        $branchId = intval($_POST['branch_id'] ?? 1); // Default branch

        // Validaciones básicas
        $errors = $this->validateLoginInput($username, $password, $branchId);
        
        if (!empty($errors)) {
            $_SESSION['login_error'] = implode('<br>', $errors);
            $_SESSION['old_input'] = ['username' => $username, 'branch_id' => $branchId];
            $this->redirect('/login');
            return;
        }

        try {
            // TEMPORAL: Usuario por defecto para pruebas
            if ($username === 'admin' && $password === '123456') {
                $this->createUserSession($username, $branchId, $remember);
                $_SESSION['login_success'] = "¡Bienvenido, {$username}!";
                $this->redirect('/dashboard');
                return;
            }

            // Buscar usuario en base de datos (cuando esté implementado)
            // $user = User::findByUsername($username);
            // if (!$user || !$user->verifyPassword($password)) {
            //     $this->handleFailedLogin($username, 'Credenciales incorrectas');
            //     return;
            // }

            $this->handleFailedLogin($username, 'Credenciales incorrectas');

        } catch (\Exception $e) {
            error_log("Error en login: " . $e->getMessage());
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
                error_log("Usuario {$userId} cerró sesión");
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
        $this->view('auth/forgot-password', $data);
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

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['forgot_error'] = 'Por favor, ingrese un email válido';
            $this->redirect('/forgot-password');
            return;
        }

        // Por seguridad, siempre mostrar el mismo mensaje
        $_SESSION['forgot_success'] = 'Si el email existe en nuestro sistema, recibirá instrucciones para restablecer su contraseña.';
        $this->redirect('/forgot-password');
    }

    /**
     * Mostrar formulario para restablecer contraseña
     * Ruta: GET /reset-password/{token} -> LoginController@showResetForm
     */
    public function showResetForm(string $token = null): void
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
        $this->view('auth/reset-password', $data);
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

    /**
     * Validar datos de entrada del login
     */
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

    /**
     * Manejar login fallido
     */
    private function handleFailedLogin(string $username, string $message): void
    {
        error_log("Failed login attempt for username: {$username} from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        
        $_SESSION['login_error'] = $message;
        $_SESSION['old_input'] = ['username' => $username];
        $this->redirect('/login');
    }

    /**
     * Crear sesión de usuario
     */
    private function createUserSession(string $username, int $branchId, bool $remember): void
    {
        $_SESSION['user'] = [
            'id' => 1, // TEMPORAL
            'username' => $username,
            'role' => 'BOSS', // TEMPORAL
            'branch_id' => $branchId,
            'login_time' => time()
        ];

        if ($remember) {
            // Crear cookie de "recordarme" (implementar después)
        }

        error_log("User {$username} logged in successfully");
    }

    /**
     * Verificar si el usuario está autenticado
     */
    public function isAuthenticated(): bool
    {
        return isset($_SESSION['user']) && !empty($_SESSION['user']['id']);
    }

    /**
     * Obtener sucursales activas
     */
    private function getActiveBranches(): array
    {
        // TEMPORAL: Datos de prueba
        return [
            ['id' => 1, 'name' => 'Sucursal Principal'],
            ['id' => 2, 'name' => 'Sucursal Norte'],
            ['id' => 3, 'name' => 'Sucursal Sur']
        ];
    }

    /**
     * Obtener ruta de redirección por defecto según rol
     */
    private function getDefaultRedirectPath(string $role): string
    {
        switch ($role) {
            case 'BOSS':
                return '/dashboard';
            case 'MANAGER':
                return '/dashboard';
            case 'CASHIER':
                return '/pos';
            case 'WAITER':
                return '/orders';
            default:
                return '/dashboard';
        }
    }
}
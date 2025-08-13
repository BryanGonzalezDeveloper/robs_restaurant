<?php

/**
 * ROBS - Controlador Base
 * 
 * Clase base para todos los controladores del sistema
 */
abstract class Controller
{
    protected array $data = [];
    protected ?string $layout = 'main';
    
    /**
     * Constructor del controlador
     */
    public function __construct()
    {
        // Verificar si hay sesión activa
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Datos globales disponibles en todas las vistas
        $this->data['currentUser'] = $this->getCurrentUser();
        $this->data['currentBranch'] = $this->getCurrentBranch();
        $this->data['flashMessages'] = $this->getFlashMessages();
        
        // Limpiar mensajes flash después de obtenerlos
        $this->clearFlashMessages();
    }
    
    /**
     * Renderizar una vista
     */
    protected function view(string $view, array $data = []): void
    {
        // Combinar datos del controlador con datos específicos de la vista
        $viewData = array_merge($this->data, $data);
        
        // Extraer variables para usar en la vista
        extract($viewData);
        
        // Buffer de salida para capturar el contenido de la vista
        ob_start();
        
        $viewFile = VIEWS_PATH . '/' . str_replace('.', '/', $view) . '.php';
        
        if (!file_exists($viewFile)) {
            throw new Exception("Vista {$view} no encontrada en {$viewFile}");
        }
        
        include $viewFile;
        
        $content = ob_get_clean();
        
        // Si hay un layout definido, renderizarlo
        if ($this->layout) {
            $this->renderLayout($content, $viewData);
        } else {
            echo $content;
        }
    }
    
    /**
     * Renderizar layout
     */
    private function renderLayout(string $content, array $data): void
    {
        extract($data);
        
        $layoutFile = VIEWS_PATH . '/layouts/' . $this->layout . '.php';
        
        if (!file_exists($layoutFile)) {
            throw new Exception("Layout {$this->layout} no encontrado");
        }
        
        include $layoutFile;
    }
    
    /**
     * Renderizar vista sin layout
     */
    protected function partial(string $view, array $data = []): void
    {
        $originalLayout = $this->layout;
        $this->layout = null;
        $this->view($view, $data);
        $this->layout = $originalLayout;
    }
    
    /**
     * Redireccionar a una URL
     */
    protected function redirect(string $url): void
    {
        header("Location: {$url}");
        exit;
    }
    
    /**
     * Redireccionar con mensaje flash
     */
    protected function redirectWithMessage(string $url, string $message, string $type = 'success'): void
    {
        $this->setFlashMessage($message, $type);
        $this->redirect($url);
    }
    
    /**
     * Devolver respuesta JSON
     */
    protected function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Devolver respuesta de éxito JSON
     */
    protected function jsonSuccess(string $message = 'Operación exitosa', $data = null): void
    {
        $response = [
            'success' => true,
            'message' => $message
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        $this->json($response);
    }
    
    /**
     * Devolver respuesta de error JSON
     */
    protected function jsonError(string $message = 'Ha ocurrido un error', int $statusCode = 400): void
    {
        $this->json([
            'success' => false,
            'message' => $message
        ], $statusCode);
    }
    
    /**
     * Validar datos de entrada
     */
    protected function validate(array $data, array $rules): array
    {
        $errors = [];
        
        foreach ($rules as $field => $ruleSet) {
            $fieldRules = explode('|', $ruleSet);
            $value = $data[$field] ?? null;
            
            foreach ($fieldRules as $rule) {
                $error = $this->validateField($field, $value, $rule, $data);
                if ($error) {
                    $errors[$field] = $error;
                    break; // Solo mostrar el primer error por campo
                }
            }
        }
        
        return $errors;
    }
    
    /**
     * Validar un campo específico
     */
    private function validateField(string $field, $value, string $rule, array $allData): ?string
    {
        // Separar regla de parámetros
        $parts = explode(':', $rule);
        $ruleName = $parts[0];
        $parameter = $parts[1] ?? null;
        
        switch ($ruleName) {
            case 'required':
                if (empty($value)) {
                    return "El campo {$field} es requerido";
                }
                break;
                
            case 'email':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    return "El campo {$field} debe ser un email válido";
                }
                break;
                
            case 'min':
                if (!empty($value) && strlen($value) < (int)$parameter) {
                    return "El campo {$field} debe tener al menos {$parameter} caracteres";
                }
                break;
                
            case 'max':
                if (!empty($value) && strlen($value) > (int)$parameter) {
                    return "El campo {$field} no puede tener más de {$parameter} caracteres";
                }
                break;
                
            case 'numeric':
                if (!empty($value) && !is_numeric($value)) {
                    return "El campo {$field} debe ser numérico";
                }
                break;
                
            case 'confirmed':
                $confirmField = $field . '_confirmation';
                if ($value !== ($allData[$confirmField] ?? null)) {
                    return "La confirmación de {$field} no coincide";
                }
                break;
                
            case 'unique':
                // Verificar unicidad en base de datos
                if (!empty($value)) {
                    list($table, $column) = explode(',', $parameter);
                    if ($this->isValueUnique($table, $column, $value)) {
                        return "El {$field} ya está en uso";
                    }
                }
                break;
        }
        
        return null;
    }
    
    /**
     * Verificar si un valor es único en la base de datos
     */
    private function isValueUnique(string $table, string $column, $value): bool
{
    try {
        $db = Database::getInstance();
        $count = $db->fetchColumn("SELECT COUNT(*) FROM {$table} WHERE {$column} = ?", [$value]);
        return $count > 0;
    } catch (Exception $e) {
        return false;
    }
}
    
    /**
     * Sanitizar datos de entrada
     */
    protected function sanitize(array $data): array
    {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Obtener usuario actual de la sesión
     */
    protected function getCurrentUser(): ?array
    {
        return $_SESSION['user'] ?? null;
    }
    
    /**
     * Obtener sucursal actual de la sesión
     */
    protected function getCurrentBranch(): ?array
    {
        return $_SESSION['branch'] ?? null;
    }
    
    /**
     * Verificar si el usuario está autenticado
     */
    protected function isAuthenticated(): bool
    {
        return isset($_SESSION['user']) && !empty($_SESSION['user']);
    }
    
    /**
     * Verificar si el usuario tiene un rol específico
     */
    protected function hasRole(string $role): bool
    {
        $user = $this->getCurrentUser();
        return $user && ($user['role'] ?? '') === $role;
    }
    
    /**
     * Verificar si el usuario tiene alguno de los roles especificados
     */
    protected function hasAnyRole(array $roles): bool
    {
        $user = $this->getCurrentUser();
        return $user && in_array($user['role'] ?? '', $roles);
    }
    
    /**
     * Establecer mensaje flash
     */
    protected function setFlashMessage(string $message, string $type = 'info'): void
    {
        if (!isset($_SESSION['flash_messages'])) {
            $_SESSION['flash_messages'] = [];
        }
        
        $_SESSION['flash_messages'][] = [
            'message' => $message,
            'type' => $type,
            'timestamp' => time()
        ];
    }
    
    /**
     * Obtener mensajes flash
     */
    protected function getFlashMessages(): array
    {
        return $_SESSION['flash_messages'] ?? [];
    }
    
    /**
     * Limpiar mensajes flash
     */
    protected function clearFlashMessages(): void
    {
        unset($_SESSION['flash_messages']);
    }
    
    /**
     * Manejar subida de archivos
     */
    protected function handleFileUpload(array $file, string $directory = 'general', array $allowedTypes = ['jpg', 'jpeg', 'png', 'gif']): array
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Error al subir el archivo'];
        }
        
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($extension, $allowedTypes)) {
            return ['success' => false, 'message' => 'Tipo de archivo no permitido'];
        }
        
        $uploadDir = UPLOADS_PATH . '/' . $directory;
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $filename = uniqid() . '_' . time() . '.' . $extension;
        $filepath = $uploadDir . '/' . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            return [
                'success' => true,
                'filename' => $filename,
                'path' => $filepath,
                'url' => '/assets/uploads/' . $directory . '/' . $filename
            ];
        }
        
        return ['success' => false, 'message' => 'No se pudo guardar el archivo'];
    }
    
    /**
     * Establecer layout para la vista
     */
    protected function setLayout(?string $layout): void
    {
        $this->layout = $layout;
    }
}
<?php

/**
 * ROBS - Controlador de Inicio
 * 
 * Maneja las páginas principales y de bienvenida
 */
class HomeController extends Controller
{
    /**
     * Página de inicio
     */
    public function index(): void
    {
        // Si el usuario está autenticado, redirigir al dashboard
        if ($this->isAuthenticated()) {
            $this->redirect('/dashboard');
            return;
        }
        
        // Datos para la vista de inicio
        $data = [
            'title' => 'Bienvenido a ROBS',
            'systemInfo' => $this->getSystemStatus(),
            'features' => $this->getSystemFeatures()
        ];
        
        $this->setLayout('welcome');
        $this->view('home.index', $data);
    }
    
    /**
     * Página de información
     */
    public function about(): void
    {
        $data = [
            'title' => 'Acerca de ROBS',
            'version' => config('app.version', '1.0.0'),
            'description' => config('app.description', 'Sistema POS para restaurantes')
        ];
        
        $this->view('home.about', $data);
    }
    
    /**
     * Página de contacto
     */
    public function contact(): void
    {
        $data = [
            'title' => 'Contacto',
            'contact_info' => [
                'email' => 'support@robs.com',
                'phone' => '+52 (555) 123-4567',
                'address' => 'México'
            ]
        ];
        
        $this->view('home.contact', $data);
    }
    
    /**
     * Obtener estado del sistema
     */
    private function getSystemStatus(): array
    {
        try {
            // Verificar conexión a base de datos
            $dbStatus = testDatabaseConnection();
            
            // Verificar directorios necesarios
            $directories = [
                'storage' => is_writable(STORAGE_PATH),
                'logs' => is_writable(STORAGE_PATH . '/logs'),
                'cache' => is_writable(STORAGE_PATH . '/cache'),
                'uploads' => is_writable(PUBLIC_PATH . '/assets/uploads')
            ];
            
            return [
                'database' => $dbStatus,
                'directories' => $directories,
                'php_version' => PHP_VERSION,
                'environment' => config('app.env', 'production'),
                'debug_mode' => config('app.debug', false),
                'timezone' => config('app.timezone', 'UTC')
            ];
            
        } catch (Exception $e) {
            logError("Error al verificar estado del sistema: " . $e->getMessage());
            
            return [
                'database' => false,
                'directories' => [],
                'error' => 'Error al verificar el estado del sistema'
            ];
        }
    }
    
    /**
     * Obtener características del sistema
     */
    private function getSystemFeatures(): array
    {
        return [
            [
                'icon' => 'users',
                'title' => 'Gestión de Usuarios',
                'description' => 'Control completo de usuarios con roles y permisos específicos'
            ],
            [
                'icon' => 'store',
                'title' => 'Multi-Sucursal',
                'description' => 'Manejo independiente de múltiples restaurantes desde un solo sistema'
            ],
            [
                'icon' => 'menu',
                'title' => 'Gestión de Menú',
                'description' => 'Administración completa de productos, categorías y modificadores'
            ],
            [
                'icon' => 'receipt',
                'title' => 'Sistema de Órdenes',
                'description' => 'Toma de órdenes optimizada para tablets, computadoras y QR'
            ],
            [
                'icon' => 'cash',
                'title' => 'Control Financiero',
                'description' => 'Manejo de turnos, pagos, propinas y movimientos de efectivo'
            ],
            [
                'icon' => 'chart',
                'title' => 'Reportes Avanzados',
                'description' => 'Analytics en tiempo real con reportes detallados de ventas'
            ],
            [
                'icon' => 'printer',
                'title' => 'Impresión Automática',
                'description' => 'Comandas automáticas a cocina y recibos de pago'
            ],
            [
                'icon' => 'star',
                'title' => 'Encuestas de Satisfacción',
                'description' => 'Recopilación de feedback de clientes vía tablet o QR'
            ]
        ];
    }
}
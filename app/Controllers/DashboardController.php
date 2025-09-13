<?php

/**
 * DashboardController - Panel principal del sistema
 */
class DashboardController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->setLayout('main');
        
        // Verificar autenticación
        if (!$this->isAuthenticated()) {
            $this->redirect('/login');
            return;
        }
    }

    /**
     * Dashboard principal
     * Ruta: GET /dashboard -> DashboardController@index
     */
    public function index(): void
    {
        $user = $_SESSION['user'] ?? [];
        
        $data = [
            'page_title' => 'Dashboard - ROBS',
            'user' => $user,
            'stats' => $this->getDashboardStats(),
            'recent_orders' => $this->getRecentOrders(),
            'alerts' => $this->getSystemAlerts()
        ];

        $this->view('dashboard/index', $data);
    }

    /**
     * Obtener estadísticas para AJAX
     * Ruta: GET /dashboard/stats -> DashboardController@getStats
     */
    public function getStats(): void
    {
        header('Content-Type: application/json');
        
        $stats = $this->getDashboardStats();
        
        echo json_encode([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Obtener órdenes recientes para AJAX
     * Ruta: GET /dashboard/recent-orders -> DashboardController@getRecentOrders
     */
    public function getRecentOrders(): void
    {
        header('Content-Type: application/json');
        
        $orders = $this->getRecentOrdersTemp();
        
        echo json_encode([
            'success' => true,
            'data' => $orders
        ]);
    }

    // MÉTODOS PRIVADOS

    /**
     * Obtener estadísticas del dashboard
     */
    private function getDashboardStats(): array
    {
        // TEMPORAL: Datos de prueba
        return [
            'today_sales' => [
                'amount' => 15420.50,
                'orders' => 127,
                'avg_ticket' => 121.42
            ],
            'active_tables' => [
                'occupied' => 18,
                'available' => 32,
                'total' => 50
            ],
            'staff_online' => [
                'waiters' => 8,
                'kitchen' => 4,
                'cashiers' => 2
            ],
            'pending_orders' => 23
        ];
    }

    /**
     * Obtener órdenes recientes
     */
    public function getRecentOrdersTemp(): array
    {
        // TEMPORAL: Datos de prueba
        return [
            [
                'id' => 1001,
                'table' => 'Mesa 15',
                'waiter' => 'Carlos López',
                'amount' => 425.00,
                'status' => 'preparando',
                'time' => '10:30 AM'
            ],
            [
                'id' => 1002,
                'table' => 'Mesa 8',
                'waiter' => 'Ana García',
                'amount' => 280.00,
                'status' => 'servido',
                'time' => '10:25 AM'
            ],
            [
                'id' => 1003,
                'table' => 'Para llevar',
                'waiter' => 'Sistema',
                'amount' => 150.00,
                'status' => 'listo',
                'time' => '10:20 AM'
            ]
        ];
    }

    /**
     * Obtener alertas del sistema
     */
    private function getSystemAlerts(): array
    {
        // TEMPORAL: Alertas de ejemplo
        return [
            [
                'type' => 'warning',
                'message' => 'Mesa 12 lleva más de 45 minutos esperando',
                'time' => '5 min ago'
            ],
            [
                'type' => 'info',
                'message' => 'Producto "Pizza Margarita" con stock bajo (3 unidades)',
                'time' => '15 min ago'
            ]
        ];
    }

    /**
     * Verificar si el usuario está autenticado
     */
    public function isAuthenticated(): bool
    {
        return isset($_SESSION['user']) && !empty($_SESSION['user']['id']);
    }
}
<?php

/**
 * ROBS - Configuración de Rutas
 * 
 * Define todas las rutas del sistema organizadas por módulos
 */

/*
|--------------------------------------------------------------------------
| Ruta de inicio y páginas públicas
|--------------------------------------------------------------------------
*/

// Página de inicio
$router->get('/', 'HomeController@index', 'home');

// Páginas de información
$router->get('/about', 'HomeController@about', 'about');
$router->get('/contact', 'HomeController@contact', 'contact');

/*
|--------------------------------------------------------------------------
| Rutas de autenticación
|--------------------------------------------------------------------------
*/

// Login y logout
$router->get('/login', 'LoginController@showLoginForm', 'login');
$router->post('/login', 'LoginController@authenticate', 'login.post');
$router->post('/logout', 'LoginController@logout', 'logout');

// Recuperación de contraseña
$router->get('/forgot-password', 'LoginController@showForgotForm', 'password.forgot');
$router->post('/forgot-password', 'LoginController@sendResetLink', 'password.forgot.post');
$router->get('/reset-password/{token}', 'LoginController@showResetForm', 'password.reset');
$router->post('/reset-password', 'LoginController@resetPassword', 'password.reset.post');

/*
|--------------------------------------------------------------------------
| Rutas protegidas (requieren autenticación)
|--------------------------------------------------------------------------
*/

$router->group(['middleware' => ['AuthMiddleware']], function($router) {
    
    /*
    |--------------------------------------------------------------------------
    | Dashboard principal
    |--------------------------------------------------------------------------
    */
    $router->get('/dashboard', 'DashboardController@index', 'dashboard');
    $router->get('/dashboard/stats', 'DashboardController@getStats', 'dashboard.stats');
    $router->get('/dashboard/recent-orders', 'DashboardController@getRecentOrders', 'dashboard.recent');
    
    /*
    |--------------------------------------------------------------------------
    | Gestión de usuarios (BOSS y MANAGER)
    |--------------------------------------------------------------------------
    */
    $router->group(['middleware' => ['RoleMiddleware:BOSS,MANAGER']], function($router) {
        
        // CRUD de usuarios
        $router->get('/users', 'UserController@index', 'users.index');
        $router->get('/users/create', 'UserController@create', 'users.create');
        $router->post('/users', 'UserController@store', 'users.store');
        $router->get('/users/{id}', 'UserController@show', 'users.show');
        $router->get('/users/{id}/edit', 'UserController@edit', 'users.edit');
        $router->post('/users/{id}', 'UserController@update', 'users.update');
        $router->post('/users/{id}/delete', 'UserController@delete', 'users.delete');
        
        // Gestión de roles
        $router->get('/roles', 'RoleController@index', 'roles.index');
        $router->get('/roles/{id}', 'RoleController@show', 'roles.show');
        
        // Activar/desactivar usuarios
        $router->post('/users/{id}/toggle-status', 'UserController@toggleStatus', 'users.toggle');
        $router->post('/users/{id}/reset-password', 'UserController@resetPassword', 'users.reset.password');
    });
    
    /*
    |--------------------------------------------------------------------------
    | Gestión de sucursales (BOSS)
    |--------------------------------------------------------------------------
    */
    $router->group(['middleware' => ['RoleMiddleware:BOSS']], function($router) {
        
        // CRUD de sucursales
        $router->get('/branches', 'BranchController@index', 'branches.index');
        $router->get('/branches/create', 'BranchController@create', 'branches.create');
        $router->post('/branches', 'BranchController@store', 'branches.store');
        $router->get('/branches/{id}', 'BranchController@show', 'branches.show');
        $router->get('/branches/{id}/edit', 'BranchController@edit', 'branches.edit');
        $router->post('/branches/{id}', 'BranchController@update', 'branches.update');
        $router->post('/branches/{id}/delete', 'BranchController@delete', 'branches.delete');
        
        // Configuración de sucursales
        $router->get('/branches/{id}/config', 'ConfigController@showBranchConfig', 'branches.config');
        $router->post('/branches/{id}/config', 'ConfigController@saveBranchConfig', 'branches.config.save');
        
        // Gestión de impresoras
        $router->get('/branches/{id}/printers', 'ConfigController@showPrinters', 'branches.printers');
        $router->post('/branches/{id}/printers', 'ConfigController@savePrinters', 'branches.printers.save');
        $router->post('/branches/{id}/printers/test', 'ConfigController@testPrinter', 'branches.printers.test');
        
        // Áreas de trabajo
        $router->get('/branches/{id}/work-areas', 'ConfigController@showWorkAreas', 'branches.work.areas');
        $router->post('/branches/{id}/work-areas', 'ConfigController@saveWorkAreas', 'branches.work.areas.save');
    });
    
    /*
    |--------------------------------------------------------------------------
    | Gestión de productos y menú (BOSS y MANAGER)
    |--------------------------------------------------------------------------
    */
    $router->group(['middleware' => ['RoleMiddleware:BOSS,MANAGER']], function($router) {
        
        // Categorías
        $router->get('/categories', 'CategoryController@index', 'categories.index');
        $router->post('/categories', 'CategoryController@store', 'categories.store');
        $router->post('/categories/{id}', 'CategoryController@update', 'categories.update');
        $router->post('/categories/{id}/delete', 'CategoryController@delete', 'categories.delete');
        
        // Productos
        $router->get('/products', 'ProductController@index', 'products.index');
        $router->get('/products/create', 'ProductController@create', 'products.create');
        $router->post('/products', 'ProductController@store', 'products.store');
        $router->get('/products/{id}', 'ProductController@show', 'products.show');
        $router->get('/products/{id}/edit', 'ProductController@edit', 'products.edit');
        $router->post('/products/{id}', 'ProductController@update', 'products.update');
        $router->post('/products/{id}/delete', 'ProductController@delete', 'products.delete');
        $router->post('/products/{id}/toggle-availability', 'ProductController@toggleAvailability', 'products.toggle');
        
        // Modificadores
        $router->get('/modifiers', 'ModifierController@index', 'modifiers.index');
        $router->post('/modifiers/groups', 'ModifierController@storeGroup', 'modifiers.groups.store');
        $router->post('/modifiers', 'ModifierController@store', 'modifiers.store');
        $router->post('/modifiers/{id}', 'ModifierController@update', 'modifiers.update');
        $router->post('/modifiers/{id}/delete', 'ModifierController@delete', 'modifiers.delete');
        
        // Subir imágenes de productos
        $router->post('/products/upload-image', 'ProductController@uploadImage', 'products.upload.image');
    });
    
    /*
    |--------------------------------------------------------------------------
    | Sistema de órdenes
    |--------------------------------------------------------------------------
    */
    
    // Órdenes para meseros y cajeros
    $router->group(['middleware' => ['RoleMiddleware:MESERO,CAJERO,MANAGER,BOSS']], function($router) {
        
        // Vista de órdenes
        $router->get('/orders', 'OrderController@index', 'orders.index');
        $router->get('/orders/create', 'OrderController@create', 'orders.create');
        $router->post('/orders', 'OrderController@store', 'orders.store');
        $router->get('/orders/{id}', 'OrderController@show', 'orders.show');
        $router->post('/orders/{id}/add-item', 'OrderController@addItem', 'orders.add.item');
        $router->post('/orders/{id}/remove-item', 'OrderController@removeItem', 'orders.remove.item');
        $router->post('/orders/{id}/send-kitchen', 'OrderController@sendToKitchen', 'orders.send.kitchen');
        
        // Gestión de mesas
        $router->get('/tables', 'TableController@index', 'tables.index');
        $router->get('/tables/{id}/orders', 'TableController@getOrders', 'tables.orders');
        $router->post('/tables/{id}/assign', 'TableController@assign', 'tables.assign');
        $router->post('/tables/{id}/clear', 'TableController@clear', 'tables.clear');
    });
    
    // Órdenes específicas para cajeros
    $router->group(['middleware' => ['RoleMiddleware:CAJERO,MANAGER,BOSS']], function($router) {
        
        // Proceso de pago
        $router->get('/orders/{id}/payment', 'PaymentController@showPaymentForm', 'orders.payment');
        $router->post('/orders/{id}/payment', 'PaymentController@processPayment', 'orders.payment.process');
        $router->post('/orders/{id}/split-payment', 'PaymentController@splitPayment', 'orders.payment.split');
        
        // Gestión de propinas
        $router->post('/orders/{id}/tip', 'TipController@addTip', 'orders.tip.add');
        $router->post('/orders/{id}/tip-later', 'TipController@addTipLater', 'orders.tip.later');
        
        // Descuentos
        $router->post('/orders/{id}/discount', 'OrderController@applyDiscount', 'orders.discount');
        $router->post('/orders/{id}/remove-discount', 'OrderController@removeDiscount', 'orders.discount.remove');
        
        // Reimpresión
        $router->post('/orders/{id}/reprint', 'OrderController@reprint', 'orders.reprint');
    });
    
    /*
    |--------------------------------------------------------------------------
    | Gestión financiera
    |--------------------------------------------------------------------------
    */
    
    // Turnos (cajeros y managers)
    $router->group(['middleware' => ['RoleMiddleware:CAJERO,MANAGER,BOSS']], function($router) {
        
        // Gestión de turnos
        $router->get('/shifts', 'ShiftController@index', 'shifts.index');
        $router->get('/shifts/current', 'ShiftController@current', 'shifts.current');
        $router->post('/shifts/open', 'ShiftController@open', 'shifts.open');
        $router->post('/shifts/close', 'ShiftController@close', 'shifts.close');
        $router->get('/shifts/{id}', 'ShiftController@show', 'shifts.show');
        
        // Movimientos de efectivo
        $router->post('/shifts/cash-withdrawal', 'CashMovementController@withdrawal', 'cash.withdrawal');
        $router->post('/shifts/cash-deposit', 'CashMovementController@deposit', 'cash.deposit');
        $router->post('/shifts/cash-expense', 'CashMovementController@expense', 'cash.expense');
        $router->get('/shifts/{id}/cash-movements', 'CashMovementController@getMovements', 'cash.movements');
    });
    
    /*
    |--------------------------------------------------------------------------
    | Reportes y análisis
    |--------------------------------------------------------------------------
    */
    
    // Reportes básicos (todos los usuarios autenticados)
    $router->get('/reports', 'ReportController@index', 'reports.index');
    $router->get('/reports/daily', 'SalesController@daily', 'reports.daily');
    $router->get('/reports/shift/{id}', 'SalesController@shift', 'reports.shift');
    
    // Reportes avanzados (managers y boss)
    $router->group(['middleware' => ['RoleMiddleware:MANAGER,BOSS']], function($router) {
        
        // Reportes de ventas
        $router->get('/reports/sales', 'SalesController@index', 'reports.sales');
        $router->get('/reports/sales/weekly', 'SalesController@weekly', 'reports.sales.weekly');
        $router->get('/reports/sales/products', 'SalesController@products', 'reports.sales.products');
        $router->get('/reports/sales/waiters', 'SalesController@waiters', 'reports.sales.waiters');
        $router->get('/reports/sales/payment-methods', 'SalesController@paymentMethods', 'reports.sales.payment.methods');
        
        // Reportes de propinas
        $router->get('/reports/tips', 'TipReportController@index', 'reports.tips');
        $router->get('/reports/tips/distribution', 'TipReportController@distribution', 'reports.tips.distribution');
        $router->post('/reports/tips/calculate', 'TipReportController@calculate', 'reports.tips.calculate');
        
        // Reportes de inventario
        $router->get('/reports/inventory', 'InventoryReportController@index', 'reports.inventory');
        $router->get('/reports/inventory/low-stock', 'InventoryReportController@lowStock', 'reports.inventory.low');
        
        // Encuestas de satisfacción
        $router->get('/reports/surveys', 'SurveyReportController@index', 'reports.surveys');
        $router->get('/reports/surveys/ratings', 'SurveyReportController@ratings', 'reports.surveys.ratings');
    });
    
    // Reportes de BOSS únicamente
    $router->group(['middleware' => ['RoleMiddleware:BOSS']], function($router) {
        
        // Reportes comparativos
        $router->get('/reports/comparative', 'AnalyticsController@comparative', 'reports.comparative');
        $router->get('/reports/monthly', 'SalesController@monthly', 'reports.monthly');
        $router->get('/reports/yearly', 'SalesController@yearly', 'reports.yearly');
        $router->get('/reports/branches', 'AnalyticsController@branches', 'reports.branches');
        
        // Análisis avanzados
        $router->get('/analytics', 'AnalyticsController@index', 'analytics.index');
        $router->get('/analytics/trends', 'AnalyticsController@trends', 'analytics.trends');
        $router->get('/analytics/forecast', 'AnalyticsController@forecast', 'analytics.forecast');
    });
    
    /*
    |--------------------------------------------------------------------------
    | Configuración y administración
    |--------------------------------------------------------------------------
    */
    
    // Configuración general (managers y boss)
    $router->group(['middleware' => ['RoleMiddleware:MANAGER,BOSS']], function($router) {
        
        // Descuentos
        $router->get('/config/discounts', 'DiscountController@index', 'config.discounts');
        $router->post('/config/discounts', 'DiscountController@store', 'config.discounts.store');
        $router->post('/config/discounts/{id}', 'DiscountController@update', 'config.discounts.update');
        $router->post('/config/discounts/{id}/delete', 'DiscountController@delete', 'config.discounts.delete');
        
        // Configuración de propinas
        $router->get('/config/tips', 'TipConfigController@index', 'config.tips');
        $router->post('/config/tips', 'TipConfigController@store', 'config.tips.store');
        $router->post('/config/tips/{id}', 'TipConfigController@update', 'config.tips.update');
        
        // Métodos de pago
        $router->get('/config/payment-methods', 'PaymentMethodController@index', 'config.payment.methods');
        $router->post('/config/payment-methods', 'PaymentMethodController@store', 'config.payment.methods.store');
        $router->post('/config/payment-methods/{id}', 'PaymentMethodController@update', 'config.payment.methods.update');
    });
    
    /*
    |--------------------------------------------------------------------------
    | Interfaz para tablets (meseros)
    |--------------------------------------------------------------------------
    */
    
    $router->group(['middleware' => ['RoleMiddleware:MESERO,MANAGER,BOSS']], function($router) {
        
        // Vista optimizada para tablet
        $router->get('/tablet', 'TabletController@index', 'tablet.index');
        $router->get('/tablet/menu', 'TabletController@menu', 'tablet.menu');
        $router->get('/tablet/tables', 'TabletController@tables', 'tablet.tables');
        $router->get('/tablet/orders/{id}', 'TabletController@order', 'tablet.order');
        
        // Gestión de órdenes en tablet
        $router->post('/tablet/orders', 'TabletController@createOrder', 'tablet.order.create');
        $router->post('/tablet/orders/{id}/items', 'TabletController@addItems', 'tablet.order.add.items');
        $router->post('/tablet/orders/{id}/send', 'TabletController@sendOrder', 'tablet.order.send');
        
        // Encuestas en tablet
        $router->get('/tablet/survey', 'TabletController@survey', 'tablet.survey');
        $router->post('/tablet/survey', 'TabletController@submitSurvey', 'tablet.survey.submit');
    });
    
    /*
    |--------------------------------------------------------------------------
    | Vista de cocina
    |--------------------------------------------------------------------------
    */
    
    $router->group(['middleware' => ['RoleMiddleware:MANAGER,BOSS']], function($router) {
        
        // Display de cocina
        $router->get('/kitchen', 'KitchenController@display', 'kitchen.display');
        $router->get('/kitchen/orders', 'KitchenController@getOrders', 'kitchen.orders');
        $router->post('/kitchen/orders/{id}/start', 'KitchenController@startOrder', 'kitchen.start');
        $router->post('/kitchen/orders/{id}/ready', 'KitchenController@markReady', 'kitchen.ready');
        $router->post('/kitchen/orders/{id}/served', 'KitchenController@markServed', 'kitchen.served');
    });
    
    /*
    |--------------------------------------------------------------------------
    | Perfil de usuario
    |--------------------------------------------------------------------------
    */
    
    // Perfil personal (todos los usuarios)
    $router->get('/profile', 'ProfileController@show', 'profile.show');
    $router->post('/profile', 'ProfileController@update', 'profile.update');
    $router->post('/profile/password', 'ProfileController@changePassword', 'profile.password');
    $router->get('/profile/tips', 'ProfileController@myTips', 'profile.tips'); // Solo para meseros
    
    /*
    |--------------------------------------------------------------------------
    | Gestión de archivos y utilidades
    |--------------------------------------------------------------------------
    */
    
    // Subida de archivos
    $router->post('/upload/image', 'FileController@uploadImage', 'upload.image');
    $router->post('/upload/document', 'FileController@uploadDocument', 'upload.document');
    $router->get('/files/{type}/{filename}', 'FileController@serve', 'files.serve');
    
    // Utilidades del sistema
    $router->get('/system/status', 'SystemController@status', 'system.status');
    $router->post('/system/clear-cache', 'SystemController@clearCache', 'system.clear.cache');
    $router->get('/system/logs', 'SystemController@logs', 'system.logs'); // Solo BOSS
});

/*
|--------------------------------------------------------------------------
| APIs externas y webhooks
|--------------------------------------------------------------------------
*/

// APIs para aplicaciones móviles y sistemas externos
$router->group(['prefix' => 'api'], function($router) {
    
    // Autenticación API
    $router->post('/auth/login', 'API\AuthAPIController@login', 'api.auth.login');
    $router->post('/auth/refresh', 'API\AuthAPIController@refresh', 'api.auth.refresh');
    
    // APIs protegidas con token
    $router->group(['middleware' => ['ApiAuthMiddleware']], function($router) {
        
        // Información de sucursal
        $router->get('/branch/info', 'API\BranchAPIController@info', 'api.branch.info');
        $router->get('/branch/menu', 'API\BranchAPIController@menu', 'api.branch.menu');
        
        // Órdenes
        $router->get('/orders', 'API\OrderAPIController@index', 'api.orders.index');
        $router->post('/orders', 'API\OrderAPIController@store', 'api.orders.store');
        $router->get('/orders/{id}', 'API\OrderAPIController@show', 'api.orders.show');
        $router->post('/orders/{id}/status', 'API\OrderAPIController@updateStatus', 'api.orders.status');
        
        // Reportes
        $router->get('/reports/dashboard', 'API\ReportAPIController@dashboard', 'api.reports.dashboard');
        $router->get('/reports/sales/{date}', 'API\ReportAPIController@dailySales', 'api.reports.sales');
        
        // Impresión
        $router->post('/print/order/{id}', 'API\PrinterAPIController@printOrder', 'api.print.order');
        $router->post('/print/receipt/{id}', 'API\PrinterAPIController@printReceipt', 'api.print.receipt');
        $router->post('/print/test', 'API\PrinterAPIController@testPrint', 'api.print.test');
    });
    
    // Webhooks de plataformas de delivery (sin autenticación)
    $router->post('/webhooks/uber-eats', 'API\WebhookController@uberEats', 'api.webhook.uber');
    $router->post('/webhooks/rappi', 'API\WebhookController@rappi', 'api.webhook.rappi');
    $router->post('/webhooks/didi', 'API\WebhookController@didi', 'api.webhook.didi');
});

/*
|--------------------------------------------------------------------------
| Sistema de órdenes por QR (para clientes)
|--------------------------------------------------------------------------
*/

// Acceso público para clientes con QR
$router->group(['prefix' => 'qr'], function($router) {
    
    // Menú para clientes
    $router->get('/{table_code}', 'QRController@menu', 'qr.menu');
    $router->get('/{table_code}/menu', 'QRController@getMenu', 'qr.get.menu');
    
    // Proceso de pedido
    $router->post('/{table_code}/order', 'QRController@createOrder', 'qr.order.create');
    $router->get('/order/{order_id}/status', 'QRController@orderStatus', 'qr.order.status');
    
    // Encuesta de satisfacción
    $router->get('/survey/{order_id}', 'QRController@survey', 'qr.survey');
    $router->post('/survey/{order_id}', 'QRController@submitSurvey', 'qr.survey.submit');
    
    // Información de la mesa
    $router->get('/table/{table_code}/info', 'QRController@tableInfo', 'qr.table.info');
});

/*
|--------------------------------------------------------------------------
| Sistema de autofacturación
|--------------------------------------------------------------------------
*/

// Portal de facturación para clientes
$router->group(['prefix' => 'billing'], function($router) {
    
    // Acceso con ticket ID y contraseña
    $router->get('/', 'BillingController@showForm', 'billing.form');
    $router->post('/access', 'BillingController@validateAccess', 'billing.access');
    
    // Proceso de facturación
    $router->get('/invoice/{ticket_id}', 'BillingController@showInvoiceForm', 'billing.invoice.form');
    $router->post('/invoice/{ticket_id}', 'BillingController@generateInvoice', 'billing.invoice.generate');
    
    // Descarga de factura
    $router->get('/download/{invoice_id}', 'BillingController@downloadInvoice', 'billing.download');
    
    // Validación de RFC
    $router->post('/validate-rfc', 'BillingController@validateRFC', 'billing.validate.rfc');
});

/*
|--------------------------------------------------------------------------
| Rutas de mantenimiento y errores
|--------------------------------------------------------------------------
*/

// Páginas de error
$router->get('/403', function() {
    http_response_code(403);
    include VIEWS_PATH . '/errors/403.php';
}, 'error.403');

$router->get('/404', function() {
    http_response_code(404);
    include VIEWS_PATH . '/errors/404.php';
}, 'error.404');

$router->get('/500', function() {
    http_response_code(500);
    include VIEWS_PATH . '/errors/500.php';
}, 'error.500');

// Página de mantenimiento
$router->get('/maintenance', function() {
    http_response_code(503);
    include VIEWS_PATH . '/maintenance.php';
}, 'maintenance');

/*
|--------------------------------------------------------------------------
| Rutas de desarrollo (solo en ambiente de desarrollo)
|--------------------------------------------------------------------------
*/

if (($_ENV['APP_ENV'] ?? 'production') === 'development') {
    
    // Información del sistema
    $router->get('/dev/info', function() {
        phpinfo();
    }, 'dev.info');
    
    // Pruebas de base de datos
    $router->get('/dev/db-test', 'DevController@testDatabase', 'dev.db.test');
    
    // Generar datos de prueba
    $router->get('/dev/seed', 'DevController@seedDatabase', 'dev.seed');
    
    // Limpiar caché
    $router->get('/dev/clear-cache', 'DevController@clearCache', 'dev.clear.cache');
    
    // Logs del sistema
    $router->get('/dev/logs', 'DevController@viewLogs', 'dev.logs');
    
    // Prueba de impresoras
    $router->get('/dev/test-printer', 'DevController@testPrinter', 'dev.test.printer');
}
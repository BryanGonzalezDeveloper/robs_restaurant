-- =====================================================
-- ROBS - Scripts de Migración de Base de Datos
-- =====================================================

-- =====================================================
-- migration_001_base_tables.sql
-- Tablas base sin dependencias
-- =====================================================

-- Tabla de roles del sistema
CREATE TABLE roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) UNIQUE NOT NULL COMMENT 'BOSS, MANAGER, CAJERO, MESERO',
    display_name VARCHAR(100) NOT NULL,
    description TEXT,
    permissions JSON COMMENT 'Permisos específicos del rol en formato JSON',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de sucursales
CREATE TABLE branches (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL COMMENT 'URL-friendly name',
    address TEXT NOT NULL,
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100) NOT NULL,
    postal_code VARCHAR(10),
    country VARCHAR(100) DEFAULT 'México',
    phone VARCHAR(20),
    email VARCHAR(100),
    rfc VARCHAR(20),
    tax_name VARCHAR(200),
    logo_url VARCHAR(500),
    operating_hours JSON COMMENT 'Horarios de operación por día de la semana',
    timezone VARCHAR(50) DEFAULT 'America/Mexico_City',
    currency VARCHAR(3) DEFAULT 'MXN',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- migration_002_users_and_config.sql
-- Tablas que dependen de las bases
-- =====================================================

-- Tabla de usuarios
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    employee_code VARCHAR(20) UNIQUE,
    role_id INT NOT NULL,
    branch_id INT NULL COMMENT 'NULL = acceso a todas las sucursales (solo BOSS)',
    phone VARCHAR(20),
    hire_date DATE,
    salary DECIMAL(10,2),
    commission_rate DECIMAL(5,2) DEFAULT 0.00,
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    failed_login_attempts INT DEFAULT 0,
    locked_until TIMESTAMP NULL,
    password_changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE RESTRICT,
    
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_branch_role (branch_id, role_id)
) ENGINE=InnoDB CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de áreas de trabajo
CREATE TABLE work_areas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    branch_id INT NOT NULL,
    name VARCHAR(50) NOT NULL COMMENT 'cocina, barra, horno, parrilla, etc.',
    description TEXT,
    printer_ip VARCHAR(15),
    printer_port INT DEFAULT 9100,
    printer_name VARCHAR(100),
    auto_print BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_branch_name (branch_id, name),
    INDEX idx_branch_active (branch_id, is_active)
) ENGINE=InnoDB CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de categorías de productos
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    branch_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    color VARCHAR(7) DEFAULT '#6B7280' COMMENT 'Color hex para UI',
    icon VARCHAR(50) COMMENT 'Icono para mostrar en UI',
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_branch_name (branch_id, name),
    INDEX idx_branch_active_order (branch_id, is_active, sort_order)
) ENGINE=InnoDB CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de mesas
CREATE TABLE tables (
    id INT PRIMARY KEY AUTO_INCREMENT,
    branch_id INT NOT NULL,
    table_number VARCHAR(10) NOT NULL,
    table_name VARCHAR(50) COMMENT 'Nombre descriptivo opcional',
    seats INT DEFAULT 4,
    location VARCHAR(50) COMMENT 'terraza, interior, barra, privado',
    qr_code VARCHAR(100) UNIQUE COMMENT 'Código QR único para pedidos',
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_branch_table (branch_id, table_number),
    INDEX idx_branch_active (branch_id, is_active)
) ENGINE=InnoDB CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de descuentos configurables
CREATE TABLE discounts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    branch_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20) UNIQUE COMMENT 'Código para aplicar descuento',
    type ENUM('percentage', 'fixed_amount', 'two_for_one', 'buy_x_get_y') NOT NULL,
    value DECIMAL(10,2) NOT NULL COMMENT 'Valor del descuento (% o monto fijo)',
    min_amount DECIMAL(10,2) DEFAULT 0 COMMENT 'Monto mínimo para aplicar',
    max_discount DECIMAL(10,2) COMMENT 'Descuento máximo en monto fijo',
    description TEXT,
    valid_from DATE,
    valid_until DATE,
    usage_limit INT COMMENT 'Límite de usos (NULL = ilimitado)',
    times_used INT DEFAULT 0,
    requires_authorization BOOLEAN DEFAULT TRUE,
    authorized_roles JSON COMMENT 'Roles que pueden aplicar este descuento',
    applicable_days JSON COMMENT 'Días de la semana donde aplica [1,2,3,4,5,6,0]',
    applicable_hours JSON COMMENT 'Horarios donde aplica {"start":"09:00","end":"17:00"}',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
    
    INDEX idx_branch_active (branch_id, is_active),
    INDEX idx_code (code),
    INDEX idx_valid_dates (valid_from, valid_until)
) ENGINE=InnoDB CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de grupos de modificadores
CREATE TABLE modifier_groups (
    id INT PRIMARY KEY AUTO_INCREMENT,
    branch_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    is_required BOOLEAN DEFAULT FALSE,
    min_selections INT DEFAULT 0,
    max_selections INT DEFAULT 1,
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_branch_name (branch_id, name),
    INDEX idx_branch_active (branch_id, is_active)
) ENGINE=InnoDB CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- migration_003_products_and_relations.sql
-- Productos y sus relaciones
-- =====================================================

-- Tabla de productos
CREATE TABLE products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    branch_id INT NOT NULL,
    category_id INT NOT NULL,
    work_area_id INT NOT NULL,
    sku VARCHAR(50),
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    cost DECIMAL(10,2) DEFAULT 0,
    image_url VARCHAR(500),
    preparation_time INT DEFAULT 15 COMMENT 'Tiempo estimado en minutos',
    calories INT COMMENT 'Información nutricional',
    allergens JSON COMMENT 'Lista de alérgenos',
    ingredients TEXT COMMENT 'Lista de ingredientes visible al cliente',
    is_available BOOLEAN DEFAULT TRUE,
    is_featured BOOLEAN DEFAULT FALSE,
    is_spicy BOOLEAN DEFAULT FALSE,
    is_vegetarian BOOLEAN DEFAULT FALSE,
    is_vegan BOOLEAN DEFAULT FALSE,
    sort_order INT DEFAULT 0,
    stock_quantity INT COMMENT 'NULL = sin control de stock',
    low_stock_alert INT DEFAULT 5,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
    FOREIGN KEY (work_area_id) REFERENCES work_areas(id) ON DELETE RESTRICT,
    
    UNIQUE KEY unique_branch_sku (branch_id, sku),
    INDEX idx_branch_category (branch_id, category_id),
    INDEX idx_branch_available (branch_id, is_available, is_active),
    INDEX idx_featured (is_featured),
    FULLTEXT KEY ft_name_description (name, description)
) ENGINE=InnoDB CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de modificadores
CREATE TABLE modifiers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    modifier_group_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price_adjustment DECIMAL(10,2) DEFAULT 0,
    cost_adjustment DECIMAL(10,2) DEFAULT 0,
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (modifier_group_id) REFERENCES modifier_groups(id) ON DELETE CASCADE,
    
    INDEX idx_group_active (modifier_group_id, is_active)
) ENGINE=InnoDB CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de relación producto-modificadores
CREATE TABLE product_modifiers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    modifier_group_id INT NOT NULL,
    is_required BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (modifier_group_id) REFERENCES modifier_groups(id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_product_modifier_group (product_id, modifier_group_id)
) ENGINE=InnoDB CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- migration_004_operations.sql
-- Operaciones y auditoría
-- =====================================================

-- Tabla de turnos
CREATE TABLE shifts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    branch_id INT NOT NULL,
    user_id INT NOT NULL,
    shift_number VARCHAR(20) UNIQUE NOT NULL,
    start_time TIMESTAMP NOT NULL,
    end_time TIMESTAMP NULL,
    initial_cash DECIMAL(10,2) NOT NULL DEFAULT 0,
    final_cash DECIMAL(10,2) NULL,
    expected_cash DECIMAL(10,2) NULL,
    cash_difference DECIMAL(10,2) NULL,
    total_sales DECIMAL(10,2) DEFAULT 0,
    total_tips DECIMAL(10,2) DEFAULT 0,
    total_discounts DECIMAL(10,2) DEFAULT 0,
    total_orders INT DEFAULT 0,
    notes TEXT,
    closing_notes TEXT,
    is_closed BOOLEAN DEFAULT FALSE,
    closed_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE RESTRICT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (closed_by) REFERENCES users(id) ON DELETE RESTRICT,
    
    INDEX idx_branch_date (branch_id, start_time),
    INDEX idx_user_date (user_id, start_time),
    INDEX idx_shift_number (shift_number)
) ENGINE=InnoDB CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de logs de auditoría
CREATE TABLE audit_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL COMMENT 'CREATE, UPDATE, DELETE, LOGIN, etc.',
    table_name VARCHAR(50) NOT NULL,
    record_id INT NOT NULL,
    old_values JSON NULL,
    new_values JSON NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    session_id VARCHAR(100),
    additional_data JSON COMMENT 'Datos adicionales del contexto',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
    
    INDEX idx_user_action (user_id, action),
    INDEX idx_table_record (table_name, record_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- migration_005_orders_system.sql
-- Sistema de órdenes
-- =====================================================

-- Tabla de órdenes
CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    branch_id INT NOT NULL,
    table_id INT NULL,
    shift_id INT NOT NULL,
    waiter_id INT NOT NULL,
    order_number VARCHAR(20) UNIQUE NOT NULL,
    order_type ENUM('local', 'delivery', 'pickup', 'qr') DEFAULT 'local',
    customer_name VARCHAR(100) COMMENT 'Para delivery y pickup',
    customer_phone VARCHAR(20) COMMENT 'Para delivery y pickup',
    delivery_address TEXT COMMENT 'Para delivery',
    guests_count INT DEFAULT 1,
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0,
    discount_id INT NULL,
    discount_amount DECIMAL(10,2) DEFAULT 0,
    discount_reason VARCHAR(200),
    discount_applied_by INT NULL,
    tax_rate DECIMAL(5,4) DEFAULT 0.16,
    tax_amount DECIMAL(10,2) DEFAULT 0,
    service_charge DECIMAL(10,2) DEFAULT 0,
    delivery_fee DECIMAL(10,2) DEFAULT 0,
    tip_amount DECIMAL(10,2) DEFAULT 0,
    total DECIMAL(10,2) NOT NULL DEFAULT 0,
    status ENUM('draft', 'open', 'sent', 'preparing', 'ready', 'delivered', 'paid', 'cancelled') DEFAULT 'draft',
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    special_instructions TEXT,
    kitchen_notes TEXT,
    estimated_ready_time TIMESTAMP NULL,
    sent_to_kitchen_at TIMESTAMP NULL,
    ready_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,
    paid_at TIMESTAMP NULL,
    cancelled_at TIMESTAMP NULL,
    cancellation_reason TEXT,
    cancelled_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE RESTRICT,
    FOREIGN KEY (table_id) REFERENCES tables(id) ON DELETE SET NULL,
    FOREIGN KEY (shift_id) REFERENCES shifts(id) ON DELETE RESTRICT,
    FOREIGN KEY (waiter_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (discount_id) REFERENCES discounts(id) ON DELETE SET NULL,
    FOREIGN KEY (discount_applied_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (cancelled_by) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_branch_status (branch_id, status),
    INDEX idx_order_number (order_number),
    INDEX idx_waiter_shift (waiter_id, shift_id),
    INDEX idx_table_status (table_id, status),
    INDEX idx_created_at (created_at),
    INDEX idx_status_priority (status, priority)
) ENGINE=InnoDB CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de items de órdenes
CREATE TABLE order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    cost_price DECIMAL(10,2) DEFAULT 0,
    special_instructions TEXT,
    status ENUM('pending', 'sent', 'preparing', 'ready', 'served', 'cancelled') DEFAULT 'pending',
    position_in_order INT DEFAULT 0,
    sent_at TIMESTAMP NULL,
    started_preparing_at TIMESTAMP NULL,
    ready_at TIMESTAMP NULL,
    served_at TIMESTAMP NULL,
    cancelled_at TIMESTAMP NULL,
    cancellation_reason TEXT,
    cancelled_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    FOREIGN KEY (cancelled_by) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_order_status (order_id, status),
    INDEX idx_product_id (product_id),
    INDEX idx_status_ready (status, ready_at)
) ENGINE=InnoDB CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de modificadores aplicados a items
CREATE TABLE order_item_modifiers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_item_id INT NOT NULL,
    modifier_id INT NOT NULL,
    modifier_name VARCHAR(100) NOT NULL COMMENT 'Snapshot del nombre al momento de la orden',
    price_adjustment DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (order_item_id) REFERENCES order_items(id) ON DELETE CASCADE,
    FOREIGN KEY (modifier_id) REFERENCES modifiers(id) ON DELETE RESTRICT,
    
    INDEX idx_order_item (order_item_id)
) ENGINE=InnoDB CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- migration_006_financial.sql
-- Sistema financiero
-- =====================================================

-- Tabla de métodos de pago
CREATE TABLE payment_methods (
    id INT PRIMARY KEY AUTO_INCREMENT,
    branch_id INT NOT NULL,
    name VARCHAR(50) NOT NULL COMMENT 'Efectivo, Tarjeta, Transferencia, etc.',
    code VARCHAR(20) UNIQUE NOT NULL,
    type ENUM('cash', 'card', 'transfer', 'digital', 'credit') NOT NULL,
    requires_reference BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_branch_code (branch_id, code),
    INDEX idx_branch_active (branch_id, is_active)
) ENGINE=InnoDB CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de pagos
CREATE TABLE payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    shift_id INT NOT NULL,
    payment_method_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    received_amount DECIMAL(10,2) COMMENT 'Cantidad recibida (para calcular cambio)',
    change_amount DECIMAL(10,2) DEFAULT 0,
    reference VARCHAR(100) COMMENT 'Referencia de pago (tarjeta/transfer)',
    authorization_code VARCHAR(50),
    terminal_id VARCHAR(20),
    transaction_id VARCHAR(100),
    processed_by INT NOT NULL,
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'completed',
    notes TEXT,
    processed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE RESTRICT,
    FOREIGN KEY (shift_id) REFERENCES shifts(id) ON DELETE RESTRICT,
    FOREIGN KEY (payment_method_id) REFERENCES payment_methods(id) ON DELETE RESTRICT,
    FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE RESTRICT,
    
    INDEX idx_order_id (order_id),
    INDEX idx_shift_method (shift_id, payment_method_id),
    INDEX idx_processed_at (processed_at),
    INDEX idx_reference (reference)
) ENGINE=InnoDB CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de propinas
CREATE TABLE tips (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NULL,
    waiter_id INT NOT NULL,
    shift_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    percentage DECIMAL(5,2) COMMENT 'Porcentaje de propina calculado',
    tip_type ENUM('order', 'additional', 'cash') DEFAULT 'order',
    payment_method ENUM('cash', 'card', 'included') DEFAULT 'cash',
    notes TEXT,
    added_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
    FOREIGN KEY (waiter_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (shift_id) REFERENCES shifts(id) ON DELETE RESTRICT,
    FOREIGN KEY (added_by) REFERENCES users(id) ON DELETE RESTRICT,
    
    INDEX idx_waiter_shift (waiter_id, shift_id),
    INDEX idx_order_id (order_id),
    INDEX idx_shift_type (shift_id, tip_type)
) ENGINE=InnoDB CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de movimientos de efectivo
CREATE TABLE cash_movements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    shift_id INT NOT NULL,
    type ENUM('withdrawal', 'expense', 'purchase', 'deposit', 'tip_payout', 'correction') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    reason VARCHAR(200) NOT NULL,
    description TEXT,
    reference VARCHAR(100),
    supplier VARCHAR(100) COMMENT 'Para compras',
    category VARCHAR(50) COMMENT 'Categoría del gasto',
    authorized_by INT NOT NULL,
    processed_by INT NOT NULL,
    receipt_url VARCHAR(500) COMMENT 'URL del comprobante escaneado',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (shift_id) REFERENCES shifts(id) ON DELETE RESTRICT,
    FOREIGN KEY (authorized_by) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE RESTRICT,
    
    INDEX idx_shift_type (shift_id, type),
    INDEX idx_authorized_by (authorized_by),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- migration_007_additional_features.sql
-- Características adicionales
-- =====================================================

-- Tabla de encuestas de satisfacción
CREATE TABLE satisfaction_surveys (
    id INT PRIMARY KEY AUTO_INCREMENT,
    branch_id INT NOT NULL,
    order_id INT NULL,
    waiter_id INT NULL,
    customer_name VARCHAR(100),
    customer_email VARCHAR(100),
    customer_phone VARCHAR(20),
    food_rating INT CHECK (food_rating BETWEEN 1 AND 5),
    service_rating INT CHECK (service_rating BETWEEN 1 AND 5),
    ambiance_rating INT CHECK (ambiance_rating BETWEEN 1 AND 5),
    cleanliness_rating INT CHECK (cleanliness_rating BETWEEN 1 AND 5),
    overall_rating INT CHECK (overall_rating BETWEEN 1 AND 5),
    would_recommend BOOLEAN,
    comments TEXT,
    suggestions TEXT,
    survey_method ENUM('tablet', 'qr', 'email', 'phone') DEFAULT 'tablet',
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
    FOREIGN KEY (waiter_id) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_branch_date (branch_id, completed_at),
    INDEX idx_waiter_rating (waiter_id, overall_rating),
    INDEX idx_overall_rating (overall_rating)
) ENGINE=InnoDB CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de configuración de reparto de propinas
CREATE TABLE tip_distribution_configs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    branch_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    type ENUM('total', 'percentage', 'forced_percentage') NOT NULL,
    waiter_percentage DECIMAL(5,2) DEFAULT 100.00,
    kitchen_percentage DECIMAL(5,2) DEFAULT 0.00,
    bar_percentage DECIMAL(5,2) DEFAULT 0.00,
    admin_percentage DECIMAL(5,2) DEFAULT 0.00,
    min_tip_percentage DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Para tipo forced_percentage',
    description TEXT,
    is_default BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_branch_default (branch_id, is_default),
    INDEX idx_branch_active (branch_id, is_active)
) ENGINE=InnoDB CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de sesiones de usuario (para control de sesiones activas)
CREATE TABLE user_sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INT NOT NULL,
    branch_id INT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    device_type ENUM('desktop', 'tablet', 'mobile') DEFAULT 'desktop',
    is_active BOOLEAN DEFAULT TRUE,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
    
    INDEX idx_user_active (user_id, is_active),
    INDEX idx_expires_at (expires_at),
    INDEX idx_last_activity (last_activity)
) ENGINE=InnoDB CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- migration_008_indexes_and_triggers.sql
-- Índices adicionales y triggers
-- =====================================================

-- Índices adicionales para rendimiento
CREATE INDEX idx_orders_branch_date ON orders(branch_id, created_at);
CREATE INDEX idx_order_items_product_date ON order_items(product_id, created_at);
CREATE INDEX idx_payments_shift_method_date ON payments(shift_id, payment_method_id, created_at);
CREATE INDEX idx_tips_waiter_date ON tips(waiter_id, created_at);
CREATE INDEX idx_users_branch_role_active ON users(branch_id, role_id, is_active);
CREATE INDEX idx_products_category_available ON products(category_id, is_available, is_active);

-- Trigger para actualizar totales de orden
DELIMITER $$
CREATE TRIGGER update_order_totals_after_item_insert
    AFTER INSERT ON order_items
    FOR EACH ROW
BEGIN
    UPDATE orders 
    SET subtotal = (
        SELECT COALESCE(SUM(total_price), 0) 
        FROM order_items 
        WHERE order_id = NEW.order_id AND status != 'cancelled'
    ),
    total = subtotal - discount_amount + tax_amount + service_charge + delivery_fee + tip_amount
    WHERE id = NEW.order_id;
END$$

CREATE TRIGGER update_order_totals_after_item_update
    AFTER UPDATE ON order_items
    FOR EACH ROW
BEGIN
    UPDATE orders 
    SET subtotal = (
        SELECT COALESCE(SUM(total_price), 0) 
        FROM order_items 
        WHERE order_id = NEW.order_id AND status != 'cancelled'
    ),
    total = subtotal - discount_amount + tax_amount + service_charge + delivery_fee + tip_amount
    WHERE id = NEW.order_id;
END$

CREATE TRIGGER update_order_totals_after_item_delete
    AFTER DELETE ON order_items
    FOR EACH ROW
BEGIN
    UPDATE orders 
    SET subtotal = (
        SELECT COALESCE(SUM(total_price), 0) 
        FROM order_items 
        WHERE order_id = OLD.order_id AND status != 'cancelled'
    ),
    total = subtotal - discount_amount + tax_amount + service_charge + delivery_fee + tip_amount
    WHERE id = OLD.order_id;
END$

-- Trigger para actualizar estadísticas de turno
CREATE TRIGGER update_shift_stats_after_payment
    AFTER INSERT ON payments
    FOR EACH ROW
BEGIN
    UPDATE shifts 
    SET total_sales = (
        SELECT COALESCE(SUM(amount), 0) 
        FROM payments 
        WHERE shift_id = NEW.shift_id AND status = 'completed'
    )
    WHERE id = NEW.shift_id;
END$

-- Trigger para actualizar conteo de órdenes en turno
CREATE TRIGGER update_shift_orders_after_order_paid
    AFTER UPDATE ON orders
    FOR EACH ROW
BEGIN
    IF NEW.status = 'paid' AND OLD.status != 'paid' THEN
        UPDATE shifts 
        SET total_orders = (
            SELECT COUNT(*) 
            FROM orders 
            WHERE shift_id = NEW.shift_id AND status = 'paid'
        )
        WHERE id = NEW.shift_id;
    END IF;
END$

-- Trigger para registrar auditoría automáticamente
CREATE TRIGGER audit_users_insert
    AFTER INSERT ON users
    FOR EACH ROW
BEGIN
    INSERT INTO audit_logs (user_id, action, table_name, record_id, new_values, ip_address)
    VALUES (NEW.id, 'CREATE', 'users', NEW.id, 
            JSON_OBJECT('username', NEW.username, 'email', NEW.email, 'role_id', NEW.role_id), 
            @current_ip);
END$

CREATE TRIGGER audit_users_update
    AFTER UPDATE ON users
    FOR EACH ROW
BEGIN
    INSERT INTO audit_logs (user_id, action, table_name, record_id, old_values, new_values, ip_address)
    VALUES (NEW.id, 'UPDATE', 'users', NEW.id, 
            JSON_OBJECT('username', OLD.username, 'email', OLD.email, 'role_id', OLD.role_id),
            JSON_OBJECT('username', NEW.username, 'email', NEW.email, 'role_id', NEW.role_id),
            @current_ip);
END$

DELIMITER ;

-- =====================================================
-- migration_009_seeders.sql
-- Datos iniciales del sistema
-- =====================================================

-- Insertar roles base del sistema
INSERT INTO roles (name, display_name, description, permissions) VALUES
('BOSS', 'Dueño', 'Acceso completo al sistema', 
 '{"users": ["create", "read", "update", "delete"], "products": ["create", "read", "update", "delete"], "orders": ["create", "read", "update", "delete"], "reports": ["all"], "financial": ["all"], "settings": ["all"]}'),

('MANAGER', 'Gerente', 'Administración de sucursal', 
 '{"users": ["create", "read", "update"], "products": ["create", "read", "update", "delete"], "orders": ["create", "read", "update"], "reports": ["daily", "weekly"], "financial": ["daily"], "settings": ["branch"]}'),

('CAJERO', 'Cajero', 'Operaciones de caja y pagos', 
 '{"orders": ["create", "read", "update"], "payments": ["create", "read"], "shifts": ["open", "close"], "reports": ["shift"], "discounts": ["apply_with_auth"]}'),

('MESERO', 'Mesero', 'Toma de órdenes y servicio', 
 '{"orders": ["create", "read", "update_own"], "tables": ["read", "update"], "tips": ["view_own"], "surveys": ["manage"]}');

-- Insertar métodos de pago base
INSERT INTO payment_methods (branch_id, name, code, type, requires_reference, sort_order) VALUES
(1, 'Efectivo', 'CASH', 'cash', FALSE, 1),
(1, 'Tarjeta de Débito', 'DEBIT', 'card', TRUE, 2),
(1, 'Tarjeta de Crédito', 'CREDIT', 'card', TRUE, 3),
(1, 'Transferencia', 'TRANSFER', 'transfer', TRUE, 4),
(1, 'Uber Eats', 'UBER', 'digital', TRUE, 5),
(1, 'Rappi', 'RAPPI', 'digital', TRUE, 6),
(1, 'DiDi Food', 'DIDI', 'digital', TRUE, 7),
(1, 'Crédito Empleado', 'EMP_CREDIT', 'credit', FALSE, 8);

-- Insertar configuración base de reparto de propinas
INSERT INTO tip_distribution_configs (branch_id, name, type, waiter_percentage, kitchen_percentage, bar_percentage, admin_percentage, is_default) VALUES
(1, 'Propina Total al Mesero', 'total', 100.00, 0.00, 0.00, 0.00, TRUE),
(1, 'Reparto por Porcentajes', 'percentage', 70.00, 20.00, 5.00, 5.00, FALSE),
(1, 'Porcentaje Forzoso 10%', 'forced_percentage', 50.00, 30.00, 10.00, 10.00, FALSE);

-- Insertar usuario administrador inicial
INSERT INTO users (username, email, password_hash, first_name, last_name, role_id, branch_id, is_active) VALUES
('admin', 'admin@robs.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador', 'Sistema', 1, NULL, TRUE);

-- =====================================================
-- INSTRUCCIONES DE EJECUCIÓN
-- =====================================================

/*
ORDEN DE EJECUCIÓN:

1. Ejecutar cada migración en orden numérico
2. Verificar que no hay errores antes de continuar
3. Ejecutar el seeder al final

COMANDOS RECOMENDADOS:

-- Verificar estructura
SHOW TABLES;
SELECT COUNT(*) as total_tables FROM information_schema.tables 
WHERE table_schema = 'robs_db';

-- Verificar relaciones
SELECT 
    TABLE_NAME,
    CONSTRAINT_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE 
WHERE REFERENCED_TABLE_SCHEMA = 'robs_db'
ORDER BY TABLE_NAME;

-- Verificar datos iniciales
SELECT r.name as role, COUNT(u.id) as users_count 
FROM roles r 
LEFT JOIN users u ON r.id = u.role_id 
GROUP BY r.id, r.name;

-- Verificar triggers
SHOW TRIGGERS;

-- Verificar índices importantes
SHOW INDEX FROM orders;
SHOW INDEX FROM order_items;
SHOW INDEX FROM products;
SHOW INDEX FROM users;

NOTAS IMPORTANTES:

1. Cambiar la contraseña del usuario admin después del primer login
2. Configurar la primera sucursal antes de crear usuarios
3. Los triggers se encargan de mantener totales actualizados
4. La auditoría se registra automáticamente para acciones críticas
5. Los índices están optimizados para las consultas más frecuentes
6. Las tablas usan InnoDB para soporte completo de transacciones
7. Los campos JSON requieren MySQL 5.7+ o MariaDB 10.2+

RECOMENDACIONES DE RENDIMIENTO:

1. Configurar max_connections según la carga esperada
2. Ajustar innodb_buffer_pool_size al 70-80% de la RAM disponible
3. Habilitar query_cache para consultas repetitivas
4. Configurar logs lentos para optimizar consultas problemáticas
5. Realizar backup diario de la base de datos
6. Monitorear el crecimiento de la tabla audit_logs
7. Implementar particionado por fecha en tablas grandes si es necesario

SEGURIDAD:

1. Crear usuario específico para la aplicación (no usar root)
2. Configurar SSL para conexiones remotas
3. Implementar rate limiting en la aplicación
4. Configurar firewall para acceso restringido al puerto 3306
5. Habilitar auditoría a nivel de MySQL para accesos administrativos
6. Rotar contraseñas regularmente
7. Implementar backup encriptado
*/
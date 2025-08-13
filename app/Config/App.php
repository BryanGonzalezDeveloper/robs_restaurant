<?php

/**
 * ROBS - Configuración General de la Aplicación
 * 
 * Define las configuraciones principales del sistema
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Configuración básica de la aplicación
    |--------------------------------------------------------------------------
    */
    'name' => $_ENV['APP_NAME'] ?? 'ROBS',
    'description' => 'Restaurant Order, Billing & Sales System',
    'version' => '1.0.0',
    'env' => $_ENV['APP_ENV'] ?? 'production',
    'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'url' => $_ENV['APP_URL'] ?? 'http://localhost',
    'timezone' => $_ENV['TIMEZONE'] ?? 'America/Mexico_City',
    'locale' => $_ENV['LOCALE'] ?? 'es_MX',
    
    /*
    |--------------------------------------------------------------------------
    | Configuración de encriptación
    |--------------------------------------------------------------------------
    */
    'encryption' => [
        'key' => $_ENV['ENCRYPTION_KEY'] ?? null,
        'cipher' => 'AES-256-CBC',
        'hash_algo' => 'sha256'
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de sesiones
    |--------------------------------------------------------------------------
    */
    'session' => [
        'lifetime' => (int) ($_ENV['SESSION_LIFETIME'] ?? 480), // minutos
        'name' => 'ROBS_SESSION',
        'path' => '/',
        'domain' => null,
        'secure' => filter_var($_ENV['SESSION_SECURE'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'http_only' => filter_var($_ENV['SESSION_HTTP_ONLY'] ?? true, FILTER_VALIDATE_BOOLEAN),
        'same_site' => 'Lax',
        'storage_path' => STORAGE_PATH . '/sessions'
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de logging
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'level' => $_ENV['LOG_LEVEL'] ?? 'info', // debug, info, notice, warning, error, critical
        'path' => STORAGE_PATH . '/logs',
        'max_files' => (int) ($_ENV['LOG_MAX_FILES'] ?? 30),
        'max_size' => $_ENV['LOG_MAX_SIZE'] ?? '50MB',
        'channels' => [
            'app' => STORAGE_PATH . '/logs/app.log',
            'error' => STORAGE_PATH . '/logs/error.log',
            'sql' => STORAGE_PATH . '/logs/sql.log',
            'auth' => STORAGE_PATH . '/logs/auth.log',
            'orders' => STORAGE_PATH . '/logs/orders.log',
            'payments' => STORAGE_PATH . '/logs/payments.log'
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de archivos subidos
    |--------------------------------------------------------------------------
    */
    'uploads' => [
        'max_size' => (int) ($_ENV['UPLOAD_MAX_SIZE'] ?? 10485760), // 10MB en bytes
        'allowed_types' => explode(',', $_ENV['ALLOWED_IMAGES'] ?? 'jpg,jpeg,png,gif,webp'),
        'path' => PUBLIC_PATH . '/assets/uploads',
        'url_prefix' => '/assets/uploads',
        'directories' => [
            'products' => 'products',
            'logos' => 'logos', 
            'receipts' => 'receipts',
            'temp' => 'temp'
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de seguridad
    |--------------------------------------------------------------------------
    */
    'security' => [
        'password_min_length' => (int) ($_ENV['PASSWORD_MIN_LENGTH'] ?? 8),
        'max_login_attempts' => (int) ($_ENV['MAX_LOGIN_ATTEMPTS'] ?? 5),
        'lockout_duration' => (int) ($_ENV['LOCKOUT_DURATION'] ?? 900), // 15 minutos
        'csrf_token_lifetime' => 3600, // 1 hora
        'require_https' => filter_var($_ENV['REQUIRE_HTTPS'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'allowed_hosts' => explode(',', $_ENV['ALLOWED_HOSTS'] ?? 'localhost'),
        'headers' => [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'X-XSS-Protection' => '1; mode=block',
            'Referrer-Policy' => 'strict-origin-when-cross-origin'
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de email
    |--------------------------------------------------------------------------
    */
    'mail' => [
        'driver' => $_ENV['MAIL_DRIVER'] ?? 'smtp',
        'host' => $_ENV['MAIL_HOST'] ?? 'smtp.gmail.com',
        'port' => (int) ($_ENV['MAIL_PORT'] ?? 587),
        'username' => $_ENV['MAIL_USERNAME'] ?? '',
        'password' => $_ENV['MAIL_PASSWORD'] ?? '',
        'encryption' => $_ENV['MAIL_ENCRYPTION'] ?? 'tls',
        'from_address' => $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@robs.local',
        'from_name' => $_ENV['MAIL_FROM_NAME'] ?? 'ROBS System',
        'timeout' => 30
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de impresoras
    |--------------------------------------------------------------------------
    */
    'printers' => [
        'default_port' => 9100,
        'timeout' => 10, // segundos
        'retry_attempts' => 3,
        'paper_width' => 80, // mm
        'char_per_line' => 48,
        'test_print_enabled' => true,
        'auto_cut' => true,
        'cash_drawer' => [
            'enabled' => true,
            'pulse_duration' => 120, // ms
            'pulse_count' => 2
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de reportes
    |--------------------------------------------------------------------------
    */
    'reports' => [
        'cache_enabled' => true,
        'cache_ttl' => 300, // 5 minutos
        'export_formats' => ['pdf', 'excel', 'csv'],
        'max_records' => 10000,
        'date_format' => 'd/m/Y',
        'datetime_format' => 'd/m/Y H:i:s',
        'currency_format' => [
            'symbol' => '$',
            'position' => 'before', // before, after
            'thousands_separator' => ',',
            'decimal_separator' => '.',
            'decimals' => 2
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de POS
    |--------------------------------------------------------------------------
    */
    'pos' => [
        'order_timeout' => 1800, // 30 minutos
        'auto_print_kitchen' => true,
        'auto_print_receipt' => false,
        'allow_split_payment' => true,
        'require_customer_count' => true,
        'default_tax_rate' => 0.16, // 16% IVA
        'rounding_precision' => 2,
        'table_management' => [
            'enabled' => true,
            'auto_assign' => false,
            'merge_tables' => true
        ],
        'kitchen_display' => [
            'enabled' => true,
            'auto_refresh' => 30, // segundos
            'show_preparation_time' => true,
            'alert_overdue' => true
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de facturación
    |--------------------------------------------------------------------------
    */
    'billing' => [
        'enabled' => filter_var($_ENV['BILLING_ENABLED'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'auto_invoice' => false,
        'invoice_series' => 'A',
        'sat_environment' => $_ENV['SAT_ENVIRONMENT'] ?? 'test', // test, production
        'pac_credentials' => [
            'username' => $_ENV['PAC_USERNAME'] ?? '',
            'password' => $_ENV['PAC_PASSWORD'] ?? '',
            'url' => $_ENV['PAC_URL'] ?? ''
        ],
        'certificates' => [
            'cer_path' => $_ENV['SAT_CER_PATH'] ?? '',
            'key_path' => $_ENV['SAT_KEY_PATH'] ?? '',
            'key_password' => $_ENV['SAT_KEY_PASSWORD'] ?? ''
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de APIs externas
    |--------------------------------------------------------------------------
    */
    'external_apis' => [
        'delivery_platforms' => [
            'uber_eats' => [
                'enabled' => false,
                'webhook_url' => '',
                'api_key' => $_ENV['UBER_EATS_API_KEY'] ?? ''
            ],
            'rappi' => [
                'enabled' => false,
                'webhook_url' => '',
                'api_key' => $_ENV['RAPPI_API_KEY'] ?? ''
            ],
            'didi' => [
                'enabled' => false,
                'webhook_url' => '',
                'api_key' => $_ENV['DIDI_API_KEY'] ?? ''
            ]
        ],
        'payment_gateways' => [
            'stripe' => [
                'enabled' => false,
                'public_key' => $_ENV['STRIPE_PUBLIC_KEY'] ?? '',
                'secret_key' => $_ENV['STRIPE_SECRET_KEY'] ?? ''
            ],
            'paypal' => [
                'enabled' => false,
                'client_id' => $_ENV['PAYPAL_CLIENT_ID'] ?? '',
                'client_secret' => $_ENV['PAYPAL_CLIENT_SECRET'] ?? ''
            ]
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de cache
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'default' => $_ENV['CACHE_DRIVER'] ?? 'file',
        'stores' => [
            'file' => [
                'driver' => 'file',
                'path' => STORAGE_PATH . '/cache'
            ],
            'redis' => [
                'driver' => 'redis',
                'host' => $_ENV['REDIS_HOST'] ?? '127.0.0.1',
                'port' => $_ENV['REDIS_PORT'] ?? 6379,
                'password' => $_ENV['REDIS_PASSWORD'] ?? null,
                'database' => $_ENV['REDIS_DB'] ?? 0
            ]
        ],
        'prefix' => 'robs_cache_',
        'ttl' => 3600 // 1 hora por defecto
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de notificaciones
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        'channels' => ['email', 'sms', 'push'],
        'email_notifications' => [
            'shift_close' => true,
            'low_stock' => true,
            'daily_report' => true,
            'fraud_alert' => true,
            'system_error' => true
        ],
        'sms' => [
            'enabled' => false,
            'provider' => 'twilio', // twilio, nexmo
            'api_key' => $_ENV['SMS_API_KEY'] ?? '',
            'api_secret' => $_ENV['SMS_API_SECRET'] ?? '',
            'from_number' => $_ENV['SMS_FROM_NUMBER'] ?? ''
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de respaldo
    |--------------------------------------------------------------------------
    */
    'backup' => [
        'enabled' => filter_var($_ENV['BACKUP_ENABLED'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'frequency' => $_ENV['BACKUP_FREQUENCY'] ?? 'daily',
        'retention_days' => (int) ($_ENV['BACKUP_RETENTION'] ?? 30),
        'storage' => [
            'local' => STORAGE_PATH . '/backups',
            'cloud' => $_ENV['BACKUP_CLOUD_PATH'] ?? null
        ],
        'compress' => true,
        'encrypt' => true
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de desarrollo
    |--------------------------------------------------------------------------
    */
    'development' => [
        'show_debug_bar' => $_ENV['APP_ENV'] === 'development',
        'log_queries' => $_ENV['APP_ENV'] === 'development',
        'mock_printers' => $_ENV['APP_ENV'] === 'development',
        'test_data_enabled' => $_ENV['APP_ENV'] === 'development'
    ]
];
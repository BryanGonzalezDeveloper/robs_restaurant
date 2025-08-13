<?php

/**
 * ROBS - Configuración de Base de Datos
 * 
 * Define las configuraciones de conexión a la base de datos
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Conexión por defecto
    |--------------------------------------------------------------------------
    |
    | Define cuál conexión de base de datos usar por defecto
    |
    */
    'default' => $_ENV['DB_CONNECTION'] ?? 'mysql',

    /*
    |--------------------------------------------------------------------------
    | Configuraciones de conexión
    |--------------------------------------------------------------------------
    |
    | Aquí puedes configurar múltiples conexiones de base de datos para
    | diferentes ambientes o propósitos específicos
    |
    */
    'connections' => [
        
        'mysql' => [
            'driver' => 'mysql',
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'port' => $_ENV['DB_PORT'] ?? '3306',
            'database' => $_ENV['DB_DATABASE'] ?? 'robs_db',
            'username' => $_ENV['DB_USERNAME'] ?? 'root',
            'password' => $_ENV['DB_PASSWORD'] ?? '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => 'InnoDB',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ]
        ],

        'testing' => [
            'driver' => 'mysql',
            'host' => $_ENV['TEST_DB_HOST'] ?? 'localhost',
            'port' => $_ENV['TEST_DB_PORT'] ?? '3306',
            'database' => $_ENV['TEST_DB_DATABASE'] ?? 'robs_test',
            'username' => $_ENV['TEST_DB_USERNAME'] ?? 'root',
            'password' => $_ENV['TEST_DB_PASSWORD'] ?? '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => 'InnoDB',
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Pool de conexiones
    |--------------------------------------------------------------------------
    |
    | Configuración para pool de conexiones persistentes
    |
    */
    'pool' => [
        'enabled' => $_ENV['DB_POOL_ENABLED'] ?? false,
        'max_connections' => $_ENV['DB_POOL_MAX'] ?? 10,
        'min_connections' => $_ENV['DB_POOL_MIN'] ?? 2,
        'timeout' => $_ENV['DB_POOL_TIMEOUT'] ?? 30
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de migraciones
    |--------------------------------------------------------------------------
    |
    | Configuración para el sistema de migraciones
    |
    */
    'migrations' => [
        'table' => 'migrations',
        'path' => ROOT_PATH . '/database/migrations',
        'auto_run' => $_ENV['AUTO_RUN_MIGRATIONS'] ?? false
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de logging
    |--------------------------------------------------------------------------
    |
    | Configuración para logging de consultas SQL
    |
    */
    'logging' => [
        'enabled' => $_ENV['DB_LOG_ENABLED'] ?? false,
        'slow_query_time' => $_ENV['DB_SLOW_QUERY_TIME'] ?? 2.0, // segundos
        'log_file' => STORAGE_PATH . '/logs/sql.log',
        'max_file_size' => $_ENV['DB_LOG_MAX_SIZE'] ?? '50MB'
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de cache de consultas
    |--------------------------------------------------------------------------
    |
    | Configuración para cache de resultados de consultas
    |
    */
    'cache' => [
        'enabled' => $_ENV['DB_CACHE_ENABLED'] ?? false,
        'ttl' => $_ENV['DB_CACHE_TTL'] ?? 300, // 5 minutos
        'prefix' => 'robs_query_',
        'store' => 'file' // file, redis, memcached
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de réplicas
    |--------------------------------------------------------------------------
    |
    | Configuración para lecturas en réplicas y escrituras en master
    |
    */
    'read_write_split' => [
        'enabled' => $_ENV['DB_READ_WRITE_SPLIT'] ?? false,
        'read_connections' => [
            'slave1' => [
                'host' => $_ENV['DB_READ_HOST_1'] ?? 'localhost',
                'port' => $_ENV['DB_READ_PORT_1'] ?? '3306',
                'database' => $_ENV['DB_DATABASE'] ?? 'robs_db',
                'username' => $_ENV['DB_READ_USERNAME_1'] ?? 'root',
                'password' => $_ENV['DB_READ_PASSWORD_1'] ?? '',
            ]
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de backup automático
    |--------------------------------------------------------------------------
    |
    | Configuración para backups automáticos de la base de datos
    |
    */
    'backup' => [
        'enabled' => $_ENV['DB_BACKUP_ENABLED'] ?? false,
        'frequency' => $_ENV['DB_BACKUP_FREQUENCY'] ?? 'daily', // hourly, daily, weekly
        'retention_days' => $_ENV['DB_BACKUP_RETENTION'] ?? 30,
        'path' => STORAGE_PATH . '/backups',
        'compress' => true,
        'tables' => [
            'exclude' => ['user_sessions', 'audit_logs'], // Tablas a excluir del backup
            'include_data' => true, // Incluir datos o solo estructura
        ]
    ]
];
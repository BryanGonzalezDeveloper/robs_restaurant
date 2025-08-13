<?php

/**
 * ROBS - Sistema de Base de Datos
 * 
 * Maneja la conexión a MySQL usando PDO con patrón Singleton
 */
class Database
{
    private static ?Database $instance = null;
    private ?PDO $connection = null;
    private array $config;
    
    /**
     * Constructor privado para patrón Singleton
     */
    private function __construct()
    {
        $this->config = [
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'port' => $_ENV['DB_PORT'] ?? '3306',
            'database' => $_ENV['DB_DATABASE'] ?? 'robs_db',
            'username' => $_ENV['DB_USERNAME'] ?? 'root',
            'password' => $_ENV['DB_PASSWORD'] ?? '',
            'charset' => 'utf8mb4',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ]
        ];
        
        $this->connect();
    }
    
    /**
     * Obtener instancia única de la base de datos
     */
    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Obtener conexión PDO
     */
    public function getConnection(): PDO
    {
        if ($this->connection === null) {
            $this->connect();
        }
        
        return $this->connection;
    }
    
    /**
     * Establecer conexión a la base de datos
     */
    private function connect(): void
    {
        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $this->config['host'],
                $this->config['port'],
                $this->config['database'],
                $this->config['charset']
            );
            
            $this->connection = new PDO(
                $dsn,
                $this->config['username'],
                $this->config['password'],
                $this->config['options']
            );
            
            // Configurar zona horaria de MySQL
            $timezone = $_ENV['TIMEZONE'] ?? 'America/Mexico_City';
            $this->connection->exec("SET time_zone = '{$timezone}'");
            
            $this->logQuery("Conexión establecida exitosamente");
            
        } catch (PDOException $e) {
            $this->logError("Error de conexión: " . $e->getMessage());
            throw new Exception("Error al conectar con la base de datos: " . $e->getMessage());
        }
    }
    
    /**
     * Ejecutar consulta preparada
     */
    public function query(string $sql, array $params = []): PDOStatement
    {
        try {
            $this->logQuery($sql, $params);
            
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            
            return $stmt;
            
        } catch (PDOException $e) {
            $this->logError("Error en consulta: " . $e->getMessage(), $sql, $params);
            throw new Exception("Error en consulta: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener un solo registro
     */
    public function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->query($sql, $params);
        $result = $stmt->fetch();
        
        return $result ?: null;
    }
    
    /**
     * Obtener múltiples registros
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener valor de una columna específica
     */
    public function fetchColumn(string $sql, array $params = [], int $column = 0)
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchColumn($column);
    }
    
    /**
     * Insertar registro y devolver ID
     */
    public function insert(string $table, array $data): int
    {
        $columns = array_keys($data);
        $placeholders = array_map(fn($col) => ":$col", $columns);
        
        $sql = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            $table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );
        
        $params = [];
        foreach ($data as $key => $value) {
            $params[":$key"] = $value;
        }
        
        $this->query($sql, $params);
        
        return (int) $this->connection->lastInsertId();
    }
    
    /**
     * Actualizar registros
     */
    public function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $setParts = [];
        $params = [];
        
        foreach ($data as $key => $value) {
            $setParts[] = "$key = :set_$key";
            $params[":set_$key"] = $value;
        }
        
        // Combinar parámetros de SET y WHERE
        $params = array_merge($params, $whereParams);
        
        $sql = sprintf(
            "UPDATE %s SET %s WHERE %s",
            $table,
            implode(', ', $setParts),
            $where
        );
        
        $stmt = $this->query($sql, $params);
        
        return $stmt->rowCount();
    }
    
    /**
     * Eliminar registros
     */
    public function delete(string $table, string $where, array $params = []): int
    {
        $sql = "DELETE FROM $table WHERE $where";
        $stmt = $this->query($sql, $params);
        
        return $stmt->rowCount();
    }
    
    /**
     * Verificar si existe un registro
     */
    public function exists(string $table, string $where, array $params = []): bool
    {
        $sql = "SELECT 1 FROM $table WHERE $where LIMIT 1";
        $result = $this->fetchOne($sql, $params);
        
        return $result !== null;
    }
    
    /**
     * Contar registros
     */
    public function count(string $table, string $where = '1=1', array $params = []): int
    {
        $sql = "SELECT COUNT(*) FROM $table WHERE $where";
        return (int) $this->fetchColumn($sql, $params);
    }
    
    /**
     * Iniciar transacción
     */
    public function beginTransaction(): bool
    {
        $this->logQuery("BEGIN TRANSACTION");
        return $this->connection->beginTransaction();
    }
    
    /**
     * Confirmar transacción
     */
    public function commit(): bool
    {
        $this->logQuery("COMMIT");
        return $this->connection->commit();
    }
    
    /**
     * Revertir transacción
     */
    public function rollBack(): bool
    {
        $this->logQuery("ROLLBACK");
        return $this->connection->rollBack();
    }
    
    /**
     * Ejecutar múltiples consultas en transacción
     */
    public function transaction(callable $callback): mixed
    {
        $this->beginTransaction();
        
        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (Exception $e) {
            $this->rollBack();
            throw $e;
        }
    }
    
    /**
     * Obtener último ID insertado
     */
    public function lastInsertId(): int
    {
        return (int) $this->connection->lastInsertId();
    }
    
    /**
     * Escapar nombre de tabla/columna
     */
    public function escapeIdentifier(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }
    
    /**
     * Preparar lista de parámetros para IN clause
     */
    public function prepareInClause(array $values): array
    {
        $placeholders = [];
        $params = [];
        
        foreach ($values as $i => $value) {
            $placeholder = ":in_param_$i";
            $placeholders[] = $placeholder;
            $params[$placeholder] = $value;
        }
        
        return [
            'placeholders' => '(' . implode(', ', $placeholders) . ')',
            'params' => $params
        ];
    }
    
    /**
     * Obtener información de la tabla
     */
    public function getTableInfo(string $table): array
    {
        $sql = "DESCRIBE " . $this->escapeIdentifier($table);
        return $this->fetchAll($sql);
    }
    
    /**
     * Verificar si una tabla existe
     */
    public function tableExists(string $table): bool
    {
        $sql = "SHOW TABLES LIKE ?";
        $result = $this->fetchOne($sql, [$table]);
        
        return $result !== null;
    }
    
    /**
     * Obtener estadísticas de la conexión
     */
    public function getConnectionStats(): array
    {
        $stats = [];
        
        try {
            $stats['server_version'] = $this->fetchColumn("SELECT VERSION()");
            $stats['connection_id'] = $this->fetchColumn("SELECT CONNECTION_ID()");
            $stats['current_user'] = $this->fetchColumn("SELECT CURRENT_USER()");
            $stats['database'] = $this->fetchColumn("SELECT DATABASE()");
            $stats['charset'] = $this->fetchColumn("SELECT @@character_set_connection");
            $stats['timezone'] = $this->fetchColumn("SELECT @@time_zone");
        } catch (Exception $e) {
            $stats['error'] = $e->getMessage();
        }
        
        return $stats;
    }
    
    /**
     * Logging de consultas (solo en desarrollo)
     */
    private function logQuery(string $sql, array $params = []): void
    {
        if (($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
            $logMessage = date('Y-m-d H:i:s') . " - SQL: $sql";
            if (!empty($params)) {
                $logMessage .= " | Params: " . json_encode($params);
            }
            $logMessage .= "\n";
            
            $logFile = STORAGE_PATH . '/logs/sql.log';
            file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
        }
    }
    
    /**
     * Logging de errores
     */
    private function logError(string $message, string $sql = '', array $params = []): void
    {
        $logMessage = date('Y-m-d H:i:s') . " - ERROR: $message";
        if ($sql) {
            $logMessage .= " | SQL: $sql";
        }
        if (!empty($params)) {
            $logMessage .= " | Params: " . json_encode($params);
        }
        $logMessage .= "\n";
        
        $logFile = STORAGE_PATH . '/logs/error.log';
        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Prevenir clonación
     */
    private function __clone() {}
    
    /**
     * Prevenir deserialización
     */
    public function __wakeup()
    {
        throw new Exception("No se puede deserializar la instancia de Database");
    }
    
    /**
     * Cerrar conexión al destruir el objeto
     */
    public function __destruct()
    {
        $this->connection = null;
    }
}
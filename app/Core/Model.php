<?php

/**
 * ROBS - Modelo Base
 * 
 * Clase base para todos los modelos del sistema con ORM básico
 */
abstract class Model
{
    protected Database $db;
    protected string $table;
    protected string $primaryKey = 'id';
    protected array $fillable = [];
    protected array $guarded = ['id', 'created_at', 'updated_at'];
    protected bool $timestamps = true;
    protected array $casts = [];
    protected array $hidden = [];
    protected array $attributes = [];
    protected array $original = [];
    protected bool $exists = false;
    
    /**
     * Constructor del modelo
     */
    public function __construct(array $attributes = [])
    {
        $this->db = Database::getInstance();
        
        if (!empty($attributes)) {
            $this->fill($attributes);
        }
    }
    
    /**
     * Crear nuevo registro
     */
    public static function create(array $attributes): static
    {
        $instance = new static();
        $instance->fill($attributes);
        $instance->save();
        
        return $instance;
    }
    
    /**
     * Encontrar registro por ID
     */
    public static function find($id): ?static
    {
        $instance = new static();
        $sql = "SELECT * FROM {$instance->table} WHERE {$instance->primaryKey} = ? LIMIT 1";
        $data = $instance->db->fetchOne($sql, [$id]);
        
        if ($data) {
            return $instance->newFromDatabase($data);
        }
        
        return null;
    }
    
    /**
     * Encontrar registro por ID o lanzar excepción
     */
    public static function findOrFail($id): static
    {
        $result = static::find($id);
        
        if (!$result) {
            throw new Exception("Registro no encontrado con ID: $id");
        }
        
        return $result;
    }
    
    /**
     * Encontrar primer registro que coincida
     */
    public static function where(string $column, $operator, $value = null): ModelQueryBuilder
    {
        $instance = new static();
        $builder = new ModelQueryBuilder($instance);
        
        // Si solo se pasaron 2 parámetros, asumir operador '='
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }
        
        return $builder->where($column, $operator, $value);
    }
    
    /**
     * Obtener todos los registros
     */
    public static function all(): array
    {
        $instance = new static();
        $sql = "SELECT * FROM {$instance->table}";
        $results = $instance->db->fetchAll($sql);
        
        return array_map(fn($data) => $instance->newFromDatabase($data), $results);
    }
    
    /**
     * Obtener registros paginados
     */
    public static function paginate(int $perPage = 15, int $page = 1): array
    {
        $instance = new static();
        $offset = ($page - 1) * $perPage;
        
        // Obtener total de registros
        $total = $instance->db->count($instance->table);
        
        // Obtener registros de la página actual
        $sql = "SELECT * FROM {$instance->table} LIMIT ? OFFSET ?";
        $results = $instance->db->fetchAll($sql, [$perPage, $offset]);
        
        $items = array_map(fn($data) => $instance->newFromDatabase($data), $results);
        
        return [
            'data' => $items,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage),
            'from' => $offset + 1,
            'to' => min($offset + $perPage, $total)
        ];
    }
    
    /**
     * Guardar el modelo
     */
    public function save(): bool
    {
        if ($this->exists) {
            return $this->performUpdate();
        } else {
            return $this->performInsert();
        }
    }
    
    /**
     * Actualizar el modelo
     */
    public function update(array $attributes = []): bool
    {
        if (!empty($attributes)) {
            $this->fill($attributes);
        }
        
        return $this->save();
    }
    
    /**
     * Eliminar el modelo
     */
    public function delete(): bool
    {
        if (!$this->exists) {
            return false;
        }
        
        $id = $this->getAttribute($this->primaryKey);
        $result = $this->db->delete($this->table, "{$this->primaryKey} = ?", [$id]);
        
        if ($result > 0) {
            $this->exists = false;
            return true;
        }
        
        return false;
    }
    
    /**
     * Llenar atributos del modelo
     */
    public function fill(array $attributes): static
    {
        foreach ($attributes as $key => $value) {
            if ($this->isFillable($key)) {
                $this->setAttribute($key, $value);
            }
        }
        
        return $this;
    }
    
    /**
     * Verificar si un atributo es rellenable
     */
    protected function isFillable(string $key): bool
    {
        // Si hay lista de fillable, verificar que esté en ella
        if (!empty($this->fillable)) {
            return in_array($key, $this->fillable);
        }
        
        // Si no hay fillable, verificar que no esté en guarded
        return !in_array($key, $this->guarded);
    }
    
    /**
     * Establecer atributo
     */
    public function setAttribute(string $key, $value): void
    {
        // Aplicar mutator si existe
        $mutator = 'set' . $this->studly($key) . 'Attribute';
        if (method_exists($this, $mutator)) {
            $value = $this->$mutator($value);
        }
        
        $this->attributes[$key] = $value;
    }
    
    /**
     * Obtener atributo
     */
    public function getAttribute(string $key)
    {
        if (!array_key_exists($key, $this->attributes)) {
            return null;
        }
        
        $value = $this->attributes[$key];
        
        // Aplicar accessor si existe
        $accessor = 'get' . $this->studly($key) . 'Attribute';
        if (method_exists($this, $accessor)) {
            return $this->$accessor($value);
        }
        
        // Aplicar cast si está definido
        if (isset($this->casts[$key])) {
            return $this->castAttribute($key, $value);
        }
        
        return $value;
    }
    
    /**
     * Aplicar cast a un atributo
     */
    protected function castAttribute(string $key, $value)
    {
        if ($value === null) {
            return null;
        }
        
        $cast = $this->casts[$key];
        
        switch ($cast) {
            case 'int':
            case 'integer':
                return (int) $value;
                
            case 'real':
            case 'float':
            case 'double':
                return (float) $value;
                
            case 'string':
                return (string) $value;
                
            case 'bool':
            case 'boolean':
                return (bool) $value;
                
            case 'array':
            case 'json':
                return is_string($value) ? json_decode($value, true) : $value;
                
            case 'date':
            case 'datetime':
                return $value instanceof DateTime ? $value : new DateTime($value);
                
            default:
                return $value;
        }
    }
    
    /**
     * Realizar inserción
     */
    protected function performInsert(): bool
    {
        $attributes = $this->getAttributesForInsert();
        
        if ($this->timestamps) {
            $now = date('Y-m-d H:i:s');
            $attributes['created_at'] = $now;
            $attributes['updated_at'] = $now;
        }
        
        $id = $this->db->insert($this->table, $attributes);
        
        if ($id) {
            $this->setAttribute($this->primaryKey, $id);
            $this->exists = true;
            $this->syncOriginal();
            return true;
        }
        
        return false;
    }
    
    /**
     * Realizar actualización
     */
    protected function performUpdate(): bool
    {
        $id = $this->getAttribute($this->primaryKey);
        $attributes = $this->getAttributesForUpdate();
        
        if (empty($attributes)) {
            return true; // No hay cambios
        }
        
        if ($this->timestamps) {
            $attributes['updated_at'] = date('Y-m-d H:i:s');
        }
        
        $result = $this->db->update(
            $this->table,
            $attributes,
            "{$this->primaryKey} = ?",
            [$id]
        );
        
        if ($result >= 0) {
            $this->syncOriginal();
            return true;
        }
        
        return false;
    }
    
    /**
     * Obtener atributos para inserción
     */
    protected function getAttributesForInsert(): array
    {
        $attributes = [];
        
        foreach ($this->attributes as $key => $value) {
            if (!in_array($key, $this->guarded)) {
                // Convertir arrays/objetos a JSON si es necesario
                if (isset($this->casts[$key]) && in_array($this->casts[$key], ['array', 'json'])) {
                    $value = is_array($value) ? json_encode($value) : $value;
                }
                $attributes[$key] = $value;
            }
        }
        
        return $attributes;
    }
    
    /**
     * Obtener atributos para actualización (solo los que cambiaron)
     */
    protected function getAttributesForUpdate(): array
    {
        $attributes = [];
        
        foreach ($this->attributes as $key => $value) {
            if (!in_array($key, $this->guarded) && $this->isDirty($key)) {
                // Convertir arrays/objetos a JSON si es necesario
                if (isset($this->casts[$key]) && in_array($this->casts[$key], ['array', 'json'])) {
                    $value = is_array($value) ? json_encode($value) : $value;
                }
                $attributes[$key] = $value;
            }
        }
        
        return $attributes;
    }
    
    /**
     * Verificar si un atributo ha cambiado
     */
    public function isDirty(string $key): bool
    {
        return array_key_exists($key, $this->attributes) &&
               (!array_key_exists($key, $this->original) || 
                $this->attributes[$key] !== $this->original[$key]);
    }
    
    /**
     * Sincronizar atributos originales
     */
    protected function syncOriginal(): void
    {
        $this->original = $this->attributes;
    }
    
    /**
     * Crear instancia desde datos de base de datos
     */
    public function newFromDatabase(array $data): static
    {
        $instance = new static();
        $instance->setRawAttributes($data);
        $instance->exists = true;
        $instance->syncOriginal();
        
        return $instance;
    }
    
    /**
     * Establecer atributos sin mutators
     */
    protected function setRawAttributes(array $attributes): void
    {
        $this->attributes = $attributes;
    }
    
    /**
     * Convertir a array
     */
    public function toArray(): array
    {
        $array = [];
        
        foreach ($this->attributes as $key => $value) {
            if (!in_array($key, $this->hidden)) {
                $array[$key] = $this->getAttribute($key);
            }
        }
        
        return $array;
    }
    
    /**
     * Convertir a JSON
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * Convertir string a StudlyCase
     */
    protected function studly(string $value): string
    {
        return str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $value)));
    }
    
    /**
     * Acceso mágico a atributos
     */
    public function __get(string $key)
    {
        return $this->getAttribute($key);
    }
    
    /**
     * Establecimiento mágico de atributos
     */
    public function __set(string $key, $value): void
    {
        $this->setAttribute($key, $value);
    }
    
    /**
     * Verificar si existe un atributo
     */
    public function __isset(string $key): bool
    {
        return array_key_exists($key, $this->attributes);
    }
    
    /**
     * Eliminar un atributo
     */
    public function __unset(string $key): void
    {
        unset($this->attributes[$key]);
    }
    
    /**
     * Representación string del modelo
     */
    public function __toString(): string
    {
        return $this->toJson();
    }
}

/**
 * Query Builder básico para modelos
 */
class ModelQueryBuilder
{
    private Model $model;
    private array $wheres = [];
    private array $orders = [];
    private ?int $limit = null;
    private ?int $offset = null;
    
    public function __construct(Model $model)
    {
        $this->model = $model;
    }
    
    public function where(string $column, string $operator, $value = null): self
    {
          if ($value === null) {
        $value = $operator;
        $operator = '=';
    }
        $this->wheres[] = [$column, $operator, $value];
        return $this;
    }
    
    public function orderBy(string $column, string $direction = 'asc'): self
    {
        $this->orders[] = [$column, $direction];
        return $this;
    }
    
    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }
    
    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }
    
    public function first(): ?Model
    {
        $results = $this->limit(1)->get();
        return !empty($results) ? $results[0] : null;
    }
    
    public function get(): array
    {
        $sql = $this->buildSelect();
        $params = $this->getParameters();
        
        $results = $this->model->db->fetchAll($sql, $params);
        
        return array_map(fn($data) => $this->model->newFromDatabase($data), $results);
    }
    
    private function buildSelect(): string
    {
        $sql = "SELECT * FROM {$this->model->table}";
        
        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . $this->buildWheres();
        }
        
        if (!empty($this->orders)) {
            $sql .= ' ORDER BY ' . $this->buildOrders();
        }
        
        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
        }
        
        if ($this->offset !== null) {
            $sql .= " OFFSET {$this->offset}";
        }
        
        return $sql;
    }
    
    private function buildWheres(): string
    {
        $conditions = [];
        
        foreach ($this->wheres as $where) {
            $conditions[] = "{$where[0]} {$where[1]} ?";
        }
        
        return implode(' AND ', $conditions);
    }
    
    private function buildOrders(): string
    {
        $orders = [];
        
        foreach ($this->orders as $order) {
            $orders[] = "{$order[0]} {$order[1]}";
        }
        
        return implode(', ', $orders);
    }
    
    private function getParameters(): array
    {
        return array_column($this->wheres, 2);
    }
}
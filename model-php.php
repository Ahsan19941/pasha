<?php
// File: app/core/Model.php
// Base Model class for the PASHA Benefits Portal

namespace app\core;

class Model {
    /**
     * @var \PDO Database connection
     */
    protected $db;
    
    /**
     * @var string Table name
     */
    protected $table;
    
    /**
     * @var string Primary key name
     */
    protected $primaryKey = 'id';
    
    /**
     * Constructor
     * 
     * @throws \Exception If table name is not set
     */
    public function __construct() {
        // Check if table name is set
        if (empty($this->table)) {
            throw new \Exception('Table name must be set in the model');
        }
        
        // Get database connection
        $this->db = \getDbConnection();
    }
    
    /**
     * Find a record by ID
     * 
     * @param int $id Record ID
     * @return array|null Record data or null if not found
     */
    public function find($id) {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        
        return $stmt->fetch() ?: null;
    }
    
    /**
     * Get all records
     * 
     * @param string $orderBy Order by clause
     * @param int $limit Limit
     * @param int $offset Offset
     * @return array Records
     */
    public function all($orderBy = null, $limit = null, $offset = null) {
        $sql = "SELECT * FROM {$this->table}";
        
        // Add order by clause if specified
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        
        // Add limit and offset if specified
        if ($limit) {
            $sql .= " LIMIT {$limit}";
            
            if ($offset) {
                $sql .= " OFFSET {$offset}";
            }
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Find records by a field value
     * 
     * @param string $field Field name
     * @param mixed $value Field value
     * @param string $orderBy Order by clause
     * @return array Records
     */
    public function findBy($field, $value, $orderBy = null) {
        $sql = "SELECT * FROM {$this->table} WHERE {$field} = ?";
        
        // Add order by clause if specified
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$value]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Find a single record by a field value
     * 
     * @param string $field Field name
     * @param mixed $value Field value
     * @return array|null Record data or null if not found
     */
    public function findOneBy($field, $value) {
        $sql = "SELECT * FROM {$this->table} WHERE {$field} = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$value]);
        
        return $stmt->fetch() ?: null;
    }
    
    /**
     * Create a new record
     * 
     * @param array $data Record data
     * @return int|bool The last inserted ID or false on failure
     */
    public function create($data) {
        // Prepare the SQL statement
        $fields = array_keys($data);
        $placeholders = array_fill(0, count($fields), '?');
        
        $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute(array_values($data));
        
        return $result ? $this->db->lastInsertId() : false;
    }
    
    /**
     * Update a record
     * 
     * @param int $id Record ID
     * @param array $data Record data
     * @return bool True on success, false on failure
     */
    public function update($id, $data) {
        // Prepare the SET part of the SQL statement
        $set = [];
        foreach ($data as $field => $value) {
            $set[] = "{$field} = ?";
        }
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $set) . " WHERE {$this->primaryKey} = ?";
        
        $stmt = $this->db->prepare($sql);
        
        // Add the ID to the values
        $values = array_values($data);
        $values[] = $id;
        
        return $stmt->execute($values);
    }
    
    /**
     * Delete a record
     * 
     * @param int $id Record ID
     * @return bool True on success, false on failure
     */
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([$id]);
    }
    
    /**
     * Count records
     * 
     * @param string $where Where clause (without 'WHERE' keyword)
     * @param array $params Parameters for the where clause
     * @return int Number of records
     */
    public function count($where = null, $params = []) {
        $sql = "SELECT COUNT(*) FROM {$this->table}";
        
        // Add where clause if specified
        if ($where) {
            $sql .= " WHERE {$where}";
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return (int) $stmt->fetchColumn();
    }
    
    /**
     * Execute a custom query
     * 
     * @param string $sql SQL query
     * @param array $params Parameters for the query
     * @param bool $fetchAll Whether to fetch all records
     * @return mixed Query result
     */
    public function query($sql, $params = [], $fetchAll = true) {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $fetchAll ? $stmt->fetchAll() : $stmt->fetch();
    }
    
    /**
     * Begin a transaction
     * 
     * @return bool True on success, false on failure
     */
    public function beginTransaction() {
        return $this->db->beginTransaction();
    }
    
    /**
     * Commit a transaction
     * 
     * @return bool True on success, false on failure
     */
    public function commit() {
        return $this->db->commit();
    }
    
    /**
     * Rollback a transaction
     * 
     * @return bool True on success, false on failure
     */
    public function rollBack() {
        return $this->db->rollBack();
    }
    
    /**
     * Get the last inserted ID
     * 
     * @return string The last inserted ID
     */
    public function lastInsertId() {
        return $this->db->lastInsertId();
    }
}

<?php
// Database configuration using environment variables (PostgreSQL)
$db_host = getenv('PGHOST');
$db_port = getenv('PGPORT');
$db_user = getenv('PGUSER');
$db_pass = getenv('PGPASSWORD');
$db_name = getenv('PGDATABASE');

// Create connection string
$dsn = "pgsql:host=$db_host;port=$db_port;dbname=$db_name;user=$db_user;password=$db_pass";

try {
    // Create PDO instance for PostgreSQL (global variable)
    $pdo = new PDO($dsn);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Create a compatibility layer for our code that was written for mysqli
    $conn = new class($pdo) {
        public $pdo; // Make PDO object accessible to external scripts
        public $insert_id = null;
        public $affected_rows = null;
        
        public function __construct($pdo) {
            $this->pdo = $pdo;
        }
        
        public function query($sql) {
            // Convert MySQL syntax to PostgreSQL where needed
            
            // Replace CURDATE() with CURRENT_DATE
            $sql = str_replace('CURDATE()', 'CURRENT_DATE', $sql);
            
            // For SUM and other aggregate functions ensure PostgreSQL compatibility
            if (strpos($sql, 'SELECT SUM') !== false) {
                $sql = preg_replace('/SUM\((.*?)\) as (.*?)(?:,|\s|$)/i', 'COALESCE(SUM($1), 0) as $2$3', $sql);
            }
            if (strpos($sql, 'SELECT COUNT') !== false) {
                $sql = preg_replace('/COUNT\((.*?)\) as (.*?)(?:,|\s|$)/i', 'COALESCE(COUNT($1), 0) as $2$3', $sql);
            }
            
            try {
                // Execute query
                $stmt = $this->pdo->query($sql);
                
                // For INSERT queries, get the last insert ID
                if (stripos($sql, 'INSERT INTO') === 0) {
                    // In PostgreSQL, use the RETURNING clause to get the ID
                    if (stripos($sql, 'RETURNING') === false) {
                        // For tables with serial primary key named 'id'
                        $tableName = preg_match('/INSERT\s+INTO\s+([^\s\(]+)/i', $sql, $matches) ? $matches[1] : '';
                        $this->insert_id = $this->pdo->lastInsertId($tableName . '_id_seq');
                    }
                }
                
                // For UPDATE/DELETE queries, get affected rows
                if (stripos($sql, 'UPDATE') === 0 || stripos($sql, 'DELETE') === 0) {
                    $this->affected_rows = $stmt->rowCount();
                }
                
                // Return a result wrapper object
                return new class($stmt) {
                    private $stmt;
                    public $num_rows = 0;
                    private $data = null;
                    
                    public function __construct($stmt) {
                        $this->stmt = $stmt;
                        
                        // Pre-fetch all data to calculate num_rows correctly
                        // since PDO's rowCount() doesn't work for SELECT queries in all drivers
                        $this->data = $this->stmt->fetchAll(PDO::FETCH_ASSOC);
                        $this->num_rows = count($this->data);
                        reset($this->data); // Reset array pointer
                    }
                    
                    public function fetch_assoc() {
                        if ($this->data === null) {
                            return $this->stmt->fetch(PDO::FETCH_ASSOC);
                        }
                        
                        $row = current($this->data);
                        if ($row) {
                            next($this->data);
                            return $row;
                        }
                        return false;
                    }
                    
                    public function fetch_all($mode = null) {
                        if ($this->data === null) {
                            return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
                        }
                        return $this->data;
                    }
                };
            } catch (Exception $e) {
                error_log("SQL Error: " . $e->getMessage() . " in query: " . $sql);
                throw $e;
            }
        }
        
        public function prepare($sql) {
            // Convert MySQL syntax to PostgreSQL if needed
            $sql = str_replace('CURDATE()', 'CURRENT_DATE', $sql);
            
            // Return a statement wrapper object
            try {
                $pstmt = $this->pdo->prepare($sql);
                return new class($pstmt, $this) {
                    private $stmt;
                    private $conn;
                    private $params = [];
                    
                    public function __construct($stmt, $conn) {
                        $this->stmt = $stmt;
                        $this->conn = $conn;
                    }
                    
                    public function bind_param($types, ...$params) {
                        // Since PDO uses positional parameters, we'll just store the params
                        $this->params = $params;
                        return true;
                    }
                    
                    public function execute() {
                        if (isset($this->params) && !empty($this->params)) {
                            $result = $this->stmt->execute($this->params);
                        } else {
                            $result = $this->stmt->execute();
                        }
                        return $result;
                    }
                    
                    public function get_result() {
                        // Execute the statement if not already executed
                        $this->execute();
                        
                        // Return a result wrapper
                        return new class($this->stmt) {
                            private $stmt;
                            public $num_rows = 0;
                            private $data = null;
                            
                            public function __construct($stmt) {
                                $this->stmt = $stmt;
                                
                                // Pre-fetch all data
                                $this->data = $this->stmt->fetchAll(PDO::FETCH_ASSOC);
                                $this->num_rows = count($this->data);
                                reset($this->data);
                            }
                            
                            public function fetch_assoc() {
                                $row = current($this->data);
                                if ($row) {
                                    next($this->data);
                                    return $row;
                                }
                                return false;
                            }
                        };
                    }
                };
            } catch (Exception $e) {
                error_log("SQL Prepare Error: " . $e->getMessage() . " in query: " . $sql);
                throw $e;
            }
        }
        
        public function real_escape_string($value) {
            // PDO params will handle escaping, but for compatibility
            return str_replace("'", "''", $value); // PostgreSQL uses '' to escape single quotes
        }
        
        public function error() {
            $errorInfo = $this->pdo->errorInfo();
            return $errorInfo[2] ?? 'Unknown error';
        }
    };
    
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>

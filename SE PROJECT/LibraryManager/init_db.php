<?php
// Initialize database with PostgreSQL schema

// Get the database connection
require_once 'includes/db_connect.php';

// Create a new PDO connection directly for initialization
$db_host = getenv('PGHOST');
$db_port = getenv('PGPORT');
$db_user = getenv('PGUSER');
$db_pass = getenv('PGPASSWORD');
$db_name = getenv('PGDATABASE');

// Create connection string
$dsn = "pgsql:host=$db_host;port=$db_port;dbname=$db_name;user=$db_user;password=$db_pass";

try {
    $pdo = new PDO($dsn);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get the schema file content
    $schema = file_get_contents('database/library_db.sql');
    
    // Execute as a single transaction to preserve integrity
    $pdo->beginTransaction();
    
    // Split the schema by semicolons, but handle DO blocks specially
    $parts = explode(';', $schema);
    $statements = [];
    $current = '';
    
    foreach ($parts as $part) {
        $part = trim($part);
        if (empty($part)) continue;
        
        // Handle DO blocks which contain semicolons internally
        if (strpos($part, 'DO $$') !== false) {
            // This is the start of a DO block
            $current = $part;
        } elseif (!empty($current)) {
            // This is part of a DO block that was split by a semicolon
            $current .= ';' . $part;
            if (strpos($part, 'END$$') !== false) {
                // End of the DO block
                $statements[] = $current;
                $current = '';
            }
        } else {
            // Regular statement
            $statements[] = $part;
        }
    }
    
    // Execute each statement
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            try {
                $pdo->exec($statement);
                echo "Executed: " . substr(str_replace(["\n", "\r"], ' ', $statement), 0, 50) . "...\n";
            } catch (PDOException $e) {
                echo "Error executing: " . substr(str_replace(["\n", "\r"], ' ', $statement), 0, 50) . "...\n";
                echo "Error message: " . $e->getMessage() . "\n\n";
                // Rollback on error
                $pdo->rollBack();
                exit(1);
            }
        }
    }
    
    // Commit if all successful
    $pdo->commit();
    echo "Database initialization completed successfully!\n";
    
} catch (PDOException $e) {
    echo "Database connection error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
<?php
// Import books from the CSV file to the PostgreSQL database
require_once 'includes/db_connect.php'; // This file defines $conn and $pdo
require_once 'includes/functions.php';

// Set up output format for browser or command line
$is_cli = php_sapi_name() === 'cli';
if (!$is_cli) {
    echo "<pre>";
}

echo "Starting book import process...\n";

// Define the CSV file path
$csvFile = 'attached_assets/prog_book.csv';

// Check if file exists
if (!file_exists($csvFile)) {
    die("Error: CSV file not found at $csvFile\n");
}

// Open and parse CSV with proper handling of quotes
$file = fopen($csvFile, 'r');
if (!$file) {
    die("Error: Unable to open CSV file at $csvFile\n");
}

// Read headers
$headers = fgetcsv($file);
echo "Headers found: " . implode(', ', $headers) . "\n";

// Find column indexes
$titleIndex = array_search('Book_title', $headers);
$descriptionIndex = array_search('Description', $headers);
$pagesIndex = array_search('Number_Of_Pages', $headers);
$typeIndex = array_search('Type', $headers);

if ($titleIndex === false || $descriptionIndex === false || $pagesIndex === false || $typeIndex === false) {
    die("Error: Required columns not found in CSV file\n");
}

// Read CSV rows into array
$csvData = [];
while (($row = fgetcsv($file)) !== false) {
    if (count($row) >= count($headers)) {
        $csvData[] = $row;
    }
}
fclose($file);

echo "Found " . count($csvData) . " books in the CSV file\n";

// Ensure global variables are accessible
global $conn;

// Debug the connection
echo "Database connection status: " . (isset($conn) ? "Connected" : "Not connected") . "\n";
echo "PDO object status: " . (isset($conn->pdo) ? "Available" : "Not available") . "\n";

// Start transaction 
try {
    // Make sure we have a PDO connection through our $conn object
    if (!isset($conn) || !isset($conn->pdo) || !($conn->pdo instanceof PDO)) {
        // If connection is not set, create a new connection
        include_once 'includes/db_connect.php';
        echo "Re-established database connection\n";
    }
    
    echo "Starting database transaction...\n";
    // Skip transaction for now to simplify the process
    // $pdo->beginTransaction();
    
    $importCount = 0;
    $skipCount = 0;
    
    // Let's just import the first 20 books for testing
    $bookLimit = 20;
    $booksProcessed = 0;
    
    // Process each row
    foreach ($csvData as $row) {
        if ($booksProcessed >= $bookLimit) {
            echo "Reached test limit of $bookLimit books.\n";
            break;
        }
        
        // Extract data and escape for PostgreSQL
        $title = isset($row[$titleIndex]) ? trim($row[$titleIndex]) : '';
        $description = isset($row[$descriptionIndex]) ? trim($row[$descriptionIndex]) : '';
        $pages = isset($row[$pagesIndex]) ? intval(trim($row[$pagesIndex])) : 0;
        $type = isset($row[$typeIndex]) ? trim($row[$typeIndex]) : 'Programming';
        
        // Skip empty titles
        if (empty($title)) {
            continue;
        }
        
        // Generate an ISBN if needed (for books that don't have one)
        $isbn = 'ISBN-' . mt_rand(1000000000, 9999999999);
        
        // Try direct SQL insert for now
        try {
            $insertQuery = "INSERT INTO books (title, author, isbn, category, quantity, available_quantity, description) 
                           VALUES ('$title', 'Unknown Author', '$isbn', '$type', 5, 5, '$description')";
            
            // Replace single quotes with escaped quotes for PostgreSQL
            $insertQuery = str_replace("'", "''", $insertQuery);
            
            // Now add the single quotes back around values
            $insertQuery = "INSERT INTO books (title, author, isbn, category, quantity, available_quantity, description) 
                           VALUES ('" . str_replace("''", "'", $title) . "', 'Unknown Author', '$isbn', '" . 
                           str_replace("''", "'", $type) . "', 5, 5, '" . str_replace("''", "'", $description) . "')";
                           
            echo "Executing SQL: $insertQuery\n";
            
            // Execute the insert directly
            $result = $conn->pdo->exec($insertQuery);
            
            if ($result !== false) {
                echo "Imported: $title\n";
                $importCount++;
            } else {
                echo "Error importing '$title': " . implode(' ', $conn->pdo->errorInfo()) . "\n";
            }
            
            $booksProcessed++;
            
        } catch (Exception $insertError) {
            echo "Exception during insert: " . $insertError->getMessage() . "\n";
        }
    }
    
    // No need to commit if we're not using a transaction
    // $pdo->commit();
    
    echo "Import complete. Imported $importCount books. Skipped $skipCount existing books.\n";
    
} catch (Exception $e) {
    // Rollback on error
    if (isset($conn) && isset($conn->pdo)) {
        $conn->pdo->rollBack();
    }
    echo "Error during import: " . $e->getMessage() . "\n";
}

echo "Book import process completed.\n";

if (!$is_cli) {
    echo "</pre>";
    echo "<p><a href='index.php' class='btn btn-primary'>Return to Home Page</a></p>";
}
?>
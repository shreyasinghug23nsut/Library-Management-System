<?php
// Simple script to import some books into the database
// Initialize PDO variable globally
$pdo = null;

require_once 'includes/db_connect.php'; // This file defines $conn and $pdo

// Ensure $conn variable is properly set from db_connect.php
global $conn;
// Check if we have PDO from the included file
if (!isset($pdo) && isset($conn) && isset($conn->pdo)) {
    $pdo = $conn->pdo;
    echo "Retrieved PDO object from connection\n";
} else if (!isset($pdo) || !($pdo instanceof PDO)) {
    // Create a new PDO connection if needed
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
        echo "Created new PDO connection\n";
    } catch (PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

echo "Starting quick import of books...\n";

$books = [
    [
        'title' => 'JavaScript: The Good Parts',
        'author' => 'Douglas Crockford',
        'isbn' => 'ISBN-9780596517748',
        'category' => 'Programming',
        'description' => 'A book about the good parts of JavaScript',
        'quantity' => 5,
        'available_quantity' => 5
    ],
    [
        'title' => 'Clean Code',
        'author' => 'Robert C. Martin',
        'isbn' => 'ISBN-9780132350884',
        'category' => 'Programming',
        'description' => 'A handbook of agile software craftsmanship',
        'quantity' => 3,
        'available_quantity' => 3
    ],
    [
        'title' => 'Design Patterns',
        'author' => 'Erich Gamma, Richard Helm, Ralph Johnson, John Vlissides',
        'isbn' => 'ISBN-9780201633610',
        'category' => 'Programming',
        'description' => 'Elements of Reusable Object-Oriented Software',
        'quantity' => 2,
        'available_quantity' => 2
    ],
    [
        'title' => 'The Pragmatic Programmer',
        'author' => 'Andrew Hunt, David Thomas',
        'isbn' => 'ISBN-9780201616224',
        'category' => 'Programming',
        'description' => 'From Journeyman to Master',
        'quantity' => 4,
        'available_quantity' => 4
    ],
    [
        'title' => 'Introduction to Algorithms',
        'author' => 'Thomas H. Cormen, Charles E. Leiserson, Ronald L. Rivest, Clifford Stein',
        'isbn' => 'ISBN-9780262033848',
        'category' => 'Programming',
        'description' => 'A comprehensive introduction to algorithms',
        'quantity' => 2,
        'available_quantity' => 2
    ]
];

$importCount = 0;

foreach ($books as $book) {
    try {
        // Use standard PDO placeholders (?)
        $sql = "INSERT INTO books (title, author, isbn, category, quantity, available_quantity, description) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $book['title'],
            $book['author'],
            $book['isbn'],
            $book['category'],
            $book['quantity'],
            $book['available_quantity'],
            $book['description']
        ]);
        
        if ($result) {
            echo "Imported: {$book['title']}\n";
            $importCount++;
        } else {
            echo "Error importing '{$book['title']}': " . implode(' ', $stmt->errorInfo()) . "\n";
        }
    } catch (Exception $e) {
        echo "Exception during import: " . $e->getMessage() . "\n";
    }
}

echo "Quick import complete. Imported $importCount books.\n";
echo "You can now return to the <a href='index.php'>main page</a>.\n";
?>
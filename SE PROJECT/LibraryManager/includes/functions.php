<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Function to sanitize input data
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to check if user is admin
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin';
}

// Function to redirect to a specific page
function redirect($location) {
    header("Location: $location");
    exit;
}

// Function to display error message
function display_error($message) {
    return "<div class='alert alert-danger' role='alert'>$message</div>";
}

// Function to display success message
function display_success($message) {
    return "<div class='alert alert-success' role='alert'>$message</div>";
}

// Function to get all books
function getAllBooks($conn) {
    try {
        $books = array();
        $sql = "SELECT * FROM books ORDER BY title ASC";
        $stmt = $conn->pdo->prepare($sql);
        $stmt->execute();
        $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $books;
    } catch (Exception $e) {
        error_log("getAllBooks error: " . $e->getMessage());
        return [];
    }
}

// Function to get a book by ID
function getBookById($conn, $book_id) {
    try {
        $sql = "SELECT * FROM books WHERE id = :book_id";
        $stmt = $conn->pdo->prepare($sql);
        $stmt->bindParam(':book_id', $book_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("getBookById error: " . $e->getMessage());
        return null;
    }
}

// Function to search books
function searchBooks($conn, $keyword) {
    try {
        // Use prepared statements for PostgreSQL ILIKE
        $sql = "SELECT * FROM books WHERE 
                title ILIKE :keyword OR 
                author ILIKE :keyword OR 
                isbn ILIKE :keyword OR 
                category ILIKE :keyword
                ORDER BY title ASC";
        
        $stmt = $conn->pdo->prepare($sql);
        $searchParam = "%" . $keyword . "%";
        $stmt->bindParam(':keyword', $searchParam, PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("searchBooks error: " . $e->getMessage());
        return [];
    }
}

// Function to get all users
function getAllUsers($conn) {
    try {
        $sql = "SELECT * FROM users WHERE role = 'user' ORDER BY name ASC";
        $stmt = $conn->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("getAllUsers error: " . $e->getMessage());
        return [];
    }
}

// Function to get a user by ID
function getUserById($conn, $user_id) {
    try {
        $sql = "SELECT * FROM users WHERE id = :user_id";
        $stmt = $conn->pdo->prepare($sql);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("getUserById error: " . $e->getMessage());
        return null;
    }
}

// Function to get borrowed books by user
function getBorrowedBooks($conn, $user_id) {
    try {
        $sql = "SELECT bi.*, b.title, b.author, b.isbn 
                FROM book_issues bi 
                JOIN books b ON bi.book_id = b.id 
                WHERE bi.user_id = :user_id AND bi.status != 'returned' 
                ORDER BY bi.issue_date DESC";
        
        $stmt = $conn->pdo->prepare($sql);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("getBorrowedBooks error: " . $e->getMessage());
        return [];
    }
}

// Function to get issue history
function getIssueHistory($conn) {
    try {
        $sql = "SELECT bi.*, b.title, b.isbn, u.name as user_name 
                FROM book_issues bi 
                JOIN books b ON bi.book_id = b.id 
                JOIN users u ON bi.user_id = u.id 
                ORDER BY bi.issue_date DESC";
        
        $stmt = $conn->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("getIssueHistory error: " . $e->getMessage());
        return [];
    }
}

// Function to calculate fine
function calculateFine($return_date, $today = null) {
    if ($today === null) {
        $today = new DateTime();
    } else {
        if (!$today instanceof DateTime) {
            $today = new DateTime($today);
        }
    }
    
    if (!$return_date instanceof DateTime) {
        $return_date = new DateTime($return_date);
    }
    
    if ($today <= $return_date) {
        return 0;
    }
    
    $diff = $today->diff($return_date);
    $days = $diff->days;
    
    // Assume fine is $1 per day
    return $days * 1.00;
}

// Get issue details by ID
function getIssueById($conn, $issue_id) {
    try {
        $sql = "SELECT bi.*, b.title, b.isbn, u.name as user_name 
                FROM book_issues bi 
                JOIN books b ON bi.book_id = b.id 
                JOIN users u ON bi.user_id = u.id 
                WHERE bi.id = :issue_id";
        
        $stmt = $conn->pdo->prepare($sql);
        $stmt->bindParam(':issue_id', $issue_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("getIssueById error: " . $e->getMessage());
        return null;
    }
}
?>

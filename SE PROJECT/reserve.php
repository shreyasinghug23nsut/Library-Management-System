<?php
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if (isset($_GET['book_id'])) {
    $book_id = $_GET['book_id'];
    $user_id = $_SESSION['user_id'];
    
    // Check if book is available
    $book = $conn->query("SELECT * FROM books WHERE id = $book_id AND available > 0")->fetch_assoc();
    
    if ($book) {
        // Check if user already has a reservation for this book
        $existing = $conn->query("SELECT * FROM reservations WHERE user_id = $user_id AND book_id = $book_id AND status = 'pending'");
        
        if ($existing->num_rows == 0) {
            $stmt = $conn->prepare("INSERT INTO reservations (user_id, book_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $user_id, $book_id);
            
            if ($stmt->execute()) {
                $_SESSION['message'] = "Book reserved successfully!";
            } else {
                $_SESSION['error'] = "Failed to reserve book.";
            }
        } else {
            $_SESSION['error'] = "You already have a pending reservation for this book.";
        }
    } else {
        $_SESSION['error'] = "Book is not available for reservation.";
    }
    
    header('Location: search.php');
    exit();
}
?>
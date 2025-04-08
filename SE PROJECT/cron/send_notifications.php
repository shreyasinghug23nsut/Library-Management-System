<?php
require_once '../includes/config.php';
require_once '../includes/email_service.php';
require_once '../includes/fine_calculator.php';

$email_service = new EmailService();
$fine_calculator = new FineCalculator();

// Update fines
$fine_calculator->updateFines($conn);

// Send overdue notifications
$overdue_books = $conn->query("
    SELECT bi.*, b.title, u.email 
    FROM book_issues bi 
    JOIN books b ON bi.book_id = b.id 
    JOIN users u ON bi.user_id = u.id 
    WHERE bi.status = 'overdue' 
    AND DATE(bi.last_notification) < CURRENT_DATE
");

while ($book = $overdue_books->fetch_assoc()) {
    $fine = $fine_calculator->calculateFine($book['return_date']);
    
    if ($email_service->sendOverdueNotification(
        $book['email'],
        $book['title'],
        $book['return_date'],
        $fine
    )) {
        // Update last notification date
        $conn->query("
            UPDATE book_issues 
            SET last_notification = CURRENT_DATE 
            WHERE id = {$book['id']}
        ");
    }
}

// Check and notify for available reservations
$available_reservations = $conn->query("
    SELECT r.*, b.title, u.email 
    FROM reservations r 
    JOIN books b ON r.book_id = b.id 
    JOIN users u ON r.user_id = u.id 
    WHERE r.status = 'pending' 
    AND b.available > 0
");

while ($reservation = $available_reservations->fetch_assoc()) {
    if ($email_service->sendReservationNotification(
        $reservation['email'],
        $reservation['title']
    )) {
        // Update reservation status
        $conn->query("
            UPDATE reservations 
            SET status = 'approved' 
            WHERE id = {$reservation['id']}
        ");
    }
}
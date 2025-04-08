<?php
class EmailService {
    private $from_email = 'library@yourdomain.com';
    private $from_name = 'Library Management System';

    public function sendOverdueNotification($user_email, $book_title, $due_date, $fine) {
        $subject = 'Overdue Book Notice';
        $message = "Dear Library Member,\n\n";
        $message .= "This is a reminder that the following book is overdue:\n";
        $message .= "Book: $book_title\n";
        $message .= "Due Date: $due_date\n";
        $message .= "Current Fine: $" . number_format($fine, 2) . "\n\n";
        $message .= "Please return the book as soon as possible to avoid additional fines.\n\n";
        $message .= "Best regards,\nLibrary Management Team";

        $headers = "From: {$this->from_name} <{$this->from_email}>";
        
        return mail($user_email, $subject, $message, $headers);
    }

    public function sendReservationNotification($user_email, $book_title) {
        $subject = 'Book Reservation Available';
        $message = "Dear Library Member,\n\n";
        $message .= "The book you reserved is now available:\n";
        $message .= "Book: $book_title\n\n";
        $message .= "Please visit the library within 48 hours to check out the book.\n\n";
        $message .= "Best regards,\nLibrary Management Team";

        $headers = "From: {$this->from_name} <{$this->from_email}>";
        
        return mail($user_email, $subject, $message, $headers);
    }
}
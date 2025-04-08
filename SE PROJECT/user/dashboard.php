<?php
session_start();
require_once '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Get user's borrowed books
$user_id = $_SESSION['user_id'];
$borrowed_books = $conn->query("
    SELECT bi.*, b.title, b.author 
    FROM book_issues bi 
    JOIN books b ON bi.book_id = b.id 
    WHERE bi.user_id = $user_id AND bi.status != 'returned'
    ORDER BY bi.issue_date DESC
");

// Get user's reservations
$reservations = $conn->query("
    SELECT r.*, b.title, b.author 
    FROM reservations r 
    JOIN books b ON r.book_id = b.id 
    WHERE r.user_id = $user_id AND r.status = 'pending'
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - Library Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/user.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <h1>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></h1>
        
        <div class="borrowed-books">
            <h2>Currently Borrowed Books</h2>
            <?php if ($borrowed_books->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Issue Date</th>
                        <th>Return Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($book = $borrowed_books->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($book['title']); ?></td>
                        <td><?php echo htmlspecialchars($book['author']); ?></td>
                        <td><?php echo $book['issue_date']; ?></td>
                        <td><?php echo $book['return_date']; ?></td>
                        <td><?php echo $book['status']; ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p>No books currently borrowed.</p>
            <?php endif; ?>
        </div>

        <div class="reservations">
            <h2>Your Reservations</h2>
            <?php if ($reservations->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Reservation Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($reservation = $reservations->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($reservation['title']); ?></td>
                        <td><?php echo htmlspecialchars($reservation['author']); ?></td>
                        <td><?php echo $reservation['reservation_date']; ?></td>
                        <td><?php echo $reservation['status']; ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p>No active reservations.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
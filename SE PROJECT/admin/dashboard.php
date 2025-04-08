<?php
session_start();
require_once '../includes/config.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Get statistics
$stats = [
    'total_books' => $conn->query("SELECT COUNT(*) FROM books")->fetch_row()[0],
    'books_issued' => $conn->query("SELECT COUNT(*) FROM book_issues WHERE status='issued'")->fetch_row()[0],
    'total_users' => $conn->query("SELECT COUNT(*) FROM users WHERE user_type='user'")->fetch_row()[0],
    'pending_returns' => $conn->query("SELECT COUNT(*) FROM book_issues WHERE status='overdue'")->fetch_row()[0]
];

// Get recent activities
$recent_activities = $conn->query("
    SELECT bi.*, b.title, u.username 
    FROM book_issues bi 
    JOIN books b ON bi.book_id = b.id 
    JOIN users u ON bi.user_id = u.id 
    ORDER BY bi.issue_date DESC LIMIT 10
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Library Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <h1>Admin Dashboard</h1>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Books</h3>
                <p><?php echo $stats['total_books']; ?></p>
            </div>
            <div class="stat-card">
                <h3>Books Issued</h3>
                <p><?php echo $stats['books_issued']; ?></p>
            </div>
            <div class="stat-card">
                <h3>Total Users</h3>
                <p><?php echo $stats['total_users']; ?></p>
            </div>
            <div class="stat-card">
                <h3>Pending Returns</h3>
                <p><?php echo $stats['pending_returns']; ?></p>
            </div>
        </div>

        <div class="recent-activities">
            <h2>Recent Activities</h2>
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Book</th>
                        <th>Issue Date</th>
                        <th>Return Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($activity = $recent_activities->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($activity['username']); ?></td>
                        <td><?php echo htmlspecialchars($activity['title']); ?></td>
                        <td><?php echo $activity['issue_date']; ?></td>
                        <td><?php echo $activity['return_date']; ?></td>
                        <td><?php echo $activity['status']; ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
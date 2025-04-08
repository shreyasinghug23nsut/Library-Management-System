<?php
// Import key files and ensure db_connect.php comes first
require_once '../includes/db_connect.php'; // This file declares $conn as global
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Check if user is admin
require_admin();

// Ensure we have access to database functions and connection
include_once '../includes/db_connect.php';
include_once '../includes/functions.php';

// Get database connection
global $conn;

// Use $conn->pdo for database queries (established in db_connect.php)
try {
    // Initialize variables in case queries fail
    $total_books = 0;
    $available_books = 0;
    $total_users = 0;
    $issued_books = 0;
    $overdue_books = 0;
    $recent_issues = [];

    // Get statistics
    $total_books_query = "SELECT COALESCE(SUM(quantity), 0) as total FROM books";
    $total_books_stmt = $conn->pdo->query($total_books_query);
    $total_books = $total_books_stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    $available_books_query = "SELECT COALESCE(SUM(available_quantity), 0) as available FROM books";
    $available_books_stmt = $conn->pdo->query($available_books_query);
    $available_books = $available_books_stmt->fetch(PDO::FETCH_ASSOC)['available'] ?? 0;

    $total_users_query = "SELECT COUNT(*) as total FROM users WHERE role = 'user'";
    $total_users_stmt = $conn->pdo->query($total_users_query);
    $total_users = $total_users_stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    $issued_books_query = "SELECT COUNT(*) as total FROM book_issues WHERE status = 'issued'";
    $issued_books_stmt = $conn->pdo->query($issued_books_query);
    $issued_books = $issued_books_stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    $overdue_books_query = "SELECT COUNT(*) as total FROM book_issues WHERE status = 'issued' AND return_date < CURRENT_DATE";
    $overdue_books_stmt = $conn->pdo->query($overdue_books_query);
    $overdue_books = $overdue_books_stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    // Get recent issues
    $recent_issues_query = "SELECT bi.*, b.title as book_title, u.name as user_name 
                          FROM book_issues bi 
                          JOIN books b ON bi.book_id = b.id 
                          JOIN users u ON bi.user_id = u.id 
                          ORDER BY bi.created_at DESC LIMIT 5";
    $recent_issues_stmt = $conn->pdo->query($recent_issues_query);
    $recent_issues = $recent_issues_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Log the error
    error_log("Dashboard error: " . $e->getMessage());
}

// Set flag for admin page
$is_admin = true;

// Include header
include '../includes/header.php';
?>

<div class="container">
    <h2 class="mb-4"><i class="fas fa-tachometer-alt me-2"></i>Admin Dashboard</h2>
    
    <!-- Dashboard Widgets -->
    <div class="row">
        <div class="col-md-4">
            <div class="dashboard-widget widget-primary">
                <div class="widget-icon">
                    <i class="fas fa-book"></i>
                </div>
                <div class="widget-content">
                    <h3><?php echo $total_books; ?></h3>
                    <p>Total Books</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="dashboard-widget widget-success">
                <div class="widget-icon">
                    <i class="fas fa-book-reader"></i>
                </div>
                <div class="widget-content">
                    <h3><?php echo $available_books; ?></h3>
                    <p>Available Books</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="dashboard-widget widget-info">
                <div class="widget-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="widget-content">
                    <h3><?php echo $total_users; ?></h3>
                    <p>Registered Users</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="dashboard-widget widget-success">
                <div class="widget-icon">
                    <i class="fas fa-book-open"></i>
                </div>
                <div class="widget-content">
                    <h3><?php echo $issued_books; ?></h3>
                    <p>Books Currently Issued</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="dashboard-widget widget-danger">
                <div class="widget-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="widget-content">
                    <h3><?php echo $overdue_books; ?></h3>
                    <p>Overdue Books</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Links -->
    <div class="card mt-4">
        <div class="card-header">
            <h5><i class="fas fa-link me-2"></i>Quick Links</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 col-sm-6 mb-3">
                    <a href="manage_books.php" class="btn btn-primary w-100">
                        <i class="fas fa-book me-2"></i>Manage Books
                    </a>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <a href="manage_users.php" class="btn btn-primary w-100">
                        <i class="fas fa-users me-2"></i>Manage Users
                    </a>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <a href="issue_book.php" class="btn btn-primary w-100">
                        <i class="fas fa-book-reader me-2"></i>Issue Book
                    </a>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <a href="return_book.php" class="btn btn-primary w-100">
                        <i class="fas fa-undo me-2"></i>Return Book
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Book Issues -->
    <div class="card mt-4">
        <div class="card-header">
            <h5><i class="fas fa-history me-2"></i>Recent Book Issues</h5>
        </div>
        <div class="card-body">
            <?php if (empty($recent_issues)): ?>
                <div class="alert alert-info">No recent book issues.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Book Title</th>
                                <th>User</th>
                                <th>Issue Date</th>
                                <th>Return Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_issues as $issue): ?>
                                <tr>
                                    <td><?php echo $issue['book_title']; ?></td>
                                    <td><?php echo $issue['user_name']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($issue['issue_date'])); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($issue['return_date'])); ?></td>
                                    <td>
                                        <?php if ($issue['status'] === 'issued'): ?>
                                            <?php if (strtotime($issue['return_date']) < time()): ?>
                                                <span class="badge bg-danger">Overdue</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">Issued</span>
                                            <?php endif; ?>
                                        <?php elseif ($issue['status'] === 'returned'): ?>
                                            <span class="badge bg-primary">Returned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($issue['status'] === 'issued'): ?>
                                            <a href="return_book.php?id=<?php echo $issue['id']; ?>" class="btn btn-sm btn-primary">
                                                Return
                                            </a>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-secondary" disabled>Returned</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    <a href="return_book.php" class="btn btn-primary">View All Issues</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Include footer
include '../includes/footer.php';
?>

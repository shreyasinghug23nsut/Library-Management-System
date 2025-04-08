<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Check if user is logged in
require_login();

// Get user info
$user_id = $_SESSION['user_id'];
$user = getUserById($conn, $user_id);

// Get borrowed books
$borrowed_books_query = "SELECT bi.*, b.title, b.author, b.isbn 
                       FROM book_issues bi 
                       JOIN books b ON bi.book_id = b.id 
                       WHERE bi.user_id = $user_id AND bi.status = 'issued' 
                       ORDER BY bi.return_date ASC";
$borrowed_books_result = $conn->query($borrowed_books_query);
$borrowed_books = [];
$overdue_books = 0;

if ($borrowed_books_result->num_rows > 0) {
    while ($row = $borrowed_books_result->fetch_assoc()) {
        $row['is_overdue'] = strtotime($row['return_date']) < time();
        if ($row['is_overdue']) {
            $overdue_books++;
        }
        $borrowed_books[] = $row;
    }
}

// Get borrowed history
$history_query = "SELECT bi.*, b.title, b.author, b.isbn 
                 FROM book_issues bi 
                 JOIN books b ON bi.book_id = b.id 
                 WHERE bi.user_id = $user_id AND bi.status = 'returned' 
                 ORDER BY bi.actual_return_date DESC 
                 LIMIT 5";
$history_result = $conn->query($history_query);
$history = [];

if ($history_result->num_rows > 0) {
    while ($row = $history_result->fetch_assoc()) {
        $history[] = $row;
    }
}

// Set flag for user page
$is_user = true;

// Include header
include '../includes/header.php';
?>

<div class="container">
    <h2 class="mb-4"><i class="fas fa-tachometer-alt me-2"></i>User Dashboard</h2>
    
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-user me-2"></i>Account Information</h5>
                </div>
                <div class="card-body">
                    <p><strong>Name:</strong> <?php echo $user['name']; ?></p>
                    <p><strong>Email:</strong> <?php echo $user['email']; ?></p>
                    <p><strong>Member Since:</strong> <?php echo date('M d, Y', strtotime($user['created_at'])); ?></p>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-4">
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="dashboard-widget widget-primary">
                        <i class="fas fa-book-reader"></i>
                        <h3><?php echo count($borrowed_books); ?></h3>
                        <p>Books Borrowed</p>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="dashboard-widget <?php echo $overdue_books > 0 ? 'widget-danger' : 'widget-success'; ?>">
                        <i class="fas fa-exclamation-circle"></i>
                        <h3><?php echo $overdue_books; ?></h3>
                        <p>Overdue Books</p>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-link me-2"></i>Quick Links</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6 mb-2">
                            <a href="books.php" class="btn btn-primary w-100">
                                <i class="fas fa-book me-2"></i>Browse Books
                            </a>
                        </div>
                        <div class="col-6 mb-2">
                            <a href="borrowed_books.php" class="btn btn-primary w-100">
                                <i class="fas fa-book-reader me-2"></i>My Books
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Borrowed Books -->
    <div class="card mb-4">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-book-reader me-2"></i>Currently Borrowed Books</h5>
                <a href="borrowed_books.php" class="btn btn-sm btn-primary">View All</a>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($borrowed_books)): ?>
                <div class="alert alert-info">You don't have any books currently borrowed.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Author</th>
                                <th>Issue Date</th>
                                <th>Due Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($borrowed_books as $book): ?>
                                <tr>
                                    <td><?php echo $book['title']; ?></td>
                                    <td><?php echo $book['author']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($book['issue_date'])); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($book['return_date'])); ?></td>
                                    <td>
                                        <?php if ($book['is_overdue']): ?>
                                            <span class="badge bg-danger">Overdue</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Recent History -->
    <div class="card">
        <div class="card-header">
            <h5><i class="fas fa-history me-2"></i>Recent Return History</h5>
        </div>
        <div class="card-body">
            <?php if (empty($history)): ?>
                <div class="alert alert-info">You haven't returned any books yet.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Author</th>
                                <th>Issued On</th>
                                <th>Returned On</th>
                                <th>Fine</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($history as $book): ?>
                                <tr>
                                    <td><?php echo $book['title']; ?></td>
                                    <td><?php echo $book['author']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($book['issue_date'])); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($book['actual_return_date'])); ?></td>
                                    <td>
                                        <?php if ($book['fine'] > 0): ?>
                                            <span class="text-danger">$<?php echo number_format($book['fine'], 2); ?></span>
                                        <?php else: ?>
                                            <span class="text-success">$0.00</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Include footer
include '../includes/footer.php';
?>

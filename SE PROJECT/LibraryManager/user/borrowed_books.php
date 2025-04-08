<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Check if user is logged in
require_login();

// Get user info
$user_id = $_SESSION['user_id'];
$user = getUserById($conn, $user_id);

// Initialize variables
$error = '';
$success = '';
$tab = isset($_GET['tab']) ? sanitize_input($_GET['tab']) : 'current';

// Get borrowed books
$borrowed_books_query = "SELECT bi.*, b.title, b.author, b.isbn 
                       FROM book_issues bi 
                       JOIN books b ON bi.book_id = b.id 
                       WHERE bi.user_id = $user_id AND bi.status = 'issued' 
                       ORDER BY bi.return_date ASC";
$borrowed_books_result = $conn->query($borrowed_books_query);
$borrowed_books = [];

if ($borrowed_books_result->num_rows > 0) {
    while ($row = $borrowed_books_result->fetch_assoc()) {
        $row['is_overdue'] = strtotime($row['return_date']) < time();
        if ($row['is_overdue']) {
            $row['fine'] = calculateFine($row['return_date']);
        } else {
            $row['fine'] = 0;
        }
        $borrowed_books[] = $row;
    }
}

// Get return history
$history_query = "SELECT bi.*, b.title, b.author, b.isbn 
                 FROM book_issues bi 
                 JOIN books b ON bi.book_id = b.id 
                 WHERE bi.user_id = $user_id AND bi.status = 'returned' 
                 ORDER BY bi.actual_return_date DESC";
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
    <h2 class="mb-4"><i class="fas fa-book-reader me-2"></i>My Borrowed Books</h2>
    
    <!-- Messages -->
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <!-- Tab navigation -->
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link <?php echo $tab === 'current' ? 'active' : ''; ?>" href="borrowed_books.php?tab=current">
                Currently Borrowed
                <?php if (!empty($borrowed_books)): ?>
                    <span class="badge bg-primary ms-1"><?php echo count($borrowed_books); ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $tab === 'history' ? 'active' : ''; ?>" href="borrowed_books.php?tab=history">
                Return History
                <?php if (!empty($history)): ?>
                    <span class="badge bg-secondary ms-1"><?php echo count($history); ?></span>
                <?php endif; ?>
            </a>
        </li>
    </ul>
    
    <?php if ($tab === 'current'): ?>
        <!-- Currently Borrowed Books -->
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-book-reader me-2"></i>Currently Borrowed Books</h5>
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
                                    <th>ISBN</th>
                                    <th>Issue Date</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
                                    <th>Fine (if any)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($borrowed_books as $book): ?>
                                    <tr>
                                        <td><?php echo $book['title']; ?></td>
                                        <td><?php echo $book['author']; ?></td>
                                        <td><?php echo $book['isbn']; ?></td>
                                        <td><?php echo date('M d, Y', strtotime($book['issue_date'])); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($book['return_date'])); ?></td>
                                        <td>
                                            <?php if ($book['is_overdue']): ?>
                                                <span class="badge bg-danger">Overdue</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($book['is_overdue']): ?>
                                                <span class="text-danger">$<?php echo number_format($book['fine'], 2); ?></span>
                                                <small class="d-block text-muted">(Increasing daily)</small>
                                            <?php else: ?>
                                                <span class="text-success">$0.00</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Borrowing Rules -->
                    <div class="alert alert-info mt-3">
                        <h6><i class="fas fa-info-circle me-2"></i>Library Rules:</h6>
                        <ul class="mb-0">
                            <li>Books are issued for a period of 14 days.</li>
                            <li>A fine of $1.00 per day is charged for overdue books.</li>
                            <li>Please return books on time to avoid fines.</li>
                            <li>Visit the library in person to return your books.</li>
                            <li>Lost or damaged books must be reported immediately.</li>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <!-- Return History -->
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-history me-2"></i>Return History</h5>
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
                                    <th>ISBN</th>
                                    <th>Issued On</th>
                                    <th>Due Date</th>
                                    <th>Returned On</th>
                                    <th>Fine Paid</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($history as $book): ?>
                                    <tr>
                                        <td><?php echo $book['title']; ?></td>
                                        <td><?php echo $book['author']; ?></td>
                                        <td><?php echo $book['isbn']; ?></td>
                                        <td><?php echo date('M d, Y', strtotime($book['issue_date'])); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($book['return_date'])); ?></td>
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
    <?php endif; ?>
    
    <!-- Browse Books Button -->
    <div class="text-center mt-4">
        <a href="books.php" class="btn btn-primary">
            <i class="fas fa-book me-2"></i>Browse Library Books
        </a>
    </div>
</div>

<?php
// Include footer
include '../includes/footer.php';
?>

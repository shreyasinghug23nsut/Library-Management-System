<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Check if user is admin
require_admin();

// Initialize variables
$error = '';
$success = '';
$today = date('Y-m-d');

// Handle return form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $issue_id = (int)$_POST['issueId'];
    $actual_return_date = sanitize_input($_POST['actualReturnDate']);
    $fine = (float)$_POST['fine'];
    
    // Validate form data
    if (empty($issue_id) || empty($actual_return_date)) {
        $error = 'Please fill in all required fields';
    } else {
        // Get issue details
        $issue_query = "SELECT * FROM book_issues WHERE id = $issue_id";
        $issue_result = $conn->query($issue_query);
        
        if ($issue_result->num_rows > 0) {
            $issue = $issue_result->fetch_assoc();
            
            if ($issue['status'] === 'returned') {
                $error = 'This book has already been returned';
            } else {
                // Process return
                $update_sql = "UPDATE book_issues SET actual_return_date = ?, status = 'returned', fine = ? WHERE id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("sdi", $actual_return_date, $fine, $issue_id);
                
                if ($update_stmt->execute()) {
                    // Update book available quantity
                    $book_id = $issue['book_id'];
                    $book_query = "SELECT * FROM books WHERE id = $book_id";
                    $book_result = $conn->query($book_query);
                    $book = $book_result->fetch_assoc();
                    
                    $new_available_quantity = $book['available_quantity'] + 1;
                    $update_book_sql = "UPDATE books SET available_quantity = ? WHERE id = ?";
                    $update_book_stmt = $conn->prepare($update_book_sql);
                    $update_book_stmt->bind_param("ii", $new_available_quantity, $book_id);
                    $update_book_stmt->execute();
                    
                    $success = 'Book returned successfully' . ($fine > 0 ? ' with a fine of $' . number_format($fine, 2) : '');
                } else {
                    $error = 'Error processing return: ' . $conn->error;
                }
            }
        } else {
            $error = 'Issue record not found';
        }
    }
}

// Handle specific issue
$issue_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$issue = null;

if ($issue_id > 0) {
    $issue = getIssueById($conn, $issue_id);
}

// Get all issued books
$issues_query = "SELECT bi.*, b.title as book_title, b.isbn, u.name as user_name 
                FROM book_issues bi 
                JOIN books b ON bi.book_id = b.id 
                JOIN users u ON bi.user_id = u.id 
                WHERE bi.status = 'issued' 
                ORDER BY bi.return_date ASC";
$issues_result = $conn->query($issues_query);
$issues = [];

if ($issues_result->num_rows > 0) {
    while ($row = $issues_result->fetch_assoc()) {
        $row['is_overdue'] = strtotime($row['return_date']) < time();
        $issues[] = $row;
    }
}

// Set flag for admin page
$is_admin = true;

// Include header
include '../includes/header.php';
?>

<div class="container">
    <h2 class="mb-4"><i class="fas fa-undo me-2"></i>Return Book</h2>
    
    <!-- Messages -->
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if ($issue): ?>
        <!-- Return Book Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h5>Return Book</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="return_book.php">
                    <input type="hidden" name="issueId" value="<?php echo $issue['id']; ?>">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><strong>Book:</strong> <?php echo $issue['title']; ?></p>
                            <p><strong>ISBN:</strong> <?php echo $issue['isbn']; ?></p>
                            <p><strong>User:</strong> <?php echo $issue['user_name']; ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Issue Date:</strong> <?php echo date('M d, Y', strtotime($issue['issue_date'])); ?></p>
                            <p><strong>Due Return Date:</strong> <?php echo date('M d, Y', strtotime($issue['return_date'])); ?></p>
                            <p>
                                <strong>Status:</strong>
                                <?php if (strtotime($issue['return_date']) < time()): ?>
                                    <span class="badge bg-danger">Overdue</span>
                                <?php else: ?>
                                    <span class="badge bg-success">Active</span>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="actualReturnDate" class="form-label">Actual Return Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="actualReturnDate" name="actualReturnDate" value="<?php echo $today; ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="fineAmount" class="form-label">Fine Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="text" class="form-control" id="fineAmount" value="0.00" readonly>
                            </div>
                            <input type="hidden" id="fine" name="fine" value="0.00">
                            <div class="form-text">Fine is calculated at $1 per day if returned after due date.</div>
                        </div>
                    </div>
                    
                    <div class="text-end">
                        <a href="return_book.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Return Book</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Issued Books List -->
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5>Currently Issued Books</h5>
                <input type="text" class="form-control form-control-sm" style="width: 250px;" id="searchInput" placeholder="Search issued books...">
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($issues)): ?>
                <div class="alert alert-info">No books are currently issued.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Book Title</th>
                                <th>User</th>
                                <th>Issue Date</th>
                                <th>Return Due Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($issues as $issue): ?>
                                <tr>
                                    <td><?php echo $issue['book_title']; ?></td>
                                    <td><?php echo $issue['user_name']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($issue['issue_date'])); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($issue['return_date'])); ?></td>
                                    <td>
                                        <?php if ($issue['is_overdue']): ?>
                                            <span class="badge bg-danger">Overdue</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="return_book.php?id=<?php echo $issue['id']; ?>" class="btn btn-sm btn-primary">
                                            Return
                                        </a>
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

<script>
    // Initialize after page load
    document.addEventListener('DOMContentLoaded', function() {
        calculateFine();
    });
</script>

<?php
// Include footer
include '../includes/footer.php';
?>

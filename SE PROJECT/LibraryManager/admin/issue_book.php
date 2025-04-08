<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Check if user is admin
require_admin();

// Initialize variables
$error = '';
$success = '';
$book_id = isset($_GET['book_id']) ? (int)$_GET['book_id'] : 0;
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$today = date('Y-m-d');
$default_return_date = date('Y-m-d', strtotime('+14 days'));

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $book_id = (int)$_POST['bookId'];
    $user_id = (int)$_POST['userId'];
    $issue_date = sanitize_input($_POST['issueDate']);
    $return_date = sanitize_input($_POST['returnDate']);
    
    // Validate form data
    if (empty($book_id) || empty($user_id) || empty($issue_date) || empty($return_date)) {
        $error = 'Please fill in all required fields';
    } elseif (strtotime($return_date) <= strtotime($issue_date)) {
        $error = 'Return date must be after issue date';
    } else {
        // Check if book is available
        $book_query = "SELECT * FROM books WHERE id = $book_id";
        $book_result = $conn->query($book_query);
        
        if ($book_result->num_rows > 0) {
            $book = $book_result->fetch_assoc();
            
            if ($book['available_quantity'] > 0) {
                // Check if user already has this book
                $check_query = "SELECT * FROM book_issues WHERE book_id = $book_id AND user_id = $user_id AND status = 'issued'";
                $check_result = $conn->query($check_query);
                
                if ($check_result->num_rows > 0) {
                    $error = 'This user already has this book issued';
                } else {
                    // Issue book
                    $issue_sql = "INSERT INTO book_issues (book_id, user_id, issue_date, return_date, issued_by) 
                                 VALUES (?, ?, ?, ?, ?)";
                    $issue_stmt = $conn->prepare($issue_sql);
                    $issue_stmt->bind_param("iissi", $book_id, $user_id, $issue_date, $return_date, $_SESSION['user_id']);
                    
                    if ($issue_stmt->execute()) {
                        // Update available quantity
                        $new_available_quantity = $book['available_quantity'] - 1;
                        $update_sql = "UPDATE books SET available_quantity = ? WHERE id = ?";
                        $update_stmt = $conn->prepare($update_sql);
                        $update_stmt->bind_param("ii", $new_available_quantity, $book_id);
                        $update_stmt->execute();
                        
                        $success = 'Book issued successfully';
                        $book_id = $user_id = 0;
                    } else {
                        $error = 'Error issuing book: ' . $conn->error;
                    }
                }
            } else {
                $error = 'Book is not available for issue';
            }
        } else {
            $error = 'Book not found';
        }
    }
}

// Get all available books
$books_query = "SELECT * FROM books WHERE available_quantity > 0 ORDER BY title ASC";
$books_result = $conn->query($books_query);
$books = [];

if ($books_result->num_rows > 0) {
    while ($row = $books_result->fetch_assoc()) {
        $books[] = $row;
    }
}

// Get all users
$users_query = "SELECT * FROM users WHERE role = 'user' ORDER BY name ASC";
$users_result = $conn->query($users_query);
$users = [];

if ($users_result->num_rows > 0) {
    while ($row = $users_result->fetch_assoc()) {
        $users[] = $row;
    }
}

// If book_id is provided, get book details
$selected_book = null;
if ($book_id > 0) {
    $book_query = "SELECT * FROM books WHERE id = $book_id";
    $book_result = $conn->query($book_query);
    
    if ($book_result->num_rows > 0) {
        $selected_book = $book_result->fetch_assoc();
    }
}

// If user_id is provided, get user details
$selected_user = null;
if ($user_id > 0) {
    $user_query = "SELECT * FROM users WHERE id = $user_id";
    $user_result = $conn->query($user_query);
    
    if ($user_result->num_rows > 0) {
        $selected_user = $user_result->fetch_assoc();
    }
}

// Set flag for admin page
$is_admin = true;

// Include header
include '../includes/header.php';
?>

<div class="container">
    <h2 class="mb-4"><i class="fas fa-book me-2"></i>Issue Book</h2>
    
    <!-- Messages -->
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <!-- Issue Book Form -->
    <div class="card">
        <div class="card-header">
            <h5>Issue a Book to User</h5>
        </div>
        <div class="card-body">
            <form id="issueBookForm" method="POST" action="issue_book.php">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="bookId" class="form-label">Select Book <span class="text-danger">*</span></label>
                        <select class="form-select" id="bookId" name="bookId" required>
                            <option value="">Select a book</option>
                            <?php foreach ($books as $book): ?>
                                <option value="<?php echo $book['id']; ?>" <?php echo ($book_id == $book['id']) ? 'selected' : ''; ?>>
                                    <?php echo $book['title']; ?> (<?php echo $book['author']; ?>) - <?php echo $book['available_quantity']; ?> available
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="userId" class="form-label">Select User <span class="text-danger">*</span></label>
                        <select class="form-select" id="userId" name="userId" required>
                            <option value="">Select a user</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['id']; ?>" <?php echo ($user_id == $user['id']) ? 'selected' : ''; ?>>
                                    <?php echo $user['name']; ?> (<?php echo $user['email']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="issueDate" class="form-label">Issue Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="issueDate" name="issueDate" value="<?php echo $today; ?>" required>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="returnDate" class="form-label">Return Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="returnDate" name="returnDate" value="<?php echo $default_return_date; ?>" required>
                    </div>
                </div>
                
                <div class="text-end">
                    <button type="submit" class="btn btn-primary">Issue Book</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Book Information Card (shown only when a book is selected) -->
    <?php if ($selected_book): ?>
        <div class="card mt-4">
            <div class="card-header">
                <h5>Selected Book Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Title:</strong> <?php echo $selected_book['title']; ?></p>
                        <p><strong>Author:</strong> <?php echo $selected_book['author']; ?></p>
                        <p><strong>ISBN:</strong> <?php echo $selected_book['isbn']; ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Category:</strong> <?php echo $selected_book['category']; ?></p>
                        <p><strong>Available Quantity:</strong> <?php echo $selected_book['available_quantity']; ?> of <?php echo $selected_book['quantity']; ?></p>
                        <?php if (!empty($selected_book['description'])): ?>
                            <p><strong>Description:</strong> <?php echo $selected_book['description']; ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- User Information Card (shown only when a user is selected) -->
    <?php if ($selected_user): ?>
        <div class="card mt-4">
            <div class="card-header">
                <h5>Selected User Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Name:</strong> <?php echo $selected_user['name']; ?></p>
                        <p><strong>Email:</strong> <?php echo $selected_user['email']; ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Registration Date:</strong> <?php echo date('M d, Y', strtotime($selected_user['created_at'])); ?></p>
                        
                        <?php
                        // Get user's borrowed books
                        $borrowed_books_query = "SELECT bi.*, b.title 
                                               FROM book_issues bi 
                                               JOIN books b ON bi.book_id = b.id 
                                               WHERE bi.user_id = $user_id AND bi.status = 'issued'";
                        $borrowed_books_result = $conn->query($borrowed_books_query);
                        $borrowed_books = [];
                        
                        if ($borrowed_books_result->num_rows > 0) {
                            while ($row = $borrowed_books_result->fetch_assoc()) {
                                $borrowed_books[] = $row;
                            }
                        }
                        ?>
                        
                        <p><strong>Currently Borrowed Books:</strong> <?php echo count($borrowed_books); ?></p>
                        
                        <?php if (!empty($borrowed_books)): ?>
                            <ul>
                                <?php foreach ($borrowed_books as $book): ?>
                                    <li>
                                        <?php echo $book['title']; ?> 
                                        (Due: <?php echo date('M d, Y', strtotime($book['return_date'])); ?>)
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
// Include footer
include '../includes/footer.php';
?>

<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Check if user is admin
require_admin();

// Initialize variables
$error = '';
$success = '';
$title = '';
$author = '';
$isbn = '';
$category = '';
$quantity = '';
$description = '';
$editing = false;
$book_id = 0;

// Handle book form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_book']) || isset($_POST['update_book'])) {
        // Get form data
        $title = sanitize_input($_POST['title']);
        $author = sanitize_input($_POST['author']);
        $isbn = sanitize_input($_POST['isbn']);
        $category = sanitize_input($_POST['category']);
        $quantity = (int)sanitize_input($_POST['quantity']);
        $description = sanitize_input($_POST['description']);
        
        // Validate form data
        if (empty($title) || empty($author) || empty($isbn) || empty($category) || empty($quantity)) {
            $error = 'Please fill in all required fields';
        } elseif ($quantity <= 0) {
            $error = 'Quantity must be a positive number';
        } else {
            // Prepare SQL statement
            if (isset($_POST['add_book'])) {
                // Check if ISBN already exists
                $check_sql = "SELECT * FROM books WHERE isbn = ?";
                $check_stmt = $conn->prepare($check_sql);
                $check_stmt->bind_param("s", $isbn);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows > 0) {
                    $error = 'A book with this ISBN already exists';
                } else {
                    // Add new book
                    $sql = "INSERT INTO books (title, author, isbn, category, quantity, available_quantity, description, added_by) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ssssiiis", $title, $author, $isbn, $category, $quantity, $quantity, $description, $_SESSION['user_id']);
                    
                    if ($stmt->execute()) {
                        $success = 'Book added successfully';
                        $title = $author = $isbn = $category = $quantity = $description = '';
                    } else {
                        $error = 'Error adding book: ' . $conn->error;
                    }
                }
            } elseif (isset($_POST['update_book'])) {
                $book_id = (int)$_POST['book_id'];
                
                // Get current book details
                $current_book_sql = "SELECT * FROM books WHERE id = ?";
                $current_book_stmt = $conn->prepare($current_book_sql);
                $current_book_stmt->bind_param("i", $book_id);
                $current_book_stmt->execute();
                $current_book_result = $current_book_stmt->get_result();
                
                if ($current_book_result->num_rows > 0) {
                    $current_book = $current_book_result->fetch_assoc();
                    
                    // Check if ISBN is changed and already exists
                    if ($isbn !== $current_book['isbn']) {
                        $check_sql = "SELECT * FROM books WHERE isbn = ? AND id != ?";
                        $check_stmt = $conn->prepare($check_sql);
                        $check_stmt->bind_param("si", $isbn, $book_id);
                        $check_stmt->execute();
                        $check_result = $check_stmt->get_result();
                        
                        if ($check_result->num_rows > 0) {
                            $error = 'A book with this ISBN already exists';
                            $editing = true;
                            goto end_processing;
                        }
                    }
                    
                    // Calculate the difference in quantity
                    $quantity_diff = $quantity - $current_book['quantity'];
                    $new_available_quantity = $current_book['available_quantity'] + $quantity_diff;
                    
                    if ($new_available_quantity < 0) {
                        $error = 'Cannot reduce quantity below the number of books currently issued';
                        $editing = true;
                        goto end_processing;
                    }
                    
                    // Update book
                    $sql = "UPDATE books SET title = ?, author = ?, isbn = ?, category = ?, 
                            quantity = ?, available_quantity = ?, description = ? WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ssssiisi", $title, $author, $isbn, $category, $quantity, $new_available_quantity, $description, $book_id);
                    
                    if ($stmt->execute()) {
                        $success = 'Book updated successfully';
                        $editing = false;
                        $title = $author = $isbn = $category = $quantity = $description = '';
                        $book_id = 0;
                    } else {
                        $error = 'Error updating book: ' . $conn->error;
                        $editing = true;
                    }
                } else {
                    $error = 'Book not found';
                }
            }
        }
    }
}

// Handle edit request
if (isset($_GET['edit'])) {
    $book_id = (int)$_GET['edit'];
    $book = getBookById($conn, $book_id);
    
    if ($book) {
        $editing = true;
        $title = $book['title'];
        $author = $book['author'];
        $isbn = $book['isbn'];
        $category = $book['category'];
        $quantity = $book['quantity'];
        $description = $book['description'];
    } else {
        $error = 'Book not found';
    }
}

// Handle delete request
if (isset($_GET['delete'])) {
    $book_id = (int)$_GET['delete'];
    
    // Check if book is issued
    $check_issued_sql = "SELECT * FROM book_issues WHERE book_id = ? AND status = 'issued'";
    $check_issued_stmt = $conn->prepare($check_issued_sql);
    $check_issued_stmt->bind_param("i", $book_id);
    $check_issued_stmt->execute();
    $check_issued_result = $check_issued_stmt->get_result();
    
    if ($check_issued_result->num_rows > 0) {
        $error = 'Cannot delete book as it is currently issued to users';
    } else {
        // Delete book
        $delete_sql = "DELETE FROM books WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $book_id);
        
        if ($delete_stmt->execute()) {
            $success = 'Book deleted successfully';
        } else {
            $error = 'Error deleting book: ' . $conn->error;
        }
    }
}

end_processing:

// Get all books
$books_query = "SELECT * FROM books ORDER BY title ASC";
$books_result = $conn->query($books_query);
$books = [];

if ($books_result->num_rows > 0) {
    while ($row = $books_result->fetch_assoc()) {
        $books[] = $row;
    }
}

// Set flag for admin page
$is_admin = true;

// Include header
include '../includes/header.php';
?>

<div class="container">
    <h2 class="mb-4"><i class="fas fa-book me-2"></i>Manage Books</h2>
    
    <!-- Messages -->
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <!-- Book Form -->
    <div class="card mb-4">
        <div class="card-header">
            <h5><?php echo $editing ? 'Edit Book' : 'Add New Book'; ?></h5>
        </div>
        <div class="card-body">
            <form id="bookForm" method="POST" action="manage_books.php">
                <?php if ($editing): ?>
                    <input type="hidden" name="book_id" value="<?php echo $book_id; ?>">
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" value="<?php echo $title; ?>" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="author" class="form-label">Author <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="author" name="author" value="<?php echo $author; ?>" required>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="isbn" class="form-label">ISBN <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="isbn" name="isbn" value="<?php echo $isbn; ?>" required>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="category" class="form-label">Category <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="category" name="category" value="<?php echo $category; ?>" required>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="quantity" class="form-label">Quantity <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="quantity" name="quantity" value="<?php echo $quantity; ?>" min="1" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3"><?php echo $description; ?></textarea>
                </div>
                
                <div class="text-end">
                    <?php if ($editing): ?>
                        <button type="submit" name="update_book" class="btn btn-primary">Update Book</button>
                        <a href="manage_books.php" class="btn btn-secondary">Cancel</a>
                    <?php else: ?>
                        <button type="submit" name="add_book" class="btn btn-primary">Add Book</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Books List -->
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5>Books List</h5>
                <input type="text" class="form-control form-control-sm" style="width: 250px;" id="searchInput" placeholder="Search books...">
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($books)): ?>
                <div class="alert alert-info">No books available in the library.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Author</th>
                                <th>ISBN</th>
                                <th>Category</th>
                                <th>Quantity</th>
                                <th>Available</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($books as $book): ?>
                                <tr>
                                    <td><?php echo $book['title']; ?></td>
                                    <td><?php echo $book['author']; ?></td>
                                    <td><?php echo $book['isbn']; ?></td>
                                    <td><?php echo $book['category']; ?></td>
                                    <td><?php echo $book['quantity']; ?></td>
                                    <td>
                                        <?php if ($book['available_quantity'] > 0): ?>
                                            <span class="badge bg-success"><?php echo $book['available_quantity']; ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="manage_books.php?edit=<?php echo $book['id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="manage_books.php?delete=<?php echo $book['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirmDelete(event, 'Are you sure you want to delete this book?')">
                                            <i class="fas fa-trash"></i>
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

<?php
// Include footer
include '../includes/footer.php';
?>

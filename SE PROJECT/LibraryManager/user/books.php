<?php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Check if user is logged in
require_login();

// Initialize variables
$search_keyword = '';
$category_filter = '';
$view_book = null;

// Handle search query
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_keyword = sanitize_input($_GET['search']);
}

// Handle category filter
if (isset($_GET['category']) && !empty($_GET['category'])) {
    $category_filter = sanitize_input($_GET['category']);
}

// Handle view book request
if (isset($_GET['book_id'])) {
    $book_id = (int)$_GET['book_id'];
    $view_book = getBookById($conn, $book_id);
}

// Get all books
$books_query = "SELECT * FROM books";
$where_conditions = [];

if (!empty($search_keyword)) {
    $search_keyword = $conn->real_escape_string($search_keyword);
    $where_conditions[] = "(title LIKE '%$search_keyword%' OR author LIKE '%$search_keyword%' OR isbn LIKE '%$search_keyword%' OR category LIKE '%$search_keyword%')";
}

if (!empty($category_filter)) {
    $category_filter = $conn->real_escape_string($category_filter);
    $where_conditions[] = "category = '$category_filter'";
}

if (!empty($where_conditions)) {
    $books_query .= " WHERE " . implode(" AND ", $where_conditions);
}

$books_query .= " ORDER BY title ASC";
$books_result = $conn->query($books_query);
$books = [];

if ($books_result->num_rows > 0) {
    while ($row = $books_result->fetch_assoc()) {
        $books[] = $row;
    }
}

// Get all categories
$categories_query = "SELECT DISTINCT category FROM books ORDER BY category ASC";
$categories_result = $conn->query($categories_query);
$categories = [];

if ($categories_result->num_rows > 0) {
    while ($row = $categories_result->fetch_assoc()) {
        $categories[] = $row['category'];
    }
}

// Set flag for user page
$is_user = true;

// Include header
include '../includes/header.php';
?>

<div class="container">
    <?php if ($view_book): ?>
        <!-- Book Details View -->
        <div class="book-details">
            <div class="mb-3">
                <a href="books.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left me-1"></i> Back to Books
                </a>
            </div>
            
            <h2><?php echo $view_book['title']; ?></h2>
            
            <div class="row">
                <div class="col-md-8">
                    <table class="table">
                        <tr>
                            <th style="width: 150px;">Author</th>
                            <td><?php echo $view_book['author']; ?></td>
                        </tr>
                        <tr>
                            <th>ISBN</th>
                            <td><?php echo $view_book['isbn']; ?></td>
                        </tr>
                        <tr>
                            <th>Category</th>
                            <td><?php echo $view_book['category']; ?></td>
                        </tr>
                        <tr>
                            <th>Availability</th>
                            <td>
                                <?php if ($view_book['available_quantity'] > 0): ?>
                                    <span class="badge bg-success">Available</span>
                                    (<?php echo $view_book['available_quantity']; ?> of <?php echo $view_book['quantity']; ?> copies)
                                <?php else: ?>
                                    <span class="badge bg-danger">Not Available</span>
                                    (0 of <?php echo $view_book['quantity']; ?> copies)
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php if (!empty($view_book['description'])): ?>
                            <tr>
                                <th>Description</th>
                                <td><?php echo $view_book['description']; ?></td>
                            </tr>
                        <?php endif; ?>
                    </table>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="fas fa-book fa-5x mb-3 text-primary"></i>
                            <?php if ($view_book['available_quantity'] > 0): ?>
                                <p>This book is currently available in the library.</p>
                                <p class="text-muted">Please visit the library to check out this book.</p>
                            <?php else: ?>
                                <p class="text-danger">This book is currently unavailable.</p>
                                <p class="text-muted">All copies are currently borrowed.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Books Listing -->
        <h2 class="mb-4"><i class="fas fa-book me-2"></i>Library Books</h2>
        
        <!-- Search and Filter -->
        <div class="card mb-4">
            <div class="card-body">
                <form action="books.php" method="GET" class="row g-3">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" id="search" name="search" placeholder="Search by title, author, ISBN..." value="<?php echo $search_keyword; ?>">
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <select class="form-select" id="category" name="category">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category; ?>" <?php echo ($category === $category_filter) ? 'selected' : ''; ?>>
                                    <?php echo $category; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Books Display -->
        <?php if (empty($books)): ?>
            <div class="alert alert-info">
                <?php echo !empty($search_keyword) || !empty($category_filter) ? 'No books found matching your criteria.' : 'No books available in the library.'; ?>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($books as $book): ?>
                    <div class="col-md-4 col-sm-6 mb-4">
                        <div class="card book-card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span class="text-truncate"><?php echo $book['title']; ?></span>
                                <?php if ($book['available_quantity'] > 0): ?>
                                    <span class="badge rounded-pill badge-available">Available</span>
                                <?php else: ?>
                                    <span class="badge rounded-pill badge-unavailable">Unavailable</span>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo $book['title']; ?></h5>
                                <p class="card-text"><strong>Author:</strong> <?php echo $book['author']; ?></p>
                                <p class="card-text"><strong>ISBN:</strong> <?php echo $book['isbn']; ?></p>
                                <p class="card-text"><strong>Category:</strong> <?php echo $book['category']; ?></p>
                                <p class="card-text">
                                    <strong>Availability:</strong> 
                                    <?php echo $book['available_quantity']; ?>/<?php echo $book['quantity']; ?> copies
                                </p>
                                
                                <div class="mt-auto text-center">
                                    <a href="books.php?book_id=<?php echo $book['id']; ?>" class="btn btn-primary btn-sm">View Details</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php
// Include footer
include '../includes/footer.php';
?>

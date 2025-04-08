<?php
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Handle search query - only for logged-in users
$books = [];
$search_keyword = '';

// Ensure we have access to database functions and connection
include_once 'includes/db_connect.php';
include_once 'includes/functions.php';

// Access global database connection
global $conn;

// Only get books if user is logged in
if (isLoggedIn()) {
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search_keyword = sanitize_input($_GET['search']);
        $books = searchBooks($conn, $search_keyword);
    } else {
        $books = getAllBooks($conn);
    }
}

// Include header
include 'includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section text-center">
    <div class="container">
        <h1><i class="fas fa-book-open me-2"></i>Welcome to Our Library</h1>
        <p class="lead">Explore our vast collection of books and resources.</p>
        <?php if (!isLoggedIn()): ?>
            <div class="mt-4">
                <a href="login.php" class="btn btn-light btn-lg me-2">Login</a>
                <a href="register.php" class="btn btn-outline-light btn-lg">Register</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Search Box - only visible when logged in -->
<?php if (isLoggedIn()): ?>
<section class="search-box">
    <div class="container">
        <form action="index.php" method="GET" class="row g-3">
            <div class="col-md-8">
                <div class="input-group">
                    <div class="input-group-prepend">
                        <i class="fas fa-search"></i>
                    </div>
                    <input type="text" class="form-control" id="searchInput" name="search" placeholder="Search books by title, author, ISBN or category..." value="<?php echo $search_keyword; ?>">
                </div>
            </div>
            <div class="col-md-2">
                <select class="form-select" id="availabilityFilter">
                    <option value="all">All Books</option>
                    <option value="available">Available</option>
                    <option value="unavailable">Unavailable</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search me-2"></i>Search
                </button>
            </div>
        </form>
    </div>
</section>
<?php endif; ?>

<!-- Books Section - only visible when logged in -->
<?php if (isLoggedIn()): ?>
<section class="books-section">
    <div class="container">
        <h2 class="mb-4"><?php echo !empty($search_keyword) ? 'Search Results for "' . $search_keyword . '"' : 'Available Books'; ?></h2>
        
        <?php if (empty($books)): ?>
            <div class="alert alert-info">
                <?php echo !empty($search_keyword) ? 'No books found matching your search criteria.' : 'No books available in the library at the moment.'; ?>
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
                                <p class="card-text book-author"><strong>Author:</strong> <?php echo $book['author']; ?></p>
                                <p class="card-text book-isbn"><strong>ISBN:</strong> <?php echo $book['isbn']; ?></p>
                                <p class="card-text"><strong>Category:</strong> <?php echo $book['category']; ?></p>
                                <p class="card-text">
                                    <strong>Availability:</strong> 
                                    <?php echo $book['available_quantity']; ?>/<?php echo $book['quantity']; ?> copies
                                </p>
                                <?php if (!empty($book['description'])): ?>
                                    <p class="card-text book-description">
                                        <?php 
                                            $desc = $book['description'];
                                            echo strlen($desc) > 150 ? substr($desc, 0, 150) . '...' : $desc; 
                                        ?>
                                    </p>
                                <?php endif; ?>
                                
                                <?php if (isAdmin()): ?>
                                    <div class="mt-auto text-center">
                                        <a href="admin/issue_book.php?book_id=<?php echo $book['id']; ?>" class="btn btn-sm btn-primary">Issue Book</a>
                                        <a href="admin/manage_books.php?edit=<?php echo $book['id']; ?>" class="btn btn-sm btn-secondary">Edit</a>
                                    </div>
                                <?php elseif ($book['available_quantity'] > 0): ?>
                                    <div class="mt-auto text-center">
                                        <a href="user/books.php?book_id=<?php echo $book['id']; ?>" class="btn btn-sm btn-primary">View Details</a>
                                    </div>
                                <?php else: ?>
                                    <div class="mt-auto text-center">
                                        <button class="btn btn-sm btn-secondary" disabled>Currently Unavailable</button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php else: ?>
<!-- Welcome message for non-logged in users -->
<section class="py-5">
    <div class="container">
        <div class="text-center">
            <h2 class="mb-4">Welcome to our Library Management System</h2>
            <p class="lead mb-4">Please log in or register to view and borrow books from our collection.</p>
            <div class="mb-4">
                <a href="login.php" class="btn btn-primary mx-2">Login</a>
                <a href="register.php" class="btn btn-outline-primary mx-2">Register</a>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Library Features Section -->
<section class="py-5 bg-light mt-5">
    <div class="container">
        <h2 class="text-center mb-4">Library Features</h2>
        <div class="row">
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-body text-center">
                        <i class="fas fa-search fa-3x mb-3 text-primary"></i>
                        <h5 class="card-title">Search Books</h5>
                        <p class="card-text">Easily search our extensive collection of books by title, author, or category.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-body text-center">
                        <i class="fas fa-book-reader fa-3x mb-3 text-primary"></i>
                        <h5 class="card-title">Borrow Books</h5>
                        <p class="card-text">Register to borrow books from our collection and track your reading history.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-body text-center">
                        <i class="fas fa-clock fa-3x mb-3 text-primary"></i>
                        <h5 class="card-title">Easy Returns</h5>
                        <p class="card-text">Return books at your convenience with our simple return process.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
// Include footer
include 'includes/footer.php';
?>

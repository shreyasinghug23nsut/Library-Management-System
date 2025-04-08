<?php
session_start();
require_once '../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Handle book addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_book'])) {
    $isbn = $_POST['isbn'];
    $title = $_POST['title'];
    $author = $_POST['author'];
    $category = $_POST['category'];
    $quantity = $_POST['quantity'];

    $stmt = $conn->prepare("INSERT INTO books (isbn, title, author, category, quantity, available) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssii", $isbn, $title, $author, $category, $quantity, $quantity);
    
    if ($stmt->execute()) {
        $success = "Book added successfully!";
    } else {
        $error = "Error adding book: " . $conn->error;
    }
}

// Get all books
$books = $conn->query("SELECT * FROM books ORDER BY title");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Books - Library Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <h1>Manage Books</h1>
        
        <!-- Add Book Form -->
        <div class="add-book-form">
            <h2>Add New Book</h2>
            <?php if (isset($success)): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="isbn">ISBN:</label>
                    <input type="text" id="isbn" name="isbn" required>
                </div>
                
                <div class="form-group">
                    <label for="title">Title:</label>
                    <input type="text" id="title" name="title" required>
                </div>
                
                <div class="form-group">
                    <label for="author">Author:</label>
                    <input type="text" id="author" name="author" required>
                </div>
                
                <div class="form-group">
                    <label for="category">Category:</label>
                    <select id="category" name="category" required>
                        <option value="Fiction">Fiction</option>
                        <option value="Non-Fiction">Non-Fiction</option>
                        <option value="Science">Science</option>
                        <option value="Technology">Technology</option>
                        <option value="Programming">Programming</option>
                        <option value="History">History</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="quantity">Quantity:</label>
                    <input type="number" id="quantity" name="quantity" min="1" required>
                </div>
                
                <button type="submit" name="add_book" class="btn">Add Book</button>
            </form>
        </div>

        <!-- Books List -->
        <div class="books-list">
            <h2>Current Books</h2>
            <table>
                <thead>
                    <tr>
                        <th>ISBN</th>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Category</th>
                        <th>Available/Total</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($book = $books->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($book['isbn']); ?></td>
                        <td><?php echo htmlspecialchars($book['title']); ?></td>
                        <td><?php echo htmlspecialchars($book['author']); ?></td>
                        <td><?php echo htmlspecialchars($book['category']); ?></td>
                        <td><?php echo $book['available']; ?>/<?php echo $book['quantity']; ?></td>
                        <td>
                            <a href="edit-book.php?id=<?php echo $book['id']; ?>" class="btn btn-small">Edit</a>
                            <a href="delete-book.php?id=<?php echo $book['id']; ?>" class="btn btn-small btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
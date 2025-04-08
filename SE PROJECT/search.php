<?php
session_start();
require_once 'includes/config.php';

$search = isset($_GET['search']) ? $_GET['search'] : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';

$sql = "SELECT * FROM books WHERE 1=1";
if ($search) {
    $sql .= " AND (title LIKE ? OR author LIKE ? OR isbn LIKE ?)";
}
if ($category) {
    $sql .= " AND category = ?";
}

$stmt = $conn->prepare($sql);

if ($search && $category) {
    $search = "%$search%";
    $stmt->bind_param("ssss", $search, $search, $search, $category);
} elseif ($search) {
    $search = "%$search%";
    $stmt->bind_param("sss", $search, $search, $search);
} elseif ($category) {
    $stmt->bind_param("s", $category);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Books - Library Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <h1>Search Books</h1>
        
        <div class="search-form">
            <form method="GET" action="">
                <div class="form-group">
                    <input type="text" name="search" placeholder="Search by title, author, or ISBN" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                
                <div class="form-group">
                    <select name="category">
                        <option value="">All Categories</option>
                        <option value="Fiction" <?php echo $category === 'Fiction' ? 'selected' : ''; ?>>Fiction</option>
                        <option value="Non-Fiction" <?php echo $category === 'Non-Fiction' ? 'selected' : ''; ?>>Non-Fiction</option>
                        <option value="Science" <?php echo $category === 'Science' ? 'selected' : ''; ?>>Science</option>
                        <option value="Technology" <?php echo $category === 'Technology' ? 'selected' : ''; ?>>Technology</option>
                        <option value="Programming" <?php echo $category === 'Programming' ? 'selected' : ''; ?>>Programming</option>
                        <option value="History" <?php echo $category === 'History' ? 'selected' : ''; ?>>History</option>
                    </select>
                </div>
                
                <button type="submit" class="btn">Search</button>
            </form>
        </div>

        <div class="search-results">
            <?php if ($result->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Category</th>
                            <th>Availability</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($book = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($book['title']); ?></td>
                            <td><?php echo htmlspecialchars($book['author']); ?></td>
                            <td><?php echo htmlspecialchars($book['category']); ?></td>
                            <td><?php echo $book['available'] > 0 ? 'Available' : 'Not Available'; ?></td>
                            <td>
                                <?php if (isset($_SESSION['user_id']) && $book['available'] > 0): ?>
                                    <a href="reserve.php?book_id=<?php echo $book['id']; ?>" class="btn btn-small">Reserve</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No books found matching your criteria.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
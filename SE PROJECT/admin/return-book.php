<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/fine_calculator.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['issue_id'])) {
    $issue_id = $_POST['issue_id'];
    
    // Get issue details
    $issue = $conn->query("SELECT * FROM book_issues WHERE id = $issue_id")->fetch_assoc();
    
    if ($issue) {
        $calculator = new FineCalculator();
        $fine = $calculator->calculateFine($issue['return_date']);
        
        // Update book issue
        $stmt = $conn->prepare("
            UPDATE book_issues 
            SET status = 'returned', 
                actual_return_date = CURRENT_DATE,
                fine = ?
            WHERE id = ?
        ");
        $stmt->bind_param("di", $fine, $issue_id);
        
        if ($stmt->execute()) {
            // Update book availability
            $conn->query("
                UPDATE books 
                SET available = available + 1 
                WHERE id = {$issue['book_id']}
            ");
            
            $_SESSION['message'] = "Book returned successfully. Fine amount: $" . number_format($fine, 2);
        } else {
            $_SESSION['error'] = "Failed to process return.";
        }
    }
    
    header('Location: manage-issues.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Return Book - Library Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <h1>Return Book</h1>
        
        <?php
        if (isset($_GET['issue_id'])) {
            $issue_id = $_GET['issue_id'];
            $issue = $conn->query("
                SELECT bi.*, b.title, u.username 
                FROM book_issues bi 
                JOIN books b ON bi.book_id = b.id 
                JOIN users u ON bi.user_id = u.id 
                WHERE bi.id = $issue_id
            ")->fetch_assoc();
            
            if ($issue): 
            ?>
            <div class="return-form">
                <h2>Return Details</h2>
                <p><strong>Book:</strong> <?php echo htmlspecialchars($issue['title']); ?></p>
                <p><strong>Borrowed By:</strong> <?php echo htmlspecialchars($issue['username']); ?></p>
                <p><strong>Issue Date:</strong> <?php echo $issue['issue_date']; ?></p>
                <p><strong>Due Date:</strong> <?php echo $issue['return_date']; ?></p>
                
                <?php
                $calculator = new FineCalculator();
                $estimated_fine = $calculator->calculateFine($issue['return_date']);
                if ($estimated_fine > 0):
                ?>
                <p><strong>Estimated Fine:</strong> $<?php echo number_format($estimated_fine, 2); ?></p>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <input type="hidden" name="issue_id" value="<?php echo $issue_id; ?>">
                    <button type="submit" class="btn" onclick="return confirm('Confirm book return?')">
                        Confirm Return
                    </button>
                </form>
            </div>
            <?php else: ?>
            <p>Invalid issue ID.</p>
            <?php endif; ?>
        <?php } ?>
    </div>
</body>
</html>
<?php
require_once '../includes/db_connect.php';  // This includes the $conn and $pdo variables
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Check if user is admin
require_admin();

// Initialize variables
$error = '';
$success = '';

// Handle user deletion
if (isset($_GET['delete'])) {
    $user_id = (int)$_GET['delete'];
    
    // Check if user has any issued books - using PDO
    $check_issued_sql = "SELECT * FROM book_issues WHERE user_id = :user_id AND status = 'issued'";
    $check_issued_stmt = $pdo->prepare($check_issued_sql);
    $check_issued_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $check_issued_stmt->execute();
    $has_issued_books = $check_issued_stmt->rowCount() > 0;
    
    if ($has_issued_books) {
        $error = 'Cannot delete user as they have books currently issued';
    } else {
        // Delete user
        $delete_sql = "DELETE FROM users WHERE id = :user_id AND role = 'user'";
        $delete_stmt = $pdo->prepare($delete_sql);
        $delete_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        
        if ($delete_stmt->execute()) {
            $success = 'User deleted successfully';
        } else {
            $error = 'Error deleting user';
        }
    }
}

// Get all users
$users_query = "SELECT * FROM users WHERE role = 'user' ORDER BY name ASC";
$users_stmt = $pdo->prepare($users_query);
$users_stmt->execute();
$users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get issued books for each user
foreach ($users as &$user) {
    $user_id = $user['id'];
    
    // Use PDO prepared statement for safer and more reliable query
    $issued_books_query = "SELECT COUNT(*) as count FROM book_issues WHERE user_id = :user_id AND status = 'issued'";
    $stmt = $pdo->prepare($issued_books_query);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $user['issued_books'] = $result['count'];
}

// Set flag for admin page
$is_admin = true;

// Include header
include '../includes/header.php';
?>

<div class="container">
    <h2 class="mb-4"><i class="fas fa-users me-2"></i>Manage Users</h2>
    
    <!-- Messages -->
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <!-- Users List -->
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5>Registered Users</h5>
                <input type="text" class="form-control form-control-sm" style="width: 250px;" id="searchInput" placeholder="Search users...">
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($users)): ?>
                <div class="alert alert-info">No registered users found.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Registration Date</th>
                                <th>Books Issued</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['name']; ?></td>
                                    <td><?php echo $user['email']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <?php if ($user['issued_books'] > 0): ?>
                                            <span class="badge bg-primary"><?php echo $user['issued_books']; ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="issue_book.php?user_id=<?php echo $user['id']; ?>" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Issue Book">
                                            <i class="fas fa-book"></i>
                                        </a>
                                        <a href="manage_users.php?delete=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirmDelete(event, 'Are you sure you want to delete this user?')" data-bs-toggle="tooltip" title="Delete User">
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

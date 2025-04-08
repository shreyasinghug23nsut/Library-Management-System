<?php
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Redirect if user is already logged in
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('admin/dashboard.php');
    } else {
        redirect('user/dashboard.php');
    }
}

$error = '';
$email = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    
    // Validate form data
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password';
    } else {
        // Attempt to login
        if (loginUser($conn, $email, $password)) {
            // Redirect to appropriate dashboard
            if (isAdmin()) {
                redirect('admin/dashboard.php');
            } else {
                redirect('user/dashboard.php');
            }
        } else {
            $error = 'Invalid email or password';
        }
    }
}

// Include header
include 'includes/header.php';
?>

<div class="container">
    <div class="auth-form">
        <h2><i class="fas fa-sign-in-alt me-2"></i>Login</h2>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form id="loginForm" method="POST" action="login.php">
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo $email; ?>" required>
            </div>
            
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">Login</button>
            </div>
        </form>
        
        <div class="mt-3 text-center">
            <p>Don't have an account? <a href="register.php">Register here</a></p>
        </div>
    </div>
</div>

<?php
// Include footer
include 'includes/footer.php';
?>

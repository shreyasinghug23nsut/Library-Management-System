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
$success = '';
$name = '';
$email = '';

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize_input($_POST['name']);
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirmPassword'];
    
    // Validate form data
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Please fill in all fields';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        // Attempt to register new user
        if (registerUser($conn, $name, $email, $password)) {
            $success = 'Registration successful! You can now login.';
            $name = '';
            $email = '';
        } else {
            $error = 'Email address already exists';
        }
    }
}

// Include header
include 'includes/header.php';
?>

<div class="container">
    <div class="auth-form">
        <h2><i class="fas fa-user-plus me-2"></i>Register</h2>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form id="registerForm" method="POST" action="register.php">
            <div class="mb-3">
                <label for="name" class="form-label">Name</label>
                <input type="text" class="form-control" id="name" name="name" value="<?php echo $name; ?>" required>
            </div>
            
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo $email; ?>" required>
            </div>
            
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
                <div class="form-text">Password must be at least 6 characters long.</div>
            </div>
            
            <div class="mb-3">
                <label for="confirmPassword" class="form-label">Confirm Password</label>
                <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" required>
            </div>
            
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">Register</button>
            </div>
        </form>
        
        <div class="mt-3 text-center">
            <p>Already have an account? <a href="login.php">Login here</a></p>
        </div>
    </div>
</div>

<?php
// Include footer
include 'includes/footer.php';
?>

<?php
require_once dirname(__FILE__) . '/functions.php';
require_once dirname(__FILE__) . '/auth.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Management System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo isset($is_admin) ? '../css/style.css' : 'css/style.css'; ?>">
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
            <div class="container">
                <a class="navbar-brand" href="<?php echo isset($is_admin) ? '../index.php' : 'index.php'; ?>">
                    <i class="fas fa-book-open me-2"></i>Library Management System
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <?php if (isLoggedIn()): ?>
                            <?php if (isAdmin()): ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="<?php echo isset($is_admin) ? 'dashboard.php' : 'admin/dashboard.php'; ?>">
                                        <i class="fas fa-tachometer-alt me-1"></i>Admin Dashboard
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="<?php echo isset($is_admin) ? 'manage_books.php' : 'admin/manage_books.php'; ?>">
                                        <i class="fas fa-books me-1"></i>Manage Books
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="<?php echo isset($is_admin) ? 'manage_users.php' : 'admin/manage_users.php'; ?>">
                                        <i class="fas fa-users me-1"></i>Manage Users
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="<?php echo isset($is_admin) ? 'issue_book.php' : 'admin/issue_book.php'; ?>">
                                        <i class="fas fa-book me-1"></i>Issue Book
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="<?php echo isset($is_admin) ? 'return_book.php' : 'admin/return_book.php'; ?>">
                                        <i class="fas fa-undo me-1"></i>Return Book
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="<?php echo isset($is_user) ? 'dashboard.php' : 'user/dashboard.php'; ?>">
                                        <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="<?php echo isset($is_user) ? 'books.php' : 'user/books.php'; ?>">
                                        <i class="fas fa-book me-1"></i>Books
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="<?php echo isset($is_user) ? 'borrowed_books.php' : 'user/borrowed_books.php'; ?>">
                                        <i class="fas fa-book-reader me-1"></i>My Borrowed Books
                                    </a>
                                </li>
                            <?php endif; ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-user me-1"></i><?php echo $_SESSION['user_name']; ?>
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                                    <li>
                                        <a class="dropdown-item" href="<?php echo isset($is_admin) || isset($is_user) ? '../logout.php' : 'logout.php'; ?>">
                                            <i class="fas fa-sign-out-alt me-1"></i>Logout
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo isset($is_admin) || isset($is_user) ? '../index.php' : 'index.php'; ?>">
                                    <i class="fas fa-home me-1"></i>Home
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo isset($is_admin) || isset($is_user) ? '../login.php' : 'login.php'; ?>">
                                    <i class="fas fa-sign-in-alt me-1"></i>Login
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo isset($is_admin) || isset($is_user) ? '../register.php' : 'register.php'; ?>">
                                    <i class="fas fa-user-plus me-1"></i>Register
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
    <main class="container my-4">

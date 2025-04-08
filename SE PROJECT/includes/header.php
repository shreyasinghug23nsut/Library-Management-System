<?php
session_start();
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Management System</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <header class="header">
        <nav class="nav-menu">
            <div class="logo">
                <h1>Library MS</h1>
            </div>
            <div class="menu-items">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <?php if($_SESSION['user_type'] === 'admin'): ?>
                        <a href="/admin/dashboard.php">Dashboard</a>
                        <a href="/admin/manage-books.php">Manage Books</a>
                        <a href="/admin/manage-users.php">Manage Users</a>
                    <?php else: ?>
                        <a href="/user/dashboard.php">Dashboard</a>
                    <?php endif; ?>
                    <a href="/logout.php">Logout</a>
                <?php else: ?>
                    <a href="/login.php">Login</a>
                    <a href="/register.php">Register</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>
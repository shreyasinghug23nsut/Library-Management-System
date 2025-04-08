<?php
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Log out the user
logoutUser();

// Redirect to home page
redirect('index.php');
?>

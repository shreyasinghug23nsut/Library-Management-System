    </main>
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>Library Management System</h5>
                    <p>A comprehensive solution for managing library resources, book issues, and returns.</p>
                </div>
                <div class="col-md-3">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="<?php echo isset($is_admin) || isset($is_user) ? '../index.php' : 'index.php'; ?>" class="text-white">Home</a></li>
                        <li><a href="<?php echo isset($is_admin) || isset($is_user) ? '../login.php' : 'login.php'; ?>" class="text-white">Login</a></li>
                        <li><a href="<?php echo isset($is_admin) || isset($is_user) ? '../register.php' : 'register.php'; ?>" class="text-white">Register</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h5>Contact</h5>
                    <address>
                        <p><i class="fas fa-map-marker-alt me-2"></i>123 Library St, Book City</p>
                        <p><i class="fas fa-phone me-2"></i>(123) 456-7890</p>
                        <p><i class="fas fa-envelope me-2"></i>info@librarysystem.com</p>
                    </address>
                </div>
            </div>
            <hr>
            <div class="text-center">
                <p>&copy; <?php echo date('Y'); ?> Library Management System. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="<?php echo isset($is_admin) || isset($is_user) ? '../js/main.js' : 'js/main.js'; ?>"></script>
</body>
</html>

// Main JavaScript file for Library Management System

// Document Ready Function
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Search functionality
    initializeSearch();
    
    // Book availability filter
    initializeAvailabilityFilter();
    
    // Form validations
    initializeFormValidations();
});

// Search functionality
function initializeSearch() {
    const searchInput = document.getElementById('searchInput');
    if (!searchInput) return;
    
    searchInput.addEventListener('keyup', function() {
        const searchValue = this.value.toLowerCase();
        const bookCards = document.querySelectorAll('.book-card');
        
        bookCards.forEach(function(card) {
            const title = card.querySelector('.card-title').textContent.toLowerCase();
            const author = card.querySelector('.book-author').textContent.toLowerCase();
            const isbn = card.querySelector('.book-isbn').textContent.toLowerCase();
            
            if (title.includes(searchValue) || author.includes(searchValue) || isbn.includes(searchValue)) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    });
}

// Book availability filter
function initializeAvailabilityFilter() {
    const availabilityFilter = document.getElementById('availabilityFilter');
    if (!availabilityFilter) return;
    
    availabilityFilter.addEventListener('change', function() {
        const filterValue = this.value;
        const bookCards = document.querySelectorAll('.book-card');
        
        bookCards.forEach(function(card) {
            if (filterValue === 'all') {
                card.style.display = '';
                return;
            }
            
            const isAvailable = card.querySelector('.badge-available') !== null;
            
            if ((filterValue === 'available' && isAvailable) || 
                (filterValue === 'unavailable' && !isAvailable)) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    });
}

// Form validations
function initializeFormValidations() {
    // Login form validation
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function(event) {
            if (!validateLoginForm()) {
                event.preventDefault();
            }
        });
    }
    
    // Registration form validation
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', function(event) {
            if (!validateRegisterForm()) {
                event.preventDefault();
            }
        });
    }
    
    // Book form validation
    const bookForm = document.getElementById('bookForm');
    if (bookForm) {
        bookForm.addEventListener('submit', function(event) {
            if (!validateBookForm()) {
                event.preventDefault();
            }
        });
    }
    
    // Issue book form validation
    const issueForm = document.getElementById('issueBookForm');
    if (issueForm) {
        issueForm.addEventListener('submit', function(event) {
            if (!validateIssueBookForm()) {
                event.preventDefault();
            }
        });
    }
}

// Validate login form
function validateLoginForm() {
    const email = document.getElementById('email');
    const password = document.getElementById('password');
    let isValid = true;
    
    // Reset error messages
    clearErrorMessages();
    
    // Validate email
    if (!email.value.trim()) {
        displayError(email, 'Email is required');
        isValid = false;
    } else if (!isValidEmail(email.value)) {
        displayError(email, 'Enter a valid email address');
        isValid = false;
    }
    
    // Validate password
    if (!password.value.trim()) {
        displayError(password, 'Password is required');
        isValid = false;
    }
    
    return isValid;
}

// Validate registration form
function validateRegisterForm() {
    const name = document.getElementById('name');
    const email = document.getElementById('email');
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirmPassword');
    let isValid = true;
    
    // Reset error messages
    clearErrorMessages();
    
    // Validate name
    if (!name.value.trim()) {
        displayError(name, 'Name is required');
        isValid = false;
    }
    
    // Validate email
    if (!email.value.trim()) {
        displayError(email, 'Email is required');
        isValid = false;
    } else if (!isValidEmail(email.value)) {
        displayError(email, 'Enter a valid email address');
        isValid = false;
    }
    
    // Validate password
    if (!password.value.trim()) {
        displayError(password, 'Password is required');
        isValid = false;
    } else if (password.value.length < 6) {
        displayError(password, 'Password must be at least 6 characters');
        isValid = false;
    }
    
    // Validate confirm password
    if (!confirmPassword.value.trim()) {
        displayError(confirmPassword, 'Confirm your password');
        isValid = false;
    } else if (password.value !== confirmPassword.value) {
        displayError(confirmPassword, 'Passwords do not match');
        isValid = false;
    }
    
    return isValid;
}

// Validate book form
function validateBookForm() {
    const title = document.getElementById('title');
    const author = document.getElementById('author');
    const isbn = document.getElementById('isbn');
    const category = document.getElementById('category');
    const quantity = document.getElementById('quantity');
    let isValid = true;
    
    // Reset error messages
    clearErrorMessages();
    
    // Validate title
    if (!title.value.trim()) {
        displayError(title, 'Title is required');
        isValid = false;
    }
    
    // Validate author
    if (!author.value.trim()) {
        displayError(author, 'Author is required');
        isValid = false;
    }
    
    // Validate ISBN
    if (!isbn.value.trim()) {
        displayError(isbn, 'ISBN is required');
        isValid = false;
    }
    
    // Validate category
    if (!category.value.trim()) {
        displayError(category, 'Category is required');
        isValid = false;
    }
    
    // Validate quantity
    if (!quantity.value.trim()) {
        displayError(quantity, 'Quantity is required');
        isValid = false;
    } else if (isNaN(quantity.value) || parseInt(quantity.value) <= 0) {
        displayError(quantity, 'Quantity must be a positive number');
        isValid = false;
    }
    
    return isValid;
}

// Validate issue book form
function validateIssueBookForm() {
    const bookId = document.getElementById('bookId');
    const userId = document.getElementById('userId');
    const issueDate = document.getElementById('issueDate');
    const returnDate = document.getElementById('returnDate');
    let isValid = true;
    
    // Reset error messages
    clearErrorMessages();
    
    // Validate book
    if (!bookId.value.trim()) {
        displayError(bookId, 'Please select a book');
        isValid = false;
    }
    
    // Validate user
    if (!userId.value.trim()) {
        displayError(userId, 'Please select a user');
        isValid = false;
    }
    
    // Validate issue date
    if (!issueDate.value.trim()) {
        displayError(issueDate, 'Issue date is required');
        isValid = false;
    }
    
    // Validate return date
    if (!returnDate.value.trim()) {
        displayError(returnDate, 'Return date is required');
        isValid = false;
    } else if (new Date(returnDate.value) <= new Date(issueDate.value)) {
        displayError(returnDate, 'Return date must be after issue date');
        isValid = false;
    }
    
    return isValid;
}

// Helper function to validate email format
function isValidEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(String(email).toLowerCase());
}

// Helper function to display error message
function displayError(input, message) {
    const formGroup = input.closest('.mb-3');
    const errorDiv = document.createElement('div');
    
    errorDiv.className = 'invalid-feedback d-block';
    errorDiv.textContent = message;
    
    input.classList.add('is-invalid');
    formGroup.appendChild(errorDiv);
}

// Helper function to clear all error messages
function clearErrorMessages() {
    document.querySelectorAll('.invalid-feedback').forEach(function(element) {
        element.remove();
    });
    
    document.querySelectorAll('.is-invalid').forEach(function(element) {
        element.classList.remove('is-invalid');
    });
}

// Calculate fine based on return date
function calculateFine() {
    const returnDateInput = document.getElementById('returnDate');
    const actualReturnDateInput = document.getElementById('actualReturnDate');
    const fineDisplay = document.getElementById('fineAmount');
    
    if (!returnDateInput || !actualReturnDateInput || !fineDisplay) return;
    
    actualReturnDateInput.addEventListener('change', function() {
        const returnDate = new Date(returnDateInput.value);
        const actualReturnDate = new Date(this.value);
        
        if (actualReturnDate > returnDate) {
            const diffTime = Math.abs(actualReturnDate - returnDate);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            const fine = diffDays * 1.00; // $1 per day
            
            fineDisplay.textContent = '$' + fine.toFixed(2);
            document.getElementById('fine').value = fine.toFixed(2);
        } else {
            fineDisplay.textContent = '$0.00';
            document.getElementById('fine').value = '0.00';
        }
    });
}

// Function to confirm delete
function confirmDelete(event, message) {
    if (!confirm(message || 'Are you sure you want to delete this item?')) {
        event.preventDefault();
        return false;
    }
    return true;
}

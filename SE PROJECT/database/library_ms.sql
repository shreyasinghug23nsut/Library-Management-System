CREATE DATABASE IF NOT EXISTS library_ms;
USE library_ms;

CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    user_type ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE books (
    id INT PRIMARY KEY AUTO_INCREMENT,
    isbn VARCHAR(13) UNIQUE NOT NULL,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(100) NOT NULL,
    category VARCHAR(50) NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    available INT NOT NULL DEFAULT 1,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE book_issues (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    book_id INT,
    issue_date DATE NOT NULL,
    return_date DATE NOT NULL,
    actual_return_date DATE,
    fine DECIMAL(10,2) DEFAULT 0.00,
    status ENUM('issued', 'returned', 'overdue') DEFAULT 'issued',
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (book_id) REFERENCES books(id)
);

CREATE TABLE reservations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    book_id INT,
    reservation_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'approved', 'cancelled') DEFAULT 'pending',
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (book_id) REFERENCES books(id)
);

-- Insert sample data
INSERT INTO users (username, password, email, full_name, user_type) VALUES
('admin', '$2y$10$YourHashedPasswordHere', 'admin@library.com', 'System Admin', 'admin'),
('john_doe', '$2y$10$YourHashedPasswordHere', 'john@example.com', 'John Doe', 'user'),
('jane_smith', '$2y$10$YourHashedPasswordHere', 'jane@example.com', 'Jane Smith', 'user');

INSERT INTO books (isbn, title, author, category, quantity, available) VALUES
('9780132350884', 'Clean Code', 'Robert C. Martin', 'Programming', 3, 3),
('9780134685991', 'Effective Java', 'Joshua Bloch', 'Programming', 2, 2),
('9780747532743', 'Harry Potter', 'J.K. Rowling', 'Fiction', 5, 4),
('9780061120084', 'To Kill a Mockingbird', 'Harper Lee', 'Fiction', 3, 3),
('9780307474278', '1984', 'George Orwell', 'Fiction', 4, 4);
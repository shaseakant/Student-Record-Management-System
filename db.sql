-- Create database
CREATE DATABASE IF NOT EXISTS royalorbit_db;
USE royalorbit_db;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'manager', 'staff') NOT NULL,
    salary DECIMAL(10,2),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);


-- Menu items
CREATE TABLE menu_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    category VARCHAR(50),
    price DECIMAL(10,2) NOT NULL,
    image_url VARCHAR(255) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);


-- Stocks
CREATE TABLE stocks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(100) NOT NULL,
    quantity INT NOT NULL,
    unit VARCHAR(50), 
    status VARCHAR(50),
    last_updated DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_by INT,
    FOREIGN KEY (updated_by) REFERENCES users(id)
);


-- Orders
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    placed_by INT,
    total_amount DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (placed_by) REFERENCES users(id)
);

-- Order items
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    menu_item_id INT,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id)
);

-- Attendance
CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    date DATE NOT NULL,
    status ENUM('Present', 'Absent', 'Leave') NOT NULL,
    marked_by INT,
    marked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (marked_by) REFERENCES users(id)
);

-- Salary payments
CREATE TABLE salary_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    amount DECIMAL(10,2) NOT NULL,
    payment_date DATE NOT NULL,
    paid_by INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (paid_by) REFERENCES users(id)
);

-- Activity logs (optional)
CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(255),
    target_type VARCHAR(50),
    target_id INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
USE royalorbit_db;

-- Insert users
INSERT INTO users (name, username, password, role, salary) VALUES
('Admin User', 'admin', ('admin123'), 'admin', 5000.00),
('John Smith', 'john', ('john123'), 'manager', 3500.00),
('Alice Johnson', 'alice', ('alice123'), 'staff', 1500.00);

-- Insert menu items
INSERT INTO menu_items (name, category, price, image_url) VALUES
('Margherita Pizza', 'Pizza', 8.99, 'images/pizza_margherita.jpg'),
('Caesar Salad', 'Salad', 5.49, 'images/caesar_salad.jpg'),
('Grilled Salmon', 'Main Course', 15.99, 'images/grilled_salmon.jpg');

-- Insert stocks
INSERT INTO stocks (item_name, quantity, unit, status, last_updated, updated_by) VALUES
('Tomatoes', 100, 'kg', 'Available', NOW(), 1),
('Mozzarella Cheese', 50, 'kg', 'Low', NOW(), 2),
('Salmon Fillet', 20, 'pieces', 'Low', NOW(), 1);


-- Insert attendance (today)
INSERT INTO attendance (user_id, date, status, marked_by) VALUES
(2, CURDATE(), 'Present', 1),
(3, CURDATE(), 'Absent', 1);

-- Insert salary payments
INSERT INTO salary_payments (user_id, amount, payment_date, paid_by) VALUES
(2, 3500.00, CURDATE(), 1),
(3, 1500.00, CURDATE(), 1);

-- Insert activity logs (optional)
INSERT INTO activity_logs (user_id, action, target_type, target_id) VALUES
(2, 'Updated price of Margherita Pizza', 'menu_items', 1),
(3, 'Marked attendance for self', 'attendance', 1);


ALTER TABLE stocks ADD COLUMN amount DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER quantity;
ALTER TABLE menu_items ADD image VARCHAR(255) AFTER price;
ALTER TABLE orders ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP;

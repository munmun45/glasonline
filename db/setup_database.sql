-- Create database
CREATE DATABASE IF NOT EXISTS glas_online;
USE glas_online;

-- Categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Products table
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    stock_quantity INT NOT NULL DEFAULT 0,
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Orders table
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(100) NOT NULL,
    customer_email VARCHAR(100) NOT NULL,
    customer_phone VARCHAR(20),
    total_amount DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'processing', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Order items table
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    product_id INT,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Insert sample categories
INSERT INTO categories (name) VALUES 
('New Arrival'),
('Aquatic Plants'),
('Aquarium Decoration'),
('Fish Food'),
('Aquarium Filters');

-- Insert sample products
INSERT INTO products (category_id, name, description, price, stock_quantity, image_url) VALUES
(1, 'Red Guppy', 'Beautiful red guppy fish', 4.99, 50, 'images/red-guppy.jpg'),
(1, 'Neon Tetra', 'Schooling fish with bright colors', 3.99, 100, 'images/neon-tetra.jpg'),
(2, 'Java Fern', 'Easy to care aquatic plant', 7.99, 30, 'images/java-fern.jpg'),
(3, 'Sunken Ship Decoration', 'Resin shipwreck decoration', 24.99, 15, 'images/ship-decoration.jpg'),
(4, 'Tropical Fish Flakes', 'Premium fish food', 8.99, 200, 'images/fish-food.jpg'),
(5, 'Internal Aquarium Filter', 'Powerful 200L/h filter', 29.99, 25, 'images/aquarium-filter.jpg');

create database ellenfoodhouse;

use ellenfoodhouse;

CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    reservation_date DATE NOT NULL,
    reservation_time TIME NOT NULL,
    party_size INT NOT NULL,
    table_number INT,
    status ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'pending',
    special_requests TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id)
);

-- New tables for image upload system
CREATE TABLE banners (
    banner_id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    title VARCHAR(255) NOT NULL,
    active BOOLEAN DEFAULT 1,
    date_uploaded TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE menu (
    menu_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    image_path VARCHAR(255),
    is_best_seller BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Updated reservations table to match professor's requirements
CREATE TABLE reservation_system (
    reservation_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(255) NOT NULL,
    table_number INT NOT NULL,
    reservation_date DATE NOT NULL,
    reservation_time TIME NOT NULL,
    status VARCHAR(50) DEFAULT 'pending'
);

INSERT INTO customers (first_name, last_name, email, phone) VALUES
('John', 'Doe', 'john.doe@email.com', '123-456-7890'),
('Jane', 'Smith', 'jane.smith@email.com', '987-654-3210'),
('Bob', 'Johnson', 'bob.johnson@email.com', '555-123-4567');

INSERT INTO reservations (customer_id, reservation_date, reservation_time, party_size, table_number, special_requests) VALUES
(1, '2025-08-10', '19:00:00', 4, 5, 'Window table preferred'),
(2, '2025-08-11', '18:30:00', 2, 3, 'Vegetarian menu please'),
(3, '2025-08-12', '20:00:00', 6, 8, 'Birthday celebration');

-- Sample data for new tables
INSERT INTO banners (filename, title, active) VALUES
('banner1.jpg', 'Welcome to Ellen\'s Food House', 1),
('banner2.jpg', 'Special Weekend Menu', 1),
('banner3.jpg', 'Book Your Table Today', 0);

INSERT INTO menu (name, price, image_path, is_best_seller) VALUES
('Grilled Salmon', 24.99, 'salmon.jpg', 1),
('Beef Steak', 32.99, 'steak.jpg', 1),
('Pasta Carbonara', 18.99, 'pasta.jpg', 0),
('Caesar Salad', 12.99, 'salad.jpg', 1),
('Chocolate Cake', 8.99, 'cake.jpg', 0);

INSERT INTO reservation_system (customer_name, table_number, reservation_date, reservation_time, status) VALUES
('John Doe', 5, '2025-08-10', '19:00:00', 'confirmed'),
('Jane Smith', 3, '2025-08-11', '18:30:00', 'pending'),
('Bob Johnson', 8, '2025-08-12', '20:00:00', 'confirmed');
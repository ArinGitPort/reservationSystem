create database ellenfoodhouse;

use ellenfoodhouse;

CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    image_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    reservation_date DATE NOT NULL,
    reservation_time TIME NOT NULL,
    party_size INT NOT NULL,
    table_number INT,
    status ENUM('pending', 'confirmed', 'seated', 'completed', 'cancelled', 'no-show') DEFAULT 'pending',
    special_requests TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id)
);

CREATE TABLE banners (
    banner_id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    event_date DATE NULL,
    event_start_date DATE NULL,
    event_end_date DATE NULL,
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

INSERT INTO customers (first_name, last_name, email, phone) VALUES
('John', 'Doe', 'john.doe@email.com', '123-456-7890'),
('Jane', 'Smith', 'jane.smith@email.com', '987-654-3210'),
('Bob', 'Johnson', 'bob.johnson@email.com', '555-123-4567');

INSERT INTO reservations (customer_id, reservation_date, reservation_time, party_size, table_number, special_requests) VALUES
(1, '2025-08-10', '19:00:00', 4, 5, 'Window table preferred'),
(2, '2025-08-11', '18:30:00', 2, 3, 'Vegetarian menu please'),
(3, '2025-08-12', '20:00:00', 6, 8, 'Birthday celebration');

CREATE TABLE orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    order_status ENUM('pending', 'confirmed', 'preparing', 'ready', 'delivered', 'cancelled') DEFAULT 'pending',
    order_type ENUM('dine-in', 'takeout', 'delivery') DEFAULT 'dine-in',
    customer_name VARCHAR(100) NOT NULL,
    customer_phone VARCHAR(20) NOT NULL,
    customer_email VARCHAR(100),
    delivery_address TEXT NULL,
    special_instructions TEXT NULL,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
);

CREATE TABLE order_items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    menu_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (menu_id) REFERENCES menu(menu_id) ON DELETE CASCADE
);

-- Add some sample menu items for testing
INSERT INTO menu (name, price, image_path, is_best_seller) VALUES
('Adobo Rice Bowl', 150.00, '1.jpg', 1),
('Sisig Platter', 180.00, '2.jpg', 1),
('Lechon Kawali', 220.00, '3.jpg', 0),
('Chicken Inasal', 160.00, '4.jpg', 1),
('Beef Caldereta', 200.00, '5.jpg', 0),
('Fish Fillet', 190.00, '6.jpg', 0),
('Pork BBQ Skewers', 120.00, '7.jpg', 0),
('Vegetable Lumpia', 100.00, '8.jpg', 0);

ALTER TABLE banners ADD COLUMN event_date DATE NULL AFTER description;

-- Update reservation status enum to include all statuses used in the system
ALTER TABLE reservations MODIFY COLUMN status ENUM('pending', 'confirmed', 'seated', 'completed', 'cancelled', 'no-show') DEFAULT 'pending';

-- Add updated_at column to reservations table
ALTER TABLE reservations ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
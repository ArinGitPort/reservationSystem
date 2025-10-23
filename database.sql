create database ellenfoodhouse;

use ellenfoodhouse;

-- Enhanced Customers table with proper constraints and indexes
CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    image_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Indexes for performance
    INDEX idx_customer_name (first_name, last_name),
    INDEX idx_customer_email (email),
    INDEX idx_customer_phone (phone),
    INDEX idx_customer_created (created_at)
);

-- Enhanced Reservations table with proper foreign keys and indexes
CREATE TABLE reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    reservation_date DATE NOT NULL,
    reservation_time TIME NOT NULL,
    party_size INT NOT NULL CHECK (party_size > 0 AND party_size <= 20),
    table_number INT,
    status ENUM('pending', 'confirmed', 'seated', 'completed', 'cancelled', 'no-show') DEFAULT 'pending',
    special_requests TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign key constraints with proper cascading
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE ON UPDATE CASCADE,
    
    -- Indexes for performance
    INDEX idx_reservation_customer (customer_id),
    INDEX idx_reservation_date (reservation_date),
    INDEX idx_reservation_datetime (reservation_date, reservation_time),
    INDEX idx_reservation_status (status),
    INDEX idx_reservation_table (table_number),
    INDEX idx_reservation_created (created_at),
    
    -- Unique constraint to prevent double bookings
    UNIQUE KEY unique_table_datetime (table_number, reservation_date, reservation_time)
);

-- Enhanced Banners table with proper constraints and indexes
CREATE TABLE banners (
    banner_id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    event_date DATE NULL,
    event_start_date DATE NULL,
    event_end_date DATE NULL,
    active BOOLEAN DEFAULT 1,
    date_uploaded TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Constraints
    CHECK (event_end_date IS NULL OR event_start_date IS NULL OR event_end_date >= event_start_date),
    
    -- Indexes for performance
    INDEX idx_banner_active (active),
    INDEX idx_banner_dates (event_start_date, event_end_date),
    INDEX idx_banner_uploaded (date_uploaded)
);

-- Enhanced Menu table with proper constraints and indexes
CREATE TABLE menu (
    menu_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    price DECIMAL(10,2) NOT NULL CHECK (price > 0),
    image_path VARCHAR(255),
    is_best_seller BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Indexes for performance
    INDEX idx_menu_name (name),
    INDEX idx_menu_price (price),
    INDEX idx_menu_bestseller (is_best_seller),
    INDEX idx_menu_created (created_at)
);

-- Enhanced Orders table with proper constraints and indexes
CREATE TABLE orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL CHECK (total_amount >= 0),
    order_status ENUM('pending', 'confirmed', 'preparing', 'ready', 'delivered', 'cancelled') DEFAULT 'pending',
    order_type ENUM('dine-in', 'takeout', 'delivery') DEFAULT 'dine-in',
    customer_name VARCHAR(100) NOT NULL,
    customer_phone VARCHAR(20) NOT NULL,
    customer_email VARCHAR(100),
    delivery_address TEXT NULL,
    special_instructions TEXT NULL,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign key constraints
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE ON UPDATE CASCADE,
    
    -- Indexes for performance
    INDEX idx_order_customer (customer_id),
    INDEX idx_order_status (order_status),
    INDEX idx_order_type (order_type),
    INDEX idx_order_date (order_date),
    INDEX idx_order_customer_info (customer_name, customer_phone)
);

-- Enhanced Order Items table with proper constraints and indexes
CREATE TABLE order_items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    menu_id INT NOT NULL,
    quantity INT NOT NULL CHECK (quantity > 0),
    price DECIMAL(10,2) NOT NULL CHECK (price > 0),
    subtotal DECIMAL(10,2) NOT NULL CHECK (subtotal >= 0),
    
    -- Foreign key constraints
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (menu_id) REFERENCES menu(menu_id) ON DELETE CASCADE ON UPDATE CASCADE,
    
    -- Indexes for performance
    INDEX idx_orderitem_order (order_id),
    INDEX idx_orderitem_menu (menu_id),
    
    -- Unique constraint to prevent duplicate items in same order
    UNIQUE KEY unique_order_menu (order_id, menu_id)
);

-- Insert sample data
INSERT INTO customers (first_name, last_name, email, phone) VALUES
('John', 'Doe', 'john.doe@email.com', '123-456-7890'),
('Jane', 'Smith', 'jane.smith@email.com', '987-654-3210'),
('Bob', 'Johnson', 'bob.johnson@email.com', '555-123-4567'),
('Alice', 'Wilson', 'alice.wilson@email.com', '444-555-6666'),
('Charlie', 'Brown', 'charlie.brown@email.com', '777-888-9999');

INSERT INTO reservations (customer_id, reservation_date, reservation_time, party_size, table_number, special_requests) VALUES
(1, '2025-10-25', '19:00:00', 4, 5, 'Window table preferred'),
(2, '2025-10-26', '18:30:00', 2, 3, 'Vegetarian menu please'),
(3, '2025-10-27', '20:00:00', 6, 8, 'Birthday celebration'),
(4, '2025-10-28', '19:30:00', 3, 6, 'Anniversary dinner'),
(5, '2025-10-29', '18:00:00', 2, 2, 'Quiet corner table');

-- Add sample menu items for testing
INSERT INTO menu (name, price, image_path, is_best_seller) VALUES
('Adobo Rice Bowl', 150.00, '1.jpg', 1),
('Sisig Platter', 180.00, '2.jpg', 1),
('Lechon Kawali', 220.00, '3.jpg', 0),
('Chicken Inasal', 160.00, '4.jpg', 1),
('Beef Caldereta', 200.00, '5.jpg', 0),
('Fish Fillet', 190.00, '6.jpg', 0),
('Pork BBQ Skewers', 120.00, '7.jpg', 0),
('Vegetable Lumpia', 100.00, '8.jpg', 0),
('Pancit Canton', 140.00, '9.jpg', 0),
('Halo-Halo Dessert', 80.00, '10.jpg', 1);

-- Add sample banners
INSERT INTO banners (filename, title, description, event_start_date, event_end_date, active) VALUES
('christmas_special.jpg', 'Christmas Special Menu', 'Enjoy our festive holiday dishes with special pricing', '2025-12-01', '2025-12-31', 1),
('valentine_promo.jpg', 'Valentine\'s Day Romance Package', 'Special couples dinner with complimentary dessert', '2026-02-10', '2026-02-16', 0),
('summer_festival.jpg', 'Summer Food Festival', 'Fresh seafood and grilled specialties all summer long', '2026-05-01', '2026-08-31', 0);

-- Add sample orders
INSERT INTO orders (customer_id, total_amount, order_status, order_type, customer_name, customer_phone, customer_email) VALUES
(1, 330.00, 'delivered', 'dine-in', 'John Doe', '123-456-7890', 'john.doe@email.com'),
(2, 260.00, 'preparing', 'takeout', 'Jane Smith', '987-654-3210', 'jane.smith@email.com'),
(3, 440.00, 'confirmed', 'delivery', 'Bob Johnson', '555-123-4567', 'bob.johnson@email.com');

-- Add sample order items
INSERT INTO order_items (order_id, menu_id, quantity, price, subtotal) VALUES
(1, 1, 2, 150.00, 300.00),
(1, 8, 3, 10.00, 30.00),
(2, 2, 1, 180.00, 180.00),
(2, 10, 1, 80.00, 80.00),
(3, 3, 2, 220.00, 440.00);

-- Add triggers for data integrity and automation

-- Trigger to update order total when order items change
DELIMITER //
CREATE TRIGGER update_order_total_on_insert 
AFTER INSERT ON order_items
FOR EACH ROW
BEGIN
    UPDATE orders 
    SET total_amount = (
        SELECT SUM(subtotal) 
        FROM order_items 
        WHERE order_id = NEW.order_id
    )
    WHERE order_id = NEW.order_id;
END;//

CREATE TRIGGER update_order_total_on_update 
AFTER UPDATE ON order_items
FOR EACH ROW
BEGIN
    UPDATE orders 
    SET total_amount = (
        SELECT SUM(subtotal) 
        FROM order_items 
        WHERE order_id = NEW.order_id
    )
    WHERE order_id = NEW.order_id;
END;//

CREATE TRIGGER update_order_total_on_delete 
AFTER DELETE ON order_items
FOR EACH ROW
BEGIN
    UPDATE orders 
    SET total_amount = (
        SELECT IFNULL(SUM(subtotal), 0) 
        FROM order_items 
        WHERE order_id = OLD.order_id
    )
    WHERE order_id = OLD.order_id;
END;//

-- Trigger to ensure subtotal matches price * quantity
CREATE TRIGGER validate_order_item_subtotal
BEFORE INSERT ON order_items
FOR EACH ROW
BEGIN
    SET NEW.subtotal = NEW.price * NEW.quantity;
END;//

CREATE TRIGGER validate_order_item_subtotal_update
BEFORE UPDATE ON order_items
FOR EACH ROW
BEGIN
    SET NEW.subtotal = NEW.price * NEW.quantity;
END;//

DELIMITER ;

-- Create views for common queries
CREATE VIEW active_reservations AS
SELECT 
    r.*,
    CONCAT(c.first_name, ' ', c.last_name) as customer_name,
    c.email,
    c.phone
FROM reservations r
JOIN customers c ON r.customer_id = c.id
WHERE r.status IN ('pending', 'confirmed', 'seated')
ORDER BY r.reservation_date, r.reservation_time;

CREATE VIEW today_reservations AS
SELECT 
    r.*,
    CONCAT(c.first_name, ' ', c.last_name) as customer_name,
    c.email,
    c.phone
FROM reservations r
JOIN customers c ON r.customer_id = c.id
WHERE DATE(r.reservation_date) = CURDATE()
ORDER BY r.reservation_time;

CREATE VIEW bestseller_menu AS
SELECT 
    m.*,
    IFNULL(oi.total_orders, 0) as order_count
FROM menu m
LEFT JOIN (
    SELECT menu_id, SUM(quantity) as total_orders
    FROM order_items 
    GROUP BY menu_id
) oi ON m.menu_id = oi.menu_id
WHERE m.is_best_seller = 1
ORDER BY oi.total_orders DESC, m.name;

-- Performance analysis queries (commented for reference)
-- EXPLAIN SELECT * FROM reservations WHERE reservation_date = '2025-10-25' AND status = 'confirmed';
-- EXPLAIN SELECT * FROM orders o JOIN customers c ON o.customer_id = c.id WHERE o.order_date >= '2025-10-01';
-- EXPLAIN SELECT * FROM order_items oi JOIN menu m ON oi.menu_id = m.menu_id WHERE oi.order_id = 1;
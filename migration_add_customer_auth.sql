USE ellenfoodhouse;

ALTER TABLE customers ADD COLUMN password VARCHAR(255) AFTER email;

ALTER TABLE customers ADD COLUMN is_verified TINYINT(1) DEFAULT 0 AFTER phone;

ALTER TABLE customers ADD COLUMN verification_token VARCHAR(255) AFTER is_verified;

ALTER TABLE customers ADD COLUMN last_login DATETIME AFTER verification_token;

ALTER TABLE customers ADD INDEX idx_customer_verification (verification_token);

CREATE TABLE customer_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    session_token VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    last_activity DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX idx_session_token (session_token),
    INDEX idx_customer_id (customer_id),
    INDEX idx_last_activity (last_activity)
);

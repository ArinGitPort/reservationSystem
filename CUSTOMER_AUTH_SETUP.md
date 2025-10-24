# Customer Authentication Setup Guide

## 🚀 Quick Setup

You have **TWO** separate authentication systems:

1. **Admin/Staff Login** → `pages/auth/login.php` and `pages/auth/register.php`
2. **Customer Login** → `pages/customer/login.php` and `pages/customer/register.php`

## ⚠️ IMPORTANT: Database Update Required

Before customers can register, you MUST update your database:

### Option 1: For Fresh Installation
Run the entire `database.sql` file in MySQL Workbench

### Option 2: For Existing Database
Run these SQL commands in MySQL Workbench:

```sql
-- Add authentication fields to customers table
ALTER TABLE customers 
ADD COLUMN password VARCHAR(255) AFTER email,
ADD COLUMN is_verified TINYINT(1) DEFAULT 0 AFTER password,
ADD COLUMN verification_token VARCHAR(255) AFTER is_verified,
ADD COLUMN last_login DATETIME AFTER verification_token,
ADD INDEX idx_customer_verification (verification_token);

-- Create customer sessions table
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
```

## 📍 Access URLs

### Customer Pages:
- **Registration:** `http://yoursite.com/pages/customer/register.php`
- **Login:** `http://yoursite.com/pages/customer/login.php`

### Admin/Staff Pages:
- **Registration:** `http://yoursite.com/pages/auth/register.php`
- **Login:** `http://yoursite.com/pages/auth/login.php`
- **Default Login:** username: `admin`, password: `admin123`

## 🧪 Testing Customer Registration

1. Make sure you've run the SQL commands above
2. Go to `http://localhost/reservationSystem-2/pages/customer/register.php`
3. Fill in the form:
   - First Name: Test
   - Last Name: Customer
   - Email: test@customer.com
   - Phone: 09123456789
   - Password: password123
   - Confirm Password: password123
4. Click "Create Account"
5. You should be redirected to login page with success message
6. Login with the email and password you created

## 🔧 Troubleshooting

### "Column 'password' doesn't exist" error
→ You need to run the ALTER TABLE command to add the password column to customers table

### "Table 'customer_sessions' doesn't exist" error
→ You need to run the CREATE TABLE command for customer_sessions

### Redirect not working
→ Check that your web server is running and paths are correct

### Cannot register - no error message
→ Check PHP error logs, likely a database connection issue

## 📝 Notes

- Customer passwords are hashed with bcrypt (same as admin)
- Customers can have the same email as in the old customer table (will update their record with password)
- Session tokens are stored in database for security
- Remember-me feature keeps users logged in for 30 days
- All authentication is handled by `CustomerAuthController.php`

---

**Need help?** Check the files:
- Controller: `controllers/CustomerAuthController.php`
- Login Page: `pages/customer/login.php`
- Register Page: `pages/customer/register.php`
- Database Schema: `database.sql` (lines 3-15 and 274-285)

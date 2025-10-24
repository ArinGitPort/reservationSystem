# Authentication System Setup

## Database Tables Added

The authentication system has been implemented with the following database tables:

### 1. admin_users
Stores admin and staff user accounts.

**Columns:**
- `id` - Primary key
- `username` - Unique username (VARCHAR 50)
- `password` - Hashed password (VARCHAR 255)
- `email` - User email (VARCHAR 100)
- `full_name` - Full name (VARCHAR 100)
- `role` - ENUM('admin', 'staff')
- `is_active` - TINYINT (1 = active, 0 = inactive)
- `last_login` - DATETIME
- `created_at` - TIMESTAMP
- `updated_at` - TIMESTAMP

### 2. login_sessions
Tracks active login sessions for security.

**Columns:**
- `id` - Primary key
- `user_id` - Foreign key to admin_users
- `session_token` - Unique session identifier
- `ip_address` - User's IP address
- `user_agent` - Browser user agent
- `last_activity` - DATETIME
- `created_at` - TIMESTAMP

## Default Admin Account

A default admin account has been created in the database:

**Username:** `admin`  
**Password:** `admin123`  
**Email:** `admin@ellensfoodhouse.com`  
**Role:** `admin`

⚠️ **IMPORTANT:** Change this password after first login!

## Files Created/Modified

### Created Files:
- `controllers/AuthController.php` - Handles all authentication logic
- `pages/auth/login.php` - Modern login page with gradient design
- `pages/auth/register.php` - Registration page with password strength indicator
- `AUTH_SETUP.md` - This file

### Modified Files:
- `database.sql` - Added authentication tables
- `includes/sidebar.php` - Added user display and logout button
- `pages/admin/account_management.php` - Protected with authentication
- `pages/admin/menu_management.php` - Protected with authentication
- `pages/admin/reservation_management.php` - Protected with authentication
- `pages/admin/order_management.php` - Protected with authentication
- `pages/admin/event_display_management.php` - Protected with authentication

## Features Implemented

### Security Features:
✅ **Password Hashing** - Using PHP's `password_hash()` with bcrypt  
✅ **Session Management** - Secure session tokens with HttpOnly cookies  
✅ **Remember Me** - Optional 30-day login persistence  
✅ **Session Tracking** - Database logging of sessions with IP and user agent  
✅ **Route Protection** - Middleware to protect admin pages  
✅ **Active Account Check** - Accounts can be enabled/disabled  

### User Features:
✅ **Login Page** - Beautiful gradient UI with form validation  
✅ **Registration Page** - Password strength indicator and real-time validation  
✅ **User Display** - Shows logged-in user info in sidebar  
✅ **Logout Button** - Easy logout from admin panel  
✅ **Role Management** - Admin and Staff roles supported  
✅ **Last Login Tracking** - Records last login timestamp  

## How to Test

1. **Import the Updated Database:**
   ```sql
   -- Run the database.sql file to create the new tables
   -- The default admin account will be created automatically
   ```

2. **Access the Login Page:**
   - Navigate to: `http://yoursite.com/pages/auth/login.php`
   - Or try accessing any admin page - you'll be redirected to login

3. **Login with Default Credentials:**
   - Username: `admin`
   - Password: `admin123`

4. **Test Registration:**
   - Click "Create New Account" on login page
   - Fill in the registration form
   - Choose role: Admin or Staff

5. **Verify Protection:**
   - Try accessing admin pages without login
   - You should be redirected to login page

## Next Steps

### Recommended Enhancements:
1. **Change Default Password** - Update admin password immediately
2. **Email Verification** - Add email verification for new registrations
3. **Password Reset** - Implement forgot password functionality
4. **Two-Factor Authentication** - Add 2FA for enhanced security
5. **Session Timeout** - Add automatic logout after inactivity
6. **Login Attempts Limit** - Prevent brute force attacks
7. **User Activity Log** - Track user actions in admin panel

## API Endpoints

The AuthController provides these public methods:

```php
// Start secure session
AuthController::startSession();

// Check if logged in
AuthController::isLoggedIn(); // Returns true/false

// Require authentication (redirect if not logged in)
AuthController::requireAuth();

// Get current user data
AuthController::getCurrentUser(); // Returns user array or null

// Process login (from form POST)
$auth = new AuthController();
$auth->login();

// Process registration (from form POST)
$auth->register();

// Process logout (from form POST)
$auth->logout();
```

## Troubleshooting

**Issue:** Can't login with default credentials  
**Solution:** Make sure you've run the updated `database.sql` file

**Issue:** Getting redirected to login constantly  
**Solution:** Check if sessions are working - verify session.save_path in php.ini

**Issue:** "Headers already sent" error  
**Solution:** Make sure there's no output before session_start() calls

**Issue:** Password doesn't work  
**Solution:** Verify the password hash in database matches the bcrypt format ($2y$)

## Security Notes

- All passwords are hashed using bcrypt (cost factor 10)
- Sessions use HttpOnly cookies to prevent XSS attacks
- User input is escaped before database queries
- Session tokens are cryptographically secure random strings
- IP address and user agent are logged for security auditing
- Inactive accounts cannot login

---

For questions or issues, please refer to the code comments in `AuthController.php` or check the implementation in the login/register pages.

<?php
require_once __DIR__ . '/../models/db_model.php';
require_once __DIR__ . '/ControllerHelper.php';

class AuthController {
    private $userTable = 'admin_users';
    private $sessionTable = 'login_sessions';
    
    /**
     * Start secure session
     */
    public static function startSession() {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
            session_start();
        }
    }
    
    /**
     * Check if user is logged in
     */
    public static function isLoggedIn() {
        self::startSession();
        return isset($_SESSION['admin_user_id']) && isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
    }
    
    /**
     * Require authentication - redirect if not logged in
     */
    public static function requireAuth() {
        if (!self::isLoggedIn()) {
            self::startSession();
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            redirect_to('/pages/auth/login.php');
            exit;
        }
        
        // Update last activity
        self::updateLastActivity();
    }
    
    /**
     * Update last activity timestamp
     */
    private static function updateLastActivity() {
        self::startSession();
        if (isset($_SESSION['admin_user_id'])) {
            $_SESSION['last_activity'] = time();
            
            // Update database session
            if (isset($_SESSION['session_token'])) {
                global $connection;
                $token = mysqli_real_escape_string($connection, $_SESSION['session_token']);
                mysqli_query($connection, "UPDATE login_sessions SET last_activity = NOW() WHERE session_token = '$token'");
            }
        }
    }
    
    /**
     * Get current logged in user data
     */
    public static function getCurrentUser() {
        self::startSession();
        if (!self::isLoggedIn()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['admin_user_id'] ?? null,
            'username' => $_SESSION['admin_username'] ?? '',
            'full_name' => $_SESSION['admin_full_name'] ?? '',
            'email' => $_SESSION['admin_email'] ?? '',
            'role' => $_SESSION['admin_role'] ?? ''
        ];
    }
    
    /**
     * Process login
     */
    public function login() {
        self::startSession();
        
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember_me']);
        
        // Validation
        if (empty($username) || empty($password)) {
            return $this->redirectToLogin("Please enter both username and password.", "error");
        }
        
        // Get user from database
        global $connection;
        $username_escaped = mysqli_real_escape_string($connection, $username);
        $users = fetch($this->userTable, "username = '$username_escaped'");
        
        if (empty($users)) {
            return $this->redirectToLogin("Invalid username or password.", "error");
        }
        
        $user = $users[0];
        
        // Check if account is active
        if ($user['is_active'] != 1) {
            return $this->redirectToLogin("Your account has been deactivated. Please contact administrator.", "error");
        }
        
        // Verify password
        if (!password_verify($password, $user['password'])) {
            return $this->redirectToLogin("Invalid username or password.", "error");
        }
        
        // Create session
        $_SESSION['admin_user_id'] = $user['id'];
        $_SESSION['admin_username'] = $user['username'];
        $_SESSION['admin_full_name'] = $user['full_name'];
        $_SESSION['admin_email'] = $user['email'];
        $_SESSION['admin_role'] = $user['role'];
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['last_activity'] = time();
        
        // Generate session token
        $sessionToken = bin2hex(random_bytes(32));
        $_SESSION['session_token'] = $sessionToken;
        
        // Store session in database
        $sessionData = [
            'user_id' => $user['id'],
            'session_token' => $sessionToken,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            'last_activity' => date('Y-m-d H:i:s')
        ];
        save($this->sessionTable, $sessionData);
        
        // Update last login
        update($this->userTable, ['last_login' => date('Y-m-d H:i:s')], "id = " . $user['id']);
        
        // Set remember me cookie (30 days)
        if ($remember) {
            setcookie('remember_token', $sessionToken, time() + (30 * 24 * 60 * 60), '/');
        }
        
        // Redirect to intended page or dashboard
        $redirectUrl = $_SESSION['redirect_after_login'] ?? '../admin/account_management.php';
        unset($_SESSION['redirect_after_login']);
        
        redirect_to($redirectUrl);
    }
    
    /**
     * Process registration
     */
    public function register() {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $fullName = trim($_POST['full_name'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validation
        if (empty($username) || empty($email) || empty($fullName) || empty($password)) {
            return $this->redirectToRegister("All fields are required.", "error");
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->redirectToRegister("Please enter a valid email address.", "error");
        }
        
        if (strlen($username) < 4) {
            return $this->redirectToRegister("Username must be at least 4 characters long.", "error");
        }
        
        if (strlen($password) < 6) {
            return $this->redirectToRegister("Password must be at least 6 characters long.", "error");
        }
        
        if ($password !== $confirmPassword) {
            return $this->redirectToRegister("Passwords do not match.", "error");
        }
        
        // Check if username exists
        global $connection;
        $username_escaped = mysqli_real_escape_string($connection, $username);
        $existingUser = fetch($this->userTable, "username = '$username_escaped'");
        if (!empty($existingUser)) {
            return $this->redirectToRegister("Username already exists.", "error");
        }
        
        // Check if email exists
        $email_escaped = mysqli_real_escape_string($connection, $email);
        $existingEmail = fetch($this->userTable, "email = '$email_escaped'");
        if (!empty($existingEmail)) {
            return $this->redirectToRegister("Email already registered.", "error");
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert user
        $userData = [
            'username' => $username,
            'password' => $hashedPassword,
            'email' => $email,
            'full_name' => $fullName,
            'role' => 'staff', // Default role
            'is_active' => 1
        ];
        
        $userId = save($this->userTable, $userData);
        
        if ($userId) {
            return $this->redirectToLogin("Account created successfully! Please login.", "success");
        } else {
            return $this->redirectToRegister("Failed to create account. Please try again.", "error");
        }
    }
    
    /**
     * Logout user
     */
    public function logout() {
        self::startSession();
        
        // Delete session from database
        if (isset($_SESSION['session_token'])) {
            global $connection;
            $token = mysqli_real_escape_string($connection, $_SESSION['session_token']);
            mysqli_query($connection, "DELETE FROM login_sessions WHERE session_token = '$token'");
        }
        
        // Clear remember me cookie
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/');
        }
        
        // Destroy session
        session_unset();
        session_destroy();
        
        redirect_to('/pages/auth/login.php?message=' . urlencode('Logged out successfully') . '&type=success');
    }
    
    /**
     * Redirect helpers
     */
    private function redirectToLogin($message, $type) {
        redirect_with_message('/pages/auth/login.php', $message, $type);
    }
    
    private function redirectToRegister($message, $type) {
        redirect_with_message('/pages/auth/register.php', $message, $type);
    }
}

// Handle logout POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'logout') {
    $authController = new AuthController();
    $authController->logout();
}
?>

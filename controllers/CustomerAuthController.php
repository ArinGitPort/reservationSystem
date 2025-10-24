<?php
require_once __DIR__ . '/../config/db_model.php';
require_once __DIR__ . '/ControllerHelper.php';

class CustomerAuthController {
    private $customerTable = 'customers';
    private $sessionTable = 'customer_sessions';
    
    public static function startSession() {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_secure', 0);
            session_start();
        }
    }
    
    public static function isLoggedIn() {
        self::startSession();
        return isset($_SESSION['customer_id']) && isset($_SESSION['customer_logged_in']) && $_SESSION['customer_logged_in'] === true;
    }
    
    public static function getCurrentCustomer() {
        self::startSession();
        if (!self::isLoggedIn()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['customer_id'] ?? null,
            'email' => $_SESSION['customer_email'] ?? '',
            'first_name' => $_SESSION['customer_first_name'] ?? '',
            'last_name' => $_SESSION['customer_last_name'] ?? '',
            'phone' => $_SESSION['customer_phone'] ?? ''
        ];
    }
    
    public static function requireAuth($redirectTo = '../../pages/customer/login.php') {
        if (!self::isLoggedIn()) {
            self::startSession();
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            redirect_to($redirectTo);
            exit;
        }
        
        self::updateLastActivity();
    }
    
    private static function updateLastActivity() {
        self::startSession();
        if (isset($_SESSION['customer_id'])) {
            $_SESSION['last_activity'] = time();
            
            if (isset($_SESSION['customer_session_token'])) {
                global $connection;
                $token = mysqli_real_escape_string($connection, $_SESSION['customer_session_token']);
                mysqli_query($connection, "UPDATE customer_sessions SET last_activity = NOW() WHERE session_token = '$token'");
            }
        }
    }
    
    public function login() {
        self::startSession();
        
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember_me']);
        
        if (empty($email) || empty($password)) {
            return $this->redirectToLogin("Please enter both email and password.", "error");
        }
        
        global $connection;
        $email_escaped = mysqli_real_escape_string($connection, $email);
        $customers = fetch($this->customerTable, "email = '$email_escaped'");
        
        if (empty($customers)) {
            return $this->redirectToLogin("Invalid email or password.", "error");
        }
        
        $customer = $customers[0];
        
        if (empty($customer['password'])) {
            return $this->redirectToLogin("This email is not registered. Please sign up first.", "error");
        }
        
        if (!password_verify($password, $customer['password'])) {
            return $this->redirectToLogin("Invalid email or password.", "error");
        }
        
        $_SESSION['customer_id'] = $customer['id'];
        $_SESSION['customer_email'] = $customer['email'];
        $_SESSION['customer_first_name'] = $customer['first_name'];
        $_SESSION['customer_last_name'] = $customer['last_name'];
        $_SESSION['customer_phone'] = $customer['phone'];
        $_SESSION['customer_logged_in'] = true;
        $_SESSION['last_activity'] = time();
        
        $sessionToken = bin2hex(random_bytes(32));
        $_SESSION['customer_session_token'] = $sessionToken;
        
        $sessionData = [
            'customer_id' => $customer['id'],
            'session_token' => $sessionToken,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            'last_activity' => date('Y-m-d H:i:s')
        ];
        save($this->sessionTable, $sessionData);
        
        update($this->customerTable, ['last_login' => date('Y-m-d H:i:s')], "id = " . $customer['id']);
        
        if ($remember) {
            setcookie('customer_remember_token', $sessionToken, time() + (30 * 24 * 60 * 60), '/');
        }
        
        $redirectUrl = $_SESSION['redirect_after_login'] ?? '../menu.php';
        unset($_SESSION['redirect_after_login']);
        
        redirect_to($redirectUrl);
    }
    
    public function register() {
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (empty($firstName) || empty($lastName) || empty($email) || empty($phone) || empty($password)) {
            return $this->redirectToRegister("All fields are required.", "error");
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->redirectToRegister("Please enter a valid email address.", "error");
        }
        
        if (strlen($password) < 6) {
            return $this->redirectToRegister("Password must be at least 6 characters long.", "error");
        }
        
        if ($password !== $confirmPassword) {
            return $this->redirectToRegister("Passwords do not match.", "error");
        }
        
        global $connection;
        $email_escaped = mysqli_real_escape_string($connection, $email);
        $existingCustomer = fetch($this->customerTable, "email = '$email_escaped'");
        
        if (!empty($existingCustomer)) {
            if (!empty($existingCustomer[0]['password'])) {
                return $this->redirectToRegister("Email already registered. Please login.", "error");
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                update($this->customerTable, [
                    'password' => $hashedPassword,
                    'phone' => $phone,
                    'is_verified' => 1
                ], "id = " . $existingCustomer[0]['id']);
                
                return $this->redirectToLogin("Account created successfully! Please login.", "success");
            }
        }
        
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $customerData = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'phone' => $phone,
            'password' => $hashedPassword,
            'is_verified' => 1,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $customerId = save($this->customerTable, $customerData);
        
        if ($customerId) {
            return $this->redirectToLogin("Account created successfully! Please login.", "success");
        } else {
            return $this->redirectToRegister("Failed to create account. Please try again.", "error");
        }
    }
    
    public function logout() {
        self::startSession();
        
        if (isset($_SESSION['customer_session_token'])) {
            global $connection;
            $token = mysqli_real_escape_string($connection, $_SESSION['customer_session_token']);
            mysqli_query($connection, "DELETE FROM customer_sessions WHERE session_token = '$token'");
        }
        
        if (isset($_COOKIE['customer_remember_token'])) {
            setcookie('customer_remember_token', '', time() - 3600, '/');
        }
        
        session_unset();
        session_destroy();
        
        redirect_to('../../pages/customer/login.php?message=' . urlencode('Logged out successfully') . '&type=success');
    }
    
    private function redirectToLogin($message, $type) {
        redirect_with_message('../../pages/customer/login.php', $message, $type);
    }
    
    private function redirectToRegister($message, $type) {
        redirect_with_message('../../pages/customer/register.php', $message, $type);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'logout') {
    $authController = new CustomerAuthController();
    $authController->logout();
}
?>

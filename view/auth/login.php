<?php
require_once '../../controllers/AuthController.php';

// If already logged in, redirect to dashboard
AuthController::startSession();
if (AuthController::isLoggedIn()) {
    header('Location: ../admin/account_management.php');
    exit;
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $authController = new AuthController();
    $authController->login();
}

// Get messages
$message = $_GET['message'] ?? '';
$messageType = $_GET['type'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Ellen's Food House</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/login.css">
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <i class="fas fa-utensils"></i>
            <h2>Admin Login</h2>
            <p class="mb-0">Ellen's Food House</p>
        </div>
        
        <div class="login-body">
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="hidden" name="action" value="login">
                
                <div class="mb-3">
                    <label for="username" class="form-label">
                        <i class="fas fa-user me-2"></i>Username
                    </label>
                    <input type="text" class="form-control" id="username" name="username" required autofocus>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock me-2"></i>Password
                    </label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="remember_me" name="remember_me">
                    <label class="form-check-label" for="remember_me">
                        Remember me
                    </label>
                </div>
                
                <button type="submit" class="btn btn-login">
                    <i class="fas fa-sign-in-alt me-2"></i>Login
                </button>
            </form>
            
            <div class="divider">
                <span>or</span>
            </div>
            
            <div class="text-center">
                <a href="register.php" class="btn btn-outline-secondary w-100" style="border-radius: 10px;">
                    <i class="fas fa-user-plus me-2"></i>Create New Account
                </a>
            </div>
            
            <div class="back-home">
                <a href="../home.php">
                    <i class="fas fa-home me-1"></i>Back to Website
                </a>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

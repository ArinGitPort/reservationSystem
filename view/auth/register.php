<?php
require_once '../../controllers/AuthController.php';

// If already logged in, redirect to dashboard
AuthController::startSession();
if (AuthController::isLoggedIn()) {
    header('Location: ../admin/account_management.php');
    exit;
}

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    $authController = new AuthController();
    $authController->register();
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
    <title>Register - Ellen's Food House</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/register.cssg">
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <i class="fas fa-user-plus"></i>
            <h2>Create Account</h2>
            <p class="mb-0">Join Ellen's Food House Admin</p>
        </div>
        
        <div class="register-body">
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="registerForm">
                <input type="hidden" name="action" value="register">
                
                <div class="mb-3">
                    <label for="username" class="form-label">
                        <i class="fas fa-user me-2"></i>Username
                    </label>
                    <input type="text" class="form-control" id="username" name="username" required>
                    <div class="password-hint">Minimum 3 characters</div>
                </div>
                
                <div class="mb-3">
                    <label for="email" class="form-label">
                        <i class="fas fa-envelope me-2"></i>Email
                    </label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                
                <div class="mb-3">
                    <label for="full_name" class="form-label">
                        <i class="fas fa-id-card me-2"></i>Full Name
                    </label>
                    <input type="text" class="form-control" id="full_name" name="full_name" required>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock me-2"></i>Password
                    </label>
                    <input type="password" class="form-control" id="password" name="password" required>
                    <div class="password-strength">
                        <div class="password-strength-bar" id="strengthBar"></div>
                    </div>
                    <div class="password-hint">Minimum 6 characters</div>
                </div>
                
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">
                        <i class="fas fa-lock me-2"></i>Confirm Password
                    </label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    <div class="error-text" id="passwordMatchError">Passwords do not match</div>
                </div>
                
                <div class="mb-3">
                    <label for="role" class="form-label">
                        <i class="fas fa-user-tag me-2"></i>Role
                    </label>
                    <select class="form-select" id="role" name="role" required>
                        <option value="">Select Role</option>
                        <option value="admin">Admin</option>
                        <option value="staff">Staff</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-register" id="submitBtn">
                    <i class="fas fa-user-plus me-2"></i>Create Account
                </button>
            </form>
            
            <div class="divider">
                <span>or</span>
            </div>
            
            <div class="back-login">
                <p>Already have an account? <a href="login.php">Login here</a></p>
            </div>
            
            <div class="back-login">
                <a href="../home.php">
                    <i class="fas fa-home me-1"></i>Back to Website
                </a>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password strength checker
        const passwordInput = document.getElementById('password');
        const strengthBar = document.getElementById('strengthBar');
        
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            
            if (password.length >= 6) strength++;
            if (password.length >= 10) strength++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/\d/.test(password)) strength++;
            if (/[^a-zA-Z0-9]/.test(password)) strength++;
            
            strengthBar.className = 'password-strength-bar';
            if (strength <= 2) {
                strengthBar.classList.add('weak');
            } else if (strength <= 4) {
                strengthBar.classList.add('medium');
            } else {
                strengthBar.classList.add('strong');
            }
        });
        
        // Password match validation
        const confirmPasswordInput = document.getElementById('confirm_password');
        const passwordMatchError = document.getElementById('passwordMatchError');
        const submitBtn = document.getElementById('submitBtn');
        
        function checkPasswordMatch() {
            if (confirmPasswordInput.value && passwordInput.value !== confirmPasswordInput.value) {
                passwordMatchError.style.display = 'block';
                confirmPasswordInput.classList.add('is-invalid');
                return false;
            } else {
                passwordMatchError.style.display = 'none';
                confirmPasswordInput.classList.remove('is-invalid');
                return true;
            }
        }
        
        confirmPasswordInput.addEventListener('input', checkPasswordMatch);
        passwordInput.addEventListener('input', checkPasswordMatch);
        
        // Form submission validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            if (!checkPasswordMatch()) {
                e.preventDefault();
                return false;
            }
        });
    </script>
</body>
</html>

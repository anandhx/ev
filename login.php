<?php
session_start();
require_once 'config/config.php';
require_once 'includes/functions.php';

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = "Please enter both username/email and password.";
    } else {
        try {
            $userManager = new UserManager();
            $adminManager = new AdminManager();
            
            // Try user login first
            $user = $userManager->authenticateUser($username, $password);
            
            if ($user) {
                // Simple user session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_type'] = 'user';
                
                header('Location: dashboard.php');
                exit;
            } else {
                // Try admin login
                $admin = $adminManager->authenticateAdmin($username, $password);
                
                if ($admin) {
                    // Simple admin session
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['username'] = $admin['username'];
                    $_SESSION['user_type'] = 'admin';
                    
                    header('Location: admin/dashboard.php');
                    exit;
                } else {
                    $error = "Invalid username/email or password. Please try again.";
                }
            }
        } catch (Exception $e) {
            $error = "System error. Please try again later.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - EV Mobile Power & Service Station</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            width: 100%;
            max-width: 450px;
            position: relative;
            animation: slideUp 0.5s ease;
        }

        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .login-header h1 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }

        .login-header p {
            opacity: 0.9;
            font-size: 0.9rem;
        }

        .login-form {
            padding: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-group .input-icon {
            position: relative;
        }

        .form-group .input-icon i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #667eea;
        }

        .form-group .input-icon input {
            padding-left: 3rem;
        }

        .login-btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .login-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .login-btn .btn-text {
            transition: opacity 0.3s ease;
        }

        .login-btn .spinner {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .login-btn.loading .btn-text {
            opacity: 0;
        }

        .login-btn.loading .spinner {
            opacity: 1;
        }

        .login-footer {
            text-align: center;
            padding: 1rem 2rem 2rem;
            background: #f8f9fa;
        }

        .login-footer a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .login-footer a:hover {
            color: #764ba2;
            text-decoration: underline;
        }

        .error-message {
            background: #fee;
            color: #c33;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            border: 1px solid #fcc;
            animation: shake 0.5s ease;
        }

        .back-home {
            position: absolute;
            top: 1rem;
            left: 1rem;
            color: white;
            text-decoration: none;
            font-size: 1.2rem;
            transition: all 0.3s ease;
            background: rgba(255,255,255,0.2);
            padding: 0.5rem;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .back-home:hover {
            transform: translateX(-5px);
            background: rgba(255,255,255,0.3);
        }

        .features-highlight {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 1.5rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            text-align: center;
        }

        .features-highlight h3 {
            color: #333;
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }

        .features-list {
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #667eea;
            font-size: 0.9rem;
        }

        .feature-item i {
            font-size: 1.2rem;
        }

        .forgot-password {
            text-align: right;
            margin-bottom: 1rem;
        }

        .forgot-password a {
            color: #667eea;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }

        .forgot-password a:hover {
            color: #764ba2;
            text-decoration: underline;
        }

        .demo-info {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            color: #856404;
        }

        .demo-info h4 {
            color: #856404;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .demo-info ul {
            list-style: none;
            padding-left: 0;
            margin: 0;
        }

        .demo-info li {
            margin-bottom: 0.25rem;
            padding: 0.25rem 0;
        }

        .demo-info .demo-credentials {
            background: rgba(255,255,255,0.5);
            padding: 0.5rem;
            border-radius: 5px;
            margin-top: 0.5rem;
        }

        @keyframes slideUp {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        @media (max-width: 480px) {
            .login-container {
                margin: 1rem;
            }
            
            .login-header {
                padding: 1.5rem;
            }
            
            .login-form {
                padding: 1.5rem;
            }
            
            .features-list {
                flex-direction: column;
                align-items: center;
            }
        }

        .field-error {
            border-color: #c33 !important;
            animation: shake 0.5s ease;
        }

        .field-success {
            border-color: #28a745 !important;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <a href="index.php" class="back-home" title="Back to Home">
            <i class="fas fa-arrow-left"></i>
        </a>
        
        <div class="login-header">
            <h1><i class="fas fa-bolt"></i> EV Mobile Station</h1>
            <p>Welcome back! Sign in to your account</p>
        </div>

        <div class="login-form">
            <!-- Features Highlight -->
            <div class="features-highlight">
                <h3><i class="fas fa-star"></i> Your EV Roadside Partner</h3>
                <div class="features-list">
                    <div class="feature-item">
                        <i class="fas fa-bolt"></i>
                        <span>Fast Charging</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-tools"></i>
                        <span>Expert Service</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-clock"></i>
                        <span>24/7 Support</span>
                    </div>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <div class="demo-info">
                <h4><i class="fas fa-info-circle"></i> Demo Credentials</h4>
                <div class="demo-credentials">
                    <strong>User Login:</strong> john_doe / demo123<br>
                    <strong>Admin Login:</strong> admin / admin123
                </div>
                <small style="display: block; margin-top: 0.5rem; opacity: 0.8;">
                    Use these credentials to test the system
                </small>
            </div>

            <form method="POST" action="" id="loginForm">
                <div class="form-group">
                    <label for="username">Username or Email</label>
                    <div class="input-icon">
                        <i class="fas fa-user"></i>
                        <input type="text" id="username" name="username" required 
                               value="<?php echo htmlspecialchars($username); ?>"
                               placeholder="Enter your username or email"
                               autocomplete="username">
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" required 
                               placeholder="Enter your password"
                               autocomplete="current-password">
                    </div>
                </div>

                <div class="forgot-password">
                    <a href="#" onclick="alert('Password reset functionality coming soon!')">
                        <i class="fas fa-key"></i> Forgot Password?
                    </a>
                </div>

                <button type="submit" class="login-btn" id="loginBtn">
                    <span class="btn-text">
                        <i class="fas fa-sign-in-alt"></i> Sign In
                    </span>
                    <i class="fas fa-spinner fa-spin spinner"></i>
                </button>
            </form>
        </div>

        <div class="login-footer">
            <p>Don't have an account? <a href="signup.php">Sign up here</a></p>
            <p><a href="index.php">Back to Home</a></p>
        </div>
    </div>

    <script>
        // Form validation and submission
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const loginBtn = document.getElementById('loginBtn');
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();
            
            if (!username || !password) {
                e.preventDefault();
                
                // Show error message
                if (!username) {
                    document.getElementById('username').classList.add('field-error');
                }
                if (!password) {
                    document.getElementById('password').classList.add('field-error');
                }
                
                // Create error message
                const errorDiv = document.createElement('div');
                errorDiv.className = 'error-message';
                errorDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Please fill in all fields.';
                
                // Insert error message
                const form = document.getElementById('loginForm');
                form.insertBefore(errorDiv, form.firstChild);
                
                // Remove error message after 5 seconds
                setTimeout(() => {
                    if (errorDiv.parentNode) {
                        errorDiv.remove();
                    }
                }, 5000);
                
                return false;
            }
            
            // Show loading state
            loginBtn.classList.add('loading');
            loginBtn.disabled = true;
        });

        // Real-time field validation
        document.querySelectorAll('input').forEach(input => {
            input.addEventListener('input', function() {
                if (this.classList.contains('field-error')) {
                    this.classList.remove('field-error');
                }
                if (this.value.trim()) {
                    this.classList.add('field-success');
                } else {
                    this.classList.remove('field-success');
                }
            });
            
            input.addEventListener('blur', function() {
                if (this.value.trim()) {
                    this.classList.add('field-success');
                } else {
                    this.classList.remove('field-success');
                }
            });
        });

        // Add interactive effects
        document.querySelectorAll('input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });

        // Auto-focus username field
        document.getElementById('username').focus();

        // Enter key navigation
        document.getElementById('username').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('password').focus();
            }
        });

        document.getElementById('password').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('loginForm').submit();
            }
        });
    </script>
</body>
</html> 
</html> 
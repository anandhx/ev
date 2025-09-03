<?php
session_start();
require_once 'config/config.php';
require_once 'includes/functions.php';

// If already logged in, redirect to appropriate dashboard
if (SessionManager::isLoggedIn()) {
    if (SessionManager::isAdmin()) {
        header('Location: admin/dashboard.php');
    } elseif (!empty($_SESSION['user_type']) && $_SESSION['user_type'] === 'technician') {
        header('Location: technicians/dashboard.php');
    } else {
        header('Location: dashboard.php');
    }
    exit;
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Username and password are required';
    } else {
        try {
            $userManager = new UserManager();
            $adminManager = new AdminManager();
            $techManager = new TechnicianManager();

            // Try user login first
            $user = $userManager->authenticateUser($username, $password);
            if ($user) {
                SessionManager::setUserSession($user);
                header('Location: dashboard.php');
                exit;
            }

            // Try admin login
            $admin = $adminManager->authenticateAdmin($username, $password);
            if ($admin) {
                SessionManager::setAdminSession($admin);
                header('Location: admin/dashboard.php');
                exit;
            }

            // Try technician login (by email or username if supported)
            $technician = null;
            if (method_exists($techManager, 'authenticateTechnician')) {
                $technician = $techManager->authenticateTechnician($username, $password);
            } else {
                // Fallback: direct DB check
                $dbi = Database::getInstance();
                $stmt = $dbi->executeQuery("SELECT * FROM technicians WHERE (email = ? OR full_name = ?) LIMIT 1", [$username, $username]);
                $row = $stmt->fetch();
                if ($row && !empty($row['password']) && password_verify($password, $row['password'])) {
                    $technician = $row;
                }
            }

            if ($technician) {
                // Set technician session
                $_SESSION['technician_id'] = (int)$technician['id'];
                $_SESSION['technician_name'] = $technician['full_name'] ?? 'Technician';
                $_SESSION['user_type'] = 'technician';
                $_SESSION['logged_in'] = true;
                header('Location: technicians/dashboard.php');
                exit;
            }

            $error = 'Invalid credentials';
        } catch (Exception $e) {
            error_log('Login error: ' . $e->getMessage());
            $error = 'System error. Please try again later.';
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

        @keyframes slideUp {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .field-error { border-color: #c33 !important; }
        .field-success { border-color: #28a745 !important; }
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
            <?php if ($error): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

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

                <div class="forgot-password" style="text-align:right;margin-bottom:1rem;">
                    <a href="#" onclick="alert('Password reset functionality coming soon!')">
                        <i class="fas fa-key"></i> Forgot Password?
                    </a>
                </div>

                <button type="submit" class="login-btn" id="loginBtn">
                    <span class="btn-text">
                        <i class="fas fa-sign-in-alt"></i> Sign In
                    </span>
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
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();
            
            if (!username || !password) {
                e.preventDefault();
                if (!username) document.getElementById('username').classList.add('field-error');
                if (!password) document.getElementById('password').classList.add('field-error');
                return false;
            }
        });

        // Real-time field validation
        document.querySelectorAll('input').forEach(input => {
            input.addEventListener('input', function() {
                this.classList.remove('field-error');
                if (this.value.trim()) {
                    this.classList.add('field-success');
                } else {
                    this.classList.remove('field-success');
                }
            });
        });

        // Auto-focus username field
        document.getElementById('username').focus();
    </script>
</body>
</html> 
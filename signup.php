<?php
session_start();
require_once 'config/config.php';
require_once 'includes/functions.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$errors = [];
$success = '';
$form_data = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userManager = new UserManager();
    
    // Sanitize and validate input
    $form_data = [
        'username' => trim($_POST['username'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'confirm_password' => $_POST['confirm_password'] ?? '',
        'full_name' => trim($_POST['full_name'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'vehicle_model' => trim($_POST['vehicle_model'] ?? ''),
        'vehicle_plate' => trim($_POST['vehicle_plate'] ?? '')
    ];
    
    // Comprehensive validation
    if (empty($form_data['username'])) {
        $errors[] = "Username is required";
    } elseif (strlen($form_data['username']) < 3) {
        $errors[] = "Username must be at least 3 characters long";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $form_data['username'])) {
        $errors[] = "Username can only contain letters, numbers, and underscores";
    }
    
    if (empty($form_data['email'])) {
        $errors[] = "Email is required";
    } elseif (!UtilityFunctions::validateEmail($form_data['email'])) {
        $errors[] = "Please enter a valid email address";
    }
    
    if (empty($form_data['password'])) {
        $errors[] = "Password is required";
    } elseif (strlen($form_data['password']) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/', $form_data['password'])) {
        $errors[] = "Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character";
    }
    
    if ($form_data['password'] !== $form_data['confirm_password']) {
        $errors[] = "Passwords do not match";
    }
    
    if (empty($form_data['full_name'])) {
        $errors[] = "Full name is required";
    } elseif (strlen($form_data['full_name']) < 2) {
        $errors[] = "Full name must be at least 2 characters long";
    }
    
    // Phone number validation removed - field is now optional
    
    // Check if username or email already exists
    if (empty($errors)) {
        try {
            $db = Database::getInstance();
            
            // Check username
            $stmt = $db->executeQuery("SELECT id FROM users WHERE username = ?", [$form_data['username']]);
            if ($stmt->fetch()) {
                $errors[] = "Username already exists. Please choose a different one.";
            }
            
            // Check email
            $stmt = $db->executeQuery("SELECT id FROM users WHERE email = ?", [$form_data['email']]);
            if ($stmt->fetch()) {
                $errors[] = "Email already exists. Please use a different email or login.";
            }
        } catch (Exception $e) {
            $errors[] = "System error. Please try again later.";
            error_log("Signup validation error: " . $e->getMessage());
        }
    }
    
    // If no errors, create user
    if (empty($errors)) {
        try {
            $user_id = $userManager->createUser($form_data);
            
            if ($user_id) {
                $success = "Account created successfully! You can now login with your credentials.";
                
                // Log the successful registration before clearing form data
                error_log("New user registered: {$form_data['username']} ({$form_data['email']})");
                
                $form_data = []; // Clear form data after logging
            } else {
                $errors[] = "Failed to create account. Please try again.";
            }
        } catch (Exception $e) {
            $errors[] = "System error. Please try again later.";
            error_log("User creation error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - EV Mobile Power & Service Station</title>
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
            padding: 2rem;
        }

        .signup-container {
            max-width: 700px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            position: relative;
        }

        .signup-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .signup-header h1 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }

        .signup-header p {
            opacity: 0.9;
            font-size: 0.9rem;
        }

        .signup-form {
            padding: 2rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
        }

        .form-group input, .form-group select {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus, .form-group select:focus {
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

        .signup-btn {
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
            margin-top: 1rem;
        }

        .signup-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .signup-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .signup-footer {
            text-align: center;
            padding: 1rem 2rem 2rem;
            background: #f8f9fa;
        }

        .signup-footer a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .signup-footer a:hover {
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
            animation: slideIn 0.3s ease;
        }

        .success-message {
            background: #efe;
            color: #363;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            border: 1px solid #cfc;
            animation: slideIn 0.3s ease;
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

        .password-strength {
            margin-top: 0.5rem;
            font-size: 0.8rem;
            padding: 0.5rem;
            border-radius: 5px;
            text-align: center;
            font-weight: 500;
        }

        .strength-weak { 
            background: #fee; 
            color: #c33; 
            border: 1px solid #fcc;
        }
        .strength-medium { 
            background: #fff3cd; 
            color: #856404; 
            border: 1px solid #ffeaa7;
        }
        .strength-strong { 
            background: #d4edda; 
            color: #155724; 
            border: 1px solid #c3e6cb;
        }

        .field-error {
            border-color: #c33 !important;
            animation: shake 0.5s ease;
        }

        .field-success {
            border-color: #28a745 !important;
        }

        .loading {
            opacity: 0.7;
            pointer-events: none;
        }

        .progress-bar {
            width: 100%;
            height: 4px;
            background: #e1e5e9;
            border-radius: 2px;
            margin-top: 0.5rem;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #c33, #f90, #28a745);
            transition: width 0.3s ease;
            border-radius: 2px;
        }

        @keyframes slideIn {
            from { transform: translateY(-10px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .signup-container {
                margin: 1rem;
            }
            
            .signup-header {
                padding: 1.5rem;
            }
            
            .signup-form {
                padding: 1.5rem;
            }
        }

        .features-preview {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 15px;
            margin-bottom: 2rem;
        }

        .features-preview h3 {
            color: #333;
            margin-bottom: 1rem;
            text-align: center;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .feature-item {
            text-align: center;
            padding: 1rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .feature-item i {
            font-size: 2rem;
            color: #667eea;
            margin-bottom: 0.5rem;
        }

        .feature-item h4 {
            color: #333;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .feature-item p {
            color: #666;
            font-size: 0.8rem;
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="signup-container">
        <a href="index.php" class="back-home" title="Back to Home">
            <i class="fas fa-arrow-left"></i>
        </a>
        
        <div class="signup-header">
            <h1><i class="fas fa-bolt"></i> EV Mobile Station</h1>
            <p>Join thousands of EV drivers getting roadside assistance</p>
        </div>

        <div class="signup-form">
            <!-- Features Preview -->
            <div class="features-preview">
                <h3><i class="fas fa-star"></i> Why Choose EV Mobile Station?</h3>
                <div class="features-grid">
                    <div class="feature-item">
                        <i class="fas fa-bolt"></i>
                        <h4>Fast Charging</h4>
                        <p>Get charged in minutes, not hours</p>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-tools"></i>
                        <h4>Expert Technicians</h4>
                        <p>Certified EV specialists</p>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-clock"></i>
                        <h4>24/7 Support</h4>
                        <p>Help whenever you need it</p>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <h4>GPS Tracking</h4>
                        <p>Real-time service updates</p>
                    </div>
                </div>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Please fix the following errors:</strong>
                    <ul style="margin: 0.5rem 0 0 1.5rem;">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i> 
                    <strong><?php echo htmlspecialchars($success); ?></strong>
                    <div style="margin-top: 1rem;">
                        <a href="login.php" class="btn btn-primary" style="background: #28a745; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 5px;">
                            <i class="fas fa-sign-in-alt"></i> Login Now
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="signupForm">
                <div class="form-row">
                    <div class="form-group">
                        <label for="username">Username *</label>
                        <div class="input-icon">
                            <i class="fas fa-user"></i>
                            <input type="text" id="username" name="username" required 
                                   value="<?php echo htmlspecialchars($form_data['username'] ?? ''); ?>"
                                   placeholder="Choose a username (min 3 characters)"
                                   pattern="[a-zA-Z0-9_]{3,}"
                                   title="Username must be at least 3 characters and can only contain letters, numbers, and underscores">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email">Email *</label>
                        <div class="input-icon">
                            <i class="fas fa-envelope"></i>
                            <input type="email" id="email" name="email" required 
                                   value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>"
                                   placeholder="Enter your email address">
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Password *</label>
                        <div class="input-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="password" name="password" required 
                                   placeholder="Create a strong password (min 8 characters)"
                                   minlength="8">
                        </div>
                        <div class="password-strength" id="password-strength">
                            <div class="progress-bar">
                                <div class="progress-fill" id="progress-fill" style="width: 0%"></div>
                            </div>
                            <div id="strength-text">Enter your password</div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm Password *</label>
                        <div class="input-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="confirm_password" name="confirm_password" required 
                                   placeholder="Confirm your password">
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="full_name">Full Name *</label>
                        <div class="input-icon">
                            <i class="fas fa-id-card"></i>
                            <input type="text" id="full_name" name="full_name" required 
                                   value="<?php echo htmlspecialchars($form_data['full_name'] ?? ''); ?>"
                                   placeholder="Enter your full name">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <div class="input-icon">
                            <i class="fas fa-phone"></i>
                            <input type="tel" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($form_data['phone'] ?? ''); ?>"
                                   placeholder="Enter your phone number">
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="vehicle_model">Vehicle Model</label>
                        <div class="input-icon">
                            <i class="fas fa-car"></i>
                            <input type="text" id="vehicle_model" name="vehicle_model" 
                                   value="<?php echo htmlspecialchars($form_data['vehicle_model'] ?? ''); ?>"
                                   placeholder="e.g., Tesla Model 3, Nissan Leaf">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="vehicle_plate">License Plate</label>
                        <div class="input-icon">
                            <i class="fas fa-hashtag"></i>
                            <input type="text" id="vehicle_plate" name="vehicle_plate" 
                                   value="<?php echo htmlspecialchars($form_data['vehicle_plate'] ?? ''); ?>"
                                   placeholder="e.g., ABC-123">
                        </div>
                    </div>
                </div>

                <button type="submit" class="signup-btn" id="submitBtn">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
            </form>
        </div>

        <div class="signup-footer">
            <p>Already have an account? <a href="login.php">Sign in here</a></p>
            <p><a href="index.php">Back to Home</a></p>
        </div>
    </div>

    <script>
        // Password strength indicator
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthDiv = document.getElementById('password-strength');
            const progressFill = document.getElementById('progress-fill');
            const strengthText = document.getElementById('strength-text');
            
            let strength = 0;
            let message = '';
            let className = '';
            let progressWidth = 0;

            if (password.length >= 8) strength++;
            if (password.match(/[a-z]/)) strength++;
            if (password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;

            switch (strength) {
                case 0:
                case 1:
                    message = 'Very Weak';
                    className = 'strength-weak';
                    progressWidth = 20;
                    break;
                case 2:
                    message = 'Weak';
                    className = 'strength-weak';
                    progressWidth = 40;
                    break;
                case 3:
                    message = 'Medium';
                    className = 'strength-medium';
                    progressWidth = 60;
                    break;
                case 4:
                    message = 'Strong';
                    className = 'strength-strong';
                    progressWidth = 80;
                    break;
                case 5:
                    message = 'Very Strong';
                    className = 'strength-strong';
                    progressWidth = 100;
                    break;
            }

            strengthText.textContent = `Password Strength: ${message}`;
            strengthDiv.className = `password-strength ${className}`;
            progressFill.style.width = progressWidth + '%';
        });

        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (confirmPassword && password !== confirmPassword) {
                this.classList.add('field-error');
                this.classList.remove('field-success');
            } else if (confirmPassword) {
                this.classList.remove('field-error');
                this.classList.add('field-success');
            } else {
                this.classList.remove('field-error', 'field-success');
            }
        });

        // Real-time field validation
        document.querySelectorAll('input[required]').forEach(input => {
            input.addEventListener('blur', function() {
                validateField(this);
            });
            
            input.addEventListener('input', function() {
                if (this.classList.contains('field-error')) {
                    validateField(this);
                }
            });
        });

        function validateField(field) {
            const value = field.value.trim();
            let isValid = true;
            let errorMessage = '';

            switch (field.name) {
                case 'username':
                    if (value.length < 3) {
                        isValid = false;
                        errorMessage = 'Username must be at least 3 characters';
                    } else if (!/^[a-zA-Z0-9_]+$/.test(value)) {
                        isValid = false;
                        errorMessage = 'Username can only contain letters, numbers, and underscores';
                    }
                    break;
                case 'email':
                    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                        isValid = false;
                        errorMessage = 'Please enter a valid email address';
                    }
                    break;
                case 'password':
                    if (value.length < 8) {
                        isValid = false;
                        errorMessage = 'Password must be at least 8 characters';
                    }
                    break;
                case 'full_name':
                    if (value.length < 2) {
                        isValid = false;
                        errorMessage = 'Full name must be at least 2 characters';
                    }
                    break;
                case 'phone':
                    // Phone number validation removed - field is now optional
                    break;
            }

            if (!isValid) {
                field.classList.add('field-error');
                field.title = errorMessage;
            } else {
                field.classList.remove('field-error');
                field.classList.add('field-success');
                field.title = '';
            }
        }

        // Form submission
        document.getElementById('signupForm').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submitBtn');
            const form = this;
            
            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Account...';
            form.classList.add('loading');

            // Validate all required fields
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('field-error');
                }
            });

            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (password !== confirmPassword) {
                isValid = false;
                document.getElementById('confirm_password').classList.add('field-error');
            }

            if (!isValid) {
                e.preventDefault();
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-user-plus"></i> Create Account';
                form.classList.remove('loading');
                
                // Show error message
                const errorDiv = document.createElement('div');
                errorDiv.className = 'error-message';
                errorDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Please fix the errors above before submitting.';
                form.insertBefore(errorDiv, form.firstChild);
                
                // Remove error message after 5 seconds
                setTimeout(() => {
                    if (errorDiv.parentNode) {
                        errorDiv.remove();
                    }
                }, 5000);
                
                return false;
            }
        });

        // Add interactive effects
        document.querySelectorAll('input, select').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });

        // Auto-format phone number
        document.getElementById('phone').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 0) {
                if (value.length <= 3) {
                    value = value;
                } else if (value.length <= 6) {
                    value = value.slice(0, 3) + '-' + value.slice(3);
                } else {
                    value = value.slice(0, 3) + '-' + value.slice(3, 6) + '-' + value.slice(6, 10);
                }
            }
            e.target.value = value;
        });
    </script>
</body>
</html>

<?php
session_start();
require_once 'includes/functions.php';

// Check if user is logged in (session-based)
if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'user') {
    header('Location: login.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$message = '';
$userManager = new UserManager();
$user = $userManager->getUserById($user_id);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profile'])) {
        $updateData = [
            'full_name' => UtilityFunctions::sanitizeInput($_POST['name'] ?? $_POST['full_name'] ?? ''),
            'phone' => UtilityFunctions::sanitizeInput($_POST['phone'] ?? ''),
            'vehicle_model' => UtilityFunctions::sanitizeInput($_POST['vehicle_model'] ?? ''),
            'vehicle_plate' => ''
        ];
        if ($userManager->updateUser($user_id, $updateData)) {
            $message = 'Profile updated successfully!';
            $user = $userManager->getUserById($user_id);
        } else {
            $message = 'Failed to update profile.';
        }
    } elseif (isset($_POST['change_password'])) {
        // Simulate password change
        $message = 'Password changed successfully!';
    }
}

if (!$user) {
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - EV Mobile Power & Service Station</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: radial-gradient(1000px 500px at 10% -10%, #e6e9ff 0%, #ffffff 40%),
                        radial-gradient(800px 400px at 110% 10%, #f5e6ff 0%, #ffffff 50%);
            color: #333;
            min-height: 100vh;
        }

        .navbar { position: sticky; top: 0; background: linear-gradient(135deg, rgba(102,126,234,0.9) 0%, rgba(118,75,162,0.9) 100%); backdrop-filter: blur(8px); color: white; padding: 1rem 2rem; box-shadow: 0 8px 30px rgba(0,0,0,0.08); z-index: 1000; }
        .navbar-content { display: flex; justify-content: space-between; align-items: center; width: 100%; margin: 0; }
        .navbar-brand { font-size: 1.5rem; font-weight: bold; color: white; text-decoration: none; display: flex; align-items: center; gap: 0.6rem; letter-spacing: 0.3px; }
        .brand-badge { font-size: 0.75rem; background: rgba(255,255,255,0.2); padding: 0.2rem 0.5rem; border-radius: 999px; }
        .navbar-nav { display: flex; list-style: none; gap: 1rem; align-items: center; }
        .navbar-nav a { color: white; text-decoration: none; transition: all 0.25s ease; padding: 0.5rem 1rem; border-radius: 8px; }
        .navbar-nav a:hover { background: rgba(255,255,255,0.18); transform: translateY(-2px); }
        .user-menu { position: relative; }
        .user-menu-toggle { background: rgba(255,255,255,0.18); border: 1px solid rgba(255,255,255,0.25); color: white; padding: 0.6rem 0.9rem; border-radius: 10px; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; transition: all 0.25s ease; }
        .user-menu-toggle:hover { background: rgba(255,255,255,0.28); }
        .user-dropdown { position: absolute; top: 110%; right: 0; background: #fff; border-radius: 12px; box-shadow: 0 18px 50px rgba(0,0,0,0.12); min-width: 220px; opacity: 0; visibility: hidden; transform: translateY(-8px); transition: all 0.25s ease; overflow: hidden; }
        .user-dropdown.show { opacity: 1; visibility: visible; transform: translateY(0); }
        .user-dropdown a { color: #333; padding: 0.9rem 1rem; display: block; text-decoration: none; border-bottom: 1px solid #f0f0f0; transition: background 0.25s ease; }
        .user-dropdown a:last-child { border-bottom: none; }
        .user-dropdown a:hover { background: #f6f7ff; color: #667eea; }
        .main-content { width: 100%; max-width: none; margin: 1rem 0; padding: 0 1rem; }

        .welcome-section { position: relative; background: linear-gradient(135deg, #ffffff 0%, #f6f7ff 100%); border-radius: 20px; padding: 2rem; min-height: 120px; margin-bottom: 2rem; box-shadow: 0 15px 40px rgba(102, 126, 234, 0.08); overflow: hidden; }
        .welcome-section h1 { color: #222; margin: 0; font-size: 1.6rem; display:flex; align-items:center; gap:0.5rem; }

        .profile-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .profile-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .profile-avatar {
            text-align: center;
            margin-bottom: 30px;
        }

        .avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 48px;
            color: white;
        }

        .profile-info h3 {
            color: #333;
            margin-bottom: 20px;
            text-align: center;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: #666;
        }

        .info-value {
            color: #333;
        }

        .form-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .form-section h3 {
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 25px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }

        .btn-danger {
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
            color: white;
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 107, 107, 0.3);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: rgba(40, 167, 69, 0.1);
            border: 1px solid #28a745;
            color: #155724;
        }

        .alert-error {
            background: rgba(220, 53, 69, 0.1);
            border: 1px solid #dc3545;
            color: #721c24;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #666;
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .profile-grid {
                grid-template-columns: 1fr;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .nav-links {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-content">
            <a href="index.php" class="navbar-brand">
                <i class="fas fa-bolt"></i> EV Mobile Station
                <span class="brand-badge">Profile</span>
            </a>
            <ul class="navbar-nav">
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="request-service.php"><i class="fas fa-plus-circle"></i> Request Service</a></li>
                <li><a href="service-history.php"><i class="fas fa-history"></i> History</a></li>
                <li><a href="vehicles.php"><i class="fas fa-car"></i> My Vehicles</a></li>
                <li><a href="spares.php"><i class="fas fa-gears"></i> Spare Parts</a></li>
                <li><a href="track-service.php"><i class="fas fa-map-marker-alt"></i> Track Service</a></li>
                <li><a href="support.php"><i class="fas fa-headset"></i> Support</a></li>
                <li class="user-menu">
                    <button class="user-menu-toggle" onclick="toggleUserMenu()">
                        <i class="fas fa-user-circle"></i>
                        <?php echo htmlspecialchars($user['full_name'] ?? 'User'); ?>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="user-dropdown" id="userDropdown">
                        <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                        <a href="notifications.php"><i class="fas fa-bell"></i> Notifications</a>
                        <a href="payments.php"><i class="fas fa-credit-card"></i> Payments</a>
                        <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </li>
            </ul>
        </div>
    </nav>

    <div class="main-content">
        <div class="welcome-section">
            <h1><i class="fas fa-user-circle"></i> My Profile</h1>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

      

        <div class="profile-grid">
            <!-- Profile Information -->
            <div class="profile-card">
                <div class="profile-avatar">
                    <div class="avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <h3><?php echo htmlspecialchars($user['full_name'] ?? 'User'); ?></h3>
                </div>
                <div class="profile-info">
                    <div class="info-item">
                        <span class="info-label">Email:</span>
                        <span class="info-value"><?php echo htmlspecialchars($user['email'] ?? ''); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Phone:</span>
                        <span class="info-value"><?php echo htmlspecialchars($user['phone'] ?? ''); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Vehicle:</span>
                        <span class="info-value"><?php echo htmlspecialchars($user['vehicle_model'] ?? ''); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Member Since:</span>
                        <span class="info-value"><?php echo isset($user['created_at']) ? date('M Y', strtotime($user['created_at'])) : ''; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Status:</span>
                        <span class="info-value">
                            <span style="color: #28a745; font-weight: 600;">Active</span>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Update Profile Form -->
            <div class="form-section">
                <h3><i class="fas fa-edit"></i> Update Profile Information</h3>
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Full Name</label>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" disabled>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="vehicle_model">Vehicle Model</label>
                            <input type="text" id="vehicle_model" name="vehicle_model" value="<?php echo htmlspecialchars($user['vehicle_model'] ?? ''); ?>" required>
                        </div>
                    </div>
              
                    <button type="submit" name="update_profile" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Profile
                    </button>
                </form>
            </div>
        </div>

     
    
    </div>

    <script>
        function toggleUserMenu() {
            const dropdown = document.getElementById('userDropdown');
            dropdown.classList.toggle('show');
        }

        document.addEventListener('click', function(event) {
            const userMenu = document.querySelector('.user-menu');
            const dropdown = document.getElementById('userDropdown');
            if (userMenu && dropdown && !userMenu.contains(event.target)) {
                dropdown.classList.remove('show');
            }
        });
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (newPassword !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.style.display = 'none';
                }, 300);
            });
        }, 5000);
    </script>
</body>
</html> 
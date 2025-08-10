<?php
session_start();
require_once 'includes/functions.php';
require_once 'includes/dummy_data.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect_with_message('login.php', 'Please login to access your profile.', 'error');
}

$user_id = $_SESSION['user_id'];
$message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profile'])) {
        // Simulate profile update
        $message = 'Profile updated successfully!';
    } elseif (isset($_POST['change_password'])) {
        // Simulate password change
        $message = 'Password changed successfully!';
    } elseif (isset($_POST['delete_account'])) {
        // Simulate account deletion
        session_destroy();
        redirect_with_message('index.php', 'Account deleted successfully.', 'success');
    }
}

// Get user data from dummy data
$users = get_dummy_users();
$current_user = null;
foreach ($users as $user) {
    if ($user['id'] == $user_id) {
        $current_user = $user;
        break;
    }
}

if (!$current_user) {
    redirect_with_message('login.php', 'User not found.', 'error');
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .header h1 {
            color: #333;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-links {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .nav-links a {
            text-decoration: none;
            color: #667eea;
            padding: 8px 16px;
            border-radius: 25px;
            background: rgba(102, 126, 234, 0.1);
            transition: all 0.3s ease;
        }

        .nav-links a:hover {
            background: #667eea;
            color: white;
        }

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
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-user-circle"></i> My Profile</h1>
            <div class="nav-links">
                <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                <a href="request-service.php"><i class="fas fa-plus"></i> Request Service</a>
                <a href="track-service.php"><i class="fas fa-map-marker-alt"></i> Track Service</a>
                <a href="service-history.php"><i class="fas fa-history"></i> Service History</a>
                <a href="payments.php"><i class="fas fa-credit-card"></i> Payments</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- User Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number">12</div>
                <div class="stat-label">Total Services</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">8</div>
                <div class="stat-label">Completed</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">2</div>
                <div class="stat-label">In Progress</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">$1,250</div>
                <div class="stat-label">Total Spent</div>
            </div>
        </div>

        <div class="profile-grid">
            <!-- Profile Information -->
            <div class="profile-card">
                <div class="profile-avatar">
                    <div class="avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <h3><?php echo htmlspecialchars($current_user['name']); ?></h3>
                </div>
                <div class="profile-info">
                    <div class="info-item">
                        <span class="info-label">Email:</span>
                        <span class="info-value"><?php echo htmlspecialchars($current_user['email']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Phone:</span>
                        <span class="info-value"><?php echo htmlspecialchars($current_user['phone']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Vehicle:</span>
                        <span class="info-value"><?php echo htmlspecialchars($current_user['vehicle_model']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Member Since:</span>
                        <span class="info-value"><?php echo date('M Y', strtotime($current_user['created_at'])); ?></span>
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
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($current_user['name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($current_user['email']); ?>" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($current_user['phone']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="vehicle_model">Vehicle Model</label>
                            <input type="text" id="vehicle_model" name="vehicle_model" value="<?php echo htmlspecialchars($current_user['vehicle_model']); ?>" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" rows="3" placeholder="Enter your full address">123 Main Street, City, State 12345</textarea>
                    </div>
                    <button type="submit" name="update_profile" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Profile
                    </button>
                </form>
            </div>
        </div>

        <!-- Change Password -->
        <div class="form-section">
            <h3><i class="fas fa-lock"></i> Change Password</h3>
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <button type="submit" name="change_password" class="btn btn-primary">
                    <i class="fas fa-key"></i> Change Password
                </button>
            </form>
        </div>

        <!-- Account Settings -->
        <div class="form-section">
            <h3><i class="fas fa-cog"></i> Account Settings</h3>
            <div class="form-row">
                <div class="form-group">
                    <label for="notifications">Email Notifications</label>
                    <select id="notifications" name="notifications">
                        <option value="all">All Notifications</option>
                        <option value="important">Important Only</option>
                        <option value="none">No Notifications</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="language">Language</label>
                    <select id="language" name="language">
                        <option value="en">English</option>
                        <option value="es">Spanish</option>
                        <option value="fr">French</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-secondary">
                <i class="fas fa-save"></i> Save Settings
            </button>
        </div>

        <!-- Danger Zone -->
        <div class="form-section">
            <h3><i class="fas fa-exclamation-triangle"></i> Danger Zone</h3>
            <p style="color: #666; margin-bottom: 20px;">
                Once you delete your account, there is no going back. Please be certain.
            </p>
            <form method="POST" onsubmit="return confirm('Are you sure you want to delete your account? This action cannot be undone.');">
                <button type="submit" name="delete_account" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Delete Account
                </button>
            </form>
        </div>
    </div>

    <script>
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
<?php
session_start();
require_once 'config/config.php';
require_once 'includes/functions.php';

// Simple session check
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header('Location: login.php');
    exit;
}

$userManager = new UserManager();
$serviceRequestManager = new ServiceRequestManager();

try {
    $user = $userManager->getUserById($_SESSION['user_id']);
    $userRequests = $serviceRequestManager->getRequestsByUser($_SESSION['user_id']);
    $recentRequests = array_slice($userRequests, 0, 5); // Get last 5 requests
} catch (Exception $e) {
    $user = null;
    $userRequests = [];
    $recentRequests = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - EV Mobile Power & Service Station</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            color: #333;
        }

        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .navbar-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
        }

        .navbar-brand {
            font-size: 1.5rem;
            font-weight: bold;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .navbar-nav {
            display: flex;
            list-style: none;
            gap: 2rem;
            align-items: center;
        }

        .navbar-nav a {
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            padding: 0.5rem 1rem;
            border-radius: 5px;
        }

        .navbar-nav a:hover {
            background: rgba(255,255,255,0.2);
            transform: translateY(-2px);
        }

        .user-menu {
            position: relative;
        }

        .user-menu-toggle {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .user-menu-toggle:hover {
            background: rgba(255,255,255,0.3);
        }

        .user-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            min-width: 200px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .user-dropdown.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .user-dropdown a {
            color: #333;
            padding: 1rem;
            display: block;
            text-decoration: none;
            border-bottom: 1px solid #f0f0f0;
            transition: background 0.3s ease;
        }

        .user-dropdown a:last-child {
            border-bottom: none;
        }

        .user-dropdown a:hover {
            background: #f8f9fa;
            color: #667eea;
        }

        .main-content {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .welcome-section {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            text-align: center;
        }

        .welcome-section h1 {
            color: #333;
            margin-bottom: 1rem;
            font-size: 2rem;
        }

        .welcome-section p {
            color: #666;
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .action-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
        }

        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .action-card i {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 1rem;
        }

        .action-card h3 {
            color: #333;
            margin-bottom: 0.5rem;
        }

        .action-card p {
            color: #666;
            font-size: 0.9rem;
        }

        .stats-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }

        .stat-card .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 0.5rem;
        }

        .stat-card .stat-label {
            color: #666;
            font-size: 0.9rem;
        }

        .recent-requests {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }

        .recent-requests h2 {
            color: #333;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .request-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border: 1px solid #f0f0f0;
            border-radius: 10px;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }

        .request-item:hover {
            border-color: #667eea;
            box-shadow: 0 2px 10px rgba(102, 126, 234, 0.1);
        }

        .request-info h4 {
            color: #333;
            margin-bottom: 0.5rem;
        }

        .request-info p {
            color: #666;
            font-size: 0.9rem;
            margin: 0;
        }

        .request-status {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            text-transform: uppercase;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-assigned { background: #cce5ff; color: #004085; }
        .status-in_progress { background: #d1ecf1; color: #0c5460; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }

        .urgency-high { border-left: 4px solid #dc3545; }
        .urgency-medium { border-left: 4px solid #ffc107; }
        .urgency-low { border-left: 4px solid #28a745; }
        .urgency-emergency { border-left: 4px solid #dc3545; background: #f8d7da; }

        .no-requests {
            text-align: center;
            padding: 2rem;
            color: #666;
        }

        .no-requests i {
            font-size: 3rem;
            color: #ddd;
            margin-bottom: 1rem;
        }

        .view-all-btn {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 0.75rem 1.5rem;
            text-decoration: none;
            border-radius: 25px;
            transition: all 0.3s ease;
            margin-top: 1rem;
        }

        .view-all-btn:hover {
            background: #5a6fd8;
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .navbar-content {
                flex-direction: column;
                gap: 1rem;
            }

            .navbar-nav {
                gap: 1rem;
            }

            .main-content {
                padding: 0 1rem;
                margin: 1rem auto;
            }

            .quick-actions {
                grid-template-columns: 1fr;
            }

            .stats-section {
                grid-template-columns: repeat(2, 1fr);
            }

            .request-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
        }

        .logout-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            background: #c82333;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-content">
            <a href="index.php" class="navbar-brand">
                <i class="fas fa-bolt"></i> EV Mobile Station
            </a>
            
            <ul class="navbar-nav">
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="request-service.php"><i class="fas fa-plus-circle"></i> Request Service</a></li>
                <li><a href="service-history.php"><i class="fas fa-history"></i> History</a></li>
                <li><a href="track-service.php"><i class="fas fa-map-marker-alt"></i> Track Service</a></li>
                <li><a href="support.php"><i class="fas fa-headset"></i> Support</a></li>
                
                <li class="user-menu">
                    <button class="user-menu-toggle" onclick="toggleUserMenu()">
                        <i class="fas fa-user-circle"></i>
                        <?php echo htmlspecialchars($user['username'] ?? 'User'); ?>
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
            <h1>Welcome back, <?php echo htmlspecialchars($user['full_name'] ?? 'User'); ?>! 👋</h1>
            <p>Your EV is in good hands. Here's what's happening with your service requests.</p>
        </div>

        <div class="quick-actions">
            <a href="request-service.php" class="action-card">
                <i class="fas fa-plus-circle"></i>
                <h3>Request Service</h3>
                <p>Need help? Request roadside assistance</p>
            </a>
            
            <a href="track-service.php" class="action-card">
                <i class="fas fa-map-marker-alt"></i>
                <h3>Track Service</h3>
                <p>See where your service vehicle is</p>
            </a>
            
            <a href="support.php" class="action-card">
                <i class="fas fa-headset"></i>
                <h3>Get Support</h3>
                <p>Contact our support team</p>
            </a>
            
            <a href="payments.php" class="action-card">
                <i class="fas fa-credit-card"></i>
                <h3>Payments</h3>
                <p>View and manage payments</p>
            </a>
        </div>

        <div class="stats-section">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($userRequests); ?></div>
                <div class="stat-label">Total Requests</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo count(array_filter($userRequests, function($r) { return $r['status'] === 'pending'; })); ?></div>
                <div class="stat-label">Pending</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo count(array_filter($userRequests, function($r) { return $r['status'] === 'in_progress'; })); ?></div>
                <div class="stat-label">In Progress</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo count(array_filter($userRequests, function($r) { return $r['status'] === 'completed'; })); ?></div>
                <div class="stat-label">Completed</div>
            </div>
        </div>

        <div class="recent-requests">
            <h2><i class="fas fa-clock"></i> Recent Service Requests</h2>
            
            <?php if (empty($recentRequests)): ?>
                <div class="no-requests">
                    <i class="fas fa-clipboard-list"></i>
                    <h3>No service requests yet</h3>
                    <p>When you request roadside assistance, it will appear here.</p>
                    <a href="request-service.php" class="view-all-btn">
                        <i class="fas fa-plus"></i> Request Your First Service
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($recentRequests as $request): ?>
                    <div class="request-item urgency-<?php echo $request['urgency_level']; ?>">
                        <div class="request-info">
                            <h4><?php echo htmlspecialchars(ucfirst($request['request_type'])); ?> Service</h4>
                            <p>
                                <strong>Status:</strong> 
                                <span class="request-status status-<?php echo $request['status']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $request['status'])); ?>
                                </span>
                            </p>
                            <p>
                                <strong>Urgency:</strong> 
                                <?php echo ucfirst($request['urgency_level']); ?>
                            </p>
                            <p>
                                <strong>Created:</strong> 
                                <?php echo UtilityFunctions::formatDateTime($request['created_at']); ?>
                            </p>
                            <?php if ($request['description']): ?>
                                <p><strong>Description:</strong> <?php echo htmlspecialchars($request['description']); ?></p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="request-actions">
                            <a href="track-service.php?id=<?php echo $request['id']; ?>" class="view-all-btn">
                                <i class="fas fa-eye"></i> View Details
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <div style="text-align: center; margin-top: 1.5rem;">
                    <a href="service-history.php" class="view-all-btn">
                        <i class="fas fa-history"></i> View All Requests
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleUserMenu() {
            const dropdown = document.getElementById('userDropdown');
            dropdown.classList.toggle('show');
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const userMenu = document.querySelector('.user-menu');
            const dropdown = document.getElementById('userDropdown');
            
            if (!userMenu.contains(event.target)) {
                dropdown.classList.remove('show');
            }
        });

        // Add smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Add loading animation for action cards
        document.querySelectorAll('.action-card').forEach(card => {
            card.addEventListener('click', function() {
                this.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 150);
            });
        });
    </script>
</body>
</html> 
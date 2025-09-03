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
            background: radial-gradient(1000px 500px at 10% -10%, #e6e9ff 0%, #ffffff 40%),
                        radial-gradient(800px 400px at 110% 10%, #f5e6ff 0%, #ffffff 50%);
            color: #333;
        }

        .navbar {
            position: sticky;
            top: 0;
            background: linear-gradient(135deg, rgba(102,126,234,0.9) 0%, rgba(118,75,162,0.9) 100%);
            backdrop-filter: blur(8px);
            color: white;
            padding: 1rem 2rem;
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
            z-index: 1000;
        }

        .navbar-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            margin: 0;
        }

        .navbar-brand {
            font-size: 1.5rem;
            font-weight: bold;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.6rem;
            letter-spacing: 0.3px;
        }

        .brand-badge {
            font-size: 0.75rem;
            background: rgba(255,255,255,0.2);
            padding: 0.2rem 0.5rem;
            border-radius: 999px;
        }

        .navbar-nav {
            display: flex;
            list-style: none;
            gap: 1rem;
            align-items: center;
        }

        .navbar-nav a {
            color: white;
            text-decoration: none;
            transition: all 0.25s ease;
            padding: 0.5rem 1rem;
            border-radius: 8px;
        }

        .navbar-nav a:hover {
            background: rgba(255,255,255,0.18);
            transform: translateY(-2px);
        }

        .user-menu { position: relative; }

        .user-menu-toggle {
            background: rgba(255,255,255,0.18);
            border: 1px solid rgba(255,255,255,0.25);
            color: white;
            padding: 0.6rem 0.9rem;
            border-radius: 10px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.25s ease;
        }
        .user-menu-toggle:hover { background: rgba(255,255,255,0.28); }

        .user-dropdown {
            position: absolute;
            top: 110%;
            right: 0;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 18px 50px rgba(0,0,0,0.12);
            min-width: 220px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-8px);
            transition: all 0.25s ease;
            overflow: hidden;
        }
        .user-dropdown.show { opacity: 1; visibility: visible; transform: translateY(0); }
        .user-dropdown a {
            color: #333; padding: 0.9rem 1rem; display: block; text-decoration: none; border-bottom: 1px solid #f0f0f0; transition: background 0.25s ease;
        }
        .user-dropdown a:last-child { border-bottom: none; }
        .user-dropdown a:hover { background: #f6f7ff; color: #667eea; }

        .main-content { width: 100%; max-width: none; margin: 1rem 0; padding: 0 1rem; }

        .welcome-section {
            position: relative;
            background: linear-gradient(135deg, #ffffff 0%, #f6f7ff 100%);
            border-radius: 20px;
            padding: 2rem;
            min-height: 180px;
            margin-bottom: 2rem;
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.08);
            overflow: hidden;
        }
        .welcome-section:after {
            content: '';
            position: absolute;
            top: -40px; right: -40px;
            width: 180px; height: 180px;
            background: radial-gradient(circle at 30% 30%, rgba(102,126,234,0.25), rgba(118,75,162,0.15));
            filter: blur(8px);
            border-radius: 50%;
        }
        .welcome-section h1 { color: #222; margin-bottom: 0.75rem; font-size: 2rem; }
        .welcome-section p { color: #666; font-size: 1.05rem; }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .action-card {
            position: relative;
            background: #ffffff;
            border-radius: 16px;
            padding: 1.6rem;
            min-height: 180px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.06);
            transition: transform 0.25s ease, box-shadow 0.25s ease;
            text-decoration: none;
            color: inherit;
            border: 1px solid #eef0ff;
            overflow: hidden;
        }
        .action-card:before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 16px;
            padding: 1px;
            background: linear-gradient(135deg, rgba(102,126,234,0.35), rgba(118,75,162,0.35));
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor; mask-composite: exclude;
        }
        .action-card:hover { transform: translateY(-5px); box-shadow: 0 16px 40px rgba(102, 126, 234, 0.18); }
        .action-card i { font-size: 2.6rem; color: #667eea; margin-bottom: 0.75rem; }
        .action-card h3 { color: #222; margin-bottom: 0.4rem; }
        .action-card p { color: #666; font-size: 0.95rem; }

        .stats-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            position: relative;
            background: linear-gradient(180deg, #ffffff 0%, #f8f9ff 100%);
            border-radius: 16px;
            padding: 1.2rem 1.2rem 1.2rem 1.2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            border: 1px solid #eef0ff;
        }
        .stat-top {
            display: flex; align-items: center; justify-content: center; gap: 0.6rem; margin-bottom: 0.4rem;
        }
        .stat-top i { color: #667eea; }
        .stat-number { font-size: 2rem; font-weight: 700; color: #222; text-align: center; }
        .stat-label { color: #666; font-size: 0.9rem; text-align: center; }

        .recent-requests {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            min-height: 400px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.06);
            border: 1px solid #eef0ff;
        }
        .recent-requests h2 { color: #222; margin-bottom: 1.2rem; display: flex; align-items: center; gap: 0.6rem; }

        .request-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border: 1px solid #eef0ff;
            border-radius: 12px;
            margin-bottom: 0.9rem;
            transition: all 0.25s ease;
            background: #fff;
            position: relative;
        }
        .request-item:hover { border-color: #cfd5ff; box-shadow: 0 8px 22px rgba(102, 126, 234, 0.12); }
        .request-item:before {
            content: '';
            position: absolute;
            left: 0; top: 0; bottom: 0;
            width: 4px;
            border-radius: 12px 0 0 12px;
            background: linear-gradient(180deg, #667eea, #764ba2);
        }

        .request-info h4 { color: #222; margin-bottom: 0.35rem; }
        .request-info p { color: #666; font-size: 0.92rem; margin: 0.08rem 0; }

        .request-status {
            padding: 0.35rem 0.75rem;
            border-radius: 999px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            border: 1px solid transparent;
        }
        .status-pending { background: #fff7df; color: #8a6d00; border-color: #ffe39a; }
        .status-assigned { background: #e9f2ff; color: #0b4ea7; border-color: #cfe0ff; }
        .status-in_progress { background: #e8f7fb; color: #0c5460; border-color: #cdeef6; }
        .status-completed { background: #e9f8ee; color: #1a6d38; border-color: #c5efd4; }
        .status-cancelled { background: #fde8ea; color: #8a2530; border-color: #f8c7cd; }

        .urgency-high { box-shadow: inset 0 0 0 2px #dc354530; }
        .urgency-medium { box-shadow: inset 0 0 0 2px #ffc10730; }
        .urgency-low { box-shadow: inset 0 0 0 2px #28a74530; }
        .urgency-emergency { box-shadow: inset 0 0 0 2px #dc354560; background: #fff0f0; }

        .no-requests { text-align: center; padding: 2rem; color: #666; }
        .no-requests i { font-size: 3rem; color: #d9dcff; margin-bottom: 1rem; }

        .view-all-btn {
            display: inline-block;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 0.75rem 1.5rem;
            text-decoration: none;
            border-radius: 25px;
            transition: all 0.25s ease;
            margin-top: 1rem;
        }
        .view-all-btn:hover { filter: brightness(1.05); transform: translateY(-2px); }

        @media (max-width: 768px) {
            .navbar-content { flex-direction: column; gap: 1rem; }
            .navbar-nav { gap: 0.6rem; flex-wrap: wrap; justify-content: center; }
            .main-content { padding: 0 1rem; margin: 1rem auto; }
            .quick-actions { grid-template-columns: 1fr; }
            .stats-section { grid-template-columns: repeat(2, 1fr); }
            .request-item { flex-direction: column; align-items: flex-start; gap: 0.8rem; }
        }

        .logout-btn { background: #dc3545; color: white; border: none; padding: 0.5rem 1rem; border-radius: 8px; cursor: pointer; transition: all 0.25s ease; }
        .logout-btn:hover { background: #c82333; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-content">
            <a href="index.php" class="navbar-brand">
                <i class="fas fa-bolt"></i> EV Mobile Station
                <span class="brand-badge">Dashboard</span>
            </a>
            
            <ul class="navbar-nav">
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="request-service.php"><i class="fas fa-plus-circle"></i> Request Service</a></li>
                <li><a href="service-history.php"><i class="fas fa-history"></i> History</a></li>
                <li><a href="vehicles.php"><i class="fas fa-car"></i> My Vehicles</a></li>
                
                <a href="spares.php"><i class="fas fa-gears"></i> Spare Parts</a>
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
                <div class="stat-top"><i class="fas fa-list"></i><span>Total Requests</span></div>
                <div class="stat-number"><?php echo count($userRequests); ?></div>
                <div class="stat-label">All time</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-top"><i class="fas fa-hourglass-half"></i><span>Pending</span></div>
                <div class="stat-number"><?php echo count(array_filter($userRequests, function($r) { return $r['status'] === 'pending'; })); ?></div>
                <div class="stat-label">Awaiting assignment</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-top"><i class="fas fa-spinner"></i><span>In Progress</span></div>
                <div class="stat-number"><?php echo count(array_filter($userRequests, function($r) { return $r['status'] === 'in_progress'; })); ?></div>
                <div class="stat-label">Active services</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-top"><i class="fas fa-check-circle"></i><span>Completed</span></div>
                <div class="stat-number"><?php echo count(array_filter($userRequests, function($r) { return $r['status'] === 'completed'; })); ?></div>
                <div class="stat-label">Successfully resolved</div>
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
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });

        // Subtle click animation for cards
        document.querySelectorAll('.action-card').forEach(card => {
            card.addEventListener('mousedown', function() {
                this.style.transform = 'scale(0.98)';
            });
            card.addEventListener('mouseup', function() {
                this.style.transform = '';
            });
        });
    </script>
</body>
</html> 
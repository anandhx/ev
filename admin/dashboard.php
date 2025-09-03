<?php
session_start();
/**
 * Admin Dashboard - EV Mobile Station
 * Main admin interface with different UIs for each module
 */

require_once '../config/config.php';
require_once '../includes/functions.php';

// Simple admin session check
if (!isset($_SESSION['admin_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$adminManager = new AdminManager();
$stats = $adminManager->getDashboardStats();

$current_page = 'dashboard';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - EV Mobile Station</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
            --dark-color: #343a40;
            --light-color: #f8f9fa;
        }
        
        body {
            background: radial-gradient(1200px 600px at 10% 10%, rgba(102,126,234,0.15), transparent),
                        radial-gradient(1200px 600px at 90% 0%, rgba(118,75,162,0.18), transparent),
                        #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .sidebar {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            box-shadow: 2px 0 20px rgba(0,0,0,0.12);
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 240px;
            overflow: hidden;
        }
        .sidebar:hover { overflow-y: auto; }
        
        .sidebar-header {
            padding: 2rem 1rem;
            text-align: center;
            color: white;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-nav { padding: 1rem 0; }
        
        .nav-link {
            color: rgba(255,255,255,0.85);
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            transition: all 0.25s ease;
            margin: 0.15rem 0.5rem;
            display: block;
            text-decoration: none;
        }
        
        .nav-link:hover, .nav-link.active {
            color: white;
            background-color: rgba(255,255,255,0.12);
            transform: translateX(4px);
        }
        
        .nav-link i { width: 20px; margin-right: 10px; }
        
        .main-content { padding: 2rem; }
        @media (min-width: 992px) {
            .main-with-fixed-sidebar { margin-left: 240px; width: calc(100% - 240px); }
        }
        
        /* Glass cards */
        .glass {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: saturate(180%) blur(12px);
            -webkit-backdrop-filter: saturate(180%) blur(12px);
            border-radius: 16px;
            border: 1px solid rgba(255,255,255,0.3);
            box-shadow: 0 10px 30px rgba(31, 38, 135, 0.12);
        }
        
        .stats-card { padding: 1.5rem; transition: transform 0.25s ease, box-shadow 0.25s ease; border: none; }
        .stats-card:hover { transform: translateY(-4px); box-shadow: 0 16px 40px rgba(0,0,0,0.12); }
        .stats-icon { width: 56px; height: 56px; border-radius: 14px; display:flex; align-items:center; justify-content:center; font-size:1.4rem; color:#fff; box-shadow: 0 8px 20px rgba(0,0,0,0.15); }
        .stats-number { font-size: 2.1rem; font-weight: 800; margin: 0; letter-spacing: 0.3px; }
        .stats-label { color: #6c757d; font-size: 0.9rem; margin: 0; }
        
        .chart-container { padding: 2.5rem; height: 340px; overflow: hidden; }
        .recent-requests { padding: 1.5rem; }
        .request-item { padding: 1rem; border-bottom: 1px solid #eef0f4; transition: background-color 0.2s ease; }
        .request-item:hover { background-color: #f8faff; }
        .status-badge { padding: 0.35rem 0.8rem; border-radius: 999px; font-size: 0.78rem; font-weight: 700; letter-spacing: 0.3px; }
        .urgency-badge { padding: 0.3rem 0.6rem; border-radius: 999px; font-size: 0.72rem; font-weight: 700; }
        
        .top-bar { padding: 1rem 1.5rem; }
        .admin-avatar { width: 42px; height: 42px; border-radius: 12px; background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); display:flex; align-items:center; justify-content:center; color:#fff; font-weight:700; }
        
        /* Buttons */
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; }
        .btn-outline-primary { border-color: #667eea; color: #667eea; }
        .btn-outline-primary:hover { background: #667eea; color: #fff; }
        
        /* Micro-interactions */
        .fade-in { animation: fadeIn 0.35s ease both; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(6px);} to { opacity: 1; transform: none; } }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="px-0">
                <div class="sidebar">
                    <div class="sidebar-header">
                        <h4><i class="fas fa-charging-station me-2"></i>EV Station</h4>
                        <small>Admin Panel</small>
                    </div>
                    
                    <nav class="sidebar-nav">
                        <a href="dashboard.php" class="nav-link <?php echo $current_page === 'dashboard' ? 'active' : ''; ?>">
                            <i class="fas fa-tachometer-alt"></i>Dashboard
                        </a>
                        <a href="requests.php" class="nav-link">
                            <i class="fas fa-clipboard-list"></i>Service Requests
                        </a>
                        <a href="vehicles.php" class="nav-link">
                            <i class="fas fa-truck"></i>Service Vehicles
                        </a>
                        <a href="technicians.php" class="nav-link">
                            <i class="fas fa-user-cog"></i>Technicians
                        </a>
                        <a href="users.php" class="nav-link">
                            <i class="fas fa-users"></i>Users
                        </a>
                        <a href="payments.php" class="nav-link">
                            <i class="fas fa-credit-card"></i>Payments
                        </a>
                        <a href="emergency.php" class="nav-link">
                            <i class="fas fa-exclamation-triangle"></i>Emergency
                        </a>
                        <a href="../logout.php" class="nav-link text-danger">
                            <i class="fas fa-sign-out-alt"></i>Logout
                        </a>
                    </nav>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="main-with-fixed-sidebar">
                <div class="main-content">
                    <!-- Top Bar -->
                    <div class="top-bar glass fade-in">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h2 class="mb-0">Dashboard Overview</h2>
                                <p class="text-muted mb-0">Welcome back, <?php echo htmlspecialchars($_SESSION['admin_full_name'] ?? 'Admin'); ?></p>
                            </div>
                            <div class="col-md-6 text-end">
                                <div class="admin-info justify-content-end">
                                    <div class="text-end">
                                        <div class="fw-bold"><?php echo htmlspecialchars($_SESSION['admin_role'] ?? 'Admin'); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($_SESSION['admin_username'] ?? ''); ?></small>
                                    </div>
                                    <div class="">
                                        <?php echo strtoupper(substr($_SESSION['admin_full_name'] ?? 'A', 0, 1)); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <br>
                    <br> 
                    <!-- Statistics Cards -->
                    <div class="row mb-4 fade-in">
                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="stats-card glass">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        <div class="stats-icon" style="background: linear-gradient(135deg,#667eea,#764ba2);">
                                            <i class="fas fa-clipboard-list"></i>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="stats-number text-primary"><?php echo $stats['total_requests'] ?? 0; ?></div>
                                        <div class="stats-label">Total Requests</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="stats-card glass">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        <div class="stats-icon" style="background: linear-gradient(135deg,#ffc107,#ffd75e);">
                                            <i class="fas fa-clock"></i>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="stats-number" style="color:#e0a800; "><?php echo $stats['pending_requests'] ?? 0; ?></div>
                                        <div class="stats-label">Pending Requests</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="stats-card glass">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        <div class="stats-icon" style="background: linear-gradient(135deg,#17a2b8,#67d7e0);">
                                            <i class="fas fa-tools"></i>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="stats-number" style="color:#17a2b8; "><?php echo $stats['active_requests'] ?? 0; ?></div>
                                        <div class="stats-label">Active Requests</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="stats-card glass">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        <div class="stats-icon" style="background: linear-gradient(135deg,#28a745,#7ae690);">
                                            <i class="fas fa-truck"></i>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="stats-number" style="color:#28a745; "><?php echo $stats['available_vehicles'] ?? 0; ?></div>
                                        <div class="stats-label">Available Vehicles</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Charts Row -->
                    <div class="row mb-4 fade-in">
                        <div class="col-lg-8">
                            <div class="chart-container glass">
                                <h5 class="mb-3">Request Trends</h5>
                                <canvas id="requestChart"></canvas>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="chart-container glass">
                                <h5 class="mb-3">Request Types</h5>
                                <canvas id="typeChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <br>
                    
                    <!-- Recent Requests -->
                    <div class="recent-requests glass fade-in">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Recent Service Requests</h5>
                            <a href="requests.php" class="btn btn-primary btn-sm">View All</a>
                        </div>
                        
                        <?php
                        $recent_requests = $adminManager->getAllRequests(null, 5);
                        if (!empty($recent_requests)):
                        ?>
                            <?php foreach ($recent_requests as $request): ?>
                                <div class="request-item">
                                    <div class="row align-items-center">
                                        <div class="col-md-3">
                                            <div class="d-flex align-items-center">
                                                <div class="me-3">
                                                    <i class="fas fa-user-circle fa-2x text-muted"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-bold"><?php echo htmlspecialchars($request['user_name']); ?></div>
                                                    <small class="text-muted"><?php echo htmlspecialchars($request['user_phone']); ?></small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <span class="badge bg-<?php echo UtilityFunctions::getStatusColor($request['status']); ?> status-badge">
                                                <?php echo ucfirst(str_replace('_', ' ', $request['status'])); ?>
                                            </span>
                                        </div>
                                        <div class="col-md-2">
                                            <span class="badge bg-<?php echo UtilityFunctions::getUrgencyColor($request['urgency_level']); ?> urgency-badge">
                                                <?php echo ucfirst($request['urgency_level']); ?>
                                            </span>
                                        </div>
                                        <div class="col-md-2">
                                            <small class="text-muted"><?php echo UtilityFunctions::formatDateTime($request['created_at']); ?></small>
                                        </div>
                                        <div class="col-md-1">
                                            <a href="assign-request.php?id=<?php echo $request['id']; ?>" class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No service requests found</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>
    <script>
        // Request Trends Chart
        const requestCtx = document.getElementById('requestChart').getContext('2d');
        new Chart(requestCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Service Requests',
                    data: [12, 19, 15, 25, 22, 30],
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.15)',
                    tension: 0.4,
                    fill: true,
                    pointRadius: 3,
                    pointBackgroundColor: '#667eea'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } },
                height: 280
            }
        });

        // Request Types Chart
        const typeCtx = document.getElementById('typeChart').getContext('2d');
        new Chart(typeCtx, {
            type: 'doughnut',
            data: {
                labels: ['Charging', 'Mechanical', 'Both'],
                datasets: [{
                    data: [45, 30, 25],
                    backgroundColor: ['#667eea', '#764ba2', '#f093fb'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } },
                height: 260
            }
        });
    </script>
</body>
</html> 
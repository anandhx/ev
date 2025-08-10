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
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .sidebar {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar-header {
            padding: 2rem 1rem;
            text-align: center;
            color: white;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-nav {
            padding: 1rem 0;
        }
        
        .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 0.75rem 1.5rem;
            border-radius: 0;
            transition: all 0.3s ease;
        }
        
        .nav-link:hover, .nav-link.active {
            color: white;
            background-color: rgba(255,255,255,0.1);
            transform: translateX(5px);
        }
        
        .nav-link i {
            width: 20px;
            margin-right: 10px;
        }
        
        .main-content {
            padding: 2rem;
        }
        
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s ease;
            border: none;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
        }
        
        .stats-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
        }
        
        .stats-label {
            color: #6c757d;
            font-size: 0.9rem;
            margin: 0;
        }
        
        .chart-container {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
            height: 300px;
            overflow: hidden;
        }
        
        .chart-container canvas {
            max-height: 250px !important;
        }
        
        .recent-requests {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .request-item {
            padding: 1rem;
            border-bottom: 1px solid #eee;
            transition: background-color 0.3s ease;
        }
        
        .request-item:hover {
            background-color: #f8f9fa;
        }
        
        .request-item:last-child {
            border-bottom: none;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .urgency-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .top-bar {
            background: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            border-radius: 15px;
        }
        
        .admin-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .admin-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0">
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
            <div class="col-md-9 col-lg-10">
                <div class="main-content">
                    <!-- Top Bar -->
                    <div class="top-bar">
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
                                    <div class="admin-avatar">
                                        <?php echo strtoupper(substr($_SESSION['admin_full_name'] ?? 'A', 0, 1)); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="stats-card">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        <div class="stats-icon bg-primary">
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
                            <div class="stats-card">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        <div class="stats-icon bg-warning">
                                            <i class="fas fa-clock"></i>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="stats-number text-warning"><?php echo $stats['pending_requests'] ?? 0; ?></div>
                                        <div class="stats-label">Pending Requests</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="stats-card">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        <div class="stats-icon bg-info">
                                            <i class="fas fa-tools"></i>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="stats-number text-info"><?php echo $stats['active_requests'] ?? 0; ?></div>
                                        <div class="stats-label">Active Requests</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="stats-card">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        <div class="stats-icon bg-success">
                                            <i class="fas fa-truck"></i>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="stats-number text-success"><?php echo $stats['available_vehicles'] ?? 0; ?></div>
                                        <div class="stats-label">Available Vehicles</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Charts Row -->
                    <div class="row mb-4">
                        <div class="col-lg-8">
                            <div class="chart-container">
                                <h5 class="mb-3">Request Trends</h5>
                                <canvas id="requestChart"></canvas>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="chart-container">
                                <h5 class="mb-3">Request Types</h5>
                                <canvas id="typeChart"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Requests -->
                    <div class="recent-requests">
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
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                height: 250
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
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                },
                height: 250
            }
        });
    </script>
</body>
</html> 
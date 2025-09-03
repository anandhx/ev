<?php
session_start();
/**
 * Admin Service Requests Management - EV Mobile Station
 * Manage and assign service requests with modern UI
 */

require_once '../includes/functions.php';

// Simple admin session check
if (!isset($_SESSION['admin_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$adminManager = new AdminManager();
$serviceRequestManager = new ServiceRequestManager();
$vehicleManager = new VehicleManager();
$technicianManager = new TechnicianManager();

$current_page = 'requests';

// Handle status filter
$status_filter = $_GET['status'] ?? '';
$search_query = $_GET['search'] ?? '';

// Get requests based on filters
$requests = $adminManager->getAllRequests($status_filter, 100);

// Filter by search if provided
if (!empty($search_query)) {
    $requests = array_filter($requests, function($request) use ($search_query) {
        return stripos($request['user_name'], $search_query) !== false ||
               stripos($request['description'], $search_query) !== false ||
               stripos($request['user_phone'], $search_query) !== false;
    });
}

// Get available vehicles and technicians for assignment
$available_vehicles = $vehicleManager->getAvailableVehicles();
$available_technicians = $technicianManager->getAvailableTechnicians();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Requests - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
        }
        
        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .sidebar {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 240px;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            overflow: hidden;
            z-index: 1000;
        }
        .sidebar:hover { overflow-y: auto; }
        
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
        
        .main-content { padding: 2rem; }
        .main-with-fixed-sidebar { margin-left: 240px; width: calc(100% - 240px); }
        
        .top-bar {
            background: white;
            padding: 1.5rem 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            border-radius: 15px;
        }
        
        .filters-section {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }
        
        .request-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 1rem;
            transition: transform 0.3s ease;
            border: none;
        }
        
        .request-card:hover {
            transform: translateY(-2px);
        }
        
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .urgency-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .request-type-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: white;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .btn-action {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .stats-summary {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }
        
        .stat-item {
            background: white;
            padding: 1rem;
            border-radius: 10px;
            text-align: center;
            min-width: 120px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0;
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 0.8rem;
            margin: 0;
        }
    </style>
</head>
<body> 
       
            <!-- Sidebar -->
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h4><i class="fas fa-charging-station me-2"></i>EV Station</h4>
            <small>Admin Panel</small>
        </div>
        
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-link">
                <i class="fas fa-tachometer-alt"></i>Dashboard
            </a>
            <a href="requests.php" class="nav-link active">
                <i class="fas fa-clipboard-list"></i>Service Requests
            </a>
            <a href="vehicles.php" class="nav-link">
                <i class="fas fa-truck"></i>Service Vehicles
            </a>
            <a href="technicians.php" class="nav-link">
                <i class="fas fa-user-cog"></i>Technicians
            </a>
            <a href="users.php" class="nav-link <?php echo $current_page === 'users' ? 'active' : ''; ?>">
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
    
            <!-- Main Content --> 
            <div class="main-with-fixed-sidebar">
                <div class="main-content">
                    <!-- Top Bar -->
                    <div class="top-bar">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h2 class="mb-0">Service Requests Management</h2>
                                <p class="text-muted mb-0">Manage and assign service requests</p>
                            </div>
                         
                        </div>
                    </div>
                    <!-- Statistics Summary -->
                    <div class="stats-summary">
                        <?php
                        $total_requests = count($requests);
                        $pending_count = count(array_filter($requests, fn($r) => $r['status'] === 'pending'));
                        $assigned_count = count(array_filter($requests, fn($r) => $r['status'] === 'assigned'));
                        $in_progress_count = count(array_filter($requests, fn($r) => $r['status'] === 'in_progress'));
                        $completed_count = count(array_filter($requests, fn($r) => $r['status'] === 'completed'));
                        ?>
                        <div class="stat-item">
                            <div class="stat-number text-primary"><?php echo $total_requests; ?></div>
                            <div class="stat-label">Total</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number text-warning"><?php echo $pending_count; ?></div>
                            <div class="stat-label">Pending</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number text-info"><?php echo $assigned_count + $in_progress_count; ?></div>
                            <div class="stat-label">Active</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number text-success"><?php echo $completed_count; ?></div>
                            <div class="stat-label">Completed</div>
                        </div>
                    </div>
                    
                    <!-- Filters Section -->
                    <div class="filters-section">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label for="search" class="form-label">Search</label>
                                <input type="text" class="form-control" id="search" name="search" 
                                       value="<?php echo htmlspecialchars($search_query); ?>" 
                                       placeholder="Search by name, phone, or description">
                            </div>
                            <div class="col-md-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="">All Statuses</option>
                                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="assigned" <?php echo $status_filter === 'assigned' ? 'selected' : ''; ?>>Assigned</option>
                                    <option value="in_progress" <?php echo $status_filter === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                    <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search me-2"></i>Filter
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-grid">
                                    <a href="requests.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-2"></i>Clear
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Requests List -->
                    <?php if (!empty($requests)): ?>
                        <?php foreach ($requests as $request): ?>
                            <div class="request-card">
                                <div class="row align-items-center">
                                    <div class="col-md-1">
                                        <div class="request-type-icon bg-<?php echo $request['request_type'] === 'charging' ? 'primary' : ($request['request_type'] === 'mechanical' ? 'success' : 'info'); ?>">
                                            <i class="fas fa-<?php echo $request['request_type'] === 'charging' ? 'bolt' : ($request['request_type'] === 'mechanical' ? 'wrench' : 'cogs'); ?>"></i>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-3">
                                        <div class="d-flex align-items-center">
                                            <div class="me-3">
                                                <i class="fas fa-user-circle fa-2x text-muted"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold"><?php echo htmlspecialchars($request['user_name']); ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars($request['user_phone']); ?></small>
                                                <br>
                                                <small class="text-muted"><?php echo UtilityFunctions::formatDateTime($request['created_at']); ?></small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-2">
                                        <div class="mb-2">
                                            <span class="badge bg-<?php echo UtilityFunctions::getStatusColor($request['status']); ?> status-badge">
                                                <?php echo ucfirst(str_replace('_', ' ', $request['status'])); ?>
                                            </span>
                                        </div>
                                        <div>
                                            <span class="badge bg-<?php echo UtilityFunctions::getUrgencyColor($request['urgency_level']); ?> urgency-badge">
                                                <?php echo ucfirst($request['urgency_level']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-3">
                                        <div class="mb-1">
                                            <strong>Type:</strong> <?php echo ucfirst($request['request_type']); ?>
                                        </div>
                                        <?php if ($request['description']): ?>
                                            <div class="text-muted small">
                                                <?php echo htmlspecialchars(substr($request['description'], 0, 50)) . (strlen($request['description']) > 50 ? '...' : ''); ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($request['assigned_vehicle_id']): ?>
                                            <div class="text-info small">
                                                <i class="fas fa-truck me-1"></i><?php echo htmlspecialchars($request['vehicle_number']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="col-md-2">
                                        <div class="action-buttons">
                                            <?php if ($request['status'] === 'pending'): ?>
                                                <a href="assign-request.php?id=<?php echo $request['id']; ?>" 
                                                   class="btn btn-success btn-action">
                                                    <i class="fas fa-user-plus me-1"></i>Assign
                                                </a>
                                            <?php elseif ($request['status'] === 'assigned'): ?>
                                                <a href="update-status.php?id=<?php echo $request['id']; ?>&status=in_progress" 
                                                   class="btn btn-info btn-action">
                                                    <i class="fas fa-play me-1"></i>Start
                                                </a>
                                            <?php elseif ($request['status'] === 'in_progress'): ?>
                                                <a href="update-status.php?id=<?php echo $request['id']; ?>&status=completed" 
                                                   class="btn btn-success btn-action">
                                                    <i class="fas fa-check me-1"></i>Complete
                                                </a>
                                            <?php endif; ?>
                                            
                                            <a href="view-request.php?id=<?php echo $request['id']; ?>" 
                                               class="btn btn-outline-primary btn-action">
                                                <i class="fas fa-eye me-1"></i>View
                                            </a>
                                            
                                            
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                            <h4 class="text-muted">No service requests found</h4>
                            <p class="text-muted">There are no service requests matching your criteria.</p>
                            <a href="add-request.php" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Create First Request
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-refresh every 30 seconds
        setInterval(() => {
            location.reload();
        }, 30000);
        
        // Add smooth animations
        document.querySelectorAll('.request-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
    </script>
</body>
</html> 
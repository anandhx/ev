<?php
session_start();
require_once '../includes/functions.php';

// Simple admin session check
if (!isset($_SESSION['admin_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$message = '';
$current_page = 'vehicles';

// Initialize managers
$vehicleManager = new VehicleManager();
$technicianManager = new TechnicianManager();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_vehicle'])) {
        $vehicle_id = $_POST['vehicle_id'];
        $vehicle_data = [
            'vehicle_number' => $_POST['vehicle_number'],
            'vehicle_type' => $_POST['vehicle_type'],
            'capacity' => $_POST['capacity'],
            'status' => $_POST['status']
        ];
        
        if ($vehicleManager->updateVehicle($vehicle_id, $vehicle_data)) {
            $message = 'Vehicle updated successfully!';
        } else {
            $message = 'Error updating vehicle.';
        }
    } elseif (isset($_POST['delete_vehicle'])) {
        $vehicle_id = $_POST['vehicle_id'];
        if ($vehicleManager->deleteVehicle($vehicle_id)) {
            $message = 'Vehicle deleted successfully!';
        } else {
            $message = 'Error deleting vehicle.';
        }
    } elseif (isset($_POST['assign_technician'])) {
        $vehicle_id = $_POST['vehicle_id'];
        $technician_id = $_POST['technician_id'];
        
        if ($vehicleManager->assignTechnician($vehicle_id, $technician_id)) {
            $message = 'Technician assigned successfully!';
        } else {
            $message = 'Error assigning technician.';
        }
    } elseif (isset($_POST['unassign_technician'])) {
        $vehicle_id = $_POST['vehicle_id'];
        
        if ($vehicleManager->unassignTechnician($vehicle_id)) {
            $message = 'Technician unassigned successfully!';
        } else {
            $message = 'Error unassigning technician.';
        }
    } elseif (isset($_POST['add_vehicle'])) {
        $vehicle_data = [
            'vehicle_number' => $_POST['vehicle_number'],
            'vehicle_type' => $_POST['vehicle_type'],
            'capacity' => $_POST['capacity'],
            'status' => $_POST['status'],
            'current_location_lat' => $_POST['current_location_lat'] ?: null,
            'current_location_lng' => $_POST['current_location_lng'] ?: null
        ];
        
        if ($vehicleManager->createVehicle($vehicle_data)) {
            $message = 'Vehicle added successfully!';
        } else {
            $message = 'Error adding vehicle.';
        }
    }
}

// Get real vehicles data from database
$vehicles = $vehicleManager->getAllVehicles();

// Filter vehicles
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

$filtered_vehicles = $vehicles;

if ($filter === 'available') {
    $filtered_vehicles = array_filter($vehicles, function($v) {
        return $v['status'] === 'available';
    });
} elseif ($filter === 'busy') {
    $filtered_vehicles = array_filter($vehicles, function($v) {
        return $v['status'] === 'busy';
    });
} elseif ($filter === 'maintenance') {
    $filtered_vehicles = array_filter($vehicles, function($v) {
        return $v['status'] === 'maintenance';
    });
} elseif ($filter === 'offline') {
    $filtered_vehicles = array_filter($vehicles, function($v) {
        return $v['status'] === 'offline';
    });
}

if ($search) {
    $filtered_vehicles = array_filter($filtered_vehicles, function($v) use ($search) {
        return stripos($v['vehicle_number'], $search) !== false || 
               stripos($v['vehicle_type'], $search) !== false ||
               stripos($v['capacity'], $search) !== false;
    });
}

$total_vehicles = count($vehicles);
$available_vehicles = count(array_filter($vehicles, function($v) { return $v['status'] === 'available'; }));
$busy_vehicles = count(array_filter($vehicles, function($v) { return $v['status'] === 'busy'; }));
$maintenance_vehicles = count(array_filter($vehicles, function($v) { return $v['status'] === 'maintenance'; }));
$offline_vehicles = count(array_filter($vehicles, function($v) { return $v['status'] === 'offline'; }));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vehicle Management - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .sidebar {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            height: 100vh;
            width: 250px;
            position: fixed;
            top: 0;
            left: 0;
            overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            z-index: 1000;
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
            text-decoration: none;
            display: block;
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
            margin-left: 250px;
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

        .container {
            width: 100%;
            max-width: none;
            margin: 0;
            padding: 0 1rem;
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
            font-size: 2.5rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .header p {
            color: #666;
            font-size: 1.1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }

        .filter-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .filter-tab {
            padding: 10px 20px;
            border: 2px solid #e1e5e9;
            border-radius: 25px;
            background: white;
            color: #666;
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .filter-tab.active,
        .filter-tab:hover {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .search-box {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .search-input {
            flex: 1;
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 25px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 25px;
            font-size: 14px;
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

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .btn-danger {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(220, 53, 69, 0.3);
        }

        .btn-success {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.3);
        }

        .btn-warning {
            background: linear-gradient(135deg, #ffc107, #e0a800);
            color: white;
        }

        .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 193, 7, 0.3);
        }

        .vehicles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .vehicle-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .vehicle-card:hover {
            transform: translateY(-5px);
        }

        .vehicle-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .vehicle-info h3 {
            color: #333;
            margin-bottom: 0.5rem;
            font-size: 1.3rem;
        }

        .vehicle-plate {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-available {
            background: #d4edda;
            color: #155724;
        }

        .status-in_use {
            background: #fff3cd;
            color: #856404;
        }

        .status-maintenance {
            background: #f8d7da;
            color: #721c24;
        }

        .vehicle-details {
            margin-bottom: 1rem;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            padding: 0.5rem 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            color: #666;
            font-weight: 500;
        }

        .detail-value {
            color: #333;
            font-weight: 600;
        }

        .vehicle-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
        }

        .message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }

        @media (max-width: 767.98px) {
            .main-content {
                margin-left: 0;
            }
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            .sidebar.show {
                transform: translateX(0);
            }
            .vehicles-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
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
            <a href="requests.php" class="nav-link">
                <i class="fas fa-clipboard-list"></i>Service Requests
            </a>
            <a href="vehicles.php" class="nav-link <?php echo $current_page === 'vehicles' ? 'active' : ''; ?>">
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
            <a href="spares.php" class="nav-link"><i class="fas fa-cogs"></i>Spares</a>
            <a href="../logout.php" class="nav-link text-danger">
                <i class="fas fa-sign-out-alt"></i>Logout
            </a>
        </nav>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h2 class="mb-0">Vehicle Management</h2>
                    <p class="text-muted mb-0">Manage and monitor all service vehicles in the EV Mobile Station network</p>
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
        
        <div class="container">
       

            <?php if ($message): ?>
                <div class="message"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_vehicles; ?></div>
                    <div class="stat-label">Total Vehicles</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $available_vehicles; ?></div>
                    <div class="stat-label">Available</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $busy_vehicles; ?></div>
                    <div class="stat-label">Busy</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $maintenance_vehicles; ?></div>
                    <div class="stat-label">Maintenance</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $offline_vehicles; ?></div>
                    <div class="stat-label">Offline</div>
                </div>
            </div>

            <div class="filter-tabs">
                <a href="?filter=all" class="filter-tab <?php echo $filter === 'all' ? 'active' : ''; ?>">All Vehicles</a>
                <a href="?filter=available" class="filter-tab <?php echo $filter === 'available' ? 'active' : ''; ?>">Available</a>
                <a href="?filter=busy" class="filter-tab <?php echo $filter === 'busy' ? 'active' : ''; ?>">Busy</a>
                <a href="?filter=maintenance" class="filter-tab <?php echo $filter === 'maintenance' ? 'active' : ''; ?>">Maintenance</a>
                <a href="?filter=offline" class="filter-tab <?php echo $filter === 'offline' ? 'active' : ''; ?>">Offline</a>
            </div>

            <div class="search-box">
                <form method="GET" style="display: flex; gap: 10px; width: 100%;">
                    <input type="text" name="search" class="search-input" placeholder="Search vehicles by vehicle number, type, or capacity..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-primary">Search</button>
                    <?php if ($search): ?>
                        <a href="?" class="btn btn-secondary">Clear</a>
                    <?php endif; ?>
                </form>
                <button class="btn btn-success" onclick="showAddVehicleModal()" style="margin-top: 10px;">
                    <i class="fas fa-plus"></i> Add New Vehicle
                </button>
            </div>

            <div class="vehicles-grid">
                <?php if (empty($filtered_vehicles)): ?>
                    <div style="grid-column: 1 / -1; text-align: center; padding: 2rem; color: #666;">
                        No vehicles found.
                    </div>
                <?php else: ?>
                    <?php foreach ($filtered_vehicles as $vehicle): ?>
                        <div class="vehicle-card">
                            <div class="vehicle-header">
                                <div class="vehicle-info">
                                    <h3><?php echo htmlspecialchars($vehicle['vehicle_type'] . ' - ' . $vehicle['vehicle_number']); ?></h3>
                                    <div class="vehicle-plate"><?php echo htmlspecialchars($vehicle['vehicle_number']); ?></div>
                                </div>
                                <span class="status-badge status-<?php echo $vehicle['status']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $vehicle['status'])); ?>
                                </span>
                            </div>
                            
                            <div class="vehicle-details">
                                <div class="detail-row">
                                    <span class="detail-label">Type:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($vehicle['vehicle_type']); ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Capacity:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($vehicle['capacity']); ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Location:</span>
                                    <span class="detail-value">
                                        <?php if (!empty($vehicle['current_location_lat']) && !empty($vehicle['current_location_lng'])): ?>
                                            <?php echo htmlspecialchars($vehicle['current_location_lat'] . ', ' . $vehicle['current_location_lng']); ?>
                                        <?php else: ?>
                                            Location not set
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <?php if ($vehicle['status'] === 'busy'): ?>
                                    <div class="detail-row">
                                        <span class="detail-label">Assigned Technician:</span>
                                        <span class="detail-value">
                                            <?php 
                                            $assigned_technician = $vehicleManager->getAssignedTechnician($vehicle['id']);
                                            if ($assigned_technician): 
                                                echo htmlspecialchars($assigned_technician['full_name'] . ' (' . $assigned_technician['specialization'] . ')');
                                            else: 
                                                echo 'No technician assigned';
                                            endif; 
                                            ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="vehicle-actions">
                                <button class="btn btn-primary btn-sm" onclick="editVehicle(<?php echo $vehicle['id']; ?>)">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <?php if ($vehicle['status'] === 'available'): ?>
                                    <button class="btn btn-warning btn-sm" onclick="assignTechnician(<?php echo $vehicle['id']; ?>)">
                                        <i class="fas fa-user-plus"></i> Assign
                                    </button>
                                <?php elseif ($vehicle['status'] === 'busy'): ?>
                                    <button class="btn btn-info btn-sm" onclick="unassignTechnician(<?php echo $vehicle['id']; ?>)">
                                        <i class="fas fa-user-minus"></i> Unassign
                                    </button>
                                <?php endif; ?>
                               
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add Vehicle Modal -->
    <div class="modal fade" id="addVehicleModal" tabindex="-1" aria-labelledby="addVehicleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addVehicleModalLabel">Add New Vehicle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="vehicle_number" class="form-label">Vehicle Number</label>
                            <input type="text" class="form-control" id="vehicle_number" name="vehicle_number" required>
                        </div>
                        <div class="mb-3">
                            <label for="vehicle_type" class="form-label">Vehicle Type</label>
                            <select class="form-control" id="vehicle_type" name="vehicle_type" required>
                                <option value="">Select Type</option>
                                <option value="Fast Charger">Fast Charger</option>
                                <option value="Tool Kit">Tool Kit</option>
                                <option value="Battery Swapper">Battery Swapper</option>
                                <option value="Diagnostic Unit">Diagnostic Unit</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="capacity" class="form-label">Capacity</label>
                            <input type="text" class="form-control" id="capacity" name="capacity" required>
                        </div>
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-control" id="status" name="status" required>
                                <option value="available">Available</option>
                                <option value="busy">Busy</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="offline">Offline</option>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="current_location_lat" class="form-label">Latitude</label>
                                    <input type="number" step="any" class="form-control" id="current_location_lat" name="current_location_lat">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="current_location_lng" class="form-label">Longitude</label>
                                    <input type="number" step="any" class="form-control" id="current_location_lng" name="current_location_lng">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_vehicle" class="btn btn-primary">Add Vehicle</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Vehicle Modal -->
    <div class="modal fade" id="editVehicleModal" tabindex="-1" aria-labelledby="editVehicleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editVehicleModalLabel">Edit Vehicle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <input type="hidden" id="edit_vehicle_id" name="vehicle_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_vehicle_number" class="form-label">Vehicle Number</label>
                            <input type="text" class="form-control" id="edit_vehicle_number" name="vehicle_number" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_vehicle_type" class="form-label">Vehicle Type</label>
                            <select class="form-control" id="edit_vehicle_type" name="vehicle_type" required>
                                <option value="">Select Type</option>
                                <option value="Fast Charger">Fast Charger</option>
                                <option value="Tool Kit">Tool Kit</option>
                                <option value="Battery Swapper">Battery Swapper</option>
                                <option value="Diagnostic Unit">Diagnostic Unit</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_capacity" class="form-label">Capacity</label>
                            <input type="text" class="form-control" id="edit_capacity" name="capacity" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_status" class="form-label">Status</label>
                            <select class="form-control" id="edit_status" name="status" required>
                                <option value="available">Available</option>
                                <option value="busy">Busy</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="offline">Offline</option>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_current_location_lat" class="form-label">Latitude</label>
                                    <input type="number" step="any" class="form-control" id="edit_current_location_lat" name="current_location_lat">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_current_location_lng" class="form-label">Longitude</label>
                                    <input type="number" step="any" class="form-control" id="edit_current_location_lng" name="current_location_lng">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_vehicle" class="btn btn-primary">Update Vehicle</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Assign Technician Modal -->
    <div class="modal fade" id="assignTechnicianModal" tabindex="-1" aria-labelledby="assignTechnicianModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="assignTechnicianModalLabel">Assign Technician to Vehicle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <input type="hidden" id="assign_vehicle_id" name="vehicle_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="technician_id" class="form-label">Select Technician</label>
                            <select class="form-control" id="technician_id" name="technician_id" required>
                                <option value="">Select Technician</option>
                                <?php 
                                $technicians = $technicianManager->getAllTechnicians();
                                foreach ($technicians as $technician): 
                                ?>
                                    <option value="<?php echo $technician['id']; ?>">
                                        <?php echo htmlspecialchars($technician['full_name'] . ' (' . $technician['specialization'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="assign_technician" class="btn btn-primary">Assign Technician</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function showAddVehicleModal() {
            // Reset form
            document.getElementById('addVehicleModal').querySelector('form').reset();
            // Show modal
            new bootstrap.Modal(document.getElementById('addVehicleModal')).show();
        }

        function editVehicle(vehicleId) {
            console.log('Fetching vehicle data for ID:', vehicleId);
            // Get vehicle data and populate form
            fetch(`../api/index.php?action=get_vehicle&id=${vehicleId}`)
                .then(response => {
                    console.log('Response status:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('Response data:', data);
                    if (data.success) {
                        const vehicle = data.data.vehicle;
                        console.log('Vehicle data:', vehicle);
                        document.getElementById('edit_vehicle_id').value = vehicle.id;
                        document.getElementById('edit_vehicle_number').value = vehicle.vehicle_number;
                        document.getElementById('edit_vehicle_type').value = vehicle.vehicle_type;
                        document.getElementById('edit_capacity').value = vehicle.capacity;
                        document.getElementById('edit_status').value = vehicle.status;
                        document.getElementById('edit_current_location_lat').value = vehicle.current_location_lat || '';
                        document.getElementById('edit_current_location_lng').value = vehicle.current_location_lng || '';
                        
                        // Show modal
                        new bootstrap.Modal(document.getElementById('editVehicleModal')).show();
                    } else {
                        alert('Error loading vehicle data: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading vehicle data');
                });
        }

        function assignTechnician(vehicleId) {
            document.getElementById('assign_vehicle_id').value = vehicleId;
            new bootstrap.Modal(document.getElementById('assignTechnicianModal')).show();
        }

        function unassignTechnician(vehicleId) {
            if (confirm('Are you sure you want to unassign the technician from this vehicle?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `<input type="hidden" name="unassign_technician" value="1">
                                <input type="hidden" name="vehicle_id" value="${vehicleId}">`;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function deleteVehicle(vehicleId) {
            if (confirm('Are you sure you want to delete this vehicle? This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `<input type="hidden" name="delete_vehicle" value="1">
                                <input type="hidden" name="vehicle_id" value="${vehicleId}">`;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 
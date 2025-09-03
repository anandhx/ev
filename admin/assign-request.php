<?php
session_start();
require_once '../includes/functions.php';

// Simple admin session check
if (!isset($_SESSION['admin_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$message = '';
$error = '';

$adminManager = new AdminManager();
$serviceManager = new ServiceRequestManager();
$techManager = new TechnicianManager();
$vehicleManager = new VehicleManager();

// Handle assignment
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['assign_request'])) {
        $request_id = (int)($_POST['request_id'] ?? 0);
        $technician_id = (int)($_POST['technician_id'] ?? 0);
        $vehicle_id = (int)($_POST['vehicle_id'] ?? 0);
        $priority = $_POST['priority'] ?? 'medium';
        $estimated_time = (int)($_POST['estimated_time'] ?? 30);

        if ($request_id && $technician_id && $vehicle_id) {
            $ok = $serviceManager->assignRequest($request_id, $vehicle_id, $technician_id);
            if ($ok) {
                // Update ETA
                try {
                    $db = Database::getInstance();
                    $db->executeQuery(
                        "UPDATE service_requests SET estimated_arrival_time = DATE_ADD(NOW(), INTERVAL ? MINUTE), updated_at = CURRENT_TIMESTAMP WHERE id = ?",
                        [$estimated_time, $request_id]
                    );
                } catch (Exception $e) {}
                $message = 'Request assigned successfully!';
            } else {
                $error = 'Failed to assign request.';
            }
        } else {
            $error = 'Please select technician and vehicle.';
        }
    }
}

// Get pending requests from DB
$pending_requests = $adminManager->getAllRequests('pending', 100);

// Available technicians and vehicles from DB
$available_technicians = $techManager->getAvailableTechnicians();
$available_vehicles = $vehicleManager->getAvailableVehicles();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Request - Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            min-height: 100vh;
        }

        /* Fixed sidebar (match admin dashboard) */
        .sidebar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 240px;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .sidebar:hover { overflow-y: auto; }
        .sidebar-header { padding: 2rem 1rem; text-align:center; color:#fff; border-bottom:1px solid rgba(255,255,255,0.1); }
        .sidebar-nav { padding: 1rem 0; }
        .nav-link { color: rgba(255,255,255,0.85); padding: 0.75rem 1.5rem; display:block; text-decoration:none; }
        .nav-link:hover, .nav-link.active { color:#fff; background: rgba(255,255,255,0.12); transform: translateX(5px); }
        .nav-link i { width:20px; margin-right:10px; }

        .main-with-fixed-sidebar { margin-left: 240px; width: calc(100% - 240px); }
        .container { max-width: none; margin: 0; padding: 1rem; }

        .header {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            color: #333;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .nav-links {
            display: flex;
            gap: 1rem;
        }

        .nav-links a {
            padding: 0.75rem 1.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            transition: transform 0.3s;
        }

        .nav-links a:hover {
            transform: translateY(-2px);
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            font-weight: bold;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        .section {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f8f9fa;
        }

        .section-header h2 {
            color: #333;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .request-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border: 2px solid transparent;
            transition: all 0.3s;
        }

        .request-card:hover {
            border-color: #667eea;
            transform: translateY(-2px);
        }

        .request-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .request-id {
            font-weight: bold;
            color: #333;
            font-size: 1.1rem;
        }

        .urgency-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .urgency-emergency {
            background: #f8d7da;
            color: #721c24;
        }

        .urgency-high {
            background: #fff3cd;
            color: #856404;
        }

        .urgency-medium {
            background: #d1ecf1;
            color: #0c5460;
        }

        .urgency-low {
            background: #d4edda;
            color: #155724;
        }

        .request-details {
            margin-bottom: 1rem;
        }

        .request-detail {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .request-detail strong {
            color: #333;
        }

        .assign-btn {
            padding: 0.5rem 1rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: transform 0.3s;
        }

        .assign-btn:hover {
            transform: translateY(-2px);
        }

        .resource-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            border: 2px solid transparent;
            transition: all 0.3s;
        }

        .resource-card:hover {
            border-color: #28a745;
            transform: translateY(-2px);
        }

        .resource-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .resource-name {
            font-weight: bold;
            color: #333;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .status-available {
            background: #d4edda;
            color: #155724;
        }

        .status-busy {
            background: #fff3cd;
            color: #856404;
        }

        .resource-details {
            font-size: 0.9rem;
            color: #666;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #666;
            font-size: 1rem;
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 2rem;
            border-radius: 15px;
            width: 90%;
            max-width: 600px;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e1e5e9;
        }

        .close {
            font-size: 2rem;
            cursor: pointer;
            color: #666;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
            color: #333;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            transition: transform 0.3s;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn-primary {
            background: #007bff;
            color: white;
        }

        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h4><i class="fas fa-charging-station" style="margin-right:8px;"></i>EV Station</h4>
            <small>Admin Panel</small>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i>Dashboard</a>
            <a href="requests.php" class="nav-link active"><i class="fas fa-clipboard-list"></i>Service Requests</a>
            <a href="vehicles.php" class="nav-link"><i class="fas fa-truck"></i>Service Vehicles</a>
            <a href="technicians.php" class="nav-link"><i class="fas fa-user-cog"></i>Technicians</a>
            <a href="users.php" class="nav-link"><i class="fas fa-users"></i>Users</a>
            <a href="payments.php" class="nav-link"><i class="fas fa-credit-card"></i>Payments</a>
            <a href="emergency.php" class="nav-link"><i class="fas fa-exclamation-triangle"></i>Emergency</a>
            <a href="../logout.php" class="nav-link" style="color:#ffd1d1;"><i class="fas fa-sign-out-alt"></i>Logout</a>
        </nav>
    </div>

    <div class="main-with-fixed-sidebar">
        <div class="container">
            <div class="header">
                <h1><i class="fas fa-user-plus"></i> Assign Service Request</h1>
                <div class="nav-links">
                    <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                    <a href="requests.php"><i class="fas fa-clipboard-list"></i> Requests</a>
                    <a href="vehicles.php"><i class="fas fa-truck"></i> Vehicles</a>
                    <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($pending_requests); ?></div>
                <div class="stat-label">Pending Requests</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($available_technicians); ?></div>
                <div class="stat-label">Available Technicians</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($available_vehicles); ?></div>
                <div class="stat-label">Available Vehicles</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($pending_requests) > 0 && count($available_technicians) > 0 ? round(count($pending_requests) / count($available_technicians), 1) : 0; ?></div>
                <div class="stat-label">Avg Requests per Tech</div>
            </div>
        </div>

        <div class="content-grid">
            <!-- Pending Requests -->
            <div class="section">
                <div class="section-header">
                    <h2><i class="fas fa-clock"></i> Pending Requests</h2>
                    <span class="badge"><?php echo count($pending_requests); ?> requests</span>
                </div>
                
                <?php if (empty($pending_requests)): ?>
                    <div style="text-align: center; padding: 2rem; color: #666;">
                        <i class="fas fa-check-circle" style="font-size: 3rem; color: #28a745; margin-bottom: 1rem;"></i>
                        <h3>No pending requests</h3>
                        <p>All service requests have been assigned!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($pending_requests as $request): ?>
                        <div class="request-card">
                            <div class="request-header">
                                <div class="request-id">#<?php echo $request['id']; ?></div>
                                <span class="urgency-badge urgency-<?php echo $request['urgency_level']; ?>">
                                    <?php echo ucfirst($request['urgency_level']); ?>
                                </span>
                            </div>
                            
                            <div class="request-details">
                                <div class="request-detail">
                                    <strong>User:</strong>
                                    <span><?php echo htmlspecialchars($request['user_name'] ?? 'Unknown'); ?></span>
                                </div>
                                <div class="request-detail">
                                    <strong>Type:</strong>
                                    <span><?php echo ucfirst($request['request_type'] ?? 'Unknown'); ?></span>
                                </div>
                                <?php if (!empty($request['description'])): ?>
                                <div class="request-detail">
                                    <strong>Description:</strong>
                                    <span><?php echo htmlspecialchars($request['description']); ?></span>
                                </div>
                                <?php endif; ?>
                                <div class="request-detail">
                                    <strong>Cost:</strong>
                                    <span>₹<?php echo number_format($request['total_cost'] ?? 0, 2); ?></span>
                                </div>
                            </div>
                            
                            <button class="assign-btn" onclick="openAssignModal(<?php echo $request['id']; ?>, '<?php echo htmlspecialchars($request['user_name'] ?? 'Unknown', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($request['request_type'] ?? 'Unknown', ENT_QUOTES); ?>')">
                                <i class="fas fa-user-plus"></i> Assign Request
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Available Resources -->
            <div class="section">
                <div class="section-header">
                    <h2><i class="fas fa-users"></i> Available Resources</h2>
                </div>
                
                <h3 style="margin-bottom: 1rem; color: #333;">Technicians</h3>
                <?php foreach ($available_technicians as $technician): ?>
                    <div class="resource-card">
                        <div class="resource-header">
                            <div class="resource-name"><?php echo htmlspecialchars($technician['full_name'] ?? 'Unknown'); ?></div>
                            <span class="status-badge status-<?php echo htmlspecialchars($technician['status'] ?? 'unknown'); ?>">
                                <?php echo ucfirst($technician['status'] ?? 'unknown'); ?>
                            </span>
                        </div>
                        <div class="resource-details">
                            Specialization: <?php echo ucfirst(htmlspecialchars($technician['specialization'] ?? 'general')); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <h3 style="margin: 2rem 0 1rem 0; color: #333;">Vehicles</h3>
                <?php foreach ($available_vehicles as $vehicle): ?>
                    <div class="resource-card">
                        <div class="resource-header">
                            <div class="resource-name"><?php echo htmlspecialchars($vehicle['vehicle_number'] ?? 'Unknown'); ?></div>
                            <span class="status-badge status-<?php echo htmlspecialchars($vehicle['status'] ?? 'unknown'); ?>">
                                <?php echo ucfirst($vehicle['status'] ?? 'unknown'); ?>
                            </span>
                        </div>
                        <div class="resource-details">
                            Type: <?php echo ucfirst(htmlspecialchars($vehicle['vehicle_type'] ?? 'unknown')); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        </div>
    </div>

    <!-- Assign Request Modal -->
    <div id="assignModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Assign Service Request</h3>
                <span class="close" onclick="closeAssignModal()">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="request_id" id="assignRequestId">
                
                <div class="form-group">
                    <label>Request Details:</label>
                    <div style="background: #f8f9fa; padding: 1rem; border-radius: 8px; margin-top: 0.5rem;">
                        <strong>Request ID:</strong> <span id="modalRequestId"></span><br>
                        <strong>User:</strong> <span id="modalUserName"></span><br>
                        <strong>Type:</strong> <span id="modalRequestType"></span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="technician_id">Select Technician:</label>
                    <select name="technician_id" id="technician_id" required>
                        <option value="">Choose Technician</option>
                        <?php foreach ($available_technicians as $technician): ?>
                            <option value="<?php echo (int)$technician['id']; ?>">
                                <?php echo htmlspecialchars($technician['full_name'] ?? 'Unknown'); ?> (<?php echo htmlspecialchars($technician['specialization'] ?? 'general'); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="vehicle_id">Select Vehicle:</label>
                    <select name="vehicle_id" id="vehicle_id" required>
                        <option value="">Choose Vehicle</option>
                        <?php foreach ($available_vehicles as $vehicle): ?>
                            <option value="<?php echo (int)$vehicle['id']; ?>">
                                <?php echo htmlspecialchars($vehicle['vehicle_number'] ?? 'Unknown'); ?> (<?php echo htmlspecialchars($vehicle['vehicle_type'] ?? 'unknown'); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="priority">Priority Level:</label>
                    <select name="priority" id="priority" required>
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                        <option value="emergency">Emergency</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="estimated_time">Estimated Arrival Time (minutes):</label>
                    <input type="number" name="estimated_time" id="estimated_time" required min="5" max="120" value="30">
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-warning" onclick="closeAssignModal()">Cancel</button>
                    <button type="submit" name="assign_request" class="btn btn-primary">Assign Request</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAssignModal(requestId, userName, requestType) {
            document.getElementById('assignRequestId').value = requestId;
            document.getElementById('modalRequestId').textContent = '#' + requestId;
            document.getElementById('modalUserName').textContent = userName;
            document.getElementById('modalRequestType').textContent = requestType;
            document.getElementById('assignModal').style.display = 'block';
        }

        function closeAssignModal() {
            document.getElementById('assignModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = 'none';
            }
        }

        // Auto-hide alerts
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.display = 'none';
            });
        }, 5000);
    </script>
</body>
</html> 
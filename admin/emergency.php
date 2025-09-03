<?php
session_start();
require_once '../includes/functions.php';

// Simple admin session check
if (!isset($_SESSION['admin_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$message = '';
$current_page = 'emergency';

$adminManager = new AdminManager();
$serviceManager = new ServiceRequestManager();
$techManager = new TechnicianManager();
$vehicleManager = new VehicleManager();
$db = Database::getInstance();

// Handle emergency dispatch
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['dispatch_emergency'])) {
        $request_id = (int)($_POST['request_id'] ?? 0);
        $technician_id = (int)($_POST['technician_id'] ?? 0);
        $vehicle_id = (int)($_POST['vehicle_id'] ?? 0);
        $estimated_time = (int)($_POST['estimated_time'] ?? 5);
        if ($request_id && $technician_id && $vehicle_id) {
            $ok = $serviceManager->assignRequest($request_id, $vehicle_id, $technician_id);
            if ($ok) {
                try {
                    $db->executeQuery(
                        "UPDATE service_requests SET estimated_arrival_time = DATE_ADD(NOW(), INTERVAL ? MINUTE), updated_at = CURRENT_TIMESTAMP WHERE id = ?",
                        [$estimated_time, $request_id]
                    );
                } catch (Exception $e) {}
                $message = 'Emergency dispatched successfully!';
            } else {
                $message = 'Failed to dispatch emergency.';
            }
        } else {
            $message = 'Please select technician and vehicle.';
        }
    }
}

// Get emergency requests from DB (pending & emergency)
try {
    $stmt = $db->executeQuery(
        "SELECT sr.*, u.full_name AS user_name, u.phone AS user_phone
         FROM service_requests sr
         JOIN users u ON sr.user_id = u.id
         WHERE sr.urgency_level = 'emergency' AND sr.status = 'pending'
         ORDER BY sr.created_at DESC"
    );
    $emergency_requests = $stmt->fetchAll();
} catch (Exception $e) {
    $emergency_requests = [];
}

// Available technicians and vehicles
$emergency_technicians = $techManager->getAvailableTechnicians();
$emergency_vehicles = $vehicleManager->getAvailableVehicles();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emergency Dispatch - Admin Dashboard</title>
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
        
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body { background-color: #f5f7fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        .sidebar { background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); min-height: 100vh; height: 100vh; width: 250px; position: fixed; top: 0; left: 0; overflow-y: auto; box-shadow: 2px 0 10px rgba(0,0,0,0.1); z-index: 1000; }
        .sidebar-header { padding: 2rem 1rem; text-align: center; color: white; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-nav { padding: 1rem 0; }
        .nav-link { color: rgba(255,255,255,0.8); padding: 0.75rem 1.5rem; border-radius: 0; transition: all 0.3s ease; text-decoration: none; display: block; }
        .nav-link:hover, .nav-link.active { color: white; background-color: rgba(255,255,255,0.1); transform: translateX(5px); }
        .nav-link i { width: 20px; margin-right: 10px; }
        
        .main-content { padding: 2rem; margin-left: 250px; }
        .top-bar { background: white; padding: 1rem 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 2rem; border-radius: 15px; }
        .admin-info { display: flex; align-items: center; gap: 1rem; }
        .admin-avatar { width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; }

        .container { width: 100%; max-width: none; margin: 0; padding: 0 1rem; }

        .header { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); border-radius: 20px; padding: 20px; margin-bottom: 30px; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1); }
        .header h1 { color: #dc3545; margin-bottom: 10px; font-size: 2.5rem; display: flex; align-items: center; gap: 1rem; }
        .header p { color: #666; font-size: 1.1rem; }

        .emergency-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); border-radius: 15px; padding: 1.5rem; text-align: center; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1); transition: transform 0.3s ease; border-left: 5px solid #dc3545; }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-number { font-size: 2.5rem; font-weight: bold; margin-bottom: 0.5rem; color: #dc3545; }
        .stat-label { color: #666; font-size: 1rem; }

        .content-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; }
        .section { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); border-radius: 15px; padding: 2rem; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1); }
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 2px solid #f8f9fa; }
        .section-header h2 { color: #dc3545; display: flex; align-items: center; gap: 0.5rem; }

        .emergency-card { background: #fff5f5; border: 2px solid #dc3545; border-radius: 10px; padding: 1.5rem; margin-bottom: 1rem; transition: all 0.3s; }
        .emergency-card:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(220, 53, 69, 0.2); }
        .emergency-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
        .emergency-id { font-weight: bold; color: #dc3545; font-size: 1.1rem; }
        .emergency-badge { background: #dc3545; color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.8rem; font-weight: bold; }
        .emergency-details { margin-bottom: 1rem; }
        .emergency-detail { display: flex; justify-content: space-between; margin-bottom: 0.5rem; font-size: 0.9rem; }
        .emergency-detail strong { color: #333; }
        .dispatch-btn { padding: 0.75rem 1.5rem; background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; transition: transform 0.3s; width: 100%; }
        .dispatch-btn:hover { transform: translateY(-2px); }

        .resource-card { background: #f8f9fa; border-radius: 10px; padding: 1rem; margin-bottom: 1rem; border: 2px solid transparent; transition: all 0.3s; }
        .resource-card:hover { border-color: #28a745; transform: translateY(-2px); }
        .resource-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; }
        .resource-name { font-weight: bold; color: #333; }
        .status-badge { padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.8rem; font-weight: bold; }
        .status-available { background: #d4edda; color: #155724; }
        .resource-details { font-size: 0.9rem; color: #666; }

        .emergency-info { background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; padding: 1rem; margin-bottom: 1.5rem; }
        .emergency-info h3 { color: #856404; margin-bottom: 0.5rem; }
        .emergency-info p { color: #856404; font-size: 0.9rem; }

        .message { background: linear-gradient(135deg, #d4edda, #c3e6cb); color: #155724; padding: 15px 20px; border-radius: 15px; margin-bottom: 20px; border: 1px solid #c3e6cb; box-shadow: 0 4px 15px rgba(40, 167, 69, 0.1); display: flex; align-items: center; justify-content: space-between; transition: all 0.3s ease; }
        .error { background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 10px; margin-bottom: 1rem; text-align: center; }

        @media (max-width: 767.98px) { .main-content { margin-left: 0; } }
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
            <a href="dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i>Dashboard</a>
            <a href="requests.php" class="nav-link"><i class="fas fa-clipboard-list"></i>Service Requests</a>
            <a href="vehicles.php" class="nav-link"><i class="fas fa-truck"></i>Service Vehicles</a>
            <a href="technicians.php" class="nav-link"><i class="fas fa-user-cog"></i>Technicians</a>
            <a href="users.php" class="nav-link"><i class="fas fa-users"></i>Users</a>
            <a href="payments.php" class="nav-link"><i class="fas fa-credit-card"></i>Payments</a>
            <a href="emergency.php" class="nav-link active"><i class="fas fa-exclamation-triangle"></i>Emergency</a>
            <a href="spares.php" class="nav-link"><i class="fas fa-cogs"></i>Spares</a>
            <a href="../logout.php" class="nav-link text-danger"><i class="fas fa-sign-out-alt"></i>Logout</a>
        </nav>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h2 class="mb-0">Emergency Dispatch</h2>
                    <p class="text-muted mb-0">Manage emergency service requests and dispatch resources</p>
                </div>
                <div class="col-md-6 text-end">
                    <div class="admin-info justify-content-end">
                        <div class="text-end">
                            <div class="fw-bold"><?php echo htmlspecialchars($_SESSION['admin_role'] ?? 'Admin'); ?></div>
                            <small class="text-muted"><?php echo htmlspecialchars($_SESSION['admin_username'] ?? ''); ?></small>
                        </div>
                        <div class="admin-avatar"><?php echo strtoupper(substr($_SESSION['admin_full_name'] ?? 'A', 0, 1)); ?></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="container">
            <?php if ($message): ?>
                <div class="message" id="messageAlert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close float-end" onclick="hideMessage()"></button>
                </div>
            <?php endif; ?>

            <!-- Emergency Statistics -->
            <div class="emergency-stats">
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($emergency_requests); ?></div>
                    <div class="stat-label">Emergency Requests</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($emergency_technicians); ?></div>
                    <div class="stat-label">Available Technicians</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($emergency_vehicles); ?></div>
                    <div class="stat-label">Available Vehicles</div>
                </div>
            </div>

            <div class="content-grid">
                <!-- Emergency Requests -->
                <div class="section">
                    <div class="section-header">
                        <h2><i class="fas fa-exclamation-circle"></i> Emergency Requests</h2>
                        <span class="badge" style="background: #dc3545; color: white; padding: 0.5rem 1rem; border-radius: 20px;">
                            <?php echo count($emergency_requests); ?> urgent
                        </span>
                    </div>
                    
                    <?php if (empty($emergency_requests)): ?>
                        <div style="text-align: center; padding: 2rem; color: #666;">
                            <i class="fas fa-check-circle" style="font-size: 3rem; color: #28a745; margin-bottom: 1rem;"></i>
                            <h3>No emergency requests</h3>
                            <p>All emergency situations have been handled!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($emergency_requests as $request): ?>
                            <div class="emergency-card">
                                <div class="emergency-header">
                                    <div class="emergency-id">#<?php echo (int)$request['id']; ?></div>
                                    <span class="emergency-badge">EMERGENCY</span>
                                </div>
                                
                                <div class="emergency-details">
                                    <div class="emergency-detail"><strong>User:</strong><span><?php echo htmlspecialchars($request['user_name']); ?></span></div>
                                    <div class="emergency-detail"><strong>Type:</strong><span><?php echo htmlspecialchars($request['request_type']); ?></span></div>
                                    <?php if (!empty($request['description'])): ?><div class="emergency-detail"><strong>Description:</strong><span><?php echo htmlspecialchars($request['description']); ?></span></div><?php endif; ?>
                                    <div class="emergency-detail"><strong>Phone:</strong><span><?php echo htmlspecialchars($request['user_phone']); ?></span></div>
                                </div>
                                
                                <button class="dispatch-btn" onclick="openEmergencyModal(<?php echo (int)$request['id']; ?>, '<?php echo htmlspecialchars($request['user_name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($request['request_type'], ENT_QUOTES); ?>')">
                                    <i class="fas fa-bolt"></i> DISPATCH EMERGENCY
                                </button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Emergency Resources -->
                <div class="section">
                    <div class="section-header">
                        <h2><i class="fas fa-users"></i> Emergency Resources</h2>
                    </div>
                    
                    <div class="emergency-info">
                        <h3><i class="fas fa-info-circle"></i> Emergency Protocol</h3>
                        <p>Emergency requests require immediate attention. Available technicians and vehicles are prioritized for emergency dispatch.</p>
                    </div>
                    
                    <h3 style="margin-bottom: 1rem; color: #333;">Available Technicians</h3>
                    <?php foreach ($emergency_technicians as $technician): ?>
                        <div class="resource-card">
                            <div class="resource-header">
                                <div class="resource-name"><?php echo htmlspecialchars($technician['full_name']); ?></div>
                                <span class="status-badge status-<?php echo htmlspecialchars($technician['status']); ?>"><?php echo ucfirst($technician['status']); ?></span>
                            </div>
                            <div class="resource-details">Specialization: <?php echo ucfirst(htmlspecialchars($technician['specialization'])); ?></div>
                        </div>
                    <?php endforeach; ?>
                    
                    <h3 style="margin: 2rem 0 1rem 0; color: #333;">Available Vehicles</h3>
                    <?php foreach ($emergency_vehicles as $vehicle): ?>
                        <div class="resource-card">
                            <div class="resource-header">
                                <div class="resource-name"><?php echo htmlspecialchars($vehicle['vehicle_number']); ?></div>
                                <span class="status-badge status-<?php echo htmlspecialchars($vehicle['status']); ?>"><?php echo ucfirst($vehicle['status']); ?></span>
                            </div>
                            <div class="resource-details">Type: <?php echo ucfirst(htmlspecialchars($vehicle['vehicle_type'])); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Emergency Dispatch Modal -->
    <div id="emergencyModal" class="modal" style="display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background-color: rgba(0,0,0,0.5);">
        <div class="modal-content" style="background:#fff; margin:5% auto; padding:2rem; border-radius:15px; width:90%; max-width:600px; border:3px solid #dc3545; position:relative;">
            <div class="modal-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; padding-bottom:1rem; border-bottom:1px solid #e1e5e9;">
                <h3><i class="fas fa-exclamation-triangle"></i> Emergency Dispatch</h3>
                <span class="close" onclick="closeEmergencyModal()" style="font-size:2rem; cursor:pointer; color:#666;">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="request_id" id="emergencyRequestId">
                <div class="form-group" style="margin-bottom:1rem;">
                    <label>Emergency Request Details:</label>
                    <div style="background:#fff5f5; padding:1rem; border-radius:8px; margin-top:0.5rem; border:1px solid #dc3545;">
                        <strong>Request ID:</strong> <span id="modalRequestId"></span><br>
                        <strong>User:</strong> <span id="modalUserName"></span><br>
                        <strong>Type:</strong> <span id="modalRequestType"></span><br>
                        <strong style="color:#dc3545;">PRIORITY: EMERGENCY</strong>
                    </div>
                </div>
                <div class="form-group" style="margin-bottom:1rem;">
                    <label for="technician_id">Select Technician (Emergency):</label>
                    <select name="technician_id" id="technician_id" required style="width:100%; padding:0.75rem; border:1px solid #ddd; border-radius:8px;">
                        <option value="">Choose Emergency Technician</option>
                        <?php foreach ($emergency_technicians as $technician): ?>
                            <option value="<?php echo (int)$technician['id']; ?>"><?php echo htmlspecialchars($technician['full_name']) . ' (' . htmlspecialchars($technician['specialization']) . ')'; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom:1rem;">
                    <label for="vehicle_id">Select Vehicle (Emergency):</label>
                    <select name="vehicle_id" id="vehicle_id" required style="width:100%; padding:0.75rem; border:1px solid #ddd; border-radius:8px;">
                        <option value="">Choose Emergency Vehicle</option>
                        <?php foreach ($emergency_vehicles as $vehicle): ?>
                            <option value="<?php echo (int)$vehicle['id']; ?>"><?php echo htmlspecialchars($vehicle['vehicle_number']) . ' (' . htmlspecialchars($vehicle['vehicle_type']) . ')'; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom:1rem;">
                    <label for="estimated_time">Estimated Arrival Time (minutes):</label>
                    <input type="number" name="estimated_time" id="estimated_time" required min="1" max="30" value="5" style="width:100%; padding:0.75rem; border:1px solid #ddd; border-radius:8px;">
                </div>
                <div class="form-actions" style="display:flex; gap:1rem; justify-content:flex-end; margin-top:1.5rem;">
                    <button type="button" class="btn btn-secondary" onclick="closeEmergencyModal()">Cancel</button>
                    <button type="submit" name="dispatch_emergency" class="btn btn-danger"><i class="fas fa-bolt"></i> DISPATCH EMERGENCY</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEmergencyModal(requestId, userName, requestType) {
            document.getElementById('emergencyRequestId').value = requestId;
            document.getElementById('modalRequestId').textContent = '#' + requestId;
            document.getElementById('modalUserName').textContent = userName;
            document.getElementById('modalRequestType').textContent = requestType;
            document.getElementById('emergencyModal').style.display = 'block';
        }
        function closeEmergencyModal() { document.getElementById('emergencyModal').style.display = 'none'; }
        // Close modal when clicking outside
        window.onclick = function(event) { if (event.target.id === 'emergencyModal') { closeEmergencyModal(); } }

        // Auto-hide alerts
        function hideMessage() {
            const messageAlert = document.getElementById('messageAlert');
            if (messageAlert) {
                messageAlert.style.transition = 'opacity 0.5s ease';
                messageAlert.style.opacity = '0';
                setTimeout(() => { messageAlert.remove(); }, 500);
            }
        }
        setTimeout(hideMessage, 5000);
    </script>
</body>
</html> 
<?php
session_start();
require_once '../config/config.php';
require_once '../includes/functions.php';

// Admin session check
if (!SessionManager::isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$message = '';
$error = '';
$current_page = 'technicians';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        if (isset($_POST['add_technician'])) {
            $name = sanitizeInput($_POST['name'] ?? '');
            $email = sanitizeInput($_POST['email'] ?? '');
            $phone = sanitizeInput($_POST['phone'] ?? '');
            $specialization = sanitizeInput($_POST['specialization'] ?? '');
            $experience_years = (int)($_POST['experience_years'] ?? 0);
            
            // Validate input
            if (empty($name) || empty($phone) || empty($specialization) || empty($email)) {
                $error = 'Name, email, phone, and specialization are required fields.';
            } else {
                $db = Database::getInstance();
                // Check duplicate email
                $dup = $db->executeQuery("SELECT id FROM technicians WHERE email = ? LIMIT 1", [$email])->fetch();
                if ($dup) {
                    $error = 'Email already exists. Please use a different email.';
                } else {
                    $plainPassword = trim((string)($_POST['password'] ?? ''));
                    if ($plainPassword === '') {
                        $plainPassword = substr(bin2hex(random_bytes(6)), 0, 12);
                    }
                    $hashed = password_hash($plainPassword, PASSWORD_DEFAULT);
                    $sql = "INSERT INTO technicians (full_name, email, phone, specialization, experience_years, status, password) 
                            VALUES (?, ?, ?, ?, ?, 'available', ?)";
                    $stmt = $db->executeQuery($sql, [$name, $email, $phone, $specialization, $experience_years, $hashed]);
                    
                    if ($stmt) {
                        $subject = 'Your Technician Account Credentials';
                        $body = "Hello $name,\n\nYour technician account has been created.\nEmail: $email\nTemporary Password: $plainPassword\n\nPlease log in and change your password.\n\nRegards, EV Mobile Station";
                        UtilityFunctions::sendEmail($email, $subject, $body);
                        $message = 'Technician added and credentials emailed.';
                    } else {
                        $error = 'Failed to add technician.';
                    }
                }
            }
        } elseif (isset($_POST['update_technician'])) {
            $technician_id = (int)($_POST['technician_id'] ?? 0);
            $status = sanitizeInput($_POST['status'] ?? '');
            
            if (empty($technician_id) || empty($status)) {
                $error = 'Technician ID and status are required.';
            } else {
                $db = Database::getInstance();
                $sql = "UPDATE technicians SET status = ? WHERE id = ?";
                $stmt = $db->executeQuery($sql, [$status, $technician_id]);
                
                if ($stmt) {
                    $message = 'Technician status updated successfully!';
                } else {
                    $error = 'Failed to update technician status.';
                }
            }
        } elseif (isset($_POST['delete_technician'])) {
            $technician_id = (int)($_POST['technician_id'] ?? 0);
            
            if (empty($technician_id)) {
                $error = 'Technician ID is required.';
            } else {
                // Check if technician has assigned requests
                $db = Database::getInstance();
                $check_sql = "SELECT COUNT(*) as count FROM service_requests WHERE assigned_technician_id = ?";
                $check_stmt = $db->executeQuery($check_sql, [$technician_id]);
                $check_result = $check_stmt->fetch();
                
                if ($check_result['count'] > 0) {
                    $error = 'Cannot delete technician with assigned service requests.';
                } else {
                    $sql = "DELETE FROM technicians WHERE id = ?";
                    $stmt = $db->executeQuery($sql, [$technician_id]);
                    
                    if ($stmt) {
                        $message = 'Technician deleted successfully!';
                    } else {
                        $error = 'Failed to delete technician.';
                    }
                }
            }
        } elseif (isset($_POST['edit_technician'])) {
            $technician_id = (int)($_POST['technician_id'] ?? 0);
            $name = sanitizeInput($_POST['name'] ?? '');
            $phone = sanitizeInput($_POST['phone'] ?? '');
            $specialization = sanitizeInput($_POST['specialization'] ?? '');
            $experience_years = (int)($_POST['experience_years'] ?? 0);
            $status = sanitizeInput($_POST['status'] ?? '');
            
            if (empty($technician_id) || empty($name) || empty($phone) || empty($specialization)) {
                $error = 'All fields are required.';
            } else {
                $db = Database::getInstance();
                $sql = "UPDATE technicians SET full_name = ?, phone = ?, specialization = ?, 
                        experience_years = ?, status = ? WHERE id = ?";
                $stmt = $db->executeQuery($sql, [$name, $phone, $specialization, $experience_years, $status, $technician_id]);
                
                if ($stmt) {
                    $message = 'Technician updated successfully!';
                } else {
                    $error = 'Failed to update technician.';
                }
            }
        }
    } catch (Exception $e) {
        $error = 'An error occurred: ' . $e->getMessage();
    }
}

// Get technicians from database
try {
    $db = Database::getInstance();
    $sql = "SELECT t.*, 
            COUNT(sr.id) as total_requests,
            AVG(CASE WHEN sr.status = 'completed' THEN 5 ELSE NULL END) as rating
            FROM technicians t 
            LEFT JOIN service_requests sr ON t.id = sr.assigned_technician_id 
            GROUP BY t.id 
            ORDER BY t.created_at DESC";
    
    $stmt = $db->executeQuery($sql);
    $technicians = $stmt->fetchAll();
    
    // Calculate statistics
    $total_technicians = count($technicians);
    $active_technicians = count(array_filter($technicians, function($t) { return $t['status'] === 'available'; }));
    $busy_technicians = count(array_filter($technicians, function($t) { return $t['status'] === 'busy'; }));
    $offline_technicians = count(array_filter($technicians, function($t) { return $t['status'] === 'offline'; }));
    
} catch (Exception $e) {
    $error = 'Database error: ' . $e->getMessage();
    $technicians = [];
    $total_technicians = $active_technicians = $busy_technicians = $offline_technicians = 0;
}

// Filter technicians
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

$filtered_technicians = $technicians;

if ($filter !== 'all') {
    $filtered_technicians = array_filter($technicians, function($tech) use ($filter) {
        return $tech['status'] === $filter;
    });
}

if ($search) {
    $filtered_technicians = array_filter($filtered_technicians, function($tech) use ($search) {
        return stripos($tech['full_name'], $search) !== false || 
               stripos($tech['phone'], $search) !== false ||
               stripos($tech['specialization'], $search) !== false;
    });
}

// Helper function for sanitization
function sanitizeInput($data) {
    if ($data === null) {
        return '';
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Technician Management - Admin Dashboard</title>
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
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .header h1 {
            color: #333;
            margin-bottom: 0.5rem;
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

        .controls {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .controls-row {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .search-box {
            flex: 1;
            min-width: 300px;
        }

        .search-box input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .search-box input:focus {
            outline: none;
            border-color: #667eea;
        }

        .filter-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .filter-btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 10px;
            background: #f8f9fa;
            color: #666;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .filter-btn:hover, .filter-btn.active {
            background: #667eea;
            color: white;
        }

        .add-btn {
            padding: 0.75rem 1.5rem;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: background 0.3s ease;
            font-size: 0.9rem;
        }

        .add-btn:hover {
            background: #218838;
        }

        .technicians-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 1.5rem;
        }

        .technician-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .technician-card:hover {
            transform: translateY(-5px);
        }

        .technician-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .technician-info h3 {
            color: #333;
            margin-bottom: 0.5rem;
        }

        .technician-phone {
            color: #888;
            font-size: 0.85rem;
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

        .status-busy {
            background: #fff3cd;
            color: #856404;
        }

        .status-offline {
            background: #f8d7da;
            color: #721c24;
        }

        .technician-details {
            margin: 1rem 0;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .detail-label {
            color: #666;
            font-weight: 500;
        }

        .detail-value {
            color: #333;
        }

        .rating {
            color: #ffc107;
            font-size: 0.9rem;
        }

        .technician-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .action-btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.8rem;
            flex: 1;
        }

        .edit-btn {
            background: #007bff;
            color: white;
        }

        .edit-btn:hover {
            background: #0056b3;
        }

        .delete-btn {
            background: #dc3545;
            color: white;
        }

        .delete-btn:hover {
            background: #c82333;
        }

        .message {
            background: #d4edda;
            color: #155724;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            text-align: center;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            text-align: center;
        }

        /* Modal Styles */
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
            background-color: #fefefe;
            margin: 5% auto;
            padding: 2rem;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            position: relative;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            position: absolute;
            top: 1rem;
            right: 1.5rem;
        }

        .close:hover {
            color: #000;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #333;
        }

        .form-group input, .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
        }

        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 1.5rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            transition: background 0.3s ease;
        }

        .btn-primary {
            background: #007bff;
            color: white;
        }

        .btn-primary:hover {
            background: #0056b3;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #545b62;
        }

        @media (max-width: 768px) {
            .controls-row {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-box {
                min-width: auto;
            }
            
            .technicians-grid {
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
            <a href="vehicles.php" class="nav-link">
                <i class="fas fa-truck"></i>Service Vehicles
            </a>
            <a href="technicians.php" class="nav-link <?php echo $current_page === 'technicians' ? 'active' : ''; ?>">
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
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h2 class="mb-0">Technician Management</h2>
                    <p class="text-muted mb-0">Manage and monitor all technicians in the EV Mobile Station network</p>
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

        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_technicians; ?></div>
                <div class="stat-label">Total Technicians</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $active_technicians; ?></div>
                <div class="stat-label">Available</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $busy_technicians; ?></div>
                <div class="stat-label">Busy</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $offline_technicians; ?></div>
                <div class="stat-label">Offline</div>
            </div>
        </div>

        <div class="controls">
            <div class="controls-row">
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="Search technicians by name, phone, or specialization..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="filter-buttons">
                    <button class="filter-btn <?php echo $filter === 'all' ? 'active' : ''; ?>" 
                            onclick="filterTechnicians('all')">All</button>
                    <button class="filter-btn <?php echo $filter === 'available' ? 'active' : ''; ?>" 
                            onclick="filterTechnicians('available')">Available</button>
                    <button class="filter-btn <?php echo $filter === 'busy' ? 'active' : ''; ?>" 
                            onclick="filterTechnicians('busy')">Busy</button>
                    <button class="filter-btn <?php echo $filter === 'offline' ? 'active' : ''; ?>" 
                            onclick="filterTechnicians('offline')">Offline</button>
                </div>
                <button class="add-btn" onclick="showAddForm()">
                    <i class="fas fa-plus me-2"></i>Add Technician
                </button>
            </div>
        </div>

        <div class="technicians-grid">
            <?php if (empty($filtered_technicians)): ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 2rem; color: #666;">
                    No technicians found.
                </div>
            <?php else: ?>
                <?php foreach ($filtered_technicians as $technician): ?>
                    <div class="technician-card">
                        <div class="technician-header">
                            <div class="technician-info">
                                <h3><?php echo htmlspecialchars($technician['full_name']); ?></h3>
                                <div class="technician-phone"><?php echo htmlspecialchars($technician['phone']); ?></div>
                            </div>
                            <span class="status-badge status-<?php echo $technician['status']; ?>">
                                <?php echo ucfirst($technician['status']); ?>
                            </span>
                        </div>
                        
                        <div class="technician-details">
                            <div class="detail-row">
                                <span class="detail-label">Specialization:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($technician['specialization']); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Experience:</span>
                                <span class="detail-value"><?php echo $technician['experience_years'] ?? 0; ?> years</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Total Requests:</span>
                                <span class="detail-value"><?php echo $technician['total_requests'] ?? 0; ?></span>
                            </div>
                            <?php if (isset($technician['rating']) && $technician['rating'] > 0): ?>
                            <div class="detail-row">
                                <span class="detail-label">Rating:</span>
                                <span class="detail-value">
                                    <i class="fas fa-star"></i>
                                    <?php echo number_format($technician['rating'], 1); ?>
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="technician-actions">
                            <button class="action-btn edit-btn" onclick="editTechnician(<?php echo $technician['id']; ?>)">
                                <i class="fas fa-edit me-1"></i>Edit
                            </button>
                        
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Technician Modal -->
    <div id="addTechnicianModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('addTechnicianModal')">&times;</span>
            <h2>Add New Technician</h2>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="name">Full Name *</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="phone">Phone *</label>
                    <input type="tel" id="phone" name="phone" required>
                </div>
                <div class="form-group">
                    <label for="specialization">Specialization *</label>
                    <select id="specialization" name="specialization" required>
                        <option value="">Select specialization</option>
                        <option value="electrical">Electrical</option>
                        <option value="mechanical">Mechanical</option>
                        <option value="both">Both</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="experience_years">Experience (Years)</label>
                    <input type="number" id="experience_years" name="experience_years" min="0" max="50" value="0">
                </div>
                <div class="form-group">
                    <label for="password">Password (Optional)</label>
                    <input type="password" id="password" name="password" placeholder="Leave empty for auto-generated">
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addTechnicianModal')">Cancel</button>
                    <button type="submit" name="add_technician" class="btn btn-primary">Add Technician</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Technician Modal -->
    <div id="editTechnicianModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('editTechnicianModal')">&times;</span>
            <h2>Edit Technician</h2>
            <form method="POST" action="" id="editForm">
                <input type="hidden" id="edit_technician_id" name="technician_id">
                <div class="form-group">
                    <label for="edit_name">Full Name *</label>
                    <input type="text" id="edit_name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="edit_phone">Phone *</label>
                    <input type="tel" id="edit_phone" name="phone" required>
                </div>
                <div class="form-group">
                    <label for="edit_specialization">Specialization *</label>
                    <select id="edit_specialization" name="specialization" required>
                        <option value="">Select specialization</option>
                        <option value="electrical">Electrical</option>
                        <option value="mechanical">Mechanical</option>
                        <option value="both">Both</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_experience_years">Experience (Years)</label>
                    <input type="number" id="edit_experience_years" name="experience_years" min="0" max="50">
                </div>
                <div class="form-group">
                    <label for="edit_status">Status *</label>
                    <select id="edit_status" name="status" required>
                        <option value="available">Available</option>
                        <option value="busy">Busy</option>
                        <option value="offline">Offline</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editTechnicianModal')">Cancel</button>
                    <button type="submit" name="edit_technician" class="btn btn-primary">Update Technician</button>
                </div>
            </form>
        </div>
        </div>
    </div>

    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const cards = document.querySelectorAll('.technician-card');
            
            cards.forEach(card => {
                const text = card.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });

        // Filter functionality
        function filterTechnicians(status) {
            const cards = document.querySelectorAll('.technician-card');
            const filterBtns = document.querySelectorAll('.filter-btn');
            
            // Update active button
            filterBtns.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            cards.forEach(card => {
                const statusBadge = card.querySelector('.status-badge');
                const cardStatus = statusBadge.textContent.toLowerCase().trim();
                
                if (status === 'all' || cardStatus === status) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        // Modal functions
        function showAddForm() {
            document.getElementById('addTechnicianModal').style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }

        // Edit technician
        function editTechnician(id) {
            // Get technician data and populate form
            const technicianCard = event.target.closest('.technician-card');
            const name = technicianCard.querySelector('h3').textContent;
            const phone = technicianCard.querySelector('.technician-phone').textContent;
            const specialization = technicianCard.querySelector('.detail-row:first-child .detail-value').textContent;
            const experience = technicianCard.querySelector('.detail-row:nth-child(2) .detail-value').textContent.replace(' years', '');
            const status = technicianCard.querySelector('.status-badge').textContent.toLowerCase().trim();
            
            document.getElementById('edit_technician_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_phone').value = phone;
            document.getElementById('edit_specialization').value = specialization.toLowerCase();
            document.getElementById('edit_experience_years').value = experience;
            document.getElementById('edit_status').value = status;
            
            document.getElementById('editTechnicianModal').style.display = 'block';
        }

        // Delete technician
        function deleteTechnician(id) {
            if (confirm('Are you sure you want to delete this technician?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `<input type="hidden" name="delete_technician" value="1">
                                <input type="hidden" name="technician_id" value="${id}">`;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

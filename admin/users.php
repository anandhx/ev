<?php
session_start();
require_once '../includes/functions.php';

// Simple admin session check
if (!isset($_SESSION['admin_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$message = '';
$current_page = 'users';

// Initialize managers
$userManager = new UserManager();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_user'])) {
        $user_id = $_POST['user_id'];
        $user_data = [
            'full_name' => $_POST['full_name'],
            'phone' => $_POST['phone'],
            'vehicle_model' => $_POST['vehicle_model'],
            'vehicle_plate' => $_POST['vehicle_plate']
        ];
        
        if ($userManager->updateUser($user_id, $user_data)) {
            $message = 'User updated successfully!';
        } else {
            $message = 'Error updating user.';
        }
    } elseif (isset($_POST['delete_user'])) {
        $user_id = $_POST['user_id'];
        $message = 'User deleted successfully!';
    } elseif (isset($_POST['suspend_user'])) {
        $user_id = $_POST['user_id'];
        $message = 'User suspended successfully!';
    } elseif (isset($_POST['activate_user'])) {
        $user_id = $_POST['user_id'];
        $message = 'User activated successfully!';
    }
}

// Get real users data from database
$users = $userManager->getAllUsers();

// Filter users
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

$filtered_users = $users;

if ($filter === 'with_vehicle') {
    $filtered_users = array_filter($users, function($u) {
        return !empty($u['vehicle_model']);
    });
} elseif ($filter === 'without_vehicle') {
    $filtered_users = array_filter($users, function($u) {
        return empty($u['vehicle_model']);
    });
}

if ($search) {
    $filtered_users = array_filter($filtered_users, function($u) use ($search) {
        return stripos($u['full_name'], $search) !== false || 
               stripos($u['email'], $search) !== false ||
               stripos($u['vehicle_model'], $search) !== false;
    });
}

$total_users = count($users);
$users_with_vehicle = count(array_filter($users, function($u) { return !empty($u['vehicle_model']); }));
$users_without_vehicle = count(array_filter($users, function($u) { return empty($u['vehicle_model']); }));
$recent_users = count(array_filter($users, function($u) { 
    return strtotime($u['created_at']) > strtotime('-30 days'); 
}));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Admin Dashboard</title>
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

        .users-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .users-table th,
        .users-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e1e5e9;
        }

        .users-table th {
            background: rgba(255, 255, 255, 0.95);
            font-weight: 600;
            color: #333;
        }

        .users-table tr:hover {
            background: rgba(255, 255, 255, 0.7);
        }



        .message {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            padding: 15px 20px;
            border-radius: 15px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.3s ease;
        }

        .message .btn-close {
            background: none;
            border: none;
            font-size: 1.2rem;
            color: #155724;
            opacity: 0.7;
            cursor: pointer;
            padding: 0;
            margin: 0;
        }

        .message .btn-close:hover {
            opacity: 1;
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
        }

        /* Modal styling */
        .modal-content {
            border-radius: 15px;
            border: none;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border-radius: 15px 15px 0 0;
            border-bottom: none;
        }

        .modal-header .btn-close {
            filter: invert(1);
        }

        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }

        .form-control {
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .form-control[readonly] {
            background-color: #f8f9fa;
            color: #6c757d;
        }

        .modal-footer {
            border-top: 1px solid #e1e5e9;
            padding: 20px;
        }

        .modal-footer .btn {
            padding: 10px 25px;
            border-radius: 25px;
            font-weight: 600;
        }

        .btn-loading {
            display: inline-flex;
            align-items: center;
        }

        .d-none {
            display: none !important;
        }

        .btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
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
                    <h2 class="mb-0">User Management</h2>
                    <p class="text-muted mb-0">Manage and monitor all users in the EV Mobile Station network</p>
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
                <div class="message" id="messageAlert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close float-end" onclick="hideMessage()"></button>
                </div>
            <?php endif; ?>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_users; ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $users_with_vehicle; ?></div>
                    <div class="stat-label">With Vehicle</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $users_without_vehicle; ?></div>
                    <div class="stat-label">Without Vehicle</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $recent_users; ?></div>
                    <div class="stat-label">New (30 days)</div>
                </div>
            </div>

            <div class="filter-tabs">
                <a href="?filter=all" class="filter-tab <?php echo $filter === 'all' ? 'active' : ''; ?>">All Users</a>
                <a href="?filter=with_vehicle" class="filter-tab <?php echo $filter === 'with_vehicle' ? 'active' : ''; ?>">With Vehicle</a>
                <a href="?filter=without_vehicle" class="filter-tab <?php echo $filter === 'without_vehicle' ? 'active' : ''; ?>">Without Vehicle</a>
            </div>

            <div class="search-box">
                <form method="GET" style="display: flex; gap: 10px; width: 100%;">
                    <input type="text" name="search" class="search-input" placeholder="Search users by name, email, or vehicle..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-primary">Search</button>
                    <?php if ($search): ?>
                        <a href="?" class="btn btn-secondary">Clear</a>
                    <?php endif; ?>
                </form>
            </div>

            <table class="users-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Vehicle</th>
                        <th>Phone</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($filtered_users as $user): ?>
                        <tr>
                            <td>
                                <div style="font-weight: 600; color: #333;"><?php echo htmlspecialchars($user['full_name']); ?></div>
                                <div style="font-size: 0.9rem; color: #666;">@<?php echo htmlspecialchars($user['username']); ?></div>
                            </td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <?php if (!empty($user['vehicle_model'])): ?>
                                    <div><?php echo htmlspecialchars($user['vehicle_model']); ?></div>
                                    <?php if (!empty($user['vehicle_plate'])): ?>
                                        <div style="font-size: 0.9rem; color: #666;"><?php echo htmlspecialchars($user['vehicle_plate']); ?></div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="color: #999; font-style: italic;">No vehicle</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></td>
                            <td>
                                <div><?php echo date('M j, Y', strtotime($user['created_at'])); ?></div>
                                <div style="font-size: 0.9rem; color: #666;"><?php echo date('g:i A', strtotime($user['created_at'])); ?></div>
                            </td>
                            <td>
                                <div style="display: flex; gap: 5px;">
                                    <button class="btn btn-primary btn-sm" onclick="editUser(<?php echo $user['id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                             
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="editUserForm">
                    <div class="modal-body">
                        <input type="hidden" name="user_id" id="edit_user_id">
                        <input type="hidden" name="update_user" value="1">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_full_name" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="edit_full_name" name="full_name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_phone" class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" id="edit_phone" name="phone">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_vehicle_model" class="form-label">Vehicle Model</label>
                                    <input type="text" class="form-control" id="edit_vehicle_model" name="vehicle_model" placeholder="e.g., Tesla Model 3">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_vehicle_plate" class="form-label">Vehicle Plate</label>
                                    <input type="text" class="form-control" id="edit_vehicle_plate" name="vehicle_plate" placeholder="e.g., ABC-123">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="edit_username" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="edit_email" readonly>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_created_at" class="form-label">Joined Date</label>
                                    <input type="text" class="form-control" id="edit_created_at" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_status" class="form-label">Status</label>
                                    <select class="form-control" id="edit_status" name="status">
                                        <option value="active">Active</option>
                                        <option value="suspended">Suspended</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="updateBtn">
                            <span class="btn-text">Update User</span>
                            <span class="btn-loading d-none">
                                <i class="fas fa-spinner fa-spin me-2"></i>Updating...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function editUser(userId) {
            // Get user data from the table row
            const row = event.target.closest('tr');
            const cells = row.cells;
            
            // Extract user data from table cells
            const fullName = cells[0].querySelector('div:first-child').textContent;
            const username = cells[0].querySelector('div:last-child').textContent.replace('@', '');
            const email = cells[1].textContent;
            const vehicleModel = cells[2].querySelector('div:first-child') ? cells[2].querySelector('div:first-child').textContent : '';
            const vehiclePlate = cells[2].querySelector('div:last-child') ? cells[2].querySelector('div:last-child').textContent : '';
            const phone = cells[3].textContent !== 'N/A' ? cells[3].textContent : '';
            const joinedDate = cells[4].querySelector('div:first-child').textContent + ' ' + cells[4].querySelector('div:last-child').textContent;
            
            // Populate modal fields
            document.getElementById('edit_user_id').value = userId;
            document.getElementById('edit_full_name').value = fullName;
            document.getElementById('edit_username').value = username;
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_phone').value = phone;
            document.getElementById('edit_vehicle_model').value = vehicleModel;
            document.getElementById('edit_vehicle_plate').value = vehiclePlate;
            document.getElementById('edit_created_at').value = joinedDate;
            
            // Show the modal
            const modal = new bootstrap.Modal(document.getElementById('editUserModal'));
            modal.show();
        }

        function deleteUser(userId) {
            if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `<input type="hidden" name="delete_user" value="1">
                                <input type="hidden" name="user_id" value="${userId}">`;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Add form validation and loading state
        document.getElementById('editUserForm').addEventListener('submit', function(e) {
            const fullName = document.getElementById('edit_full_name').value.trim();
            const phone = document.getElementById('edit_phone').value.trim();
            
            if (!fullName) {
                e.preventDefault();
                alert('Full name is required!');
                return false;
            }
            
            // Basic phone validation
            if (phone && !/^[\+]?[1-9][\d]{0,15}$/.test(phone.replace(/[\s\-\(\)]/g, ''))) {
                e.preventDefault();
                alert('Please enter a valid phone number!');
                return false;
            }

            // Show loading state
            const updateBtn = document.getElementById('updateBtn');
            const btnText = updateBtn.querySelector('.btn-text');
            const btnLoading = updateBtn.querySelector('.btn-loading');
            
            btnText.classList.add('d-none');
            btnLoading.classList.remove('d-none');
            updateBtn.disabled = true;
        });

        // Auto-hide success message after 5 seconds
        const messageAlert = document.getElementById('messageAlert');
        if (messageAlert) {
            setTimeout(() => {
                hideMessage();
            }, 5000);
        }

        function hideMessage() {
            const messageAlert = document.getElementById('messageAlert');
            if (messageAlert) {
                messageAlert.style.transition = 'opacity 0.5s ease';
                messageAlert.style.opacity = '0';
                setTimeout(() => {
                    messageAlert.remove();
                }, 500);
            }
        }
    </script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 
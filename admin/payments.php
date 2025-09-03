<?php
session_start();
require_once '../includes/functions.php';

// Simple admin session check
if (!isset($_SESSION['admin_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$message = '';
$current_page = 'payments';

$db = Database::getInstance();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_payment'])) {
        $payment_id = (int)$_POST['payment_id'];
        $status = $_POST['status'];
        try {
            $db->executeQuery("UPDATE payments SET status = ? WHERE id = ?", [$status, $payment_id]);
            // Also update related service request payment_status
            $sr = $db->executeQuery("SELECT service_request_id FROM payments WHERE id = ?", [$payment_id])->fetch();
            if ($sr) {
                $db->executeQuery("UPDATE service_requests SET payment_status = ? WHERE id = ?", [$status === 'completed' ? 'paid' : ($status === 'refunded' ? 'pending' : $status), $sr['service_request_id']]);
            }
            $message = 'Payment status updated successfully!';
        } catch (Exception $e) {
            $message = 'Failed to update payment status.';
        }
    } elseif (isset($_POST['refund_payment'])) {
        $payment_id = (int)$_POST['payment_id'];
        try {
            $db->executeQuery("UPDATE payments SET status = 'refunded' WHERE id = ?", [$payment_id]);
            $sr = $db->executeQuery("SELECT service_request_id FROM payments WHERE id = ?", [$payment_id])->fetch();
            if ($sr) {
                $db->executeQuery("UPDATE service_requests SET payment_status = 'pending' WHERE id = ?", [$sr['service_request_id']]);
            }
            $message = 'Refund processed successfully!';
        } catch (Exception $e) {
            $message = 'Failed to process refund.';
        }
    }
}

// Filters
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

// Fetch payments from DB
$sql = "SELECT p.id, p.transaction_id, p.amount, p.payment_method, p.status, p.created_at,
               sr.id AS request_id, sr.request_type,
               u.full_name AS user_name, u.email AS user_email
        FROM payments p
        JOIN service_requests sr ON p.service_request_id = sr.id
        JOIN users u ON sr.user_id = u.id
        ORDER BY p.created_at DESC";
$stmt = $db->executeQuery($sql);
$payments = $stmt->fetchAll();

// Filter payments
$filtered_payments = $payments;
if ($filter !== 'all' && !empty($filter)) {
    $filtered_payments = array_filter($filtered_payments, function($p) use ($filter) {
        return $p['status'] === $filter;
    });
}

if ($search) {
    $q = strtolower($search);
    $filtered_payments = array_filter($filtered_payments, function($p) use ($q) {
        return strpos(strtolower($p['user_name']), $q) !== false ||
               strpos(strtolower($p['user_email']), $q) !== false ||
               strpos(strtolower($p['transaction_id'] ?? ''), $q) !== false ||
               strpos(strtolower('REQ-' . $p['request_id']), $q) !== false;
    });
}

// Stats
$total_payments = count($payments);
$completed_payments = count(array_filter($payments, function($p) { return $p['status'] === 'completed'; }));
$pending_payments = count(array_filter($payments, function($p) { return $p['status'] === 'pending'; }));
$failed_payments = count(array_filter($payments, function($p) { return $p['status'] === 'failed'; }));

$total_revenue = array_sum(array_map(function($p) { return $p['status'] === 'completed' ? (float)$p['amount'] : 0; }, $payments));
$pending_revenue = array_sum(array_map(function($p) { return $p['status'] === 'pending' ? (float)$p['amount'] : 0; }, $payments));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Management - Admin Dashboard</title>
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
        
        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }
        
        .sidebar {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            height: 100vh;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            width: 250px;
            overflow-y: auto;
            overflow-x: hidden;
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
            margin-left: -71px;
        }
        
        /* Ensure sidebar is always visible */
        .col-md-3.col-lg-2 {
            min-width: 250px;
            flex-shrink: 0;
        }
        
        .sidebar {
            min-width: 250px;
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }

        .revenue-card {
            background: linear-gradient(135deg, var(--success-color), #20c997);
            color: white;
        }

        .revenue-card .stat-number { color: white; }
        .revenue-card .stat-label { color: rgba(255, 255, 255, 0.8); }

        .controls {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }

        .controls-row { display: flex; gap: 1rem; align-items: center; flex-wrap: wrap; }
        .search-box { flex: 1; min-width: 300px; }
        .search-box input { width: 100%; padding: 0.75rem 1rem; border: 2px solid #e1e5e9; border-radius: 10px; font-size: 1rem; transition: border-color 0.3s ease; }
        .search-box input:focus { outline: none; border-color: var(--primary-color); }
        .filter-buttons { display: flex; gap: 0.5rem; }
        .filter-btn { padding: 0.75rem 1.5rem; border: none; border-radius: 10px; background: #f8f9fa; color: #666; cursor: pointer; transition: all 0.3s ease; font-size: 0.9rem; }
        .filter-btn:hover, .filter-btn.active { background: var(--primary-color); color: white; }

        .payments-table { background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.08); }
        .table-header { background: #f8f9fa; padding: 1.5rem; border-bottom: 1px solid #e9ecef; }
        .table-header h3 { color: #333; margin: 0; }
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 1rem 1.5rem; text-align: left; border-bottom: 1px solid #e9ecef; }
        th { background: #f8f9fa; font-weight: 600; color: #495057; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px; }
        td { color: #333; font-size: 0.9rem; }

        .status-badge { padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.8rem; font-weight: 600; text-transform: uppercase; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-failed { background: #f8d7da; color: #721c24; }
        .status-refunded { background: #e2e3e5; color: #495057; }

        .amount { font-weight: 600; color: var(--success-color); }
        .payment-actions { display: flex; gap: 0.5rem; }
        .action-btn { padding: 0.5rem 1rem; border: none; border-radius: 8px; cursor: pointer; transition: all 0.3s ease; font-size: 0.8rem; }
        .view-btn { background: var(--info-color); color: white; }
        .view-btn:hover { background: #138496; }
        .update-btn { background: var(--warning-color); color: #212529; }
        .update-btn:hover { background: #e0a800; }
        .refund-btn { background: var(--danger-color); color: white; }
        .refund-btn:hover { background: #c82333; }

        @media (max-width: 768px) {
            .sidebar { position: fixed; width: 100%; height: auto; max-height: 100vh; transform: translateX(-100%); transition: transform 0.3s ease; }
            .sidebar.show { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 1rem; }
            .controls-row { flex-direction: column; align-items: stretch; }
            .search-box { min-width: auto; }
            .table-container { font-size: 0.8rem; }
            th, td { padding: 0.75rem 1rem; }
        }

        /* Fallback grid helpers */
        .container-fluid { width: 100%; padding-right: 15px; padding-left: 15px; margin-right: auto; margin-left: auto; }
        .row { display: flex; flex-wrap: wrap; margin-right: -15px; margin-left: -15px; }
        .col-md-3 { flex: 0 0 25%; max-width: 25%; padding-right: 15px; padding-left: 15px; }
        .col-lg-2 { flex: 0 0 16.666667%; max-width: 16.666667%; }
        .col-md-9 { flex: 0 0 75%; max-width: 75%; padding-right: 15px; padding-left: 15px; }
        .col-lg-10 { flex: 0 0 83.333333%; max-width: 83.333333%; }
        .px-0 { padding-right: 0 !important; padding-left: 0 !important; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0" style="position: relative; z-index: 1000;">
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
                        <a href="users.php" class="nav-link">
                            <i class="fas fa-users"></i>Users
                        </a>
                        <a href="payments.php" class="nav-link active">
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
                                <button class="btn btn-primary d-md-none me-3" id="sidebarToggle">
                                    <i class="fas fa-bars"></i>
                                </button>
                                <h2 class="mb-0"><i class="fas fa-credit-card me-2"></i>Payment Management</h2>
                                <p class="text-muted mb-0">Monitor and manage all payment transactions in the EV Mobile Station network</p>
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

                    <?php if ($message): ?>
                        <div class="message"><?php echo htmlspecialchars($message); ?></div>
                    <?php endif; ?>

                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $total_payments; ?></div>
                            <div class="stat-label">Total Payments</div>
                        </div>
                    
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $completed_payments; ?></div>
                            <div class="stat-label">Completed</div>
                        </div>
                       
                    </div>

                    <div class="controls">
                        <form method="GET" class="controls-row" style="width:100%">
                            <div class="search-box">
                                <input type="text" name="search" id="searchInput" placeholder="Search payments by user name, email, or transaction ID..." 
                                       value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="filter-buttons">
                                <button type="submit" name="filter" value="all" class="filter-btn <?php echo $filter === 'all' ? 'active' : ''; ?>">All</button>
                                <button type="submit" name="filter" value="completed" class="filter-btn <?php echo $filter === 'completed' ? 'active' : ''; ?>">Completed</button>
                                <button type="submit" name="filter" value="pending" class="filter-btn <?php echo $filter === 'pending' ? 'active' : ''; ?>">Pending</button>
                                <button type="submit" name="filter" value="failed" class="filter-btn <?php echo $filter === 'failed' ? 'active' : ''; ?>">Failed</button>
                                <button type="submit" name="filter" value="refunded" class="filter-btn <?php echo $filter === 'refunded' ? 'active' : ''; ?>">Refunded</button>
                            </div>
                        </form>
                    </div>

                    <div class="payments-table">
                        <div class="table-header">
                            <h3>Payment Transactions</h3>
                        </div>
                        
                        <?php if (empty($filtered_payments)): ?>
                            <div class="empty-state">
                                <i class="fas fa-credit-card"></i>
                                <p>No payments found matching your criteria</p>
                            </div>
                        <?php else: ?>
                            <div class="table-container">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Transaction ID</th>
                                            <th>User</th>
                                            <th>Service Type</th>
                                            <th>Amount</th>
                                            <th>Payment Method</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($filtered_payments as $payment): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($payment['transaction_id'] ?? ''); ?></strong>
                                                    <br>
                                                    <small class="text-muted"><?php echo 'REQ-' . (int)$payment['request_id']; ?></small>
                                                </td>
                                                <td>
                                                    <div><?php echo htmlspecialchars($payment['user_name']); ?></div>
                                                    <small class="text-muted"><?php echo htmlspecialchars($payment['user_email']); ?></small>
                                                </td>
                                                <td><?php echo htmlspecialchars(ucfirst($payment['request_type'])); ?></td>
                                                <td class="amount"><?php echo number_format((float)$payment['amount'], 2); ?></td>
                                                <td><?php echo htmlspecialchars(str_replace('_',' ', $payment['payment_method'])); ?></td>
                                                <td>
                                                    <span class="status-badge status-<?php echo $payment['status']; ?>">
                                                        <?php echo ucfirst($payment['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M j, Y g:i A', strtotime($payment['created_at'])); ?></td>
                                                <td>
                                                    <div class="payment-actions">
                                                        <form method="POST" style="display:inline">
                                                            <input type="hidden" name="payment_id" value="<?php echo (int)$payment['id']; ?>">
                                                            <select name="status" class="action-btn update-btn">
                                                                <option value="pending" <?php echo $payment['status']==='pending'?'selected':''; ?>>Pending</option>
                                                                <option value="completed" <?php echo $payment['status']==='completed'?'selected':''; ?>>Completed</option>
                                                                <option value="failed" <?php echo $payment['status']==='failed'?'selected':''; ?>>Failed</option>
                                                                <option value="refunded" <?php echo $payment['status']==='refunded'?'selected':''; ?>>Refunded</option>
                                                            </select>
                                                            <button class="action-btn update-btn" type="submit" name="update_payment"><i class="fas fa-edit"></i></button>
                                                        </form>
                                                        <?php if ($payment['status'] === 'completed'): ?>
                                                        <form method="POST" style="display:inline" onsubmit="return confirm('Refund this payment?');">
                                                            <input type="hidden" name="payment_id" value="<?php echo (int)$payment['id']; ?>">
                                                         </form>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mobile sidebar toggle
        document.getElementById('sidebarToggle')?.addEventListener('click', function() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('show');
        });
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.querySelector('.sidebar');
            const sidebarToggle = document.getElementById('sidebarToggle');
            
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(event.target) && !sidebarToggle.contains(event.target)) {
                    sidebar.classList.remove('show');
                }
            }
        });
    </script>
</body>
</html>


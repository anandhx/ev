<?php
session_start();
require_once '../includes/functions.php';
require_once '../includes/dummy_data.php';

// Simple admin session check
if (!isset($_SESSION['admin_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$message = '';
$current_page = 'payments';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_payment'])) {
        $payment_id = $_POST['payment_id'];
        $status = $_POST['status'];
        $message = 'Payment status updated successfully!';
    } elseif (isset($_POST['refund_payment'])) {
        $payment_id = $_POST['payment_id'];
        $message = 'Refund processed successfully!';
    }
}

// Dummy payments data
$payments = [
    [
        'id' => 1,
        'user_name' => 'John Doe',
        'user_email' => 'john.doe@email.com',
        'amount' => 75.00,
        'payment_method' => 'Credit Card',
        'status' => 'completed',
        'service_type' => 'Charging',
        'request_id' => 'REQ-001',
        'created_at' => '2024-01-15 10:30:00',
        'transaction_id' => 'TXN-2024-001'
    ],
    [
        'id' => 2,
        'user_name' => 'Jane Smith',
        'user_email' => 'jane.smith@email.com',
        'amount' => 120.00,
        'payment_method' => 'PayPal',
        'status' => 'pending',
        'service_type' => 'Mechanical',
        'request_id' => 'REQ-002',
        'created_at' => '2024-01-20 14:00:00',
        'transaction_id' => 'TXN-2024-002'
    ],
    [
        'id' => 3,
        'user_name' => 'Mike Johnson',
        'user_email' => 'mike.johnson@email.com',
        'amount' => 95.00,
        'payment_method' => 'Credit Card',
        'status' => 'completed',
        'service_type' => 'Hybrid',
        'request_id' => 'REQ-003',
        'created_at' => '2024-01-18 09:00:00',
        'transaction_id' => 'TXN-2024-003'
    ],
    [
        'id' => 4,
        'user_name' => 'Sarah Wilson',
        'user_email' => 'sarah.wilson@email.com',
        'amount' => 150.00,
        'payment_method' => 'Bank Transfer',
        'status' => 'failed',
        'service_type' => 'Emergency',
        'request_id' => 'REQ-004',
        'created_at' => '2024-01-22 16:30:00',
        'transaction_id' => 'TXN-2024-004'
    ],
    [
        'id' => 5,
        'user_name' => 'David Brown',
        'user_email' => 'david.brown@email.com',
        'amount' => 85.00,
        'payment_method' => 'Credit Card',
        'status' => 'completed',
        'service_type' => 'Charging',
        'request_id' => 'REQ-005',
        'created_at' => '2024-01-25 11:15:00',
        'transaction_id' => 'TXN-2024-005'
    ],
    [
        'id' => 6,
        'user_name' => 'Lisa Chen',
        'user_email' => 'lisa.chen@email.com',
        'amount' => 200.00,
        'payment_method' => 'PayPal',
        'status' => 'pending',
        'service_type' => 'Mechanical',
        'request_id' => 'REQ-006',
        'created_at' => '2024-01-26 13:45:00',
        'transaction_id' => 'TXN-2024-006'
    ]
];

// Filter payments
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

$filtered_payments = $payments;

if ($filter !== 'all') {
    $filtered_payments = array_filter($payments, function($payment) use ($filter) {
        return $payment['status'] === $filter;
    });
}

if ($search) {
    $filtered_payments = array_filter($filtered_payments, function($payment) use ($search) {
        return stripos($payment['user_name'], $search) !== false || 
               stripos($payment['user_email'], $search) !== false ||
               stripos($payment['transaction_id'], $search) !== false;
    });
}

$total_payments = count($payments);
$completed_payments = count(array_filter($payments, function($p) { return $p['status'] === 'completed'; }));
$pending_payments = count(array_filter($payments, function($p) { return $p['status'] === 'pending'; }));
$failed_payments = count(array_filter($payments, function($p) { return $p['status'] === 'failed'; }));

$total_revenue = array_sum(array_map(function($p) { 
    return $p['status'] === 'completed' ? $p['amount'] : 0; 
}, $payments));

$pending_revenue = array_sum(array_map(function($p) { 
    return $p['status'] === 'pending' ? $p['amount'] : 0; 
}, $payments));
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

        .revenue-card .stat-number {
            color: white;
        }

        .revenue-card .stat-label {
            color: rgba(255, 255, 255, 0.8);
        }

        .controls {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
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
            border-color: var(--primary-color);
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
            background: var(--primary-color);
            color: white;
        }

        .payments-table {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }

        .table-header {
            background: #f8f9fa;
            padding: 1.5rem;
            border-bottom: 1px solid #e9ecef;
        }

        .table-header h3 {
            color: #333;
            margin: 0;
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 1rem 1.5rem;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }

        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            color: #333;
            font-size: 0.9rem;
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-completed {
            background: #d4edda;
            color: #155724;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-failed {
            background: #f8d7da;
            color: #721c24;
        }

        .amount {
            font-weight: 600;
            color: var(--success-color);
        }

        .payment-actions {
            display: flex;
            gap: 0.5rem;
        }

        .action-btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.8rem;
        }

        .view-btn {
            background: var(--info-color);
            color: white;
        }

        .view-btn:hover {
            background: #138496;
        }

        .update-btn {
            background: var(--warning-color);
            color: #212529;
        }

        .update-btn:hover {
            background: #e0a800;
        }

        .refund-btn {
            background: var(--danger-color);
            color: white;
        }

        .refund-btn:hover {
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

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #666;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #ccc;
        }

        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                width: 100%;
                height: auto;
                max-height: 100vh;
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .controls-row {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-box {
                min-width: auto;
            }
            
            .table-container {
                font-size: 0.8rem;
            }
            
            th, td {
                padding: 0.75rem 1rem;
            }
        }
        
        /* Fallback CSS if Bootstrap fails to load */
        .container-fluid {
            width: 100%;
            padding-right: 15px;
            padding-left: 15px;
            margin-right: auto;
            margin-left: auto;
        }
        
        .row {
            display: flex;
            flex-wrap: wrap;
            margin-right: -15px;
            margin-left: -15px;
        }
        
        .col-md-3 {
            flex: 0 0 25%;
            max-width: 25%;
            padding-right: 15px;
            padding-left: 15px;
        }
        
        .col-lg-2 {
            flex: 0 0 16.666667%;
            max-width: 16.666667%;
        }
        
        .col-md-9 {
            flex: 0 0 75%;
            max-width: 75%;
            padding-right: 15px;
            padding-left: 15px;
        }
        
        .col-lg-10 {
            flex: 0 0 83.333333%;
            max-width: 83.333333%;
        }
        
        .px-0 {
            padding-right: 0 !important;
            padding-left: 0 !important;
        }
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
                        <div class="stat-card revenue-card">
                            <div class="stat-number">$<?php echo number_format($total_revenue, 2); ?></div>
                            <div class="stat-label">Total Revenue</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $completed_payments; ?></div>
                            <div class="stat-label">Completed</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number">$<?php echo number_format($pending_revenue, 2); ?></div>
                            <div class="stat-label">Pending Revenue</div>
                        </div>
                    </div>

                    <div class="controls">
                        <div class="controls-row">
                            <div class="search-box">
                                <input type="text" id="searchInput" placeholder="Search payments by user name, email, or transaction ID..." 
                                       value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="filter-buttons">
                                <button class="filter-btn <?php echo $filter === 'all' ? 'active' : ''; ?>" 
                                        onclick="filterPayments('all')">All</button>
                                <button class="filter-btn <?php echo $filter === 'completed' ? 'active' : ''; ?>" 
                                        onclick="filterPayments('completed')">Completed</button>
                                <button class="filter-btn <?php echo $filter === 'pending' ? 'active' : ''; ?>" 
                                        onclick="filterPayments('pending')">Pending</button>
                                <button class="filter-btn <?php echo $filter === 'failed' ? 'active' : ''; ?>" 
                                        onclick="filterPayments('failed')">Failed</button>
                            </div>
                        </div>
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
                                                    <strong><?php echo htmlspecialchars($payment['transaction_id']); ?></strong>
                                                    <br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($payment['request_id']); ?></small>
                                                </td>
                                                <td>
                                                    <div><?php echo htmlspecialchars($payment['user_name']); ?></div>
                                                    <small class="text-muted"><?php echo htmlspecialchars($payment['user_email']); ?></small>
                                                </td>
                                                <td><?php echo htmlspecialchars($payment['service_type']); ?></td>
                                                <td class="amount">$<?php echo number_format($payment['amount'], 2); ?></td>
                                                <td><?php echo htmlspecialchars($payment['payment_method']); ?></td>
                                                <td>
                                                    <span class="status-badge status-<?php echo $payment['status']; ?>">
                                                        <?php echo ucfirst($payment['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M j, Y g:i A', strtotime($payment['created_at'])); ?></td>
                                                <td>
                                                    <div class="payment-actions">
                                                        <button class="action-btn view-btn" onclick="viewPayment(<?php echo $payment['id']; ?>)">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <?php if ($payment['status'] === 'pending'): ?>
                                                            <button class="action-btn update-btn" onclick="updatePayment(<?php echo $payment['id']; ?>)">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                        <?php if ($payment['status'] === 'completed'): ?>
                                                            <button class="action-btn refund-btn" onclick="refundPayment(<?php echo $payment['id']; ?>)">
                                                                <i class="fas fa-undo"></i>
                                                            </button>
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
        document.getElementById('sidebarToggle').addEventListener('click', function() {
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
        
        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = 'table-row';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Filter functionality
        function filterPayments(status) {
            const rows = document.querySelectorAll('tbody tr');
            const filterBtns = document.querySelectorAll('.filter-btn');
            
            // Update active button
            filterBtns.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            rows.forEach(row => {
                const statusBadge = row.querySelector('.status-badge');
                const rowStatus = statusBadge.textContent.toLowerCase().trim();
                
                if (status === 'all' || rowStatus === status) {
                    row.style.display = 'table-row';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // View payment details (placeholder)
        function viewPayment(id) {
            alert(`View Payment ${id} details would open here. This is a demo interface.`);
        }

        // Update payment status (placeholder)
        function updatePayment(id) {
            alert(`Update Payment ${id} status form would open here. This is a demo interface.`);
        }

        // Refund payment (placeholder)
        function refundPayment(id) {
            if (confirm(`Are you sure you want to process a refund for payment ${id}?`)) {
                alert(`Refund for payment ${id} would be processed. This is a demo interface.`);
            }
        }
    </script>
</body>
</html>


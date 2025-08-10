<?php
session_start();
require_once 'includes/functions.php';

// Simple session check
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header('Location: login.php');
    exit;
}

// Dummy payment data
$payment_history = [
    [
        'id' => 'TXN_001_2024',
        'service_id' => 1,
        'amount' => 75.00,
        'method' => 'credit_card',
        'status' => 'completed',
        'date' => '2024-01-15 12:30:00',
        'description' => 'Emergency charging service',
        'card_last4' => '1234'
    ],
    [
        'id' => 'TXN_002_2024',
        'service_id' => 4,
        'amount' => 60.00,
        'method' => 'digital_wallet',
        'status' => 'completed',
        'date' => '2024-01-12 17:45:00',
        'description' => 'Fast charging service',
        'card_last4' => null
    ],
    [
        'id' => 'TXN_003_2024',
        'service_id' => 2,
        'amount' => 120.00,
        'method' => 'credit_card',
        'status' => 'pending',
        'date' => '2024-01-20 14:45:00',
        'description' => 'Tire replacement service',
        'card_last4' => '5678'
    ],
    [
        'id' => 'TXN_004_2024',
        'service_id' => 3,
        'amount' => 95.00,
        'method' => 'debit_card',
        'status' => 'pending',
        'date' => '2024-01-18 09:00:00',
        'description' => 'Charging and mechanical service',
        'card_last4' => '9012'
    ]
];

$payment_methods = [
    'credit_card' => 'Credit Card',
    'debit_card' => 'Debit Card',
    'digital_wallet' => 'Digital Wallet',
    'cash' => 'Cash'
];

$status_filter = $_GET['status'] ?? '';
$method_filter = $_GET['method'] ?? '';

// Apply filters
$filtered_payments = $payment_history;
if ($status_filter) {
    $filtered_payments = array_filter($filtered_payments, function($payment) use ($status_filter) {
        return $payment['status'] === $status_filter;
    });
}
if ($method_filter) {
    $filtered_payments = array_filter($filtered_payments, function($payment) use ($method_filter) {
        return $payment['method'] === $method_filter;
    });
}

// Calculate totals
$total_paid = array_sum(array_column(array_filter($payment_history, function($p) { return $p['status'] === 'completed'; }), 'amount'));
$total_pending = array_sum(array_column(array_filter($payment_history, function($p) { return $p['status'] === 'pending'; }), 'amount'));

$current_page = 'payments';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment History - EV Mobile Power & Service Station</title>
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
            position: relative;
            z-index: 1000;
            width: 100%;
            display: block;
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
        }
        
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .summary-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s;
        }

        .summary-card:hover {
            transform: translateY(-5px);
        }

        .summary-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .summary-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .summary-label {
            color: #666;
            font-size: 1rem;
        }

        .summary-card.success .summary-icon,
        .summary-card.success .summary-number {
            color: #28a745;
        }

        .summary-card.warning .summary-icon,
        .summary-card.warning .summary-number {
            color: #ffc107;
        }

        .summary-card.info .summary-icon,
        .summary-card.info .summary-number {
            color: #17a2b8;
        }

        .filters {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .filter-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #333;
        }

        .filter-group select {
            padding: 0.75rem;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
        }

        .filter-group select:focus {
            outline: none;
            border-color: #667eea;
        }

        .filter-btn {
            padding: 0.75rem 1.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            transition: transform 0.3s;
        }

        .filter-btn:hover {
            transform: translateY(-2px);
        }

        .payment-grid {
            display: grid;
            gap: 1.5rem;
        }

        .payment-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }

        .payment-card:hover {
            transform: translateY(-5px);
        }

        .payment-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f8f9fa;
        }

        .payment-info h3 {
            color: #333;
            margin-bottom: 0.5rem;
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.8rem;
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

        .payment-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .detail-item i {
            color: #667eea;
            width: 1rem;
        }

        .payment-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .action-btn {
            padding: 0.75rem 1rem;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .action-btn.primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .action-btn.secondary {
            background: #6c757d;
            color: white;
        }

        .action-btn:hover {
            transform: translateY(-2px);
        }

        .no-results {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }

        .no-results i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #dee2e6;
        }

        .top-bar {
            background: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            border-radius: 15px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-avatar {
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

        @media (max-width: 768px) {
            .filter-row {
                grid-template-columns: 1fr;
            }
            
            .payment-details {
                grid-template-columns: 1fr;
            }
            
            .payment-actions {
                flex-direction: column;
            }
            
            .main-content {
                padding: 1rem;
            }
        }

        /* Ensure sidebar is always visible */
        .col-md-3.col-lg-2 {
            min-width: 250px;
            flex-shrink: 0;
        }

        .sidebar {
            min-width: 250px;
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
                        <small>User Panel</small>
                    </div>
                    
                    <nav class="sidebar-nav">
                        <a href="dashboard.php" class="nav-link">
                            <i class="fas fa-tachometer-alt"></i>Dashboard
                        </a>
                        <a href="request-service.php" class="nav-link">
                            <i class="fas fa-plus-circle"></i>Request Service
                        </a>
                        <a href="service-history.php" class="nav-link">
                            <i class="fas fa-history"></i>Service History
                        </a>
                        <a href="track-service.php" class="nav-link">
                            <i class="fas fa-map-marker-alt"></i>Track Service
                        </a>
                        <a href="payments.php" class="nav-link active">
                            <i class="fas fa-credit-card"></i>Payments
                        </a>
                        <a href="profile.php" class="nav-link">
                            <i class="fas fa-user"></i>Profile
                        </a>
                        <a href="support.php" class="nav-link">
                            <i class="fas fa-headset"></i>Support
                        </a>
                        <a href="logout.php" class="nav-link text-danger">
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
                                <h2 class="mb-0"><i class="fas fa-credit-card"></i> Payment History</h2>
                                <p class="text-muted mb-0">View all your payment transactions and billing information</p>
                            </div>
                            <div class="col-md-6 text-end">
                                <div class="user-info justify-content-end">
                                    <div class="text-end">
                                        <div class="fw-bold"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?></small>
                                    </div>
                                    <div class="user-avatar">
                                        <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Summary Cards -->
                    <div class="summary-cards">
                        <div class="summary-card success">
                            <div class="summary-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="summary-number">$<?php echo number_format($total_paid, 2); ?></div>
                            <div class="summary-label">Total Paid</div>
                        </div>
                        <div class="summary-card warning">
                            <div class="summary-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="summary-number">$<?php echo number_format($total_pending, 2); ?></div>
                            <div class="summary-label">Pending Payments</div>
                        </div>
                        <div class="summary-card info">
                            <div class="summary-icon">
                                <i class="fas fa-receipt"></i>
                            </div>
                            <div class="summary-number"><?php echo count($payment_history); ?></div>
                            <div class="summary-label">Total Transactions</div>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="filters">
                        <form method="GET" class="filter-row">
                            <div class="filter-group">
                                <label for="status">Status</label>
                                <select id="status" name="status">
                                    <option value="">All Status</option>
                                    <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="failed" <?php echo $status_filter === 'failed' ? 'selected' : ''; ?>>Failed</option>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label for="method">Payment Method</label>
                                <select id="method" name="method">
                                    <option value="">All Methods</option>
                                    <?php foreach ($payment_methods as $key => $value): ?>
                                        <option value="<?php echo $key; ?>" <?php echo $method_filter === $key ? 'selected' : ''; ?>><?php echo $value; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="filter-group">
                                <button type="submit" class="filter-btn">
                                    <i class="fas fa-filter"></i> Filter
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Payment History -->
                    <div class="payment-grid">
                        <?php if (empty($filtered_payments)): ?>
                            <div class="no-results">
                                <i class="fas fa-search"></i>
                                <h3>No payment transactions found</h3>
                                <p>Try adjusting your filters or make a new service request.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($filtered_payments as $payment): ?>
                                <div class="payment-card">
                                    <div class="payment-header">
                                        <div class="payment-info">
                                            <h3>Transaction #<?php echo $payment['id']; ?></h3>
                                            <p><?php echo $payment['description']; ?></p>
                                        </div>
                                        <span class="status-badge status-<?php echo $payment['status']; ?>">
                                            <?php echo ucfirst($payment['status']); ?>
                                        </span>
                                    </div>

                                    <div class="payment-details">
                                        <div class="detail-item">
                                            <i class="fas fa-dollar-sign"></i>
                                            <span><strong>Amount:</strong> $<?php echo number_format($payment['amount'], 2); ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <i class="fas fa-credit-card"></i>
                                            <span><strong>Method:</strong> <?php echo $payment_methods[$payment['method']]; ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <i class="fas fa-calendar"></i>
                                            <span><strong>Date:</strong> <?php echo date('M j, Y g:i A', strtotime($payment['date'])); ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <i class="fas fa-hashtag"></i>
                                            <span><strong>Service ID:</strong> #<?php echo $payment['service_id']; ?></span>
                                        </div>
                                        <?php if ($payment['card_last4']): ?>
                                            <div class="detail-item">
                                                <i class="fas fa-credit-card"></i>
                                                <span><strong>Card:</strong> **** **** **** <?php echo $payment['card_last4']; ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="payment-actions">
                                        <?php if ($payment['status'] === 'pending'): ?>
                                            <button class="action-btn primary" onclick="processPayment('<?php echo $payment['id']; ?>')">
                                                <i class="fas fa-credit-card"></i> Process Payment
                                            </button>
                                        <?php endif; ?>
                                        
                                        <button class="action-btn secondary" onclick="viewReceipt('<?php echo $payment['id']; ?>')">
                                            <i class="fas fa-receipt"></i> View Receipt
                                        </button>
                                        
                                        <?php if ($payment['status'] === 'completed'): ?>
                                            <button class="action-btn secondary" onclick="downloadInvoice('<?php echo $payment['id']; ?>')">
                                                <i class="fas fa-download"></i> Download Invoice
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Filter form submission
        document.querySelectorAll('select').forEach(element => {
            element.addEventListener('change', function() {
                document.querySelector('form').submit();
            });
        });

        // Process payment function
        function processPayment(transactionId) {
            if (confirm('Process payment for transaction #' + transactionId + '?')) {
                alert('Payment processed successfully!');
                location.reload();
            }
        }

        // View receipt function
        function viewReceipt(transactionId) {
            alert('Receipt for transaction #' + transactionId + ' would be displayed here.');
        }

        // Download invoice function
        function downloadInvoice(transactionId) {
            alert('Invoice for transaction #' + transactionId + ' would be downloaded.');
        }

        // Add hover effects
        document.querySelectorAll('.payment-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });

        // Add click effects to summary cards
        document.querySelectorAll('.summary-card').forEach(card => {
            card.addEventListener('click', function() {
                this.style.transform = 'scale(1.05)';
                setTimeout(() => {
                    this.style.transform = 'translateY(-5px)';
                }, 200);
            });
        });
    </script>
</body>
</html> 
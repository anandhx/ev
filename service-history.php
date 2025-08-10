<?php
session_start();
require_once 'includes/functions.php';

// Simple session check
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header('Location: login.php');
    exit;
}

// Dummy service history data
$service_history = [
    [
        'id' => 1,
        'type' => 'charging',
        'status' => 'completed',
        'date' => '2024-01-15',
        'time' => '10:30 AM',
        'location' => 'Highway 101, Mile 45',
        'cost' => 75.00,
        'technician' => 'John Smith',
        'vehicle' => 'EV-CHG-001',
        'description' => 'Battery completely dead, need emergency charging',
        'payment_status' => 'paid',
        'rating' => 5
    ],
    [
        'id' => 2,
        'type' => 'mechanical',
        'status' => 'in_progress',
        'date' => '2024-01-20',
        'time' => '2:00 PM',
        'location' => 'Downtown Parking Lot',
        'cost' => 120.00,
        'technician' => 'Mike Johnson',
        'vehicle' => 'EV-MECH-001',
        'description' => 'Flat tire, need immediate replacement',
        'payment_status' => 'pending',
        'rating' => null
    ],
    [
        'id' => 3,
        'type' => 'both',
        'status' => 'assigned',
        'date' => '2024-01-18',
        'time' => '9:00 AM',
        'location' => 'Central Park Area',
        'cost' => 95.00,
        'technician' => 'Sarah Wilson',
        'vehicle' => 'EV-HYB-001',
        'description' => 'Battery low and minor electrical issue',
        'payment_status' => 'pending',
        'rating' => null
    ],
    [
        'id' => 4,
        'type' => 'charging',
        'status' => 'completed',
        'date' => '2024-01-12',
        'time' => '4:30 PM',
        'location' => 'Brooklyn Bridge Area',
        'cost' => 60.00,
        'technician' => 'David Lee',
        'vehicle' => 'EV-CHG-002',
        'description' => 'Need fast charging, running late for meeting',
        'payment_status' => 'paid',
        'rating' => 4
    ],
    [
        'id' => 5,
        'type' => 'mechanical',
        'status' => 'pending',
        'date' => '2024-01-22',
        'time' => '11:00 AM',
        'location' => 'Queens Expressway',
        'cost' => 80.00,
        'technician' => null,
        'vehicle' => null,
        'description' => 'Dashboard warning lights, need diagnostic',
        'payment_status' => 'pending',
        'rating' => null
    ]
];

// Filter options
$status_filter = $_GET['status'] ?? '';
$type_filter = $_GET['type'] ?? '';
$date_filter = $_GET['date'] ?? '';

// Apply filters
$filtered_history = $service_history;
if ($status_filter) {
    $filtered_history = array_filter($filtered_history, function($item) use ($status_filter) {
        return $item['status'] === $status_filter;
    });
}
if ($type_filter) {
    $filtered_history = array_filter($filtered_history, function($item) use ($type_filter) {
        return $item['type'] === $type_filter;
    });
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service History - EV Mobile Power & Service Station</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            color: #333;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .nav {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 2rem;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-menu a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: background 0.3s;
        }

        .user-menu a:hover {
            background: rgba(255,255,255,0.1);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .page-header h1 {
            color: #333;
            margin-bottom: 0.5rem;
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

        .filter-group select,
        .filter-group input {
            padding: 0.75rem;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
        }

        .filter-group select:focus,
        .filter-group input:focus {
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

        .history-grid {
            display: grid;
            gap: 1.5rem;
        }

        .history-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }

        .history-card:hover {
            transform: translateY(-5px);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f8f9fa;
        }

        .request-info h3 {
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

        .status-in-progress {
            background: #fff3cd;
            color: #856404;
        }

        .status-assigned {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status-pending {
            background: #f8d7da;
            color: #721c24;
        }

        .card-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }

        .service-details {
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

        .service-actions {
            display: flex;
            flex-direction: column;
            gap: 1rem;
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

        .rating-stars {
            display: flex;
            gap: 0.25rem;
            margin-top: 0.5rem;
        }

        .star {
            color: #ffc107;
        }

        .star.empty {
            color: #e9ecef;
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

        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            .filter-row {
                grid-template-columns: 1fr;
            }
            
            .card-content {
                grid-template-columns: 1fr;
            }
            
            .service-details {
                grid-template-columns: 1fr;
            }
            
            .container {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <nav class="nav">
            <div class="logo">
                <i class="fas fa-bolt"></i>
                EV Mobile Station
            </div>
            <div class="user-menu">
                <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="index.php"><i class="fas fa-home"></i> Home</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </nav>
    </header>

    <div class="container">
        <div class="page-header">
            <div>
                <h1><i class="fas fa-history"></i> Service History</h1>
                <p>View all your past and current service requests</p>
            </div>
            <a href="request-service.php" class="action-btn primary">
                <i class="fas fa-plus"></i> New Request
            </a>
        </div>

        <!-- Filters -->
        <div class="filters">
            <form method="GET" class="filter-row">
                <div class="filter-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="">All Status</option>
                        <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="in_progress" <?php echo $status_filter === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="assigned" <?php echo $status_filter === 'assigned' ? 'selected' : ''; ?>>Assigned</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="type">Service Type</label>
                    <select id="type" name="type">
                        <option value="">All Types</option>
                        <option value="charging" <?php echo $type_filter === 'charging' ? 'selected' : ''; ?>>Charging</option>
                        <option value="mechanical" <?php echo $type_filter === 'mechanical' ? 'selected' : ''; ?>>Mechanical</option>
                        <option value="both" <?php echo $type_filter === 'both' ? 'selected' : ''; ?>>Both</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="date">Date</label>
                    <input type="date" id="date" name="date" value="<?php echo $date_filter; ?>">
                </div>
                <div class="filter-group">
                    <button type="submit" class="filter-btn">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Service History -->
        <div class="history-grid">
            <?php if (empty($filtered_history)): ?>
                <div class="no-results">
                    <i class="fas fa-search"></i>
                    <h3>No service requests found</h3>
                    <p>Try adjusting your filters or create a new service request.</p>
                </div>
            <?php else: ?>
                <?php foreach ($filtered_history as $service): ?>
                    <div class="history-card">
                        <div class="card-header">
                            <div class="request-info">
                                <h3>Service Request #<?php echo $service['id']; ?></h3>
                                <p><?php echo ucfirst($service['type']); ?> Service</p>
                            </div>
                            <span class="status-badge status-<?php echo str_replace('_', '-', $service['status']); ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $service['status'])); ?>
                            </span>
                        </div>

                        <div class="card-content">
                            <div class="service-details">
                                <div class="detail-item">
                                    <i class="fas fa-calendar"></i>
                                    <span><?php echo $service['date']; ?> at <?php echo $service['time']; ?></span>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span><?php echo $service['location']; ?></span>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-dollar-sign"></i>
                                    <span>$<?php echo number_format($service['cost'], 2); ?></span>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-credit-card"></i>
                                    <span><?php echo ucfirst($service['payment_status']); ?></span>
                                </div>
                                <?php if ($service['technician']): ?>
                                    <div class="detail-item">
                                        <i class="fas fa-user"></i>
                                        <span><?php echo $service['technician']; ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <i class="fas fa-truck"></i>
                                        <span><?php echo $service['vehicle']; ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="service-actions">
                                <div>
                                    <strong>Description:</strong><br>
                                    <small><?php echo $service['description']; ?></small>
                                </div>
                                
                                <?php if ($service['rating']): ?>
                                    <div>
                                        <strong>Rating:</strong>
                                        <div class="rating-stars">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star <?php echo $i <= $service['rating'] ? 'star' : 'star empty'; ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div style="margin-top: 1rem;">
                                    <?php if ($service['status'] === 'in_progress'): ?>
                                        <a href="track-service.php?id=<?php echo $service['id']; ?>" class="action-btn primary">
                                            <i class="fas fa-map-marker-alt"></i> Track Service
                                        </a>
                                    <?php elseif ($service['status'] === 'completed' && !$service['rating']): ?>
                                        <button class="action-btn primary" onclick="rateService(<?php echo $service['id']; ?>)">
                                            <i class="fas fa-star"></i> Rate Service
                                        </button>
                                    <?php endif; ?>
                                    
                                    <button class="action-btn secondary" onclick="viewDetails(<?php echo $service['id']; ?>)">
                                        <i class="fas fa-eye"></i> View Details
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Filter form submission
        document.querySelectorAll('select, input').forEach(element => {
            element.addEventListener('change', function() {
                document.querySelector('form').submit();
            });
        });

        // Service rating function
        function rateService(serviceId) {
            const rating = prompt('Rate this service (1-5 stars):');
            if (rating && rating >= 1 && rating <= 5) {
                alert('Thank you for your rating!');
                // In real app, this would submit to backend
                location.reload();
            }
        }

        // View service details
        function viewDetails(serviceId) {
            alert('Service details for #' + serviceId + ' would be displayed here.');
            // In real app, this would open a modal or redirect to details page
        }

        // Add hover effects
        document.querySelectorAll('.history-card').forEach(card => {
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
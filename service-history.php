<?php
session_start();
require_once 'includes/functions.php';

// Simple session check
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header('Location: login.php');
    exit;
}

// Load history from DB
$serviceManager = new ServiceRequestManager();
$service_history = $serviceManager->getRequestsByUser($_SESSION['user_id']);
// Map latest payment per request
$paymentsByRequest = [];
if (!empty($service_history)) {
    try {
        $ids = array_map(function($r){ return (int)$r['id']; }, $service_history);
        $ids = array_filter($ids);
        if (!empty($ids)) {
            $db = Database::getInstance();
            $in = implode(',', array_fill(0, count($ids), '?'));
            $sql = "SELECT * FROM payments WHERE service_request_id IN ($in) ORDER BY created_at DESC";
            $stmt = $db->executeQuery($sql, $ids);
            $rows = $stmt->fetchAll();
            foreach ($rows as $row) {
                $rid = (int)$row['service_request_id'];
                if (!isset($paymentsByRequest[$rid])) {
                    $paymentsByRequest[$rid] = $row; // keep latest
                }
            }
        }
    } catch (Exception $e) {
        error_log('History payments load failed: ' . $e->getMessage());
    }
}

// Filter options
$status_filter = $_GET['status'] ?? '';
$type_filter = $_GET['type'] ?? '';
$date_filter = $_GET['date'] ?? '';

// Apply filters
$filtered_history = array_filter($service_history ?: [], function($row) use ($status_filter, $type_filter, $date_filter) {
    $ok = true;
    if ($status_filter && $row['status'] !== $status_filter) $ok = false;
    if ($type_filter && $row['request_type'] !== $type_filter) $ok = false;
    if ($date_filter) {
        $d = substr($row['created_at'] ?? '', 0, 10);
        if ($d !== $date_filter) $ok = false;
    }
    return $ok;
});
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

        .nav { width: 100%; display: flex; justify-content: space-between; align-items: center; padding: 0 1rem; }

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

        .container { width: 100%; max-width: none; margin: 0; padding: 1rem; }

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

        /* Modal */
        .modal-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,0.45); display:none; align-items:center; justify-content:center; z-index: 1000; }
        .modal { background:#fff; width: 95%; max-width: 640px; border-radius: 12px; box-shadow: 0 18px 50px rgba(0,0,0,0.2); overflow:hidden; }
        .modal-header { display:flex; justify-content:space-between; align-items:center; padding: 0.9rem 1rem; border-bottom:1px solid #f0f0f5; }
        .modal-title { font-weight:700; }
        .modal-close { background:none; border:none; font-size:1.2rem; cursor:pointer; }
        .modal-body { padding: 1rem; }
        .details-grid { display:grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }
        .details-item { background:#fafbff; border:1px solid #eef0ff; border-radius:8px; padding:0.6rem 0.75rem; }
        .details-item small { display:block; color:#666; }
        .details-item strong { color:#222; }

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
                <a href="vehicles.php"><i class="fas fa-car"></i> My Vehicles</a>
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
                                <h3>Service Request #<?php echo (int)$service['id']; ?></h3>
                                <p><?php echo ucfirst($service['request_type']); ?> Service</p>
                            </div>
                            <span class="status-badge status-<?php echo str_replace('_', '-', $service['status']); ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $service['status'])); ?>
                            </span>
                        </div>

                        <div class="card-content">
                            <div class="service-details">
                                <div class="detail-item">
                                    <i class="fas fa-calendar"></i>
                                    <span><?php echo UtilityFunctions::formatDateTime($service['created_at']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span><?php echo ($service['vehicle_location_lat'] && $service['vehicle_location_lng']) ? ($service['vehicle_location_lat'] . ', ' . $service['vehicle_location_lng']) : '—'; ?></span>
                                </div>
                             
                                <div class="detail-item">
                                    <i class="fas fa-credit-card"></i>
                                    <span><?php echo ucfirst($service['payment_status']); ?></span>
                                </div>
                                <?php if (!empty($service['technician_name'])): ?>
                                    <div class="detail-item">
                                        <i class="fas fa-user"></i>
                                        <span><?php echo $service['technician_name']; ?></span>
                                    </div>
                                    <?php if (!empty($service['vehicle_number'])): ?>
                                        <div class="detail-item">
                                            <i class="fas fa-truck"></i>
                                            <span><?php echo $service['vehicle_number']; ?></span>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>

                            <div class="service-actions">
                                <div>
                                    <strong>Description:</strong><br>
                                    <small><?php echo $service['description']; ?></small>
                                </div>
                                
                                <?php $ratingValue = (int)($service['rating'] ?? 0); ?>
                                <?php if ($ratingValue > 0): ?>
                                    <div>
                                        <strong>Rating:</strong>
                                        <div class="rating-stars">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star <?php echo $i <= $ratingValue ? 'star' : 'star empty'; ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div style="margin-top: 1rem;">
                                    <?php if ($service['status'] === 'in_progress' || $service['status'] === 'assigned'): ?>
                                        <a href="track-service.php?id=<?php echo (int)$service['id']; ?>" class="action-btn primary">
                                            <i class="fas fa-map-marker-alt"></i> Track Service
                                        </a>
                                    <?php elseif ($service['status'] === 'completed'): ?>
                                        <button class="action-btn primary" onclick="rateService(<?php echo (int)$service['id']; ?>)">
                                            <i class="fas fa-star"></i> Rate Service
                                        </button>
                                    <?php endif; ?>
                                    
                                    <?php $pmRow = $paymentsByRequest[(int)$service['id']] ?? null; ?>
                                    <button class="action-btn secondary view-details"
                                        data-request_id="<?php echo (int)$service['id']; ?>"
                                        data-type="<?php echo htmlspecialchars(ucfirst($service['request_type'])); ?>"
                                        data-status="<?php echo htmlspecialchars(ucfirst(str_replace('_',' ', $service['status']))); ?>"
                                        data-created="<?php echo htmlspecialchars(UtilityFunctions::formatDateTime($service['created_at'])); ?>"
                                        data-technician="<?php echo htmlspecialchars($service['technician_name'] ?? '—'); ?>"
                                        data-vehicle="<?php echo htmlspecialchars($service['vehicle_number'] ?? '—'); ?>"
                                        data-amount="<?php echo $pmRow ? number_format((float)$pmRow['amount'],2) : '0.00'; ?>"
                                        data-pay_status="<?php echo $pmRow ? htmlspecialchars(ucfirst($pmRow['status'])) : htmlspecialchars(ucfirst($service['payment_status'])); ?>"
                                        data-method="<?php echo $pmRow ? htmlspecialchars(ucwords(str_replace('_',' ', $pmRow['payment_method']))) : '—'; ?>"
                                        data-txn="<?php echo $pmRow ? htmlspecialchars($pmRow['transaction_id'] ?: '—') : '—'; ?>"
                                    >
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

    <!-- Details Modal (appended in DOM ready) -->

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

        // Modal for details
        (function(){
            // Build modal once DOM is parsed
            const modal = document.createElement('div');
            modal.className = 'modal-backdrop';
            modal.innerHTML = `
                <div class="modal">
                    <div class="modal-header">
                        <div class="modal-title"><i class="fas fa-file-invoice"></i> Service Details</div>
                        <button class="modal-close" id="mClose">×</button>
                    </div>
                    <div class="modal-body">
                        <div class="details-grid">
                            <div class="details-item"><small>Request ID</small><strong id="dReq">—</strong></div>
                            <div class="details-item"><small>Created</small><strong id="dCreated">—</strong></div>
                            <div class="details-item"><small>Type</small><strong id="dType">—</strong></div>
                            <div class="details-item"><small>Status</small><strong id="dStatus">—</strong></div>
                            <div class="details-item"><small>Technician</small><strong id="dTech">—</strong></div>
                            <div class="details-item"><small>Vehicle</small><strong id="dVeh">—</strong></div>
                            <div class="details-item"><small>Payment Amount</small><strong id="dAmount">—</strong></div>
                            <div class="details-item"><small>Payment Status</small><strong id="dPayStatus">—</strong></div>
                            <div class="details-item"><small>Method</small><strong id="dMethod">—</strong></div>
                            <div class="details-item" style="grid-column:1/-1;"><small>Transaction ID</small><strong id="dTxn">—</strong></div>
                        </div>
                        <div style="margin-top:1rem;text-align:right;">
                            <a id="dTrack" href="#" style="text-decoration:none;background:#667eea;color:#fff;padding:0.5rem 0.8rem;border-radius:8px;">Track Request</a>
                        </div>
                    </div>
                </div>`;
            document.body.appendChild(modal);
            const openModal = ()=>{ modal.style.display='flex'; };
            const closeModal = ()=>{ modal.style.display='none'; };
            modal.addEventListener('click', (e)=>{ if(e.target===modal) closeModal(); });
            modal.querySelector('#mClose').addEventListener('click', closeModal);

            document.querySelectorAll('.view-details').forEach(btn => {
                btn.addEventListener('click', () => {
                    document.getElementById('dReq').textContent = '#' + (btn.dataset.request_id || '—');
                    document.getElementById('dCreated').textContent = btn.dataset.created || '—';
                    document.getElementById('dType').textContent = btn.dataset.type || '—';
                    document.getElementById('dStatus').textContent = btn.dataset.status || '—';
                    document.getElementById('dTech').textContent = btn.dataset.technician || '—';
                    document.getElementById('dVeh').textContent = btn.dataset.vehicle || '—';
                    document.getElementById('dAmount').textContent = '₹' + (btn.dataset.amount || '0.00');
                    document.getElementById('dPayStatus').textContent = btn.dataset.pay_status || '—';
                    document.getElementById('dMethod').textContent = btn.dataset.method || '—';
                    document.getElementById('dTxn').textContent = btn.dataset.txn || '—';
                    const track = document.getElementById('dTrack');
                    track.href = 'track-service.php?id=' + (btn.dataset.request_id || '');
                    openModal();
                });
            });
        })();

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
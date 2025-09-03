<?php
session_start();
require_once 'includes/functions.php';

// Simple session check
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header('Location: login.php');
    exit;
}

$request_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = null;
$serviceRequestManager = new ServiceRequestManager();
$request = $request_id ? $serviceRequestManager->getRequestById($request_id) : null;

if (!$request || (int)$request['user_id'] !== (int)$_SESSION['user_id']) {
    $error = 'Service request not found.';
}

// Try to ensure technician phone/name are present if assigned
if (!$error) {
    try {
        if (!empty($request['assigned_technician_id'])) {
            $needPhone = empty($request['technician_phone']);
            $needName = empty($request['technician_name']);
            if ($needPhone || $needName) {
                $dbx = Database::getInstance();
                $stmt = $dbx->executeQuery(
                    "SELECT full_name, phone FROM technicians WHERE id = ? LIMIT 1",
                    [(int)$request['assigned_technician_id']]
                );
                if ($tech = $stmt->fetch()) {
                    if ($needPhone) { $request['technician_phone'] = $tech['phone'] ?? null; }
                    if ($needName) { $request['technician_name'] = $tech['full_name'] ?? null; }
                }
            }
        }
    } catch (Exception $e) { /* ignore */ }
}

// Prepare view model
if (!$error) {
    $tracking_data = [
        'request_id' => $request['id'],
        'status' => $request['status'],
        'service_type' => $request['request_type'],
        'description' => $request['description'] ?: '—',
        'location' => 'Pinned map location',
        'assigned_vehicle' => $request['vehicle_number'] ?: 'Not assigned',
        'technician' => $request['technician_name'] ?: 'Not assigned',
        'technician_phone' => $request['technician_phone'] ?? '—',
        'estimated_arrival' => $request['estimated_arrival_time'] ?: '—',
        'actual_arrival' => $request['actual_arrival_time'] ?: '—',
        'current_location' => [
            'lat' => (float)($request['vehicle_location_lat'] ?? 9.5916),
            'lng' => (float)($request['vehicle_location_lng'] ?? 76.5222)
        ],
        'destination' => [
            'lat' => (float)$request['vehicle_location_lat'],
            'lng' => (float)$request['vehicle_location_lng']
        ],
        'eta' => $request['estimated_arrival_time'] ? UtilityFunctions::formatDateTime($request['estimated_arrival_time']) : '—',
        'distance' => '—'
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Service - EV Mobile Power & Service Station</title>
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
            text-align: center;
            margin-bottom: 2rem;
        }

        .page-header h1 {
            color: #333;
            margin-bottom: 0.5rem;
        }

        .tracking-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 1rem; }

        .tracking-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .status-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f8f9fa;
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.9rem;
        }

        .status-in-progress { background: #fff3cd; color: #856404; }
        .status-assigned { background: #e1f0ff; color: #0d6efd; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }

        .map-container {
            background: #e9ecef;
            height: 420px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
            position: relative;
            overflow: hidden;
        }

        .map-overlay { position: absolute; top: 1rem; right: 1rem; background: rgba(255,255,255,0.9); padding: 0.5rem; border-radius: 5px; font-size: 0.8rem; }

        .progress-timeline { margin-top: 2rem; }
        .timeline-item { display: flex; align-items: flex-start; margin-bottom: 1.5rem; position: relative; }
        .timeline-item:not(:last-child)::after { content: ''; position: absolute; left: 1.5rem; top: 2.5rem; bottom: -1.5rem; width: 2px; background: #e1e5e9; }
        .timeline-icon { width: 3rem; height: 3rem; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 1rem; font-size: 1.2rem; flex-shrink: 0; }
        .timeline-icon.completed { background: #28a745; color: white; }
        .timeline-icon.current { background: #007bff; color: white; }
        .timeline-icon.pending { background: #e9ecef; color: #6c757d; }
        .timeline-content { flex: 1; }
        .timeline-time { font-weight: bold; color: #333; margin-bottom: 0.25rem; }
        .timeline-status { font-weight: bold; margin-bottom: 0.25rem; }
        .timeline-description { color: #666; font-size: 0.9rem; }

        .contact-info { background: #f8f9fa; padding: 1.5rem; border-radius: 10px; margin-bottom: 1.5rem; }
        .contact-item { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem; }
        .action-buttons { display: flex; gap: 1rem; margin-top: 1.5rem; }
        .btn { padding: 0.75rem 1.5rem; border: none; border-radius: 8px; text-decoration: none; font-weight: bold; cursor: pointer; transition: transform 0.3s; display: inline-flex; align-items: center; gap: 0.5rem; }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn:hover { transform: translateY(-2px); }
        .eta-display { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1.5rem; border-radius: 10px; text-align: center; margin-bottom: 1.5rem; }
        .eta-time { font-size: 2rem; font-weight: bold; margin-bottom: 0.5rem; }

        @media (max-width: 768px) {
            .tracking-grid { grid-template-columns: 1fr; }
            .container { padding: 1rem; }
            .tracking-card { padding: 1.5rem; }
            .action-buttons { flex-direction: column; }
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
            <h1><i class="fas fa-map-marker-alt"></i> Track Service</h1>
            <p><?php echo isset($error) ? '' : 'Real-time tracking for your service request #'. (int)$tracking_data['request_id']; ?></p>
        </div>

        <?php if (isset($error)): ?>
            <div class="tracking-card" style="background:#fde8ea;border:1px solid #f8c7cd;">
                <h3 style="color:#8a2530;"><i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?></h3>
                <p>Please select a service request to track.</p>
            </div>

            <?php
            // Load user's requests for selection with simple filters
            $statusFilter = $_GET['status'] ?? '';
            $typeFilter = $_GET['type'] ?? '';
            $allRequests = $serviceRequestManager->getRequestsByUser($_SESSION['user_id']);
            $filtered = array_filter($allRequests, function($r) use ($statusFilter, $typeFilter) {
                $ok = true;
                if ($statusFilter !== '' && $r['status'] !== $statusFilter) $ok = false;
                if ($typeFilter !== '' && $r['request_type'] !== $typeFilter) $ok = false;
                return $ok;
            });
            ?>

            <div class="tracking-card">
                <h3 style="margin-bottom:1rem;"><i class="fas fa-filter"></i> Filter Requests</h3>
                <form method="GET" style="display:grid;grid-template-columns:1fr 1fr auto;gap:0.5rem;margin-bottom:1rem;">
                    <input type="hidden" name="id" value="">
                    <select name="status" style="padding:0.6rem;border:1px solid #e1e5e9;border-radius:8px;">
                        <option value="">All Statuses</option>
                        <?php foreach (['pending','assigned','in_progress','completed','cancelled'] as $st): ?>
                            <option value="<?php echo $st; ?>" <?php echo $statusFilter===$st?'selected':''; ?>><?php echo ucfirst(str_replace('_',' ',$st)); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="type" style="padding:0.6rem;border:1px solid #e1e5e9;border-radius:8px;">
                        <option value="">All Types</option>
                        <?php foreach (['charging','mechanical','both'] as $tp): ?>
                            <option value="<?php echo $tp; ?>" <?php echo $typeFilter===$tp?'selected':''; ?>><?php echo ucfirst($tp); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" style="background:#667eea;color:#fff;border:none;border-radius:8px;padding:0.6rem 1rem;cursor:pointer;">Apply</button>
                </form>

                <?php if (empty($filtered)): ?>
                    <div style="background:#fafbff;border:1px dashed #e1e5e9;border-radius:8px;padding:1rem;">No matching requests found.</div>
                <?php else: ?>
                    <div style="overflow:auto;">
                        <table style="width:100%;border-collapse:collapse;">
                            <thead>
                                <tr style="background:#fafbff;">
                                    <th style="text-align:left;padding:0.6rem;border-bottom:1px solid #f0f0f5;">ID</th>
                                    <th style="text-align:left;padding:0.6rem;border-bottom:1px solid #f0f0f5;">Type</th>
                                    <th style="text-align:left;padding:0.6rem;border-bottom:1px solid #f0f0f5;">Status</th>
                                    <th style="text-align:left;padding:0.6rem;border-bottom:1px solid #f0f0f5;">Created</th>
                                    <th style="text-align:left;padding:0.6rem;border-bottom:1px solid #f0f0f5;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($filtered as $r): ?>
                                <tr>
                                    <td style="padding:0.6rem;border-bottom:1px solid #f0f0f5;">#<?php echo (int)$r['id']; ?></td>
                                    <td style="padding:0.6rem;border-bottom:1px solid #f0f0f5;">&<?php echo ucfirst($r['request_type']); ?></td>
                                    <td style="padding:0.6rem;border-bottom:1px solid #f0f0f5;">&<?php echo ucfirst(str_replace('_',' ', $r['status'])); ?></td>
                                    <td style="padding:0.6rem;border-bottom:1px solid #f0f0f5;">&<?php echo UtilityFunctions::formatDateTime($r['created_at']); ?></td>
                                    <td style="padding:0.6rem;border-bottom:1px solid #f0f0f5;"><a href="track-service.php?id=<?php echo (int)$r['id']; ?>" style="text-decoration:none;background:#667eea;color:#fff;padding:0.4rem 0.8rem;border-radius:6px;">Track</a></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
        <div class="tracking-grid">
            <!-- Main Tracking Section -->
            <div class="main-tracking">
                <div class="tracking-card">
                    <div class="status-header">
                        <div>
                            <h2>Service Request #<?php echo $tracking_data['request_id']; ?></h2>
                            <p><?php echo ucfirst($tracking_data['service_type']); ?> Service</p>
                        </div>
                        <span class="status-badge status-<?php echo str_replace('_', '-', $tracking_data['status']); ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $tracking_data['status'])); ?>
                        </span>
                    </div>

                    <div class="map-container">
                        <div class="map-overlay">
                            <div><strong>ETA:</strong> <?php echo $tracking_data['eta']; ?></div>
                            <div><strong>Vehicle:</strong> <?php echo htmlspecialchars($tracking_data['assigned_vehicle']); ?></div>
                        </div>
                        <div id="liveMap" style="position:absolute;inset:0;"></div>
                    </div>

                    <div class="progress-timeline">
                        <h3><i class="fas fa-clock"></i> Service Progress</h3>
                        <!-- Created -->
                        <div class="timeline-item">
                            <div class="timeline-icon completed"><i class="fas fa-check"></i></div>
                            <div class="timeline-content">
                                <div class="timeline-status">Request created</div>
                                <div class="timeline-description">Awaiting assignment and dispatch</div>
                            </div>
                        </div>
                        <!-- Assigned -->
                        <?php if (!empty($request['assigned_technician_id'])): ?>
                        <div class="timeline-item">
                            <div class="timeline-icon current"><i class="fas fa-user-check"></i></div>
                            <div class="timeline-content">
                                <div class="timeline-status">Technician assigned</div>
                                <div class="timeline-description">Assigned to <?php echo htmlspecialchars($tracking_data['technician']); ?><?php if ($tracking_data['estimated_arrival'] !== '—'): ?>, ETA <?php echo UtilityFunctions::formatDateTime($request['estimated_arrival_time']); ?><?php endif; ?></div>
                            </div>
                        </div>
                        <?php endif; ?>
                        <!-- In Progress -->
                        <?php if ($tracking_data['status'] === 'in_progress'): ?>
                        <div class="timeline-item">
                            <div class="timeline-icon current"><i class="fas fa-tools"></i></div>
                            <div class="timeline-content">
                                <div class="timeline-status">Work started</div>
                                <div class="timeline-description">Technician has started servicing your vehicle</div>
                            </div>
                        </div>
                        <?php endif; ?>
                        <!-- Completed -->
                        <?php if ($tracking_data['status'] === 'completed'): ?>
                        <div class="timeline-item">
                            <div class="timeline-icon completed"><i class="fas fa-flag-checkered"></i></div>
                            <div class="timeline-content">
                                <div class="timeline-status">Completed</div>
                                <div class="timeline-description">Service completed<?php if (!empty($request['completion_time'])): ?> at <?php echo UtilityFunctions::formatDateTime($request['completion_time']); ?><?php endif; ?></div>
                            </div>
                        </div>
                        <?php endif; ?>
                        <!-- Cancelled -->
                        <?php if ($tracking_data['status'] === 'cancelled'): ?>
                        <div class="timeline-item">
                            <div class="timeline-icon pending"><i class="fas fa-ban"></i></div>
                            <div class="timeline-content">
                                <div class="timeline-status">Cancelled</div>
                                <div class="timeline-description">This request has been cancelled.</div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="sidebar">
                <!-- ETA Display -->
                <div class="eta-display">
                    <div class="eta-time"><?php echo $tracking_data['eta']; ?></div>
                    <div>Estimated Time of Arrival</div>
                </div>

                <!-- Service Details -->
                <div class="tracking-card">
                    <h3><i class="fas fa-info-circle"></i> Service Details</h3>
                    <div style="margin-bottom: 1rem;">
                        <strong>Service Type:</strong> <?php echo ucfirst($tracking_data['service_type']); ?><br>
                        <strong>Description:</strong> <?php echo htmlspecialchars($tracking_data['description']); ?><br>
                        <strong>Location:</strong> Map pin
                    </div>

                    <div class="contact-info">
                        <h4><i class="fas fa-user"></i> Assigned Technician</h4>
                        <div class="contact-item">
                            <i class="fas fa-user"></i>
                            <span><?php echo htmlspecialchars($tracking_data['technician']); ?></span>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-phone"></i>
                            <span><?php echo htmlspecialchars($tracking_data['technician_phone']); ?></span>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-truck"></i>
                            <span><?php echo htmlspecialchars($tracking_data['assigned_vehicle']); ?></span>
                        </div>
                    </div>

                    <div class="action-buttons">
                        <?php $canCall = ($tracking_data['technician'] !== 'Not assigned' && $tracking_data['technician_phone'] !== '—' && trim($tracking_data['technician_phone']) !== ''); ?>
                        <?php if ($canCall): ?>
                            <a href="tel:<?php echo htmlspecialchars($tracking_data['technician_phone']); ?>" class="btn btn-primary">
                                <i class="fas fa-phone"></i> Call Technician
                            </a>
                        <?php else: ?>
                            <button class="btn btn-primary" style="opacity:0.6;cursor:not-allowed;pointer-events:none;" disabled title="Technician not assigned yet">
                                <i class="fas fa-phone"></i> Call Technician
                            </button>
                        <?php endif; ?>
                        <a href="dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-tachometer-alt"></i> Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
        // Init simple map with Google Maps if available
        function initMap() {
            const container = document.getElementById('liveMap');
            if (!container) return;
            const center = { lat: <?php echo isset($tracking_data) ? (float)$tracking_data['current_location']['lat'] : 9.5916; ?>, lng: <?php echo isset($tracking_data) ? (float)$tracking_data['current_location']['lng'] : 76.5222; ?> };
            const map = new google.maps.Map(container, { center, zoom: 12, mapTypeControl:false, streetViewControl:false, fullscreenControl:false });
            new google.maps.Marker({ position: center, map });
        }
        window.initMap = initMap;

        // Add some interactive effects
        document.querySelectorAll('.timeline-item').forEach((item, index) => {
            item.addEventListener('click', function() {
                if (index < 5) {
                    this.style.transform = 'scale(1.02)';
                    setTimeout(() => { this.style.transform = 'scale(1)'; }, 200);
                }
            });
        });

        // Optional: Auto-refresh every 2 min
        // setTimeout(() => location.reload(), 120000);
    </script>
    <script async defer src="https://maps.googleapis.com/maps/api/js?key=AIzaSyD3MPnSnyWwNmpnVEFkaddVvy_GWtxSejs&callback=initMap"></script>
</body>
</html> 
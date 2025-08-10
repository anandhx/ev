<?php
session_start();
require_once 'includes/functions.php';

// Simple session check
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header('Location: login.php');
    exit;
}

// Dummy tracking data
$request_id = $_GET['id'] ?? 2;
$tracking_data = [
    'request_id' => $request_id,
    'status' => 'in_progress',
    'service_type' => 'mechanical',
    'description' => 'Flat tire, need immediate replacement',
    'location' => 'Downtown Parking Lot',
    'assigned_vehicle' => 'EV-MECH-001',
    'technician' => 'Mike Johnson',
    'technician_phone' => '+1-555-0102',
    'estimated_arrival' => '2024-01-20 15:30:00',
    'actual_arrival' => '2024-01-20 14:45:00',
    'current_location' => [
        'lat' => 40.7589,
        'lng' => -73.9851,
        'address' => '5th Avenue & 42nd Street'
    ],
    'destination' => [
        'lat' => 40.7589,
        'lng' => -73.9851,
        'address' => 'Downtown Parking Lot'
    ],
    'progress' => [
        ['time' => '14:00', 'status' => 'Request received', 'description' => 'Service request submitted'],
        ['time' => '14:05', 'status' => 'Vehicle assigned', 'description' => 'EV-MECH-001 dispatched'],
        ['time' => '14:15', 'status' => 'Technician assigned', 'description' => 'Mike Johnson assigned'],
        ['time' => '14:30', 'status' => 'En route', 'description' => 'Vehicle is on the way'],
        ['time' => '14:45', 'status' => 'Arrived', 'description' => 'Technician arrived at location'],
        ['time' => '15:00', 'status' => 'Service started', 'description' => 'Tire replacement in progress']
    ],
    'eta' => '15 minutes',
    'distance' => '2.3 miles'
];
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
            max-width: 1000px;
            margin: 0 auto;
            padding: 2rem;
        }

        .page-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .page-header h1 {
            color: #333;
            margin-bottom: 0.5rem;
        }

        .tracking-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }

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

        .status-in-progress {
            background: #fff3cd;
            color: #856404;
        }

        .map-container {
            background: #e9ecef;
            height: 300px;
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

        .map-overlay {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: rgba(255,255,255,0.9);
            padding: 0.5rem;
            border-radius: 5px;
            font-size: 0.8rem;
        }

        .progress-timeline {
            margin-top: 2rem;
        }

        .timeline-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 1.5rem;
            position: relative;
        }

        .timeline-item:not(:last-child)::after {
            content: '';
            position: absolute;
            left: 1.5rem;
            top: 2.5rem;
            bottom: -1.5rem;
            width: 2px;
            background: #e1e5e9;
        }

        .timeline-icon {
            width: 3rem;
            height: 3rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .timeline-icon.completed {
            background: #28a745;
            color: white;
        }

        .timeline-icon.current {
            background: #007bff;
            color: white;
        }

        .timeline-icon.pending {
            background: #e9ecef;
            color: #6c757d;
        }

        .timeline-content {
            flex: 1;
        }

        .timeline-time {
            font-weight: bold;
            color: #333;
            margin-bottom: 0.25rem;
        }

        .timeline-status {
            font-weight: bold;
            margin-bottom: 0.25rem;
        }

        .timeline-description {
            color: #666;
            font-size: 0.9rem;
        }

        .contact-info {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .eta-display {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .eta-time {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        @media (max-width: 768px) {
            .tracking-grid {
                grid-template-columns: 1fr;
            }
            
            .container {
                padding: 1rem;
            }
            
            .tracking-card {
                padding: 1.5rem;
            }
            
            .action-buttons {
                flex-direction: column;
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
            <h1><i class="fas fa-map-marker-alt"></i> Track Service</h1>
            <p>Real-time tracking for your service request #<?php echo $tracking_data['request_id']; ?></p>
        </div>

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
                            <div><strong>Distance:</strong> <?php echo $tracking_data['distance']; ?></div>
                        </div>
                        <i class="fas fa-map" style="font-size: 3rem; margin-right: 1rem;"></i>
                        Live Map View
                    </div>

                    <div class="progress-timeline">
                        <h3><i class="fas fa-clock"></i> Service Progress</h3>
                        <?php foreach ($tracking_data['progress'] as $index => $progress): ?>
                            <div class="timeline-item">
                                <div class="timeline-icon <?php echo $index < 5 ? 'completed' : 'pending'; ?>">
                                    <i class="fas fa-<?php echo $index < 5 ? 'check' : 'circle'; ?>"></i>
                                </div>
                                <div class="timeline-content">
                                    <div class="timeline-time"><?php echo $progress['time']; ?></div>
                                    <div class="timeline-status"><?php echo $progress['status']; ?></div>
                                    <div class="timeline-description"><?php echo $progress['description']; ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
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
                        <strong>Description:</strong> <?php echo $tracking_data['description']; ?><br>
                        <strong>Location:</strong> <?php echo $tracking_data['location']; ?>
                    </div>

                    <div class="contact-info">
                        <h4><i class="fas fa-user"></i> Assigned Technician</h4>
                        <div class="contact-item">
                            <i class="fas fa-user"></i>
                            <span><?php echo $tracking_data['technician']; ?></span>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-phone"></i>
                            <span><?php echo $tracking_data['technician_phone']; ?></span>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-truck"></i>
                            <span><?php echo $tracking_data['assigned_vehicle']; ?></span>
                        </div>
                    </div>

                    <div class="action-buttons">
                        <a href="tel:<?php echo $tracking_data['technician_phone']; ?>" class="btn btn-primary">
                            <i class="fas fa-phone"></i> Call Technician
                        </a>
                        <a href="dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-tachometer-alt"></i> Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Simulate real-time updates
        let currentTime = new Date();
        
        function updateETA() {
            const etaElement = document.querySelector('.eta-time');
            const currentETA = etaElement.textContent;
            const minutes = parseInt(currentETA);
            
            if (minutes > 0) {
                const newMinutes = minutes - 1;
                etaElement.textContent = newMinutes + ' minutes';
                
                if (newMinutes === 0) {
                    etaElement.textContent = 'Arriving now';
                    etaElement.style.color = '#28a745';
                }
            }
        }

        // Update ETA every minute
        setInterval(updateETA, 60000);

        // Simulate location updates
        function updateLocation() {
            const mapContainer = document.querySelector('.map-container');
            const overlay = document.querySelector('.map-overlay');
            
            // Simulate moving vehicle
            const currentLat = 40.7589 + (Math.random() - 0.5) * 0.001;
            const currentLng = -73.9851 + (Math.random() - 0.5) * 0.001;
            
            overlay.innerHTML = `
                <div><strong>Current Location:</strong></div>
                <div>${currentLat.toFixed(6)}, ${currentLng.toFixed(6)}</div>
                <div><strong>ETA:</strong> ${document.querySelector('.eta-time').textContent}</div>
            `;
        }

        // Update location every 30 seconds
        setInterval(updateLocation, 30000);

        // Add some interactive effects
        document.querySelectorAll('.timeline-item').forEach((item, index) => {
            item.addEventListener('click', function() {
                if (index < 5) {
                    this.style.transform = 'scale(1.02)';
                    setTimeout(() => {
                        this.style.transform = 'scale(1)';
                    }, 200);
                }
            });
        });

        // Auto-refresh page every 2 minutes for real-time updates
        setTimeout(() => {
            location.reload();
        }, 120000);
    </script>
</body>
</html> 
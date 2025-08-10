<?php
session_start();
require_once 'includes/functions.php';

// Simple session check
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header('Location: login.php');
    exit;
}

// Dummy data for the form
$service_types = [
    'charging' => 'Mobile EV Charging',
    'mechanical' => 'Mechanical Support',
    'both' => 'Charging + Mechanical'
];

$urgency_levels = [
    'low' => 'Low Priority',
    'medium' => 'Medium Priority', 
    'high' => 'High Priority',
    'emergency' => 'Emergency'
];

$payment_methods = [
    'credit_card' => 'Credit Card',
    'debit_card' => 'Debit Card',
    'digital_wallet' => 'Digital Wallet',
    'cash' => 'Cash'
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_type = sanitize_input($_POST['request_type'] ?? '');
    $urgency_level = sanitize_input($_POST['urgency_level'] ?? '');
    $description = sanitize_input($_POST['description'] ?? '');
    $location_lat = sanitize_input($_POST['location_lat'] ?? '');
    $location_lng = sanitize_input($_POST['location_lng'] ?? '');
    $payment_method = sanitize_input($_POST['payment_method'] ?? '');
    
    // Simulate successful request creation
    $success = true;
    $request_id = rand(1000, 9999);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Service - EV Mobile Power & Service Station</title>
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
            max-width: 800px;
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

        .request-form {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .form-section {
            margin-bottom: 2rem;
        }

        .form-section h3 {
            color: #333;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .submit-btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.3s;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            border: 1px solid #c3e6cb;
        }

        .location-section {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
        }

        .map-placeholder {
            background: #e9ecef;
            height: 200px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            font-size: 1.1rem;
            margin-bottom: 1rem;
        }

        .estimated-cost {
            background: #fff3cd;
            color: #856404;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            border: 1px solid #ffeaa7;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .container {
                padding: 1rem;
            }
            
            .request-form {
                padding: 1.5rem;
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
            <h1><i class="fas fa-plus-circle"></i> Request Service</h1>
            <p>Tell us about your emergency and we'll dispatch help immediately</p>
        </div>

        <?php if (isset($success) && $success): ?>
            <div class="success-message">
                <h3><i class="fas fa-check-circle"></i> Service Request Created Successfully!</h3>
                <p><strong>Request ID:</strong> #<?php echo $request_id; ?></p>
                <p>We've received your request and are dispatching the nearest available service vehicle. You'll receive updates on your dashboard.</p>
                <a href="dashboard.php" class="submit-btn" style="margin-top: 1rem; text-decoration: none; display: inline-block;">
                    <i class="fas fa-tachometer-alt"></i> Go to Dashboard
                </a>
            </div>
        <?php else: ?>
            <form method="POST" class="request-form">
                <!-- Service Type Section -->
                <div class="form-section">
                    <h3><i class="fas fa-tools"></i> Service Type</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="request_type">Service Required *</label>
                            <select id="request_type" name="request_type" required>
                                <option value="">Select service type</option>
                                <?php foreach ($service_types as $key => $value): ?>
                                    <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="urgency_level">Urgency Level *</label>
                            <select id="urgency_level" name="urgency_level" required>
                                <option value="">Select urgency</option>
                                <?php foreach ($urgency_levels as $key => $value): ?>
                                    <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Location Section -->
                <div class="form-section">
                    <h3><i class="fas fa-map-marker-alt"></i> Location</h3>
                    <div class="location-section">
                        <div class="map-placeholder">
                            <i class="fas fa-map" style="font-size: 2rem; margin-right: 1rem;"></i>
                            Click to set your location on map
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="location_lat">Latitude</label>
                                <input type="number" id="location_lat" name="location_lat" step="any" 
                                       value="40.7128" placeholder="Enter latitude">
                            </div>
                            <div class="form-group">
                                <label for="location_lng">Longitude</label>
                                <input type="number" id="location_lng" name="location_lng" step="any" 
                                       value="-74.0060" placeholder="Enter longitude">
                            </div>
                        </div>
                        <button type="button" style="background: #667eea; color: white; border: none; padding: 0.5rem 1rem; border-radius: 5px; cursor: pointer;">
                            <i class="fas fa-crosshairs"></i> Use Current Location
                        </button>
                    </div>
                </div>

                <!-- Description Section -->
                <div class="form-section">
                    <h3><i class="fas fa-comment"></i> Description</h3>
                    <div class="form-group">
                        <label for="description">Describe your issue *</label>
                        <textarea id="description" name="description" required 
                                  placeholder="Please describe your vehicle issue in detail..."></textarea>
                    </div>
                </div>

                <!-- Payment Section -->
                <div class="form-section">
                    <h3><i class="fas fa-credit-card"></i> Payment Method</h3>
                    <div class="form-group">
                        <label for="payment_method">Preferred Payment Method *</label>
                        <select id="payment_method" name="payment_method" required>
                            <option value="">Select payment method</option>
                            <?php foreach ($payment_methods as $key => $value): ?>
                                <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="estimated-cost">
                        <h4><i class="fas fa-info-circle"></i> Estimated Cost</h4>
                        <p><strong>Service Fee:</strong> $50 - $150 (depending on service type and urgency)</p>
                        <p><strong>Distance Fee:</strong> $0.50 per mile</p>
                        <p><strong>Total Estimate:</strong> $75 - $200</p>
                        <small>* Final cost will be calculated based on actual service provided</small>
                    </div>
                </div>

                <button type="submit" class="submit-btn">
                    <i class="fas fa-paper-plane"></i> Submit Service Request
                </button>
            </form>
        <?php endif; ?>
    </div>

    <script>
        // Simulate location detection
        document.querySelector('button[type="button"]').addEventListener('click', function() {
            // Simulate getting current location
            const lat = (40.7128 + (Math.random() - 0.5) * 0.1).toFixed(6);
            const lng = (-74.0060 + (Math.random() - 0.5) * 0.1).toFixed(6);
            
            document.getElementById('location_lat').value = lat;
            document.getElementById('location_lng').value = lng;
            
            alert('Location detected: ' + lat + ', ' + lng);
        });

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const requiredFields = this.querySelectorAll('[required]');
            let isValid = true;

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.style.borderColor = '#dc3545';
                } else {
                    field.style.borderColor = '#e1e5e9';
                }
            });

            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields');
                return false;
            }
        });

        // Dynamic cost estimation
        document.getElementById('request_type').addEventListener('change', function() {
            const urgency = document.getElementById('urgency_level').value;
            const serviceType = this.value;
            
            let baseCost = 50;
            let urgencyMultiplier = 1;
            
            if (serviceType === 'mechanical') baseCost = 80;
            if (serviceType === 'both') baseCost = 120;
            
            if (urgency === 'high') urgencyMultiplier = 1.3;
            if (urgency === 'emergency') urgencyMultiplier = 1.5;
            
            const estimatedCost = Math.round(baseCost * urgencyMultiplier);
            
            const costElement = document.querySelector('.estimated-cost p:last-child');
            costElement.innerHTML = `<strong>Total Estimate:</strong> $${estimatedCost} - $${estimatedCost + 50}`;
        });
    </script>
</body>
</html> 
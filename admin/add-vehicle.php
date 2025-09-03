<?php
session_start();
require_once '../includes/functions.php';

// Simple admin session check
if (!isset($_SESSION['admin_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $vehicle_number = sanitize_input($_POST['vehicle_number']);
    $vehicle_type = sanitize_input($_POST['vehicle_type']);
    $capacity = sanitize_input($_POST['capacity']);
    $location_lat = sanitize_input($_POST['location_lat']);
    $location_lng = sanitize_input($_POST['location_lng']);
    
    $message = 'Vehicle added successfully!';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Vehicle - Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            color: #333;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .nav-links {
            display: flex;
            gap: 1rem;
        }

        .nav-links a {
            padding: 0.75rem 1.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            transition: transform 0.3s;
        }

        .nav-links a:hover {
            transform: translateY(-2px);
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            font-weight: bold;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .form-container {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .form-header {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f8f9fa;
        }

        .form-header h2 {
            color: #333;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
            color: #333;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 2px solid #f8f9fa;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            transition: transform 0.3s;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-plus"></i> Add New Vehicle</h1>
            <div class="nav-links">
                <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="vehicles.php"><i class="fas fa-truck"></i> Vehicles</a>
                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="form-container">
            <div class="form-header">
                <h2><i class="fas fa-truck"></i> Vehicle Information</h2>
                <p>Add a new service vehicle to the fleet</p>
            </div>

            <form method="POST">
                <div class="form-group">
                    <label for="vehicle_number">Vehicle Number *</label>
                    <input type="text" name="vehicle_number" id="vehicle_number" required placeholder="e.g., EV-CHG-003">
                </div>

                <div class="form-group">
                    <label for="vehicle_type">Vehicle Type *</label>
                    <select name="vehicle_type" id="vehicle_type" required>
                        <option value="">Select Vehicle Type</option>
                        <option value="charging">Charging Vehicle</option>
                        <option value="mechanical">Mechanical Vehicle</option>
                        <option value="hybrid">Hybrid Vehicle</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="capacity">Capacity/Equipment *</label>
                    <textarea name="capacity" id="capacity" rows="3" required placeholder="Describe the vehicle's capacity and equipment..."></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="location_lat">Current Latitude</label>
                        <input type="number" step="any" name="location_lat" id="location_lat" placeholder="40.7128">
                    </div>
                    <div class="form-group">
                        <label for="location_lng">Current Longitude</label>
                        <input type="number" step="any" name="location_lng" id="location_lng" placeholder="-74.0060">
                    </div>
                </div>

                <div class="form-actions">
                    <a href="vehicles.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Add Vehicle</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Auto-hide alerts
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.display = 'none';
            });
        }, 5000);
    </script>
</body>
</html> 
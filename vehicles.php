<?php
session_start();
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header('Location: login.php');
    exit;
}

$manager = new UserVehicleManager();
$userId = (int)$_SESSION['user_id'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $data = [
            'make' => UtilityFunctions::sanitizeInput($_POST['make'] ?? ''),
            'model' => UtilityFunctions::sanitizeInput($_POST['model'] ?? ''),
            'plate' => UtilityFunctions::sanitizeInput($_POST['plate'] ?? ''),
            'vin' => UtilityFunctions::sanitizeInput($_POST['vin'] ?? ''),
            'color' => UtilityFunctions::sanitizeInput($_POST['color'] ?? ''),
            'is_primary' => isset($_POST['is_primary']) ? 1 : 0,
        ];
        $id = $manager->createVehicle($userId, $data);
        $message = $id ? 'Vehicle added.' : 'Failed to add vehicle.';
    } elseif ($action === 'update') {
        $vehId = (int)($_POST['vehicle_id'] ?? 0);
        $data = [
            'make' => UtilityFunctions::sanitizeInput($_POST['make'] ?? ''),
            'model' => UtilityFunctions::sanitizeInput($_POST['model'] ?? ''),
            'plate' => UtilityFunctions::sanitizeInput($_POST['plate'] ?? ''),
            'vin' => UtilityFunctions::sanitizeInput($_POST['vin'] ?? ''),
            'color' => UtilityFunctions::sanitizeInput($_POST['color'] ?? ''),
            'is_primary' => isset($_POST['is_primary']) ? 1 : 0,
        ];
        $ok = $manager->updateVehicle($userId, $vehId, $data);
        $message = $ok ? 'Vehicle updated.' : 'Failed to update vehicle.';
    } elseif ($action === 'delete') {
        $vehId = (int)($_POST['vehicle_id'] ?? 0);
        $ok = $manager->deleteVehicle($userId, $vehId);
        $message = $ok ? 'Vehicle deleted.' : 'Failed to delete vehicle.';
    } elseif ($action === 'primary') {
        $vehId = (int)($_POST['vehicle_id'] ?? 0);
        $ok = $manager->setPrimary($userId, $vehId);
        $message = $ok ? 'Primary vehicle set.' : 'Failed to set primary vehicle.';
    }
}

$vehicles = $manager->getVehiclesByUser($userId);
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Vehicles - EV Mobile Power & Service Station</title>
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

        .container { width: 100%; max-width: none; margin: 0; padding: 1.25rem; }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1rem; align-items: stretch; }
        .card { background:linear-gradient(180deg, #ffffff 0%, #fbfbff 100%); border:1px solid #eef0ff; border-radius:14px; box-shadow:0 8px 22px rgba(0,0,0,0.06); padding:1rem; transition:transform .2s ease, box-shadow .2s ease; display:flex; flex-direction:column; min-height:320px; }
        .card:hover { transform: translateY(-4px); box-shadow:0 14px 32px rgba(0,0,0,0.10); }
        .card-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:0.5rem; }
        .card-title { display:flex; align-items:center; gap:0.5rem; font-weight:700; }
        .card-title i { color:#667eea; }
        .meta { display:flex; flex-wrap:wrap; gap:0.4rem; margin:0.4rem 0 0.2rem 0; }
        .chip { background:#f1f3ff; border:1px solid #e3e7ff; color:#4c5cff; padding:0.25rem 0.5rem; border-radius:999px; font-size:0.78rem; }
        .divider { height:1px; background:linear-gradient(90deg, rgba(230,233,255,0), rgba(230,233,255,1), rgba(230,233,255,0)); margin:0.6rem 0 0.8rem; }
        .actions { display:flex; gap:0.5rem; justify-content:flex-end; flex-wrap:wrap; }
        .card form { margin-top:auto; }
        .badge { background:#e9f0ff; color:#3d5afe; border-radius:999px; padding:0.25rem 0.6rem; font-size:0.75rem; font-weight:700; }
        .muted { color:#6c757d; font-size:0.9rem; margin-top:0.25rem; }
        .btn-danger { background:#dc3545; color:#fff; }
        .btn-danger:hover { filter:brightness(0.95); }
        .success { background:#e9f8ee; border:1px solid #c5efd4; color:#1a6d38; padding:0.6rem 0.8rem; border-radius:8px; margin:0.5rem 0; }
        .empty { text-align:center; padding:1.5rem; color:#666; border:1px dashed #e1e5e9; border-radius:12px; }
        .section-title { display:flex; align-items:center; gap:0.5rem; margin-bottom:0.75rem; }
        .section-title i { color:#667eea; }
        .form-card h3 { margin:0 0 0.75rem 0; }
        .row { display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 0.75rem; }
        .row label { display:block; font-weight:600; margin-bottom:0.25rem; }
        .row input { width:100%; padding:0.7rem; border:2px solid #e1e5e9; border-radius:8px; transition:border-color .2s ease; }
        .row input:focus { outline:none; border-color:#667eea; }

        .page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom: 1rem; }
        .page-header h1 { color:#333; margin:0; font-size:1.4rem; }

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

        .status-in-progress {
            background: #fff3cd;
            color: #856404;
        }

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
                <a href="vehicles.php"><i class="fas fa-car"></i> My Vehicles</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </nav>
    </header>



    
    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-car"></i> My Vehicles</h1>
            <div class="actions">
                <a href="request-service.php" class="btn btn-primary"><i class="fas fa-plus"></i> Request Service</a>
            </div>
        </div>
        <?php if ($message): ?><div class="success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>

        <div class="card form-card" style="margin-bottom:1rem;">
            <div class="section-title"><i class="fas fa-plus-circle"></i><h3>Add Vehicle</h3></div>
            <form method="POST" class="row">
                <input type="hidden" name="action" value="create">
                <div>
                    <label>Make</label>
                    <input name="make" placeholder="e.g., Tesla" required>
                </div>
                <div>
                    <label>Model</label>
                    <input name="model" placeholder="e.g., Model 3" required>
                </div>
                <div>
                    <label>Plate</label>
                    <input name="plate" placeholder="e.g., KL-07-AB-1234">
                </div>
                <div>
                    <label>VIN</label>
                    <input name="vin" placeholder="Vehicle Identification Number">
                </div>
                <div>
                    <label>Color</label>
                    <input name="color" placeholder="e.g., Midnight Silver">
                </div>
                <div>
                    <label>&nbsp;</label>
                    <label style="display:inline-flex;align-items:center;gap:0.4rem;">
                        <input type="checkbox" name="is_primary" value="1"> Primary
                    </label>
                </div>
                <div style="grid-column:1/-1;">
                    <button class="btn btn-primary" type="submit">Add Vehicle</button>
                </div>
            </form>
        </div>

        <div class="grid">
            <?php if (empty($vehicles)): ?>
                <div class="empty">No vehicles added yet. Use the form above to add your first EV.</div>
            <?php else: foreach ($vehicles as $v): ?>
                <div class="card">
                    <div class="card-header">
                        <div class="card-title"><i class="fas fa-car"></i><span><?php echo htmlspecialchars($v['make'] . ' ' . $v['model']); ?></span></div>
                        <?php if ($v['is_primary']): ?><span class="badge">Primary</span><?php endif; ?>
                    </div>
                    <div class="meta">
                        <span class="chip">Plate: <?php echo htmlspecialchars($v['plate'] ?: '—'); ?></span>
                        <span class="chip">VIN: <?php echo htmlspecialchars($v['vin'] ?: '—'); ?></span>
                        <span class="chip">Color: <?php echo htmlspecialchars($v['color'] ?: '—'); ?></span>
                    </div>
                    <div class="divider"></div>
                    <form method="POST" class="row" style="margin-top:0.5rem;">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="vehicle_id" value="<?php echo (int)$v['id']; ?>">
                        <div>
                            <label>Make</label>
                            <input name="make" value="<?php echo htmlspecialchars($v['make']); ?>">
                        </div>
                        <div>
                            <label>Model</label>
                            <input name="model" value="<?php echo htmlspecialchars($v['model']); ?>">
                        </div>
                        <div>
                            <label>Plate</label>
                            <input name="plate" value="<?php echo htmlspecialchars($v['plate']); ?>">
                        </div>
                        <div>
                            <label>VIN</label>
                            <input name="vin" value="<?php echo htmlspecialchars($v['vin']); ?>">
                        </div>
                        <div>
                            <label>Color</label>
                            <input name="color" value="<?php echo htmlspecialchars($v['color']); ?>">
                        </div>
                        <div style="display:flex;align-items:center;gap:0.5rem;">
                            <label style="display:inline-flex;align-items:center;gap:0.4rem;">
                                <input type="checkbox" name="is_primary" value="1" <?php echo $v['is_primary']? 'checked':''; ?>> Primary
                            </label>
                        </div>
                        <div style="grid-column:1/-1;" class="actions">
                            <button class="btn btn-primary" type="submit">Save</button>
                            <button class="btn btn-secondary" type="submit" name="action" value="primary">Set Primary</button>
                            <button class="btn btn-danger" type="submit" name="action" value="delete" onclick="return confirm('Delete this vehicle?')">Delete</button>
                        </div>
                    </form>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</body>
</html>


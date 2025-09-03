<?php
session_start();
require_once '../config/config.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['admin_id']) || ($_SESSION['user_type'] ?? '') !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$serviceManager = new ServiceRequestManager();
$paymentManager = new PaymentManager();
$db = Database::getInstance();

$request = $id > 0 ? $serviceManager->getRequestById($id) : false;
$payment = $id > 0 ? $paymentManager->getPaymentByRequestId($id) : null;

// Load history
$history = [];
if ($id > 0) {
    try {
        $stmt = $db->executeQuery("SELECT * FROM service_history WHERE service_request_id = ? ORDER BY created_at DESC", [$id]);
        $history = $stmt->fetchAll();
    } catch (Exception $e) { $history = []; }
}

$current_page = 'requests';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Request #<?php echo htmlspecialchars($id); ?> - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root { --primary-color:#667eea; --secondary-color:#764ba2; }
        body { background-color:#f5f7fa; font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .sidebar { background:linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); position:fixed; top:0; left:0; height:100vh; width:240px; box-shadow:2px 0 10px rgba(0,0,0,0.1); overflow:hidden; }
        .sidebar:hover { overflow-y:auto; }
        .sidebar-header { padding:2rem 1rem; text-align:center; color:#fff; border-bottom:1px solid rgba(255,255,255,0.1); }
        .sidebar-nav { padding:1rem 0; }
        .nav-link { color:rgba(255,255,255,0.85); padding:0.75rem 1.5rem; border-radius:0; }
        .nav-link:hover, .nav-link.active { color:#fff; background:rgba(255,255,255,0.12); transform:translateX(5px); }
        .nav-link i { width:20px; margin-right:10px; }
        .main-with-fixed-sidebar { margin-left:240px; width:calc(100% - 240px); }
        .card-soft { background:#fff; border-radius:15px; border:1px solid #eef0ff; box-shadow:0 8px 22px rgba(0,0,0,0.06); }
        .badge-soft { padding:0.35rem 0.6rem; border-radius:999px; font-weight:600; font-size:0.8rem; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h4><i class="fas fa-charging-station me-2"></i>EV Station</h4>
            <small>Admin Panel</small>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i>Dashboard</a>
            <a href="requests.php" class="nav-link active"><i class="fas fa-clipboard-list"></i>Service Requests</a>
            <a href="vehicles.php" class="nav-link"><i class="fas fa-truck"></i>Service Vehicles</a>
            <a href="technicians.php" class="nav-link"><i class="fas fa-user-cog"></i>Technicians</a>
            <a href="users.php" class="nav-link"><i class="fas fa-users"></i>Users</a>
            <a href="payments.php" class="nav-link"><i class="fas fa-credit-card"></i>Payments</a>
            <a href="emergency.php" class="nav-link"><i class="fas fa-exclamation-triangle"></i>Emergency</a>
            <a href="../logout.php" class="nav-link text-danger"><i class="fas fa-sign-out-alt"></i>Logout</a>
        </nav>
    </div>

    <div class="main-with-fixed-sidebar">
        <div class="container-fluid p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="mb-0">Request #<?php echo htmlspecialchars($id); ?></h2>
                <div>
                    <a href="requests.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
                    <?php if ($request): ?>
                        <a href="assign-request.php?id=<?php echo (int)$id; ?>" class="btn btn-primary btn-sm"><i class="fas fa-edit"></i> Assign/Update</a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!$request): ?>
                <div class="alert alert-warning">Request not found.</div>
            <?php else: ?>
            <div class="row g-3">
                <div class="col-lg-8">
                    <div class="card-soft p-3 mb-3">
                        <h5 class="mb-3">Overview</h5>
                        <div class="row g-2">
                            <div class="col-md-4">
                                <div class="text-muted">User</div>
                                <div class="fw-semibold"><?php echo htmlspecialchars($request['user_name']); ?></div>
                                <small class="text-muted"><?php echo htmlspecialchars($request['user_phone']); ?></small>
                            </div>
                            <div class="col-md-3">
                                <div class="text-muted">Type</div>
                                <div class="fw-semibold"><?php echo htmlspecialchars(ucfirst($request['request_type'])); ?></div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-muted">Urgency</div>
                                <span class="badge badge-soft bg-<?php echo UtilityFunctions::getUrgencyColor($request['urgency_level']); ?>"><?php echo htmlspecialchars(ucfirst($request['urgency_level'])); ?></span>
                            </div>
                            <div class="col-md-2">
                                <div class="text-muted">Status</div>
                                <span class="badge badge-soft bg-<?php echo UtilityFunctions::getStatusColor($request['status']); ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_',' ',$request['status']))); ?></span>
                            </div>
                        </div>
                        <?php if (!empty($request['description'])): ?>
                        <div class="mt-3">
                            <div class="text-muted">Description</div>
                            <div><?php echo nl2br(htmlspecialchars($request['description'])); ?></div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="card-soft p-3 mb-3">
                        <h5 class="mb-3">Location</h5>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <div class="text-muted">Latitude</div>
                                <div class="fw-semibold"><?php echo htmlspecialchars($request['vehicle_location_lat']); ?></div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted">Longitude</div>
                                <div class="fw-semibold"><?php echo htmlspecialchars($request['vehicle_location_lng']); ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="card-soft p-3 mb-3">
                        <h5 class="mb-3">Timeline</h5>
                        <div class="row g-2">
                            <div class="col-md-4"><div class="text-muted">Created</div><div class="fw-semibold"><?php echo UtilityFunctions::formatDateTime($request['created_at']); ?></div></div>
                            <div class="col-md-4"><div class="text-muted">ETA</div><div class="fw-semibold"><?php echo $request['estimated_arrival_time'] ? UtilityFunctions::formatDateTime($request['estimated_arrival_time']) : '—'; ?></div></div>
                            <div class="col-md-4"><div class="text-muted">Arrived</div><div class="fw-semibold"><?php echo $request['actual_arrival_time'] ? UtilityFunctions::formatDateTime($request['actual_arrival_time']) : '—'; ?></div></div>
                            <div class="col-md-4"><div class="text-muted">Completed</div><div class="fw-semibold"><?php echo $request['completion_time'] ? UtilityFunctions::formatDateTime($request['completion_time']) : '—'; ?></div></div>
                        </div>
                    </div>

                    <div class="card-soft p-3 mb-3">
                        <h5 class="mb-3">History</h5>
                        <?php if (empty($history)): ?>
                            <div class="text-muted">No history yet.</div>
                        <?php else: ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($history as $h): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="fw-semibold text-capitalize"><?php echo htmlspecialchars(str_replace('_',' ', $h['action'])); ?></div>
                                        <div class="text-muted small"><?php echo htmlspecialchars($h['description']); ?></div>
                                    </div>
                                    <div class="text-muted small"><?php echo UtilityFunctions::formatDateTime($h['created_at']); ?></div>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card-soft p-3 mb-3">
                        <h5 class="mb-3">Assignment</h5>
                        <div class="mb-2"><span class="text-muted">Vehicle:</span> <span class="fw-semibold"><?php echo htmlspecialchars($request['vehicle_number'] ?? '—'); ?></span></div>
                        <div class="mb-2"><span class="text-muted">Technician:</span> <span class="fw-semibold"><?php echo htmlspecialchars($request['technician_name'] ?? '—'); ?></span></div>
                        <a class="btn btn-outline-primary btn-sm" href="assign-request.php?id=<?php echo (int)$id; ?>"><i class="fas fa-edit"></i> Assign / Update</a>
                    </div>

                    <div class="card-soft p-3 mb-3">
                        <h5 class="mb-3">Payment</h5>
                        <div class="mb-2"><span class="text-muted">Status:</span> <span class="fw-semibold text-capitalize"><?php echo htmlspecialchars($request['payment_status']); ?></span></div>
                        <div class="mb-2"><span class="text-muted">Amount:</span> <span class="fw-semibold">₹<?php echo number_format((float)($request['total_cost'] ?? $payment['amount'] ?? 0), 2); ?></span></div>
                        <?php if ($payment): ?>
                        <div class="mb-2"><span class="text-muted">Method:</span> <span class="fw-semibold text-capitalize"><?php echo htmlspecialchars(str_replace('_',' ', $payment['payment_method'])); ?></span></div>
                        <div class="mb-2"><span class="text-muted">TXN:</span> <span class="fw-semibold"><?php echo htmlspecialchars($payment['transaction_id'] ?? ''); ?></span></div>
                        <div class="mb-2"><span class="text-muted">Paid At:</span> <span class="fw-semibold"><?php echo isset($payment['created_at']) ? UtilityFunctions::formatDateTime($payment['created_at']) : '—'; ?></span></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>




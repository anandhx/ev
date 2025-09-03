<?php
session_start();
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header('Location: login.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$parts = new SparePartsManager();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'request') {
        $data = [
            'user_id' => $userId,
            'vehicle_make' => UtilityFunctions::sanitizeInput($_POST['vehicle_make'] ?? ''),
            'vehicle_model' => UtilityFunctions::sanitizeInput($_POST['vehicle_model'] ?? ''),
            'part_name' => UtilityFunctions::sanitizeInput($_POST['part_name'] ?? ''),
            'part_description' => UtilityFunctions::sanitizeInput($_POST['part_description'] ?? ''),
            'quantity' => (int)($_POST['quantity'] ?? 1),
        ];
        $rid = $parts->createRequest($data);
        $msg = $rid ? 'Spare part request submitted.' : 'Failed to submit request.';
    } elseif ($action === 'order') {
        $request_id = (int)($_POST['request_id'] ?? 0);
        $total = (float)($_POST['total_amount'] ?? 0);
        $orderData = [
            'request_id' => $request_id,
            'user_id' => $userId,
            'total_amount' => $total,
            'shipping_name' => UtilityFunctions::sanitizeInput($_POST['shipping_name'] ?? ''),
            'shipping_phone' => UtilityFunctions::sanitizeInput($_POST['shipping_phone'] ?? ''),
            'shipping_address' => UtilityFunctions::sanitizeInput($_POST['shipping_address'] ?? ''),
            'shipping_city' => UtilityFunctions::sanitizeInput($_POST['shipping_city'] ?? ''),
            'shipping_state' => UtilityFunctions::sanitizeInput($_POST['shipping_state'] ?? ''),
            'shipping_postal' => UtilityFunctions::sanitizeInput($_POST['shipping_postal'] ?? ''),
        ];
        $oid = $parts->createOrder($orderData);
        $msg = $oid ? 'Order placed. We will process your shipment.' : 'Failed to place order.';
    }
}

$requests = $parts->getRequestsByUser($userId);
$orders = $parts->getOrdersByUser($userId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spare Parts - EV Mobile Power & Service Station</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background:#f8f9fa; color:#333; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color:#fff; padding:1rem 0; box-shadow:0 2px 10px rgba(0,0,0,0.1); }
        .nav { width:100%; display:flex; justify-content:space-between; align-items:center; padding:0 1rem; }
        .logo { font-size:1.5rem; font-weight:bold; display:flex; align-items:center; gap:0.5rem; }
        .user-menu { display:flex; align-items:center; gap:1rem; }
        .user-menu a { color:#fff; text-decoration:none; padding:0.5rem 1rem; border-radius:5px; transition: background 0.3s; }
        .user-menu a:hover { background: rgba(255,255,255,0.1); }
        .container { width:100%; max-width:none; margin:0; padding:1.25rem; }
        .grid { display:grid; grid-template-columns:2fr 1fr; gap:1rem; }
        .card { background:#fff; border:1px solid #eef0ff; border-radius:12px; box-shadow:0 6px 18px rgba(0,0,0,0.06); padding:1rem; }
        .row { display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:0.75rem; }
        label { display:block; font-weight:600; margin-bottom:0.25rem; }
        input, textarea { width:100%; padding:0.7rem; border:2px solid #e1e5e9; border-radius:8px; }
        textarea { min-height:90px; }
        .btn { padding:0.7rem 1.2rem; border:none; border-radius:8px; font-weight:700; cursor:pointer; }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color:#fff; }
        .muted { color:#6c757d; font-size:0.9rem; }
        .success { background:#e9f8ee; border:1px solid #c5efd4; color:#1a6d38; padding:0.6rem 0.8rem; border-radius:8px; margin:0.5rem 0; }
        table { width:100%; border-collapse:collapse; }
        th, td { padding:0.6rem; border-bottom:1px solid #eee; text-align:left; }
        th { background:#f7f8ff; }
        .actions { display:flex; gap:0.5rem; flex-wrap:wrap; }
    </style>
</head>
<body>
    <header class="header">
        <nav class="nav">
            <div class="logo"><i class="fas fa-bolt"></i> EV Mobile Station</div>
            <div class="user-menu">
                <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="vehicles.php"><i class="fas fa-car"></i> My Vehicles</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </nav>
    </header>
    <div class="container">
        <?php if ($msg): ?><div class="success"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
        <div class="grid">
            <div>
                <div class="card" style="margin-bottom:1rem;">
                    <h3 style="margin-bottom:0.5rem;">Request Spare Part</h3>
                    <form method="POST" class="row">
                        <input type="hidden" name="action" value="request">
                        <div>
                            <label>Vehicle Make</label>
                            <input name="vehicle_make" placeholder="e.g., Tata">
                        </div>
                        <div>
                            <label>Vehicle Model</label>
                            <input name="vehicle_model" placeholder="e.g., Nexon EV">
                        </div>
                        <div>
                            <label>Part Name</label>
                            <input name="part_name" placeholder="e.g., Brake Pads" required>
                        </div>
                        <div>
                            <label>Quantity</label>
                            <input name="quantity" type="number" min="1" value="1">
                        </div>
                        <div style="grid-column:1/-1;">
                            <label>Details (optional)</label>
                            <textarea name="part_description" placeholder="Describe the issue or exact part code if known"></textarea>
                        </div>
                        <div style="grid-column:1/-1;">
                            <button class="btn btn-primary" type="submit">Submit Request</button>
                        </div>
                    </form>
                </div>

                <div class="card">
                    <h3 style="margin-bottom:0.5rem;">My Requests</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Part</th>
                                <th>Status</th>
                                <th>Quote</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($requests)): ?>
                            <tr><td colspan="5" class="muted">No requests yet.</td></tr>
                            <?php else: foreach ($requests as $r): ?>
                            <tr>
                                <td><?php echo (int)$r['id']; ?></td>
                                <td><?php echo htmlspecialchars($r['part_name']); ?></td>
                                <td><?php echo htmlspecialchars($r['status']); ?></td>
                                <td>
                                    <?php if ($r['status']==='quoted' && $r['admin_available']): ?>
                                        <div>
                                            <div class="muted">Code: <?php echo htmlspecialchars($r['admin_part_code'] ?: '—'); ?></div>
                                            <div><strong>₹<?php echo number_format((float)$r['admin_price'], 2); ?></strong></div>
                                        </div>
                                    <?php else: ?>
                                        <span class="muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="actions">
                                    <?php if ($r['status']==='quoted' && $r['admin_available'] && $r['admin_price']>0): ?>
                                        <form method="POST" style="display:inline-grid; grid-template-columns:1fr; gap:0.4rem; min-width:260px;">
                                            <input type="hidden" name="action" value="order">
                                            <input type="hidden" name="request_id" value="<?php echo (int)$r['id']; ?>">
                                            <input type="hidden" name="total_amount" value="<?php echo htmlspecialchars($r['admin_price']); ?>">
                                            <input name="shipping_name" placeholder="Full Name" required>
                                            <input name="shipping_phone" placeholder="Phone" required>
                                            <input name="shipping_address" placeholder="Address" required>
                                            <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:0.4rem;">
                                                <input name="shipping_city" placeholder="City" required>
                                                <input name="shipping_state" placeholder="State" required>
                                                <input name="shipping_postal" placeholder="Postal Code" required>
                                            </div>
                                            <button class="btn btn-primary" type="submit">Buy for ₹<?php echo number_format((float)$r['admin_price'], 2); ?></button>
                                        </form>
                                    <?php else: ?>
                                        <span class="muted">Waiting for quote</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <aside>
                <div class="card">
                    <h3 style="margin-bottom:0.5rem;">My Spare Orders</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Part</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)): ?>
                            <tr><td colspan="4" class="muted">No orders yet.</td></tr>
                            <?php else: foreach ($orders as $o): ?>
                            <tr>
                                <td><?php echo (int)$o['id']; ?></td>
                                <td><?php echo htmlspecialchars($o['part_name']); ?></td>
                                <td>₹<?php echo number_format((float)$o['total_amount'], 2); ?></td>
                                <td><?php echo htmlspecialchars($o['status']); ?></td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </aside>
        </div>
    </div>
    </body>
    </html>



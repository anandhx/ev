<?php
session_start();
require_once '../includes/functions.php';

// Admin session guard
if (!isset($_SESSION['admin_id']) || ($_SESSION['user_type'] ?? '') !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$db = Database::getInstance();
$message = '';
$error = '';

// Handle admin actions: update quote/availability or decline
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'quote') {
        $reqId = (int)($_POST['request_id'] ?? 0);
        $available = isset($_POST['admin_available']) ? 1 : 0;
        $code = trim($_POST['admin_part_code'] ?? '');
        $price = isset($_POST['admin_price']) ? (float)$_POST['admin_price'] : 0;
        $note = trim($_POST['admin_note'] ?? '');
        if ($reqId > 0) {
            try {
                $status = $available ? 'quoted' : 'declined';
                $db->executeQuery(
                    "UPDATE spare_part_requests SET admin_available=?, admin_part_code=?, admin_price=?, admin_note=?, status=?, updated_at=CURRENT_TIMESTAMP WHERE id=?",
                    [$available, $code ?: null, $price ?: null, $note ?: null, $status, $reqId]
                );
                $message = 'Request updated successfully.';
            } catch (Exception $e) {
                $error = 'Failed to update request: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'decline') {
        $reqId = (int)($_POST['request_id'] ?? 0);
        if ($reqId > 0) {
            try {
                $db->executeQuery("UPDATE spare_part_requests SET status='declined', updated_at=CURRENT_TIMESTAMP WHERE id=?", [$reqId]);
                $message = 'Request declined.';
            } catch (Exception $e) {
                $error = 'Failed to decline request.';
            }
        }
    } elseif ($action === 'update_order') {
        $orderId = (int)($_POST['order_id'] ?? 0);
        $newStatus = $_POST['status'] ?? '';
        $allowed = ['pending','paid','processing','shipped','delivered','cancelled'];
        if ($orderId > 0 && in_array($newStatus, $allowed, true)) {
            try {
                $db->executeQuery("UPDATE spare_orders SET status=?, updated_at=CURRENT_TIMESTAMP WHERE id=?", [$newStatus, $orderId]);
                $message = 'Order status updated to ' . ucfirst($newStatus) . '.';
            } catch (Exception $e) {
                $error = 'Failed to update order: ' . $e->getMessage();
            }
        }
    }
}

// Load spare part requests with user info
$requests = [];
try {
    $stmt = $db->executeQuery(
        "SELECT r.*, u.full_name AS user_name, u.email AS user_email, u.phone AS user_phone
         FROM spare_part_requests r
         JOIN users u ON u.id = r.user_id
         ORDER BY r.created_at DESC"
    );
    $requests = $stmt->fetchAll();
} catch (Exception $e) { $requests = []; }

// Load orders for quick view
$orders = [];
try {
    $stmt = $db->executeQuery(
        "SELECT o.*, r.part_name, u.full_name AS user_name
         FROM spare_orders o
         JOIN spare_part_requests r ON r.id = o.request_id
         JOIN users u ON u.id = o.user_id
         ORDER BY o.created_at DESC"
    );
    $orders = $stmt->fetchAll();
} catch (Exception $e) { $orders = []; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spare Parts - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background:#f5f7fa; }
        .sidebar { background: linear-gradient(135deg, #667eea, #764ba2); position: fixed; top:0; left:0; height:100vh; width:240px; overflow:hidden; box-shadow:2px 0 10px rgba(0,0,0,0.1); }
        .sidebar-header { padding:2rem 1rem; text-align:center; color:#fff; border-bottom:1px solid rgba(255,255,255,0.15); }
        .sidebar-nav { padding:1rem 0; }
        .nav-link { color: rgba(255,255,255,0.85); padding:0.75rem 1.5rem; display:block; text-decoration:none; }
        .nav-link:hover, .nav-link.active { color:#fff; background:rgba(255,255,255,0.12); transform: translateX(5px); }
        .nav-link i { width:20px; margin-right:10px; }
        .main { margin-left:240px; padding:2rem; }
        .top { background:#fff; border-radius:15px; padding:1.25rem 1.5rem; margin-bottom:1rem; box-shadow:0 5px 15px rgba(0,0,0,0.1); display:flex; justify-content:space-between; align-items:center; }
        .grid { display:grid; grid-template-columns: 2fr 1fr; gap:1rem; }
        .card { background:#fff; border-radius:15px; padding:1rem; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        table { width:100%; border-collapse:collapse; }
        th, td { text-align:left; padding:0.65rem; border-bottom:1px solid #eee; vertical-align: top; }
        th { background:#f9f9ff; }
        .badge { padding:0.25rem 0.6rem; border-radius:12px; font-size:0.8rem; font-weight:700; }
        .b-requested { background:#e9ecef; color:#495057; }
        .b-quoted { background:#d4edda; color:#155724; }
        .b-declined { background:#f8d7da; color:#721c24; }
        .b-cancelled { background:#ffe8cc; color:#9a5b00; }
        .b-ordered { background:#d1ecf1; color:#0c5460; }
        .row { display:grid; grid-template-columns: repeat(auto-fit, minmax(180px,1fr)); gap:0.5rem; }
        input, textarea, select { width:100%; padding:0.6rem; border:1px solid #e1e5e9; border-radius:8px; }
        textarea { min-height:72px; }
        .form-actions { display:flex; gap:0.5rem; justify-content:flex-end; margin-top:0.5rem; }
        .btn { padding:0.55rem 1rem; border:none; border-radius:8px; font-weight:700; cursor:pointer; }
        .btn-primary { background: linear-gradient(135deg, #667eea, #764ba2); color:#fff; }
        .btn-danger { background:#dc3545; color:#fff; }
        .msg { padding:0.8rem 1rem; border-radius:10px; margin-bottom:1rem; }
        .ok { background:#d4edda; color:#155724; border:1px solid #c3e6cb; }
        .err { background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; }
        @media(max-width: 900px){ .grid { grid-template-columns:1fr; } }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h4><i class="fas fa-charging-station" style="margin-right:8px;"></i>EV Station</h4>
            <small>Admin Panel</small>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i>Dashboard</a>
            <a href="requests.php" class="nav-link"><i class="fas fa-clipboard-list"></i>Service Requests</a>
            <a href="vehicles.php" class="nav-link"><i class="fas fa-truck"></i>Service Vehicles</a>
            <a href="technicians.php" class="nav-link"><i class="fas fa-user-cog"></i>Technicians</a>
            <a href="users.php" class="nav-link"><i class="fas fa-users"></i>Users</a>
            <a href="payments.php" class="nav-link"><i class="fas fa-credit-card"></i>Payments</a>
            <a href="emergency.php" class="nav-link"><i class="fas fa-exclamation-triangle"></i>Emergency</a>
            <a href="spares.php" class="nav-link active"><i class="fas fa-cogs"></i>Spares</a>
            <a href="../logout.php" class="nav-link" style="color:#ffd1d1;"><i class="fas fa-sign-out-alt"></i>Logout</a>
        </nav>
    </div>

    <div class="main">
        <div class="top">
            <div>
                <h2 style="margin:0;">Spare Parts Management</h2>
                <small style="color:#666;">Review user requests, quote availability, and view orders</small>
            </div>
        </div>

        <?php if ($message): ?><div class="msg ok"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="msg err"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

        <div class="grid">
            <div class="card">
                <h3 style="margin-bottom:0.75rem;">Spare Part Requests</h3>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>User</th>
                            <th>Vehicle</th>
                            <th>Part</th>
                            <th>Status</th>
                            <th>Quote</th>
                            <th style="width:320px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($requests)): ?>
                        <tr><td colspan="7" style="color:#666; text-align:center; padding:1rem;">No spare part requests.</td></tr>
                        <?php else: foreach ($requests as $r): ?>
                        <tr>
                            <td><?php echo (int)$r['id']; ?></td>
                            <td>
                                <div><strong><?php echo htmlspecialchars($r['user_name']); ?></strong></div>
                                <div style="color:#666; font-size:0.9rem;">
                                    <?php echo htmlspecialchars($r['user_email']); ?> · <?php echo htmlspecialchars($r['user_phone']); ?>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars(trim(($r['vehicle_make'] ?? '') . ' ' . ($r['vehicle_model'] ?? '')) ?: '—'); ?></td>
                            <td>
                                <div><strong><?php echo htmlspecialchars($r['part_name']); ?></strong></div>
                                <?php if (!empty($r['part_description'])): ?>
                                    <div style="color:#666; font-size:0.9rem;">"<?php echo htmlspecialchars($r['part_description']); ?>"</div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php $b = 'b-' . htmlspecialchars($r['status']); ?>
                                <span class="badge <?php echo $b; ?>"><?php echo ucfirst(str_replace('_',' ', $r['status'])); ?></span>
                            </td>
                            <td>
                                <?php if ($r['status'] === 'quoted' && (int)$r['admin_available'] === 1): ?>
                                    <div style="font-size:0.9rem;">
                                        Code: <strong><?php echo htmlspecialchars($r['admin_part_code'] ?: '—'); ?></strong><br>
                                        Price: <strong>₹<?php echo number_format((float)($r['admin_price'] ?? 0), 2); ?></strong>
                                        <?php if (!empty($r['admin_note'])): ?><div style="color:#666;">Note: <?php echo htmlspecialchars($r['admin_note']); ?></div><?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <span style="color:#666;">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="POST" class="row">
                                    <input type="hidden" name="action" value="quote">
                                    <input type="hidden" name="request_id" value="<?php echo (int)$r['id']; ?>">
                                    <div>
                                        <label><input type="checkbox" name="admin_available" <?php echo ((int)$r['admin_available']===1)?'checked':''; ?>> Available</label>
                                    </div>
                                    <div>
                                        <input name="admin_part_code" placeholder="Part code" value="<?php echo htmlspecialchars($r['admin_part_code'] ?? ''); ?>">
                                    </div>
                                    <div>
                                        <input name="admin_price" type="number" step="0.01" min="0" placeholder="Price" value="<?php echo htmlspecialchars($r['admin_price'] ?? ''); ?>">
                                    </div>
                                    <div style="grid-column:1/-1;">
                                        <textarea name="admin_note" placeholder="Admin note (optional)"><?php echo htmlspecialchars($r['admin_note'] ?? ''); ?></textarea>
                                    </div>
                                    <div class="form-actions" style="grid-column:1/-1;">
                                        <button class="btn btn-primary" type="submit">Save Quote</button>
                                        <button class="btn btn-danger" type="submit" name="action" value="decline">Decline</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="card">
                <h3 style="margin-bottom:0.75rem;">Recent Spare Orders</h3>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>User</th>
                            <th>Part</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Update</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                        <tr><td colspan="6" style="color:#666; text-align:center; padding:1rem;">No orders yet.</td></tr>
                        <?php else: foreach ($orders as $o): ?>
                        <tr>
                            <td><?php echo (int)$o['id']; ?></td>
                            <td><?php echo htmlspecialchars($o['user_name']); ?></td>
                            <td><?php echo htmlspecialchars($o['part_name']); ?></td>
                            <td>₹<?php echo number_format((float)$o['total_amount'], 2); ?></td>
                            <td><?php echo ucfirst($o['status']); ?></td>
                            <td>
                                <form method="POST" style="display:flex; gap:0.5rem; align-items:center;">
                                    <input type="hidden" name="action" value="update_order">
                                    <input type="hidden" name="order_id" value="<?php echo (int)$o['id']; ?>">
                                    <select name="status">
                                        <?php foreach (['pending','paid','processing','shipped','delivered','cancelled'] as $st): ?>
                                            <option value="<?php echo $st; ?>" <?php echo $o['status']===$st?'selected':''; ?>><?php echo ucfirst($st); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="btn btn-primary" type="submit">Save</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
                <div style="color:#666; font-size:0.9rem; margin-top:0.5rem;">Note: User checkout for spares is Cash on Delivery. Mark as Delivered when the part is handed over.</div>
            </div>
        </div>
    </div>
</body>
</html>

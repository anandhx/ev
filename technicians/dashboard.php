<?php
session_start();
require_once '../includes/functions.php';

// Technician session check
if (!isset($_SESSION['technician_id']) || ($_SESSION['user_type'] ?? '') !== 'technician') {
    header('Location: ../login.php');
    exit;
}

$technicianId = (int)$_SESSION['technician_id'];
$technicianName = $_SESSION['technician_name'] ?? 'Technician';

$db = Database::getInstance();

// Load counts
$assignedCount = 0;
$inProgressCount = 0;
$completedToday = 0;
$recentRequests = [];

try {
    $stmt = $db->executeQuery("SELECT COUNT(*) AS c FROM service_requests WHERE assigned_technician_id = ? AND status IN ('assigned')", [$technicianId]);
    $assignedCount = (int)($stmt->fetch()['c'] ?? 0);

    $stmt = $db->executeQuery("SELECT COUNT(*) AS c FROM service_requests WHERE assigned_technician_id = ? AND status = 'in_progress'", [$technicianId]);
    $inProgressCount = (int)($stmt->fetch()['c'] ?? 0);

    $stmt = $db->executeQuery("SELECT COUNT(*) AS c FROM service_requests WHERE assigned_technician_id = ? AND status = 'completed' AND DATE(updated_at) = CURDATE()", [$technicianId]);
    $completedToday = (int)($stmt->fetch()['c'] ?? 0);

    $stmt = $db->executeQuery(
        "SELECT sr.id, sr.request_type, sr.urgency_level, sr.status, sr.total_cost, u.full_name AS user_name, sr.created_at
         FROM service_requests sr
         JOIN users u ON u.id = sr.user_id
         WHERE sr.assigned_technician_id = ?
         ORDER BY sr.updated_at DESC, sr.created_at DESC
         LIMIT 8",
        [$technicianId]
    );
    $recentRequests = $stmt->fetchAll();
} catch (Exception $e) {
    // ignore and show zeros/empty
}
$flash = $_SESSION['tech_flash'] ?? '';
unset($_SESSION['tech_flash']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Technician Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background:#f5f7fa; }
        .topbar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color:#fff; padding:1rem 1.5rem; display:flex; align-items:center; justify-content:space-between; }
        .brand { font-weight:bold; }
        .nav a { color:#fff; text-decoration:none; margin-left:1rem; background:rgba(255,255,255,0.15); padding:0.5rem 0.75rem; border-radius:6px; }
        .container { padding:1.5rem; width:100%; margin:0; }
        .grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap:1rem; }
        .card { background:#fff; border-radius:12px; box-shadow:0 5px 15px rgba(0,0,0,0.08); padding:1.25rem; }
        .stat { text-align:center; }
        .stat .num { font-size:2rem; font-weight:bold; }
        .table { width:100%; border-collapse:collapse; margin-top:0.5rem; }
        .table th, .table td { text-align:left; padding:0.75rem; border-bottom:1px solid #eee; }
        .badge { padding:0.25rem 0.5rem; border-radius:12px; font-size:0.8rem; }
        .b-assigned { background:#fff3cd; color:#856404; }
        .b-inprog { background:#d1ecf1; color:#0c5460; }
        .b-completed { background:#d4edda; color:#155724; }
        .actions a { text-decoration:none; color:#fff; background:#667eea; padding:0.35rem 0.6rem; border-radius:6px; margin-right:0.25rem; font-size:0.85rem; }
        .actions a.warn { background:#ffc107; color:#212529; }
        .actions a.success { background:#28a745; }
        .flash { background:#e9f7ef; color:#155724; border:1px solid #c3e6cb; padding:.75rem 1rem; border-radius:8px; margin-bottom:1rem; display:flex; justify-content:space-between; align-items:center; }
        .toggle { background:#fff; border:1px solid #e5e8ee; border-radius:10px; padding:.5rem; display:inline-flex; align-items:center; }
        .toggle a { text-decoration:none; padding:.5rem .75rem; border-radius:8px; color:#333; }
        .toggle a.do { background:linear-gradient(135deg, #667eea 0%, #764ba2 100%); color:#fff; }
    </style>
</head>
<body>
    <div class="topbar">
        <div class="brand"><i class="fas fa-user-cog"></i> Technician Panel</div>
        <div class="nav">
            <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="requests.php"><i class="fas fa-clipboard-list"></i> My Requests</a>
            <a href="update-availability.php?toggle=1&redirect=dashboard.php"><i class="fas fa-toggle-on"></i> Toggle Availability</a>
            <a href="../logout.php" style="background:#ff6b6b;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="container">
        <?php if ($flash): ?>
            <div class="flash" id="flashMsg">
                <span><i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($flash); ?></span>
                <a href="#" onclick="document.getElementById('flashMsg').remove();return false;" style="color:#155724;">&times;</a>
            </div>
        <?php endif; ?>

        <h2 style="margin-bottom:1rem;color:#333;">Welcome, <?php echo htmlspecialchars($technicianName); ?></h2>
        <div class="grid" style="margin-bottom:1rem;">
            <div class="card stat"><div class="num"><?php echo $assignedCount; ?></div><div>Assigned</div></div>
            <div class="card stat"><div class="num"><?php echo $inProgressCount; ?></div><div>In Progress</div></div>
            <div class="card stat"><div class="num"><?php echo $completedToday; ?></div><div>Completed Today</div></div>
        </div>

        <div class="card" style="margin-bottom:1rem; display:flex; align-items:center; justify-content:space-between;">
            <div>
                <strong>Availability</strong>
                <div style="color:#666;font-size:.9rem;">Toggle your availability for new assignments</div>
            </div>
            <div class="toggle">
                <a class="do" href="update-availability.php?toggle=1&redirect=dashboard.php"><i class="fas fa-toggle-on"></i> Toggle</a>
            </div>
        </div>

        <div class="card">
            <h3>Recent Requests</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>User</th>
                        <th>Type</th>
                        <th>Urgency</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentRequests)): ?>
                        <tr><td colspan="6" style="text-align:center;color:#666;">No recent requests</td></tr>
                    <?php else: foreach ($recentRequests as $r): ?>
                        <tr>
                            <td>#<?php echo (int)$r['id']; ?></td>
                            <td><?php echo htmlspecialchars($r['user_name']); ?></td>
                            <td><?php echo ucfirst(htmlspecialchars($r['request_type'])); ?></td>
                            <td><?php echo ucfirst(htmlspecialchars($r['urgency_level'])); ?></td>
                            <td>
                                <?php $s=$r['status']; $cls=$s==='in_progress'?'b-inprog':($s==='completed'?'b-completed':'b-assigned'); ?>
                                <span class="badge <?php echo $cls; ?>"><?php echo ucfirst($s); ?></span>
                            </td>
                            <td class="actions">
                                <?php if ($r['status']==='assigned'): ?>
                                    <a href="update-status.php?id=<?php echo (int)$r['id']; ?>&status=in_progress" class="warn">Start</a>
                                <?php endif; ?>
                                <?php if ($r['status']==='in_progress'): ?>
                                    <a href="update-status.php?id=<?php echo (int)$r['id']; ?>&status=completed" class="success">Complete</a>
                                <?php endif; ?>
                                <a href="requests.php#req-<?php echo (int)$r['id']; ?>">View</a>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script>
        setTimeout(function(){ var f=document.getElementById('flashMsg'); if(f){ f.style.opacity='0'; setTimeout(()=>f.remove(), 500);}}, 4000);
    </script>
</body>
</html>

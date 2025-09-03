<?php
session_start();
require_once 'includes/functions.php';

// Require user session
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header('Location: login.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$payments = [];
$totals = ['count' => 0, 'sum' => 0.0];
$filterRequestId = isset($_GET['request_id']) ? (int)$_GET['request_id'] : 0;

try {
    $db = Database::getInstance();
    if ($filterRequestId) {
        $sql = "SELECT p.id, p.service_request_id, p.amount, p.payment_method, p.transaction_id, p.status, p.created_at,
                       sr.request_type, sr.status AS request_status
                FROM payments p
                JOIN service_requests sr ON sr.id = p.service_request_id
                WHERE sr.user_id = ? AND p.service_request_id = ?
                ORDER BY p.created_at DESC";
        $stmt = $db->executeQuery($sql, [$userId, $filterRequestId]);
    } else {
        $sql = "SELECT p.id, p.service_request_id, p.amount, p.payment_method, p.transaction_id, p.status, p.created_at,
                       sr.request_type, sr.status AS request_status
                FROM payments p
                JOIN service_requests sr ON sr.id = p.service_request_id
                WHERE sr.user_id = ?
                ORDER BY p.created_at DESC";
        $stmt = $db->executeQuery($sql, [$userId]);
    }
    $payments = $stmt->fetchAll();

    foreach ($payments as $pm) {
        if ($pm['status'] === 'completed') {
            $totals['sum'] += (float)$pm['amount'];
        }
        $totals['count']++;
    }
} catch (Exception $e) {
    error_log('Fetch payments failed: ' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments - EV Mobile Power & Service Station</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; color: #333; }

        .navbar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1rem 1rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .navbar-content { display: flex; justify-content: space-between; align-items: center; width: 100%; }
        .navbar-brand { font-size: 1.5rem; font-weight: bold; color: white; text-decoration: none; display: flex; align-items: center; gap: 0.5rem; }
        .navbar-nav { display: flex; list-style: none; gap: 1rem; align-items: center; }
        .navbar-nav a { color: white; text-decoration: none; padding: 0.5rem 0.75rem; border-radius: 8px; transition: background 0.25s ease; }
        .navbar-nav a:hover { background: rgba(255,255,255,0.18); }

        .main { width: 100%; padding: 1rem; }
        .page-header { margin: 1rem 0 1.25rem; }
        .page-header h1 { margin-bottom: 0.25rem; }

        .summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 1rem; }
        .summary-card { background: #fff; border: 1px solid #eef0ff; border-radius: 12px; padding: 1rem; box-shadow: 0 8px 20px rgba(0,0,0,0.04); }
        .summary-title { color: #666; font-size: 0.9rem; }
        .summary-value { font-size: 1.6rem; font-weight: 700; color: #222; margin-top: 0.25rem; }

        .card { background: #fff; border-radius: 12px; border: 1px solid #eef0ff; box-shadow: 0 10px 25px rgba(0,0,0,0.04); }
        .card-header { padding: 1rem; border-bottom: 1px solid #f0f0f5; display: flex; justify-content: space-between; align-items: center; }
        .card-body { padding: 1rem; }

        /* Modal */
        .modal-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,0.45); display:none; align-items:center; justify-content:center; z-index: 1000; }
        .modal { background:#fff; width: 95%; max-width: 560px; border-radius: 12px; box-shadow: 0 18px 50px rgba(0,0,0,0.2); overflow:hidden; }
        .modal-header { display:flex; justify-content:space-between; align-items:center; padding: 0.9rem 1rem; border-bottom:1px solid #f0f0f5; }
        .modal-title { font-weight:700; }
        .modal-close { background:none; border:none; font-size:1.2rem; cursor:pointer; }
        .modal-body { padding: 1rem; }
        .details-grid { display:grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }
        .details-item { background:#fafbff; border:1px solid #eef0ff; border-radius:8px; padding:0.6rem 0.75rem; }
        .details-item small { display:block; color:#666; }
        .details-item strong { color:#222; }

        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { text-align: left; padding: 0.75rem; border-bottom: 1px solid #f0f0f5; }
        .table th { color: #555; font-weight: 600; background: #fafbff; }
        .table tr:hover { background: #fafbff; }

        .badge { display: inline-block; padding: 0.25rem 0.6rem; border-radius: 999px; font-size: 0.8rem; font-weight: 600; }
        .badge.completed { background: #e9f8ee; color: #1a6d38; border: 1px solid #c5efd4; }
        .badge.pending { background: #fff7df; color: #8a6d00; border: 1px solid #ffe39a; }
        .badge.failed { background: #fde8ea; color: #8a2530; border: 1px solid #f8c7cd; }
        .badge.refunded { background: #e8f7fb; color: #0c5460; border: 1px solid #cdeef6; }

        .pill { display: inline-block; padding: 0.2rem 0.55rem; border-radius: 6px; background: #eef0ff; color: #445; font-size: 0.8rem; }

        .muted { color: #666; font-size: 0.9rem; }

        @media (max-width: 768px) {
            .navbar-content { flex-direction: column; gap: 0.75rem; }
            .table thead { display: none; }
            .table tr { display: block; margin-bottom: 0.75rem; border: 1px solid #f0f0f5; border-radius: 8px; }
            .table td { display: grid; grid-template-columns: 140px 1fr; gap: 0.5rem; border: none; border-bottom: 1px dashed #f0f0f5; }
            .table td:last-child { border-bottom: none; }
            .table td::before { content: attr(data-label); font-weight: 600; color: #555; }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-content">
            <a href="index.php" class="navbar-brand"><i class="fas fa-bolt"></i> EV Mobile Station</a>
            <ul class="navbar-nav">
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="request-service.php"><i class="fas fa-plus-circle"></i> Request Service</a></li>
                <li><a href="service-history.php"><i class="fas fa-history"></i> History</a></li>
                <li><a href="vehicles.php"><i class="fas fa-car"></i> My Vehicles</a></li>
                <li><a href="payments.php"><i class="fas fa-credit-card"></i> Payments</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
    </nav>

    <main class="main">
        <div class="page-header">
            <h1><i class="fas fa-credit-card"></i> Payments</h1>
            <p class="muted">View how much you paid for your service requests</p>
        </div>

        <div class="card" style="margin-bottom:1rem;">
            <div class="card-body">
                <form method="GET" style="display:grid;grid-template-columns:1fr auto;gap:0.5rem;max-width:520px;">
                    <input type="number" name="request_id" placeholder="Filter by Service Request ID" value="<?php echo $filterRequestId ?: ''; ?>" style="padding:0.6rem;border:1px solid #e1e5e9;border-radius:8px;">
                    <button type="submit" style="background:#667eea;color:#fff;border:none;border-radius:8px;padding:0.6rem 1rem;cursor:pointer;">Apply</button>
                </form>
            </div>
        </div>

        <div class="summary">
            <div class="summary-card">
                <div class="summary-title">Total Payments</div>
                <div class="summary-value">₹<?php echo number_format($totals['sum'], 2); ?></div>
            </div>
            <div class="summary-card">
                <div class="summary-title">Transactions</div>
                <div class="summary-value"><?php echo (int)$totals['count']; ?></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <strong>Payment History</strong>
                <span class="pill">User ID: <?php echo (int)$userId; ?></span>
            </div>
            <div class="card-body">
                <?php if (empty($payments)): ?>
                    <div class="summary-card" style="margin:0;">
                        <h3 style="margin:0 0 0.25rem 0;">No payments yet</h3>
                        <p class="muted">Your successful payments will show up here once you complete a service.</p>
                        <a href="request-service.php" style="display:inline-block;margin-top:0.5rem;background:#667eea;color:#fff;padding:0.6rem 1rem;border-radius:8px;text-decoration:none;">
                            <i class="fas fa-plus"></i> Request a Service
                        </a>
                    </div>
                <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Request</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th>Transaction ID</th>
                            <th>Date</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $pm): ?>
                            <tr>
                                <td data-label="Request">
                                    #<?php echo (int)$pm['service_request_id']; ?>
                                    <div class="muted"><?php echo ucfirst($pm['request_type']); ?> (<?php echo str_replace('_',' ', $pm['request_status']); ?>)</div>
                                </td>
                                <td data-label="Amount">₹<?php echo number_format((float)$pm['amount'], 2); ?></td>
                                <td data-label="Method"><?php echo ucwords(str_replace('_',' ', $pm['payment_method'])); ?></td>
                                <td data-label="Status">
                                    <span class="badge <?php echo htmlspecialchars($pm['status']); ?>"><?php echo ucfirst($pm['status']); ?></span>
                                </td>
                                <td data-label="Transaction ID"><?php echo htmlspecialchars($pm['transaction_id'] ?: '—'); ?></td>
                                <td data-label="Date"><?php echo UtilityFunctions::formatDateTime($pm['created_at']); ?></td>
                                <td data-label=" ">
                                    <button type="button" class="view-btn" 
                                        data-id="<?php echo (int)$pm['id']; ?>"
                                        data-request_id="<?php echo (int)$pm['service_request_id']; ?>"
                                        data-amount="<?php echo number_format((float)$pm['amount'],2); ?>"
                                        data-method="<?php echo htmlspecialchars(ucwords(str_replace('_',' ',$pm['payment_method']))); ?>"
                                        data-status="<?php echo htmlspecialchars(ucfirst($pm['status'])); ?>"
                                        data-transaction_id="<?php echo htmlspecialchars($pm['transaction_id'] ?: '—'); ?>"
                                        data-date="<?php echo htmlspecialchars(UtilityFunctions::formatDateTime($pm['created_at'])); ?>"
                                        data-request_type="<?php echo htmlspecialchars(ucfirst($pm['request_type'])); ?>"
                                        data-request_status="<?php echo htmlspecialchars(ucfirst(str_replace('_',' ',$pm['request_status']))); ?>"
                                        style="background:#667eea;color:#fff;border:none;border-radius:8px;padding:0.4rem 0.8rem;cursor:pointer;">
                                        View Details
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Details Modal -->
    <div id="paymentModal" class="modal-backdrop">
        <div class="modal">
            <div class="modal-header">
                <div class="modal-title"><i class="fas fa-receipt"></i> Payment Details</div>
                <button class="modal-close" id="paymentClose">×</button>
            </div>
            <div class="modal-body">
                <div class="details-grid">
                    <div class="details-item"><small>Transaction ID</small><strong id="mTxn">—</strong></div>
                    <div class="details-item"><small>Date</small><strong id="mDate">—</strong></div>
                    <div class="details-item"><small>Amount</small><strong id="mAmount">—</strong></div>
                    <div class="details-item"><small>Status</small><strong id="mStatus">—</strong></div>
                    <div class="details-item"><small>Method</small><strong id="mMethod">—</strong></div>
                    <div class="details-item"><small>Service Request</small><strong id="mReq">—</strong></div>
                    <div class="details-item"><small>Request Type</small><strong id="mReqType">—</strong></div>
                    <div class="details-item"><small>Request Status</small><strong id="mReqStatus">—</strong></div>
                </div>
                <div style="margin-top:1rem;text-align:right;">
                    <a id="trackLink" href="#" style="text-decoration:none;background:#6c757d;color:#fff;padding:0.5rem 0.8rem;border-radius:8px;">Track Request</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function(){
            const modal = document.getElementById('paymentModal');
            const closeBtn = document.getElementById('paymentClose');
            const openModal = ()=>{ modal.style.display='flex'; };
            const hideModal = ()=>{ modal.style.display='none'; };
            if (closeBtn) closeBtn.addEventListener('click', hideModal);
            modal.addEventListener('click', (e)=>{ if(e.target===modal) hideModal(); });

            document.querySelectorAll('.view-btn').forEach(btn=>{
                btn.addEventListener('click', ()=>{
                    document.getElementById('mTxn').textContent = btn.dataset.transaction_id || '—';
                    document.getElementById('mDate').textContent = btn.dataset.date || '—';
                    document.getElementById('mAmount').textContent = '₹' + (btn.dataset.amount || '0.00');
                    document.getElementById('mStatus').textContent = btn.dataset.status || '—';
                    document.getElementById('mMethod').textContent = btn.dataset.method || '—';
                    document.getElementById('mReq').textContent = '#' + (btn.dataset.request_id || '—');
                    document.getElementById('mReqType').textContent = btn.dataset.request_type || '—';
                    document.getElementById('mReqStatus').textContent = btn.dataset.request_status || '—';
                    const trackLink = document.getElementById('trackLink');
                    trackLink.href = 'track-service.php?id=' + (btn.dataset.request_id || '');
                    openModal();
                });
            });
        })();
    </script>
</body>
</html>


<?php
session_start();
require_once '../includes/functions.php';

// Simple admin session check
if (!isset($_SESSION['admin_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$message = '';
$error = '';

// Get request ID and status from URL parameters
$request_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$new_status = isset($_GET['status']) ? $_GET['status'] : '';

// Valid statuses
$valid_statuses = ['pending', 'in_progress', 'completed', 'cancelled'];

if (!$request_id || !in_array($new_status, $valid_statuses)) {
    $error = 'Invalid request ID or status.';
} else {
    try {
        $db = Database::getInstance();
        
        // Check if request exists
        $stmt = $db->executeQuery("SELECT * FROM service_requests WHERE id = ?", [$request_id]);
        $request = $stmt->fetch();
        
        if (!$request) {
            $error = 'Service request not found.';
        } else {
            // Update the status
            $result = $db->executeQuery(
                "UPDATE service_requests SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
                [$new_status, $request_id]
            );
            
            if ($result) {
                // Try to log the status change (optional - table might not exist yet)
                try {
                    $db->executeQuery(
                        "INSERT INTO service_request_history (service_request_id, status, notes, created_at) VALUES (?, ?, ?, CURRENT_TIMESTAMP)",
                        [$request_id, $new_status, "Status updated to " . ucfirst($new_status) . " by admin"]
                    );
                } catch (Exception $e) {
                    // History logging failed, but that's okay - main status update succeeded
                    error_log("Failed to log status change to history: " . $e->getMessage());
                }
                
                $message = "Service request #$request_id status updated to " . ucfirst($new_status) . " successfully!";
                
                // Redirect back to the referring page after a short delay
                header("refresh:2;url=" . ($_SERVER['HTTP_REFERER'] ?? 'requests.php'));
            } else {
                $error = 'Failed to update status.';
            }
        }
    } catch (Exception $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Status - Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .status-update-container {
            background: white;
            border-radius: 15px;
            padding: 3rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 500px;
            width: 90%;
        }

        .status-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }

        .status-success {
            color: #28a745;
        }

        .status-error {
            color: #dc3545;
        }

        .status-info {
            color: #17a2b8;
        }

        .message {
            font-size: 1.2rem;
            margin-bottom: 1rem;
            color: #333;
        }

        .request-details {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 10px;
            margin: 1.5rem 0;
            text-align: left;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            padding: 0.5rem 0;
            border-bottom: 1px solid #e9ecef;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: bold;
            color: #495057;
        }

        .detail-value {
            color: #6c757d;
        }

        .redirect-message {
            color: #6c757d;
            font-size: 0.9rem;
            margin-top: 1.5rem;
        }

        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            margin: 0.5rem;
            transition: transform 0.3s;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #6c757d;
        }

        .btn-danger {
            background: #dc3545;
        }

        .btn-success {
            background: #28a745;
        }

        .btn-warning {
            background: #ffc107;
            color: #212529;
        }

        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .badge-pending {
            background: #fff3cd;
            color: #856404;
        }

        .badge-in_progress {
            background: #d1ecf1;
            color: #0c5460;
        }

        .badge-completed {
            background: #d4edda;
            color: #155724;
        }

        .badge-cancelled {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="status-update-container">
        <?php if ($message): ?>
            <div class="status-icon status-success">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
            
            <?php if ($request): ?>
            <div class="request-details">
                <h3>Request Details</h3>
                <div class="detail-row">
                    <span class="detail-label">Request ID:</span>
                    <span class="detail-value">#<?php echo $request['id']; ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">User:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($request['user_id']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Type:</span>
                    <span class="detail-value"><?php echo ucfirst(htmlspecialchars($request['request_type'])); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">New Status:</span>
                    <span class="detail-value">
                        <span class="badge badge-<?php echo $new_status; ?>">
                            <?php echo ucfirst($new_status); ?>
                        </span>
                    </span>
                </div>
            </div>
            <?php endif; ?>
            
        <?php elseif ($error): ?>
            <div class="status-icon status-error">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="redirect-message">
            <i class="fas fa-clock"></i> Redirecting in 2 seconds...
        </div>

        <div style="margin-top: 2rem;">
            <a href="requests.php" class="btn">
                <i class="fas fa-list"></i> Back to Requests
            </a>
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
        </div>
    </div>

    <script>
        // Auto-redirect after 2 seconds
        setTimeout(() => {
            const referrer = '<?php echo $_SERVER['HTTP_REFERER'] ?? "requests.php"; ?>';
            if (referrer && referrer !== window.location.href) {
                window.location.href = referrer;
            } else {
                window.location.href = 'requests.php';
            }
        }, 2000);
    </script>
</body>
</html>

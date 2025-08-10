<?php
session_start();
require_once 'includes/functions.php';
require_once 'includes/dummy_data.php';

// Simple session check
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['mark_read'])) {
        $notification_id = $_POST['notification_id'];
        $message = 'Notification marked as read!';
    } elseif (isset($_POST['mark_all_read'])) {
        $message = 'All notifications marked as read!';
    } elseif (isset($_POST['delete_notification'])) {
        $notification_id = $_POST['notification_id'];
        $message = 'Notification deleted!';
    }
}

// Dummy notifications data
$notifications = [
    [
        'id' => 1,
        'type' => 'service_update',
        'title' => 'Service Request Updated',
        'message' => 'Your service request #SR-2024-001 has been assigned to technician John Smith.',
        'timestamp' => '2024-01-15 14:30:00',
        'is_read' => false,
        'priority' => 'high'
    ],
    [
        'id' => 2,
        'type' => 'payment',
        'title' => 'Payment Received',
        'message' => 'Payment of $150.00 for service request #SR-2024-001 has been received successfully.',
        'timestamp' => '2024-01-15 12:15:00',
        'is_read' => true,
        'priority' => 'medium'
    ],
    [
        'id' => 3,
        'type' => 'service_complete',
        'title' => 'Service Completed',
        'message' => 'Your service request #SR-2023-012 has been completed. Please rate your experience.',
        'timestamp' => '2024-01-14 16:45:00',
        'is_read' => false,
        'priority' => 'high'
    ],
    [
        'id' => 4,
        'type' => 'promotion',
        'title' => 'Special Offer',
        'message' => 'Get 20% off on your next service request. Use code: SAVE20',
        'timestamp' => '2024-01-14 10:00:00',
        'is_read' => true,
        'priority' => 'low'
    ],
    [
        'id' => 5,
        'type' => 'system',
        'title' => 'System Maintenance',
        'message' => 'Scheduled maintenance on January 20th, 2024 from 2:00 AM to 4:00 AM.',
        'timestamp' => '2024-01-13 09:30:00',
        'is_read' => true,
        'priority' => 'medium'
    ],
    [
        'id' => 6,
        'type' => 'service_update',
        'title' => 'Technician En Route',
        'message' => 'Technician Mike Johnson is on the way to your location. ETA: 15 minutes.',
        'timestamp' => '2024-01-12 15:20:00',
        'is_read' => false,
        'priority' => 'high'
    ],
    [
        'id' => 7,
        'type' => 'payment',
        'title' => 'Payment Pending',
        'message' => 'Payment of $200.00 for service request #SR-2023-015 is pending. Please complete payment.',
        'timestamp' => '2024-01-12 11:45:00',
        'is_read' => true,
        'priority' => 'medium'
    ],
    [
        'id' => 8,
        'type' => 'promotion',
        'title' => 'New Service Available',
        'message' => 'We now offer 24/7 emergency roadside assistance. Call us anytime!',
        'timestamp' => '2024-01-11 14:15:00',
        'is_read' => true,
        'priority' => 'low'
    ]
];

// Filter notifications
$filter = $_GET['filter'] ?? 'all';
$filtered_notifications = $notifications;

if ($filter === 'unread') {
    $filtered_notifications = array_filter($notifications, function($n) {
        return !$n['is_read'];
    });
} elseif ($filter === 'high') {
    $filtered_notifications = array_filter($notifications, function($n) {
        return $n['priority'] === 'high';
    });
}

$unread_count = count(array_filter($notifications, function($n) {
    return !$n['is_read'];
}));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - EV Mobile Power & Service Station</title>
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
            padding: 20px;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .header h1 {
            color: #333;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-links {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .nav-links a {
            text-decoration: none;
            color: #667eea;
            padding: 8px 16px;
            border-radius: 25px;
            background: rgba(102, 126, 234, 0.1);
            transition: all 0.3s ease;
        }

        .nav-links a:hover {
            background: #667eea;
            color: white;
        }

        .notifications-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .notifications-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .notifications-title {
            color: #333;
            font-size: 24px;
            font-weight: 600;
        }

        .notifications-actions {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .filter-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .filter-tab {
            padding: 10px 20px;
            border: 2px solid #e1e5e9;
            border-radius: 25px;
            background: white;
            color: #666;
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .filter-tab.active,
        .filter-tab:hover {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .notification-item {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border-left: 4px solid #e1e5e9;
        }

        .notification-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .notification-item.unread {
            border-left-color: #667eea;
            background: rgba(102, 126, 234, 0.05);
        }

        .notification-item.high-priority {
            border-left-color: #dc3545;
        }

        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }

        .notification-title {
            font-weight: 600;
            color: #333;
            font-size: 16px;
            margin-bottom: 5px;
        }

        .notification-time {
            color: #666;
            font-size: 12px;
        }

        .notification-message {
            color: #555;
            line-height: 1.5;
            margin-bottom: 15px;
        }

        .notification-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }

        .notification-type {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .type-service_update {
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
        }

        .type-payment {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
        }

        .type-service_complete {
            background: rgba(255, 193, 7, 0.1);
            color: #ffc107;
        }

        .type-promotion {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }

        .type-system {
            background: rgba(108, 117, 125, 0.1);
            color: #6c757d;
        }

        .priority-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 8px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            margin-left: 10px;
        }

        .priority-high {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }

        .priority-medium {
            background: rgba(255, 193, 7, 0.1);
            color: #ffc107;
        }

        .priority-low {
            background: rgba(108, 117, 125, 0.1);
            color: #6c757d;
        }

        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: rgba(40, 167, 69, 0.1);
            border: 1px solid #28a745;
            color: #155724;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .empty-state i {
            font-size: 48px;
            color: #ccc;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            margin-bottom: 10px;
            color: #333;
        }

        @media (max-width: 768px) {
            .notifications-header {
                flex-direction: column;
                align-items: stretch;
            }

            .notifications-actions {
                justify-content: center;
            }

            .filter-tabs {
                justify-content: center;
            }

            .notification-header {
                flex-direction: column;
                gap: 10px;
            }

            .notification-actions {
                justify-content: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-bell"></i> Notifications</h1>
            <div class="nav-links">
                <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                <a href="request-service.php"><i class="fas fa-plus"></i> Request Service</a>
                <a href="track-service.php"><i class="fas fa-map-marker-alt"></i> Track Service</a>
                <a href="service-history.php"><i class="fas fa-history"></i> Service History</a>
                <a href="payments.php"><i class="fas fa-credit-card"></i> Payments</a>
                <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="notifications-container">
            <div class="notifications-header">
                <div class="notifications-title">
                    Notifications
                    <?php if ($unread_count > 0): ?>
                        <span style="background: #dc3545; color: white; padding: 4px 8px; border-radius: 12px; font-size: 12px; margin-left: 10px;">
                            <?php echo $unread_count; ?> unread
                        </span>
                    <?php endif; ?>
                </div>
                <div class="notifications-actions">
                    <form method="POST" style="display: inline;">
                        <button type="submit" name="mark_all_read" class="btn btn-secondary">
                            <i class="fas fa-check-double"></i> Mark All Read
                        </button>
                    </form>
                    <a href="notifications.php" class="btn btn-primary">
                        <i class="fas fa-sync"></i> Refresh
                    </a>
                </div>
            </div>

            <div class="filter-tabs">
                <a href="?filter=all" class="filter-tab <?php echo $filter === 'all' ? 'active' : ''; ?>">
                    All (<?php echo count($notifications); ?>)
                </a>
                <a href="?filter=unread" class="filter-tab <?php echo $filter === 'unread' ? 'active' : ''; ?>">
                    Unread (<?php echo $unread_count; ?>)
                </a>
                <a href="?filter=high" class="filter-tab <?php echo $filter === 'high' ? 'active' : ''; ?>">
                    High Priority
                </a>
            </div>

            <?php if (empty($filtered_notifications)): ?>
                <div class="empty-state">
                    <i class="fas fa-bell-slash"></i>
                    <h3>No notifications found</h3>
                    <p>You're all caught up! Check back later for new updates.</p>
                </div>
            <?php else: ?>
                <?php foreach ($filtered_notifications as $notification): ?>
                    <div class="notification-item <?php echo !$notification['is_read'] ? 'unread' : ''; ?> <?php echo $notification['priority'] === 'high' ? 'high-priority' : ''; ?>">
                        <div class="notification-header">
                            <div>
                                <div class="notification-type type-<?php echo $notification['type']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $notification['type'])); ?>
                                </div>
                                <div class="notification-title">
                                    <?php echo htmlspecialchars($notification['title']); ?>
                                    <span class="priority-badge priority-<?php echo $notification['priority']; ?>">
                                        <?php echo ucfirst($notification['priority']); ?>
                                    </span>
                                </div>
                                <div class="notification-time">
                                    <i class="fas fa-clock"></i>
                                    <?php echo format_datetime($notification['timestamp']); ?>
                                </div>
                            </div>
                        </div>
                        <div class="notification-message">
                            <?php echo htmlspecialchars($notification['message']); ?>
                        </div>
                        <div class="notification-actions">
                            <?php if (!$notification['is_read']): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                    <button type="submit" name="mark_read" class="btn btn-primary btn-sm">
                                        <i class="fas fa-check"></i> Mark Read
                                    </button>
                                </form>
                            <?php endif; ?>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this notification?');">
                                <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                <button type="submit" name="delete_notification" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.style.display = 'none';
                }, 300);
            });
        }, 5000);

        // Add click animation to notification items
        document.querySelectorAll('.notification-item').forEach(function(item) {
            item.addEventListener('click', function() {
                this.style.transform = 'scale(0.98)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 150);
            });
        });
    </script>
</body>
</html> 
<?php
session_start();
require_once '../includes/functions.php';

if (!isset($_SESSION['technician_id']) || ($_SESSION['user_type'] ?? '') !== 'technician') {
    header('Location: ../login.php');
    exit;
}

$technicianId = (int)$_SESSION['technician_id'];
$db = Database::getInstance();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    // Server-side validation
    if ($full_name === '' || $phone === '') {
        $error = 'Name and phone are required.';
    } elseif (!preg_match('/^[A-Za-z ]{2,}$/', $full_name)) {
        $error = 'Full name must contain only letters and spaces (min 2 characters).';
    } elseif (!preg_match('/^\d{10}$/', $phone)) {
        $error = 'Phone must be exactly 10 digits.';
    } else {
        try {
            if ($password !== '') {
                if ($password !== $confirm) {
                    $error = 'Passwords do not match.';
                } else {
                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    $db->executeQuery("UPDATE technicians SET full_name = ?, phone = ?, password = ? WHERE id = ?", [$full_name, $phone, $hash, $technicianId]);
                    $_SESSION['technician_name'] = $full_name;
                    $message = 'Profile and password updated.';
                }
            } else {
                $db->executeQuery("UPDATE technicians SET full_name = ?, phone = ? WHERE id = ?", [$full_name, $phone, $technicianId]);
                $_SESSION['technician_name'] = $full_name;
                $message = 'Profile updated.';
            }
        } catch (Exception $e) {
            $error = 'Update failed: ' . $e->getMessage();
        }
    }
}

// Load current profile
$tech = ['full_name' => $_SESSION['technician_name'] ?? '', 'phone' => ''];
try {
    $stmt = $db->executeQuery("SELECT full_name, phone, email, status FROM technicians WHERE id = ?", [$technicianId]);
    $row = $stmt->fetch();
    if ($row) $tech = $row;
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Technician</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;background:#f5f7fa}
        .topbar{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#fff;padding:1rem 1.5rem;display:flex;justify-content:space-between;align-items:center}
        .nav a{color:#fff;text-decoration:none;margin-left:1rem;background:rgba(255,255,255,0.15);padding:0.5rem .75rem;border-radius:6px}
        .container{padding:1.5rem;max-width:900px;margin:0 auto}
        .card{background:#fff;border-radius:12px;box-shadow:0 5px 15px rgba(0,0,0,.08);padding:1rem}
        .row{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
        .form-group{margin-bottom:1rem}
        label{display:block;margin-bottom:.35rem;color:#333}
        input{width:100%;padding:.75rem;border:1px solid #e5e8ee;border-radius:8px}
        .btn{padding:.65rem 1rem;border:none;border-radius:8px;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#fff;cursor:pointer}
        .alert{padding:.75rem 1rem;border-radius:8px;margin-bottom:1rem}
        .success{background:#e9f7ef;color:#155724;border:1px solid #c3e6cb}
        .error{background:#f8d7da;color:#721c24;border:1px solid #f5c6cb}
        .hint{font-size:.85rem;color:#777;margin-top:.25rem}
        @media (max-width: 768px){ .row{grid-template-columns:1fr} }
    </style>
</head>
<body>
    <div class="topbar">
        <div><i class="fas fa-user"></i> My Profile</div>
        <div class="nav">
            <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="requests.php"><i class="fas fa-clipboard-list"></i> My Requests</a>
            <a href="update-availability.php?toggle=1&redirect=profile.php"><i class="fas fa-toggle-on"></i> Toggle Availability</a>
            <a href="../logout.php" style="background:#ff6b6b;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="container">
        <?php if ($message): ?><div class="alert success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <div class="card">
            <form method="post" id="profileForm" novalidate>
                <div class="row">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="full_name" id="full_name" value="<?php echo htmlspecialchars($tech['full_name'] ?? ''); ?>" required pattern="^[A-Za-z ]{2,}$" title="Only letters and spaces, at least 2 characters">
                        <div class="hint">Only letters and spaces, minimum 2 characters.</div>
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone" id="phone" value="<?php echo htmlspecialchars($tech['phone'] ?? ''); ?>" required maxlength="10" pattern="^\d{10}$" title="Exactly 10 digits">
                        <div class="hint">Exactly 10 digits. No letters or symbols.</div>
                    </div>
                </div>
                <div class="row">
                    <div class="form-group">
                        <label>Email (read-only)</label>
                        <input type="text" value="<?php echo htmlspecialchars($tech['email'] ?? ''); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <input type="text" value="<?php echo htmlspecialchars($tech['status'] ?? ''); ?>" readonly>
                    </div>
                </div>
                <hr style="margin:1rem 0;border:none;border-top:1px solid #eee">
                <div class="row">
                    <div class="form-group">
                        <label>New Password (optional)</label>
                        <input type="password" name="password" id="password" placeholder="Leave blank to keep current">
                    </div>
                    <div class="form-group">
                        <label>Confirm Password</label>
                        <input type="password" name="confirm_password" id="confirm_password" placeholder="Retype new password">
                    </div>
                </div>
                <button class="btn" type="submit"><i class="fas fa-save"></i> Save Changes</button>
            </form>
        </div>
    </div>

    <script>
        // Client-side validation helpers
        const nameInput = document.getElementById('full_name');
        const phoneInput = document.getElementById('phone');
        const form = document.getElementById('profileForm');

        // Prevent non-letters in name
        nameInput.addEventListener('input', function(){
            this.value = this.value.replace(/[^A-Za-z ]+/g, '');
        });
        // Only digits, max 10
        phoneInput.addEventListener('input', function(){
            this.value = this.value.replace(/\D+/g, '').slice(0,10);
        });

        form.addEventListener('submit', function(e){
            let valid = true;
            if (!/^([A-Za-z ]{2,})$/.test(nameInput.value.trim())) valid = false;
            if (!/^\d{10}$/.test(phoneInput.value.trim())) valid = false;
            if (!valid) {
                e.preventDefault();
                alert('Please correct the highlighted fields.');
            }
        });
    </script>
</body>
</html>

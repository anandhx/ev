<?php
/**
 * Debug Login Issues
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Debug Login Issues\n";
echo "==================\n\n";

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "POST Request Received\n";
    echo "====================\n";
    
    echo "POST Data:\n";
    print_r($_POST);
    
    echo "\nSession Data:\n";
    session_start();
    print_r($_SESSION);
    
    // Test the login process
    try {
        require_once 'includes/functions.php';
        SessionManager::startSession();
        
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        echo "\nAttempting Login:\n";
        echo "Username: $username\n";
        echo "Password: $password\n";
        
        $userManager = new UserManager();
        $user = $userManager->authenticateUser($username, $password);
        
        if ($user) {
            echo "✓ Authentication successful!\n";
            echo "User data: " . json_encode($user) . "\n";
            
            // Set session
            SessionManager::setUserSession($user);
            echo "✓ Session set\n";
            
            // Check session
            if (SessionManager::isLoggedIn()) {
                echo "✓ User is logged in\n";
                echo "User ID: " . SessionManager::getCurrentUserId() . "\n";
            } else {
                echo "❌ User is NOT logged in\n";
            }
            
        } else {
            echo "❌ Authentication failed\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
    
} else {
    echo "GET Request - Show Login Form\n";
    echo "=============================\n";
    
    // Check current session
    session_start();
    echo "Current Session Data:\n";
    print_r($_SESSION);
    
    if (isset($_SESSION['user_id'])) {
        echo "\n✓ User is already logged in!\n";
        echo "User ID: " . $_SESSION['user_id'] . "\n";
        echo "Username: " . $_SESSION['username'] . "\n";
    } else {
        echo "\n❌ No user session found\n";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Debug Login</title>
</head>
<body>
    <h1>Debug Login Form</h1>
    
    <?php if ($_SERVER['REQUEST_METHOD'] !== 'POST'): ?>
    <form method="POST">
        <p>
            <label>Username:</label>
            <input type="text" name="username" value="ask" required>
        </p>
        <p>
            <label>Password:</label>
            <input type="password" name="password" value="Anandhu@123" required>
        </p>
        <p>
            <button type="submit">Test Login</button>
        </p>
    </form>
    
    <p><strong>Test Credentials:</strong></p>
    <ul>
        <li>ask / Anandhu@123</li>
        <li>john_doe / demo123</li>
        <li>admin / admin123</li>
    </ul>
    <?php endif; ?>
    
    <p><a href="login.php">Go to Real Login Page</a></p>
</body>
</html>

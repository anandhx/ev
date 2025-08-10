<?php
/**
 * Simple Login Test
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Simple Login Test\n";
echo "=================\n\n";

try {
    // Include the config and functions
    require_once 'config/config.php';
    require_once 'includes/functions.php';
    
    echo "1. Testing session initialization...\n";
    SessionManager::startSession();
    echo "✓ Session started\n";
    
    echo "\n2. Testing user authentication...\n";
    $userManager = new UserManager();
    $user = $userManager->authenticateUser('ask', 'Anandhu@123');
    
    if ($user) {
        echo "✓ Authentication successful\n";
        echo "   Username: {$user['username']}\n";
        echo "   Email: {$user['email']}\n";
        
        echo "\n3. Testing session management...\n";
        SessionManager::setUserSession($user);
        
        if (SessionManager::isLoggedIn()) {
            echo "✓ User is logged in\n";
            echo "   User ID: " . SessionManager::getCurrentUserId() . "\n";
            echo "   Username: " . $_SESSION['username'] . "\n";
            
            echo "\n4. Testing logout...\n";
            SessionManager::logout();
            
            if (!SessionManager::isLoggedIn()) {
                echo "✓ Logout successful\n";
            } else {
                echo "❌ Logout failed\n";
            }
            
        } else {
            echo "❌ User is not logged in\n";
        }
        
    } else {
        echo "❌ Authentication failed\n";
    }
    
    echo "\n🎉 Test completed!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>

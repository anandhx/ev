<?php
/**
 * Test Login Process
 * Simulate the exact login process
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing Login Process for 'ask' with 'Anandhu@123'\n";
echo "==================================================\n\n";

try {
    require_once 'includes/functions.php';
    
    // Start session
    SessionManager::startSession();
    
    $username = 'ask';
    $password = 'Anandhu@123';
    
    echo "1. Testing User Authentication...\n";
    $userManager = new UserManager();
    $user = $userManager->authenticateUser($username, $password);
    
    if ($user) {
        echo "✓ User authentication successful!\n";
        echo "   - Username: {$user['username']}\n";
        echo "   - Email: {$user['email']}\n";
        echo "   - Full Name: {$user['full_name']}\n\n";
        
        echo "2. Testing Session Management...\n";
        try {
            SessionManager::setUserSession($user);
            echo "✓ User session set successfully\n";
            
            // Check if session is working
            if (SessionManager::isLoggedIn()) {
                echo "✓ Session login status: TRUE\n";
                $current_user_id = SessionManager::getCurrentUserId();
                echo "✓ Current user ID: $current_user_id\n";
                
                if (SessionManager::isAdmin()) {
                    echo "✓ User is admin: TRUE\n";
                } else {
                    echo "✓ User is admin: FALSE\n";
                }
                
            } else {
                echo "❌ Session login status: FALSE\n";
            }
            
        } catch (Exception $e) {
            echo "❌ Session error: " . $e->getMessage() . "\n";
        }
        
    } else {
        echo "❌ User authentication failed\n";
    }
    
    echo "\n3. Testing Database Query Directly...\n";
    require_once 'config/database.php';
    $db = Database::getInstance();
    
    $stmt = $db->executeQuery("SELECT * FROM users WHERE username = ?", [$username]);
    $db_user = $stmt->fetch();
    
    if ($db_user) {
        echo "✓ Database query successful\n";
        echo "   - Username: {$db_user['username']}\n";
        echo "   - Password hash: " . substr($db_user['password'], 0, 20) . "...\n";
        
        // Test password verification
        if (password_verify($password, $db_user['password'])) {
            echo "✓ Password verification successful\n";
        } else {
            echo "❌ Password verification failed\n";
        }
    } else {
        echo "❌ Database query failed\n";
    }
    
    echo "\n🎉 Login test completed!\n";
    
} catch (Exception $e) {
    echo "❌ Test failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>

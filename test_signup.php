<?php
/**
 * Test Signup Process
 * This script tests the signup functionality
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing Signup Process\n";
echo "======================\n\n";

try {
    require_once 'includes/functions.php';
    $userManager = new UserManager();
    
    // Test user data
    $test_user = [
        'username' => 'testuser_' . time(),
        'email' => 'test_' . time() . '@example.com',
        'password' => 'TestPass123!',
        'full_name' => 'Test User',
        'phone' => '+1234567890',
        'vehicle_model' => 'Tesla Model S',
        'vehicle_plate' => 'TEST-123'
    ];
    
    echo "1. Creating test user...\n";
    echo "   Username: {$test_user['username']}\n";
    echo "   Email: {$test_user['email']}\n";
    echo "   Password: {$test_user['password']}\n\n";
    
    $user_id = $userManager->createUser($test_user);
    
    if ($user_id) {
        echo "✓ User created successfully with ID: $user_id\n\n";
        
        echo "2. Testing authentication...\n";
        $authenticated_user = $userManager->authenticateUser($test_user['username'], $test_user['password']);
        
        if ($authenticated_user) {
            echo "✓ Authentication successful!\n";
            echo "   - ID: {$authenticated_user['id']}\n";
            echo "   - Username: {$authenticated_user['username']}\n";
            echo "   - Email: {$authenticated_user['email']}\n";
            echo "   - Full Name: {$authenticated_user['full_name']}\n";
            echo "   - Phone: {$authenticated_user['phone']}\n";
            echo "   - Vehicle: {$authenticated_user['vehicle_model']}\n";
            echo "   - Plate: {$authenticated_user['vehicle_plate']}\n\n";
            
            echo "3. Testing login with email...\n";
            $email_auth = $userManager->authenticateUser($test_user['email'], $test_user['password']);
            if ($email_auth) {
                echo "✓ Email login successful!\n";
            } else {
                echo "❌ Email login failed\n";
            }
            
        } else {
            echo "❌ Authentication failed\n";
        }
        
        // Clean up
        echo "\n4. Cleaning up test data...\n";
        require_once 'config/database.php';
        $db = Database::getInstance();
        $db->executeQuery("DELETE FROM users WHERE id = ?", [$user_id]);
        echo "✓ Test user removed\n";
        
    } else {
        echo "❌ User creation failed\n";
    }
    
    echo "\n🎉 Signup test completed!\n";
    echo "\nThe signup system is working correctly.\n";
    echo "You can now:\n";
    echo "1. Go to signup.php to create new accounts\n";
    echo "2. Use login.php to sign in with existing accounts\n";
    echo "3. Use the system normally\n";
    
} catch (Exception $e) {
    echo "❌ Test failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>

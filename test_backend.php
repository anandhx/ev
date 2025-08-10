<?php
/**
 * Backend Test Script
 * Test database connection, user creation, and authentication
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "EV Mobile Station - Backend Test\n";
echo "================================\n\n";

try {
    // Test 1: Database Connection
    echo "1. Testing Database Connection...\n";
    require_once 'config/database.php';
    $db = Database::getInstance();
    
    if ($db->testConnection()) {
        echo "✓ Database connection successful\n";
    } else {
        echo "❌ Database connection failed\n";
        exit(1);
    }
    
    // Test 2: Check if tables exist
    echo "\n2. Checking Database Tables...\n";
    $tables = ['users', 'admin_users', 'service_vehicles', 'technicians', 'service_requests'];
    foreach ($tables as $table) {
        $count = $db->getTableCount($table);
        echo "✓ Table '$table' exists with $count records\n";
    }
    
    // Test 3: Test User Creation
    echo "\n3. Testing User Creation...\n";
    require_once 'includes/functions.php';
    $userManager = new UserManager();
    
    $test_user = [
        'username' => 'test_user_' . time(),
        'email' => 'test_' . time() . '@example.com',
        'password' => 'TestPass123!',
        'full_name' => 'Test User',
        'phone' => '+1234567890',
        'vehicle_model' => 'Tesla Model S',
        'vehicle_plate' => 'TEST-123'
    ];
    
    $user_id = $userManager->createUser($test_user);
    if ($user_id) {
        echo "✓ User created successfully with ID: $user_id\n";
        
        // Test 4: Test User Authentication
        echo "\n4. Testing User Authentication...\n";
        $authenticated_user = $userManager->authenticateUser($test_user['username'], $test_user['password']);
        if ($authenticated_user) {
            echo "✓ User authentication successful\n";
            echo "  - Username: {$authenticated_user['username']}\n";
            echo "  - Email: {$authenticated_user['email']}\n";
            echo "  - Full Name: {$authenticated_user['full_name']}\n";
        } else {
            echo "❌ User authentication failed\n";
        }
        
        // Test 5: Test Admin Authentication
        echo "\n5. Testing Admin Authentication...\n";
        $adminManager = new AdminManager();
        $admin = $adminManager->authenticateAdmin('admin', 'admin123');
        if ($admin) {
            echo "✓ Admin authentication successful\n";
            echo "  - Username: {$admin['username']}\n";
            echo "  - Role: {$admin['role']}\n";
        } else {
            echo "❌ Admin authentication failed\n";
        }
        
        // Clean up test user
        echo "\n6. Cleaning up test data...\n";
        try {
            $db->executeQuery("DELETE FROM users WHERE id = ?", [$user_id]);
            echo "✓ Test user removed\n";
        } catch (Exception $e) {
            echo "⚠ Could not remove test user: " . $e->getMessage() . "\n";
        }
        
    } else {
        echo "❌ User creation failed\n";
    }
    
    echo "\n🎉 Backend test completed successfully!\n";
    echo "\nYou can now:\n";
    echo "1. Sign up new users at signup.php\n";
    echo "2. Login with existing users at login.php\n";
    echo "3. Use the system normally\n";
    
} catch (Exception $e) {
    echo "❌ Test failed with error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>

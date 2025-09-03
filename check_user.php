<?php
/**
 * Check User Data in Database
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Checking User Data for 'ask'\n";
echo "============================\n\n";

try {
    require_once 'config/database.php';
    $db = Database::getInstance();
    
    // Check if user exists
    $stmt = $db->executeQuery("SELECT * FROM users WHERE username = ?", ['ask']);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "✓ User found!\n\n";
        echo "User Details:\n";
        echo "  - ID: {$user['id']}\n";
        echo "  - Username: {$user['username']}\n";
        echo "  - Email: {$user['email']}\n";
        echo "  - Full Name: {$user['full_name']}\n";
        echo "  - Phone: {$user['phone']}\n";
        echo "  - Vehicle Model: {$user['vehicle_model']}\n";
        echo "  - Vehicle Plate: {$user['vehicle_plate']}\n";
        echo "  - Created: {$user['created_at']}\n";
        echo "  - Password Hash: {$user['password']}\n\n";
        
        // Test password verification
        echo "Testing Password Verification:\n";
        $test_password = 'Anandhu@123';
        $is_valid = password_verify($test_password, $user['password']);
        
        if ($is_valid) {
            echo "✓ Password 'Anandhu@123' is correct!\n";
        } else {
            echo "❌ Password 'Anandhu@123' is incorrect!\n";
        }
        
        // Test with UserManager
        echo "\nTesting with UserManager:\n";
        require_once 'includes/functions.php';
        $userManager = new UserManager();
        
        $auth_result = $userManager->authenticateUser('ask', 'Anandhu@123');
        if ($auth_result) {
            echo "✓ UserManager authentication successful!\n";
        } else {
            echo "❌ UserManager authentication failed!\n";
        }
        
    } else {
        echo "❌ User 'ask' not found in database\n";
        
        // Show all users
        echo "\nAll users in database:\n";
        $stmt = $db->executeQuery("SELECT username, email, full_name FROM users");
        $users = $stmt->fetchAll();
        
        foreach ($users as $u) {
            echo "  - {$u['username']} ({$u['email']}) - {$u['full_name']}\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>

<?php
require_once 'config/config.php';
require_once 'includes/functions.php';

try {
    echo "Testing UserManager...\n";
    
    $userManager = new UserManager();
    echo "UserManager created successfully\n";
    
    $users = $userManager->getAllUsers();
    echo "Users count: " . count($users) . "\n";
    
    if (!empty($users)) {
        echo "First user data: ";
        var_dump($users[0]);
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>

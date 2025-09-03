<?php
/**
 * EV Mobile Station - Database Setup Script
 * This script sets up the database and can be used to update existing schemas
 */

require_once 'config/database.php';

echo "<h1>EV Mobile Station - Database Setup</h1>";

try {
    $database = Database::getInstance();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Failed to connect to database");
    }
    
    echo "<p style='color: green;'>✓ Database connection successful</p>";
    
    // Check if technicians table exists and has experience_years column
    $check_column_sql = "SHOW COLUMNS FROM technicians LIKE 'experience_years'";
    $check_stmt = $db->prepare($check_column_sql);
    $check_stmt->execute();
    $column_exists = $check_stmt->rowCount() > 0;
    
    if (!$column_exists) {
        echo "<p>Adding experience_years column to technicians table...</p>";
        $alter_sql = "ALTER TABLE technicians ADD COLUMN experience_years INT DEFAULT 0 AFTER specialization";
        $alter_stmt = $db->prepare($alter_sql);
        $alter_stmt->execute();
        echo "<p style='color: green;'>✓ experience_years column added successfully</p>";
    } else {
        echo "<p style='color: blue;'>ℹ experience_years column already exists</p>";
    }
    
    // Check if all required tables exist
    $required_tables = [
        'users',
        'service_vehicles', 
        'technicians',
        'service_requests',
        'payments',
        'service_history',
        'admin_users'
    ];
    
    foreach ($required_tables as $table) {
        $check_table_sql = "SHOW TABLES LIKE '$table'";
        $check_table_stmt = $db->prepare($check_table_sql);
        $check_table_stmt->execute();
        
        if ($check_table_stmt->rowCount() > 0) {
            echo "<p style='color: green;'>✓ Table '$table' exists</p>";
        } else {
            echo "<p style='color: red;'>✗ Table '$table' missing</p>";
        }
    }
    
    // Check if sample data exists
    $check_technicians_sql = "SELECT COUNT(*) as count FROM technicians";
    $check_tech_stmt = $db->prepare($check_technicians_sql);
    $check_tech_stmt->execute();
    $tech_count = $check_tech_stmt->fetch()['count'];
    
    if ($tech_count == 0) {
        echo "<p>No technicians found. Would you like to insert sample data?</p>";
        echo "<p><a href='?insert_sample=1'>Insert Sample Data</a></p>";
    } else {
        echo "<p style='color: green;'>✓ Found $tech_count technicians in database</p>";
    }
    
    // Handle sample data insertion
    if (isset($_GET['insert_sample']) && $_GET['insert_sample'] == '1') {
        echo "<h2>Inserting Sample Data...</h2>";
        
        // Insert sample technicians if none exist
        if ($tech_count == 0) {
            $insert_tech_sql = "INSERT INTO technicians (full_name, phone, specialization, experience_years, status) VALUES 
                               (?, ?, ?, ?, ?)";
            $insert_tech_stmt = $db->prepare($insert_tech_sql);
            
            $sample_technicians = [
                ['John Smith', '+1-555-0101', 'electrical', 5, 'available'],
                ['Mike Johnson', '+1-555-0102', 'mechanical', 7, 'available'],
                ['Sarah Wilson', '+1-555-0103', 'both', 4, 'available'],
                ['Robert Garcia', '+1-555-0104', 'electrical', 6, 'busy'],
                ['Lisa Chen', '+1-555-0105', 'mechanical', 3, 'offline']
            ];
            
            foreach ($sample_technicians as $tech) {
                $insert_tech_stmt->execute($tech);
            }
            echo "<p style='color: green;'>✓ Sample technicians inserted successfully</p>";
        }
        
        // Check if service vehicles exist
        $check_vehicles_sql = "SELECT COUNT(*) as count FROM service_vehicles";
        $check_veh_stmt = $db->prepare($check_vehicles_sql);
        $check_veh_stmt->execute();
        $veh_count = $check_veh_stmt->fetch()['count'];
        
        if ($veh_count == 0) {
            $insert_veh_sql = "INSERT INTO service_vehicles (vehicle_number, vehicle_type, capacity, current_location_lat, current_location_lng, status) VALUES 
                               (?, ?, ?, ?, ?, ?)";
            $insert_veh_stmt = $db->prepare($insert_veh_sql);
            
            $sample_vehicles = [
                ['EV-CHG-001', 'charging', '50kW Fast Charger', 40.7128, -74.0060, 'available'],
                ['EV-MECH-001', 'mechanical', 'Full Tool Kit', 40.7589, -73.9851, 'available'],
                ['EV-HYB-001', 'hybrid', '30kW Charger + Tools', 40.7505, -73.9934, 'available']
            ];
            
            foreach ($sample_vehicles as $veh) {
                $insert_veh_stmt->execute($veh);
            }
            echo "<p style='color: green;'>✓ Sample service vehicles inserted successfully</p>";
        }
        
        // Check if admin user exists
        $check_admin_sql = "SELECT COUNT(*) as count FROM admin_users";
        $check_admin_stmt = $db->prepare($check_admin_sql);
        $check_admin_stmt->execute();
        $admin_count = $check_admin_stmt->fetch()['count'];
        
        if ($admin_count == 0) {
            $insert_admin_sql = "INSERT INTO admin_users (username, email, password, full_name, role) VALUES (?, ?, ?, ?, ?)";
            $insert_admin_stmt = $db->prepare($insert_admin_sql);
            
            $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
            $insert_admin_stmt->execute(['admin', 'admin@evstation.com', $admin_password, 'System Administrator', 'super_admin']);
            echo "<p style='color: green;'>✓ Admin user created successfully (username: admin, password: admin123)</p>";
        }
        
        echo "<p style='color: green;'>✓ Sample data insertion completed!</p>";
        echo "<p><a href='admin/technicians.php'>Go to Technician Management</a></p>";
    }
    
    echo "<h2>Database Status</h2>";
    echo "<p>Database setup completed successfully!</p>";
    echo "<p><a href='admin/technicians.php'>Go to Technician Management</a> | <a href='admin/dashboard.php'>Go to Admin Dashboard</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
    echo "<p>Please check your database configuration in config/database.php</p>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    background: #f5f5f5;
}

h1, h2 {
    color: #333;
}

p {
    margin: 10px 0;
    padding: 10px;
    background: white;
    border-radius: 5px;
    border-left: 4px solid #ddd;
}

a {
    color: #007bff;
    text-decoration: none;
}

a:hover {
    text-decoration: underline;
}
</style>

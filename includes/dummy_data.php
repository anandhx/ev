<?php
/**
 * Dummy Data Setup
 * EV Mobile Power & Service Station
 */

// require_once 'config/database.php'; // Commented out - no MySQL server available

/**
 * Insert dummy data (dummy implementation)
 */
function insert_dummy_data() {
    // Dummy implementation - no database connection
    // In a real implementation, this would insert data into the database
    error_log("Dummy data insertion simulated - no database connection available");
    return true;
}

/**
 * Get user statistics (dummy implementation)
 */
function get_user_stats($user_id) {
    // Dummy implementation - return hardcoded stats
    return [
        'total_requests' => 8,
        'completed_requests' => 5,
        'in_progress_requests' => 2,
        'total_spent' => 425.00
    ];
}

/**
 * Get recent service requests for user (dummy implementation)
 */
function get_user_requests($user_id, $limit = 10) {
    // Dummy implementation - return hardcoded service requests
    return [
        [
            'id' => 1,
            'request_type' => 'charging',
            'description' => 'Battery completely dead, need emergency charging',
            'urgency_level' => 'emergency',
            'status' => 'completed',
            'total_cost' => 75.00,
            'payment_status' => 'paid',
            'created_at' => '2025-01-15 10:30:00',
            'vehicle_number' => 'EV-CHG-001',
            'technician_name' => 'John Smith'
        ],
        [
            'id' => 2,
            'request_type' => 'mechanical',
            'description' => 'Flat tire, need immediate replacement',
            'urgency_level' => 'high',
            'status' => 'in_progress',
            'total_cost' => 120.00,
            'payment_status' => 'pending',
            'created_at' => '2025-01-20 14:00:00',
            'vehicle_number' => 'EV-MECH-001',
            'technician_name' => 'Mike Johnson'
        ],
        [
            'id' => 3,
            'request_type' => 'both',
            'description' => 'Battery low and minor electrical issue',
            'urgency_level' => 'medium',
            'status' => 'assigned',
            'total_cost' => 95.00,
            'payment_status' => 'pending',
            'created_at' => '2025-01-18 09:00:00',
            'vehicle_number' => 'EV-HYB-001',
            'technician_name' => 'Sarah Wilson'
        ]
    ];
}

/**
 * Get available service vehicles (dummy implementation)
 */
function get_available_vehicles() {
    // Dummy implementation - return hardcoded vehicles
    return [
        [
            'id' => 1,
            'vehicle_number' => 'EV-CHG-001',
            'vehicle_type' => 'charging',
            'capacity' => '50kW Fast Charger',
            'current_location_lat' => 40.7128,
            'current_location_lng' => -74.0060,
            'status' => 'available',
            'technician_name' => 'John Smith'
        ],
        [
            'id' => 2,
            'vehicle_number' => 'EV-MECH-001',
            'vehicle_type' => 'mechanical',
            'capacity' => 'Full Tool Kit',
            'current_location_lat' => 40.7589,
            'current_location_lng' => -73.9851,
            'status' => 'available',
            'technician_name' => 'Mike Johnson'
        ],
        [
            'id' => 3,
            'vehicle_number' => 'EV-HYB-001',
            'vehicle_type' => 'hybrid',
            'capacity' => '30kW Charger + Tools',
            'current_location_lat' => 40.7505,
            'current_location_lng' => -73.9934,
            'status' => 'available',
            'technician_name' => 'Sarah Wilson'
        ],
        [
            'id' => 4,
            'vehicle_number' => 'EV-CHG-002',
            'vehicle_type' => 'charging',
            'capacity' => '100kW Ultra Fast Charger',
            'current_location_lat' => 40.7300,
            'current_location_lng' => -73.9900,
            'status' => 'available',
            'technician_name' => 'David Lee'
        ]
    ];
}

/**
 * Get all service requests (for admin) - dummy implementation
 */
function get_all_service_requests($status = null, $limit = 50) {
    // Dummy implementation - return hardcoded service requests
    $requests = [
        [
            'id' => 1,
            'user_name' => 'John Doe',
            'user_phone' => '+1-555-0201',
            'request_type' => 'charging',
            'description' => 'Battery completely dead, need emergency charging',
            'urgency_level' => 'emergency',
            'status' => 'completed',
            'total_cost' => 75.00,
            'payment_status' => 'paid',
            'created_at' => '2025-01-15 10:30:00',
            'vehicle_number' => 'EV-CHG-001',
            'technician_name' => 'John Smith'
        ],
        [
            'id' => 2,
            'user_name' => 'Jane Smith',
            'user_phone' => '+1-555-0202',
            'request_type' => 'mechanical',
            'description' => 'Flat tire, need immediate replacement',
            'urgency_level' => 'high',
            'status' => 'in_progress',
            'total_cost' => 120.00,
            'payment_status' => 'pending',
            'created_at' => '2025-01-20 14:00:00',
            'vehicle_number' => 'EV-MECH-001',
            'technician_name' => 'Mike Johnson'
        ],
        [
            'id' => 3,
            'user_name' => 'Mike Wilson',
            'user_phone' => '+1-555-0203',
            'request_type' => 'both',
            'description' => 'Battery low and minor electrical issue',
            'urgency_level' => 'medium',
            'status' => 'assigned',
            'total_cost' => 95.00,
            'payment_status' => 'pending',
            'created_at' => '2025-01-18 09:00:00',
            'vehicle_number' => 'EV-HYB-001',
            'technician_name' => 'Sarah Wilson'
        ],
        [
            'id' => 4,
            'user_name' => 'Sarah Jones',
            'user_phone' => '+1-555-0204',
            'request_type' => 'charging',
            'description' => 'Need fast charging, running late for meeting',
            'urgency_level' => 'high',
            'status' => 'completed',
            'total_cost' => 60.00,
            'payment_status' => 'paid',
            'created_at' => '2025-01-12 16:30:00',
            'vehicle_number' => 'EV-CHG-002',
            'technician_name' => 'David Lee'
        ],
        [
            'id' => 5,
            'user_name' => 'David Brown',
            'user_phone' => '+1-555-0205',
            'request_type' => 'mechanical',
            'description' => 'Dashboard warning lights, need diagnostic',
            'urgency_level' => 'low',
            'status' => 'pending',
            'total_cost' => 80.00,
            'payment_status' => 'pending',
            'created_at' => '2025-01-22 11:00:00',
            'vehicle_number' => null,
            'technician_name' => null
        ]
    ];
    
    // Filter by status if specified
    if ($status) {
        $requests = array_filter($requests, function($request) use ($status) {
            return $request['status'] === $status;
        });
    }
    
    // Limit results
    return array_slice($requests, 0, $limit);
}

/**
 * Get dummy users data
 */
function get_dummy_users() {
    return [
        [
            'id' => 1,
            'username' => 'john_doe',
            'email' => 'john@example.com',
            'full_name' => 'John Doe',
            'phone' => '+1-555-0201',
            'vehicle_model' => 'Tesla Model 3',
            'vehicle_plate' => 'ABC-123',
            'status' => 'active',
            'membership' => 'premium',
            'created_at' => '2025-01-01 10:00:00'
        ],
        [
            'id' => 2,
            'username' => 'jane_smith',
            'email' => 'jane@example.com',
            'full_name' => 'Jane Smith',
            'phone' => '+1-555-0202',
            'vehicle_model' => 'Nissan Leaf',
            'vehicle_plate' => 'XYZ-789',
            'status' => 'active',
            'membership' => 'standard',
            'created_at' => '2025-01-02 11:30:00'
        ],
        [
            'id' => 3,
            'username' => 'mike_wilson',
            'email' => 'mike@example.com',
            'full_name' => 'Mike Wilson',
            'phone' => '+1-555-0203',
            'vehicle_model' => 'Chevrolet Bolt',
            'vehicle_plate' => 'DEF-456',
            'status' => 'active',
            'membership' => 'premium',
            'created_at' => '2025-01-03 14:15:00'
        ],
        [
            'id' => 4,
            'username' => 'sarah_jones',
            'email' => 'sarah@example.com',
            'full_name' => 'Sarah Jones',
            'phone' => '+1-555-0204',
            'vehicle_model' => 'Ford Mustang Mach-E',
            'vehicle_plate' => 'GHI-789',
            'status' => 'suspended',
            'membership' => 'standard',
            'created_at' => '2025-01-04 09:45:00'
        ],
        [
            'id' => 5,
            'username' => 'david_brown',
            'email' => 'david@example.com',
            'full_name' => 'David Brown',
            'phone' => '+1-555-0205',
            'vehicle_model' => 'Audi e-tron',
            'vehicle_plate' => 'JKL-012',
            'status' => 'active',
            'membership' => 'premium',
            'created_at' => '2025-01-05 16:20:00'
        ]
    ];
}

// Insert dummy data if this file is accessed directly
if (basename($_SERVER['PHP_SELF']) == 'dummy_data.php') {
    if (insert_dummy_data()) {
        echo "Dummy data inserted successfully!";
    } else {
        echo "Error inserting dummy data.";
    }
}
?> 
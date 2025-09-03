<?php
/**
 * EV Mobile Station - Core Functions
 * Provides backend functionality for all modules
 */

require_once __DIR__ . '/../config/database.php';

// User Management Functions
class UserManager {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function createUser($data) {
        try {
            $sql = "INSERT INTO users (username, email, password, full_name, phone, vehicle_model, vehicle_plate) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
            
            $stmt = $this->db->executeQuery($sql, [
                $data['username'],
                $data['email'],
                $hashed_password,
                $data['full_name'],
                $data['phone'],
                $data['vehicle_model'],
                $data['vehicle_plate']
            ]);
            
            $userId = $this->db->getLastInsertId();

            // If vehicle details provided, also insert into user_vehicles as primary
            if (!empty($data['vehicle_model']) || !empty($data['vehicle_plate'])) {
                try {
                    $makeModel = $data['vehicle_model'] ?: 'EV';
                    $parts = explode(' ', $makeModel, 2);
                    $make = $parts[0] ?? 'EV';
                    $model = $parts[1] ?? $makeModel;
                    $plate = $data['vehicle_plate'] ?: null;
                    $sql2 = "INSERT INTO user_vehicles (user_id, make, model, plate, is_primary) VALUES (?, ?, ?, ?, 1)";
                    $this->db->executeQuery($sql2, [$userId, $make, $model, $plate]);
                } catch (Exception $e) {
                    error_log('Failed to add primary vehicle on signup: ' . $e->getMessage());
                }
            }

            return $userId;
        } catch (Exception $e) {
            error_log("User creation error: " . $e->getMessage());
            return false;
        }
    }
    
    public function authenticateUser($username, $password) {
        try {
            $sql = "SELECT * FROM users WHERE username = ? OR email = ?";
            $stmt = $this->db->executeQuery($sql, [$username, $username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                unset($user['password']); // Don't return password
                return $user;
            }
            return false;
        } catch (Exception $e) {
            error_log("User authentication error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getUserById($id) {
        try {
            $sql = "SELECT id, username, email, full_name, phone, vehicle_model, vehicle_plate, created_at FROM users WHERE id = ?";
            $stmt = $this->db->executeQuery($sql, [$id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Get user error: " . $e->getMessage());
            return false;
        }
    }
    
    public function updateUser($id, $data) {
        try {
            $sql = "UPDATE users SET full_name = ?, phone = ?, vehicle_model = ?, vehicle_plate = ? WHERE id = ?";
            $stmt = $this->db->executeQuery($sql, [
                $data['full_name'],
                $data['phone'],
                $data['vehicle_model'],
                $data['vehicle_plate'],
                $id
            ]);
            return true;
        } catch (Exception $e) {
            error_log("User update error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getAllUsers() {
        try {
            $sql = "SELECT id, username, email, full_name, phone, vehicle_model, vehicle_plate, created_at FROM users ORDER BY created_at DESC";
            $stmt = $this->db->executeQuery($sql);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get all users error: " . $e->getMessage());
            return [];
        }
    }
}

// Service Request Management
class ServiceRequestManager {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function createRequest($data) {
        try {
            $hasUserVehicle = array_key_exists('user_vehicle_id', $data) && !empty($data['user_vehicle_id']);
            if ($hasUserVehicle) {
                $sql = "INSERT INTO service_requests (user_id, user_vehicle_id, request_type, vehicle_location_lat, vehicle_location_lng, 
                        description, urgency_level) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $params = [
                    $data['user_id'],
                    $data['user_vehicle_id'],
                    $data['request_type'],
                    $data['vehicle_location_lat'],
                    $data['vehicle_location_lng'],
                    $data['description'],
                    $data['urgency_level']
                ];
            } else {
                $sql = "INSERT INTO service_requests (user_id, request_type, vehicle_location_lat, vehicle_location_lng, 
                        description, urgency_level) VALUES (?, ?, ?, ?, ?, ?)";
                $params = [
                    $data['user_id'],
                    $data['request_type'],
                    $data['vehicle_location_lat'],
                    $data['vehicle_location_lng'],
                    $data['description'],
                    $data['urgency_level']
                ];
            }
            
            $stmt = $this->db->executeQuery($sql, $params);
            
            $request_id = $this->db->getLastInsertId();
            
            // Log the request creation
            $this->logServiceAction($request_id, 'request_created', 'Service request created', 'user');
            
            return $request_id;
        } catch (Exception $e) {
            error_log("Service request creation error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getRequestsByUser($user_id) {
        try {
            $sql = "SELECT sr.*, sv.vehicle_number, t.full_name as technician_name 
                    FROM service_requests sr 
                    LEFT JOIN service_vehicles sv ON sr.assigned_vehicle_id = sv.id 
                    LEFT JOIN technicians t ON sr.assigned_technician_id = t.id 
                    WHERE sr.user_id = ? 
                    ORDER BY sr.created_at DESC";
            
            $stmt = $this->db->executeQuery($sql, [$user_id]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get user requests error: " . $e->getMessage());
            return [];
        }
    }
    
    public function getRequestById($id) {
        try {
            $sql = "SELECT sr.*, u.full_name as user_name, u.phone as user_phone, 
                    sv.vehicle_number, t.full_name as technician_name 
                    FROM service_requests sr 
                    JOIN users u ON sr.user_id = u.id 
                    LEFT JOIN service_vehicles sv ON sr.assigned_vehicle_id = sv.id 
                    LEFT JOIN technicians t ON sr.assigned_technician_id = t.id 
                    WHERE sr.id = ?";
            
            $stmt = $this->db->executeQuery($sql, [$id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Get request error: " . $e->getMessage());
            return false;
        }
    }
    
    public function updateRequestStatus($id, $status, $admin_id = null) {
        try {
            $sql = "UPDATE service_requests SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $stmt = $this->db->executeQuery($sql, [$status, $id]);
            
            $action = 'status_updated';
            $description = "Status updated to: $status";
            $performed_by = $admin_id ? 'admin' : 'system';
            
            $this->logServiceAction($id, $action, $description, $performed_by);
            
            return true;
        } catch (Exception $e) {
            error_log("Request status update error: " . $e->getMessage());
            return false;
        }
    }
    
    public function assignRequest($request_id, $vehicle_id, $technician_id) {
        try {
            $sql = "UPDATE service_requests SET assigned_vehicle_id = ?, assigned_technician_id = ?, 
                    status = 'assigned', updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            
            $stmt = $this->db->executeQuery($sql, [$vehicle_id, $technician_id, $request_id]);
            
            $this->logServiceAction($request_id, 'request_assigned', 
                "Assigned to vehicle $vehicle_id and technician $technician_id", 'admin');
            
            return true;
        } catch (Exception $e) {
            error_log("Request assignment error: " . $e->getMessage());
            return false;
        }
    }
    
    private function logServiceAction($request_id, $action, $description, $performed_by) {
        try {
            $sql = "INSERT INTO service_history (service_request_id, action, description, performed_by) 
                    VALUES (?, ?, ?, ?)";
            $this->db->executeQuery($sql, [$request_id, $action, $description, $performed_by]);
        } catch (Exception $e) {
            error_log("Service action logging error: " . $e->getMessage());
        }
    }
}

// Vehicle Management
class VehicleManager {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function getAvailableVehicles($type = null) {
        try {
            $sql = "SELECT * FROM service_vehicles WHERE status = 'available'";
            $params = [];
            
            if ($type) {
                $sql .= " AND vehicle_type = ?";
                $params[] = $type;
            }
            
            $stmt = $this->db->executeQuery($sql, $params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get available vehicles error: " . $e->getMessage());
            return [];
        }
    }
    
    public function updateVehicleLocation($vehicle_id, $lat, $lng) {
        try {
            $sql = "UPDATE service_vehicles SET current_location_lat = ?, current_location_lng = ? WHERE id = ?";
            $stmt = $this->db->executeQuery($sql, [$lat, $lng, $vehicle_id]);
            return true;
        } catch (Exception $e) {
            error_log("Vehicle location update error: " . $e->getMessage());
            return false;
        }
    }
    
    public function updateVehicleStatus($vehicle_id, $status) {
        try {
            $sql = "UPDATE service_vehicles SET status = ? WHERE id = ?";
            $stmt = $this->db->executeQuery($sql, [$status, $vehicle_id]);
            return true;
        } catch (Exception $e) {
            error_log("Vehicle status update error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getAllVehicles() {
        try {
            $sql = "SELECT * FROM service_vehicles ORDER BY created_at DESC";
            $stmt = $this->db->executeQuery($sql);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get all vehicles error: " . $e->getMessage());
            return [];
        }
    }
    
    public function getVehicleById($id) {
        try {
            $sql = "SELECT * FROM service_vehicles WHERE id = ?";
            $stmt = $this->db->executeQuery($sql, [$id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Get vehicle by ID error: " . $e->getMessage());
            return false;
        }
    }
    
    public function createVehicle($data) {
        try {
            $sql = "INSERT INTO service_vehicles (vehicle_number, vehicle_type, capacity, current_location_lat, current_location_lng, status) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->executeQuery($sql, [
                $data['vehicle_number'],
                $data['vehicle_type'],
                $data['capacity'],
                $data['current_location_lat'] ?? null,
                $data['current_location_lng'] ?? null,
                $data['status'] ?? 'available'
            ]);
            
            return $this->db->getLastInsertId();
        } catch (Exception $e) {
            error_log("Vehicle creation error: " . $e->getMessage());
            return false;
        }
    }
    
    public function updateVehicle($id, $data) {
        try {
            $sql = "UPDATE service_vehicles SET vehicle_number = ?, vehicle_type = ?, capacity = ?, 
                    current_location_lat = ?, current_location_lng = ?, status = ? WHERE id = ?";
            
            $stmt = $this->db->executeQuery($sql, [
                $data['vehicle_number'],
                $data['vehicle_type'],
                $data['capacity'],
                $data['current_location_lat'] ?? null,
                $data['current_location_lng'] ?? null,
                $data['status'],
                $id
            ]);
            
            return true;
        } catch (Exception $e) {
            error_log("Vehicle update error: " . $e->getMessage());
            return false;
        }
    }
    
    public function deleteVehicle($id) {
        try {
            $sql = "DELETE FROM service_vehicles WHERE id = ?";
            $stmt = $this->db->executeQuery($sql, [$id]);
            return true;
        } catch (Exception $e) {
            error_log("Vehicle deletion error: " . $e->getMessage());
            return false;
        }
    }
    
    public function assignTechnician($vehicle_id, $technician_id) {
        try {
            // First, check if the vehicle is available
            $vehicle = $this->getVehicleById($vehicle_id);
            if (!$vehicle || $vehicle['status'] !== 'available') {
                return false;
            }
            
            // Update vehicle status to busy
            $sql = "UPDATE service_vehicles SET status = 'busy' WHERE id = ?";
            $stmt = $this->db->executeQuery($sql, [$vehicle_id]);
            
            // Update technician status to busy and assign vehicle
            $sql2 = "UPDATE technicians SET status = 'busy', assigned_vehicle_id = ? WHERE id = ?";
            $stmt2 = $this->db->executeQuery($sql2, [$vehicle_id, $technician_id]);
            
            return true;
        } catch (Exception $e) {
            error_log("Technician assignment error: " . $e->getMessage());
            return false;
        }
    }
    
    public function unassignTechnician($vehicle_id) {
        try {
            // Get the assigned technician
            $sql = "SELECT assigned_vehicle_id FROM technicians WHERE assigned_vehicle_id = ?";
            $stmt = $this->db->executeQuery($sql, [$vehicle_id]);
            $technician = $stmt->fetch();
            
            if ($technician) {
                // Update technician status to available and remove vehicle assignment
                $sql2 = "UPDATE technicians SET status = 'available', assigned_vehicle_id = NULL WHERE assigned_vehicle_id = ?";
                $stmt2 = $this->db->executeQuery($sql2, [$vehicle_id]);
            }
            
            // Update vehicle status to available
            $sql3 = "UPDATE service_vehicles SET status = 'available' WHERE id = ?";
            $stmt3 = $this->db->executeQuery($sql3, [$vehicle_id]);
            
            return true;
        } catch (Exception $e) {
            error_log("Technician unassignment error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getAssignedTechnician($vehicle_id) {
        try {
            $sql = "SELECT t.* FROM technicians t WHERE t.assigned_vehicle_id = ?";
            $stmt = $this->db->executeQuery($sql, [$vehicle_id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Get assigned technician error: " . $e->getMessage());
            return false;
        }
    }
}

// Technician Management
class TechnicianManager {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function getAllTechnicians() {
        try {
            $sql = "SELECT t.*, 
                    COUNT(sr.id) as total_requests,
                    AVG(CASE WHEN sr.status = 'completed' THEN 5 ELSE NULL END) as rating
                    FROM technicians t 
                    LEFT JOIN service_requests sr ON t.id = sr.assigned_technician_id 
                    GROUP BY t.id 
                    ORDER BY t.created_at DESC";
            
            $stmt = $this->db->executeQuery($sql);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get all technicians error: " . $e->getMessage());
            return [];
        }
    }
    
    public function getAvailableTechnicians($specialization = null) {
        try {
            $sql = "SELECT * FROM technicians WHERE status = 'available'";
            $params = [];
            
            if ($specialization) {
                $sql .= " AND specialization = ?";
                $params[] = $specialization;
            }
            
            $stmt = $this->db->executeQuery($sql, $params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get available technicians error: " . $e->getMessage());
            return [];
        }
    }
    
    public function getTechnicianById($id) {
        try {
            $sql = "SELECT * FROM technicians WHERE id = ?";
            $stmt = $this->db->executeQuery($sql, [$id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Get technician by ID error: " . $e->getMessage());
            return false;
        }
    }
    
    public function createTechnician($data) {
        try {
            $sql = "INSERT INTO technicians (full_name, phone, specialization, experience_years, status) 
                    VALUES (?, ?, ?, ?, ?)";
            
            $stmt = $this->db->executeQuery($sql, [
                $data['full_name'],
                $data['phone'],
                $data['specialization'],
                $data['experience_years'] ?? 0,
                $data['status'] ?? 'available'
            ]);
            
            return $this->db->getLastInsertId();
        } catch (Exception $e) {
            error_log("Create technician error: " . $e->getMessage());
            return false;
        }
    }
    
    public function updateTechnician($id, $data) {
        try {
            $sql = "UPDATE technicians SET full_name = ?, phone = ?, specialization = ?, 
                    experience_years = ?, status = ? WHERE id = ?";
            
            $stmt = $this->db->executeQuery($sql, [
                $data['full_name'],
                $data['phone'],
                $data['specialization'],
                $data['experience_years'] ?? 0,
                $data['status'],
                $id
            ]);
            
            return true;
        } catch (Exception $e) {
            error_log("Update technician error: " . $e->getMessage());
            return false;
        }
    }
    
    public function deleteTechnician($id) {
        try {
            // Check if technician has assigned requests
            $check_sql = "SELECT COUNT(*) as count FROM service_requests WHERE assigned_technician_id = ?";
            $check_stmt = $this->db->executeQuery($check_sql, [$id]);
            $check_result = $check_stmt->fetch();
            
            if ($check_result['count'] > 0) {
                return false; // Cannot delete technician with assigned requests
            }
            
            $sql = "DELETE FROM technicians WHERE id = ?";
            $stmt = $this->db->executeQuery($sql, [$id]);
            return true;
        } catch (Exception $e) {
            error_log("Delete technician error: " . $e->getMessage());
            return false;
        }
    }
    
    public function updateTechnicianStatus($technician_id, $status) {
        try {
            $sql = "UPDATE technicians SET status = ? WHERE id = ?";
            $stmt = $this->db->executeQuery($sql, [$status, $technician_id]);
            return true;
        } catch (Exception $e) {
            error_log("Technician status update error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getTechnicianStats() {
        try {
            $stats = [];
            
            // Total technicians
            $stmt = $this->db->executeQuery("SELECT COUNT(*) as count FROM technicians");
            $stats['total'] = $stmt->fetch()['count'];
            
            // Available technicians
            $stmt = $this->db->executeQuery("SELECT COUNT(*) as count FROM technicians WHERE status = 'available'");
            $stats['available'] = $stmt->fetch()['count'];
            
            // Busy technicians
            $stmt = $this->db->executeQuery("SELECT COUNT(*) as count FROM technicians WHERE status = 'busy'");
            $stats['busy'] = $stmt->fetch()['count'];
            
            // Offline technicians
            $stmt = $this->db->executeQuery("SELECT COUNT(*) as count FROM technicians WHERE status = 'offline'");
            $stats['offline'] = $stmt->fetch()['count'];
            
            return $stats;
        } catch (Exception $e) {
            error_log("Get technician stats error: " . $e->getMessage());
            return [];
        }
    }
}

// Payment Management
class PaymentManager {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function createPayment($data) {
        try {
            $status = $data['status'] ?? (in_array($data['payment_method'], ['cash','cash_on_delivery','cash_on_payment']) ? 'pending' : 'completed');
            $txn = $data['transaction_id'] ?? UtilityFunctions::generateTransactionId();

            $sql = "INSERT INTO payments (service_request_id, amount, payment_method, transaction_id, status) 
                    VALUES (?, ?, ?, ?, ?)";
            
            $stmt = $this->db->executeQuery($sql, [
                $data['service_request_id'],
                $data['amount'],
                $data['payment_method'],
                $txn,
                $status
            ]);
            
            $payment_id = $this->db->getLastInsertId();
            
            // Update service request payment status
            $this->updateRequestPaymentStatus($data['service_request_id'], $status === 'completed' ? 'paid' : 'pending');
            
            return $payment_id;
        } catch (Exception $e) {
            error_log("Payment creation error: " . $e->getMessage());
            return false;
        }
    }
    
    public function updateRequestPaymentStatus($request_id, $status) {
        try {
            $sql = "UPDATE service_requests SET payment_status = ? WHERE id = ?";
            $stmt = $this->db->executeQuery($sql, [$status, $request_id]);
            return true;
        } catch (Exception $e) {
            error_log("Payment status update error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getPaymentByRequestId($request_id) {
        try {
            $sql = "SELECT * FROM payments WHERE service_request_id = ? ORDER BY created_at DESC LIMIT 1";
            $stmt = $this->db->executeQuery($sql, [$request_id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Get payment error: " . $e->getMessage());
            return false;
        }
    }
}

// Spare Parts Management
class SparePartsManager {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // User creates a spare part request
    public function createRequest(array $data) {
        try {
            $sql = "INSERT INTO spare_part_requests (user_id, vehicle_make, vehicle_model, part_name, part_description, quantity) VALUES (?, ?, ?, ?, ?, ?)";
            $this->db->executeQuery($sql, [
                $data['user_id'],
                $data['vehicle_make'] ?? null,
                $data['vehicle_model'] ?? null,
                $data['part_name'],
                $data['part_description'] ?? null,
                max(1, (int)($data['quantity'] ?? 1))
            ]);
            return $this->db->getLastInsertId();
        } catch (Exception $e) { return false; }
    }

    // Admin posts quote/availability
    public function adminQuote($request_id, array $quote) {
        try {
            $sql = "UPDATE spare_part_requests SET status = 'quoted', admin_part_code = ?, admin_available = ?, admin_price = ?, admin_note = ? WHERE id = ?";
            $this->db->executeQuery($sql, [
                $quote['admin_part_code'] ?? null,
                isset($quote['admin_available']) ? (int)$quote['admin_available'] : null,
                $quote['admin_price'] ?? null,
                $quote['admin_note'] ?? null,
                $request_id
            ]);
            return true;
        } catch (Exception $e) { return false; }
    }

    public function getRequestsByUser($user_id) {
        try {
            $stmt = $this->db->executeQuery("SELECT * FROM spare_part_requests WHERE user_id = ? ORDER BY created_at DESC", [$user_id]);
            return $stmt->fetchAll();
        } catch (Exception $e) { return []; }
    }

    public function getRequestById($id) {
        try {
            $stmt = $this->db->executeQuery("SELECT * FROM spare_part_requests WHERE id = ?", [$id]);
            return $stmt->fetch();
        } catch (Exception $e) { return false; }
    }

    // User places order after quote
    public function createOrder(array $data) {
        try {
            $sql = "INSERT INTO spare_orders (request_id, user_id, total_amount, shipping_name, shipping_phone, shipping_address, shipping_city, shipping_state, shipping_postal, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
            $this->db->executeQuery($sql, [
                $data['request_id'], $data['user_id'], $data['total_amount'],
                $data['shipping_name'], $data['shipping_phone'], $data['shipping_address'],
                $data['shipping_city'], $data['shipping_state'], $data['shipping_postal']
            ]);
            return $this->db->getLastInsertId();
        } catch (Exception $e) { return false; }
    }

    public function getOrdersByUser($user_id) {
        try {
            $stmt = $this->db->executeQuery("SELECT o.*, r.part_name, r.admin_part_code FROM spare_orders o JOIN spare_part_requests r ON o.request_id = r.id WHERE o.user_id = ? ORDER BY o.created_at DESC", [$user_id]);
            return $stmt->fetchAll();
        } catch (Exception $e) { return []; }
    }

    public function updateOrderStatus($order_id, $status) {
        try {
            $stmt = $this->db->executeQuery("UPDATE spare_orders SET status = ? WHERE id = ?", [$status, $order_id]);
            return true;
        } catch (Exception $e) { return false; }
    }
}

// Admin Management
class AdminManager {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function authenticateAdmin($username, $password) {
        try {
            $sql = "SELECT * FROM admin_users WHERE username = ? OR email = ?";
            $stmt = $this->db->executeQuery($sql, [$username, $username]);
            $admin = $stmt->fetch();
            
            if ($admin && password_verify($password, $admin['password'])) {
                unset($admin['password']);
                return $admin;
            }
            return false;
        } catch (Exception $e) {
            error_log("Admin authentication error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getAllRequests($status = null, $limit = 50) {
        try {
            $sql = "SELECT sr.*, u.full_name as user_name, u.phone as user_phone, 
                    sv.vehicle_number, t.full_name as technician_name 
                    FROM service_requests sr 
                    JOIN users u ON sr.user_id = u.id 
                    LEFT JOIN service_vehicles sv ON sr.assigned_vehicle_id = sv.id 
                    LEFT JOIN technicians t ON sr.assigned_technician_id = t.id";
            
            $params = [];
            if ($status) {
                $sql .= " WHERE sr.status = ?";
                $params[] = $status;
            }
            
            $sql .= " ORDER BY sr.created_at DESC LIMIT ?";
            $params[] = $limit;
            
            $stmt = $this->db->executeQuery($sql, $params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get all requests error: " . $e->getMessage());
            return [];
        }
    }
    
    public function getDashboardStats() {
        try {
            $stats = [];
            
            // Total requests
            $stmt = $this->db->executeQuery("SELECT COUNT(*) as count FROM service_requests");
            $stats['total_requests'] = $stmt->fetch()['count'];
            
            // Pending requests
            $stmt = $this->db->executeQuery("SELECT COUNT(*) as count FROM service_requests WHERE status = 'pending'");
            $stats['pending_requests'] = $stmt->fetch()['count'];
            
            // Active requests
            $stmt = $this->db->executeQuery("SELECT COUNT(*) as count FROM service_requests WHERE status IN ('assigned', 'in_progress')");
            $stats['active_requests'] = $stmt->fetch()['count'];
            
            // Available vehicles
            $stmt = $this->db->executeQuery("SELECT COUNT(*) as count FROM service_vehicles WHERE status = 'available'");
            $stats['available_vehicles'] = $stmt->fetch()['count'];
            
            // Available technicians
            $stmt = $this->db->executeQuery("SELECT COUNT(*) as count FROM technicians WHERE status = 'available'");
            $stats['available_technicians'] = $stmt->fetch()['count'];
            
            return $stats;
        } catch (Exception $e) {
            error_log("Dashboard stats error: " . $e->getMessage());
            return [];
        }
    }
}

// Utility Functions
class UtilityFunctions {
    
    public static function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitizeInput'], $data);
        }
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
    
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
    
    public static function generateTransactionId() {
        return 'TXN' . date('YmdHis') . rand(1000, 9999);
    }
    
    public static function calculateDistance($lat1, $lng1, $lat2, $lng2) {
        $earth_radius = 6371; // Earth's radius in kilometers
        
        $lat_diff = deg2rad($lat2 - $lat1);
        $lng_diff = deg2rad($lng2 - $lng1);
        
        $a = sin($lat_diff/2) * sin($lat_diff/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lng_diff/2) * sin($lng_diff/2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        return $earth_radius * $c;
    }
    
    public static function formatCurrency($amount) {
        return '$' . number_format($amount, 2);
    }
    
    public static function sendEmail($toEmail, $subject, $body) {
        $fromEmail = EMAIL_FROM_EMAIL;
        $fromName = EMAIL_FROM_NAME;
        $headers = 'From: ' . $fromName . ' <' . $fromEmail . ">\r\n" .
                   'Reply-To: ' . $fromEmail . ">\r\n" .
                   'MIME-Version: 1.0' . ">\r\n" .
                   'Content-Type: text/plain; charset=UTF-8';
        // Try SMTP first
        $ok = self::sendEmailSmtp($toEmail, $subject, $body, $fromEmail, $fromName);
        if ($ok) { return true; }
        // Fallback to mail()
        return @mail($toEmail, $subject, $body, $headers);
    }

    private static function sendEmailSmtp($toEmail, $subject, $body, $fromEmail, $fromName) {
        $host = EMAIL_SMTP_HOST ?? '';
        $port = (int)(EMAIL_SMTP_PORT ?? 0);
        $useSsl = defined('EMAIL_SMTP_SSL') ? EMAIL_SMTP_SSL : true;
        $username = EMAIL_SMTP_USER ?? '';
        $password = EMAIL_SMTP_PASSWORD ?? '';
        if (empty($host) || empty($port) || empty($username) || empty($password)) {
            return false;
        }

        $remote = ($useSsl ? 'ssl://' : '') . $host . ':' . $port;
        $fp = @stream_socket_client($remote, $errno, $errstr, 15, STREAM_CLIENT_CONNECT);
        if (!$fp) { error_log('SMTP connect failed: ' . $errstr); return false; }
        stream_set_timeout($fp, 15);

        $read = function() use ($fp) { return fgets($fp, 515); };
        $write = function($cmd) use ($fp) { fwrite($fp, $cmd . "\r\n"); };

        $greeting = $read();
        if (strpos($greeting, '220') !== 0) { fclose($fp); return false; }
        $write('EHLO localhost');
        $ehloResp = '';
        for ($i=0; $i<10; $i++) { $line = $read(); $ehloResp .= $line; if (strpos($line, '250 ') === 0) break; }
        $write('AUTH LOGIN');
        $resp = $read();
        if (strpos($resp, '334') !== 0) { fclose($fp); return false; }
        $write(base64_encode($username));
        if (strpos($read(), '334') !== 0) { fclose($fp); return false; }
        $write(base64_encode($password));
        if (strpos($read(), '235') !== 0) { fclose($fp); return false; }

        // Gmail requires MAIL FROM to match authenticated user
        $mailFrom = $username;
        $write('MAIL FROM:<' . $mailFrom . '>');
        if (strpos($read(), '250') !== 0) { fclose($fp); return false; }
        $write('RCPT TO:<' . $toEmail . '>');
        if (strpos($read(), '250') !== 0 && strpos($read(), '251') !== 0) { fclose($fp); return false; }
        $write('DATA');
        if (strpos($read(), '354') !== 0) { fclose($fp); return false; }

        $headers = '';
        $headers .= 'From: ' . $fromName . ' <' . $mailFrom . ">\r\n";
        $headers .= 'To: <' . $toEmail . ">\r\n";
        $headers .= 'Subject: ' . self::encodeHeader($subject) . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $headers .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $data = $headers . $body . "\r\n.";
        $write($data);
        if (strpos($read(), '250') !== 0) { fclose($fp); return false; }
        $write('QUIT');
        fclose($fp);
        return true;
    }

    private static function encodeHeader($str) {
        // Simple UTF-8 header encoding
        if (preg_match('/[^\x20-\x7E]/', $str)) {
            return '=?UTF-8?B?' . base64_encode($str) . '?=';
        }
        return $str;
    }
    
    public static function formatDateTime($datetime) {
        return date('M j, Y g:i A', strtotime($datetime));
    }
    
    public static function getUrgencyColor($urgency) {
        switch ($urgency) {
            case 'emergency': return 'danger';
            case 'high': return 'warning';
            case 'medium': return 'info';
            case 'low': return 'success';
            default: return 'secondary';
        }
    }
    
    public static function getStatusColor($status) {
        switch ($status) {
            case 'pending': return 'warning';
            case 'assigned': return 'info';
            case 'in_progress': return 'primary';
            case 'completed': return 'success';
            case 'cancelled': return 'danger';
            default: return 'secondary';
        }
    }
}

// User Vehicle Management
class UserVehicleManager {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getVehiclesByUser($user_id) {
        try {
            $stmt = $this->db->executeQuery("SELECT * FROM user_vehicles WHERE user_id = ? ORDER BY is_primary DESC, created_at DESC", [$user_id]);
            return $stmt->fetchAll();
        } catch (Exception $e) { return []; }
    }

    public function createVehicle($user_id, $data) {
        try {
            if (!empty($data['is_primary'])) {
                $this->db->executeQuery("UPDATE user_vehicles SET is_primary = 0 WHERE user_id = ?", [$user_id]);
            }
            $sql = "INSERT INTO user_vehicles (user_id, make, model, plate, vin, color, is_primary) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $this->db->executeQuery($sql, [
                $user_id,
                $data['make'],
                $data['model'],
                $data['plate'] ?? null,
                $data['vin'] ?? null,
                $data['color'] ?? null,
                !empty($data['is_primary']) ? 1 : 0
            ]);
            return $this->db->getLastInsertId();
        } catch (Exception $e) { return false; }
    }

    public function updateVehicle($user_id, $vehicle_id, $data) {
        try {
            if (!empty($data['is_primary'])) {
                $this->db->executeQuery("UPDATE user_vehicles SET is_primary = 0 WHERE user_id = ?", [$user_id]);
            }
            $sql = "UPDATE user_vehicles SET make = ?, model = ?, plate = ?, vin = ?, color = ?, is_primary = ? WHERE id = ? AND user_id = ?";
            $this->db->executeQuery($sql, [
                $data['make'],
                $data['model'],
                $data['plate'] ?? null,
                $data['vin'] ?? null,
                $data['color'] ?? null,
                !empty($data['is_primary']) ? 1 : 0,
                $vehicle_id,
                $user_id
            ]);
            return true;
        } catch (Exception $e) { return false; }
    }

    public function deleteVehicle($user_id, $vehicle_id) {
        try {
            $this->db->executeQuery("DELETE FROM user_vehicles WHERE id = ? AND user_id = ?", [$vehicle_id, $user_id]);
            return true;
        } catch (Exception $e) { return false; }
    }

    public function setPrimary($user_id, $vehicle_id) {
        try {
            $this->db->executeQuery("UPDATE user_vehicles SET is_primary = 0 WHERE user_id = ?", [$user_id]);
            $this->db->executeQuery("UPDATE user_vehicles SET is_primary = 1 WHERE id = ? AND user_id = ?", [$vehicle_id, $user_id]);
            return true;
        } catch (Exception $e) { return false; }
    }
}

// Session Management
class SessionManager {
    
    public static function startSession() {
        // Session is already started by config.php, just return true
        return true;
    }
    
    public static function setUserSession($user_data) {
        self::startSession();
        $_SESSION['user_id'] = $user_data['id'];
        $_SESSION['username'] = $user_data['username'];
        $_SESSION['full_name'] = $user_data['full_name'];
        $_SESSION['user_type'] = 'user';
        $_SESSION['logged_in'] = true;
    }
    
    public static function setAdminSession($admin_data) {
        self::startSession();
        $_SESSION['admin_id'] = $admin_data['id'];
        $_SESSION['admin_username'] = $admin_data['username'];
        $_SESSION['admin_full_name'] = $admin_data['full_name'];
        $_SESSION['admin_role'] = $admin_data['role'];
        $_SESSION['user_type'] = 'admin';
        $_SESSION['logged_in'] = true;
    }
    
    public static function isLoggedIn() {
        self::startSession();
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    public static function isAdmin() {
        self::startSession();
        return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
    }
    
    public static function getCurrentUserId() {
        self::startSession();
        return $_SESSION['user_id'] ?? null;
    }
    
    public static function getCurrentAdminId() {
        self::startSession();
        return $_SESSION['admin_id'] ?? null;
    }
    
    public static function logout() {
        self::startSession();
        session_destroy();
        session_unset();
    }
    
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header('Location: login.php');
            exit();
        }
    }
    
    public static function requireAdmin() {
        if (!self::isAdmin()) {
            header('Location: ../login.php');
            exit();
        }
    }
}

// Session is now initialized in config.php
?> 
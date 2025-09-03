<?php
/**
 * EV Mobile Station - API Endpoint
 * Handles AJAX requests from the frontend
 */

require_once '../config/config.php';
require_once '../includes/functions.php';

// Set CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get the action from the request
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Verify CSRF token for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    sendErrorResponse('Invalid CSRF token', 403);
}

try {
    switch ($action) {
        case 'login':
            handleLogin();
            break;
            
        case 'signup':
            handleSignup();
            break;
            
        case 'create_service_request':
            handleCreateServiceRequest();
            break;
            
        case 'get_service_requests':
            handleGetServiceRequests();
            break;
            
        case 'update_service_request':
            handleUpdateServiceRequest();
            break;
            
        case 'get_available_vehicles':
            handleGetAvailableVehicles();
            break;
            
        case 'get_available_technicians':
            handleGetAvailableTechnicians();
            break;
            
        case 'create_payment':
            handleCreatePayment();
            break;
            
        case 'get_user_profile':
            handleGetUserProfile();
            break;
            
        case 'update_user_profile':
            handleUpdateUserProfile();
            break;
            
        case 'admin_login':
            handleAdminLogin();
            break;
            
        case 'admin_get_dashboard_stats':
            handleAdminGetDashboardStats();
            break;
            
        case 'admin_get_all_requests':
            handleAdminGetAllRequests();
            break;
            
        case 'admin_assign_request':
            handleAdminAssignRequest();
            break;
            
        case 'admin_get_technicians':
            handleAdminGetTechnicians();
            break;
            
        case 'admin_add_technician':
            handleAdminAddTechnician();
            break;
            
        case 'admin_update_technician':
            handleAdminUpdateTechnician();
            break;
            
        case 'admin_delete_technician':
            handleAdminDeleteTechnician();
            break;
            
        case 'get_vehicle':
            handleGetVehicle();
            break;
            
        default:
            sendErrorResponse('Invalid action', 400);
    }
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    sendErrorResponse('Internal server error', 500);
}

// API Handler Functions

function handleLogin() {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        sendErrorResponse('Username and password are required');
    }
    
    $userManager = new UserManager();
    $adminManager = new AdminManager();
    
    // Try user login first
    $user = $userManager->authenticateUser($username, $password);
    if ($user) {
        // Simple session for API
        session_start();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_type'] = 'user';
        sendSuccessResponse($user, 'User login successful');
    }
    
    // Try admin login
    $admin = $adminManager->authenticateAdmin($username, $password);
    if ($admin) {
        // Simple session for API
        session_start();
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['username'] = $admin['username'];
        $_SESSION['user_type'] = 'admin';
        sendSuccessResponse($admin, 'Admin login successful');
    }
    
    sendErrorResponse('Invalid credentials');
}

function handleSignup() {
    $data = [
        'username' => $_POST['username'] ?? '',
        'email' => $_POST['email'] ?? '',
        'password' => $_POST['password'] ?? '',
        'full_name' => $_POST['full_name'] ?? '',
        'phone' => $_POST['phone'] ?? '',
        'vehicle_model' => $_POST['vehicle_model'] ?? '',
        'vehicle_plate' => $_POST['vehicle_plate'] ?? ''
    ];
    
    // Validate required fields
    $required_fields = ['username', 'email', 'password', 'full_name'];
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            sendErrorResponse("Field '$field' is required");
        }
    }

    // Additional format validation
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        sendErrorResponse('Invalid email format');
    }
    if (!preg_match('/^[a-zA-Z0-9_]{3,}$/', $data['username'])) {
        sendErrorResponse('Username must be 3+ chars, letters/numbers/underscore only');
    }
    if (strlen($data['password']) < 8) {
        sendErrorResponse('Password must be at least 8 characters long');
    }
    // Full name: letters and spaces only, min 2 characters
    if (!preg_match('/^[A-Za-z ]{2,}$/', $data['full_name'])) {
        sendErrorResponse('Full name can contain only letters and spaces');
    }
    // Phone: require exactly 10 digits; normalize to digits-only
    $digitsOnlyPhone = preg_replace('/\D/', '', $data['phone'] ?? '');
    if ($digitsOnlyPhone === '' || !preg_match('/^\d{10}$/', $digitsOnlyPhone)) {
        sendErrorResponse('Phone number must be exactly 10 digits');
    }
    $data['phone'] = $digitsOnlyPhone;
    
    // Uniqueness checks
    try {
        $db = Database::getInstance();
        $stmt = $db->executeQuery('SELECT id FROM users WHERE username = ? LIMIT 1', [$data['username']]);
        if ($stmt->fetch()) {
            sendErrorResponse('Username already exists');
        }
        $stmt = $db->executeQuery('SELECT id FROM users WHERE email = ? LIMIT 1', [$data['email']]);
        if ($stmt->fetch()) {
            sendErrorResponse('Email already exists');
        }
    } catch (Exception $e) {
        sendErrorResponse('Validation failed: ' . $e->getMessage());
    }
    
    $userManager = new UserManager();
    $user_id = $userManager->createUser($data);
    
    if ($user_id) {
        sendSuccessResponse(['user_id' => $user_id], 'User created successfully');
    } else {
        sendErrorResponse('Failed to create user');
    }
}

function handleCreateServiceRequest() {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        sendErrorResponse('User not authenticated', 401);
    }
    
    $data = [
        'user_id' => $_SESSION['user_id'],
        'request_type' => $_POST['request_type'] ?? '',
        'vehicle_location_lat' => $_POST['vehicle_location_lat'] ?? '',
        'vehicle_location_lng' => $_POST['vehicle_location_lng'] ?? '',
        'description' => $_POST['description'] ?? '',
        'urgency_level' => $_POST['urgency_level'] ?? 'medium'
    ];
    
    // Validate required fields
    if (empty($data['request_type']) || empty($data['vehicle_location_lat']) || empty($data['vehicle_location_lng'])) {
        sendErrorResponse('Request type and location coordinates are required');
    }
    
    $serviceManager = new ServiceRequestManager();
    $request_id = $serviceManager->createRequest($data);
    
    if ($request_id) {
        sendSuccessResponse(['request_id' => $request_id], 'Service request created successfully');
    } else {
        sendErrorResponse('Failed to create service request');
    }
}

function handleGetServiceRequests() {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        sendErrorResponse('User not authenticated', 401);
    }
    
    $user_id = $_SESSION['user_id'];
    $serviceManager = new ServiceRequestManager();
    $requests = $serviceManager->getRequestsByUser($user_id);
    
    sendSuccessResponse($requests);
}

function handleUpdateServiceRequest() {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        sendErrorResponse('User not authenticated', 401);
    }
    
    $request_id = $_POST['request_id'] ?? '';
    $status = $_POST['status'] ?? '';
    
    if (empty($request_id) || empty($status)) {
        sendErrorResponse('Request ID and status are required');
    }
    
    $serviceManager = new ServiceRequestManager();
    $result = $serviceManager->updateRequestStatus($request_id, $status);
    
    if ($result) {
        sendSuccessResponse(null, 'Service request updated successfully');
    } else {
        sendErrorResponse('Failed to update service request');
    }
}

function handleGetAvailableVehicles() {
    $type = $_GET['type'] ?? null;
    $vehicleManager = new VehicleManager();
    $vehicles = $vehicleManager->getAvailableVehicles($type);
    
    sendSuccessResponse($vehicles);
}

function handleGetAvailableTechnicians() {
    $specialization = $_GET['specialization'] ?? null;
    $technicianManager = new TechnicianManager();
    $technicians = $technicianManager->getAvailableTechnicians($specialization);
    
    sendSuccessResponse($technicians);
}

function handleCreatePayment() {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        sendErrorResponse('User not authenticated', 401);
    }
    
    $data = [
        'service_request_id' => $_POST['service_request_id'] ?? '',
        'amount' => $_POST['amount'] ?? '',
        'payment_method' => $_POST['payment_method'] ?? ''
    ];
    
    if (empty($data['service_request_id']) || empty($data['amount']) || empty($data['payment_method'])) {
        sendErrorResponse('Service request ID, amount, and payment method are required');
    }
    
    $paymentManager = new PaymentManager();
    $payment_id = $paymentManager->createPayment($data);
    
    if ($payment_id) {
        sendSuccessResponse(['payment_id' => $payment_id], 'Payment created successfully');
    } else {
        sendErrorResponse('Failed to create payment');
    }
}

function handleGetUserProfile() {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        sendErrorResponse('User not authenticated', 401);
    }
    
    $user_id = $_SESSION['user_id'];
    $userManager = new UserManager();
    $user = $userManager->getUserById($user_id);
    
    if ($user) {
        sendSuccessResponse($user);
    } else {
        sendErrorResponse('User not found');
    }
}

function handleUpdateUserProfile() {
    if (!SessionManager::isLoggedIn()) {
        sendErrorResponse('User not authenticated', 401);
    }
    
    $user_id = SessionManager::getCurrentUserId();
    $data = [
        'full_name' => $_POST['full_name'] ?? '',
        'phone' => $_POST['phone'] ?? '',
        'vehicle_model' => $_POST['vehicle_model'] ?? '',
        'vehicle_plate' => $_POST['vehicle_plate'] ?? ''
    ];
    
    $userManager = new UserManager();
    $result = $userManager->updateUser($user_id, $data);
    
    if ($result) {
        sendSuccessResponse(null, 'Profile updated successfully');
    } else {
        sendErrorResponse('Failed to update profile');
    }
}

function handleAdminLogin() {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        sendErrorResponse('Username and password are required');
    }
    
    $adminManager = new AdminManager();
    $admin = $adminManager->authenticateAdmin($username, $password);
    
    if ($admin) {
        SessionManager::setAdminSession($admin);
        sendSuccessResponse($admin, 'Admin login successful');
    } else {
        sendErrorResponse('Invalid admin credentials');
    }
}

function handleAdminGetDashboardStats() {
    if (!SessionManager::isAdmin()) {
        sendErrorResponse('Admin access required', 403);
    }
    
    $adminManager = new AdminManager();
    $stats = $adminManager->getDashboardStats();
    
    sendSuccessResponse($stats);
}

function handleAdminGetAllRequests() {
    if (!SessionManager::isAdmin()) {
        sendErrorResponse('Admin access required', 403);
    }
    
    $status = $_GET['status'] ?? null;
    $limit = $_GET['limit'] ?? 50;
    
    $adminManager = new AdminManager();
    $requests = $adminManager->getAllRequests($status, $limit);
    
    sendSuccessResponse($requests);
}

function handleAdminAssignRequest() {
    if (!SessionManager::isAdmin()) {
        sendErrorResponse('Admin access required', 403);
    }
    
    $request_id = $_POST['request_id'] ?? '';
    $vehicle_id = $_POST['vehicle_id'] ?? '';
    $technician_id = $_POST['technician_id'] ?? '';
    
    if (empty($request_id) || empty($vehicle_id) || empty($technician_id)) {
        sendErrorResponse('Request ID, vehicle ID, and technician ID are required');
    }
    
    $serviceManager = new ServiceRequestManager();
    $result = $serviceManager->assignRequest($request_id, $vehicle_id, $technician_id);
    
    if ($result) {
        sendSuccessResponse(null, 'Request assigned successfully');
    } else {
        sendErrorResponse('Failed to assign request');
    }
}

function handleAdminGetTechnicians() {
    if (!SessionManager::isAdmin()) {
        sendErrorResponse('Admin access required', 403);
    }
    
    try {
        $db = Database::getInstance();
        $sql = "SELECT t.*, 
                COUNT(sr.id) as total_requests,
                AVG(CASE WHEN sr.status = 'completed' THEN 5 ELSE NULL END) as rating
                FROM technicians t 
                LEFT JOIN service_requests sr ON t.id = sr.assigned_technician_id 
                GROUP BY t.id 
                ORDER BY t.created_at DESC";
        
        $stmt = $db->executeQuery($sql);
        $technicians = $stmt->fetchAll();
        
        sendSuccessResponse($technicians);
    } catch (Exception $e) {
        sendErrorResponse('Failed to fetch technicians: ' . $e->getMessage());
    }
}

function handleAdminAddTechnician() {
    if (!SessionManager::isAdmin()) {
        sendErrorResponse('Admin access required', 403);
    }
    
    $data = [
        'full_name' => $_POST['full_name'] ?? '',
        'phone' => $_POST['phone'] ?? '',
        'specialization' => $_POST['specialization'] ?? '',
        'experience_years' => (int)($_POST['experience_years'] ?? 0)
    ];
    
    // Validate required fields
    if (empty($data['full_name']) || empty($data['phone']) || empty($data['specialization'])) {
        sendErrorResponse('Name, phone, and specialization are required');
    }
    
    try {
        $db = Database::getInstance();
        $sql = "INSERT INTO technicians (full_name, phone, specialization, experience_years, status) 
                VALUES (?, ?, ?, ?, 'available')";
        $stmt = $db->executeQuery($sql, [
            $data['full_name'],
            $data['phone'],
            $data['specialization'],
            $data['experience_years']
        ]);
        
        if ($stmt) {
            $technician_id = $db->getLastInsertId();
            sendSuccessResponse(['technician_id' => $technician_id], 'Technician added successfully');
        } else {
            sendErrorResponse('Failed to add technician');
        }
    } catch (Exception $e) {
        sendErrorResponse('Failed to add technician: ' . $e->getMessage());
    }
}

function handleAdminUpdateTechnician() {
    if (!SessionManager::isAdmin()) {
        sendErrorResponse('Admin access required', 403);
    }
    
    $technician_id = (int)($_POST['technician_id'] ?? 0);
    $data = [
        'full_name' => $_POST['full_name'] ?? '',
        'phone' => $_POST['phone'] ?? '',
        'specialization' => $_POST['specialization'] ?? '',
        'experience_years' => (int)($_POST['experience_years'] ?? 0),
        'status' => $_POST['status'] ?? ''
    ];
    
    if (empty($technician_id) || empty($data['full_name']) || empty($data['phone']) || empty($data['specialization'])) {
        sendErrorResponse('Technician ID and all fields are required');
    }
    
    try {
        $db = Database::getInstance();
        $sql = "UPDATE technicians SET full_name = ?, phone = ?, specialization = ?, 
                experience_years = ?, status = ? WHERE id = ?";
        $stmt = $db->executeQuery($sql, [
            $data['full_name'],
            $data['phone'],
            $data['specialization'],
            $data['experience_years'],
            $data['status'],
            $technician_id
        ]);
        
        if ($stmt) {
            sendSuccessResponse(null, 'Technician updated successfully');
        } else {
            sendErrorResponse('Failed to update technician');
        }
    } catch (Exception $e) {
        sendErrorResponse('Failed to update technician: ' . $e->getMessage());
    }
}

function handleAdminDeleteTechnician() {
    if (!SessionManager::isAdmin()) {
        sendErrorResponse('Admin access required', 403);
    }
    
    $technician_id = (int)($_POST['technician_id'] ?? 0);
    
    if (empty($technician_id)) {
        sendErrorResponse('Technician ID is required');
    }
    
    try {
        $db = Database::getInstance();
        
        // Check if technician has assigned requests
        $check_sql = "SELECT COUNT(*) as count FROM service_requests WHERE assigned_technician_id = ?";
        $check_stmt = $db->executeQuery($check_sql, [$technician_id]);
        $check_result = $check_stmt->fetch();
        
        if ($check_result['count'] > 0) {
            sendErrorResponse('Cannot delete technician with assigned service requests');
        }
        
        $sql = "DELETE FROM technicians WHERE id = ?";
        $stmt = $db->executeQuery($sql, [$technician_id]);
        
        if ($stmt) {
            sendSuccessResponse(null, 'Technician deleted successfully');
        } else {
            sendErrorResponse('Failed to delete technician');
        }
    } catch (Exception $e) {
        sendErrorResponse('Failed to delete technician: ' . $e->getMessage());
    }
}

function handleGetVehicle() {
    $vehicle_id = (int)($_GET['id'] ?? 0);
    
    if (empty($vehicle_id)) {
        sendErrorResponse('Vehicle ID is required');
    }
    
    try {
        $vehicleManager = new VehicleManager();
        $vehicle = $vehicleManager->getVehicleById($vehicle_id);
        
        if ($vehicle) {
            sendSuccessResponse(['vehicle' => $vehicle], 'Vehicle retrieved successfully');
        } else {
            sendErrorResponse('Vehicle not found');
        }
    } catch (Exception $e) {
        sendErrorResponse('Failed to get vehicle: ' . $e->getMessage());
    }
}
?>

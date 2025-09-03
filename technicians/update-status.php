<?php
session_start();
require_once '../includes/functions.php';

if (!isset($_SESSION['technician_id']) || ($_SESSION['user_type'] ?? '') !== 'technician') {
    header('Location: ../login.php');
    exit;
}

$technicianId = (int)$_SESSION['technician_id'];
$requestId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$newStatus = $_GET['status'] ?? '';

$valid = ['assigned','in_progress','completed','cancelled'];
if (!$requestId || !in_array($newStatus, $valid)) {
    die('Invalid parameters');
}

try {
    $db = Database::getInstance();
    // Ensure request belongs to this technician
    $stmt = $db->executeQuery("SELECT status FROM service_requests WHERE id = ? AND assigned_technician_id = ?", [$requestId, $technicianId]);
    $row = $stmt->fetch();
    if (!$row) {
        die('Unauthorized: Not your request');
    }

    // Simple state machine rules
    $current = $row['status'];
    $allowed = (
        ($current === 'assigned' && $newStatus === 'in_progress') ||
        ($current === 'in_progress' && in_array($newStatus, ['completed','cancelled'])) ||
        ($newStatus === 'assigned' && $current === 'assigned')
    );
    if (!$allowed) {
        die('Invalid status transition');
    }

    $db->executeQuery("UPDATE service_requests SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?", [$newStatus, $requestId]);
    // Optional history log
    try {
        $db->executeQuery(
            "INSERT INTO service_request_history (service_request_id, status, notes, created_at) VALUES (?,?,?,CURRENT_TIMESTAMP)",
            [$requestId, $newStatus, 'Technician updated status']
        );
    } catch (Exception $e) {}

    header('Location: requests.php');
    exit;
} catch (Exception $e) {
    die('Database error: '.$e->getMessage());
}

<?php
session_start();
require_once '../includes/functions.php';

if (!isset($_SESSION['technician_id']) || ($_SESSION['user_type'] ?? '') !== 'technician') {
    header('Location: ../login.php');
    exit;
}

$technicianId = (int)$_SESSION['technician_id'];
$toggle = isset($_GET['toggle']) ? (int)$_GET['toggle'] : 0;
$redirect = $_GET['redirect'] ?? '';

try {
    $db = Database::getInstance();
    $newStatus = null;

    if ($toggle === 1) {
        // Fetch current status
        $stmt = $db->executeQuery("SELECT status FROM technicians WHERE id = ?", [$technicianId]);
        $row = $stmt->fetch();
        $current = $row ? ($row['status'] ?? 'available') : 'available';
        $newStatus = ($current === 'available') ? 'offline' : 'available';
        $db->executeQuery("UPDATE technicians SET status = ? WHERE id = ?", [$newStatus, $technicianId]);
    }

    if ($newStatus !== null) {
        $_SESSION['tech_flash'] = 'Availability updated: now ' . strtoupper($newStatus);
    }

    if ($redirect) {
        header('Location: ' . $redirect);
    } elseif (!empty($_SERVER['HTTP_REFERER'])) {
        header('Location: ' . $_SERVER['HTTP_REFERER']);
    } else {
        header('Location: dashboard.php');
    }
    exit;
} catch (Exception $e) {
    $_SESSION['tech_flash'] = 'Failed to update availability: ' . $e->getMessage();
    header('Location: dashboard.php');
    exit;
}

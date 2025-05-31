<?php
session_start();
require_once 'config/config.php';
require_once 'helpers/auth_helper.php';

// Check if user is logged in and is a teacher or admin
if (!isLoggedIn() || ($_SESSION['user_role'] !== 'teacher' && $_SESSION['user_role'] !== 'admin')) {
    header('Location: login.php');
    exit;
}

// Get request ID and action from URL
$requestId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_STRING);

// Validate inputs
if (!$requestId || !in_array($action, ['approve', 'reject'])) {
    header('Location: dashboard.php');
    exit;
}

// Process the request
try {
    $db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get request current status
    $stmt = $db->prepare("SELECT status_id FROM requests WHERE id = ?");
    $stmt->execute([$requestId]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$request) {
        $_SESSION['error'] = "Request not found.";
        header('Location: dashboard.php');
        exit;
    }
    
    // Only allow processing of pending requests
    if ($request['status_id'] != 1) {
        $_SESSION['error'] = "This request has already been processed.";
        header("Location: request_details.php?id=$requestId");
        exit;
    }
    
    // Determine new status based on action
    $newStatusId = ($action === 'approve') ? 2 : 3; // 2 = Approved, 3 = Rejected
    $newStatusName = ($action === 'approve') ? 'Approved' : 'Rejected';
    
    // Begin transaction
    $db->beginTransaction();
    
    // Update request status
    $stmt = $db->prepare("UPDATE requests SET status_id = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$newStatusId, $requestId]);
    
    // Add to request history
    $stmt = $db->prepare("
        INSERT INTO request_history 
        (request_id, user_id, action, new_value, created_at)
        VALUES (?, ?, 'status_change', ?, NOW())
    ");
    $stmt->execute([
        $requestId,
        $_SESSION['user_id'],
        $newStatusName
    ]);
    
    // Commit transaction
    $db->commit();
    
    // Set success message
    $_SESSION['success'] = "Request has been " . strtolower($newStatusName) . " successfully.";
    
} catch (PDOException $e) {
    // Rollback transaction on error
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    $_SESSION['error'] = "Database error: " . $e->getMessage();
}

// Redirect back to request details
header("Location: request_details.php?id=$requestId");
exit;
?>
<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

if (!isLoggedIn()) {
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create':
            handleCreateVisit();
            break;
        case 'update':
            handleUpdateVisit();
            break;
        case 'approve':
            handleApproveVisit();
            break;
        case 'reject':
            handleRejectVisit();
            break;
        default:
            header('Location: ../dashboard.php?error=Invalid action');
            exit;
    }
}

function handleCreateVisit() {
    global $pdo;
    
    // Validate input
    $host_id = $_POST['host_id'] ?? '';
    $visit_date = $_POST['visit_date'] ?? '';
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    $purpose = $_POST['purpose'] ?? '';
    $notes = $_POST['notes'] ?? '';
    
    if (!$host_id || !$visit_date || !$start_time || !$end_time || !$purpose) {
        header('Location: create.php?error=Please fill in all required fields');
        exit;
    }
    
    // Validate date and time
    $visit_datetime = strtotime($visit_date);
    $current_datetime = strtotime(date('Y-m-d'));
    
    if ($visit_datetime < $current_datetime) {
        header('Location: create.php?error=Visit date cannot be in the past');
        exit;
    }
    
    if ($start_time >= $end_time) {
        header('Location: create.php?error=End time must be after start time');
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO visits (visitor_id, host_id, visit_date, start_time, end_time, purpose, notes, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
        ");
        
        $stmt->execute([
            $_SESSION['user_id'],
            $host_id,
            $visit_date,
            $start_time,
            $end_time,
            $purpose,
            $notes
        ]);
        
        header('Location: create.php?success=1');
        exit;
        
    } catch (PDOException $e) {
        error_log("Error creating visit: " . $e->getMessage());
        header('Location: create.php?error=Failed to create visit request');
        exit;
    }
}

function handleUpdateVisit() {
    global $pdo;
    
    if (!hasPermission('employee')) {
        header('Location: ../dashboard.php?error=Unauthorized');
        exit;
    }
    
    $visit_id = $_POST['visit_id'] ?? '';
    $visit_date = $_POST['visit_date'] ?? '';
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    $purpose = $_POST['purpose'] ?? '';
    $notes = $_POST['notes'] ?? '';
    
    if (!$visit_id || !$visit_date || !$start_time || !$end_time || !$purpose) {
        header('Location: edit.php?id=' . $visit_id . '&error=Please fill in all required fields');
        exit;
    }
    
    try {
        // Verify ownership
        $stmt = $pdo->prepare("SELECT visitor_id FROM visits WHERE id = ?");
        $stmt->execute([$visit_id]);
        $visit = $stmt->fetch();
        
        if (!$visit || $visit['visitor_id'] != $_SESSION['user_id']) {
            header('Location: ../dashboard.php?error=Unauthorized');
            exit;
        }
        
        // Update visit
        $stmt = $pdo->prepare("
            UPDATE visits 
            SET visit_date = ?, start_time = ?, end_time = ?, purpose = ?, notes = ?, status = 'pending'
            WHERE id = ? AND visitor_id = ?
        ");
        
        $stmt->execute([
            $visit_date,
            $start_time,
            $end_time,
            $purpose,
            $notes,
            $visit_id,
            $_SESSION['user_id']
        ]);
        
        header('Location: view.php?id=' . $visit_id . '&success=1');
        exit;
        
    } catch (PDOException $e) {
        error_log("Error updating visit: " . $e->getMessage());
        header('Location: edit.php?id=' . $visit_id . '&error=Failed to update visit');
        exit;
    }
}

function handleApproveVisit() {
    global $pdo;
    
    if (!hasPermission('manager')) {
        header('Location: ../dashboard.php?error=Unauthorized');
        exit;
    }
    
    $visit_id = $_POST['visit_id'] ?? '';
    
    try {
        $stmt = $pdo->prepare("
            UPDATE visits 
            SET status = 'approved'
            WHERE id = ? AND host_id = ?
        ");
        
        $stmt->execute([$visit_id, $_SESSION['user_id']]);
        
        header('Location: view.php?id=' . $visit_id . '&success=Visit approved');
        exit;
        
    } catch (PDOException $e) {
        error_log("Error approving visit: " . $e->getMessage());
        header('Location: view.php?id=' . $visit_id . '&error=Failed to approve visit');
        exit;
    }
}

function handleRejectVisit() {
    global $pdo;
    
    if (!hasPermission('manager')) {
        header('Location: ../dashboard.php?error=Unauthorized');
        exit;
    }
    
    $visit_id = $_POST['visit_id'] ?? '';
    $rejection_reason = $_POST['rejection_reason'] ?? '';
    
    try {
        $stmt = $pdo->prepare("
            UPDATE visits 
            SET status = 'rejected', notes = CONCAT(notes, '\nRejection reason: ', ?)
            WHERE id = ? AND host_id = ?
        ");
        
        $stmt->execute([$rejection_reason, $visit_id, $_SESSION['user_id']]);
        
        header('Location: view.php?id=' . $visit_id . '&success=Visit rejected');
        exit;
        
    } catch (PDOException $e) {
        error_log("Error rejecting visit: " . $e->getMessage());
        header('Location: view.php?id=' . $visit_id . '&error=Failed to reject visit');
        exit;
    }
}
?>

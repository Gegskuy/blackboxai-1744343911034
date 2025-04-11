<?php
session_start();

// Log the logout if we have a user_id
if (isset($_SESSION['user_id'])) {
    require_once 'config/database.php';
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO login_logs (user_id, action, ip_address)
            VALUES (?, 'logout', ?)
        ");
        $stmt->execute([$_SESSION['user_id'], $_SERVER['REMOTE_ADDR']]);
    } catch (PDOException $e) {
        error_log("Logout logging error: " . $e->getMessage());
    }
}

// Destroy the session
session_destroy();

// Redirect to login page
header('Location: index.php');
exit;
?>

<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getUserRole() {
    global $pdo;
    if (!isLoggedIn()) return null;

    try {
        $stmt = $pdo->prepare("
            SELECT r.name as role_name 
            FROM users u 
            JOIN roles r ON u.role_id = r.id 
            WHERE u.id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['role_name'] : null;
    } catch (PDOException $e) {
        error_log("Error getting user role: " . $e->getMessage());
        return null;
    }
}

function getUserPosition() {
    global $pdo;
    if (!isLoggedIn()) return null;

    try {
        $stmt = $pdo->prepare("
            SELECT p.name as position_name, p.level 
            FROM users u 
            JOIN positions p ON u.position_id = p.id 
            WHERE u.id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting user position: " . $e->getMessage());
        return null;
    }
}

function hasPermission($requiredRole) {
    $userRole = getUserRole();
    
    // Define role hierarchy
    $roleHierarchy = [
        'admin' => ['admin', 'manager', 'employee', 'security'],
        'manager' => ['manager', 'employee'],
        'security' => ['security'],
        'employee' => ['employee']
    ];
    
    return isset($roleHierarchy[$userRole]) && in_array($requiredRole, $roleHierarchy[$userRole]);
}

function requireRole($role) {
    if (!isLoggedIn()) {
        header('Location: /index.php');
        exit;
    }
    
    if (!hasPermission($role)) {
        header('Location: /dashboard.php?error=unauthorized');
        exit;
    }
}

function getUserDetails() {
    global $pdo;
    if (!isLoggedIn()) return null;

    try {
        $stmt = $pdo->prepare("
            SELECT 
                u.id,
                u.username,
                u.full_name,
                u.email,
                r.name as role,
                p.name as position,
                p.level as position_level
            FROM users u
            JOIN roles r ON u.role_id = r.id
            JOIN positions p ON u.position_id = p.id
            WHERE u.id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting user details: " . $e->getMessage());
        return null;
    }
}
?>

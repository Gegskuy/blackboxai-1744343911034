<?php
session_start();
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Basic validation
    if (empty($username) || empty($password)) {
        header('Location: index.php?error=Please fill in all fields');
        exit;
    }

    try {
        // Get user details including role and position
        $stmt = $pdo->prepare("
            SELECT 
                u.id,
                u.username,
                u.password,
                u.full_name,
                u.email,
                r.name as role_name,
                p.name as position_name,
                p.level as position_level
            FROM users u
            JOIN roles r ON u.role_id = r.id
            JOIN positions p ON u.position_id = p.id
            WHERE u.username = ?
        ");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            // Password is correct, create session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role_name'];
            $_SESSION['position'] = $user['position_name'];
            $_SESSION['position_level'] = $user['position_level'];

            // Log successful login
            $log_stmt = $pdo->prepare("
                INSERT INTO login_logs (user_id, action, ip_address)
                VALUES (?, 'login', ?)
            ");
            $log_stmt->execute([$user['id'], $_SERVER['REMOTE_ADDR']]);

            // Redirect based on role
            switch($user['role_name']) {
                case 'admin':
                    header('Location: dashboard.php?view=all');
                    break;
                case 'manager':
                    header('Location: dashboard.php?view=pending');
                    break;
                case 'security':
                    header('Location: dashboard.php?view=today');
                    break;
                default:
                    header('Location: dashboard.php');
            }
            exit;
        } else {
            // Invalid credentials
            header('Location: index.php?error=Invalid username or password');
            exit;
        }
    } catch (PDOException $e) {
        // Log error (in a production environment, use proper logging)
        error_log("Login error: " . $e->getMessage());
        header('Location: index.php?error=An error occurred. Please try again later.');
        exit;
    }
} else {
    // If someone tries to access this file directly
    header('Location: index.php');
    exit;
}
?>

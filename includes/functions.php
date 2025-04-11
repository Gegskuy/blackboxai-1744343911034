<?php
/**
 * Format a date to a readable string
 */
function formatDate($date) {
    return date('F j, Y', strtotime($date));
}

/**
 * Format time to 12-hour format
 */
function formatTime($time) {
    return date('g:i A', strtotime($time));
}

/**
 * Get status badge class based on visit status
 */
function getStatusBadgeClass($status) {
    return match($status) {
        'pending' => 'bg-yellow-100 text-yellow-800',
        'approved' => 'bg-green-100 text-green-800',
        'rejected' => 'bg-red-100 text-red-800',
        'completed' => 'bg-blue-100 text-blue-800',
        default => 'bg-gray-100 text-gray-800'
    };
}

/**
 * Check if a date is in the past
 */
function isDatePast($date) {
    return strtotime($date) < strtotime(date('Y-m-d'));
}

/**
 * Validate time range
 */
function isValidTimeRange($start_time, $end_time) {
    return strtotime($start_time) < strtotime($end_time);
}

/**
 * Get user's full name or username if full name is not set
 */
function getUserDisplayName($user) {
    return $user['full_name'] ?? $user['username'];
}

/**
 * Format position with level
 */
function formatPosition($position_name, $level) {
    return "$position_name (Level $level)";
}

/**
 * Get visit duration in hours and minutes
 */
function getVisitDuration($start_time, $end_time) {
    $start = strtotime($start_time);
    $end = strtotime($end_time);
    $diff = $end - $start;
    
    $hours = floor($diff / 3600);
    $minutes = floor(($diff % 3600) / 60);
    
    $duration = [];
    if ($hours > 0) {
        $duration[] = $hours . ' hour' . ($hours > 1 ? 's' : '');
    }
    if ($minutes > 0) {
        $duration[] = $minutes . ' minute' . ($minutes > 1 ? 's' : '');
    }
    
    return implode(' ', $duration);
}

/**
 * Clean and sanitize input
 */
function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate a random password
 */
function generateRandomPassword($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $password;
}

/**
 * Check if user has specific position level or higher
 */
function hasPositionLevel($required_level) {
    return isset($_SESSION['position_level']) && $_SESSION['position_level'] <= $required_level;
}

/**
 * Format file size
 */
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, 2) . ' ' . $units[$pow];
}

/**
 * Get time ago string
 */
function getTimeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M j, Y', $time);
    }
}

/**
 * Log system activity
 */
function logActivity($user_id, $action, $details = '') {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (user_id, action, details, ip_address)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $user_id,
            $action,
            $details,
            $_SERVER['REMOTE_ADDR']
        ]);
    } catch (PDOException $e) {
        error_log("Error logging activity: " . $e->getMessage());
    }
}
?>

<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$userDetails = getUserDetails();
$userRole = getUserRole();
$view = $_GET['view'] ?? 'default';

// Get visits based on user role and view
try {
    switch ($view) {
        case 'pending':
            if (hasPermission('manager')) {
                $stmt = $pdo->prepare("
                    SELECT 
                        v.*,
                        visitor.full_name as visitor_name,
                        visitor.email as visitor_email,
                        host.full_name as host_name,
                        visitor_pos.name as visitor_position
                    FROM visits v
                    JOIN users visitor ON v.visitor_id = visitor.id
                    JOIN users host ON v.host_id = host.id
                    JOIN positions visitor_pos ON visitor.position_id = visitor_pos.id
                    WHERE v.host_id = ? AND v.status = 'pending'
                    ORDER BY v.visit_date ASC, v.start_time ASC
                ");
                $stmt->execute([$_SESSION['user_id']]);
            }
            break;
            
        case 'today':
            if (hasPermission('security')) {
                $stmt = $pdo->prepare("
                    SELECT 
                        v.*,
                        visitor.full_name as visitor_name,
                        host.full_name as host_name,
                        visitor_pos.name as visitor_position
                    FROM visits v
                    JOIN users visitor ON v.visitor_id = visitor.id
                    JOIN users host ON v.host_id = host.id
                    JOIN positions visitor_pos ON visitor.position_id = visitor_pos.id
                    WHERE v.visit_date = CURDATE() AND v.status = 'approved'
                    ORDER BY v.start_time ASC
                ");
                $stmt->execute();
            }
            break;
            
        default:
            // For employees, show their visits
            $stmt = $pdo->prepare("
                SELECT 
                    v.*,
                    host.full_name as host_name,
                    host_pos.name as host_position
                FROM visits v
                JOIN users host ON v.host_id = host.id
                JOIN positions host_pos ON host.position_id = host_pos.id
                WHERE v.visitor_id = ?
                ORDER BY v.visit_date DESC, v.start_time DESC
                LIMIT 10
            ");
            $stmt->execute([$_SESSION['user_id']]);
    }
    
    $visits = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Error fetching visits: " . $e->getMessage());
    $visits = [];
}

// Get statistics
try {
    $stats = [];
    
    if (hasPermission('manager')) {
        // Get pending approvals count
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM visits 
            WHERE host_id = ? AND status = 'pending'
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $stats['pending_approvals'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    }
    
    if (hasPermission('security')) {
        // Get today's visits count
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM visits 
            WHERE visit_date = CURDATE() AND status = 'approved'
        ");
        $stmt->execute();
        $stats['today_visits'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    }
    
    // Get user's total visits
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM visits 
        WHERE visitor_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $stats['total_visits'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
} catch (PDOException $e) {
    error_log("Error fetching statistics: " . $e->getMessage());
    $stats = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Visit Pipeline</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <?php include 'includes/navbar.php'; ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <!-- Stats Cards -->
            <?php if (isset($stats['total_visits'])): ?>
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                        <i class="fas fa-calendar-check text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Total Visits</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['total_visits']; ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (isset($stats['pending_approvals'])): ?>
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                        <i class="fas fa-clock text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Pending Approvals</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['pending_approvals']; ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (isset($stats['today_visits'])): ?>
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600">
                        <i class="fas fa-calendar-day text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Today's Visits</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['today_visits']; ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900">
                    <?php
                    switch ($view) {
                        case 'pending':
                            echo '<i class="fas fa-clock text-yellow-500 mr-2"></i>Pending Approvals';
                            break;
                        case 'today':
                            echo '<i class="fas fa-calendar-day text-green-500 mr-2"></i>Today\'s Visits';
                            break;
                        default:
                            echo '<i class="fas fa-calendar-check text-blue-500 mr-2"></i>Recent Visits';
                    }
                    ?>
                </h2>
            </div>

            <?php if (empty($visits)): ?>
            <div class="p-6 text-center text-gray-500">
                <i class="fas fa-calendar-times text-4xl mb-4"></i>
                <p>No visits found</p>
            </div>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                            <?php if ($view === 'pending' || $view === 'today'): ?>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Visitor</th>
                            <?php else: ?>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Host</th>
                            <?php endif; ?>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($visits as $visit): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    <?php echo date('M j, Y', strtotime($visit['visit_date'])); ?>
                                </div>
                                <div class="text-sm text-gray-500">
                                    <?php 
                                    echo date('g:i A', strtotime($visit['start_time'])) . ' - ' . 
                                         date('g:i A', strtotime($visit['end_time']));
                                    ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($view === 'pending' || $view === 'today' ? 
                                        $visit['visitor_name'] : $visit['host_name']); ?>
                                </div>
                                <div class="text-sm text-gray-500">
                                    <?php echo htmlspecialchars($view === 'pending' || $view === 'today' ? 
                                        $visit['visitor_position'] : $visit['host_position']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?php echo match($visit['status']) {
                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                        'approved' => 'bg-green-100 text-green-800',
                                        'rejected' => 'bg-red-100 text-red-800',
                                        'completed' => 'bg-blue-100 text-blue-800',
                                        default => 'bg-gray-100 text-gray-800'
                                    }; ?>">
                                    <?php echo ucfirst($visit['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <a href="visits/view.php?id=<?php echo $visit['id']; ?>" 
                                   class="text-blue-600 hover:text-blue-900">View Details</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

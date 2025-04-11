<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

if (!isLoggedIn()) {
    header('Location: ../index.php');
    exit;
}

$visit_id = $_GET['id'] ?? '';
if (!$visit_id) {
    header('Location: ../dashboard.php');
    exit;
}

try {
    // Get visit details with visitor and host information
    $stmt = $pdo->prepare("
        SELECT 
            v.*,
            visitor.full_name as visitor_name,
            visitor.email as visitor_email,
            host.full_name as host_name,
            host.email as host_email,
            visitor_pos.name as visitor_position,
            host_pos.name as host_position
        FROM visits v
        JOIN users visitor ON v.visitor_id = visitor.id
        JOIN users host ON v.host_id = host.id
        JOIN positions visitor_pos ON visitor.position_id = visitor_pos.id
        JOIN positions host_pos ON host.position_id = host_pos.id
        WHERE v.id = ?
    ");
    $stmt->execute([$visit_id]);
    $visit = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$visit) {
        header('Location: ../dashboard.php?error=Visit not found');
        exit;
    }

    // Check if user has permission to view this visit
    $userRole = getUserRole();
    $isVisitor = $_SESSION['user_id'] == $visit['visitor_id'];
    $isHost = $_SESSION['user_id'] == $visit['host_id'];
    $isSecurity = $userRole === 'security';
    $isAdmin = $userRole === 'admin';

    if (!$isVisitor && !$isHost && !$isSecurity && !$isAdmin) {
        header('Location: ../dashboard.php?error=Unauthorized');
        exit;
    }

} catch (PDOException $e) {
    error_log("Error fetching visit: " . $e->getMessage());
    header('Location: ../dashboard.php?error=Failed to fetch visit details');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visit Details</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <?php include '../includes/navbar.php'; ?>

    <div class="max-w-4xl mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800">
                    <i class="fas fa-calendar-check mr-2 text-blue-600"></i>
                    Visit Details
                </h2>
                <span class="px-4 py-2 rounded-full text-sm font-semibold 
                    <?php echo match($visit['status']) {
                        'pending' => 'bg-yellow-100 text-yellow-800',
                        'approved' => 'bg-green-100 text-green-800',
                        'rejected' => 'bg-red-100 text-red-800',
                        'completed' => 'bg-blue-100 text-blue-800',
                        default => 'bg-gray-100 text-gray-800'
                    }; ?>">
                    <?php echo ucfirst($visit['status']); ?>
                </span>
            </div>

            <?php if(isset($_GET['error'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>

            <?php if(isset($_GET['success'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                    <?php echo htmlspecialchars($_GET['success']); ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="space-y-4">
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Visitor</h3>
                        <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($visit['visitor_name']); ?></p>
                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($visit['visitor_position']); ?></p>
                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($visit['visitor_email']); ?></p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Visit Date</h3>
                        <p class="mt-1 text-sm text-gray-900"><?php echo date('F j, Y', strtotime($visit['visit_date'])); ?></p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Time</h3>
                        <p class="mt-1 text-sm text-gray-900">
                            <?php 
                            echo date('g:i A', strtotime($visit['start_time'])) . ' - ' . 
                                 date('g:i A', strtotime($visit['end_time'])); 
                            ?>
                        </p>
                    </div>
                </div>
                <div class="space-y-4">
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Host</h3>
                        <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($visit['host_name']); ?></p>
                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($visit['host_position']); ?></p>
                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($visit['host_email']); ?></p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Purpose</h3>
                        <p class="mt-1 text-sm text-gray-900"><?php echo nl2br(htmlspecialchars($visit['purpose'])); ?></p>
                    </div>
                    <?php if($visit['notes']): ?>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Notes</h3>
                        <p class="mt-1 text-sm text-gray-900"><?php echo nl2br(htmlspecialchars($visit['notes'])); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="border-t border-gray-200 pt-6">
                <?php if($isHost && $visit['status'] === 'pending'): ?>
                <div class="flex justify-end space-x-3">
                    <button onclick="showRejectModal()" 
                            class="px-4 py-2 border border-red-300 text-red-700 rounded-md hover:bg-red-50">
                        Reject
                    </button>
                    <form action="process.php" method="POST" class="inline">
                        <input type="hidden" name="action" value="approve">
                        <input type="hidden" name="visit_id" value="<?php echo $visit_id; ?>">
                        <button type="submit" 
                                class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                            Approve
                        </button>
                    </form>
                </div>
                <?php endif; ?>

                <?php if($isVisitor && $visit['status'] === 'pending'): ?>
                <div class="flex justify-end">
                    <a href="edit.php?id=<?php echo $visit_id; ?>" 
                       class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        Edit Request
                    </a>
                </div>
                <?php endif; ?>

                <?php if($isSecurity && $visit['status'] === 'approved'): ?>
                <div class="flex justify-end">
                    <form action="process.php" method="POST">
                        <input type="hidden" name="action" value="complete">
                        <input type="hidden" name="visit_id" value="<?php echo $visit_id; ?>">
                        <button type="submit" 
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            Mark as Completed
                        </button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Reject Modal -->
    <div id="rejectModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900">Reject Visit Request</h3>
                <form action="process.php" method="POST" class="mt-4">
                    <input type="hidden" name="action" value="reject">
                    <input type="hidden" name="visit_id" value="<?php echo $visit_id; ?>">
                    
                    <div class="mt-2">
                        <label for="rejection_reason" class="block text-sm font-medium text-gray-700">Reason for Rejection</label>
                        <textarea name="rejection_reason" id="rejection_reason" rows="3" required
                                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"></textarea>
                    </div>

                    <div class="mt-4 flex justify-end space-x-3">
                        <button type="button" onclick="hideRejectModal()"
                                class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                            Reject
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function showRejectModal() {
            document.getElementById('rejectModal').classList.remove('hidden');
        }

        function hideRejectModal() {
            document.getElementById('rejectModal').classList.add('hidden');
        }
    </script>
</body>
</html>

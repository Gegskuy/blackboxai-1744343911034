<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

// Ensure user is logged in and has permission
requireRole('employee');

$visit_id = $_GET['id'] ?? '';
if (!$visit_id) {
    header('Location: ../dashboard.php');
    exit;
}

try {
    // Get visit details
    $stmt = $pdo->prepare("
        SELECT v.*, host.full_name as host_name, host.position_id as host_position_id
        FROM visits v
        JOIN users host ON v.host_id = host.id
        WHERE v.id = ? AND v.visitor_id = ? AND v.status = 'pending'
    ");
    $stmt->execute([$visit_id, $_SESSION['user_id']]);
    $visit = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$visit) {
        header('Location: ../dashboard.php?error=Visit not found or cannot be edited');
        exit;
    }

    // Get list of potential hosts
    $stmt = $pdo->prepare("
        SELECT u.id, u.full_name, p.name as position
        FROM users u
        JOIN positions p ON u.position_id = p.id
        WHERE u.id != ? AND u.role_id IN (
            SELECT id FROM roles WHERE name IN ('manager', 'employee')
        )
        ORDER BY p.level, u.full_name
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $potential_hosts = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error fetching visit details: " . $e->getMessage());
    header('Location: ../dashboard.php?error=Failed to load visit details');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Visit Request</title>
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
            <h2 class="text-2xl font-bold text-gray-800 mb-6">
                <i class="fas fa-edit mr-2 text-blue-600"></i>
                Edit Visit Request
            </h2>

            <?php if(isset($_GET['error'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>

            <form action="process.php" method="POST" class="space-y-6">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="visit_id" value="<?php echo $visit_id; ?>">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">Current Host</label>
                    <div class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-gray-50 rounded-md shadow-sm">
                        <?php echo htmlspecialchars($visit['host_name']); ?>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Change Host (Optional)</label>
                    <select name="host_id" 
                            class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 rounded-md">
                        <option value="<?php echo $visit['host_id']; ?>">Keep current host</option>
                        <?php foreach($potential_hosts as $host): ?>
                            <option value="<?php echo $host['id']; ?>" 
                                    <?php echo $host['id'] == $visit['host_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($host['full_name'] . ' (' . $host['position'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Visit Date</label>
                    <input type="date" name="visit_date" required 
                           value="<?php echo $visit['visit_date']; ?>"
                           min="<?php echo date('Y-m-d'); ?>"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Start Time</label>
                        <input type="time" name="start_time" required 
                               value="<?php echo $visit['start_time']; ?>"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">End Time</label>
                        <input type="time" name="end_time" required 
                               value="<?php echo $visit['end_time']; ?>"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <?php if($visit['photo_path']): ?>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Current Photo</label>
                    <div class="mt-1 relative h-48 bg-gray-100 rounded-lg overflow-hidden">
                        <img src="../<?php echo htmlspecialchars($visit['photo_path']); ?>" 
                             alt="Current visit photo" 
                             class="absolute inset-0 w-full h-full object-cover">
                    </div>
                </div>
                <?php endif; ?>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Update Photo (optional)</label>
                    <div class="mt-1 flex items-center">
                        <div class="w-full">
                            <input type="file" name="visit_photo" accept="image/*"
                                   class="block w-full text-sm text-gray-500
                                          file:mr-4 file:py-2 file:px-4
                                          file:rounded-md file:border-0
                                          file:text-sm file:font-semibold
                                          file:bg-blue-50 file:text-blue-700
                                          hover:file:bg-blue-100">
                        </div>
                    </div>
                    <p class="mt-1 text-sm text-gray-500">Leave empty to keep current photo. Max size: 5MB</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea name="description" required rows="4" 
                              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                              placeholder="Please provide a detailed description of your visit"><?php echo htmlspecialchars($visit['description']); ?></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Additional Notes</label>
                    <textarea name="notes" rows="2" 
                              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                              placeholder="Any additional information"><?php echo htmlspecialchars($visit['notes']); ?></textarea>
                </div>

                <div class="flex justify-end space-x-3">
                    <a href="view.php?id=<?php echo $visit_id; ?>" 
                       class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Cancel
                    </a>
                    <button type="submit" 
                            class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Update Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>

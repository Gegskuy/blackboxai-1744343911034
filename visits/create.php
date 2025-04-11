<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

// Ensure user is logged in and has permission
requireRole('employee');

// Get list of potential hosts (managers and employees)
try {
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
    error_log("Error fetching hosts: " . $e->getMessage());
    $potential_hosts = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Visit Request</title>
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
                <i class="fas fa-calendar-plus mr-2 text-blue-600"></i>
                Create Visit Request
            </h2>

            <?php if(isset($_GET['error'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>

            <?php if(isset($_GET['success'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                    Visit request created successfully!
                </div>
            <?php endif; ?>

            <form action="process.php" method="POST" class="space-y-6">
                <input type="hidden" name="action" value="create">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">Host</label>
                    <select name="host_id" required class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 rounded-md">
                        <option value="">Select a host</option>
                        <?php foreach($potential_hosts as $host): ?>
                            <option value="<?php echo $host['id']; ?>">
                                <?php echo htmlspecialchars($host['full_name'] . ' (' . $host['position'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Visit Date</label>
                    <input type="date" name="visit_date" required 
                           min="<?php echo date('Y-m-d'); ?>"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Start Time</label>
                        <input type="time" name="start_time" required 
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">End Time</label>
                        <input type="time" name="end_time" required 
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Purpose of Visit</label>
                    <textarea name="purpose" required rows="3" 
                              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                              placeholder="Please describe the purpose of your visit"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Additional Notes</label>
                    <textarea name="notes" rows="2" 
                              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                              placeholder="Any additional information"></textarea>
                </div>

                <div class="flex justify-end space-x-3">
                    <a href="../dashboard.php" 
                       class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Cancel
                    </a>
                    <button type="submit" 
                            class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Submit Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>

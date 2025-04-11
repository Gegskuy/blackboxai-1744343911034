<?php
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}
require_once BASE_PATH . '/includes/auth.php';

$userDetails = getUserDetails();
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<nav class="bg-white shadow-lg">
    <div class="max-w-6xl mx-auto px-4">
        <div class="flex justify-between items-center h-16">
            <div class="flex items-center">
                <a href="/dashboard.php" class="flex items-center">
                    <i class="fas fa-building text-blue-600 text-2xl"></i>
                    <span class="ml-2 text-xl font-semibold text-gray-800">Visit Pipeline</span>
                </a>
                
                <div class="hidden md:flex items-center ml-8 space-x-4">
                    <a href="/dashboard.php" 
                       class="<?php echo $currentPage === 'dashboard.php' ? 'text-blue-600' : 'text-gray-600'; ?> hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-tachometer-alt mr-1"></i> Dashboard
                    </a>
                    
                    <?php if (hasPermission('employee')): ?>
                    <a href="/visits/create.php" 
                       class="<?php echo $currentPage === 'create.php' ? 'text-blue-600' : 'text-gray-600'; ?> hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-calendar-plus mr-1"></i> New Visit
                    </a>
                    <?php endif; ?>
                    
                    <?php if (hasPermission('manager')): ?>
                    <a href="/dashboard.php?view=pending" 
                       class="text-gray-600 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-clock mr-1"></i> Pending Approvals
                    </a>
                    <?php endif; ?>
                    
                    <?php if (hasPermission('security')): ?>
                    <a href="/dashboard.php?view=today" 
                       class="text-gray-600 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-calendar-day mr-1"></i> Today's Visits
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="flex items-center">
                <div class="hidden md:flex items-center">
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" 
                                class="flex items-center space-x-2 text-gray-700 hover:text-gray-900 focus:outline-none">
                            <span class="text-sm font-medium">
                                <i class="fas fa-user-circle mr-1"></i>
                                <?php echo htmlspecialchars($userDetails['full_name'] ?? $userDetails['username']); ?>
                            </span>
                            <span class="text-xs px-2 py-0.5 bg-blue-100 text-blue-800 rounded-full">
                                <?php echo htmlspecialchars($userDetails['role']); ?>
                            </span>
                            <i class="fas fa-chevron-down text-xs"></i>
                        </button>
                        
                        <div x-show="open" @click.away="open = false" 
                             class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1">
                            <a href="/profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-user-cog mr-2"></i> Profile Settings
                            </a>
                            <a href="/logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                                <i class="fas fa-sign-out-alt mr-2"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Mobile menu button -->
                <div class="md:hidden flex items-center">
                    <button class="mobile-menu-button p-2 rounded-md text-gray-600 hover:text-gray-900 focus:outline-none">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Mobile menu -->
    <div class="md:hidden hidden mobile-menu">
        <div class="px-2 pt-2 pb-3 space-y-1">
            <a href="/dashboard.php" 
               class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">
                <i class="fas fa-tachometer-alt mr-2"></i> Dashboard
            </a>
            
            <?php if (hasPermission('employee')): ?>
            <a href="/visits/create.php" 
               class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">
                <i class="fas fa-calendar-plus mr-2"></i> New Visit
            </a>
            <?php endif; ?>
            
            <?php if (hasPermission('manager')): ?>
            <a href="/dashboard.php?view=pending" 
               class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">
                <i class="fas fa-clock mr-2"></i> Pending Approvals
            </a>
            <?php endif; ?>
            
            <?php if (hasPermission('security')): ?>
            <a href="/dashboard.php?view=today" 
               class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">
                <i class="fas fa-calendar-day mr-2"></i> Today's Visits
            </a>
            <?php endif; ?>
            
            <div class="border-t border-gray-200 pt-4">
                <a href="/profile.php" 
                   class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">
                    <i class="fas fa-user-cog mr-2"></i> Profile Settings
                </a>
                <a href="/logout.php" 
                   class="block px-3 py-2 rounded-md text-base font-medium text-red-600 hover:text-red-700 hover:bg-gray-50">
                    <i class="fas fa-sign-out-alt mr-2"></i> Logout
                </a>
            </div>
        </div>
    </div>
</nav>

<script>
    // Mobile menu toggle
    document.querySelector('.mobile-menu-button').addEventListener('click', function() {
        document.querySelector('.mobile-menu').classList.toggle('hidden');
    });
</script>

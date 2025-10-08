<?php
// Enhanced navigation component with fine-grained permission filtering
// This component dynamically renders navigation based on user permissions

use App\Libraries\PermissionManager;

// Get the PermissionManager service
$permissionManager = service('permissions');
$user = auth()->user();

// Define navigation items with their required permissions
$navigationItems = [
    [
        'name' => 'Dashboard',
        'icon' => 'home',
        'url' => '/dashboard',
        'permission' => 'templates.view', // Basic permission to view the app
        'activePattern' => '/dashboard'
    ],
    [
        'name' => 'File Explorer',
        'icon' => 'folder-open',
        'url' => '/file-explorer',
        'permission' => 'templates.view',
        'activePattern' => '/file-explorer'
    ],
    [
        'name' => 'User Management',
        'icon' => 'users-cog',
        'url' => '/user-management',
        'permission' => 'users.manage-admins', // Only admins can manage users
        'activePattern' => '/user-management'
    ],
    [
        'name' => 'Advanced Permissions',
        'icon' => 'shield-alt',
        'url' => '/advanced-permissions',
        'permission' => 'admin.access', // Only superadmins can manage advanced permissions
        'activePattern' => '/advanced-permissions'
    ]
];

// Filter navigation items based on user permissions
$filteredNavigationItems = [];
foreach ($navigationItems as $item) {
    // If user has the required permission, add the item to filtered list
    if ($permissionManager->can($user, $item['permission'])) {
        $filteredNavigationItems[] = $item;
    }
}

// Determine active navigation item based on current URL
$currentUrl = current_url();
$activeNavItem = '';
foreach ($filteredNavigationItems as $item) {
    if (strpos($currentUrl, $item['activePattern']) !== false) {
        $activeNavItem = $item['name'];
        break;
    }
}
?>

<nav class="bg-white shadow-lg border-b border-gray-200 <?php echo isset($stickyNav) && $stickyNav ? 'sticky top-0 z-10' : '' ?>">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <h1 class="text-2xl font-bold text-blue-600">
                        <i class="fas fa-<?php echo $pageIcon ?? 'file-alt' ?> mr-2"></i>
                        <?php echo $pageTitle ?? 'Templately' ?>
                    </h1>
                </div>
            </div>

            <div class="flex items-center space-x-6">
                <?php if (!isset($hideNavLinks) || !$hideNavLinks) : ?>
                    <?php foreach ($filteredNavigationItems as $item): ?>
                        <?php 
                        $isActive = strpos(current_url(), $item['activePattern']) !== false;
                        ?>
                        <a href="<?php echo base_url($item['url']); ?>" 
                           class="px-3 py-2 text-gray-600 hover:text-blue-600 transition duration-200 <?php echo $isActive ? 'border-b-2 border-blue-600 text-blue-600' : '' ?>">
                            <i class="fas fa-<?php echo $item['icon']; ?> mr-2"></i><?php echo $item['name']; ?>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>

                <?php if (auth()->user()) : ?>
                    <div class="ml-6 text-gray-700">
                        <i class="fas fa-user-circle mr-2"></i>
                        <?php if (isset($showWelcome) && $showWelcome) : ?>
                            Welcome, <span class="font-semibold"><?php echo esc(auth()->user()->username) ?></span>
                        <?php else: ?>
                            <span class="font-semibold"><?php echo esc(auth()->user()->username) ?></span>
                        <?php endif; ?>
                    </div>

                    <a href="<?php echo base_url('logout'); ?>" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition duration-200">
                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
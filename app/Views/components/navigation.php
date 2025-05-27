<nav class="bg-white shadow-lg border-b border-gray-200 <?= isset($stickyNav) && $stickyNav ? 'sticky top-0 z-10' : '' ?>">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
                        <div class="flex items-center">
                <div class="flex-shrink-0">
                    <h1 class="text-2xl font-bold text-blue-600">
                        <i class="fas fa-<?= $pageIcon ?? 'file-alt' ?> mr-2"></i>
                        <?= $pageTitle ?? 'Templately' ?>
                    </h1>
                </div>
            </div>

                        <div class="flex items-center space-x-4">
                                <?php if (!isset($hideNavLinks) || !$hideNavLinks): ?>
                    <?php 
                    $currentPath = current_url();
                    $dashboardActive = strpos($currentPath, '/dashboard') !== false;
                    $fileExplorerActive = strpos($currentPath, '/file-explorer') !== false;
                    $userManagementActive = strpos($currentPath, '/user-management') !== false;
                    ?>
                    
                                        <?php if (!$dashboardActive): ?>
                        <a href="/dashboard" class="text-gray-600 hover:text-blue-600 transition duration-200">
                            <i class="fas fa-home mr-2"></i>Dashboard
                        </a>
                    <?php endif; ?>
                    
                                        <?php if (!$fileExplorerActive): ?>
                        <a href="/file-explorer" class="<?= $dashboardActive ? 'bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg' : 'text-gray-600 hover:text-blue-600' ?> transition duration-200">
                            <i class="fas fa-folder-open mr-2"></i>File Explorer
                        </a>
                    <?php endif; ?>
                    
                                        <?php if (auth()->user() && auth()->user()->inGroup('superadmin', 'admin')): ?>
                        <?php if (!$userManagementActive): ?>
                            <a href="/user-management" class="<?= $dashboardActive ? 'bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg' : 'text-gray-600 hover:text-blue-600' ?> transition duration-200">
                                <i class="fas fa-users-cog mr-2"></i>User Management
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php endif; ?>

                                <?php if (auth()->user()): ?>
                    <div class="text-gray-700">
                        <i class="fas fa-user-circle mr-2"></i>
                        <?php if (isset($showWelcome) && $showWelcome): ?>
                            Welcome, <span class="font-semibold"><?= esc(auth()->user()->username) ?></span>
                        <?php else: ?>
                            <span class="font-semibold"><?= esc(auth()->user()->username) ?></span>
                        <?php endif; ?>
                    </div>

                                        <a href="/logout" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition duration-200">
                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<nav class="bg-white shadow-lg border-b border-gray-200 <?php echo isset($stickyNav) && $stickyNav ? 'sticky top-0 z-10' : '' ?>" x-data="{ mobileMenuOpen: false }">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <a href="/" class="text-2xl font-bold text-blue-600 hover:text-blue-700 transition duration-200">
                        <i class="fas fa-<?php echo $pageIcon ?? 'file-alt' ?> mr-2"></i>
                        <?php echo $pageTitle ?? 'Templately' ?>
                    </a>
                </div>
            </div>

            <!-- Desktop Menu -->
            <div class="hidden md:flex items-center space-x-1">
                <?php if (!isset($hideNavLinks) || !$hideNavLinks) : ?>
                    <?php
                    $currentPath = current_url();
                    $dashboardActive = strpos($currentPath, '/dashboard') !== false;
                    $fileExplorerActive = strpos($currentPath, '/file-explorer') !== false;
                    $usersActive = strpos($currentPath, '/users') !== false;
                    ?>
                    
                    <a href="/dashboard" class="px-4 py-2 text-sm font-medium text-gray-700 hover:text-blue-600 hover:bg-blue-50 rounded-md transition duration-200 <?php echo $dashboardActive ? 'bg-blue-50 text-blue-600' : '' ?>">
                        <i class="fas fa-home mr-1.5"></i>Dashboard
                    </a>
                    
                    <a href="/file-explorer" class="px-4 py-2 text-sm font-medium text-gray-700 hover:text-blue-600 hover:bg-blue-50 rounded-md transition duration-200 <?php echo $fileExplorerActive ? 'bg-blue-50 text-blue-600' : '' ?>">
                        <i class="fas fa-folder-open mr-1.5"></i>File Explorer
                    </a>
                    
                    <?php if (auth()->user() && auth()->user()->inGroup('superadmin', 'manager')) : ?>
                        <a href="/users" class="px-4 py-2 text-sm font-medium text-gray-700 hover:text-indigo-600 hover:bg-indigo-50 rounded-md transition duration-200 <?php echo $usersActive ? 'bg-indigo-50 text-indigo-600' : '' ?>">
                            <i class="fas fa-users-cog mr-1.5"></i>Users
                        </a>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if (auth()->user()) : ?>
                    <div class="ml-4 pl-4 border-l border-gray-300 flex items-center space-x-3">
                        <!-- Profile Dropdown -->
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" @click.away="open = false"
                                    class="flex items-center text-sm text-gray-700 hover:text-blue-600 focus:outline-none transition duration-200">
                                <i class="fas fa-user-circle mr-2 text-gray-500"></i>
                                <span class="font-semibold"><?php echo esc(auth()->user()->username) ?></span>
                                <i class="fas fa-chevron-down ml-1 text-xs"></i>
                            </button>
                            
                            <div x-show="open"
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="transform opacity-0 scale-95"
                                 x-transition:enter-end="transform opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="transform opacity-100 scale-100"
                                 x-transition:leave-end="transform opacity-0 scale-95"
                                 class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50 border border-gray-200"
                                 style="display: none;">
                                <a href="/profile" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-user-circle mr-2"></i>My Profile
                                </a>
                                <div class="border-t border-gray-200"></div>
                                <a href="/logout" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                    <i class="fas fa-sign-out-alt mr-2"></i>Logout
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Mobile Menu Button -->
            <div class="flex md:hidden items-center">
                <button @click="mobileMenuOpen = !mobileMenuOpen" type="button" class="text-gray-500 hover:text-gray-600 focus:outline-none focus:text-gray-600" aria-label="Toggle menu">
                    <i class="fas fa-bars text-2xl" x-show="!mobileMenuOpen"></i>
                    <i class="fas fa-times text-2xl" x-show="mobileMenuOpen" style="display: none;"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Menu -->
    <div x-show="mobileMenuOpen" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2"
         class="md:hidden bg-white border-t border-gray-200"
         style="display: none;">
        <div class="px-2 pt-2 pb-3 space-y-1">
            <?php if (!isset($hideNavLinks) || !$hideNavLinks) : ?>
                <a href="/dashboard" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-blue-600 hover:bg-blue-50 <?php echo $dashboardActive ? 'bg-blue-50 text-blue-600' : '' ?>">
                    <i class="fas fa-home mr-2 w-5 text-center"></i>Dashboard
                </a>
                
                <a href="/file-explorer" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-blue-600 hover:bg-blue-50 <?php echo $fileExplorerActive ? 'bg-blue-50 text-blue-600' : '' ?>">
                    <i class="fas fa-folder-open mr-2 w-5 text-center"></i>File Explorer
                </a>
                
                <?php if (auth()->user() && auth()->user()->inGroup('superadmin', 'manager')) : ?>
                    <a href="/users" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-indigo-600 hover:bg-indigo-50 <?php echo $usersActive ? 'bg-indigo-50 text-indigo-600' : '' ?>">
                        <i class="fas fa-users-cog mr-2 w-5 text-center"></i>Users
                    </a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <?php if (auth()->user()) : ?>
            <div class="pt-4 pb-4 border-t border-gray-200">
                <div class="flex items-center px-5">
                    <div class="flex-shrink-0">
                        <i class="fas fa-user-circle text-gray-400 text-3xl"></i>
                    </div>
                    <div class="ml-3">
                        <div class="text-base font-medium leading-none text-gray-800"><?php echo esc(auth()->user()->username) ?></div>
                        <div class="text-sm font-medium leading-none text-gray-500 mt-1"><?php echo esc(auth()->user()->email) ?></div>
                    </div>
                </div>
                <div class="mt-3 px-2 space-y-1">
                    <a href="/profile" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-blue-600 hover:bg-blue-50">
                        <i class="fas fa-user-circle mr-2 w-5 text-center"></i>My Profile
                    </a>
                    <a href="/logout" class="block px-3 py-2 rounded-md text-base font-medium text-red-600 hover:text-red-700 hover:bg-red-50">
                        <i class="fas fa-sign-out-alt mr-2 w-5 text-center"></i>Logout
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</nav>

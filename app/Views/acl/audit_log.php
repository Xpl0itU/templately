<?php echo $this->extend('layouts/app') ?>

<?php echo $this->section('title') ?>Permission Audit Log<?php echo $this->endSection() ?>

<?php echo $this->section('navigation') ?>
    <?php echo $this->include('components/navigation', [
        'pageTitle' => 'Permission Audit Log',
        'pageIcon' => 'history',
        'stickyNav' => false,
        'showWelcome' => false
    ]) ?>
<?php echo $this->endSection() ?>

<?php echo $this->section('content') ?>
<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="bg-white shadow-lg rounded-lg overflow-hidden">
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">
                <i class="fas fa-history mr-2 text-blue-500"></i>Permission Audit Log
            </h2>
            <p class="text-gray-600 mt-1">Track all permission-related activities in the system</p>
        </div>
        
        <div class="p-6">
            <!-- Filters -->
            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label for="filter-action" class="block text-sm font-medium text-gray-700 mb-1">Action</label>
                        <select id="filter-action" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="">All Actions</option>
                            <option value="permission_check">Permission Check</option>
                            <option value="grant">Grant Permission</option>
                            <option value="revoke">Revoke Permission</option>
                            <option value="change_owner">Change Owner</option>
                            <option value="access_attempt">Access Attempt</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="filter-user" class="block text-sm font-medium text-gray-700 mb-1">User</label>
                        <select id="filter-user" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="">All Users</option>
                            <!-- Users will be populated via JavaScript -->
                        </select>
                    </div>
                    
                    <div>
                        <label for="filter-resource-type" class="block text-sm font-medium text-gray-700 mb-1">Resource Type</label>
                        <select id="filter-resource-type" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="">All Types</option>
                            <option value="template">Template</option>
                            <option value="filled_file">Filled File</option>
                            <option value="user">User</option>
                        </select>
                    </div>
                    
                    <div class="flex items-end">
                        <button type="button" 
                                id="apply-filters"
                                class="w-full inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <i class="fas fa-filter mr-2"></i>Apply Filters
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Audit Log Table -->
            <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Timestamp</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Resource</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Permission</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Result</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200" id="audit-log-body">
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">
                                Loading audit log entries...
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <!-- Pagination -->
                <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                    <div class="flex-1 flex justify-between sm:hidden">
                        <a href="#" id="prev-page" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Previous
                        </a>
                        <a href="#" id="next-page" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Next
                        </a>
                    </div>
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">
                                Showing <span id="start-row">0</span> to <span id="end-row">0</span> of <span id="total-rows">0</span> results
                            </p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                <a href="#" id="prev-page-sm" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    <span class="sr-only">Previous</span>
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                                <a href="#" id="next-page-sm" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    <span class="sr-only">Next</span>
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentPage = 1;
    const itemsPerPage = 20;
    
    // Load users for filter
    loadUsersForFilter();
    
    // Load initial audit log
    loadAuditLog(currentPage, itemsPerPage);
    
    // Apply filters button
    document.getElementById('apply-filters').addEventListener('click', function() {
        currentPage = 1;
        loadAuditLog(currentPage, itemsPerPage);
    });
    
    // Pagination buttons
    document.getElementById('prev-page').addEventListener('click', function(e) {
        e.preventDefault();
        if (currentPage > 1) {
            currentPage--;
            loadAuditLog(currentPage, itemsPerPage);
        }
    });
    
    document.getElementById('next-page').addEventListener('click', function(e) {
        e.preventDefault();
        currentPage++;
        loadAuditLog(currentPage, itemsPerPage);
    });
    
    document.getElementById('prev-page-sm').addEventListener('click', function(e) {
        e.preventDefault();
        if (currentPage > 1) {
            currentPage--;
            loadAuditLog(currentPage, itemsPerPage);
        }
    });
    
    document.getElementById('next-page-sm').addEventListener('click', function(e) {
        e.preventDefault();
        currentPage++;
        loadAuditLog(currentPage, itemsPerPage);
    });
});

function loadUsersForFilter() {
    // In a real implementation, this would fetch users from an API
    // For now, we'll leave it empty as the backend would handle this
    // This is just to show the structure
}

function loadAuditLog(page, limit) {
    const actionFilter = document.getElementById('filter-action').value;
    const userFilter = document.getElementById('filter-user').value;
    const resourceTypeFilter = document.getElementById('filter-resource-type').value;
    
    // In a real implementation, this would make an API call
    // For now, we'll show a message indicating it's coming soon
    const tbody = document.getElementById('audit-log-body');
    tbody.innerHTML = `
        <tr>
            <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">
                <div class="flex flex-col items-center">
                    <i class="fas fa-history text-gray-400 text-4xl mb-2"></i>
                    <p>Audit logging functionality is coming soon.</p>
                    <p class="text-xs mt-1">This is a placeholder for the actual audit log implementation.</p>
                </div>
            </td>
        </tr>
    `;
}
</script>
<?php echo $this->endSection() ?>
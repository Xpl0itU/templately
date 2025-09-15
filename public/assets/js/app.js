/**
 * Main Application Initializer
 */
Templately.App = (function() {
    'use strict';
    
    // Private functions
    function initializeModules() {
        // Initialize modals
        Templately.Modal.init({
            success: document.getElementById('successModal'),
            error: document.getElementById('errorModal'),
            confirm: document.getElementById('confirmModal'),
            input: document.getElementById('inputModal'),
            loading: document.getElementById('loadingModal')
        });
        
        // Initialize modules only if their elements exist on the page
        if (document.getElementById('fileHierarchy')) {
            Templately.FileExplorerMain.init();
        }
        
        if (document.getElementById('uploadWizardModal')) {
            Templately.TemplateWizard.init();
        }
        
        Templately.FilledFileEditor.init();
    }
    
    function attachGlobalEventListeners() {
        // Global keydown handler
        document.addEventListener('keydown', handleGlobalKeydown);
    }
    
    function handleGlobalKeydown(e) {
        // Handle escape key for modals
        if (e.key === 'Escape') {
            if (Templately.Modal.currentModal) {
                e.preventDefault();
                Templately.Modal.dismissCurrentModal();
            } else if (!document.getElementById('uploadWizardModal').classList.contains('hidden')) {
                e.preventDefault();
                Templately.TemplateWizard.reset();
            } else if (!document.getElementById('loadingModal').classList.contains('hidden')) {
                e.preventDefault();
                Templately.FileExplorer.hideLoadingModal();
            }
        }
    }
    
    // Public API
    return {
        init: function() {
            // Wait for DOM to be fully loaded
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initializeModules);
            } else {
                initializeModules();
            }
            
            attachGlobalEventListeners();
        }
    };
})();

// Initialize the application when the DOM is ready
Templately.App.init();
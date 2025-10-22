Templately.App = (function() {
    'use strict';
    
    function initializeModules() {
        Templately.Modal.init({
            success: document.getElementById('successModal'),
            error: document.getElementById('errorModal'),
            confirm: document.getElementById('confirmModal'),
            input: document.getElementById('inputModal'),
            loading: document.getElementById('loadingModal')
        });
        
        if (document.getElementById('fileHierarchy')) {
            Templately.FileExplorerMain.init();
        }
        
        if (document.getElementById('uploadWizardModal')) {
            Templately.TemplateWizard.init();
        }
        
        Templately.FilledFileEditor.init();
    }
    
    function attachGlobalEventListeners() {
        document.addEventListener('keydown', handleGlobalKeydown);
    }
    
    function handleGlobalKeydown(e) {
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
    
    return {
        init: function() {
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initializeModules);
            } else {
                initializeModules();
            }
            
            attachGlobalEventListeners();
        }
    };
})();

Templately.App.init();
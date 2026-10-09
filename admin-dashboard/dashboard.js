document.addEventListener('DOMContentLoaded', function () {
    const tabs = document.querySelectorAll('.nav-tab');
    const contents = document.querySelectorAll('.tab-content');
    
    // 1. Check if a tab state was saved from a previous session/refresh
    const activeTabId = localStorage.getItem('activeWpTab');

    if (activeTabId) {
        // Remove default active classes
        document.querySelector('.nav-tab-active')?.classList.remove('nav-tab-active');
        document.querySelector('.tab-content.active')?.classList.remove('active');

        // Apply active classes to the saved tab selection
        const savedTab = document.querySelector(`[data-tab="${activeTabId}"]`);
        const savedContent = document.getElementById(`tab-${activeTabId}`);
        
        if (savedTab && savedContent) {
            savedTab.classList.add('nav-tab-active');
            savedContent.classList.add('active');
        }
    }

    // 2. Handle tab switching on click
    tabs.forEach(tab => {
        tab.addEventListener('click', function (e) {
            e.preventDefault();

            // Remove active status from all tabs and contents
            tabs.forEach(t => t.classList.remove('nav-tab-active'));
            contents.forEach(c => c.classList.remove('active'));

            // Add active status to the clicked tab
            this.classList.add('nav-tab-active');
            
            // Get the target content ID using the data-tab attribute
            const tabSlug = this.getAttribute('data-tab');
            const targetContent = document.getElementById(`tab-${tabSlug}`);
            if (targetContent) {
                targetContent.classList.add('active');
            }

            // Save the state to localStorage so it survives a refresh
            localStorage.setItem('activeWpTab', tabSlug);
        });
    });
});
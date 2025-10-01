// resources/js/sidebar.js

class Sidebar {
    constructor() {
        this.sidebar = document.getElementById('sidebar');
        this.toggle = document.getElementById('sidebar-toggle');
        this.overlay = document.getElementById('sidebar-overlay');
        this.storageKey = 'sidebarCollapsed';
        
        if (!this.sidebar) return;
        
        this.init();
    }
    
    init() {
        this.loadState();
        this.attachEvents();
    }
    
    loadState() {
        if (window.innerWidth > 991.98) {
            const isCollapsed = localStorage.getItem(this.storageKey) === 'true';
            if (isCollapsed) {
                document.body.classList.add('sidebar-collapsed');
            }
        }
    }
    
    attachEvents() {
        // Desktop toggle
        if (this.toggle) {
            this.toggle.addEventListener('click', () => {
                document.body.classList.toggle('sidebar-collapsed');
                const isCollapsed = document.body.classList.contains('sidebar-collapsed');
                localStorage.setItem(this.storageKey, isCollapsed);
            });
        }
        
        // Mobile overlay
        if (this.overlay) {
            this.overlay.addEventListener('click', () => {
                document.body.classList.remove('sidebar-open');
            });
        }
        
        // Resize handler
        window.addEventListener('resize', () => {
            if (window.innerWidth > 991.98) {
                document.body.classList.remove('sidebar-open');
            }
        });
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    new Sidebar();
});

export default Sidebar;
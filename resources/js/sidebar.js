// resources/js/sidebar.js

class Sidebar {
    constructor() {
        this.sidebar = document.getElementById('sidebar');
        this.toggle = document.getElementById('sidebar-toggle');
        this.overlay = document.getElementById('sidebar-overlay');
        this.mobileToggle = document.getElementById('mobile-sidebar-toggle'); // TAMBAHKAN INI
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
        
        // ==========================================================
        // === BAGIAN YANG HILANG ADA DI SINI ===
        // Mobile toggle untuk MEMBUKA sidebar
        if (this.mobileToggle) {
            this.mobileToggle.addEventListener('click', () => {
                document.body.classList.add('sidebar-open');
            });
        }
        // ==========================================================-
        
        document.addEventListener('click', (event) => {
            // Cek apakah sidebar sedang terbuka
            const isSidebarOpen = document.body.classList.contains('sidebar-open');
            
            // Cek apakah target klik BUKAN bagian dari sidebar
            const isClickInsideSidebar = this.sidebar.contains(event.target);
            
            // Cek apakah target klik BUKAN tombol toggle mobile itu sendiri
            const isClickOnMobileToggle = this.mobileToggle ? this.mobileToggle.contains(event.target) : false;

            // Jika sidebar terbuka, dan klik terjadi di luar sidebar & di luar tombol toggle,
            // maka tutup sidebar.
            if (isSidebarOpen && !isClickInsideSidebar && !isClickOnMobileToggle) {
                document.body.classList.remove('sidebar-open');
            }
        });
        
        // Resize handler
        window.addEventListener('resize', () => {
            // Otomatis tutup sidebar mobile jika layar membesar ke mode desktop
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
document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.getElementById('sidebar');
    const collapseToggleBtn = document.getElementById('sidebar-collapse-toggle');
    const hamburgerMenuBtn = document.getElementById('hamburger-menu');
    const overlay = document.getElementById('sidebar-overlay');

    const SIDEBAR_STATE_KEY = 'sidebarCollapsed';

    if (!sidebar) {
        console.error("Sidebar element not found!");
        return;
    }

    // Fungsi untuk handle collapse/expand di desktop
    const handleDesktopCollapse = () => {
        document.body.classList.toggle('sidebar-collapsed');
        localStorage.setItem(SIDEBAR_STATE_KEY, document.body.classList.contains('sidebar-collapsed'));
    };

    // Fungsi untuk buka sidebar di mobile
    const openMobileSidebar = () => {
        sidebar.classList.add('is-mobile-open');
        overlay.classList.add('is-active');
    };

    // Fungsi untuk tutup sidebar di mobile
    const closeMobileSidebar = () => {
        sidebar.classList.remove('is-mobile-open');
        overlay.classList.remove('active');
    };

    // Terapkan state awal saat halaman dimuat
    const applyInitialSidebarState = () => {
        if (window.innerWidth > 991.98) {
            const isCollapsed = localStorage.getItem(SIDEBAR_STATE_KEY) === 'true';
            if (isCollapsed) {
                document.body.classList.add('sidebar-collapsed');
            }
        }
    };

    // Pasang event listeners
    if (collapseToggleBtn) {
        collapseToggleBtn.addEventListener('click', handleDesktopCollapse);
    }
    if (hamburgerMenuBtn) {
        hamburgerMenuBtn.addEventListener('click', openMobileSidebar);
    }
    if (overlay) {
        overlay.addEventListener('click', closeMobileSidebar);
    }
    
    // Inisialisasi
    applyInitialSidebarState();
});
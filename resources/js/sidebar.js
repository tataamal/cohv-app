// Fungsi ini akan dieksekusi setelah semua HTML dimuat
document.addEventListener('DOMContentLoaded', function() {
    const toggleButton = document.getElementById('sidebar-collapse-toggle');

    // Periksa apakah tombolnya ada di halaman
    if (!toggleButton) {
        // Jika Anda ingin pesan error, bisa ditambahkan di sini.
        // console.error("Tombol collapse sidebar tidak ditemukan.");
        return; 
    }

    const collapseSidebar = () => {
        document.body.classList.toggle('sidebar-collapsed');
        updateToggleButtonState();
    };
    
    const updateToggleButtonState = () => {
        const isCollapsed = document.body.classList.contains('sidebar-collapsed');
        const collapseButton = document.getElementById('sidebar-collapse-toggle');
        
        if (collapseButton) {
            const icon = collapseButton.querySelector('.collapse-icon');
            const text = collapseButton.querySelector('.sidebar-text');
            
            if (isCollapsed) {
                icon.classList.remove('fa-chevron-left');
                icon.classList.add('fa-chevron-right');
                if(text) text.textContent = 'Expand';
            } else {
                icon.classList.remove('fa-chevron-right');
                icon.classList.add('fa-chevron-left');
                if(text) text.textContent = 'Collapse';
            }
        }
    };

    // Tambahkan event listener ke tombol di footer sidebar
    toggleButton.addEventListener('click', collapseSidebar);
    
    // Inisialisasi state tombol saat halaman dimuat
    updateToggleButtonState();
});


function handleComponentSelect(aufnr) {
    // Memilih semua checkbox yang dicentang di dalam tabel PRO ini
    const selectedCheckboxes = document.querySelectorAll(`.component-select-${aufnr}:checked`);
    
    // Mengambil wadah kontrol bulk action dari DOM
    // Catatan: Pastikan ini adalah ID unik di halaman Anda
    const bulkControls = document.getElementById('bulk-action-controls'); 

    // Jika wadah kontrol ditemukan
    if (!bulkControls) return; 

    // Tentukan apakah ada item yang dipilih
    if (selectedCheckboxes.length > 0) {
        bulkControls.classList.remove('d-none');
        bulkControls.classList.add('d-flex');
        
        // Perbarui teks tombol
        // Perhatian: Ganti selector ini jika Anda menggunakan ID yang berbeda
        document.querySelector('#bulk-action-controls .btn-warning').innerHTML = 
            `<i class="fas fa-edit me-1"></i> Edit Selected (${selectedCheckboxes.length})`;
        document.querySelector('#bulk-action-controls .btn-danger').innerHTML = 
            `<i class="fas fa-trash me-1"></i> Remove Selected (${selectedCheckboxes.length})`;
            
    } else {
        bulkControls.classList.remove('d-flex');
        bulkControls.classList.add('d-none');
    }
}

// Fungsi untuk memilih/menghapus semua
function toggleSelectAllComponents(aufnr) {
    const selectAllCheckbox = document.getElementById(`select-all-components-${aufnr}`);
    const checkboxes = document.querySelectorAll(`.component-select-${aufnr}`);
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAllCheckbox.checked;
    });
    
    handleComponentSelect(aufnr);
}

// Logika untuk tombol Edit per baris
function handleEditClick(buttonElement) {
    // Ambil data-attribute dan buka modal edit
    const aufnr = buttonElement.dataset.aufnr;
    const rspos = buttonElement.dataset.rspos;
    // ... logika untuk mengisi dan membuka modal edit
    console.log(`Mengedit Komponen PRO: ${aufnr}, Item: ${rspos}`);
}
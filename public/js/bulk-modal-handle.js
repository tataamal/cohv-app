function updateBulkControls() {
    const wrapper = document.getElementById('bulk-actions-wrapper');
    const countBadge = document.getElementById('selection-count-badge');
    if (!wrapper || !countBadge) return; // Pengaman jika elemen tidak ada

    const hasPLO = selectedPLO.size > 0;
    const hasPRO = selectedPRO.size > 0;
    const totalSelected = selectedPLO.size + selectedPRO.size;

    // 1. Perbarui angka di dalam badge
    countBadge.textContent = totalSelected;

    // 2. Tampilkan atau sembunyikan seluruh wrapper
    wrapper.classList.toggle('d-none', totalSelected === 0);

    // 3. Ambil dan atur visibilitas tombol-tombol spesifik PRO
    const bulkScheduleBtn = document.getElementById('bulk-schedule-btn');
    const bulkReadppBtn = document.getElementById('bulk-readpp-btn');
    const bulkTecoBtn = document.getElementById('bulk-teco-btn');
    const bulkRefreshBtn = document.getElementById('bulk-refresh-btn');

    // Tampilkan tombol-tombol ini HANYA jika ada PRO yang dipilih
    if (bulkScheduleBtn) bulkScheduleBtn.style.display = hasPRO ? 'inline-block' : 'none';
    if (bulkReadppBtn) bulkReadppBtn.style.display = hasPRO ? 'inline-block' : 'none';
    if (bulkTecoBtn) bulkTecoBtn.style.display = hasPRO ? 'inline-block' : 'none';
    if (bulkRefreshBtn) bulkRefreshBtn.style.display = hasPRO ? 'inline-block' : 'none';
}

// const scheduleForm = document.getElementById('bulkScheduleForm');
// const bulkScheduleModal = new bootstrap.Modal(document.getElementById('bulkScheduleModal')); // Inisialisasi modal

function openBulkScheduleModal() {
    if (selectedPRO.size === 0) {
        Swal.fire('Info', 'Tidak ada Production Order (PRO) yang dipilih.', 'info');
        return;
    }

    // Ambil elemen-elemen di dalam modal
    const proListElement = document.getElementById('bulkScheduleProList');
    const plantDisplayElement = document.getElementById('bulkSchedulePlant');

    // Kosongkan daftar dan plant sebelumnya
    proListElement.innerHTML = ''; 

    // --- PERUBAHAN UTAMA ---
    // 1. Ambil nilai plant dari atribut data-plant di form
    const plant = bulkActionPlantCode;

    // 2. Tampilkan nilai plant di elemen <p> yang sudah kita siapkan
    plantDisplayElement.textContent = plant || 'Plant tidak ditemukan!';
    // ----------------------

    // Isi daftar PRO
    selectedPRO.forEach(pro => {
        const listItem = document.createElement('li');
        listItem.textContent = pro;
        proListElement.appendChild(listItem);
    });

    // Set tanggal hari ini sebagai default
    document.getElementById('bulkScheduleDate').valueAsDate = new Date();

    // Tampilkan modal
    bulkScheduleModal.show();
}

function openBulkReadPpModal() {
    if (selectedPRO.size === 0) return;
    const proListElement = document.getElementById('bulkReadPpProList');
    proListElement.innerHTML = '';
    selectedPRO.forEach(pro => {
        const listItem = document.createElement('li');
        listItem.textContent = pro;
        proListElement.appendChild(listItem);
    });
    bulkReadPpModal.show();
}

function openBulkTecoModal() {
    if (selectedPRO.size === 0) return;
    const proListElement = document.getElementById('bulkTecoProList');
    proListElement.innerHTML = '';
    selectedPRO.forEach(pro => {
        const listItem = document.createElement('li');
        listItem.textContent = pro;
        proListElement.appendChild(listItem);
    });
    bulkTecoModal.show();
}

function openBulkRefreshModal() {
    // console.log(bulkActionPlantCode)
    if (selectedPRO.size === 0) {
        return Swal.fire('Info', 'Tidak ada Production Order (PRO) yang dipilih.', 'info');
    }
    
    // Validasi bahwa plant code sudah tersimpan
    if (!bulkActionPlantCode) {
        return Swal.fire('Error', 'Kode Plant tidak ditemukan. Coba pilih ulang item.', 'error');
    }

    const proListElement = document.getElementById('bulkRefreshProList');
    proListElement.innerHTML = ''; 

    // Opcional: Tampilkan plant code di modal untuk informasi
    const plantInfo = document.createElement('p');
    plantInfo.innerHTML = `Aksi ini akan dijalankan untuk Plant: <strong>${bulkActionPlantCode}</strong>`;
    plantInfo.className = 'mb-2';
    proListElement.appendChild(plantInfo);

    selectedPRO.forEach(pro => {
        const listItem = document.createElement('li');
        listItem.textContent = pro;
        proListElement.appendChild(listItem);
    });

    // === BAGIAN PENTING ===
    // 1. Cari tombol konfirmasi di dalam modal
    const confirmBtn = document.getElementById('confirmBulkRefreshBtn');
    
    // 2. "Titipkan" kode plant ke atribut data-plant
    confirmBtn.dataset.plant = bulkActionPlantCode;
    // =======================
    
    bulkRefreshModal.show();
}
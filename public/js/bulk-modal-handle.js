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

function openBulkScheduleModal() {
    if (selectedPRO.size === 0) {
        Swal.fire('Info', 'Tidak ada Production Order (PRO) yang dipilih.', 'info');
        return;
    }
}

function openBulkScheduleModal() {
    if (selectedPRO.size === 0) {
        Swal.fire('Info', 'Tidak ada Production Order (PRO) yang dipilih.', 'info');
        return;
    }

    const proListElement = document.getElementById('bulkScheduleProList');
    proListElement.innerHTML = ''; // Kosongkan daftar sebelumnya

    // Isi daftar PRO
    selectedPRO.forEach(pro => {
        const listItem = document.createElement('li');
        listItem.textContent = pro;
        proListElement.appendChild(listItem);
    });

    // Set tanggal hari ini sebagai default
    document.getElementById('bulkScheduleDate').valueAsDate = new Date();

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
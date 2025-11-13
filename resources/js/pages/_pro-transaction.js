document.addEventListener('DOMContentLoaded', function() {
    /*==================== Inisialisasi Elemen ====================*/
    const selectAll = document.getElementById('selectAllCheckbox');
    const proCheckboxes = document.querySelectorAll('.pro-checkbox');

    // Tombol Aksi
    const scheduleBtn = document.getElementById('bulkRescheduleBtn');
    const refreshBtn = document.getElementById('bulkRefreshBtn');
    const changePv = document.getElementById('bulkChangePv');
    const readPpBtn = document.getElementById('bultkReadPpBtn');
    const changeQty = document.getElementById('bulkChangeQty');
    const tecoBtn = document.getElementById('bulkTecoBtn');
    
    const allBulkButtons = [scheduleBtn, refreshBtn, changePv, readPpBtn, changeQty, tecoBtn];

    // Elemen Modal Generik
    const bulkActionModal = document.getElementById('bulkActionModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalDescription = document.getElementById('modalDescription');
    const modalProList = document.getElementById('modalProList');
    const modalBtnBatal = document.getElementById('modalBtnBatal');
    const modalBtnLanjutkan = document.getElementById('modalBtnLanjutkan');
    const modalHeader = document.getElementById('modalHeader');
    const modalIcon = document.getElementById('modalIcon');
    const scheduleInputs = document.getElementById('scheduleInputs');
    const modalScheduleDate = document.getElementById('modalScheduleDate');
    const modalScheduleTime = document.getElementById('modalScheduleTime');

    // State untuk menyimpan aksi dan data yang sedang diproses
    let currentAction = null;
    let currentPros = [];

    // Cek elemen penting
    if (!selectAll || !proCheckboxes.length || !bulkActionModal || allBulkButtons.some(btn => !btn)) {
        console.warn('Satu atau lebih elemen UI (checkbox, tombol, atau modal) tidak ditemukan. Fungsionalitas bulk mungkin tidak bekerja.');
        return;
    }

    // ==================== Logika Checkbox ====================
    function updateSelectionState() {
        const checkedCount = document.querySelectorAll('.pro-checkbox:checked').length;
        const totalCount = proCheckboxes.length;
        const anySelected = checkedCount > 0;

        allBulkButtons.forEach(btn => btn.disabled = !anySelected);
        
        if (checkedCount === 0) {
            selectAll.checked = false;
            selectAll.indeterminate = false;
        } else if (checkedCount === totalCount) {
            selectAll.checked = true;
            selectAll.indeterminate = false;
        } else {
            selectAll.checked = false;
            selectAll.indeterminate = true;
        }
    }

    function getSelectedPros() {
        return Array.from(proCheckboxes)
            .filter(cb => cb.checked)
            .map(cb => cb.value);
    }

    selectAll.addEventListener('change', function() {
        proCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateSelectionState();
    });

    proCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateSelectionState();
        });
    });

    // ==================== Logika Modal Generik ====================

    /**
     * Membuka dan mengisi modal konfirmasi.
     * @param {string} action - Tipe aksi (misal: 'refresh', 'teco')
     * @param {string} title - Judul untuk modal
     * @param {string} description - Teks deskripsi/pertanyaan
     * @param {string[]} pros - Array berisi nomor PRO yang dipilih
     */
    function openConfirmationModal(action, title, description, pros) {
        currentAction = action;
        currentPros = pros;

        modalTitle.textContent = title;
        modalDescription.textContent = description;
        modalHeader.classList.remove('warning');
        scheduleInputs.style.display = 'none'; 
        modalIcon.innerHTML = '&#9776;'; 

        if (action === 'teco') {
            modalHeader.classList.add('warning'); 
            modalIcon.innerHTML = '&#9888;'; 
        } else if (action === 'refresh') {
            modalIcon.innerHTML = '&#10227;'; 
        
        } else if (action === 'schedule') {
            modalIcon.innerHTML = '&#128197;'; 
            const today = new Date().toISOString().split('T')[0];
            modalScheduleDate.setAttribute('min', today);
            if (!modalScheduleDate.value) {
                modalScheduleDate.value = today;
            }
            scheduleInputs.style.display = 'block';
        }
        modalProList.innerHTML = ''; 
        currentPros.forEach(pro => {
            const li = document.createElement('li');
            li.textContent = pro;
            modalProList.appendChild(li);
        });

        // Tampilkan modal
        bulkActionModal.style.display = 'flex';
    }

    function closeConfirmationModal() {
        bulkActionModal.style.display = 'none';
        // Bersihkan state
        currentAction = null;
        currentPros = [];
        modalProList.innerHTML = '';
    }

    modalBtnBatal.addEventListener('click', closeConfirmationModal);
    bulkActionModal.addEventListener('click', function(event) {
        if (event.target === bulkActionModal) {
            closeConfirmationModal();
        }
    });

    editComponentBtn.addEventListener('click',function(){
        console.log('Button Edit Component Berhasil diaktifkan');
        Swal.fire({
            title: 'Maintenance!',
            text: 'Fitur masih dalam tahap pengembangan',
            icon: 'warning'
        })
    });

    showStockBtn.addEventListener('click', function(){
        console.log('Button Edit Component Berhasil diaktifkan');
        Swal.fire({
            title: 'Maintenance!',
            text: 'Fitur masih dalam tahap pengembangan',
            icon: 'warning'
        })
    });

    modalBtnLanjutkan.addEventListener('click', function() {
        const sapUser = document.querySelector('meta[name="sap-username"]').getAttribute('content');
        const sapPass = document.querySelector('meta[name="sap-password"]').getAttribute('content');
        const prosToRefresh = getSelectedPros(); 
        const kodeHalaman = document.getElementById('kode-halaman').value;
        if (!currentAction || currentPros.length === 0) {
            console.error('Tidak ada aksi atau PRO yang dipilih saat konfirmasi.');
            closeConfirmationModal();
            return;
        }

        switch (currentAction) {
            case 'refresh':
                if (!sapUser || !sapPass) {
                    Swal.fire('Error', 'Kredensial SAP tidak ditemukan di halaman. (Cek tag meta)', 'error');
                    return; 
                }
                if (!kodeHalaman) {
                    Swal.fire('Error', 'Kode halaman (WERKS) tidak ditemukan.', 'error');
                    return;
                }
                if (prosToRefresh.length === 0) {
                    Swal.fire('Error', 'Tidak ada PRO yang dipilih.', 'error');
                    return;
                }
                
                closeConfirmationModal();
                
                Swal.fire({
                    title: '1/2: Mengambil dari SAP...',
                    text: 'Harap tunggu, sedang memproses data...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading(); 
                    }
                });

                fetch('http://192.168.90.27:4002/api/bulk-refresh', { 
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-SAP-Username': sapUser,
                        'X-SAP-Password': sapPass
                    },
                    body: JSON.stringify({
                        kode: kodeHalaman,
                        pros: prosToRefresh 
                    })
                })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(err => { 
                            throw new Error(err.message || `Error ${response.status}: Gagal mengambil data SAP.`); 
                        });
                    }
                    return response.json();
                })
                .then(sapData => {
                    Swal.update({
                        title: '2/2: Menyimpan ke Database...'
                    });
                    
                    if (!sapData.aggregated_data) {
                        throw new Error('Data "aggregated_data" tidak ada (null) dari SAP.');
                    }
                    if (sapData.sap_failed_details && sapData.sap_failed_details.length > 0) {
                        const failedPros = sapData.sap_failed_details.map(f => f.pro).join(', ');
                        throw new Error(`Gagal mengambil data SAP untuk PRO: ${failedPros}. Proses simpan dibatalkan.`);
                    }

                    // === PANGGILAN FETCH 2 (Menyimpan Data) ===
                    const saveDataPayload = { 
                        kode: kodeHalaman, 
                        pros_to_refresh: prosToRefresh,
                        aggregated_data: sapData.aggregated_data 
                    };
                    
                    return fetch('http://192.168.90.27:4002/api/save-data', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(saveDataPayload)
                    });
                })
                .then(saveResponse => {
                    if (!saveResponse.ok) {
                        return saveResponse.json().then(err => { 
                            throw new Error(err.message || `Error ${saveResponse.status}: Gagal menyimpan ke DB.`); 
                        });
                    }
                    return saveResponse.json();
                })
                .then(finalData => {
                    Swal.fire({
                        title: 'Sukses!',
                        text: 'Proses refresh selesai: ' + finalData.message,
                        icon: 'success'
                    }).then(() => {
                        window.location.reload();
                    });
                })
                .catch(error => {
                    Swal.fire({
                        title: 'Terjadi Kesalahan',
                        text: error.message,
                        icon: 'error'
                    }).then(() => {
                        window.location.reload(); 
                    });
                });
                break;
            
            case 'schedule':
                // --- 1. Ambil semua data yang dibutuhkan ---
                const scheduleDate = document.getElementById('modalScheduleDate').value;
                const scheduleTime = document.getElementById('modalScheduleTime').value;
                // --- 2. Validasi Input ---
                if (!scheduleDate || !scheduleTime) {
                    Swal.fire('Error', 'Silakan pilih tanggal dan waktu penjadwalan.', 'error');
                    return; 
                }
                if (!sapUser || !sapPass) {
                    Swal.fire('Error', 'Kredensial SAP tidak ditemukan (Cek tag meta).', 'error');
                    return;
                }
                if (!kodeHalaman || prosToRefresh.length === 0) {
                    Swal.fire('Error', 'Data Halaman (WERKS) atau PRO tidak ditemukan.', 'error');
                    return;
                }

                // --- 3. Tutup modal lama & Buka Modal Loading ---
                closeConfirmationModal(); // Tutup modal 'bulkActionModal' Anda
                
                Swal.fire({
                    title: '1/3: Mengirim Jadwal...',
                    text: 'Harap tunggu, memproses reschedule di SAP...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // === PANGGILAN FETCH 1 (Schedule) ===
                fetch('http://192.168.90.27:4002/api/bulk-schedule-pro', { 
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-SAP-Username': sapUser, // 
                        'X-SAP-Password': sapPass  // 
                    },
                    body: JSON.stringify({
                        pro_list: prosToRefresh, // <-- Menggunakan data PRO terbaru     
                        schedule_date: scheduleDate,  
                        schedule_time: scheduleTime + ":00"   
                    })
                })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(err => { 
                            throw new Error(err.message || 'Gagal melakukan reschedule.'); 
                        });
                    }
                    return response.json();
                })
                .then(scheduleData => {
                    console.log('Langkah 1 Sukses (Schedule):', scheduleData.message || 'OK');
                    Swal.update({
                        title: '2/3: Mengambil Data Baru...',
                        text: 'Jadwal berhasil, mengambil data terbaru dari SAP...'
                    });
                    return fetch('http://192.168.90.27:4002/api/bulk-refresh', { 
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-SAP-Username': sapUser,
                            'X-SAP-Password': sapPass
                        },
                        body: JSON.stringify({
                            kode: kodeHalaman,
                            pros: prosToRefresh
                        })
                    });
                })
                .then(refreshResponse => {
                    if (!refreshResponse.ok) {
                        return refreshResponse.json().then(err => { 
                            throw new Error(err.message || 'Gagal mengambil data SAP.'); 
                        });
                    }
                    return refreshResponse.json();
                })
                .then(sapData => {
                    console.log('Langkah 2 Sukses (Refresh):', sapData.message);
                    Swal.update({
                        title: '3/3: Menyimpan Data...',
                        text: 'Data terbaru diterima, menyimpan ke database...'
                    });

                    if (sapData.sap_failed_details && sapData.sap_failed_details.length > 0) {
                        const failedPros = sapData.sap_failed_details.map(f => f.pro).join(', ');
                        throw new Error(`Gagal mengambil data SAP untuk PRO: ${failedPros}.`);
                    }
                    if (!sapData.aggregated_data) {
                        throw new Error('Tidak ada data (null) yang dikembalikan dari SAP.');
                    }

                    // === PANGGILAN FETCH 3 (Save) ===
                    const saveDataPayload = { 
                        kode: kodeHalaman, 
                        pros_to_refresh: prosToRefresh,
                        aggregated_data: sapData.aggregated_data 
                    };
                    
                    return fetch('http://192.168.90.27:4002/api/save-data', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(saveDataPayload)
                    });
                })
                .then(saveResponse => {
                    if (!saveResponse.ok) {
                        return saveResponse.json().then(err => { 
                            throw new Error(err.message || 'Gagal menyimpan ke DB.'); 
                        });
                    }
                    return saveResponse.json();
                })
                .then(finalData => {
                    // --- 6. Semua Langkah Sukses ---
                    console.log('Langkah 3 Sukses (Save):', finalData.message);
                    Swal.fire({
                        title: 'Sukses!',
                        text: 'PRO berhasil dijadwalkan ulang DAN data telah diperbarui.',
                        icon: 'success'
                    }).then(() => {
                        // Muat ulang halaman (dengan metode GET yang aman)
                        window.location.reload(); 
                    });
                })
                .catch(error => {
                    console.error('Error selama proses berantai:', error);
                    Swal.fire({
                        title: 'Terjadi Kesalahan',
                        text: error.message,
                        icon: 'error'
                    });
                });
                break;
            
            case 'readPp':
                console.log('PROs selected for bulk read PP:', currentPros);
                Swal.fire({
                    title: 'Maintenance!',
                    text: 'Fitur masih dalam tahap pengembangan',
                    icon: 'warning'
                })
                break;
            
            case 'changePv':
                console.log('PROs selected for bulk change PV:', currentPros);
                Swal.fire({
                    title: 'Maintenance!',
                    text: 'Fitur masih dalam tahap pengembangan',
                    icon: 'warning'
                })
                break;
                
            case 'changeQty':
                console.log('PROs selected for bulk change Qty:', currentPros);
                Swal.fire({
                    title: 'Maintenance!',
                    text: 'Fitur masih dalam tahap pengembangan',
                    icon: 'warning'
                })
                break;
            
            case 'teco':
                console.log('PROs selected for bulk TECO:', currentPros);
                Swal.fire({
                    title: 'Maintenance!',
                    text: 'Fitur masih dalam tahap pengembangan',
                    icon: 'warning'
                })
                break;
            
            default:
                console.warn('Aksi tidak dikenal:', currentAction);
        }

        // Tutup modal setelah selesai
        closeConfirmationModal();
    });

    // ==================== Listener Tombol Aksi Bulk ====================
    // Semua tombol ini sekarang hanya memanggil openConfirmationModal

    refreshBtn.addEventListener('click', function() {
        const selectedPros = getSelectedPros();
        if (selectedPros.length > 0) {
            openConfirmationModal(
                'refresh',
                'Konfirmasi Refresh PRO',
                'Apakah Anda yakin ingin me-refresh PRO berikut?',
                selectedPros
            );
        } else {
            alert('Tidak ada PRO yang dipilih.');
        }
    });

    scheduleBtn.addEventListener('click', function() {
        const selectedPros = getSelectedPros();
        if (selectedPros.length > 0) {
            openConfirmationModal(
                'schedule',
                'Konfirmasi Reschedule PRO',
                'Apakah Anda yakin ingin me-reschedule PRO berikut?',
                selectedPros
            );
        } else {
            alert('Tidak ada PRO yang dipilih.');
        }
    });

    readPpBtn.addEventListener('click', function() {
        const selectedPros = getSelectedPros();
        if (selectedPros.length > 0) {
            openConfirmationModal(
                'readPp',
                'Konfirmasi Baca PP',
                'Apakah Anda yakin ingin membaca PP untuk PRO berikut?',
                selectedPros
            );
        }
    });

    changePv.addEventListener('click', function() {
        const selectedPros = getSelectedPros();
        if (selectedPros.length > 0) {
            openConfirmationModal(
                'changePv',
                'Konfirmasi Ubah PV',
                'Apakah Anda yakin ingin mengubah PV untuk PRO berikut?',
                selectedPros
            );
        }
    });
    
    changeQty.addEventListener('click', function() {
        const selectedPros = getSelectedPros();
        if (selectedPros.length > 0) {
            openConfirmationModal(
                'changeQty',
                'Konfirmasi Ubah Kuantitas',
                'Apakah Anda yakin ingin mengubah kuantitas untuk PRO berikut?',
                selectedPros
            );
        }
    });

    tecoBtn.addEventListener('click', function() {
        const selectedPros = getSelectedPros();
        if (selectedPros.length > 0) {
            openConfirmationModal(
                'teco',
                'Konfirmasi TECO',
                'PERHATIAN: Aksi TECO tidak dapat dibatalkan. Lanjutkan?',
                selectedPros
            );
        }
    });

    // ==================== Inisialisasi Awal ====================
    updateSelectionState(); // Panggil saat halaman dimuat
});

document.body.addEventListener('click', function(event) {
    if (event.target.classList.contains('edit-component-btn')) {
        console.log('Button Edit Component Berhasil diaktifkan');
        Swal.fire({
            title: 'Maintenance!',
            text: 'Fitur masih dalam tahap pengembangan',
            icon: 'warning'
        });
    }
    if (event.target.classList.contains('show-stock-btn')) {
        console.log('Button Show Stock Berhasil diaktifkan');
        Swal.fire({
            title: 'Maintenance!',
            text: 'Fitur masih dalam tahap pengembangan',
            icon: 'warning'
        });
    }
});
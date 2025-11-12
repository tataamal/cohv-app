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

    modalBtnLanjutkan.addEventListener('click', function() {
        if (!currentAction || currentPros.length === 0) {
            console.error('Tidak ada aksi atau PRO yang dipilih saat konfirmasi.');
            closeConfirmationModal();
            return;
        }

        switch (currentAction) {
            case 'refresh':
                const prosToRefresh = getSelectedPros(); 
                const sapUser = document.querySelector('meta[name="sap-username"]').getAttribute('content');
                const sapPass = document.querySelector('meta[name="sap-password"]').getAttribute('content');
                const kodeHalaman = document.getElementById('kode-halaman').value;

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

                const scheduleDate = document.getElementById('modalScheduleDate').value;
                const scheduleTime = document.getElementById('modalScheduleTime').value;

                // 2. Validasi
                if (!scheduleDate || !scheduleTime) {
                    Swal.fire('Error', 'Silakan pilih tanggal dan waktu penjadwalan.', 'error');
                    return; 
                }

                modalBtnLanjutkan.disabled = true;
                modalBtnLanjutkan.textContent = 'Menjadwalkan...';
                fetch('http://192.168.90.27:4002/api/bulk-schedule-pro', { 
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-SAP-Username': sapUser, 
                        'X-SAP-Password': sapPass  
                    },
                    body: JSON.stringify({
                        pro_list: currentPros,        
                        schedule_date: scheduleDate,  
                        schedule_time: scheduleTime   
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
                .then(data => {
                    // Tampilkan pesan sukses
                    const successCount = data.success_details ? data.success_details.length : 0;
                    Swal.fire({
                        title: 'Sukses!',
                        text: `Berhasil menjadwalkan ulang ${successCount} PRO.`,
                        icon: 'success'
                    }).then(() => window.location.reload());
                })
                .catch(error => {
                    // Tampilkan pesan error
                    Swal.fire('Terjadi Kesalahan', error.message, 'error');
                })
                .finally(() => {
                    modalBtnLanjutkan.disabled = false;
                    modalBtnLanjutkan.textContent = 'Lanjutkan';
                    closeConfirmationModal();
                });

                break;
            
            case 'readPp':
                console.log('PROs selected for bulk read PP:', currentPros);
                alert('PROs yang dipilih untuk baca PP (lihat console): ' + currentPros.join(', '));
                // TODO: Tambahkan logika AJAX/fetch untuk read PP di sini
                break;
            
            case 'changePv':
                console.log('PROs selected for bulk change PV:', currentPros);
                alert('PROs yang dipilih untuk ubah PV (lihat console): ' + currentPros.join(', '));
                // TODO: Tambahkan logika AJAX/fetch untuk change PV di sini
                break;
                
            case 'changeQty':
                console.log('PROs selected for bulk change Qty:', currentPros);
                alert('PROs yang dipilih untuk ubah Qty (lihat console): ' + currentPros.join(', '));
                // TODO: Tambahkan logika AJAX/fetch untuk change Qty di sini
                break;
            
            case 'teco':
                console.log('PROs selected for bulk TECO:', currentPros);
                alert('PROs yang dipilih untuk TECO (lihat console): ' + currentPros.join(', '));
                // TODO: Tambahkan logika AJAX/fetch untuk TECO di sini
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
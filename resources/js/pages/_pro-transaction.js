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
    const releaseBtn = document.getElementById('bulkReleaseBtn');
    
    const allBulkButtons = [scheduleBtn, refreshBtn, changePv, readPpBtn, changeQty, tecoBtn, releaseBtn];

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
    const changePvInputs = document.getElementById('changePvInputs');
    const modalChangePv = document.getElementById('changePvInput');

    // State untuk menyimpan aksi dan data yang sedang diproses
    let currentAction = null;
    let currentPros = [];

    // Cek elemen penting
    // Jika selectAll tidak ada, asumsikan kita TIDAK berada di halaman transaction, jadi return diam-diam (clean console).
    if (!selectAll) return;

    // Jika selectAll ada TAPI elemen lain (modal/tombol) hilang, baru warn (mungkin broken HTML).
    if (!bulkActionModal || allBulkButtons.some(btn => !btn)) {
        console.warn('Elemen UI Bulk Transaction tidak lengkap (tombol/modal hilang). Fungsionalitas bulk mungkin terbatas.');
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

    function openConfirmationModal(action, title, description, pros) {
        currentAction = action;
        currentPros = pros;

        modalTitle.textContent = title;
        modalDescription.textContent = description;
        modalHeader.classList.remove('warning');
        scheduleInputs.style.display = 'none';
        changePvInputs.style.display = 'none';
        modalProList.innerHTML = '';
        modalIcon.innerHTML = '&#9776;';

        if (action === 'teco') {
            modalHeader.classList.add('warning');
            modalIcon.innerHTML = '&#9888;'; // Ikon warning
        
        } else if (action === 'refresh') {
            modalIcon.innerHTML = '&#10227;'; // Ikon refresh
        
        } else if (action === 'schedule') {
            modalIcon.innerHTML = '&#128197;'; // Ikon kalender
            const today = new Date().toISOString().split('T')[0];
            modalScheduleDate.setAttribute('min', today);
            if (!modalScheduleDate.value) {
                modalScheduleDate.value = today;
            }
            scheduleInputs.style.display = 'block';
        
        } else if (action === 'changePv') {
            modalIcon.innerHTML = '&#9888;'; // Ikon warning
            changePvInputs.style.display = 'block';
            modalChangePv.value = '';
        
        } else if (action === 'changeQuantity') {
            modalIcon.innerHTML = '&#9998;'; // Ikon pensil
        }
        
        if (action === 'changeQuantity') {
            currentPros.forEach(pro => {
                const li = document.createElement('li');
                li.className = 'd-flex justify-content-between align-items-center mb-2'; 
                const label = document.createElement('label');
                label.textContent = `${pro.aufnr}:`;
                label.className = 'form-label me-3 fw-bold'; // me-3 = margin-right: 3
                label.htmlFor = `qty-${pro.aufnr}`; // Untuk aksesibilitas
                const input = document.createElement('input');
                input.type = 'number';
                input.value = pro.psmng; // <-- Ini default value dari $pro->PSMNG
                input.id = `qty-${pro.aufnr}`;
                input.className = 'form-control modal-qty-input'; // Class untuk mengambil data nanti
                input.style.width = '120px'; // Batasi lebar input
                input.setAttribute('data-aufnr', pro.aufnr); // Simpan AUFNR di data attribute
                li.appendChild(label);
                li.appendChild(input);
                modalProList.appendChild(li);
            });

        } else {
            currentPros.forEach(pro => {
                const li = document.createElement('li');
                li.textContent = pro;
                modalProList.appendChild(li);
            });
        }

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
        const sapUser = document.querySelector('meta[name="sap-username"]').getAttribute('content');
        const sapPass = document.querySelector('meta[name="sap-password"]').getAttribute('content');
        const prosToRefresh = getSelectedPros();
        const prosToProcess = getSelectedPros();
        const kodeHalaman = document.getElementById('kode-halaman').value;
        const ordersToSubmit = [];
        const actionToRun = currentAction;

        if (!actionToRun) {
            console.error('Tidak ada aksi.');
            closeConfirmationModal();
            return;
        }

        if (actionToRun === 'changeQuantity') {
            const qtyInputs = modalProList.querySelectorAll('.modal-qty-input');
            
            let hasError = false; 
            qtyInputs.forEach(input => {
                if (hasError) return; 

                const aufnr = input.dataset.aufnr; 
                const newQuantity = input.value;   

                if (!newQuantity || parseFloat(newQuantity) < 0) {
                    Swal.fire(
                        'Input Tidak Valid',
                        `Kuantitas untuk PRO ${aufnr} tidak boleh kosong atau negatif.`,
                        'warning'
                    );
                    hasError = true; 
                }
                
                ordersToSubmit.push({
                    AUFNR: aufnr,
                    QUANTITY: newQuantity
                });
            });

            if (hasError) {
                return; 
            }
        }
        closeConfirmationModal();

        function showSuccessToast(title) {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: title,
                showConfirmButton: false,
                timer: 3500,
                timerProgressBar: true
            });
            setTimeout(() => {
                window.location.reload();
            }, 3600);
        }

        // Gunakan variabel LOKAL
        switch (actionToRun) {
            
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
                
                Swal.fire({
                    title: '1/2: Mengambil dari SAP...',
                    text: 'Harap tunggu, sedang memproses data...',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });

                fetch('/api/bulk/bulk-refresh', { 
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json', 
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-SAP-Username': sapUser, 
                        'X-SAP-Password': sapPass,
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ plant: kodeHalaman, pros: prosToRefresh })
                })
                .then(response => {
                    if (!response.ok) return response.json().then(err => { throw new Error(err.message || `Error ${response.status}`); });
                    return response.json();
                })
                .then(sapData => {
                    Swal.update({ title: '2/2: Menyimpan ke Database...' });
                    const saveDataPayload = { kode: kodeHalaman, pros_to_refresh: prosToRefresh, aggregated_data: [] };
                    return fetch('/api/bulk/save-data', {
                        method: 'POST',
                        headers: { 
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify(saveDataPayload)
                    });
                })
                .then(saveResponse => {
                    if (!saveResponse.ok) {
                        return saveResponse.text().then(text => {
                            throw new Error(`Save failed (${saveResponse.status}): ${text.substring(0, 100)}`);
                        });
                    }
                    return saveResponse.json();
                })
                .then(finalData => {
                    Swal.close();
                    showSuccessToast('Sukses! Data PRO telah di-refresh.');
                })
                .catch(error => {
                    Swal.fire({ title: 'Terjadi Kesalahan', text: error.message, icon: 'error' })
                        .then(() => { window.location.reload(); });
                });
                break;
            
            case 'schedule':
                const scheduleDate = document.getElementById('modalScheduleDate').value;
                const scheduleTime = document.getElementById('modalScheduleTime').value;
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
                
                Swal.fire({
                    title: '1/3: Mengirim Jadwal...',
                    text: 'Harap tunggu, memproses reschedule di SAP...',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });

                fetch('/api/bulk/bulk-schedule-pro', { 
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json', 
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-SAP-Username': sapUser, 
                        'X-SAP-Password': sapPass,
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ pro_list: prosToRefresh, schedule_date: scheduleDate, schedule_time: scheduleTime + ":00" })
                })
                .then(response => {
                    if (!response.ok) {
                        return response.text().then(text => {
                            throw new Error(`Request failed (${response.status}): ${text.substring(0, 100)}`);
                        });
                    }
                    return response.json();
                })
                .then(scheduleData => {
                    Swal.update({ title: '2/3: Mengambil Data Baru...' });
                    return fetch('/api/bulk/bulk-refresh', { 
                        method: 'POST',
                        headers: { 
                        'Content-Type': 'application/json', 
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-SAP-Username': sapUser, 
                        'X-SAP-Password': sapPass,
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') 
                    },
                        body: JSON.stringify({ plant: kodeHalaman, pros: prosToRefresh })
                    });
                })
                .then(refreshResponse => {
                    if (!refreshResponse.ok) {
                        return refreshResponse.text().then(text => {
                            throw new Error(`Refresh failed (${refreshResponse.status}): ${text.substring(0, 100)}`);
                        });
                    }
                    return refreshResponse.json();
                })
                .then(sapData => {
                    Swal.update({ title: '3/3: Menyimpan Data...' });
                    if (sapData.sap_failed_details && sapData.sap_failed_details.length > 0) throw new Error('Gagal mengambil data SAP.');
                    // if (!sapData.aggregated_data) throw new Error('Tidak ada data (null) dari SAP.');

                    const saveDataPayload = { kode: kodeHalaman, pros_to_refresh: prosToRefresh, aggregated_data: [] };
                    return fetch('/api/bulk/save-data', {
                        method: 'POST',
                        headers: { 
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify(saveDataPayload)
                    });
                })
                .then(saveResponse => {
                    if (!saveResponse.ok) {
                        return saveResponse.text().then(text => {
                            throw new Error(`Save failed (${saveResponse.status}): ${text.substring(0, 100)}`);
                        });
                    }
                    return saveResponse.json();
                })
                .then(finalData => {
                    // --- PERUBAHAN DI SINI ---
                    console.log('Langkah 3 Sukses (Save):', finalData.message);
                    Swal.close();
                    showSuccessToast('Sukses! PRO telah dijadwalkan ulang.');
                })
                .catch(error => {
                    console.error('Error selama proses berantai:', error);
                    Swal.fire({ title: 'Terjadi Kesalahan', text: error.message, icon: 'error' });
                });
                break;
            
            case 'readPp': {

                if (!sapUser || !sapPass) {
                    Swal.fire('Error', 'Kredensial SAP tidak ditemukan (Cek tag meta).', 'error');
                    return; 
                }
                if (!kodeHalaman) {
                    Swal.fire('Error', 'Kode halaman (WERKS) tidak ditemukan.', 'error');
                    return;
                }
                if (prosToProcess.length === 0) {
                    Swal.fire('Error', 'Tidak ada PRO yang dipilih.', 'error');
                    return; 
                }

                Swal.fire({
                    title: '1/3: Membaca Master Data PP...',
                    text: `Harap tunggu, sedang memproses ${prosToProcess.length} PRO...`,
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });

                fetch('/api/bulk/bulk-readpp-pro', { 
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json', 
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-SAP-Username': sapUser, 
                        'X-SAP-Password': sapPass,
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ pro_list: prosToProcess })
                })
                .then(response => {
                    if (!response.ok) {
                        return response.text().then(text => {
                            throw new Error(`Read PP failed (${response.status}): ${text.substring(0, 100)}`);
                        });
                    }
                    return response.json();
                })
                .then(readPpData => {
                    if (readPpData.error_details && readPpData.error_details.length > 0) throw new Error(`Read PP gagal untuk ${readPpData.error_details.length} PRO.`);
                    Swal.update({ title: '2/3: Mengambil Data Baru...' });
                    
                    return fetch('/api/bulk/bulk-refresh', { 
                        method: 'POST',
                        headers: { 
                        'Content-Type': 'application/json', 
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-SAP-Username': sapUser, 
                        'X-SAP-Password': sapPass,
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                        body: JSON.stringify({ plant: kodeHalaman, pros: prosToProcess })
                    });
                })
                .then(refreshResponse => {
                    if (!refreshResponse.ok) {
                        return refreshResponse.text().then(text => {
                            throw new Error(`Refresh failed (${refreshResponse.status}): ${text.substring(0, 100)}`);
                        });
                    }
                    return refreshResponse.json();
                })
                .then(sapData => {
                    Swal.update({ title: '3/3: Menyimpan Data...' });
                    if (sapData.sap_failed_details && sapData.sap_failed_details.length > 0) throw new Error('Gagal mengambil data SAP.');
                    // if (!sapData.aggregated_data) throw new Error('Tidak ada data (null) dari SAP.');

                    const saveDataPayload = { kode: kodeHalaman, pros_to_refresh: prosToProcess, aggregated_data: [] };
                    return fetch('/api/bulk/save-data', {
                        method: 'POST',
                        headers: { 
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify(saveDataPayload)
                    });
                })
                .then(saveResponse => {
                    if (!saveResponse.ok) {
                        return saveResponse.text().then(text => { 
                            throw new Error(`Save failed (${saveResponse.status}): ${text.substring(0, 100)}`); 
                        });
                    }
                    return saveResponse.json();
                })
                .then(finalData => {
                    // --- PERUBAHAN DI SINI ---
                    console.log('Langkah 3 Sukses (Save):', finalData.message);
                    Swal.close();
                    showSuccessToast('Sukses! Read PP dan refresh data selesai.');
                })
                .catch(error => {
                    console.error('Error selama proses berantai:', error);
                    Swal.fire({ title: 'Terjadi Kesalahan', text: error.message, icon: 'error' });
                });
                break; 
            }
            
            case 'changePv': {
                const selectedPv = modalChangePv.value; 
                if (!selectedPv) {
                    Swal.fire('Error', 'Silakan pilih Production Version terlebih dahulu.', 'error');
                    return; 
                }
                if (!sapUser || !sapPass) {
                    Swal.fire('Error', 'Kredensial SAP tidak ditemukan (Cek tag meta).', 'error');
                    return;
                }
                if (!kodeHalaman) {
                    Swal.fire('Error', 'Kode halaman (WERKS) tidak ditemukan.', 'error');
                    return;
                }
                if (prosToProcess.length === 0) {
                    Swal.fire('Error', 'Tidak ada PRO yang dipilih.', 'error');
                    return;
                }

                Swal.fire({
                    title: '1/3: Mengubah Production Version...',
                    text: `Harap tunggu, sedang memproses ${prosToProcess.length} PRO...`,
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });

                fetch('/api/bulk/bulk-change-pv', { 
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json', 
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-SAP-Username': sapUser, 
                        'X-SAP-Password': sapPass,
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ pro_list: prosToProcess, PROD_VERSION: selectedPv })
                })
                .then(response => {
                    if (!response.ok) {
                        return response.text().then(text => { 
                            throw new Error(`Change PV failed (${response.status}): ${text.substring(0, 100)}`); 
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.error_details && data.error_details.length > 0) {
                        // ... (Logika error parsial Anda sudah benar) ...
                        throw new Error('Proses dihentikan karena ada error parsial.');
                    }
                    Swal.update({ title: '2/3: Mengambil Data Baru...' });
                    
                    return fetch('/api/bulk/bulk-refresh', { 
                        method: 'POST',
                        headers: { 
                        'Content-Type': 'application/json', 
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-SAP-Username': sapUser, 
                        'X-SAP-Password': sapPass,
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                        body: JSON.stringify({ plant: kodeHalaman, pros: prosToProcess })
                    });
                })
                .then(refreshResponse => {
                    if (!refreshResponse) return;
                    if (!refreshResponse.ok) {
                        return refreshResponse.text().then(text => {
                            throw new Error(`Refresh failed (${refreshResponse.status}): ${text.substring(0, 100)}`);
                        });
                    }
                    return refreshResponse.json();
                })
                .then(sapData => {
                    if (!sapData) return;
                    Swal.update({ title: '3/3: Menyimpan Data...' });
                    if (sapData.sap_failed_details && sapData.sap_failed_details.length > 0) throw new Error('Gagal mengambil data SAP.');
                    // if (!sapData.aggregated_data) throw new Error('Tidak ada data (null) dari SAP.');

                    const saveDataPayload = { kode: kodeHalaman, pros_to_refresh: prosToProcess, aggregated_data: [] };
                    return fetch('/api/bulk/save-data', {
                        method: 'POST',
                        headers: { 
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify(saveDataPayload)
                    });
                })
                .then(saveResponse => {
                    if (!saveResponse) return;
                    if (!saveResponse.ok) {
                        return saveResponse.text().then(text => {
                            throw new Error(`Save failed (${saveResponse.status}): ${text.substring(0, 100)}`);
                        });
                    }
                    return saveResponse.json();
                })
                .then(finalData => {
                    if (!finalData) return; 
                    // --- PERUBAHAN DI SINI ---
                    console.log('Langkah 3 Sukses (Save):', finalData.message);
                    Swal.close();
                    showSuccessToast('Sukses! Ubah PV, refresh, dan simpan data selesai.');
                })
                .catch(error => {
                    console.error('Error selama proses berantai:', error);
                    if (error.message !== 'Proses dihentikan karena ada error parsial.') {
                        Swal.fire({ title: 'Terjadi Kesalahan', text: error.message, icon: 'error' });
                    }
                });
                break; 
            }
            
            case 'changeQuantity': { 
                if (ordersToSubmit.length === 0) {
                    Swal.fire('Tidak Ada Data', 'Tidak ada PRO yang valid untuk diproses.', 'info');
                    break;
                }
                console.log('PROs selected for bulk change Qty:', ordersToSubmit);

                const totalOrders = ordersToSubmit.length;
                let processedCount = 0;

                Swal.fire({
                    title: '1/3: Memproses Perubahan Kuantitas...',
                    html: `
                        <p>Memproses ${totalOrders} PRO...</p>
                        <div class="progress" style="height: 20px; margin-top: 10px;">
                            <div id="swal-progress-bar" 
                                 class="progress-bar progress-bar-striped progress-bar-animated" 
                                 role="progressbar" 
                                 style="width: 0%" 
                                 aria-valuenow="0" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100">
                            </div>
                        </div>
                        <p id="swal-progress-text" style="margin-top: 5px; font-weight: bold;">0 / ${totalOrders}</p>
                        <div id="swal-progress-status" style="text-align: left; font-size: 0.8em; margin-top: 10px; height: 20px; overflow: hidden;">Memulai...</div>
                    `,
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading(); 
                    }
                });

                const handleProgress = (data) => {
                    processedCount++;
                    const percentage = Math.round((processedCount / totalOrders) * 100);
                    
                    const progressBar = document.getElementById('swal-progress-bar');
                    const progressText = document.getElementById('swal-progress-text');
                    const progressStatus = document.getElementById('swal-progress-status');

                    if (progressBar) {
                        progressBar.style.width = percentage + '%';
                        progressBar.setAttribute('aria-valuenow', percentage);
                    }
                    if (progressText) {
                        progressText.textContent = `${processedCount} / ${totalOrders}`;
                    }
                    if (progressStatus) {
                        const statusIcon = data.status === 'success' ? '✅' : '❌';
                        progressStatus.innerHTML = `${statusIcon} ${data.AUFNR}: ${data.message}`;
                    }
                    console.log(data); // Tetap log di console
                };

                const handleComplete = () => {
                    Swal.update({
                        title: '2/3: Mengambil Data Baru...',
                        html: 'Perubahan kuantitas selesai. Mengambil data terbaru dari SAP...',
                    });

                    const prosToRefresh = ordersToSubmit.map(o => o.AUFNR);

                   fetch('/api/bulk/bulk-refresh', { 
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-SAP-Username': sapUser, 
                            'X-SAP-Password': sapPass,
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            plant: kodeHalaman,
                            pros: prosToRefresh 
                        })
                    })

                    .then(response => {
                        if (!response.ok) {
                             return response.text().then(text => {
                                throw new Error(`Refresh post-change failed (${response.status}): ${text.substring(0, 100)}`);
                            });
                        }
                        return response.json();
                    })
                    
                    .then(sapData => {
                        Swal.update({ title: '3/3: Menyimpan ke Database...' });

                        const saveDataPayload = { 
                            kode: kodeHalaman, 
                            pros_to_refresh: prosToRefresh,
                            aggregated_data: [] 
                        };
                        
                        return fetch('/api/bulk/save-data', {
                            method: 'POST',
                            headers: { 
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify(saveDataPayload)
                        });
                    })
                    
                    .then(saveResponse => {
                        if (!saveResponse.ok) {
                             return saveResponse.text().then(text => {
                                throw new Error(`Save failed (${saveResponse.status})`);
                            });
                        }
                        return saveResponse.json();
                    })

                    .then(finalData => {
                        Swal.close(); 
                        showSuccessToast('Sukses! Data telah diubah dan diperbarui.');
                    })
                    .catch(error => {
                        Swal.fire({
                            title: 'Error saat Refresh Data',
                            text: `Perubahan kuantitas di SAP berhasil, TAPI gagal me-refresh/menyimpan data lokal: ${error.message}`,
                            icon: 'warning'
                        }).then(() => {
                            window.location.reload(); 
                        });
                    });
                };

                const handleError = (error) => {
                    Swal.fire('Error Fatal Streaming', `Terjadi kesalahan saat streaming: ${error.message}`, 'error');
                };

                callChangeQuantityStream(ordersToSubmit, handleProgress, handleComplete, handleError);
                break;
            } 
            
            case 'teco':
                if (prosToProcess.length === 0) {
                    Swal.fire('Error', 'Tidak ada PRO yang dipilih untuk TECO.', 'error');
                    return;
                }
                if (!sapUser || !sapPass) {
                    Swal.fire('Error', 'Kredensial SAP tidak ditemukan.', 'error');
                    return;
                }

                const totalTecoOrders = prosToProcess.length;
                let processedTecoCount = 0;
                let successfulTecos = [];

                Swal.fire({
                    title: '1/2: Memproses TECO di SAP...',
                    html: `
                        <p>Memproses ${totalTecoOrders} PRO...</p>
                        <div class="progress" style="height: 20px; margin-top: 10px;">
                            <div id="swal-progress-bar" 
                                 class="progress-bar progress-bar-striped progress-bar-animated" 
                                 role="progressbar" 
                                 style="width: 0%" 
                                 aria-valuenow="0" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100">
                            </div>
                        </div>
                        <p id="swal-progress-text" style="margin-top: 5px; font-weight: bold;">0 / ${totalTecoOrders}</p>
                        <div id="swal-progress-status" style="text-align: left; font-size: 0.8em; margin-top: 10px; height: 40px; overflow-y: auto; border: 1px solid #eee; padding: 5px;">Memulai...</div>
                    `,
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                const handleTecoProgress = (data) => {
                    processedTecoCount++;
                    const percentage = Math.round((processedTecoCount / totalTecoOrders) * 100);

                    // Update UI Progress Bar
                    const progressBar = document.getElementById('swal-progress-bar');
                    const progressText = document.getElementById('swal-progress-text');
                    const progressStatus = document.getElementById('swal-progress-status');

                    if (progressBar) {
                        progressBar.style.width = percentage + '%';
                        progressBar.setAttribute('aria-valuenow', percentage);
                    }
                    if (progressText) {
                        progressText.textContent = `${processedTecoCount} / ${totalTecoOrders}`;
                    }
                    if (progressStatus) {
                        const statusIcon = data.status === 'success' ? '✅' : '❌';
                        // Prepend log terbaru agar muncul paling atas
                        progressStatus.innerHTML = `<div>${statusIcon} ${data.AUFNR}: ${data.message}</div>` + progressStatus.innerHTML;
                    }

                    if (data.status === 'success') {
                        successfulTecos.push(data.AUFNR);
                    }
                };

                const handleTecoComplete = () => {
                    if (successfulTecos.length === 0) {
                        Swal.fire('Gagal', 'Tidak ada PRO yang berhasil di-TECO. Database tidak diubah.', 'error')
                            .then(() => window.location.reload());
                        return;
                    }

                    Swal.update({
                        title: '2/2: Menghapus Data Lokal...',
                        html: `Berhasil TECO ${successfulTecos.length} PRO. Sedang menghapus data dari database MySQL...`,
                        icon: 'info'
                    });

                    fetch('/api/bulk/delete-data', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            pro_list: successfulTecos
                        })
                    })
                    .then(response => {
                        if (!response.ok) {
                            return response.text().then(text => {
                                throw new Error(`Delete failed (${response.status})`);
                            });
                        }
                        return response.json();
                    })
                    .then(deleteResult => {
                        Swal.close();
                        showSuccessToast(`Selesai! ${successfulTecos.length} PRO TECO & Dihapus.`);
                    })
                    .catch(error => {
                        Swal.fire({
                            title: 'Warning',
                            text: `TECO Sukses di SAP, tetapi gagal menghapus data lokal: ${error.message}`,
                            icon: 'warning'
                        }).then(() => window.location.reload());
                    });
                };

                const handleTecoError = (error) => {
                    Swal.fire('Error Fatal', `Terjadi kesalahan saat streaming TECO: ${error.message}`, 'error');
                };

                callTecoStream(prosToProcess, handleTecoProgress, handleTecoComplete, handleTecoError);
                break;
            
            default:
                console.warn('Aksi tidak dikenal:', actionToRun);
        }
    });

    // ==================== Listener Tombol Aksi Bulk ====================

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
        onButtonChangeQuantityClick();
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

    // ==================== Fungsi Helper ====================

    function onButtonChangeQuantityClick() {
        const selectedCheckboxes = document.querySelectorAll('.pro-checkbox:checked');
        const proDataForModal = [];
        
        selectedCheckboxes.forEach(checkbox => {
            const row = checkbox.closest('.accordion-item'); 
            if (!row || !row.dataset.aufnr || typeof row.dataset.psmng === 'undefined') {
                console.error('Checkbox dicentang, tapi .accordion-item atau data-aufnr/data-psmng tidak ditemukan.', checkbox);
                return; 
            }

            const aufnr = row.dataset.aufnr; 
            const psmng = row.dataset.psmng;

            proDataForModal.push({ 
                aufnr: aufnr, 
                psmng: psmng 
            });
        });

        if (proDataForModal.length === 0) {
            if (document.querySelectorAll('.pro-checkbox:checked').length > 0) {
                alert('Gagal mengambil data PRO. Pastikan .accordion-item memiliki data-aufnr dan data-psmng.');
            } else {
                alert('Pilih setidaknya satu PRO.');
            }
            return;
        }
        
        openConfirmationModal(
            'changeQuantity', // Pastikan konsisten
            'Ubah Kuantitas Produksi',
            'Masukkan kuantitas baru untuk PRO yang dipilih:',
            proDataForModal 
        );
    }

    // --- Fungsi Fetch Streaming (Penting) ---
    async function callChangeQuantityStream(orders, onProgress, onComplete, onError) {
        const payload = {
            orders: orders
        };

        try {
            const response = await fetch('/api/bulk/change_quantity', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-SAP-Username': document.querySelector('meta[name="sap-username"]').getAttribute('content'), 
                'X-SAP-Password': document.querySelector('meta[name="sap-password"]').getAttribute('content')
            },
            body: JSON.stringify(payload)
            });

            if (!response.ok) {
                // Tangani error HTTP non-streaming
                const errorData = await response.json();
                throw new Error(errorData.error || `HTTP error! status: ${response.status}`);
            }

            const reader = response.body.getReader();
            const decoder = new TextDecoder();
            let buffer = '';

            while (true) {
                const { value, done } = await reader.read();
                if (done) break;

                buffer += decoder.decode(value, { stream: true });
                const lines = buffer.split('\n');
                buffer = lines.pop(); 

                for (const line of lines) {
                    if (line.trim() === '') continue;
                    try {
                        const progressData = JSON.parse(line);
                        if (progressData.status === 'fatal_error') {
                            throw new Error(progressData.message);
                        }
                        if (onProgress) {
                            onProgress(progressData); 
                        }
                    } catch (parseError) {
                        console.error('Gagal parsing JSON per baris:', line, parseError);
                    }
                }
            }

            if (onComplete) {
                onComplete(); 
            }

        } catch (error) {
            console.error('Terjadi error saat fetch stream:', error);
            if (onError) {
                onError(error); 
            }
        }
    }

    async function callTecoStream(proList, onProgress, onComplete, onError) {
        const sapUser = document.querySelector('meta[name="sap-username"]').getAttribute('content');
        const sapPass = document.querySelector('meta[name="sap-password"]').getAttribute('content');

        try {
            const response = await fetch('/api/bulk/stream_teco_orders', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-SAP-Username': sapUser, 
                    'X-SAP-Password': sapPass
                },
                body: JSON.stringify({
                    pro_list: proList
                })
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.error || `HTTP error! status: ${response.status}`);
            }

            const reader = response.body.getReader();
            const decoder = new TextDecoder();
            let buffer = '';

            while (true) {
                const { value, done } = await reader.read();
                if (done) break;

                buffer += decoder.decode(value, { stream: true });
                const lines = buffer.split('\n');
                
                // Simpan sisa buffer yang belum lengkap (baris terakhir yg tidak ada \n)
                buffer = lines.pop(); 

                for (const line of lines) {
                    if (line.trim() === '') continue;
                    try {
                        const progressData = JSON.parse(line);
                        
                        // Cek error fatal dari backend stream
                        if (progressData.status === 'fatal_error') {
                            throw new Error(progressData.message);
                        }

                        if (onProgress) {
                            onProgress(progressData); 
                        }
                    } catch (parseError) {
                        console.error('Gagal parsing JSON stream line:', line, parseError);
                    }
                }
            }

            // Selesai streaming
            if (onComplete) {
                onComplete(); 
            }

        } catch (error) {
            console.error('Terjadi error saat TECO stream:', error);
            if (onError) {
                onError(error); 
            }
        }
    }

    // ==================== Inisialisasi Awal ====================
    updateSelectionState(); // Panggil saat halaman dimuat
});

// Listener di luar DOMContentLoaded (jika ada)
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

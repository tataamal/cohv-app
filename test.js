
    modalBtnLanjutkan.addEventListener('click', function() {
        const sapUser = document.querySelector('meta[name="sap-username"]').getAttribute('content');
        const sapPass = document.querySelector('meta[name="sap-password"]').getAttribute('content');
        const prosToRefresh = getSelectedPros();
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

                fetch('http://192.168.90.27:4002/api/bulk-refresh', { 
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-SAP-Username': sapUser, 'X-SAP-Password': sapPass },
                    body: JSON.stringify({ kode: kodeHalaman, pros: prosToRefresh })
                })
                .then(response => {
                    if (!response.ok) return response.json().then(err => { throw new Error(err.message || `Error ${response.status}`); });
                    return response.json();
                })
                .then(sapData => {
                    Swal.update({ title: '2/2: Menyimpan ke Database...' });
                    if (!sapData.aggregated_data) throw new Error('Data "aggregated_data" tidak ada (null) dari SAP.');
                    if (sapData.sap_failed_details && sapData.sap_failed_details.length > 0) throw new Error('Gagal mengambil data SAP untuk beberapa PRO.');

                    const saveDataPayload = { kode: kodeHalaman, pros_to_refresh: prosToRefresh, aggregated_data: sapData.aggregated_data };
                    return fetch('http://192.168.90.27:4002/api/save-data', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(saveDataPayload)
                    });
                })
                .then(saveResponse => {
                    if (!saveResponse.ok) return saveResponse.json().then(err => { throw new Error(err.message || 'Gagal menyimpan ke DB.'); });
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

                fetch('http://192.168.90.27:4002/api/bulk-schedule-pro', { 
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-SAP-Username': sapUser, 'X-SAP-Password': sapPass },
                    body: JSON.stringify({ pro_list: prosToRefresh, schedule_date: scheduleDate, schedule_time: scheduleTime + ":00" })
                })
                .then(response => {
                    if (!response.ok) return response.json().then(err => { throw new Error(err.message || 'Gagal melakukan reschedule.'); });
                    return response.json();
                })
                .then(scheduleData => {
                    Swal.update({ title: '2/3: Mengambil Data Baru...' });
                    return fetch('http://192.168.90.27:4002/api/bulk-refresh', { 
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-SAP-Username': sapUser, 'X-SAP-Password': sapPass },
                        body: JSON.stringify({ kode: kodeHalaman, pros: prosToRefresh })
                    });
                })
                .then(refreshResponse => {
                    if (!refreshResponse.ok) return refreshResponse.json().then(err => { throw new Error(err.message || 'Gagal mengambil data SAP.'); });
                    return refreshResponse.json();
                })
                .then(sapData => {
                    Swal.update({ title: '3/3: Menyimpan Data...' });
                    if (sapData.sap_failed_details && sapData.sap_failed_details.length > 0) throw new Error('Gagal mengambil data SAP.');
                    if (!sapData.aggregated_data) throw new Error('Tidak ada data (null) dari SAP.');

                    const saveDataPayload = { kode: kodeHalaman, pros_to_refresh: prosToRefresh, aggregated_data: sapData.aggregated_data };
                    return fetch('http://192.168.90.27:4002/api/save-data', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(saveDataPayload)
                    });
                })
                .then(saveResponse => {
                    if (!saveResponse.ok) return saveResponse.json().then(err => { throw new Error(err.message || 'Gagal menyimpan ke DB.'); });
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
                const prosToProcess = currentPros;
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

                fetch('http://192.168.90.27:4002/api/bulk-readpp-pro', { 
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-SAP-Username': sapUser, 'X-SAP-Password': sapPass },
                    body: JSON.stringify({ pro_list: prosToProcess })
                })
                .then(response => {
                    if (!response.ok) return response.json().then(err => { throw new Error(err.error || 'Gagal Read PP'); });
                    return response.json();
                })
                .then(readPpData => {
                    if (readPpData.error_details && readPpData.error_details.length > 0) throw new Error(`Read PP gagal untuk ${readPpData.error_details.length} PRO.`);
                    Swal.update({ title: '2/3: Mengambil Data Baru...' });
                    
                    return fetch('http://192.168.90.27:4002/api/bulk-refresh', { 
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-SAP-Username': sapUser, 'X-SAP-Password': sapPass },
                        body: JSON.stringify({ kode: kodeHalaman, pros: prosToProcess })
                    });
                })
                .then(refreshResponse => {
                    if (!refreshResponse.ok) return refreshResponse.json().then(err => { throw new Error(err.message || 'Gagal mengambil data SAP.'); });
                    return refreshResponse.json();
                })
                .then(sapData => {
                    Swal.update({ title: '3/3: Menyimpan Data...' });
                    if (sapData.sap_failed_details && sapData.sap_failed_details.length > 0) throw new Error('Gagal mengambil data SAP.');
                    if (!sapData.aggregated_data) throw new Error('Tidak ada data (null) dari SAP.');

                    const saveDataPayload = { kode: kodeHalaman, pros_to_refresh: prosToProcess, aggregated_data: sapData.aggregated_data };
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
                const prosToProcess = currentPros;
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

                fetch('http://192.168.90.27:4002/api/bulk-change-pv', { 
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-SAP-Username': sapUser, 'X-SAP-Password': sapPass },
                    body: JSON.stringify({ pro_list: prosToProcess, PROD_VERSION: selectedPv })
                })
                .then(response => {
                    if (!response.ok) return response.json().then(err => { throw new Error(err.error || 'Gagal Ubah PV'); });
                    return response.json();
                })
                .then(data => {
                    if (data.error_details && data.error_details.length > 0) {
                        // ... (Logika error parsial Anda sudah benar) ...
                        throw new Error('Proses dihentikan karena ada error parsial.');
                    }
                    Swal.update({ title: '2/3: Mengambil Data Baru...' });
                    
                    return fetch('http://192.168.90.27:4002/api/bulk-refresh', { 
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-SAP-Username': sapUser, 'X-SAP-Password': sapPass },
                        body: JSON.stringify({ kode: kodeHalaman, pros: prosToProcess })
                    });
                })
                .then(refreshResponse => {
                    if (!refreshResponse) return;
                    if (!refreshResponse.ok) return refreshResponse.json().then(err => { throw new Error(err.message || 'Gagal mengambil data SAP.'); });
                    return refreshResponse.json();
                })
                .then(sapData => {
                    if (!sapData) return;
                    Swal.update({ title: '3/3: Menyimpan Data...' });
                    if (sapData.sap_failed_details && sapData.sap_failed_details.length > 0) throw new Error('Gagal mengambil data SAP.');
                    if (!sapData.aggregated_data) throw new Error('Tidak ada data (null) dari SAP.');

                    const saveDataPayload = { kode: kodeHalaman, pros_to_refresh: prosToProcess, aggregated_data: sapData.aggregated_data };
                    return fetch('http://192.168.90.27:4002/api/save-data', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(saveDataPayload)
                    });
                })
                .then(saveResponse => {
                    if (!saveResponse) return;
                    if (!saveResponse.ok) return saveResponse.json().then(err => { throw new Error(err.message || 'Gagal menyimpan ke DB.'); });
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
                        if (!response.ok) return response.json().then(err => { throw new Error(err.message || 'Gagal refresh data SAP post-change.'); });
                        return response.json();
                    })
                    
                    .then(sapData => {
                        Swal.update({ title: '3/3: Menyimpan ke Database...' });

                        if (!sapData.aggregated_data) throw new Error('Data "aggregated_data" tidak ada (null) dari SAP.');
                        if (sapData.sap_failed_details && sapData.sap_failed_details.length > 0) throw new Error('Gagal refresh data SAP.');
                        
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
                        if (!saveResponse.ok) return saveResponse.json().then(err => { throw new Error(err.message || 'Gagal menyimpan ke DB.'); });
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
                console.log('PROs selected for bulk TECO:', currentPros);
                Swal.fire({
                    title: 'Maintenance!',
                    text: 'Fitur masih dalam tahap pengembangan',
                    icon: 'warning'
                })
                break;
            
            default:
                console.warn('Aksi tidak dikenal:', actionToRun);
        }
    });